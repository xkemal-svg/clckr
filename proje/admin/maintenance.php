<?php
require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/layout.php';

enforceAdminSession();

$success = '';
$error = '';

/**
 * DB bağlantısı - BU DOSYA İÇİNDE PDO oluşturuluyor.
 * config.php şu formatta bir array döndürmeli: ['db'=>['host'=>...,'name'=>...,'user'=>...,'pass'=>...,'charset'=>...]]
 */
try {
    $config = require __DIR__ . '/../config.php';
    $dbHost = $config['db']['host'] ?? '127.0.0.1';
    $dbName = $config['db']['name'] ?? 'dbname';
    $dbUser = $config['db']['user'] ?? 'dbuser';
    $dbPass = $config['db']['pass'] ?? '';
    $dbCharset = $config['db']['charset'] ?? 'utf8mb4';

    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        // **BUFFERED QUERY AKTİF** -> aynı bağlantıda birden fazla sorgu çalıştırınca 2014 hatasını önler
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ];

    $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
} catch (Throwable $e) {
    // Eğer DB bağlanamazsa ekranda hata göster, sonraki işlemler atlanacak
    $pdo = null;
    $error = "Veritabanına bağlantı kurulamadı: " . $e->getMessage();
}

/* =======================
   İstatistikleri getir
   - tüm SELECT'ler fetchAll / fetchColumn ile bellekleniyor
======================= */
function fetchMaintenanceStats(PDO $pdo): array {
    $stats = [
        'visitors_total' => 0,
        'visitors_30d' => 0,
        'bot_detections' => 0,
    ];

    try {
        $stats['visitors_total'] = (int)$pdo->query("SELECT COUNT(*) FROM cloacker_visitors")->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cloacker_visitors WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $stats['visitors_30d'] = (int)$stmt->fetchColumn();
        $stmt->closeCursor();
    } catch (Throwable $e) {}

    try {
        $stats['bot_detections'] = (int)$pdo->query("SELECT COUNT(*) FROM cloacker_bot_detections")->fetchColumn();
    } catch (Throwable $e) {}

    return $stats;
}

/* Bot detections tablosu var mı kontrol (buffered olduğu için fetch yapıyoruz) */
$botDetectionsTableExists = false;
if ($pdo) {
    try {
        $row = $pdo->query("SELECT 1 FROM cloacker_bot_detections LIMIT 1")->fetch();
        if ($row !== false) $botDetectionsTableExists = true;
    } catch (Throwable $e) {
        // tablo yoksa exception atılacaktır, onunla ilgilenmiyoruz
    }
}

$action = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    requireCsrfToken();
    $action = $_POST['maintenance_action'] ?? '';

    try {
        /* ---------------- Eski Log Temizleme ---------------- */
        if ($action === 'clear_old_logs') {
            $days = max(1, min(365, (int)($_POST['older_than_days'] ?? 30)));
            $threshold = (new DateTimeImmutable("-{$days} days"))->format('Y-m-d H:i:s');

            $deletedDetections = 0;
            if ($botDetectionsTableExists) {
                $stmt = $pdo->prepare("
                    DELETE bd FROM cloacker_bot_detections bd
                    INNER JOIN cloacker_visitors v ON bd.visitor_id = v.id
                    WHERE v.created_at < :threshold
                ");
                $stmt->execute([':threshold' => $threshold]);
                $deletedDetections = $stmt->rowCount();
                $stmt->closeCursor();
            }

            $stmt = $pdo->prepare("DELETE FROM cloacker_visitors WHERE created_at < :threshold");
            $stmt->execute([':threshold' => $threshold]);
            $deletedVisitors = $stmt->rowCount();
            $stmt->closeCursor();

            $success = "📦 {$days} günden eski {$deletedVisitors} ziyaret kaydı ve {$deletedDetections} bot kaydı temizlendi.";

        /* ---------------- Site Bazlı Temizlik ---------------- */
        } elseif ($action === 'clear_site_logs') {
            $siteId = (int)($_POST['site_id'] ?? 0);
            if ($siteId <= 0) throw new InvalidArgumentException('Lütfen bir site seçin.');

            $deletedDetections = 0;
            if ($botDetectionsTableExists) {
                $stmt = $pdo->prepare("
                    DELETE bd FROM cloacker_bot_detections bd
                    INNER JOIN cloacker_visitors v ON bd.visitor_id = v.id
                    WHERE v.site_id = :site_id
                ");
                $stmt->execute([':site_id' => $siteId]);
                $deletedDetections = $stmt->rowCount();
                $stmt->closeCursor();
            }

            $stmt = $pdo->prepare("DELETE FROM cloacker_visitors WHERE site_id = :site_id");
            $stmt->execute([':site_id' => $siteId]);
            $deletedVisitors = $stmt->rowCount();
            $stmt->closeCursor();

            $success = "🌍 Seçili site için {$deletedVisitors} ziyaret kaydı ve {$deletedDetections} bot kaydı silindi.";

        /* ---------------- Pasif / Silinmiş Site Kaydı Temizleme ---------------- */
        } elseif ($action === 'clear_inactive_logs') {
            if ($botDetectionsTableExists) {
                $sql = "
                    DELETE bd FROM cloacker_bot_detections bd
                    INNER JOIN cloacker_visitors v ON bd.visitor_id = v.id
                    LEFT JOIN cloacker_sites s ON s.id = v.site_id
                    WHERE v.site_id IS NOT NULL AND (s.id IS NULL OR s.is_active = 0)
                ";
                $pdo->exec($sql);
            }
            $sql = "
                DELETE v FROM cloacker_visitors v
                LEFT JOIN cloacker_sites s ON s.id = v.site_id
                WHERE v.site_id IS NOT NULL AND (s.id IS NULL OR s.is_active = 0)
            ";
            $deletedVisitors = $pdo->exec($sql);
            $success = "🔄 Pasif veya silinmiş sitelere ait {$deletedVisitors} ziyaret kaydı temizlendi.";

        /* ---------------- Kullanılmayan API Anahtar Logları ---------------- */
        } elseif ($action === 'clear_unused_api_logs') {
            if ($botDetectionsTableExists) {
                $pdo->exec("
                    DELETE bd FROM cloacker_bot_detections bd
                    INNER JOIN cloacker_visitors v ON bd.visitor_id = v.id
                    LEFT JOIN cloacker_api_keys k ON k.id = v.api_key_id
                    WHERE v.api_key_id IS NOT NULL AND (k.id IS NULL OR k.is_active = 0)
                ");
            }
            $deletedVisitors = $pdo->exec("
                DELETE v FROM cloacker_visitors v
                LEFT JOIN cloacker_api_keys k ON k.id = v.api_key_id
                WHERE v.api_key_id IS NOT NULL AND (k.id IS NULL OR k.is_active = 0)
            ");
            $success = "🔑 Pasif/silinmiş API anahtarlarına ait {$deletedVisitors} ziyaret kaydı temizlendi.";

        /* ---------------- Hata Log Temizleme ---------------- */
        } elseif ($action === 'clear_error_logs') {
            $errorLogPath = realpath(__DIR__ . '/../error_log.txt');
            if ($errorLogPath && is_writable($errorLogPath)) {
                file_put_contents($errorLogPath, '');
                $success = "🧹 error_log.txt dosyası temizlendi.";
            } else {
                throw new RuntimeException('error_log.txt dosyasına erişilemiyor veya yazma izni yok.');
            }

        /* ---------------- VERİTABANI OPTIMIZE ---------------- */
        } elseif ($action === 'optimize_tables') {
            // Optimize işlemi için İZOLASYON: tamamen yeni PDO kullan
            $dsnOpt = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
            $optimizeOptions = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ];

            $pdoOptimize = new PDO($dsnOpt, $dbUser, $dbPass, $optimizeOptions);

            try {
                // Tabloları ayrı ayrı optimize et
                $pdoOptimize->exec("OPTIMIZE TABLE cloacker_visitors");
            } catch (PDOException $e) {
                // Eğer OPTIMIZE başarısız olursa (ör. bazı MariaDB durumları) alternatif bir komut deneyelim
                try {
                    $pdoOptimize->exec("ALTER TABLE cloacker_visitors ENGINE=InnoDB");
                } catch (Throwable $inner) {
                    // ignore, atılacak hata üstte yakalanacak
                }
            }

            if ($botDetectionsTableExists) {
                try {
                    $pdoOptimize->exec("OPTIMIZE TABLE cloacker_bot_detections");
                } catch (PDOException $e) {
                    try {
                        $pdoOptimize->exec("ALTER TABLE cloacker_bot_detections ENGINE=InnoDB");
                    } catch (Throwable $inner) {
                        // ignore
                    }
                }
            }

            // İzole optimize bağlantısını kapat
            $pdoOptimize = null;
            unset($pdoOptimize);

            /**
             * Çok kritik: main PDO'yu resetle.
             * buffered query aktif olsa bile, optimize sırasında DB engine değişikliği vs. olursa
             * eski PDO yeniden oluşturularak olası pending cursorlar temizlenir.
             */
            $pdo = null;
            $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);

            $success = "💾 Veritabanı tabloları optimize edildi.";
        } else {
            throw new InvalidArgumentException('Geçersiz bakım işlemi.');
        }
    } catch (Throwable $e) {
        $error = "İşlem başarısız: " . $e->getMessage();
    }
}

/* =======================
   → Stats & Sites (her zaman buffered & fetchAll)
======================= */
$stats = ['visitors_total'=>0,'visitors_30d'=>0,'bot_detections'=>0];
$sites = [];

if ($pdo) {
    try {
        $stats = fetchMaintenanceStats($pdo);
    } catch (Throwable $e) {}

    try {
        $sites = $pdo->query("SELECT id, name, is_active FROM cloacker_sites ORDER BY name ASC")->fetchAll();
    } catch (Throwable $e) {
        $sites = [];
    }
}

/* =======================
   → HTML ÇIKTI
======================= */
render_admin_layout_start('Bakım & Temizlik', 'maintenance');
?>

<?php if ($error): ?>
    <div class="mb-4 p-3 rounded glass-card border border-red-500/30 text-red-400"><?=htmlspecialchars($error)?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-4 p-3 rounded glass-card border border-cyan-500/30 text-cyan-400"><?=htmlspecialchars($success)?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
    <div class="rounded-xl glass-card border border-cyan-500/20 p-4">
        <p class="text-sm text-gray-400 uppercase">Toplam Ziyaretçi Kaydı</p>
        <p class="text-3xl font-semibold mt-2"><?=number_format($stats['visitors_total'])?></p>
    </div>
    <div class="rounded-xl glass-card border border-cyan-500/20 p-4">
        <p class="text-sm text-gray-400 uppercase">Son 30 Gün</p>
        <p class="text-3xl font-semibold mt-2"><?=number_format($stats['visitors_30d'])?></p>
    </div>
    <div class="rounded-xl glass-card border border-cyan-500/20 p-4">
        <p class="text-sm text-gray-400 uppercase">Bot Tespiti Kaydı</p>
        <p class="text-3xl font-semibold mt-2"><?=number_format($stats['bot_detections'])?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <section class="glass-card rounded-2xl border border-cyan-500/20 shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Tarih Bazlı Temizlik</h2>
        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
            <input type="hidden" name="maintenance_action" value="clear_old_logs">
            <label class="block text-sm font-medium">Kaç günden eski kayıtlar silinsin?</label>
            <input type="number" name="older_than_days" min="1" max="365" value="<?=htmlspecialchars($_POST['older_than_days'] ?? 30)?>" class="w-full border rounded px-3 py-2 glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
            <p class="text-xs text-gray-500">Bot tespiti detayları da otomatik silinir.</p>
            <button type="submit" class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 neon-glow-red text-white py-2 rounded-lg">Eski Kayıtları Temizle</button>
        </form>
    </section>

    <section class="glass-card rounded-2xl border border-cyan-500/20 shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Site Bazlı Temizlik</h2>
        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
            <input type="hidden" name="maintenance_action" value="clear_site_logs">
            <label class="block text-sm font-medium">Site seçin</label>
            <select name="site_id" class="w-full border rounded px-3 py-2 glass-card border border-cyan-500/20 bg-gray-900/50 text-white" required>
                <option value="">— Site seçin —</option>
                <?php foreach ($sites as $site): ?>
                    <option value="<?=$site['id']?>" <?=(!empty($_POST['site_id']) && (int)$_POST['site_id'] === (int)$site['id']) ? 'selected' : ''?>>
                        <?=htmlspecialchars($site['name'])?> <?=$site['is_active'] ? '' : '(Pasif)'?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-500">Seçtiğiniz siteye ait tüm ziyaret ve bot kayıtları temizlenir.</p>
            <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white py-2 rounded-lg">Site Loglarını Sil</button>
        </form>
    </section>

    <section class="glass-card rounded-2xl border border-cyan-500/20 shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Pasif / Silinmiş Kayıtları Temizle</h2>
        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
            <input type="hidden" name="maintenance_action" value="clear_inactive_logs">
            <p class="text-sm text-gray-300">Pasif veya silinmiş sitelere ait ziyaret kayıtlarını temizleyerek veritabanını hafifletin.</p>
            <button type="submit" class="w-full bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 neon-glow-cyan text-white py-2 rounded-lg">Pasif Site Loglarını Temizle</button>
        </form>
    </section>

    <section class="glass-card rounded-2xl border border-cyan-500/20 shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Kullanılmayan API Anahtarları</h2>
        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
            <input type="hidden" name="maintenance_action" value="clear_unused_api_logs">
            <p class="text-sm text-gray-300">Pasif veya silinmiş API anahtarlarından gelen geçmiş logları sil.</p>
            <button type="submit" class="w-full bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white py-2 rounded-lg">API Loglarını Temizle</button>
        </form>
    </section>

    <section class="glass-card rounded-2xl border border-cyan-500/20 shadow p-6 col-span-2">
        <h2 class="text-xl font-semibold mb-4">Hata Kayıtları & Optimizasyon</h2>
        <div class="space-y-4">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
                <input type="hidden" name="maintenance_action" value="clear_error_logs">
                <button type="submit" class="w-full glass-card border border-gray-600/30 hover:border-gray-500/50 text-white py-2 rounded-lg">error_log.txt Dosyasını Temizle</button>
            </form>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
                <input type="hidden" name="maintenance_action" value="optimize_tables">
                <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white py-2 rounded-lg">Veritabanını Optimize Et</button>
            </form>
        </div>
    </section>
</div>

<?php
render_admin_layout_end();
