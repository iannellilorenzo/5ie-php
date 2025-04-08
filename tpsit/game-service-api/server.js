const WebSocket = require('ws');
const { v4: uuidv4 } = require('uuid');
const { connectToDatabase, closeConnection } = require('./db/mongodb');
const Lobby = require('./lobby');
const Player = require('./player');
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