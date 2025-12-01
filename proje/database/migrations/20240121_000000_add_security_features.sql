-- ============================================
-- GÜVENLİK ÖZELLİKLERİ MİGRATİON
-- Tarih: 2024-01-21
-- ============================================

-- Fingerprint hash sütunu ekle (24 saat duplicate kontrol için)
ALTER TABLE `cloacker_visitors`
ADD COLUMN `fingerprint_hash` varchar(64) DEFAULT NULL;

-- İndeks ekle
ALTER TABLE `cloacker_visitors`
ADD INDEX `idx_fingerprint_hash` (`fingerprint_hash`);

-- Rate limiting tablosu
CREATE TABLE IF NOT EXISTS `cloacker_rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `fingerprint_hash` varchar(64) NOT NULL,
  `request_count` int(11) NOT NULL DEFAULT 1,
  `window_start` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_fp` (`ip`, `fingerprint_hash`),
  KEY `window_start` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




