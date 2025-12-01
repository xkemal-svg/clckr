-- ============================================
-- Profil alanları ekleme (full_name, phone)
-- ============================================

ALTER TABLE `cloacker_admins` 
ADD COLUMN `full_name` varchar(255) DEFAULT NULL AFTER `email`,
ADD COLUMN `phone` varchar(50) DEFAULT NULL AFTER `full_name`;

















