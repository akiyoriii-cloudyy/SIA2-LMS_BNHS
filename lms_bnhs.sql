-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2026 at 09:55 AM
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
('laravel-cache-112bf52c1c20fe6f8e8ffa6c308fa8ac', 'i:4;', 1775719192),
('laravel-cache-112bf52c1c20fe6f8e8ffa6c308fa8ac:timer', 'i:1775719192;', 1775719192),
('laravel-cache-418553eb189e46e040337fbb781553d8', 'i:1;', 1775718707),
('laravel-cache-418553eb189e46e040337fbb781553d8:timer', 'i:1775718707;', 1775718707),
('laravel-cache-50f6326cb2f983670f3996182642ffa5', 'i:1;', 1775716167),
('laravel-cache-50f6326cb2f983670f3996182642ffa5:timer', 'i:1775716167;', 1775716167),
('laravel-cache-560c76dc4f552f9d51f3572e3fddf5ae', 'i:3;', 1775718706),
('laravel-cache-560c76dc4f552f9d51f3572e3fddf5ae:timer', 'i:1775718706;', 1775718706),
('laravel-cache-633036d07871e06ac31f7482e3ef1e0a', 'i:1;', 1775719209),
('laravel-cache-633036d07871e06ac31f7482e3ef1e0a:timer', 'i:1775719209;', 1775719209),
('laravel-cache-827de4e08d050b92ec7112d42fecbf0d', 'i:2;', 1775716988),
('laravel-cache-827de4e08d050b92ec7112d42fecbf0d:timer', 'i:1775716988;', 1775716988),
('laravel-cache-d7676ee5000fbb659268c4f0aa360b41', 'i:3;', 1775719192),
('laravel-cache-d7676ee5000fbb659268c4f0aa360b41:timer', 'i:1775719192;', 1775719192),
('laravel-cache-d9a1227071d7fc181a972b16e5f77e8e', 'i:1;', 1775718708),
('laravel-cache-d9a1227071d7fc181a972b16e5f77e8e:timer', 'i:1775718708;', 1775718708),
('laravel-cache-f3d2678e41b852fa25c398d1a3cd3459', 'i:1;', 1775718746),
('laravel-cache-f3d2678e41b852fa25c398d1a3cd3459:timer', 'i:1775718746;', 1775718746),
('laravel-cache-fe3353e1542eae021e7e4fd8412c64b3', 'i:1;', 1775718706),
('laravel-cache-fe3353e1542eae021e7e4fd8412c64b3:timer', 'i:1775718706;', 1775718706);

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
(1, 1, 1, 1, 1, 'Oral Communication in Context - Grade 11', 'LMS course for Oral Communication in Context', 1, '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(2, 1, 1, 2, 1, 'Komunikasyon at Pananaliksik - Grade 11', 'LMS course for Komunikasyon at Pananaliksik', 1, '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(3, 1, 1, 3, 1, '21st Century Literature - Grade 11', 'LMS course for 21st Century Literature', 1, '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(4, 1, 1, 4, 1, 'Contemporary Philippine Arts - Grade 11', 'LMS course for Contemporary Philippine Arts', 1, '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(5, 1, 1, 5, 1, 'Media and Information Literacy - Grade 11', 'LMS course for Media and Information Literacy', 1, '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(6, 1, 1, 6, 1, 'Personal Development - Grade 11', 'LMS course for Personal Development', 1, '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(7, 1, 1, 7, 1, 'Earth and Life Science - Grade 11', 'LMS course for Earth and Life Science', 1, '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(8, 1, 1, 8, 1, 'Physical Education and Health - Grade 11', 'LMS course for Physical Education and Health', 1, '2026-04-08 18:34:31', '2026-04-08 18:34:31');

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
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
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
(1, 1, 1, 1, 'active', '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(2, 2, 1, 1, 'active', '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(3, 3, 1, 1, 'active', '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(4, 4, 13, 1, 'active', '2026-04-08 18:38:48', '2026-04-08 18:38:48');

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
(1, 4, 9, 1, 15.00, 50.00, 50.00, 60.00, 43.50, '2026-04-08 18:53:54', '2026-04-08 19:01:27');

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
(1, NULL, 'Parent of Ana', 'Santos', '+639330000001', 'santos.guardian@bnhs.local', NULL, '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(2, NULL, 'Parent of Mark', 'Reyes', '+639330000002', 'reyes.guardian@bnhs.local', NULL, '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(3, NULL, 'Parent of Jessa', 'Cruz', '+639330000003', 'cruz.guardian@bnhs.local', NULL, '2026-04-08 18:34:31', '2026-04-08 18:34:31');

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
(1, 1, 1, 'Parent', 1, 1, '2026-04-08 18:34:31', '2026-04-08 19:16:10'),
(2, 2, 2, 'Parent', 1, 1, '2026-04-08 18:34:31', '2026-04-08 19:16:10'),
(3, 3, 3, 'Parent', 1, 1, '2026-04-08 18:34:31', '2026-04-08 19:16:10');

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
-- Table structure for table `jwt_revoked_tokens`
--

CREATE TABLE `jwt_revoked_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `jti` varchar(64) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `revoked_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jwt_revoked_tokens`
--

INSERT INTO `jwt_revoked_tokens` (`id`, `user_id`, `jti`, `expires_at`, `revoked_at`, `created_at`, `updated_at`) VALUES
(1, 2, 'b4b0178165a068f2ba10aabb2419e979', '2026-05-09 05:06:44', '2026-04-09 05:08:41', '2026-04-08 21:08:41', '2026-04-08 21:08:41'),
(3, 20, '824861dabd911284d5292de18c1dd0e7', '2026-05-09 07:10:48', '2026-04-09 07:10:48', '2026-04-08 23:10:48', '2026-04-08 23:10:48'),
(4, 21, 'd2aab7158d92d158f73244945b26adb4', '2026-05-09 07:10:49', '2026-04-09 07:10:49', '2026-04-08 23:10:49', '2026-04-08 23:10:49'),
(5, 1, 'ed97bdb0774efc4f42740d684a70bf7b', '2026-05-09 07:14:15', '2026-04-09 07:14:15', '2026-04-08 23:14:15', '2026-04-08 23:14:15');

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
(9, '2026_02_26_000008_add_deleted_at_to_students_and_subjects_table', 1),
(10, '2026_03_03_220000_add_observed_values_to_report_cards_table', 1),
(11, '2026_03_03_230000_add_performance_task_category_and_student_demographics', 1),
(12, '2026_03_03_230000_sync_subjects_sections_and_strands_for_shs', 1),
(13, '2026_03_03_233000_sync_sections_to_requested_grade_levels', 1),
(14, '2026_03_05_090000_add_age_to_students_table', 1),
(15, '2026_03_06_080000_add_level_to_roles_table', 1),
(16, '2026_03_06_081000_create_permissions_tables', 1),
(17, '2026_03_06_090000_create_sms_logs_table_if_missing', 1),
(18, '2026_03_27_100000_create_user_profiles_table', 1),
(19, '2026_03_27_101000_add_rfid_uid_to_students_table', 1),
(20, '2026_03_27_120000_create_admin_management_tables', 1),
(21, '2026_03_27_130000_create_strands_and_teacher_subjects_tables', 1),
(22, '2026_04_09_100000_rename_teacher_role_to_adviser_add_subject_teacher', 1),
(23, '2026_04_10_000000_ensure_adviser_subject_teacher_roles', 1),
(24, '2026_04_09_200000_normalize_teacher_subjects_table_for_term_and_section_scope', 2),
(25, '2026_04_09_210000_add_mfa_fields_to_users_table', 3),
(26, '2026_04_10_010000_create_jwt_revoked_tokens_table', 4);

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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `student_id`, `type`, `channel`, `title`, `message`, `meta`, `sent_at`, `read_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'assignment', 'in_app', 'Teacher assignment', 'You have successfully assigned Subject Teacher Test to teach «21st Century Literature».', '{\"actor_id\":1,\"actor_name\":\"School Admin\",\"subject_id\":3,\"teacher_id\":2}', '2026-04-08 18:50:31', NULL, '2026-04-08 18:50:31', '2026-04-08 18:50:31'),
(2, 3, NULL, 'assignment', 'in_app', 'New subject assignment', 'You have been assigned to teach «21st Century Literature».', '{\"actor_id\":1,\"actor_name\":\"School Admin\",\"subject_id\":3,\"teacher_id\":2}', '2026-04-08 18:50:31', NULL, '2026-04-08 18:50:31', '2026-04-08 18:50:31'),
(3, 2, NULL, 'grade_sync', 'in_app', 'Subject grades updated', 'Subject Teacher Test saved 3 grade row(s) for 21st Century Literature (21CLIT), Grade 11 - HUMSS, S1 Q1 (2025-2026).', '{\"by_user_id\":3,\"subject_assignment_id\":3,\"school_year_id\":1,\"section_id\":1,\"subject_id\":3,\"quarter\":1,\"saved_rows\":3}', '2026-04-08 19:05:10', NULL, '2026-04-08 19:05:10', '2026-04-08 19:05:10'),
(4, 1, NULL, 'user_management', 'in_app', 'User deleted', 'User account for Subject Teacher Test was deactivated (soft deleted).', '{\"actor_id\":1,\"actor_name\":\"School Admin\",\"user_id\":3}', '2026-04-08 19:17:00', NULL, '2026-04-08 19:17:00', '2026-04-08 19:17:00'),
(5, 1, NULL, 'user_management', 'in_app', 'User updated', 'User account for Subject Teacher One (subject.teacher1@bnhs.local) was updated; role is now subject_teacher.', '{\"actor_id\":1,\"actor_name\":\"School Admin\",\"user_id\":20}', '2026-04-08 22:06:20', NULL, '2026-04-08 22:06:20', '2026-04-08 22:06:20');

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
(1, 'dashboard.view', 'View dashboard', '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(2, 'courses.view', 'View courses', '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(3, 'gradebook.view', 'View gradebook', '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(4, 'gradebook.edit', 'Edit grades', '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(5, 'records.manage', 'Manage student/subject records', '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(6, 'attendance.manage', 'Manage attendance records', '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(7, 'report_cards.view', 'View report cards', '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(8, 'report_cards.edit', 'Edit report card observed values', '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(9, 'sms_logs.view', 'View SMS logs', '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(10, 'settings.manage_own', 'Manage own profile/settings', '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(11, 'users.manage', 'Manage users', '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(12, 'settings.manage', 'Manage admin settings', '2026-04-08 18:34:30', '2026-04-08 18:34:30');

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
(1, 4, NULL, NULL, '2026-04-08 18:53:54', '2026-04-08 18:53:54', NULL),
(2, 1, NULL, NULL, '2026-04-08 18:54:16', '2026-04-08 18:54:16', NULL),
(3, 2, NULL, NULL, '2026-04-08 18:54:16', '2026-04-08 18:54:16', NULL),
(4, 3, NULL, NULL, '2026-04-08 18:54:16', '2026-04-08 18:54:16', NULL);

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
(1, 1, 9, 43.50, NULL, NULL, NULL, NULL, '2026-04-08 18:53:54', '2026-04-08 19:01:27');

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
(1, 'subject_teacher', 'Subject teacher (grade encoding for assigned subjects)', 200, '2026-04-08 18:33:14', '2026-04-08 18:34:30'),
(2, 'admin', 'School administrator', 300, '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(3, 'adviser', 'Class adviser / homeroom teacher', 200, '2026-04-08 18:34:30', '2026-04-08 18:34:30'),
(4, 'user', 'Limited user', 100, '2026-04-08 18:34:30', '2026-04-08 18:34:30');

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
(1, 2, 6, NULL, NULL),
(2, 2, 2, NULL, NULL),
(3, 2, 1, NULL, NULL),
(4, 2, 4, NULL, NULL),
(5, 2, 3, NULL, NULL),
(6, 2, 5, NULL, NULL),
(7, 2, 8, NULL, NULL),
(8, 2, 7, NULL, NULL),
(9, 2, 12, NULL, NULL),
(10, 2, 10, NULL, NULL),
(11, 2, 9, NULL, NULL),
(12, 2, 11, NULL, NULL),
(13, 3, 6, NULL, NULL),
(14, 3, 2, NULL, NULL),
(15, 3, 1, NULL, NULL),
(16, 3, 4, NULL, NULL),
(17, 3, 3, NULL, NULL),
(18, 3, 5, NULL, NULL),
(19, 3, 8, NULL, NULL),
(20, 3, 7, NULL, NULL),
(21, 3, 10, NULL, NULL),
(22, 3, 9, NULL, NULL),
(23, 1, 1, NULL, NULL),
(24, 1, 4, NULL, NULL),
(25, 1, 3, NULL, NULL),
(26, 1, 10, NULL, NULL),
(27, 4, 2, NULL, NULL),
(28, 4, 1, NULL, NULL);

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
(1, '2025-2026', 1, '2026-04-08 18:34:31', '2026-04-08 18:34:31');

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
(1, 'HUMSS', 11, 'Academic', 'HUMSS', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(2, 'ABM', 11, 'Academic', 'ABM', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(3, 'COOKERY/BPP', 11, 'TVL', 'COOKERY/BPP', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(4, 'SMAW', 11, 'TVL', 'SMAW', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(5, 'FOP', 11, 'TVL', 'FOP', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(6, 'CSS', 11, 'TVL', 'CSS', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(7, 'CSS', 12, 'TVL', 'CSS', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(8, 'ABM', 12, 'Academic', 'ABM', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(9, 'SMAW', 12, 'TVL', 'SMAW', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(10, 'FBS', 12, 'TVL', 'FBS', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(11, 'HUMS', 12, 'Academic', 'HUMS', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(12, 'FOP', 12, 'TVL', 'FOP', '2026-04-08 18:33:13', '2026-04-08 18:33:13'),
(13, 'ST TEST', 11, 'Academic', 'HUMSS', '2026-04-08 18:38:41', '2026-04-08 18:38:41');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
('baO1NeMgHqDgx11rfQG1KYCFo0SnS5UHoCbySNBF', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.8115', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicGJ4NHdZNUhqcWdKZEdIZXdJdTEyQ0JYOHQ3UXBkZFpIOEYzU1BaOSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3QvTE1TX0JOSFMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1775713552),
('ctqr91752BdNEKoeBy6qtHj0M8MrKwdPekscceDu', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRHpob3pqWjZQZ25ZYnljenlzV05Ec3psMWhoYmwyUlcwR1l6ZXdsUCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3QvTE1TX0JOSFMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO319', 1775719164),
('elg74UrSAj2DR9BKPMk5i9zBn4kePTv2PdL5pBLo', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.8115', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRFlNbERWTHZkNHlCVllOVmJlWGN6U21SbkxKZ3BWb3hZR295a2s5YiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3QvTE1TX0JOSFMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1775712266),
('tdougoNoJNWdPTno7Y6j0CYSBkzBat7ZzZQINvgE', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.8115', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTldlWnhlS0RGS2pnaHpZV1psRTd4MHJNaXRsYmJvT0k5anY1U2doeCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3QvTE1TX0JOSFMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1775711280);

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
-- Table structure for table `strands`
--

CREATE TABLE `strands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
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
  `rfid_uid` varchar(100) DEFAULT NULL,
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

INSERT INTO `students` (`id`, `user_id`, `lrn`, `rfid_uid`, `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `age`, `date_of_birth`, `address`, `ethnicity`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, NULL, NULL, 'Ana', NULL, 'Santos', NULL, 'Female', NULL, '2008-08-12', 'Purok 3, Bawing, General Santos City', 'Blaan', '2026-04-08 18:34:31', '2026-04-08 18:34:31', NULL),
(2, NULL, NULL, NULL, 'Mark', NULL, 'Reyes', NULL, 'Male', NULL, '2008-03-21', 'Purok 5, Bawing, General Santos City', 'Islam', '2026-04-08 18:34:31', '2026-04-08 18:34:31', NULL),
(3, NULL, NULL, NULL, 'Jessa', NULL, 'Cruz', NULL, 'Female', NULL, '2008-11-02', 'Purok 1, Bawing, General Santos City', 'Blaan', '2026-04-08 18:34:31', '2026-04-08 18:34:31', NULL),
(4, NULL, '13131379999', NULL, 'Demo', NULL, 'Student', NULL, 'Male', NULL, NULL, NULL, NULL, '2026-04-08 18:38:48', '2026-04-08 18:38:48', NULL);

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
(1, 'ORALCOMM', 'Oral Communication in Context', 'core', '2026-04-08 18:33:13', '2026-04-08 18:33:13', NULL),
(2, 'KOMPAN', 'Komunikasyon at Pananaliksik', 'core', '2026-04-08 18:33:13', '2026-04-08 18:33:13', NULL),
(3, '21CLIT', '21st Century Literature', 'core', '2026-04-08 18:33:13', '2026-04-08 18:33:13', NULL),
(4, 'CPAR', 'Contemporary Philippine Arts', 'applied', '2026-04-08 18:33:13', '2026-04-08 18:34:31', NULL),
(5, 'MIL', 'Media and Information Literacy', 'applied', '2026-04-08 18:33:13', '2026-04-08 18:34:31', NULL),
(6, 'PERDEV', 'Personal Development', 'applied', '2026-04-08 18:33:13', '2026-04-08 18:34:31', NULL),
(7, 'ELS', 'Earth and Life Science', 'core', '2026-04-08 18:33:13', '2026-04-08 18:33:13', NULL),
(8, 'PEH', 'Physical Education and Health', 'core', '2026-04-08 18:33:13', '2026-04-08 18:33:13', NULL),
(9, 'STDEMO101', 'Subject Teacher Demo', 'core', '2026-04-08 18:38:41', '2026-04-08 18:38:41', NULL);

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
(9, NULL, 13, 9, 1, '2026-04-08 18:38:41', '2026-04-08 19:04:21'),
(10, 19, 1, 1, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(11, 19, 1, 2, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(12, 19, 1, 3, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(13, 19, 1, 4, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(14, 20, 1, 5, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(15, 20, 1, 6, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(16, 20, 1, 7, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(17, 1, 1, 8, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05');

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
(1, 4, 9, 43.50, NULL, NULL, NULL, NULL, '2026-04-08 18:53:54', '2026-04-08 18:53:54');

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
(1, 2, 'Adviser', 'One', '2026-04-08 18:34:31', '2026-04-08 18:34:31'),
(2, 3, 'Subject', 'Teacher', '2026-04-08 18:38:33', '2026-04-08 18:38:33'),
(3, 4, 'Subject', 'Teacher ORALCOMM', '2026-04-08 19:10:13', '2026-04-08 19:10:13'),
(4, 5, 'Subject', 'Teacher KOMPAN', '2026-04-08 19:10:14', '2026-04-08 19:10:14'),
(5, 6, 'Subject', 'Teacher 21CLIT', '2026-04-08 19:10:14', '2026-04-08 19:10:14'),
(6, 7, 'Subject', 'Teacher CPAR', '2026-04-08 19:10:14', '2026-04-08 19:10:14'),
(7, 8, 'Subject', 'Teacher MIL', '2026-04-08 19:10:14', '2026-04-08 19:10:14'),
(8, 9, 'Subject', 'Teacher PERDEV', '2026-04-08 19:10:15', '2026-04-08 19:10:15'),
(9, 10, 'Subject', 'Teacher ELS', '2026-04-08 19:10:15', '2026-04-08 19:10:15'),
(10, 11, 'Subject', 'Teacher PEH', '2026-04-08 19:10:15', '2026-04-08 19:10:15'),
(19, 20, 'Subject', 'Teacher One', '2026-04-08 21:57:04', '2026-04-08 21:57:04'),
(20, 21, 'Subject', 'Teacher Two', '2026-04-08 21:57:05', '2026-04-08 21:57:05');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED DEFAULT NULL,
  `section_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`id`, `teacher_id`, `subject_id`, `school_year_id`, `section_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 2, 9, NULL, NULL, 1, '2026-04-08 18:38:41', '2026-04-08 18:38:41'),
(2, 2, 3, NULL, NULL, 1, '2026-04-08 18:50:31', '2026-04-08 18:50:31'),
(11, 1, 1, NULL, NULL, 1, '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(12, 2, 2, NULL, NULL, 1, '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(13, 2, 4, NULL, NULL, 1, '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(14, 2, 5, NULL, NULL, 1, '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(15, 2, 6, NULL, NULL, 1, '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(16, 2, 7, NULL, NULL, 1, '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(17, 2, 8, NULL, NULL, 1, '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(18, 19, 1, 1, 1, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(19, 19, 2, 1, 1, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(20, 19, 3, 1, 1, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(21, 19, 4, 1, 1, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(22, 20, 5, 1, 1, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(23, 20, 6, 1, 1, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(24, 20, 7, 1, 1, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05'),
(25, 1, 8, 1, 1, 1, '2026-04-08 21:57:05', '2026-04-08 21:57:05');

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `semester_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `term_number` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `mfa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `mfa_secret` text DEFAULT NULL,
  `mfa_recovery_codes` longtext DEFAULT NULL,
  `mfa_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `email_verified_at`, `password`, `mfa_enabled`, `mfa_secret`, `mfa_recovery_codes`, `mfa_confirmed_at`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'School Admin', 'admin@bnhs.local', '+639111111111', NULL, '$2y$12$C1H9ADHurNIxYgK3u/Io9u95uGtWnweITLXZOR9ujkEapabIfVZhS', 1, 'eyJpdiI6InRyUjluODFnQk14S25rZGx1U3U2NVE9PSIsInZhbHVlIjoiODJCZmFlUHNlekZ1akVQdnEyajdIVTh3eGd4TGNiNlJmNCs1MkJ1WmlaNDhNUnkxS1NocmcwbENicTA1OXJ5biIsIm1hYyI6Ijk3ZTdlYjFlNmFlZTMxMTg3NGRlNDA4NWZjYWU2NDhkYTU0MjQzNzg4NDAxYmU2ZWVhYTMwZDIyNzgzY2ViYjAiLCJ0YWciOiIifQ==', 'eyJpdiI6Ik1ydEl0RnBid21Kd1BjUXhtQlpOWWc9PSIsInZhbHVlIjoiQ2h4WVNhRllKWFRvOVZ6ZUdSZFlCSUp3YVkxNEFrTmwvaWZKc3NPc0NiVVovQ2hyc1JMQmRJZjN1Tm9BbTkyNXJtc1dFcHdWRWJTSENINW8vRVI2dzRHc0x1U1dNUnRCYy9mY0hhcnJya3B4UndaNkdoVEZYcm5SYUk5UnJOZWFteWFocm1RUXp6cERnQ0xNWTlHeHYzaTlhQkpBRk1vTEZ5cEZNVHN1ZkFrUUhVNjMrNlE3TzVvOGVrbzZua3pHYWQ4VWRsSGN5R2taTFpqVHBmNzd2TzVNMG5VLzRqWGt5TmlIdm5lVWwrbFZuaGZIVmFpNU5xeGJZVDNxb0RCM1QwN3JOZ285M2FwSXZMN1E4dmQyWUY5VVpnbk1KeTlBZUpSQmsxK0ZMRXNuc0pQWmMycXpLQlhaS0dtZ3kyMHdKMTd3Rkg3OER0emFJcytmZlhiTzFncmNIb1kwblMvY0txQzVMVnN2L2Q2NU1hY0c1MXJnT0UvblpJZ2k5S0ZUdWJLUVFRTjdRcTNuWUFGRUxUcWFXcExCYzZTaGJ6WDYyRUcvWjg5dURaN0FBNVVuTjF2WkRXTW9jQXVodUpFb1BGZjdCVmlnanRlNEZ5bThaaEJjcWQ2QTE3TU1rYmFKa0NBQ1d4RkJBVVlsWmlGZ3JmZElSalVkeVFzWDJZTTVDbThINlNlS045SWlWR1p3aytjL3F1c2cyNnVkd0VoM3JPK2g0eWJFdkJEbURVejlubkNpWUxiNjQvQ3FuazM1UzlRWkZUU2MzNmlzaXFxVnoxWjBkbmhlVlk4RmxDZGovaitoV2FHKzdzeW9DcEErNE12dFNiWDNzVFBDZlFoLzFUWU9jNndHOEg0MkZ0WlMwTFpmYmFlT2g3cTFBNHBoa3g0NUtJZEIyQkk9IiwibWFjIjoiMGMwYWQxNWQ1MzhlYmVlOGM2MDQxMjIwYWQ4YjI3NDZjMjI3YjY4Zjc2YWNkMDU1NWZkN2NmNjUxZDA1N2E4MCIsInRhZyI6IiJ9', '2026-04-08 22:30:52', 'HBNy9yloONusWUVowYOYQGQ3dooE9GKMM4Lo3TtbsQpImwSqW84ARtGbtlFG', '2026-04-08 18:34:31', '2026-04-08 22:30:52', NULL),
(2, 'Adviser One', 'adviser@bnhs.local', '+639222222222', NULL, '$2y$12$BHry.mKUsTqncPwaGXNx0.PDDwDXxdt5yXfiX0ILLqEg8B0M71t.O', 1, 'eyJpdiI6Ikk3TG9DcEJlT0ZKdXp2bzlQWEt4Z2c9PSIsInZhbHVlIjoiaDNmMDZkVVUva0J3bEc2QWtOZVkvamhFMThCRjF3QXBiOFRIM1pKc3hocmw4UDlMT1hMam9FSWxVNy80eWNpMSIsIm1hYyI6IjlhNzRhNTAyY2ZiZmY5ZGNjNDJlNGNmNDc3MWIzNDNjMDUyZDEwNTdmZmFjODMzYTUyZmYyMjNkZGExYWE3Y2MiLCJ0YWciOiIifQ==', 'eyJpdiI6IlJBZlBqb05Ndm5TSnhsMGdVVjJsbkE9PSIsInZhbHVlIjoiOFl6S0crVlBRMHNPQ3hlWDF3cVpvdFZ1MUlzUlBtRnkxdDZZSzhWejEySjZZdU5QbE84TTYyRTBXak5VQkcxZFY3L2k1TTVJcnpucXphYnhrNlpvQ3FnN3FlOHIxNWJZZ05YdnNQOStZN1RTaXMwT0J0V0pGaWVySVdMMVJrVEJFeEVOUmlqcThLdXRmWFNzbEFrRk94aXVNeVhXSTk2WVRJZHRxdjA0Q0EvMDdJSTIxNnlYbDB3ZUV0VU81czVpd0tnWVVESW5JQTdpZW9lZXA4d3JFK0U3UmpWT2xzRVdTWXp2ZHd3L2NTMGkxWXgxTVNSb2JvTHlVdEx3YTFBY0dUcnVMMDRmVFFqQ1pVdmJOalNXSXZOL25KUEM0d1lkdDlON0lNQlN3UHVJS0d4Vys1SjFJR1JsSzNiZ1EyQ01CVU4wMi9mcTlacTh6OVNwWitNWTZtYmZKdzh1S3JQSWxpVXhtc1MxN3ZhUGNOTHVoaGIxckVLYU9wTDZiL3BkYmJNcEdpb3dDWWR2aVdzb1dSc0RsdC9NK2VXMHVRQWVLQWZIZUtPTmdybE8yVnFUZjFjYzhpSWdHVFlaTjdBR2R2aFJCTUEvOWU1RlNKdmZ3cm5iaVo3ck5LWFVRVlJMWU5yZGNxNEUvUElwWFhxREFVNGFkT1NuUXNmYmQydVlRUElCVjg5YnpqaFI0VnN0eE5pR3FTR0t4TE5UOEoxdi9qVFpMM2F6RUFTUW9yNFdaVCtjRmVFSlZ3RFVjdHVpRVMvMkdlbmg3clFYRld4ZjhidXBKbDRaQVc0VFMzVHZYV1duOE80dDhpbmFsMXFLWVo5cE1BYW5uS2dUUHVUOFloeXlaUXBCQkcxbGVFbG8wSjBvZ1ZnSTFlMUxSRDgzbTBpUUVwQkJVWHphWTE5TUxkOC9vaVZJWVhld2FaeXkiLCJtYWMiOiI3NjMxZGU4MWZmOThkMDBjOGFkZTdhODVkYjJmMGY2NzI0YjZhNTVhNGZlNjY3NzlkZjQ0NjAxMmQwZDI4MDY5IiwidGFnIjoiIn0=', '2026-04-08 22:42:28', 'Dstuk6o3nAfyiEeX6YKtaZ0jJD9bFwFOyCsykcm9Bok619ipaocEEbNlzX6h', '2026-04-08 18:34:31', '2026-04-08 22:42:28', NULL),
(3, 'Subject Teacher Test', 'subjectteacher.test@bnhs.local', '+639333333333', NULL, '$2y$12$osJe2zSXHlIQTqtdkDLfXeLhN5/PRVtCa05vq4bmSllHf9/I/N18K', 0, NULL, NULL, NULL, 'hRcbaNdvgyrcHcF94lIdNjanKdp3WLv64pnLHcuktaaU4fEWLEzZaFQDdx12', '2026-04-08 18:38:21', '2026-04-08 19:17:00', '2026-04-08 19:17:00'),
(4, 'ST ORALCOMM', 'st.oralcomm@bnhs.local', '+6393000000001', NULL, '$2y$12$Coa5ehSn6bZ/4IXK4GXp3OcJZ6bdwg9K7eeFolT64BF987pkiD5BG', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:10:13', '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(5, 'ST KOMPAN', 'st.kompan@bnhs.local', '+6393000000002', NULL, '$2y$12$Sh7AyWjm6fBd8MP0.1shpOXhJoo8t0QMYZcREf/T0Bc1kE7b.8fmu', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:10:14', '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(6, 'ST 21CLIT', 'st.21clit@bnhs.local', '+6393000000003', NULL, '$2y$12$bb3vr23yppiGuIOU55PXL.qYuyjTM1T/e23u9uGCe7qKhc3bZBZe6', 0, NULL, NULL, NULL, 'hmTSsd852DeXt5SBRk8PfZS6PALC3XNk2QlyLXukSIyG45ENKxAjaoWxcc0h', '2026-04-08 19:10:14', '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(7, 'ST CPAR', 'st.cpar@bnhs.local', '+6393000000004', NULL, '$2y$12$BlGrs40pUJwKWXSJ2dnn/esBJpOtst/LLEXif/1sFuqUzfZhfSWly', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:10:14', '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(8, 'ST MIL', 'st.mil@bnhs.local', '+6393000000005', NULL, '$2y$12$2TZYGKmZyzW8KO6aWwvy7.lwpkJA024PgrWXVweHcK6MY6OlfT4nu', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:10:14', '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(9, 'ST PERDEV', 'st.perdev@bnhs.local', '+6393000000006', NULL, '$2y$12$.EMzQoti4Zhz7vC5AnWmJuuOZZuU8YBt6xCUxE8.Vs0J2pyJ8Otre', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:10:15', '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(10, 'ST ELS', 'st.els@bnhs.local', '+6393000000007', NULL, '$2y$12$EoztceHHGUUltFnsN/XGve.xd.i16JgPQ621zo9KiTrGFy8nwV4y.', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:10:15', '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(11, 'ST PEH', 'st.peh@bnhs.local', '+6393000000008', NULL, '$2y$12$6Zib14JQNGFe6XZ8uZgLW.d0LvQ31E0dRLPx2UGiZkPUK3SsQbLym', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:10:15', '2026-04-08 19:13:05', '2026-04-08 19:13:05'),
(12, 'ORALCOMM Teacher', 'oralcomm.teacher@bnhs.local', '+639334208767', NULL, '$2y$12$KbcC8BuSO9wKQnxbxC5BXOrDVlVNRgthOyWEd1Mne7xKMmAJcTeHS', 0, NULL, NULL, NULL, 'yIPcbM5KVwfp9DKpAEBqeQlxTKAHna0YIWUBbMtkTZ46U8mhpLKZgKVbDD67', '2026-04-08 19:16:09', '2026-04-08 22:02:42', '2026-04-08 22:02:42'),
(13, 'KOMPAN Teacher', 'kompan.teacher@bnhs.local', '+639330561133', NULL, '$2y$12$eYk04sy62N6uMiRtwRi5S.6O.wOBLZ5E4WF0sHM6k3djs6fgWJ926', 0, NULL, NULL, NULL, '8yZKJ7fjhfIdyM9jAezqFNZ78Nwqdvq6WEDzou6c2RKq9KSCFIAwSeeEfXFr', '2026-04-08 19:16:09', '2026-04-08 22:02:42', '2026-04-08 22:02:42'),
(14, '21CLIT Teacher', '21clit.teacher@bnhs.local', '+639339026389', NULL, '$2y$12$itGaFX3N/OCvouLa4q4RXuT1nRZ76bHI9RcHytD1A2FDfJfoJbHpa', 0, NULL, NULL, NULL, 'dnOcDoZy62xrYVL0jKy8ITYmRh2ybTUG4dAMMQyreuYCQqxy03AeUxYESwFS', '2026-04-08 19:16:09', '2026-04-08 22:02:42', '2026-04-08 22:02:42'),
(15, 'CPAR Teacher', 'cpar.teacher@bnhs.local', '+639331553907', NULL, '$2y$12$WHzu5T.ACSp2Wu4BTyrjtu1WIXdOFs2Vs6jcipZwUbEmNATS.n3h6', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:16:10', '2026-04-08 22:02:42', '2026-04-08 22:02:42'),
(16, 'MIL Teacher', 'mil.teacher@bnhs.local', '+639334036599', NULL, '$2y$12$rwvKPw6jYllsUnOafuKWBOii/GZy5dSYRwnBcUe6cSEGL6lTjoZ0m', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:16:10', '2026-04-08 22:02:42', '2026-04-08 22:02:42'),
(17, 'PERDEV Teacher', 'perdev.teacher@bnhs.local', '+639331759359', NULL, '$2y$12$7VunfsmHv/kzX9YiWZEjiOV2A6miGdnHINGn1JQmRF2hgyzEZmPJG', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:16:10', '2026-04-08 22:02:42', '2026-04-08 22:02:42'),
(18, 'ELS Teacher', 'els.teacher@bnhs.local', '+639334109526', NULL, '$2y$12$83mtB6dDf.a/i7O0dsdU9OGj6WbQoStP9TxGPsw.6RHijx1dMiFzS', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:16:10', '2026-04-08 22:02:42', '2026-04-08 22:02:42'),
(19, 'PEH Teacher', 'peh.teacher@bnhs.local', '+639339207650', NULL, '$2y$12$a.i3WB6c328PB1iSeTDmMuPWGWfRQqr13/oEQRB96qd2RVj9zxlAi', 0, NULL, NULL, NULL, NULL, '2026-04-08 19:16:10', '2026-04-08 22:02:42', '2026-04-08 22:02:42'),
(20, 'Subject Teacher One', 'subject.teacher1@bnhs.local', NULL, NULL, '$2y$12$5RL9p0fNot3pypB6Vc9cf.Co2Bv.2HObyrjleILpzHzhawAyst.36', 1, 'eyJpdiI6IitDbmdUeDJEK1ZWcDA2ZnRPRXBwWUE9PSIsInZhbHVlIjoiRmdZWTlYREZXSzJQTnlrTnQ4Tk1Tdm12aU1EN2xNZ2FJeEZQNUgyWWxkVW92UmFBTkN0aFVaMTBxYWQ4WUJRaiIsIm1hYyI6ImYzYjMzNmExNGEyNWZkNTcxMWRiOGFkNGU1YmQ0NWViNWZmZmY0OWIwZWI2YTRjNWNiOGM5NzRmZjVlMTBhZDAiLCJ0YWciOiIifQ==', 'eyJpdiI6InFCblpsUHZYQnJuQUUvY3hPaGFqckE9PSIsInZhbHVlIjoiRHEvL3pJbzJQNHU3aWowT2xnbVpaZWJIMm8zd1ZjSjA2MjFKSzgvai9RVmoxeHBsY0NIalZHWU1vdlN0aCtvZlg1OHZaNEZFd2syL0t1L0d3UzA0QktyZjcwNDd0Q3pGamYrTmZBZmFNVmU4b3k0aGF2VkZyc0NVNUU2RDdmZzM1ZVh3ZnVsM2kvK2s3YkZwdmd0R2JsbTlMSHV0TWpDOFlRQjk2VHdSSUwrdE1Id3JpTHZ1OVpmK1VXSUdVRUlEdzRWQldnd282dGovYTViOVU5eEt0VUhxQWNRcUM4ZUNOR000RkpDd2YySVp0U1U5YUd0OUlKNVRmT3RNaW5hbGZDUnhJaldIQ0hORGtkTDlqcmlEc1lPb0x0YW5FM2dzcktxQXpXcE9hS3F0NDhCVEdLZW1hTGtLa01TNEw1NWtEMzNKek1Za3k1alF0ZjVEV0ZTQXNPN1ZQUzgxclFpMVZJRGlzR0VVR09NNWFId3JPWUFMNUgwRFlSZjl2WE1sd0dYMytFNHV4MEZOT1hUdERtcjBMTE9GY1BzWXdLd3RkK296ZzFySGo3V3FKeGprTGUrYlNFSDF4dzNsc2pOa3pscUlOMFZQdjB2M2IxN0h5TXNkTUZmcno2U3NzYWV4bTU3czVBckY2VitvdU01K2FmVEE0NXpwME1NUkFvWVVhTkNrRlhQdGV1ajNRbk84Ni9tcjdhSWFqOEh3Q2x2dzRCaFVUVDlNQlE4UlV6aVdwRmw3UHM3M3A3Nm4rTk1sTnV3M1p0YlptL3RjWUswSnJzelFVN0tGVS9wZDM5Tnc1OWh0MFZKMnlnRVFJT0o4VmhYRjUvd200eVZTanRhQjIwTHc4TUh5Tld6WGFIY2FRa0hmTnBveVVPYzZ0N05UZTEyb3RZaVBZYk09IiwibWFjIjoiMTQ1MjIwNDAzNDA5Y2ZhYjYxZjQ1MWRiY2U1YjQyN2ZjODZlNjk2MWFiZjUyZjQ4ZTg1ZDU0Mzc3ODlkZjFiMiIsInRhZyI6IiJ9', '2026-04-08 22:26:42', 'mZkipOm2q0loJGythlvFkvVIIifEnRvQe9bCrJQ1kUUIcPceNI9QeYXVbycR', '2026-04-08 21:57:04', '2026-04-08 22:26:42', NULL),
(21, 'Subject Teacher Two', 'subject.teacher2@bnhs.local', NULL, NULL, '$2y$12$IOirT/Ws8GQAH.j5ndTiKelvNk9RNFqBZ9dWkm8f14jRLFLAeZdpm', 1, 'eyJpdiI6InI2RUVOaUZPV0RIYU53RFUzZi9uR1E9PSIsInZhbHVlIjoiak1UVGt2THJHVll0ZGFKRmJQV0dmU1NVZmdhVnNBb1hXY2JHY0tnVTJzT3NxSGt5TTRYaStjMzMxcVB0VlRTUyIsIm1hYyI6IjM3Y2Y2N2NhNTVhYmQ0ZTgxYTY0MDJkNzVkM2ZjMjMwYjA1NDMwOGM1MDA5MDc5NjJlOWYyZmY0YzgwYWNiYWIiLCJ0YWciOiIifQ==', 'eyJpdiI6IitQWTFZUmZtRlJ1SGc5MlJqVUoyNWc9PSIsInZhbHVlIjoieEc4VVkzY0JXQVNVbVFIR0d1cFR5TmZNNHd3V3pVTkdURFZueURNVS8wdlk1YU9HME05Z1RkdWRRN0Jpa3luWGh1TjM3SGxsNGU0ckVvSmtSMVFNbitWYTgyRkZQdmltTDArNE91WFN4b3BqYTBJY05CMzlKYzZENVR3d0FybTJkSWRha05WMmQ2eEhBaFE0KzN4Y2JaeURNK3Jja0tsQktKNlp6dkdKTFQ3QjNKL0l4Q0d0WjdOeElxUHM2QXVLY0NlbnpTY2h5RmoyRndETFQyaVJnRG96QmZiVGZlQzFQUHhXTGZ0dUhXdEUxKzNzZjdhRXF5ZW9yRTUzVU10VGUxT29hbkEzb3pKWHBOOW96S20xQmxoTC9RUlZnWTlORTdOaTQrcTU4Mnlwa0xnZVhtcnptN3NXNEZLbDFJdjBMZnZzRVJEUSt6c0RmVUVrZFAxdk5FVTEvQS9mSjdXNU1DY0RQNmFVRXBHYW5rS1A3RERnNVZPb1JTS0hRZXJ4TE1QdVZ4ZjJQMWFscjZPK244RXpHY3RFcTYrWlVYRDFzS1YzZ1J6YVJiWTNGT1g5elNYdEpydHpaZzAxN2hsemtDc1VyMUFzTkJ4a3MwaDdnMC9YSjM5NThDSWhjb1ZQWldRTWtja0dGUVBRVEUrM2owa1RudVh6Z3dnWis2WWkxM1ZNZUNqMXdLa1RXRy9CT2RSWUIzQ0NzZmRER3NhTHJxNHN1VnpYWUZQU1h2N1hscndQb1Z4NGhXenlZRld5KzVkMkhwYTBRL0UyOUNiaURMYXh5M1lxcHkwQjR6Nk43OFlUaWtHZUtLZ01oTmY3UkNKcmpEc1M5V1FRdXU4aDFxY3REMm1CaVQzQXZUTTExQktqUjR4bHozdlQ3N2piU215Wk0vL0ZBbms9IiwibWFjIjoiOTU1NjAzMGQ3ZjM1ZGI2ZTY5YTc1NTc3YTNjNDMyMTEyMTg0NzVjN2Q2NjU4MDAwZGE1MzM2YWZkNDMxODg4NyIsInRhZyI6IiJ9', '2026-04-08 22:27:40', 'sN7zJZa8POpcjSyPCwIyTdaI3AN2bFj5wftLoAtn2MbHaQ8gjKpP34w1IPlZ', '2026-04-08 21:57:05', '2026-04-08 22:27:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `created_at`, `updated_at`) VALUES
(1, 20, 'Subject', NULL, 'Teacher One', NULL, '2026-04-08 22:06:20', '2026-04-08 22:06:20');

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
(1, 1, 2, NULL, NULL),
(2, 2, 3, NULL, NULL),
(3, 3, 1, NULL, NULL),
(4, 4, 1, NULL, NULL),
(5, 5, 1, NULL, NULL),
(6, 6, 1, NULL, NULL),
(7, 7, 1, NULL, NULL),
(8, 8, 1, NULL, NULL),
(9, 9, 1, NULL, NULL),
(10, 10, 1, NULL, NULL),
(11, 11, 1, NULL, NULL),
(20, 20, 1, NULL, NULL),
(21, 21, 1, NULL, NULL);

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
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_code_unique` (`code`);

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
-- Indexes for table `jwt_revoked_tokens`
--
ALTER TABLE `jwt_revoked_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jwt_revoked_tokens_jti_unique` (`jti`),
  ADD KEY `jwt_revoked_tokens_user_id_revoked_at_index` (`user_id`,`revoked_at`);

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
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `semesters_name_unique` (`name`);

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
  ADD KEY `sms_logs_enrollment_id_foreign` (`enrollment_id`);

--
-- Indexes for table `strands`
--
ALTER TABLE `strands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `strands_code_unique` (`code`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `students_lrn_unique` (`lrn`),
  ADD UNIQUE KEY `students_rfid_uid_unique` (`rfid_uid`),
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
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_subjects_teacher_id_subject_id_unique` (`teacher_id`,`subject_id`),
  ADD UNIQUE KEY `uniq_teacher_subject_scoped` (`teacher_id`,`subject_id`,`school_year_id`,`section_id`),
  ADD KEY `teacher_subjects_subject_id_foreign` (`subject_id`),
  ADD KEY `teacher_subjects_school_year_id_foreign` (`school_year_id`),
  ADD KEY `teacher_subjects_section_id_foreign` (`section_id`);

--
-- Indexes for table `terms`
--
ALTER TABLE `terms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_term_per_sem_sy` (`semester_id`,`school_year_id`,`term_number`),
  ADD KEY `terms_school_year_id_foreign` (`school_year_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_profiles_user_id_unique` (`user_id`);

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
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
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
-- AUTO_INCREMENT for table `jwt_revoked_tokens`
--
ALTER TABLE `jwt_revoked_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `strands`
--
ALTER TABLE `strands`
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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `subject_assignments`
--
ALTER TABLE `subject_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
-- Constraints for table `jwt_revoked_tokens`
--
ALTER TABLE `jwt_revoked_tokens`
  ADD CONSTRAINT `jwt_revoked_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teacher_subjects_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teacher_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `terms`
--
ALTER TABLE `terms`
  ADD CONSTRAINT `terms_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `terms_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
