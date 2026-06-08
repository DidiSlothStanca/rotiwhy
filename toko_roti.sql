-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 08, 2026 at 03:52 PM
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
-- Database: `toko_roti`
--

-- --------------------------------------------------------

--
-- Table structure for table `antrian`
--

CREATE TABLE `antrian` (
  `id_antrian` int(11) NOT NULL,
  `nomor_antrian` int(11) NOT NULL,
  `status` enum('open','closed','selesai','dibatalkan') DEFAULT 'open',
  `jam_dibuka` datetime DEFAULT current_timestamp(),
  `jam_ditutup` datetime DEFAULT NULL,
  `nama_pembeli` varchar(100) DEFAULT NULL,
  `nama_roti` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `antrian`
--

INSERT INTO `antrian` (`id_antrian`, `nomor_antrian`, `status`, `jam_dibuka`, `jam_ditutup`, `nama_pembeli`, `nama_roti`) VALUES
(1, 1, 'closed', '2026-06-06 04:08:31', '2026-06-06 04:08:38', NULL, NULL),
(2, 2, 'closed', '2026-06-06 04:12:19', '2026-06-06 04:12:27', 'Tor', 'Kue Cokelat'),
(3, 3, 'closed', '2026-06-06 04:14:26', '2026-06-06 04:14:36', 'Letno', 'Croissant'),
(4, 4, 'closed', '2026-06-06 04:14:44', '2026-06-06 04:15:02', NULL, NULL),
(5, 5, 'closed', '2026-06-06 04:14:51', '2026-06-06 04:15:00', NULL, NULL),
(6, 6, 'closed', '2026-06-06 05:44:40', '2026-06-06 05:45:19', 'Emal', 'Croissant'),
(7, 7, 'closed', '2026-06-06 05:44:53', '2026-06-06 05:45:16', 'Lukman', 'Kue Cokelat'),
(8, 8, 'closed', '2026-06-06 05:46:50', '2026-06-06 05:47:04', 'Onmar', 'Croissant'),
(9, 9, 'closed', '2026-06-06 06:30:35', '2026-06-06 06:39:16', NULL, NULL),
(10, 10, 'closed', '2026-06-06 06:45:09', '2026-06-06 06:49:22', 'Agnes', 'Croissant'),
(11, 11, 'closed', '2026-06-06 06:45:28', '2026-06-06 06:49:20', 'Lucas', 'Croissant'),
(12, 12, 'closed', '2026-06-06 06:49:12', '2026-06-06 06:49:18', 'Pler', 'Croissant'),
(13, 13, 'closed', '2026-06-06 06:56:46', '2026-06-06 06:56:52', 'Pler12', 'Roti Tawar'),
(14, 1, 'closed', '2026-06-08 02:28:48', '2026-06-08 02:28:59', 'Tuma', 'Croissant'),
(15, 2, 'closed', '2026-06-08 02:30:32', '2026-06-08 02:31:01', 'lok', 'Croissant'),
(18, 3, 'closed', '2026-06-08 02:47:23', '2026-06-08 02:53:01', 'papapipipupu', 'Croissant'),
(19, 4, 'closed', '2026-06-08 02:51:55', '2026-06-08 02:52:58', NULL, NULL),
(22, 5, 'selesai', '2026-06-08 02:56:58', NULL, 'qweqr', 'Croissant'),
(23, 6, 'dibatalkan', '2026-06-08 02:57:10', NULL, 'afgeft', 'Croissant'),
(24, 7, 'dibatalkan', '2026-06-08 15:04:36', NULL, 'Dappa', 'Roti Jumbo'),
(25, 8, 'dibatalkan', '2026-06-08 15:05:23', NULL, 'Dappa', 'Roti Jumbo'),
(26, 9, 'dibatalkan', '2026-06-08 15:09:43', NULL, 'hitam', 'Roti Jumbo');

-- --------------------------------------------------------

--
-- Table structure for table `roti`
--

CREATE TABLE `roti` (
  `id_roti` int(11) NOT NULL,
  `nama_roti` varchar(50) NOT NULL,
  `harga` int(11) NOT NULL,
  `stok_awal` int(11) NOT NULL DEFAULT 50,
  `tanggal_update` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roti`
--

INSERT INTO `roti` (`id_roti`, `nama_roti`, `harga`, `stok_awal`, `tanggal_update`) VALUES
(1, 'Croissant', 6000, 229, '2026-06-08'),
(2, 'Roti Tawar', 12000, 50, '2026-06-06'),
(3, 'Kue Cokelat', 25000, 55, '2026-06-06'),
(4, 'Donat Gula', 8000, 23, '2026-06-06'),
(5, 'Baguette', 18000, 43, '2026-06-06'),
(6, 'Roti Abon', 14000, 46, '2026-06-06'),
(8, 'Roti Jumbo', 3000, 21, '2026-06-08');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `nama_pembeli` varchar(100) NOT NULL,
  `id_roti` int(11) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `nama_pembeli`, `id_roti`, `qty`, `total_harga`, `tanggal`) VALUES
(9, 'Lukman', 3, 5, 125000, '2026-06-06'),
(10, 'Onmar', 1, 39, 585000, '2026-06-06'),
(11, 'Agnes', 1, 4, 60000, '2026-06-06'),
(12, 'Lucas', 1, 55, 825000, '2026-06-06'),
(14, 'Pler12', 2, 4, 48000, '2026-06-06'),
(15, 'Tuma', 1, 20, 300000, '2026-06-08'),
(16, 'lok', 1, 60, 900000, '2026-06-08'),
(20, 'asdwe', 1, 2, 30000, '2026-06-08'),
(21, 'gasfa', 1, 2, 30000, '2026-06-08'),
(22, 'qweqr', 1, 2, 30000, '2026-06-08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','kasir') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`, `role`) VALUES
(6, 'didibanu', '25d55ad283aa400af464c76d713c07ad', 'Didi Banu', 'admin'),
(8, 'jarvis', 'c4ca4238a0b923820dcc509a6f75849b', 'Jarvis van roni', 'kasir'),
(9, 'aeawe', '3a1038d7091e869f41e4d676e756de31', 'awewaedwa', 'kasir');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `antrian`
--
ALTER TABLE `antrian`
  ADD PRIMARY KEY (`id_antrian`),
  ADD KEY `status` (`status`),
  ADD KEY `jam_dibuka` (`jam_dibuka`);

--
-- Indexes for table `roti`
--
ALTER TABLE `roti`
  ADD PRIMARY KEY (`id_roti`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_roti` (`id_roti`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `antrian`
--
ALTER TABLE `antrian`
  MODIFY `id_antrian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `roti`
--
ALTER TABLE `roti`
  MODIFY `id_roti` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_roti`) REFERENCES `roti` (`id_roti`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
