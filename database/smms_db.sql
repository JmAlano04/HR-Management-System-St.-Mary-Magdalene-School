-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql304.infinityfree.com
-- Generation Time: Nov 20, 2026 at 12:50 AM
-- Server version: 11.4.13-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_43438934_smms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `first_name` varchar(75) DEFAULT NULL,
  `middle_name` varchar(75) DEFAULT NULL,
  `last_name` varchar(75) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` text NOT NULL,
  `home_address` text NOT NULL,
  `birth_date` date NOT NULL,
  `gender` varchar(255) NOT NULL,
  `civil_status` varchar(255) NOT NULL,
  `education` varchar(255) NOT NULL,
  `job_type` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `resume` varchar(255) NOT NULL,
  `coverletter` varchar(255) NOT NULL,
  `appletter` varchar(255) NOT NULL,
  `interview_date` date DEFAULT NULL,
  `interview_time` varchar(255) DEFAULT NULL,
  `interview_location` varchar(255) DEFAULT NULL,
  `interview_note` text DEFAULT NULL,
  `interview_completed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `resume_original_name` varchar(255) DEFAULT NULL,
  `coverletter_original_name` varchar(255) DEFAULT NULL,
  `appletter_original_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_records`
--

INSERT INTO `attendance_records` (`id`, `employee_id`, `date`, `time_in`, `time_out`, `hours_worked`, `status`, `archived`, `created_at`, `updated_at`) VALUES
(80, 1, '2026-11-10', '09:00:44', '13:21:31', '4.35', 'Undertime', 0, '2026-11-11 05:27:51', '2026-11-11 06:26:02'),
(81, 2, '2026-11-10', '11:22:16', '14:30:36', '3.14', 'Undertime', 0, '2026-11-11 05:27:51', '2026-11-11 06:30:41'),
(82, 6, '2026-11-10', '09:03:32', '13:22:08', '4.31', 'Undertime', 0, '2026-11-11 05:27:51', '2026-11-11 05:27:51');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `placement` varchar(255) NOT NULL DEFAULT 'admin',
  `path` varchar(255) NOT NULL,
  `alt` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `placement`, `path`, `alt`, `is_default`, `sort_order`, `uploaded_by`, `created_at`, `updated_at`) VALUES
(53, 'admin', 'image/promote1.jpg', 'promote1', 1, 1, NULL, '2026-11-20 09:54:04', '2026-11-20 09:54:04'),
(54, 'admin', 'image/promote2.jpg', 'promote2', 1, 2, NULL, '2026-11-20 09:54:04', '2026-11-20 09:54:04'),
(55, 'admin', 'image/promote3.jpg', 'promote3', 1, 3, NULL, '2026-11-20 09:54:04', '2026-11-20 09:54:04'),
(56, 'homepage', 'image/homepage1.jpg', 'homepage1', 1, 1, NULL, '2026-11-20 09:54:13', '2026-11-20 09:54:13'),
(57, 'homepage', 'image/homepage2.png', 'homepage2', 1, 2, NULL, '2026-11-20 09:54:13', '2026-11-20 09:54:13');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-c10ab5d0b611c86fd634f94fe7342edde8c57b3b', 'i:1;', 1795140591),
('laravel-cache-c10ab5d0b611c86fd634f94fe7342edde8c57b3b:timer', 'i:1795140591;', 1795140591),
('laravel-cache-smms.accounting00@gmail.com|103.129.124.189', 'i:1;', 1795142493),
('laravel-cache-smms.accounting00@gmail.com|103.129.124.189:timer', 'i:1795142493;', 1795142493);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache_locks`
--

INSERT INTO `cache_locks` (`key`, `owner`, `expiration`) VALUES
('laravel-cache-framework\\schedule-79f20b176e40a106da90e25746fc8267e6b7d083', '7cPxISDBraZu70g7', 1794270600);

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_tasks`
--

CREATE TABLE `dashboard_tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `task_date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_id` varchar(255) NOT NULL,
  `zkteco_user_id` varchar(50) DEFAULT NULL,
  `first_name` varchar(75) DEFAULT NULL,
  `middle_name` varchar(75) DEFAULT NULL,
  `last_name` varchar(75) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `position` varchar(150) DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `role_type` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` text DEFAULT NULL,
  `birthday` date NOT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `civil_status` varchar(255) DEFAULT NULL,
  `address` text NOT NULL,
  `education` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `employee_id`, `zkteco_user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `name`, `nickname`, `position`, `department`, `role_type`, `email`, `phone`, `birthday`, `gender`, `civil_status`, `address`, `education`, `emergency_name`, `emergency_contact`, `employment_status`, `step_no`, `created_at`, `updated_at`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin`) VALUES
(1, 1, '1', '1', 'Administrator', NULL, NULL, NULL, 'Administrator', NULL, 'Admin Staff', 'Administrator', 'Administrative Staff', 'admin@stmary.edu', NULL, '1980-03-11', 'Female', 'Single', 'eyJpdiI6IjMxUytWWndEVzQ0TkpPcmVoaFBndmc9PSIsInZhbHVlIjoiUnp2RVhvL1V4dENmVHFHYk9kUzI0OFNjeUtmbW1BNmV1Z3pTVDNzdzI3TkpSSGZIMGRUTmgrWWdOQmM0U1NjRTRta2FvVytySjRCV3MrQjZmci9ZNkNXNzdzN0VpOG5jZVlkNUNuczlNTjJOTU4zdmdFSllBTGtJcSsxUnh3VUkiLCJtYWMiOiIyMGYxMzZkM2FhNzI5NzA5MzI5MzE2ZWYyY2RkNzM2NTcxOGFkYTMyNzM3ZDMwY2JjZDM1N2VkNTgxNjc0MWMxIiwidGFnIjoiIn0=', NULL, 'eyJpdiI6Ijd5NU1XZkZzdlVpTzQ1OHVVK2pDb1E9PSIsInZhbHVlIjoidnpEYjZkcUIxeCs2bDAvT2F4eXl0QT09IiwibWFjIjoiOTc5YTA5OTMwMGI2ZWNmMDhiZDgzMTE3ZjQ2MDAwMDQ5NTdmNjg3ZTlhYzE2YTY3ZGE2MjM1NDdiMTk1OGQwZiIsInRhZyI6IiJ9', 'eyJpdiI6Ijh0cmpIQnk1eGtaSjgyVnRQV0tUZVE9PSIsInZhbHVlIjoiMTRlSldFcE5xRUd2cGwzcDFIdy9Idz09IiwibWFjIjoiOWE4NzI2MTg3NjJlY2E3ZWMxYjYwYTY3OTQ4NWQwNWM3NjRlYmM4MWE4YWMyN2JmMDhhYTc1ZmE5ZTMwZjg3OCIsInRhZyI6IiJ9', 'non regular', 0, '2026-10-24 00:33:57', '2026-11-03 18:25:22', NULL, NULL, NULL, NULL),
(2, 2, '2', '2', 'Human', NULL, 'Resource', NULL, 'Human Resource', NULL, 'HR Officer', 'HR', 'Administrative Staff', 'hr@stmary.edu', NULL, '1988-07-21', NULL, NULL, 'eyJpdiI6IkQ5bTVtZHdkV0w5T25ObmdqMEFRZWc9PSIsInZhbHVlIjoiUVZhcGY2UC8vdGxvOE9yWVpoSUN4Zz09IiwibWFjIjoiMjFjYjk5ZTQ5YTZmNmQ5Y2ZiYzY5YmQ0OTg1YWY0MmY1OTc4YjFjZmM5ZjU4ODkyNjEzMTk3Mjc4MTQwOTFmMSIsInRhZyI6IiJ9', NULL, 'eyJpdiI6ImNnNnZ0eTc5WWNKQi9iWHNyTFN0N1E9PSIsInZhbHVlIjoiRVg2S2VQRm9DdEpVbnY0dEt0WmhFUT09IiwibWFjIjoiMzIxMzNjYjgzYmM0YzUzNjNjYjI3ODVhMmU1Mjc2NjAwM2E3NzRhNTMwY2NkMjI4MzBkMDljYzg4YjcxMTI1MSIsInRhZyI6IiJ9', 'eyJpdiI6IjdVbUN5MjNLYVN2azI2bkdUSXI2a0E9PSIsInZhbHVlIjoia2ZxejFLWGpBQmJZZnU3UzV4eUlFQT09IiwibWFjIjoiMjMzODFiYjkyOTc1ZmQ4MmZhOTQ0NDRmY2NkYjc1MGQ1MWJjODUwZDZmYjEzZGE4ODVlODliZjA5YmMzY2M5ZCIsInRhZyI6IiJ9', 'regular', 3, '2026-10-24 00:33:57', '2026-11-03 18:24:25', NULL, NULL, NULL, NULL),
(3, 4, '3', '3', 'Accounting', NULL, 'Personnela', NULL, 'Accounting Personnel', NULL, 'Accounting Staff', 'Accounting', 'Administrative Staff', 'accounting@stmary.edu', NULL, '1990-11-05', 'Male', NULL, 'eyJpdiI6InIrNXBJS3c3M3lKWHc2V1lnRURzQ0E9PSIsInZhbHVlIjoiZDJ5dFM4VUl6b1lVZlFVcm1uTDAxWjBGc2lrT1BWVWI3WS9BNmtIMmhPbVYxRlB2QUE3QzJUOThxZlVsbHIyakJocjJTeWhJckNqVXhIUzlZelJveUs5Y2RvbDhoaFFVa20yY2t0d2FYV2EzcldtQWRFRTU2RjFGVzNoa3JKaWciLCJtYWMiOiIxMDVkOGYyM2NjN2Q5YWY5OGRiZTY0MzM3ZTQ0MzY0NGE4MmIwMWMwY2ZmODJmNTQ4YmYyZjUzOTZiOWMwYzY5IiwidGFnIjoiIn0=', NULL, 'eyJpdiI6IkdySjlWUmNrb0I3dzlReWlld00wTVE9PSIsInZhbHVlIjoiM05ESHFMRmpDbUZlR0VER1M2SGlTUT09IiwibWFjIjoiMmYzZDk2ZmQzYjZjZjcwOThiYTc3YWYxNWM4M2QyZjdhZGEyN2Q0NmExZDY2OTQ5OWQwMjEzY2NiMjZhNjJmMCIsInRhZyI6IiJ9', 'eyJpdiI6IncxZmNLTWZOYklrSDBHMUJyNVhGd3c9PSIsInZhbHVlIjoiN0gxalVDajNYemxvdnl3MlNYQWJiUT09IiwibWFjIjoiZGNkZWI1ZTQ0YWMzYzBmNTBjYzlmNjQzZTY1YjFjYTc2NzEzMTlhZDA4YzkwNDQzZjU2YzA5MDAzMjE4MjkzZCIsInRhZyI6IiJ9', 'regular', 1, '2026-10-24 00:33:57', '2026-11-03 18:25:01', NULL, NULL, NULL, NULL),
(6, 5, '1006', '1006', 'Juan', 'Carlos Dela', 'Cruz', NULL, 'Juan Carlos Dela Cruz', NULL, 'TLE Teacher', 'Elementary', 'Teaching Staff', 'accounting@example.com', NULL, '2011-03-30', 'Male', NULL, 'eyJpdiI6IlREeUVXdkRBcXJXVERsR25YZnliNkE9PSIsInZhbHVlIjoiZDJMYWZjeHBrMDVGVlpZVm9GSXIvY0FmeGVqWEV6NVRiU2FTanpJbFJuNW5PMElDd25vNlRGdERUUnI2aUVQYWpmUHdjRVVvcERWTHU3UWNrVTl5T25oTmM0MUNRRjFDMXlEeE03QXpDaWZEM0M4bmd5dUxuZWhKU0xGK1NFZ3AiLCJtYWMiOiIzMGUxZGFkNGUwZTcyYmI2ZDYyMzQyMTdmMjQ5NWEyMWI1NjI5ZDQyOTc1ZDA1NDQ5M2U4NWI3YjVjYzM5NGZmIiwidGFnIjoiIn0=', NULL, 'eyJpdiI6IkFKK2VrMWo3SEZXd0F3VXpwV2YzZkE9PSIsInZhbHVlIjoiVHJkVjVzRGZCNnpnMUQ5dHZnU0gwQT09IiwibWFjIjoiNjUwZTRjNTk5Mjc0M2JiMDUzYTkyNzZjMDAwNDJhNjA4ZTBiZWJkNTUzZWViYzE1NzNkMWU3NzJiYjc1OTc3ZiIsInRhZyI6IiJ9', 'eyJpdiI6IkxtS01DVXo1a1V0L2hzcnRmTlRnRUE9PSIsInZhbHVlIjoidVAzY29VRHo1K2dReEZ3WFM1RDZvZz09IiwibWFjIjoiZWU0ZTU5NWMxMzcwMjRlYTNkMDA1NjYxZTQ5Y2EzNjdlZmY2MjUxMWMxNjg3ZWYzN2U1MGRlNmI2YmUwYThkZSIsInRhZyI6IiJ9', 'regular', 1, '2026-11-01 00:00:16', '2026-11-03 20:38:28', NULL, NULL, NULL, NULL),
(43, NULL, '23123', '23123', 'Jenmar', 'Acantilado', 'Alano', NULL, 'Jenmar Acantilado Alano', NULL, 'Employee', 'Administrator', 'Administrative Staff', 'alanojenmar19@gmail.com', NULL, '1996-01-10', 'Male', 'Single', 'eyJpdiI6IjNvWjFtc3d5WjVaeERZYzcwVzNtYWc9PSIsInZhbHVlIjoiVlVtZkNQUzZ2YndrZ2ExV2JPS1pPMEpKZWp4YnBpODN1Um1Fbi84TWFDYzlkbm9DNWNwTXA5RktmbFQ4MlZiQjI1YkkzYklqME1mOTJyMUZ4MTBuYkJlbGpMOSt2UnZUTVVEaEt1eVp0MEllWmVPNy9YMk83OHo5U1VzckcydDdRblNyMlc1UDMyMHo3dlJBNlY2T0V1UGFEdjJPS3lpQjZrbW12TmxDVmtFPSIsIm1hYyI6ImUwOWY1NzczYzk2MThlNjY5NjE4YzgyZWM4NDIxOTljOThiZjUwZTFmNzEyM2I3NTFmYTMxMzMxZjE3ZTZiMjQiLCJ0YWciOiIifQ==', NULL, 'eyJpdiI6ImhXYXIvdGtJdU9iMVIwVGJxSGU4SkE9PSIsInZhbHVlIjoiS1dUWmh0Vk1mYnZ4dkZ0LzBrWThUdz09IiwibWFjIjoiNGQyZWI4YjNlMTAzMjYzMzkwMWE2Yzc3YTdiMTU4OTVkMzUzOTBjYmI5NjFiOWY5NmQxY2MyNjAyMTQ5OTA3NSIsInRhZyI6IiJ9', 'eyJpdiI6Ik50S2tvakVPakEyWXpaVnl5eFkySVE9PSIsInZhbHVlIjoib1ZyWlY5a3hmWjlDNGNoblRRbTM5dz09IiwibWFjIjoiZDc2YjBjNTRkNzhhZjZiZTY1ZmEwMDgzODA4MWMyOGQ0NmQ0NmZlZGNkZjkzN2M2YzBkNGQ0ZTYyYWU5OGMzOCIsInRhZyI6IiJ9', 'regular', 1, '2026-11-12 07:16:02', '2026-11-12 07:16:02', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `submission_box_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `size_bytes` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Pending',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_openings`
--

CREATE TABLE `job_openings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_openings`
--

INSERT INTO `job_openings` (`id`, `created_at`, `updated_at`, `title`) VALUES
(8, '2026-10-31 20:33:10', '2026-10-31 20:33:10', 'Elementary Teacher'),
(9, '2026-11-02 00:00:44', '2026-11-02 00:00:44', 'Maintenance Staff'),
(10, '2026-11-02 21:15:07', '2026-11-02 21:15:07', 'Senior High School Teacher');

-- --------------------------------------------------------

--
-- Table structure for table `leave_balance_resets`
--

CREATE TABLE `leave_balance_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `year` smallint(5) UNSIGNED NOT NULL,
  `reset_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_balance_resets`
--

INSERT INTO `leave_balance_resets` (`id`, `year`, `reset_at`, `reset_by`, `created_at`, `updated_at`) VALUES
(5, 2026, '2026-11-05 01:37:52', 2, '2026-11-05 01:37:52', '2026-11-05 01:37:52');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_code` varchar(255) DEFAULT NULL,
  `employee_name` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `requested_date` date NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `leave_balance` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `employee_archived` tinyint(1) NOT NULL DEFAULT 0,
  `rejection_reason` text DEFAULT NULL,
  `admin_archived` tinyint(1) NOT NULL DEFAULT 0,
  `decided_by` bigint(20) UNSIGNED DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `hr_status` varchar(255) NOT NULL DEFAULT 'pending',
  `hr_decided_by` bigint(20) UNSIGNED DEFAULT NULL,
  `hr_decided_at` timestamp NULL DEFAULT NULL,
  `admin_status` varchar(255) NOT NULL DEFAULT 'pending',
  `admin_decided_by` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_decided_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `user_id`, `employee_code`, `employee_name`, `department`, `type`, `requested_date`, `start_date`, `end_date`, `reason`, `leave_balance`, `status`, `employee_archived`, `rejection_reason`, `admin_archived`, `decided_by`, `decided_at`, `created_at`, `updated_at`, `hr_status`, `hr_decided_by`, `hr_decided_at`, `admin_status`, `admin_decided_by`, `admin_decided_at`) VALUES
(30, NULL, NULL, 'David John Castillo', 'Maintenance', 'Non-Convertible Leave', '2026-11-05', '2026-11-05', '2026-11-05', 'emergency', '5', 'approved', 0, NULL, 0, 1, '2026-11-05 01:38:35', '2026-11-05 01:38:19', '2026-11-05 01:38:35', 'approved', 2, '2026-11-05 01:38:26', 'approved', 1, '2026-11-05 01:38:35'),
(31, NULL, NULL, 'David John Castillo', 'Maintenance', 'Sick Leave', '2026-11-05', '2026-11-05', '2026-11-05', 'Nagtatae', '3', 'approved', 0, NULL, 0, 1, '2026-11-05 05:17:18', '2026-11-05 05:16:48', '2026-11-05 05:17:18', 'approved', 2, '2026-11-05 05:17:10', 'approved', 1, '2026-11-05 05:17:18'),
(32, NULL, NULL, 'David John Castillo', 'Maintenance', 'Non-Convertible Leave', '2026-11-05', '2026-11-05', '2026-11-05', 'zvsd', '4', 'approved', 0, NULL, 0, 1, '2026-11-05 06:21:39', '2026-11-05 06:21:10', '2026-11-05 06:21:39', 'approved', 2, '2026-11-05 06:21:24', 'approved', 1, '2026-11-05 06:21:39'),
(33, 5, NULL, 'Juan Carlos Dela Cruz', 'Elementary', 'Non-Convertible Leave', '2026-11-12', '2026-11-13', '2026-11-13', 'asdasd', '5', 'pending', 0, NULL, 0, NULL, NULL, '2026-11-12 20:40:51', '2026-11-12 20:40:51', 'pending', NULL, NULL, 'pending', NULL, NULL),
(34, 5, NULL, 'Juan Carlos Dela Cruz', 'Elementary', 'Mandatory Summer Leave', '2026-11-12', '2026-11-13', '2026-11-13', 'asaaa', '5', 'pending', 0, NULL, 0, NULL, NULL, '2026-11-12 20:42:35', '2026-11-12 20:42:35', 'pending', NULL, NULL, 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `loan_repayments`
--

CREATE TABLE `loan_repayments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_request_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_at` date NOT NULL,
  `payslip_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_requests`
--

CREATE TABLE `loan_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `loan_type` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hr_status` varchar(255) NOT NULL DEFAULT 'pending',
  `hr_rejection_reason` text DEFAULT NULL,
  `hr_reviewed_at` timestamp NULL DEFAULT NULL,
  `hr_reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `accounting_status` varchar(255) NOT NULL DEFAULT 'pending',
  `accounting_rejection_reason` text DEFAULT NULL,
  `accounting_reviewed_at` timestamp NULL DEFAULT NULL,
  `accounting_reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_status` varchar(255) NOT NULL DEFAULT 'pending',
  `admin_rejection_reason` text DEFAULT NULL,
  `admin_reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `staff_archived_at` timestamp NULL DEFAULT NULL,
  `staff_archived_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_10_14_170147_create_permission_tables', 1),
(5, '2026_10_16_123520_create_applications_table', 1),
(6, '2026_10_16_173854_create_banners_table', 1),
(7, '2026_10_16_173958_create_calendar_events_table', 1),
(8, '2026_10_17_102132_create_employee_table', 1),
(9, '2026_10_17_112345_create_time_correction_table', 1),
(10, '2026_10_17_142447_create_leave_request_table', 1),
(11, '2026_10_18_113832_create_payslips_table', 1),
(12, '2026_10_18_120411_create_loan_request_table', 1),
(13, '2026_10_18_122340_create_offboarding_table', 1),
(14, '2026_10_18_150333_craete_attendance_record_table', 1),
(15, '2026_10_19_070652_create_employee_document_table', 1),
(16, '2026_10_19_121156_create_payroll_record_table', 1),
(17, '2026_10_19_122327_create_payroll_step_table', 1),
(18, '2026_10_22_141145_create_dashboard_task_table', 2),
(19, '2026_10_23_140223_add_employee_archive_table', 2),
(20, '2026_10_23_151535_add_repayment_months_to_loan_requests_table', 2),
(21, '2026_10_23_162756_add_employment_status_to_employees_table', 2),
(22, '2026_10_23_162957_add_employment_status_to_employees_table', 2),
(23, '2026_10_24_073000_create_payslip_responses_table', 3),
(24, '2026_10_24_132611_add_account_field_to_employees_table', 4),
(25, '2026_10_24_135823_update_employees_table', 5),
(26, '2026_10_25_133227_create_non_working_days_table', 6),
(27, '2026_10_26_094012_add_staff_archived_at_to_loan_requests_table', 7),
(28, '2026_10_26_111452_create_job_openings_table', 8),
(29, '2026_10_26_120000_add_title_to_job_openings_table', 9),
(30, '2026_10_26_123153_add_interview_note_to_applications_table', 10),
(31, '2026_10_26_182108_add_applied_at_to_time_connections_table', 11),
(32, '2026_10_27_062941_create_notifications_table', 12),
(33, '2026_10_27_063051_add_rejection_reason_to_requests', 12),
(34, '2026_10_28_133958_align_employees_table_for_profile', 13),
(35, '2026_10_29_165808_add_employee_archieved_to_leave_requests_table', 14),
(36, '2026_10_29_170341_add_employee_archived_to_leave_requests_table', 15),
(37, '2026_10_29_183123_create_zkteco_tables', 16),
(38, '2026_10_29_190715_add_zkteco_user_id_to_employees_table', 17),
(39, '2026_10_31_142913_add_step_no_to_employees_table', 18),
(40, '2026_11_01_000001_add_placement_to_banners_table', 19),
(41, '2026_11_02_000000_create_system_data_versions_table', 20),
(42, '2026_11_01_141500_create_leave_balance_resets_table', 21),
(43, '2026_11_02_000001_add_interview_completed_at_to_applications_table', 22),
(44, '2026_11_02_000002_add_original_document_names_to_applications_table', 23),
(45, '2026_11_02_000003_add_hr_workflow_fields_to_offboarding_tables', 24),
(46, '2026_11_02_000004_add_proof_original_name_to_time_corrections_table', 25),
(47, '2026_11_02_000005_add_proof_original_name_to_time_corrections_table', 26),
(48, '2026_11_02_130000_add_philhealth_loan_to_payslips_table', 27),
(49, '2026_11_02_140000_add_philhealth_loan_to_payroll_records_table', 28),
(50, '2026_11_02_153500_add_employee_id_to_submission_boxes_table', 29),
(51, '2026_11_02_170000_add_archived_at_to_notifications_table', 30),
(52, '2026_11_03_000001_add_name_parts_to_applications_table', 31),
(53, '2026_11_03_000002_add_dual_review_to_leave_requests', 32),
(54, '2026_11_08_025621_add_placement_and_is_default_to_banners_table', 33),
(55, '2026_10_29_185114_create_zkteco_tables', 34),
(56, '2026_10_31_155738_add_step_no_to_employees_table', 34),
(57, '2026_11_03_220000_add_undertime_to_payroll_tables', 34),
(58, '2026_11_10_000001_ensure_civil_status_and_gender_on_employees_table', 35),
(59, '2026_11_12_100000_add_name_parts_to_employees_table', 36);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 3),
(4, 'App\\Models\\User', 4),
(3, 'App\\Models\\User', 5),
(5, 'App\\Models\\User', 11),
(5, 'App\\Models\\User', 12),
(5, 'App\\Models\\User', 13),
(5, 'App\\Models\\User', 14),
(5, 'App\\Models\\User', 15),
(3, 'App\\Models\\User', 16),
(5, 'App\\Models\\User', 17),
(5, 'App\\Models\\User', 18),
(5, 'App\\Models\\User', 19),
(5, 'App\\Models\\User', 22),
(5, 'App\\Models\\User', 23),
(5, 'App\\Models\\User', 27),
(3, 'App\\Models\\User', 28),
(3, 'App\\Models\\User', 30),
(3, 'App\\Models\\User', 31),
(3, 'App\\Models\\User', 32),
(5, 'App\\Models\\User', 33),
(3, 'App\\Models\\User', 34),
(3, 'App\\Models\\User', 35),
(3, 'App\\Models\\User', 36),
(3, 'App\\Models\\User', 37),
(3, 'App\\Models\\User', 38),
(3, 'App\\Models\\User', 39),
(3, 'App\\Models\\User', 40),
(3, 'App\\Models\\User', 41),
(3, 'App\\Models\\User', 42),
(3, 'App\\Models\\User', 43),
(3, 'App\\Models\\User', 44),
(3, 'App\\Models\\User', 45),
(3, 'App\\Models\\User', 46),
(3, 'App\\Models\\User', 47),
(3, 'App\\Models\\User', 48),
(3, 'App\\Models\\User', 49),
(3, 'App\\Models\\User', 50),
(3, 'App\\Models\\User', 51),
(3, 'App\\Models\\User', 52),
(3, 'App\\Models\\User', 53),
(3, 'App\\Models\\User', 54),
(3, 'App\\Models\\User', 55),
(3, 'App\\Models\\User', 56),
(3, 'App\\Models\\User', 57),
(3, 'App\\Models\\User', 58),
(3, 'App\\Models\\User', 59),
(3, 'App\\Models\\User', 60),
(3, 'App\\Models\\User', 61),
(5, 'App\\Models\\User', 62),
(5, 'App\\Models\\User', 63),
(5, 'App\\Models\\User', 64),
(5, 'App\\Models\\User', 65),
(5, 'App\\Models\\User', 66),
(5, 'App\\Models\\User', 67),
(5, 'App\\Models\\User', 68),
(5, 'App\\Models\\User', 69),
(5, 'App\\Models\\User', 70),
(5, 'App\\Models\\User', 71),
(5, 'App\\Models\\User', 72),
(5, 'App\\Models\\User', 73),
(3, 'App\\Models\\User', 74),
(5, 'App\\Models\\User', 75),
(5, 'App\\Models\\User', 76),
(3, 'App\\Models\\User', 77),
(3, 'App\\Models\\User', 78),
(5, 'App\\Models\\User', 79),
(5, 'App\\Models\\User', 80),
(5, 'App\\Models\\User', 81),
(5, 'App\\Models\\User', 82),
(5, 'App\\Models\\User', 83),
(5, 'App\\Models\\User', 84),
(5, 'App\\Models\\User', 85),
(5, 'App\\Models\\User', 86),
(5, 'App\\Models\\User', 87),
(5, 'App\\Models\\User', 88),
(5, 'App\\Models\\User', 89);

-- --------------------------------------------------------

--
-- Table structure for table `non_working_days`
--

CREATE TABLE `non_working_days` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(40) NOT NULL,
  `pay_type` varchar(10) NOT NULL DEFAULT 'unpaid',
  `paid_days` decimal(3,2) NOT NULL DEFAULT 0.00,
  `paid_hours` decimal(4,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`, `archived_at`) VALUES
('5a3dfbcb-acdc-49f8-bd76-745274bfe80c', 'App\\Notifications\\HrmsNotification', 'App\\Models\\User', 1, '{\"title\":\"Leave request needs review\",\"message\":\"Reference: LEAVE-34. Juan Carlos Dela Cruz requested Mandatory Summer Leave from 2026-11-13 00:00:00 to 2026-11-13 00:00:00. Reason: asaaa. Please review and approve.\",\"url\":\"\\/leaves\",\"type\":\"warning\",\"dedupe_key\":\"leave:34:submitted\",\"from\":\"Juan Carlos Dela Cruz\"}', '2026-11-12 20:42:50', '2026-11-12 20:42:35', '2026-11-12 20:42:50', NULL),
('852aef88-3f48-4e8d-b835-db31bebfa223', 'App\\Notifications\\HrmsNotification', 'App\\Models\\User', 2, '{\"title\":\"Payslip needs review\",\"message\":\"Reference: PAYSLIP-212. Accounting submitted Jenmar Acantilado Alano\'s 30th Pay Period payslip for approval on Nov 12, 2026 12:44 PM. Transfer date: 2026-11-01 00:00:00.\",\"url\":\"\\/payslips\",\"type\":\"warning\",\"dedupe_key\":\"payslip:212:20261112124426.000000:submitted\",\"from\":\"Accounting Personnel\"}', '2026-11-12 20:46:24', '2026-11-12 20:44:26', '2026-11-12 20:46:24', NULL),
('b5946dd0-adfa-4b82-aa91-fa8850ed9480', 'App\\Notifications\\HrmsNotification', 'App\\Models\\User', 1, '{\"title\":\"Payslip needs review\",\"message\":\"Reference: PAYSLIP-212. Accounting submitted Jenmar Acantilado Alano\'s 30th Pay Period payslip for approval on Nov 12, 2026 12:44 PM. Transfer date: 2026-11-01 00:00:00.\",\"url\":\"\\/payslips\",\"type\":\"warning\",\"dedupe_key\":\"payslip:212:20261112124426.000000:submitted\",\"from\":\"Accounting Personnel\"}', '2026-11-12 20:44:50', '2026-11-12 20:44:26', '2026-11-12 20:44:50', NULL),
('f3d9b616-2eaf-4187-9a6a-bc028e0a2a01', 'App\\Notifications\\HrmsNotification', 'App\\Models\\User', 2, '{\"title\":\"Leave request needs review\",\"message\":\"Reference: LEAVE-34. Juan Carlos Dela Cruz requested Mandatory Summer Leave from 2026-11-13 00:00:00 to 2026-11-13 00:00:00. Reason: asaaa. Please review and approve.\",\"url\":\"\\/leaves\",\"type\":\"warning\",\"dedupe_key\":\"leave:34:submitted\",\"from\":\"Juan Carlos Dela Cruz\"}', '2026-11-12 20:42:58', '2026-11-12 20:42:35', '2026-11-12 20:42:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `offboarding_cases`
--

CREATE TABLE `offboarding_cases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `last_working_day` date NOT NULL,
  `employee_message` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Open',
  `progress` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `sent_to_admin` tinyint(1) NOT NULL DEFAULT 0,
  `sent_to_admin_at` timestamp NULL DEFAULT NULL,
  `admin_final_approved` tinyint(1) NOT NULL DEFAULT 0,
  `admin_approved_at` timestamp NULL DEFAULT NULL,
  `admin_approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `requirements_sent_at` timestamp NULL DEFAULT NULL,
  `required_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- Dumping data for table `offboarding_cases`
--

INSERT INTO `offboarding_cases` (`id`, `employee_id`, `reason`, `last_working_day`, `employee_message`, `status`, `progress`, `sent_to_admin`, `sent_to_admin_at`, `admin_final_approved`, `admin_approved_at`, `admin_approved_by`, `created_at`, `updated_at`, `requirements_sent_at`, `required_documents`, `completed_interviews`, `rejection_reason`) VALUES
(23, 6, 'Resignation', '2026-11-12', 'adadasd', 'Rejected', 10, 0, NULL, 0, NULL, NULL, '2026-11-12 14:30:13', '2026-11-12 14:34:00', NULL, NULL, NULL, 'asdasd');

-- --------------------------------------------------------

--
-- Table structure for table `offboarding_documents`
--

CREATE TABLE `offboarding_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `offboarding_case_id` bigint(20) UNSIGNED NOT NULL,
  `document_label` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `review_status` varchar(255) NOT NULL DEFAULT 'Under review',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('jenmaracantilado.alano@gmail.com', '$2y$12$o13IJ7ktfOkHqMJwmtmir.Gmpoi2SA1zrRKFTwIPQxPmQ7EwHh55i', '2026-11-10 03:42:10');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `period` varchar(7) NOT NULL,
  `cutoff` varchar(4) NOT NULL,
  `step_no` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `days_worked` decimal(5,2) NOT NULL DEFAULT 22.00,
  `hours_worked` decimal(6,2) NOT NULL DEFAULT 176.00,
  `late_minutes` decimal(8,2) NOT NULL DEFAULT 0.00,
  `undertime_minutes` decimal(8,2) NOT NULL DEFAULT 0.00,
  `honorarium` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sss` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sss_loan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `philhealth` decimal(10,2) NOT NULL DEFAULT 0.00,
  `philhealth_loan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `peraa_ceap` decimal(10,2) NOT NULL DEFAULT 0.00,
  `peraa_ceap_loan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hdmf` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hdmf_loan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cash_advance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_records`
--

INSERT INTO `payroll_records` (`id`, `employee_id`, `period`, `cutoff`, `step_no`, `days_worked`, `hours_worked`, `late_minutes`, `undertime_minutes`, `honorarium`, `sss`, `sss_loan`, `philhealth`, `philhealth_loan`, `peraa_ceap`, `peraa_ceap_loan`, `hdmf`, `hdmf_loan`, `cash_advance`, `created_at`, `updated_at`) VALUES
(461, 6, '2026-08', '30th', 1, '10.00', '79.57', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-08-30 16:00:00', '2026-11-03 21:10:39'),
(471, 6, '2026-09', '15th', 1, '11.50', '93.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-09-14 16:00:00', '2026-11-03 21:10:39'),
(481, 6, '2026-09', '30th', 1, '11.00', '87.03', '58.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-09-29 16:00:00', '2026-11-03 21:10:39'),
(491, 6, '2026-10', '15th', 1, '8.50', '71.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-10-14 16:00:00', '2026-11-03 21:10:39'),
(501, 6, '2026-10', '30th', 1, '9.00', '71.00', '60.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-10-30 16:00:00', '2026-11-03 21:10:39'),
(2222, 6, '2026-11', '15th', 1, '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-11-05 01:17:54', '2026-11-11 01:13:13'),
(2269, 6, '2026-11', '30th', 1, '1.00', '2.94', '123.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-11-05 01:18:10', '2026-11-20 10:40:49'),
(6072, 3, '2026-11', '30th', 1, '0.00', '0.00', '0.00', '0.00', '1900.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-11-12 20:43:44', '2026-11-20 10:40:49'),
(6073, 1, '2026-11', '30th', 0, '1.00', '2.99', '120.00', '0.00', '1900.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-11-12 20:43:44', '2026-11-20 10:40:49'),
(6074, 2, '2026-11', '30th', 3, '1.00', '1.64', '262.00', '0.00', '1900.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-11-12 20:43:44', '2026-11-20 10:40:49'),
(6075, 43, '2026-11', '30th', 1, '0.00', '0.00', '0.00', '0.00', '1900.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-11-12 20:43:44', '2026-11-20 10:40:49');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_steps`
--

CREATE TABLE `payroll_steps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `step_no` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `daily_wage` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_steps`
--

INSERT INTO `payroll_steps` (`id`, `step_no`, `name`, `daily_wage`, `created_at`, `updated_at`) VALUES
(1, 1, 'Step 1', '550.00', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(2, 2, 'Step 2', '577.50', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(3, 3, 'Step 3', '606.38', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(4, 4, 'Step 4', '636.70', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(5, 5, 'Step 5', '668.53', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(6, 6, 'Step 6', '701.96', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(7, 7, 'Step 7', '737.06', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(8, 8, 'Step 8', '773.91', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(9, 9, 'Step 9', '812.60', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(10, 10, 'Step 10', '853.23', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(11, 11, 'Step 11', '895.89', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(12, 12, 'Step 12', '940.69', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(13, 13, 'Step 13', '987.72', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(14, 14, 'Step 14', '1037.11', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(15, 15, 'Step 15', '1088.96', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(16, 16, 'Step 16', '1142.13', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(17, 17, 'Step 17', '1199.24', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(18, 18, 'Step 18', '1259.20', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(19, 19, 'Step 19', '1322.16', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(20, 20, 'Step 20', '1388.27', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(21, 21, 'Step 21', '1457.68', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(22, 22, 'Step 22', '1530.56', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(23, 23, 'Step 23', '1607.09', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(24, 24, 'Step 24', '1687.45', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(25, 25, 'Step 25', '1771.82', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(26, 26, 'Step 26', '1860.41', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(27, 27, 'Step 27', '1953.43', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(28, 28, 'Step 28', '2051.10', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(29, 29, 'Step 29', '2153.66', '2026-10-24 20:49:16', '2026-10-24 20:49:16'),
(30, 30, 'Step 30', '2261.34', '2026-10-24 20:49:16', '2026-10-24 20:49:16');

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `cutoff` varchar(255) NOT NULL DEFAULT '15th',
  `transfer_date` date DEFAULT NULL,
  `step_name` varchar(255) DEFAULT NULL,
  `days_worked` decimal(6,2) NOT NULL DEFAULT 0.00,
  `hours_worked` decimal(7,2) NOT NULL DEFAULT 0.00,
  `late_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `undertime_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `basic_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `daily_wage` decimal(12,2) NOT NULL DEFAULT 0.00,
  `honorarium` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gross_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `malasakitan` decimal(12,2) NOT NULL DEFAULT 50.00,
  `late_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `undertime_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `absences_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sss` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sss_loan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `philhealth` decimal(12,2) NOT NULL DEFAULT 0.00,
  `philhealth_loan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `peraa_ceap` decimal(12,2) NOT NULL DEFAULT 0.00,
  `peraa_ceap_loan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hdmf` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hdmf_loan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cash_advance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sent_to_admin_at` timestamp NULL DEFAULT NULL,
  `admin_status` varchar(255) NOT NULL DEFAULT 'Pending',
  `admin_rejection_reason` text DEFAULT NULL,
  `admin_reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `hr_status` varchar(255) NOT NULL DEFAULT 'Pending',
  `hr_rejection_reason` text DEFAULT NULL,
  `hr_reviewed_at` timestamp NULL DEFAULT NULL,
  `hr_reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payslips`
--

INSERT INTO `payslips` (`id`, `employee_id`, `cutoff`, `transfer_date`, `step_name`, `days_worked`, `hours_worked`, `late_minutes`, `undertime_minutes`, `basic_pay`, `daily_wage`, `honorarium`, `gross_pay`, `malasakitan`, `late_deduction`, `undertime_deduction`, `absences_deduction`, `sss`, `sss_loan`, `philhealth`, `philhealth_loan`, `peraa_ceap`, `peraa_ceap_loan`, `hdmf`, `hdmf_loan`, `cash_advance`, `total_deductions`, `net_pay`, `sent_to_admin_at`, `admin_status`, `admin_rejection_reason`, `admin_reviewed_at`, `admin_reviewed_by`, `hr_status`, `hr_rejection_reason`, `hr_reviewed_at`, `hr_reviewed_by`, `released_at`, `created_at`, `updated_at`) VALUES
(210, 6, '30th', '2026-11-01', 'Step 1', '1.00', '2.94', 123, 0, '202.13', '550.00', '0.00', '202.13', '50.00', '221.40', '0.00', '0.00', '725.00', '0.00', '357.50', '0.00', '572.00', '0.00', '200.00', '0.00', '0.00', '2125.90', '-1923.78', '2026-11-12 12:06:15', 'Rejected', 'asdasds', '2026-11-12 12:15:39', NULL, 'Rejected', 'asdasds', '2026-11-12 12:15:39', 2, NULL, '2026-11-09 07:39:16', '2026-11-12 12:15:39'),
(212, 43, '30th', '2026-11-01', 'Step 1', '0.00', '0.00', 0, 0, '0.00', '550.00', '1900.00', '1900.00', '50.00', '0.00', '0.00', '0.00', '725.00', '0.00', '357.50', '0.00', '572.00', '0.00', '200.00', '0.00', '0.00', '1904.50', '-4.50', '2026-11-12 20:44:26', 'Pending', NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, '2026-11-12 20:44:16', '2026-11-12 20:44:26');

-- --------------------------------------------------------

--
-- Table structure for table `payslip_responses`
--

CREATE TABLE `payslip_responses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payslip_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL,
  `reason` text DEFAULT NULL,
  `reviewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'manage users', 'web', '2026-10-23 23:22:46', '2026-10-23 23:22:46'),
(2, 'manage employees', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(3, 'view employees', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(4, 'manage attendance', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(5, 'view attendance', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(6, 'manage leave', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(7, 'request leave', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(8, 'manage payroll', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(9, 'view payroll', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(10, 'view own profile', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(2, 'HR', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(3, 'Employee', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(4, 'Accounting', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47'),
(5, 'Applicant', 'web', '2026-10-23 23:22:47', '2026-10-23 23:22:47');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(2, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(5, 3),
(7, 3),
(10, 3),
(8, 4),
(9, 4),
(10, 5);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('0zgxJarkLXX0dN2HwQWwDwWg7PygG8xpco34XmRj', NULL, '69.171.236.8', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaWgwUUVZTWdkZTJpcDlHNTJHZ0FQdFpNakZ3RW1JbDE0dzlGYnlOZCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147897),
('1kzbOO83nKzCJtHf51bhekYNITKTaSZvkPaQaMR7', NULL, '69.63.184.112', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYUMzOWVxZ2QzQW9Rdnp1UEwySHVhUkRPcHF3WDhQd3ppMTF2UlkwZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTkwOiJodHRwOi8vc3RtYXJ5bWFnZGFsZW5lLnNpdGUuamUvP2ZiY2xpZD1Jd1pYaDBiZ05oWlcwQ01URUFjR1J2WmdSemNuUmpCbUZ3Y0Y5cFpBd3lOVFl5T0RFd05EQTFOVGdBQVI4VTNZejhIMGRyYmxaZnZzdDlWUEUxbi1INzFQSGoyTm5yQzg0QThwekZOQjdvOGlvak1DNWZpN3ZzdWdfYWVtX21BWTJ5Q1dwTjZFVE5Dbm9sMExYbGcmaT0xIjtzOjU6InJvdXRlIjtzOjQ6ImhvbWUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1795147933),
('1XVfiHPQKbwqnBa8oHP91ub1TlrgHjphfdMLcKqQ', NULL, '173.252.95.7', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTExna2xLTHpRZ0VWRlFCT0dlOVdGeVdBTUJpaE1nanR5UjNWQVh4VyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147896),
('4W8rAKMqtzoOx3GTEvOOijGWyCorAodigFWU9ciM', NULL, '173.252.95.0', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYms2b2pkOTdHMG1zVmJvcUl5RXNMYVBCc2xTUGM2bnBmWFBlSnpLYiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147891),
('6Kr04Gz03KmA5vgxKRtnrIpt0oTzDrRK5llQ7Xmy', NULL, '173.252.95.36', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ29La2hRUWo0SGJrU2s4akJnazVqWTQwcDRDc3ZBck9OUm5tM1NMcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147894),
('85IZmmxqR0ggr0AGAHcfkINQqad2szedlqEd5WWB', NULL, '173.252.82.17', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUkRlWnpETnV0UGlPNTY1czJXY0dzZ2J1ZDlxNGdic0JTdDJ4TGR2WSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147943),
('AcqL5UtjYUoeIcHGjWCCjAAa7TYHdqBl1gqVstkB', NULL, '173.252.70.11', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTmlEb25RVUhhaTRiZjZSUDI4bmo1RTZDM3NBbWI2M3piZm9BdFg3TCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147895),
('Bpxq8YjcCmziuVY4RnKsbxTvhusgclqnNA82RXmE', NULL, '173.252.95.4', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZzV2aUpFaGJhTVBnSEFEdjEwRDlPbUVQblZBajNlWnBwVXFhaGFnSSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147895),
('cPwfV3kkHDHt3xlbaQJ2pa1XZUOPNzVEAd1g6iBQ', NULL, '173.252.109.112', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicXBQd3lScDJKZE5iVFVNdUk3azd4NUdONmFSaXFjOHpLYVB6NDY4OCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147892),
('g8uIer1buzZewubBsQlXyInFEbLkAMxJFR1CehUz', NULL, '173.252.95.34', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTkxMSDIxQ3BmclVxeGZOTzZlcEp3V3lwWXlZTWlMZHg5cFl5eVNWWiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147893),
('GMlTP4h6PGFc7ORnHHepCBzaRxz7tIGQaJW3uxzv', NULL, '103.129.124.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibHRSMXRnRmpUOVJ6SHY3ak9MMG1GS0lDQXhQeXJ3RnRraTFLRUNvcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHBzOi8vc3RtYXJ5bWFnZGFsZW5lLnNpdGUuamUiO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1795153453),
('iSWEKQYpuVU16lbdDUJmsNRYg1tnefq9IF3leNFe', NULL, '57.141.20.53', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQUxoclNSUnJuZVJOR3RuVzRkcmNIalFidWhQdTNYSUVMTldFUDlnMSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTkwOiJodHRwOi8vc3RtYXJ5bWFnZGFsZW5lLnNpdGUuamUvP2ZiY2xpZD1Jd1pYaDBiZ05oWlcwQ01URUFjR1J2WmdSemNuUmpCbUZ3Y0Y5cFpBd3lOVFl5T0RFd05EQTFOVGdBQVItNExvMDN6ai1aOHV1dnNNSnVqbm5nRXJZS01ZRV92ekEwUDg1dDJNOVZjOTVseEp3VHJwZ2xCMzdNRUFfYWVtX3lMQVF4U0IwQ0lHbW9GZ2piSWRkYUEmaT0xIjtzOjU6InJvdXRlIjtzOjQ6ImhvbWUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1795147901),
('jA0Pcu7cjY2aMac7O6PP3qeA51UM1DS7KJ3Cg8k6', NULL, '173.252.95.27', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQjc0ckpqZ01jWlZndk9nTWQ4QVN4a3NUR2xtY3VhczZWY1Q5SHBCUCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147898),
('lAT69RLPedojNBeR2LzBWKe8nRTPya468Io1qDsa', NULL, '57.141.20.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibHV5Nnlaa1RXV0FzWm9QdEV6MU1oMTRxcVQ1TlRZMGJNeVNxaGZwRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTkwOiJodHRwOi8vc3RtYXJ5bWFnZGFsZW5lLnNpdGUuamUvP2ZiY2xpZD1Jd1pYaDBiZ05oWlcwQ01URUFjR1J2WmdSemNuUmpCbUZ3Y0Y5cFpBd3lOVFl5T0RFd05EQTFOVGdBQVI4VTNZejhIMGRyYmxaZnZzdDlWUEUxbi1INzFQSGoyTm5yQzg0QThwekZOQjdvOGlvak1DNWZpN3ZzdWdfYWVtX21BWTJ5Q1dwTjZFVE5Dbm9sMExYbGcmaT0xIjtzOjU6InJvdXRlIjtzOjQ6ImhvbWUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1795147900),
('OFFq0GMOKGVwEZdHwkbBMa31D4wdJSViDFFriK7c', NULL, '69.171.231.33', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidml5R1I3aDZOSDN6YWVkWkdRRjNxNmhwUDh5Nm5VUjZYQ3VMbm1qeiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147881),
('pxoshwzjFeJyOqSlehQS1a5Kd8DE1CWHPdmbPiBt', 1, '103.129.124.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiRVRjYjFETjZCaTFibTNxZ1lRVEJsdlVhSW1FZTNpNXE3andoUEdqRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTc6Imh0dHBzOi8vc3RtYXJ5bWFnZGFsZW5lLnNpdGUuamUvZGFzaGJvYXJkL2Jhbm5lcnMvY3VycmVudCI7czo1OiJyb3V0ZSI7czoyNToiZGFzaGJvYXJkLmJhbm5lcnMuY3VycmVudCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6IjgyMTQ1NzkxMGY3MGVhMDVhNmFiNTM2OTY3NzA0NzIwYTg3ODk3ZDRkOGI2OGQwOThiM2JiMGEzZTllODVmNTIiO30=', 1795153848),
('r1CtQDjiqARDHRVxaAkP8928IrPD034Pv9fd2EtR', NULL, '173.252.95.24', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiY1BIbENsQzcxQldFeTlWV2tJaEdjZlNiOGVRTXpieTVKYlpVYXZtMSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147896),
('XM8DgCDMMDO60B45B3XIfrShI9yt8r6wqSDPFKNZ', NULL, '173.252.95.17', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSGlXakpQZEJoZjBFQ1hrOERTcUlwNWN4cXpRb0lnSDVvTWFyV21nSyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9zdG1hcnltYWdkYWxlbmUuc2l0ZS5qZSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1795147897),
('ZMMJY68y22XWJvHtj9q1Mmr74doCGl6i7RyN97jy', NULL, '173.252.105.77', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUDRIbWhiMnFTbURpYkJrMHZ6SUl4aG00QXR6UFpjbXA3Y3NRQmk3QSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTkwOiJodHRwOi8vc3RtYXJ5bWFnZGFsZW5lLnNpdGUuamUvP2ZiY2xpZD1Jd1pYaDBiZ05oWlcwQ01URUFjR1J2WmdSemNuUmpCbUZ3Y0Y5cFpBd3lOVFl5T0RFd05EQTFOVGdBQVItNExvMDN6ai1aOHV1dnNNSnVqbm5nRXJZS01ZRV92ekEwUDg1dDJNOVZjOTVseEp3VHJwZ2xCMzdNRUFfYWVtX3lMQVF4U0IwQ0lHbW9GZ2piSWRkYUEmaT0xIjtzOjU6InJvdXRlIjtzOjQ6ImhvbWUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1795147933);

-- --------------------------------------------------------

--
-- Table structure for table `submission_boxes`
--

CREATE TABLE `submission_boxes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `deadline` date DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `employee_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submission_boxes`
--

INSERT INTO `submission_boxes` (`id`, `name`, `type`, `deadline`, `instructions`, `is_active`, `created_by`, `created_at`, `updated_at`, `employee_id`) VALUES
(6, 'NBI Clearance', 'NBI', '2026-11-03', 'Submit the NBI in PDF', 1, 2, '2026-11-01 23:36:31', '2026-11-01 23:36:31', NULL),
(7, 'HDMF Form', 'HDMF', '2026-11-04', 'Submit the HDMF in PDF ASAPP!!!!!', 1, 2, '2026-11-01 23:57:15', '2026-11-01 23:57:15', NULL),
(8, 'PSA', 'PSA PDF', '2026-11-04', 'Pass it now', 1, 2, '2026-11-02 23:50:24', '2026-11-02 23:50:24', NULL),
(9, 'sdfdsf', 'sdfdf', '2026-11-12', 'sfsdf', 1, 2, '2026-11-12 14:00:01', '2026-11-12 14:00:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_data_versions`
--

CREATE TABLE `system_data_versions` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `version` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_data_versions`
--

INSERT INTO `system_data_versions` (`id`, `version`, `created_at`, `updated_at`) VALUES
(1, 631, '2026-10-31 20:38:54', '2026-11-20 09:54:13');

-- --------------------------------------------------------

--
-- Table structure for table `time_corrections`
--

CREATE TABLE `time_corrections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `correction_date` date NOT NULL,
  `correction_type` varchar(255) NOT NULL,
  `incorrect_time` time DEFAULT NULL,
  `correct_time` time NOT NULL,
  `note` text NOT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `proof_original_name` varchar(255) DEFAULT NULL,
  `admin_status` varchar(255) NOT NULL DEFAULT 'Pending',
  `admin_reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `hr_status` varchar(255) NOT NULL DEFAULT 'Pending',
  `hr_reviewed_at` timestamp NULL DEFAULT NULL,
  `hr_reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'smms.administrator00@gmail.com', '2026-11-09 15:11:14', '$2y$12$9WF62gFkfsJMrw./nfp1hOO034c1zwtc8EQHZhC6.3MbTXkCN559S', NULL, '2026-10-23 23:27:09', '2026-11-03 18:25:22'),
(2, 'Human Resource', 'smms.hr00@gmail.com', '2026-11-09 15:11:14', '$2y$12$9WF62gFkfsJMrw./nfp1hOO034c1zwtc8EQHZhC6.3MbTXkCN559S', NULL, '2026-10-23 23:27:10', '2026-11-03 18:24:25'),
(4, 'Accounting Personnel', 'smms.finance.officer@gmail.com', '2026-11-09 15:11:14', '$2y$12$9WF62gFkfsJMrw./nfp1hOO034c1zwtc8EQHZhC6.3MbTXkCN559S', NULL, '2026-10-23 23:27:10', '2026-11-03 18:25:01'),
(5, 'Juan Carlos Dela Cruz', 'juan.delacruz@gmail.com', '2026-11-09 15:11:14', '$2y$12$9WF62gFkfsJMrw./nfp1hOO034c1zwtc8EQHZhC6.3MbTXkCN559S', NULL, '2026-10-23 23:27:10', '2026-10-23 23:27:10'),
(89, 'Jenmar Acantilado Alano', 'jenmaralano11@gmail.com', '2026-11-20 10:08:51', '$2y$12$9WF62gFkfsJMrw./nfp1hOO034c1zwtc8EQHZhC6.3MbTXkCN559S', NULL, '2026-11-20 10:08:31', '2026-11-20 10:08:51');

-- --------------------------------------------------------

--
-- Table structure for table `zkteco_attendances`
--

CREATE TABLE `zkteco_attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fingerprint` varchar(64) NOT NULL,
  `device` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `uid` int(10) UNSIGNED DEFAULT NULL,
  `punched_at` datetime NOT NULL,
  `timezone` varchar(64) DEFAULT NULL,
  `direction` varchar(32) NOT NULL,
  `verify_method` varchar(32) NOT NULL,
  `raw_direction` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `raw_verify` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `work_code` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `zkteco_attendances`
--

INSERT INTO `zkteco_attendances` (`id`, `fingerprint`, `device`, `user_id`, `uid`, `punched_at`, `timezone`, `direction`, `verify_method`, `raw_direction`, `raw_verify`, `work_code`, `created_at`, `updated_at`) VALUES
(67975, '6845eb756449f18e80d6ae4cbff446e99a232d9a9f5d58d684a8ae2314a615e9', '192.168.10.201', '2', NULL, '2026-11-10 14:16:01', 'Asia/Manila', 'CheckIn', 'Fingerprint', 0, 1, NULL, NULL, NULL),
(67976, '6ebc5692455132cbc0f14afb50bc259e9e16794f1053f35509350ede34ee88d7', '192.168.10.201', '1', NULL, '2026-11-10 14:16:07', 'Asia/Manila', 'CheckIn', 'Fingerprint', 0, 1, NULL, NULL, NULL),
(67977, '36df006e860d4b6c57627b76258e2b916da27a307438bcdddf4cb5e001b85ec7', '192.168.10.201', '2', NULL, '2026-11-10 14:27:05', 'Asia/Manila', 'CheckIn', 'Fingerprint', 0, 1, NULL, NULL, NULL),
(67978, 'd2fe4a0658de6b2384dea548293317864929769b5660a3f84cc97accc16a5238', '192.168.10.201', '2', NULL, '2026-11-10 14:30:36', 'Asia/Manila', 'CheckIn', 'Fingerprint', 0, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `zkteco_push_commands`
--

CREATE TABLE `zkteco_push_commands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `command` text NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `return_code` int(11) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zkteco_push_devices`
--

CREATE TABLE `zkteco_push_devices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `ip_address` varchar(45) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `applications_user_id_unique` (`user_id`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_records_employee_id_date_unique` (`employee_id`,`date`),
  ADD KEY `attendance_records_archived_date_index` (`archived`,`date`),
  ADD KEY `attendance_records_status_index` (`status`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `banners_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `banners_placement_index` (`placement`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `calendar_events_created_by_foreign` (`created_by`),
  ADD KEY `calendar_events_event_date_index` (`event_date`);

--
-- Indexes for table `dashboard_tasks`
--
ALTER TABLE `dashboard_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dashboard_tasks_user_id_task_date_index` (`user_id`,`task_date`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_documents_submission_box_id_foreign` (`submission_box_id`),
  ADD KEY `employee_documents_employee_id_foreign` (`employee_id`),
  ADD KEY `employee_documents_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `employee_documents_status_index` (`status`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_openings`
--
ALTER TABLE `job_openings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_balance_resets`
--
ALTER TABLE `leave_balance_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_balance_resets_reset_by_foreign` (`reset_by`),
  ADD KEY `leave_balance_resets_year_reset_at_index` (`year`,`reset_at`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_requests_user_id_foreign` (`user_id`),
  ADD KEY `leave_requests_decided_by_foreign` (`decided_by`),
  ADD KEY `leave_requests_admin_archived_status_index` (`admin_archived`,`status`),
  ADD KEY `leave_requests_requested_date_index` (`requested_date`),
  ADD KEY `leave_requests_hr_decided_by_foreign` (`hr_decided_by`),
  ADD KEY `leave_requests_admin_decided_by_foreign` (`admin_decided_by`);

--
-- Indexes for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_repayments_loan_request_id_paid_at_index` (`loan_request_id`,`paid_at`);

--
-- Indexes for table `loan_requests`
--
ALTER TABLE `loan_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_requests_hr_reviewed_by_foreign` (`hr_reviewed_by`),
  ADD KEY `loan_requests_accounting_reviewed_by_foreign` (`accounting_reviewed_by`),
  ADD KEY `loan_requests_admin_reviewed_by_foreign` (`admin_reviewed_by`),
  ADD KEY `loan_requests_requested_at_index` (`requested_at`),
  ADD KEY `loan_requests_status_index` (`hr_status`,`accounting_status`,`admin_status`),
  ADD KEY `loan_requests_employee_archive_index` (`employee_id`,`archived_at`),
  ADD KEY `loan_requests_staff_archived_by_foreign` (`staff_archived_by`),
  ADD KEY `loan_requests_staff_archived_at_index` (`staff_archived_at`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `non_working_days`
--
ALTER TABLE `non_working_days`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `non_working_days_date_unique` (`date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  ADD KEY `notifications_archived_at_index` (`archived_at`);

--
-- Indexes for table `offboarding_documents`
--
ALTER TABLE `offboarding_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offboarding_documents_offboarding_case_id_foreign` (`offboarding_case_id`),
  ADD KEY `offboarding_documents_reviewed_by_foreign` (`reviewed_by`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_records_period_cutoff_employee_id_unique` (`period`,`cutoff`,`employee_id`),
  ADD KEY `payroll_records_employee_id_foreign` (`employee_id`),
  ADD KEY `payroll_records_period_index` (`period`);

--
-- Indexes for table `payroll_steps`
--
ALTER TABLE `payroll_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_steps_step_no_unique` (`step_no`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payslips_employee_id_transfer_date_cutoff_unique` (`employee_id`,`transfer_date`,`cutoff`),
  ADD KEY `payslips_admin_reviewed_by_foreign` (`admin_reviewed_by`),
  ADD KEY `payslips_hr_reviewed_by_foreign` (`hr_reviewed_by`),
  ADD KEY `payslips_admin_status_index` (`admin_status`);

--
-- Indexes for table `payslip_responses`
--
ALTER TABLE `payslip_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payslip_responses_payslip_id_unique` (`payslip_id`),
  ADD KEY `payslip_responses_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `submission_boxes`
--
ALTER TABLE `submission_boxes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_boxes_created_by_foreign` (`created_by`),
  ADD KEY `submission_boxes_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `system_data_versions`
--
ALTER TABLE `system_data_versions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_corrections`
--
ALTER TABLE `time_corrections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `time_corrections_employee_id_foreign` (`employee_id`),
  ADD KEY `time_corrections_admin_reviewed_by_foreign` (`admin_reviewed_by`),
  ADD KEY `time_corrections_hr_reviewed_by_foreign` (`hr_reviewed_by`),
  ADD KEY `time_corrections_correction_date_index` (`correction_date`),
  ADD KEY `time_corrections_admin_status_index` (`admin_status`),
  ADD KEY `time_corrections_hr_status_index` (`hr_status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `zkteco_attendances`
--
ALTER TABLE `zkteco_attendances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `zkteco_attendances_fingerprint_unique` (`fingerprint`),
  ADD KEY `zkteco_attendances_user_id_punched_at_index` (`user_id`,`punched_at`),
  ADD KEY `zkteco_attendances_device_index` (`device`),
  ADD KEY `zkteco_attendances_user_id_index` (`user_id`),
  ADD KEY `zkteco_attendances_punched_at_index` (`punched_at`);

--
-- Indexes for table `zkteco_push_commands`
--
ALTER TABLE `zkteco_push_commands`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zkteco_push_commands_serial_number_status_index` (`serial_number`,`status`),
  ADD KEY `zkteco_push_commands_serial_number_index` (`serial_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dashboard_tasks`
--
ALTER TABLE `dashboard_tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_openings`
--
ALTER TABLE `job_openings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `leave_balance_resets`
--
ALTER TABLE `leave_balance_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `loan_requests`
--
ALTER TABLE `loan_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `non_working_days`
--
ALTER TABLE `non_working_days`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `offboarding_cases`
--
ALTER TABLE `offboarding_cases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offboarding_documents`
--
ALTER TABLE `offboarding_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6093;

--
-- AUTO_INCREMENT for table `payroll_steps`
--
ALTER TABLE `payroll_steps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=213;

--
-- AUTO_INCREMENT for table `payslip_responses`
--
ALTER TABLE `payslip_responses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `submission_boxes`
--
ALTER TABLE `submission_boxes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `time_corrections`
--
ALTER TABLE `time_corrections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `zkteco_attendances`
--
ALTER TABLE `zkteco_attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67979;

--
-- AUTO_INCREMENT for table `zkteco_push_commands`
--
ALTER TABLE `zkteco_push_commands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zkteco_push_devices`
--
ALTER TABLE `zkteco_push_devices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `attendance_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `banners`
--
ALTER TABLE `banners`
  ADD CONSTRAINT `banners_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `calendar_events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dashboard_tasks`
--
ALTER TABLE `dashboard_tasks`
  ADD CONSTRAINT `dashboard_tasks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_documents_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_documents_submission_box_id_foreign` FOREIGN KEY (`submission_box_id`) REFERENCES `submission_boxes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_balance_resets`
--
ALTER TABLE `leave_balance_resets`
  ADD CONSTRAINT `leave_balance_resets_reset_by_foreign` FOREIGN KEY (`reset_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_admin_decided_by_foreign` FOREIGN KEY (`admin_decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_hr_decided_by_foreign` FOREIGN KEY (`hr_decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD CONSTRAINT `loan_repayments_loan_request_id_foreign` FOREIGN KEY (`loan_request_id`) REFERENCES `loan_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_requests`
--
ALTER TABLE `loan_requests`
  ADD CONSTRAINT `loan_requests_accounting_reviewed_by_foreign` FOREIGN KEY (`accounting_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `loan_requests_admin_reviewed_by_foreign` FOREIGN KEY (`admin_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `loan_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_requests_hr_reviewed_by_foreign` FOREIGN KEY (`hr_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `loan_requests_staff_archived_by_foreign` FOREIGN KEY (`staff_archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `offboarding_documents`
--
ALTER TABLE `offboarding_documents`
  ADD CONSTRAINT `offboarding_documents_offboarding_case_id_foreign` FOREIGN KEY (`offboarding_case_id`) REFERENCES `offboarding_cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offboarding_documents_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `payroll_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `payslips_admin_reviewed_by_foreign` FOREIGN KEY (`admin_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payslips_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payslips_hr_reviewed_by_foreign` FOREIGN KEY (`hr_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payslip_responses`
--
ALTER TABLE `payslip_responses`
  ADD CONSTRAINT `payslip_responses_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payslip_responses_payslip_id_foreign` FOREIGN KEY (`payslip_id`) REFERENCES `payslips` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submission_boxes`
--
ALTER TABLE `submission_boxes`
  ADD CONSTRAINT `submission_boxes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `submission_boxes_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `time_corrections`
--
ALTER TABLE `time_corrections`
  ADD CONSTRAINT `time_corrections_admin_reviewed_by_foreign` FOREIGN KEY (`admin_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `time_corrections_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `time_corrections_hr_reviewed_by_foreign` FOREIGN KEY (`hr_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
