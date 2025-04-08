class Player {
  constructor(id, connection, type = 'player') {
    this.id = id;
    this.connection = connection;
    this.type = type; // 'player' or 'spectator'
    this.lobbyId = null;
    this.symbol = null;
  }
  
  setAsPlayer(lobbyId, symbol) {
    this.lobbyId = lobbyId;
    this.symbol = symbol;
    this.type = 'player';
  }
  
  setAsSpectator(lobbyId) {
    this.lobbyId = lobbyId;
    this.symbol = null;
    this.type = 'spectator';
  }
  
  // Send a message to this player
  send(message) {
    if (this.connection && this.connection.readyState === 1) { // WebSocket.OPEN
      if (typeof message === 'object') {
        this.connection.send(JSON.stringify(message));
      } else {
        this.connection.send(message);
      }
    }
  }
  
  // Leave current lobby
  leaveLobby() {
    this.lobbyId = null;
    this.symbol = null;
  }
  
  // Check if player is in a lobby
  isInLobby() {
    return !!this.lobbyId;
  }
  
  // Check if player is a spectator
  isSpectator() {
    return this.type === 'spectator';
  }
}

module.exports = Player;