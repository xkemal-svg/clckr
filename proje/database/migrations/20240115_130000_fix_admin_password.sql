-- ============================================
-- Admin şifresini düzelt (admin123)
-- ============================================

-- Doğru hash ile admin şifresini güncelle
-- Şifre: admin123
UPDATE `cloacker_admins` 
SET `password_hash` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE `username` = 'admin';

















