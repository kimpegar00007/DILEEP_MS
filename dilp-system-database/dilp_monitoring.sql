-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 12, 2026 at 04:14 AM
-- Server version: 11.4.10-MariaDB-cll-lve
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dilemvwz_dilp_monitoring`
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
(405, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-05-06 03:34:32'),
(406, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-05-06 06:02:53'),
(407, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-05-07 05:00:58'),
(408, 1, 'update', 'proponents', 48, 'Updated proponent', '119.92.137.254', '2026-05-07 05:16:00'),
(409, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-05-07 09:08:22'),
(410, 1, 'reset', 'system', 0, 'Erased proponent and beneficiary records. Proponents: 2, Beneficiaries: 91.', '119.92.137.254', '2026-05-07 09:10:29'),
(411, 1, 'update', 'users', 3, 'Updated user ID: 3', '119.92.137.254', '2026-05-07 09:11:02'),
(412, 1, 'logout', 'users', 1, 'User logged out', '119.92.137.254', '2026-05-07 09:11:07'),
(413, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-07 09:11:13'),
(414, 3, 'logout', 'users', 3, 'User logged out', '119.92.137.254', '2026-05-07 09:11:16'),
(415, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-05-07 09:11:19'),
(416, 1, 'logout', 'users', 1, 'User logged out', '119.92.137.254', '2026-05-07 09:13:29'),
(417, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-07 09:13:57'),
(418, 3, 'logout', 'users', 3, 'User logged out', '119.92.137.254', '2026-05-07 09:15:46'),
(419, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-07 09:15:50'),
(420, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-07 09:18:12'),
(421, 3, 'create', 'proponents', 1, 'Created new proponent', '119.92.137.254', '2026-05-07 09:31:24'),
(422, 3, 'logout', 'users', 3, 'User logged out', '119.92.137.254', '2026-05-07 09:46:29'),
(423, 3, 'create', 'beneficiaries', 1, 'Created new beneficiary', '119.92.137.254', '2026-05-07 09:54:34'),
(424, 3, 'update', 'beneficiaries', 1, 'Updated beneficiary', '119.92.137.254', '2026-05-07 09:57:19'),
(425, 3, 'update', 'beneficiaries', 1, 'Updated beneficiary', '119.92.137.254', '2026-05-07 10:00:40'),
(426, 3, 'update', 'beneficiaries', 1, 'Updated beneficiary', '119.92.137.254', '2026-05-07 10:01:57'),
(427, 3, 'create', 'beneficiaries', 2, 'Created new beneficiary', '119.92.137.254', '2026-05-07 10:06:40'),
(428, 3, 'create', 'beneficiaries', 3, 'Created new beneficiary', '119.92.137.254', '2026-05-07 10:09:01'),
(429, 3, 'create', 'beneficiaries', 4, 'Created new beneficiary', '119.92.137.254', '2026-05-07 10:11:15'),
(430, 3, 'create', 'beneficiaries', 5, 'Created new beneficiary', '119.92.137.254', '2026-05-07 10:14:05'),
(431, 3, 'create', 'beneficiaries', 6, 'Created new beneficiary', '119.92.137.254', '2026-05-07 10:16:11'),
(432, 3, 'create', 'beneficiaries', 7, 'Created new beneficiary', '119.92.137.254', '2026-05-07 10:18:10'),
(433, 3, 'create', 'beneficiaries', 8, 'Created new beneficiary', '119.92.137.254', '2026-05-07 10:20:09'),
(434, 3, 'create', 'beneficiaries', 9, 'Created new beneficiary', '119.92.137.254', '2026-05-07 10:23:19'),
(435, 3, 'create', 'beneficiaries', 10, 'Created new beneficiary', '119.92.137.254', '2026-05-07 10:26:25'),
(436, 6, 'login', 'users', 6, 'User logged in', '103.252.32.206', '2026-05-10 09:36:13'),
(437, 6, 'create', 'beneficiaries', 11, 'Created new beneficiary', '103.252.32.206', '2026-05-10 09:56:51'),
(438, 6, 'update', 'beneficiaries', 11, 'Updated beneficiary', '103.252.32.206', '2026-05-10 10:02:40'),
(439, 6, 'update', 'beneficiaries', 11, 'Updated beneficiary', '103.252.32.206', '2026-05-10 10:06:44'),
(440, 6, 'update', 'beneficiaries', 11, 'Updated beneficiary', '103.252.32.206', '2026-05-10 10:08:42'),
(441, 6, 'update', 'fieldwork_schedule', 15, 'Updated fieldwork schedule: Conduct of Orientation on the DOLE Integrated Livelihood Program (DILP) Cum Accreditation of CO-Partners (ACP)', '103.252.32.206', '2026-05-10 10:33:06'),
(442, 6, 'update', 'fieldwork_schedule', 15, 'Updated fieldwork schedule: Courtesy Call to LCE of LGU La Castillana, Moises Padilla, Ilog and Cong. Dino Yulo', '103.252.32.206', '2026-05-10 10:35:40'),
(443, 6, 'update', 'fieldwork_schedule', 15, 'Updated fieldwork schedule: Courtesy Call to LCE of LGU La Castillana, Moises Padilla, Ilog and Cong. Dino Yulo', '103.252.32.206', '2026-05-10 10:35:50'),
(444, 6, 'create', 'fieldwork_schedule', 18, 'Created fieldwork schedule: Courtesy Call to LCE of LGU La Castellana, Moises Padilla, Ilog and Cong. Dino Yulo', '103.252.32.206', '2026-05-10 10:37:07'),
(445, 6, 'update', 'fieldwork_schedule', 18, 'Updated fieldwork schedule: Courtesy Call to LCE of LGU La Castellana, Moises Padilla, Ilog and Cong. Dino Yulo', '103.252.32.206', '2026-05-10 10:37:18'),
(446, 6, 'update', 'fieldwork_schedule', 15, 'Updated fieldwork schedule: Stakeholders\'Forum on Livelihood Recovery for Typhoon Tino by Philippine Red Cross', '103.252.32.206', '2026-05-10 10:38:16'),
(447, 6, 'create', 'fieldwork_schedule', 19, 'Created fieldwork schedule: Labor Day', '103.252.32.206', '2026-05-10 10:38:47'),
(448, 6, 'update', 'fieldwork_schedule', 19, 'Updated fieldwork schedule: Labor Day', '103.252.32.206', '2026-05-10 10:39:29'),
(449, 6, 'delete', 'fieldwork_schedule', 19, 'Deleted fieldwork schedule: Labor Day', '103.252.32.206', '2026-05-10 10:39:51'),
(450, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-11 01:09:04'),
(451, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-11 01:11:24'),
(452, 3, 'create', 'beneficiaries', 12, 'Created new beneficiary', '119.92.137.254', '2026-05-11 01:14:26'),
(453, 3, 'create', 'beneficiaries', 13, 'Created new beneficiary', '119.92.137.254', '2026-05-11 01:19:02'),
(454, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-11 01:20:45'),
(455, 3, 'create', 'beneficiaries', 14, 'Created new beneficiary', '119.92.137.254', '2026-05-11 01:22:59'),
(456, 3, 'return', 'proponents', 1, 'Application returned: beneficiaries', '119.92.137.254', '2026-05-11 01:25:28'),
(457, 3, 'update', 'fieldwork_schedule', 13, 'Updated fieldwork schedule: LGU-LA CASTELANA (NOMINEE/S FOR KABUHAYAN AWARD INDIVIDUAL PROJECT CATEGORY)', '119.92.137.254', '2026-05-11 01:30:53'),
(458, 3, 'update', 'fieldwork_schedule', 14, 'Updated fieldwork schedule: CARIDAD 1 (NOMINEE/S FOR KABUHAYAN AWARD GROUP PROJECT CATEGORY)', '119.92.137.254', '2026-05-11 01:31:12'),
(459, 3, 'create', 'beneficiaries', 15, 'Created new beneficiary', '119.92.137.254', '2026-05-11 01:42:00'),
(460, 3, 'create', 'beneficiaries', 16, 'Created new beneficiary', '119.92.137.254', '2026-05-11 01:45:05'),
(461, 3, 'create', 'beneficiaries', 17, 'Created new beneficiary', '119.92.137.254', '2026-05-11 01:47:55'),
(462, 3, 'create', 'beneficiaries', 18, 'Created new beneficiary', '119.92.137.254', '2026-05-11 01:50:43'),
(463, 3, 'create', 'beneficiaries', 19, 'Created new beneficiary', '119.92.137.254', '2026-05-11 01:52:49'),
(464, 3, 'create', 'beneficiaries', 20, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:11:28'),
(465, 3, 'create', 'beneficiaries', 21, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:14:55'),
(466, 3, 'create', 'beneficiaries', 22, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:17:17'),
(467, 3, 'create', 'beneficiaries', 23, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:19:18'),
(468, 3, 'create', 'beneficiaries', 24, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:21:46'),
(469, 3, 'update', 'proponents', 1, 'Updated proponent', '119.92.137.254', '2026-05-11 02:23:54'),
(470, 3, 'create', 'beneficiaries', 25, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:24:06'),
(471, 3, 'create', 'proponents', 2, 'Created new proponent', '119.92.137.254', '2026-05-11 02:25:21'),
(472, 3, 'create', 'beneficiaries', 26, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:28:54'),
(473, 3, 'create', 'beneficiaries', 27, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:31:45'),
(474, 3, 'create', 'beneficiaries', 28, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:38:02'),
(475, 3, 'create', 'beneficiaries', 29, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:40:55'),
(476, 3, 'create', 'beneficiaries', 30, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:42:58'),
(477, 3, 'create', 'beneficiaries', 31, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:45:42'),
(478, 3, 'create', 'beneficiaries', 32, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:48:07'),
(479, 3, 'create', 'beneficiaries', 33, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:50:26'),
(480, 3, 'create', 'beneficiaries', 34, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:53:50'),
(481, 3, 'create', 'beneficiaries', 35, 'Created new beneficiary', '119.92.137.254', '2026-05-11 02:59:28'),
(482, 3, 'create', 'beneficiaries', 36, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:02:14'),
(483, 3, 'create', 'beneficiaries', 37, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:04:32'),
(484, 3, 'create', 'beneficiaries', 38, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:07:21'),
(485, 3, 'create', 'beneficiaries', 39, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:09:23'),
(486, 3, 'create', 'beneficiaries', 40, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:12:30'),
(487, 3, 'create', 'beneficiaries', 41, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:15:14'),
(488, 3, 'create', 'beneficiaries', 42, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:17:45'),
(489, 3, 'create', 'beneficiaries', 43, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:20:40'),
(490, 3, 'create', 'beneficiaries', 44, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:23:01'),
(491, 3, 'create', 'beneficiaries', 45, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:26:34'),
(492, 3, 'create', 'beneficiaries', 46, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:30:36'),
(493, 3, 'create', 'beneficiaries', 47, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:33:24'),
(494, 3, 'create', 'beneficiaries', 48, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:35:39'),
(495, 3, 'create', 'beneficiaries', 49, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:38:27'),
(496, 3, 'create', 'beneficiaries', 50, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:41:24');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
(497, 3, 'create', 'beneficiaries', 51, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:43:40'),
(498, 3, 'create', 'beneficiaries', 52, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:46:12'),
(499, 3, 'create', 'beneficiaries', 53, 'Created new beneficiary', '119.92.137.254', '2026-05-11 03:48:34'),
(500, 3, 'logout', 'users', 3, 'User logged out', '119.92.137.254', '2026-05-11 04:30:41'),
(501, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-11 05:21:04'),
(502, 3, 'update', 'beneficiaries', 2, 'Updated beneficiary', '119.92.137.254', '2026-05-11 05:22:30'),
(503, 3, 'create', 'fieldwork_schedule', 20, 'Created fieldwork schedule: LIQUIDATION COACHING', '119.92.137.254', '2026-05-11 05:33:28'),
(504, 3, 'create', 'fieldwork_schedule', 21, 'Created fieldwork schedule: LGU-Bago City', '119.92.137.254', '2026-05-11 05:39:07'),
(505, 3, 'create', 'fieldwork_schedule', 22, 'Created fieldwork schedule: DILP ORIENTATION', '119.92.137.254', '2026-05-11 05:47:31'),
(506, 3, 'update', 'fieldwork_schedule', 21, 'Updated fieldwork schedule: DILP ORIENTATION', '119.92.137.254', '2026-05-11 05:47:56'),
(507, 6, 'login', 'users', 6, 'User logged in', '103.252.32.206', '2026-05-11 06:23:06'),
(508, 6, 'create', 'fieldwork_schedule', 23, 'Created fieldwork schedule: TESDA appointment', '103.252.32.206', '2026-05-11 06:28:44'),
(509, 6, 'update', 'fieldwork_schedule', 23, 'Updated fieldwork schedule: TESDA appointment 10:00am', '103.252.32.206', '2026-05-11 06:29:05'),
(510, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-11 06:56:36'),
(511, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-11 07:12:23'),
(512, 3, 'update', 'proponents', 1, 'Updated proponent', '119.92.137.254', '2026-05-11 07:47:21'),
(513, 3, 'create', 'beneficiaries', 54, 'Created new beneficiary', '119.92.137.254', '2026-05-11 07:56:02'),
(514, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-05-11 07:56:50'),
(515, 3, 'create', 'beneficiaries', 55, 'Created new beneficiary', '119.92.137.254', '2026-05-11 07:58:09'),
(516, 3, 'create', 'beneficiaries', 56, 'Created new beneficiary', '119.92.137.254', '2026-05-11 07:59:59'),
(517, 3, 'create', 'beneficiaries', 57, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:02:53'),
(518, 3, 'update', 'beneficiaries', 57, 'Updated beneficiary', '119.92.137.254', '2026-05-11 08:03:07'),
(519, 3, 'update', 'beneficiaries', 56, 'Updated beneficiary', '119.92.137.254', '2026-05-11 08:03:32'),
(520, 3, 'update', 'beneficiaries', 55, 'Updated beneficiary', '119.92.137.254', '2026-05-11 08:03:52'),
(521, 3, 'update', 'beneficiaries', 54, 'Updated beneficiary', '119.92.137.254', '2026-05-11 08:04:22'),
(522, 3, 'create', 'beneficiaries', 58, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:06:22'),
(523, 3, 'create', 'beneficiaries', 59, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:08:38'),
(524, 3, 'update', 'beneficiaries', 54, 'Updated beneficiary', '119.92.137.254', '2026-05-11 08:09:11'),
(525, 3, 'update', 'beneficiaries', 55, 'Updated beneficiary', '119.92.137.254', '2026-05-11 08:09:40'),
(526, 3, 'update', 'beneficiaries', 56, 'Updated beneficiary', '119.92.137.254', '2026-05-11 08:10:19'),
(527, 3, 'update', 'beneficiaries', 57, 'Updated beneficiary', '119.92.137.254', '2026-05-11 08:11:04'),
(528, 3, 'update', 'beneficiaries', 58, 'Updated beneficiary', '119.92.137.254', '2026-05-11 08:11:32'),
(529, 3, 'update', 'proponents', 2, 'Updated proponent', '119.92.137.254', '2026-05-11 08:13:39'),
(530, 3, 'create', 'beneficiaries', 60, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:16:39'),
(531, 3, 'update', 'beneficiaries', 60, 'Updated beneficiary', '119.92.137.254', '2026-05-11 08:16:49'),
(532, 3, 'create', 'beneficiaries', 61, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:20:37'),
(533, 3, 'create', 'beneficiaries', 62, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:24:51'),
(534, 3, 'create', 'beneficiaries', 63, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:27:09'),
(535, 3, 'create', 'beneficiaries', 64, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:29:08'),
(536, 3, 'create', 'beneficiaries', 65, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:30:57'),
(537, 3, 'create', 'beneficiaries', 66, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:33:07'),
(538, 3, 'create', 'beneficiaries', 67, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:35:00'),
(539, 3, 'create', 'beneficiaries', 68, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:37:31'),
(540, 3, 'create', 'beneficiaries', 69, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:52:19'),
(541, 3, 'create', 'beneficiaries', 70, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:54:29'),
(542, 3, 'create', 'beneficiaries', 71, 'Created new beneficiary', '119.92.137.254', '2026-05-11 08:58:03'),
(543, 3, 'update', 'beneficiaries', 62, 'Updated beneficiary', '119.92.137.254', '2026-05-11 08:58:39'),
(544, 3, 'logout', 'users', 3, 'User logged out', '119.92.137.254', '2026-05-11 08:59:45'),
(545, 3, 'update', 'beneficiaries', 13, 'Updated beneficiary', '119.92.137.254', '2026-05-11 09:08:36'),
(546, 3, 'update', 'beneficiaries', 64, 'Updated beneficiary', '119.92.137.254', '2026-05-11 09:16:22'),
(547, 3, 'update', 'beneficiaries', 60, 'Updated beneficiary', '119.92.137.254', '2026-05-11 09:19:52'),
(548, 3, 'update', 'beneficiaries', 59, 'Updated beneficiary', '119.92.137.254', '2026-05-11 09:21:04'),
(549, 3, 'update', 'beneficiaries', 19, 'Updated beneficiary', '119.92.137.254', '2026-05-11 09:36:48'),
(550, 3, 'update', 'beneficiaries', 18, 'Updated beneficiary', '119.92.137.254', '2026-05-11 09:39:03'),
(551, 3, 'update', 'beneficiaries', 5, 'Updated beneficiary', '119.92.137.254', '2026-05-11 09:43:25'),
(552, 3, 'update', 'beneficiaries', 6, 'Updated beneficiary', '119.92.137.254', '2026-05-11 09:45:23'),
(553, 3, 'update', 'beneficiaries', 7, 'Updated beneficiary', '119.92.137.254', '2026-05-11 09:47:58'),
(554, 3, 'update', 'beneficiaries', 8, 'Updated beneficiary', '119.92.137.254', '2026-05-11 09:49:49'),
(555, 3, 'update', 'beneficiaries', 8, 'Updated beneficiary', '119.92.137.254', '2026-05-11 09:50:05'),
(556, 3, 'update', 'beneficiaries', 12, 'Updated beneficiary', '119.92.137.254', '2026-05-11 10:01:13'),
(557, 3, 'update', 'beneficiaries', 11, 'Updated beneficiary', '119.92.137.254', '2026-05-11 10:10:47'),
(558, 3, 'create', 'beneficiaries', 72, 'Created new beneficiary', '119.92.137.254', '2026-05-11 10:14:04'),
(559, 3, 'create', 'beneficiaries', 73, 'Created new beneficiary', '119.92.137.254', '2026-05-11 10:15:48'),
(560, 3, 'create', 'beneficiaries', 74, 'Created new beneficiary', '119.92.137.254', '2026-05-11 10:17:43'),
(561, 3, 'update', 'beneficiaries', 73, 'Updated beneficiary', '119.92.137.254', '2026-05-11 10:18:13'),
(562, 1, 'login', 'users', 1, 'User logged in', '131.226.109.197', '2026-05-11 13:25:04'),
(563, 6, 'login', 'users', 6, 'User logged in', '103.252.32.206', '2026-05-11 23:15:19'),
(564, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-12 00:18:26'),
(565, 6, 'login', 'users', 6, 'User logged in', '143.44.170.108', '2026-05-12 00:53:59'),
(566, 6, 'logout', 'users', 6, 'User logged out', '143.44.170.108', '2026-05-12 00:54:05'),
(567, 3, 'login', 'users', 3, 'User logged in', '119.92.137.254', '2026-05-12 01:45:33'),
(568, 6, 'login', 'users', 6, 'User logged in', '143.44.170.108', '2026-05-12 02:24:35'),
(569, 6, 'login', 'users', 6, 'User logged in', '143.44.170.108', '2026-05-12 03:13:45'),
(570, 6, 'login', 'users', 6, 'User logged in', '143.44.170.108', '2026-05-12 04:46:40'),
(571, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-05-12 05:35:58'),
(572, 6, 'login', 'users', 6, 'User logged in', '143.44.170.108', '2026-05-12 07:09:25'),
(573, 1, 'login', 'users', 1, 'User logged in', '119.92.137.254', '2026-05-12 07:54:02'),
(574, 1, 'logout', 'users', 1, 'User logged out', '119.92.137.254', '2026-05-12 07:54:17'),
(575, 6, 'login', 'users', 6, 'User logged in', '143.44.170.108', '2026-05-12 08:10:00');

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
(1, 'TINGAL', 'RODELIO', 'JOVEN', '', 'Male', 'Buenavista', 'CITY OF ESCALANTE', 'Negros Occidental', '09569154990', 'INTEGRATED FISHING AND RICE RETAILING PROJECT', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.80034590, 123.55377300, 'approved', 3, 3, '2026-05-07 09:54:34', '2026-05-07 10:00:40'),
(2, 'FUNDADOR', 'ALFREDO', 'SANOY', '', 'Male', 'Tabu', 'ILOG', 'Negros Occidental', '09383458405', 'COMMODITY STORE', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 9.89268280, 122.70999190, 'approved', 3, 3, '2026-05-07 10:06:40', '2026-05-11 05:22:30'),
(3, 'MORANDARTE', 'NESTOR', 'LECEÑA', '', 'Male', 'Tigbon', 'CALATRAVA', 'Negros Occidental', '09222731174', 'COMMODITIES STORE', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.61244970, 123.44550320, 'approved', 3, 3, '2026-05-07 10:09:01', '2026-05-07 10:09:01'),
(4, 'PELINGON', 'RODOLFO', 'FUERTES', 'JR', 'Male', 'Barangay VIII (Pob.)', 'CITY OF VICTORIAS', 'Negros Occidental', '09462656968', 'COMMODITY STORE', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.90134120, 123.07149430, 'approved', 3, 3, '2026-05-07 10:11:15', '2026-05-07 10:11:15'),
(5, 'CONDES', 'DIOSDADO', 'SAMPIT', '', 'Male', 'Granada', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09816385325', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.66600000, 123.03450000, 'approved', 3, 3, '2026-05-07 10:14:05', '2026-05-11 09:43:25'),
(6, 'BEGASA', 'NAPOLEON', 'JAPITAN', 'III', 'Male', 'Estefania', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09289351404', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.66980000, 122.98740000, 'approved', 3, 3, '2026-05-07 10:16:11', '2026-05-11 09:45:23'),
(7, 'CARMONA', 'EDEN', 'OLMO', '', 'Male', 'Barangay 12 (Pob.)', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09701072482', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.68340000, 122.95060000, 'approved', 3, 3, '2026-05-07 10:18:10', '2026-05-11 09:47:58'),
(8, 'CACERES', 'LAZARITO', 'PAMINTU-AN', 'JR', 'Male', 'Barangay 16 (Pob.)', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09090419503', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.66600000, 122.93920000, 'approved', 3, 3, '2026-05-07 10:20:09', '2026-05-11 09:50:05'),
(9, 'CAUSING', 'REZ', 'DOLOROSA', '', 'Male', 'Barangay XIX-A', 'CITY OF VICTORIAS', 'Negros Occidental', '09457964237', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.90134120, 123.07149430, 'approved', 3, 3, '2026-05-07 10:23:19', '2026-05-07 10:23:19'),
(10, 'CHAVEZ', 'ERICKSON', 'SANGRENES', '', 'Male', 'Daga', 'CADIZ CITY', 'Negros Occidental', '09543198432', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.94812490, 123.28467940, 'approved', 3, 3, '2026-05-07 10:26:25', '2026-05-07 10:26:25'),
(11, 'ESTOLANO', 'JONATHAN', 'LOGRONIO', '', 'Male', 'Barangay 2 (Pob.)', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09338611441', 'COMMODITY STORE', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', '2026-05-08', '2026-05-08', NULL, 10.68340000, 122.95060000, 'approved', 6, 3, '2026-05-10 09:56:51', '2026-05-11 10:10:47'),
(12, 'CONDES', 'SAMSON', 'SAMPIT', '', 'Male', 'Granada', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09164361106', 'COMMODITIES STORE', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.66600000, 123.03450000, 'approved', 3, 3, '2026-05-11 01:14:26', '2026-05-11 10:01:13'),
(13, 'SERUELO', 'LENNY', 'LIBO-ON', '', 'Female', 'Handumanan', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09462656968', 'COMMODITY STORE', 'DISPLACED WORKER', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.60660000, 122.96580000, 'approved', 3, 3, '2026-05-11 01:19:02', '2026-05-11 09:08:36'),
(14, 'ABAJA', 'FRANKLIN', 'MATULA', '', 'Male', 'Binicuil', 'CITY OF KABANKALAN', 'Negros Occidental', '09954869883', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.02160700, 122.82199380, 'approved', 3, 3, '2026-05-11 01:22:59', '2026-05-11 01:22:59'),
(15, 'ARZAGA', 'DELMER', 'PADASAS', '', 'Male', 'Bagroy', 'BINALBAGAN', 'Negros Occidental', '09678166446', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.20550500, 122.94481810, 'approved', 3, 3, '2026-05-11 01:42:00', '2026-05-11 01:42:00'),
(16, 'CAÑETE', 'RANDY', 'LUMOGDANG', '', 'Male', 'Sag-Ang', 'LA CASTELLANA', 'Negros Occidental', '09922990564', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.37020240, 123.08286820, 'approved', 3, 3, '2026-05-11 01:45:05', '2026-05-11 01:45:05'),
(17, 'CARAY', 'JOHN KENNETH', 'OTILLA', '', 'Male', 'Nato', 'LA CASTELLANA', 'Negros Occidental', '09090419503', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.31436970, 122.98909300, 'approved', 3, 3, '2026-05-11 01:47:55', '2026-05-11 01:47:55'),
(18, 'CONDE', 'JUDY', 'GALLEGO', '', 'Male', 'Banago', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09457964237', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-26', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.70360000, 122.95010000, 'approved', 3, 3, '2026-05-11 01:50:43', '2026-05-11 09:39:03'),
(19, 'CONDE', 'DARIUS', 'GALLEGO', '', 'Male', 'Banago', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09946027885', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.70360000, 122.95010000, 'approved', 3, 3, '2026-05-11 01:52:49', '2026-05-11 09:36:48'),
(20, 'CONTIGA', 'ROGELIO', 'MAHINAY', '', 'Male', 'Bacong-Montilla', 'BAGO CITY', 'Negros Occidental', '09771293405', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.51894560, 123.03455930, 'approved', 3, 3, '2026-05-11 02:11:28', '2026-05-11 02:11:28'),
(21, 'DIAMANTE', 'ROBERTO', 'ESPARAGOSA', '', 'Male', 'Robles (Pob.)', 'LA CASTELLANA', 'Negros Occidental', '09307062521', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.32304980, 123.01874180, 'approved', 3, 3, '2026-05-11 02:14:55', '2026-05-11 02:14:55'),
(22, 'DIANONGCO', 'JANILO', 'GABASAN', '', 'Male', 'Aguisan', 'CITY OF HIMAMAYLAN', 'Negros Occidental', '09629721752', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.12477920, 122.88793290, 'approved', 3, 3, '2026-05-11 02:17:17', '2026-05-11 02:17:17'),
(23, 'DIAZ', 'JAMES', 'SABECO', '', 'Male', 'Aguisan', 'CITY OF HIMAMAYLAN', 'Negros Occidental', '09129819987', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.12477920, 122.88793290, 'approved', 3, 3, '2026-05-11 02:19:18', '2026-05-11 02:19:18'),
(24, 'DUENA', 'JOEL', 'BIRONDO', '', 'Male', 'Zone 12 (Pob.)', 'CITY OF TALISAY', 'Negros Occidental', '09850803980', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.73726490, 122.96733250, 'approved', 3, 3, '2026-05-11 02:21:46', '2026-05-11 02:21:46'),
(25, 'ESMAYAN', 'JOE-AN', 'PEBRIO', '', 'Male', 'Tagda', 'HINIGARAN', 'Negros Occidental', '09703885130', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.26558830, 122.84428370, 'approved', 3, 3, '2026-05-11 02:24:06', '2026-05-11 02:24:06'),
(26, 'ESMAYAN', 'ROBERTO', 'MONDEJAR', '', 'Male', 'Tagda', 'HINIGARAN', 'Negros Occidental', '09703885130', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.26558830, 122.84428370, 'approved', 3, 3, '2026-05-11 02:28:54', '2026-05-11 02:28:54'),
(27, 'GABUCAY', 'GARRY', 'ARSENAL', '', 'Male', 'Tapi', 'CITY OF KABANKALAN', 'Negros Occidental', '09682183445', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 9.82967430, 122.75483960, 'approved', 3, 3, '2026-05-11 02:31:45', '2026-05-11 02:31:45'),
(28, 'GARCIA', 'RICHARD JOHN', 'ROSITE', '', 'Male', 'Daga', 'CADIZ CITY', 'Negros Occidental', '09277994854', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.96570670, 123.29100930, 'approved', 3, 3, '2026-05-11 02:38:02', '2026-05-11 02:38:02'),
(29, 'GUANGCO', 'ALFIO', 'ORBEGUSO', '', 'Male', 'Tinongan', 'ISABELA', 'Negros Occidental', '09488011749', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.21414770, 123.03193050, 'approved', 3, 3, '2026-05-11 02:40:55', '2026-05-11 02:40:55'),
(30, 'LARA', 'ROBERTO', 'MABANSAG', '', 'Male', 'Concepcion', 'CITY OF TALISAY', 'Negros Occidental', '09156296668', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.69218440, 123.05851050, 'approved', 3, 3, '2026-05-11 02:42:58', '2026-05-11 02:42:58'),
(31, 'LOZADA', 'REYNALDO', 'SALOPESA', '', 'Male', 'Talacdan', 'CAUAYAN', 'Negros Occidental', '09107815795', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 9.86640210, 122.68526760, 'approved', 3, 3, '2026-05-11 02:45:42', '2026-05-11 02:45:42'),
(32, 'MAG-ARO', 'JIMMY', 'GILLANG', '', 'Male', 'Cabacungan', 'LA CASTELLANA', 'Negros Occidental', '09071825872', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.32440310, 123.13285390, 'approved', 3, 3, '2026-05-11 02:48:07', '2026-05-11 02:48:07'),
(33, 'MAGDATO', 'RENE', 'MANDOLADO', '', 'Male', 'Taloc', 'BAGO CITY', 'Negros Occidental', '09534153056', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.58682110, 122.89632340, 'approved', 3, 3, '2026-05-11 02:50:26', '2026-05-11 02:50:26'),
(34, 'MAGDATO', 'REYNALDO', 'MANDOLADO', '', 'Male', 'Mabini', 'VALLADOLID', 'Negros Occidental', '0938384009', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.49329100, 122.83391770, 'approved', 3, 3, '2026-05-11 02:53:50', '2026-05-11 02:53:50'),
(35, 'NAVIDA', 'MARIO', 'ESMAYAN', '', 'Male', 'Tagda', 'HINIGARAN', 'Negros Occidental', '09703885130', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.26558830, 122.84428370, 'approved', 3, 3, '2026-05-11 02:59:28', '2026-05-11 02:59:28'),
(36, 'JUDILLA', 'LORENZ', 'JORDAN', '', 'Male', 'Barangay III (Pob.)', 'CITY OF HIMAMAYLAN', 'Negros Occidental', '09777163028', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.09928410, 122.87053900, 'approved', 3, 3, '2026-05-11 03:02:14', '2026-05-11 03:02:14'),
(37, 'LACHICA', 'BERNIE', 'LACANARIA', '', 'Male', 'Zone 10 (Pob.)', 'CITY OF TALISAY', 'Negros Occidental', '09300689955', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.73726490, 122.96733250, 'approved', 3, 3, '2026-05-11 03:04:32', '2026-05-11 03:04:32'),
(38, 'OLIVERIO', 'JESSIE', 'ANTONIO', '', 'Male', 'Cabacungan', 'LA CASTELLANA', 'Negros Occidental', '09859741740', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.32440310, 123.13285390, 'approved', 3, 3, '2026-05-11 03:07:21', '2026-05-11 03:07:21'),
(39, 'PACUNLA', 'PRUDENCIO', 'DINOLAN', '', 'Male', 'Tiling', 'CAUAYAN', 'Negros Occidental', '09194101347', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 9.97466990, 122.65359860, 'approved', 3, 3, '2026-05-11 03:09:23', '2026-05-11 03:09:23'),
(40, 'PADILLA', 'EDWIN', 'SALES', '', 'Male', 'Binicuil', 'CITY OF KABANKALAN', 'Negros Occidental', '09776624255', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.02160700, 122.82199380, 'approved', 3, 3, '2026-05-11 03:12:30', '2026-05-11 03:12:30'),
(41, 'PALMA', 'RENATO', 'DELA CRUZ', '', 'Male', 'Luna', 'CADIZ CITY', 'Negros Occidental', '09383741370', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.95816020, 123.24000810, 'approved', 3, 3, '2026-05-11 03:15:14', '2026-05-11 03:15:14'),
(42, 'PIDOY', 'LEONILO', 'LANGRIO', '', 'Male', 'Barangay II (Pob.)', 'CITY OF VICTORIAS', 'Negros Occidental', '09169545228', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.90134120, 123.07149430, 'approved', 3, 3, '2026-05-11 03:17:45', '2026-05-11 03:17:45'),
(43, 'QUINDO', 'DELBERT', 'PLACENCIA', '', 'Male', 'Antipolo', 'PONTEVEDRA', 'Negros Occidental', '09675231296', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.35695060, 122.96388600, 'approved', 3, 3, '2026-05-11 03:20:40', '2026-05-11 03:20:40'),
(44, 'SADIASA', 'CRISPIN', 'HERVAS', '', 'Male', 'Sag-Ang', 'LA CASTELLANA', 'Negros Occidental', '0954377420', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.37020240, 123.08286820, 'approved', 3, 3, '2026-05-11 03:23:01', '2026-05-11 03:23:01'),
(45, 'SEPE', 'FELISA', 'MONARES', '', 'Female', 'Alijis', 'VALLADOLID', 'Negros Occidental', '09953495224', 'RICE RETAILING', 'KIA', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.44844840, 122.84868260, 'approved', 3, 3, '2026-05-11 03:26:34', '2026-05-11 03:26:34'),
(46, 'SERMONIA', 'ARIEL', 'ESPOSO', '', 'Male', 'Aguisan', 'CITY OF HIMAMAYLAN', 'Negros Occidental', '09097230806', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.16019050, 122.86280560, 'approved', 3, 3, '2026-05-11 03:30:36', '2026-05-11 03:30:36'),
(47, 'SIMON', 'WILLY', 'TRAVEÑA', '', 'Male', 'Barangay 9 (Pob.)', 'CITY OF KABANKALAN', 'Negros Occidental', '09287425709', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 9.98891530, 122.81348020, 'approved', 3, 3, '2026-05-11 03:33:24', '2026-05-11 03:33:24'),
(48, 'SINGABOR', 'CHRISTIAN', 'ALARCON', '', 'Male', 'Cabadiangan', 'CITY OF HIMAMAYLAN', 'Negros Occidental', '09483748949', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.14042140, 122.94698950, 'approved', 3, 3, '2026-05-11 03:35:39', '2026-05-11 03:35:39'),
(49, 'SODICTA', 'JOSE', 'SEBUGERO', '', 'Male', 'San Isidro', 'CALATRAVA', 'Negros Occidental', '09662367833', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.55454820, 123.46346550, 'approved', 3, 3, '2026-05-11 03:38:27', '2026-05-11 03:38:27'),
(50, 'SUMAGAYSAY', 'NOEL', 'HILADO', '', 'Male', 'Inapoy', 'CITY OF KABANKALAN', 'Negros Occidental', '09638608968', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 9.80435540, 122.87500470, 'approved', 3, 3, '2026-05-11 03:41:24', '2026-05-11 03:41:24'),
(51, 'TABUADA', 'MICHEAL', 'MALAYAS', '', 'Male', 'Barangay II (Pob.)', 'HINIGARAN', 'Negros Occidental', '09852102678', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.27138320, 122.85199090, 'approved', 3, 3, '2026-05-11 03:43:40', '2026-05-11 03:43:40'),
(52, 'TOLENTINO', 'ADONIE', 'BERNABE', '', 'Male', 'San Isidro', 'ENRIQUE B. MAGALONA (SARAVIA)', 'Negros Occidental', '09082270707', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.79609900, 123.13888550, 'approved', 3, 3, '2026-05-11 03:46:12', '2026-05-11 03:46:12'),
(53, 'YBIERNAS', 'JERRY LIPSIE', 'ZARAGA', '', 'Male', 'Banquerohan', 'CADIZ CITY', 'Negros Occidental', '09120757726', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-25', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.94959090, 123.33760840, 'approved', 3, 3, '2026-05-11 03:48:34', '2026-05-11 03:48:34'),
(54, 'ZOLYAVAR', 'ALEXANDREI', 'MORALES', '', 'Male', 'Barangay 41 (Pob.)', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09628118579', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.66350000, 122.96320000, 'approved', 3, 3, '2026-05-11 07:56:02', '2026-05-11 08:09:11'),
(55, 'YULO', 'JOSE LOVETT', 'TAÑO', '', 'Male', 'Sum-ag', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09103988261', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.60060000, 122.91910000, 'approved', 3, 3, '2026-05-11 07:58:09', '2026-05-11 08:09:40'),
(56, 'VILLANUEVA', 'XERXES', 'TOMARONG', '', 'Male', 'Mansilingan', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09208543020', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', NULL, NULL, NULL, '2026-03-26', 10.63100000, 122.97610000, 'approved', 3, 3, '2026-05-11 07:59:59', '2026-05-11 08:10:19'),
(57, 'VERDAD', 'RICKY', 'NAVARRO', '', 'Male', 'Barangay 16 (Pob.)', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09196927818', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.66600000, 122.93920000, 'approved', 3, 3, '2026-05-11 08:02:53', '2026-05-11 08:11:04'),
(58, 'TAPANG', 'PROCOPIO', 'BEATINGO', 'JR', 'Male', 'Felisa', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09921601282', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.59050000, 122.97560000, 'approved', 3, 3, '2026-05-11 08:06:22', '2026-05-11 08:11:32'),
(59, 'RULL', 'CRISTINA', 'COSINO', '', 'Female', 'Punta Taytay', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09455290240', 'RICE RETAILING', 'UNEMPLOYED WOMEN', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.60020000, 122.90540000, 'approved', 3, 3, '2026-05-11 08:08:38', '2026-05-11 09:21:04'),
(60, 'REPUELA', 'RENANTE', 'DAVID', '', 'Male', 'Sum-ag', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09624988993', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.60060000, 122.91910000, 'approved', 3, 3, '2026-05-11 08:16:39', '2026-05-11 09:19:52'),
(61, 'PONTERO', 'EDPINLAN', 'HECHANOVA', '', 'Male', 'Barangay 16 (Pob.)', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09123152069', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.66600000, 122.93920000, 'approved', 3, 3, '2026-05-11 08:20:37', '2026-05-11 08:20:37'),
(62, 'PEÑOSO', 'ARTURO', 'BENITEZ', '', 'Male', 'Barangay 2 (Pob.)', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09641680864', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.68340000, 122.95060000, 'approved', 3, 3, '2026-05-11 08:24:51', '2026-05-11 08:58:39'),
(63, 'PAMA', 'LEMUEL', 'PIEZ', '', 'Male', 'Vista Alegre', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09197211335', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.64220000, 123.00810000, 'approved', 3, 3, '2026-05-11 08:27:09', '2026-05-11 08:27:09'),
(64, 'NOBLEZA', 'NERISSA', 'DOROMAL', '', 'Female', 'Punta Taytay', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09167993343', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.60020000, 122.90540000, 'approved', 3, 3, '2026-05-11 08:29:08', '2026-05-11 09:16:22'),
(65, 'LAM', 'JAIME', 'OÑATE', '', 'Male', 'Taculing', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09216291177', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.64960000, 122.94750000, 'approved', 3, 3, '2026-05-11 08:30:57', '2026-05-11 08:30:57'),
(66, 'JAVIER', 'JESSIE', 'BARILEA', '', 'Male', 'Bata', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09504194633', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.67220000, 122.94430000, 'approved', 3, 3, '2026-05-11 08:33:07', '2026-05-11 08:33:07'),
(67, 'LUTO', 'EMMA', 'LLASOS', '', 'Female', 'Mansilingan', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09506554423', 'RICE RETAILING', 'VENDOR', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.62480000, 122.97190000, 'approved', 3, 3, '2026-05-11 08:35:00', '2026-05-11 08:35:00'),
(68, 'LLAVE', 'DONY NIÑO', 'NATALIO', '', 'Male', 'Banago', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09100895751', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.70560000, 122.94940000, 'approved', 3, 3, '2026-05-11 08:37:31', '2026-05-11 08:37:31'),
(69, 'FLORES', 'JOEMAR', 'TORIANO', '', 'Male', 'Granada', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09264727616', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.66600000, 123.03450000, 'approved', 3, 3, '2026-05-11 08:52:19', '2026-05-11 08:52:19'),
(70, 'CUADRA', 'RITCHEL', 'SAUSE', '', 'Male', 'Villamonte', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09267716944', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.66850000, 122.96470000, 'approved', 3, 3, '2026-05-11 08:54:29', '2026-05-11 08:54:29'),
(71, 'DERLA', 'REY', 'DELA PEÑA', '', 'Male', 'Barangay 8 (Pob.)', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09936244157', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.68250000, 122.94460000, 'approved', 3, 3, '2026-05-11 08:58:03', '2026-05-11 08:58:03'),
(72, 'ALCALDE', 'RICARDO', 'AMBONG', '', 'Male', 'Villamonte', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09696127389', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.66850000, 122.96470000, 'approved', 3, 3, '2026-05-11 10:14:04', '2026-05-11 10:14:04'),
(73, 'ANTIVOLA', 'ANDY', 'HALILI', '', 'Male', 'Sum-ag', 'BACOLOD CITY (Capital)', 'Negros Occidental', '09776626042', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.60060000, 122.91910000, 'approved', 3, 3, '2026-05-11 10:15:48', '2026-05-11 10:18:13'),
(74, 'AGDANA', 'ARNOLD', 'RAFOLS', '', 'Male', 'Banago', 'BACOLOD CITY (Capital)', 'Negros Occidental', '0951748452', 'RICE RETAILING', 'FORMER PDL', 50000.00, '', '2026-03-18', '2026-03-26', '', '2026-03-26', NULL, NULL, NULL, 10.70360000, 122.95010000, 'approved', 3, 3, '2026-05-11 10:17:43', '2026-05-11 10:17:43');

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
(13, 'LGU-LA CASTELANA (NOMINEE/S FOR KABUHAYAN AWARD INDIVIDUAL PROJECT CATEGORY)', 'CONDUCT LGU-LA CASTELLANA AS ONE OF THE NOMINEE/S FOR KABUHAYAN AWARD INDIVIDUAL PROJECT CATEGORY)', 'LA CASTELLANA', 6, '2026-04-14', '2026-04-14', 'completed', 3, '2026-04-13 05:56:48', '2026-05-11 01:30:53'),
(14, 'CARIDAD 1 (NOMINEE/S FOR KABUHAYAN AWARD GROUP PROJECT CATEGORY)', 'CONDUCT WITH CARIDAD 1 AS ONE OF THE NOMINEE/S FOR KABUHAYAN AWARD GROUP PROJECT CATEGORY', 'BRGY. LUNA, CADIZ CITY', 6, '2026-04-15', '2026-04-15', 'completed', 3, '2026-04-13 06:00:30', '2026-05-11 01:31:12'),
(15, 'Stakeholders\'Forum on Livelihood Recovery for Typhoon Tino by Philippine Red Cross', 'Discussion in relation to livelihood projects', 'Maze Garden, Bacolod City', 6, '2026-05-05', '2026-05-05', 'completed', 6, '2026-04-15 08:46:25', '2026-05-10 10:38:16'),
(17, 'Mancom Meeting', '', 'Siquijor', 6, '2026-05-14', '2026-05-15', 'pending', 6, '2026-04-17 05:50:26', '2026-04-17 05:50:26'),
(18, 'Courtesy Call to LCE of LGU La Castellana, Moises Padilla, Ilog and Cong. Dino Yulo', 'Discussion in relation to livelihood', 'Ilog', 6, '2026-05-06', '2026-05-06', 'completed', 6, '2026-05-10 10:37:07', '2026-05-10 10:37:18'),
(20, 'LIQUIDATION COACHING', 'Meeting with PESO Manager and other members of LGU-Himamaylan City regarding the liquidation report under the Kabuhayan Program', 'LGU-HIMAMAYLAN CITY', 2, '2026-05-12', '2026-05-12', 'ongoing', 3, '2026-05-11 05:33:28', '2026-05-12 05:38:25'),
(21, 'DILP ORIENTATION', 'CONDUCT OF ORIENTATION ON DILP, PROJECT ID  AND PROPOSAL MAKING FOR THE THREE ADDITIONAL ASSOCIATIONS OF LGU-BAGO CITY AND CHECKED THE KABUHAYAN DOCUMENTARY REQUIREMENTS OF 15 ASSOCIATIONS', 'LGU-BAGO CITY', 3, '2026-05-13', '2026-05-13', 'pending', 3, '2026-05-11 05:39:07', '2026-05-11 05:47:56'),
(22, 'DILP ORIENTATION', 'CONDUCT DILP AND PREPARE THE DOCUMENTARY REQUIREMENTS FOR THE IDENTIFIED BENEFICIARIES OF THE LABOR INTERVENTION FINANCIAL ECONOMIC ASSISTANCE (LIFE) PROGRAM UNDER THE  KABUHAYAN ASSISTANCE', 'ESCALANTE CITY', 2, '2026-05-14', '2026-05-14', 'pending', 3, '2026-05-11 05:47:31', '2026-05-11 05:47:31'),
(23, 'TESDA appointment 10:00am', 'Possible Collaboration with TESDA', 'Talisay City', 6, '2026-05-20', '2026-05-20', 'pending', 6, '2026-05-11 06:28:44', '2026-05-11 06:29:05');

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
  `beneficiary_full_name` varchar(255) DEFAULT NULL,
  `male_beneficiaries` int(11) DEFAULT 0,
  `female_beneficiaries` int(11) DEFAULT 0,
  `type_of_beneficiaries` varchar(255) DEFAULT NULL,
  `type_of_workers` varchar(255) DEFAULT NULL,
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

INSERT INTO `proponents` (`id`, `proponent_type`, `date_received`, `noted_findings`, `control_number`, `number_of_copies`, `date_copies_received`, `district`, `province`, `proponent_name`, `project_title`, `amount`, `number_of_associations`, `total_beneficiaries`, `beneficiary_full_name`, `male_beneficiaries`, `female_beneficiaries`, `type_of_beneficiaries`, `type_of_workers`, `category`, `recipient_barangays`, `letter_of_intent_date`, `date_forwarded_to_ro6`, `rpmt_findings`, `date_complied_by_proponent`, `date_complied_by_proponent_nofo`, `date_forwarded_to_nofo`, `date_approved`, `date_check_release`, `check_number`, `check_date_issued`, `or_number`, `or_date_issued`, `date_turnover`, `date_implemented`, `date_liquidated`, `liquidation_deadline`, `date_monitoring`, `source_of_funds`, `latitude`, `longitude`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'Non-LGU-associated', '2026-03-18', '', NULL, 0, NULL, '6TH', 'Negros Occidental', 'TAMPALON RAINFED UPLAND FARMERS ASSOCIATION (TRUFA)', 'CONSOLIDATED PROJECT PROPOSAL', 1500000.00, 1, 55, 'ROCKY BASILIO ANGELO, NELIA ANCERO BALDOMERO, FELIZARDO ROJO BALIGYAN, MERLINA DEGUIT BALIGYAN, ARGIE MOSCOSO CABRILLOS, RICHARD MOSCOSO CABRILLOS, NELFA BARDELOSA CALAMBA, GERPA GUZON CANTUTAY, EDWARD ARAGON CEBALLOS, JOSE ASUELA CORTEJO JR, SONNY GARNAN', 29, 26, 'Farmers', '', 'Enhancement', 'TAMPALON RAINFED UPLAND FARMERS ASSOCIATION (TRUFA)', '2026-03-18', '2026-03-26', '', NULL, NULL, NULL, '2026-03-26', '2026-05-01', NULL, '2026-03-31', '366', NULL, NULL, NULL, NULL, NULL, NULL, 'GAA', 9.87278650, 122.79865400, 'approved', 3, 3, '2026-05-07 09:31:24', '2026-05-11 07:47:21'),
(2, 'Non-LGU-associated', '2026-03-12', '', NULL, 0, NULL, '4TH', 'Negros Occidental', 'AMIA VILLAGE ORGANIC FARMERS ASSOCIATIONS (AVOFA)', 'AGRI-VENTURE ON ORGANIC FERTILIZER AND RICE RETAILING ENTERPRISE', 2000000.00, 1, 52, 'Nena Acedera  Agravante, Charlie Torres Aguirre, Dinah Moguad Aguirre, Restituto Gevero Alojamiento, Leandra Cordova Barres, Estrella Mahusay Bayobay, Analisa Tanaleon Bernal, Estelita Laus Dimausay, Roger Jemino Ecullada, Susana Elbanbuena Ecullada, Deli', 16, 36, 'Farmers, Senior Citizen', '', 'Formation', '', '2026-03-12', '2026-03-26', '', NULL, NULL, NULL, '2026-03-26', '2026-05-01', NULL, '2026-03-31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'GAA', 10.32208300, 122.90027150, 'approved', 3, 3, '2026-05-11 02:25:21', '2026-05-11 08:13:39');

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
(4, 1, 'TAMPALON RAINFED UPLAND FARMERS ASSOCIATION (TRUFA)', 'BRGY. TAMPALON, KABANKALAN CITY', 0, '2026-05-11 07:47:21'),
(5, 2, 'AMIA VILLAGE ORGANIC FARMERS ASSOCIATIONS (AVOFA)', 'BRGY. SAN ISIDRO, PONTEVEDRA, NEGROS OCCIDENTAL', 0, '2026-05-11 08:13:39');

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
(1, 'maintenance_mode', '1', '2026-03-11 05:50:02', '2026-05-11 13:25:35');

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
(3, 'jona', 'jona@dilp.com', '$2y$10$uGvNPAI0qvwJqAaBx0ViTOFPZ/JHV03JCQhgh4Srvrb5Wk73OJGe6', 'encoder', 'Jona Cepriano', 1, '2026-02-04 07:46:59', '2026-05-07 09:11:02'),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=576;

--
-- AUTO_INCREMENT for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `fieldwork_schedule`
--
ALTER TABLE `fieldwork_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `proponents`
--
ALTER TABLE `proponents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `proponent_associations`
--
ALTER TABLE `proponent_associations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `proponent_returns`
--
ALTER TABLE `proponent_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
