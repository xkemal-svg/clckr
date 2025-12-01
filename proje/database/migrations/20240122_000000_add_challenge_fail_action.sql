-- ============================================
-- CHALLENGE FAIL ACTION AYARI MİGRATİON
-- Tarih: 2024-01-22
-- ============================================

-- Challenge fail action sütunu ekle
ALTER TABLE `cloacker_settings`
ADD COLUMN IF NOT EXISTS `challenge_fail_action` VARCHAR(20) DEFAULT 'add_score';

-- Mevcut ayarları güncelle
UPDATE `cloacker_settings` SET 
  `challenge_fail_action` = 'add_score'
WHERE `id` = 1 AND `challenge_fail_action` IS NULL;



