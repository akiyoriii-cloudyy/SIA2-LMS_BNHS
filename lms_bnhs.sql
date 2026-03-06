-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 06, 2026 at 01:48 AM
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
-- Database: `lms_bnhs`
--

-- --------------------------------------------------------

--
-- Table structure for table `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT 'mobile',
  `token_hash` varchar(64) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `instructions` text DEFAULT NULL,
  `points` decimal(8,2) NOT NULL DEFAULT 100.00,
  `due_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assignment_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `content` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `score` decimal(8,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `enrollment_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `school_week_start` date NOT NULL,
  `status` enum('present','late','absent','excused') NOT NULL DEFAULT 'present',
  `remarks` text DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
('laravel-cache-0313f9685c50f57363d3d8a1a14ad121', 'i:1;', 1772738789),
('laravel-cache-0313f9685c50f57363d3d8a1a14ad121:timer', 'i:1772738789;', 1772738789),
('laravel-cache-112bf52c1c20fe6f8e8ffa6c308fa8ac', 'i:2;', 1772738789),
('laravel-cache-112bf52c1c20fe6f8e8ffa6c308fa8ac:timer', 'i:1772738789;', 1772738789),
('laravel-cache-75e66f936fdcef7ea894f62b1b580e77', 'i:1;', 1772738838),
('laravel-cache-75e66f936fdcef7ea894f62b1b580e77:timer', 'i:1772738838;', 1772738838),
('laravel-cache-f3d2678e41b852fa25c398d1a3cd3459', 'i:1;', 1772738602),
('laravel-cache-f3d2678e41b852fa25c398d1a3cd3459:timer', 'i:1772738602;', 1772738602);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `section_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `school_year_id`, `section_id`, `subject_id`, `teacher_id`, `title`, `description`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 'Oral Communication - Grade 11', 'LMS course for Oral Communication', 1, '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(2, 1, 1, 2, 1, 'General Mathematics - Grade 11', 'LMS course for General Mathematics', 1, '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(3, 1, 1, 3, 1, 'Earth and Life Science - Grade 11', 'LMS course for Earth and Life Science', 1, '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(4, 1, 1, 4, 1, '21st Century Literature - Grade 11', 'LMS course for 21st Century Literature', 1, '2026-03-05 08:00:08', '2026-03-05 08:00:08'),
(5, 1, 1, 5, 1, 'Contemporary Philippine Arts - Grade 11', 'LMS course for Contemporary Philippine Arts', 1, '2026-03-05 08:00:08', '2026-03-05 08:00:08'),
(6, 1, 1, 6, 1, 'Media and Information Literacy - Grade 11', 'LMS course for Media and Information Literacy', 1, '2026-03-05 08:00:08', '2026-03-05 08:00:08'),
(7, 1, 1, 7, 1, 'Personal Development - Grade 11', 'LMS course for Personal Development', 1, '2026-03-05 08:00:08', '2026-03-05 08:00:08'),
(8, 1, 1, 8, 1, 'Physical Education and Health - Grade 11', 'LMS course for Physical Education and Health', 1, '2026-03-05 08:00:08', '2026-03-05 08:00:08');

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `section_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `section_id`, `school_year_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'active', '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(2, 2, 1, 1, 'active', '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(3, 3, 1, 1, 'active', '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(4, 4, 1, 1, 'active', '2026-03-04 08:26:43', '2026-03-04 08:26:43');

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
-- Table structure for table `grade_entries`
--

CREATE TABLE `grade_entries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `enrollment_id` bigint(20) UNSIGNED NOT NULL,
  `subject_assignment_id` bigint(20) UNSIGNED NOT NULL,
  `quarter` tinyint(3) UNSIGNED NOT NULL,
  `quiz` decimal(5,2) DEFAULT NULL,
  `performance_task` decimal(5,2) DEFAULT NULL,
  `assignment` decimal(5,2) DEFAULT NULL,
  `exam` decimal(5,2) DEFAULT NULL,
  `quarter_grade` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grade_entries`
--

INSERT INTO `grade_entries` (`id`, `enrollment_id`, `subject_assignment_id`, `quarter`, `quiz`, `performance_task`, `assignment`, `exam`, `quarter_grade`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, NULL, NULL, NULL, NULL, NULL, '2026-03-04 09:41:06', '2026-03-04 09:47:09'),
(2, 2, 1, 2, NULL, NULL, NULL, NULL, NULL, '2026-03-04 09:41:06', '2026-03-04 09:41:06'),
(3, 3, 1, 2, NULL, NULL, NULL, NULL, NULL, '2026-03-04 09:41:06', '2026-03-04 09:41:06'),
(4, 4, 1, 2, NULL, NULL, NULL, NULL, NULL, '2026-03-04 09:41:06', '2026-03-04 09:41:06');

-- --------------------------------------------------------

--
-- Table structure for table `guardians`
--

CREATE TABLE `guardians` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guardians`
--

INSERT INTO `guardians` (`id`, `user_id`, `first_name`, `last_name`, `phone`, `email`, `address`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Parent of Ana', 'Santos', '+639330000001', 'santos.guardian@bnhs.local', NULL, '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(2, NULL, 'Parent of Mark', 'Reyes', '+639330000002', 'reyes.guardian@bnhs.local', NULL, '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(3, NULL, 'Parent of Jessa', 'Cruz', '+639330000003', 'cruz.guardian@bnhs.local', NULL, '2026-02-26 06:16:17', '2026-02-26 06:16:17');

-- --------------------------------------------------------

--
-- Table structure for table `guardian_students`
--

CREATE TABLE `guardian_students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `guardian_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `receive_sms` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guardian_students`
--

INSERT INTO `guardian_students` (`id`, `guardian_id`, `student_id`, `relationship`, `is_primary`, `receive_sms`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Parent', 1, 1, '2026-02-26 06:16:17', '2026-03-05 08:00:08'),
(2, 2, 2, 'Parent', 1, 1, '2026-02-26 06:16:17', '2026-03-05 08:00:08'),
(3, 3, 3, 'Parent', 1, 1, '2026-02-26 06:16:17', '2026-03-05 08:00:08');

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
(4, '2026_02_16_000003_create_grading_master_tables', 1),
(5, '2026_02_16_000004_create_grading_transaction_tables', 1),
(6, '2026_02_16_000005_create_lms_attendance_tables', 1),
(7, '2026_02_16_000006_create_sessions_table', 1),
(8, '2026_02_26_000007_add_deleted_at_to_users_table', 1),
(9, '2026_02_26_000008_add_deleted_at_to_students_and_subjects_table', 2),
(10, '2026_03_03_220000_add_observed_values_to_report_cards_table', 3),
(11, '2026_03_03_230000_sync_subjects_sections_and_strands_for_shs', 4),
(12, '2026_03_03_233000_sync_sections_to_requested_grade_levels', 5),
(13, '2026_03_03_230000_add_performance_task_category_and_student_demographics', 6),
(14, '2026_03_05_090000_add_age_to_students_table', 7),
(15, '2026_03_06_080000_add_level_to_roles_table', 8),
(16, '2026_03_06_081000_create_permissions_tables', 8),
(17, '2026_03_06_090000_create_sms_logs_table_if_missing', 9);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `channel` varchar(255) NOT NULL DEFAULT 'in_app',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `sent_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
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

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'dashboard.view', 'View dashboard', '2026-03-05 08:44:22', '2026-03-05 08:44:22'),
(2, 'courses.view', 'View courses', '2026-03-05 08:44:22', '2026-03-05 08:44:22'),
(3, 'gradebook.view', 'View gradebook', '2026-03-05 08:44:22', '2026-03-05 08:44:22'),
(4, 'gradebook.edit', 'Edit grades', '2026-03-05 08:44:22', '2026-03-05 08:44:22'),
(5, 'records.manage', 'Manage student/subject records', '2026-03-05 08:44:22', '2026-03-05 08:44:22'),
(6, 'attendance.manage', 'Manage attendance records', '2026-03-05 08:44:22', '2026-03-05 08:44:22'),
(7, 'report_cards.view', 'View report cards', '2026-03-05 08:44:22', '2026-03-05 08:44:22'),
(8, 'report_cards.edit', 'Edit report card observed values', '2026-03-05 08:44:22', '2026-03-05 08:44:22'),
(9, 'sms_logs.view', 'View SMS logs', '2026-03-05 08:44:22', '2026-03-05 08:44:22'),
(10, 'settings.manage_own', 'Manage own profile/settings', '2026-03-05 08:44:22', '2026-03-05 08:44:22'),
(11, 'users.manage', 'Manage users', '2026-03-05 08:44:22', '2026-03-05 08:44:22'),
(12, 'settings.manage', 'Manage admin settings', '2026-03-05 08:44:22', '2026-03-05 08:44:22');

-- --------------------------------------------------------

--
-- Table structure for table `report_cards`
--

CREATE TABLE `report_cards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `enrollment_id` bigint(20) UNSIGNED NOT NULL,
  `general_average` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `observed_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`observed_values`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_cards`
--

INSERT INTO `report_cards` (`id`, `enrollment_id`, `general_average`, `remarks`, `created_at`, `updated_at`, `observed_values`) VALUES
(1, 1, NULL, NULL, '2026-02-26 06:21:29', '2026-02-26 06:21:29', NULL),
(2, 2, NULL, NULL, '2026-02-26 06:21:29', '2026-02-26 06:21:29', NULL),
(3, 3, NULL, NULL, '2026-02-26 06:21:29', '2026-02-26 06:21:29', NULL),
(4, 4, NULL, NULL, '2026-03-04 08:52:32', '2026-03-04 08:52:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `report_card_items`
--

CREATE TABLE `report_card_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_card_id` bigint(20) UNSIGNED NOT NULL,
  `subject_assignment_id` bigint(20) UNSIGNED NOT NULL,
  `q1` decimal(5,2) DEFAULT NULL,
  `q2` decimal(5,2) DEFAULT NULL,
  `q3` decimal(5,2) DEFAULT NULL,
  `q4` decimal(5,2) DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_card_items`
--

INSERT INTO `report_card_items` (`id`, `report_card_id`, `subject_assignment_id`, `q1`, `q2`, `q3`, `q4`, `final_grade`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL, NULL, NULL, NULL, '2026-02-26 06:21:29', '2026-03-05 10:49:03'),
(2, 1, 2, NULL, NULL, NULL, NULL, NULL, '2026-02-26 06:21:29', '2026-03-05 10:49:03'),
(3, 1, 3, NULL, NULL, NULL, NULL, NULL, '2026-02-26 06:21:29', '2026-03-05 10:49:03'),
(4, 2, 1, NULL, NULL, NULL, NULL, NULL, '2026-02-26 06:21:29', '2026-03-04 09:47:09'),
(5, 2, 2, NULL, NULL, NULL, NULL, NULL, '2026-02-26 06:21:29', '2026-03-04 09:47:09'),
(6, 2, 3, NULL, NULL, NULL, NULL, NULL, '2026-02-26 06:21:29', '2026-03-04 09:47:09'),
(7, 3, 1, NULL, NULL, NULL, NULL, NULL, '2026-02-26 06:21:29', '2026-03-04 09:47:09'),
(8, 3, 2, NULL, NULL, NULL, NULL, NULL, '2026-02-26 06:21:29', '2026-03-04 09:47:09'),
(9, 3, 3, NULL, NULL, NULL, NULL, NULL, '2026-02-26 06:21:29', '2026-03-04 09:47:09'),
(56, 4, 1, NULL, NULL, NULL, NULL, NULL, '2026-03-04 08:52:32', '2026-03-04 09:47:09'),
(57, 4, 2, NULL, NULL, NULL, NULL, NULL, '2026-03-04 08:52:32', '2026-03-04 09:47:09'),
(58, 4, 3, NULL, NULL, NULL, NULL, NULL, '2026-03-04 08:52:32', '2026-03-04 09:47:09'),
(117, 1, 4, NULL, NULL, NULL, NULL, NULL, '2026-03-05 08:03:06', '2026-03-05 10:49:03'),
(118, 1, 5, NULL, NULL, NULL, NULL, NULL, '2026-03-05 08:03:06', '2026-03-05 10:49:03'),
(119, 1, 6, NULL, NULL, NULL, NULL, NULL, '2026-03-05 08:03:06', '2026-03-05 10:49:03'),
(120, 1, 7, NULL, NULL, NULL, NULL, NULL, '2026-03-05 08:03:06', '2026-03-05 10:49:03'),
(121, 1, 8, NULL, NULL, NULL, NULL, NULL, '2026-03-05 08:03:06', '2026-03-05 10:49:03');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `level` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `level`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'School administrator', 300, '2026-02-26 06:16:17', '2026-03-05 08:44:22'),
(2, 'teacher', 'Subject teacher (editor)', 200, '2026-02-26 06:16:17', '2026-03-05 08:44:22'),
(3, 'user', 'Limited user', 100, '2026-03-05 08:44:22', '2026-03-05 08:44:22');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(1, 1, 6, NULL, NULL),
(2, 1, 2, NULL, NULL),
(3, 1, 1, NULL, NULL),
(4, 1, 4, NULL, NULL),
(5, 1, 3, NULL, NULL),
(6, 1, 5, NULL, NULL),
(7, 1, 8, NULL, NULL),
(8, 1, 7, NULL, NULL),
(9, 1, 12, NULL, NULL),
(10, 1, 10, NULL, NULL),
(11, 1, 9, NULL, NULL),
(12, 1, 11, NULL, NULL),
(13, 2, 6, NULL, NULL),
(14, 2, 2, NULL, NULL),
(15, 2, 1, NULL, NULL),
(16, 2, 4, NULL, NULL),
(17, 2, 3, NULL, NULL),
(18, 2, 5, NULL, NULL),
(19, 2, 8, NULL, NULL),
(20, 2, 7, NULL, NULL),
(21, 2, 10, NULL, NULL),
(22, 2, 9, NULL, NULL),
(23, 3, 2, NULL, NULL),
(24, 3, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '2025-2026', 1, '2026-02-26 06:16:17', '2026-02-26 06:16:17');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `grade_level` tinyint(3) UNSIGNED NOT NULL,
  `track` varchar(255) DEFAULT NULL,
  `strand` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `name`, `grade_level`, `track`, `strand`, `created_at`, `updated_at`) VALUES
(1, 'HUMSS', 11, 'Academic', 'HUMSS', '2026-03-03 06:35:44', '2026-03-03 06:58:52'),
(2, 'ABM', 11, 'Academic', 'ABM', '2026-03-03 06:35:44', '2026-03-03 06:58:52'),
(3, 'COOKERY/BPP', 11, 'TVL', 'COOKERY/BPP', '2026-03-03 06:35:44', '2026-03-03 06:58:52'),
(4, 'SMAW', 11, 'TVL', 'SMAW', '2026-03-03 06:35:44', '2026-03-03 06:58:52'),
(5, 'FOP', 11, 'TVL', 'FOP', '2026-03-03 06:58:52', '2026-03-03 06:58:52'),
(6, 'CSS', 11, 'TVL', 'CSS', '2026-03-03 06:58:52', '2026-03-03 06:58:52'),
(7, 'CSS', 12, 'TVL', 'CSS', '2026-03-03 06:58:52', '2026-03-03 06:58:52'),
(8, 'ABM', 12, 'Academic', 'ABM', '2026-03-03 06:58:52', '2026-03-03 06:58:52'),
(9, 'SMAW', 12, 'TVL', 'SMAW', '2026-03-03 06:58:52', '2026-03-03 06:58:52'),
(10, 'FBS', 12, 'TVL', 'FBS', '2026-03-03 06:58:52', '2026-03-03 06:58:52'),
(11, 'HUMS', 12, 'Academic', 'HUMS', '2026-03-03 06:58:52', '2026-03-03 06:58:52'),
(12, 'FOP', 12, 'TVL', 'FOP', '2026-03-03 06:58:52', '2026-03-03 06:58:52');

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
('5Tmg2MeEZnV0vWnsx3zbteUcixtAQ3eviLOnlKKF', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.7920', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNHpJRmtMa0RlMmYzYnhCNE1MVFQ2OVZqam5uRUF0ZmxJU3JUQTE3byI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3QvTE1TX0JOSFMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1772733389),
('cm7uWlnwGQyoV2o5dIlB7INuR3jdPUmRwOSGW0t2', NULL, '::1', 'curl/8.18.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidFAzcnFNYllmd3owY2lndVVuc0FQZVZ1TDJCOFNYaU9BYlFhSEhiayI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyNToiaHR0cDovL2xvY2FsaG9zdC9MTVNfQk5IUyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1772733411),
('dmlsSjfD5r7ozbqKI4yhwCXBGIPRltzfWAQIamkM', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.7920', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNmloNEdLMUtaY01CTkcxUllJOXF3YWQ1TFB4V21CNkRKN0pZTXVDYiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3QvTE1TX0JOSFMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1772731842),
('I2TcEZH5vwwWbostUXySYNUtAJr8RkQMlf6ZiaNw', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.7920', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidnFRcldZZGpXTVFrendWalBZaDJNaUZXTklBRTMyZGhNTDRFazV2eiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3QvTE1TX0JOSFMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1772731788),
('ONu9DRLIT0vsDPAprTJzLU9zYdA0JA3Qg2JXRYRG', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.7920', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVWhXdER2dmIzUnFFYjQ1dGJpaEViNkRWVDBrM1pIc1ZtZVRCanBUaCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozODoiaHR0cDovL2xvY2FsaG9zdC9MTVNfQk5IUy9yZXBvcnQtY2FyZHMiO31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czozODoiaHR0cDovL2xvY2FsaG9zdC9MTVNfQk5IUy9yZXBvcnQtY2FyZHMiO3M6NToicm91dGUiO3M6MTg6InJlcG9ydC1jYXJkcy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1772733395),
('vqz9Frndyddobgq9AL8KGCP6GgrqO8OUl0e7MQE1', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibU5LRnFiYzFleUlRTTVMSHlpUzE2YWNqZFMwRFBiOFhMMVhOSlh5eSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3QvTE1TX0JOSFMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO319', 1772738786),
('vybakyEBGl2y6jRo9fysXff3fvnNKQKLlYt3Ut9f', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.7920', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiako4UHFxMzN3aG9DWVBhWnFxYWNta2VRSXhFODFLTTgybGdsclVXVSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3QvTE1TX0JOSFMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1772731813);

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `guardian_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `enrollment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `week_start` date NOT NULL,
  `absences_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `phone_number` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `notification_key` varchar(255) NOT NULL,
  `provider` varchar(255) NOT NULL DEFAULT 'twilio',
  `provider_message_id` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'queued',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lrn` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `age` tinyint(3) UNSIGNED DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `ethnicity` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `lrn`, `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `age`, `date_of_birth`, `address`, `ethnicity`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, '13131385948', 'Ana', NULL, 'Santos', NULL, 'Female', 18, '2008-08-12', 'Purok 3, Bawing, General Santos City', 'Blaan', '2026-02-26 06:16:17', '2026-03-05 08:00:08', NULL),
(2, NULL, '13131369585', 'Mark', NULL, 'Reyes', NULL, 'Male', 17, '2008-03-21', 'Purok 5, Bawing, General Santos City', 'Islam', '2026-02-26 06:16:17', '2026-03-05 08:00:08', NULL),
(3, NULL, '13131325625', 'Jessa', NULL, 'Cruz', NULL, 'Female', 18, '2008-11-02', 'Purok 1, Bawing, General Santos City', 'Blaan', '2026-02-26 06:16:17', '2026-03-05 08:00:08', NULL),
(4, NULL, '13131358948', 'Marky', NULL, 'Padilla', NULL, 'Male', 20, '2026-03-07', 'Yumang Street, Mabuhay', 'Blaan', '2026-03-04 08:26:43', '2026-03-04 08:51:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(20) NOT NULL DEFAULT 'core',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `code`, `title`, `category`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'ORALCOMM', 'Oral Communication in Context', 'core', '2026-03-03 06:35:44', '2026-03-04 09:15:19', NULL),
(2, 'KOMPAN', 'Komunikasyon at Pananaliksik', 'core', '2026-03-03 06:35:44', '2026-03-03 06:35:44', NULL),
(3, 'ELS', 'Earth and Life Science', 'core', '2026-03-03 06:35:44', '2026-03-03 06:35:44', NULL),
(4, '21CLIT', '21st Century Literature', 'core', '2026-03-03 06:35:44', '2026-03-04 09:15:01', NULL),
(5, 'CPAR', 'Contemporary Philippine Arts', 'applied', '2026-03-03 06:35:44', '2026-03-05 08:00:08', NULL),
(6, 'MIL', 'Media and Information Literacy', 'applied', '2026-03-03 06:35:44', '2026-03-05 08:00:08', NULL),
(7, 'PERDEV', 'Personal Development', 'applied', '2026-03-03 06:35:44', '2026-03-05 08:00:08', NULL),
(8, 'PEH', 'Physical Education and Health', 'core', '2026-03-03 06:35:44', '2026-03-03 06:35:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subject_assignments`
--

CREATE TABLE `subject_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `section_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subject_assignments`
--

INSERT INTO `subject_assignments` (`id`, `teacher_id`, `section_id`, `subject_id`, `school_year_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(2, 1, 1, 2, 1, '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(3, 1, 1, 3, 1, '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(4, 1, 1, 4, 1, '2026-03-05 08:00:08', '2026-03-05 08:00:08'),
(5, 1, 1, 5, 1, '2026-03-05 08:00:08', '2026-03-05 08:00:08'),
(6, 1, 1, 6, 1, '2026-03-05 08:00:08', '2026-03-05 08:00:08'),
(7, 1, 1, 7, 1, '2026-03-05 08:00:08', '2026-03-05 08:00:08'),
(8, 1, 1, 8, 1, '2026-03-05 08:00:08', '2026-03-05 08:00:08');

-- --------------------------------------------------------

--
-- Table structure for table `subject_final_grades`
--

CREATE TABLE `subject_final_grades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `enrollment_id` bigint(20) UNSIGNED NOT NULL,
  `subject_assignment_id` bigint(20) UNSIGNED NOT NULL,
  `q1` decimal(5,2) DEFAULT NULL,
  `q2` decimal(5,2) DEFAULT NULL,
  `q3` decimal(5,2) DEFAULT NULL,
  `q4` decimal(5,2) DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subject_final_grades`
--

INSERT INTO `subject_final_grades` (`id`, `enrollment_id`, `subject_assignment_id`, `q1`, `q2`, `q3`, `q4`, `final_grade`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL, NULL, NULL, NULL, '2026-03-04 09:41:06', '2026-03-04 09:47:09'),
(2, 2, 1, NULL, NULL, NULL, NULL, NULL, '2026-03-04 09:41:06', '2026-03-04 09:41:06'),
(3, 3, 1, NULL, NULL, NULL, NULL, NULL, '2026-03-04 09:41:06', '2026-03-04 09:41:06'),
(4, 4, 1, NULL, NULL, NULL, NULL, NULL, '2026-03-04 09:41:06', '2026-03-04 09:41:06');

-- --------------------------------------------------------

--
-- Table structure for table `sync_batches`
--

CREATE TABLE `sync_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `batch_uuid` char(36) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `status` varchar(255) NOT NULL DEFAULT 'processed',
  `error_message` text DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `first_name`, `last_name`, `created_at`, `updated_at`) VALUES
(1, 2, 'Adviser', 'One', '2026-02-26 06:16:17', '2026-02-26 06:16:17'),
(2, 3, 'Marky', 'Padilla', '2026-03-05 11:22:50', '2026-03-05 11:22:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'School Admin', 'admin@bnhs.local', '+639111111111', NULL, '$2y$12$bim4AAXAU4y6vlaGqgYyA.SslouNjukmm2ln.AFB7ml9rIM4rJ3Ha', '1HslCeW6el2JUr3shGVcW5scbxR3NwcnsYXdpzYZIbqnm0WDwmMdC0WEXxXQ', '2026-02-26 06:16:17', '2026-02-26 06:16:17', NULL),
(2, 'Adviser One', 'teacher@bnhs.local', '+639222222222', NULL, '$2y$12$MEj6WHBTosR2Ul.VbTKMBurcIj9r1FZZwQy5GQBMtzmSLU2xpfttW', 'l9nATYnNqLIbyX2gajMaBWNURDygtWbBpJ4Z3JJ1loikORQPxuqGdKH6L2hc', '2026-02-26 06:16:17', '2026-02-26 06:16:17', NULL),
(3, 'Markypadilla', 'mansuetomarky@gmail.com', '09943621529', NULL, '$2y$12$ql/c07rzaZUo3lzi4ZVhIO.YsZ4eC3OLsMHRRd4qZQDpM4N9DlbN6', 'WW4UKffYmXUY6vLRaGE21sCRh1faRmh8IsWsNDYxPa8rJYuG3L2M4Q6MIAYj', '2026-03-05 11:22:50', '2026-03-05 11:25:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL),
(2, 2, 2, NULL, NULL),
(3, 3, 2, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_tokens_token_hash_unique` (`token_hash`),
  ADD KEY `api_tokens_user_id_foreign` (`user_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignments_course_id_foreign` (`course_id`);

--
-- Indexes for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assignment_submissions_assignment_id_student_id_unique` (`assignment_id`,`student_id`),
  ADD KEY `assignment_submissions_student_id_foreign` (`student_id`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_records_enrollment_id_attendance_date_unique` (`enrollment_id`,`attendance_date`),
  ADD KEY `attendance_records_course_id_foreign` (`course_id`),
  ADD KEY `attendance_records_recorded_by_foreign` (`recorded_by`),
  ADD KEY `attendance_records_enrollment_id_school_week_start_status_index` (`enrollment_id`,`school_week_start`,`status`);

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
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `courses_school_year_id_foreign` (`school_year_id`),
  ADD KEY `courses_section_id_foreign` (`section_id`),
  ADD KEY `courses_subject_id_foreign` (`subject_id`),
  ADD KEY `courses_teacher_id_foreign` (`teacher_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_materials_course_id_foreign` (`course_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_year` (`student_id`,`school_year_id`),
  ADD KEY `enrollments_section_id_foreign` (`section_id`),
  ADD KEY `enrollments_school_year_id_foreign` (`school_year_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `grade_entries`
--
ALTER TABLE `grade_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_grade_quarter` (`enrollment_id`,`subject_assignment_id`,`quarter`),
  ADD KEY `grade_entries_subject_assignment_id_foreign` (`subject_assignment_id`);

--
-- Indexes for table `guardians`
--
ALTER TABLE `guardians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guardians_user_id_foreign` (`user_id`);

--
-- Indexes for table `guardian_students`
--
ALTER TABLE `guardian_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `guardian_students_guardian_id_student_id_unique` (`guardian_id`,`student_id`),
  ADD KEY `guardian_students_student_id_foreign` (`student_id`);

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
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_foreign` (`user_id`),
  ADD KEY `notifications_student_id_foreign` (`student_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`);

--
-- Indexes for table `report_cards`
--
ALTER TABLE `report_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_cards_enrollment_id_foreign` (`enrollment_id`);

--
-- Indexes for table `report_card_items`
--
ALTER TABLE `report_card_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_report_card_item` (`report_card_id`,`subject_assignment_id`),
  ADD KEY `report_card_items_subject_assignment_id_foreign` (`subject_assignment_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  ADD KEY `role_permissions_permission_id_foreign` (`permission_id`);

--
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_years_name_unique` (`name`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sms_logs_notification_key_unique` (`notification_key`),
  ADD KEY `sms_logs_guardian_id_foreign` (`guardian_id`),
  ADD KEY `sms_logs_student_id_foreign` (`student_id`),
  ADD KEY `sms_logs_enrollment_id_week_start_index` (`enrollment_id`,`week_start`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `students_lrn_unique` (`lrn`),
  ADD KEY `students_user_id_foreign` (`user_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subjects_code_unique` (`code`);

--
-- Indexes for table `subject_assignments`
--
ALTER TABLE `subject_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_subject_assignment` (`section_id`,`subject_id`,`school_year_id`),
  ADD KEY `subject_assignments_teacher_id_foreign` (`teacher_id`),
  ADD KEY `subject_assignments_subject_id_foreign` (`subject_id`),
  ADD KEY `subject_assignments_school_year_id_foreign` (`school_year_id`);

--
-- Indexes for table `subject_final_grades`
--
ALTER TABLE `subject_final_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_subject_final_grade` (`enrollment_id`,`subject_assignment_id`),
  ADD KEY `subject_final_grades_subject_assignment_id_foreign` (`subject_assignment_id`);

--
-- Indexes for table `sync_batches`
--
ALTER TABLE `sync_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sync_batches_batch_uuid_unique` (`batch_uuid`),
  ADD KEY `sync_batches_user_id_foreign` (`user_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teachers_user_id_foreign` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_roles_user_id_role_id_unique` (`user_id`,`role_id`),
  ADD KEY `user_roles_role_id_foreign` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_entries`
--
ALTER TABLE `grade_entries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `guardians`
--
ALTER TABLE `guardians`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `guardian_students`
--
ALTER TABLE `guardian_students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `report_cards`
--
ALTER TABLE `report_cards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `report_card_items`
--
ALTER TABLE `report_card_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=405;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subject_assignments`
--
ALTER TABLE `subject_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subject_final_grades`
--
ALTER TABLE `subject_final_grades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sync_batches`
--
ALTER TABLE `sync_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD CONSTRAINT `api_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `assignment_submissions_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `attendance_records_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_records_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_records_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `courses_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `courses_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `courses_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grade_entries`
--
ALTER TABLE `grade_entries`
  ADD CONSTRAINT `grade_entries_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grade_entries_subject_assignment_id_foreign` FOREIGN KEY (`subject_assignment_id`) REFERENCES `subject_assignments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `guardians`
--
ALTER TABLE `guardians`
  ADD CONSTRAINT `guardians_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `guardian_students`
--
ALTER TABLE `guardian_students`
  ADD CONSTRAINT `guardian_students_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `guardian_students_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `report_cards`
--
ALTER TABLE `report_cards`
  ADD CONSTRAINT `report_cards_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_card_items`
--
ALTER TABLE `report_card_items`
  ADD CONSTRAINT `report_card_items_report_card_id_foreign` FOREIGN KEY (`report_card_id`) REFERENCES `report_cards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_card_items_subject_assignment_id_foreign` FOREIGN KEY (`subject_assignment_id`) REFERENCES `subject_assignments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD CONSTRAINT `sms_logs_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sms_logs_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sms_logs_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `subject_assignments`
--
ALTER TABLE `subject_assignments`
  ADD CONSTRAINT `subject_assignments_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_assignments_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_assignments_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_assignments_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `subject_final_grades`
--
ALTER TABLE `subject_final_grades`
  ADD CONSTRAINT `subject_final_grades_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_final_grades_subject_assignment_id_foreign` FOREIGN KEY (`subject_assignment_id`) REFERENCES `subject_assignments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sync_batches`
--
ALTER TABLE `sync_batches`
  ADD CONSTRAINT `sync_batches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
