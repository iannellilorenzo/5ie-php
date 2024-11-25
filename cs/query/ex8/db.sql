-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Nov 20, 2024 alle 19:00
-- Versione del server: 10.4.22-MariaDB
-- Versione PHP: 8.0.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `scuola`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `alunni`
--

CREATE TABLE `alunni` (
  `id` int(11) NOT NULL,
  `nome` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `alunni`
--

INSERT INTO `alunni` (`id`, `nome`) VALUES
(1, 'Bianchi'),
(2, 'Rossi'),
(3, 'Verdi'),
(4, 'Neri');

-- --------------------------------------------------------

--
-- Struttura della tabella `interrogazioni`
--

CREATE TABLE `interrogazioni` (
  `id` int(11) NOT NULL,
  `data` date DEFAULT NULL,
  `voto` int(11) DEFAULT NULL,
  `id_materia` int(11) NOT NULL,
  `Id_alunno` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `interrogazioni`
--

INSERT INTO `interrogazioni` (`id`, `data`, `voto`, `id_materia`, `Id_alunno`) VALUES
(1, '2024-11-03', 8, 1, 1),
(2, '2024-10-14', 7, 2, 1),
(3, '2024-11-04', 6, 3, 2),
(4, '2024-11-06', 5, 4, 1),
(7, '2024-11-17', 9, 3, 4),
(8, '2024-11-05', 10, 4, 3);

-- --------------------------------------------------------

--
-- Struttura della tabella `materie`
--

CREATE TABLE `materie` (
  `id` int(11) NOT NULL,
  `nome` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `materie`
--

INSERT INTO `materie` (`id`, `nome`) VALUES
(1, 'Italiano'),
(2, 'Matematica'),
(3, 'Inglese'),
(4, 'Informatica');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `alunni`
--
ALTER TABLE `alunni`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `interrogazioni`
--
ALTER TABLE `interrogazioni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rel_mat` (`id_materia`),
  ADD KEY `rel_alunno` (`Id_alunno`);

--
-- Indici per le tabelle `materie`
--
ALTER TABLE `materie`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `alunni`
--
ALTER TABLE `alunni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `interrogazioni`
--
ALTER TABLE `interrogazioni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `materie`
--
ALTER TABLE `materie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `interrogazioni`
--
ALTER TABLE `interrogazioni`
  ADD CONSTRAINT `rel_alunno` FOREIGN KEY (`Id_alunno`) REFERENCES `alunni` (`id`),
  ADD CONSTRAINT `rel_mat` FOREIGN KEY (`id_materia`) REFERENCES `materie` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
