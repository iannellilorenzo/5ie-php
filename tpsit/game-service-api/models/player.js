class Player {
  constructor(id, connection) {
    this.id = id;
    this.connection = connection;
    this.lobbyId = null;
    this.symbol = null;
    this.spectator = false;
  }

  setAsPlayer(lobbyId, symbol) {
    this.lobbyId = lobbyId;
    this.symbol = symbol;
    this.spectator = false;
  }

  setAsSpectator(lobbyId) {
    this.lobbyId = lobbyId;
    this.spectator = true;
    this.symbol = null;
  }

  isInLobby() {
    return this.lobbyId !== null;
  }

  isSpectator() {
    return this.spectator;
  }

  send(message) {
    if (this.connection.readyState === 1) { // 1 = WebSocket.OPEN
      this.connection.send(JSON.stringify(message));
    }
  }
}

module.exports = Player;