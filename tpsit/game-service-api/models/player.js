class Player {
  constructor(id, connection) {
    this.id = id;
    this.connection = connection;
    this.lobbyId = null;
    this.spectator = false;
    this.isAdmin = false;
    this.connectedAt = new Date();
    this.alias = null;
  }

  // Set player's alias
  setAlias(alias) {
    this.alias = alias;
  }

  // Set as player in a lobby
  setAsPlayer(lobbyId, alias = null) {
    this.lobbyId = lobbyId;
    this.spectator = false;
    if (alias) this.alias = alias;
  }

  // Set as spectator in a lobby
  setAsSpectator(lobbyId, alias = null) {
    this.lobbyId = lobbyId;
    this.spectator = true;
    if (alias) this.alias = alias;
  }

  // Check if player is in a lobby
  isInLobby() {
    return this.lobbyId !== null;
  }

  // Check if player is a spectator
  isSpectator() {
    return this.spectator;
  }

  // Send message to this player
  send(message) {
    if (this.connection.readyState === 1) { // 1 = WebSocket.OPEN
      this.connection.send(JSON.stringify(message));
    }
  }
}

module.exports = Player;