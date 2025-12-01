<?php
/**
 * MİGRATE SİSTEMİ
 * 
 * Bu script migrate dosyalarını çalıştırır ve ana SQL dosyasına ekler.
 * 
 * Kullanım:
 *   php database/migrate.php
 * 
 * Migrate dosyaları database/migrations/ klasöründe olmalıdır.
 * Dosya adı formatı: YYYYMMDD_HHMMSS_migration_name.sql
 */

// Config dosyasını yükle
$configPath = __DIR__ . '/../config.php';
if (file_exists($configPath)) {
    $GLOBALS['app_config'] = require $configPath;
} else {
    die("❌ config.php dosyası bulunamadı!\n");
}

if (!function_exists('config')) {
    function config(string $key, $default = null) {
        $segments = explode('.', $key);
        $value = $GLOBALS['app_config'] ?? [];

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

class Migrator {
    private $pdo;
    private $migrationsDir;
    private $installSqlFile;

    public function __construct() {
        $host = config('db.host');
        $db   = config('db.name');
        $user = config('db.user');
        $pass = config('db.pass');
        $charset = config('db.charset', 'utf8mb4');
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new PDO($dsn, $user, $pass, $opt);
        $this->migrationsDir = __DIR__ . '/migrations';
        $this->installSqlFile = __DIR__ . '/install.sql';
        
        // Migrations tablosunu oluştur (eğer yoksa)
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `cloacker_migrations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `migration_name` varchar(255) NOT NULL,
            `executed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `migration_name` (`migration_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            // Tablo zaten varsa hata vermez
        }
    }

    private function getExecutedMigrations(): array {
        $stmt = $this->pdo->query("SELECT migration_name FROM cloacker_migrations ORDER BY migration_name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function markMigrationAsExecuted(string $migrationName) {
        $stmt = $this->pdo->prepare("INSERT INTO cloacker_migrations (migration_name) VALUES (:name)");
        $stmt->execute([':name' => $migrationName]);
    }

    private function getMigrationFiles(): array {
        if (!is_dir($this->migrationsDir)) {
            mkdir($this->migrationsDir, 0755, true);
            return [];
        }

        $files = glob($this->migrationsDir . '/*.sql');
        usort($files, function($a, $b) {
            return basename($a) <=> basename($b);
        });

        return $files;
    }

    private function extractMigrationName(string $filePath): string {
        return basename($filePath, '.sql');
    }

    private function appendToInstallSql(string $migrationContent, string $migrationName) {
        if (!file_exists($this->installSqlFile)) {
            return; // Ana SQL dosyası yoksa ekleme yapma
        }

        $append = "\n\n-- ============================================\n";
        $append .= "-- MIGRATION: {$migrationName}\n";
        $append .= "-- ============================================\n";
        $append .= $migrationContent . "\n";

        file_put_contents($this->installSqlFile, $append, FILE_APPEND | LOCK_EX);
    }

    public function run() {
        echo "🚀 Migrate sistemi başlatılıyor...\n\n";

        $executedMigrations = $this->getExecutedMigrations();
        $migrationFiles = $this->getMigrationFiles();

        if (empty($migrationFiles)) {
            echo "✅ Çalıştırılacak migration bulunamadı.\n";
            return;
        }

        $newMigrations = [];
        foreach ($migrationFiles as $file) {
            $migrationName = $this->extractMigrationName($file);
            
            if (in_array($migrationName, $executedMigrations)) {
                echo "⏭️  {$migrationName} - Zaten çalıştırılmış, atlanıyor.\n";
                continue;
            }

            $newMigrations[] = ['file' => $file, 'name' => $migrationName];
        }

        if (empty($newMigrations)) {
            echo "✅ Tüm migrationlar zaten çalıştırılmış.\n";
            return;
        }

        echo "📦 " . count($newMigrations) . " yeni migration bulundu.\n\n";

        foreach ($newMigrations as $migration) {
            echo "🔄 Çalıştırılıyor: {$migration['name']}...\n";
            
            try {
                $sql = file_get_contents($migration['file']);
                
                if (empty(trim($sql))) {
                    echo "⚠️  Dosya boş, atlanıyor.\n\n";
                    continue;
                }

                // SQL'i çalıştır
                $this->pdo->exec($sql);
                
                // Migration'ı işaretle
                $this->markMigrationAsExecuted($migration['name']);
                
                // Ana SQL dosyasına ekle
                $this->appendToInstallSql($sql, $migration['name']);
                
                echo "✅ {$migration['name']} başarıyla çalıştırıldı ve ana SQL'e eklendi.\n\n";
                
            } catch (PDOException $e) {
                echo "❌ HATA: {$migration['name']} çalıştırılamadı!\n";
                echo "   Mesaj: " . $e->getMessage() . "\n\n";
                throw $e; // Hata durumunda dur
            }
        }

        echo "🎉 Tüm migrationlar başarıyla tamamlandı!\n";
    }
}

// CLI'den çalıştırılıyorsa otomatik çalıştır
if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) === 'migrate.php') {
    try {
        $migrator = new Migrator();
        $migrator->run();
    } catch (Exception $e) {
        echo "❌ Kritik Hata: " . $e->getMessage() . "\n";
        exit(1);
    }
}

