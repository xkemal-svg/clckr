# Veritabanı Kurulum ve Migrate Sistemi

Bu klasör, Cloacker sisteminin veritabanı kurulum ve güncelleme dosyalarını içerir.

## 📁 Dosya Yapısı

```
database/
├── install.sql          # Sıfır kurulum SQL dosyası (tüm tablolar)
├── migrate.php          # Migrate çalıştırıcı script
├── migrations/          # Migration dosyaları klasörü
│   └── .gitkeep
└── README.md            # Bu dosya
```

## 🚀 İlk Kurulum

### 1. Veritabanını Oluştur

```bash
mysql -u root -p < database/install.sql
```

veya MySQL/MariaDB konsolundan:

```sql
source database/install.sql;
```

### 2. Varsayılan Admin Kullanıcı

Kurulum sonrası varsayılan admin kullanıcı:
- **Kullanıcı Adı:** `admin`
- **Şifre:** `admin123`

⚠️ **GÜVENLİK:** İlk girişten sonra mutlaka şifreyi değiştirin!

## 🔄 Migrate Sistemi

### Migration Nedir?

Migration, veritabanı yapısındaki değişiklikleri (yeni tablo, sütun ekleme, değiştirme vb.) yönetmek için kullanılan bir sistemdir.

### Migration Dosyası Oluşturma

1. `database/migrations/` klasörüne yeni bir SQL dosyası oluşturun
2. Dosya adı formatı: `YYYYMMDD_HHMMSS_migration_name.sql`

**Örnek:**
```
20240115_143000_add_telegram_notifications.sql
```

### Migration Dosyası İçeriği

```sql
-- Yeni sütun ekleme örneği
ALTER TABLE `cloacker_sites` 
ADD COLUMN `telegram_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_active`;

-- Yeni tablo örneği
CREATE TABLE IF NOT EXISTS `cloacker_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`),
  CONSTRAINT `fk_notifications_site` FOREIGN KEY (`site_id`) REFERENCES `cloacker_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Migration Çalıştırma

#### Komut Satırından (CLI):

```bash
php database/migrate.php
```

#### Web Tarayıcısından:

```
http://yourdomain.com/database/migrate.php
```

### Migrate Sistemi Nasıl Çalışır?

1. `migrate.php` scripti `migrations/` klasöründeki tüm `.sql` dosyalarını tarar
2. `cloacker_migrations` tablosuna bakarak hangi migrationların çalıştırıldığını kontrol eder
3. Çalıştırılmamış migrationları sırayla çalıştırır
4. Her migration çalıştırıldıktan sonra:
   - `cloacker_migrations` tablosuna kaydedilir
   - `install.sql` dosyasına otomatik olarak eklenir (gelecekteki kurulumlar için)

### Önemli Notlar

- ✅ Migration dosyaları sadece bir kez çalıştırılır
- ✅ Aynı migration tekrar çalıştırılmaz (güvenlik için)
- ✅ Migrationlar dosya adına göre sıralı çalıştırılır
- ✅ Her migration `install.sql` dosyasına eklenir
- ⚠️ Migration dosyalarını silmeyin (geçmiş kayıt için gerekli)

## 📝 Örnek Migration Senaryoları

### Senaryo 1: Yeni Sütun Ekleme

```sql
-- database/migrations/20240115_143000_add_telegram_enabled.sql
ALTER TABLE `cloacker_sites` 
ADD COLUMN `telegram_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_active`;
```

### Senaryo 2: Yeni Tablo Ekleme

```sql
-- database/migrations/20240115_144000_create_notifications_table.sql
CREATE TABLE IF NOT EXISTS `cloacker_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`),
  CONSTRAINT `fk_notifications_site` FOREIGN KEY (`site_id`) REFERENCES `cloacker_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Senaryo 3: Mevcut Sütunu Değiştirme

```sql
-- database/migrations/20240115_145000_modify_settings_column.sql
ALTER TABLE `cloacker_sites` 
MODIFY COLUMN `settings` text DEFAULT NULL COMMENT 'JSON formatında site özel ayarlar';
```

### Senaryo 4: Index Ekleme

```sql
-- database/migrations/20240115_146000_add_index_to_visitors.sql
ALTER TABLE `cloacker_visitors` 
ADD INDEX `idx_created_at_country` (`created_at`, `country`);
```

## 🔧 Sorun Giderme

### Migration Çalışmıyor

1. Veritabanı bağlantı bilgilerini `config.php` dosyasından kontrol edin
2. `cloacker_migrations` tablosunun var olduğundan emin olun
3. Migration dosyasının SQL syntax'ının doğru olduğunu kontrol edin

### Migration'ı Geri Alma

Migration sistemi otomatik geri alma (rollback) desteklemez. Manuel olarak:

1. Migration dosyasındaki SQL'i tersine çevirin
2. Yeni bir migration dosyası oluşturun
3. Veya `cloacker_migrations` tablosundan ilgili kaydı silin ve migration'ı tekrar çalıştırın

## 📚 İlgili Dosyalar

- `config.php` - Veritabanı bağlantı ayarları
- `install.sql` - Sıfır kurulum SQL dosyası
- `migrate.php` - Migrate çalıştırıcı script

## 🆘 Yardım

Sorun yaşarsanız:
1. Hata mesajlarını kontrol edin
2. Veritabanı loglarını inceleyin
3. Migration dosyasının syntax'ını doğrulayın

















