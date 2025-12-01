-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 01, 2025 at 09:38 AM
-- Server version: 10.5.29-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cloacker_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_ab_tests`
--

CREATE TABLE `cloacker_ab_tests` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Test adı',
  `description` text DEFAULT NULL COMMENT 'Test açıklaması',
  `test_type` enum('detection_strategy','threshold','redirect_method') NOT NULL DEFAULT 'detection_strategy',
  `variant_a` text NOT NULL COMMENT 'Variant A ayarları (JSON)',
  `variant_b` text NOT NULL COMMENT 'Variant B ayarları (JSON)',
  `traffic_split` decimal(5,2) NOT NULL DEFAULT 50.00 COMMENT 'Variant A için trafik yüzdesi (50 = %50-%50)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `end_date` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_ab_test_daily_stats`
--

CREATE TABLE `cloacker_ab_test_daily_stats` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `variant` enum('A','B') NOT NULL,
  `total_visitors` int(11) NOT NULL DEFAULT 0,
  `normal_visitors` int(11) NOT NULL DEFAULT 0,
  `fake_visitors` int(11) NOT NULL DEFAULT 0,
  `bot_detected` int(11) NOT NULL DEFAULT 0,
  `avg_bot_confidence` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_ab_test_results`
--

CREATE TABLE `cloacker_ab_test_results` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `visitor_id` int(11) DEFAULT NULL,
  `variant` enum('A','B') NOT NULL,
  `is_bot` tinyint(1) NOT NULL DEFAULT 0,
  `bot_confidence` decimal(5,2) DEFAULT NULL,
  `redirect_target` enum('normal','fake') NOT NULL,
  `test_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_admins`
--

CREATE TABLE `cloacker_admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `telegram_username` varchar(255) DEFAULT NULL,
  `telegram_bot_token` varchar(255) DEFAULT NULL,
  `telegram_chat_id` varchar(100) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_admins`
--

INSERT INTO `cloacker_admins` (`id`, `username`, `password_hash`, `email`, `full_name`, `phone`, `telegram_username`, `telegram_bot_token`, `telegram_chat_id`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$QIjNHnCJ3Phoa2x6WzzLV.7/a1RNL2umJz8nNeX6iIVH/64kUGE7q', 'admin@example.com', NULL, NULL, NULL, NULL, NULL, '2025-12-01 08:32:37', '2025-11-29 17:21:07');

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_admin_logins`
--

CREATE TABLE `cloacker_admin_logins` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `login_time` datetime NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_admin_logins`
--

INSERT INTO `cloacker_admin_logins` (`id`, `admin_id`, `login_time`, `ip`, `user_agent`, `success`) VALUES
(1, NULL, '2025-11-29 17:52:26', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 0),
(2, NULL, '2025-11-29 17:52:34', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 0),
(3, NULL, '2025-11-29 17:52:39', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 0),
(4, NULL, '2025-11-29 17:52:51', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 0),
(5, NULL, '2025-11-29 17:53:00', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 0),
(6, NULL, '2025-11-29 17:54:39', '159.69.81.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 0),
(7, 1, '2025-11-29 17:57:12', '159.69.81.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(8, 1, '2025-11-29 17:57:23', '159.69.81.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(9, 1, '2025-11-29 17:57:36', '159.69.81.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(10, 1, '2025-11-29 18:00:51', '159.69.81.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(11, 1, '2025-11-29 18:00:58', '159.69.81.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(12, 1, '2025-11-29 18:01:03', '159.69.81.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(13, 1, '2025-11-29 18:34:02', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(14, 1, '2025-11-29 18:42:57', '159.69.81.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(15, 1, '2025-11-29 19:01:10', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(16, 1, '2025-11-29 21:27:16', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(17, 1, '2025-11-30 00:46:01', '212.253.187.0', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 1),
(18, NULL, '2025-11-30 08:32:34', '212.253.187.0', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 0),
(19, NULL, '2025-11-30 08:32:48', '212.253.187.0', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 0),
(20, 1, '2025-11-30 08:33:20', '212.253.187.0', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 1),
(21, 1, '2025-11-30 09:39:49', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(22, 1, '2025-11-30 10:06:09', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(23, 1, '2025-11-30 10:36:18', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(24, 1, '2025-11-30 13:13:16', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(25, 1, '2025-11-30 16:12:31', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(26, 1, '2025-11-30 16:30:18', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(27, 1, '2025-11-30 18:33:50', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(28, 1, '2025-11-30 19:10:47', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1),
(29, 1, '2025-12-01 08:32:37', '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_allowed_countries`
--

CREATE TABLE `cloacker_allowed_countries` (
  `id` int(11) NOT NULL,
  `country` varchar(2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_api_keys`
--

CREATE TABLE `cloacker_api_keys` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `api_secret` varchar(64) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_used` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_api_keys`
--

INSERT INTO `cloacker_api_keys` (`id`, `site_id`, `api_key`, `api_secret`, `name`, `is_active`, `created_by`, `created_at`, `last_used`) VALUES
(3, 2, 'bf0d15ac8e54a965ae8c259adcfbefc73e5278976fd93399661fcec584d4fc93', '23327504ab2fcfbe50c1cf93cbe319cf76f94eef380e2a7c50291c4c46c6f0be', 'dhostpro', 1, 1, '2025-11-30 13:23:57', '2025-12-01 09:17:24');

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_behavioral_data`
--

CREATE TABLE `cloacker_behavioral_data` (
  `id` int(11) NOT NULL,
  `visitor_id` int(11) DEFAULT NULL,
  `fingerprint_hash` varchar(64) DEFAULT NULL,
  `behavioral_features` text DEFAULT NULL COMMENT 'JSON formatında behavioral özellikler',
  `mouse_linearity` decimal(5,4) DEFAULT NULL,
  `mouse_speed_variance` decimal(10,4) DEFAULT NULL,
  `scroll_smoothness` decimal(5,4) DEFAULT NULL,
  `typing_rhythm_variance` decimal(10,4) DEFAULT NULL,
  `click_speed_variance` decimal(10,4) DEFAULT NULL,
  `interaction_duration` int(11) DEFAULT NULL COMMENT 'Saniye cinsinden',
  `total_interactions` int(11) DEFAULT NULL,
  `bot_score` decimal(5,2) DEFAULT NULL COMMENT 'Behavioral analiz bot skoru',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_bots`
--

CREATE TABLE `cloacker_bots` (
  `id` int(11) NOT NULL,
  `bot_name` varchar(255) NOT NULL,
  `user_agent` text NOT NULL,
  `target_url` text NOT NULL,
  `delay_ms` int(11) NOT NULL DEFAULT 5000,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_bot_detections`
--

CREATE TABLE `cloacker_bot_detections` (
  `id` int(11) NOT NULL,
  `visitor_id` int(11) NOT NULL,
  `detection_type` varchar(50) NOT NULL,
  `score` int(11) DEFAULT 0,
  `details` text DEFAULT NULL COMMENT 'JSON formatında detaylar',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_bot_detections`
--

INSERT INTO `cloacker_bot_detections` (`id`, `visitor_id`, `detection_type`, `score`, `details`, `created_at`) VALUES
(3, 264, 'fingerprint', 7, '{\"signal\":\"inconsistent_browser_headers\",\"fingerprint\":{\"inconsistent_headers\":true}}', '2025-11-30 10:19:35'),
(4, 265, 'fingerprint', 10, '{\"signal\":\"ua_missing\",\"fingerprint\":{\"ua_missing\":true}}', '2025-11-30 10:26:51'),
(5, 267, 'fingerprint', 18, '{\"signal\":\"scripted_client\",\"fingerprint\":{\"script_client\":\"python-requests\"}}', '2025-11-30 10:41:12'),
(6, 270, 'fingerprint', 7, '{\"signal\":\"inconsistent_browser_headers\",\"fingerprint\":{\"inconsistent_headers\":true}}', '2025-11-30 10:50:23');

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_bot_stats`
--

CREATE TABLE `cloacker_bot_stats` (
  `id` int(11) NOT NULL,
  `bot_id` int(11) NOT NULL,
  `visit_time` datetime NOT NULL DEFAULT current_timestamp(),
  `redirect_type` enum('normal','fake') NOT NULL DEFAULT 'fake',
  `response_code` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_fingerprint_history`
--

CREATE TABLE `cloacker_fingerprint_history` (
  `id` int(11) NOT NULL,
  `fingerprint_hash` varchar(64) NOT NULL,
  `fingerprint_vector` text DEFAULT NULL COMMENT 'JSON formatında vektör',
  `is_verified_human` tinyint(1) DEFAULT 0,
  `visit_count` int(11) DEFAULT 1,
  `human_ratio` decimal(5,2) DEFAULT 0.00 COMMENT 'İnsan ziyaret oranı (0-1)',
  `last_seen` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_fingerprint_history`
--

INSERT INTO `cloacker_fingerprint_history` (`id`, `fingerprint_hash`, `fingerprint_vector`, `is_verified_human`, `visit_count`, `human_ratio`, `last_seen`, `created_at`) VALUES
(1, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 4, 1.00, '2025-11-30 00:11:18', '2025-11-29 23:45:57'),
(2, '3704d0627ebcfed5f6dacc53d3b424baa95940fb335c6276c0ad6ed6bcb820d4', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-29 23:46:52', '2025-11-29 23:46:52'),
(3, '7e2051ef95e3a94d95370eb5f05b84c3f093400955c0253bf552066176abd8c6', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-29 23:48:19', '2025-11-29 23:48:19'),
(4, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 4, 0.00, '2025-11-30 00:09:06', '2025-11-29 23:59:23'),
(5, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 4, 0.00, '2025-11-30 00:09:06', '2025-11-29 23:59:23'),
(6, '5e833e7f4b1ab298aaa830b333ae4b0666628a8a3402bfb91fe2da8286508e8f', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 0.00, '2025-11-30 00:09:06', '2025-11-29 23:59:23'),
(7, 'a71dff7d901bcfa17588613ccaee636c9b1bf5b4dc4a9321be1d22d808d661a1', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 0.00, '2025-11-30 00:09:06', '2025-11-29 23:59:23'),
(8, '7a8fa71126ba8605076c046182c717140fd46b0921895e7404d32ff204f6a7a4', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 0.00, '2025-11-30 00:09:06', '2025-11-29 23:59:24'),
(9, 'cdd8072d04e0459e7069a496475f438866feb1bede85e38fa1a8c1740ccba239', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 0.00, '2025-11-30 00:09:06', '2025-11-29 23:59:24'),
(10, 'e0b3f10076096eaa1eaf23bb7fd36f6c7e1279a13774787493b0b01637641d48', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 0.00, '2025-11-30 00:09:06', '2025-11-29 23:59:24'),
(11, '470c93708558e6a7cf96c088e6416e217429ce1a03065f75d137ca64b4556214', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 0.00, '2025-11-30 00:09:06', '2025-11-29 23:59:24'),
(12, '7639d7d846bacf692534b951710679289c27957fd5815afadbee3d061c38a006', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 00:06:06', '2025-11-30 00:06:06'),
(13, '0823bb37b88abe67a954d9a1e6109c703e01ab550531058f7c3705f3bb70b8a9', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 00:06:59', '2025-11-30 00:06:59'),
(14, 'db6dca1ec882163d26a172cce6fcfb641eb5240e9ee10174b86ca9b27618f12a', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 00:09:20', '2025-11-30 00:09:20'),
(15, 'fcffb18ef4ca191f58c93fff2c110f9c1689c6c22262ee83526dc098fc6ec388', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 00:09:38', '2025-11-30 00:09:38'),
(16, '96bbf3842a1db8d20d3166641dbf3a4aa15b4beb383c19ba00ae947f55b38b51', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 00:13:40', '2025-11-30 00:13:40'),
(17, '44d46e1513950a10cb7c73896b51fb06439035659bcda5123324fa7a764897a5', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 00:16:01', '2025-11-30 00:16:01'),
(18, 'e4e64dcbb6c720cd27a9d88c456b5e94edf900e2365822f79df19eb54a18b04e', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 1, 0.00, '2025-11-30 00:20:44', '2025-11-30 00:20:44'),
(19, 'c3fb7354cdf61a3ccb5950d6ff47fdec6bbd494a7340f091e912eba0d816dc73', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 6, 0.00, '2025-11-30 00:26:27', '2025-11-30 00:25:00'),
(20, '73ec655f84114562b1c73a8e229b21adebba5b121cbd23750936768fc95b1f63', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 6, 1.00, '2025-11-30 00:26:14', '2025-11-30 00:25:55'),
(21, 'c03ee0d2ec8be0e608a65735af39b1706d592feb029a98e9d9484ae9bc56db79', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 00:37:53', '2025-11-30 00:37:53'),
(22, '9ae49be5bce472cc07363b326f37a6423512a977b37a8cd86caff97839d0a2ac', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 4, 0.00, '2025-11-30 00:48:09', '2025-11-30 00:48:09'),
(23, '28be552a8cbd2f996045c979fd1c6bbed89a823a6f935ee1fccf660e83897bd8', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 00:48:53', '2025-11-30 00:48:53'),
(24, 'f1cf7db7b6d112cf6323dcc6bc846fb42a052c77615ca9cfa36565e121eb9cd1', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 1, 0.00, '2025-11-30 00:48:54', '2025-11-30 00:48:54'),
(25, 'acbd0f7919227480299990cc4ffb528085c9e95c624d80a6f470a9548b69d86a', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 00:59:03', '2025-11-30 00:59:03'),
(26, 'b8628c4b2ad371aff05342fbb834972966b6485eeed636de9fba3dfc7c0d81b7', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 01:03:55', '2025-11-30 01:03:55'),
(27, 'c5a717283c163366f3344761af4cf6ebf7a66c190fadd41f439a053ca0775f16', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 0.00, '2025-11-30 05:35:06', '2025-11-30 01:04:49'),
(28, '3b75e52de5d4e2d6c910e8931afe4b236a79c7e3d581ef14f23ee7445c5cf04a', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 01:05:37', '2025-11-30 01:05:37'),
(29, '97b55baa015b5859a4bb3e36ac29d3cef5c21ed436c6e0b373c745a7f911e45b', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 01:06:43', '2025-11-30 01:06:43'),
(30, '20d89b6fba8eafcae3093544d5ade716f42d967d981a9ff13bc2999b22e1b208', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 01:40:00', '2025-11-30 01:40:00'),
(31, 'f90fa1948921a25056268fa652d9b36fd6205d20c651aa9ddc2d2bf2f5a59f7f', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 01:43:00', '2025-11-30 01:43:00'),
(32, 'ca134fb64b3ea5cf19c2bd4c88515b62b906027bc402afb9008e5c5d8b6a6a57', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 02:04:23', '2025-11-30 02:04:23'),
(33, 'f0c5efc0ec68d73c695814182b924975ac6517de8493e029c9179a3b0e9936a9', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 1, 0.00, '2025-11-30 02:12:02', '2025-11-30 02:12:02'),
(34, '95322346ec6d2b8fa84692c98e3910555471ef96940752eb200aed942b110586', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 02:19:28', '2025-11-30 02:19:28'),
(35, 'f1fcf807cb656bc5f8650a9fdccb73411cdaa1366aab1466ac59eaf2f3ae1b79', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 4, 0.00, '2025-11-30 02:28:50', '2025-11-30 02:28:49'),
(36, 'b2fc9eb31e093c9f23593ff62d1112378670836f5805dc0421015bff1758b209', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 02:28:50', '2025-11-30 02:28:50'),
(37, '8ca6146afba9ca536b9872505651aa48fba364ef5529b2a5ddd39a34082d4c60', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 1, 0.00, '2025-11-30 02:28:52', '2025-11-30 02:28:52'),
(38, 'b1cc3f137c7f709fc5d797678b67bc404f5aafeb9497c3b8547324a3565c2287', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 1, 0.00, '2025-11-30 02:30:52', '2025-11-30 02:30:52'),
(39, 'f725b5ba20b5cb2fc8506eacf0cbed43d2fa35221f74450e7b21a4c5266643cc', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 02:44:42', '2025-11-30 02:44:42'),
(40, 'dd441c402c3356ba2b4fe219bef10c93cf96cb4f27f4e3ac1f890be80d6680c0', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 1, 0.00, '2025-11-30 03:09:12', '2025-11-30 03:09:12'),
(41, '50c9540f42e992ae561c2b5bb5ec14879e8ec114340b1ba2fb64810bab2b346a', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 1.00, '2025-11-30 04:11:30', '2025-11-30 03:59:42'),
(42, '314b76b4a74b42366c4fd656c44cd17b333ae3220f9a836f217faa37268bc880', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 04:00:00', '2025-11-30 04:00:00'),
(43, 'ddf78f68ea481915b051e356a203efa83660e7e3be85d689095a12740366c6bb', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 04:04:40', '2025-11-30 04:04:40'),
(44, '1559545f702d463330e58a1ab2b0cdd5a40a5405f4521c8e0a57c5e46bdee07c', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 1.00, '2025-11-30 04:05:55', '2025-11-30 04:04:59'),
(45, '3eb998c87871ae72ed66ad3961528f27dc163fecc0abeebe3b4161f6e012011e', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 04:06:41', '2025-11-30 04:06:41'),
(46, 'a2916077d014d497b5b1346bdd6c6c0133448bbcde449e8ed0e253e4444ad7fb', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 04:07:44', '2025-11-30 04:07:44'),
(47, '83b79cf789edab759842a1c4f6eec2cc09480fc002116135178cd76305bc7497', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 04:20:53', '2025-11-30 04:20:53'),
(48, '5738fdc66dc9145f189b050fc80fb1fc6e585d3f30d751d90c402da539de8370', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 04:42:28', '2025-11-30 04:42:28'),
(49, '2f0ec29e1a396ca53b51b25319ab18bbd48a2c232782abb0e78a7fc30820edf0', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 1.00, '2025-11-30 04:59:56', '2025-11-30 04:59:53'),
(50, '20ba7226ccfbdaa696ce072433af0cbf071412a1b1e223f6bb238591ee189edf', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 05:31:57', '2025-11-30 05:31:57'),
(51, 'fc4de8553499be3826601629c54f38675bc772dc1e025987adce4da64d5effb8', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 05:36:16', '2025-11-30 05:36:16'),
(52, '3441383437d2cd2de23d925dcffe8e77514955322e84eb9d6a1e0363d8257a05', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 05:41:45', '2025-11-30 05:41:45'),
(53, '7dc25db2594a03d211ff383fc9334b5ab4918d280d9301df64c7ea1c97db9d70', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 1.00, '2025-11-30 05:55:44', '2025-11-30 05:43:07'),
(54, '64017bd89e65e9b52aefb8c9def321cea0649ec7c9cec18336d4880ac6032c78', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 05:44:01', '2025-11-30 05:44:01'),
(55, '290eb26c2b0a4f36012bdfc4c4932554af883633b5e44dde4e9474c2d08a62dd', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 05:57:32', '2025-11-30 05:57:32'),
(56, 'c737c4ae9b70d049129df3c89c2826b0bf024f5596ab7de75f9143355c154475', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 06:02:50', '2025-11-30 06:02:50'),
(57, 'd00778220be351cd878e9bdb4dc8ff75e6466ce52598c55dbef1361482a7481b', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 06:21:46', '2025-11-30 06:21:46'),
(58, 'c93ebfe0158a7f46761fc843094e603b78125afef0d9183a9341784dfffbd71a', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 06:33:30', '2025-11-30 06:33:30'),
(59, '3a6dc403887e7f19682885e61aeed37f4024d7ebd4922c033e1e5346128d784d', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 06:36:53', '2025-11-30 06:36:53'),
(60, '3d3b456db18451b32ba26019d3fb312e9622e008a708bcc456162d398f99c41c', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 1, 0.00, '2025-11-30 06:44:12', '2025-11-30 06:44:12'),
(61, '9b4999b8aa1926b14072217184c307651503fa8c20d79ce06767e3a5107323f4', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 1, 0.00, '2025-11-30 06:58:01', '2025-11-30 06:58:01'),
(62, '0d5b6222c1bd3b380703ff60e18bd666eaf9abe27288607ef45e56b62834a550', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 07:03:56', '2025-11-30 07:03:56'),
(63, '103f82967276df209c45206b3d5d81e6f593cbae5e404db76a87121f63c99415', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 07:18:06', '2025-11-30 07:18:06'),
(64, 'c3d5a696c6bc00399abb9fabd422a941cb74a922c9fbc94904172a5b93ac9670', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 07:29:57', '2025-11-30 07:29:57'),
(65, '958dd48e09c920ed43e6266e750e525a9ac0f0a77b2cdcb12ea9bedacc1f7221', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 07:51:23', '2025-11-30 07:51:23'),
(66, '72e09fdf2c99d6a2f60df3ebdbd84f125dee52ef95d1bb1aea17075c1a9b64ed', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 07:55:33', '2025-11-30 07:55:33'),
(67, '7ec48ad2609e3e96ae24fc96399071fc8a047ea96df302a30f5252f96ca64d37', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 08:02:03', '2025-11-30 08:02:03'),
(68, 'e63141ecf0a2c38ba5ed86f0bf779128bb589c815b2abe51ecd9b69f31ebc98d', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 08:02:13', '2025-11-30 08:02:13'),
(69, 'f516f3286d68bb8e56d6a6bd25c8fa67289faa76715a1b7fda4faf00831dddc5', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 1, 0.00, '2025-11-30 08:41:35', '2025-11-30 08:41:35'),
(70, '1e5f67b5ea81e927a6d83236c898ca1fc45be6df8b970d963042054a55ead714', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 08:49:33', '2025-11-30 08:49:33'),
(71, '03f110c4cb38053f1f635c1c288cfd447bf1695fc301df4f501c4b4b40c8685b', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 0, 2, 1.00, '2025-11-30 09:15:29', '2025-11-30 09:14:42'),
(72, '70e7c7bc1d2cbdc452775a9cc2b77c4efa40a89f326742989435a9f6ec87ddb3', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 09:47:09', '2025-11-30 09:47:09'),
(73, '44ebc6fae343db454b098ef15f513a135df0868ef5354f72158dbd36991889e0', '{\"canvas\":0,\"webgl\":0,\"audio\":0,\"fonts\":0,\"plugins\":0,\"screen_width\":0,\"screen_height\":0,\"timezone\":0,\"language\":4082745666}', 1, 1, 1.00, '2025-11-30 09:47:11', '2025-11-30 09:47:11');

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_ja3_blacklist`
--

CREATE TABLE `cloacker_ja3_blacklist` (
  `id` int(11) NOT NULL,
  `ja3_hash` varchar(64) NOT NULL,
  `ja3s_hash` varchar(64) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `chrome_version` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_ja3_blacklist`
--

INSERT INTO `cloacker_ja3_blacklist` (`id`, `ja3_hash`, `ja3s_hash`, `description`, `chrome_version`, `is_active`, `created_at`) VALUES
(13, '771,4865-4866-4867-49195-49199-49196-49200-52393-52392-49171-491', NULL, 'Chrome 129 Example', '129', 1, '2025-11-29 18:38:47');

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_migrations`
--

CREATE TABLE `cloacker_migrations` (
  `id` int(11) NOT NULL,
  `migration_name` varchar(255) NOT NULL,
  `executed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_migrations`
--

INSERT INTO `cloacker_migrations` (`id`, `migration_name`, `executed_at`) VALUES
(1, '20240101_120000_example_migration', '2025-11-29 18:04:16');

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_ml_training`
--

CREATE TABLE `cloacker_ml_training` (
  `id` int(11) NOT NULL,
  `visitor_id` int(11) DEFAULT NULL,
  `features` text NOT NULL COMMENT 'JSON formatında özellikler',
  `label` tinyint(1) NOT NULL COMMENT '1=bot, 0=gerçek',
  `confidence` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_password_resets`
--

CREATE TABLE `cloacker_password_resets` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_rate_limits`
--

CREATE TABLE `cloacker_rate_limits` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `fingerprint_hash` varchar(64) NOT NULL,
  `request_count` int(11) NOT NULL DEFAULT 1,
  `window_start` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_rate_limits`
--

INSERT INTO `cloacker_rate_limits` (`id`, `ip`, `fingerprint_hash`, `request_count`, `window_start`, `created_at`) VALUES
(425, '13.219.121.241', 'f37dfe09b1925264a37d74fe008da4f5fe13b732a29e024264b73ce49e282e29', 1, '2025-12-01 09:17:24', '2025-12-01 09:17:24');

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_rdns_cache`
--

CREATE TABLE `cloacker_rdns_cache` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `is_bot` tinyint(1) DEFAULT 0,
  `is_valid` tinyint(1) DEFAULT 0,
  `last_checked` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_rdns_cache`
--

INSERT INTO `cloacker_rdns_cache` (`id`, `ip`, `hostname`, `is_bot`, `is_valid`, `last_checked`, `created_at`) VALUES
(1, '212.253.187.0', NULL, 0, 0, '2025-11-29 23:45:57', '2025-11-29 23:45:57'),
(2, '159.69.81.37', 'server.aralikreklam.com.tr', 0, 1, '2025-11-29 23:46:52', '2025-11-29 23:46:52'),
(3, '35.203.211.227', '227.211.203.35.bc.googleusercontent.com', 0, 1, '2025-11-29 23:48:19', '2025-11-29 23:48:19'),
(4, '8.8.8.8', 'dns.google', 0, 1, '2025-11-29 23:58:04', '2025-11-29 23:58:04'),
(5, '46.224.33.89', 's.dhostpro.site', 0, 1, '2025-11-29 23:59:23', '2025-11-29 23:59:23'),
(6, '43.166.130.123', NULL, 0, 0, '2025-11-30 00:06:06', '2025-11-30 00:06:06'),
(7, '185.247.137.95', 'galvanized.monitoring.internet-measurement.com', 0, 1, '2025-11-30 00:06:59', '2025-11-30 00:06:59'),
(8, '202.53.164.90', NULL, 0, 0, '2025-11-30 00:09:20', '2025-11-30 00:09:20'),
(9, '185.242.226.102', 'security.criminalip.com', 0, 0, '2025-11-30 00:09:38', '2025-11-30 00:09:38'),
(10, '87.236.176.230', 'civilized.monitoring.internet-measurement.com', 0, 1, '2025-11-30 00:13:40', '2025-11-30 00:13:40'),
(11, '43.153.71.132', NULL, 0, 0, '2025-11-30 00:16:01', '2025-11-30 00:16:01'),
(12, '195.178.110.201', NULL, 0, 0, '2025-11-30 00:20:44', '2025-11-30 00:20:44'),
(13, '172.235.235.248', '172-235-235-248.ip.linodeusercontent.com', 0, 1, '2025-11-30 00:25:00', '2025-11-30 00:25:00'),
(14, '93.174.93.12', 'rnd.group-ib.com', 0, 0, '2025-11-30 00:37:53', '2025-11-30 00:37:53'),
(15, '165.22.29.29', NULL, 0, 0, '2025-11-30 00:48:08', '2025-11-30 00:48:08'),
(16, '142.93.171.56', NULL, 0, 0, '2025-11-30 00:48:53', '2025-11-30 00:48:53'),
(17, '157.230.117.167', NULL, 0, 0, '2025-11-30 00:48:54', '2025-11-30 00:48:54'),
(18, '159.223.0.20', 'red-4.scan.shadowforce.io', 0, 0, '2025-11-30 00:59:02', '2025-11-30 00:59:02'),
(19, '43.159.132.207', NULL, 0, 0, '2025-11-30 01:03:55', '2025-11-30 01:03:55'),
(20, '193.142.147.209', NULL, 0, 0, '2025-11-30 01:04:49', '2025-11-30 01:04:49'),
(21, '170.106.161.78', NULL, 0, 0, '2025-11-30 01:05:37', '2025-11-30 01:05:37'),
(22, '101.32.49.171', NULL, 0, 0, '2025-11-30 01:06:43', '2025-11-30 01:06:43'),
(23, '104.248.158.73', NULL, 0, 0, '2025-11-30 01:40:00', '2025-11-30 01:40:00'),
(24, '64.227.97.118', NULL, 0, 0, '2025-11-30 01:43:00', '2025-11-30 01:43:00'),
(25, '43.135.148.92', NULL, 0, 0, '2025-11-30 02:04:23', '2025-11-30 02:04:23'),
(26, '128.90.135.12', 'undefined.hostname.localhost', 0, 0, '2025-11-30 02:12:02', '2025-11-30 02:12:02'),
(27, '79.124.40.174', 'ip-40-174.4vendeta.com', 0, 0, '2025-11-30 02:19:28', '2025-11-30 02:19:28'),
(28, '207.154.234.68', NULL, 0, 0, '2025-11-30 02:28:49', '2025-11-30 02:28:49'),
(29, '138.197.191.158', NULL, 0, 0, '2025-11-30 02:28:50', '2025-11-30 02:28:50'),
(30, '207.154.203.193', NULL, 0, 0, '2025-11-30 02:28:52', '2025-11-30 02:28:52'),
(31, '92.75.26.235', 'dslb-092-075-026-235.092.075.pools.vodafone-ip.de', 0, 1, '2025-11-30 02:30:52', '2025-11-30 02:30:52'),
(32, '159.65.201.157', NULL, 0, 0, '2025-11-30 02:44:42', '2025-11-30 02:44:42'),
(33, '198.235.24.174', NULL, 0, 0, '2025-11-30 03:09:12', '2025-11-30 03:09:12'),
(34, '113.31.186.146', NULL, 0, 0, '2025-11-30 03:59:42', '2025-11-30 03:59:42'),
(35, '183.134.59.133', NULL, 0, 0, '2025-11-30 04:04:59', '2025-11-30 04:04:59'),
(36, '60.13.138.156', NULL, 0, 0, '2025-11-30 04:07:44', '2025-11-30 04:07:44'),
(37, '78.153.140.203', NULL, 0, 0, '2025-11-30 04:20:53', '2025-11-30 04:20:53'),
(38, '43.135.211.148', NULL, 0, 0, '2025-11-30 04:42:28', '2025-11-30 04:42:28'),
(39, '35.78.119.136', 'ec2-35-78-119-136.ap-northeast-1.compute.amazonaws.com', 0, 1, '2025-11-30 04:59:53', '2025-11-30 04:59:53'),
(40, '182.42.105.144', NULL, 0, 0, '2025-11-30 05:31:57', '2025-11-30 05:31:57'),
(41, '43.135.130.202', NULL, 0, 0, '2025-11-30 05:36:16', '2025-11-30 05:36:16'),
(42, '64.62.197.47', 'scan-45a.shadowserver.org', 0, 1, '2025-11-30 05:41:45', '2025-11-30 05:41:45'),
(43, '216.218.206.69', 'scan-08.shadowserver.org', 0, 1, '2025-11-30 05:43:06', '2025-11-30 05:43:06'),
(44, '64.62.197.51', 'scan-45e.shadowserver.org', 0, 1, '2025-11-30 05:44:01', '2025-11-30 05:44:01'),
(45, '45.135.193.3', '45.135.193.3.ptr.pfcloud.network', 0, 1, '2025-11-30 06:02:50', '2025-11-30 06:02:50'),
(46, '172.236.228.198', '172-236-228-198.ip.linodeusercontent.com', 0, 1, '2025-11-30 06:21:46', '2025-11-30 06:21:46'),
(47, '178.128.63.241', NULL, 0, 0, '2025-11-30 06:33:30', '2025-11-30 06:33:30'),
(48, '43.157.188.74', NULL, 0, 0, '2025-11-30 06:36:53', '2025-11-30 06:36:53'),
(49, '35.187.114.229', '229.114.187.35.bc.googleusercontent.com', 0, 1, '2025-11-30 06:44:12', '2025-11-30 06:44:12'),
(50, '205.210.31.136', NULL, 0, 0, '2025-11-30 06:58:01', '2025-11-30 06:58:01'),
(51, '147.185.133.190', NULL, 0, 0, '2025-11-30 07:03:56', '2025-11-30 07:03:56'),
(52, '176.53.219.118', NULL, 0, 0, '2025-11-30 07:18:06', '2025-11-30 07:18:06'),
(53, '217.114.43.48', NULL, 0, 0, '2025-11-30 07:29:57', '2025-11-30 07:29:57'),
(54, '162.62.213.187', NULL, 0, 0, '2025-11-30 07:51:23', '2025-11-30 07:51:23'),
(55, '14.215.163.132', NULL, 0, 0, '2025-11-30 07:55:33', '2025-11-30 07:55:33'),
(56, '160.225.169.212', NULL, 0, 0, '2025-11-30 08:02:03', '2025-11-30 08:02:03'),
(57, '176.53.219.26', NULL, 0, 0, '2025-11-30 08:02:13', '2025-11-30 08:02:13'),
(58, '34.140.92.201', '201.92.140.34.bc.googleusercontent.com', 0, 1, '2025-11-30 08:41:35', '2025-11-30 08:41:35'),
(59, '66.132.153.122', NULL, 0, 0, '2025-11-30 08:49:33', '2025-11-30 08:49:33'),
(60, '3.131.215.38', 'ec2-3-131-215-38.us-east-2.compute.amazonaws.com', 0, 1, '2025-11-30 09:14:42', '2025-11-30 09:14:42'),
(61, '157.230.252.44', NULL, 0, 0, '2025-11-30 09:47:09', '2025-11-30 09:47:09'),
(62, '159.65.132.48', NULL, 0, 0, '2025-11-30 12:14:27', '2025-11-30 12:14:27'),
(63, '43.157.170.13', NULL, 0, 0, '2025-11-30 12:26:25', '2025-11-30 12:26:25'),
(64, '101.33.81.73', NULL, 0, 0, '2025-11-30 12:46:32', '2025-11-30 12:46:32'),
(65, '209.97.186.201', NULL, 0, 0, '2025-11-30 13:00:30', '2025-11-30 13:00:30'),
(66, '198.235.24.128', NULL, 0, 0, '2025-11-30 13:13:12', '2025-11-30 13:13:12'),
(67, '66.249.66.1', 'crawl-66-249-66-1.googlebot.com', 1, 1, '2025-11-30 17:28:38', '2025-11-30 17:28:38'),
(68, '54.236.1.0', 'crawl-36ec0100.pinterestcrawler.com', 0, 1, '2025-11-30 17:30:01', '2025-11-30 17:30:01'),
(69, '91.142.170.219', '91.142.170.219.sitel.com.ua', 0, 1, '2025-11-30 17:53:57', '2025-11-30 17:53:57');

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_settings`
--

CREATE TABLE `cloacker_settings` (
  `id` int(11) NOT NULL,
  `normal_url` text DEFAULT 'https://google.com',
  `fake_url` text DEFAULT 'https://google.com',
  `allowed_countries` text DEFAULT NULL COMMENT 'Virgülle ayrılmış ülke kodları',
  `allowed_os` text DEFAULT NULL COMMENT 'Virgülle ayrılmış OS listesi',
  `allowed_browsers` text DEFAULT NULL COMMENT 'Virgülle ayrılmış tarayıcı listesi',
  `bot_confidence_threshold` decimal(5,2) DEFAULT 30.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `ml_enabled` tinyint(1) DEFAULT 1,
  `dynamic_threshold_enabled` tinyint(1) DEFAULT 1,
  `min_threshold` decimal(5,2) DEFAULT 20.00,
  `max_threshold` decimal(5,2) DEFAULT 50.00,
  `enable_ja3_check` tinyint(1) DEFAULT 1,
  `enable_canvas_check` tinyint(1) DEFAULT 1,
  `enable_webgl_check` tinyint(1) DEFAULT 1,
  `enable_audio_check` tinyint(1) DEFAULT 1,
  `enable_webrtc_check` tinyint(1) DEFAULT 1,
  `enable_fonts_check` tinyint(1) DEFAULT 1,
  `enable_plugins_check` tinyint(1) DEFAULT 1,
  `enable_headless_check` tinyint(1) DEFAULT 1,
  `enable_challenge_check` tinyint(1) DEFAULT 1,
  `enable_rate_limit` tinyint(1) DEFAULT 1,
  `enable_residential_proxy_check` tinyint(1) DEFAULT 1,
  `enable_cloudflare_bot_check` tinyint(1) DEFAULT 1,
  `enable_duplicate_check` tinyint(1) DEFAULT 1,
  `canvas_score` int(11) DEFAULT 8,
  `webgl_score` int(11) DEFAULT 7,
  `audio_score` int(11) DEFAULT 6,
  `webrtc_score` int(11) DEFAULT 10,
  `headless_score` int(11) DEFAULT 12,
  `fonts_score` int(11) DEFAULT 4,
  `plugins_score` int(11) DEFAULT 3,
  `challenge_score` int(11) DEFAULT 15,
  `rate_limit_max_requests` int(11) DEFAULT 10,
  `rate_limit_window_seconds` int(11) DEFAULT 60,
  `challenge_fail_action` varchar(20) DEFAULT 'add_score',
  `speech_synthesis_score` int(11) DEFAULT 3,
  `ja3_score` int(11) DEFAULT 20,
  `enable_rdns_check` tinyint(1) DEFAULT 1 COMMENT 'rDNS kontrolü aktif mi',
  `enable_fingerprint_similarity` tinyint(1) DEFAULT 1 COMMENT 'Fingerprint similarity kontrolü',
  `enable_behavioral_analysis` tinyint(1) DEFAULT 1 COMMENT 'Behavioral analysis aktif mi',
  `fingerprint_similarity_threshold_high` decimal(5,4) DEFAULT 0.9800 COMMENT 'Yüksek benzerlik eşiği (white list)',
  `fingerprint_similarity_threshold_low` decimal(5,4) DEFAULT 0.8500 COMMENT 'Düşük benzerlik eşiği (review)',
  `behavioral_bot_threshold` decimal(5,2) DEFAULT 70.00 COMMENT 'Behavioral bot skoru eşiği',
  `rdns_cache_ttl_hours` int(11) DEFAULT 24 COMMENT 'rDNS cache TTL (saat)',
  `enable_asn_check` tinyint(1) DEFAULT 1 COMMENT 'ASN ve datacenter kontrolü',
  `enable_ip_age_check` tinyint(1) DEFAULT 1 COMMENT 'IP yaşı ve fraud skoru kontrolü',
  `enable_delayed_redirect` tinyint(1) DEFAULT 0 COMMENT 'Delayed redirect aktif mi',
  `delayed_redirect_min_seconds` int(11) DEFAULT 7 COMMENT 'Delayed redirect minimum süre (saniye)',
  `delayed_redirect_max_seconds` int(11) DEFAULT 15 COMMENT 'Delayed redirect maksimum süre (saniye)',
  `rdns_score` int(11) DEFAULT 20 COMMENT 'rDNS bot tespit skoru',
  `enable_proxy_check` tinyint(1) DEFAULT 1 COMMENT 'Proxy/VPN kontrolü aktif mi (IPHub)',
  `enable_tls13_fingerprinting` tinyint(1) DEFAULT 1 COMMENT 'TLS 1.3 fingerprinting aktif mi',
  `enable_threat_intelligence` tinyint(1) DEFAULT 1 COMMENT 'Real-time threat intelligence aktif mi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_settings`
--

INSERT INTO `cloacker_settings` (`id`, `normal_url`, `fake_url`, `allowed_countries`, `allowed_os`, `allowed_browsers`, `bot_confidence_threshold`, `created_at`, `updated_at`, `ml_enabled`, `dynamic_threshold_enabled`, `min_threshold`, `max_threshold`, `enable_ja3_check`, `enable_canvas_check`, `enable_webgl_check`, `enable_audio_check`, `enable_webrtc_check`, `enable_fonts_check`, `enable_plugins_check`, `enable_headless_check`, `enable_challenge_check`, `enable_rate_limit`, `enable_residential_proxy_check`, `enable_cloudflare_bot_check`, `enable_duplicate_check`, `canvas_score`, `webgl_score`, `audio_score`, `webrtc_score`, `headless_score`, `fonts_score`, `plugins_score`, `challenge_score`, `rate_limit_max_requests`, `rate_limit_window_seconds`, `challenge_fail_action`, `speech_synthesis_score`, `ja3_score`, `enable_rdns_check`, `enable_fingerprint_similarity`, `enable_behavioral_analysis`, `fingerprint_similarity_threshold_high`, `fingerprint_similarity_threshold_low`, `behavioral_bot_threshold`, `rdns_cache_ttl_hours`, `enable_asn_check`, `enable_ip_age_check`, `enable_delayed_redirect`, `delayed_redirect_min_seconds`, `delayed_redirect_max_seconds`, `rdns_score`, `enable_proxy_check`, `enable_tls13_fingerprinting`, `enable_threat_intelligence`) VALUES
(1, 'https://google.com', 'https://google.com', 'TR,US,GB,DE,FR', 'windows,macos,linux,android,ios', 'chrome,firefox,safari,edge,opera', 30.00, '2025-11-29 17:21:07', '2025-11-30 17:45:43', 1, 1, 20.00, 50.00, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 8, 7, 6, 10, 12, 4, 3, 15, 10, 60, 'add_score', 3, 20, 1, 1, 1, 0.9800, 0.8500, 70.00, 24, 1, 1, 1, 7, 15, 20, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_sites`
--

CREATE TABLE `cloacker_sites` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `normal_url` text NOT NULL,
  `fake_url` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `settings` text DEFAULT NULL COMMENT 'JSON formatında site özel ayarlar',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_sites`
--

INSERT INTO `cloacker_sites` (`id`, `name`, `domain`, `normal_url`, `fake_url`, `is_active`, `settings`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'dhostpro', 'dhostpro.site', 'https://www.google.com.tr', 'https://www.hurriyet.com.tr', 1, '{\"bot_confidence_threshold\":30,\"allowed_countries\":\"TR\",\"allowed_os\":\"android,ios,windows,macos\",\"allowed_browsers\":\"chrome,firefox,safari,edge,opera\",\"telegram_bot_token\":\"\",\"telegram_chat_id\":\"\"}', 1, '2025-11-29 19:29:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cloacker_visitors`
--

CREATE TABLE `cloacker_visitors` (
  `id` int(11) NOT NULL,
  `site_id` int(11) DEFAULT NULL,
  `api_key_id` int(11) DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `country` varchar(2) DEFAULT NULL,
  `os` varchar(50) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `is_proxy` tinyint(1) NOT NULL DEFAULT 0,
  `is_bot` tinyint(1) NOT NULL DEFAULT 0,
  `redirect_target` varchar(20) NOT NULL DEFAULT 'normal',
  `is_fake_url` tinyint(1) NOT NULL DEFAULT 0,
  `fingerprint_score` int(11) DEFAULT NULL,
  `bot_confidence` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ja3_hash` varchar(64) DEFAULT NULL,
  `ja3s_hash` varchar(64) DEFAULT NULL,
  `http2_fingerprint` text DEFAULT NULL,
  `canvas_fingerprint` varchar(64) DEFAULT NULL,
  `webgl_fingerprint` varchar(64) DEFAULT NULL,
  `audio_fingerprint` varchar(64) DEFAULT NULL,
  `webrtc_leak` tinyint(1) DEFAULT 0,
  `local_ip_detected` varchar(45) DEFAULT NULL,
  `fonts_hash` varchar(64) DEFAULT NULL,
  `plugins_hash` varchar(64) DEFAULT NULL,
  `extensions_detected` text DEFAULT NULL COMMENT 'JSON formatında tespit edilen extensionlar',
  `extensions_count` int(11) DEFAULT 0 COMMENT 'Tespit edilen extension sayısı',
  `ml_confidence` decimal(5,2) DEFAULT NULL,
  `dynamic_threshold` decimal(5,2) DEFAULT NULL,
  `fingerprint_hash` varchar(64) DEFAULT NULL,
  `rdns_hostname` varchar(255) DEFAULT NULL,
  `rdns_is_bot` tinyint(1) DEFAULT 0,
  `fingerprint_similarity` decimal(5,4) DEFAULT NULL,
  `behavioral_bot_score` decimal(5,2) DEFAULT NULL,
  `asn` varchar(20) DEFAULT NULL,
  `asn_name` varchar(255) DEFAULT NULL,
  `is_datacenter` tinyint(1) DEFAULT 0,
  `ip_age_days` int(11) DEFAULT NULL,
  `fraud_score` int(11) DEFAULT NULL,
  `tls13_fingerprint` varchar(255) DEFAULT NULL COMMENT 'TLS 1.3 fingerprint hash',
  `threat_score` decimal(5,2) DEFAULT NULL COMMENT 'Threat intelligence skoru (0-100)',
  `threat_source` varchar(50) DEFAULT NULL COMMENT 'Threat intelligence kaynağı (AbuseIPDB, VirusTotal vb.)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cloacker_visitors`
--

INSERT INTO `cloacker_visitors` (`id`, `site_id`, `api_key_id`, `ip`, `user_agent`, `country`, `os`, `browser`, `referer`, `is_proxy`, `is_bot`, `redirect_target`, `is_fake_url`, `fingerprint_score`, `bot_confidence`, `created_at`, `ja3_hash`, `ja3s_hash`, `http2_fingerprint`, `canvas_fingerprint`, `webgl_fingerprint`, `audio_fingerprint`, `webrtc_leak`, `local_ip_detected`, `fonts_hash`, `plugins_hash`, `extensions_detected`, `extensions_count`, `ml_confidence`, `dynamic_threshold`, `fingerprint_hash`, `rdns_hostname`, `rdns_is_bot`, `fingerprint_similarity`, `behavioral_bot_score`, `asn`, `asn_name`, `is_datacenter`, `ip_age_days`, `fraud_score`, `tls13_fingerprint`, `threat_score`, `threat_source`) VALUES
(151, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-29 23:17:02', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(152, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-29 23:17:12', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(153, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-29 23:45:57', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(154, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-29 23:46:03', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(155, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-29 23:46:03', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(156, 2, NULL, '159.69.81.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'DE', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-29 23:46:52', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '3704d0627ebcfed5f6dacc53d3b424baa95940fb335c6276c0ad6ed6bcb820d4', 'server.aralikreklam.com.tr', 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(157, 2, NULL, '35.203.211.227', 'Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity', 'GB', 'unknown', 'Unknown', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-29 23:48:19', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '7e2051ef95e3a94d95370eb5f05b84c3f093400955c0253bf552066176abd8c6', '227.211.203.35.bc.googleusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(158, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 9, 95.00, '2025-11-29 23:59:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 77.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', 's.dhostpro.site', 1, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(159, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 9, 95.00, '2025-11-29 23:59:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 77.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', 's.dhostpro.site', 1, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(160, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 7, 51.80, '2025-11-29 23:59:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 58.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(161, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 9, 67.00, '2025-11-29 23:59:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 77.00, 30.00, '5e833e7f4b1ab298aaa830b333ae4b0666628a8a3402bfb91fe2da8286508e8f', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(162, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 7, 51.80, '2025-11-29 23:59:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 58.00, 30.00, 'a71dff7d901bcfa17588613ccaee636c9b1bf5b4dc4a9321be1d22d808d661a1', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(163, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 8, 57.40, '2025-11-29 23:59:24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 65.00, 30.00, '7a8fa71126ba8605076c046182c717140fd46b0921895e7404d32ff204f6a7a4', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(164, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 8, 57.40, '2025-11-29 23:59:24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 65.00, 30.00, 'cdd8072d04e0459e7069a496475f438866feb1bede85e38fa1a8c1740ccba239', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(165, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 8, 61.40, '2025-11-29 23:59:24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 70.00, 30.00, 'e0b3f10076096eaa1eaf23bb7fd36f6c7e1279a13774787493b0b01637641d48', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(166, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 7, 51.80, '2025-11-29 23:59:24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 58.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(167, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 7, 51.80, '2025-11-29 23:59:24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 58.00, 30.00, '470c93708558e6a7cf96c088e6416e217429ce1a03065f75d137ca64b4556214', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(168, 2, NULL, '43.166.130.123', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'US', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 00:06:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '7639d7d846bacf692534b951710679289c27957fd5815afadbee3d061c38a006', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(169, 2, NULL, '185.247.137.95', 'Mozilla/5.0 (compatible; InternetMeasurement/1.0; +https://internet-measurement.com/)', 'GB', 'unknown', 'Mozilla', '', 0, 0, 'fake', 1, 1, 20.60, '2025-11-30 00:06:59', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, '0823bb37b88abe67a954d9a1e6109c703e01ab550531058f7c3705f3bb70b8a9', 'galvanized.monitoring.internet-measurement.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(170, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 9, 95.00, '2025-11-30 00:09:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 77.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', 's.dhostpro.site', 1, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(171, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 9, 95.00, '2025-11-30 00:09:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 77.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', 's.dhostpro.site', 1, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(172, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 7, 51.80, '2025-11-30 00:09:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 58.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(173, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 9, 67.00, '2025-11-30 00:09:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 77.00, 30.00, '5e833e7f4b1ab298aaa830b333ae4b0666628a8a3402bfb91fe2da8286508e8f', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(174, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 7, 51.80, '2025-11-30 00:09:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 58.00, 30.00, 'a71dff7d901bcfa17588613ccaee636c9b1bf5b4dc4a9321be1d22d808d661a1', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(175, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 8, 57.40, '2025-11-30 00:09:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 65.00, 30.00, '7a8fa71126ba8605076c046182c717140fd46b0921895e7404d32ff204f6a7a4', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(176, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 8, 57.40, '2025-11-30 00:09:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 65.00, 30.00, 'cdd8072d04e0459e7069a496475f438866feb1bede85e38fa1a8c1740ccba239', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(177, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 8, 61.40, '2025-11-30 00:09:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 70.00, 30.00, 'e0b3f10076096eaa1eaf23bb7fd36f6c7e1279a13774787493b0b01637641d48', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(178, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 7, 51.80, '2025-11-30 00:09:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 58.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(179, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 1, 7, 51.80, '2025-11-30 00:09:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 58.00, 30.00, '470c93708558e6a7cf96c088e6416e217429ce1a03065f75d137ca64b4556214', 's.dhostpro.site', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(180, 2, NULL, '202.53.164.90', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/601.7.7 (KHTML, like Gecko) Version/9.1.2 Safari/601.7.7', 'BD', 'macos', 'Safari', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 00:09:20', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'db6dca1ec882163d26a172cce6fcfb641eb5240e9ee10174b86ca9b27618f12a', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(181, 2, NULL, '185.242.226.102', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.190 Safari/537.36', 'NL', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 00:09:38', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'fcffb18ef4ca191f58c93fff2c110f9c1689c6c22262ee83526dc098fc6ec388', 'security.criminalip.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(182, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-30 00:11:18', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(183, 2, NULL, '87.236.176.230', 'Mozilla/5.0 (compatible; InternetMeasurement/1.0; +https://internet-measurement.com/)', 'GB', 'unknown', 'Mozilla', '', 0, 0, 'fake', 1, 1, 20.60, '2025-11-30 00:13:40', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, '96bbf3842a1db8d20d3166641dbf3a4aa15b4beb383c19ba00ae947f55b38b51', 'civilized.monitoring.internet-measurement.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(184, 2, NULL, '43.153.71.132', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'US', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 00:16:01', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '44d46e1513950a10cb7c73896b51fb06439035659bcda5123324fa7a764897a5', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(185, 2, NULL, '195.178.110.201', 'Python/3.10 aiohttp/3.13.2', 'AD', 'unknown', 'Unknown', '', 0, 1, 'fake', 1, 0, 15.00, '2025-11-30 00:20:44', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'e4e64dcbb6c720cd27a9d88c456b5e94edf900e2365822f79df19eb54a18b04e', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(186, 2, NULL, '172.235.235.248', '', 'IT', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 00:25:00', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'c3fb7354cdf61a3ccb5950d6ff47fdec6bbd494a7340f091e912eba0d816dc73', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(187, 2, NULL, '172.235.235.248', '', 'IT', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 00:25:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'c3fb7354cdf61a3ccb5950d6ff47fdec6bbd494a7340f091e912eba0d816dc73', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(188, 2, NULL, '172.235.235.248', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11) AppleWebKit/537.36 (KHTML', 'IT', 'macos', 'Mozilla', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 00:25:55', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '73ec655f84114562b1c73a8e229b21adebba5b121cbd23750936768fc95b1f63', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(189, 2, NULL, '172.235.235.248', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11) AppleWebKit/537.36 (KHTML', 'IT', 'macos', 'Mozilla', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 00:25:55', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '73ec655f84114562b1c73a8e229b21adebba5b121cbd23750936768fc95b1f63', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(190, 2, NULL, '172.235.235.248', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11) AppleWebKit/537.36 (KHTML', 'IT', 'macos', 'Mozilla', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 00:25:55', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '73ec655f84114562b1c73a8e229b21adebba5b121cbd23750936768fc95b1f63', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(191, 2, NULL, '172.235.235.248', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11) AppleWebKit/537.36 (KHTML', 'IT', 'macos', 'Mozilla', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 00:25:55', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '73ec655f84114562b1c73a8e229b21adebba5b121cbd23750936768fc95b1f63', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(192, 2, NULL, '172.235.235.248', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11) AppleWebKit/537.36 (KHTML', 'IT', 'macos', 'Mozilla', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 00:26:13', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '73ec655f84114562b1c73a8e229b21adebba5b121cbd23750936768fc95b1f63', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(193, 2, NULL, '172.235.235.248', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11) AppleWebKit/537.36 (KHTML', 'IT', 'macos', 'Mozilla', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 00:26:14', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '73ec655f84114562b1c73a8e229b21adebba5b121cbd23750936768fc95b1f63', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(194, 2, NULL, '172.235.235.248', '', 'IT', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 00:26:26', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'c3fb7354cdf61a3ccb5950d6ff47fdec6bbd494a7340f091e912eba0d816dc73', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(195, 2, NULL, '172.235.235.248', '', 'IT', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 00:26:26', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'c3fb7354cdf61a3ccb5950d6ff47fdec6bbd494a7340f091e912eba0d816dc73', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(196, 2, NULL, '172.235.235.248', '', 'IT', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 00:26:27', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'c3fb7354cdf61a3ccb5950d6ff47fdec6bbd494a7340f091e912eba0d816dc73', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(197, 2, NULL, '172.235.235.248', '', 'IT', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 00:26:27', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'c3fb7354cdf61a3ccb5950d6ff47fdec6bbd494a7340f091e912eba0d816dc73', '172-235-235-248.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(198, 2, NULL, '93.174.93.12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3887.0 Safari/537.36', 'NL', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 00:37:53', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'c03ee0d2ec8be0e608a65735af39b1706d592feb029a98e9d9484ae9bc56db79', 'rnd.group-ib.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(199, 2, NULL, '165.22.29.29', '', 'DE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 00:48:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, '9ae49be5bce472cc07363b326f37a6423512a977b37a8cd86caff97839d0a2ac', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(200, 2, NULL, '165.22.29.29', '', 'DE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 00:48:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, '9ae49be5bce472cc07363b326f37a6423512a977b37a8cd86caff97839d0a2ac', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(201, 2, NULL, '165.22.29.29', '', 'DE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 00:48:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, '9ae49be5bce472cc07363b326f37a6423512a977b37a8cd86caff97839d0a2ac', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(202, 2, NULL, '165.22.29.29', '', 'DE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 00:48:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, '9ae49be5bce472cc07363b326f37a6423512a977b37a8cd86caff97839d0a2ac', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(203, 2, NULL, '142.93.171.56', 'Mozilla/5.0 (compatible; Odin; https://docs.getodin.com/)', 'DE', 'unknown', 'Mozilla', '', 1, 0, 'fake', 1, 1, 20.60, '2025-11-30 00:48:53', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, '28be552a8cbd2f996045c979fd1c6bbed89a823a6f935ee1fccf660e83897bd8', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(204, 2, NULL, '157.230.117.167', 'Go-http-client/1.1', 'DE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 0, 15.00, '2025-11-30 00:48:54', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'f1cf7db7b6d112cf6323dcc6bc846fb42a052c77615ca9cfa36565e121eb9cd1', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(205, 2, NULL, '159.223.0.20', 'Mozilla/5.0 zgrab/0.x', 'NL', 'unknown', 'Mozilla', '', 1, 0, 'fake', 1, 1, 20.60, '2025-11-30 00:59:03', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, 'acbd0f7919227480299990cc4ffb528085c9e95c624d80a6f470a9548b69d86a', 'red-4.scan.shadowforce.io', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(206, 2, NULL, '43.159.132.207', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'US', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 01:03:55', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'b8628c4b2ad371aff05342fbb834972966b6485eeed636de9fba3dfc7c0d81b7', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(207, 2, NULL, '193.142.147.209', '', 'NL', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 01:04:49', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'c5a717283c163366f3344761af4cf6ebf7a66c190fadd41f439a053ca0775f16', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(208, 2, NULL, '170.106.161.78', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'US', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 01:05:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '3b75e52de5d4e2d6c910e8931afe4b236a79c7e3d581ef14f23ee7445c5cf04a', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(209, 2, NULL, '101.32.49.171', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'HK', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 01:06:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '97b55baa015b5859a4bb3e36ac29d3cef5c21ed436c6e0b373c745a7f911e45b', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(210, 2, NULL, '104.248.158.73', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36', 'SG', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 01:40:00', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '20d89b6fba8eafcae3093544d5ade716f42d967d981a9ff13bc2999b22e1b208', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(211, 2, NULL, '64.227.97.118', 'libredtail-http', 'US', 'unknown', 'Unknown', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 01:43:00', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'f90fa1948921a25056268fa652d9b36fd6205d20c651aa9ddc2d2bf2f5a59f7f', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(212, 2, NULL, '43.135.148.92', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'US', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 02:04:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'ca134fb64b3ea5cf19c2bd4c88515b62b906027bc402afb9008e5c5d8b6a6a57', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(213, 2, NULL, '128.90.135.12', 'Go-http-client/1.1', 'NL', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 0, 15.00, '2025-11-30 02:12:02', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'f0c5efc0ec68d73c695814182b924975ac6517de8493e029c9179a3b0e9936a9', 'undefined.hostname.localhost', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(214, 2, NULL, '79.124.40.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36', 'BG', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 02:19:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '95322346ec6d2b8fa84692c98e3910555471ef96940752eb200aed942b110586', 'ip-40-174.4vendeta.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(215, 2, NULL, '207.154.234.68', '', 'DE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 02:28:49', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'f1fcf807cb656bc5f8650a9fdccb73411cdaa1366aab1466ac59eaf2f3ae1b79', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(216, 2, NULL, '207.154.234.68', '', 'DE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 02:28:49', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'f1fcf807cb656bc5f8650a9fdccb73411cdaa1366aab1466ac59eaf2f3ae1b79', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(217, 2, NULL, '207.154.234.68', '', 'DE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 02:28:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'f1fcf807cb656bc5f8650a9fdccb73411cdaa1366aab1466ac59eaf2f3ae1b79', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(218, 2, NULL, '207.154.234.68', '', 'DE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 02:28:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'f1fcf807cb656bc5f8650a9fdccb73411cdaa1366aab1466ac59eaf2f3ae1b79', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(219, 2, NULL, '138.197.191.158', 'Mozilla/5.0 (compatible; Odin; https://docs.getodin.com/)', 'DE', 'unknown', 'Mozilla', '', 1, 0, 'fake', 1, 1, 20.60, '2025-11-30 02:28:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, 'b2fc9eb31e093c9f23593ff62d1112378670836f5805dc0421015bff1758b209', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(220, 2, NULL, '207.154.203.193', 'Go-http-client/1.1', 'DE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 0, 15.00, '2025-11-30 02:28:52', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8ca6146afba9ca536b9872505651aa48fba364ef5529b2a5ddd39a34082d4c60', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(221, 2, NULL, '92.75.26.235', '', 'DE', 'unknown', 'Unknown', '', 0, 1, 'fake', 1, 1, 23.80, '2025-11-30 02:30:52', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'b1cc3f137c7f709fc5d797678b67bc404f5aafeb9497c3b8547324a3565c2287', 'dslb-092-075-026-235.092.075.pools.vodafone-ip.de', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(222, 2, NULL, '159.65.201.157', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', 'NL', 'linux', 'Firefox', '', 1, 0, 'fake', 1, 1, 20.60, '2025-11-30 02:44:42', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, 'f725b5ba20b5cb2fc8506eacf0cbed43d2fa35221f74450e7b21a4c5266643cc', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(223, 2, NULL, '198.235.24.174', '', 'US', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 03:09:12', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'dd441c402c3356ba2b4fe219bef10c93cf96cb4f27f4e3ac1f890be80d6680c0', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(224, 2, NULL, '113.31.186.146', 'Dalvik/2.1.0 (Linux; U; Android 9.0; ZTE BA520 Build/MRA58K)', 'CN', 'android', 'Unknown', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 03:59:42', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '50c9540f42e992ae561c2b5bb5ec14879e8ec114340b1ba2fb64810bab2b346a', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(225, 2, NULL, '113.31.186.146', 'Mozilla/5.0 (X11; Linux i686; rv:2.0b10) Gecko/20100101 Firefox/4.0b10', 'CN', 'linux', 'Firefox', '', 0, 0, 'fake', 1, 1, 20.60, '2025-11-30 03:59:59', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, '314b76b4a74b42366c4fd656c44cd17b333ae3220f9a836f217faa37268bc880', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(226, 2, NULL, '113.31.186.146', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.8.1.18) Gecko/20081029 Firefox/2.0.0.18', 'CN', 'windows', 'Firefox', '', 0, 0, 'fake', 1, 1, 20.60, '2025-11-30 04:04:40', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, 'ddf78f68ea481915b051e356a203efa83660e7e3be85d689095a12740366c6bb', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(227, 2, NULL, '183.134.59.133', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11) AppleWebKit/601.1.27 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/601.1.27', 'CN', 'macos', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 04:04:59', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '1559545f702d463330e58a1ab2b0cdd5a40a5405f4521c8e0a57c5e46bdee07c', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(228, 2, NULL, '183.134.59.133', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11) AppleWebKit/601.1.27 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/601.1.27', 'CN', 'macos', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 04:05:55', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '1559545f702d463330e58a1ab2b0cdd5a40a5405f4521c8e0a57c5e46bdee07c', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(229, 2, NULL, '113.31.186.146', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11) AppleWebKit/601.1.27 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/601.1.27', 'CN', 'macos', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 04:06:41', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '3eb998c87871ae72ed66ad3961528f27dc163fecc0abeebe3b4161f6e012011e', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(230, 2, NULL, '60.13.138.156', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Mobile Safari/537.36', 'CN', 'android', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 04:07:44', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'a2916077d014d497b5b1346bdd6c6c0133448bbcde449e8ed0e253e4444ad7fb', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(231, 2, NULL, '113.31.186.146', 'Dalvik/2.1.0 (Linux; U; Android 9.0; ZTE BA520 Build/MRA58K)', 'CN', 'android', 'Unknown', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 04:11:30', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '50c9540f42e992ae561c2b5bb5ec14879e8ec114340b1ba2fb64810bab2b346a', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(232, 2, NULL, '78.153.140.203', 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:33.0) Gecko/20100101 Firefox/33.0', 'GB', 'linux', 'Firefox', '', 0, 0, 'fake', 1, 1, 20.60, '2025-11-30 04:20:53', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, '83b79cf789edab759842a1c4f6eec2cc09480fc002116135178cd76305bc7497', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(233, 2, NULL, '43.135.211.148', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'BR', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 04:42:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '5738fdc66dc9145f189b050fc80fb1fc6e585d3f30d751d90c402da539de8370', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(234, 2, NULL, '35.78.119.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4240.193 Safari/537.36', 'JP', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 04:59:53', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '2f0ec29e1a396ca53b51b25319ab18bbd48a2c232782abb0e78a7fc30820edf0', 'ec2-35-78-119-136.ap-northeast-1.compute.amazonaws.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(235, 2, NULL, '35.78.119.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4240.193 Safari/537.36', 'JP', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 04:59:56', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '2f0ec29e1a396ca53b51b25319ab18bbd48a2c232782abb0e78a7fc30820edf0', 'ec2-35-78-119-136.ap-northeast-1.compute.amazonaws.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(236, 2, NULL, '182.42.105.144', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'CN', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 05:31:57', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '20ba7226ccfbdaa696ce072433af0cbf071412a1b1e223f6bb238591ee189edf', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(237, 2, NULL, '193.142.147.209', '', 'NL', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 05:35:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, 'c5a717283c163366f3344761af4cf6ebf7a66c190fadd41f439a053ca0775f16', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(238, 2, NULL, '43.135.130.202', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'US', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 05:36:16', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'fc4de8553499be3826601629c54f38675bc772dc1e025987adce4da64d5effb8', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(239, 2, NULL, '64.62.197.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:136.0) Gecko/20100101 Firefox/136.0', 'US', 'windows', 'Firefox', '', 1, 0, 'fake', 1, 1, 20.60, '2025-11-30 05:41:45', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, '3441383437d2cd2de23d925dcffe8e77514955322e84eb9d6a1e0363d8257a05', 'scan-45a.shadowserver.org', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(240, 2, NULL, '216.218.206.69', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', 'US', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 05:43:07', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '7dc25db2594a03d211ff383fc9334b5ab4918d280d9301df64c7ea1c97db9d70', 'scan-08.shadowserver.org', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(241, 2, NULL, '64.62.197.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36', 'US', 'android', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 05:44:01', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '64017bd89e65e9b52aefb8c9def321cea0649ec7c9cec18336d4880ac6032c78', 'scan-45e.shadowserver.org', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(242, 2, NULL, '216.218.206.69', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', 'US', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 05:55:44', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '7dc25db2594a03d211ff383fc9334b5ab4918d280d9301df64c7ea1c97db9d70', 'scan-08.shadowserver.org', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(243, 2, NULL, '216.218.206.69', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', 'US', 'linux', 'Firefox', '', 0, 0, 'fake', 1, 1, 20.60, '2025-11-30 05:57:32', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, '290eb26c2b0a4f36012bdfc4c4932554af883633b5e44dde4e9474c2d08a62dd', 'scan-08.shadowserver.org', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(244, 2, NULL, '45.135.193.3', 'Mozilla/5.0 (Windows NT 5.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.78 Safari/537.36 OPR/47.0.2631.39', 'DE', 'windows', 'Opera', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 06:02:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'c737c4ae9b70d049129df3c89c2826b0bf024f5596ab7de75f9143355c154475', '45.135.193.3.ptr.pfcloud.network', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(245, 2, NULL, '172.236.228.198', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36', 'US', 'macos', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 06:21:46', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'd00778220be351cd878e9bdb4dc8ff75e6466ce52598c55dbef1361482a7481b', '172-236-228-198.ip.linodeusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(246, 2, NULL, '178.128.63.241', 'libredtail-http', 'SG', 'unknown', 'Unknown', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 06:33:30', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'c93ebfe0158a7f46761fc843094e603b78125afef0d9183a9341784dfffbd71a', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(247, 2, NULL, '43.157.188.74', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'BR', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 06:36:53', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '3a6dc403887e7f19682885e61aeed37f4024d7ebd4922c033e1e5346128d784d', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(248, 2, NULL, '35.187.114.229', 'python-requests/2.32.5', 'BE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 28.60, '2025-11-30 06:44:12', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 16.00, 30.00, '3d3b456db18451b32ba26019d3fb312e9622e008a708bcc456162d398f99c41c', '229.114.187.35.bc.googleusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(249, 2, NULL, '205.210.31.136', '', 'US', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 23.80, '2025-11-30 06:58:01', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 12.00, 30.00, '9b4999b8aa1926b14072217184c307651503fa8c20d79ce06767e3a5107323f4', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(250, 2, NULL, '147.185.133.190', 'Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity', 'US', 'unknown', 'Unknown', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 07:03:56', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '0d5b6222c1bd3b380703ff60e18bd666eaf9abe27288607ef45e56b62834a550', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(251, 2, NULL, '176.53.219.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36', 'RU', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 07:18:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '103f82967276df209c45206b3d5d81e6f593cbae5e404db76a87121f63c99415', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(252, 2, NULL, '217.114.43.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'RU', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 07:29:57', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'c3d5a696c6bc00399abb9fabd422a941cb74a922c9fbc94904172a5b93ac9670', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(253, 2, NULL, '162.62.213.187', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'DE', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 07:51:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '958dd48e09c920ed43e6266e750e525a9ac0f0a77b2cdcb12ea9bedacc1f7221', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(254, 2, NULL, '14.215.163.132', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'CN', 'macos', 'Safari', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 07:55:33', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '72e09fdf2c99d6a2f60df3ebdbd84f125dee52ef95d1bb1aea17075c1a9b64ed', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(255, 2, NULL, '160.225.169.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36', 'RU', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 08:02:03', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '7ec48ad2609e3e96ae24fc96399071fc8a047ea96df302a30f5252f96ca64d37', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(256, 2, NULL, '176.53.219.26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36', 'RU', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 08:02:13', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'e63141ecf0a2c38ba5ed86f0bf779128bb589c815b2abe51ecd9b69f31ebc98d', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(257, 2, NULL, '34.140.92.201', 'python-requests/2.32.5', 'BE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 28.60, '2025-11-30 08:41:35', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 16.00, 30.00, 'f516f3286d68bb8e56d6a6bd25c8fa67289faa76715a1b7fda4faf00831dddc5', '201.92.140.34.bc.googleusercontent.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(258, 2, NULL, '66.132.153.122', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'US', 'unknown', 'Mozilla', '', 0, 0, 'fake', 1, 1, 20.60, '2025-11-30 08:49:33', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, '1e5f67b5ea81e927a6d83236c898ca1fc45be6df8b970d963042054a55ead714', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(259, 2, NULL, '3.131.215.38', 'cypex.ai/scanning Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/126.0.0.0 Safari/537.36', 'US', 'macos', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 09:14:42', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '03f110c4cb38053f1f635c1c288cfd447bf1695fc301df4f501c4b4b40c8685b', 'ec2-3-131-215-38.us-east-2.compute.amazonaws.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(260, 2, NULL, '3.131.215.38', 'cypex.ai/scanning Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/126.0.0.0 Safari/537.36', 'US', 'macos', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 09:15:29', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '03f110c4cb38053f1f635c1c288cfd447bf1695fc301df4f501c4b4b40c8685b', 'ec2-3-131-215-38.us-east-2.compute.amazonaws.com', 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(261, 2, NULL, '157.230.252.44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36', 'SG', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 09:47:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '70e7c7bc1d2cbdc452775a9cc2b77c4efa40a89f326742989435a9f6ec87ddb3', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(262, 2, NULL, '157.230.252.44', 'Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 10.0; .NET4.0C; .NET4.0E; .NET CLR 2.0.50727; .NET CLR 3.0.30729; .NET CLR 3.5.30729)', 'SG', 'windows', 'Internet Explorer', '', 1, 0, 'fake', 1, 1, 20.60, '2025-11-30 09:47:11', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 7.00, 30.00, '44ebc6fae343db454b098ef15f513a135df0868ef5354f72158dbd36991889e0', NULL, 0, 1.0000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(263, 2, NULL, '54.227.66.133', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36', 'US', 'linux', 'Chrome', '', 1, 0, 'fake', 1, 0, 0.00, '2025-11-30 10:14:02', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(264, 2, NULL, '176.65.148.246', 'Mozilla/5.0 (X11; Linux x86_64; rv:140.0) Gecko/20100101 Firefox/140.0', 'NL', 'linux', 'Firefox', '', 1, 0, 'fake', 1, 1, 7.00, '2025-11-30 10:19:35', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(265, 2, NULL, '193.142.147.209', '', 'NL', 'unknown', 'Unknown', '', 0, 1, 'fake', 1, 1, 10.00, '2025-11-30 10:26:51', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `cloacker_visitors` (`id`, `site_id`, `api_key_id`, `ip`, `user_agent`, `country`, `os`, `browser`, `referer`, `is_proxy`, `is_bot`, `redirect_target`, `is_fake_url`, `fingerprint_score`, `bot_confidence`, `created_at`, `ja3_hash`, `ja3s_hash`, `http2_fingerprint`, `canvas_fingerprint`, `webgl_fingerprint`, `audio_fingerprint`, `webrtc_leak`, `local_ip_detected`, `fonts_hash`, `plugins_hash`, `extensions_detected`, `extensions_count`, `ml_confidence`, `dynamic_threshold`, `fingerprint_hash`, `rdns_hostname`, `rdns_is_bot`, `fingerprint_similarity`, `behavioral_bot_score`, `asn`, `asn_name`, `is_datacenter`, `ip_age_days`, `fraud_score`, `tls13_fingerprint`, `threat_score`, `threat_source`) VALUES
(266, 2, NULL, '79.124.40.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36', 'BG', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 0.00, '2025-11-30 10:33:57', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(267, 2, NULL, '35.195.241.97', 'python-requests/2.32.5', 'BE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 18.00, '2025-11-30 10:41:12', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(268, 2, NULL, '170.106.167.214', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'US', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 0.00, '2025-11-30 10:43:24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(269, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 0.00, '2025-11-30 10:45:12', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(270, 2, NULL, '87.236.176.97', 'Mozilla/5.0 (compatible; InternetMeasurement/1.0; +https://internet-measurement.com/)', 'GB', 'unknown', 'Mozilla', '', 0, 0, 'fake', 1, 1, 7.00, '2025-11-30 10:50:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(271, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 11:10:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(272, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 11:10:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(273, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 11:10:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(274, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 11:10:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '5e833e7f4b1ab298aaa830b333ae4b0666628a8a3402bfb91fe2da8286508e8f', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(275, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 11:10:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'a71dff7d901bcfa17588613ccaee636c9b1bf5b4dc4a9321be1d22d808d661a1', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(276, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 11:10:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '7a8fa71126ba8605076c046182c717140fd46b0921895e7404d32ff204f6a7a4', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(277, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 11:10:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'cdd8072d04e0459e7069a496475f438866feb1bede85e38fa1a8c1740ccba239', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(278, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 55.40, '2025-11-30 11:10:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'e0b3f10076096eaa1eaf23bb7fd36f6c7e1279a13774787493b0b01637641d48', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(279, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 11:10:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(280, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 11:10:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '470c93708558e6a7cf96c088e6416e217429ce1a03065f75d137ca64b4556214', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(281, 2, NULL, '185.247.137.182', 'Mozilla/5.0 (compatible; InternetMeasurement/1.0; +https://internet-measurement.com/)', 'GB', 'unknown', 'Mozilla', '', 0, 0, 'fake', 1, 1, 17.80, '2025-11-30 11:34:18', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'e4c6e7f4bb125dcd0386de92b85d94505d21e2e8f8938f1eb7efdedb00cdd116', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(282, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 11:44:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(283, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 11:44:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(284, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 11:44:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(285, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 11:44:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '5e833e7f4b1ab298aaa830b333ae4b0666628a8a3402bfb91fe2da8286508e8f', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(286, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 11:44:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'a71dff7d901bcfa17588613ccaee636c9b1bf5b4dc4a9321be1d22d808d661a1', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(287, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 11:44:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '7a8fa71126ba8605076c046182c717140fd46b0921895e7404d32ff204f6a7a4', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(288, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 11:44:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'cdd8072d04e0459e7069a496475f438866feb1bede85e38fa1a8c1740ccba239', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(289, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 55.40, '2025-11-30 11:44:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'e0b3f10076096eaa1eaf23bb7fd36f6c7e1279a13774787493b0b01637641d48', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(290, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 11:44:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(291, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 11:44:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '470c93708558e6a7cf96c088e6416e217429ce1a03065f75d137ca64b4556214', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(292, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 40.00, '2025-11-30 11:59:59', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, '34984', 'Superonline Iletisim Hizmetleri A.S.', 1, NULL, NULL, NULL, NULL, NULL),
(293, 2, NULL, '221.229.106.25', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'CN', 'macos', 'Safari', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 12:00:16', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'ae63ad175da03f39307b7503244332f0919838c0684d8564e04dd8649d0f0b42', NULL, 0, NULL, NULL, '146966', 'China Telecom', 0, NULL, NULL, NULL, NULL, NULL),
(294, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 40.00, '2025-11-30 12:09:40', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, '34984', 'Superonline Iletisim Hizmetleri A.S.', 1, NULL, NULL, NULL, NULL, NULL),
(295, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 83.20, '2025-11-30 12:10:36', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(296, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 83.20, '2025-11-30 12:10:36', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(297, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 75.60, '2025-11-30 12:10:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(298, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 83.20, '2025-11-30 12:10:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '5e833e7f4b1ab298aaa830b333ae4b0666628a8a3402bfb91fe2da8286508e8f', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(299, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 75.60, '2025-11-30 12:10:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'a71dff7d901bcfa17588613ccaee636c9b1bf5b4dc4a9321be1d22d808d661a1', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(300, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 78.40, '2025-11-30 12:10:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '7a8fa71126ba8605076c046182c717140fd46b0921895e7404d32ff204f6a7a4', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(301, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 78.40, '2025-11-30 12:10:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'cdd8072d04e0459e7069a496475f438866feb1bede85e38fa1a8c1740ccba239', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(302, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 80.40, '2025-11-30 12:10:38', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'e0b3f10076096eaa1eaf23bb7fd36f6c7e1279a13774787493b0b01637641d48', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(303, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 75.60, '2025-11-30 12:10:38', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(304, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 75.60, '2025-11-30 12:10:38', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '470c93708558e6a7cf96c088e6416e217429ce1a03065f75d137ca64b4556214', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(305, 2, NULL, '159.65.132.48', 'Mozilla/5.0 (Linux; Android 5.1.1; SM-J111F)', 'SG', 'android', 'Mozilla', '', 1, 0, 'fake', 1, 1, 42.80, '2025-11-30 12:14:27', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '45fb3dca817daf7102d6c39b308c36e269494a9b6413f6087631d27a74e93a20', NULL, 0, NULL, NULL, '14061', 'DigitalOcean LLC', 1, NULL, NULL, NULL, NULL, NULL),
(306, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 40.00, '2025-11-30 12:16:25', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, '34984', 'Superonline Iletisim Hizmetleri A.S.', 1, NULL, NULL, NULL, NULL, NULL),
(307, 2, NULL, '159.69.81.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'DE', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 40.00, '2025-11-30 12:17:10', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '3704d0627ebcfed5f6dacc53d3b424baa95940fb335c6276c0ad6ed6bcb820d4', 'server.aralikreklam.com.tr', 0, NULL, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(308, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 40.00, '2025-11-30 12:17:56', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'd7e5074ccabc868843a4b7046219d1e3828716fb018ef764b7d63c5cac46b5aa', NULL, 0, NULL, NULL, '34984', 'Superonline Iletisim Hizmetleri A.S.', 1, NULL, NULL, NULL, NULL, NULL),
(309, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 83.20, '2025-11-30 12:18:31', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(310, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 83.20, '2025-11-30 12:18:31', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(311, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 75.60, '2025-11-30 12:18:31', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(312, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 83.20, '2025-11-30 12:18:32', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '5e833e7f4b1ab298aaa830b333ae4b0666628a8a3402bfb91fe2da8286508e8f', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(313, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 75.60, '2025-11-30 12:18:32', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'a71dff7d901bcfa17588613ccaee636c9b1bf5b4dc4a9321be1d22d808d661a1', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(314, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 78.40, '2025-11-30 12:18:32', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '7a8fa71126ba8605076c046182c717140fd46b0921895e7404d32ff204f6a7a4', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(315, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 78.40, '2025-11-30 12:18:32', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'cdd8072d04e0459e7069a496475f438866feb1bede85e38fa1a8c1740ccba239', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(316, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 80.40, '2025-11-30 12:18:32', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'e0b3f10076096eaa1eaf23bb7fd36f6c7e1279a13774787493b0b01637641d48', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(317, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 75.60, '2025-11-30 12:18:33', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(318, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 75.60, '2025-11-30 12:18:33', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '470c93708558e6a7cf96c088e6416e217429ce1a03065f75d137ca64b4556214', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(319, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 9, 83.20, '2025-11-30 12:19:10', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', 's.dhostpro.site', 0, 1.0000, NULL, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(320, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 40.00, '2025-11-30 12:24:02', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, '34984', 'Superonline Iletisim Hizmetleri A.S.', 1, NULL, NULL, NULL, NULL, NULL),
(321, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 83.20, '2025-11-30 12:24:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', 's.dhostpro.site', 0, 1.0000, 81.00, '24940', 'Hetzner Online GmbH', 1, NULL, NULL, NULL, NULL, NULL),
(322, 2, NULL, '43.157.170.13', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'BR', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 40.00, '2025-11-30 12:26:25', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'aff10d2e4cb1841498b3af1b43d913c634534ae7acc7f49aaff49adb41ebe872', NULL, 0, NULL, NULL, '132203', 'Tencent Building Kejizhongyi Avenue', 1, NULL, NULL, NULL, NULL, NULL),
(323, 2, NULL, '101.33.81.73', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'KR', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 40.00, '2025-11-30 12:46:32', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'fec87f17b2dca2d5a9de74e1d7d9fb751ab8eab10f01d99401a97fa246762f60', NULL, 0, NULL, NULL, '132203', 'Tencent Building Kejizhongyi Avenue', 1, NULL, NULL, NULL, NULL, NULL),
(324, 2, NULL, '209.97.186.201', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'GB', 'linux', 'Chrome', '', 1, 0, 'fake', 1, 0, 40.00, '2025-11-30 13:00:30', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '3cd37a061bd8213b0382fb9dc1c7c01173a1806c0e77862b6e2accdca5bc6b14', NULL, 0, NULL, NULL, '14061', 'DigitalOcean LLC', 1, NULL, NULL, NULL, NULL, NULL),
(325, 2, NULL, '198.235.24.128', '', 'US', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 43.33, '2025-11-30 13:13:12', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '98c8b16d88ff0e2058b37c3e1ae55c472e47d4684d16fcef4bb8838215c864a3', NULL, 0, NULL, NULL, '396982', 'Google LLC', 1, NULL, NULL, NULL, NULL, NULL),
(326, 2, NULL, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 40.00, '2025-11-30 13:13:56', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, '34984', 'Superonline Iletisim Hizmetleri A.S.', 1, NULL, NULL, NULL, NULL, NULL),
(327, 2, 3, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 40.00, '2025-11-30 13:37:07', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, '34984', 'Superonline Iletisim Hizmetleri A.S.', 1, NULL, NULL, NULL, NULL, NULL),
(328, 2, 3, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 40.00, '2025-11-30 13:37:36', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'd7e5074ccabc868843a4b7046219d1e3828716fb018ef764b7d63c5cac46b5aa', NULL, 0, NULL, NULL, '34984', 'Superonline Iletisim Hizmetleri A.S.', 1, NULL, NULL, NULL, NULL, NULL),
(329, 2, 3, '167.94.138.122', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'US', 'unknown', 'Mozilla', '', 0, 0, 'fake', 1, 1, 17.80, '2025-11-30 14:02:46', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '913f87f3f5ad6997e40e59713de1cb13d0b381267c869c544aa246b86b091fed', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(330, 2, 3, '104.140.188.54', 'https://gdnplus.com:Gather Analyze Provide.', 'DE', 'unknown', 'Unknown', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 14:03:35', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'f5f8df5b5efafb00e78ca0d8b1816f2bc0be6bb3ed8718d7489d096e7b70c648', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(331, 2, 3, '64.227.184.157', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'IN', 'linux', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 14:04:41', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'd90dad17a9da31bf651980f51a6dc3a7ab15cdbd8deb9bbacc7c541506fa9cc7', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(332, 2, 3, '64.227.184.157', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'IN', 'linux', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 14:04:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'd90dad17a9da31bf651980f51a6dc3a7ab15cdbd8deb9bbacc7c541506fa9cc7', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(333, 2, 3, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-30 14:12:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'eaf6a7702eb745c6a29e800ae97aad7a00d666ef65c7f5cb045d593b1c67bc8e', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(334, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 9, 58.20, '2025-11-30 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'c4deda8072f5763c16dfa1a63603c85aa3023b8a57e0c38bb8ad33e604f1596e', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(335, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 9, 58.20, '2025-11-30 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'c4deda8072f5763c16dfa1a63603c85aa3023b8a57e0c38bb8ad33e604f1596e', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(336, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 7, 50.60, '2025-11-30 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '65154366e3155e0fc3fa79d5d55f19928a8ddd8bdb95d9440aafaefe98a0e5b3', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(337, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 9, 58.20, '2025-11-30 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'cf68c7fa2374f91b2cd0fdefd813f6472c2cd52068b107ca2ae4f164772e2046', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(338, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 7, 50.60, '2025-11-30 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '39a818efa0e77bcc4756aebe479ab74a9e779adaa6a6b731b5d5301ca5726470', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(339, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 8, 53.40, '2025-11-30 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '810ccca512f7e1ba2e0c2cfe30a7a2bed13374ed7b2eb3237afaa0db5edb30d2', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(340, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 8, 53.40, '2025-11-30 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '67fa63a4dd0edfc8277e85b800d8cc2ee4cee7f81db3e36f22f42f3458f3182f', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(341, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 8, 55.40, '2025-11-30 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '2eb349ffc1bd3fdf28a28e1ee250b09cef52369e89fc1153102e86fdd6dfbeca', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(342, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 7, 50.60, '2025-11-30 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '65154366e3155e0fc3fa79d5d55f19928a8ddd8bdb95d9440aafaefe98a0e5b3', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(343, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 7, 50.60, '2025-11-30 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '71beb8ea1b2043ba981774cc61d9c7c59e069d07c2f5440d9fb914e86369850e', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(344, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 9, 58.20, '2025-11-30 14:41:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'c4deda8072f5763c16dfa1a63603c85aa3023b8a57e0c38bb8ad33e604f1596e', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(345, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 9, 58.20, '2025-11-30 14:41:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'c4deda8072f5763c16dfa1a63603c85aa3023b8a57e0c38bb8ad33e604f1596e', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(346, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 7, 50.60, '2025-11-30 14:41:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '65154366e3155e0fc3fa79d5d55f19928a8ddd8bdb95d9440aafaefe98a0e5b3', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(347, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 9, 58.20, '2025-11-30 14:41:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'cf68c7fa2374f91b2cd0fdefd813f6472c2cd52068b107ca2ae4f164772e2046', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(348, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 7, 50.60, '2025-11-30 14:41:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '39a818efa0e77bcc4756aebe479ab74a9e779adaa6a6b731b5d5301ca5726470', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(349, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 8, 53.40, '2025-11-30 14:41:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '810ccca512f7e1ba2e0c2cfe30a7a2bed13374ed7b2eb3237afaa0db5edb30d2', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(350, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 8, 53.40, '2025-11-30 14:41:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '67fa63a4dd0edfc8277e85b800d8cc2ee4cee7f81db3e36f22f42f3458f3182f', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(351, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 8, 55.40, '2025-11-30 14:41:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '2eb349ffc1bd3fdf28a28e1ee250b09cef52369e89fc1153102e86fdd6dfbeca', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(352, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 7, 50.60, '2025-11-30 14:41:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '65154366e3155e0fc3fa79d5d55f19928a8ddd8bdb95d9440aafaefe98a0e5b3', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(353, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 0, 1, 'fake', 0, 7, 50.60, '2025-11-30 14:41:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '71beb8ea1b2043ba981774cc61d9c7c59e069d07c2f5440d9fb914e86369850e', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(354, 2, 3, '3.137.73.221', 'cypex.ai/scanning Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/126.0.0.0 Safari/537.36', 'US', 'macos', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 14:52:26', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '2115ec88be6bd2bf2f191b265bcb2f69dcac35c155226d29cf7ba6afcedbe5b0', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(355, 2, 3, '124.156.226.179', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'JP', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 14:54:26', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'f4bd085fac8fc6eeb3c9d72dd012417034e43d8ee8860ea2c866534ef7612ee7', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(356, 2, 3, '170.106.35.137', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'US', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:06:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '0def111cb3a08cd10bee5bdbb3ba7276d7463cdac13658a594756f31e61ffbbf', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(357, 2, 3, '213.209.143.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36', 'DE', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:14:36', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '9df31b462e37c82572ea4221ff5023f26e93a358472de3d93ec4709aab79412e', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(358, 2, 3, '213.209.143.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36', 'DE', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:14:36', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'f9e64e3abd9bb7da560163ccf49e6e8bc28d2a5b5a7dd5610fc2438846c1d03b', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(359, 2, 3, '213.209.143.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36', 'DE', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:14:36', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '059fe18c172c5f6b6ca7a79d1ff6b0dae3ef7a1a352ff17246305f73b5df291e', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(360, 2, 3, '213.209.143.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36', 'DE', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:14:36', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '0611b3e63897346dd1617c8b121ad24e51bbc31d7caa1a2ac8b2e7c66efd4357', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(361, 2, 3, '213.209.143.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36', 'DE', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:14:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '059fe18c172c5f6b6ca7a79d1ff6b0dae3ef7a1a352ff17246305f73b5df291e', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(362, 2, 3, '213.209.143.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36', 'DE', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:14:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'bbec7077e4550b13af4391fce9b7806e72137113d81dfd73d768fbed2785f84f', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(363, 2, 3, '213.209.143.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', 'DE', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:14:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'de6fc0c2f6cb484502f237ec089acfb01487e27289aeb8902a21af79efdb86dc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(364, 2, 3, '213.209.143.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36', 'DE', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:14:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'eba14d9501f373e9d91fb42611f50ad9d914904eadf1e80c94d9f56bc3062789', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(365, 2, 3, '185.196.97.69', 'Mozilla/5.0 (Linux; U; Android 4.4.2; en-US; HM NOTE 1W Build/KOT49H) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 UCBrowser/11.0.5.850 U3/0.8.0 Mobile Safari/534.30', 'SG', 'android', 'Safari', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:17:00', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '199fca23557784969022afa71ecbcba5fc10d6d5382d30d0c78cf5c4f99ab302', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(366, 2, 3, '185.196.97.69', 'Mozilla/5.0 (Linux; U; Android 4.4.2; en-US; HM NOTE 1W Build/KOT49H) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 UCBrowser/11.0.5.850 U3/0.8.0 Mobile Safari/534.30', 'SG', 'android', 'Safari', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:17:02', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '199fca23557784969022afa71ecbcba5fc10d6d5382d30d0c78cf5c4f99ab302', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(367, 2, 3, '23.23.27.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'US', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:28:03', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '38a7da1f2e862ad69b90a2df7d4fc2e1361e8a4cfdbbf18e55716d90cd44b8c1', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(368, 2, 3, '2.57.171.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.69 Safari/537.36', 'BR', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 15:58:06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '01864717ac519560ea98ce576852023c3244c9a749b0ad4c8e5c96e819bddc0d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(369, 2, 3, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-30 16:12:15', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'eaf6a7702eb745c6a29e800ae97aad7a00d666ef65c7f5cb045d593b1c67bc8e', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(370, 2, 3, '43.159.149.56', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'US', 'macos', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 16:26:27', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '5c672e0ded7438ea31f9897a73dcb26f9348ed7d14ecd9fe133b1ba28df1a4c5', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(371, 2, 3, '93.174.93.12', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.0 Safari/605.1.15 Epiphany/605.1.15', 'NL', 'linux', 'Safari', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 16:47:59', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '3cc6e4f66efa38c1034b63ad3bea6ba3cddc6245ce759759e1d523b75763797d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(372, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 16:53:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(373, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 16:53:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `cloacker_visitors` (`id`, `site_id`, `api_key_id`, `ip`, `user_agent`, `country`, `os`, `browser`, `referer`, `is_proxy`, `is_bot`, `redirect_target`, `is_fake_url`, `fingerprint_score`, `bot_confidence`, `created_at`, `ja3_hash`, `ja3s_hash`, `http2_fingerprint`, `canvas_fingerprint`, `webgl_fingerprint`, `audio_fingerprint`, `webrtc_leak`, `local_ip_detected`, `fonts_hash`, `plugins_hash`, `extensions_detected`, `extensions_count`, `ml_confidence`, `dynamic_threshold`, `fingerprint_hash`, `rdns_hostname`, `rdns_is_bot`, `fingerprint_similarity`, `behavioral_bot_score`, `asn`, `asn_name`, `is_datacenter`, `ip_age_days`, `fraud_score`, `tls13_fingerprint`, `threat_score`, `threat_source`) VALUES
(374, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 16:53:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(375, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 16:53:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '5e833e7f4b1ab298aaa830b333ae4b0666628a8a3402bfb91fe2da8286508e8f', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(376, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 16:53:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'a71dff7d901bcfa17588613ccaee636c9b1bf5b4dc4a9321be1d22d808d661a1', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(377, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 16:53:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '7a8fa71126ba8605076c046182c717140fd46b0921895e7404d32ff204f6a7a4', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(378, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 16:53:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'cdd8072d04e0459e7069a496475f438866feb1bede85e38fa1a8c1740ccba239', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(379, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 55.40, '2025-11-30 16:53:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'e0b3f10076096eaa1eaf23bb7fd36f6c7e1279a13774787493b0b01637641d48', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(380, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 16:53:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(381, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 16:53:09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '470c93708558e6a7cf96c088e6416e217429ce1a03065f75d137ca64b4556214', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(382, 2, 3, '86.54.31.32', 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36', 'NL', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 17:10:27', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '64f095140349e17ccd31ceca3663bb5f4d6b40d4785a3e2892c3eac4d07a278d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(383, 2, 3, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-30 17:21:41', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(384, 2, 3, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-30 17:21:41', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(385, 2, 3, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-30 17:21:44', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(386, 2, 3, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-30 17:21:45', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(387, 2, 3, '79.124.40.86', '', 'BG', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 19.00, '2025-11-30 17:22:17', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '84935fbc0d8f9d4510f278729bf2805dc230bc2b1ba373e223c785ca0cf58942', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(388, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 17:22:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(389, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 17:22:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(390, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:22:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(391, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 17:22:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '5e833e7f4b1ab298aaa830b333ae4b0666628a8a3402bfb91fe2da8286508e8f', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(392, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:22:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'a71dff7d901bcfa17588613ccaee636c9b1bf5b4dc4a9321be1d22d808d661a1', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(393, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 17:22:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '7a8fa71126ba8605076c046182c717140fd46b0921895e7404d32ff204f6a7a4', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(394, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 17:22:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'cdd8072d04e0459e7069a496475f438866feb1bede85e38fa1a8c1740ccba239', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(395, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 8, 55.40, '2025-11-30 17:22:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'e0b3f10076096eaa1eaf23bb7fd36f6c7e1279a13774787493b0b01637641d48', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(396, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:22:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(397, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:22:24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '470c93708558e6a7cf96c088e6416e217429ce1a03065f75d137ca64b4556214', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(398, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 17:25:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(399, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 17:25:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(400, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:25:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(401, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 17:25:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '5e833e7f4b1ab298aaa830b333ae4b0666628a8a3402bfb91fe2da8286508e8f', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(402, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:25:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'a71dff7d901bcfa17588613ccaee636c9b1bf5b4dc4a9321be1d22d808d661a1', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(403, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 17:25:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '7a8fa71126ba8605076c046182c717140fd46b0921895e7404d32ff204f6a7a4', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(404, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 17:25:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'cdd8072d04e0459e7069a496475f438866feb1bede85e38fa1a8c1740ccba239', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(405, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 8, 55.40, '2025-11-30 17:25:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'e0b3f10076096eaa1eaf23bb7fd36f6c7e1279a13774787493b0b01637641d48', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(406, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:25:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(407, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:25:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '470c93708558e6a7cf96c088e6416e217429ce1a03065f75d137ca64b4556214', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(408, NULL, NULL, '66.249.66.1', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'US', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 17:28:38', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '7a4d126e0f6e01d2bc39c38dd69a5b5395a22bde8dee7c093389106abe42562c', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(409, NULL, NULL, '31.13.24.0', 'WhatsApp/2.0', 'US', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?clear_logs=1', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:29:49', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'ccc63d29dcc4b423da9a41dbe0b038c9a870b1164aaddd060074fa187eb0f232', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(410, NULL, NULL, '54.236.1.0', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'US', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?run_test=1&bot=whatsapp', 1, 1, 'fake', 1, 8, 55.40, '2025-11-30 17:30:01', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'e9c579a6f236b4bf74095f1e45000c31b7a1db8f2c61c9466767c96f824367c9', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(411, 2, 3, '212.253.187.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'TR', 'windows', 'Chrome', '', 0, 0, 'normal', 0, 0, 15.00, '2025-11-30 17:45:51', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8789c0a841d204505e3381ec91f12f79a029659bb34b241a86ea7e34002df387', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(412, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?target_url=https%3A%2F%2Fdhostpro.site', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 17:46:54', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(413, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?target_url=https%3A%2F%2Fdhostpro.site', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 17:46:54', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(414, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?target_url=https%3A%2F%2Fdhostpro.site', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:46:54', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(415, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?target_url=https%3A%2F%2Fdhostpro.site', 1, 1, 'fake', 1, 9, 58.20, '2025-11-30 17:46:54', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '5e833e7f4b1ab298aaa830b333ae4b0666628a8a3402bfb91fe2da8286508e8f', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(416, NULL, NULL, '46.224.33.89', 'Twitterbot/1.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?target_url=https%3A%2F%2Fdhostpro.site', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:46:54', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'a71dff7d901bcfa17588613ccaee636c9b1bf5b4dc4a9321be1d22d808d661a1', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(417, NULL, NULL, '46.224.33.89', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?target_url=https%3A%2F%2Fdhostpro.site', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 17:46:54', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '7a8fa71126ba8605076c046182c717140fd46b0921895e7404d32ff204f6a7a4', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(418, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?target_url=https%3A%2F%2Fdhostpro.site', 1, 1, 'fake', 1, 8, 53.40, '2025-11-30 17:46:54', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'cdd8072d04e0459e7069a496475f438866feb1bede85e38fa1a8c1740ccba239', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(419, NULL, NULL, '46.224.33.89', 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?target_url=https%3A%2F%2Fdhostpro.site', 1, 1, 'fake', 1, 8, 55.40, '2025-11-30 17:46:54', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'e0b3f10076096eaa1eaf23bb7fd36f6c7e1279a13774787493b0b01637641d48', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(420, NULL, NULL, '46.224.33.89', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?target_url=https%3A%2F%2Fdhostpro.site', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:46:54', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '00d10ffbe5a8006d8e9ad295382053ed71bec8bd9e13504197dc7a1b02e098cc', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(421, NULL, NULL, '46.224.33.89', 'WhatsApp/2.0', 'DE', 'unknown', 'Unknown', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?target_url=https%3A%2F%2Fdhostpro.site', 1, 1, 'fake', 1, 7, 50.60, '2025-11-30 17:46:54', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, '470c93708558e6a7cf96c088e6416e217429ce1a03065f75d137ca64b4556214', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(422, 2, 3, '91.142.170.219', 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36', 'UA', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 17:53:57', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '26dad159b3f70826e77c9584198829e8177e83454aeca00bd567f2cdde46bd0a', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(423, 2, 3, '188.166.243.95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36', 'SG', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 17:54:18', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '8b2f2ba7747feaa77cc5c5866df04358a0ca45cd8933de2cefd464037439236f', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(424, NULL, NULL, '46.224.33.89', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'DE', 'unknown', 'Mozilla', 'https://hayalimbilgi.site/proje/admin/bot_simulation.php?target_url=https%3A%2F%2Fdhostpro.site', 0, 1, 'fake', 1, 9, 58.20, '2025-11-30 17:58:03', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 55.00, 30.00, 'f566e5e2c06fd3614d2581c8cd85b875df94b298a7b94cfae041022b7913ce9d', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(425, 2, 3, '3.253.253.119', 'Mozilla/4.0 (compatible; Netcraft Web Server Survey)', 'IE', 'unknown', 'Mozilla', '', 1, 0, 'fake', 1, 1, 17.80, '2025-11-30 18:01:12', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '40242c9f068a68c3629dd87f693314d53d27773670b78e195ef696d4c2178c69', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(426, 2, 3, '129.212.224.186', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36', 'US', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 18:31:48', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '369b603d9ea566fb01470a1bd7ae8a17f2d09466733093151fad3625d517c04e', NULL, 0, 0.9400, NULL, '', '', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(427, 2, 3, '2620:96:e000::108', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'US', 'unknown', 'Mozilla', '', 0, 0, 'fake', 1, 1, 17.80, '2025-11-30 18:37:24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'ee2a502a4ec69e4e5ad8dc682daf930d8627b7beb102120b30288b34359d26ce', NULL, 0, 0.9400, NULL, '398324 Censys, Inc.', 'CENSYS-ARIN-01', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(428, 2, 3, '2620:96:e000::114', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'US', 'unknown', 'Mozilla', '', 0, 0, 'fake', 1, 1, 17.80, '2025-11-30 19:45:07', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '6046f67939ffedbb6f57e1b254a5769ddd2852fc1c295cad2ab93f29f86154f5', NULL, 0, 0.9400, NULL, '398324 Censys, Inc.', 'CENSYS-ARIN-01', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(429, 2, 3, '185.242.226.80', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.190 Safari/537.36', 'NL', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 20:26:49', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '25391684da208bba8873f019699140a00bc90526662e522d711606a3f64744c8', NULL, 0, 0.9400, NULL, '202425 IP Volume inc', 'INT-NETWORK', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(430, 2, 3, '198.235.24.148', '', 'US', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 19.00, '2025-11-30 21:17:43', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '75bd80d56f026dd981f24d9d50de96d77255cdc9e1ef65500cc162997cff9a78', NULL, 0, 0.9400, NULL, '396982 Google LLC', 'GOOGLE-CLOUD-PLATFORM', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(431, 2, 3, '87.236.176.183', 'Mozilla/5.0 (compatible; InternetMeasurement/1.0; +https://internet-measurement.com/)', 'GB', 'unknown', 'Mozilla', '', 0, 0, 'fake', 1, 1, 17.80, '2025-11-30 21:31:38', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '44cae77988bfc791bc0579f22f649df4ffa2153f46d6937e079643baf8e09b51', NULL, 0, 0.9400, NULL, '211298 Driftnet Ltd', 'DRIFTNET', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(432, 2, 3, '78.11.16.228', 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36', 'PL', 'windows', 'Chrome', '', 0, 0, 'fake', 1, 0, 15.00, '2025-11-30 21:34:50', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'dfea3bc1c71f603ae9690681e697c8b6b82be3f3b67dbec593df97a935fac368', NULL, 0, 0.9400, NULL, '12741 Netia SA', 'AS-NETIA', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(433, 2, 3, '198.235.24.147', '', 'US', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 19.00, '2025-11-30 22:02:47', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '78b9e53b42ef19625a0fa70d080e2f3226060b2d1a33cfe0cb2075217dde4c55', NULL, 0, 0.9400, NULL, '396982 Google LLC', 'GOOGLE-CLOUD-PLATFORM', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(434, 2, 3, '66.132.153.115', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'US', 'unknown', 'Mozilla', '', 0, 0, 'fake', 1, 1, 17.80, '2025-11-30 22:55:03', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'c83257ed24c035d5d9fd14aea286b0ae789137c1e75d6b2b1ec2a147a841e0fb', NULL, 0, 0.9400, NULL, '398324 Censys, Inc.', 'CENSYS-ARIN-01', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(435, 2, 3, '185.242.226.80', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML,like Gecko) Chrome/95.0.4638.69 Safari/537.36', 'NL', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 22:59:01', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '1d5fec99dbe1754f0a52fefdeb20d21cce51e25f144a0620a02bc63af5fc9646', NULL, 0, 0.9400, NULL, '202425 IP Volume inc', 'INT-NETWORK', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(436, 2, 3, '34.79.159.68', 'python-requests/2.32.5', 'BE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 22.20, '2025-11-30 23:05:46', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '7757d0a8eb9a92185c75b2ba42ba8f7c66993f372cf61e79aa8ce2114c3f83aa', NULL, 0, 0.9400, NULL, '396982 Google LLC', 'GOOGLE-CLOUD-PLATFORM', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(437, 2, 3, '51.222.106.76', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.96 Safari/537.36 Edg/116.0.1938.62', 'CA', 'windows', 'Edge', '', 1, 0, 'fake', 1, 0, 15.00, '2025-11-30 23:43:44', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'ac49c6834b181338a9a025faefc10c02b1c8486aac284cbbbb4fc1a1870e3d90', NULL, 0, 0.9400, NULL, '16276 OVH SAS', 'OVH', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(438, 2, 3, '147.185.133.234', 'Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity', 'US', 'unknown', 'Unknown', '', 1, 0, 'fake', 1, 0, 15.00, '2025-12-01 00:01:01', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '150e508ded46e9df94f646c12518b3210b68d24edfc1ec3cd71c9900f1f05498', NULL, 0, 0.9400, NULL, '396982 Google LLC', 'GOOGLE-CLOUD-PLATFORM', 0, NULL, NULL, NULL, NULL, NULL),
(439, 2, 3, '71.6.232.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'US', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-12-01 00:13:03', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '0f0a095f6a1bb7db2b22bf17c832a267be77a0c21bd345ba2856af565aeda9f0', NULL, 0, 0.9400, NULL, '10439 CariNet, Inc.', 'CARINET', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(440, 2, 3, '34.76.134.123', 'python-requests/2.32.5', 'BE', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 22.20, '2025-12-01 00:47:11', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'e71ca61e8adeea6dfcf003c8b20f9aeba4df45e9e7195433350cf89008f6fb14', NULL, 0, 0.9400, NULL, '396982 Google LLC', 'GOOGLE-CLOUD-PLATFORM', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(441, 2, 3, '125.75.66.97', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', 'CN', 'macos', 'Safari', '', 0, 0, 'fake', 1, 0, 15.00, '2025-12-01 00:51:45', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'feafceab81b2aa29a595e017db5335f7d28e22104c518a7dce55ff3e5e7890b1', NULL, 0, 0.9400, NULL, '141998 China Telecom', 'CHINANET-LANZHOU-IDC', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(442, 2, 3, '66.249.64.13', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.122 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'US', 'android', 'Chrome', '', 1, 1, 'fake', 1, 1, 19.80, '2025-12-01 01:10:01', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '477e563c09c1f24cb90fc696217e0d325e0e89d9cd8643314f3b7a5243ce9187', NULL, 0, 0.9600, NULL, '15169 Google LLC', 'GOOGLE', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(443, 2, 3, '205.210.31.52', '', 'US', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 19.00, '2025-12-01 01:31:58', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'f4c31695fbddab73b50365270b71195bbfe43a8f53ccf662a6dfef6c63cbda1f', NULL, 0, 0.9400, NULL, '396982 Google LLC', 'GOOGLE-CLOUD-PLATFORM', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(444, 2, 3, '198.235.24.141', '', 'US', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 19.00, '2025-12-01 01:47:13', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '38e3e85f8fc00eee935c30e1e9db141ffa1d1aba9297052cac7636d644a80a04', NULL, 0, 0.9400, NULL, '396982 Google LLC', 'GOOGLE-CLOUD-PLATFORM', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(445, 2, 3, '147.185.132.52', 'Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity', 'US', 'unknown', 'Unknown', '', 1, 0, 'fake', 1, 0, 15.00, '2025-12-01 03:13:42', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '30acb0dc1391512bd7e8d6dc258026cfd67b7acd403be21a5d1400a7efbab24a', NULL, 0, 0.9400, NULL, '396982 Google LLC', 'GOOGLE-CLOUD-PLATFORM', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(446, 2, 3, '79.124.40.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36', 'BG', 'windows', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-12-01 03:32:26', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '95322346ec6d2b8fa84692c98e3910555471ef96940752eb200aed942b110586', NULL, 0, 0.9400, NULL, '50360 Tamatiya EOOD', 'TAMATIYA-AS', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(447, 2, 3, '204.76.203.52', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36', 'NL', 'linux', 'Chrome', '', 1, 0, 'fake', 1, 0, 15.00, '2025-12-01 06:13:57', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'b86495944bcfb4977acbd03d5143bbe16e5bf83d9f919433e63fbe3478164f59', NULL, 0, 0.9400, NULL, '51396 Pfcloud UG', 'PFCLOUD', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(448, 2, 3, '35.227.136.42', '', 'US', 'unknown', 'Unknown', '', 1, 1, 'fake', 1, 1, 19.00, '2025-12-01 08:34:38', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, 'ff15c2f54e4ab86c18b3cbf98eb4256271a3d81f3b9bbfed439309bda789fb8e', NULL, 0, 0.9400, NULL, '396982 Google LLC', 'GOOGLE-CLOUD-PLATFORM', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(449, 2, 3, '162.216.150.182', 'Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity', 'US', 'unknown', 'Unknown', '', 1, 0, 'fake', 1, 0, 15.00, '2025-12-01 08:49:20', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '86bfa1efe7c83064f471196917aa95c0e251e33d1d79c5b08c1672277ba9e6e1', NULL, 0, 0.9400, NULL, '396982 Google LLC', 'GOOGLE-CLOUD-PLATFORM', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB'),
(450, 2, 3, '35.216.195.77', 'Mozilla/5.0', 'CH', 'unknown', 'Mozilla', '', 0, 0, 'fake', 1, 1, 17.80, '2025-12-01 09:02:53', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0.00, 30.00, '9538bbacdf235b8547564705bbe01504293d031e3e229261ad5392c3474e24d5', NULL, 0, 0.9400, NULL, '15169 Google LLC', 'GOOGLE', 0, NULL, NULL, NULL, 0.00, 'AbuseIPDB');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cloacker_ab_tests`
--
ALTER TABLE `cloacker_ab_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `test_type` (`test_type`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `cloacker_ab_test_daily_stats`
--
ALTER TABLE `cloacker_ab_test_daily_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `test_date_variant` (`test_id`,`test_date`,`variant`),
  ADD KEY `test_id` (`test_id`),
  ADD KEY `test_date` (`test_date`);

--
-- Indexes for table `cloacker_ab_test_results`
--
ALTER TABLE `cloacker_ab_test_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `test_id` (`test_id`),
  ADD KEY `visitor_id` (`visitor_id`),
  ADD KEY `variant` (`variant`),
  ADD KEY `test_date` (`test_date`),
  ADD KEY `is_bot` (`is_bot`);

--
-- Indexes for table `cloacker_admins`
--
ALTER TABLE `cloacker_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cloacker_admin_logins`
--
ALTER TABLE `cloacker_admin_logins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `ip` (`ip`),
  ADD KEY `login_time` (`login_time`),
  ADD KEY `success` (`success`);

--
-- Indexes for table `cloacker_allowed_countries`
--
ALTER TABLE `cloacker_allowed_countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `country` (`country`);

--
-- Indexes for table `cloacker_api_keys`
--
ALTER TABLE `cloacker_api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `site_id` (`site_id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `cloacker_behavioral_data`
--
ALTER TABLE `cloacker_behavioral_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visitor_id` (`visitor_id`),
  ADD KEY `fingerprint_hash` (`fingerprint_hash`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `cloacker_bots`
--
ALTER TABLE `cloacker_bots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `active` (`active`);

--
-- Indexes for table `cloacker_bot_detections`
--
ALTER TABLE `cloacker_bot_detections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visitor_id` (`visitor_id`);

--
-- Indexes for table `cloacker_bot_stats`
--
ALTER TABLE `cloacker_bot_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bot_id` (`bot_id`),
  ADD KEY `visit_time` (`visit_time`);

--
-- Indexes for table `cloacker_fingerprint_history`
--
ALTER TABLE `cloacker_fingerprint_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fingerprint_hash` (`fingerprint_hash`),
  ADD KEY `is_verified_human` (`is_verified_human`),
  ADD KEY `last_seen` (`last_seen`);

--
-- Indexes for table `cloacker_ja3_blacklist`
--
ALTER TABLE `cloacker_ja3_blacklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ja3_hash` (`ja3_hash`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `cloacker_migrations`
--
ALTER TABLE `cloacker_migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `migration_name` (`migration_name`);

--
-- Indexes for table `cloacker_ml_training`
--
ALTER TABLE `cloacker_ml_training`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visitor_id` (`visitor_id`),
  ADD KEY `label` (`label`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `cloacker_password_resets`
--
ALTER TABLE `cloacker_password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `cloacker_rate_limits`
--
ALTER TABLE `cloacker_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_fp` (`ip`,`fingerprint_hash`),
  ADD KEY `window_start` (`window_start`);

--
-- Indexes for table `cloacker_rdns_cache`
--
ALTER TABLE `cloacker_rdns_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip` (`ip`),
  ADD KEY `is_bot` (`is_bot`),
  ADD KEY `last_checked` (`last_checked`);

--
-- Indexes for table `cloacker_settings`
--
ALTER TABLE `cloacker_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cloacker_sites`
--
ALTER TABLE `cloacker_sites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `domain` (`domain`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `cloacker_visitors`
--
ALTER TABLE `cloacker_visitors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `site_id` (`site_id`),
  ADD KEY `api_key_id` (`api_key_id`),
  ADD KEY `ip` (`ip`),
  ADD KEY `country` (`country`),
  ADD KEY `is_bot` (`is_bot`),
  ADD KEY `is_proxy` (`is_proxy`),
  ADD KEY `redirect_target` (`redirect_target`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_ja3_hash` (`ja3_hash`),
  ADD KEY `idx_canvas_fp` (`canvas_fingerprint`),
  ADD KEY `idx_webrtc_leak` (`webrtc_leak`),
  ADD KEY `idx_fingerprint_hash` (`fingerprint_hash`),
  ADD KEY `idx_tls13_fingerprint` (`tls13_fingerprint`),
  ADD KEY `idx_threat_score` (`threat_score`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cloacker_ab_tests`
--
ALTER TABLE `cloacker_ab_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cloacker_ab_test_daily_stats`
--
ALTER TABLE `cloacker_ab_test_daily_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cloacker_ab_test_results`
--
ALTER TABLE `cloacker_ab_test_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cloacker_admins`
--
ALTER TABLE `cloacker_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cloacker_admin_logins`
--
ALTER TABLE `cloacker_admin_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `cloacker_allowed_countries`
--
ALTER TABLE `cloacker_allowed_countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cloacker_api_keys`
--
ALTER TABLE `cloacker_api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cloacker_behavioral_data`
--
ALTER TABLE `cloacker_behavioral_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cloacker_bots`
--
ALTER TABLE `cloacker_bots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cloacker_bot_detections`
--
ALTER TABLE `cloacker_bot_detections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cloacker_bot_stats`
--
ALTER TABLE `cloacker_bot_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cloacker_fingerprint_history`
--
ALTER TABLE `cloacker_fingerprint_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `cloacker_ja3_blacklist`
--
ALTER TABLE `cloacker_ja3_blacklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `cloacker_migrations`
--
ALTER TABLE `cloacker_migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cloacker_ml_training`
--
ALTER TABLE `cloacker_ml_training`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cloacker_password_resets`
--
ALTER TABLE `cloacker_password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cloacker_rate_limits`
--
ALTER TABLE `cloacker_rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=426;

--
-- AUTO_INCREMENT for table `cloacker_rdns_cache`
--
ALTER TABLE `cloacker_rdns_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `cloacker_settings`
--
ALTER TABLE `cloacker_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cloacker_sites`
--
ALTER TABLE `cloacker_sites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cloacker_visitors`
--
ALTER TABLE `cloacker_visitors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=451;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cloacker_ab_tests`
--
ALTER TABLE `cloacker_ab_tests`
  ADD CONSTRAINT `fk_ab_tests_created_by` FOREIGN KEY (`created_by`) REFERENCES `cloacker_admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cloacker_ab_test_daily_stats`
--
ALTER TABLE `cloacker_ab_test_daily_stats`
  ADD CONSTRAINT `fk_ab_test_daily_stats_test` FOREIGN KEY (`test_id`) REFERENCES `cloacker_ab_tests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cloacker_ab_test_results`
--
ALTER TABLE `cloacker_ab_test_results`
  ADD CONSTRAINT `fk_ab_test_results_test` FOREIGN KEY (`test_id`) REFERENCES `cloacker_ab_tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ab_test_results_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `cloacker_visitors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cloacker_admin_logins`
--
ALTER TABLE `cloacker_admin_logins`
  ADD CONSTRAINT `fk_admin_logins_admin` FOREIGN KEY (`admin_id`) REFERENCES `cloacker_admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cloacker_api_keys`
--
ALTER TABLE `cloacker_api_keys`
  ADD CONSTRAINT `fk_api_keys_created_by` FOREIGN KEY (`created_by`) REFERENCES `cloacker_admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_api_keys_site` FOREIGN KEY (`site_id`) REFERENCES `cloacker_sites` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cloacker_behavioral_data`
--
ALTER TABLE `cloacker_behavioral_data`
  ADD CONSTRAINT `fk_behavioral_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `cloacker_visitors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cloacker_bot_detections`
--
ALTER TABLE `cloacker_bot_detections`
  ADD CONSTRAINT `fk_bot_detections_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `cloacker_visitors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cloacker_bot_stats`
--
ALTER TABLE `cloacker_bot_stats`
  ADD CONSTRAINT `fk_bot_stats_bot` FOREIGN KEY (`bot_id`) REFERENCES `cloacker_bots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cloacker_ml_training`
--
ALTER TABLE `cloacker_ml_training`
  ADD CONSTRAINT `fk_ml_training_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `cloacker_visitors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cloacker_password_resets`
--
ALTER TABLE `cloacker_password_resets`
  ADD CONSTRAINT `fk_password_resets_admin` FOREIGN KEY (`admin_id`) REFERENCES `cloacker_admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cloacker_sites`
--
ALTER TABLE `cloacker_sites`
  ADD CONSTRAINT `fk_sites_created_by` FOREIGN KEY (`created_by`) REFERENCES `cloacker_admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cloacker_visitors`
--
ALTER TABLE `cloacker_visitors`
  ADD CONSTRAINT `fk_visitors_api_key` FOREIGN KEY (`api_key_id`) REFERENCES `cloacker_api_keys` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_visitors_site` FOREIGN KEY (`site_id`) REFERENCES `cloacker_sites` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
