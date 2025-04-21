CREATE DATABASE IF NOT EXISTS carpooling;
USE carpooling;

CREATE TABLE IF NOT EXISTS autisti (
    id_autista INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    data_nascita DATE NOT NULL,
    numero_patente VARCHAR(20) NOT NULL UNIQUE,
    scadenza_patente DATE NOT NULL,
    numero_telefono VARCHAR(15) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    fotografia VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS passeggeri (
    id_passeggero INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    documento_identita VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(15) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    citta_partenza VARCHAR(50),
    citta_destinazione VARCHAR(50),
    data_desiderata DATE
);

CREATE TABLE IF NOT EXISTS automobili (
    targa VARCHAR(10) PRIMARY KEY,
    marca VARCHAR(30) NOT NULL,
    modello VARCHAR(50) NOT NULL,
    id_autista INT NOT NULL,
    FOREIGN KEY (id_autista) REFERENCES autista(id_autista) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS viaggi (
    id_viaggio INT AUTO_INCREMENT PRIMARY KEY,
    citta_partenza VARCHAR(50) NOT NULL,
    citta_destinazione VARCHAR(50) NOT NULL,
    timestamp_partenza DATETIME NOT NULL,
    prezzo_cadauno DECIMAL(6,2) NOT NULL,
    tempo_stimato TIME NOT NULL,
    soste BOOLEAN DEFAULT FALSE,
    bagaglio BOOLEAN DEFAULT FALSE,
    animali BOOLEAN DEFAULT FALSE,
    id_autista INT NOT NULL,
    FOREIGN KEY (id_autista) REFERENCES autista(id_autista) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS prenotazioni (
    id_prenotazione INT AUTO_INCREMENT PRIMARY KEY,
    id_passeggero INT NOT NULL,
    id_viaggio INT NOT NULL,
    voto_autista TINYINT,
    voto_passeggero TINYINT,
    feedback_autista TEXT,
    feedback_passeggero TEXT,
    FOREIGN KEY (id_passeggero) REFERENCES passeggero(id_passeggero) ON DELETE CASCADE,
    FOREIGN KEY (id_viaggio) REFERENCES viaggio(id_viaggio) ON DELETE CASCADE
);