<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/includes/admin_guard.php';
    require_once __DIR__ . '/includes/layout.php';

    enforceAdminSession();

    // Migrator sınıfını yükle
    require_once __DIR__ . '/../database/migrate.php';
} catch (Exception $e) {
    die("Hata: " . $e->getMessage() . " - Dosya: " . $e->getFile() . " - Satır: " . $e->getLine());
}

// Migration içeriğini görüntüleme (AJAX) - En başta kontrol et
if (isset($_GET['view'])) {
    header('Content-Type: application/json');
    
    $migrationsDir = __DIR__ . '/../database/migrations';
    $migrationName = $_GET['view'];
    $migrationFile = $migrationsDir . '/' . $migrationName . '.sql';
    
    if (file_exists($migrationFile)) {
        $content = file_get_contents($migrationFile);
        echo json_encode([
            'status' => 'ok',
            'content' => $content
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Dosya bulunamadı'
        ]);
    }
    exit;
}

$pdo = DB::connect();
$error = '';
$success = '';
$migrationResults = [];

// Migrate çalıştırma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migrations'])) {
    requireCsrfToken();
    
    try {
        $migrator = new Migrator();
        
        // Output'u yakalamak için output buffering
        ob_start();
        $migrator->run();
        $output = ob_get_clean();
        
        $success = "Migrationlar başarıyla çalıştırıldı!";
        $migrationResults = explode("\n", trim($output));
    } catch (Exception $e) {
        $error = "Migration hatası: " . $e->getMessage();
    }
}

// Çalıştırılmış migrationları al
$executedMigrations = [];
try {
    $stmt = $pdo->query("SELECT migration_name, executed_at FROM cloacker_migrations ORDER BY executed_at DESC");
    $executedMigrations = $stmt->fetchAll();
} catch (PDOException $e) {
    // Tablo yoksa boş array
}

// Migration dosyalarını listele
$migrationsDir = __DIR__ . '/../database/migrations';
$migrationFiles = [];
if (is_dir($migrationsDir)) {
    $files = glob($migrationsDir . '/*.sql');
    usort($files, function($a, $b) {
        return basename($a) <=> basename($b);
    });
    
    foreach ($files as $file) {
        $fileName = basename($file);
        $migrationName = preg_replace('/\.sql$/', '', $fileName);
        
        $isExecuted = false;
        foreach ($executedMigrations as $executed) {
            if ($executed['migration_name'] === $migrationName) {
                $isExecuted = true;
                break;
            }
        }
        
        $migrationFiles[] = [
            'file' => $fileName,
            'name' => $migrationName,
            'path' => $file,
            'is_executed' => $isExecuted,
            'size' => filesize($file),
            'modified' => filemtime($file)
        ];
    }
}

render_admin_layout_start('Migration Yönetimi', 'migrate');
?>

<?php if($error): ?>
    <div class="mb-4 p-4 rounded-lg glass-card border border-red-500/30 text-red-400">
        <?=htmlspecialchars($error)?>
    </div>
<?php endif; ?>

<?php if($success): ?>
    <div class="mb-4 p-4 rounded-lg glass-card border border-cyan-500/30 text-cyan-400">
        <?=htmlspecialchars($success)?>
    </div>
<?php endif; ?>

<div class="space-y-6 mb-8">
    <!-- Migration Çalıştırma -->
    <div class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h3 class="text-xl font-heading font-semibold text-white mb-4">Migration Çalıştır</h3>
        
        <div class="mb-4 p-4 rounded-lg bg-yellow-500/10 border border-yellow-500/30 text-yellow-300 text-sm">
            <strong>⚠️ Uyarı:</strong> Migrationlar veritabanı yapısını değiştirir. 
            Çalıştırmadan önce veritabanı yedeği alın!
        </div>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
            <button type="submit" name="run_migrations" 
                    class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 text-white rounded-lg transition font-medium">
                Çalıştırılmamış Migrationları Çalıştır
            </button>
        </form>
        
        <?php if (!empty($migrationResults)): ?>
            <div class="mt-4 p-4 rounded-lg bg-gray-900/50 border border-gray-700">
                <h4 class="text-sm font-semibold text-white mb-2">Çalıştırma Sonuçları:</h4>
                <pre class="text-xs text-gray-300 whitespace-pre-wrap font-mono"><?=htmlspecialchars(implode("\n", $migrationResults))?></pre>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Migration Dosyaları Listesi -->
    <div class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h3 class="text-xl font-heading font-semibold text-white mb-4">Migration Dosyaları</h3>
        
        <?php if (empty($migrationFiles)): ?>
            <p class="text-gray-400">Migration dosyası bulunamadı.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-400 border-b border-gray-700">
                        <tr>
                            <th class="py-3 font-medium">Dosya Adı</th>
                            <th class="py-3 font-medium">Durum</th>
                            <th class="py-3 font-medium">Boyut</th>
                            <th class="py-3 font-medium">Değiştirilme</th>
                            <th class="py-3 font-medium">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        <?php foreach ($migrationFiles as $migration): ?>
                            <tr class="hover:bg-cyan-500/5 transition">
                                <td class="py-3 font-mono text-xs text-white">
                                    <?=htmlspecialchars($migration['file'])?>
                                </td>
                                <td class="py-3">
                                    <?php if ($migration['is_executed']): ?>
                                        <span class="px-3 py-1 text-xs rounded-lg bg-green-500/20 text-green-400 border border-green-500/30">
                                            ✅ Çalıştırılmış
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 text-xs rounded-lg bg-yellow-500/20 text-yellow-400 border border-yellow-500/30">
                                            ⏳ Beklemede
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-gray-300">
                                    <?=number_format($migration['size'] / 1024, 2)?> KB
                                </td>
                                <td class="py-3 text-gray-300 text-xs">
                                    <?=date('d.m.Y H:i', $migration['modified'])?>
                                </td>
                                <td class="py-3">
                                    <button onclick="viewMigration('<?=htmlspecialchars($migration['name'], ENT_QUOTES)?>')" 
                                            class="text-xs px-3 py-1 rounded-lg glass-card border border-cyan-500/20 text-cyan-400 hover:border-cyan-500/40 transition">
                                        Görüntüle
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Çalıştırılmış Migrationlar -->
    <div class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h3 class="text-xl font-heading font-semibold text-white mb-4">Çalıştırılmış Migrationlar</h3>
        
        <?php if (empty($executedMigrations)): ?>
            <p class="text-gray-400">Henüz çalıştırılmış migration yok.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-400 border-b border-gray-700">
                        <tr>
                            <th class="py-3 font-medium">Migration Adı</th>
                            <th class="py-3 font-medium">Çalıştırılma Tarihi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        <?php foreach ($executedMigrations as $executed): ?>
                            <tr class="hover:bg-cyan-500/5 transition">
                                <td class="py-3 font-mono text-xs text-white">
                                    <?=htmlspecialchars($executed['migration_name'])?>
                                </td>
                                <td class="py-3 text-gray-300 text-xs">
                                    <?=date('d.m.Y H:i:s', strtotime($executed['executed_at']))?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Migration Görüntüleme Modal -->
<div id="migrationModal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center">
    <div class="glass-card rounded-xl border border-cyan-500/20 p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-heading font-semibold text-white">Migration İçeriği</h3>
            <button onclick="closeMigrationModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <pre id="migrationContent" class="text-xs text-gray-300 whitespace-pre-wrap font-mono bg-gray-900/50 p-4 rounded-lg overflow-x-auto"></pre>
    </div>
</div>

<script>
function viewMigration(migrationName) {
    fetch('migrate.php?view=' + encodeURIComponent(migrationName))
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                document.getElementById('migrationContent').textContent = data.content;
                const modal = document.getElementById('migrationModal');
                modal.classList.remove('hidden');
            } else {
                alert('Migration içeriği yüklenemedi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu');
        });
}

function closeMigrationModal() {
    document.getElementById('migrationModal').classList.add('hidden');
}

// Modal dışına tıklanınca kapat
document.getElementById('migrationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMigrationModal();
    }
});
</script>

<?php render_admin_layout_end(); ?>
