const { getDb } = require('./db/mongodb');
const { ObjectId } = require('mongodb');

class Game {
  constructor(config = {}) {
    this._id = null;
    this.lobbyId = null;
    this.gameType = config.gameType || 'generic';
    this.maxPlayers = config.maxPlayers || 2;
    this.state = {
      board: config.initialBoard || null,
      currentTurn: config.startingPlayer || 'X',
      moves: [],
      winner: null,
      isDraw: false,
      isComplete: false,
      startedAt: null,
      endedAt: null
    };
    this.config = {
      turnTimeLimit: config.turnTimeLimit || null, // in seconds, null means no limit
      totalTimeLimit: config.totalTimeLimit || null, // in seconds, null means no limit
      winCondition: config.winCondition || null,
      boardSize: config.boardSize || null,
      customRules: config.customRules || {}
    };
    this.createdAt = new Date();
  }

  // Initialize a new game and link it to a lobby
  async initialize(lobbyId) {
    this.lobbyId = lobbyId;
    this.state.startedAt = new Date();
    return await this.save();
  }

  // Save the game to the database
  async save() {
    const db = getDb();
    const gamesCollection = db.collection('games');
    
    if (this._id) {
      // Update existing game
      await gamesCollection.updateOne(
        { _id: new ObjectId(this._id) },
        { $set: {
            state: this.state,
            lobbyId: this.lobbyId,
            config: this.config,
            updatedAt: new Date()
          } 
        }
      );
      return this._id;
    } else {
      // Create new game
      const result = await gamesCollection.insertOne(this);
      this._id = result.insertedId;
      return this._id;
    }
  }

  // Record a move in the game
  async makeMove(playerId, moveData) {
    // Validate the move (generic implementation)
    if (this.state.isComplete) {
      throw new Error('Game is already complete');
    }
    
    // Add the move to the history
    const move = {
      playerId,
      moveData,
      timestamp: new Date()
    };
    
    this.state.moves.push(move);
    
    // Update current turn (basic alternating turns)
    this.state.currentTurn = this.state.currentTurn === 'X' ? 'O' : 'X';
    
    // Save the updated state
    await this.save();
    
    return move;
  }

  // Check if the game is over
  async checkGameEnd() {
    // This is a placeholder - specific game implementations would override this
    // with their own win conditions
    return false;
  }

  // End the game with a winner or draw
  async endGame(winner = null, isDraw = false) {
    this.state.isComplete = true;
    this.state.endedAt = new Date();
    
    if (winner) {
      this.state.winner = winner;
    } else if (isDraw) {
      this.state.isDraw = true;
    }
    
    await this.save();
  }

  // Get the current state of the game
  getState() {
    return {
      ...this.state,
      gameType: this.gameType,
      config: this.config
    };
  }

  // Get summary info for this game
  getSummary() {
    return {
      id: this._id ? this._id.toString() : null,
      gameType: this.gameType,
      maxPlayers: this.maxPlayers,
      isComplete: this.state.isComplete,
      winner: this.state.winner,
      isDraw: this.state.isDraw,
      movesCount: this.state.moves.length,
      startedAt: this.state.startedAt,
      endedAt: this.state.endedAt
    };
  }

  // Static methods for finding and working with games

  // Find a game by ID
  static async findById(id) {
    const db = getDb();
    const gamesCollection = db.collection('games');
    
    const gameData = await gamesCollection.findOne({ _id: new ObjectId(id) });
    if (!gameData) return null;
    
    const game = new Game({
      gameType: gameData.gameType,
      maxPlayers: gameData.maxPlayers
    });
    
    game._id = gameData._id;
    game.lobbyId = gameData.lobbyId;
    game.state = gameData.state;
    game.config = gameData.config;
    game.createdAt = gameData.createdAt;
    
    return game;
  }

  // Find games for a specific lobby
  static async findByLobbyId(lobbyId) {
    const db = getDb();
    const gamesCollection = db.collection('games');
    
    const games = await gamesCollection.find({ lobbyId }).toArray();
    
    return games.map(gameData => {
      const game = new Game({
        gameType: gameData.gameType,
        maxPlayers: gameData.maxPlayers
      });
      
      game._id = gameData._id;
      game.lobbyId = gameData.lobbyId;
      game.state = gameData.state;
      game.config = gameData.config;
      game.createdAt = gameData.createdAt;
      
      return game;
    });
  }

  // Get the active game for a lobby
  static async getActiveGameForLobby(lobbyId) {
    const db = getDb();
    const gamesCollection = db.collection('games');
    
    const gameData = await gamesCollection.findOne({ 
      lobbyId, 
      'state.isComplete': false 
    });
    
    if (!gameData) return null;
    
    const game = new Game({
      gameType: gameData.gameType,
      maxPlayers: gameData.maxPlayers
    });
    
    game._id = gameData._id;
    game.lobbyId = gameData.lobbyId;
    game.state = gameData.state;
    game.config = gameData.config;
    game.createdAt = gameData.createdAt;
    
    return game;
  }

  // Create game subclasses for specific games

  // Create a TicTacToe game
  static createTicTacToe() {
    return new Game({
      gameType: 'tictactoe',
      maxPlayers: 2,
      initialBoard: Array(9).fill(null),
      startingPlayer: 'X',
      boardSize: 3  // 3x3 grid
    });
  }

  // Create a Chess game
  static createChess() {
    return new Game({
      gameType: 'chess',
      maxPlayers: 2,
      startingPlayer: 'white',
      boardSize: 8  // 8x8 grid
      // Would need to add initial chess piece positions
    });
  }

  // Create a Checkers game
  static createCheckers() {
    return new Game({
      gameType: 'checkers',
      maxPlayers: 2,
      startingPlayer: 'black',
      boardSize: 8  // 8x8 grid
      // Would need to add initial checker piece positions
    });
  }

  // Create a customized game
  static createCustomGame(config) {
    return new Game(config);
  }
}

module.exports = Game;