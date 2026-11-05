-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2026 at 08:51 AM
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
  `price` decimal(10,2) NOT NULL,
  `category` enum('Main','Soups','Drinks') NOT NULL DEFAULT 'Main',
  `sub_category` enum('Main','Add_Ons','Pitcher','Glass') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `price`, `category`, `sub_category`) VALUES
(1, 'Crispy pata', 650.00, 'Main', 'Main'),
(2, 'Chapsuey', 300.00, 'Main', 'Main'),
(3, 'Lengua', 460.00, 'Main', 'Main'),
(4, 'Rice', 50.00, 'Main', 'Add_Ons'),
(5, 'Bulalo', 700.00, 'Soups', NULL),
(6, 'Whole Crispy Ulo', 2400.00, 'Main', 'Main'),
(7, 'Half Crispy Ulo', 1200.00, 'Main', 'Main'),
(8, 'Shanghai', 300.00, 'Main', 'Main'),
(9, 'Canton', 300.00, 'Main', 'Main'),
(10, 'Beefsteak', 460.00, 'Main', 'Main'),
(11, 'Sotanghon Dry', 300.00, 'Main', 'Main'),
(12, 'Tanigue Steak', 460.00, 'Main', 'Main'),
(13, 'Kilawin Tanigue', 460.00, 'Main', 'Main'),
(14, 'Hilabos na Hipon', 460.00, 'Main', 'Main'),
(15, 'Hipon Rebusado', 460.00, 'Main', 'Main'),
(16, 'Calamares', 460.00, 'Main', 'Main'),
(17, 'Spicy Shrimp', 460.00, 'Main', 'Main'),
(18, 'Fishball', 300.00, 'Main', 'Main'),
(19, 'Meatball', 300.00, 'Main', 'Main'),
(20, 'Lapu-Lapu Sweet & Sour', 460.00, 'Main', 'Main'),
(21, 'Whole Crispy Chicken', 600.00, 'Main', 'Main'),
(22, 'Half Crispy Chicken', 300.00, 'Main', 'Main'),
(23, 'Whole Fried Chicken', 600.00, 'Main', 'Main'),
(24, 'Half Fried Chicken', 300.00, 'Main', 'Main'),
(25, 'Sinigang na Hipon', 460.00, 'Soups', NULL),
(26, 'Sinigang na Tanigue', 460.00, 'Soups', NULL),
(27, 'Sotanghon Soup', 300.00, 'Soups', NULL),
(28, 'Buko Shell', 90.00, 'Drinks', 'Glass'),
(29, 'Buko Juice (Solo)', 60.00, 'Drinks', 'Glass'),
(30, 'Buko Juice (Pitcher)', 180.00, 'Drinks', 'Pitcher'),
(31, 'Iced Tea (Solo)', 45.00, 'Drinks', 'Glass'),
(32, 'Iced Tea (Pitcher)', 180.00, 'Drinks', 'Pitcher'),
(33, 'Melon Juice (Solo)', 45.00, 'Drinks', 'Glass'),
(34, 'Melon Juice (Pitcher)', 180.00, 'Drinks', 'Pitcher'),
(35, 'Pineapple Juice (Solo)', 60.00, 'Drinks', 'Glass'),
(36, 'Pineapple Juice (Pitcher)', 180.00, 'Drinks', 'Pitcher'),
(37, 'Calamansi Juice (Solo)', 45.00, 'Drinks', 'Glass'),
(38, 'Calamansi Juice (Pitcher)', 180.00, 'Drinks', 'Pitcher'),
(39, 'Palameg', 60.00, 'Drinks', 'Glass'),
(40, 'Coke', 45.00, 'Drinks', 'Glass'),
(41, 'Sprite', 45.00, 'Drinks', 'Glass'),
(42, 'Coffee', 45.00, 'Drinks', 'Glass'),
(43, 'Beer', 95.00, 'Drinks', 'Glass'),
(44, 'Mineral Water', 40.00, 'Drinks', 'Glass');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
