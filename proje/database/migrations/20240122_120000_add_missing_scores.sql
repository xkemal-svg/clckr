-- ============================================
-- EKSİK SKOR AYARLARI MİGRATİON
-- Tarih: 2024-01-22
-- ============================================

-- Speech Synthesis ve JA3 skorları ekle
ALTER TABLE `cloacker_settings`
ADD COLUMN IF NOT EXISTS `speech_synthesis_score` INT(11) DEFAULT 3,
ADD COLUMN IF NOT EXISTS `ja3_score` INT(11) DEFAULT 20;

-- Mevcut ayarları güncelle
UPDATE `cloacker_settings` SET 
  `speech_synthesis_score` = 3,
  `ja3_score` = 20
WHERE `id` = 1 AND (`speech_synthesis_score` IS NULL OR `ja3_score` IS NULL);



