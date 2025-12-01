-- ============================================
-- GELİŞMİŞ FİNGERPRİNT SİSTEMİ MİGRATİON
-- Tarih: 2024-01-20
-- ============================================

-- JA3/JA3s blacklist tablosu
CREATE TABLE IF NOT EXISTS `cloacker_ja3_blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ja3_hash` varchar(64) NOT NULL,
  `ja3s_hash` varchar(64) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `chrome_version` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ja3_hash` (`ja3_hash`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chrome 129-132 JA3 hash'leri (curl-impersonate referansı)
-- Not: Gerçek JA3 hash'leri sunucu tarafında TLS handshake sırasında toplanır
-- Bu örnek hash'ler referans içindir, gerçek değerler sisteminizde toplanmalıdır
INSERT IGNORE INTO `cloacker_ja3_blacklist` (`ja3_hash`, `ja3s_hash`, `description`, `chrome_version`) VALUES
('771,4865-4866-4867-49195-49199-49196-49200-52393-52392-49171-49172-156-157-47-53,0-23-65281-10-11-35-16-5-13-18-51-45-43-27-17513,29-23-24,0', NULL, 'Chrome 129 Example', '129'),
('771,4865-4866-4867-49195-49199-49196-49200-52393-52392-49171-49172-156-157-47-53,0-23-65281-10-11-35-16-5-13-18-51-45-43-27-17513,29-23-24,1', NULL, 'Chrome 130 Example', '130'),
('771,4865-4866-4867-49195-49199-49196-49200-52393-52392-49171-49172-156-157-47-53,0-23-65281-10-11-35-16-5-13-18-51-45-43-27-17513,29-23-24,2', NULL, 'Chrome 131 Example', '131'),
('771,4865-4866-4867-49195-49199-49196-49200-52393-52392-49171-49172-156-157-47-53,0-23-65281-10-11-35-16-5-13-18-51-45-43-27-17513,29-23-24,3', NULL, 'Chrome 132 Example', '132');

-- Client-side fingerprint verileri için yeni sütunlar
-- Eski MySQL versiyonları için IF NOT EXISTS kontrolü yapılmadan ekleniyor
-- Eğer sütun zaten varsa hata verebilir, bu durumda hata mesajını görmezden gelebilirsiniz

ALTER TABLE `cloacker_visitors` 
ADD COLUMN `ja3_hash` varchar(64) DEFAULT NULL,
ADD COLUMN `ja3s_hash` varchar(64) DEFAULT NULL,
ADD COLUMN `http2_fingerprint` text DEFAULT NULL,
ADD COLUMN `canvas_fingerprint` varchar(64) DEFAULT NULL,
ADD COLUMN `webgl_fingerprint` varchar(64) DEFAULT NULL,
ADD COLUMN `audio_fingerprint` varchar(64) DEFAULT NULL,
ADD COLUMN `webrtc_leak` tinyint(1) DEFAULT 0,
ADD COLUMN `local_ip_detected` varchar(45) DEFAULT NULL,
ADD COLUMN `fonts_hash` varchar(64) DEFAULT NULL,
ADD COLUMN `plugins_hash` varchar(64) DEFAULT NULL,
ADD COLUMN `ml_confidence` decimal(5,2) DEFAULT NULL,
ADD COLUMN `dynamic_threshold` decimal(5,2) DEFAULT NULL;

-- İndeksler (eğer zaten varsa hata verebilir)
ALTER TABLE `cloacker_visitors`
ADD INDEX `idx_ja3_hash` (`ja3_hash`),
ADD INDEX `idx_canvas_fp` (`canvas_fingerprint`),
ADD INDEX `idx_webrtc_leak` (`webrtc_leak`);

-- ML model eğitim verileri için tablo
CREATE TABLE IF NOT EXISTS `cloacker_ml_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_id` int(11) DEFAULT NULL,
  `features` text NOT NULL COMMENT 'JSON formatında özellikler',
  `label` tinyint(1) NOT NULL COMMENT '1=bot, 0=gerçek',
  `confidence` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `visitor_id` (`visitor_id`),
  KEY `label` (`label`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_ml_training_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `cloacker_visitors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dinamik eşik ayarları
ALTER TABLE `cloacker_settings`
ADD COLUMN `ml_enabled` tinyint(1) DEFAULT 1,
ADD COLUMN `dynamic_threshold_enabled` tinyint(1) DEFAULT 1,
ADD COLUMN `min_threshold` decimal(5,2) DEFAULT 20.00,
ADD COLUMN `max_threshold` decimal(5,2) DEFAULT 50.00;

-- Mevcut ayarları güncelle
UPDATE `cloacker_settings` SET 
  `ml_enabled` = 1,
  `dynamic_threshold_enabled` = 1,
  `min_threshold` = 20.00,
  `max_threshold` = 50.00
WHERE `id` = 1;
