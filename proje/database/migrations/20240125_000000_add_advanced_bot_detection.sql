-- ============================================
-- GELİŞMİŞ BOT DETECTION ÖZELLİKLERİ
-- ============================================
-- rDNS Cache, Fingerprint History, Behavioral Data
-- ============================================

-- 1. rDNS Cache Tablosu
CREATE TABLE IF NOT EXISTS `cloacker_rdns_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `is_bot` tinyint(1) DEFAULT 0,
  `is_valid` tinyint(1) DEFAULT 0,
  `last_checked` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`),
  KEY `is_bot` (`is_bot`),
  KEY `last_checked` (`last_checked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Fingerprint History Tablosu
CREATE TABLE IF NOT EXISTS `cloacker_fingerprint_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fingerprint_hash` varchar(64) NOT NULL,
  `fingerprint_vector` text DEFAULT NULL COMMENT 'JSON formatında vektör',
  `is_verified_human` tinyint(1) DEFAULT 0,
  `visit_count` int(11) DEFAULT 1,
  `human_ratio` decimal(5,2) DEFAULT 0.00 COMMENT 'İnsan ziyaret oranı (0-1)',
  `last_seen` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fingerprint_hash` (`fingerprint_hash`),
  KEY `is_verified_human` (`is_verified_human`),
  KEY `last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Behavioral Data Tablosu
CREATE TABLE IF NOT EXISTS `cloacker_behavioral_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `visitor_id` (`visitor_id`),
  KEY `fingerprint_hash` (`fingerprint_hash`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_behavioral_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `cloacker_visitors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Settings tablosuna yeni ayarlar ekle
ALTER TABLE `cloacker_settings` 
  ADD COLUMN IF NOT EXISTS `enable_proxy_check` tinyint(1) DEFAULT 1 COMMENT 'Proxy/VPN kontrolü aktif mi (IPHub)',
  ADD COLUMN IF NOT EXISTS `enable_rdns_check` tinyint(1) DEFAULT 1 COMMENT 'rDNS kontrolü aktif mi',
  ADD COLUMN IF NOT EXISTS `enable_fingerprint_similarity` tinyint(1) DEFAULT 1 COMMENT 'Fingerprint similarity kontrolü',
  ADD COLUMN IF NOT EXISTS `enable_behavioral_analysis` tinyint(1) DEFAULT 1 COMMENT 'Behavioral analysis aktif mi',
  ADD COLUMN IF NOT EXISTS `enable_asn_check` tinyint(1) DEFAULT 1 COMMENT 'ASN ve datacenter kontrolü',
  ADD COLUMN IF NOT EXISTS `enable_ip_age_check` tinyint(1) DEFAULT 1 COMMENT 'IP yaşı ve fraud skoru kontrolü',
  ADD COLUMN IF NOT EXISTS `enable_delayed_redirect` tinyint(1) DEFAULT 0 COMMENT 'Delayed redirect aktif mi',
  ADD COLUMN IF NOT EXISTS `fingerprint_similarity_threshold_high` decimal(5,4) DEFAULT 0.98 COMMENT 'Yüksek benzerlik eşiği (white list)',
  ADD COLUMN IF NOT EXISTS `fingerprint_similarity_threshold_low` decimal(5,4) DEFAULT 0.85 COMMENT 'Düşük benzerlik eşiği (review)',
  ADD COLUMN IF NOT EXISTS `behavioral_bot_threshold` decimal(5,2) DEFAULT 70.00 COMMENT 'Behavioral bot skoru eşiği',
  ADD COLUMN IF NOT EXISTS `delayed_redirect_min_seconds` int(11) DEFAULT 7 COMMENT 'Delayed redirect minimum süre (saniye)',
  ADD COLUMN IF NOT EXISTS `delayed_redirect_max_seconds` int(11) DEFAULT 15 COMMENT 'Delayed redirect maksimum süre (saniye)',
  ADD COLUMN IF NOT EXISTS `rdns_cache_ttl_hours` int(11) DEFAULT 24 COMMENT 'rDNS cache TTL (saat)',
  ADD COLUMN IF NOT EXISTS `rdns_score` int(11) DEFAULT 20 COMMENT 'rDNS bot tespit skoru';

-- 5. cloacker_visitors tablosuna yeni sütunlar (eğer yoksa)
ALTER TABLE `cloacker_visitors`
  ADD COLUMN IF NOT EXISTS `rdns_hostname` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `rdns_is_bot` tinyint(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `fingerprint_similarity` decimal(5,4) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `behavioral_bot_score` decimal(5,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `asn` varchar(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `asn_name` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `is_datacenter` tinyint(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `ip_age_days` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `fraud_score` int(11) DEFAULT NULL;

