const { getDb } = require('../db/mongodb');

class Lobby {
  constructor(name, creatorId) {
    this._id = null;
    this.name = name;
    this.creatorId = creatorId;
    this.createdAt = new Date();
    this.players = [];
    this.spectators = [];
    this.gameState = {
      board: Array(9).fill(null),
      currentTurn: 'X',
      winner: null
    };
  }

  async save() {
    const db = getDb();
    
    // Check if lobby with this name already exists
    const existingLobby = await db.collection('lobbies').findOne({ name: this.name });
    if (existingLobby) {
      throw new Error('Lobby with this name already exists');
    }
    
    const result = await db.collection('lobbies').insertOne(this);
    this._id = result.insertedId;
    return this._id;
  }

  async addPlayer(playerId, symbol) {
    const db = getDb();
    
    // Add player to the lobby
    await db.collection('lobbies').updateOne(
      { _id: this._id },
      { $push: { players: { id: playerId, symbol } } }
    );
    
    // Update local state
    this.players.push({ id: playerId, symbol });
    
    return { id: playerId, symbol };
  }

  async addSpectator(spectatorId) {
    const db = getDb();
    
    await db.collection('lobbies').updateOne(
      { _id: this._id },
      { $push: { spectators: { id: spectatorId } } }
    );
    
    // Update local state
    this.spectators.push({ id: spectatorId });
    
    return { id: spectatorId };
  }

  async removeParticipant(participantId) {
    const db = getDb();
    
    // Remove from players and spectators
    await db.collection('lobbies').updateOne(
      { _id: this._id },
      { 
        $pull: { 
          players: { id: participantId },
          spectators: { id: participantId }
        } 
      }
    );
    
    // Update local state
    this.players = this.players.filter(p => p.id !== participantId);
    this.spectators = this.spectators.filter(s => s.id !== participantId);
  }

  static async findByName(name) {
    const db = getDb();
    
    const lobby = await db.collection('lobbies').findOne({ name });
    
    if (!lobby) return null;
    
    // Create Lobby instance from DB data
    const lobbyInstance = new Lobby(lobby.name, lobby.creatorId);
    lobbyInstance._id = lobby._id;
    lobbyInstance.players = lobby.players || [];
    lobbyInstance.spectators = lobby.spectators || [];
    lobbyInstance.gameState = lobby.gameState || {
      board: Array(9).fill(null),
      currentTurn: 'X',
      winner: null
    };
    
    return lobbyInstance;
  }

  static async findById(id) {
    const db = getDb();
    
    const lobby = await db.collection('lobbies').findOne({ _id: id });
    
    if (!lobby) return null;
    
    // Create Lobby instance from DB data
    const lobbyInstance = new Lobby(lobby.name, lobby.creatorId);
    lobbyInstance._id = lobby._id;
    lobbyInstance.players = lobby.players || [];
    lobbyInstance.spectators = lobby.spectators || [];
    lobbyInstance.gameState = lobby.gameState || {
      board: Array(9).fill(null),
      currentTurn: 'X',
      winner: null
    };
    
    return lobbyInstance;
  }

  static async getActiveWithPlayerCounts() {
    const db = getDb();
    
    const lobbies = await db.collection('lobbies').find({}).toArray();
    
    return lobbies.map(lobby => ({
      id: lobby._id,
      name: lobby.name,
      players: lobby.players ? lobby.players.length : 0,
      spectators: lobby.spectators ? lobby.spectators.length : 0,
      isFull: lobby.players && lobby.players.length >= 2
    }));
  }
}

module.exports = Lobby;