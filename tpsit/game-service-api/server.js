const WebSocket = require('ws');
const { v4: uuidv4 } = require('uuid');
const { getDb, connectToDatabase, closeConnection } = require('./db/mongodb');
const Lobby = require('./models/lobby');
const Player = require('./models/player');
const { ObjectId } = require('mongodb'); // Aggiungi questa importazione all'inizio del file se non c'è già
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
            const gameType = data.gameType || 'tris'; 
            const maxPlayers = data.maxPlayers || 2;
            const alias = data.alias || `Giocatore_${playerId.substring(0, 5)}`;
            player.setAlias(alias);
            
            const lobby = new Lobby(data.lobby, playerId, maxPlayers, gameType);
            await lobby.save();
            
            // Aggiunto alias nelle opzioni del giocatore
            await lobby.addPlayer(playerId, { symbol: 'X', alias: alias });
            player.setAsPlayer(lobby._id, 'X', alias);
            
            ws.send(JSON.stringify({
              type: 'lobby_created',
              lobbyId: lobby._id,
              symbol: 'X',
              alias: alias
            }));
          } catch (error) {
            ws.send(JSON.stringify({
              type: 'error',
              message: error.message || 'Errore nella creazione della lobby'
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
              
              // Ottieni tutte le mosse per questa lobby
              const moves = await Move.getMovesForLobby(lobby._id);

              ws.send(JSON.stringify({ 
                type: 'joined_as_spectator', 
                lobby: data.lobby,
                gameState: lobby.gameState,
                moves: moves
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
          
        case 'join_lobby_by_id':
          try {
            const { ObjectId } = require('mongodb');
            
            // Converti la stringa dell'ID in ObjectId
            const lobbyObjectId = new ObjectId(data.lobbyId);
            
            // Cerca la lobby per ID
            const db = getDb();
            const lobby = await db.collection('lobbies').findOne({ _id: lobbyObjectId });
            
            if (!lobby) {
              throw new Error('Lobby does not exist');
            }
            
            // Crea un'istanza della lobby
            const lobbyInstance = new Lobby(lobby.name, lobby.creatorId);
            lobbyInstance._id = lobby._id;
            lobbyInstance.players = lobby.players || [];
            lobbyInstance.spectators = lobby.spectators || [];
            lobbyInstance.gameState = lobby.gameState || {
              board: Array(9).fill(null),
              currentTurn: 'X',
              winner: null
            };
            
            // Aggiungi il giocatore alla lobby
            if (lobbyInstance.players.length >= 2) {
              // Se la lobby è piena, unisciti come spettatore
              await lobbyInstance.addSpectator(playerId);
              player.setAsSpectator(lobby._id);
              
              // Ottieni tutte le mosse per questa lobby
              const moves = await Move.getMovesForLobby(lobby._id);

              ws.send(JSON.stringify({ 
                type: 'joined_as_spectator', 
                lobby: lobby.name,
                gameState: lobby.gameState,
                moves: moves
              }));
            } else {
              // Unisciti come giocatore
              const symbol = lobbyInstance.players.length === 0 ? 'X' : 'O';
              await lobbyInstance.addPlayer(playerId, symbol);
              player.setAsPlayer(lobby._id, symbol);
              
              ws.send(JSON.stringify({ 
                type: 'lobby_joined', 
                lobby: lobby.name,
                symbol: symbol
              }));
              
              // Notifica agli altri giocatori
              if (symbol === 'O') {
                const otherPlayer = lobbyInstance.players.find(p => p.id !== playerId);
                if (otherPlayer) {
                  const otherPlayerConnection = activePlayers.get(otherPlayer.id);
                  if (otherPlayerConnection) {
                    otherPlayerConnection.send({
                      type: 'player_joined',
                      opponentSymbol: 'O'
                    });
                  }
                }
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
              
              // Salva la mossa nel database
              const lastMoveNumber = await Move.getLastMoveNumber(lobbyId);
              const move = new Move({
                lobbyId: new ObjectId(player.lobbyId),
                playerId: playerId,
                playerSymbol: player.symbol,
                moveData: data.move,
                moveNumber: lastMoveNumber + 1
              });
              await move.save();
              
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

        case 'game_action':
          if (player.isInLobby()) {
            try {
              const lobbyId = player.lobbyId;
              const lobby = await Lobby.findById(lobbyId);
              
              if (!lobby) {
                throw new Error('Lobby not found');
              }
              
              // Verifica se è il turno del giocatore (opzionale, può essere gestito dal client)
              if (data.requireTurnValidation && lobby.gameState.currentTurn !== player.id) {
                throw new Error('Not your turn');
              }
              
              // Aggiorna lo stato del gioco con l'azione del client
              await db.collection('lobbies').updateOne(
                { _id: new ObjectId(lobbyId) },
                { $set: { "gameState.data": data.gameState } }
              );
              
              // Se il client comunica un cambio di turno, lo registriamo
              if (data.nextTurn) {
                await db.collection('lobbies').updateOne(
                  { _id: new ObjectId(lobbyId) },
                  { $set: { "gameState.currentTurn": data.nextTurn } }
                );
              }
              
              // Trasmetti l'azione a tutti i giocatori e spettatori
              lobby.players.forEach(p => {
                const playerConnection = activePlayers.get(p.id);
                if (playerConnection && p.id !== playerId) {
                  playerConnection.send({
                    type: 'game_action',
                    playerId: playerId,
                    action: data.action,
                    gameState: data.gameState
                  });
                }
              });
              
              // Trasmetti anche agli spettatori
              lobby.spectators.forEach(s => {
                // code for spectators
              });
            } catch (error) {
              // gestione errori
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
              
              // Format games data con alias dei giocatori
              const games = [];
              for (const lobby of lobbies) {
                const lobbyDetail = await Lobby.findById(lobby.id);
                
                if (lobbyDetail && lobbyDetail.players.length > 0) {
                  games.push({
                    lobbyId: lobby.id,
                    players: lobbyDetail.players.map(p => ({
                      id: p.id,
                      symbol: p.symbol,
                      alias: p.alias || `Player_${p.id.substring(0, 5)}`
                    })),
                    maxPlayers: lobbyDetail.maxPlayers || 2,
                    currentTurn: lobbyDetail.gameState.currentTurn
                  });
                }
              }
              
              // Format client data con alias
              const clients = Array.from(activePlayers.entries()).map(([id, player]) => ({
                id: id,
                alias: player.alias || `Client_${id.substring(0, 5)}`,
                type: player.spectator ? 'Spectator' : (player.symbol ? `Player ${player.symbol}` : 'Unknown'),
                lobbyId: player.lobbyId,
                connectedAt: player.connectedAt || new Date(),
                connected: player.connection.readyState === 1
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
              const maxPlayers = data.maxPlayers || 2;
              const lobby = new Lobby(data.name, 'admin', maxPlayers);
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

        // Nel case 'admin_reset_game'
        case 'admin_reset_game':
          if (player.isAdmin) {
            try {
              const db = getDb();
              const lobbyId = data.lobbyId;
              
              // Reset game state
              await db.collection('lobbies').updateOne(
                { _id: new ObjectId(lobbyId) },
                { $set: { 
                    gameState: {
                      board: Array(9).fill(null),
                      currentTurn: 'X',
                      winner: null,
                      status: 'waiting',
                      data: {},
                      lastUpdate: new Date()
                    }
                  } 
                }
              );
              
              // Clear all moves history
              await Move.clearMovesForLobby(lobbyId);
              
              // Notify all connected players and spectators
              const lobby = await Lobby.findById(lobbyId);
              if (lobby) {
                [...lobby.players, ...lobby.spectators].forEach(p => {
                  const connection = activePlayers.get(p.id);
                  if (connection) {
                    connection.send({
                      type: 'game_reset'
                    });
                  }
                });
              }
              
              ws.send(JSON.stringify({
                type: 'game_reset_success',
                lobbyId: lobbyId
              }));
              
              // Refresh admin data
              refreshData(ws, player);
            } catch (error) {
              console.error('Error resetting game:', error);
              ws.send(JSON.stringify({
                type: 'error',
                message: 'Failed to reset game: ' + error.message
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