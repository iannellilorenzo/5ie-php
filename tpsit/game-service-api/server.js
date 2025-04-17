const WebSocket = require('ws');
const { v4: uuidv4 } = require('uuid');
const { getDb, connectToDatabase, closeConnection } = require('./db/mongodb');
const Lobby = require('./models/lobby');
const Player = require('./models/player');
const { ObjectId } = require('mongodb'); // Aggiungi questa importazione all'inizio del file se non c'è già
require('dotenv').config();

// Initialize MongoDB connection
connectToDatabase().then(() => {
  console.log('Database initialized');
}).catch(err => {
  console.error('Failed to connect to MongoDB:', err);
  process.exit(1);
});

const wss = new WebSocket.Server({ port: 8080 });

// Store active players (both regular players and spectators)
const activePlayers = new Map();

wss.on('connection', (ws) => {
  // Assign a unique ID to this connection
  const playerId = uuidv4();
  const player = new Player(playerId, ws);
  activePlayers.set(playerId, player);
  
  console.log(`New connection: ${playerId}`);

  ws.on('message', async (message) => {
    try {
      const data = JSON.parse(message);

      switch (data.type) {
        case 'create_lobby':
          try {
            // Create a new lobby with this player as creator
            const lobby = new Lobby(data.lobby, playerId);
            const lobbyId = await lobby.save();
            
            // Add the player to the lobby
            await lobby.addPlayer(playerId, 'X');
            
            // Update player info
            player.setAsPlayer(lobbyId, 'X');
            
            ws.send(JSON.stringify({ 
              type: 'lobby_created', 
              lobby: data.lobby, 
              symbol: 'X' 
            }));
          } catch (error) {
            ws.send(JSON.stringify({ 
              type: 'error', 
              message: error.message || 'Lobby already exists' 
            }));
          }
          break;
        
        case 'join_lobby':
          try {
            // Get the lobby
            const lobby = await Lobby.findByName(data.lobby);
            
            if (!lobby) {
              throw new Error('Lobby does not exist');
            }
            
            // Check if player wants to join as spectator
            if (data.as_spectator) {
              await lobby.addSpectator(playerId);
              player.setAsSpectator(lobby._id);
              
              ws.send(JSON.stringify({ 
                type: 'joined_as_spectator', 
                lobby: data.lobby
              }));
              
              // Notify all players in the lobby that a spectator joined
              lobby.players.forEach(p => {
                const playerConnection = activePlayers.get(p.id);
                if (playerConnection) {
                  playerConnection.send({
                    type: 'spectator_joined'
                  });
                }
              });
            } else {
              // Check if lobby already has 2 players
              if (lobby.players.length >= 2) {
                // Can only join as spectator
                throw new Error('Lobby is full. You can join as a spectator.');
              }
              
              // Add player to lobby
              const playerInfo = await lobby.addPlayer(playerId, 'O');
              player.setAsPlayer(lobby._id, 'O');
              
              ws.send(JSON.stringify({ 
                type: 'lobby_joined', 
                lobby: data.lobby, 
                symbol: 'O'
              }));
              
              // Notify the other player
              const otherPlayer = lobby.players.find(p => p.id !== playerId);
              if (otherPlayer) {
                const otherPlayerConnection = activePlayers.get(otherPlayer.id);
                if (otherPlayerConnection) {
                  otherPlayerConnection.send({
                    type: 'player_joined',
                    opponentSymbol: 'O'
                  });
                }
              }
              
              // Notify all spectators
              lobby.spectators.forEach(s => {
                const spectatorConnection = activePlayers.get(s.id);
                if (spectatorConnection) {
                  spectatorConnection.send({
                    type: 'game_starting',
                    players: [
                      { symbol: 'X' },
                      { symbol: 'O' }
                    ]
                  });
                }
              });
            }
          } catch (error) {
            ws.send(JSON.stringify({ 
              type: 'error', 
              message: error.message
            }));
          }
          break;
          
        case 'get_lobbies':
          try {
            // Get all active lobbies with player counts
            const lobbies = await Lobby.getActiveWithPlayerCounts();
            
            ws.send(JSON.stringify({
              type: 'lobbies_list',
              lobbies: lobbies
            }));
          } catch (error) {
            ws.send(JSON.stringify({
              type: 'error',
              message: 'Error fetching lobbies'
            }));
          }
          break;
        
        case 'move':
          // Handle game moves
          if (player.isInLobby() && !player.isSpectator()) {
            try {
              const lobby = await Lobby.findById(player.lobbyId);
              if (!lobby) throw new Error('Lobby not found');
              
              // Send move to other player
              const otherPlayer = lobby.players.find(p => p.id !== playerId);
              if (otherPlayer) {
                const otherPlayerConnection = activePlayers.get(otherPlayer.id);
                if (otherPlayerConnection) {
                  otherPlayerConnection.send({
                    type: 'opponent_move',
                    move: data.move
                  });
                }
              }
              
              // Send move to all spectators
              lobby.spectators.forEach(s => {
                const spectatorConnection = activePlayers.get(s.id);
                if (spectatorConnection) {
                  spectatorConnection.send({
                    type: 'game_move',
                    player: player.symbol,
                    move: data.move
                  });
                }
              });
            } catch (error) {
              console.error('Error handling move:', error);
            }
          }
          break;

        case 'admin_auth':
          // Simple password authentication for admin
          if (data.password === 'admin') {  // Use a secure password in production
            ws.send(JSON.stringify({
              type: 'admin_auth_response',
              success: true
            }));
            // Mark this connection as an admin
            player.isAdmin = true;
          } else {
            ws.send(JSON.stringify({
              type: 'admin_auth_response',
              success: false
            }));
          }
          break;

        case 'admin_get_data':
          // Only respond if this is an admin connection
          if (player.isAdmin) {
            // Get all lobbies from MongoDB
            try {
              const lobbies = await Lobby.getActiveWithPlayerCounts();
              
              // Format games data (from active lobbies with games)
              const games = [];
              for (const lobby of lobbies) {
                const lobbyDetail = await Lobby.findById(lobby.id);
                if (lobbyDetail && lobbyDetail.players.length > 1) {
                  games.push({
                    lobbyId: lobby.id,
                    playerX: lobbyDetail.players.find(p => p.symbol === 'X')?.id || 'N/A',
                    playerO: lobbyDetail.players.find(p => p.symbol === 'O')?.id || 'N/A',
                    currentTurn: lobbyDetail.gameState.currentTurn
                  });
                }
              }
              
              // Format client data
              const clients = Array.from(activePlayers.entries()).map(([id, player]) => ({
                id: id,
                type: player.spectator ? 'Spectator' : (player.symbol ? `Player ${player.symbol}` : 'Unknown'),
                lobbyId: player.lobbyId,
                connectedAt: player.connectedAt || new Date(),
                connected: player.connection.readyState === 1  // 1 = WebSocket.OPEN
              }));
              
              ws.send(JSON.stringify({
                type: 'admin_data',
                lobbies: lobbies,
                games: games,
                clients: clients
              }));
            } catch (error) {
              console.error('Error getting admin data:', error);
              ws.send(JSON.stringify({
                type: 'error',
                message: 'Failed to retrieve data'
              }));
            }
          }
          break;

        case 'admin_create_lobby':
          // Only proceed if this is an admin connection
          if (player.isAdmin) {
            try {
              const lobby = new Lobby(data.name, 'admin');
              await lobby.save();
              
              ws.send(JSON.stringify({
                type: 'lobby_created',
                success: true
              }));
              
              // Refresh admin data
              const updatedLobbies = await Lobby.getActiveWithPlayerCounts();
              ws.send(JSON.stringify({
                type: 'admin_data',
                lobbies: updatedLobbies,
                games: [],
                clients: []
              }));
            } catch (error) {
              ws.send(JSON.stringify({
                type: 'error',
                message: error.message || 'Failed to create lobby'
              }));
            }
          }
          break;

        case 'admin_delete_lobby':
          // Only proceed if this is an admin connection
          if (player.isAdmin) {
            try {
              const db = getDb();
              const { ObjectId } = require('mongodb'); // Aggiungi questa importazione all'inizio del file se non c'è già
              
              // Converti la stringa lobbyId in un ObjectId
              const objectId = new ObjectId(data.lobbyId);
              
              await db.collection('lobbies').deleteOne({ _id: objectId });
              
              ws.send(JSON.stringify({
                type: 'lobby_deleted',
                success: true
              }));
              
              // Refresh admin data
              const updatedLobbies = await Lobby.getActiveWithPlayerCounts();
              ws.send(JSON.stringify({
                type: 'admin_data',
                lobbies: updatedLobbies,
                games: [],
                clients: []
              }));
            } catch (error) {
              console.error('Error deleting lobby:', error);
              ws.send(JSON.stringify({
                type: 'error',
                message: 'Failed to delete lobby: ' + error.message
              }));
            }
          }
          break;

        case 'admin_export_data':
          if (player.isAdmin) {
            try {
              const db = getDb();
              
              // Get all collections
              const collections = await db.listCollections().toArray();
              const exportData = {
                metadata: {
                  exportDate: new Date(),
                  version: '1.0'
                },
                collections: {}
              };
              
              // Export each collection
              for (const collection of collections) {
                const collectionName = collection.name;
                const documents = await db.collection(collectionName).find({}).toArray();
                exportData.collections[collectionName] = documents;
              }
              
              // Send back to client
              ws.send(JSON.stringify({
                type: 'admin_export_data_response',
                data: exportData
              }));
              
              console.log(`Database export sent to admin ${playerId}`);
              
            } catch (error) {
              console.error('Error exporting data:', error);
              ws.send(JSON.stringify({
                type: 'error',
                message: 'Failed to export database'
              }));
            }
          }
          break;
      }
    } catch (error) {
      ws.send(JSON.stringify({ type: 'error', message: 'Invalid JSON' }));
    }
  });

  ws.on('close', async () => {
    console.log(`Connection closed: ${playerId}`);
    
    try {
      if (player.isInLobby()) {
        const lobby = await Lobby.findById(player.lobbyId);
        
        if (lobby) {
          // Remove player from lobby
          await lobby.removeParticipant(playerId);
          
          if (!player.isSpectator()) {
            // Notify other player if this was a player (not spectator)
            const otherPlayer = lobby.players.find(p => p.id !== playerId);
            if (otherPlayer) {
              const otherPlayerConnection = activePlayers.get(otherPlayer.id);
              if (otherPlayerConnection) {
                otherPlayerConnection.send({
                  type: 'opponent_left'
                });
              }
            }
          }
          
          // Notify spectators
          lobby.spectators.forEach(s => {
            const spectatorConnection = activePlayers.get(s.id);
            if (spectatorConnection) {
              spectatorConnection.send({
                type: 'player_left',
                symbol: player.symbol
              });
            }
          });
        }
      }
    } catch (error) {
      console.error('Error handling disconnection:', error);
    }
    
    // Remove from active players
    activePlayers.delete(playerId);
  });
});

// Handle server shutdown gracefully
process.on('SIGINT', async () => {
  await closeConnection();
  process.exit(0);
});

console.log('WebSocket server started on ws://localhost:8080');