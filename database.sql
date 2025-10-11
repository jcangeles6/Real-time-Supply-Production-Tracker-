-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2025 at 01:30 PM
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
-- Database: `hotwheels`
--

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('scheduled','in_progress','completed') DEFAULT 'scheduled',
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `product_name`, `quantity`, `status`, `scheduled_at`, `completed_at`, `is_deleted`) VALUES
(227, 'Sir Dennis', 1, 'scheduled', '2025-10-11 11:29:35', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `batch_log`
--

CREATE TABLE `batch_log` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch_log`
--

INSERT INTO `batch_log` (`id`, `batch_id`, `user_id`, `action`, `timestamp`) VALUES
(568, 227, 14, 'Batch Created', '2025-10-11 19:29:35');

-- --------------------------------------------------------

--
-- Table structure for table `batch_materials`
--

CREATE TABLE `batch_materials` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `quantity_used` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity_reserved` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch_materials`
--

INSERT INTO `batch_materials` (`id`, `batch_id`, `stock_id`, `quantity_used`, `created_at`, `quantity_reserved`) VALUES
(308, 227, 68, 2, '2025-10-11 11:29:35', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'kg',
  `supplier` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `name`, `price`, `unit`, `supplier`, `created_at`) VALUES
(1, 'Flour', 20.00, '25kg', 'ABC Mills', '2025-09-28 14:17:31'),
(2, 'Sugar	', 18.00, '25kg', 'Sweet Co.', '2025-09-28 14:17:31'),
(3, 'Butter', 45.00, '10kg', 'Dairy Best', '2025-09-28 14:17:31'),
(4, 'Yeast', 12.00, '5kg', 'BakePro', '2025-09-28 14:17:31');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL,
  `status` enum('available','low','out') DEFAULT 'available',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `quantity`, `unit`, `status`, `updated_at`, `created_at`) VALUES
(66, 'Design', 17, 'pcs', 'available', '2025-10-11 11:14:10', '2025-10-08 05:04:18'),
(67, 'Paper', 45, 'pcs', 'available', '2025-10-11 11:14:10', '2025-10-08 05:04:30'),
(68, 'Ribbon', 40, 'pcs', 'available', '2025-10-11 11:29:35', '2025-10-08 05:04:38'),
(69, 'Vase', 49, 'pcs', 'available', '2025-10-11 11:14:10', '2025-10-08 05:04:44'),
(71, 'Rose', 49, 'pcs', 'available', '2025-10-11 11:14:10', '2025-10-11 00:04:26');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ingredient_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','completed') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `ingredient_id`, `user_id`, `ingredient_name`, `quantity`, `notes`, `unit`, `status`, `requested_at`) VALUES
(1, 0, 0, 'flour', 30, NULL, 'kg', '', '2025-09-28 13:49:02'),
(2, 0, 0, 'flour', 123, NULL, 'kg', '', '2025-09-28 16:51:51'),
(3, 0, 0, 'yeast', 400, NULL, 'kg', '', '2025-09-28 19:52:10'),
(4, 0, 0, 'flour', 1, NULL, 'kg', '', '2025-09-28 19:55:49'),
(5, 0, 0, 'yeast', 14, NULL, 'kg', '', '2025-09-28 19:58:36'),
(6, 0, 0, 'flour', 15, NULL, 'kg', '', '2025-09-28 20:00:40'),
(7, 0, 0, 'sugar', 100, NULL, 'kg', '', '2025-09-28 20:01:20'),
(8, 0, 0, 'sugar', 15, NULL, 'kg', '', '2025-09-28 20:03:59'),
(9, 0, 0, 'yeast', 51, NULL, 'kg', '', '2025-09-28 20:08:51'),
(10, 0, 0, 'yeast', 51, NULL, 'kg', '', '2025-09-28 20:11:17'),
(11, 0, 0, 'sugar', 124, NULL, 'kg', '', '2025-09-28 20:13:35'),
(12, 0, 0, 'sugar', 54, NULL, 'kg', '', '2025-09-28 20:17:30'),
(13, 0, 0, 'flour', 15, NULL, 'kg', 'pending', '2025-10-01 17:08:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `reset_requested` tinyint(1) DEFAULT 0,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `reset_token`, `is_admin`, `reset_requested`, `security_question`, `security_answer`, `failed_attempts`, `locked_until`) VALUES
(2, 'admin', 'administrator@gmail.com', '$2y$10$Tc405XDWqFBsX1WYUHti1esUWm/8qbMYo7sZ4FFLKLoetqfmDZ2pa', NULL, 1, 0, NULL, NULL, 0, NULL),
(3, 'jcangeles6', 'jcangeles6@gmail.com', '$2y$10$0zqLhTIwC1RidG4EeUDsAOPAG0C8z06IjqAUZL5FjXQ/VNMQQjdZi', NULL, 1, 0, 'ano nickname ko?', '$2y$10$ghXoLBM3f6/KgeXIEhfrP.cByINiZVGWtOuDeRNfl6pMcnXqMfOVy', 0, NULL),
(5, 'jcang22', 'jcang22@gmail.com', '$2y$10$AoPoJbpmGNBimUZR815Vpu4pz1j03jYj0QLYMoJtLJ7fj.Dl423w6', NULL, 0, 0, 'What is your first pet\'s name?', '$2y$10$rexNf6YnuEhhHbuwCNHv.umtCXtXKC2EOp8dIldkU6nHsgjl9Ed2.', 0, NULL),
(6, 'alex', 'alexjagonoy@gmail.com', '$2y$10$jIhk9V.npnLu5M9v6t8FlefplkL6TL.p2Rs2bQ/JX7qnr8kac5.se', NULL, 0, 0, 'What is your favorite color?', '$2y$10$NrPNJJjvE4jdW6./U2gji.2icWbkeZ1p87k4UbqQw.Kghj3tsB0u6', 0, NULL),
(7, 'pitoy', 'keanoivanpitoy@gmail.com', '$2y$10$Gb.EdZ1HKB8fsHaViYwf2ehKvvPPXP4tJ4ZLIY0IuPJ0vdDetK4La', NULL, 1, 0, 'What is your favorite color?', '$2y$10$fkkqM0o/8aLWOxWb7rCgLuAjNUjDZ.XamXcxtiSByHxgXxEER20QW', 0, NULL),
(14, 'ivan', 'peanutsfriedrice@gmail.com', '$2y$10$DhWXoXSjSSYWPOKrE/tpWO2GokSUXNC5toNum0FsW2zpRfADx8dmK', NULL, 0, 0, 'What is your favorite color?', '$2y$10$ZQBlvrJmhH/deGzk68m/G.w8YhnCYhWuEcLcOxG1Vl6V6358POzOq', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `batch_log`
--
ALTER TABLE `batch_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `batch_materials`
--
ALTER TABLE `batch_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `stock_id` (`stock_id`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT for table `batch_log`
--
ALTER TABLE `batch_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=569;

--
-- AUTO_INCREMENT for table `batch_materials`
--
ALTER TABLE `batch_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=309;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `batch_materials`
--
ALTER TABLE `batch_materials`
  ADD CONSTRAINT `batch_materials_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `batch_materials_ibfk_2` FOREIGN KEY (`stock_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
