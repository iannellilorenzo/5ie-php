-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 16, 2025 at 10:06 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gestionale_tirocini`
--

-- --------------------------------------------------------

--
-- Table structure for table `accordo`
--

CREATE TABLE `accordo` (
  `id` int(11) NOT NULL,
  `studente_id` int(11) NOT NULL,
  `tirocinio_id` int(11) NOT NULL,
  `stato` enum('proposto','approvato','rifiutato') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accordo`
--

INSERT INTO `accordo` (`id`, `studente_id`, `tirocinio_id`, `stato`) VALUES
(1, 1, 1, 'approvato'),
(2, 2, 2, 'approvato'),
(3, 3, 3, 'rifiutato'),
(4, 1, 2, 'rifiutato'),
(6, 1, 3, 'proposto');

-- --------------------------------------------------------

--
-- Table structure for table `azienda`
--

CREATE TABLE `azienda` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `indirizzo` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `azienda`
--

INSERT INTO `azienda` (`id`, `nome`, `indirizzo`, `telefono`, `password`) VALUES
(1, 'Tech Solutions SpA', 'Via Roma 1, Milano', '02123456', '$argon2id$v=19$m=65536,t=4,p=1$a3lBMmtUdzk1VUJCaWNyRw$77Lw6o8JE3F9hcTH8ctc5V8aodHKJCxevMRTHEgG1os'),
(2, 'Digital Future Srl', 'Via Venezia 2, Roma', '06654321', '$argon2id$v=19$m=65536,t=4,p=1$MjV5bWpKalRkQWVKbTZRUg$ICg7pCXXI8XFtjsRI3AKb+kI0wgj+NdpBqYNS516eJU'),
(3, 'Smart Systems Inc', 'Via Napoli 3, Torino', '011987654', '$argon2id$v=19$m=65536,t=4,p=1$bTk1T1drcHdTWkRvaUxmVA$Z9kiXI01pnpAGGaF0i6RRLls6JP0sYijkgDWTXp4f90');

-- --------------------------------------------------------

--
-- Table structure for table `offerta`
--

CREATE TABLE `offerta` (
  `id` int(11) NOT NULL,
  `titolo` varchar(255) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `stato` enum('inserita','accettata','rifiutata') NOT NULL,
  `azienda_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offerta`
--

INSERT INTO `offerta` (`id`, `titolo`, `descrizione`, `stato`, `azienda_id`) VALUES
(1, 'Junior Developer', 'Posizione come sviluppatore junior', 'inserita', 1),
(2, 'Database Intern', 'Stage su gestione database', 'inserita', 2),
(3, 'Mobile Developer', 'Sviluppo app Android/iOS', 'inserita', 3);

-- --------------------------------------------------------

--
-- Table structure for table `responsabile`
--

CREATE TABLE `responsabile` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `responsabile`
--

INSERT INTO `responsabile` (`id`, `nome`, `email`, `password`) VALUES
(1, 'Mario Rossi', 'mario.rossi@uni.it', '$argon2id$v=19$m=65536,t=4,p=1$SWdscGpJOVBERkhYUGNzRg$WQxe2dZ7yO9ghlW/kAMCh+eCjp48kRuQXzME7K0cThI'),
(2, 'Laura Bianchi', 'laura.bianchi@uni.it', '$argon2id$v=19$m=65536,t=4,p=1$WENtMkdMcWZ3REJISExEbA$LZ4k0cO8Jd12fPo8QAt+CrYJvV+DugRe4hH2qaNmZ88'),
(3, 'Giuseppe Verdi', 'giuseppe.verdi@uni.it', '$argon2id$v=19$m=65536,t=4,p=1$Q3laQ2dnNnl0bUVhbk94aw$QMjz1MJRoLJRu90Shxx7NasoJihVbUsYxUE2FHJQszE');

-- --------------------------------------------------------

--
-- Table structure for table `studente`
--

CREATE TABLE `studente` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `matricola` varchar(20) NOT NULL,
  `corso_di_studio` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studente`
--

INSERT INTO `studente` (`id`, `nome`, `matricola`, `corso_di_studio`, `password`) VALUES
(1, 'Luca Ferrari', 'S123456', 'Informatica', '$argon2id$v=19$m=65536,t=4,p=1$V2VSaDBCNnYzdmI5NkE2aA$n1Ui5Thr+NPeWLQ2MIRJ5V7Fmm+pvKnvm5HlcKxFFbg'),
(2, 'Anna Romano', 'S123457', 'Ingegneria', '$argon2id$v=19$m=65536,t=4,p=1$VFh1VXhLcWE1RkQ3aGoxdg$Gvg6DxJq3eHn9omrk/pLDC/dqSPeM/n5iCnpxy58nwM'),
(3, 'Marco Esposito', 'S123458', 'Economia', '$argon2id$v=19$m=65536,t=4,p=1$UlJ4SEF4dy5DZTBXZ0gvSA$rQ7gB/CIZVqTBboeygz1x6sWjaEU+GzbAn40V8jKxMg');

-- --------------------------------------------------------

--
-- Table structure for table `tirocinio`
--

CREATE TABLE `tirocinio` (
  `id` int(11) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `durata` int(11) DEFAULT NULL,
  `azienda_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tirocinio`
--

INSERT INTO `tirocinio` (`id`, `descrizione`, `durata`, `azienda_id`) VALUES
(1, 'Sviluppo Web Backend', 6, 1),
(2, 'Database Management', 3, 2),
(3, 'Mobile App Development', 4, 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accordo`
--
ALTER TABLE `accordo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `studente_id` (`studente_id`),
  ADD KEY `tirocinio_id` (`tirocinio_id`);

--
-- Indexes for table `azienda`
--
ALTER TABLE `azienda`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offerta`
--
ALTER TABLE `offerta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `azienda_id` (`azienda_id`);

--
-- Indexes for table `responsabile`
--
ALTER TABLE `responsabile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `studente`
--
ALTER TABLE `studente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricola` (`matricola`);

--
-- Indexes for table `tirocinio`
--
ALTER TABLE `tirocinio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tirocinio_ibfk_1` (`azienda_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accordo`
--
ALTER TABLE `accordo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `azienda`
--
ALTER TABLE `azienda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `offerta`
--
ALTER TABLE `offerta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `responsabile`
--
ALTER TABLE `responsabile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `studente`
--
ALTER TABLE `studente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tirocinio`
--
ALTER TABLE `tirocinio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accordo`
--
ALTER TABLE `accordo`
  ADD CONSTRAINT `accordo_ibfk_1` FOREIGN KEY (`studente_id`) REFERENCES `studente` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accordo_ibfk_2` FOREIGN KEY (`tirocinio_id`) REFERENCES `tirocinio` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `offerta`
--
ALTER TABLE `offerta`
  ADD CONSTRAINT `offerta_ibfk_1` FOREIGN KEY (`azienda_id`) REFERENCES `azienda` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tirocinio`
--
ALTER TABLE `tirocinio`
  ADD CONSTRAINT `tirocinio_ibfk_1` FOREIGN KEY (`azienda_id`) REFERENCES `azienda` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
