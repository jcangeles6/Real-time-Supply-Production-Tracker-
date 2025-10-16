-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2025 at 12:30 PM
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
-- Database: `flowershop`
--

-- --------------------------------------------------------

--
-- Table structure for table `backup_ingredients`
--

CREATE TABLE `backup_ingredients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `unit` enum('pcs','kg','g','L','ml') NOT NULL DEFAULT 'pcs',
  `supplier` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `backup_ingredients`
--

INSERT INTO `backup_ingredients` (`id`, `name`, `price`, `unit`, `supplier`, `created_at`) VALUES
(1, 'Flour', 20.00, 'pcs', 'ABC Mills', '2025-09-28 14:17:31'),
(2, 'Sugar	', 18.00, 'pcs', 'Sweet Co.', '2025-09-28 14:17:31'),
(3, 'Butter', 45.00, 'pcs', 'Dairy Best', '2025-09-28 14:17:31'),
(4, 'Yeast', 12.00, 'pcs', 'BakePro', '2025-09-28 14:17:31');

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
(861, '123', 1, 'completed', '2025-10-16 10:22:34', '2025-10-16 10:22:36', 0),
(862, '123', 2, 'in_progress', '2025-10-16 10:22:45', NULL, 1);

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
(2159, 861, 14, 'Batch Created', '2025-10-16 18:22:34'),
(2160, 861, 14, 'Batch Started', '2025-10-16 18:22:35'),
(2161, 861, 14, 'Batch Completed', '2025-10-16 18:22:36'),
(2162, 862, 14, 'Batch Created', '2025-10-16 18:22:45'),
(2163, 862, 14, 'Batch Started', '2025-10-16 18:22:48'),
(2164, 862, 14, 'Batch Deleted', '2025-10-16 18:24:11');

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
(1127, 861, 171, 1, '2025-10-16 10:22:34', 0);

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
(171, 'Rose', 5, 'kg', 'available', '2025-10-16 10:22:35', '2025-10-15 21:18:54'),
(172, 'Sunflower', 6, 'kg', 'available', '2025-10-16 10:24:11', '2025-10-16 06:24:36'),
(173, 'Ribbon', 6, 'kg', 'available', '2025-10-16 09:55:45', '2025-10-16 06:24:44'),
(174, 'Tulips', 6, 'kg', 'available', '2025-10-16 09:55:51', '2025-10-16 06:24:48'),
(175, 'Daisy', 10, 'kg', 'available', '2025-10-16 06:24:52', '2025-10-16 06:24:52'),
(177, 'Soil', 10, 'kg', 'available', '2025-10-16 06:36:21', '2025-10-16 06:36:21'),
(178, 'Ribbonn', 6, 'kg', 'available', '2025-10-16 09:55:35', '2025-10-16 07:41:52');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_backup`
--

CREATE TABLE `inventory_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL,
  `status` enum('available','low','out') DEFAULT 'available',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_backup`
--

INSERT INTO `inventory_backup` (`id`, `item_name`, `quantity`, `unit`, `status`, `updated_at`, `created_at`) VALUES
(67, 'Paper', 4, 'pcs', 'available', '2025-10-13 21:39:30', '2025-10-08 05:04:30'),
(68, 'Ribbon', 5, 'pcs', 'available', '2025-10-13 21:18:53', '2025-10-08 05:04:38'),
(69, 'Vase', 5, 'pcs', 'available', '2025-10-13 21:18:56', '2025-10-08 05:04:44'),
(71, 'Rose', 5, 'pcs', 'available', '2025-10-13 21:18:57', '2025-10-11 00:04:26');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `batch_id`, `type`, `message`, `created_at`) VALUES
(534, 861, 'in_progress', '🛠️ 123 - Batch Started', '2025-10-16 10:22:35'),
(535, 861, 'completed', '✔️ 123 - Batch Completed', '2025-10-16 10:22:36'),
(536, 862, 'deleted', '🛠️ 123 - Batch Started (Canceled)', '2025-10-16 10:22:48');

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
  `status` enum('pending','approved','completed','denied') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_thresholds`
--

CREATE TABLE `stock_thresholds` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `threshold` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_thresholds`
--

INSERT INTO `stock_thresholds` (`id`, `item_id`, `threshold`, `created_at`, `updated_at`) VALUES
(102, 171, 4, '2025-10-15 21:18:54', '2025-10-15 21:18:54'),
(103, 172, 5, '2025-10-16 06:24:36', '2025-10-16 06:24:36'),
(104, 173, 5, '2025-10-16 06:24:44', '2025-10-16 06:24:44'),
(105, 174, 5, '2025-10-16 06:24:48', '2025-10-16 06:24:48'),
(106, 175, 5, '2025-10-16 06:24:52', '2025-10-16 06:24:52'),
(108, 177, 5, '2025-10-16 06:36:21', '2025-10-16 06:36:21'),
(109, 178, 4, '2025-10-16 07:41:52', '2025-10-16 07:41:52');

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
(14, 'ivan', 'peanutsfriedrice@gmail.com', '$2y$10$t39rU9mpLZWMuGsLIMb/Oe9OGcJgzmRqOAtD.0tz/jnXi0vTF5IYC', NULL, 0, 0, 'What is your favorite color?', '$2y$10$ZQBlvrJmhH/deGzk68m/G.w8YhnCYhWuEcLcOxG1Vl6V6358POzOq', 0, NULL),
(15, 'rat', 'peanutsfriedrice@gmail.com', '$2y$10$LKncXEuK3vji37ApvKKcl.SQBxkTbO5NzpZZe9AglHgIsCQfgPuzm', NULL, 0, 0, 'What is your favorite bread?', '$2y$10$vwU4W9GEL4qg6FUIPl7NVOcl/i0oM01v2Fg5QxQPmJnxbkTyzodjG', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_notifications`
--

INSERT INTO `user_notifications` (`id`, `user_id`, `notification_id`, `is_read`, `read_at`) VALUES
(4187, 2, 534, 0, NULL),
(4188, 6, 534, 0, NULL),
(4189, 14, 534, 0, NULL),
(4190, 5, 534, 0, NULL),
(4191, 3, 534, 0, NULL),
(4192, 7, 534, 0, NULL),
(4193, 15, 534, 0, NULL),
(4194, 2, 535, 0, NULL),
(4195, 6, 535, 0, NULL),
(4196, 14, 535, 1, '2025-10-16 18:28:23'),
(4197, 5, 535, 0, NULL),
(4198, 3, 535, 0, NULL),
(4199, 7, 535, 0, NULL),
(4200, 15, 535, 0, NULL),
(4201, 2, 536, 0, NULL),
(4202, 6, 536, 0, NULL),
(4203, 14, 536, 0, NULL),
(4204, 5, 536, 0, NULL),
(4205, 3, 536, 0, NULL),
(4206, 7, 536, 0, NULL),
(4207, 15, 536, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backup_ingredients`
--
ALTER TABLE `backup_ingredients`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_thresholds`
--
ALTER TABLE `stock_thresholds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_notification_unique` (`user_id`,`notification_id`),
  ADD UNIQUE KEY `unique_user_notif` (`user_id`,`notification_id`),
  ADD KEY `notification_id` (`notification_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `backup_ingredients`
--
ALTER TABLE `backup_ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=863;

--
-- AUTO_INCREMENT for table `batch_log`
--
ALTER TABLE `batch_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2165;

--
-- AUTO_INCREMENT for table `batch_materials`
--
ALTER TABLE `batch_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1129;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=537;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `stock_thresholds`
--
ALTER TABLE `stock_thresholds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4208;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `batch_materials`
--
ALTER TABLE `batch_materials`
  ADD CONSTRAINT `batch_materials_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `batch_materials_ibfk_2` FOREIGN KEY (`stock_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_thresholds`
--
ALTER TABLE `stock_thresholds`
  ADD CONSTRAINT `stock_thresholds_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_notifications_ibfk_2` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
