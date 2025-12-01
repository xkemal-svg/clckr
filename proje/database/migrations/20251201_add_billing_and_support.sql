-- Migration: Kullanıcı, Paket, Abonelik, Ödeme, Destek tabloları
-- Çalıştırmadan önce yedeğinizi alın.

START TRANSACTION;

-- Kullanıcılar (müşteri + admin)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paketler
CREATE TABLE IF NOT EXISTS `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `price_cents` int(11) NOT NULL DEFAULT 0, -- kuruş / cent
  `currency` varchar(10) NOT NULL DEFAULT 'TRY',
  `duration_days` int(11) NOT NULL DEFAULT 30,
  `visitor_quota` int(11) DEFAULT NULL, -- aylık ziyaretçi kotası (NULL = limitsiz)
  `features` text DEFAULT NULL COMMENT 'JSON: feature toggles (ml,behavioral,ab_tests,ja3_check,...)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Abonelikler / Subscriptions
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `status` enum('active','pending','cancelled','expired') NOT NULL DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `pay_method` enum('bank_transfer','paytr') DEFAULT NULL,
  `pay_reference` varchar(255) DEFAULT NULL, -- banka referansı veya paytr_order_id
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_idx` (`user_id`),
  KEY `package_idx` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ödemeler
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `amount_cents` int(11) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'TRY',
  `method` enum('bank_transfer','paytr') NOT NULL,
  `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `meta` text DEFAULT NULL, -- json
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support/Ticket sistemi
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('open','waiting','closed') NOT NULL DEFAULT 'open',
  `assigned_admin` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `sender_user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_idx` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site-ownership link (opsiyonel: mevcut created_by kullanılabilir)
ALTER TABLE `cloacker_sites`
  ADD COLUMN IF NOT EXISTS `owner_user_id` int(11) DEFAULT NULL, ADD KEY (`owner_user_id`);

COMMIT;