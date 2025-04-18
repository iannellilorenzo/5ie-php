class Player {
  constructor(id, connection) {
    this.id = id;
    this.connection = connection;
    this.lobbyId = null;
    this.symbol = null;
    this.spectator = false;
    this.isAdmin = false;
    this.connectedAt = new Date();
    this.alias = null; // Nuovo campo per l'alias
  }

  // Imposta l'alias per il giocatore
  setAlias(alias) {
    this.alias = alias;
  }

  // Modifica i metodi esistenti per supportare gli alias
  setAsPlayer(lobbyId, symbol, alias = null) {
    this.lobbyId = lobbyId;
    this.symbol = symbol;
    this.spectator = false;
    if (alias) this.alias = alias;
  }

  setAsSpectator(lobbyId, alias = null) {
    this.lobbyId = lobbyId;
    this.symbol = null;
    this.spectator = true;
    if (alias) this.alias = alias;
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