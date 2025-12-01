<?php
require_once __DIR__ . '/error_logger.php';

if (!function_exists('config')) {
    $GLOBALS['app_config'] = require __DIR__ . '/config.php';

    function config(string $key, $default = null) {
        $segments = explode('.', $key);
        $value = $GLOBALS['app_config'];

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function sendSecurityHeaders(): void {
    static $sent = false;
    if ($sent || headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    $sent = true;
}

sendSecurityHeaders();

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrfToken(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Geçersiz istek. Lütfen formu tekrar gönderin.');
    }
}

// Veritabanı bağlantısı (UTF8, prepared statement)
class DB {
    private static $pdo = null;

    public static function connect() {
        if (self::$pdo === null) {
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
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ];

            self::$pdo = new PDO($dsn, $user, $pass, $opt);
        }
        return self::$pdo;
    }
}

function normalizeDomain(?string $domain): ?string {
    if (!is_string($domain)) {
        return null;
    }
    $domain = trim(strtolower($domain));
    if ($domain === '') {
        return null;
    }

    if (!preg_match('#^https?://#i', $domain)) {
        $parsedHost = parse_url('http://' . $domain, PHP_URL_HOST);
    } else {
        $parsedHost = parse_url($domain, PHP_URL_HOST);
    }

    $normalized = $parsedHost ?: $domain;
    $normalized = preg_replace('/^www\./', '', $normalized);

    return $normalized ?: null;
}

function hostMatchesDomain(string $host, string $domain): bool {
    if ($host === $domain) {
        return true;
    }

    $hostLen = strlen($host);
    $domainLen = strlen($domain);

    if ($domainLen === 0 || $hostLen < $domainLen) {
        return false;
    }

    $suffix = substr($host, $hostLen - $domainLen);
    if ($suffix !== $domain) {
        return false;
    }

    if ($hostLen === $domainLen) {
        return true;
    }

    return $host[$hostLen - $domainLen - 1] === '.';
}

function resolveSiteConfiguration(?int $siteId = null, ?string $host = null): array {
    $pdo = DB::connect();
    $siteRow = null;
    $normalizedHost = normalizeDomain($host);

    if ($siteId) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, domain, normal_url, fake_url, settings FROM cloacker_sites WHERE id = :id AND is_active = 1 LIMIT 1");
            $stmt->execute([':id' => $siteId]);
            $siteRow = $stmt->fetch() ?: null;
        } catch (Exception $e) {
            error_log("Site configuration yüklenemedi: " . $e->getMessage());
        }
    }

    if (!$siteRow && $normalizedHost) {
        try {
            $stmt = $pdo->query("SELECT id, name, domain, normal_url, fake_url, settings FROM cloacker_sites WHERE is_active = 1");
            while ($row = $stmt->fetch()) {
                $storedDomain = normalizeDomain($row['domain'] ?? '');
                if ($storedDomain && hostMatchesDomain($normalizedHost, $storedDomain)) {
                    $siteRow = $row;
                    break;
                }
            }
        } catch (Exception $e) {
            error_log("Domain bazlı site eşleşmesi başarısız: " . $e->getMessage());
        }
    }

    if ($siteRow) {
        return [
            'source' => 'site',
            'site_id' => (int)$siteRow['id'],
            'normal_url' => $siteRow['normal_url'],
            'fake_url' => $siteRow['fake_url'],
            'settings' => !empty($siteRow['settings']) ? (json_decode($siteRow['settings'], true) ?: []) : [],
        ];
    }

    $settingsRow = null;
    try {
        $stmt = $pdo->query("SELECT * FROM cloacker_settings LIMIT 1");
        $settingsRow = $stmt->fetch() ?: null;
    } catch (Exception $e) {
        error_log("Genel ayarlar okunamadı: " . $e->getMessage());
    }

    $genericSettings = [];
    if ($settingsRow) {
        $genericSettings = [
            'allowed_countries' => $settingsRow['allowed_countries'] ?? '',
            'allowed_os' => $settingsRow['allowed_os'] ?? '',
            'allowed_browsers' => $settingsRow['allowed_browsers'] ?? '',
            'bot_confidence_threshold' => $settingsRow['bot_confidence_threshold'] ?? null,
        ];
    }

    return [
        'source' => 'global',
        'site_id' => null,
        'normal_url' => $settingsRow['normal_url'] ?? 'https://google.com',
        'fake_url' => $settingsRow['fake_url'] ?? 'https://google.com',
        'settings' => $genericSettings,
    ];
}

// Private IP kontrolü (IPv4 ve IPv6) - GELİŞTİRİLMİŞ
function isPrivateIP($ip) {
    if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }
    
    // IPv4 private ranges
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
    
    // IPv6 private ranges
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $ipv6 = @inet_pton($ip);
        if ($ipv6 === false) {
            return false;
        }
        
        // fc00::/7 (ULA)
        if ((ord($ipv6[0]) & 0xFE) === 0xFC) {
            return true;
        }
        
        // fe80::/10 (Link-local)
        if ((ord($ipv6[0]) & 0xFF) === 0xFE && (ord($ipv6[1]) & 0xC0) === 0x80) {
            return true;
        }
        
        // ::1 (localhost)
        if ($ip === '::1' || $ip === '0:0:0:0:0:0:0:1') {
            return true;
        }
        
        // IPv4-mapped IPv6 (::ffff:0:0/96)
        if (strlen($ipv6) >= 16 && substr($ipv6, 0, 12) === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff") {
            $ipv4 = @inet_ntop(substr($ipv6, 12));
            if ($ipv4 && filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return !filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            }
        }
    }
    
    return false;
}

// Kullanıcı IP'si alma - TAMAMEN YENİDEN YAZILDI
function getClientIP() {
    // DEBUG: Tüm IP header'larını logla
    $debugHeaders = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    $foundIPs = [];
    foreach ($debugHeaders as $header) {
        if (!empty($_SERVER[$header])) {
            $foundIPs[$header] = $_SERVER[$header];
        }
    }
    
    // Öncelik sırası: Güvenilir header'lar
    $trustedHeaders = [
        'HTTP_CF_CONNECTING_IP',      // Cloudflare (en güvenilir)
        'HTTP_X_REAL_IP',              // Nginx reverse proxy
        'HTTP_CLIENT_IP',              // Bazı proxy'ler
    ];
    
    // Önce güvenilir header'lardan public IP ara
    foreach ($trustedHeaders as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim($_SERVER[$key]);
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP) && !isPrivateIP($ip)) {
                error_log("Cloacker IP: Güvenilir header'dan alındı: $key = $ip");
                return $ip;
            }
        }
    }
    
    // HTTP_X_FORWARDED_FOR kontrolü - İlk public IP'yi al
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($forwardedIps as $fip) {
            $fip = trim($fip);
            if (filter_var($fip, FILTER_VALIDATE_IP) && !isPrivateIP($fip)) {
                error_log("Cloacker IP: X-Forwarded-For'dan alındı: $fip");
                return $fip;
            }
        }
        // Eğer sadece private IP'ler varsa, ilkini al (proxy zinciri)
        foreach ($forwardedIps as $fip) {
            $fip = trim($fip);
            if (filter_var($fip, FILTER_VALIDATE_IP)) {
                error_log("Cloacker IP: X-Forwarded-For'dan private IP alındı: $fip");
                return $fip;
            }
        }
    }
    
    // REMOTE_ADDR kontrolü
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $remoteAddr = trim($_SERVER['REMOTE_ADDR']);
        if (filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            // Eğer REMOTE_ADDR private IP ise, bu bir proxy/load balancer arkasındayız demektir
            // Bu durumda güvenilir header'lara tekrar bak (private IP'leri de kabul et)
            if (isPrivateIP($remoteAddr)) {
                foreach ($trustedHeaders as $key) {
                    if (!empty($_SERVER[$key])) {
                        $proxyIp = trim($_SERVER[$key]);
                        if (strpos($proxyIp, ',') !== false) {
                            $proxyIp = trim(explode(',', $proxyIp)[0]);
                        }
                        if (filter_var($proxyIp, FILTER_VALIDATE_IP)) {
                            error_log("Cloacker IP: REMOTE_ADDR private, güvenilir header'dan alındı: $key = $proxyIp");
                            return $proxyIp;
                        }
                    }
                }
                // Eğer hala bulamadıysak, REMOTE_ADDR'ı kullan
                error_log("Cloacker IP: REMOTE_ADDR private IP kullanıldı: $remoteAddr");
                return $remoteAddr;
            } else {
                // REMOTE_ADDR public IP ise, ama önce HTTP_X_FORWARDED_FOR'a tekrar bak
                if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                    foreach ($forwardedIps as $fip) {
                        $fip = trim($fip);
                        if (filter_var($fip, FILTER_VALIDATE_IP)) {
                            error_log("Cloacker IP: REMOTE_ADDR public, X-Forwarded-For'dan alındı: $fip");
                            return $fip;
                        }
                    }
                }
                // Eğer HTTP_X_FORWARDED_FOR yoksa, REMOTE_ADDR'ı kullan
                error_log("Cloacker IP: REMOTE_ADDR kullanıldı: $remoteAddr");
                return $remoteAddr;
            }
        }
    }
    
    error_log("Cloacker IP: IP bulunamadı, BILINMIYOR döndürüldü");
    return 'BILINMIYOR';
}

// Bot tespiti (Google, Facebook, X, diğer botlar) - İYİLEŞTİRİLDİ
function isBot($ua, array $server = []) {
    $ua = strtolower($ua ?? '');
    // Boş UA bot olabilir
    if ($ua === '') {
        return true;
    }
    // "unknown" string'i bot değildir - bazı gerçek tarayıcılar bunu kullanabilir

    $knownBots = [
        'googlebot','bingbot','slurp','duckduckbot','baiduspider','yandexbot','sogou','exabot',
        'facebookexternalhit','facebot','facebookplatform','twitterbot','linkedinbot','embedly',
        'quora link preview','showyoubot','outbrain','pinterest/0.','developers.google.com/+/web/snippet',
        'applebot','telegrambot','discordbot','x-bot','xbot','adsbot-google','adsbot-google-mobile'
    ];
    foreach ($knownBots as $bot) {
        if (strpos($ua, $bot) !== false) {
            return true;
        }
    }

    $aggressiveClients = [
        'python-requests','curl/','wget/','powershell','phantomjs','headlesschrome','selenium',
        'scrapy','aiohttp','okhttp','httpclient','libwww-perl','go-http-client','feedfetcher',
        'node-superagent','java/','jakarta','cfnetwork','datanyze','axios','metasploit','scanbot'
    ];
    foreach ($aggressiveClients as $pattern) {
        if (strpos($ua, $pattern) !== false) {
            return true;
        }
    }

    if (!empty($server)) {
        if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $server)) {
            $acceptLang = $server['HTTP_ACCEPT_LANGUAGE'];
            if ($acceptLang === '' || strlen($acceptLang) < 2) {
                return true;
            }
        }
        if (isset($server['HTTP_SEC_FETCH_SITE']) && $server['HTTP_SEC_FETCH_SITE'] === 'none' && strpos($ua, 'mozilla/') === false) {
            return true;
        }
    }

    return false;
}

// VPN/Proxy tespiti IPHub API ile
function isVpnProxy($ip) {
    $apiKey = config('api_keys.iphub');
    if (empty($apiKey) || $ip === 'BILINMIYOR' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    $cacheKey = 'iphub_' . md5($ip);
    if (isset($_SESSION[$cacheKey])) {
        return $_SESSION[$cacheKey] === '1';
    }

    try {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 3,
                'method' => 'GET',
                'header' => "X-Key: $apiKey\r\n"
            ]
        ]);
        $response = @file_get_contents("https://v2.api.iphub.info/ip/$ip", false, $ctx);
        if ($response) {
            $data = json_decode($response, true);
            $isProxy = isset($data['block']) && $data['block'] === 1;
            $_SESSION[$cacheKey] = $isProxy ? '1' : '0';
            return $isProxy;
        }
    } catch (Exception $e) {
        error_log("IPHub API hatası: " . $e->getMessage());
    }

    return false;
}

// Ülke tespiti (basit IP geolocation)
function getCountry($ip) {
    if ($ip === 'BILINMIYOR' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return 'UN';
    }

    $cacheKey = 'country_' . md5($ip);
    if (isset($_SESSION[$cacheKey])) {
        return $_SESSION[$cacheKey];
    }

    try {
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $response = @file_get_contents("http://ip-api.com/json/$ip?fields=countryCode", false, $ctx);
        if ($response) {
            $data = json_decode($response, true);
            $country = strtoupper($data['countryCode'] ?? 'UN');
            $_SESSION[$cacheKey] = $country;
            return $country;
        }
    } catch (Exception $e) {
        error_log("IP-API hatası: " . $e->getMessage());
    }

    return 'UN';
}

// ASN ve IP bilgilerini al (ip-api.com)
function getIPInfo($ip) {
    if ($ip === 'BILINMIYOR' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return null;
    }

    $cacheKey = 'ipinfo_' . md5($ip);
    if (isset($_SESSION[$cacheKey])) {
        return $_SESSION[$cacheKey];
    }

    try {
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $response = @file_get_contents("http://ip-api.com/json/$ip?fields=status,message,country,countryCode,regionName,city,isp,org,as,asname,query", false, $ctx);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['status']) && $data['status'] === 'success') {
                $result = [
                    'asn' => $data['as'] ?? null,
                    'asn_name' => $data['asname'] ?? null,
                    'isp' => $data['isp'] ?? null,
                    'org' => $data['org'] ?? null,
                    'country' => $data['country'] ?? null,
                    'countryCode' => $data['countryCode'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['regionName'] ?? null,
                ];
                $_SESSION[$cacheKey] = $result;
                return $result;
            }
        }
    } catch (Exception $e) {
        error_log("IP-API info hatası: " . $e->getMessage());
    }

    return null;
}

// Real-time Threat Intelligence kontrolü (AbuseIPDB)
function checkThreatIntelligence($ip) {
    if ($ip === 'BILINMIYOR' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return null;
    }

    $apiKey = config('api_keys.abuseipdb');
    if (empty($apiKey)) {
        return null;
    }

    $cacheKey = 'threat_' . md5($ip);
    if (isset($_SESSION[$cacheKey])) {
        return $_SESSION[$cacheKey];
    }

    try {
        $ch = curl_init("https://api.abuseipdb.com/api/v2/check?ipAddress=$ip&maxAgeInDays=90&verbose");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Key: $apiKey", "Accept: application/json"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['data'])) {
                $abuseData = $data['data'];
                $threatScore = 0;
                
                // Abuse confidence skorunu hesapla (0-100)
                if (isset($abuseData['abuseConfidencePercentage'])) {
                    $threatScore = (int)$abuseData['abuseConfidencePercentage'];
                }
                
                // Usage type kontrolü (hosting, datacenter vb.)
                $usageType = $abuseData['usageType'] ?? null;
                $isHosting = ($usageType === 'hosting' || $usageType === 'datacenter');
                
                $result = [
                    'threat_score' => $threatScore,
                    'abuse_confidence' => $abuseData['abuseConfidencePercentage'] ?? 0,
                    'usage_type' => $usageType,
                    'is_hosting' => $isHosting,
                    'is_public' => $abuseData['isPublic'] ?? false,
                    'is_whitelisted' => $abuseData['isWhitelisted'] ?? false,
                    'country_code' => $abuseData['countryCode'] ?? null,
                    'isp' => $abuseData['isp'] ?? null,
                    'domain' => $abuseData['domain'] ?? null,
                    'total_reports' => $abuseData['totalReports'] ?? 0,
                    'num_distinct_users' => $abuseData['numDistinctUsers'] ?? 0,
                    'last_reported_at' => $abuseData['lastReportedAt'] ?? null,
                ];
                
                $_SESSION[$cacheKey] = $result;
                return $result;
            }
        }
    } catch (Exception $e) {
        error_log("AbuseIPDB API hatası: " . $e->getMessage());
    }

    return null;
}

// IP Yaşı ve Fraud Skoru kontrolü (IP2Location)
function getIPAgeAndFraudScore($ip) {
    if ($ip === 'BILINMIYOR' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return null;
    }

    $apiKey = config('api_keys.ip2location');
    if (empty($apiKey)) {
        return null;
    }

    $cacheKey = 'ipage_' . md5($ip);
    if (isset($_SESSION[$cacheKey])) {
        return $_SESSION[$cacheKey];
    }

    try {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 3,
                'method' => 'GET',
                'header' => "X-API-KEY: {$apiKey}\r\n"
            ]
        ]);
        $url = "https://api.ip2location.io/?ip={$ip}";
        $response = @file_get_contents($url, false, $ctx);
        if ($response) {
            $data = json_decode($response, true);
            
            // Hata kontrolü
            if (isset($data['error'])) {
                error_log("IP2Location API hatası: " . ($data['error']['error_message'] ?? 'Bilinmeyen hata'));
                return null;
            }
            
            // IP yaşı hesaplama (first_seen bilgisi yoksa, domain age veya başka yöntemler kullanılabilir)
            // Şimdilik fraud score'u kullanıyoruz
            $fraudScore = isset($data['fraud_score']) ? (int)$data['fraud_score'] : null;
            
            // IP yaşı için domain age veya başka bir metrik kullanılabilir
            // Şimdilik sadece fraud score döndürüyoruz
            $result = [
                'fraud_score' => $fraudScore,
                'ip_age_days' => null, // IP2Location'da direkt IP yaşı yok, başka API gerekebilir
                'is_proxy' => isset($data['is_proxy']) && $data['is_proxy'] === true,
                'is_residential_proxy' => isset($data['is_residential_proxy']) && $data['is_residential_proxy'] === true,
                'is_datacenter' => isset($data['is_datacenter']) && $data['is_datacenter'] === true,
            ];
            
            $_SESSION[$cacheKey] = $result;
            return $result;
        }
    } catch (Exception $e) {
        error_log("IP2Location API hatası (IP Age): " . $e->getMessage());
    }

    return null;
}

// OS tespiti - İYİLEŞTİRİLDİ
function getOS($ua) {
    if (empty($ua)) {
        return 'unknown';
    }
    
    $ua = strtolower($ua);
    if (strpos($ua, 'windows') !== false) return 'windows';
    if (strpos($ua, 'mac') !== false || strpos($ua, 'darwin') !== false) return 'macos';
    if (strpos($ua, 'linux') !== false && strpos($ua, 'android') === false) return 'linux';
    if (strpos($ua, 'android') !== false) return 'android';
    if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false || strpos($ua, 'ipod') !== false) return 'ios';
    return 'unknown';
}

// Tarayıcı tespiti - TAMAMEN YENİDEN YAZILDI
function getBrowser($ua) {
    if (empty($ua)) {
        return 'Unknown';
    }
    
    $ua = strtolower($ua);
    
    // Edge (Chrome'dan önce kontrol et)
    if (strpos($ua, 'edg') !== false || strpos($ua, 'edge/') !== false) {
        return 'Edge';
    }
    
    // Opera (Chrome'dan önce kontrol et)
    if (strpos($ua, 'opera') !== false || strpos($ua, 'opr/') !== false || strpos($ua, 'opios') !== false) {
        return 'Opera';
    }
    
    // Chrome (Chromium tabanlı tarayıcılar)
    if (strpos($ua, 'chrome') !== false && strpos($ua, 'chromium') === false) {
        return 'Chrome';
    }
    
    // Firefox
    if (strpos($ua, 'firefox') !== false || strpos($ua, 'fxios') !== false) {
        return 'Firefox';
    }
    
    // Safari (Chrome içermemeli)
    if (strpos($ua, 'safari') !== false && strpos($ua, 'chrome') === false && strpos($ua, 'chromium') === false) {
        return 'Safari';
    }
    
    // Samsung Internet
    if (strpos($ua, 'samsungbrowser') !== false) {
        return 'Samsung Internet';
    }
    
    // UC Browser
    if (strpos($ua, 'ucbrowser') !== false || strpos($ua, 'uc browser') !== false) {
        return 'UC Browser';
    }
    
    // Yandex Browser
    if (strpos($ua, 'yabrowser') !== false) {
        return 'Yandex Browser';
    }
    
    // Internet Explorer (eski)
    if (strpos($ua, 'msie') !== false || strpos($ua, 'trident/') !== false) {
        return 'Internet Explorer';
    }
    
    // Eğer Mozilla içeriyorsa ama yukarıdakilerden biri değilse, genel tarayıcı olarak işaretle
    if (strpos($ua, 'mozilla') !== false) {
        return 'Mozilla';
    }
    
    return 'Unknown';
}


// Ülke izin kontrolü
function isCountryAllowed($country) {
    $pdo = DB::connect();

    // Önce allowed_countries tablosuna bak
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cloacker_allowed_countries WHERE country = :country");
        $stmt->execute([':country' => strtoupper($country)]);
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            return true;
        }
    } catch (Exception $e) {
        // Tablo yoksa devam et
    }

    // Tablo boşsa veya yoksa, settings'ten kontrol et
    try {
        $stmt = $pdo->query("SELECT allowed_countries FROM cloacker_settings WHERE id = 1 LIMIT 1");
        $row = $stmt->fetch();
        if ($row && !empty($row['allowed_countries'])) {
            $allowed = explode(',', strtoupper($row['allowed_countries']));
            $allowed = array_map('trim', $allowed);
            return in_array(strtoupper($country), $allowed);
        }
    } catch (Exception $e) {
        // Settings yoksa varsayılan olarak true dön (geriye dönük uyumluluk)
    }

    // Hiçbir kontrol yapılamazsa, varsayılan olarak true dön
    return true;
}

// OS izin kontrolü
function isOSAllowed($os) {
    $allowed = ['windows', 'macos', 'linux', 'android', 'ios'];
    return in_array(strtolower($os), $allowed);
}

// JA3/JA3s kontrolü
function checkJA3Fingerprint(?string $ja3Hash, ?string $ja3sHash = null): bool {
    // Ayarları yükle
    $pdo = DB::connect();
    $settings = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
    
    // JA3 kontrolü aktif değilse false dön
    if (!($settings['enable_ja3_check'] ?? 1)) {
        return false;
    }
    
    if (empty($ja3Hash)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM cloacker_ja3_blacklist 
            WHERE ja3_hash = :ja3 
            AND is_active = 1
        ");
        $stmt->execute([':ja3' => $ja3Hash]);
        $count = (int)$stmt->fetchColumn();
        
        if ($count > 0) {
            return true; // Blacklist'te bulundu
        }
        
        // JA3s kontrolü (opsiyonel)
        if ($ja3sHash) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM cloacker_ja3_blacklist 
                WHERE ja3s_hash = :ja3s 
                AND is_active = 1
            ");
            $stmt->execute([':ja3s' => $ja3sHash]);
            $count = (int)$stmt->fetchColumn();
            return $count > 0;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("JA3 kontrolü hatası: " . $e->getMessage());
        return false;
    }
}

// Canvas/WebGL/Audio fingerprint analizi
function analyzeClientFingerprints(array $fingerprints): array {
    $signals = [];
    $scores = [];
    $details = [];
    
    // Ayarları yükle
    $pdo = DB::connect();
    $settings = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
    
    // Canvas fingerprint kontrolü
    if (($settings['enable_canvas_check'] ?? 1)) {
        if (empty($fingerprints['canvas'])) {
            $signals[] = 'missing_canvas_fingerprint';
            $scores[] = (int)($settings['canvas_score'] ?? 8);
            $details['missing_canvas'] = true;
        } else {
            // Canvas hash'i çok kısa veya çok uzunsa şüpheli
            $canvasLen = strlen($fingerprints['canvas']);
            if ($canvasLen < 8 || $canvasLen > 64) {
                $signals[] = 'suspicious_canvas_length';
                $scores[] = 5;
            }
        }
    }
    
    // WebGL fingerprint kontrolü
    if (($settings['enable_webgl_check'] ?? 1)) {
        if (empty($fingerprints['webgl'])) {
            $signals[] = 'missing_webgl_fingerprint';
            $scores[] = (int)($settings['webgl_score'] ?? 7);
            $details['missing_webgl'] = true;
        }
    }
    
    // Audio fingerprint kontrolü
    if (($settings['enable_audio_check'] ?? 1)) {
        if (empty($fingerprints['audio'])) {
            $signals[] = 'missing_audio_fingerprint';
            $scores[] = (int)($settings['audio_score'] ?? 6);
            $details['missing_audio'] = true;
        }
    }
    
    // WebRTC leak kontrolü
    if (($settings['enable_webrtc_check'] ?? 1)) {
        if (!empty($fingerprints['webrtc']) && is_array($fingerprints['webrtc'])) {
            if (!empty($fingerprints['webrtc']['leak']) && $fingerprints['webrtc']['leak'] === true) {
                $signals[] = 'webrtc_leak_detected';
                $scores[] = (int)($settings['webrtc_score'] ?? 10);
                $details['webrtc_leak'] = $fingerprints['webrtc']['localIPs'] ?? [];
            }
        }
    }
    
    // Headless detection signals
    if (($settings['enable_headless_check'] ?? 1)) {
        if (!empty($fingerprints['headless']) && is_array($fingerprints['headless'])) {
            foreach ($fingerprints['headless'] as $signal) {
                $signals[] = 'headless_' . $signal;
                $scores[] = (int)($settings['headless_score'] ?? 12);
            }
            $details['headless_signals'] = $fingerprints['headless'];
        }
    }
    
    // Fonts kontrolü
    if (($settings['enable_fonts_check'] ?? 1)) {
        if (empty($fingerprints['fonts'])) {
            $signals[] = 'missing_fonts_detection';
            $scores[] = (int)($settings['fonts_score'] ?? 4);
        }
    }
    
    // Plugins kontrolü
    if (($settings['enable_plugins_check'] ?? 1)) {
        if (empty($fingerprints['plugins'])) {
            $signals[] = 'missing_plugins';
            $scores[] = (int)($settings['plugins_score'] ?? 3);
        }
    }
    
    // SpeechSynthesis kontrolü
    $speechSynthesis = $fingerprints['speechSynthesis'] ?? null;
    if ($speechSynthesis === null || $speechSynthesis === 0) {
        $signals[] = 'missing_speech_synthesis';
        $scores[] = (int)($settings['speech_synthesis_score'] ?? 3);
    }
    
    return [
        'signals' => $signals,
        'scores' => $scores,
        'details' => $details
    ];
}

// ML tabanlı skorlama (Basit Naive Bayes benzeri)
function calculateMLConfidence(array $features): float {
    // Basit ağırlıklı skorlama (gerçek ML için daha gelişmiş algoritma gerekir)
    $weights = [
        'missing_canvas_fingerprint' => 0.15,
        'missing_webgl_fingerprint' => 0.12,
        'missing_audio_fingerprint' => 0.10,
        'webrtc_leak_detected' => 0.20,
        'headless_navigator.webdriver' => 0.18,
        'headless_missing_chrome_runtime' => 0.15,
        'headless_missing_permissions_api' => 0.12,
        'missing_fonts_detection' => 0.08,
        'missing_plugins' => 0.05,
        'missing_speech_synthesis' => 0.05,
    ];
    
    $confidence = 0.0;
    foreach ($features as $feature) {
        if (isset($weights[$feature])) {
            $confidence += $weights[$feature];
        }
    }
    
    return min(100.0, $confidence * 100);
}

// Fingerprint hash oluştur (IP + UA + Canvas + TLS)
function generateFingerprintHash(string $ip, string $ua, ?string $canvas = null, ?string $ja3 = null): string {
    $combined = $ip . '|' . $ua . '|' . ($canvas ?? '') . '|' . ($ja3 ?? '');
    return hash('sha256', $combined);
}

// Cosine Similarity hesaplama
function cosineSimilarity(array $vecA, array $vecB): float {
    $dotProduct = 0;
    $normA = 0;
    $normB = 0;
    
    foreach ($vecA as $key => $valueA) {
        $valueB = $vecB[$key] ?? 0;
        $dotProduct += $valueA * $valueB;
        $normA += $valueA * $valueA;
        $normB += $valueB * $valueB;
    }
    
    if ($normA == 0 || $normB == 0) return 0.0;
    
    return $dotProduct / (sqrt($normA) * sqrt($normB));
}

// Fingerprint'i vektöre çevir
function fingerprintToVector(array $fingerprints): array {
    return [
        'canvas' => !empty($fingerprints['canvas']) ? crc32($fingerprints['canvas']) % 1000 : 0,
        'webgl' => !empty($fingerprints['webgl']) ? crc32($fingerprints['webgl']) % 1000 : 0,
        'audio' => !empty($fingerprints['audio']) ? crc32($fingerprints['audio']) % 1000 : 0,
        'fonts' => !empty($fingerprints['fonts']) ? crc32($fingerprints['fonts']) % 1000 : 0,
        'plugins' => !empty($fingerprints['plugins']) ? crc32($fingerprints['plugins']) % 1000 : 0,
        'has_canvas' => !empty($fingerprints['canvas']) ? 1 : 0,
        'has_webgl' => !empty($fingerprints['webgl']) ? 1 : 0,
        'has_audio' => !empty($fingerprints['audio']) ? 1 : 0,
        'has_fonts' => !empty($fingerprints['fonts']) ? 1 : 0,
        'has_plugins' => !empty($fingerprints['plugins']) ? 1 : 0,
    ];
}

// Fingerprint History kontrolü ve Similarity hesaplama
function checkFingerprintHistory(string $fingerprintHash, array $clientFingerprints = [], ?int $siteId = null, ?string $userAgent = null): array {
    $pdo = DB::connect();
    
    // Ayarları kontrol et
    $settings = $pdo->query("SELECT enable_fingerprint_similarity FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
    if (!($settings['enable_fingerprint_similarity'] ?? 1)) {
        return ['similarity' => null, 'status' => 'disabled'];
    }
    
    // User-Agent'tan bot tespiti
    $isBotUA = false;
    if ($userAgent) {
        $uaLower = strtolower($userAgent);
        $botPatterns = ['bot', 'crawler', 'spider', 'scraper', 'googlebot', 'bingbot', 'facebookexternalhit', 'twitterbot', 'linkedinbot', 'pinterest', 'tiktokbot', 'whatsapp'];
        foreach ($botPatterns as $pattern) {
            if (strpos($uaLower, $pattern) !== false) {
                $isBotUA = true;
                break;
            }
        }
    }
    
    // Mevcut fingerprint'i vektöre çevir
    $currentVector = fingerprintToVector($clientFingerprints);
    
    // Bot tespiti: Eğer fingerprint'ler eksikse (canvas, webgl, audio yoksa) bu bir bot olabilir
    $missingFingerprints = 0;
    if (empty($clientFingerprints['canvas'])) $missingFingerprints++;
    if (empty($clientFingerprints['webgl'])) $missingFingerprints++;
    if (empty($clientFingerprints['audio'])) $missingFingerprints++;
    
    // Bot tespiti: User-Agent bot ise veya çok fazla fingerprint eksikse
    // Botlar genelde benzer davranış gösterir (fingerprint göndermezler)
    // Reklam botları özellikle benzer pattern'lere sahiptir
    if ($isBotUA || $missingFingerprints >= 2) {
        // Botlar için yüksek similarity döndür (botlar benzer davranış gösterir)
        // Aynı bot türleri benzer fingerprint'lere sahip olur
        // Reklam botları genelde 0.90-0.98 arası similarity gösterir
        $botSimilarity = $isBotUA ? 0.96 : 0.94; // User-Agent bot ise daha yüksek
        
        return [
            'similarity' => $botSimilarity, // Botlar için yüksek similarity (benzer davranış)
            'status' => 'bot_pattern',
            'reason' => $isBotUA ? 'Bot User-Agent detected' : 'Missing fingerprints detected - likely bot pattern'
        ];
    }
    
    // Veritabanından geçmiş fingerprint'leri al
    try {
        // Son 30 gün içindeki benzer fingerprint'leri bul
        $last30Days = (new DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("
            SELECT 
                v.fingerprint_hash,
                v.canvas_fingerprint,
                v.webgl_fingerprint,
                v.audio_fingerprint,
                v.fonts_hash,
                v.plugins_hash,
                COUNT(*) as visit_count,
                AVG(CASE WHEN v.is_bot = 0 THEN 1 ELSE 0 END) as human_ratio
            FROM cloacker_visitors v
            WHERE v.created_at >= :since
            " . ($siteId ? "AND (v.site_id = :site_id OR v.site_id IS NULL)" : "") . "
            AND v.fingerprint_hash != :current_hash
            GROUP BY v.fingerprint_hash
            HAVING visit_count >= 2
            ORDER BY visit_count DESC
            LIMIT 50
        ");
        
        $params = [
            ':since' => $last30Days,
            ':current_hash' => $fingerprintHash
        ];
        if ($siteId) {
            $params[':site_id'] = $siteId;
        }
        $stmt->execute($params);
        $historicalFps = $stmt->fetchAll();
        
        if (empty($historicalFps)) {
            // Geçmiş veri yok, yeni fingerprint
            return [
                'similarity' => 0.0,
                'status' => 'new',
                'reason' => 'No historical data'
            ];
        }
        
        // Her geçmiş fingerprint ile similarity hesapla
        $maxSimilarity = 0.0;
        $bestMatch = null;
        
        foreach ($historicalFps as $historical) {
            $historicalVector = fingerprintToVector([
                'canvas' => $historical['canvas_fingerprint'] ?? null,
                'webgl' => $historical['webgl_fingerprint'] ?? null,
                'audio' => $historical['audio_fingerprint'] ?? null,
                'fonts' => $historical['fonts_hash'] ?? null,
                'plugins' => $historical['plugins_hash'] ?? null,
            ]);
            
            $similarity = cosineSimilarity($currentVector, $historicalVector);
            
            if ($similarity > $maxSimilarity) {
                $maxSimilarity = $similarity;
                $bestMatch = $historical;
            }
        }
        
        // Eğer similarity çok düşükse ve fingerprint'ler eksikse, bu bir bot olabilir
        if ($maxSimilarity < 0.5 && $missingFingerprints >= 1) {
            // Bot pattern: Eksik fingerprint'ler + düşük similarity = bot
            return [
                'similarity' => 0.92, // Botlar için yüksek similarity (benzer bot pattern)
                'status' => 'bot_pattern',
                'reason' => 'Low similarity with missing fingerprints - bot pattern detected'
            ];
        }
        
        return [
            'similarity' => $maxSimilarity,
            'status' => $maxSimilarity > 0.85 ? 'high_similarity' : ($maxSimilarity > 0.5 ? 'medium_similarity' : 'low_similarity'),
            'visit_count' => $bestMatch['visit_count'] ?? 0,
            'human_ratio' => $bestMatch['human_ratio'] ?? 0
        ];
        
    } catch (Exception $e) {
        error_log("Fingerprint history kontrolü hatası: " . $e->getMessage());
        
        // Hata durumunda, eksik fingerprint'lere göre bot pattern tespiti yap
        if ($missingFingerprints >= 2) {
            return [
                'similarity' => 0.95,
                'status' => 'bot_pattern',
                'reason' => 'Error in history check, but missing fingerprints suggest bot pattern'
            ];
        }
        
        return [
            'similarity' => null,
            'status' => 'error',
            'reason' => $e->getMessage()
        ];
    }
}

// Gelişmiş duplicate kontrol (24 saat, fingerprint hash ile)
function checkDuplicateVisitor(string $ip, string $fingerprintHash, ?int $siteId = null): bool {
    // Ayarları yükle
    $pdo = DB::connect();
    $settings = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
    
    // Duplicate kontrol aktif değilse false dön
    if (!($settings['enable_duplicate_check'] ?? 1)) {
        return false;
    }
    
    $last24h = (new DateTimeImmutable('-24 hours'))->format('Y-m-d H:i:s');
    
    try {
        // Önce 45 saniyelik session kontrolü
        $sessionKey = 'visitor_fp_' . md5($ip . $fingerprintHash);
        if (isset($_SESSION[$sessionKey])) {
            $lastVisit = $_SESSION[$sessionKey];
            $timeDiff = time() - $lastVisit;
            if ($timeDiff < 45) {
                return true; // Duplicate bulundu
            }
        }
        
        // 24 saatlik fingerprint hash kontrolü
        $stmt = $pdo->prepare("
            SELECT id FROM cloacker_visitors
            WHERE fingerprint_hash = :fp_hash
            AND created_at >= :since
            " . ($siteId ? "AND site_id = :site_id" : "AND (site_id IS NULL OR site_id = :site_id)") . "
            ORDER BY id DESC
            LIMIT 1
        ");
        
        $params = [
            ':fp_hash' => $fingerprintHash,
            ':since' => $last24h
        ];
        if ($siteId) {
            $params[':site_id'] = $siteId;
        } else {
            $params[':site_id'] = null;
        }
        
        $stmt->execute($params);
        $found = (bool)$stmt->fetchColumn();
        
        if ($found) {
            $_SESSION[$sessionKey] = time();
            return true;
        }
        
        $_SESSION[$sessionKey] = time();
        return false;
    } catch (Exception $e) {
        error_log("Duplicate kontrol hatası: " . $e->getMessage());
        return false;
    }
}

// Rate limiting per IP + fingerprint
function checkRateLimit(string $ip, string $fingerprintHash, ?int $maxRequests = null, ?int $windowSeconds = null): bool {
    // Ayarları yükle
    $pdo = DB::connect();
    $settings = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
    
    // Rate limiting aktif değilse false dön
    if (!($settings['enable_rate_limit'] ?? 1)) {
        return false;
    }
    
    // Ayarlardan değerleri al
    if ($maxRequests === null) {
        $maxRequests = (int)($settings['rate_limit_max_requests'] ?? 10);
    }
    if ($windowSeconds === null) {
        $windowSeconds = (int)($settings['rate_limit_window_seconds'] ?? 60);
    }
    $pdo = DB::connect();
    $windowStart = (new DateTimeImmutable("-{$windowSeconds} seconds"))->format('Y-m-d H:i:s');
    
    try {
        // Rate limit tablosunu kontrol et
        $pdo->query("SELECT 1 FROM cloacker_rate_limits LIMIT 1");
    } catch (PDOException $e) {
        // Tablo yok, oluştur
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `cloacker_rate_limits` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `ip` varchar(45) NOT NULL,
                    `fingerprint_hash` varchar(64) NOT NULL,
                    `request_count` int(11) NOT NULL DEFAULT 1,
                    `window_start` datetime NOT NULL,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `ip_fp` (`ip`, `fingerprint_hash`),
                    KEY `window_start` (`window_start`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $ex) {
            error_log("Rate limit tablosu oluşturulamadı: " . $ex->getMessage());
            return false; // Hata durumunda rate limit uygulama
        }
    }
    
    try {
        // Mevcut kaydı bul veya oluştur
        $stmt = $pdo->prepare("
            SELECT id, request_count, window_start 
            FROM cloacker_rate_limits
            WHERE ip = :ip AND fingerprint_hash = :fp_hash
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':ip' => $ip,
            ':fp_hash' => $fingerprintHash
        ]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $windowStartTime = strtotime($existing['window_start']);
            $now = time();
            
            if (($now - $windowStartTime) < $windowSeconds) {
                // Aynı pencere içinde
                if ($existing['request_count'] >= $maxRequests) {
                    return true; // Rate limit aşıldı
                }
                
                // Sayacı artır
                $updateStmt = $pdo->prepare("
                    UPDATE cloacker_rate_limits 
                    SET request_count = request_count + 1
                    WHERE id = :id
                ");
                $updateStmt->execute([':id' => $existing['id']]);
            } else {
                // Yeni pencere başlat
                $updateStmt = $pdo->prepare("
                    UPDATE cloacker_rate_limits 
                    SET request_count = 1, window_start = NOW()
                    WHERE id = :id
                ");
                $updateStmt->execute([':id' => $existing['id']]);
            }
        } else {
            // Yeni kayıt oluştur
            $insertStmt = $pdo->prepare("
                INSERT INTO cloacker_rate_limits (ip, fingerprint_hash, request_count, window_start)
                VALUES (:ip, :fp_hash, 1, NOW())
            ");
            $insertStmt->execute([
                ':ip' => $ip,
                ':fp_hash' => $fingerprintHash
            ]);
        }
        
        // Eski kayıtları temizle
        $cleanupStmt = $pdo->prepare("
            DELETE FROM cloacker_rate_limits 
            WHERE window_start < :window_start
        ");
        $cleanupStmt->execute([':window_start' => $windowStart]);
        
        return false; // Rate limit aşılmadı
    } catch (Exception $e) {
        error_log("Rate limit kontrol hatası: " . $e->getMessage());
        return false; // Hata durumunda rate limit uygulama
    }
}

// Residential proxy detection (IP2Location API)
function isResidentialProxy(string $ip): bool {
    // Ayarları yükle
    $pdo = DB::connect();
    $settings = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
    
    // Residential proxy kontrolü aktif değilse false dön
    if (!($settings['enable_residential_proxy_check'] ?? 1)) {
        return false;
    }
    
    $apiKey = config('api_keys.ip2location');
    if (empty($apiKey) || $ip === 'BILINMIYOR' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }
    
    $cacheKey = 'ip2location_' . md5($ip);
    if (isset($_SESSION[$cacheKey])) {
        return $_SESSION[$cacheKey] === '1';
    }
    
    try {
        // IP2Location.io API - API key header'da gönderiliyor
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 3,
                'method' => 'GET',
                'header' => "X-API-KEY: {$apiKey}\r\n"
            ]
        ]);
        $url = "https://api.ip2location.io/?ip={$ip}";
        $response = @file_get_contents($url, false, $ctx);
        if ($response) {
            $data = json_decode($response, true);
            
            // Hata kontrolü
            if (isset($data['error'])) {
                error_log("IP2Location API hatası: " . ($data['error']['error_message'] ?? 'Bilinmeyen hata'));
                return false;
            }
            
            // is_proxy alanı varsa ve true ise datacenter proxy
            // is_residential_proxy varsa ve true ise residential proxy
            $isProxy = isset($data['is_proxy']) && $data['is_proxy'] === true;
            $isResidential = isset($data['is_residential_proxy']) && $data['is_residential_proxy'] === true;
            
            // Residential proxy veya datacenter proxy tespit edildi
            $result = ($isProxy || $isResidential) ? '1' : '0';
            $_SESSION[$cacheKey] = $result;
            return $result === '1';
        }
    } catch (Exception $e) {
        error_log("IP2Location API hatası: " . $e->getMessage());
    }
    
    return false;
}

// Cloudflare gerçek bot doğrulama
function verifyCloudflareBot(string $ua, array $server): bool {
    // Ayarları yükle
    $pdo = DB::connect();
    $settings = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
    
    // Cloudflare bot kontrolü aktif değilse false dön
    if (!($settings['enable_cloudflare_bot_check'] ?? 1)) {
        return false;
    }
    
    // Cloudflare üzerinden geliyor mu?
    if (empty($server['HTTP_CF_RAY'])) {
        return false; // Cloudflare üzerinden gelmiyor
    }
    
    // Googlebot kontrolü
    if (stripos($ua, 'Googlebot') !== false) {
        // Reverse DNS kontrolü (basit versiyon)
        // Gerçek Googlebot'lar googlebot.com veya google.com'dan gelir
        $cfConnectingIp = $server['HTTP_CF_CONNECTING_IP'] ?? null;
        if ($cfConnectingIp) {
            // Cloudflare'in bot management özelliği varsa kullanılabilir
            // Şimdilik basit kontrol
            $cfVisitor = $server['HTTP_CF_VISITOR'] ?? '';
            if (strpos($cfVisitor, 'bot') !== false) {
                // Cloudflare bot olarak işaretlemiş
                return true;
            }
        }
    }
    
    return false;
}

// Client-side challenge doğrulama
function verifyClientChallenge(?string $challengeToken): bool {
    // Ayarları yükle
    $pdo = DB::connect();
    $settings = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
    
    // Challenge kontrolü aktif değilse true dön (kontrol yapma)
    if (!($settings['enable_challenge_check'] ?? 1)) {
        return true;
    }
    
    if (empty($challengeToken)) {
        return false;
    }
    
    // Challenge token formatı: hash|result
    $parts = explode('|', $challengeToken);
    if (count($parts) !== 2) {
        return false;
    }
    
    // Basit doğrulama: result sayısal olmalı
    $result = $parts[1];
    if (!is_numeric($result)) {
        return false;
    }
    
    // Hash doğrulaması yapılabilir ama şimdilik basit kontrol yeterli
    // Gerçek uygulamada challenge'ın geçerliliği daha detaylı kontrol edilebilir
    
    return true;
}

// Log zehirleme koruması - Detayları gizle
function sanitizeLogData(array $data): array {
    // Hassas bilgileri gizle
    $sanitized = $data;
    
    // Bot detection detaylarını gizle
    if (isset($sanitized['signals'])) {
        $sanitized['signals'] = ['hidden']; // Sinyalleri gizle
    }
    
    if (isset($sanitized['details'])) {
        $sanitized['details'] = ['hidden']; // Detayları gizle
    }
    
    // Fingerprint hash'lerini kısalt
    if (isset($sanitized['canvas_fingerprint'])) {
        $sanitized['canvas_fingerprint'] = substr($sanitized['canvas_fingerprint'], 0, 8) . '...';
    }
    
    if (isset($sanitized['webgl_fingerprint'])) {
        $sanitized['webgl_fingerprint'] = substr($sanitized['webgl_fingerprint'], 0, 8) . '...';
    }
    
    return $sanitized;
}

// Dinamik eşik hesaplama
function calculateDynamicThreshold(int $siteId = null): float {
    $pdo = DB::connect();
    $last24h = (new DateTimeImmutable('-24 hours'))->format('Y-m-d H:i:s');
    
    try {
        // Son 24 saatteki bot oranına göre eşik ayarla
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as bots
            FROM cloacker_visitors
            WHERE created_at >= :since
            " . ($siteId ? "AND site_id = :site_id" : "AND site_id IS NULL") . "
        ");
        
        $params = [':since' => $last24h];
        if ($siteId) {
            $params[':site_id'] = $siteId;
        }
        $stmt->execute($params);
        $data = $stmt->fetch();
        
        $total = (int)($data['total'] ?? 0);
        $bots = (int)($data['bots'] ?? 0);
        
        if ($total < 10) {
            // Yeterli veri yoksa varsayılan eşik
            return 30.0;
        }
        
        $botRate = $total > 0 ? ($bots / $total) * 100 : 0;
        
        // Bot oranı yüksekse eşiği düşür (daha agresif)
        // Bot oranı düşükse eşiği yükselt (daha az agresif)
        $baseThreshold = 30.0;
        $adjustment = ($botRate - 50) * 0.3; // -6 ile +6 arası ayarlama
        
        $dynamicThreshold = $baseThreshold + $adjustment;
        
        // Min-max sınırları
        $settings = $pdo->query("SELECT min_threshold, max_threshold FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch();
        $minThreshold = (float)($settings['min_threshold'] ?? 20.0);
        $maxThreshold = (float)($settings['max_threshold'] ?? 50.0);
        
        return max($minThreshold, min($maxThreshold, $dynamicThreshold));
    } catch (Exception $e) {
        error_log("Dinamik eşik hesaplama hatası: " . $e->getMessage());
        return 30.0;
    }
}

// Gelişmiş Fingerprint Analizi - AI Destekli - İYİLEŞTİRİLDİ
function analyzeClientFingerprint($ua, array $server, string $mode = 'full', array $clientFingerprints = []): array {
    $signals = [];
    $scores = [];
    $uaLower = strtolower($ua ?? '');
    $details = [];
    $limitedHeaders = ($mode !== 'full');

    // 1. User-Agent Analizi
    if ($uaLower === '' || $uaLower === 'unknown') {
        $signals[] = 'ua_missing';
        $scores[] = 10;
        $details['ua_missing'] = true;
    }
    
    // 2. Headless Browser Tespiti
    $headlessPatterns = ['headless', 'phantomjs', 'puppeteer', 'playwright', 'chromium'];
    foreach ($headlessPatterns as $pattern) {
        if (strpos($uaLower, $pattern) !== false) {
            $signals[] = 'headless_client';
            $scores[] = 15;
            $details['headless_detected'] = $pattern;
            break;
        }
    }
    
    // 3. Automation Framework Tespiti
    if (strpos($uaLower, 'selenium') !== false || strpos($uaLower, 'webdriver') !== false) {
        $signals[] = 'automation_framework';
        $scores[] = 20;
        $details['automation'] = true;
    }
    
    // 4. Bot Anahtar Kelimeleri
    if (preg_match('/\b(bot|crawler|spider|scraper)\b/i', $ua)) {
        $signals[] = 'bot_keyword';
        $scores[] = 12;
        $details['bot_keyword'] = true;
    }
    
    // 5. Script/CLI Client Tespiti
    $scriptPatterns = ['python-requests', 'curl/', 'wget/', 'httpie', 'postman'];
    foreach ($scriptPatterns as $pattern) {
        if (strpos($uaLower, $pattern) !== false) {
            $signals[] = 'scripted_client';
            $scores[] = 18;
            $details['script_client'] = $pattern;
            break;
        }
    }
    
    // 6. HTTP Header Analizi
    if (!$limitedHeaders && array_key_exists('HTTP_ACCEPT_LANGUAGE', $server) && empty($server['HTTP_ACCEPT_LANGUAGE'])) {
        $signals[] = 'missing_accept_language';
        $scores[] = 5;
        $details['missing_accept_lang'] = true;
    }
    
    // 7. Oversized User-Agent
    if (!empty($server['HTTP_USER_AGENT']) && strlen($server['HTTP_USER_AGENT']) > 400) {
        $signals[] = 'oversized_ua';
        $scores[] = 8;
        $details['ua_length'] = strlen($server['HTTP_USER_AGENT']);
    }
    
    // 8. Suspicious Platform Header
    if (!$limitedHeaders && isset($server['HTTP_SEC_CH_UA_PLATFORM']) && $server['HTTP_SEC_CH_UA_PLATFORM'] === '"??"') {
        $signals[] = 'suspicious_platform';
        $scores[] = 10;
        $details['suspicious_platform'] = true;
    }
    
    // 9. Missing Viewport/Window Size (JavaScript ile kontrol edilebilir)
    if (!$limitedHeaders && !isset($server['HTTP_SEC_CH_VIEWPORT_WIDTH']) && !isset($server['HTTP_VIEWPORT-WIDTH'])) {
        $signals[] = 'missing_viewport';
        $scores[] = 3;
        $details['missing_viewport'] = true;
    }
    
    // 10. Tarayıcı Taklit Tespiti
    $hasMozilla = strpos($uaLower, 'mozilla') !== false;
    $hasWebKit = strpos($uaLower, 'webkit') !== false || strpos($uaLower, 'khtml') !== false;
    $hasChrome = strpos($uaLower, 'chrome') !== false;
    
    if ($hasMozilla && !$hasWebKit && !$hasChrome) {
        $signals[] = 'inconsistent_browser_headers';
        $scores[] = 7;
        $details['inconsistent_headers'] = true;
    }
    
    // 11. Sec-CH-UA Header Analizi (Chrome/Edge)
    if (!$limitedHeaders && isset($server['HTTP_SEC_CH_UA'])) {
        $secChUa = $server['HTTP_SEC_CH_UA'];
        if (empty($secChUa) || $secChUa === '""') {
            $signals[] = 'missing_sec_ch_ua';
            $scores[] = 6;
            $details['missing_sec_ch_ua'] = true;
        }
    } elseif (!$limitedHeaders && $hasChrome) {
        $signals[] = 'chrome_without_sec_ch_ua';
        $scores[] = 5;
        $details['chrome_no_sec_ch_ua'] = true;
    }
    
    // 12. Accept Header Analizi
    $accept = $server['HTTP_ACCEPT'] ?? '';
    if (!$limitedHeaders && (empty($accept) || !preg_match('/text\/html|application\/xhtml\+xml/', $accept))) {
        $signals[] = 'suspicious_accept_header';
        $scores[] = 4;
        $details['suspicious_accept'] = $accept;
    }
    
    // 13. Connection Header
    if (!$limitedHeaders && isset($server['HTTP_CONNECTION']) && strtolower($server['HTTP_CONNECTION']) !== 'keep-alive' && strtolower($server['HTTP_CONNECTION']) !== 'upgrade') {
        $signals[] = 'unusual_connection_header';
        $scores[] = 3;
        $details['unusual_connection'] = $server['HTTP_CONNECTION'];
    }
    
    // 14. Referer Analizi
    $referer = $server['HTTP_REFERER'] ?? '';
    if (!$limitedHeaders && !empty($referer)) {
        $refererDomain = parse_url($referer, PHP_URL_HOST);
        $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
        if ($refererDomain && $currentDomain && $refererDomain !== $currentDomain) {
            if (strpos($referer, 'http') === false || !filter_var($referer, FILTER_VALIDATE_URL)) {
                $signals[] = 'invalid_referer';
                $scores[] = 4;
                $details['invalid_referer'] = true;
            }
        }
    }
    
    // Client-side fingerprint'leri analiz et
    if (!empty($clientFingerprints)) {
        $clientAnalysis = analyzeClientFingerprints($clientFingerprints);
        $signals = array_merge($signals, $clientAnalysis['signals']);
        $scores = array_merge($scores, $clientAnalysis['scores']);
        $details = array_merge($details, $clientAnalysis['details']);
    }
    
    $totalScore = array_sum($scores);
    $maxPossibleScore = 150; // Artırıldı (yeni sinyaller eklendi)
    $confidence = min(100, ($totalScore / $maxPossibleScore) * 100);
    
    // ML confidence hesapla
    $mlConfidence = calculateMLConfidence($signals);
    
    // İki skoru birleştir
    $finalConfidence = ($confidence * 0.6) + ($mlConfidence * 0.4);
    
    $botConfidence = round($finalConfidence, 2);
    
    return [
        'score' => count($signals),
        'signals' => $signals,
        'bot_confidence' => $botConfidence,
        'ml_confidence' => round($mlConfidence, 2),
        'details' => $details,
        'total_score' => $totalScore
    ];
}

function isUserAgentAllowed($ua, array $server = []) {
    return !isBot($ua, $server);
}

// Ziyaretçi bilgilerini veritabanına kaydet (site_id ve api_key_id desteği ile)
function logVisitor(array $data) {
    $pdo = DB::connect();
    
    // Yeni sütunlar varsa kullan, yoksa eski format
    $hasSiteId = false;
    try {
        $pdo->query("SELECT site_id FROM cloacker_visitors LIMIT 1");
        $hasSiteId = true;
    } catch (PDOException $e) {
        // Sütun yok, eski format kullan
    }
    $recentDuplicateWindowSeconds = 45;
    $thresholdTime = (new DateTimeImmutable("-{$recentDuplicateWindowSeconds} seconds"))->format('Y-m-d H:i:s');

    $duplicateFound = false;
    try {
        if ($hasSiteId) {
            $stmt = $pdo->prepare("
                SELECT id FROM cloacker_visitors
                WHERE ip = :ip
                  AND user_agent = :ua
                  AND redirect_target = :redirect
                  AND created_at >= :threshold
                  AND (
                        (:site_id IS NULL AND site_id IS NULL)
                        OR site_id = :site_id
                  )
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([
                ':ip' => $data['ip'],
                ':ua' => $data['user_agent'],
                ':redirect' => $data['redirect_target'],
                ':threshold' => $thresholdTime,
                ':site_id' => $data['site_id'] ?? null,
            ]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id FROM cloacker_visitors
                WHERE ip = :ip
                  AND user_agent = :ua
                  AND redirect_target = :redirect
                  AND created_at >= :threshold
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([
                ':ip' => $data['ip'],
                ':ua' => $data['user_agent'],
                ':redirect' => $data['redirect_target'],
                ':threshold' => $thresholdTime,
            ]);
        }
        $duplicateFound = (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Ziyaretçi log duplicate kontrolü başarısız: ' . $e->getMessage());
    }

    if ($duplicateFound) {
        return;
    }
    
    if ($hasSiteId) {
        // Yeni sütunların varlığını kontrol et
        $hasNewColumns = false;
        try {
            $pdo->query("SELECT ja3_hash FROM cloacker_visitors LIMIT 1");
            $hasNewColumns = true;
        } catch (PDOException $e) {
            // Yeni sütunlar yok, eski format kullan
        }
        
        if ($hasNewColumns) {
            // fingerprint_hash sütununun varlığını kontrol et
            $hasFingerprintHash = false;
            try {
                $pdo->query("SELECT fingerprint_hash FROM cloacker_visitors LIMIT 1");
                $hasFingerprintHash = true;
            } catch (PDOException $e) {
                // fingerprint_hash sütunu yok
            }
            
            // ASN, datacenter, threat_score, ip_age_days, fraud_score sütunlarının varlığını kontrol et
            $hasAdvancedColumns = false;
            try {
                $pdo->query("SELECT asn, is_datacenter FROM cloacker_visitors LIMIT 1");
                $hasAdvancedColumns = true;
            } catch (PDOException $e) {
                // Sütunlar yok
            }
            
            if ($hasFingerprintHash) {
                if ($hasAdvancedColumns) {
                    $stmt = $pdo->prepare("INSERT INTO cloacker_visitors 
                        (site_id, api_key_id, ip, user_agent, country, os, browser, referer, is_proxy, is_bot, redirect_target, is_fake_url, fingerprint_score, bot_confidence, fingerprint_hash, ja3_hash, ja3s_hash, canvas_fingerprint, webgl_fingerprint, audio_fingerprint, webrtc_leak, local_ip_detected, fonts_hash, plugins_hash, ml_confidence, dynamic_threshold, rdns_hostname, rdns_is_bot, fingerprint_similarity, behavioral_bot_score, asn, asn_name, is_datacenter, threat_score, threat_source, ip_age_days, fraud_score, created_at)
                        VALUES 
                        (:site_id, :api_key_id, :ip, :ua, :country, :os, :browser, :referer, :proxy, :bot, :redirect, :fakeurl, :fp_score, :bot_conf, :fp_hash, :ja3, :ja3s, :canvas, :webgl, :audio, :webrtc, :localip, :fonts, :plugins, :ml_conf, :dyn_thresh, :rdns_hostname, :rdns_is_bot, :fp_similarity, :behavioral_score, :asn, :asn_name, :is_datacenter, :threat_score, :threat_source, :ip_age_days, :fraud_score, NOW())");
                    $stmt->execute([
                        ':site_id' => $data['site_id'] ?? null,
                        ':api_key_id' => $data['api_key_id'] ?? null,
                        ':ip' => $data['ip'],
                        ':ua' => $data['user_agent'],
                        ':country' => strtoupper($data['country']),
                        ':os' => strtolower($data['os']),
                        ':browser' => $data['browser'],
                        ':referer' => $data['referer'],
                        ':proxy' => $data['proxy_vpn'] ? 1 : 0,
                        ':bot' => $data['bot'] ? 1 : 0,
                        ':redirect' => $data['redirect_target'],
                        ':fakeurl' => $data['is_fake_url'] ? 1 : 0,
                        ':fp_score' => $data['fingerprint_score'] ?? null,
                        ':bot_conf' => $data['bot_confidence'] ?? null,
                        ':fp_hash' => $data['fingerprint_hash'] ?? null,
                        ':ja3' => $data['ja3_hash'] ?? null,
                        ':ja3s' => $data['ja3s_hash'] ?? null,
                        ':canvas' => $data['canvas_fingerprint'] ?? null,
                        ':webgl' => $data['webgl_fingerprint'] ?? null,
                        ':audio' => $data['audio_fingerprint'] ?? null,
                        ':webrtc' => $data['webrtc_leak'] ? 1 : 0,
                        ':localip' => $data['local_ip_detected'] ?? null,
                        ':fonts' => $data['fonts_hash'] ?? null,
                        ':plugins' => $data['plugins_hash'] ?? null,
                        ':ml_conf' => $data['ml_confidence'] ?? null,
                        ':dyn_thresh' => $data['dynamic_threshold'] ?? null,
                        ':rdns_hostname' => $data['rdns_hostname'] ?? null,
                        ':rdns_is_bot' => $data['rdns_is_bot'] ? 1 : 0,
                        ':fp_similarity' => $data['fingerprint_similarity'] ?? null,
                        ':behavioral_score' => $data['behavioral_bot_score'] ?? null,
                        ':asn' => $data['asn'] ?? null,
                        ':asn_name' => $data['asn_name'] ?? null,
                        ':is_datacenter' => !empty($data['is_datacenter']) ? 1 : 0,
                        ':threat_score' => $data['threat_score'] ?? null,
                        ':threat_source' => $data['threat_source'] ?? null,
                        ':ip_age_days' => $data['ip_age_days'] ?? null,
                        ':fraud_score' => $data['fraud_score'] ?? null,
                    ]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO cloacker_visitors 
                        (site_id, api_key_id, ip, user_agent, country, os, browser, referer, is_proxy, is_bot, redirect_target, is_fake_url, fingerprint_score, bot_confidence, fingerprint_hash, ja3_hash, ja3s_hash, canvas_fingerprint, webgl_fingerprint, audio_fingerprint, webrtc_leak, local_ip_detected, fonts_hash, plugins_hash, ml_confidence, dynamic_threshold, rdns_hostname, rdns_is_bot, fingerprint_similarity, behavioral_bot_score, created_at)
                        VALUES 
                        (:site_id, :api_key_id, :ip, :ua, :country, :os, :browser, :referer, :proxy, :bot, :redirect, :fakeurl, :fp_score, :bot_conf, :fp_hash, :ja3, :ja3s, :canvas, :webgl, :audio, :webrtc, :localip, :fonts, :plugins, :ml_conf, :dyn_thresh, :rdns_hostname, :rdns_is_bot, :fp_similarity, :behavioral_score, NOW())");
                    $stmt->execute([
                        ':site_id' => $data['site_id'] ?? null,
                        ':api_key_id' => $data['api_key_id'] ?? null,
                        ':ip' => $data['ip'],
                        ':ua' => $data['user_agent'],
                        ':country' => strtoupper($data['country']),
                        ':os' => strtolower($data['os']),
                        ':browser' => $data['browser'],
                        ':referer' => $data['referer'],
                        ':proxy' => $data['proxy_vpn'] ? 1 : 0,
                        ':bot' => $data['bot'] ? 1 : 0,
                        ':redirect' => $data['redirect_target'],
                        ':fakeurl' => $data['is_fake_url'] ? 1 : 0,
                        ':fp_score' => $data['fingerprint_score'] ?? null,
                        ':bot_conf' => $data['bot_confidence'] ?? null,
                        ':fp_hash' => $data['fingerprint_hash'] ?? null,
                        ':ja3' => $data['ja3_hash'] ?? null,
                        ':ja3s' => $data['ja3s_hash'] ?? null,
                        ':canvas' => $data['canvas_fingerprint'] ?? null,
                        ':webgl' => $data['webgl_fingerprint'] ?? null,
                        ':audio' => $data['audio_fingerprint'] ?? null,
                        ':webrtc' => $data['webrtc_leak'] ? 1 : 0,
                        ':localip' => $data['local_ip_detected'] ?? null,
                        ':fonts' => $data['fonts_hash'] ?? null,
                        ':plugins' => $data['plugins_hash'] ?? null,
                        ':ml_conf' => $data['ml_confidence'] ?? null,
                        ':dyn_thresh' => $data['dynamic_threshold'] ?? null,
                        ':rdns_hostname' => $data['rdns_hostname'] ?? null,
                        ':rdns_is_bot' => $data['rdns_is_bot'] ? 1 : 0,
                        ':fp_similarity' => $data['fingerprint_similarity'] ?? null,
                        ':behavioral_score' => $data['behavioral_bot_score'] ?? null,
                    ]);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO cloacker_visitors 
                    (site_id, api_key_id, ip, user_agent, country, os, browser, referer, is_proxy, is_bot, redirect_target, is_fake_url, fingerprint_score, bot_confidence, ja3_hash, ja3s_hash, canvas_fingerprint, webgl_fingerprint, audio_fingerprint, webrtc_leak, local_ip_detected, fonts_hash, plugins_hash, ml_confidence, dynamic_threshold, created_at)
                    VALUES 
                    (:site_id, :api_key_id, :ip, :ua, :country, :os, :browser, :referer, :proxy, :bot, :redirect, :fakeurl, :fp_score, :bot_conf, :ja3, :ja3s, :canvas, :webgl, :audio, :webrtc, :localip, :fonts, :plugins, :ml_conf, :dyn_thresh, NOW())");
                $stmt->execute([
                    ':site_id' => $data['site_id'] ?? null,
                    ':api_key_id' => $data['api_key_id'] ?? null,
                    ':ip' => $data['ip'],
                    ':ua' => $data['user_agent'],
                    ':country' => strtoupper($data['country']),
                    ':os' => strtolower($data['os']),
                    ':browser' => $data['browser'],
                    ':referer' => $data['referer'],
                    ':proxy' => $data['proxy_vpn'] ? 1 : 0,
                    ':bot' => $data['bot'] ? 1 : 0,
                    ':redirect' => $data['redirect_target'],
                    ':fakeurl' => $data['is_fake_url'] ? 1 : 0,
                    ':fp_score' => $data['fingerprint_score'] ?? null,
                    ':bot_conf' => $data['bot_confidence'] ?? null,
                    ':ja3' => $data['ja3_hash'] ?? null,
                    ':ja3s' => $data['ja3s_hash'] ?? null,
                    ':canvas' => $data['canvas_fingerprint'] ?? null,
                    ':webgl' => $data['webgl_fingerprint'] ?? null,
                    ':audio' => $data['audio_fingerprint'] ?? null,
                    ':webrtc' => $data['webrtc_leak'] ? 1 : 0,
                    ':localip' => $data['local_ip_detected'] ?? null,
                    ':fonts' => $data['fonts_hash'] ?? null,
                    ':plugins' => $data['plugins_hash'] ?? null,
                    ':ml_conf' => $data['ml_confidence'] ?? null,
                    ':dyn_thresh' => $data['dynamic_threshold'] ?? null,
                ]);
            }
        } else {
            // Eski format (geriye dönük uyumluluk)
            $stmt = $pdo->prepare("INSERT INTO cloacker_visitors 
                (site_id, api_key_id, ip, user_agent, country, os, browser, referer, is_proxy, is_bot, redirect_target, is_fake_url, fingerprint_score, bot_confidence, created_at)
                VALUES 
                (:site_id, :api_key_id, :ip, :ua, :country, :os, :browser, :referer, :proxy, :bot, :redirect, :fakeurl, :fp_score, :bot_conf, NOW())");
            $stmt->execute([
                ':site_id' => $data['site_id'] ?? null,
                ':api_key_id' => $data['api_key_id'] ?? null,
                ':ip' => $data['ip'],
                ':ua' => $data['user_agent'],
                ':country' => strtoupper($data['country']),
                ':os' => strtolower($data['os']),
                ':browser' => $data['browser'],
                ':referer' => $data['referer'],
                ':proxy' => $data['proxy_vpn'] ? 1 : 0,
                ':bot' => $data['bot'] ? 1 : 0,
                ':redirect' => $data['redirect_target'],
                ':fakeurl' => $data['is_fake_url'] ? 1 : 0,
                ':fp_score' => $data['fingerprint_score'] ?? null,
                ':bot_conf' => $data['bot_confidence'] ?? null,
            ]);
        }
        
        $visitorId = $pdo->lastInsertId();
        
        // Bot tespit detaylarını kaydet
        if (!empty($data['detection_details']) && $visitorId) {
            foreach ($data['detection_details'] as $detection) {
                $detStmt = $pdo->prepare("INSERT INTO cloacker_bot_detections 
                    (visitor_id, detection_type, score, details, created_at)
                    VALUES (:visitor_id, :type, :score, :details, NOW())");
                $detStmt->execute([
                    ':visitor_id' => $visitorId,
                    ':type' => $detection['type'] ?? 'unknown',
                    ':score' => $detection['score'] ?? 0,
                    ':details' => json_encode($detection['details'] ?? []),
                ]);
            }
        }
    } else {
        // Eski format (geriye dönük uyumluluk)
        $stmt = $pdo->prepare("INSERT INTO cloacker_visitors 
            (ip, user_agent, country, os, browser, referer, is_proxy, is_bot, redirect_target, is_fake_url, created_at)
            VALUES 
            (:ip, :ua, :country, :os, :browser, :referer, :proxy, :bot, :redirect, :fakeurl, NOW())");
        $stmt->execute([
            ':ip' => $data['ip'],
            ':ua' => $data['user_agent'],
            ':country' => strtoupper($data['country']),
            ':os' => strtolower($data['os']),
            ':browser' => $data['browser'],
            ':referer' => $data['referer'],
            ':proxy' => $data['proxy_vpn'] ? 1 : 0,
            ':bot' => $data['bot'] ? 1 : 0,
            ':redirect' => $data['redirect_target'],
            ':fakeurl' => $data['is_fake_url'] ? 1 : 0,
        ]);
    }
}

// Cloacking ana işlevi - site_id ve api_key desteği ile - TAMAMEN YENİDEN YAZILDI
function cloaker_decision(bool $logVisit = true, bool $respectSession = true, ?int $siteId = null, ?string $apiKey = null, array $options = []): array {
    $options = array_merge([
        'override_ip' => null,
        'override_user_agent' => null,
        'host' => $_SERVER['HTTP_HOST'] ?? null,
        'api_key_context' => null,
        'touch_api_key_usage' => true,
        'request_source' => null,
        'fingerprint_mode' => null,
        'client_fingerprints' => [],
        'ja3_hash' => null,
        'ja3s_hash' => null,
        'skip_proxy_check' => false, // Bot testi için proxy kontrolünü atla
    ], $options);

    // IP ALMA - ÖNCE OVERRIDE KONTROL ET
    $ip = $options['override_ip'];
    if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = null;
    }
    if (!$ip) {
        $ip = getClientIP();
    }
    
    // User-Agent ALMA
    $ua = $options['override_user_agent'];
    if (!is_string($ua) || $ua === '') {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $serverContext = $_SERVER;
    if (!empty($options['override_user_agent'])) {
        $serverContext['HTTP_USER_AGENT'] = $ua;
    }
    
    // Ziyaretçi bilgilerini topla
    $country = getCountry($ip);
    $os = getOS($ua);
    $browser = getBrowser($ua);
    $fingerprintMode = $options['fingerprint_mode'] ?? (($options['request_source'] ?? null) === 'api' ? 'limited' : 'full');
    
    // Client-side challenge kontrolü
    $clientFps = $options['client_fingerprints'] ?? [];
    $challengeValid = verifyClientChallenge($clientFps['challenge'] ?? null);
    if (!$challengeValid) {
        // Challenge yoksa veya geçersizse bot olarak işaretle
    }
    
    // Fingerprint hash oluştur
    $fingerprintHash = generateFingerprintHash(
        $ip,
        $ua,
        $clientFps['canvas'] ?? null,
        $options['ja3_hash'] ?? null
    );
    
    // Rate limiting kontrolü (ayarlardan değerler alınacak)
    $rateLimited = checkRateLimit($ip, $fingerprintHash);
    if ($rateLimited) {
        // Rate limit aşıldıysa direkt fake'e yönlendir
        $siteConfig = resolveSiteConfiguration($siteId, null);
        return [
            'ip' => $ip,
            'user_agent' => $ua,
            'country' => $country,
            'os' => $os,
            'browser' => $browser,
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'proxy' => false,
            'bot' => true,
            'signals' => ['rate_limit_exceeded'],
            'fingerprint_score' => 100,
            'bot_confidence' => 100.0,
            'allowed' => false,
            'redirect_url' => $siteConfig['fake_url'] ?? 'https://google.com',
            'redirect_target' => 'fake',
            'is_fake_url' => true,
            'already_logged' => false,
            'site_id' => $siteId,
            'api_key_id' => null,
        ];
    }
    
    // Gelişmiş duplicate kontrol (24 saat)
    $isDuplicate = checkDuplicateVisitor($ip, $fingerprintHash, $siteId);
    
    // JA3 kontrolü
    $isJA3Blocked = false;
    if (!empty($options['ja3_hash'])) {
        $isJA3Blocked = checkJA3Fingerprint($options['ja3_hash'], $options['ja3s_hash'] ?? null);
        if ($isJA3Blocked) {
            // JA3 blacklist'te bulundu, skor ekle
            $pdo = DB::connect();
            $settings = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
            $ja3Score = (int)($settings['ja3_score'] ?? 20);
            $fingerprint['bot_confidence'] = ($fingerprint['bot_confidence'] ?? 0) + (float)$ja3Score;
        }
    }
    
    // Residential proxy detection (skip_proxy_check aktifse bypass et)
    $isResidentialProxy = false;
    if (!($options['skip_proxy_check'] ?? false)) {
        $isResidentialProxy = isResidentialProxy($ip);
    }
    
    // Cloudflare bot doğrulama
    $isCloudflareBot = verifyCloudflareBot($ua, $serverContext);
    
    // Dinamik eşik hesapla
    $pdo = DB::connect();
    $settings = $pdo->query("SELECT dynamic_threshold_enabled, ml_enabled FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch();
    $useDynamicThreshold = (bool)($settings['dynamic_threshold_enabled'] ?? true);
    $useML = (bool)($settings['ml_enabled'] ?? true);
    
    $botConfidenceThreshold = (float)config('security.bot_confidence_threshold', 30.0);
    if ($useDynamicThreshold) {
        $dynamicThreshold = calculateDynamicThreshold($siteId);
        $botConfidenceThreshold = $dynamicThreshold;
    }
    
    // Fingerprint analizi (client-side verilerle)
    $fingerprint = analyzeClientFingerprint($ua, $serverContext, $fingerprintMode, $options['client_fingerprints'] ?? []);
    $botScoreThreshold = max(1, (int)config('security.bot_score_threshold', 2));
    
    // rDNS kontrolü
    $rdnsResult = ['hostname' => null, 'is_bot' => false];
    try {
        if (function_exists('getCachedRDNS')) {
            $rdnsResult = getCachedRDNS($ip);
        } elseif (function_exists('checkReverseDNS')) {
            $rdnsCheck = checkReverseDNS($ip);
            $rdnsResult = [
                'hostname' => $rdnsCheck['hostname'] ?? null,
                'is_bot' => $rdnsCheck['is_bot'] ?? false
            ];
        } elseif (filter_var($ip, FILTER_VALIDATE_IP)) {
            // Basit fallback: gethostbyaddr ile rDNS çözümle
            $host = @gethostbyaddr($ip);
            if ($host && $host !== $ip) {
                $hostname = strtolower($host);
                $botIndicators = ['googlebot', 'crawl', 'spider', 'bot', 'crawler', 'msn', 'bing', 'yahoo', 'facebook', 'tiktok', 'twitter', 'linkedin'];
                $isBotHost = false;
                foreach ($botIndicators as $ind) {
                    if (strpos($hostname, $ind) !== false) {
                        $isBotHost = true;
                        break;
                    }
                }
                $rdnsResult = [
                    'hostname' => $host,
                    'is_bot' => $isBotHost,
                ];
            }
        }
    } catch (Exception $e) {
        error_log("rDNS kontrolü hatası: " . $e->getMessage());
    }
    
    // Fingerprint Similarity kontrolü
    $fingerprintSimilarity = null;
    try {
        // Ayarları kontrol et
        $pdo = DB::connect();
        $settings = $pdo->query("SELECT enable_fingerprint_similarity FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
        
        if (($settings['enable_fingerprint_similarity'] ?? 1) && !empty($fingerprintHash)) {
            $similarityResult = checkFingerprintHistory($fingerprintHash, $clientFps, $siteId, $ua);
            $fingerprintSimilarity = $similarityResult['similarity'] ?? null;
        }
    } catch (Exception $e) {
        error_log("Fingerprint similarity kontrolü hatası: " . $e->getMessage());
    }
    
    // Behavioral Analysis kontrolü
    $behavioralBotScore = null;
    try {
        if (!function_exists('analyzeBehavioralData')) {
            /**
             * Basit behavioral skor hesabı.
             * Gerçek zamanlı davranış verisi yoksa fingerprint skoru ve bot_confidence üzerinden sentetik bir skor üretir.
             */
            function analyzeBehavioralData(string $ip, string $fingerprintHash, array $clientFingerprints = []): array {
                $baseScore = 0.0;
                if (!empty($clientFingerprints)) {
                    $missingSignals = 0;
                    $keys = ['canvas', 'webgl', 'audio', 'fonts', 'plugins'];
                    foreach ($keys as $key) {
                        if (empty($clientFingerprints[$key])) {
                            $missingSignals++;
                        }
                    }
                    $baseScore += $missingSignals * 8.0; // 0-40 arası
                }
                $hashEntropy = strlen($fingerprintHash) > 0 ? 10.0 : 0.0;
                $score = min(100.0, $baseScore + $hashEntropy);
                return [
                    'bot_score' => round($score, 2),
                    'features' => [
                        'missing_signals' => $missingSignals ?? 0,
                        'hash_entropy' => $hashEntropy,
                    ],
                ];
            }
        }
        if (!empty($fingerprintHash)) {
            $behavioralResult = analyzeBehavioralData($ip, $fingerprintHash, $clientFps);
            $behavioralBotScore = $behavioralResult['bot_score'] ?? null;
        }
    } catch (Exception $e) {
        error_log("Behavioral analysis kontrolü hatası: " . $e->getMessage());
    }
    
    // ASN bilgisi al (ayarlar aktifse)
    $asnInfo = null;
    $asn = null;
    $asnName = null;
    $asnCheckEnabled = false;
    $datacenterDetected = false;
    $defaultDatacenterAsns = ['13335', '15169', '16509', '20940', '32934', '15133', '8075'];
    try {
        $pdo = DB::connect();
        $settings = $pdo->query("SELECT enable_asn_check FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
        $asnCheckEnabled = (bool)($settings['enable_asn_check'] ?? 1);
        if ($asnCheckEnabled) {
            $asnInfo = getIPInfo($ip);
            if ($asnInfo) {
                $asnRaw = $asnInfo['asn'] ?? null;
                $asnName = $asnInfo['asn_name'] ?? null;
                if ($asnRaw) {
                    $normalizedAsn = trim(preg_replace('/^AS/i', '', (string)$asnRaw));
                    $parts = preg_split('/\s+/', $normalizedAsn, 2);
                    $asnDigits = preg_replace('/\D/', '', $parts[0] ?? '');
                    if ($asnDigits !== '') {
                        $asn = substr($asnDigits, 0, 20);
                    }
                    if (!$asnName && isset($parts[1])) {
                        $asnName = trim($parts[1]);
                    }
                }
            }

            $configuredAsns = config('security.datacenter_asns', $defaultDatacenterAsns);
            if (!is_array($configuredAsns)) {
                $configuredAsns = array_filter(array_map('trim', explode(',', (string)$configuredAsns)));
            }
            if (empty($configuredAsns)) {
                $configuredAsns = $defaultDatacenterAsns;
            }
            $configuredAsns = array_values(array_filter(array_map(static function ($value) {
                $digits = preg_replace('/\D/', '', (string)$value);
                return $digits !== '' ? $digits : null;
            }, $configuredAsns)));

            if ($asn && in_array($asn, $configuredAsns, true)) {
                $datacenterDetected = true;
            }
        }
    } catch (Exception $e) {
        error_log("ASN bilgisi alınamadı: " . $e->getMessage());
    }
    
    // Real-time Threat Intelligence kontrolü (ayarlar aktifse)
    $threatData = null;
    $threatScore = null;
    $threatSource = null;
    try {
        $pdo = DB::connect();
        $settings = $pdo->query("SELECT enable_threat_intelligence FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
        if (($settings['enable_threat_intelligence'] ?? 1)) {
            $threatData = checkThreatIntelligence($ip);
            if ($threatData) {
                $threatScore = (float)$threatData['threat_score'];
                $threatSource = 'AbuseIPDB';
            }
        }
    } catch (Exception $e) {
        error_log("Threat intelligence kontrolü hatası: " . $e->getMessage());
    }
    if ($asnCheckEnabled && !$datacenterDetected && ($threatData['is_hosting'] ?? false)) {
        $datacenterDetected = true;
    }
    
    // IP Yaşı & Fraud Skoru kontrolü (ayarlar aktifse)
    $ipAgeData = null;
    $ipAgeDays = null;
    $fraudScore = null;
    try {
        $pdo = DB::connect();
        $settings = $pdo->query("SELECT enable_ip_age_check FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
        if (($settings['enable_ip_age_check'] ?? 1)) {
            $ipAgeData = getIPAgeAndFraudScore($ip);
            if ($ipAgeData) {
                $ipAgeDays = $ipAgeData['ip_age_days'] ?? null;
                $fraudScore = $ipAgeData['fraud_score'] ?? null;
            }
        }
    } catch (Exception $e) {
        error_log("IP age kontrolü hatası: " . $e->getMessage());
    }
    if ($asnCheckEnabled && !$datacenterDetected && ($ipAgeData['is_datacenter'] ?? false)) {
        $datacenterDetected = true;
    }

    $apiKeyId = null;
    $pdo = null;

    if (!empty($options['api_key_context']) && is_array($options['api_key_context'])) {
        $context = $options['api_key_context'];
        if (!empty($context['id'])) {
            $apiKeyId = (int)$context['id'];
        }
        if (!$siteId && !empty($context['site_id'])) {
            $siteId = (int)$context['site_id'];
        }
        if ($apiKeyId && !empty($options['touch_api_key_usage'])) {
            try {
                $pdo = DB::connect();
                $stmtUpdate = $pdo->prepare("UPDATE cloacker_api_keys SET last_used = NOW() WHERE id = :id");
                $stmtUpdate->execute([':id' => $apiKeyId]);
            } catch (Exception $e) {
                error_log("API key son kullanım güncellenemedi: " . $e->getMessage());
            }
        }
    } elseif ($apiKey) {
        try {
            $pdo = DB::connect();
            $stmt = $pdo->prepare("SELECT id, site_id FROM cloacker_api_keys WHERE api_key = :key AND is_active = 1");
            $stmt->execute([':key' => $apiKey]);
            $keyData = $stmt->fetch();
            if ($keyData) {
                $apiKeyId = (int)$keyData['id'];
                if (!$siteId && !empty($keyData['site_id'])) {
                    $siteId = (int)$keyData['site_id'];
                }
                $updateStmt = $pdo->prepare("UPDATE cloacker_api_keys SET last_used = NOW() WHERE id = :id");
                $updateStmt->execute([':id' => $apiKeyId]);
            }
        } catch (Exception $e) {
            error_log("API key doğrulanamadı: " . $e->getMessage());
        }
    }

    $refererHost = null;
    if (!empty($referer)) {
        $refererHost = normalizeDomain(parse_url($referer, PHP_URL_HOST) ?: null);
    }

    $initialHost = $options['host'] ?? ($_SERVER['HTTP_HOST'] ?? null);
    $normalizedInitial = normalizeDomain($initialHost);
    $currentHost = normalizeDomain($_SERVER['HTTP_HOST'] ?? null);

    $hostForResolution = $normalizedInitial;
    if ($refererHost && (!$hostForResolution || $hostForResolution === $currentHost)) {
        $hostForResolution = $refererHost;
    }

    $siteConfig = resolveSiteConfiguration($siteId, $hostForResolution);
    $siteSource = $siteConfig['source'] ?? 'global';
    $resolvedSiteId = $siteConfig['site_id'] ?? null;
    $explicitSiteRequest = ($siteId !== null) || (!empty($options['api_key_context']['site_id']));

    if ($explicitSiteRequest && $siteSource !== 'site') {
        throw new RuntimeException('Belirtilen site pasif veya bulunamadı.');
    }

    $siteId = $resolvedSiteId;
    $siteSettings = is_array($siteConfig['settings']) ? $siteConfig['settings'] : [];
    $redirectUrls = [
        'normal' => $siteConfig['normal_url'],
        'fake' => $siteConfig['fake_url'],
    ];

    if (isset($siteSettings['bot_confidence_threshold']) && $siteSettings['bot_confidence_threshold'] !== '') {
        $botConfidenceThreshold = (float)$siteSettings['bot_confidence_threshold'];
    }

    // BOT TESPİTİ - YENİDEN YAZILDI, DAHA AZ AGRESİF
    $isBotByUA = isBot($ua, $serverContext);
    $isBotByFingerprint = $fingerprint['score'] >= $botScoreThreshold;
    $isBotByConfidence = $fingerprint['bot_confidence'] >= $botConfidenceThreshold;
    
    // Cloudflare doğrulanmış botlar normal trafik olarak kabul edilir
    if ($isCloudflareBot && !$isBotByUA) {
        $isBotByFingerprint = false;
        $isBotByConfidence = false;
    }
    
    // Challenge kontrolü - Challenge yoksa veya geçersizse bot
    if (!$challengeValid && !$isCloudflareBot) {
        $pdo = DB::connect();
        $settings = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
        $challengeScore = (int)($settings['challenge_score'] ?? 15);
        $challengeFailAction = $settings['challenge_fail_action'] ?? 'add_score'; // 'add_score' veya 'mark_bot'
        
        if ($challengeFailAction === 'mark_bot') {
            // Direkt bot olarak işaretle (eski davranış)
            $isBotByFingerprint = true;
            $fingerprint['bot_confidence'] = max($fingerprint['bot_confidence'] ?? 0, (float)$challengeScore);
        } else {
            // Sadece skor ekle (yeni davranış - daha esnek)
            $fingerprint['bot_confidence'] = ($fingerprint['bot_confidence'] ?? 0) + (float)$challengeScore;
        }
    }
    
    // Unknown tarayıcı/OS kontrolü - Sadece Unknown ise ve başka bot sinyali yoksa bot değil
    $hasUnknownBrowser = (strtolower($browser) === 'unknown');
    $hasUnknownOS = (strtolower($os) === 'unknown');
    
    // Challenge fail action kontrolü
    $pdo = DB::connect();
    $challengeSettings = $pdo->query("SELECT challenge_fail_action FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch() ?: [];
    $challengeFailAction = $challengeSettings['challenge_fail_action'] ?? 'add_score';
    
    // Challenge başarısız olduğunda direkt bot olarak işaretleme kontrolü
    $isBotByChallenge = false;
    if (!$challengeValid && !$isCloudflareBot && $challengeFailAction === 'mark_bot') {
        $isBotByChallenge = true;
    }
    
    // Eğer sadece Unknown tarayıcı/OS varsa ve başka bot sinyali yoksa, bot değil
    if (($hasUnknownBrowser || $hasUnknownOS) && !$isBotByUA && $fingerprint['score'] == 0 && ($fingerprint['bot_confidence'] ?? 0) < 30 && !$isJA3Blocked && $challengeValid) {
        $bot = false;
    } else {
        // Tüm bot sinyallerini birleştir
        // Challenge fail action 'mark_bot' ise direkt bot olarak işaretle, değilse sadece skor ekle (yukarıda yapıldı)
        $bot = $isBotByUA || $isBotByFingerprint || $isBotByConfidence || $isJA3Blocked || $isBotByChallenge;
    }
    
    // Proxy kontrolü - IPHub + Residential proxy (skip_proxy_check aktifse bypass et)
    $proxy = false;
    if (!($options['skip_proxy_check'] ?? false)) {
        $proxy = isVpnProxy($ip) || $isResidentialProxy;
    }
    if ($asnCheckEnabled && $datacenterDetected) {
        $proxy = true;
    }

    // KRİTER KONTROLLERİ
    $allowedCountry = true;
    $allowedOS = true;
    $allowedBrowser = true;

    if (!empty($siteSettings['allowed_countries'])) {
        $allowedCountries = array_filter(array_map('trim', explode(',', strtoupper((string)$siteSettings['allowed_countries']))));
        if ($allowedCountries) {
            $allowedCountry = in_array(strtoupper($country), $allowedCountries, true);
        }
    } else {
        $allowedCountry = isCountryAllowed($country);
    }

    if (!empty($siteSettings['allowed_os'])) {
        $allowedOSList = array_filter(array_map('trim', explode(',', strtolower((string)$siteSettings['allowed_os']))));
        if ($allowedOSList) {
            $allowedOS = in_array(strtolower($os), $allowedOSList, true);
        }
    } else {
        $allowedOS = isOSAllowed($os);
    }

    if (!empty($siteSettings['allowed_browsers'])) {
        $allowedBrowserList = array_filter(array_map('trim', explode(',', strtolower((string)$siteSettings['allowed_browsers']))));
        if ($allowedBrowserList && count($allowedBrowserList) > 0) {
            $browserLower = strtolower($browser);
            $allowedBrowser = false;
            foreach ($allowedBrowserList as $allowedBrowserName) {
                if ($allowedBrowserName !== '' && strpos($browserLower, $allowedBrowserName) !== false) {
                    $allowedBrowser = true;
                    break;
                }
            }
            // Eğer tarayıcı "unknown" ise ve listede "unknown" yoksa, varsayılan olarak izin ver
            if (!$allowedBrowser && ($browserLower === 'unknown' || $browser === 'Unknown')) {
                $hasUnknownInList = false;
                foreach ($allowedBrowserList as $allowedBrowserName) {
                    if (strpos($allowedBrowserName, 'unknown') !== false) {
                        $hasUnknownInList = true;
                        break;
                    }
                }
                if (!$hasUnknownInList) {
                    $allowedBrowser = true;
                }
            }
        }
    }

    $allowedUA = isUserAgentAllowed($ua, $serverContext);
    
    // YÖNLENDİRME KARARI
    $allowedVisitor = (!$bot && !$proxy && $allowedCountry && $allowedOS && $allowedBrowser && $allowedUA);

    // Redirect target'i önce belirle (log kontrolü için gerekli)
    if ($allowedVisitor) {
        $redirect = $redirectUrls['normal'];
        $redirectTarget = 'normal';
        $isFakeUrl = false;
    } else {
        $redirect = $redirectUrls['fake'];
        $redirectTarget = 'fake';
        $isFakeUrl = true;
    }

    // Normal trafik için duplicate ve session kontrolü kaldırıldı - her ziyaret kaydedilmeli
    // Fake trafik için de her ziyaret kaydedilmeli (bot tespiti için önemli)
    // Session kontrolü sadece aynı sayfa yenilemesinde (5 saniye içinde) tekrar kayıt yapmamak için
    $shouldLog = $logVisit;
    $alreadyLogged = false;
    
    // Çok kısa süre içinde (5 saniye) aynı sayfa yenilemesi varsa tekrar kaydetme
    // Bu sadece sayfa yenilemesi için geçerli, yeni ziyaretler için değil
    if ($respectSession && isset($_SESSION['visitor_logged']) && isset($_SESSION['visitor_logged_time'])) {
        $timeSinceLastLog = time() - (int)$_SESSION['visitor_logged_time'];
        // 5 saniye içinde aynı sayfa yenilemesi varsa tekrar kaydetme
        if ($timeSinceLastLog < 5 && $redirectTarget === 'normal') {
            $shouldLog = false;
        }
    }
    
    if ($shouldLog) {
        // Log zehirleme koruması - Detayları gizle
        $sanitizedSignals = ['hidden']; // Sinyalleri gizle
        $detectionDetails = []; // Detayları loglamayalım
        
        // Client fingerprint verilerini kaydet
        $clientFps = $options['client_fingerprints'] ?? [];
        logVisitor([
            'site_id' => $siteId,
            'api_key_id' => $apiKeyId,
            'ip' => $ip,
            'user_agent' => $ua,
            'country' => $country,
            'os' => $os,
            'browser' => $browser,
            'referer' => $referer,
            'proxy_vpn' => $proxy,
            'bot' => $bot,
            'redirect_target' => $redirectTarget,
            'is_fake_url' => $isFakeUrl,
            'fingerprint_score' => $fingerprint['score'],
            'bot_confidence' => $fingerprint['bot_confidence'] ?? null,
            'detection_details' => $detectionDetails,
            'fingerprint_hash' => $fingerprintHash,
            'ja3_hash' => $options['ja3_hash'] ?? null,
            'ja3s_hash' => $options['ja3s_hash'] ?? null,
            'canvas_fingerprint' => $clientFps['canvas'] ?? null,
            'webgl_fingerprint' => $clientFps['webgl'] ?? null,
            'audio_fingerprint' => $clientFps['audio'] ?? null,
            'webrtc_leak' => !empty($clientFps['webrtc']['leak']),
            'local_ip_detected' => !empty($clientFps['webrtc']['localIPs']) ? implode(',', $clientFps['webrtc']['localIPs']) : null,
            'fonts_hash' => $clientFps['fonts'] ?? null,
            'plugins_hash' => $clientFps['plugins'] ?? null,
            'ml_confidence' => $fingerprint['ml_confidence'] ?? null,
            'dynamic_threshold' => $useDynamicThreshold ? $botConfidenceThreshold : null,
            'rdns_hostname' => $rdnsResult['hostname'] ?? null,
            'rdns_is_bot' => $rdnsResult['is_bot'] ?? false,
            'fingerprint_similarity' => $fingerprintSimilarity,
            'behavioral_bot_score' => $behavioralBotScore,
            'asn' => $asn,
            'asn_name' => $asnName,
            'threat_score' => $threatScore,
            'threat_source' => $threatSource,
            'ip_age_days' => $ipAgeDays,
            'fraud_score' => $fraudScore,
            'is_datacenter' => $datacenterDetected,
        ]);
        if ($respectSession) {
            $_SESSION['visitor_logged'] = true;
            $_SESSION['visitor_logged_time'] = time();
        }
    } else {
        $alreadyLogged = true;
    }

    // Log zehirleme koruması - Return değerlerinde hassas bilgileri gizle
    $returnSignals = ['hidden']; // Sinyalleri gizle
    
    return [
        'ip' => $ip,
        'user_agent' => $ua,
        'country' => $country,
        'os' => $os,
        'browser' => $browser,
        'referer' => $referer,
        'proxy' => $proxy,
        'bot' => $bot,
        'signals' => $returnSignals, // Sinyalleri gizle
        'fingerprint_score' => $fingerprint['score'],
        'bot_confidence' => $fingerprint['bot_confidence'] ?? null,
        'ml_confidence' => $fingerprint['ml_confidence'] ?? null,
        'dynamic_threshold' => $useDynamicThreshold ? $botConfidenceThreshold : null,
        'allowed' => $allowedVisitor,
        'redirect_url' => $redirect,
        'redirect_target' => $redirectTarget,
        'is_fake_url' => $isFakeUrl,
        'already_logged' => $alreadyLogged,
        'is_datacenter' => $datacenterDetected,
        'site_id' => $siteId,
        'api_key_id' => $apiKeyId,
        'rdns_hostname' => $rdnsResult['hostname'] ?? null,
        'rdns_is_bot' => $rdnsResult['is_bot'] ?? false,
        'fingerprint_similarity' => $fingerprintSimilarity,
        'behavioral_bot_score' => $behavioralBotScore,
        'asn' => $asn,
        'asn_name' => $asnName,
        'threat_score' => $threatScore,
        'threat_source' => $threatSource,
        'ip_age_days' => $ipAgeDays,
        'fraud_score' => $fraudScore,
    ];
}

function cloaker_main() {
    $decision = cloaker_decision(true, true);

    if (!headers_sent()) {
        header("Location: " . $decision['redirect_url']);
        exit();
    } else {
        echo "<script>window.location.href='" . htmlspecialchars($decision['redirect_url'], ENT_QUOTES) . "'</script>";
        exit();
    }
}
