-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2025 at 09:21 PM
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
-- Database: `maindb`
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
  `started_at` datetime DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `product_name`, `quantity`, `status`, `scheduled_at`, `started_at`, `completed_at`, `is_deleted`) VALUES
(1506, 'Test 1', 1, 'in_progress', '2025-11-05 13:14:55', NULL, NULL, 0),
(1507, 'Test 2', 1, 'completed', '2025-11-05 13:15:02', NULL, '2025-11-05 13:15:38', 0),
(1508, 'Test 3', 1, 'scheduled', '2025-11-05 13:15:10', NULL, NULL, 0),
(1509, 'Test 4', 1, 'in_progress', '2025-11-05 13:15:18', NULL, NULL, 0),
(1511, 'Test 5', 1, 'scheduled', '2025-11-05 13:16:25', NULL, NULL, 0),
(1512, 'Test 6', 1, 'scheduled', '2025-11-05 13:17:33', NULL, NULL, 0);

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
(3786, 1473, 14, 'Batch Created', '2025-11-05 18:50:38'),
(3787, 1473, 14, 'Batch Started', '2025-11-05 18:50:40'),
(3788, 1473, 14, 'Batch Deleted', '2025-11-05 18:50:43'),
(3789, 1474, 14, 'Batch Created', '2025-11-05 18:50:59'),
(3790, 1474, 14, 'Batch Started', '2025-11-05 18:51:02'),
(3791, 1474, 14, 'Batch Deleted', '2025-11-05 18:51:04'),
(3792, 1475, 14, 'Batch Created', '2025-11-05 18:51:35'),
(3793, 1476, 14, 'Batch Created', '2025-11-05 18:51:47'),
(3794, 1476, 14, 'Batch Started', '2025-11-05 18:52:02'),
(3795, 1475, 14, 'Batch Started', '2025-11-05 18:52:03'),
(3796, 1475, 14, 'Batch Deleted', '2025-11-05 18:54:28'),
(3797, 1476, 14, 'Batch Deleted', '2025-11-05 18:54:30'),
(3798, 1477, 14, 'Batch Created', '2025-11-05 18:55:21'),
(3799, 1477, 14, 'Batch Deleted', '2025-11-05 18:59:53'),
(3800, 1478, 14, 'Batch Created', '2025-11-05 19:00:50'),
(3801, 1478, 14, 'Batch Updated', '2025-11-05 19:00:55'),
(3802, 1478, 14, 'Batch Started', '2025-11-05 19:01:08'),
(3803, 1478, 14, 'Batch Updated', '2025-11-05 19:01:14'),
(3804, 1478, 14, 'Batch Deleted', '2025-11-05 19:01:30'),
(3805, 1479, 14, 'Batch Created', '2025-11-05 19:07:23'),
(3806, 1479, 14, 'Batch Started', '2025-11-05 19:08:46'),
(3807, 1479, 14, 'Batch Deleted', '2025-11-05 19:09:00'),
(3808, 1481, 14, 'Batch Created', '2025-11-05 19:11:03'),
(3809, 1481, 14, 'Batch Started', '2025-11-05 19:12:10'),
(3810, 1481, 14, 'Batch Updated', '2025-11-05 19:12:13'),
(3811, 1481, 14, 'Batch Deleted', '2025-11-05 19:12:52'),
(3812, 1482, 14, 'Batch Created', '2025-11-05 19:13:11'),
(3813, 1482, 14, 'Batch Deleted', '2025-11-05 19:17:05'),
(3814, 1483, 14, 'Batch Created', '2025-11-05 19:27:34'),
(3815, 1483, 14, 'Batch Updated', '2025-11-05 19:27:40'),
(3816, 1483, 14, 'Batch Started', '2025-11-05 19:27:43'),
(3817, 1483, 14, 'Batch Updated', '2025-11-05 19:27:58'),
(3818, 1483, 14, 'Batch Deleted', '2025-11-05 19:28:01'),
(3819, 1484, 14, 'Batch Created', '2025-11-05 19:28:40'),
(3820, 1484, 14, 'Batch Started', '2025-11-05 19:39:02'),
(3821, 1484, 14, 'Batch Updated', '2025-11-05 19:39:15'),
(3822, 1484, 14, 'Batch Deleted', '2025-11-05 19:39:28'),
(3823, 1485, 14, 'Batch Created', '2025-11-05 19:41:16'),
(3824, 1485, 14, 'Batch Updated', '2025-11-05 19:41:22'),
(3825, 1485, 14, 'Batch Deleted', '2025-11-05 19:41:55'),
(3826, 1486, 14, 'Batch Created', '2025-11-05 19:41:59'),
(3827, 1486, 14, 'Batch Deleted', '2025-11-05 19:42:20'),
(3828, 1487, 14, 'Batch Created', '2025-11-05 19:42:35'),
(3829, 1487, 14, 'Batch Deleted', '2025-11-05 19:42:46'),
(3830, 1488, 14, 'Batch Created', '2025-11-05 19:44:23'),
(3831, 1488, 14, 'Batch Started', '2025-11-05 19:46:51'),
(3832, 1488, 14, 'Batch Deleted', '2025-11-05 19:46:57'),
(3833, 1489, 14, 'Batch Created', '2025-11-05 19:47:03'),
(3834, 1489, 14, 'Batch Deleted', '2025-11-05 19:48:08'),
(3835, 1491, 14, 'Batch Created', '2025-11-05 19:52:24'),
(3836, 1491, 14, 'Batch Updated', '2025-11-05 19:53:08'),
(3837, 1491, 14, 'Batch Deleted', '2025-11-05 19:53:17'),
(3838, 1492, 14, 'Batch Created', '2025-11-05 19:53:57'),
(3839, 1493, 14, 'Batch Created', '2025-11-05 19:54:04'),
(3840, 1494, 14, 'Batch Created', '2025-11-05 19:54:13'),
(3841, 1494, 14, 'Batch Started', '2025-11-05 19:54:22'),
(3842, 1493, 14, 'Batch Started', '2025-11-05 19:54:37'),
(3843, 1492, 14, 'Batch Started', '2025-11-05 19:54:43'),
(3844, 1494, 14, 'Batch Updated', '2025-11-05 19:56:49'),
(3845, 1494, 14, 'Batch Deleted', '2025-11-05 20:08:02'),
(3846, 1495, 14, 'Batch Created', '2025-11-05 20:08:06'),
(3847, 1495, 14, 'Batch Started', '2025-11-05 20:08:08'),
(3848, 1495, 14, 'Batch Updated', '2025-11-05 20:08:16'),
(3849, 1492, 14, 'Batch Deleted', '2025-11-05 20:08:32'),
(3850, 1493, 14, 'Batch Updated', '2025-11-05 20:08:37'),
(3851, 1493, 14, 'Batch Deleted', '2025-11-05 20:09:17'),
(3852, 1495, 14, 'Batch Updated', '2025-11-05 20:09:20'),
(3853, 1495, 14, 'Batch Deleted', '2025-11-05 20:09:57'),
(3854, 1496, 14, 'Batch Created', '2025-11-05 20:10:04'),
(3855, 1496, 14, 'Batch Started', '2025-11-05 20:10:06'),
(3856, 1496, 14, 'Batch Updated', '2025-11-05 20:10:09'),
(3857, 1496, 14, 'Batch Updated', '2025-11-05 20:10:13'),
(3858, 1496, 14, 'Batch Updated', '2025-11-05 20:10:21'),
(3859, 1496, 14, 'Batch Deleted', '2025-11-05 20:10:30'),
(3860, 1497, 14, 'Batch Created', '2025-11-05 20:12:27'),
(3861, 1497, 14, 'Batch Started', '2025-11-05 20:12:29'),
(3862, 1497, 14, 'Batch Deleted', '2025-11-05 20:14:37'),
(3863, 1498, 14, 'Batch Created', '2025-11-05 20:28:58'),
(3864, 1498, 14, 'Batch Started', '2025-11-05 20:29:18'),
(3865, 1498, 14, 'Batch Deleted', '2025-11-05 20:29:39'),
(3866, 1499, 14, 'Batch Created', '2025-11-05 20:31:33'),
(3867, 1499, 14, 'Batch Started', '2025-11-05 20:31:48'),
(3868, 1499, 14, 'Batch Deleted', '2025-11-05 20:31:54'),
(3869, 1500, 14, 'Batch Created', '2025-11-05 20:39:04'),
(3870, 1501, 14, 'Batch Created', '2025-11-05 20:39:09'),
(3871, 1502, 14, 'Batch Created', '2025-11-05 20:39:15'),
(3872, 1500, 14, 'Batch Deleted', '2025-11-05 20:51:11'),
(3873, 1501, 14, 'Batch Deleted', '2025-11-05 20:51:12'),
(3874, 1502, 14, 'Batch Deleted', '2025-11-05 20:51:14'),
(3875, 1503, 14, 'Batch Created', '2025-11-05 20:56:40'),
(3876, 1503, 14, 'Batch Started', '2025-11-05 20:56:44'),
(3877, 1503, 14, 'Batch Deleted', '2025-11-05 20:56:49'),
(3878, 1504, 14, 'Batch Created', '2025-11-05 20:57:32'),
(3879, 1504, 14, 'Batch Started', '2025-11-05 20:57:35'),
(3880, 1504, 14, 'Batch Completed', '2025-11-05 20:57:36'),
(3881, 1505, 14, 'Batch Created', '2025-11-05 20:58:39'),
(3882, 1505, 14, 'Batch Started', '2025-11-05 20:58:42'),
(3883, 1505, 14, 'Batch Deleted', '2025-11-05 20:58:50'),
(3884, 1506, 14, 'Batch Created', '2025-11-05 21:14:56'),
(3885, 1507, 14, 'Batch Created', '2025-11-05 21:15:02'),
(3886, 1508, 14, 'Batch Created', '2025-11-05 21:15:11'),
(3887, 1509, 14, 'Batch Created', '2025-11-05 21:15:18'),
(3888, 1506, 14, 'Batch Started', '2025-11-05 21:15:21'),
(3889, 1509, 14, 'Batch Started', '2025-11-05 21:15:22'),
(3890, 1507, 14, 'Batch Started', '2025-11-05 21:15:37'),
(3891, 1507, 14, 'Batch Completed', '2025-11-05 21:15:38'),
(3892, 1511, 14, 'Batch Created', '2025-11-05 21:16:25'),
(3893, 1512, 7, 'Batch Created', '2025-11-05 21:17:33');

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
(1977, 1506, 333, 5, '2025-11-05 13:14:56', 0),
(1978, 1507, 334, 5, '2025-11-05 13:15:02', 0),
(1979, 1508, 335, 10, '2025-11-05 13:15:11', 10),
(1980, 1509, 336, 15, '2025-11-05 13:15:18', 0),
(1981, 1511, 333, 20, '2025-11-05 13:16:25', 20),
(1982, 1512, 334, 12, '2025-11-05 13:17:33', 12);

-- --------------------------------------------------------

--
-- Table structure for table `batch_material_usage`
--

CREATE TABLE `batch_material_usage` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `inventory_batch_id` int(11) NOT NULL,
  `quantity_used` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch_material_usage`
--

INSERT INTO `batch_material_usage` (`id`, `batch_id`, `stock_id`, `inventory_batch_id`, `quantity_used`, `created_at`) VALUES
(346, 1504, 331, 131, 1.00, '2025-11-05 12:57:35'),
(348, 1506, 333, 137, 5.00, '2025-11-05 13:15:21'),
(349, 1509, 336, 155, 5.00, '2025-11-05 13:15:22'),
(350, 1509, 336, 143, 5.00, '2025-11-05 13:15:22'),
(351, 1509, 336, 147, 5.00, '2025-11-05 13:15:22'),
(352, 1507, 334, 141, 5.00, '2025-11-05 13:15:37');

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

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `status` enum('available','low','out') DEFAULT 'available',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `near_expiry_days` int(11) DEFAULT 7
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `quantity`, `unit`, `expiration_date`, `status`, `updated_at`, `created_at`, `near_expiry_days`) VALUES
(333, 'Sunflower', 25, 'kg', '2025-11-14', 'available', '2025-11-05 13:17:15', '2025-11-05 13:04:18', 7),
(334, 'Daisy', 25, 'kg', '2025-11-19', 'available', '2025-11-05 13:15:37', '2025-11-05 13:05:27', 7),
(335, 'Gerbera', 30, 'kg', '2025-11-15', 'available', '2025-11-05 13:09:02', '2025-11-05 13:06:03', 7),
(336, 'Malaysian Flower', 15, 'kg', '2025-11-29', 'available', '2025-11-05 13:15:22', '2025-11-05 13:06:14', 7);

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
-- Table structure for table `inventory_batches`
--

CREATE TABLE `inventory_batches` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `status` enum('Fresh','Near Expiry','Expired','Non-perishable') DEFAULT 'Fresh',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_batches`
--

INSERT INTO `inventory_batches` (`id`, `inventory_id`, `quantity`, `expiration_date`, `status`, `created_at`, `updated_at`) VALUES
(137, 333, 0.00, '2025-11-14', 'Fresh', '2025-11-05 21:04:18', '2025-11-05 21:17:15'),
(138, 334, 5.00, '2025-11-19', 'Fresh', '2025-11-05 21:05:27', '2025-11-05 21:17:15'),
(139, 335, 5.00, '2025-11-15', 'Fresh', '2025-11-05 21:06:03', '2025-11-05 21:17:15'),
(140, 336, 5.00, '2025-11-29', 'Fresh', '2025-11-05 21:06:14', '2025-11-05 21:17:15'),
(141, 334, 0.00, '2025-11-06', '', '2025-11-05 21:06:53', '2025-11-05 21:17:15'),
(142, 335, 5.00, '2025-11-06', '', '2025-11-05 21:06:58', '2025-11-05 21:17:15'),
(143, 336, 0.00, '2025-11-14', 'Fresh', '2025-11-05 21:07:02', '2025-11-05 21:17:15'),
(144, 333, 5.00, '2025-11-14', 'Fresh', '2025-11-05 21:07:07', '2025-11-05 21:17:15'),
(145, 334, 5.00, '2025-11-27', 'Fresh', '2025-11-05 21:07:46', '2025-11-05 21:17:15'),
(146, 335, 5.00, '2025-11-20', 'Fresh', '2025-11-05 21:07:51', '2025-11-05 21:17:15'),
(147, 336, 0.00, '2025-11-21', 'Fresh', '2025-11-05 21:07:55', '2025-11-05 21:17:15'),
(148, 333, 5.00, '2025-11-28', 'Fresh', '2025-11-05 21:08:01', '2025-11-05 21:17:15'),
(149, 334, 5.00, '2025-10-30', 'Expired', '2025-11-05 21:08:19', '2025-11-05 21:17:15'),
(150, 335, 5.00, '2025-11-04', 'Expired', '2025-11-05 21:08:26', '2025-11-05 21:17:15'),
(151, 336, 5.00, '2025-11-03', 'Expired', '2025-11-05 21:08:30', '2025-11-05 21:17:15'),
(152, 333, 5.00, '2025-11-04', 'Expired', '2025-11-05 21:08:33', '2025-11-05 21:17:15'),
(153, 334, 5.00, '2025-11-08', '', '2025-11-05 21:08:41', '2025-11-05 21:17:15'),
(154, 335, 5.00, '2025-11-21', 'Fresh', '2025-11-05 21:08:44', '2025-11-05 21:17:15'),
(155, 336, 0.00, '2025-11-12', '', '2025-11-05 21:08:48', '2025-11-05 21:17:15'),
(156, 333, 5.00, '2025-11-27', 'Fresh', '2025-11-05 21:08:52', '2025-11-05 21:17:15'),
(157, 334, 5.00, '2025-10-29', 'Expired', '2025-11-05 21:08:58', '2025-11-05 21:17:15'),
(158, 335, 5.00, '2025-11-13', '', '2025-11-05 21:09:02', '2025-11-05 21:17:15'),
(159, 336, 5.00, '2025-11-21', 'Fresh', '2025-11-05 21:09:05', '2025-11-05 21:17:15'),
(160, 333, 5.00, '2025-11-19', 'Fresh', '2025-11-05 21:09:11', '2025-11-05 21:17:15');

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
(1461, 1473, 'deleted', '🛠️ Bouquet - Batch Started (Canceled)', '2025-11-05 10:50:40'),
(1462, 1474, 'deleted', '🛠️ Test 1 - Batch Started (Canceled)', '2025-11-05 10:51:02'),
(1463, 1476, 'deleted', '🛠️ Test 2 - Batch Started (Canceled)', '2025-11-05 10:52:02'),
(1464, 1475, 'deleted', '🛠️ Test 1 - Batch Started (Canceled)', '2025-11-05 10:52:03'),
(1465, 1478, 'deleted', '🛠️ 1 - Batch Started (Canceled)', '2025-11-05 11:01:08'),
(1466, 1479, 'deleted', '🛠️ Bouquet - Batch Started (Canceled)', '2025-11-05 11:08:46'),
(1467, 1481, 'deleted', '🛠️ Bouquet - Batch Started (Canceled)', '2025-11-05 11:12:10'),
(1468, 1483, 'deleted', '🛠️ Bouquet - Batch Started (Canceled)', '2025-11-05 11:27:43'),
(1469, 1484, 'deleted', '🛠️ Test 1 - Batch Started (Canceled)', '2025-11-05 11:39:02'),
(1470, 1488, 'deleted', '🛠️ Bouquet - Batch Started (Canceled)', '2025-11-05 11:46:51'),
(1471, 1494, 'deleted', '🛠️ Bouquet 3 - Batch Started (Canceled)', '2025-11-05 11:54:22'),
(1472, 1493, 'deleted', '🛠️ Bouquet 1 - Batch Started (Canceled)', '2025-11-05 11:54:37'),
(1473, 1492, 'deleted', '🛠️ Bouquet - Batch Started (Canceled)', '2025-11-05 11:54:43'),
(1474, NULL, 'replenished', '♻️ Flour stock replenished with 2 kg!', '2025-11-05 11:56:42'),
(1475, 1495, 'deleted', '🛠️ Bouquet - Batch Started (Canceled)', '2025-11-05 12:08:08'),
(1476, 1496, 'deleted', '🛠️ Test 1 - Batch Started (Canceled)', '2025-11-05 12:10:06'),
(1477, 1497, 'deleted', '🛠️ Bouquet - Batch Started (Canceled)', '2025-11-05 12:12:29'),
(1478, NULL, 'replenished', '♻️ Flour stock replenished with 1 kg!', '2025-11-05 12:19:49'),
(1479, 1498, 'deleted', '🛠️ Test - Batch Started (Canceled)', '2025-11-05 12:29:18'),
(1480, 1499, 'deleted', '🛠️ Test 1 - Batch Started (Canceled)', '2025-11-05 12:31:48'),
(1481, 133, 'expiring', '⏳ Sunflower (Batch #133) will expire on 2025-11-06!', '2025-11-05 12:43:23'),
(1482, 134, 'expired', '💀 Sunflower (Batch #134) has expired!', '2025-11-05 12:43:23'),
(1483, 136, 'expiring', '⏳ Flour (Batch #136) will expire on 2025-11-07!', '2025-11-05 12:43:23'),
(1484, 1503, 'deleted', '🛠️ Test 1 - Batch Started (Canceled)', '2025-11-05 12:56:44'),
(1485, 1504, 'batch', '✔️ Test 1 - Batch Completed', '2025-11-05 12:57:36'),
(1486, 1505, 'deleted', '🛠️ Test 1 - Batch Started (Canceled)', '2025-11-05 12:58:42'),
(1487, NULL, '', '📦 New product Sunflower (5 kg) has been added!', '2025-11-05 13:04:18'),
(1488, NULL, '', '📦 New product Daisy (5 kg) has been added!', '2025-11-05 13:05:27'),
(1489, NULL, '', '📦 New product Gerbera (5 kg) has been added!', '2025-11-05 13:06:03'),
(1490, NULL, '', '📦 New product Malaysian Flower (5 kg) has been added!', '2025-11-05 13:06:14'),
(1491, NULL, 'replenished', '♻️ Daisy stock replenished with 5 kg!', '2025-11-05 13:06:53'),
(1492, NULL, 'replenished', '♻️ Gerbera stock replenished with 5 kg!', '2025-11-05 13:06:58'),
(1493, NULL, 'replenished', '♻️ Malaysian Flower stock replenished with 5 kg!', '2025-11-05 13:07:02'),
(1494, NULL, 'replenished', '♻️ Sunflower stock replenished with 5 kg!', '2025-11-05 13:07:07'),
(1495, NULL, 'replenished', '♻️ Daisy stock replenished with 5 kg!', '2025-11-05 13:07:46'),
(1496, NULL, 'replenished', '♻️ Gerbera stock replenished with 5 kg!', '2025-11-05 13:07:51'),
(1497, NULL, 'replenished', '♻️ Malaysian Flower stock replenished with 5 kg!', '2025-11-05 13:07:55'),
(1498, NULL, 'replenished', '♻️ Sunflower stock replenished with 5 kg!', '2025-11-05 13:08:01'),
(1499, NULL, 'replenished', '♻️ Daisy stock replenished with 5 kg!', '2025-11-05 13:08:19'),
(1500, NULL, 'replenished', '♻️ Gerbera stock replenished with 5 kg!', '2025-11-05 13:08:26'),
(1501, NULL, 'replenished', '♻️ Malaysian Flower stock replenished with 5 kg!', '2025-11-05 13:08:30'),
(1502, NULL, 'replenished', '♻️ Sunflower stock replenished with 5 kg!', '2025-11-05 13:08:33'),
(1503, NULL, 'replenished', '♻️ Daisy stock replenished with 5 kg!', '2025-11-05 13:08:41'),
(1504, NULL, 'replenished', '♻️ Gerbera stock replenished with 5 kg!', '2025-11-05 13:08:44'),
(1505, NULL, 'replenished', '♻️ Malaysian Flower stock replenished with 5 kg!', '2025-11-05 13:08:48'),
(1506, NULL, 'replenished', '♻️ Sunflower stock replenished with 5 kg!', '2025-11-05 13:08:52'),
(1507, NULL, 'replenished', '♻️ Daisy stock replenished with 5 kg!', '2025-11-05 13:08:58'),
(1508, NULL, 'replenished', '♻️ Gerbera stock replenished with 5 kg!', '2025-11-05 13:09:02'),
(1509, NULL, 'replenished', '♻️ Malaysian Flower stock replenished with 5 kg!', '2025-11-05 13:09:05'),
(1510, NULL, 'replenished', '♻️ Sunflower stock replenished with 5 kg!', '2025-11-05 13:09:11'),
(1511, 152, 'expired', '💀 Sunflower (Batch #152) has expired!', '2025-11-05 13:11:10'),
(1512, 141, 'expiring', '⏳ Daisy (Batch #141) will expire on 2025-11-06!', '2025-11-05 13:11:10'),
(1513, 149, 'expired', '💀 Daisy (Batch #149) has expired!', '2025-11-05 13:11:10'),
(1514, 153, 'expiring', '⏳ Daisy (Batch #153) will expire on 2025-11-08!', '2025-11-05 13:11:10'),
(1515, 157, 'expired', '💀 Daisy (Batch #157) has expired!', '2025-11-05 13:11:10'),
(1516, 142, 'expiring', '⏳ Gerbera (Batch #142) will expire on 2025-11-06!', '2025-11-05 13:11:10'),
(1517, 150, 'expired', '💀 Gerbera (Batch #150) has expired!', '2025-11-05 13:11:10'),
(1518, 151, 'expired', '💀 Malaysian Flower (Batch #151) has expired!', '2025-11-05 13:11:10'),
(1519, 1506, 'batch', '🛠️ Test 1 - Batch Started', '2025-11-05 13:15:21'),
(1520, 1509, 'batch', '🛠️ Test 4 - Batch Started', '2025-11-05 13:15:22'),
(1521, 1507, 'batch', '✔️ Test 2 - Batch Completed', '2025-11-05 13:15:38'),
(1522, NULL, 'replenished', '♻️ Sunflower stock replenished with 5 kg!', '2025-11-05 13:16:54');

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

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `ingredient_id`, `user_id`, `ingredient_name`, `quantity`, `notes`, `unit`, `status`, `requested_at`) VALUES
(63, 251, 14, 'Raduz', 50, '', 'kg', 'approved', '2025-10-23 15:36:25'),
(64, 251, 14, 'Raduz', 50, '', 'kg', 'denied', '2025-10-23 15:38:05'),
(65, 251, 7, 'Raduz', 10, '', 'kg', 'pending', '2025-10-23 15:40:59'),
(66, 331, 14, 'Ribbon', 5, 'Wawa', 'kg', 'pending', '2025-11-05 10:53:29');

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
(264, 333, 4, '2025-11-05 13:04:18', '2025-11-05 13:04:18'),
(265, 334, 4, '2025-11-05 13:05:27', '2025-11-05 13:05:27'),
(266, 335, 4, '2025-11-05 13:06:03', '2025-11-05 13:06:03'),
(267, 336, 4, '2025-11-05 13:06:14', '2025-11-05 13:06:14');

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
(10754, 2, 1461, 0, NULL),
(10755, 6, 1461, 0, NULL),
(10756, 14, 1461, 0, NULL),
(10757, 5, 1461, 0, NULL),
(10758, 3, 1461, 0, NULL),
(10759, 7, 1461, 0, NULL),
(10760, 15, 1461, 0, NULL),
(10761, 2, 1462, 0, NULL),
(10762, 6, 1462, 0, NULL),
(10763, 14, 1462, 0, NULL),
(10764, 5, 1462, 0, NULL),
(10765, 3, 1462, 0, NULL),
(10766, 7, 1462, 0, NULL),
(10767, 15, 1462, 0, NULL),
(10768, 2, 1463, 0, NULL),
(10769, 6, 1463, 0, NULL),
(10770, 14, 1463, 1, '2025-11-05 18:52:39'),
(10771, 5, 1463, 0, NULL),
(10772, 3, 1463, 0, NULL),
(10773, 7, 1463, 0, NULL),
(10774, 15, 1463, 0, NULL),
(10775, 2, 1464, 0, NULL),
(10776, 6, 1464, 0, NULL),
(10777, 14, 1464, 1, '2025-11-05 18:52:39'),
(10778, 5, 1464, 0, NULL),
(10779, 3, 1464, 0, NULL),
(10780, 7, 1464, 0, NULL),
(10781, 15, 1464, 0, NULL),
(10782, 2, 1465, 0, NULL),
(10783, 6, 1465, 0, NULL),
(10784, 14, 1465, 0, NULL),
(10785, 5, 1465, 0, NULL),
(10786, 3, 1465, 0, NULL),
(10787, 7, 1465, 0, NULL),
(10788, 15, 1465, 0, NULL),
(10789, 2, 1466, 0, NULL),
(10790, 6, 1466, 0, NULL),
(10791, 14, 1466, 0, NULL),
(10792, 5, 1466, 0, NULL),
(10793, 3, 1466, 0, NULL),
(10794, 7, 1466, 0, NULL),
(10795, 15, 1466, 0, NULL),
(10796, 2, 1467, 0, NULL),
(10797, 6, 1467, 0, NULL),
(10798, 14, 1467, 0, NULL),
(10799, 5, 1467, 0, NULL),
(10800, 3, 1467, 0, NULL),
(10801, 7, 1467, 0, NULL),
(10802, 15, 1467, 0, NULL),
(10803, 2, 1468, 0, NULL),
(10804, 6, 1468, 0, NULL),
(10805, 14, 1468, 0, NULL),
(10806, 5, 1468, 0, NULL),
(10807, 3, 1468, 0, NULL),
(10808, 7, 1468, 0, NULL),
(10809, 15, 1468, 0, NULL),
(10810, 2, 1469, 0, NULL),
(10811, 6, 1469, 0, NULL),
(10812, 14, 1469, 0, NULL),
(10813, 5, 1469, 0, NULL),
(10814, 3, 1469, 0, NULL),
(10815, 7, 1469, 0, NULL),
(10816, 15, 1469, 0, NULL),
(10817, 2, 1470, 0, NULL),
(10818, 6, 1470, 0, NULL),
(10819, 14, 1470, 0, NULL),
(10820, 5, 1470, 0, NULL),
(10821, 3, 1470, 0, NULL),
(10822, 7, 1470, 0, NULL),
(10823, 15, 1470, 0, NULL),
(10824, 2, 1471, 0, NULL),
(10825, 6, 1471, 0, NULL),
(10826, 14, 1471, 1, '2025-11-05 19:54:53'),
(10827, 5, 1471, 0, NULL),
(10828, 3, 1471, 0, NULL),
(10829, 7, 1471, 0, NULL),
(10830, 15, 1471, 0, NULL),
(10831, 2, 1472, 0, NULL),
(10832, 6, 1472, 0, NULL),
(10833, 14, 1472, 1, '2025-11-05 19:54:53'),
(10834, 5, 1472, 0, NULL),
(10835, 3, 1472, 0, NULL),
(10836, 7, 1472, 0, NULL),
(10837, 15, 1472, 0, NULL),
(10838, 2, 1473, 0, NULL),
(10839, 6, 1473, 0, NULL),
(10840, 14, 1473, 1, '2025-11-05 19:54:53'),
(10841, 5, 1473, 0, NULL),
(10842, 3, 1473, 0, NULL),
(10843, 7, 1473, 0, NULL),
(10844, 15, 1473, 0, NULL),
(10845, 2, 1474, 0, NULL),
(10846, 6, 1474, 0, NULL),
(10847, 14, 1474, 1, '2025-11-05 20:13:33'),
(10848, 5, 1474, 0, NULL),
(10849, 3, 1474, 0, NULL),
(10850, 7, 1474, 0, NULL),
(10851, 15, 1474, 0, NULL),
(10852, 2, 1475, 0, NULL),
(10853, 6, 1475, 0, NULL),
(10854, 14, 1475, 0, NULL),
(10855, 5, 1475, 0, NULL),
(10856, 3, 1475, 0, NULL),
(10857, 7, 1475, 0, NULL),
(10858, 15, 1475, 0, NULL),
(10859, 2, 1476, 0, NULL),
(10860, 6, 1476, 0, NULL),
(10861, 14, 1476, 0, NULL),
(10862, 5, 1476, 0, NULL),
(10863, 3, 1476, 0, NULL),
(10864, 7, 1476, 0, NULL),
(10865, 15, 1476, 0, NULL),
(10866, 2, 1477, 0, NULL),
(10867, 6, 1477, 0, NULL),
(10868, 14, 1477, 1, '2025-11-05 20:13:33'),
(10869, 5, 1477, 0, NULL),
(10870, 3, 1477, 0, NULL),
(10871, 7, 1477, 0, NULL),
(10872, 15, 1477, 0, NULL),
(10873, 2, 1478, 0, NULL),
(10874, 6, 1478, 0, NULL),
(10875, 14, 1478, 1, '2025-11-05 20:20:02'),
(10876, 5, 1478, 0, NULL),
(10877, 3, 1478, 0, NULL),
(10878, 7, 1478, 0, NULL),
(10879, 15, 1478, 0, NULL),
(10880, 2, 1479, 0, NULL),
(10881, 6, 1479, 0, NULL),
(10882, 14, 1479, 0, NULL),
(10883, 5, 1479, 0, NULL),
(10884, 3, 1479, 0, NULL),
(10885, 7, 1479, 0, NULL),
(10886, 15, 1479, 0, NULL),
(10887, 2, 1480, 0, NULL),
(10888, 6, 1480, 0, NULL),
(10889, 14, 1480, 0, NULL),
(10890, 5, 1480, 0, NULL),
(10891, 3, 1480, 0, NULL),
(10892, 7, 1480, 0, NULL),
(10893, 15, 1480, 0, NULL),
(10894, 2, 1481, 0, NULL),
(10895, 6, 1481, 0, NULL),
(10896, 14, 1481, 1, '2025-11-05 20:44:06'),
(10897, 5, 1481, 0, NULL),
(10898, 3, 1481, 0, NULL),
(10899, 7, 1481, 0, NULL),
(10900, 15, 1481, 0, NULL),
(10901, 2, 1482, 0, NULL),
(10902, 6, 1482, 0, NULL),
(10903, 14, 1482, 1, '2025-11-05 20:44:06'),
(10904, 5, 1482, 0, NULL),
(10905, 3, 1482, 0, NULL),
(10906, 7, 1482, 0, NULL),
(10907, 15, 1482, 0, NULL),
(10908, 2, 1483, 0, NULL),
(10909, 6, 1483, 0, NULL),
(10910, 14, 1483, 1, '2025-11-05 20:44:06'),
(10911, 5, 1483, 0, NULL),
(10912, 3, 1483, 0, NULL),
(10913, 7, 1483, 0, NULL),
(10914, 15, 1483, 0, NULL),
(10915, 2, 1484, 0, NULL),
(10916, 6, 1484, 0, NULL),
(10917, 14, 1484, 0, NULL),
(10918, 5, 1484, 0, NULL),
(10919, 3, 1484, 0, NULL),
(10920, 7, 1484, 0, NULL),
(10921, 15, 1484, 0, NULL),
(10922, 2, 1485, 0, NULL),
(10923, 6, 1485, 0, NULL),
(10924, 14, 1485, 1, '2025-11-05 21:06:32'),
(10925, 5, 1485, 0, NULL),
(10926, 3, 1485, 0, NULL),
(10927, 7, 1485, 0, NULL),
(10928, 15, 1485, 0, NULL),
(10930, 2, 1486, 0, NULL),
(10931, 6, 1486, 0, NULL),
(10932, 14, 1486, 0, NULL),
(10933, 5, 1486, 0, NULL),
(10934, 3, 1486, 0, NULL),
(10935, 7, 1486, 0, NULL),
(10936, 15, 1486, 0, NULL),
(10937, 2, 1487, 0, NULL),
(10938, 6, 1487, 0, NULL),
(10939, 14, 1487, 1, '2025-11-05 21:06:32'),
(10940, 5, 1487, 0, NULL),
(10941, 3, 1487, 0, NULL),
(10942, 7, 1487, 0, NULL),
(10943, 15, 1487, 0, NULL),
(10944, 2, 1488, 0, NULL),
(10945, 6, 1488, 0, NULL),
(10946, 14, 1488, 1, '2025-11-05 21:06:32'),
(10947, 5, 1488, 0, NULL),
(10948, 3, 1488, 0, NULL),
(10949, 7, 1488, 0, NULL),
(10950, 15, 1488, 0, NULL),
(10951, 2, 1489, 0, NULL),
(10952, 6, 1489, 0, NULL),
(10953, 14, 1489, 1, '2025-11-05 21:06:32'),
(10954, 5, 1489, 0, NULL),
(10955, 3, 1489, 0, NULL),
(10956, 7, 1489, 0, NULL),
(10957, 15, 1489, 0, NULL),
(10958, 2, 1490, 0, NULL),
(10959, 6, 1490, 0, NULL),
(10960, 14, 1490, 1, '2025-11-05 21:06:32'),
(10961, 5, 1490, 0, NULL),
(10962, 3, 1490, 0, NULL),
(10963, 7, 1490, 0, NULL),
(10964, 15, 1490, 0, NULL),
(10965, 2, 1491, 0, NULL),
(10966, 6, 1491, 0, NULL),
(10967, 14, 1491, 1, '2025-11-05 21:07:17'),
(10968, 5, 1491, 0, NULL),
(10969, 3, 1491, 0, NULL),
(10970, 7, 1491, 0, NULL),
(10971, 15, 1491, 0, NULL),
(10972, 2, 1492, 0, NULL),
(10973, 6, 1492, 0, NULL),
(10974, 14, 1492, 1, '2025-11-05 21:07:17'),
(10975, 5, 1492, 0, NULL),
(10976, 3, 1492, 0, NULL),
(10977, 7, 1492, 0, NULL),
(10978, 15, 1492, 0, NULL),
(10979, 2, 1493, 0, NULL),
(10980, 6, 1493, 0, NULL),
(10981, 14, 1493, 1, '2025-11-05 21:07:17'),
(10982, 5, 1493, 0, NULL),
(10983, 3, 1493, 0, NULL),
(10984, 7, 1493, 0, NULL),
(10985, 15, 1493, 0, NULL),
(10986, 2, 1494, 0, NULL),
(10987, 6, 1494, 0, NULL),
(10988, 14, 1494, 1, '2025-11-05 21:07:17'),
(10989, 5, 1494, 0, NULL),
(10990, 3, 1494, 0, NULL),
(10991, 7, 1494, 0, NULL),
(10992, 15, 1494, 0, NULL),
(10993, 2, 1495, 0, NULL),
(10994, 6, 1495, 0, NULL),
(10995, 14, 1495, 1, '2025-11-05 21:08:11'),
(10996, 5, 1495, 0, NULL),
(10997, 3, 1495, 0, NULL),
(10998, 7, 1495, 0, NULL),
(10999, 15, 1495, 0, NULL),
(11000, 2, 1496, 0, NULL),
(11001, 6, 1496, 0, NULL),
(11002, 14, 1496, 1, '2025-11-05 21:08:11'),
(11003, 5, 1496, 0, NULL),
(11004, 3, 1496, 0, NULL),
(11005, 7, 1496, 0, NULL),
(11006, 15, 1496, 0, NULL),
(11007, 2, 1497, 0, NULL),
(11008, 6, 1497, 0, NULL),
(11009, 14, 1497, 1, '2025-11-05 21:08:11'),
(11010, 5, 1497, 0, NULL),
(11011, 3, 1497, 0, NULL),
(11012, 7, 1497, 0, NULL),
(11013, 15, 1497, 0, NULL),
(11014, 2, 1498, 0, NULL),
(11015, 6, 1498, 0, NULL),
(11016, 14, 1498, 1, '2025-11-05 21:08:11'),
(11017, 5, 1498, 0, NULL),
(11018, 3, 1498, 0, NULL),
(11019, 7, 1498, 0, NULL),
(11020, 15, 1498, 0, NULL),
(11021, 2, 1499, 0, NULL),
(11022, 6, 1499, 0, NULL),
(11023, 14, 1499, 0, NULL),
(11024, 5, 1499, 0, NULL),
(11025, 3, 1499, 0, NULL),
(11026, 7, 1499, 0, NULL),
(11027, 15, 1499, 0, NULL),
(11028, 2, 1500, 0, NULL),
(11029, 6, 1500, 0, NULL),
(11030, 14, 1500, 0, NULL),
(11031, 5, 1500, 0, NULL),
(11032, 3, 1500, 0, NULL),
(11033, 7, 1500, 0, NULL),
(11034, 15, 1500, 0, NULL),
(11035, 2, 1501, 0, NULL),
(11036, 6, 1501, 0, NULL),
(11037, 14, 1501, 1, '2025-11-05 21:09:13'),
(11038, 5, 1501, 0, NULL),
(11039, 3, 1501, 0, NULL),
(11040, 7, 1501, 0, NULL),
(11041, 15, 1501, 0, NULL),
(11042, 2, 1502, 0, NULL),
(11043, 6, 1502, 0, NULL),
(11044, 14, 1502, 1, '2025-11-05 21:09:13'),
(11045, 5, 1502, 0, NULL),
(11046, 3, 1502, 0, NULL),
(11047, 7, 1502, 0, NULL),
(11048, 15, 1502, 0, NULL),
(11049, 2, 1503, 0, NULL),
(11050, 6, 1503, 0, NULL),
(11051, 14, 1503, 1, '2025-11-05 21:09:13'),
(11052, 5, 1503, 0, NULL),
(11053, 3, 1503, 0, NULL),
(11054, 7, 1503, 0, NULL),
(11055, 15, 1503, 0, NULL),
(11056, 2, 1504, 0, NULL),
(11057, 6, 1504, 0, NULL),
(11058, 14, 1504, 1, '2025-11-05 21:09:13'),
(11059, 5, 1504, 0, NULL),
(11060, 3, 1504, 0, NULL),
(11061, 7, 1504, 0, NULL),
(11062, 15, 1504, 0, NULL),
(11063, 2, 1505, 0, NULL),
(11064, 6, 1505, 0, NULL),
(11065, 14, 1505, 1, '2025-11-05 21:09:13'),
(11066, 5, 1505, 0, NULL),
(11067, 3, 1505, 0, NULL),
(11068, 7, 1505, 0, NULL),
(11069, 15, 1505, 0, NULL),
(11070, 2, 1506, 0, NULL),
(11071, 6, 1506, 0, NULL),
(11072, 14, 1506, 1, '2025-11-05 21:09:13'),
(11073, 5, 1506, 0, NULL),
(11074, 3, 1506, 0, NULL),
(11075, 7, 1506, 0, NULL),
(11076, 15, 1506, 0, NULL),
(11077, 2, 1507, 0, NULL),
(11078, 6, 1507, 0, NULL),
(11079, 14, 1507, 1, '2025-11-05 21:09:13'),
(11080, 5, 1507, 0, NULL),
(11081, 3, 1507, 0, NULL),
(11082, 7, 1507, 0, NULL),
(11083, 15, 1507, 0, NULL),
(11084, 2, 1508, 0, NULL),
(11085, 6, 1508, 0, NULL),
(11086, 14, 1508, 1, '2025-11-05 21:09:13'),
(11087, 5, 1508, 0, NULL),
(11088, 3, 1508, 0, NULL),
(11089, 7, 1508, 0, NULL),
(11090, 15, 1508, 0, NULL),
(11091, 2, 1509, 0, NULL),
(11092, 6, 1509, 0, NULL),
(11093, 14, 1509, 1, '2025-11-05 21:09:13'),
(11094, 5, 1509, 0, NULL),
(11095, 3, 1509, 0, NULL),
(11096, 7, 1509, 0, NULL),
(11097, 15, 1509, 0, NULL),
(11098, 2, 1510, 0, NULL),
(11099, 6, 1510, 0, NULL),
(11100, 14, 1510, 1, '2025-11-05 21:09:13'),
(11101, 5, 1510, 0, NULL),
(11102, 3, 1510, 0, NULL),
(11103, 7, 1510, 0, NULL),
(11104, 15, 1510, 0, NULL),
(11105, 2, 1511, 0, NULL),
(11106, 6, 1511, 0, NULL),
(11107, 14, 1511, 1, '2025-11-05 21:12:34'),
(11108, 5, 1511, 0, NULL),
(11109, 3, 1511, 0, NULL),
(11110, 7, 1511, 1, '2025-11-05 21:17:19'),
(11111, 15, 1511, 0, NULL),
(11112, 2, 1512, 0, NULL),
(11113, 6, 1512, 0, NULL),
(11114, 14, 1512, 1, '2025-11-05 21:12:34'),
(11115, 5, 1512, 0, NULL),
(11116, 3, 1512, 0, NULL),
(11117, 7, 1512, 1, '2025-11-05 21:17:19'),
(11118, 15, 1512, 0, NULL),
(11119, 2, 1513, 0, NULL),
(11120, 6, 1513, 0, NULL),
(11121, 14, 1513, 1, '2025-11-05 21:12:34'),
(11122, 5, 1513, 0, NULL),
(11123, 3, 1513, 0, NULL),
(11124, 7, 1513, 1, '2025-11-05 21:17:19'),
(11125, 15, 1513, 0, NULL),
(11126, 2, 1514, 0, NULL),
(11127, 6, 1514, 0, NULL),
(11128, 14, 1514, 1, '2025-11-05 21:12:34'),
(11129, 5, 1514, 0, NULL),
(11130, 3, 1514, 0, NULL),
(11131, 7, 1514, 1, '2025-11-05 21:17:19'),
(11132, 15, 1514, 0, NULL),
(11133, 2, 1515, 0, NULL),
(11134, 6, 1515, 0, NULL),
(11135, 14, 1515, 1, '2025-11-05 21:12:34'),
(11136, 5, 1515, 0, NULL),
(11137, 3, 1515, 0, NULL),
(11138, 7, 1515, 1, '2025-11-05 21:17:19'),
(11139, 15, 1515, 0, NULL),
(11140, 2, 1516, 0, NULL),
(11141, 6, 1516, 0, NULL),
(11142, 14, 1516, 1, '2025-11-05 21:12:34'),
(11143, 5, 1516, 0, NULL),
(11144, 3, 1516, 0, NULL),
(11145, 7, 1516, 0, NULL),
(11146, 15, 1516, 0, NULL),
(11147, 2, 1517, 0, NULL),
(11148, 6, 1517, 0, NULL),
(11149, 14, 1517, 1, '2025-11-05 21:12:34'),
(11150, 5, 1517, 0, NULL),
(11151, 3, 1517, 0, NULL),
(11152, 7, 1517, 0, NULL),
(11153, 15, 1517, 0, NULL),
(11154, 2, 1518, 0, NULL),
(11155, 6, 1518, 0, NULL),
(11156, 14, 1518, 1, '2025-11-05 21:12:34'),
(11157, 5, 1518, 0, NULL),
(11158, 3, 1518, 0, NULL),
(11159, 7, 1518, 0, NULL),
(11160, 15, 1518, 0, NULL),
(11161, 2, 1519, 0, NULL),
(11162, 6, 1519, 0, NULL),
(11163, 14, 1519, 1, '2025-11-05 21:16:28'),
(11164, 5, 1519, 0, NULL),
(11165, 3, 1519, 0, NULL),
(11166, 7, 1519, 1, '2025-11-05 21:17:19'),
(11167, 15, 1519, 0, NULL),
(11168, 2, 1520, 0, NULL),
(11169, 6, 1520, 0, NULL),
(11170, 14, 1520, 1, '2025-11-05 21:16:28'),
(11171, 5, 1520, 0, NULL),
(11172, 3, 1520, 0, NULL),
(11173, 7, 1520, 1, '2025-11-05 21:17:19'),
(11174, 15, 1520, 0, NULL),
(11175, 2, 1521, 0, NULL),
(11176, 6, 1521, 0, NULL),
(11177, 14, 1521, 1, '2025-11-05 21:16:28'),
(11178, 5, 1521, 0, NULL),
(11179, 3, 1521, 0, NULL),
(11180, 7, 1521, 1, '2025-11-05 21:17:19'),
(11181, 15, 1521, 0, NULL),
(11183, 2, 1522, 0, NULL),
(11184, 6, 1522, 0, NULL),
(11185, 14, 1522, 0, NULL),
(11186, 5, 1522, 0, NULL),
(11187, 3, 1522, 0, NULL),
(11188, 7, 1522, 1, '2025-11-05 21:17:19'),
(11189, 15, 1522, 0, NULL);

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
-- Indexes for table `batch_material_usage`
--
ALTER TABLE `batch_material_usage`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1513;

--
-- AUTO_INCREMENT for table `batch_log`
--
ALTER TABLE `batch_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3894;

--
-- AUTO_INCREMENT for table `batch_materials`
--
ALTER TABLE `batch_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1983;

--
-- AUTO_INCREMENT for table `batch_material_usage`
--
ALTER TABLE `batch_material_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=353;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=337;

--
-- AUTO_INCREMENT for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1523;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `stock_thresholds`
--
ALTER TABLE `stock_thresholds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=268;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11190;

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
-- Constraints for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD CONSTRAINT `inventory_batches_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

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
