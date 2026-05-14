-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 12, 2026 at 10:25 AM
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
(406, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-07 07:16:07'),
(407, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-07 08:33:56'),
(408, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-07 08:33:58'),
(409, 1, 'reset', 'system', 0, 'Erased proponent and beneficiary records. Proponents: 2, Beneficiaries: 91.', '::1', '2026-05-07 08:50:54'),
(410, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-07 09:48:23'),
(411, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-11 04:07:43'),
(412, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-11 05:01:19'),
(413, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-11 05:21:34'),
(414, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-11 06:16:09'),
(415, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-11 07:02:17'),
(416, 1, 'create', 'users', 7, 'Created new user: nole.dileepsys (province: Negros Oriental)', '::1', '2026-05-11 08:22:08'),
(417, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-11 08:22:12'),
(418, 7, 'login', 'users', 7, 'User logged in', '::1', '2026-05-11 08:22:14'),
(419, 7, 'logout', 'users', 7, 'User logged out', '::1', '2026-05-11 08:31:03'),
(420, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-11 08:31:08'),
(421, 1, 'update', 'users', 6, 'Updated user ID: 6', '::1', '2026-05-11 08:31:32'),
(422, 1, 'update', 'users', 5, 'Updated user ID: 5', '::1', '2026-05-11 08:31:52'),
(423, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-11 08:33:23'),
(424, 7, 'login', 'users', 7, 'User logged in', '::1', '2026-05-11 08:33:27'),
(425, 7, 'create', 'beneficiaries', 1, 'Created new beneficiary', '::1', '2026-05-11 08:35:09'),
(426, 7, 'reset', 'system', 0, 'Erased proponent and beneficiary records. Proponents: 0, Beneficiaries: 1.', '::1', '2026-05-11 08:35:22'),
(427, 7, 'create', 'proponents', 1, 'Created new proponent', '::1', '2026-05-11 08:42:03'),
(428, 7, 'reset', 'system', 0, 'Erased proponent and beneficiary records. Proponents: 1, Beneficiaries: 0.', '::1', '2026-05-11 08:42:19'),
(429, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-11 13:02:48'),
(430, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-11 14:25:29'),
(431, 7, 'login', 'users', 7, 'User logged in', '::1', '2026-05-11 14:25:34'),
(432, 7, 'logout', 'users', 7, 'User logged out', '::1', '2026-05-11 14:27:12'),
(433, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-11 14:27:16'),
(434, 1, 'create', 'users', 8, 'Created new user: siquijor.admin (province: Siquijor)', '::1', '2026-05-11 14:30:25'),
(435, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-11 14:30:28'),
(436, 8, 'login', 'users', 8, 'User logged in', '::1', '2026-05-11 14:30:30'),
(437, 8, 'logout', 'users', 8, 'User logged out', '::1', '2026-05-11 14:31:50'),
(438, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-11 14:31:58'),
(439, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-11 14:37:22'),
(440, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-11 14:37:38'),
(441, 6, 'update', 'fieldwork_schedule', 15, 'Updated fieldwork status to: pending', '::1', '2026-05-11 14:38:06'),
(442, 6, 'update', 'fieldwork_schedule', 13, 'Updated fieldwork status to: pending', '::1', '2026-05-11 14:38:13'),
(443, 6, 'update', 'fieldwork_schedule', 9, 'Updated fieldwork status to: missed', '::1', '2026-05-11 14:38:30'),
(444, 6, 'update', 'fieldwork_schedule', 9, 'Updated fieldwork status to: pending', '::1', '2026-05-11 14:38:35'),
(445, 6, 'update', 'fieldwork_schedule', 9, 'Updated fieldwork status to: pending', '::1', '2026-05-11 14:38:42'),
(446, 6, 'update', 'fieldwork_schedule', 17, 'Updated fieldwork status to: ongoing', '::1', '2026-05-11 14:39:08'),
(447, 6, 'update', 'fieldwork_schedule', 17, 'Updated fieldwork status to: completed', '::1', '2026-05-11 14:39:16'),
(448, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 01:39:07'),
(449, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 02:22:34'),
(450, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 02:25:14'),
(451, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 02:27:28'),
(452, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-12 02:27:35'),
(453, 6, 'update', 'fieldwork_schedule', 9, 'Updated fieldwork status to: pending', '::1', '2026-05-12 02:27:53'),
(454, 6, 'update', 'fieldwork_schedule', 9, 'Updated fieldwork status to: completed', '::1', '2026-05-12 02:28:00'),
(455, 6, 'update', 'fieldwork_schedule', 13, 'Updated fieldwork status to: ongoing', '::1', '2026-05-12 02:28:09'),
(456, 6, 'update', 'fieldwork_schedule', 13, 'Updated fieldwork schedule: LGU-LA CASTELANA (NOMINEE/S FOR KABUHAYAN AWARD INDIVIDUAL PROJECT CATEGORY)', '::1', '2026-05-12 02:28:27'),
(457, 6, 'delete', 'fieldwork_schedule', 15, 'Deleted fieldwork schedule: Conduct of Orientation on the DOLE Integrated Livelihood Program (DILP) Cum Accreditation of CO-Partners (ACP)', '::1', '2026-05-12 02:28:46'),
(458, 6, 'logout', 'users', 6, 'User logged out', '::1', '2026-05-12 02:29:07'),
(459, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 02:29:12'),
(460, 1, 'create', 'org_chart', 6, 'Added org chart person: LDS (Jona Cepriano)', '::1', '2026-05-12 02:29:27'),
(461, 1, 'update', 'org_chart', 4, 'Updated org chart entry ID 4: LDS', '::1', '2026-05-12 02:29:34'),
(462, 1, 'create', 'org_chart', 7, 'Added org chart person: SAWP Field Facilitator / IT Specialist (Elziakim Pegar)', '::1', '2026-05-12 02:29:59'),
(463, 1, 'create', 'org_chart', 8, 'Added org chart person: Yzabel Gane (TUPAD)', '::1', '2026-05-12 02:30:17'),
(464, 1, 'create', 'org_chart', 9, 'Added org chart person: Ieliz Jover (TUPAD)', '::1', '2026-05-12 02:30:25'),
(465, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 02:31:05'),
(466, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-12 02:31:09'),
(467, 6, 'logout', 'users', 6, 'User logged out', '::1', '2026-05-12 02:54:17'),
(468, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 02:54:21'),
(469, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 02:54:39'),
(470, 7, 'login', 'users', 7, 'User logged in', '::1', '2026-05-12 02:54:43'),
(471, 7, 'logout', 'users', 7, 'User logged out', '::1', '2026-05-12 02:55:02'),
(472, 8, 'login', 'users', 8, 'User logged in', '::1', '2026-05-12 02:55:14'),
(473, 8, 'logout', 'users', 8, 'User logged out', '::1', '2026-05-12 02:57:06'),
(474, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 02:57:10'),
(475, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 03:01:44'),
(476, 3, 'login', 'users', 3, 'User logged in', '::1', '2026-05-12 03:01:48'),
(477, 3, 'logout', 'users', 3, 'User logged out', '::1', '2026-05-12 03:04:36'),
(478, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 03:04:41'),
(479, 1, 'create', 'users', 9, 'Created new user: encoder.norfo (province: Negros Oriental)', '::1', '2026-05-12 03:05:21'),
(480, 1, 'create', 'users', 10, 'Created new user: viewer.norfo (province: Negros Oriental)', '::1', '2026-05-12 03:07:29'),
(481, 1, 'create', 'users', 11, 'Created new user: encoder.siquijor (province: Siquijor)', '::1', '2026-05-12 03:08:13'),
(482, 1, 'create', 'users', 12, 'Created new user: viewer.siquijor (province: Siquijor)', '::1', '2026-05-12 03:08:54'),
(483, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 03:10:00'),
(484, 9, 'login', 'users', 9, 'User logged in', '::1', '2026-05-12 03:10:07'),
(485, 9, 'logout', 'users', 9, 'User logged out', '::1', '2026-05-12 03:11:22'),
(486, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 03:11:26'),
(487, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 03:19:42'),
(488, 9, 'login', 'users', 9, 'User logged in', '::1', '2026-05-12 03:19:49'),
(489, 9, 'logout', 'users', 9, 'User logged out', '::1', '2026-05-12 03:32:43'),
(490, 10, 'login', 'users', 10, 'User logged in', '::1', '2026-05-12 03:32:56'),
(491, 10, 'logout', 'users', 10, 'User logged out', '::1', '2026-05-12 03:38:32'),
(492, 11, 'login', 'users', 11, 'User logged in', '::1', '2026-05-12 03:38:39'),
(493, 11, 'logout', 'users', 11, 'User logged out', '::1', '2026-05-12 03:43:36'),
(494, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 03:43:40'),
(495, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 03:44:01'),
(496, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-12 03:44:04'),
(497, 6, 'update', 'beneficiaries', 3, 'Updated beneficiary', '::1', '2026-05-12 03:59:41'),
(498, 6, 'update', 'proponents', 5, 'Updated proponent', '::1', '2026-05-12 04:02:06'),
(499, 6, 'logout', 'users', 6, 'User logged out', '::1', '2026-05-12 05:00:50'),
(500, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 05:00:54'),
(501, 1, 'update', 'users', 6, 'Updated user ID: 6', '::1', '2026-05-12 05:01:10'),
(502, 1, 'update', 'users', 5, 'Updated user ID: 5', '::1', '2026-05-12 05:01:22'),
(503, 1, 'update', 'org_chart', 2, 'Updated org chart entry ID 2: OIC, DOLE-NOCFO', '::1', '2026-05-12 05:01:49'),
(504, 1, 'update', 'org_chart', 8, 'Updated org chart entry ID 8: TUPAD', '::1', '2026-05-12 05:02:20'),
(505, 1, 'update', 'org_chart', 9, 'Updated org chart entry ID 9: TUPAD', '::1', '2026-05-12 05:02:28'),
(506, 1, 'update', 'org_chart', 3, 'Updated org chart entry ID 3: Sr. LEO / DILEEP Focal', '::1', '2026-05-12 05:02:53'),
(507, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 06:02:30'),
(508, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-12 06:02:35'),
(509, 6, 'logout', 'users', 6, 'User logged out', '::1', '2026-05-12 06:02:42'),
(510, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-12 06:03:19'),
(511, 6, 'logout', 'users', 6, 'User logged out', '::1', '2026-05-12 06:03:21'),
(512, 9, 'login', 'users', 9, 'User logged in', '::1', '2026-05-12 06:03:47'),
(513, 9, 'logout', 'users', 9, 'User logged out', '::1', '2026-05-12 06:04:14'),
(514, 10, 'login', 'users', 10, 'User logged in', '::1', '2026-05-12 06:05:14'),
(515, 10, 'logout', 'users', 10, 'User logged out', '::1', '2026-05-12 06:05:17'),
(516, 11, 'login', 'users', 11, 'User logged in', '::1', '2026-05-12 06:05:24'),
(517, 11, 'logout', 'users', 11, 'User logged out', '::1', '2026-05-12 06:05:37'),
(518, 12, 'login', 'users', 12, 'User logged in', '::1', '2026-05-12 06:05:39');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
(519, 12, 'logout', 'users', 12, 'User logged out', '::1', '2026-05-12 06:05:43'),
(520, 8, 'login', 'users', 8, 'User logged in', '::1', '2026-05-12 06:05:49'),
(521, 8, 'logout', 'users', 8, 'User logged out', '::1', '2026-05-12 06:05:53'),
(522, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 06:06:02'),
(523, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 06:06:22'),
(524, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-12 06:06:26'),
(525, 6, 'logout', 'users', 6, 'User logged out', '::1', '2026-05-12 06:07:18'),
(526, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-12 06:07:27'),
(527, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-12 06:07:43'),
(528, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 06:07:47'),
(529, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 06:08:29'),
(530, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-12 06:08:32'),
(531, 6, 'logout', 'users', 6, 'User logged out', '::1', '2026-05-12 06:11:14'),
(532, 9, 'login', 'users', 9, 'User logged in', '::1', '2026-05-12 06:11:18'),
(533, 9, 'logout', 'users', 9, 'User logged out', '::1', '2026-05-12 06:11:27'),
(534, 11, 'login', 'users', 11, 'User logged in', '::1', '2026-05-12 06:11:31'),
(535, 11, 'logout', 'users', 11, 'User logged out', '::1', '2026-05-12 06:11:58'),
(536, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-12 06:12:02'),
(537, 6, 'logout', 'users', 6, 'User logged out', '::1', '2026-05-12 06:14:05'),
(538, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 06:14:11'),
(539, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 06:15:40'),
(540, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-12 06:15:44'),
(541, 6, 'logout', 'users', 6, 'User logged out', '::1', '2026-05-12 06:18:45'),
(542, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 06:18:51'),
(543, 1, 'delete', 'org_chart', 10, 'Deleted org chart entry ID 10', '::1', '2026-05-12 06:23:35'),
(544, 1, 'delete', 'org_chart', 11, 'Deleted org chart entry ID 11', '::1', '2026-05-12 06:23:52'),
(545, 1, 'delete', 'org_chart', 15, 'Deleted org chart entry ID 15', '::1', '2026-05-12 06:23:55'),
(546, 1, 'delete', 'org_chart', 18, 'Deleted org chart entry ID 18', '::1', '2026-05-12 06:23:58'),
(547, 1, 'delete', 'org_chart', 12, 'Deleted org chart entry ID 12', '::1', '2026-05-12 06:24:02'),
(548, 1, 'delete', 'org_chart', 16, 'Deleted org chart entry ID 16', '::1', '2026-05-12 06:24:04'),
(549, 1, 'delete', 'org_chart', 19, 'Deleted org chart entry ID 19', '::1', '2026-05-12 06:24:07'),
(550, 1, 'delete', 'org_chart', 13, 'Deleted org chart entry ID 13', '::1', '2026-05-12 06:24:10'),
(551, 1, 'delete', 'org_chart', 14, 'Deleted org chart entry ID 14', '::1', '2026-05-12 06:24:14'),
(552, 1, 'delete', 'org_chart', 17, 'Deleted org chart entry ID 17', '::1', '2026-05-12 06:24:16'),
(553, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 06:25:04'),
(554, 7, 'login', 'users', 7, 'User logged in', '::1', '2026-05-12 06:25:15'),
(555, 7, 'logout', 'users', 7, 'User logged out', '::1', '2026-05-12 06:25:22'),
(556, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 06:25:27'),
(557, 1, 'create', 'org_chart', 20, 'Added org chart person: vacant (vacant)', '::1', '2026-05-12 06:25:35'),
(558, 1, 'create', 'org_chart', 21, 'Added org chart person: 2vacant (vacant2)', '::1', '2026-05-12 06:25:40'),
(559, 1, 'create', 'org_chart', 22, 'Added org chart person: vacant3 (vacant3)', '::1', '2026-05-12 06:25:44'),
(560, 1, 'delete', 'org_chart', 20, 'Deleted org chart entry ID 20', '::1', '2026-05-12 06:30:32'),
(561, 1, 'delete', 'org_chart', 21, 'Deleted org chart entry ID 21', '::1', '2026-05-12 06:30:35'),
(562, 1, 'delete', 'org_chart', 22, 'Deleted org chart entry ID 22', '::1', '2026-05-12 06:30:40'),
(563, 1, 'create', 'beneficiaries', 16, 'Created new beneficiary', '::1', '2026-05-12 06:31:25'),
(564, 1, 'create', 'proponents', 16, 'Created new proponent', '::1', '2026-05-12 06:32:36'),
(565, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 06:32:40'),
(566, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-12 06:32:44'),
(567, 6, 'create', 'beneficiaries', 17, 'Created new beneficiary', '::1', '2026-05-12 06:33:24'),
(568, 6, 'create', 'proponents', 17, 'Created new proponent', '::1', '2026-05-12 06:34:41'),
(569, 6, 'update', 'proponents', 17, 'Updated proponent', '::1', '2026-05-12 06:35:57'),
(570, 6, 'logout', 'users', 6, 'User logged out', '::1', '2026-05-12 07:02:57'),
(571, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-12 07:03:01'),
(572, 1, 'delete', 'org_chart', 23, 'Deleted org chart entry ID 23', '::1', '2026-05-12 07:03:21'),
(573, 1, 'delete', 'org_chart', 26, 'Deleted org chart entry ID 26', '::1', '2026-05-12 07:03:24'),
(574, 1, 'update', 'org_chart', 35, 'Updated org chart entry ID 35: Regional Director', '::1', '2026-05-12 07:29:12'),
(575, 1, 'update', 'org_chart', 24, 'Updated org chart entry ID 24: Chief, NORFO and SFO', '::1', '2026-05-12 07:29:58'),
(576, 1, 'update', 'org_chart', 25, 'Updated org chart entry ID 25: Sr. LEO', '::1', '2026-05-12 07:30:14'),
(577, 1, 'update', 'org_chart', 29, 'Updated org chart entry ID 29: LDS / Designated-Encoder', '::1', '2026-05-12 07:30:53'),
(578, 1, 'update', 'org_chart', 27, 'Updated org chart entry ID 27: DILEEP Focal', '::1', '2026-05-12 07:31:09'),
(579, 1, 'update', 'org_chart', 27, 'Updated org chart entry ID 27: Chief, NORFO and SFO', '::1', '2026-05-12 07:31:21'),
(580, 1, 'update', 'org_chart', 28, 'Updated org chart entry ID 28: Senior LEO', '::1', '2026-05-12 07:31:39'),
(581, 1, 'reset', 'system', 0, 'Erased proponent and beneficiary records. Proponents: 17, Beneficiaries: 17.', '::1', '2026-05-12 07:42:43'),
(582, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-12 07:44:46'),
(583, 6, 'login', 'users', 6, 'User logged in', '::1', '2026-05-12 07:44:49'),
(584, 6, 'logout', 'users', 6, 'User logged out', '::1', '2026-05-12 07:45:24'),
(585, 7, 'login', 'users', 7, 'User logged in', '::1', '2026-05-12 07:45:31');

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
  `type_of_beneficiaries` varchar(500) DEFAULT NULL COMMENT 'Comma-separated list of beneficiary types (Farmers, Fisherfolk, PDL, etc.)',
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
  `source_of_funds` varchar(255) DEFAULT NULL COMMENT 'Source of funding for the beneficiary project',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fieldwork_schedule`
--

CREATE TABLE `fieldwork_schedule` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(500) DEFAULT NULL,
  `province` enum('Negros Occidental','Negros Oriental','Siquijor') DEFAULT NULL,
  `assigned_user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','ongoing','completed','missed') DEFAULT 'pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `manual_override` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = status was manually set; skip auto-update until next natural transition'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fieldwork_schedule`
--

INSERT INTO `fieldwork_schedule` (`id`, `title`, `description`, `location`, `province`, `assigned_user_id`, `start_date`, `end_date`, `status`, `created_by`, `created_at`, `updated_at`, `manual_override`) VALUES
(5, 'Releasing of Kabuhayan Check at DOLE Neg. Occ.', '1. Brgy. Talacdan, Cauayan', 'DOLE Negros Occidental Field Office, Bacolod City', 'Negros Occidental', 2, '2026-03-16', '2026-03-16', 'completed', 6, '2026-03-10 15:24:22', '2026-05-11 07:55:01', 0),
(6, 'Releasing of Check to LGU La Carlota', 'Releasing of Check to LGU La Carlota at 10:00am', 'LGU La Carlota', 'Negros Occidental', 6, '2026-03-17', '2026-03-17', 'completed', 6, '2026-03-10 15:25:48', '2026-05-11 07:55:01', 0),
(7, 'Project Turnover of LGU Escalante', '1. Witness project turnover to 11 association\r\n2. Liquidation coaching \r\n3. ACP validation of HABARC', 'Escalante City', 'Negros Occidental', 6, '2026-04-08', '2026-04-08', 'completed', 6, '2026-04-05 02:51:13', '2026-05-11 07:55:01', 0),
(8, 'ACP VALIDATION AT ENFARBCO', 'Conduct ACP Validation at ECJ Negros Farm Agrarian Reform Beneficiaries Cooperative (ENFARBCO)', 'Hacienda Fe, La Carlota City, Negros Occidental', 'Negros Occidental', 3, '2026-03-25', '2026-03-25', 'completed', 3, '2026-04-06 05:15:16', '2026-05-11 07:55:01', 0),
(9, 'PROJECT PROPOSAL MAKING \"AVOFA\"', 'CONDUCT DILP ORIENTATION, PROJECT ID, PROPOSAL MAKING AND SITE VALIDATION WITH AMIA VILLAGE ORGANIC FARMERS ASSOCIATION (AVOFA)', 'BRGY. SAN ISIDRO, PONTEVEDRA', 'Negros Occidental', 6, '2026-03-11', '2026-03-13', 'completed', 3, '2026-04-06 05:19:06', '2026-05-12 02:28:00', 1),
(10, 'PROJECT PROPOSAL MAKING \"TRUFA\"', 'CONDUCT DILP ORIENTATION, PROJECT ID, PROPOSAL MAKING AND SITE VALIDATION WITH TAMPALON RAINFED FARMERS ASSOCIATION (TRUFA)', 'BRGY. TAMPALON, KABANKALAN CITY', 'Negros Occidental', 6, '2026-03-18', '2026-03-19', 'completed', 3, '2026-04-06 05:20:28', '2026-05-11 07:55:01', 0),
(11, 'Releasing of Kabuhayan Check at DOLE Neg. Occ.', '1. Brgy. Gargato, Hinigaran, Neg. Occ.', 'BRGY. GARGATO, HINIGARAN, NEG. OCC.', 'Negros Occidental', 2, '2026-03-18', '2026-03-18', 'completed', 3, '2026-04-06 05:27:35', '2026-05-11 07:55:01', 0),
(12, 'Meet with DOST and TESDA', 'Visit the TESDA and DOST located at Talisay and Bacolod City, respectively, for possible livelihood collaboration.', '', 'Negros Occidental', 6, '2026-04-07', '2026-04-07', 'completed', 6, '2026-04-07 13:35:18', '2026-05-11 07:55:01', 0),
(13, 'LGU-LA CASTELANA (NOMINEE/S FOR KABUHAYAN AWARD INDIVIDUAL PROJECT CATEGORY)', 'CONDUCT LGU-LA CASTELLANA AS ONE OF THE NOMINEE/S FOR KABUHAYAN AWARD INDIVIDUAL PROJECT CATEGORY)', 'LA CASTELLANA', 'Negros Occidental', 6, '2026-04-14', '2026-04-22', 'missed', 3, '2026-04-13 05:56:48', '2026-05-12 02:28:27', 0),
(14, 'CARIDAD 1 (NOMINEE/S FOR KABUHAYAN AWARD GROUP PROJECT CATEGORY)', 'CONDUCT WITH CARIDAD 1 AS ONE OF THE NOMINEE/S FOR KABUHAYAN AWARD GROUP PROJECT CATEGORY', 'BRGY. LUNA, CADIZ CITY', 'Negros Occidental', 6, '2026-04-15', '2026-04-15', 'missed', 3, '2026-04-13 06:00:30', '2026-05-11 07:55:01', 0),
(17, 'Mancom Meeting', '', 'Siquijor', 'Negros Occidental', 6, '2026-05-14', '2026-05-15', 'completed', 6, '2026-04-17 05:50:26', '2026-05-11 14:39:16', 0);

-- --------------------------------------------------------

--
-- Table structure for table `org_chart`
--

CREATE TABLE `org_chart` (
  `id` int(11) NOT NULL,
  `province` varchar(100) NOT NULL DEFAULT 'Negros Occidental' COMMENT 'Province this org chart entry belongs to',
  `position_order` int(11) NOT NULL COMMENT '1=Regional Director, 2=Field Office Head, 3=DILEEP Focal, 4=LDS/Office Staff/IT',
  `position_title` varchar(255) NOT NULL COMMENT 'Position title (e.g., Regional Director)',
  `person_name` varchar(255) DEFAULT NULL COMMENT 'Name of person in this position',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tier` tinyint(4) NOT NULL DEFAULT 0,
  `sort_order` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='DILEEP-NOCFO Organizational Chart';

--
-- Dumping data for table `org_chart`
--

INSERT INTO `org_chart` (`id`, `province`, `position_order`, `position_title`, `person_name`, `created_at`, `updated_at`, `tier`, `sort_order`) VALUES
(1, 'Negros Occidental', 1, 'Regional Director', 'Atty. Roy L. Buenafee', '2026-05-11 14:14:34', '2026-05-12 01:44:50', 0, 0),
(2, 'Negros Occidental', 2, 'OIC, DOLE-NOCFO', 'Gretchen I. Pasiolan', '2026-05-11 14:14:34', '2026-05-12 05:01:49', 1, 0),
(3, 'Negros Occidental', 3, 'Sr. LEO / DILEEP Focal', 'Engr. Milson Delos Reyess', '2026-05-11 14:14:34', '2026-05-12 05:02:53', 2, 0),
(4, 'Negros Occidental', 4, 'LDS', 'Kayzel Aranetaa', '2026-05-11 14:14:34', '2026-05-12 02:29:34', 3, 0),
(6, 'Negros Occidental', 31, 'LDS', 'Jona Cepriano', '2026-05-12 02:29:27', '2026-05-12 07:03:24', 3, 1),
(7, 'Negros Occidental', 32, 'SAWP Field Facilitator / IT Specialist', 'Elziakim Pegar', '2026-05-12 02:29:59', '2026-05-12 07:03:24', 3, 2),
(8, 'Negros Occidental', 33, 'TUPAD', 'Yzabel Gane', '2026-05-12 02:30:17', '2026-05-12 07:03:24', 3, 3),
(9, 'Negros Occidental', 34, 'TUPAD', 'Ieliz Jover', '2026-05-12 02:30:25', '2026-05-12 07:03:24', 3, 4),
(24, 'Negros Oriental', 11, 'Chief, NORFO and SFO', 'Joselita Remedios S. Bayalas', '2026-05-12 06:29:06', '2026-05-12 07:29:58', 1, 0),
(25, 'Negros Oriental', 12, 'Sr. LEO', 'Jerome D. Alam', '2026-05-12 06:29:06', '2026-05-12 07:30:14', 2, 0),
(27, 'Siquijor', 21, 'Chief, NORFO and SFO', 'Joselita Remedios S. Bayalas', '2026-05-12 06:29:06', '2026-05-12 07:31:21', 1, 0),
(28, 'Siquijor', 22, 'Senior LEO', 'Jerome D. Alam', '2026-05-12 06:29:06', '2026-05-12 07:31:39', 2, 0),
(29, 'Negros Oriental', 10, 'LDS / Designated-Encoder', 'Cristy Jane Butalid', '2026-05-12 07:22:54', '2026-05-12 07:30:53', 3, 0),
(30, 'Negros Oriental', 40, 'LDS / Office Staff / IT', NULL, '2026-05-12 07:22:54', '2026-05-12 07:22:54', 3, 0),
(31, 'Siquijor', 10, 'Regional Director', NULL, '2026-05-12 07:22:54', '2026-05-12 07:23:02', 3, 0),
(32, 'Siquijor', 40, 'LDS / Office Staff / IT', NULL, '2026-05-12 07:22:54', '2026-05-12 07:22:54', 3, 0),
(33, 'Negros Oriental', 10, 'Regional Director', NULL, '2026-05-12 07:23:02', '2026-05-12 07:23:30', 3, 0),
(34, 'Siquijor', 10, 'Regional Director', NULL, '2026-05-12 07:23:02', '2026-05-12 07:23:30', 3, 0),
(35, 'Negros Oriental', 10, 'Regional Director', 'Atty. Roy L. Buenafe', '2026-05-12 07:23:30', '2026-05-12 07:29:13', 3, 0),
(36, 'Siquijor', 10, 'Regional Director', NULL, '2026-05-12 07:23:30', '2026-05-12 07:29:13', 3, 0),
(37, 'Negros Oriental', 10, 'Regional Director', NULL, '2026-05-12 07:29:13', '2026-05-12 07:29:59', 3, 0),
(38, 'Siquijor', 10, 'Regional Director', NULL, '2026-05-12 07:29:13', '2026-05-12 07:29:59', 3, 0),
(39, 'Negros Oriental', 10, 'Regional Director', NULL, '2026-05-12 07:29:59', '2026-05-12 07:30:15', 3, 0),
(40, 'Siquijor', 10, 'Regional Director', NULL, '2026-05-12 07:29:59', '2026-05-12 07:30:15', 3, 0),
(41, 'Negros Oriental', 10, 'Regional Director', NULL, '2026-05-12 07:30:15', '2026-05-12 07:30:54', 3, 0),
(42, 'Siquijor', 10, 'Regional Director', NULL, '2026-05-12 07:30:15', '2026-05-12 07:30:54', 3, 0),
(43, 'Negros Oriental', 10, 'Regional Director', NULL, '2026-05-12 07:30:54', '2026-05-12 07:31:10', 3, 0),
(44, 'Siquijor', 10, 'Regional Director', NULL, '2026-05-12 07:30:54', '2026-05-12 07:31:10', 3, 0),
(45, 'Negros Oriental', 10, 'Regional Director', NULL, '2026-05-12 07:31:10', '2026-05-12 07:31:22', 3, 0),
(46, 'Siquijor', 10, 'Regional Director', NULL, '2026-05-12 07:31:10', '2026-05-12 07:31:22', 3, 0),
(47, 'Negros Oriental', 10, 'Regional Director', NULL, '2026-05-12 07:31:22', '2026-05-12 07:31:40', 3, 0),
(48, 'Siquijor', 10, 'Regional Director', NULL, '2026-05-12 07:31:22', '2026-05-12 07:31:40', 3, 0),
(49, 'Negros Oriental', 10, 'Regional Director', NULL, '2026-05-12 07:31:40', '2026-05-12 07:31:40', 0, 0),
(50, 'Siquijor', 10, 'Regional Director', NULL, '2026-05-12 07:31:40', '2026-05-12 07:31:40', 0, 0);

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
(1, 'maintenance_mode', '0', '2026-03-11 05:50:02', '2026-05-11 08:12:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','encoder','user','super_admin') NOT NULL DEFAULT 'user',
  `province` enum('Negros Occidental','Negros Oriental','Siquijor') DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `province`, `full_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@dilp.gov.ph', '$2y$10$a6B7wXCzG83VKX.lX/h/seGi7H40EqquOlKeKgU3ytp/W.fpuOTkm', 'super_admin', NULL, 'Kim IT- Admin', 1, '2026-02-03 09:20:07', '2026-05-11 07:55:01'),
(2, 'kayzel', 'kayzel@dilp.com', '$2y$10$BWQuVA4vDhm2MiFRTn1WXO7NyuNBBKa.AxBw3UFiYVbrCkm2l3qhm', 'encoder', 'Negros Occidental', 'Kayzel Araneta', 1, '2026-02-04 07:44:20', '2026-05-11 07:55:01'),
(3, 'jona', 'jona@dilp.com', '$2y$10$cZIQmxS4jgtie9A5Iec4geSW1pYx859hf4j9oNlk2JIy9n0oyV/Na', 'encoder', 'Negros Occidental', 'Jona Cepriano', 1, '2026-02-04 07:46:59', '2026-05-11 07:55:01'),
(4, 'user', 'testuser@gmail.com', '$2y$10$PiaNVNl7pPhAPNeF8ri46ufDCHQi3kl9Bu9mIUk.RCxEj4WkwNFpe', 'user', 'Negros Occidental', 'test user', 1, '2026-02-04 07:47:46', '2026-05-11 07:55:01'),
(5, 'gretchen.dileepsys', 'gretchenpasiolan@gmail.com', '$2y$10$S7Nv7F0eFpCD7inYJvuM/uvWdXciW/1XCuTN/6u6C4UHq7o5l3Og.', 'admin', 'Negros Occidental', 'Gretchen Pasiolan', 1, '2026-03-10 08:52:57', '2026-05-12 05:01:22'),
(6, 'milson.admin', 'mfdelosreyes@li.dole.gov.ph', '$2y$10$lVXUOHVkoPHj93vZToxhuurPo1dJh258P5WINhbtatIvyTPT.siiS', 'admin', 'Negros Occidental', 'Milson Delos Reyes', 1, '2026-03-10 08:55:27', '2026-05-12 05:01:10'),
(7, 'nole.dileepsys', 'nole.tssd@dileepsys.com', '$2y$10$CU8VMtv271MNygpCjR/.S.bwMkzXUaY/0Ht1Lxa1cR24F4yAi1oR6', 'admin', 'Negros Oriental', 'Nole TSSD', 1, '2026-05-11 08:22:08', '2026-05-11 08:22:08'),
(8, 'siquijor.admin', 'siquijor.admin@dileepsys.gov.ph', '$2y$10$Lg9TSyRSgHHBLAQgVBRYT.A372sS02vLZVwr/aQpItMUo.IVAJaVq', 'admin', 'Siquijor', 'siquijor admin', 1, '2026-05-11 14:30:25', '2026-05-11 14:30:25'),
(9, 'encoder.norfo', 'encoder.norfo@dileepsys.gov.ph', '$2y$10$mLhrwec1sxCDSOBZFA9OC.vYY8hmoVOjOYKzlgUDZJafClTvs5u1a', 'encoder', 'Negros Oriental', 'Encoder NORFO', 1, '2026-05-12 03:05:21', '2026-05-12 03:05:21'),
(10, 'viewer.norfo', 'viewer.norfo@dileepsys.dole.gov.ph', '$2y$10$VXs724TpqbuDXKiBiCjzv.rR8.3Pxq3ipxQ1mOuJ8ACqgbRsAi1mK', 'user', 'Negros Oriental', 'Viewer NORFO', 1, '2026-05-12 03:07:29', '2026-05-12 03:07:29'),
(11, 'encoder.siquijor', 'encoder.siquijor@dileepsys.dole.gov.ph', '$2y$10$SJPyJW0o9EDNoDzXjdkASetLxrM1x/RL65dTrctGqIkezU4nV9BO6', 'encoder', 'Siquijor', 'Encoder Siquijor', 1, '2026-05-12 03:08:13', '2026-05-12 03:08:13'),
(12, 'viewer.siquijor', 'viewer.siquijor@dileepsys.dole.gov.ph', '$2y$10$FsYEATA2PrnRFJ/VAd2e2eWvkZm6gvtn7WSljk0ILSv4oV9XOR2W6', 'user', 'Siquijor', 'Viewer SFO', 1, '2026-05-12 03:08:54', '2026-05-12 03:08:54');

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
  ADD KEY `idx_fieldwork_created_by` (`created_by`),
  ADD KEY `idx_fieldwork_province` (`province`);

--
-- Indexes for table `org_chart`
--
ALTER TABLE `org_chart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_position_order` (`position_order`),
  ADD KEY `idx_org_chart_province` (`province`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_province` (`province`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=586;

--
-- AUTO_INCREMENT for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fieldwork_schedule`
--
ALTER TABLE `fieldwork_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `org_chart`
--
ALTER TABLE `org_chart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `proponents`
--
ALTER TABLE `proponents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proponent_associations`
--
ALTER TABLE `proponent_associations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
