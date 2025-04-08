const { getDb } = require('./db/mongodb');
const { ObjectId } = require('mongodb');

class Lobby {
  constructor(name, createdBy = null) {
    this.name = name;
    this.createdAt = new Date();
    this.active = true;
    this.players = [];
    this.spectators = [];
    this.createdBy = createdBy;
    this._id = null;
  }

  // Save a new lobby to the database
  async save() {
    const db = getDb();
    const lobbiesCollection = db.collection('lobbies');
    
    // Check if lobby with this name already exists
    const existingLobby = await lobbiesCollection.findOne({ name: this.name, active: true });
    if (existingLobby) {
      throw new Error('Lobby name already exists');
    }
    
    const result = await lobbiesCollection.insertOne(this);
    this._id = result.insertedId;
    return this._id;
  }

  // Add a player to the lobby
  async addPlayer(playerId, symbol) {
    const db = getDb();
    const lobbiesCollection = db.collection('lobbies');
    
    // Check if player already exists in this lobby
    const lobby = await lobbiesCollection.findOne({ _id: new ObjectId(this._id) });
    if (lobby.players.some(p => p.id === playerId)) {
      throw new Error('Player already in this lobby');
    }
    
    // Check if lobby is full
    if (lobby.players.length >= 2) {
      throw new Error('Lobby is full');
    }
    
    const player = { id: playerId, symbol, joinedAt: new Date() };
    
    await lobbiesCollection.updateOne(
      { _id: new ObjectId(this._id) },
      { $push: { players: player } }
    );
    
    this.players.push(player);
    return player;
  }
  
  // Add a spectator to the lobby
  async addSpectator(spectatorId) {
    const db = getDb();
    const lobbiesCollection = db.collection('lobbies');
    
    // Check if spectator already exists in this lobby
    const lobby = await lobbiesCollection.findOne({ _id: new ObjectId(this._id) });
    if (lobby.spectators.some(s => s.id === spectatorId)) {
      throw new Error('Spectator already in this lobby');
    }
    
    const spectator = { id: spectatorId, joinedAt: new Date() };
    
    await lobbiesCollection.updateOne(
      { _id: new ObjectId(this._id) },
      { $push: { spectators: spectator } }
    );
    
    this.spectators.push(spectator);
    return spectator;
  }
  
  // Remove a player or spectator from the lobby
  async removeParticipant(participantId) {
    const db = getDb();
    const lobbiesCollection = db.collection('lobbies');
    
    // Remove from players array
    await lobbiesCollection.updateOne(
      { _id: new ObjectId(this._id) },
      { $pull: { 
          players: { id: participantId },
          spectators: { id: participantId }
        } 
      }
    );
    
    // Update local arrays
    this.players = this.players.filter(p => p.id !== participantId);
    this.spectators = this.spectators.filter(s => s.id !== participantId);
    
    // Check if lobby is empty and cleanup if needed
    await this.cleanupIfEmpty();
  }
  
  // Mark lobby as inactive if empty
  async cleanupIfEmpty() {
    if (this.players.length === 0 && this.spectators.length === 0) {
      const db = getDb();
      const lobbiesCollection = db.collection('lobbies');
      
      await lobbiesCollection.updateOne(
        { _id: new ObjectId(this._id) },
        { $set: { active: false } }
      );
      
      this.active = false;
      return true;
    }
    return false;
  }

  // Static Methods
  
  // Find a lobby by name
  static async findByName(name) {
    const db = getDb();
    const lobbiesCollection = db.collection('lobbies');
    
    const lobbyData = await lobbiesCollection.findOne({ name, active: true });
    if (!lobbyData) return null;
    
    const lobby = new Lobby(lobbyData.name, lobbyData.createdBy);
    lobby._id = lobbyData._id;
    lobby.createdAt = lobbyData.createdAt;
    lobby.active = lobbyData.active;
    lobby.players = lobbyData.players || [];
    lobby.spectators = lobbyData.spectators || [];
    
    return lobby;
  }
  
  // Find a lobby by ID
  static async findById(id) {
    const db = getDb();
    const lobbiesCollection = db.collection('lobbies');
    
    const lobbyData = await lobbiesCollection.findOne({ _id: new ObjectId(id), active: true });
    if (!lobbyData) return null;
    
    const lobby = new Lobby(lobbyData.name, lobbyData.createdBy);
    lobby._id = lobbyData._id;
    lobby.createdAt = lobbyData.createdAt;
    lobby.active = lobbyData.active;
    lobby.players = lobbyData.players || [];
    lobby.spectators = lobbyData.spectators || [];
    
    return lobby;
  }
  
  // Get all active lobbies
  static async getAllActive() {
    const db = getDb();
    const lobbiesCollection = db.collection('lobbies');
    
    const lobbies = await lobbiesCollection.find({ active: true }).toArray();
    
    return lobbies.map(lobbyData => {
      const lobby = new Lobby(lobbyData.name, lobbyData.createdBy);
      lobby._id = lobbyData._id;
      lobby.createdAt = lobbyData.createdAt;
      lobby.active = lobbyData.active;
      lobby.players = lobbyData.players || [];
      lobby.spectators = lobbyData.spectators || [];
      return lobby;
    });
  }
  
  // Get active lobbies with player counts for the lobby list
  static async getActiveWithPlayerCounts() {
    const db = getDb();
    const lobbiesCollection = db.collection('lobbies');
    
    const lobbies = await lobbiesCollection.find({ active: true }).toArray();
    
    return lobbies.map(lobby => ({
      id: lobby._id.toString(),
      name: lobby.name,
      player_count: (lobby.players || []).length,
      spectator_count: (lobby.spectators || []).length,
      created_at: lobby.createdAt
    }));
  }
}

module.exports = Lobby;