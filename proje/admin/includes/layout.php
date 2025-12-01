<?php
if (!function_exists('render_admin_layout_start')) {
    function render_admin_layout_start(string $pageTitle, string $activePage = 'dashboard'): void {
        $adminUsername = $_SESSION['admin_username'] ?? 'Admin';
        $adminId = $_SESSION['admin_id'] ?? 0;
        
        $menuItems = [
            'dashboard' => ['url' => 'dashboard.php', 'label' => 'Yönetim Paneli', 'icon' => '📊'],
            'live_visitors' => ['url' => 'live_visitors.php', 'label' => 'Canlı Ziyaretçiler', 'icon' => '👁️'],
            'sites' => ['url' => 'sites.php', 'label' => 'Siteler', 'icon' => '🌐'],
            'api_keys' => ['url' => 'api_keys.php', 'label' => 'API Anahtarları', 'icon' => '🔑'],
            'settings' => ['url' => 'settings.php', 'label' => 'Bot Filtre Ayarları', 'icon' => '⚙️'],
            'ab_testing' => ['url' => 'ab_testing.php', 'label' => 'A/B Testing', 'icon' => '🧪'],
            'bot_simulation' => ['url' => 'bot_simulation.php', 'label' => 'Bot Simulasyonu', 'icon' => '🤖'],
            'test_ip2location' => ['url' => 'test_ip2location.php', 'label' => 'IP2Location Test', 'icon' => '🔍'],
            'manage_admins' => ['url' => 'manage_admins.php', 'label' => 'Adminleri Yönet', 'icon' => '🛠️'],
            'profile' => ['url' => 'profile.php', 'label' => 'Profilim', 'icon' => '👤'],
            'manage_bots' => ['url' => 'manage_bots.php', 'label' => 'Botları Yönet', 'icon' => '🤖'],
            'bot_stats' => ['url' => 'bot_stats.php', 'label' => 'Bot İstatistikleri', 'icon' => '📈'],
            'migrate' => ['url' => 'migrate.php', 'label' => 'Migrate', 'icon' => '🔄'],
            'maintenance' => ['url' => 'maintenance.php', 'label' => 'Bakım & Temizlik', 'icon' => '🧹'],
        ];
        ?>
<!DOCTYPE html>
<html lang="tr" id="html-root" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=htmlspecialchars($pageTitle)?> - Cloacker System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { 
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'cyber-bg': '#090E15',
                        'cyber-primary': '#0CD6F5',
                        'cyber-danger': '#EF4444',
                    },
                    fontFamily: {
                        'heading': ['Montserrat', 'sans-serif'],
                        'body': ['Inter', 'sans-serif'],
                    }
                }
            }
        };
    </script>
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Montserrat', sans-serif;
        }
        
        body {
            background-color: #090E15;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            color: #ffffff;
            min-height: 100vh;
        }
        
        .glass-card {
            background: rgba(9, 14, 21, 0.4);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(12, 214, 245, 0.1);
        }
        
        .glass-sidebar {
            background: rgba(9, 14, 21, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(12, 214, 245, 0.15);
        }
        
        .neon-glow-cyan {
            box-shadow: 0 0 10px rgba(12, 214, 245, 0.3),
                        0 0 20px rgba(12, 214, 245, 0.2),
                        0 0 30px rgba(12, 214, 245, 0.1);
        }
        
        .neon-glow-red {
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.3),
                        0 0 20px rgba(239, 68, 68, 0.2),
                        0 0 30px rgba(239, 68, 68, 0.1);
        }
        
        .neon-text-cyan {
            color: #0CD6F5;
            text-shadow: 0 0 10px rgba(12, 214, 245, 0.5),
                         0 0 20px rgba(12, 214, 245, 0.3),
                         0 0 30px rgba(12, 214, 245, 0.2);
        }
        
        .logo-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #0CD6F5 0%, #00A8CC 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.05em;
        }
        
        .menu-item-active {
            background: linear-gradient(135deg, rgba(12, 214, 245, 0.2) 0%, rgba(0, 168, 204, 0.2) 100%);
            border-left: 3px solid #0CD6F5;
            color: #0CD6F5;
        }
        
        .menu-item-active .menu-icon {
            filter: drop-shadow(0 0 8px rgba(12, 214, 245, 0.6));
        }
        
        @keyframes pulse-glow {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
        
        @media (max-width: 1024px) {
            .sidebar-mobile {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            
            .sidebar-mobile.open {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black/80 z-40 lg:hidden hidden" onclick="toggleMobileMenu()"></div>
    
    <!-- Header -->
    <header class="glass-card sticky top-0 z-50 border-b border-cyan-500/20">
        <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-4">
                    <button onclick="toggleMobileMenu()" class="lg:hidden p-2 rounded-lg hover:bg-cyan-500/10 text-cyan-400 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center neon-glow-cyan">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <span class="logo-text">CLOACKER SYSTEM</span>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg glass-card border border-cyan-500/20">
                        <div class="w-2 h-2 rounded-full bg-cyan-400 pulse-glow"></div>
                        <span class="text-cyan-300"><?=htmlspecialchars($adminUsername)?></span>
                    </div>
                    <a href="?logout=1" class="px-4 py-2 rounded-lg glass-card border border-red-500/20 hover:border-red-500/40 text-red-400 hover:text-red-300 transition neon-glow-red">
                        Çıkış
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-6 gap-6">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar-mobile fixed lg:sticky top-16 left-0 w-64 h-[calc(100vh-4rem)] glass-sidebar rounded-lg p-4 z-50 lg:z-auto overflow-y-auto">
            <nav class="space-y-2">
                <?php foreach ($menuItems as $key => $item): ?>
                    <a href="<?=htmlspecialchars($item['url'])?>" 
                       onclick="if(window.innerWidth < 1024) toggleMobileMenu();"
                       class="menu-item flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300 <?= $activePage === $key ? 'menu-item-active' : 'hover:bg-cyan-500/10 text-gray-300 hover:text-cyan-400' ?>">
                        <span class="menu-icon text-xl"><?=$item['icon']?></span>
                        <span class="font-medium"><?=$item['label']?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0">
            <div class="mb-6">
                <h2 class="text-3xl font-heading font-bold text-white"><?=htmlspecialchars($pageTitle)?></h2>
            </div>
<?php
    }

    function render_admin_layout_end(): void {
        ?>
        </main>
    </div>

    <!-- Footer -->
    <footer class="glass-card border-t border-cyan-500/20 py-6 mt-12">
        <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-sm text-gray-400">
                © <?=date('Y')?> Cloacker System. Tüm hakları saklıdır. 
                <a href="mailto:sunucukrali58@gmail.com" class="neon-text-cyan hover:underline transition">Kahin</a> tarafından geliştirilmiştir.
            </p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        }
        
        // Close mobile menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('mobile-overlay');
                if (sidebar && overlay) {
                    sidebar.classList.remove('open');
                    overlay.classList.add('hidden');
                }
            }
        });
    </script>
</body>
</html>
<?php
    }
}
?>

