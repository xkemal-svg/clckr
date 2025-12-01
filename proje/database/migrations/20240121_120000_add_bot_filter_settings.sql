-- ============================================
-- BOT FİLTRE AYARLARI MİGRATİON
-- Tarih: 2024-01-21
-- ============================================

-- Bot filtre ayarları
ALTER TABLE `cloacker_settings`
ADD COLUMN `enable_ja3_check` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_canvas_check` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_webgl_check` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_audio_check` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_webrtc_check` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_fonts_check` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_plugins_check` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_headless_check` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_challenge_check` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_rate_limit` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_residential_proxy_check` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_cloudflare_bot_check` tinyint(1) DEFAULT 1,
ADD COLUMN `enable_duplicate_check` tinyint(1) DEFAULT 1,
ADD COLUMN `canvas_score` int(11) DEFAULT 8,
ADD COLUMN `webgl_score` int(11) DEFAULT 7,
ADD COLUMN `audio_score` int(11) DEFAULT 6,
ADD COLUMN `webrtc_score` int(11) DEFAULT 10,
ADD COLUMN `headless_score` int(11) DEFAULT 12,
ADD COLUMN `fonts_score` int(11) DEFAULT 4,
ADD COLUMN `plugins_score` int(11) DEFAULT 3,
ADD COLUMN `challenge_score` int(11) DEFAULT 15,
ADD COLUMN `rate_limit_max_requests` int(11) DEFAULT 10,
ADD COLUMN `rate_limit_window_seconds` int(11) DEFAULT 60;

-- Mevcut ayarları güncelle
UPDATE `cloacker_settings` SET 
  `enable_ja3_check` = 1,
  `enable_canvas_check` = 1,
  `enable_webgl_check` = 1,
  `enable_audio_check` = 1,
  `enable_webrtc_check` = 1,
  `enable_fonts_check` = 1,
  `enable_plugins_check` = 1,
  `enable_headless_check` = 1,
  `enable_challenge_check` = 1,
  `enable_rate_limit` = 1,
  `enable_residential_proxy_check` = 1,
  `enable_cloudflare_bot_check` = 1,
  `enable_duplicate_check` = 1,
  `canvas_score` = 8,
  `webgl_score` = 7,
  `audio_score` = 6,
  `webrtc_score` = 10,
  `headless_score` = 12,
  `fonts_score` = 4,
  `plugins_score` = 3,
  `challenge_score` = 15,
  `rate_limit_max_requests` = 10,
  `rate_limit_window_seconds` = 60
WHERE `id` = 1;




