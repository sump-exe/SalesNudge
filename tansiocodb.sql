-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2026 at 04:58 AM
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
-- Database: `tansiocodb`
--

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `price`) VALUES
(1, 'Crispy pata', 650.00),
(2, 'Chapsuey', 300.00),
(3, 'Lengua', 460.00),
(4, 'Rice', 50.00),
(5, 'Bulalo', 700.00),
(6, 'Whole Crispy Ulo', 2400.00),
(7, 'Half Crispy Ulo', 1200.00),
(8, 'Shanghai', 300.00),
(9, 'Canton', 300.00),
(10, 'Beefsteak', 460.00),
(11, 'Sotanghon Dry', 300.00),
(12, 'Tanigue Steak', 460.00),
(13, 'Kilawin Tanigue', 460.00),
(14, 'Hilabos na Hipon', 460.00),
(15, 'Hipon Rebusado', 460.00),
(16, 'Calamares', 460.00),
(17, 'Spicy Shrimp', 460.00),
(18, 'Fishball', 300.00),
(19, 'Meatball', 300.00),
(20, 'Lapu-Lapu Sweet & Sour', 460.00),
(21, 'Whole Crispy Chicken', 600.00),
(22, 'Half Crispy Chicken', 300.00),
(23, 'Whole Fried Chicken', 600.00),
(24, 'Half Fried Chicken', 300.00),
(25, 'Sinigang na Hipon', 460.00),
(26, 'Sinigang na Tanigue', 460.00),
(27, 'Sotanghon Soup', 300.00),
(28, 'Buko Shell', 90.00),
(29, 'Buko Juice (Solo)', 60.00),
(30, 'Buko Juice (Pitcher)', 180.00),
(31, 'Iced Tea (Solo)', 45.00),
(32, 'Iced Tea (Pitcher)', 180.00),
(33, 'Melon Juice (Solo)', 45.00),
(34, 'Melon Juice (Pitcher)', 180.00),
(35, 'Pineapple Juice (Solo)', 60.00),
(36, 'Pineapple Juice (Pitcher)', 180.00),
(37, 'Calamansi Juice (Solo)', 45.00),
(38, 'Calamansi Juice (Pitcher)', 180.00),
(39, 'Palameg', 60.00),
(40, 'Coke', 45.00),
(41, 'Sprite', 45.00),
(42, 'Coffee', 45.00),
(43, 'Beer', 95.00),
(44, 'Mineral Water', 40.00);

-- --------------------------------------------------------

--
-- Table structure for table `pos_sales`
--

CREATE TABLE `pos_sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cashier_username` varchar(50) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `change_due` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_sales`
--

INSERT INTO `pos_sales` (`id`, `cashier_username`, `total`, `amount_paid`, `change_due`, `created_at`) VALUES
(1, 'cas2.0', 1010.00, 1100.00, 90.00, '2026-10-24 03:42:00'),
(2, 'cas2.0', 1270.00, 1500.00, 230.00, '2026-10-24 04:30:00'),
(3, 'cas3.0', 1465.00, 2000.00, 535.00, '2026-10-24 10:15:00'),
(4, 'cas2.0', 630.00, 630.00, 0.00, '2026-10-25 04:05:00'),
(5, 'cas2.0', 1120.00, 1200.00, 80.00, '2026-10-25 05:10:00'),
(6, 'Cas', 3510.00, 4000.00, 490.00, '2026-10-25 11:20:00'),
(7, 'cas2.0', 480.00, 1000.00, 520.00, '2026-10-26 03:55:00'),
(8, 'cas3.0', 1440.00, 1440.00, 0.00, '2026-10-26 04:48:00'),
(9, 'cas2.0', 1325.00, 1400.00, 75.00, '2026-10-26 10:40:00'),
(10, 'cas2.0', 1470.00, 1500.00, 30.00, '2026-10-27 04:20:00'),
(11, 'cas2.0', 390.00, 1000.00, 610.00, '2026-10-27 05:05:00'),
(12, 'cas3.0', 2490.00, 2490.00, 0.00, '2026-10-27 11:00:00'),
(13, 'cas2.0', 510.00, 600.00, 90.00, '2026-10-28 03:30:00'),
(14, 'cas2.0', 1520.00, 2000.00, 480.00, '2026-10-28 04:15:00'),
(15, 'Cas', 1425.00, 2000.00, 575.00, '2026-10-28 10:55:00'),
(16, 'cas2.0', 1305.00, 1305.00, 0.00, '2026-10-28 12:10:00'),
(17, 'cas2.0', 640.00, 700.00, 60.00, '2026-10-29 04:00:00'),
(18, 'cas3.0', 2780.00, 3000.00, 220.00, '2026-10-29 04:50:00'),
(19, 'cas2.0', 870.00, 1000.00, 130.00, '2026-10-29 09:45:00'),
(20, 'cas2.0', 1270.00, 1270.00, 0.00, '2026-10-29 11:30:00'),
(21, 'cas2.0', 390.00, 400.00, 10.00, '2026-10-30 02:40:00'),
(22, 'cas2.0', 1310.00, 1500.00, 190.00, '2026-10-30 03:25:00'),
(23, 'cas3.0', 1930.00, 2000.00, 70.00, '2026-10-30 04:10:00'),
(24, 'Cas', 870.00, 870.00, 0.00, '2026-10-30 05:00:00'),
(25, 'cas2.0', 1.00, 1.00, 65.00, '2026-10-30 07:04:23');

-- --------------------------------------------------------

--
-- Table structure for table `pos_sale_items`
--

CREATE TABLE `pos_sale_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_sale_items`
--

INSERT INTO `pos_sale_items` (`id`, `sale_id`, `menu_item_id`, `item_name`, `quantity`, `unit_price`, `line_total`) VALUES
(1, 1, 1, 'Crispy Pata', 1, 650.00, 650.00),
(2, 1, 4, 'Rice', 4, 45.00, 180.00),
(3, 1, 32, 'Iced Tea (Pitcher)', 1, 180.00, 180.00),
(4, 2, 5, 'Bulalo', 1, 700.00, 700.00),
(5, 2, 2, 'Chapsuey', 1, 300.00, 300.00),
(6, 2, 4, 'Rice', 3, 45.00, 135.00),
(7, 2, 40, 'Coke', 3, 45.00, 135.00),
(8, 3, 21, 'Whole Crispy Chicken', 1, 600.00, 600.00),
(9, 3, 25, 'Sinigang na Hipon', 1, 460.00, 460.00),
(10, 3, 4, 'Rice', 5, 45.00, 225.00),
(11, 3, 30, 'Buko Juice (Pitcher)', 1, 180.00, 180.00),
(12, 4, 10, 'Beefsteak', 1, 460.00, 460.00),
(13, 4, 4, 'Rice', 2, 45.00, 90.00),
(14, 4, 44, 'Mineral Water', 2, 40.00, 80.00),
(15, 5, 3, 'Lengua', 1, 460.00, 460.00),
(16, 5, 2, 'Chapsuey', 1, 300.00, 300.00),
(17, 5, 4, 'Rice', 4, 45.00, 180.00),
(18, 5, 34, 'Melon Juice (Pitcher)', 1, 180.00, 180.00),
(19, 6, 6, 'Whole Crispy Ulo', 1, 2400.00, 2400.00),
(20, 6, 4, 'Rice', 8, 45.00, 360.00),
(21, 6, 43, 'Beer', 6, 95.00, 570.00),
(22, 6, 38, 'Calamansi Juice (Pitcher)', 1, 180.00, 180.00),
(23, 7, 24, 'Half Fried Chicken', 1, 300.00, 300.00),
(24, 7, 4, 'Rice', 2, 45.00, 90.00),
(25, 7, 41, 'Sprite', 2, 45.00, 90.00),
(26, 8, 8, 'Shanghai', 1, 300.00, 300.00),
(27, 8, 9, 'Canton', 1, 300.00, 300.00),
(28, 8, 18, 'Fishball', 1, 300.00, 300.00),
(29, 8, 4, 'Rice', 4, 45.00, 180.00),
(30, 8, 32, 'Iced Tea (Pitcher)', 2, 180.00, 360.00),
(31, 9, 12, 'Tanigue Steak', 1, 460.00, 460.00),
(32, 9, 26, 'Sinigang na Tanigue', 1, 460.00, 460.00),
(33, 9, 4, 'Rice', 3, 45.00, 135.00),
(34, 9, 28, 'Buko Shell', 3, 90.00, 270.00),
(35, 10, 1, 'Crispy Pata', 1, 650.00, 650.00),
(36, 10, 16, 'Calamares', 1, 460.00, 460.00),
(37, 10, 4, 'Rice', 4, 45.00, 180.00),
(38, 10, 40, 'Coke', 2, 45.00, 90.00),
(39, 10, 41, 'Sprite', 2, 45.00, 90.00),
(40, 11, 11, 'Sotanghon Dry', 1, 300.00, 300.00),
(41, 11, 4, 'Rice', 1, 45.00, 45.00),
(42, 11, 42, 'Coffee', 1, 45.00, 45.00),
(43, 12, 7, 'Half Crispy Ulo', 1, 1200.00, 1200.00),
(44, 12, 15, 'Hipon Rebusado', 1, 460.00, 460.00),
(45, 12, 4, 'Rice', 6, 45.00, 270.00),
(46, 12, 43, 'Beer', 4, 95.00, 380.00),
(47, 12, 36, 'Pineapple Juice (Pitcher)', 1, 180.00, 180.00),
(48, 13, 19, 'Meatball', 1, 300.00, 300.00),
(49, 13, 4, 'Rice', 2, 45.00, 90.00),
(50, 13, 39, 'Palameg', 2, 60.00, 120.00),
(51, 14, 5, 'Bulalo', 1, 700.00, 700.00),
(52, 14, 17, 'Spicy Shrimp', 1, 460.00, 460.00),
(53, 14, 4, 'Rice', 4, 45.00, 180.00),
(54, 14, 37, 'Calamansi Juice (Solo)', 4, 45.00, 180.00),
(55, 15, 20, 'Lapu-Lapu Sweet & Sour', 1, 460.00, 460.00),
(56, 15, 13, 'Kilawin Tanigue', 1, 460.00, 460.00),
(57, 15, 4, 'Rice', 4, 45.00, 180.00),
(58, 15, 43, 'Beer', 3, 95.00, 285.00),
(59, 15, 44, 'Mineral Water', 1, 40.00, 40.00),
(60, 16, 23, 'Whole Fried Chicken', 1, 600.00, 600.00),
(61, 16, 2, 'Chapsuey', 1, 300.00, 300.00),
(62, 16, 4, 'Rice', 5, 45.00, 225.00),
(63, 16, 34, 'Melon Juice (Pitcher)', 1, 180.00, 180.00),
(64, 17, 3, 'Lengua', 1, 460.00, 460.00),
(65, 17, 4, 'Rice', 2, 45.00, 90.00),
(66, 17, 31, 'Iced Tea (Solo)', 2, 45.00, 90.00),
(67, 18, 1, 'Crispy Pata', 2, 650.00, 1300.00),
(68, 18, 2, 'Chapsuey', 1, 300.00, 300.00),
(69, 18, 14, 'Hilabos na Hipon', 1, 460.00, 460.00),
(70, 18, 4, 'Rice', 8, 45.00, 360.00),
(71, 18, 30, 'Buko Juice (Pitcher)', 2, 180.00, 360.00),
(72, 19, 22, 'Half Crispy Chicken', 1, 300.00, 300.00),
(73, 19, 27, 'Sotanghon Soup', 1, 300.00, 300.00),
(74, 19, 4, 'Rice', 3, 45.00, 135.00),
(75, 19, 40, 'Coke', 3, 45.00, 135.00),
(76, 20, 10, 'Beefsteak', 1, 460.00, 460.00),
(77, 20, 12, 'Tanigue Steak', 1, 460.00, 460.00),
(78, 20, 4, 'Rice', 4, 45.00, 180.00),
(79, 20, 42, 'Coffee', 2, 45.00, 90.00),
(80, 20, 44, 'Mineral Water', 2, 40.00, 80.00),
(81, 21, 27, 'Sotanghon Soup', 1, 300.00, 300.00),
(82, 21, 4, 'Rice', 1, 45.00, 45.00),
(83, 21, 42, 'Coffee', 1, 45.00, 45.00),
(84, 22, 1, 'Crispy Pata', 1, 650.00, 650.00),
(85, 22, 2, 'Chapsuey', 1, 300.00, 300.00),
(86, 22, 4, 'Rice', 4, 45.00, 180.00),
(87, 22, 41, 'Sprite', 2, 45.00, 90.00),
(88, 22, 40, 'Coke', 2, 45.00, 90.00),
(89, 23, 21, 'Whole Crispy Chicken', 1, 600.00, 600.00),
(90, 23, 5, 'Bulalo', 1, 700.00, 700.00),
(91, 23, 4, 'Rice', 6, 45.00, 270.00),
(92, 23, 32, 'Iced Tea (Pitcher)', 2, 180.00, 360.00),
(93, 24, 8, 'Shanghai', 1, 300.00, 300.00),
(94, 24, 9, 'Canton', 1, 300.00, 300.00),
(95, 24, 4, 'Rice', 3, 45.00, 135.00),
(96, 24, 33, 'Melon Juice (Solo)', 3, 45.00, 135.00),
(97, 25, 42, 'Coffee', 1, 45.00, 45.00),
(98, 25, 37, 'Calamansi Juice (Solo)', 2, 45.00, 90.00),
(99, 25, 1, 'Crispy pata', 2, 650.00, 1.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','cashier') NOT NULL DEFAULT 'cashier',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$LVEgeu/LxSoWVHaim5KPKul1xw4S9c5cyv.f76KsiKR849e6LcgGW', 'admin', '2026-10-28 03:55:23'),
(2, 'cashier', '$2y$10$d9QwzXNBSyZdpyG40C4XJeJ6UCQtJsOMUHIXLKhvVyNoyZvcYvpJu', 'cashier', '2026-10-28 03:56:44'),
(3, 'manager', '$2y$10$s3cTT1osS6Se4qADxmNyveWHb3VOnc4K82wvHQwMBahxDFqQGxd26', 'manager', '2026-10-28 03:56:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_sales`
--
ALTER TABLE `pos_sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_sale_items`
--
ALTER TABLE `pos_sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pos_sale_items_sale_id` (`sale_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `pos_sales`
--
ALTER TABLE `pos_sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `pos_sale_items`
--
ALTER TABLE `pos_sale_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pos_sale_items`
--
ALTER TABLE `pos_sale_items`
  ADD CONSTRAINT `fk_pos_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
