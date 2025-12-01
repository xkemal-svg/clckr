-- ============================================
-- ÖRNEK MİGRATION DOSYASI
-- ============================================
-- Bu dosya sadece örnek amaçlıdır.
-- Gerçek migration dosyaları oluştururken bu formatı kullanabilirsiniz.
-- ============================================

-- Örnek 1: Yeni sütun ekleme
-- ALTER TABLE `cloacker_sites` 
-- ADD COLUMN `example_column` varchar(255) DEFAULT NULL AFTER `name`;

-- Örnek 2: Yeni tablo oluşturma
-- CREATE TABLE IF NOT EXISTS `cloacker_example_table` (
--   `id` int(11) NOT NULL AUTO_INCREMENT,
--   `name` varchar(255) NOT NULL,
--   `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   PRIMARY KEY (`id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Örnek 3: Index ekleme
-- ALTER TABLE `cloacker_visitors` 
-- ADD INDEX `idx_example` (`ip`, `created_at`);

-- NOT: Bu dosyadaki SQL'ler yorum satırı olarak bırakılmıştır.
-- Gerçek migration oluştururken yorum işaretlerini kaldırın.

















