-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 29, 2025 at 06:19 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hemotracka`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `donor_id` bigint(20) UNSIGNED NOT NULL,
  `organization_id` bigint(20) UNSIGNED NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('Scheduled','Confirmed','Completed','Cancelled','No-Show') NOT NULL DEFAULT 'Scheduled',
  `donation_type` enum('Whole Blood','Plasma','Platelets','Double Red Cells') NOT NULL DEFAULT 'Whole Blood',
  `notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blood_requests`
--

CREATE TABLE `blood_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `organization_id` bigint(20) UNSIGNED NOT NULL,
  `blood_group` varchar(5) NOT NULL,
  `units_needed` int(11) NOT NULL,
  `patient_name` varchar(191) DEFAULT NULL,
  `hospital_unit` varchar(191) DEFAULT NULL,
  `source_type` varchar(191) NOT NULL DEFAULT 'Hospital',
  `type` enum('Emergent','Bulk','Routine') NOT NULL DEFAULT 'Routine',
  `bone_marrow_type` varchar(191) DEFAULT NULL,
  `platelets_type` varchar(191) DEFAULT NULL,
  `urgency_level` enum('Critical','High','Normal') NOT NULL DEFAULT 'Normal',
  `needed_by` datetime NOT NULL,
  `status` enum('Pending','Approved','Sourcing','In Transit','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `product_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `card_charge` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blood_requests`
--

INSERT INTO `blood_requests` (`id`, `organization_id`, `blood_group`, `units_needed`, `patient_name`, `hospital_unit`, `source_type`, `type`, `bone_marrow_type`, `platelets_type`, `urgency_level`, `needed_by`, `status`, `product_fee`, `shipping_fee`, `card_charge`, `total_amount`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 5, 'A-', 7, 'Patient 333', 'ICU', 'Hospital', 'Routine', NULL, NULL, 'Critical', '2025-12-22 10:39:10', 'Cancelled', 0.00, 0.00, 0.00, 0.00, 'Sample request for patient needing units.', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(2, 5, 'A-', 10, 'Patient 697', 'Ward A', 'Hospital', 'Routine', NULL, NULL, 'Critical', '2025-12-22 11:39:10', 'Pending', 0.00, 0.00, 0.00, 0.00, 'Sample request for patient needing units.', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(3, 5, 'O-', 2, 'Patient 909', 'Surgery', 'Hospital', 'Emergent', NULL, NULL, 'Normal', '2025-12-23 07:39:10', 'Completed', 0.00, 0.00, 0.00, 0.00, 'Sample request for patient needing units.', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(4, 5, 'B+', 5, 'Patient 123', 'Ward A', 'Hospital', 'Emergent', NULL, NULL, 'Normal', '2025-12-23 01:39:10', 'Pending', 0.00, 0.00, 0.00, 0.00, 'Sample request for patient needing units.', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(5, 5, 'O+', 8, 'Patient 904', 'Ward B', 'Hospital', 'Bulk', NULL, NULL, 'High', '2025-12-23 08:39:10', 'Cancelled', 0.00, 0.00, 0.00, 0.00, 'Sample request for patient needing units.', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(6, 5, 'O+', 3, 'Patient 336', 'Ward B', 'Hospital', 'Emergent', NULL, NULL, 'High', '2025-12-21 21:39:10', 'Cancelled', 0.00, 0.00, 0.00, 0.00, 'Sample request for patient needing units.', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(7, 5, 'AB-', 1, 'Patient 627', 'Ward B', 'Hospital', 'Emergent', NULL, NULL, 'High', '2025-12-21 21:39:10', 'Approved', 0.00, 0.00, 0.00, 0.00, 'Sample request for patient needing units.', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(8, 5, 'O+', 1, 'Patient 700', 'Emergency', 'Hospital', 'Emergent', NULL, NULL, 'Critical', '2025-12-22 09:39:10', 'Pending', 0.00, 0.00, 0.00, 0.00, 'Sample request for patient needing units.', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(9, 5, 'O-', 2, 'Patient 225', 'Ward A', 'Hospital', 'Emergent', NULL, NULL, 'High', '2025-12-23 03:39:10', 'Pending', 0.00, 0.00, 0.00, 0.00, 'Sample request for patient needing units.', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(10, 5, 'B+', 3, 'Patient 642', 'Ward A', 'Hospital', 'Emergent', NULL, NULL, 'Critical', '2025-12-22 18:39:10', 'Cancelled', 0.00, 0.00, 0.00, 0.00, 'Sample request for patient needing units.', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `blood_request_id` bigint(20) UNSIGNED NOT NULL,
  `rider_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pickup_location` text NOT NULL,
  `dropoff_location` text NOT NULL,
  `pickup_time` datetime DEFAULT NULL,
  `delivery_time` datetime DEFAULT NULL,
  `receiver_confirmed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'Pending',
  `status_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_history`)),
  `tracking_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `donor_id` bigint(20) UNSIGNED NOT NULL,
  `organization_id` bigint(20) UNSIGNED NOT NULL,
  `blood_group` varchar(5) NOT NULL,
  `units` int(11) NOT NULL DEFAULT 1,
  `platelets_type` varchar(191) DEFAULT NULL,
  `donation_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `status` enum('Pending','Screened','Stored','Discarded','Used') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `donor_id`, `organization_id`, `blood_group`, `units`, `platelets_type`, `donation_date`, `notes`, `doctor_notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'B+', 1, NULL, '2025-12-21', NULL, NULL, 'Stored', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(2, 2, 1, 'B+', 1, NULL, '2025-12-21', NULL, NULL, 'Stored', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(3, 3, 1, 'A+', 1, NULL, '2025-12-21', NULL, NULL, 'Stored', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(4, 4, 1, 'B+', 1, NULL, '2025-12-21', NULL, NULL, 'Stored', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(5, 5, 1, 'AB+', 1, NULL, '2025-12-21', NULL, NULL, 'Stored', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(6, 6, 7, 'A-', 2, 'Single Donor', '2025-12-21', NULL, 'Health check normal. Donation smooth.', 'Stored', '2025-12-21 07:39:11', '2025-12-21 07:39:11'),
(7, 7, 7, 'B+', 2, 'Single Donor', '2025-12-21', NULL, 'Health check normal. Donation smooth.', 'Stored', '2025-12-21 07:39:11', '2025-12-21 07:39:11'),
(8, 8, 7, 'O+', 2, 'Single Donor', '2025-12-21', NULL, 'Health check normal. Donation smooth.', 'Stored', '2025-12-21 07:39:11', '2025-12-21 07:39:11');

-- --------------------------------------------------------

--
-- Table structure for table `donors`
--

CREATE TABLE `donors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `organization_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_name` varchar(191) NOT NULL,
  `last_name` varchar(191) NOT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `genotype` varchar(10) DEFAULT NULL,
  `height` varchar(191) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `last_donation_date` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Eligible','Permanently Deferral','Temporary Deferral') NOT NULL DEFAULT 'Eligible',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donors`
--

INSERT INTO `donors` (`id`, `user_id`, `organization_id`, `first_name`, `last_name`, `blood_group`, `genotype`, `height`, `date_of_birth`, `last_donation_date`, `address`, `phone`, `notes`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 2, 1, 'Donor', 'Seed0', 'B+', 'AA', NULL, '1990-01-01', NULL, NULL, '08165952325', NULL, 'Eligible', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(2, 3, 1, 'Donor', 'Seed1', 'B+', 'AA', NULL, '1990-01-01', NULL, NULL, '08197280012', NULL, 'Eligible', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(3, 4, 1, 'Donor', 'Seed2', 'A+', 'AA', NULL, '1990-01-01', NULL, NULL, '08164602519', NULL, 'Eligible', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(4, 5, 1, 'Donor', 'Seed3', 'B+', 'AA', NULL, '1990-01-01', NULL, NULL, '08192150242', NULL, 'Eligible', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(5, 6, 1, 'Donor', 'Seed4', 'AB+', 'AA', NULL, '1990-01-01', NULL, NULL, '08116060768', NULL, 'Eligible', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(6, NULL, 7, 'Abayomi', 'Ayodele', 'A-', 'AA', '177cm', '1979-12-21', NULL, NULL, '08088804139', NULL, 'Eligible', '2025-12-21 07:39:11', '2025-12-21 07:39:11', NULL),
(7, NULL, 7, 'Caleb', 'Oko Jumbo', 'B+', 'AS', '180cm', '2007-12-21', NULL, NULL, '08066743014', NULL, 'Eligible', '2025-12-21 07:39:11', '2025-12-21 07:39:11', NULL),
(8, NULL, 7, 'Matthew', 'Prince', 'O+', 'AA', '175cm', '1989-12-21', NULL, NULL, '08020702038', NULL, 'Eligible', '2025-12-21 07:39:11', '2025-12-21 07:39:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `donor_badges`
--

CREATE TABLE `donor_badges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(191) DEFAULT NULL,
  `color` varchar(191) NOT NULL DEFAULT '#3B82F6',
  `criteria_type` enum('donation_count','units_donated','consecutive_donations','first_donation','referral_count','blood_type_rare') NOT NULL,
  `criteria_value` int(11) NOT NULL DEFAULT 1,
  `points` int(11) NOT NULL DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donor_badge_donor`
--

CREATE TABLE `donor_badge_donor` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `donor_id` bigint(20) UNSIGNED NOT NULL,
  `donor_badge_id` bigint(20) UNSIGNED NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `target_type` varchar(191) NOT NULL,
  `target_id` bigint(20) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `organization_id` bigint(20) UNSIGNED NOT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `type` enum('Whole Blood','RBC','PLT','FFP','Cryo') NOT NULL DEFAULT 'Whole Blood',
  `units_in_stock` int(11) NOT NULL DEFAULT 0,
  `threshold` int(11) NOT NULL DEFAULT 10,
  `location` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `organization_id`, `blood_group`, `type`, `units_in_stock`, `threshold`, `location`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 2, 'A+', 'Whole Blood', 62, 10, 'Main Shelf', '2026-03-21', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(2, 2, 'O-', 'Whole Blood', 34, 10, 'Main Shelf', '2026-03-21', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(3, 2, 'B+', 'Whole Blood', 25, 10, 'Main Shelf', '2026-03-21', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(4, 4, 'A+', 'Whole Blood', 77, 10, 'Main Shelf', '2026-03-21', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(5, 4, 'O-', 'Whole Blood', 43, 10, 'Main Shelf', '2026-03-21', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(6, 4, 'B+', 'Whole Blood', 21, 10, 'Main Shelf', '2026-03-21', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(7, 6, 'O+', 'Whole Blood', 49, 5, NULL, '2025-12-30', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(8, 6, 'A+', 'Whole Blood', 24, 5, NULL, '2026-01-17', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(9, 6, 'B+', 'Whole Blood', 31, 5, NULL, '2025-12-31', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(10, 6, 'AB+', 'Whole Blood', 30, 5, NULL, '2026-01-14', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(11, 6, 'O-', 'Whole Blood', 11, 5, NULL, '2025-12-29', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(12, 6, 'A-', 'Whole Blood', 15, 5, NULL, '2025-12-30', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(13, 6, 'B-', 'Whole Blood', 44, 5, NULL, '2026-01-03', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(14, 6, 'AB-', 'Whole Blood', 15, 5, NULL, '2026-01-20', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(15, 7, 'A+', 'Whole Blood', 18, 10, NULL, '2026-06-21', '2025-12-21 07:39:11', '2025-12-21 07:39:11');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `from_user_id` bigint(20) UNSIGNED NOT NULL,
  `to_user_id` bigint(20) UNSIGNED NOT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `body` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(2, '2019_08_19_000000_create_failed_jobs_table', 1),
(3, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(4, '2025_12_18_000000_create_organizations_table', 1),
(5, '2025_12_18_000001_create_users_table', 1),
(6, '2025_12_18_000002_create_donors_table', 1),
(7, '2025_12_18_000003_create_donations_table', 1),
(8, '2025_12_18_000004_create_inventory_items_table', 1),
(9, '2025_12_18_000005_create_blood_requests_table', 1),
(10, '2025_12_18_000006_create_riders_table', 1),
(11, '2025_12_18_000007_create_deliveries_table', 1),
(12, '2025_12_18_000008_create_messages_table', 1),
(13, '2025_12_18_000009_create_settings_table', 1),
(14, '2025_12_18_000010_create_feedback_table', 1),
(15, '2025_12_18_100001_add_location_to_organizations_table', 1),
(16, '2025_12_18_100002_create_appointments_table', 1),
(17, '2025_12_18_100003_create_donor_badges_table', 1),
(18, '2025_12_18_200001_update_user_roles_enum', 1),
(19, '2025_12_18_200002_simplify_user_roles', 1),
(20, '2025_12_18_200003_add_patient_details_to_blood_requests_table', 1),
(21, '2025_12_18_214025_add_blood_bank_fields_to_organizations_table', 1),
(22, '2025_12_18_214035_add_blood_bank_fields_to_donations_table', 1),
(23, '2025_12_18_214038_add_financial_and_type_fields_to_blood_requests_table', 1),
(24, '2025_12_18_214042_update_delivery_status_enum_in_deliveries_table', 1),
(25, '2025_12_18_214153_add_height_to_donors_table', 1),
(26, '2025_12_18_215504_change_role_to_string_in_users_table', 1),
(27, '2025_12_18_230140_create_offers_table', 1),
(28, '2025_12_18_230141_create_payments_table', 1),
(29, '2025_12_18_230141_create_plans_table', 1),
(30, '2025_12_18_230141_create_subscriptions_table', 1),
(31, '2025_12_18_230142_add_status_history_to_deliveries', 1),
(32, '2025_12_19_065320_create_notifications_table', 1),
(33, '2025_12_21_083724_add_auth_fields_to_organizations_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offers`
--

CREATE TABLE `offers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `blood_request_id` bigint(20) UNSIGNED NOT NULL,
  `organization_id` bigint(20) UNSIGNED NOT NULL,
  `product_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `card_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(191) NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `type` enum('Hospital','Blood Bank','Regulatory Body','Logistics') NOT NULL,
  `license_number` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `contact_email` varchar(191) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `logo` varchar(191) DEFAULT NULL,
  `cover_photo` varchar(191) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `operating_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operating_hours`)),
  `description` text DEFAULT NULL,
  `services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`services`)),
  `facebook_link` varchar(191) DEFAULT NULL,
  `twitter_link` varchar(191) DEFAULT NULL,
  `instagram_link` varchar(191) DEFAULT NULL,
  `linkedin_link` varchar(191) DEFAULT NULL,
  `status` enum('Pending','Active','Suspended') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `name`, `type`, `license_number`, `address`, `contact_email`, `email`, `password`, `phone`, `logo`, `cover_photo`, `latitude`, `longitude`, `operating_hours`, `description`, `services`, `facebook_link`, `twitter_link`, `instagram_link`, `linkedin_link`, `status`, `created_at`, `updated_at`, `deleted_at`, `remember_token`) VALUES
(1, 'Lagos State Hospital', 'Hospital', 'LIC-ADMIN-LAGOS-STATE-HOSPITAL', 'Admin Seed Location', 'contact@lagosstatehospital.com', 'contact@lagosstatehospital.com', '$2y$10$a/o.VNyg8r1a7CO/ppnNNudj49K17kQl62opTieIgG3aC51OgzTfe', '09025938971', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL, NULL),
(2, 'Abuja Blood Center', 'Blood Bank', 'LIC-ADMIN-ABUJA-BLOOD-CENTER', 'Admin Seed Location', 'contact@abujabloodcenter.com', 'contact@abujabloodcenter.com', '$2y$10$MxELCNWun08tNlNFuarBkePh9I98Slnsf18h5HapsFtAE2YM4pupu', '09060883661', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL, NULL),
(3, 'Kano Medical', 'Hospital', 'LIC-ADMIN-KANO-MEDICAL', 'Admin Seed Location', 'contact@kanomedical.com', 'contact@kanomedical.com', '$2y$10$WqLL6EJxWtb3vhGEP4kSGuGgohd42g4pkeh1QKgqCyv5P3urWD1j.', '09099292099', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL, NULL),
(4, 'Port Harcourt Bank', 'Blood Bank', 'LIC-ADMIN-PORT-HARCOURT-BANK', 'Admin Seed Location', 'contact@portharcourtbank.com', 'contact@portharcourtbank.com', '$2y$10$E46.zEVWS9woWpjgYd1qcOYzZOpG8smWJpY/Yfvi6jtmJlhxJLTSy', '09059305251', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Suspended', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL, NULL),
(5, 'Kuje National Hospital', 'Hospital', 'HOSP-2025-001', '123 Hospital Road, Kuje, Abuja', 'contact@kujehospital.gov.ng', 'admin@kujehospital.gov.ng', '$2y$10$ban0PM/Y2wIHB9bUyv6G.usQijdHacnz8lZueWjozZ2u6aWvRN70W', '08012345678', NULL, NULL, 8.88750000, 7.22850000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL, NULL),
(6, 'Central Blood Bank Abuja', 'Blood Bank', 'BB-2025-001', 'Garki District, Abuja', 'info@centralbloodbank.org', 'info@centralbloodbank.org', '$2y$10$71Jk7W5QN8AiuujnZFbd1u24M2916KQvUrS5DiIqfAhZWV8QdNzei', '08022223333', NULL, NULL, 9.07650000, 7.39850000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL, NULL),
(7, 'Central Blood Bank Port Harcourt', 'Blood Bank', 'BB-778899', 'No 15 Aggrey Road, Port Harcourt', 'info@phbloodbank.com', 'admin@phbloodbank.com', '$2y$10$Q/4sQSKi4sYLLAK5BcKCHul9OlejOfZKCpgnxTbj12eJwA3xLvbFS', '234812343002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://facebook.com/phbloodbank', 'https://twitter.com/phbloodbank', 'https://instagram.com/phbloodbank', 'https://linkedin.com/company/phbloodbank', 'Active', '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL, NULL),
(8, 'BMH Hospital', 'Hospital', 'HOSP-112233', 'BMH Road, Port Harcourt', 'admin@bmh.com', 'admin@bmh.com', '$2y$10$J7H5V6u2zwfDe/ymRSV92Oy8qh3lxfFWLVzSDm914RMp6dGnDDbl6', '234812343003', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2025-12-21 07:39:11', '2025-12-21 07:39:11', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `blood_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(191) NOT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'Pending',
  `transaction_reference` varchar(191) DEFAULT NULL,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `duration_days` int(11) NOT NULL DEFAULT 30,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riders`
--

CREATE TABLE `riders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_type` enum('Bike','Car','Van','Drone') NOT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `current_latitude` decimal(10,8) DEFAULT NULL,
  `current_longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('Available','Busy','Offline') NOT NULL DEFAULT 'Offline',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `riders`
--

INSERT INTO `riders` (`id`, `user_id`, `vehicle_type`, `vehicle_plate`, `current_latitude`, `current_longitude`, `status`, `created_at`, `updated_at`) VALUES
(1, 7, 'Bike', 'RIDER-0', NULL, NULL, 'Available', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(2, 8, 'Bike', 'RIDER-1', NULL, NULL, 'Offline', '2025-12-21 07:39:10', '2025-12-21 07:39:10'),
(3, 9, 'Bike', 'RIDER-2', NULL, NULL, 'Offline', '2025-12-21 07:39:10', '2025-12-21 07:39:10');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `organization_id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(50) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'Active',
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `organization_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_name` varchar(191) NOT NULL,
  `last_name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(191) NOT NULL,
  `role` varchar(191) NOT NULL DEFAULT 'donor',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `organization_id`, `first_name`, `last_name`, `email`, `phone`, `password`, `role`, `date_of_birth`, `gender`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, 'System', 'Admin', 'admin@hemotracka.com', '09000000001', '$2y$10$V7Z2bCJv62/Rkgg2fccbO.pSZ4N2pFZMnQtChZaCHOK9XJ3JzHOTC', 'admin', NULL, NULL, '2025-12-21 07:39:09', NULL, '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(2, NULL, 'Donor', 'Seed0', 'donor_seed0@example.com', '08165952325', '$2y$10$l/dPGVl7xctFlS8htlv2zObVdh2AfNIn0iuh/OgvbVwiZ7m8RFPai', 'donor', NULL, NULL, NULL, NULL, '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(3, NULL, 'Donor', 'Seed1', 'donor_seed1@example.com', '08197280012', '$2y$10$f8saTejmomyZ7alR9FDdM.cgrrIx1Z8hju2Qf3SAdHeIQBbHlrlte', 'donor', NULL, NULL, NULL, NULL, '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(4, NULL, 'Donor', 'Seed2', 'donor_seed2@example.com', '08164602519', '$2y$10$tci9hN0yC5iqtk4KVbGC2.OWgp7v5MozmHNYQzyEpOKAuEsDzgfZu', 'donor', NULL, NULL, NULL, NULL, '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(5, NULL, 'Donor', 'Seed3', 'donor_seed3@example.com', '08192150242', '$2y$10$OOSuVLMmanrAsfTUeLf77O1c7mWgGblrvVkJyNZmkIxrfNwzv5GF6', 'donor', NULL, NULL, NULL, NULL, '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(6, NULL, 'Donor', 'Seed4', 'donor_seed4@example.com', '08116060768', '$2y$10$tLhfvmWVcELULXPfLCRVrug2pEfJ0YmRbGN1QNiKu5staZVOIVKuC', 'donor', NULL, NULL, NULL, NULL, '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(7, NULL, 'Rider', 'Seed0', 'rider_seed0@example.com', '07063607983', '$2y$10$fNOEWVc6h3Zuj9f6XOIyZubQiy.w.8e6xGa3mNobm/J08vv9lz3VK', 'rider', NULL, NULL, NULL, NULL, '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(8, NULL, 'Rider', 'Seed1', 'rider_seed1@example.com', '07012310075', '$2y$10$PmBB4aE4csge4uvt0NUUxOYTgTN7folQ.uIvgYfrkP6gDF9AuvmSi', 'rider', NULL, NULL, NULL, NULL, '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL),
(9, NULL, 'Rider', 'Seed2', 'rider_seed2@example.com', '07045355047', '$2y$10$5L0n27SNguLSk8saHg6ynu9r07loPFlisxSPt4uqsnpA6iE8x/vuW', 'rider', NULL, NULL, NULL, NULL, '2025-12-21 07:39:10', '2025-12-21 07:39:10', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointments_donor_id_appointment_date_index` (`donor_id`,`appointment_date`),
  ADD KEY `appointments_organization_id_appointment_date_index` (`organization_id`,`appointment_date`),
  ADD KEY `appointments_status_index` (`status`);

--
-- Indexes for table `blood_requests`
--
ALTER TABLE `blood_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `blood_requests_organization_id_foreign` (`organization_id`),
  ADD KEY `blood_requests_status_index` (`status`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `deliveries_tracking_code_unique` (`tracking_code`),
  ADD KEY `deliveries_blood_request_id_foreign` (`blood_request_id`),
  ADD KEY `deliveries_rider_id_foreign` (`rider_id`),
  ADD KEY `deliveries_status_index` (`status`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donations_donor_id_foreign` (`donor_id`),
  ADD KEY `donations_organization_id_foreign` (`organization_id`),
  ADD KEY `donations_donation_date_index` (`donation_date`);

--
-- Indexes for table `donors`
--
ALTER TABLE `donors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `donors_user_id_unique` (`user_id`),
  ADD KEY `donors_organization_id_foreign` (`organization_id`),
  ADD KEY `donors_blood_group_index` (`blood_group`),
  ADD KEY `donors_status_index` (`status`);

--
-- Indexes for table `donor_badges`
--
ALTER TABLE `donor_badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `donor_badges_slug_unique` (`slug`);

--
-- Indexes for table `donor_badge_donor`
--
ALTER TABLE `donor_badge_donor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `donor_badge_donor_donor_id_donor_badge_id_unique` (`donor_id`,`donor_badge_id`),
  ADD KEY `donor_badge_donor_donor_badge_id_foreign` (`donor_badge_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `feedback_user_id_foreign` (`user_id`),
  ADD KEY `feedback_target_type_target_id_index` (`target_type`,`target_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_inventory` (`organization_id`,`blood_group`,`type`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_from_user_id_foreign` (`from_user_id`),
  ADD KEY `messages_to_user_id_foreign` (`to_user_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offers_blood_request_id_foreign` (`blood_request_id`),
  ADD KEY `offers_organization_id_foreign` (`organization_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `organizations_license_number_unique` (`license_number`),
  ADD UNIQUE KEY `organizations_email_unique` (`email`),
  ADD KEY `organizations_type_index` (`type`),
  ADD KEY `organizations_status_index` (`status`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_transaction_reference_unique` (`transaction_reference`),
  ADD KEY `payments_user_id_foreign` (`user_id`),
  ADD KEY `payments_blood_request_id_foreign` (`blood_request_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plans_slug_unique` (`slug`);

--
-- Indexes for table `riders`
--
ALTER TABLE `riders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `riders_user_id_foreign` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting` (`organization_id`,`key`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscriptions_user_id_foreign` (`user_id`),
  ADD KEY `subscriptions_plan_id_foreign` (`plan_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`),
  ADD KEY `users_organization_id_foreign` (`organization_id`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_email_index` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blood_requests`
--
ALTER TABLE `blood_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `donors`
--
ALTER TABLE `donors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `donor_badges`
--
ALTER TABLE `donor_badges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donor_badge_donor`
--
ALTER TABLE `donor_badge_donor`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offers`
--
ALTER TABLE `offers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `riders`
--
ALTER TABLE `riders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_donor_id_foreign` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blood_requests`
--
ALTER TABLE `blood_requests`
  ADD CONSTRAINT `blood_requests_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_blood_request_id_foreign` FOREIGN KEY (`blood_request_id`) REFERENCES `blood_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deliveries_rider_id_foreign` FOREIGN KEY (`rider_id`) REFERENCES `riders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_donor_id_foreign` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donations_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `donors`
--
ALTER TABLE `donors`
  ADD CONSTRAINT `donors_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `donors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `donor_badge_donor`
--
ALTER TABLE `donor_badge_donor`
  ADD CONSTRAINT `donor_badge_donor_donor_badge_id_foreign` FOREIGN KEY (`donor_badge_id`) REFERENCES `donor_badges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donor_badge_donor_donor_id_foreign` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_to_user_id_foreign` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `offers`
--
ALTER TABLE `offers`
  ADD CONSTRAINT `offers_blood_request_id_foreign` FOREIGN KEY (`blood_request_id`) REFERENCES `blood_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offers_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_blood_request_id_foreign` FOREIGN KEY (`blood_request_id`) REFERENCES `blood_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `riders`
--
ALTER TABLE `riders`
  ADD CONSTRAINT `riders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
