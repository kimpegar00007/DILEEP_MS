-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 05, 2026 at 04:44 AM
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
(91, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-02-05 02:21:26');

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

INSERT INTO `beneficiaries` (`id`, `last_name`, `first_name`, `middle_name`, `suffix`, `gender`, `barangay`, `municipality`, `contact_number`, `project_name`, `type_of_worker`, `amount_worth`, `noted_findings`, `date_complied_by_proponent`, `date_forwarded_to_ro6`, `rpmt_findings`, `date_approved`, `date_forwarded_to_nofo`, `date_turnover`, `date_monitoring`, `latitude`, `longitude`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'TOLMO', 'ROGER', 'TUAZON', 'JR', 'Male', 'Barangay 29 (Pob.)', 'BACOLOD CITY (Capital)', '', 'RICE RETAILING PROJECT', '', 30000.00, '', NULL, NULL, '', NULL, NULL, NULL, NULL, 10.66548618, 122.95009917, 'approved', 1, 1, '2026-02-03 14:26:15', '2026-02-03 14:35:06');

-- --------------------------------------------------------

--
-- Table structure for table `proponents`
--

CREATE TABLE `proponents` (
  `id` int(11) NOT NULL,
  `proponent_type` enum('LGU-associated','Non-LGU-associated') NOT NULL,
  `date_received` date DEFAULT NULL,
  `noted_findings` text DEFAULT NULL,
  `control_number` varchar(50) DEFAULT NULL,
  `number_of_copies` int(11) DEFAULT NULL,
  `date_copies_received` date DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
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

INSERT INTO `proponents` (`id`, `proponent_type`, `date_received`, `noted_findings`, `control_number`, `number_of_copies`, `date_copies_received`, `district`, `proponent_name`, `project_title`, `amount`, `number_of_associations`, `total_beneficiaries`, `male_beneficiaries`, `female_beneficiaries`, `type_of_beneficiaries`, `category`, `recipient_barangays`, `letter_of_intent_date`, `date_forwarded_to_ro6`, `rpmt_findings`, `date_complied_by_proponent`, `date_complied_by_proponent_nofo`, `date_forwarded_to_nofo`, `date_approved`, `date_check_release`, `check_number`, `check_date_issued`, `or_number`, `or_date_issued`, `date_turnover`, `date_implemented`, `date_liquidated`, `liquidation_deadline`, `date_monitoring`, `source_of_funds`, `latitude`, `longitude`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'LGU-associated', NULL, '', NULL, 0, NULL, '', 'LGU Sipalay City', 'SIPALAY INTEGRATED KABUHAYAN AGRO-ECO TOURISM Project (SIKOP)', 10000000.00, 0, 472, 270, 202, '', 'Enhancement', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04', NULL, NULL, '2026-02-14', NULL, '', 9.79100014, 122.46803346, 'approved', 1, 1, '2026-02-03 14:43:18', '2026-02-04 04:41:05'),
(9, 'Non-LGU-associated', '2026-01-15', 'Test findings', 'FORM-TEST-1770132022', 3, '2026-01-16', 'Test District', 'Form Test Non-LGU Proponent', 'Form Test Non-LGU Project', 50000.00, 2, 100, 50, 50, 'Farmers', 'Formation', 'Test Barangay', '2026-01-10', NULL, '', NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, '2026-02-01', NULL, NULL, '2026-04-02', NULL, 'DOLE', 10.50000000, 123.00000000, 'pending', 1, 1, '2026-02-03 15:20:22', '2026-02-03 15:20:22'),
(10, 'Non-LGU-associated', '2026-01-15', '', 'LOG-TEST-1770132049', 0, NULL, '', 'Log Test Proponent', 'Log Test Project', 10000.00, 0, 50, 0, 0, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, '2026-02-01', NULL, NULL, '2026-04-02', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:20:49', '2026-02-03 15:20:49'),
(12, 'LGU-associated', NULL, '', 'LGU-VERIFY-1770132115', 0, NULL, '', 'Test LGU Proponent', 'Test LGU Project', 25000.00, 0, 50, 0, 0, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, '2026-02-01', NULL, NULL, '2026-02-11', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:21:55', '2026-02-03 15:21:55'),
(13, 'Non-LGU-associated', NULL, '', 'NON-LGU-VERIFY-1770132115', 0, NULL, '', 'Test Non-LGU Proponent', 'Test Non-LGU Project', 50000.00, 0, 100, 0, 0, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, '2026-02-01', NULL, NULL, '2026-04-02', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:21:55', '2026-02-03 15:21:55'),
(16, 'Non-LGU-associated', NULL, '', 'MODEL-TEST-1770132146', 0, NULL, '', 'Model Test', 'Test', 10000.00, 0, 50, 0, 0, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, '2026-02-01', NULL, NULL, '2026-04-02', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:22:26', '2026-02-03 15:22:26'),
(21, 'Non-LGU-associated', NULL, '', NULL, 0, NULL, '', 'Test Non-LGU Organization', 'Community Livelihood Project', 75000.00, 0, 150, 75, 75, 'Farmers', 'Formation', 'Barangay Test', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15', NULL, NULL, '2026-04-16', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:24:32', '2026-02-03 15:24:32'),
(22, 'Non-LGU-associated', NULL, '', NULL, 0, NULL, '', 'Test Non-LGU 1', 'Test Project 1', 50000.00, 0, 100, 50, 50, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15', NULL, NULL, '2026-04-16', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:24:59', '2026-02-03 15:24:59'),
(23, 'Non-LGU-associated', NULL, '', NULL, 0, NULL, '', 'Test Non-LGU 2', 'Test Project 2', 50000.00, 0, 100, 50, 50, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15', NULL, NULL, '2026-04-16', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:24:59', '2026-02-03 15:24:59'),
(24, 'LGU-associated', NULL, '', NULL, 0, NULL, '', 'Test LGU', 'LGU Project', 50000.00, 0, 100, 50, 50, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15', NULL, NULL, '2026-02-25', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:24:59', '2026-02-03 15:24:59'),
(25, 'Non-LGU-associated', NULL, '', NULL, 0, NULL, '', 'Test Non-LGU 1', 'Test Project 1', 50000.00, 0, 100, 50, 50, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15', NULL, NULL, '2026-04-16', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:24:59', '2026-02-03 15:24:59'),
(26, 'Non-LGU-associated', NULL, '', NULL, 0, NULL, '', 'Non-LGU Test Organization', 'Community Development Project', 75000.00, 0, 150, 75, 75, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15', NULL, NULL, '2026-04-16', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:25:50', '2026-02-03 15:25:50'),
(27, 'Non-LGU-associated', NULL, '', NULL, 0, NULL, '', 'Non-LGU Test Organization', 'Community Development Project', 75000.00, 0, 150, 75, 75, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15', NULL, NULL, '2026-04-16', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:25:50', '2026-02-03 15:25:50'),
(28, 'Non-LGU-associated', NULL, '', NULL, 0, NULL, '', 'Another Non-LGU', 'Community Development Project', 75000.00, 0, 150, 75, 75, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15', NULL, NULL, '2026-04-16', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:25:50', '2026-02-03 15:25:50'),
(29, 'LGU-associated', NULL, '', NULL, 0, NULL, '', 'LGU Test', 'Community Development Project', 75000.00, 0, 150, 75, 75, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15', NULL, NULL, '2026-02-25', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:25:50', '2026-02-03 15:25:50'),
(30, 'Non-LGU-associated', NULL, '', NULL, 0, NULL, '', 'Another Non-LGU', 'Community Development Project', 75000.00, 0, 150, 75, 75, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15', NULL, NULL, '2026-04-16', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-03 15:25:50', '2026-02-03 15:25:50'),
(34, 'Non-LGU-associated', NULL, '', NULL, 0, NULL, '', 'GUIN-OLAYAN AGRARIAN REFORM COOPERATIVE (GARC)', 'GROUP AGRI-ENTERPRISE RETAILING COMMODITY PROJECT', 1500000.00, 0, 29, 0, 0, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-03', NULL, NULL, '2026-04-04', NULL, '', 10.91231296, 123.13324091, 'approved', 1, 1, '2026-02-03 15:27:56', '2026-02-03 15:29:11'),
(35, 'Non-LGU-associated', NULL, '', NULL, 0, NULL, '', 'BULATA SMALL FISHERFOLKS ASSOCIATION (BUSFA)', 'BULATA LIVELIHOOD FOR INCOME GENERATING (BULIG) PROJECT', 1500000.00, 0, 50, 0, 0, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04', NULL, NULL, '2026-04-05', NULL, '', NULL, NULL, 'pending', 1, 1, '2026-02-04 04:34:38', '2026-02-04 04:34:38'),
(36, 'LGU-associated', NULL, '', NULL, 0, NULL, '', 'Cadiz City', 'ILAJAS', 3000000.00, 1, 100, 0, 0, '', 'Formation', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-05', NULL, NULL, '2026-02-15', '2026-08-05', '', 10.83324135, 123.27000579, 'approved', 1, 1, '2026-02-05 01:51:09', '2026-02-05 01:51:09');

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
(1, 'admin', 'admin@dilp.gov.ph', '$2y$12$cxubeCJxgDoHaci9zO4Ud.b7uJ7PQQpWfOafrfLY2efdUQGNuRDLi', 'admin', 'System Administrator', 1, '2026-02-03 09:20:07', '2026-02-03 09:20:07'),
(2, 'kayzel', 'kayzel@dilp.com', '$2y$10$BWQuVA4vDhm2MiFRTn1WXO7NyuNBBKa.AxBw3UFiYVbrCkm2l3qhm', 'encoder', 'Kayzel', 1, '2026-02-04 07:44:20', '2026-02-04 07:44:20'),
(3, 'jona', 'jona@dilp.com', '$2y$10$cZIQmxS4jgtie9A5Iec4geSW1pYx859hf4j9oNlk2JIy9n0oyV/Na', 'encoder', 'Jona', 1, '2026-02-04 07:46:59', '2026-02-04 07:46:59'),
(4, 'user', 'testuser@gmail.com', '$2y$10$PiaNVNl7pPhAPNeF8ri46ufDCHQi3kl9Bu9mIUk.RCxEj4WkwNFpe', 'user', 'test user', 1, '2026-02-04 07:47:46', '2026-02-04 07:47:46');

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
  ADD KEY `idx_beneficiaries_date_approved` (`date_approved`);

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
  ADD KEY `idx_proponents_date_approved` (`date_approved`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `proponents`
--
ALTER TABLE `proponents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Constraints for table `proponents`
--
ALTER TABLE `proponents`
  ADD CONSTRAINT `proponents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `proponents_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
