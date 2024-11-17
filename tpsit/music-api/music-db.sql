-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 17, 2024 at 03:22 PM
-- Server version: 8.0.30
-- PHP Version: 8.0.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `my_lorenzoiannelli`
--

-- --------------------------------------------------------

--
-- Table structure for table `MUSICA_artisti`
--

CREATE TABLE `MUSICA_artisti` (
  `ID` int NOT NULL,
  `Nome` varchar(50) NOT NULL,
  `Cognome` varchar(50) NOT NULL,
  `Data_nascita` date DEFAULT NULL,
  `Immagine` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `MUSICA_artisti`
--

INSERT INTO `MUSICA_artisti` (`ID`, `Nome`, `Cognome`, `Data_nascita`, `Immagine`) VALUES
(8, 'Fabrizio', 'Fibrazio', '1976-10-17', 'fabri.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `MUSICA_brani`
--

CREATE TABLE `MUSICA_brani` (
  `ID` int NOT NULL,
  `Titolo` varchar(100) NOT NULL,
  `Album` varchar(50) NOT NULL,
  `Durata` time NOT NULL,
  `Mp3` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `MUSICA_brani`
--

INSERT INTO `MUSICA_brani` (`ID`, `Titolo`, `Album`, `Durata`, `Mp3`) VALUES
(6, 'Non fare la puttana', 'Mr Simpatia', '00:03:15', 'non-fare-la-puttana.mp3');

-- --------------------------------------------------------

--
-- Table structure for table `MUSICA_brani_artisti`
--

CREATE TABLE `MUSICA_brani_artisti` (
  `ID_ARTISTA` int NOT NULL,
  `ID_BRANO` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `MUSICA_artisti`
--
ALTER TABLE `MUSICA_artisti`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `MUSICA_brani`
--
ALTER TABLE `MUSICA_brani`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `MUSICA_brani_artisti`
--
ALTER TABLE `MUSICA_brani_artisti`
  ADD PRIMARY KEY (`ID_ARTISTA`,`ID_BRANO`),
  ADD KEY `ID_ARTISTA` (`ID_ARTISTA`),
  ADD KEY `ID_BRANO` (`ID_BRANO`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `MUSICA_artisti`
--
ALTER TABLE `MUSICA_artisti`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `MUSICA_brani`
--
ALTER TABLE `MUSICA_brani`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `MUSICA_brani_artisti`
--
ALTER TABLE `MUSICA_brani_artisti`
  ADD CONSTRAINT `MUSICA_brani_artisti_ibfk_1` FOREIGN KEY (`ID_ARTISTA`) REFERENCES `MUSICA_artisti` (`ID`),
  ADD CONSTRAINT `MUSICA_brani_artisti_ibfk_2` FOREIGN KEY (`ID_BRANO`) REFERENCES `MUSICA_brani` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
