const { getDb } = require('../db/mongodb');
const { ObjectId } = require('mongodb');

class Move {
  constructor(data = {}) {
    this._id = null;
    this.lobbyId = data.lobbyId || null;
    this.gameType = data.gameType || 'generic';
    this.playerId = data.playerId || null;
    this.playerSymbol = data.playerSymbol || null;
    this.moveData = data.moveData || {};
    this.moveNumber = data.moveNumber || 0;
    this.timestamp = data.timestamp || new Date();
  }

  // Salva una mossa nel database
  async save() {
    const db = getDb();
    
    if (this._id) {
      // Aggiorna una mossa esistente (raramente necessario)
      await db.collection('moves').updateOne(
        { _id: this._id },
        { $set: {
            lobbyId: this.lobbyId,
            gameType: this.gameType,
            playerId: this.playerId,
            playerSymbol: this.playerSymbol,
            moveData: this.moveData,
            moveNumber: this.moveNumber,
            timestamp: this.timestamp
          } 
        }
      );
      return this._id;
    } else {
      // Crea una nuova mossa
      const result = await db.collection('moves').insertOne(this);
      this._id = result.insertedId;
      return this._id;
    }
  }

  // Trova tutte le mosse di una lobby ordinate per numero di mossa
  static async getMovesForLobby(lobbyId) {
    const db = getDb();
    
    try {
      const moves = await db.collection('moves')
        .find({ lobbyId: new ObjectId(lobbyId) })
        .sort({ moveNumber: 1 })
        .toArray();
      
      return moves;
    } catch (error) {
      console.error('Error retrieving moves:', error);
      return [];
    }
  }

  // Ottiene l'ultimo numero di mossa per una lobby
  static async getLastMoveNumber(lobbyId) {
    const db = getDb();
    
    try {
      const lastMove = await db.collection('moves')
        .find({ lobbyId: new ObjectId(lobbyId) })
        .sort({ moveNumber: -1 })
        .limit(1)
        .toArray();
      
      if (lastMove.length > 0) {
        return lastMove[0].moveNumber;
      }
      return 0;
    } catch (error) {
      console.error('Error retrieving last move number:', error);
      return 0;
    }
  }
  
  // Cancella tutte le mosse per una lobby (utile per il reset)
  static async clearMovesForLobby(lobbyId) {
    const db = getDb();
    
    try {
      await db.collection('moves').deleteMany({ 
        lobbyId: new ObjectId(lobbyId) 
      });
      return true;
    } catch (error) {
      console.error('Error clearing moves:', error);
      return false;
    }
  }
}

module.exports = Move;