-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 07, 2026 at 09:22 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dilp_monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-03 09:20:12'),
(2, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-03 09:20:35'),
(3, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-03 09:20:39'),
(4, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-03 13:29:35'),
(5, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-03 14:22:15'),
(6, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-03 14:22:16'),
(7, 1, 'create', 'beneficiaries', 1, 'Created new beneficiary', '::1', '2026-02-03 14:26:15'),
(8, 1, 'update', 'beneficiaries', 1, 'Updated beneficiary', '::1', '2026-02-03 14:34:13'),
(9, 1, 'update', 'beneficiaries', 1, 'Updated beneficiary', '::1', '2026-02-03 14:35:06'),
(10, 1, 'create', 'proponents', 1, 'Created new proponent', '::1', '2026-02-03 14:43:18'),
(11, 1, 'update', 'proponents', 1, 'Updated proponent', '::1', '2026-02-03 14:44:49'),
(12, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-03 15:12:52'),
(13, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-03 15:13:14'),
(14, 1, 'create', 'proponents', 6, 'Created new proponent', 'unknown', '2026-02-03 15:17:53'),
(15, 1, 'delete', 'proponents', 14, 'Deleted proponent', 'unknown', '2026-02-03 15:17:53'),
(16, 1, 'create', 'proponents', 9, 'Created new proponent', 'unknown', '2026-02-03 15:20:22'),
(18, 1, 'create', 'proponents', 10, 'Created new proponent', 'unknown', '2026-02-03 15:20:49'),
(20, 1, 'create', 'proponents', 12, 'Created new proponent', 'unknown', '2026-02-03 15:21:55'),
(21, 1, 'delete', 'proponents', 20, 'Deleted proponent', 'unknown', '2026-02-03 15:21:55'),
(22, 1, 'create', 'proponents', 13, 'Created new proponent', 'unknown', '2026-02-03 15:21:55'),
(23, 1, 'delete', 'proponents', 22, 'Deleted proponent', 'unknown', '2026-02-03 15:21:55'),
(24, 1, 'create', 'proponents', 16, 'Created new proponent', 'unknown', '2026-02-03 15:22:26'),
(25, 1, 'delete', 'proponents', 24, 'Deleted proponent', 'unknown', '2026-02-03 15:22:26'),
(28, 1, 'create', 'proponents', 21, 'Created new proponent', 'unknown', '2026-02-03 15:24:32'),
(29, 1, 'delete', 'proponents', 28, 'Deleted proponent', 'unknown', '2026-02-03 15:24:32'),
(30, 1, 'create', 'proponents', 22, 'Created new proponent', 'unknown', '2026-02-03 15:24:59'),
(31, 1, 'delete', 'proponents', 30, 'Deleted proponent', 'unknown', '2026-02-03 15:24:59'),
(32, 1, 'create', 'proponents', 23, 'Created new proponent', 'unknown', '2026-02-03 15:24:59'),
(33, 1, 'delete', 'proponents', 32, 'Deleted proponent', 'unknown', '2026-02-03 15:24:59'),
(34, 1, 'create', 'proponents', 24, 'Created new proponent', 'unknown', '2026-02-03 15:24:59'),
(35, 1, 'create', 'proponents', 25, 'Created new proponent', 'unknown', '2026-02-03 15:24:59'),
(36, 1, 'delete', 'proponents', 34, 'Deleted proponent', 'unknown', '2026-02-03 15:24:59'),
(37, 1, 'delete', 'proponents', 35, 'Deleted proponent', 'unknown', '2026-02-03 15:24:59'),
(38, 1, 'create', 'proponents', 26, 'Created new proponent', 'unknown', '2026-02-03 15:25:50'),
(39, 1, 'delete', 'proponents', 38, 'Deleted proponent', 'unknown', '2026-02-03 15:25:50'),
(40, 1, 'create', 'proponents', 27, 'Created new proponent', 'unknown', '2026-02-03 15:25:50'),
(41, 1, 'create', 'proponents', 28, 'Created new proponent', 'unknown', '2026-02-03 15:25:50'),
(42, 1, 'delete', 'proponents', 40, 'Deleted proponent', 'unknown', '2026-02-03 15:25:50'),
(43, 1, 'delete', 'proponents', 41, 'Deleted proponent', 'unknown', '2026-02-03 15:25:50'),
(44, 1, 'create', 'proponents', 29, 'Created new proponent', 'unknown', '2026-02-03 15:25:50'),
(45, 1, 'create', 'proponents', 30, 'Created new proponent', 'unknown', '2026-02-03 15:25:50'),
(46, 1, 'delete', 'proponents', 44, 'Deleted proponent', 'unknown', '2026-02-03 15:25:50'),
(47, 1, 'delete', 'proponents', 45, 'Deleted proponent', 'unknown', '2026-02-03 15:25:50'),
(48, 1, 'create', 'proponents', 31, 'Created new proponent', 'unknown', '2026-02-03 15:25:50'),
(49, 1, 'update', 'proponents', 48, 'Updated proponent', 'unknown', '2026-02-03 15:25:50'),
(50, 1, 'delete', 'proponents', 48, 'Deleted proponent', 'unknown', '2026-02-03 15:25:50'),
(51, 1, 'create', 'proponents', 33, 'Created new proponent', 'unknown', '2026-02-03 15:26:25'),
(52, 1, 'delete', 'proponents', 51, 'Deleted proponent', 'unknown', '2026-02-03 15:26:25'),
(53, 1, 'create', 'proponents', 34, 'Created new proponent', '::1', '2026-02-03 15:27:56'),
(54, 1, 'update', 'proponents', 34, 'Updated proponent', '::1', '2026-02-03 15:28:19'),
(55, 1, 'update', 'proponents', 34, 'Updated proponent', '::1', '2026-02-03 15:29:11'),
(56, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-04 04:30:52'),
(57, 1, 'create', 'proponents', 35, 'Created new proponent', '::1', '2026-02-04 04:34:38'),
(58, 1, 'update', 'proponents', 1, 'Updated proponent', '::1', '2026-02-04 04:41:05'),
(59, 1, 'delete', 'proponents', 33, 'Deleted proponent: Model Test', '::1', '2026-02-04 05:54:44'),
(60, 1, 'delete', 'proponents', 31, 'Deleted proponent: LGU Test', '::1', '2026-02-04 05:54:53'),
(61, 1, 'delete', 'proponents', 6, 'Deleted proponent: Test Non-LGU Proponent', '::1', '2026-02-04 05:55:05'),
(62, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-04 07:11:05'),
(63, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-04 07:11:10'),
(64, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-04 07:25:22'),
(65, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-04 07:26:08'),
(66, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-04 07:29:36'),
(67, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-04 07:31:34'),
(68, 1, 'create', 'users', 2, 'Created new user: kayzel', '::1', '2026-02-04 07:44:20'),
(69, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-04 07:44:23'),
(70, 2, 'login', 'users', 2, 'User logged in', '::1', '2026-02-04 07:44:25'),
(71, 2, 'logout', 'users', 2, 'User logged out', '::1', '2026-02-04 07:44:50'),
(72, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-04 07:44:55'),
(73, 1, 'create', 'users', 3, 'Created new user: jona', '::1', '2026-02-04 07:46:59'),
(74, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-04 07:47:04'),
(75, 3, 'login', 'users', 3, 'User logged in', '::1', '2026-02-04 07:47:10'),
(76, 3, 'logout', 'users', 3, 'User logged out', '::1', '2026-02-04 07:47:15'),
(77, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-04 07:47:24'),
(78, 1, 'create', 'users', 4, 'Created new user: user', '::1', '2026-02-04 07:47:46'),
(79, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-04 07:47:52'),
(80, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-02-04 07:47:58'),
(81, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-02-04 08:59:31'),
(82, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-05 01:39:04'),
(83, 1, 'create', 'proponents', 36, 'Created new proponent', '::1', '2026-02-05 01:51:09'),
(84, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-05 02:12:31'),
(85, 2, 'login', 'users', 2, 'User logged in', '::1', '2026-02-05 02:12:37'),
(86, 2, 'logout', 'users', 2, 'User logged out', '::1', '2026-02-05 02:12:41'),
(87, 3, 'login', 'users', 3, 'User logged in', '::1', '2026-02-05 02:12:54'),
(88, 3, 'logout', 'users', 3, 'User logged out', '::1', '2026-02-05 02:13:15'),
(89, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-05 02:13:19'),
(90, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-05 02:17:58'),
(91, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-05 02:21:26'),
(92, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-05 06:39:42'),
(93, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-05 06:39:47'),
(94, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-05 06:39:50'),
(95, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-05 06:39:53'),
(96, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 03:22:34'),
(97, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 03:22:38'),
(98, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 03:24:03'),
(99, 1, 'delete', 'proponents', 29, 'Deleted proponent: LGU Test', '::1', '2026-02-11 03:46:12'),
(100, 1, 'update', 'proponents', 30, 'Updated proponent', '::1', '2026-02-11 03:46:26'),
(101, 1, 'delete', 'proponents', 28, 'Deleted proponent: Another Non-LGU', '::1', '2026-02-11 03:47:02'),
(102, 1, 'create', 'beneficiaries', 2, 'Created new beneficiary', '::1', '2026-02-11 04:00:18'),
(103, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 05:28:10'),
(104, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 05:28:18'),
(105, 1, 'delete', 'beneficiaries', 2, 'Deleted beneficiary: John Rupert Dishwalla', '::1', '2026-02-11 05:31:23'),
(106, 1, 'update', 'beneficiaries', 1, 'Updated beneficiary', '::1', '2026-02-11 05:31:33'),
(107, 1, 'create', 'beneficiaries', 3, 'Created new beneficiary', '::1', '2026-02-11 05:32:07'),
(108, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 05:53:23'),
(109, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 05:53:25'),
(110, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 06:10:41'),
(111, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 06:10:42'),
(112, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 06:14:56'),
(113, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 06:14:57'),
(114, 1, 'update', 'users', 1, 'Updated user ID: 1', '::1', '2026-02-11 06:17:18'),
(115, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 06:17:21'),
(116, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 06:17:34'),
(117, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 06:21:39'),
(118, 2, 'login', 'users', 2, 'User logged in', '::1', '2026-02-11 06:21:42'),
(119, 2, 'logout', 'users', 2, 'User logged out', '::1', '2026-02-11 06:39:15'),
(120, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 06:39:32'),
(121, 1, 'backup', 'system', 0, 'Downloaded full database backup (5 tables)', '::1', '2026-02-11 06:39:47'),
(122, 1, 'export', 'beneficiaries', 0, 'Exported 2 records from beneficiaries as CSV', '::1', '2026-02-11 06:40:14'),
(123, 1, 'import', 'beneficiaries', 0, 'Imported 2 records into beneficiaries from CSV', '::1', '2026-02-11 06:40:34'),
(124, 1, 'delete', 'beneficiaries', 5, 'Deleted beneficiary: Jonas Dela Cruz', '::1', '2026-02-11 06:40:45'),
(125, 1, 'delete', 'beneficiaries', 4, 'Deleted beneficiary: ROGER TOLMO', '::1', '2026-02-11 06:40:51'),
(126, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 06:47:17'),
(127, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 06:47:30'),
(128, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 06:48:11'),
(129, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 07:04:11'),
(130, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 07:06:19'),
(131, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 07:10:43'),
(132, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 07:42:50'),
(133, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 07:49:53'),
(134, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 07:59:12'),
(135, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 08:22:13'),
(136, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 14:11:08'),
(137, 1, 'create', 'beneficiaries', 6, 'Created new beneficiary', '::1', '2026-02-11 14:16:47'),
(138, 1, 'create', 'proponents', 37, 'Created new proponent', '::1', '2026-02-11 14:19:22'),
(139, 1, 'delete', 'proponents', 27, 'Deleted proponent: Non-LGU Test Organization', '::1', '2026-02-11 14:19:44'),
(140, 1, 'delete', 'proponents', 12, 'Deleted proponent: Test LGU Proponent', '::1', '2026-02-11 14:19:58'),
(141, 1, 'export', 'beneficiaries', 0, 'Exported 2 records from beneficiaries as CSV (2026-02-11 to 2026-02-11)', '::1', '2026-02-11 14:26:21'),
(142, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-11 14:34:58'),
(143, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-11 14:35:25'),
(144, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-16 06:40:29'),
(145, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-16 07:10:34'),
(146, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-02-16 07:10:34'),
(147, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-23 09:39:02'),
(148, 1, 'delete', 'beneficiaries', 6, 'Deleted beneficiary: Jennifer Ramsay', '::1', '2026-02-23 09:39:12'),
(149, 1, 'delete', 'beneficiaries', 3, 'Deleted beneficiary: Jonas Dela Cruz', '::1', '2026-02-23 09:39:17'),
(150, 1, 'delete', 'beneficiaries', 1, 'Deleted beneficiary: ROGER TOLMO', '::1', '2026-02-23 09:39:23'),
(151, 1, 'delete', 'proponents', 37, 'Deleted proponent: Small Farmers Group', '::1', '2026-02-23 09:39:31'),
(152, 1, 'delete', 'proponents', 36, 'Deleted proponent: Cadiz City', '::1', '2026-02-23 09:39:36'),
(153, 1, 'delete', 'proponents', 35, 'Deleted proponent: BULATA SMALL FISHERFOLKS ASSOCIATION (BUSFA)', '::1', '2026-02-23 09:39:48'),
(154, 1, 'delete', 'proponents', 34, 'Deleted proponent: GUIN-OLAYAN AGRARIAN REFORM COOPERATIVE (GARC)', '::1', '2026-02-23 09:40:09'),
(155, 1, 'delete', 'proponents', 30, 'Deleted proponent: Another Non-LGU-2', '::1', '2026-02-23 09:40:14'),
(156, 1, 'delete', 'proponents', 26, 'Deleted proponent: Non-LGU Test Organization', '::1', '2026-02-23 09:40:19'),
(157, 1, 'delete', 'proponents', 25, 'Deleted proponent: Test Non-LGU 1', '::1', '2026-02-23 09:40:23'),
(158, 1, 'delete', 'proponents', 23, 'Deleted proponent: Test Non-LGU 2', '::1', '2026-02-23 09:40:30'),
(159, 1, 'delete', 'proponents', 24, 'Deleted proponent: Test LGU', '::1', '2026-02-23 09:40:34'),
(160, 1, 'delete', 'proponents', 22, 'Deleted proponent: Test Non-LGU 1', '::1', '2026-02-23 09:40:38'),
(161, 1, 'delete', 'proponents', 21, 'Deleted proponent: Test Non-LGU Organization', '::1', '2026-02-23 09:40:45'),
(162, 1, 'delete', 'proponents', 16, 'Deleted proponent: Model Test', '::1', '2026-02-23 09:40:50'),
(163, 1, 'delete', 'proponents', 13, 'Deleted proponent: Test Non-LGU Proponent', '::1', '2026-02-23 09:40:54'),
(164, 1, 'delete', 'proponents', 10, 'Deleted proponent: Log Test Proponent', '::1', '2026-02-23 09:40:59'),
(165, 1, 'delete', 'proponents', 9, 'Deleted proponent: Form Test Non-LGU Proponent', '::1', '2026-02-23 09:41:05'),
(166, 1, 'delete', 'proponents', 1, 'Deleted proponent: LGU Sipalay City', '::1', '2026-02-23 09:41:11'),
(167, 1, 'import', 'beneficiaries', 0, 'Imported 51 records into beneficiaries from CSV', '::1', '2026-02-23 09:41:26'),
(168, 1, 'import', 'proponents', 0, 'Imported 9 records into proponents from CSV', '::1', '2026-02-23 09:41:36'),
(169, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-03-09 01:16:26'),
(170, 1, 'backup', 'system', 0, 'Downloaded full database backup (5 tables)', '::1', '2026-03-09 01:56:15'),
(171, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-03-09 02:09:28'),
(172, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-03-09 02:09:29'),
(173, 1, 'delete', 'beneficiaries', 7, 'Deleted beneficiary: test test', '::1', '2026-03-09 02:41:32'),
(174, 1, 'create', 'fieldwork_schedule', 1, 'Created fieldwork schedule: Test Plantation Inspection', '127.0.0.1', '2026-03-09 03:05:07'),
(175, 1, 'update', 'fieldwork_schedule', 1, 'Updated fieldwork schedule: Updated Plantation Inspection', '127.0.0.1', '2026-03-09 03:05:07'),
(176, 1, 'update', 'fieldwork_schedule', 1, 'Updated fieldwork status to: completed', '127.0.0.1', '2026-03-09 03:05:07'),
(177, 1, 'delete', 'fieldwork_schedule', 1, 'Deleted fieldwork schedule: Updated Plantation Inspection', '127.0.0.1', '2026-03-09 03:05:07'),
(178, 1, 'create', 'fieldwork_schedule', 2, 'Created fieldwork schedule: Fieldwork at VMC', '::1', '2026-03-09 03:14:19'),
(179, 1, 'create', 'fieldwork_schedule', 3, 'Created fieldwork schedule: Issuance of check', '::1', '2026-03-09 03:18:05'),
(180, 1, 'create', 'fieldwork_schedule', 4, 'Created fieldwork schedule: Sample', '::1', '2026-03-09 03:21:42'),
(181, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-03-09 03:47:35'),
(182, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-03-09 03:47:36'),
(183, 1, 'update', 'fieldwork_schedule', 3, 'Updated fieldwork schedule: Issuance of check', '::1', '2026-03-09 03:47:47'),
(184, 1, 'update', 'fieldwork_schedule', 4, 'Updated fieldwork schedule: Sample', '::1', '2026-03-09 03:49:47'),
(185, 1, 'update', 'fieldwork_schedule', 2, 'Updated fieldwork schedule: Fieldwork at VMC', '::1', '2026-03-09 03:49:55'),
(186, 1, 'update', 'fieldwork_schedule', 4, 'Updated fieldwork schedule: Sample', '::1', '2026-03-09 03:50:05'),
(187, 1, 'export', 'beneficiaries', 0, 'Exported 50 records from beneficiaries as CSV', '::1', '2026-03-09 03:54:53'),
(188, 1, 'export', 'proponents', 0, 'Exported 9 records from proponents as CSV', '::1', '2026-03-09 03:55:02'),
(189, 1, 'login', 'users', 1, 'User logged in', '221.121.97.33', '2026-03-09 06:35:57'),
(190, 1, 'logout', 'users', 1, 'User logged out', '221.121.97.33', '2026-03-09 06:37:46'),
(191, 1, 'login', 'users', 1, 'User logged in', '221.121.97.33', '2026-03-09 06:39:43'),
(192, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-03-09 07:51:55'),
(193, 1, 'logout', 'users', 1, 'User logged out', '119.92.137.254', '2026-03-09 07:55:27'),
(194, 1, 'login', 'users', 1, 'User logged in', '221.121.97.33', '2026-03-09 08:10:34'),
(195, 1, 'import', 'beneficiaries', 0, 'Imported 41 records into beneficiaries from CSV', '221.121.97.33', '2026-03-09 08:10:58'),
(196, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-03-09 09:16:27'),
(197, 3, 'login', 'users', 3, 'User logged in', '221.121.97.33', '2026-03-10 02:49:19'),
(198, 1, 'login', 'users', 1, 'User logged in', '221.121.97.33', '2026-03-10 02:54:47'),
(199, 3, 'logout', 'users', 3, 'User logged out', '221.121.97.33', '2026-03-10 03:47:10'),
(200, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-10 08:51:12'),
(201, 1, 'update', 'users', 3, 'Updated user ID: 3', '131.226.108.136', '2026-03-10 08:51:39'),
(202, 1, 'update', 'users', 2, 'Updated user ID: 2', '131.226.108.136', '2026-03-10 08:51:47'),
(203, 1, 'create', 'users', 5, 'Created new user: gretchen.dileepsys', '131.226.108.136', '2026-03-10 08:52:57'),
(204, 1, 'logout', 'users', 1, 'User logged out', '131.226.108.136', '2026-03-10 08:53:01'),
(205, 5, 'login', 'users', 5, 'User logged in', '131.226.108.136', '2026-03-10 08:53:06'),
(206, 5, 'logout', 'users', 5, 'User logged out', '131.226.108.136', '2026-03-10 08:54:32'),
(207, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-10 08:54:35'),
(208, 1, 'create', 'users', 6, 'Created new user: milson.admin', '131.226.108.136', '2026-03-10 08:55:27'),
(209, 1, 'logout', 'users', 1, 'User logged out', '131.226.108.136', '2026-03-10 08:55:37'),
(210, 6, 'login', 'users', 6, 'User logged in', '103.137.205.194', '2026-03-10 09:19:40'),
(211, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-10 10:14:47'),
(212, 1, 'logout', 'users', 1, 'User logged out', '131.226.108.136', '2026-03-10 10:15:15'),
(213, 4, 'login', 'users', 4, 'User logged in', '131.226.108.136', '2026-03-10 10:15:41'),
(214, 5, 'login', 'users', 5, 'User logged in', '131.226.108.136', '2026-03-10 10:53:07'),
(215, 4, 'login', 'users', 4, 'User logged in', '112.198.27.3', '2026-03-10 10:58:20'),
(216, 5, 'logout', 'users', 5, 'User logged out', '131.226.108.136', '2026-03-10 12:04:25'),
(217, 6, 'login', 'users', 6, 'User logged in', '103.137.205.194', '2026-03-10 15:12:37'),
(218, 6, 'logout', 'users', 6, 'User logged out', '103.137.205.194', '2026-03-10 15:12:57'),
(219, 6, 'login', 'users', 6, 'User logged in', '103.137.205.194', '2026-03-10 15:13:02'),
(220, 6, 'update', 'fieldwork_schedule', 2, 'Updated fieldwork schedule: Fieldwork at VMC', '103.137.205.194', '2026-03-10 15:19:18'),
(221, 6, 'update', 'fieldwork_schedule', 2, 'Updated fieldwork schedule: Fieldwork at VMC', '103.137.205.194', '2026-03-10 15:19:33'),
(222, 6, 'update', 'fieldwork_schedule', 2, 'Updated fieldwork schedule: Fieldwork at VMC', '103.137.205.194', '2026-03-10 15:19:54'),
(223, 6, 'delete', 'fieldwork_schedule', 2, 'Deleted fieldwork schedule: Fieldwork at VMC', '103.137.205.194', '2026-03-10 15:20:59'),
(224, 6, 'delete', 'fieldwork_schedule', 4, 'Deleted fieldwork schedule: Sample', '103.137.205.194', '2026-03-10 15:21:04'),
(225, 6, 'delete', 'fieldwork_schedule', 3, 'Deleted fieldwork schedule: Issuance of check', '103.137.205.194', '2026-03-10 15:21:16'),
(226, 6, 'create', 'fieldwork_schedule', 5, 'Created fieldwork schedule: Releasing of Various Check at DOLE Neg. Occ.', '103.137.205.194', '2026-03-10 15:24:22'),
(227, 6, 'create', 'fieldwork_schedule', 6, 'Created fieldwork schedule: Releasing of Check to LGU La Carlota', '103.137.205.194', '2026-03-10 15:25:48'),
(228, 6, 'update', 'fieldwork_schedule', 5, 'Updated fieldwork schedule: Releasing of Various Check at DOLE Neg. Occ.', '103.137.205.194', '2026-03-10 15:26:09'),
(229, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-11 01:45:34'),
(230, 4, 'login', 'users', 4, 'User logged in', '49.157.74.163', '2026-03-11 02:35:43'),
(231, 1, 'logout', 'users', 1, 'User logged out', '131.226.108.136', '2026-03-11 02:43:35'),
(232, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-11 03:46:08'),
(233, 4, 'logout', 'users', 4, 'User logged out', '49.157.74.163', '2026-03-11 04:14:58'),
(234, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-11 05:44:52'),
(235, 1, 'logout', 'users', 1, 'User logged out', '131.226.108.136', '2026-03-11 05:49:13'),
(236, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-11 05:49:46'),
(237, 1, 'logout', 'users', 1, 'User logged out', '131.226.108.136', '2026-03-11 05:50:04'),
(238, 4, 'login', 'users', 4, 'User logged in', '131.226.108.136', '2026-03-11 05:50:18'),
(239, 4, 'logout', 'users', 4, 'User logged out', '131.226.108.136', '2026-03-11 05:50:22'),
(240, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-11 05:50:26'),
(241, 1, 'logout', 'users', 1, 'User logged out', '131.226.108.136', '2026-03-11 05:51:42'),
(242, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-11 05:51:45'),
(243, 1, 'logout', 'users', 1, 'User logged out', '131.226.108.136', '2026-03-11 06:48:38'),
(244, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-11 06:50:18'),
(245, 1, 'logout', 'users', 1, 'User logged out', '131.226.108.136', '2026-03-11 06:50:22'),
(246, 4, 'login', 'users', 4, 'User logged in', '131.226.108.136', '2026-03-11 06:50:26'),
(247, 4, 'login', 'users', 4, 'User logged in', '121.58.229.34', '2026-03-11 06:56:57'),
(248, 4, 'logout', 'users', 4, 'User logged out', '121.58.229.34', '2026-03-11 07:02:06'),
(249, 4, 'logout', 'users', 4, 'User logged out', '131.226.108.136', '2026-03-11 07:22:19'),
(250, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-11 07:22:22'),
(251, 4, 'login', 'users', 4, 'User logged in', '121.58.229.34', '2026-03-11 07:52:54'),
(252, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-11 11:22:43'),
(253, 1, 'logout', 'users', 1, 'User logged out', '131.226.108.136', '2026-03-11 11:23:13'),
(254, 1, 'login', 'users', 1, 'User logged in', '221.121.97.33', '2026-03-12 02:27:54'),
(255, 1, 'logout', 'users', 1, 'User logged out', '221.121.97.33', '2026-03-12 02:28:35'),
(256, 1, 'login', 'users', 1, 'User logged in', '221.121.97.33', '2026-03-12 08:06:18'),
(257, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-12 14:37:29'),
(258, 6, 'login', 'users', 6, 'User logged in', '14.1.64.138', '2026-03-14 02:14:54'),
(259, 6, 'logout', 'users', 6, 'User logged out', '14.1.64.138', '2026-03-14 02:47:08'),
(260, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-15 09:52:14'),
(261, 1, 'update', 'fieldwork_schedule', 5, 'Updated fieldwork schedule: Releasing of Various Check at DOLE Neg. Occ.', '131.226.108.136', '2026-03-15 10:37:50'),
(262, 1, 'update', 'fieldwork_schedule', 6, 'Updated fieldwork schedule: Releasing of Check to LGU La Carlota', '131.226.108.136', '2026-03-15 10:38:05'),
(263, 1, 'update', 'fieldwork_schedule', 5, 'Updated fieldwork schedule: Releasing of Various Check at DOLE Neg. Occ.', '131.226.108.136', '2026-03-15 10:38:53'),
(264, 1, 'update', 'fieldwork_schedule', 6, 'Updated fieldwork schedule: Releasing of Check to LGU La Carlota', '131.226.108.136', '2026-03-15 10:39:04'),
(265, 1, 'login', 'users', 1, 'User logged in', '131.226.108.136', '2026-03-15 12:23:54'),
(266, 6, 'login', 'users', 6, 'User logged in', '103.137.205.194', '2026-03-15 15:49:28'),
(267, 6, 'update', 'fieldwork_schedule', 6, 'Updated fieldwork schedule: Releasing of Check to LGU La Carlota', '103.137.205.194', '2026-03-15 15:50:22'),
(268, 4, 'login', 'users', 4, 'User logged in', '202.90.135.62', '2026-03-17 03:26:59'),
(269, 4, 'logout', 'users', 4, 'User logged out', '203.96.181.20', '2026-03-17 04:01:39'),
(270, 6, 'login', 'users', 6, 'User logged in', '119.93.234.220', '2026-03-19 02:22:23'),
(271, 6, 'login', 'users', 6, 'User logged in', '119.93.234.220', '2026-03-19 05:54:54'),
(272, 6, 'logout', 'users', 6, 'User logged out', '119.93.234.220', '2026-03-19 06:00:08'),
(273, 6, 'login', 'users', 6, 'User logged in', '119.93.234.220', '2026-03-19 06:18:32'),
(274, 6, 'logout', 'users', 6, 'User logged out', '119.93.234.220', '2026-03-19 06:19:54'),
(275, 6, 'login', 'users', 6, 'User logged in', '119.93.234.220', '2026-03-19 06:21:30'),
(276, 6, 'logout', 'users', 6, 'User logged out', '119.93.234.220', '2026-03-19 06:29:02'),
(277, 6, 'login', 'users', 6, 'User logged in', '119.93.234.220', '2026-03-19 06:31:26'),
(278, 1, 'login', 'users', 1, 'User logged in', '119.93.234.220', '2026-03-19 06:33:42'),
(279, 1, 'logout', 'users', 1, 'User logged out', '119.93.234.220', '2026-03-19 06:33:48'),
(280, 1, 'login', 'users', 1, 'User logged in', '119.93.234.220', '2026-03-19 06:47:43'),
(281, 1, 'login', 'users', 1, 'User logged in', '131.226.108.3', '2026-03-21 07:28:32'),
(282, 1, 'login', 'users', 1, 'User logged in', '124.217.17.64', '2026-03-23 10:50:37'),
(283, 6, 'login', 'users', 6, 'User logged in', '124.217.17.64', '2026-03-23 10:54:31'),
(284, 1, 'login', 'users', 1, 'User logged in', '124.217.17.64', '2026-03-25 06:29:00'),
(285, 1, 'login', 'users', 1, 'User logged in', '131.226.109.147', '2026-03-28 13:03:07'),
(286, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-03-30 01:03:50'),
(287, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-04-01 03:15:10'),
(288, 6, 'login', 'users', 6, 'User logged in', '119.92.137.254', '2026-04-01 05:20:54'),
(289, 6, 'export', 'beneficiaries', 0, 'Exported 91 records from beneficiaries as CSV (2026-01-01 to 2026-03-31)', '119.92.137.254', '2026-04-01 06:42:08'),
(290, 6, 'export', 'proponents', 0, 'Exported 9 records from proponents as CSV (2026-01-01 to 2026-03-31)', '119.92.137.254', '2026-04-01 06:42:44'),
(291, 6, 'export', 'beneficiaries', 0, 'Exported 91 records from beneficiaries as CSV (2026-01-01 to 2026-03-31)', '119.92.137.254', '2026-04-01 06:42:52'),
(292, 6, 'login', 'users', 6, 'User logged in', '143.44.168.220', '2026-04-05 02:48:59'),
(293, 6, 'create', 'fieldwork_schedule', 7, 'Created fieldwork schedule: Project Turnover of LGU Escalante', '143.44.168.220', '2026-04-05 02:51:13'),
(294, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-04-06 01:36:56'),
(295, 3, 'logout', 'users', 3, 'User logged out', '119.92.137.254', '2026-04-06 02:07:15'),
(296, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-04-06 02:18:45'),
(297, 3, 'create', 'proponents', 47, 'Created new proponent', '119.92.137.254', '2026-04-06 03:06:12'),
(298, 3, 'update', 'proponents', 47, 'Updated proponent', '119.92.137.254', '2026-04-06 04:06:08'),
(299, 3, 'create', 'proponents', 48, 'Created new proponent', '119.92.137.254', '2026-04-06 04:09:48'),
(300, 3, 'logout', 'users', 3, 'User logged out', '119.92.137.254', '2026-04-06 04:39:37'),
(301, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-04-06 05:12:46'),
(302, 3, 'create', 'fieldwork_schedule', 8, 'Created fieldwork schedule: ACP VALIDATION AT ENFARBCO', '119.92.137.254', '2026-04-06 05:15:16'),
(303, 3, 'create', 'fieldwork_schedule', 9, 'Created fieldwork schedule: PROJECT PROPOSAL MAKING \"AVOFA\"', '119.92.137.254', '2026-04-06 05:19:06'),
(304, 3, 'create', 'fieldwork_schedule', 10, 'Created fieldwork schedule: PROJECT PROPOSAL MAKING \"TRUFA\"', '119.92.137.254', '2026-04-06 05:20:28'),
(305, 3, 'update', 'fieldwork_schedule', 9, 'Updated fieldwork schedule: PROJECT PROPOSAL MAKING \"AVOFA\"', '119.92.137.254', '2026-04-06 05:21:15'),
(306, 3, 'update', 'fieldwork_schedule', 10, 'Updated fieldwork schedule: PROJECT PROPOSAL MAKING \"TRUFA\"', '119.92.137.254', '2026-04-06 05:21:30'),
(307, 3, 'update', 'fieldwork_schedule', 6, 'Updated fieldwork schedule: Releasing of Check to LGU La Carlota', '119.92.137.254', '2026-04-06 05:22:34'),
(308, 3, 'update', 'fieldwork_schedule', 9, 'Updated fieldwork schedule: PROJECT PROPOSAL MAKING \"AVOFA\"', '119.92.137.254', '2026-04-06 05:23:06'),
(309, 3, 'update', 'fieldwork_schedule', 10, 'Updated fieldwork schedule: PROJECT PROPOSAL MAKING \"TRUFA\"', '119.92.137.254', '2026-04-06 05:23:41'),
(310, 3, 'update', 'fieldwork_schedule', 8, 'Updated fieldwork schedule: ACP VALIDATION AT ENFARBCO', '119.92.137.254', '2026-04-06 05:24:15'),
(311, 3, 'update', 'fieldwork_schedule', 5, 'Updated fieldwork schedule: Releasing of Various Check at DOLE Neg. Occ.', '119.92.137.254', '2026-04-06 05:25:31'),
(312, 3, 'update', 'fieldwork_schedule', 5, 'Updated fieldwork schedule: Releasing of Kabuhayan Check at DOLE Neg. Occ.', '119.92.137.254', '2026-04-06 05:26:01'),
(313, 3, 'create', 'fieldwork_schedule', 11, 'Created fieldwork schedule: Releasing of Kabuhayan Check at DOLE Neg. Occ.', '119.92.137.254', '2026-04-06 05:27:35'),
(314, 3, 'update', 'fieldwork_schedule', 11, 'Updated fieldwork schedule: Releasing of Kabuhayan Check at DOLE Neg. Occ.', '119.92.137.254', '2026-04-06 05:27:54'),
(315, 3, 'create', 'proponents', 49, 'Created new proponent', '119.92.137.254', '2026-04-06 07:14:32'),
(316, 3, 'update', 'proponents', 49, 'Updated proponent', '119.92.137.254', '2026-04-06 07:15:46'),
(317, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-04-06 08:12:22'),
(318, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-04-07 00:37:42'),
(319, 3, 'logout', 'users', 3, 'User logged out', '119.92.137.254', '2026-04-07 01:14:18'),
(320, 6, 'login', 'users', 6, 'User logged in', '103.137.205.194', '2026-04-07 13:23:19'),
(321, 6, 'create', 'fieldwork_schedule', 12, 'Created fieldwork schedule: Meet with DOST and TESDA', '103.137.205.194', '2026-04-07 13:35:18'),
(322, 6, 'update', 'fieldwork_schedule', 12, 'Updated fieldwork schedule: Meet with DOST and TESDA', '103.137.205.194', '2026-04-07 13:35:33'),
(323, 6, 'update', 'fieldwork_schedule', 7, 'Updated fieldwork schedule: Project Turnover of LGU Escalante', '103.137.205.194', '2026-04-07 14:02:58'),
(324, 6, 'logout', 'users', 6, 'User logged out', '103.137.205.194', '2026-04-07 14:34:41'),
(325, 6, 'login', 'users', 6, 'User logged in', '49.147.102.172', '2026-04-08 09:04:05'),
(326, 6, 'update', 'proponents', 48, 'Updated proponent', '49.147.102.172', '2026-04-08 09:09:57'),
(327, 6, 'update', 'proponents', 47, 'Updated proponent', '49.147.102.172', '2026-04-08 09:10:54'),
(328, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-04-13 03:49:37'),
(329, 1, 'logout', 'users', 1, 'User logged out', '119.92.137.254', '2026-04-13 04:58:33'),
(330, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-04-13 05:26:36'),
(331, 3, 'create', 'proponents', 50, 'Created new proponent', '119.92.137.254', '2026-04-13 05:47:40'),
(332, 3, 'create', 'fieldwork_schedule', 13, 'Created fieldwork schedule: LGU-LA CASTELANA (NOMINEE/S FOR KABUHAYAN AWARD INDIVIDUAL PROJECT CATEGORY)', '119.92.137.254', '2026-04-13 05:56:48'),
(333, 3, 'update', 'fieldwork_schedule', 13, 'Updated fieldwork schedule: LGU-LA CASTELANA (NOMINEE/S FOR KABUHAYAN AWARD INDIVIDUAL PROJECT CATEGORY)', '119.92.137.254', '2026-04-13 05:57:15'),
(334, 3, 'create', 'fieldwork_schedule', 14, 'Created fieldwork schedule: CARIDAD 1 (NOMINEE/S FOR KABUHAYAN AWARD GROUP PROJECT CATEGORY)', '119.92.137.254', '2026-04-13 06:00:30'),
(335, 3, 'update', 'fieldwork_schedule', 7, 'Updated fieldwork schedule: Project Turnover of LGU Escalante', '119.92.137.254', '2026-04-13 06:00:52'),
(336, 3, 'update', 'proponents', 50, 'Updated proponent', '119.92.137.254', '2026-04-13 06:04:14'),
(337, 3, 'create', 'proponents', 51, 'Created new proponent', '119.92.137.254', '2026-04-13 06:18:44'),
(338, 3, 'logout', 'users', 3, 'User logged out', '119.92.137.254', '2026-04-13 07:17:38'),
(339, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-04-13 07:17:53'),
(340, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-04-13 08:01:46'),
(341, 3, 'logout', 'users', 3, 'User logged out', '119.92.137.254', '2026-04-13 08:53:40'),
(342, 6, 'login', 'users', 6, 'User logged in', '119.92.137.254', '2026-04-15 08:40:29'),
(343, 6, 'create', 'fieldwork_schedule', 15, 'Created fieldwork schedule: Conduct of Orientation on the DOLE Integrated Livelihood Program (DILP) Cum Accreditation of CO-Partners (ACP)', '119.92.137.254', '2026-04-15 08:46:25'),
(344, 6, 'create', 'fieldwork_schedule', 16, 'Created fieldwork schedule: Mancom Meeting', '119.92.137.254', '2026-04-15 08:47:03'),
(345, 6, 'update', 'fieldwork_schedule', 16, 'Updated fieldwork schedule: Mancom Meeting', '119.92.137.254', '2026-04-15 08:47:15'),
(346, 6, 'delete', 'fieldwork_schedule', 16, 'Deleted fieldwork schedule: Mancom Meeting', '119.92.137.254', '2026-04-15 08:51:31'),
(347, 1, 'login', 'users', 1, 'User logged in', '131.226.111.184', '2026-04-15 11:31:50'),
(348, 6, 'login', 'users', 6, 'User logged in', '103.252.32.206', '2026-04-15 23:40:20'),
(349, 1, 'login', 'users', 1, 'User logged in', '58.69.78.33', '2026-04-16 01:09:52'),
(350, 1, 'update', 'proponents', 41, 'Updated proponent', '58.69.78.33', '2026-04-16 01:12:39'),
(351, 1, 'update', 'proponents', 45, 'Updated proponent', '58.69.78.33', '2026-04-16 01:14:32'),
(352, 6, 'login', 'users', 6, 'User logged in', '58.69.78.33', '2026-04-16 01:26:44'),
(353, 6, 'update', 'proponents', 51, 'Updated proponent', '58.69.78.33', '2026-04-16 01:27:52'),
(354, 6, 'update', 'proponents', 50, 'Updated proponent', '58.69.78.33', '2026-04-16 01:28:25'),
(355, 6, 'update', 'proponents', 49, 'Updated proponent', '58.69.78.33', '2026-04-16 01:28:49'),
(356, 6, 'update', 'proponents', 46, 'Updated proponent', '58.69.78.33', '2026-04-16 01:29:13'),
(357, 1, 'logout', 'users', 1, 'User logged out', '58.69.78.33', '2026-04-16 02:13:52'),
(358, 6, 'create', 'proponents', 52, 'Created new proponent', '58.69.78.33', '2026-04-16 03:40:51'),
(359, 6, 'delete', 'proponents', 52, 'Deleted proponent: LGU Isabela', '58.69.78.33', '2026-04-16 03:49:35'),
(360, 6, 'login', 'users', 6, 'User logged in', '58.69.78.33', '2026-04-16 04:47:20'),
(361, 6, 'logout', 'users', 6, 'User logged out', '58.69.78.33', '2026-04-16 05:56:25'),
(362, 6, 'login', 'users', 6, 'User logged in', '58.69.78.33', '2026-04-16 05:56:28'),
(363, 1, 'login', 'users', 1, 'User logged in', '131.226.111.184', '2026-04-16 15:24:22'),
(364, 6, 'login', 'users', 6, 'User logged in', '103.252.32.206', '2026-04-16 15:26:36'),
(365, 6, 'delete', 'proponents', 51, 'Deleted proponent: BRGY. GARGATO, HINIGARAN, NEGROS OCCIDENTAL', '103.252.32.206', '2026-04-16 15:27:11'),
(366, 6, 'delete', 'proponents', 50, 'Deleted proponent: LGU-LA CARLOTA CITY', '103.252.32.206', '2026-04-16 15:27:21'),
(367, 6, 'delete', 'proponents', 49, 'Deleted proponent: BARANGAY TALACDAN, CAUAYAN, NEG. OCC.', '103.252.32.206', '2026-04-16 15:27:38'),
(368, 6, 'delete', 'proponents', 46, 'Deleted proponent: NEW INDEPENDENT WORKERS ORGANIZATION-PACIWU', '103.252.32.206', '2026-04-16 15:27:50'),
(369, 6, 'delete', 'proponents', 45, 'Deleted proponent: LGU-ESCALANTE CITY', '103.252.32.206', '2026-04-16 15:28:03'),
(370, 6, 'delete', 'proponents', 44, 'Deleted proponent: LGU-SIPALAY CITY', '103.252.32.206', '2026-04-16 15:28:13'),
(371, 6, 'delete', 'proponents', 43, 'Deleted proponent: DAMAYAN NG MGA MANGGAGAWA, MAGSASAKA AT MANGINGISDA SA BANSA, INC.', '103.252.32.206', '2026-04-16 15:28:27'),
(372, 6, 'delete', 'proponents', 42, 'Deleted proponent: LGU-CALATRAVA', '103.252.32.206', '2026-04-16 15:28:38'),
(373, 6, 'delete', 'proponents', 41, 'Deleted proponent: CALUMANGAN MASCOBADO MILL WORKERS UNION (CAMMWU)', '103.252.32.206', '2026-04-16 15:28:49'),
(374, 6, 'delete', 'proponents', 40, 'Deleted proponent: BULATA SMALL FISHERFOLK ASSOCIATION (BUSFA)', '103.252.32.206', '2026-04-16 15:29:00'),
(375, 6, 'delete', 'proponents', 39, 'Deleted proponent: CHRISTIAN ADVOCATES FOR JUSTICE AND DEVELOPMENT IN NEGROS (CAJDEN), INC.', '103.252.32.206', '2026-04-16 15:29:10'),
(376, 6, 'delete', 'proponents', 38, 'Deleted proponent: GUIN-OLAYAN AGRARIAN REFORM COOPERATIVE (GARC)', '103.252.32.206', '2026-04-16 15:29:21'),
(377, 6, 'login', 'users', 6, 'User logged in', '103.252.32.206', '2026-04-16 22:54:18'),
(378, 6, 'update', 'proponents', 48, 'Updated proponent', '103.252.32.206', '2026-04-16 23:09:25'),
(379, 6, 'update', 'proponents', 48, 'Updated proponent', '103.252.32.206', '2026-04-16 23:09:54'),
(380, 6, 'login', 'users', 6, 'User logged in', '103.252.32.206', '2026-04-16 23:33:36'),
(381, 1, 'login', 'users', 1, 'User logged in', '58.69.78.33', '2026-04-17 00:16:28'),
(382, 1, 'login', 'users', 1, 'User logged in', '58.69.78.33', '2026-04-17 01:07:32'),
(383, 1, 'logout', 'users', 1, 'User logged out', '58.69.78.33', '2026-04-17 01:38:20'),
(384, 1, 'login', 'users', 1, 'User logged in', '58.69.78.33', '2026-04-17 04:21:16'),
(385, 1, 'logout', 'users', 1, 'User logged out', '58.69.78.33', '2026-04-17 04:54:09'),
(386, 1, 'login', 'users', 1, 'User logged in', '58.69.78.33', '2026-04-17 05:03:06'),
(387, 1, 'update', 'proponents', 48, 'Updated proponent', '58.69.78.33', '2026-04-17 05:11:47'),
(388, 6, 'login', 'users', 6, 'User logged in', '58.69.78.33', '2026-04-17 05:49:33'),
(389, 6, 'create', 'fieldwork_schedule', 17, 'Created fieldwork schedule: Mancom Meeting', '58.69.78.33', '2026-04-17 05:50:26'),
(390, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-04-20 08:04:32'),
(391, 1, 'logout', 'users', 1, 'User logged out', '119.92.137.254', '2026-04-20 08:05:32'),
(392, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-04-20 08:05:47'),
(393, 1, 'logout', 'users', 1, 'User logged out', '119.92.137.254', '2026-04-20 08:44:10'),
(394, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-05-04 10:36:35'),
(395, 1, 'logout', 'users', 1, 'User logged out', '119.92.137.254', '2026-05-04 10:37:16'),
(396, 6, 'login', 'users', 6, 'User logged in', '103.252.32.206', '2026-05-04 10:45:45'),
(397, 6, 'login', 'users', 6, 'User logged in', '103.252.32.206', '2026-05-04 13:15:25'),
(398, 1, 'login', 'users', 1, 'User logged in', '131.226.110.145', '2026-05-04 14:01:38'),
(399, 1, 'update', 'users', 6, 'Updated user ID: 6', '131.226.110.145', '2026-05-04 14:02:14'),
(400, 1, 'update', 'users', 6, 'Updated user ID: 6', '131.226.110.145', '2026-05-04 14:02:30'),
(401, 1, 'logout', 'users', 1, 'User logged out', '131.226.110.145', '2026-05-04 14:02:55'),
(402, 6, 'login', 'users', 6, 'User logged in', '131.226.110.145', '2026-05-04 14:03:11'),
(403, 6, 'logout', 'users', 6, 'User logged out', '131.226.110.145', '2026-05-04 14:03:16'),
(404, 6, 'login', 'users', 6, 'User logged in', '131.226.110.145', '2026-05-04 14:03:24'),
(405, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-06 03:39:19'),
(406, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-07 07:16:07');

-- --------------------------------------------------------

--
-- Table structure for table `beneficiaries`
--

CREATE TABLE `beneficiaries` (
  `id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `municipality` varchar(100) NOT NULL,
  `province` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `project_name` varchar(255) NOT NULL,
  `type_of_worker` varchar(100) DEFAULT NULL,
  `amount_worth` decimal(15,2) NOT NULL,
  `noted_findings` text DEFAULT NULL,
  `date_complied_by_proponent` date DEFAULT NULL,
  `date_forwarded_to_ro6` date DEFAULT NULL,
  `rpmt_findings` text DEFAULT NULL,
  `date_approved` date DEFAULT NULL,
  `date_forwarded_to_nofo` date DEFAULT NULL,
  `date_turnover` date DEFAULT NULL,
  `date_monitoring` date DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('pending','approved','implemented','monitored') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `beneficiaries`
--

INSERT INTO `beneficiaries` (`id`, `last_name`, `first_name`, `middle_name`, `suffix`, `gender`, `barangay`, `municipality`, `province`, `contact_number`, `project_name`, `type_of_worker`, `amount_worth`, `noted_findings`, `date_complied_by_proponent`, `date_forwarded_to_ro6`, `rpmt_findings`, `date_approved`, `date_forwarded_to_nofo`, `date_turnover`, `date_monitoring`, `latitude`, `longitude`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(8, 'ACHIVAR', 'MAE', 'DAMIREZ', NULL, 'Female', 'Taloc', 'BAGO CITY', NULL, '09619032537', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-01-28', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.57851102, 122.90991158, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(9, 'ALBANCES', 'CRIS ANGELIE', 'DAYAGANON', NULL, 'Female', 'Mansilingan', 'BACOLOD CITY (Capital)', NULL, '09706393683', 'RICE RETAILING PROJECT', 'DISPLACED WORKER', 30000.00, NULL, '2025-01-23', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.63280269, 122.97510468, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(10, 'ATONDUCAN', 'PABLITO', 'PANES', NULL, 'Male', 'Hacienda Fe', 'CITY OF ESCALANTE', NULL, '09667356865', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2024-11-11', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.86196258, 123.48608601, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(11, 'BREGIAS', 'RODRIGO', 'SUICO', NULL, 'Male', 'Old Poblacion', 'CITY OF ESCALANTE', NULL, '09260143419', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2024-12-26', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.82896419, 123.54839415, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(12, 'CANILLO', 'MICHAEL LORENZ', 'CONAG', NULL, 'Male', 'Japitan', 'CITY OF ESCALANTE', NULL, '09920264451', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2024-12-27', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.76591973, 123.54216127, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(13, 'DELA CRUZ', 'REYMARK', 'TAYTING', NULL, 'Male', 'Barangay II (Pob.)', 'LA CARLOTA CITY', NULL, '09307522855', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2024-11-21', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.43137523, 122.92207651, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(14, 'DELILING', 'SCOTT JAY', 'MONDOY', NULL, 'Male', 'Miranda', 'PONTEVEDRA', NULL, '09102883535', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2024-11-21', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.33781734, 122.86148145, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(15, 'DINANOY', 'CARMELITO', 'OSANO', NULL, 'Male', 'Atipuluan', 'BAGO CITY', NULL, '09261726605', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-01-28', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.50979328, 122.94948086, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(16, 'DIOSO', 'JIMMY', 'INFANTE', 'JR.', 'Male', 'Poblacion', 'BAGO CITY', NULL, '09065182680', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-01-28', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.53914493, 122.83650400, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(17, 'FLORES', 'REYNALDO', 'BALCECAS', NULL, 'Male', 'Barangay 16 (Pob.)', 'BACOLOD CITY (Capital)', NULL, '09633200476', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2024-11-20', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.66580845, 122.93896270, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(18, 'GANOY', 'JOHN NICHOL', 'VILLACERAN', NULL, 'Male', 'Barangay VII (Pob.)', 'CITY OF VICTORIAS', NULL, '09205377005', 'T-SHIRT PRINTING AND OTHER PRINTING JOBS PROJECT', 'REPATRIATED MT SOUNION CREW MEMBER', 30000.00, NULL, '2024-11-14', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.89949480, 123.07582906, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(19, 'GOLVIO', 'HECTOR', 'SINGUELAS', 'SR.', 'Male', 'Balingasag', 'BAGO CITY', NULL, '09859736599', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-01-28', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.52959178, 122.84348781, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(20, 'GUMBAN', 'REGIE', 'RAMIREZ', NULL, 'Male', 'Barangay 2 Pob. (Zone 2)', 'CADIZ CITY', NULL, '09076247619', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2024-11-22', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.95949895, 123.30634636, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(21, 'LICANIEL', 'JESSIE', 'HILADO', NULL, 'Male', 'Balingasag', 'BAGO CITY', NULL, '09383980540', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-01-28', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.52947152, 122.84441641, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(22, 'ORBIGOSO', 'MILAGROS', 'PENAFIEL', NULL, 'Female', 'Canturay', 'CITY OF SIPALAY', NULL, '09169756426', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2024-11-20', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 9.80681384, 122.42341137, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(23, 'PANHILASON', 'ROGIE', 'BARRANCO', NULL, 'Male', 'Barangay XIX-A', 'CITY OF VICTORIAS', NULL, '09162104229', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2024-11-04', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-30', NULL, 10.87472701, 123.06054541, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(24, 'SALMORIN', 'PAULO', 'BULLOR', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09072262837', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-01-28', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-07-16', NULL, 10.53598586, 122.83399013, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(25, 'SEMILLANO', 'RODEL', 'LANUTAN', NULL, 'Male', 'Lag-Asan', 'BAGO CITY', NULL, '09995503044', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-02-06', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.51984693, 122.84543119, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(26, 'SOLINO', 'JOVEN', 'VILLANUEVA', NULL, 'Male', 'Zone 10 (Pob.)', 'CITY OF TALISAY', NULL, '09187425989', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-03-12', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 10.72978165, 122.97567168, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(27, 'VOLUNTATE', 'JAINOR', 'TATON', 'JR.', 'Male', 'Salong', 'CITY OF KABANKALAN', NULL, '09483190399', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2024-11-21', '2025-03-18', NULL, '2025-05-13', '2025-05-13', '2025-06-10', NULL, 9.92797374, 122.77009644, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(28, 'ARAÑEZ', 'CRISTINA', 'SUMPAY', NULL, 'Female', 'Barangay VI Pob. (Hawaiian)', 'SILAY CITY', NULL, '09056665636', 'RICE RETAILING PROJECT', 'VENDOR', 29994.00, NULL, '2024-07-16', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.83059897, 123.00020980, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(29, 'BAYONETA', 'RANDY', 'SUMAGAYSAY', NULL, 'Male', 'Guinhalaran', 'SILAY CITY', NULL, '09662308339', 'RICE RETAILING PROJECT', 'DISPLACED WORKER', 29994.00, NULL, '2024-09-12', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.77224414, 122.97896563, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(30, 'CANDAR', 'JOVILLE', 'ESTRELLANES', NULL, 'Male', 'Handumanan', 'BACOLOD CITY (Capital)', NULL, '09382262987', 'RICE RETAILING PROJECT', 'VENDOR', 29994.00, NULL, '2024-06-03', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.60659340, 122.96552981, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(31, 'ESTIMADORA', 'EDUARDO', 'DECENA', 'JR.', 'Male', 'Guinhalaran', 'SILAY CITY', NULL, '09558694990', 'RICE RETAILING PROJECT', 'DISPLACED WORKER', 29994.00, NULL, '2024-09-12', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.77221776, 122.97850893, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(32, 'GOLEZ', 'EMELY', 'RACO', NULL, 'Female', 'Tuguis', 'HINIGARAN', NULL, '09382867852', 'RICE RETAILING PROJECT', 'VENDOR', 29994.00, NULL, '2024-06-28', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.29476075, 122.89751735, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(33, 'ISUGA', 'JAMESON', 'ROA', NULL, 'Male', 'Mansilingan', 'BACOLOD CITY (Capital)', NULL, '09389559820', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-30', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.63273986, 122.97517088, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(34, 'MONTALVO', 'KENNETH', 'HACHUELA', NULL, 'Male', 'Bubog', 'CITY OF TALISAY', NULL, '09187359609', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-07-03', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.76967781, 122.96322585, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(35, 'MUYCO', 'ARLENE', 'DOLOROSA', NULL, 'Female', 'Daga', 'CADIZ CITY', NULL, '09854967147', 'RICE RETAILING PROJECT', 'DISPLACED WORKER', 29994.00, NULL, '2024-08-06', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.95066719, 123.27532601, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(36, 'NEMENZO', 'NATHANIEL', 'CANIETE', NULL, 'Male', 'Zone 16 (Pob.)', 'CITY OF TALISAY', NULL, '09630100943', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-07-09', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.75460279, 122.98072997, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(37, 'REYES', 'GEMALYN', 'VARGAS', NULL, 'Male', 'Mambulac', 'SILAY CITY', NULL, '09319934358', 'RICE RETAILING PROJECT', 'VENDOR', 29994.00, NULL, '2024-08-12', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.79754251, 122.96773967, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(38, 'TOLMO', 'ROGER', 'TUAZON', 'JR.', 'Male', 'Barangay 29 (Pob.)', 'BACOLOD CITY (Capital)', NULL, '09773161932', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-07-29', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.66505829, 122.95041535, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(39, 'VALIAO', 'LUZVIMINDA', 'BELOS', NULL, 'Female', 'Camalanda-an', 'CAUAYAN', NULL, '09750349783', 'RICE RETAILING PROJECT', 'VENDOR', 29994.00, NULL, '2024-06-28', '2024-11-04', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 9.86007154, 122.49670518, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(40, 'ALVARICO', 'ROGER', 'VALENZUELA', 'JR.', 'Male', 'Ma-ao Barrio', 'BAGO CITY', NULL, '09534173827', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.48757247, 122.99224934, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(41, 'ATILLAGA', 'FERDINAND', 'DELA CRUZ', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09056662042', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-10', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.53587700, 122.83404702, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(42, 'BAYDID', 'MARK ANTHONY', 'GUEVARRA', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09495805728', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.53593667, 122.83380425, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(43, 'BLANCO', 'GLEN', 'POL', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09512465156', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.53590683, 122.83419875, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(44, 'GAMEZ', 'JOHN ELIEZER', 'PAGTAUGAN', NULL, 'Male', 'Binubuhan', 'BAGO CITY', NULL, '09942668352', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.45550355, 123.02291042, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(45, 'GOMEZ', 'ARMEL', 'MANALO-AN', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09157142681', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-11', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.53934206, 122.83668158, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(46, 'GUILARAN', 'JOHNNY', 'DE LA CRUZ', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09535990976', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.53937028, 122.83654123, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(47, 'KNITZ', 'MA. RONELIA', 'TIPAWAN', NULL, 'Female', 'Poblacion', 'BAGO CITY', NULL, '09196895922', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.53935460, 122.83644873, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(48, 'LAMELA', 'JOSE NARAMBULO', 'FERNANDEZ', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09959603288', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.53934206, 122.83650615, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(49, 'MAGHARI', 'AMALIA', 'BEDONIA', NULL, 'Female', 'Lag-Asan', 'BAGO CITY', NULL, '09659820581', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.53045142, 122.83867827, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(50, 'MANZANO', 'DAR JOHNREY', 'GALVE', NULL, 'Male', 'Busay', 'BAGO CITY', NULL, '09104373482', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.54045304, 122.86825039, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(51, 'MARTINEZ', 'RUBEN', 'BILLIONES', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09307911060', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.53933757, 122.83666627, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(52, 'PRADO', 'PEBRIL', 'LOBRIDO', NULL, 'Male', 'Taloc', 'BAGO CITY', NULL, '09661631316', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.57849710, 122.91018685, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(53, 'REPORAS', 'ANTONIO', 'ORNELIO', NULL, 'Male', 'Sagua Banua', 'VALLADOLID', NULL, '09515845335', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-19', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.45457418, 122.82981732, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(54, 'SUSPENE', 'MARJON', 'GINTELE', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09122818074', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.53938115, 122.83649958, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(55, 'TUGAFF', 'DAFFNY JOY', 'LAGUNDAY', NULL, 'Female', 'Ma-ao Barrio', 'BAGO CITY', NULL, '09158764787', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.48774908, 122.99207277, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(56, 'VALIENDE', 'JOHN DOMINIC', 'VEGA', NULL, 'Male', 'Balingasag', 'BAGO CITY', NULL, '09122990760', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-20', '2024-11-04', NULL, NULL, NULL, '2025-11-30', NULL, 10.52856023, 122.84298844, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(57, 'YULO', 'RENE', 'POBLADOR', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09535990976', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-04', NULL, NULL, NULL, '2025-01-30', NULL, 10.53934571, 122.83654725, 'implemented', 1, 1, '2026-02-23 09:41:26', '2026-02-23 09:41:26'),
(58, 'DALIDA', 'MICHAEL', 'TAMESIS', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09948458910', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-10', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.54218269, 122.84101012, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(59, 'ENRIQUEZ', 'MARY GRACE', 'TIPAWAN', NULL, 'Female', 'Poblacion', 'BAGO CITY', NULL, '09959603228', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.54218269, 122.84101012, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(60, 'GELISANGA', 'MICHELLE', 'ENRIQUEZ', NULL, 'Female', 'Lag-Asan', 'BAGO CITY', NULL, '09810558490', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '0224-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.53018653, 122.83890915, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(61, 'GUARRA', 'ARTURO', 'NATIVIDAD', NULL, 'Male', 'Malingin', 'BAGO CITY', NULL, '09319791870', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.49393963, 122.91866488, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(62, 'GUARRA', 'ROLLY', 'TITO', NULL, 'Male', 'Sampinit', 'BAGO CITY', NULL, '09917501453', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 299400.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.53811764, 122.85602478, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(63, 'GUILARAN', 'EDWIN', 'DALUMPPINES', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09956490884', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.53156611, 122.83579071, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(64, 'ISUBOL', 'GIOVANNE', 'ORCAJADA', NULL, 'Male', 'Caridad', 'BAGO CITY', NULL, '09648142783', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-10', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.48426122, 122.89598691, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(65, 'JAMELANO', 'ROMEO', 'BAGATELA', 'JR', 'Male', 'Calumangan', 'BAGO CITY', NULL, '09454692488', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-24', '2024-11-25', '2025-01-30', NULL, 10.55849027, 122.88167194, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(66, 'MAGBANUA', 'CRISTINA', 'BAYLON', NULL, 'Female', 'Poblacion', 'BAGO CITY', NULL, '09858223029', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.55284053, 122.89229802, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(67, 'MARTIR', 'ANTONIO', 'FERNANDEZ', 'JR', 'Male', 'Poblacion', 'BAGO CITY', NULL, '09127780762', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '0204-09-10', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.48548563, 122.88207475, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(68, 'NEGARE', 'EMMANUEL', 'MARTIR', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '094651437880', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.54139550, 122.83828628, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(69, 'ROMO', 'ROGELIO', 'PERUELO', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, '09460495263', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-10', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.54546659, 122.83713942, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(70, 'SASAKI', 'MAICA', 'GELISANGA', NULL, 'Female', 'Lag-Asan', 'BAGO CITY', NULL, '09512465156', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.53017544, 122.83860377, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(71, 'ALAYON', 'EDGAR', 'GUMBAN', NULL, 'Male', 'San Isidro', 'ENRIQUE B. MAGALONA (SARAVIA)', NULL, '09302020284', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-07-28', '2025-08-15', NULL, NULL, NULL, '2025-12-10', NULL, 10.79508190, 123.14072715, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(72, 'SAYO', 'ARMANDO', 'ARAGON', NULL, 'Male', 'Napoles', 'BAGO CITY', NULL, '09104264443', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.53945792, 122.83675636, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(73, 'SAYSON', 'ANTONIO', 'MONTINOLA', 'JR', 'Male', 'Napoles', 'BAGO CITY', NULL, '09056143328', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.51272373, 122.89788469, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(74, 'SERVA', 'NIEL', 'MORATA', NULL, 'Male', 'Pacol', 'BAGO CITY', NULL, '09407675616', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.50115967, 122.85935552, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(75, 'ALBA', 'ROMEO', 'PURILLO', NULL, 'Male', 'Salvacion', 'MURCIA', NULL, '09566111193', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-08-06', '2025-08-15', NULL, NULL, NULL, '2025-12-10', NULL, 10.53586775, 123.08814319, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(76, 'SUMAGGA', 'RICHARD', 'JARDINICO', NULL, 'Male', 'Poblacion', 'BAGO CITY', NULL, 'N/A', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2025-11-25', '2025-01-30', NULL, 10.53948280, 122.83663911, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(77, 'TAGOBADER', 'RONEL', 'MAHILUM', NULL, 'Male', 'Ma-ao Barrio', 'BAGO CITY', NULL, '09632180213', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 29994.00, NULL, '2024-09-09', '2024-11-05', NULL, '2024-11-25', '2024-11-25', '2025-01-30', NULL, 10.48769482, 122.99223094, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(78, 'ANDOJAR', 'ANGELITO', 'OLVIDENCA', NULL, 'Male', 'Salvacion', 'MURCIA', NULL, '09488931569', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-08-06', '2025-08-15', NULL, NULL, NULL, '2025-12-10', NULL, 10.60853398, 123.05383424, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(79, 'AQUINO', 'VIRGILIO', 'ESPAÑOLA', NULL, 'Male', 'Cabagnaan', 'LA CASTELLANA', NULL, '09859873138', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-08-06', '2025-08-15', NULL, NULL, NULL, '2025-12-10', NULL, 10.36379295, 123.11902218, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(80, 'ANTONARES', 'ELEANOR', 'ESCARION', NULL, 'Female', 'Zone 3 (Pob.)', 'CITY OF TALISAY', NULL, '09637614112', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, '2025-08-11', '2025-08-11', '2025-12-10', NULL, 10.74381840, 122.96331337, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(81, 'BARNIZO', 'RODELO', 'GALAGATE', NULL, 'Male', 'Pahanocoy', 'BACOLOD CITY (Capital)', NULL, '09092073743', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-07-14', '2025-08-15', NULL, NULL, NULL, '2025-12-10', NULL, 10.61039851, 122.93239293, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(82, 'GANE', 'SILVENO', 'BAGAFORO', 'JR', 'Male', 'Nato', 'LA CASTELLANA', NULL, '09934826042', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-08-04', '2025-08-15', NULL, NULL, NULL, '2025-12-10', NULL, 10.31972752, 123.01981312, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(83, 'CRUZ', 'RONALD', 'CATUGURAN', NULL, 'Male', 'Zone 12 (Pob.)', 'CITY OF TALISAY', NULL, '09638674564', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, '2025-08-11', '2025-08-11', '2025-12-11', NULL, 10.73806184, 122.96981892, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(84, 'GESULGON', 'CHRISTINE', 'DEPRA', NULL, 'Female', 'Barangay II (Pob.)', 'HINIGARAN', NULL, '09704763259', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-07-24', '2025-08-15', NULL, NULL, NULL, '2025-12-10', NULL, 10.27284040, 122.84723817, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(85, 'ELECCIONES', 'STEVE', 'GERNALIN', NULL, 'Male', 'Zone 2 (Pob.)', 'CITY OF TALISAY', NULL, '09306208740', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, '2025-08-11', '2025-08-11', '2025-12-11', NULL, 10.74455264, 122.96898167, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(86, 'POLITICO', 'RAMON', 'ROSAL', 'II', 'Male', 'Barangay II (Pob.)', 'LA CARLOTA CITY', NULL, '09947366998', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-08-06', '2025-08-15', NULL, NULL, NULL, '2025-12-18', NULL, 10.42727768, 122.91723231, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(87, 'SAMPONG', 'ROMEO', 'GONZALES', NULL, 'Male', 'Iglau-an', 'MURCIA', NULL, '09610967734', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-08-06', '2025-08-15', NULL, NULL, NULL, '2025-12-10', NULL, 10.56788380, 123.06000976, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(88, 'ESTANOL', 'JEFF BRYAN', 'N/A', NULL, 'Male', 'Dos Hermanas', 'CITY OF TALISAY', NULL, '09648937475', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, '2025-08-11', '2025-08-11', '2025-12-10', NULL, 10.74291365, 123.03673217, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(89, 'TOLENTINO', 'ALBERTO', 'RAMOS', NULL, 'Male', 'San Isidro', 'ENRIQUE B. MAGALONA (SARAVIA)', NULL, '09302020284', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-07-28', '2025-08-15', NULL, NULL, NULL, '2025-12-10', NULL, 10.77972272, 123.16825865, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(90, 'TOLENTINO', 'JOE ALLAN', 'RAMOS', NULL, 'Male', 'San Isidro', 'ENRIQUE B. MAGALONA (SARAVIA)', NULL, '09082270707', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-08-04', '2025-08-15', NULL, NULL, NULL, '2025-12-10', NULL, 10.77972272, 123.16825865, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(91, 'JACILDO', 'JAY', 'TOMA-AN', NULL, 'Male', 'Bubog', 'CITY OF TALISAY', NULL, '09949908936', 'RICE RETAILING PROJECT', 'PERSON WITH DEPRIVED LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, '2025-08-11', '2025-08-11', '2025-12-10', NULL, 10.77293139, 122.96287786, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(92, 'JUGADO', 'RONA', 'LAMAYO', NULL, 'Female', 'Zone 4-A (Pob.)', 'CITY OF TALISAY', NULL, '09509944300', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, NULL, NULL, '2025-12-10', NULL, 10.73994203, 122.96602093, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(93, 'LABAYEN', 'PAUL VINCENT', 'MALUNES', NULL, 'Male', 'Zone 12 (Pob.)', 'CITY OF TALISAY', NULL, '09771290081', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, NULL, NULL, '2025-12-10', NULL, 10.71931886, 123.01426642, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(94, 'LAMBOSON', 'JOEMARIE', 'JIMENEZ', 'JR', 'Male', 'Zone 12-A (Pob.)', 'CITY OF TALISAY', NULL, '09543194667', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, NULL, NULL, '2025-12-10', NULL, 10.73519564, 122.97484977, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(95, 'MALCO', 'JOSHUA', 'ESCOTOTO', NULL, 'Male', 'Zone 12-A (Pob.)', 'CITY OF TALISAY', NULL, '09167688969', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, NULL, NULL, '2025-12-10', NULL, 10.74003050, 122.97544028, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(96, 'OÑAS', 'LERO JANICE', 'DELLERA', NULL, 'Female', 'Zone 12-A (Pob.)', 'CITY OF TALISAY', NULL, '09638079377', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, NULL, NULL, '2025-12-10', NULL, 10.73996725, 122.97537591, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(97, 'SERAPIO', 'GARRY', 'BUENAFLOR', NULL, 'Male', 'Katilingban', 'CITY OF TALISAY', NULL, '09093932603', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, NULL, NULL, '2025-12-10', NULL, 10.71768747, 123.09519883, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58'),
(98, 'SUANQUE', 'GREYLAN CURT', 'LOMILLO', NULL, 'Male', 'Zone 4 (Pob.)', 'CITY OF TALISAY', NULL, '09460317809', 'RICE RETAILING PROJECT', 'PERSON DEPRIVED OF LIBERTY (PDL)', 30000.00, NULL, '2025-03-17', '2025-08-11', NULL, NULL, NULL, '2025-12-10', NULL, 10.73860886, 122.97111259, 'implemented', 1, 1, '2026-03-09 08:10:58', '2026-03-09 08:10:58');

-- --------------------------------------------------------

--
-- Table structure for table `fieldwork_schedule`
--

CREATE TABLE `fieldwork_schedule` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(500) DEFAULT NULL,
  `assigned_user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','ongoing','completed','missed') DEFAULT 'pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fieldwork_schedule`
--

INSERT INTO `fieldwork_schedule` (`id`, `title`, `description`, `location`, `assigned_user_id`, `start_date`, `end_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(5, 'Releasing of Kabuhayan Check at DOLE Neg. Occ.', '1. Brgy. Talacdan, Cauayan', 'DOLE Negros Occidental Field Office, Bacolod City', 2, '2026-03-16', '2026-03-16', 'completed', 6, '2026-03-10 15:24:22', '2026-04-06 05:26:01'),
(6, 'Releasing of Check to LGU La Carlota', 'Releasing of Check to LGU La Carlota at 10:00am', 'LGU La Carlota', 6, '2026-03-17', '2026-03-17', 'completed', 6, '2026-03-10 15:25:48', '2026-04-06 05:22:34'),
(7, 'Project Turnover of LGU Escalante', '1. Witness project turnover to 11 association\r\n2. Liquidation coaching \r\n3. ACP validation of HABARC', 'Escalante City', 6, '2026-04-08', '2026-04-08', 'completed', 6, '2026-04-05 02:51:13', '2026-04-13 06:00:52'),
(8, 'ACP VALIDATION AT ENFARBCO', 'Conduct ACP Validation at ECJ Negros Farm Agrarian Reform Beneficiaries Cooperative (ENFARBCO)', 'Hacienda Fe, La Carlota City, Negros Occidental', 3, '2026-03-25', '2026-03-25', 'completed', 3, '2026-04-06 05:15:16', '2026-04-06 05:24:15'),
(9, 'PROJECT PROPOSAL MAKING \"AVOFA\"', 'CONDUCT DILP ORIENTATION, PROJECT ID, PROPOSAL MAKING AND SITE VALIDATION WITH AMIA VILLAGE ORGANIC FARMERS ASSOCIATION (AVOFA)', 'BRGY. SAN ISIDRO, PONTEVEDRA', 6, '2026-03-11', '2026-03-13', 'completed', 3, '2026-04-06 05:19:06', '2026-04-06 05:23:06'),
(10, 'PROJECT PROPOSAL MAKING \"TRUFA\"', 'CONDUCT DILP ORIENTATION, PROJECT ID, PROPOSAL MAKING AND SITE VALIDATION WITH TAMPALON RAINFED FARMERS ASSOCIATION (TRUFA)', 'BRGY. TAMPALON, KABANKALAN CITY', 6, '2026-03-18', '2026-03-19', 'completed', 3, '2026-04-06 05:20:28', '2026-04-06 05:23:41'),
(11, 'Releasing of Kabuhayan Check at DOLE Neg. Occ.', '1. Brgy. Gargato, Hinigaran, Neg. Occ.', 'BRGY. GARGATO, HINIGARAN, NEG. OCC.', 2, '2026-03-18', '2026-03-18', 'completed', 3, '2026-04-06 05:27:35', '2026-04-06 05:27:54'),
(12, 'Meet with DOST and TESDA', 'Visit the TESDA and DOST located at Talisay and Bacolod City, respectively, for possible livelihood collaboration.', '', 6, '2026-04-07', '2026-04-07', 'completed', 6, '2026-04-07 13:35:18', '2026-04-07 13:35:33'),
(13, 'LGU-LA CASTELANA (NOMINEE/S FOR KABUHAYAN AWARD INDIVIDUAL PROJECT CATEGORY)', 'CONDUCT LGU-LA CASTELLANA AS ONE OF THE NOMINEE/S FOR KABUHAYAN AWARD INDIVIDUAL PROJECT CATEGORY)', 'LA CASTELLANA', 6, '2026-04-14', '2026-04-14', 'missed', 3, '2026-04-13 05:56:48', '2026-04-15 08:41:45'),
(14, 'CARIDAD 1 (NOMINEE/S FOR KABUHAYAN AWARD GROUP PROJECT CATEGORY)', 'CONDUCT WITH CARIDAD 1 AS ONE OF THE NOMINEE/S FOR KABUHAYAN AWARD GROUP PROJECT CATEGORY', 'BRGY. LUNA, CADIZ CITY', 6, '2026-04-15', '2026-04-15', 'missed', 3, '2026-04-13 06:00:30', '2026-04-16 02:45:18'),
(15, 'Conduct of Orientation on the DOLE Integrated Livelihood Program (DILP) Cum Accreditation of CO-Partners (ACP)', 'Discussion on the purpose and objective, as well as the requirements of ACP; conduct a livelihood proposal workshop on prospective Co-partners.', '', 6, '2026-05-05', '2026-05-05', 'missed', 6, '2026-04-15 08:46:25', '2026-05-06 05:48:03'),
(17, 'Mancom Meeting', '', 'Siquijor', 6, '2026-05-14', '2026-05-15', 'pending', 6, '2026-04-17 05:50:26', '2026-04-17 05:50:26');

-- --------------------------------------------------------

--
-- Table structure for table `proponents`
--

CREATE TABLE `proponents` (
  `id` int(11) NOT NULL,
  `proponent_type` enum('LGU-associated','Non-LGU-associated','By Administration','Others') NOT NULL,
  `date_received` date DEFAULT NULL,
  `noted_findings` text DEFAULT NULL,
  `control_number` varchar(50) DEFAULT NULL,
  `number_of_copies` int(11) DEFAULT NULL,
  `date_copies_received` date DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `proponent_name` varchar(255) NOT NULL,
  `project_title` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `number_of_associations` int(11) DEFAULT NULL,
  `total_beneficiaries` int(11) NOT NULL,
  `male_beneficiaries` int(11) DEFAULT 0,
  `female_beneficiaries` int(11) DEFAULT 0,
  `type_of_beneficiaries` varchar(255) DEFAULT NULL,
  `category` enum('Formation','Enhancement','Restoration') NOT NULL,
  `recipient_barangays` text DEFAULT NULL,
  `letter_of_intent_date` date DEFAULT NULL,
  `date_forwarded_to_ro6` date DEFAULT NULL,
  `rpmt_findings` text DEFAULT NULL,
  `date_complied_by_proponent` date DEFAULT NULL,
  `date_complied_by_proponent_nofo` date DEFAULT NULL,
  `date_forwarded_to_nofo` date DEFAULT NULL,
  `date_approved` date DEFAULT NULL,
  `date_check_release` date DEFAULT NULL,
  `check_number` varchar(50) DEFAULT NULL,
  `check_date_issued` date DEFAULT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `or_date_issued` date DEFAULT NULL,
  `date_turnover` date DEFAULT NULL,
  `date_implemented` date DEFAULT NULL,
  `date_liquidated` date DEFAULT NULL,
  `liquidation_deadline` date DEFAULT NULL,
  `date_monitoring` date DEFAULT NULL,
  `source_of_funds` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('pending','approved','implemented','liquidated','monitored') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proponents`
--

INSERT INTO `proponents` (`id`, `proponent_type`, `date_received`, `noted_findings`, `control_number`, `number_of_copies`, `date_copies_received`, `district`, `province`, `proponent_name`, `project_title`, `amount`, `number_of_associations`, `total_beneficiaries`, `male_beneficiaries`, `female_beneficiaries`, `type_of_beneficiaries`, `category`, `recipient_barangays`, `letter_of_intent_date`, `date_forwarded_to_ro6`, `rpmt_findings`, `date_complied_by_proponent`, `date_complied_by_proponent_nofo`, `date_forwarded_to_nofo`, `date_approved`, `date_check_release`, `check_number`, `check_date_issued`, `or_number`, `or_date_issued`, `date_turnover`, `date_implemented`, `date_liquidated`, `liquidation_deadline`, `date_monitoring`, `source_of_funds`, `latitude`, `longitude`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(47, 'Non-LGU-associated', '2026-03-12', '', NULL, 4, '2026-04-26', '4TH', NULL, 'AMIA VILLAGE ORGANIC FARMERS ASSOCIATION (AVOFA)', 'AGRI-VENTURE ON ORGANIC FERTILIZER AND RICE RETAILING ENTERPRISE', 2000000.00, 0, 52, 16, 36, 'FARMERS AND SENIOR CITIZEN', 'Formation', 'AMIA VILLAGE ORGANIC FARMERS ASSOCIATION (AVOFA)', '2026-03-12', '2026-03-26', '', '2026-03-26', NULL, NULL, '2026-03-31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 10.32330000, 122.90040000, 'approved', 3, 6, '2026-04-06 03:06:12', '2026-04-08 09:10:54'),
(48, 'Non-LGU-associated', '2026-03-18', '', NULL, 4, '2026-03-25', '6TH', '', 'TAMPALON RAINFED UPLAND FARMERS ASSOCIATION (TRUFA)', 'CONSOLIDATED PROJECT PROPOSAL', 5200000.00, 2, 129, 98, 31, 'FARMERS, SENIOR CITIZEN, VENDORS, DISPLACED WORKERS AND PDLs,', 'Enhancement', 'TAMPALON RAINFED UPLAND FARMERS ASSOCIATION (TRUFA)', '2026-03-18', '2026-03-26', '', NULL, NULL, NULL, '2026-03-31', NULL, NULL, NULL, NULL, NULL, '2026-01-14', NULL, NULL, '2026-03-15', '2026-07-13', '', 9.87290000, 122.79890000, 'approved', 3, 1, '2026-04-06 04:09:48', '2026-04-17 05:11:47');

--
-- Triggers `proponents`
--
DELIMITER $$
CREATE TRIGGER `calculate_liquidation_deadline` BEFORE INSERT ON `proponents` FOR EACH ROW BEGIN
    IF NEW.date_turnover IS NOT NULL THEN
        IF NEW.proponent_type = 'LGU-associated' THEN
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 10 DAY);
        ELSE
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 60 DAY);
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_liquidation_deadline` BEFORE UPDATE ON `proponents` FOR EACH ROW BEGIN
    IF NEW.date_turnover IS NOT NULL AND (
        OLD.date_turnover IS NULL OR 
        NEW.date_turnover != OLD.date_turnover OR 
        NEW.proponent_type != OLD.proponent_type
    ) THEN
        IF NEW.proponent_type = 'LGU-associated' THEN
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 10 DAY);
        ELSE
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 60 DAY);
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `proponent_associations`
--

CREATE TABLE `proponent_associations` (
  `id` int(11) NOT NULL,
  `proponent_id` int(11) NOT NULL,
  `association_name` varchar(255) NOT NULL,
  `association_address` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proponent_associations`
--

INSERT INTO `proponent_associations` (`id`, `proponent_id`, `association_name`, `association_address`, `sort_order`, `created_at`) VALUES
(48, 48, 'TAMPALON RAINFED UPLAND FARMERS ASSOCIATION (TRUFA)', 'BRGY. TAMPALON, KABANKALAN CITY', 0, '2026-04-17 05:11:47'),
(49, 48, 'INDIVIDUAL BENEFICIARIES', 'NEGROS OCCIDENTAL', 1, '2026-04-17 05:11:47');

-- --------------------------------------------------------

--
-- Table structure for table `proponent_returns`
--

CREATE TABLE `proponent_returns` (
  `id` int(11) NOT NULL,
  `proponent_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(191) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'maintenance_mode', '1', '2026-03-11 05:50:02', '2026-05-04 14:02:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','encoder','user') DEFAULT 'user',
  `full_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `full_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@dilp.gov.ph', '$2y$10$a6B7wXCzG83VKX.lX/h/seGi7H40EqquOlKeKgU3ytp/W.fpuOTkm', 'admin', 'Kim IT- Admin', 1, '2026-02-03 09:20:07', '2026-03-19 07:09:09'),
(2, 'kayzel', 'kayzel@dilp.com', '$2y$10$BWQuVA4vDhm2MiFRTn1WXO7NyuNBBKa.AxBw3UFiYVbrCkm2l3qhm', 'encoder', 'Kayzel Araneta', 1, '2026-02-04 07:44:20', '2026-03-10 08:51:47'),
(3, 'jona', 'jona@dilp.com', '$2y$10$cZIQmxS4jgtie9A5Iec4geSW1pYx859hf4j9oNlk2JIy9n0oyV/Na', 'encoder', 'Jona Cepriano', 1, '2026-02-04 07:46:59', '2026-03-10 08:51:39'),
(4, 'user', 'testuser@gmail.com', '$2y$10$PiaNVNl7pPhAPNeF8ri46ufDCHQi3kl9Bu9mIUk.RCxEj4WkwNFpe', 'user', 'test user', 1, '2026-02-04 07:47:46', '2026-02-04 07:47:46'),
(5, 'gretchen.dileepsys', 'gretchen@dileep.gov.ph', '$2y$10$S7Nv7F0eFpCD7inYJvuM/uvWdXciW/1XCuTN/6u6C4UHq7o5l3Og.', 'user', 'Gretchen Pasiolan', 1, '2026-03-10 08:52:57', '2026-03-10 08:52:57'),
(6, 'milson.admin', 'milson@dileep.gov.ph', '$2y$10$lVXUOHVkoPHj93vZToxhuurPo1dJh258P5WINhbtatIvyTPT.siiS', 'encoder', 'Milson Delos Reyes', 1, '2026-03-10 08:55:27', '2026-05-04 14:02:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_user` (`user_id`),
  ADD KEY `idx_activity_logs_table` (`table_name`,`record_id`);

--
-- Indexes for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_beneficiaries_municipality` (`municipality`),
  ADD KEY `idx_beneficiaries_barangay` (`barangay`),
  ADD KEY `idx_beneficiaries_status` (`status`),
  ADD KEY `idx_beneficiaries_date_approved` (`date_approved`),
  ADD KEY `idx_beneficiaries_province` (`province`);

--
-- Indexes for table `fieldwork_schedule`
--
ALTER TABLE `fieldwork_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fieldwork_status` (`status`),
  ADD KEY `idx_fieldwork_start_date` (`start_date`),
  ADD KEY `idx_fieldwork_end_date` (`end_date`),
  ADD KEY `idx_fieldwork_assigned_user` (`assigned_user_id`),
  ADD KEY `idx_fieldwork_created_by` (`created_by`);

--
-- Indexes for table `proponents`
--
ALTER TABLE `proponents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `control_number` (`control_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_proponents_type` (`proponent_type`),
  ADD KEY `idx_proponents_district` (`district`),
  ADD KEY `idx_proponents_status` (`status`),
  ADD KEY `idx_proponents_control_number` (`control_number`),
  ADD KEY `idx_proponents_date_approved` (`date_approved`),
  ADD KEY `idx_proponents_province` (`province`);

--
-- Indexes for table `proponent_associations`
--
ALTER TABLE `proponent_associations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proponent_id` (`proponent_id`);

--
-- Indexes for table `proponent_returns`
--
ALTER TABLE `proponent_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `returned_by` (`returned_by`),
  ADD KEY `idx_proponent_returns_proponent` (`proponent_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=407;

--
-- AUTO_INCREMENT for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `fieldwork_schedule`
--
ALTER TABLE `fieldwork_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `proponents`
--
ALTER TABLE `proponents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `proponent_associations`
--
ALTER TABLE `proponent_associations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `proponent_returns`
--
ALTER TABLE `proponent_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD CONSTRAINT `beneficiaries_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `beneficiaries_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `fieldwork_schedule`
--
ALTER TABLE `fieldwork_schedule`
  ADD CONSTRAINT `fieldwork_schedule_ibfk_1` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fieldwork_schedule_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `proponents`
--
ALTER TABLE `proponents`
  ADD CONSTRAINT `proponents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `proponents_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `proponent_associations`
--
ALTER TABLE `proponent_associations`
  ADD CONSTRAINT `proponent_associations_ibfk_1` FOREIGN KEY (`proponent_id`) REFERENCES `proponents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proponent_returns`
--
ALTER TABLE `proponent_returns`
  ADD CONSTRAINT `proponent_returns_ibfk_1` FOREIGN KEY (`proponent_id`) REFERENCES `proponents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `proponent_returns_ibfk_2` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
