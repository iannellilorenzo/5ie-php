const WebSocket = require('ws');
const { v4: uuidv4 } = require('uuid');
const { getDb, connectToDatabase, closeConnection } = require('./db/mongodb');
const Lobby = require('./models/lobby');
const Player = require('./models/player');
const { ObjectId } = require('mongodb');
const Move = require('./models/move');
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

// Helper function for admin data refresh
async function refreshData(ws, player) {
  if (player.isAdmin && ws.readyState === WebSocket.OPEN) {
    try {
      const lobbies = await Lobby.getActiveWithPlayerCounts();
      const games = await formatGamesData(lobbies);
      const clients = formatClientsData();
      
      ws.send(JSON.stringify({
        type: 'admin_data',
        lobbies: lobbies,
        games: games,
        clients: clients
      }));
    } catch (error) {
      console.error('Error refreshing admin data:', error);
      ws.send(JSON.stringify({
        type: 'error',
        message: 'Failed to refresh data'
      }));
    }
  }
}

// Format games data for admin panel
async function formatGamesData(lobbies) {
  const games = [];
  const db = getDb();
  
  for (const lobby of lobbies) {
    const lobbyDetail = await Lobby.findById(lobby.id);
    
    if (lobbyDetail && lobbyDetail.players.length > 0) {
      games.push({
        lobbyId: lobby.id,
        gameType: lobbyDetail.gameType || 'generic',
        players: lobbyDetail.players.map(p => ({
          id: p.id,
          alias: p.alias || `Player_${p.id.substring(0, 5)}`,
          symbol: p.symbol || 'unknown'
        })),
        maxPlayers: lobbyDetail.maxPlayers || 2,
        currentTurn: lobbyDetail.gameState.currentTurn,
        gameState: lobbyDetail.gameState
      });
    }
  }
  
  return games;
}

// Format clients data for admin panel
function formatClientsData() {
  return Array.from(activePlayers.entries()).map(([id, player]) => ({
    id: id,
    alias: player.alias || `Client_${id.substring(0, 5)}`,
    type: player.spectator ? 'Spectator' : 'Player',
    lobbyId: player.lobbyId,
    connectedAt: player.connectedAt || new Date(),
    connected: player.connection.readyState === 1
  }));
}

wss.on('connection', (ws) => {
  // Assign a unique ID to this connection
  const playerId = uuidv4();
  const player = new Player(playerId, ws);
  activePlayers.set(playerId, player);
  
  console.log(`New connection: ${playerId}`);

  ws.on('message', async (message) => {
    try {
      const data = JSON.parse(message);
      console.log(`Received ${data.type} from ${playerId}`);

      switch (data.type) {
        case 'create_lobby':
          try {
            const gameType = data.gameType || 'generic'; 
            const maxPlayers = data.maxPlayers || 2;
            const alias = data.alias || `Player_${playerId.substring(0, 5)}`;
            player.setAlias(alias);
            
            const lobby = new Lobby(data.lobby, playerId, maxPlayers, gameType);
            await lobby.save();
            
            // Add player to lobby with any game-specific attributes provided by client
            const playerOptions = {
              alias: alias,
              ...data.playerOptions // Game-specific player attributes (symbol, role, etc.)
            };
            
            await lobby.addPlayer(playerId, playerOptions);
            player.setAsPlayer(lobby._id, alias);
            
            // Use symbols from playerOptions if provided, or default to firstPlayer
            const response = {
              type: 'lobby_created',
              lobbyId: lobby._id,
              playerIndex: 0,
              maxPlayers: maxPlayers,
              gameType: gameType,
              alias: alias,
              // Include any game state info needed by the client
              gameState: lobby.gameState
            };

            // If player provided a symbol/role assign it back in response
            if (data.playerOptions && data.playerOptions.symbol) {
              response.symbol = data.playerOptions.symbol;
            }
            
            ws.send(JSON.stringify(response));
          } catch (error) {
            ws.send(JSON.stringify({
              type: 'error',
              message: error.message || 'Error creating lobby'
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
              await lobby.addSpectator(playerId, { alias: data.alias });
              player.setAsSpectator(lobby._id, data.alias);
              
              // Get all moves for this lobby
              const moves = await Move.getMovesForLobby(lobby._id);

              ws.send(JSON.stringify({ 
                type: 'joined_as_spectator', 
                lobby: data.lobby,
                gameType: lobby.gameType,
                gameState: lobby.gameState,
                moves: moves,
                playerCount: lobby.players.length
              }));
              
              // Notify all players in the lobby that a spectator joined
              lobby.players.forEach(p => {
                const playerConnection = activePlayers.get(p.id);
                if (playerConnection) {
                  playerConnection.send({
                    type: 'spectator_joined',
                    alias: data.alias
                  });
                }
              });
            } else {
              // Check if lobby already has max players
              if (lobby.players.length >= lobby.maxPlayers) {
                // Can only join as spectator
                throw new Error(`Lobby is full (max ${lobby.maxPlayers} players). You can join as a spectator.`);
              }
              
              // Add player to lobby
              const playerIndex = lobby.players.length;
              const alias = data.alias || `Player_${playerId.substring(0, 5)}`;
              
              // Include any game-specific player attributes provided by client
              const playerOptions = { 
                alias,
                ...data.playerOptions
              };
              
              await lobby.addPlayer(playerId, playerOptions);
              player.setAsPlayer(lobby._id, alias);
              
              const response = {
                type: 'lobby_joined', 
                lobby: data.lobby,
                gameType: lobby.gameType,
                playerIndex: playerIndex,
                playerCount: lobby.players.length,
                maxPlayers: lobby.maxPlayers,
                gameState: lobby.gameState
              };
              
              // If player options included a symbol, return it
              if (data.playerOptions && data.playerOptions.symbol) {
                response.symbol = data.playerOptions.symbol;
              }
              
              ws.send(JSON.stringify(response));
              
              // Notify existing players
              lobby.players.forEach((p, idx) => {
                if (p.id !== playerId) {
                  const otherPlayerConnection = activePlayers.get(p.id);
                  if (otherPlayerConnection) {
                    otherPlayerConnection.send({
                      type: 'player_joined',
                      playerIndex: playerIndex,
                      playerCount: lobby.players.length,
                      alias: alias
                    });
                  }
                }
              });
              
              // Notify all spectators
              lobby.spectators.forEach(s => {
                const spectatorConnection = activePlayers.get(s.id);
                if (spectatorConnection) {
                  spectatorConnection.send({
                    type: 'player_joined',
                    playerIndex: playerIndex, 
                    playerCount: lobby.players.length,
                    alias: alias
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
          
        case 'join_lobby_by_id':
          try {
            // Convert ID string to ObjectId
            const lobbyObjectId = new ObjectId(data.lobbyId);
            
            // Look up lobby by ID
            const db = getDb();
            const lobby = await db.collection('lobbies').findOne({ _id: lobbyObjectId });
            
            if (!lobby) {
              throw new Error('Lobby does not exist');
            }
            
            // Create a lobby instance
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
            lobbyInstance.gameType = lobby.gameType || 'generic';
            lobbyInstance.maxPlayers = lobby.maxPlayers || 2;
            
            const alias = data.alias || `Player_${playerId.substring(0, 5)}`;
            
            // Add player to lobby based on capacity
            if (lobbyInstance.players.length >= lobbyInstance.maxPlayers) {
              // If lobby is full, join as spectator
              await lobbyInstance.addSpectator(playerId, { alias });
              player.setAsSpectator(lobby._id, alias);
              
              // Get all moves for this lobby
              const moves = await Move.getMovesForLobby(lobby._id);

              ws.send(JSON.stringify({ 
                type: 'joined_as_spectator', 
                lobby: lobby.name,
                gameType: lobbyInstance.gameType,
                gameState: lobby.gameState,
                moves: moves,
                playerCount: lobbyInstance.players.length
              }));
            } else {
              // Join as player with any provided options
              const playerOptions = { 
                alias,
                ...data.playerOptions
              };
              
              const playerIndex = lobbyInstance.players.length;
              await lobbyInstance.addPlayer(playerId, playerOptions);
              player.setAsPlayer(lobby._id, alias);
              
              const response = {
                type: 'lobby_joined', 
                lobby: lobby.name,
                gameType: lobbyInstance.gameType,
                playerIndex: playerIndex,
                playerCount: lobbyInstance.players.length,
                maxPlayers: lobbyInstance.maxPlayers,
                gameState: lobby.gameState
              };
              
              // If player options included a symbol, return it
              if (data.playerOptions && data.playerOptions.symbol) {
                response.symbol = data.playerOptions.symbol;
              }
              
              ws.send(JSON.stringify(response));
              
              // Notify other players
              if (playerIndex > 0) {
                lobbyInstance.players.forEach((p, idx) => {
                  if (idx !== playerIndex) {
                    const connection = activePlayers.get(p.id);
                    if (connection) {
                      connection.send({
                        type: 'player_joined',
                        playerIndex: playerIndex,
                        playerCount: lobbyInstance.players.length,
                        alias
                      });
                    }
                  }
                });
              }
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
          if (player.isInLobby() && !player.isSpectator()) {
            try {
              const lobbyId = player.lobbyId;
              const lobby = await Lobby.findById(lobbyId);
              
              if (!lobby) throw new Error('Lobby not found');
              
              // Find player in the lobby
              const playerData = lobby.players.find(p => p.id === playerId);
              if (!playerData) throw new Error('Player not found in lobby');
              
              // Store move with game-specific data
              const lastMoveNumber = await Move.getLastMoveNumber(lobbyId);
              const move = new Move({
                lobbyId: new ObjectId(player.lobbyId),
                gameType: lobby.gameType,
                playerId: playerId,
                playerSymbol: playerData.symbol, // Use player's symbol if defined
                moveData: data.move, // Game-specific move data
                moveNumber: lastMoveNumber + 1,
              });
              await move.save();
              
              // Notify other players
              lobby.players.forEach(p => {
                if (p.id !== playerId) {
                  const otherPlayerConnection = activePlayers.get(p.id);
                  if (otherPlayerConnection) {
                    otherPlayerConnection.send({
                      type: 'opponent_move',
                      playerId: playerId,
                      playerAlias: playerData.alias,
                      move: data.move,
                      moveNumber: lastMoveNumber + 1,
                      symbol: playerData.symbol // Include player's symbol if available
                    });
                  }
                }
              });
              
              // Notify all spectators
              lobby.spectators.forEach(s => {
                const spectatorConnection = activePlayers.get(s.id);
                if (spectatorConnection) {
                  spectatorConnection.send({
                    type: 'game_move',
                    playerId: playerId,
                    playerAlias: playerData.alias,
                    move: data.move,
                    moveNumber: lastMoveNumber + 1,
                    symbol: playerData.symbol // Include player's symbol if available
                  });
                }
              });

              // Update game state in the lobby if provided
              if (data.gameState) {
                const db = getDb();
                await db.collection('lobbies').updateOne(
                  { _id: new ObjectId(lobbyId) },
                  { $set: { gameState: data.gameState } }
                );
              }
            } catch (error) {
              console.error('Error handling move:', error);
              ws.send(JSON.stringify({
                type: 'error',
                message: 'Failed to process move: ' + error.message
              }));
            }
          } else {
            ws.send(JSON.stringify({
              type: 'error',
              message: 'You must be a player in a lobby to make a move'
            }));
          }
          break;

        case 'game_action':
          if (player.isInLobby()) {
            try {
              const lobbyId = player.lobbyId;
              const lobby = await Lobby.findById(lobbyId);
              
              if (!lobby) {
                throw new Error('Lobby not found');
              }
              
              const db = getDb();
              
              // Update game state based on the provided action
              if (data.gameState) {
                await db.collection('lobbies').updateOne(
                  { _id: new ObjectId(lobbyId) },
                  { $set: { gameState: data.gameState } }
                );
              }
              
              // Broadcast the action to all other participants
              const playerData = lobby.players.find(p => p.id === playerId) || 
                                { id: playerId, alias: player.alias };
              
              const actionData = {
                type: 'game_action',
                playerId: playerId,
                playerAlias: playerData.alias,
                action: data.action,
                gameState: data.gameState
              };
              
              // Send to all other players
              lobby.players.forEach(p => {
                if (p.id !== playerId) {
                  const playerConnection = activePlayers.get(p.id);
                  if (playerConnection) {
                    playerConnection.send(actionData);
                  }
                }
              });
              
              // Send to all spectators
              lobby.spectators.forEach(s => {
                if (s.id !== playerId) {
                  const spectatorConnection = activePlayers.get(s.id);
                  if (spectatorConnection) {
                    spectatorConnection.send(actionData);
                  }
                }
              });
              
              // Send confirmation to the sender
              ws.send(JSON.stringify({
                type: 'action_confirmed',
                action: data.action
              }));
            } catch (error) {
              ws.send(JSON.stringify({
                type: 'error',
                message: 'Failed to process game action: ' + error.message
              }));
            }
          }
          break;

        case 'chat_message':
          if (player.isInLobby()) {
            try {
              const lobby = await Lobby.findById(player.lobbyId);
              if (!lobby) throw new Error('Lobby not found');
              
              const message = {
                type: 'chat_message',
                playerId: playerId,
                playerAlias: player.alias || `Player_${playerId.substring(0, 5)}`,
                content: data.content,
                timestamp: new Date()
              };
              
              // Send to all players and spectators in the lobby
              [...lobby.players, ...lobby.spectators].forEach(p => {
                const connection = activePlayers.get(p.id);
                if (connection && connection.connection.readyState === WebSocket.OPEN) {
                  connection.connection.send(JSON.stringify(message));
                }
              });
            } catch (error) {
              console.error('Error sending chat message:', error);
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
          if (player.isAdmin) {
            try {
              const lobbies = await Lobby.getActiveWithPlayerCounts();
              const games = await formatGamesData(lobbies);
              const clients = formatClientsData();
              
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
          if (player.isAdmin) {
            try {
              const maxPlayers = parseInt(data.maxPlayers) || 2;
              const gameType = data.gameType || 'generic';
              const lobby = new Lobby(data.name, 'admin', maxPlayers, gameType);
              await lobby.save();
              
              ws.send(JSON.stringify({
                type: 'lobby_created',
                success: true,
                lobbyId: lobby._id,
                name: lobby.name,
                gameType: gameType,
                maxPlayers: maxPlayers
              }));
              
              await refreshData(ws, player);
            } catch (error) {
              ws.send(JSON.stringify({
                type: 'error',
                message: error.message || 'Failed to create lobby'
              }));
            }
          }
          break;

        case 'admin_delete_lobby':
          if (player.isAdmin) {
            try {
              const db = getDb();
              const objectId = new ObjectId(data.lobbyId);
              
              // Notify all players in the lobby before deleting
              const lobby = await Lobby.findById(data.lobbyId);
              if (lobby) {
                [...lobby.players, ...lobby.spectators].forEach(p => {
                  const connection = activePlayers.get(p.id);
                  if (connection) {
                    connection.send({
                      type: 'lobby_deleted',
                      message: 'This lobby has been deleted by an administrator'
                    });
                  }
                });
              }
              
              await db.collection('lobbies').deleteOne({ _id: objectId });
              
              // Also clean up associated moves
              await Move.clearMovesForLobby(data.lobbyId);
              
              ws.send(JSON.stringify({
                type: 'lobby_deleted',
                success: true,
                lobbyId: data.lobbyId
              }));
              
              await refreshData(ws, player);
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
              
              ws.send(JSON.stringify({
                type: 'admin_export_data_response',
                data: exportData
              }));
              
              console.log(`Database export sent to admin ${playerId}`);
            } catch (error) {
              console.error('Error exporting data:', error);
              ws.send(JSON.stringify({
                type: 'error',
                message: 'Failed to export database: ' + error.message
              }));
            }
          }
          break;

        case 'admin_reset_game':
          if (player.isAdmin) {
            try {
              const db = getDb();
              const lobbyId = data.lobbyId;
              
              // Get the lobby to determine its game type
              const lobby = await Lobby.findById(lobbyId);
              if (!lobby) throw new Error('Lobby not found');
              
              // Create a clean game state appropriate for the game type
              const cleanGameState = {
                status: 'waiting',
                currentTurn: lobby.players.length > 0 ? lobby.players[0].id : null,
                data: {},
                lastUpdate: new Date()
              };
              
              // Reset game state
              await db.collection('lobbies').updateOne(
                { _id: new ObjectId(lobbyId) },
                { $set: { gameState: cleanGameState } }
              );
              
              // Clear all moves history
              await Move.clearMovesForLobby(lobbyId);
              
              // Notify all connected players and spectators
              [...lobby.players, ...lobby.spectators].forEach(p => {
                const connection = activePlayers.get(p.id);
                if (connection) {
                  connection.send({
                    type: 'game_reset',
                    gameType: lobby.gameType,
                    gameState: cleanGameState
                  });
                }
              });
              
              ws.send(JSON.stringify({
                type: 'game_reset_success',
                lobbyId: lobbyId,
                gameType: lobby.gameType
              }));
              
              await refreshData(ws, player);
            } catch (error) {
              console.error('Error resetting game:', error);
              ws.send(JSON.stringify({
                type: 'error',
                message: 'Failed to reset game: ' + error.message
              }));
            }
          }
          break;

        default:
          // For any unhandled message types, pass to the appropriate handler based on game type
          if (player.isInLobby()) {
            try {
              const lobby = await Lobby.findById(player.lobbyId);
              if (lobby) {
                // Store the custom action in the move history for game replay
                const lastMoveNumber = await Move.getLastMoveNumber(player.lobbyId);
                const move = new Move({
                  lobbyId: new ObjectId(player.lobbyId),
                  gameType: lobby.gameType,
                  playerId: playerId,
                  moveData: {
                    actionType: data.type,
                    actionData: data
                  },
                  moveNumber: lastMoveNumber + 1,
                });
                await move.save();
                
                // Broadcast to other players in the same lobby
                [...lobby.players, ...lobby.spectators].forEach(p => {
                  if (p.id !== playerId) {
                    const connection = activePlayers.get(p.id);
                    if (connection) {
                      connection.send({
                        type: 'game_custom_action',
                        originalType: data.type,
                        playerId: playerId,
                        data: data,
                        timestamp: new Date()
                      });
                    }
                  }
                });
              }
            } catch (error) {
              console.error(`Error handling custom message type '${data.type}':`, error);
            }
          } else {
            ws.send(JSON.stringify({
              type: 'error',
              message: `Unrecognized message type: ${data.type}`
            }));
          }
      }
    } catch (error) {
      console.error('Error processing message:', error);
      ws.send(JSON.stringify({ 
        type: 'error', 
        message: 'Invalid message format or internal server error'
      }));
    }
  });

  ws.on('close', async () => {
    console.log(`Connection closed: ${playerId}`);
    
    try {
      if (player.isInLobby()) {
        const lobby = await Lobby.findById(player.lobbyId);
        
        if (lobby) {
          // Get player data before removal
          const playerData = lobby.players.find(p => p.id === playerId);
          const isSpectator = player.isSpectator();
          
          // Remove player from lobby
          await lobby.removeParticipant(playerId);
          
          // Create notification data
          const notificationData = {
            type: isSpectator ? 'spectator_left' : 'player_left',
            playerId: playerId
          };
          
          // Add player details if available
          if (playerData) {
            notificationData.playerIndex = lobby.players.findIndex(p => p.id === playerId);
            notificationData.alias = playerData.alias || `Player_${playerId.substring(0, 5)}`;
            if (playerData.symbol) notificationData.symbol = playerData.symbol;
          }
          
          // Notify remaining players
          lobby.players.forEach(p => {
            const connection = activePlayers.get(p.id);
            if (connection) {
              connection.send(notificationData);
            }
          });
          
          // Notify spectators
          lobby.spectators.forEach(s => {
            const spectatorConnection = activePlayers.get(s.id);
            if (spectatorConnection) {
              spectatorConnection.send(notificationData);
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