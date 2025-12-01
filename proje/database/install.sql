-- ============================================
-- CLOACKER SİSTEMİ - SIFIR KURULUM SQL
-- ============================================
-- Bu dosya sistemi sıfırdan kurmak için kullanılır.
-- Tüm tablolar ve gerekli yapılar burada tanımlanmıştır.
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Veritabanı oluştur (eğer yoksa)
CREATE DATABASE IF NOT EXISTS `cloacker_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cloacker_db`;

-- ============================================
-- 1. ADMIN KULLANICILARI
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan admin kullanıcı (username: admin, password: admin123)
-- NOT: Şifre hash'i her kurulumda farklı olacaktır, bu yüzden reset_admin_password.php scriptini kullanın
-- Veya manuel olarak: UPDATE cloacker_admins SET password_hash = '$2y$10$...' WHERE username = 'admin';
INSERT INTO `cloacker_admins` (`username`, `password_hash`, `email`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- ============================================
-- 2. SİTELER
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `normal_url` text NOT NULL,
  `fake_url` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `settings` text DEFAULT NULL COMMENT 'JSON formatında site özel ayarlar',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `domain` (`domain`),
  KEY `is_active` (`is_active`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_sites_created_by` FOREIGN KEY (`created_by`) REFERENCES `cloacker_admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. API ANAHTARLARI
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `api_secret` varchar(64) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `site_id` (`site_id`),
  KEY `is_active` (`is_active`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_api_keys_site` FOREIGN KEY (`site_id`) REFERENCES `cloacker_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_api_keys_created_by` FOREIGN KEY (`created_by`) REFERENCES `cloacker_admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. ZİYARETÇİ LOGLARI
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `redirect_target` enum('normal','fake') NOT NULL DEFAULT 'normal',
  `is_fake_url` tinyint(1) NOT NULL DEFAULT 0,
  `fingerprint_score` int(11) DEFAULT NULL,
  `bot_confidence` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`),
  KEY `api_key_id` (`api_key_id`),
  KEY `ip` (`ip`),
  KEY `country` (`country`),
  KEY `is_bot` (`is_bot`),
  KEY `is_proxy` (`is_proxy`),
  KEY `redirect_target` (`redirect_target`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_visitors_site` FOREIGN KEY (`site_id`) REFERENCES `cloacker_sites` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_visitors_api_key` FOREIGN KEY (`api_key_id`) REFERENCES `cloacker_api_keys` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. BOT TESPİT DETAYLARI
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_bot_detections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_id` int(11) NOT NULL,
  `detection_type` varchar(50) NOT NULL,
  `score` int(11) DEFAULT 0,
  `details` text DEFAULT NULL COMMENT 'JSON formatında detaylar',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `visitor_id` (`visitor_id`),
  CONSTRAINT `fk_bot_detections_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `cloacker_visitors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. BOT YAPILANDIRMALARI
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_bots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bot_name` varchar(255) NOT NULL,
  `user_agent` text NOT NULL,
  `target_url` text NOT NULL,
  `delay_ms` int(11) NOT NULL DEFAULT 5000,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. BOT İSTATİSTİKLERİ
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_bot_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) NOT NULL,
  `visit_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `redirect_type` enum('normal','fake') NOT NULL DEFAULT 'fake',
  `response_code` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_id` (`bot_id`),
  KEY `visit_time` (`visit_time`),
  CONSTRAINT `fk_bot_stats_bot` FOREIGN KEY (`bot_id`) REFERENCES `cloacker_bots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. ADMIN GİRİŞ LOGLARI
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_admin_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `login_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `ip` (`ip`),
  KEY `login_time` (`login_time`),
  KEY `success` (`success`),
  CONSTRAINT `fk_admin_logins_admin` FOREIGN KEY (`admin_id`) REFERENCES `cloacker_admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. ŞİFRE SIFIRLAMA TOKENLARI
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `admin_id` (`admin_id`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `fk_password_resets_admin` FOREIGN KEY (`admin_id`) REFERENCES `cloacker_admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. GENEL AYARLAR
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `normal_url` text DEFAULT 'https://google.com',
  `fake_url` text DEFAULT 'https://google.com',
  `allowed_countries` text DEFAULT NULL COMMENT 'Virgülle ayrılmış ülke kodları',
  `allowed_os` text DEFAULT NULL COMMENT 'Virgülle ayrılmış OS listesi',
  `allowed_browsers` text DEFAULT NULL COMMENT 'Virgülle ayrılmış tarayıcı listesi',
  `bot_confidence_threshold` decimal(5,2) DEFAULT 30.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan ayarlar
INSERT INTO `cloacker_settings` (`id`, `normal_url`, `fake_url`, `allowed_countries`, `allowed_os`, `allowed_browsers`, `bot_confidence_threshold`) VALUES
(1, 'https://google.com', 'https://google.com', 'TR,US,GB,DE,FR', 'windows,macos,linux,android,ios', 'chrome,firefox,safari,edge,opera', 30.00);

-- ============================================
-- 11. İZİN VERİLEN ÜLKELER (OPSİYONEL)
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_allowed_countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country` varchar(2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. MİGRATE TAKİP TABLOSU
-- ============================================
CREATE TABLE IF NOT EXISTS `cloacker_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(255) NOT NULL,
  `executed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_name` (`migration_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

