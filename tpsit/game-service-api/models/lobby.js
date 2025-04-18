const { getDb } = require('../db/mongodb');

class Lobby {
  constructor(name, creatorId, maxPlayers = 2, gameType = 'tris') {
    this._id = null;
    this.name = name;
    this.creatorId = creatorId;
    this.createdAt = new Date();
    this.players = [];
    this.spectators = [];
    this.maxPlayers = Math.min(Math.max(2, parseInt(maxPlayers) || 2), 16);
    this.gameType = gameType;
    
    // Stato generico del gioco - è solo un contenitore
    this.gameState = {
      currentTurn: null,  // ID del giocatore di turno
      status: 'waiting',  // waiting, playing, finished
      data: {},           // Qui ogni gioco memorizza il suo stato specifico
      lastUpdate: new Date()
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

  // I metodi restano simili ma senza logica specifica per il Tris
  async addPlayer(playerId, playerOptions = {}) {
    const db = getDb();
    
    // Verifica se la lobby è piena
    if (this.players.length >= this.maxPlayers) {
      throw new Error(`Lobby is full (max ${this.maxPlayers} players)`);
    }
    
    // Il client fornisce le opzioni del giocatore specifiche per il gioco
    const playerData = {
      id: playerId,
      joinedAt: new Date(),
      ...playerOptions  // può includere simboli, ruoli, o altro specifico del gioco
    };
    
    await db.collection('lobbies').updateOne(
      { _id: this._id },
      { $push: { players: playerData } }
    );
    
    // Imposta il giocatore di turno se è il primo a unirsi
    if (this.players.length === 0) {
      await db.collection('lobbies').updateOne(
        { _id: this._id },
        { $set: { "gameState.currentTurn": playerId } }
      );
      this.gameState.currentTurn = playerId;
    }
    
    // Update local state
    this.players.push(playerData);
    
    return playerData;
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
      currentTurn: null,
      status: 'waiting',
      data: {},
      lastUpdate: new Date()
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
      currentTurn: null,
      status: 'waiting',
      data: {},
      lastUpdate: new Date()
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
      isFull: lobby.players && lobby.players.length >= (lobby.maxPlayers || 2),
      maxPlayers: lobby.maxPlayers || 2 // Aggiungi il numero massimo di giocatori
    }));
  }
}

module.exports = Lobby;