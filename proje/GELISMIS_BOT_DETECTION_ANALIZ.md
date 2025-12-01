# Gelişmiş Bot Detection Özellikleri - Detaylı Analiz

## 1. Double Meta Refresh & JS Redirect Yasağına Karşı Çözümler

### Mevcut Durum
Sistem şu anda `window.location.replace()` kullanıyor. Bu yöntem bazı bot detection sistemleri tarafından tespit edilebilir.

### Önerilen Çözümler

#### A. Server-Side Redirect (HTTP 302/301)
- **Avantaj**: En güvenli yöntem, JavaScript gerektirmez
- **Uygulama**: PHP'de `header("Location: ...")` kullanımı
- **Durum**: ✅ Zaten mevcut (`cloaker_main()` fonksiyonunda)

#### B. History API Manipulation
```javascript
// window.location.replace() yerine
history.pushState(null, '', targetUrl);
history.replaceState(null, '', targetUrl);
window.location.href = targetUrl;
```

#### C. Form Submit Yöntemi
```javascript
// Görünmez form ile POST redirect
const form = document.createElement('form');
form.method = 'POST';
form.action = targetUrl;
document.body.appendChild(form);
form.submit();
```

#### D. Iframe + PostMessage
```javascript
// Ana sayfada iframe oluştur, iframe içinde redirect yap
const iframe = document.createElement('iframe');
iframe.style.display = 'none';
iframe.src = targetUrl;
document.body.appendChild(iframe);
```

#### E. Fetch API + Response Handling
```javascript
// Fetch ile sayfa içeriğini al, DOM'a inject et
fetch(targetUrl)
  .then(r => r.text())
  .then(html => {
    document.open();
    document.write(html);
    document.close();
  });
```

#### F. Meta Refresh Alternatifi (Gizli)
```html
<!-- Normal meta refresh yerine -->
<meta http-equiv="refresh" content="0;url=<?= $targetUrl ?>" id="redirect-meta">
<script>
  // Meta tag'i dinamik oluştur, bot detection'dan kaçın
  setTimeout(() => {
    const meta = document.getElementById('redirect-meta');
    if (meta) meta.content = '0;url=' + targetUrl;
  }, Math.random() * 100);
</script>
```

### Önerilen Yaklaşım
1. **Server-side redirect** (zaten mevcut) - Ana yöntem
2. **History API + window.location.href** - JavaScript fallback
3. **Form submit** - Son çare yöntemi

---

## 2. Behavioral Analysis (Davranış Analizi)

### A. Mouse Hareketleri Analizi

#### Toplanacak Veriler:
```javascript
const mouseData = {
  movements: [], // {x, y, timestamp, speed}
  clicks: [], // {x, y, timestamp, button}
  hoverTime: {}, // Element bazlı hover süreleri
  movementPattern: {
    linearity: 0, // 0-1 arası, 1 = tam düz çizgi (bot)
    acceleration: [], // Hızlanma/duraklama pattern'i
    curvature: 0 // Eğrilik skoru
  }
};

// Mouse hareket takibi
document.addEventListener('mousemove', (e) => {
  const now = Date.now();
  const lastMove = mouseData.movements[mouseData.movements.length - 1];
  
  if (lastMove) {
    const distance = Math.sqrt(
      Math.pow(e.clientX - lastMove.x, 2) + 
      Math.pow(e.clientY - lastMove.y, 2)
    );
    const timeDelta = now - lastMove.timestamp;
    const speed = distance / timeDelta;
    
    mouseData.movements.push({
      x: e.clientX,
      y: e.clientY,
      timestamp: now,
      speed: speed
    });
  } else {
    mouseData.movements.push({
      x: e.clientX,
      y: e.clientY,
      timestamp: now,
      speed: 0
    });
  }
  
  // Linearity hesaplama (son 10 hareket)
  if (mouseData.movements.length > 10) {
    mouseData.movementPattern.linearity = calculateLinearity(
      mouseData.movements.slice(-10)
    );
  }
});
```

#### Bot Tespiti İçin İndikatörler:
- **Yüksek Linearity (>0.95)**: Botlar genelde düz çizgi hareket yapar
- **Sabit Hız**: İnsanlar hızlanıp yavaşlar, botlar sabit hızda hareket eder
- **Kenar Hareketleri**: İnsanlar mouse'u ekran kenarlarına götürür, botlar genelde yapmaz
- **Hover Süresi**: İnsanlar elementlerin üzerinde duraklar, botlar geçer

### B. Scroll Pattern Analizi

```javascript
const scrollData = {
  events: [], // {direction, speed, timestamp, position}
  pattern: {
    smoothness: 0, // Yumuşaklık skoru
    pauses: 0, // Duraklama sayısı
    acceleration: [] // Hızlanma pattern'i
  }
};

let lastScrollTime = Date.now();
let lastScrollPos = window.scrollY;

window.addEventListener('scroll', () => {
  const now = Date.now();
  const currentPos = window.scrollY;
  const delta = currentPos - lastScrollPos;
  const timeDelta = now - lastScrollTime;
  const speed = Math.abs(delta / timeDelta);
  
  scrollData.events.push({
    direction: delta > 0 ? 'down' : 'up',
    speed: speed,
    timestamp: now,
    position: currentPos
  });
  
  // Bot tespiti: Çok düzenli scroll = bot
  if (scrollData.events.length > 20) {
    const speeds = scrollData.events.slice(-20).map(e => e.speed);
    const variance = calculateVariance(speeds);
    // Düşük varyans = bot (sabit hız)
    if (variance < 0.1) {
      flagAsBot('uniform_scroll_speed');
    }
  }
  
  lastScrollTime = now;
  lastScrollPos = currentPos;
});
```

### C. Klavye Davranışı (Typing Dynamics)

```javascript
const typingData = {
  keystrokes: [], // {key, timestamp, timeSinceLastKey}
  patterns: {
    rhythm: [], // Tuş vuruş aralıkları
    pressure: [], // Basma süreleri (touch events)
    errors: 0 // Geri tuşu (backspace) kullanımı
  }
};

document.addEventListener('keydown', (e) => {
  const now = Date.now();
  const lastKey = typingData.keystrokes[typingData.keystrokes.length - 1];
  
  typingData.keystrokes.push({
    key: e.key,
    timestamp: now,
    timeSinceLastKey: lastKey ? now - lastKey.timestamp : 0
  });
  
  // Bot tespiti: Çok düzenli tuş vuruşları = bot
  if (typingData.keystrokes.length > 10) {
    const intervals = typingData.keystrokes
      .slice(-10)
      .map(k => k.timeSinceLastKey)
      .filter(i => i > 0);
    
    if (intervals.length > 5) {
      const variance = calculateVariance(intervals);
      // Çok düşük varyans = bot (mükemmel timing)
      if (variance < 10) {
        flagAsBot('uniform_typing_rhythm');
      }
    }
  }
});
```

### D. Tıklama Hızı ve Pattern

```javascript
const clickData = {
  clicks: [], // {x, y, timestamp, target}
  doubleClicks: 0,
  clickSpeed: [] // Tıklamalar arası süre
};

document.addEventListener('click', (e) => {
  const now = Date.now();
  const lastClick = clickData.clicks[clickData.clicks.length - 1];
  
  if (lastClick) {
    const timeDelta = now - lastClick.timestamp;
    clickData.clickSpeed.push(timeDelta);
    
    // Çok hızlı tıklamalar = bot
    if (timeDelta < 50) {
      flagAsBot('impossibly_fast_clicks');
    }
    
    // Çok düzenli tıklamalar = bot
    if (clickData.clickSpeed.length > 5) {
      const variance = calculateVariance(clickData.clickSpeed.slice(-5));
      if (variance < 20) {
        flagAsBot('uniform_click_timing');
      }
    }
  }
  
  clickData.clicks.push({
    x: e.clientX,
    y: e.clientY,
    timestamp: now,
    target: e.target.tagName
  });
});
```

### ML Modeli İçin Özellik Vektörü

```javascript
const behavioralFeatures = {
  // Mouse
  mouse_linearity: calculateLinearity(mouseData.movements),
  mouse_speed_variance: calculateVariance(mouseData.movements.map(m => m.speed)),
  mouse_edge_interactions: countEdgeInteractions(mouseData.movements),
  mouse_hover_diversity: calculateHoverDiversity(mouseData.hoverTime),
  
  // Scroll
  scroll_smoothness: calculateSmoothness(scrollData.events),
  scroll_pause_count: countPauses(scrollData.events),
  scroll_direction_changes: countDirectionChanges(scrollData.events),
  
  // Typing
  typing_rhythm_variance: calculateVariance(typingData.patterns.rhythm),
  typing_error_rate: typingData.patterns.errors / typingData.keystrokes.length,
  
  // Click
  click_speed_variance: calculateVariance(clickData.clickSpeed),
  click_position_diversity: calculatePositionDiversity(clickData.clicks),
  
  // Genel
  interaction_duration: Date.now() - pageLoadTime,
  total_interactions: mouseData.movements.length + scrollData.events.length + 
                      typingData.keystrokes.length + clickData.clicks.length
};
```

---

## 3. Fingerprint Benzerlik Skoru (Cosine Similarity)

### Konsept
Gerçek kullanıcı ile bot/moderator arasındaki fingerprint benzerlik skoru hesaplanmalı.

### Uygulama

```php
function calculateFingerprintSimilarity($currentFp, $historicalFps) {
    // Mevcut fingerprint'i vektöre çevir
    $currentVector = fingerprintToVector($currentFp);
    
    // Geçmiş fingerprint'lerle karşılaştır
    $similarities = [];
    foreach ($historicalFps as $historicalFp) {
        $historicalVector = fingerprintToVector($historicalFp);
        $similarity = cosineSimilarity($currentVector, $historicalVector);
        $similarities[] = $similarity;
    }
    
    // En yüksek benzerlik skorunu döndür
    return max($similarities);
}

function fingerprintToVector($fp) {
    // Fingerprint'i sayısal vektöre çevir
    return [
        'canvas' => hashSimilarity($fp['canvas'] ?? ''),
        'webgl' => hashSimilarity($fp['webgl'] ?? ''),
        'audio' => hashSimilarity($fp['audio'] ?? ''),
        'fonts' => hashSimilarity($fp['fonts'] ?? ''),
        'plugins' => hashSimilarity($fp['plugins'] ?? ''),
        'screen_width' => (int)($fp['screen_width'] ?? 0),
        'screen_height' => (int)($fp['screen_height'] ?? 0),
        'timezone' => (int)($fp['timezone'] ?? 0),
        'language' => languageToNumber($fp['language'] ?? ''),
    ];
}

function cosineSimilarity($vecA, $vecB) {
    $dotProduct = 0;
    $normA = 0;
    $normB = 0;
    
    foreach ($vecA as $key => $valueA) {
        $valueB = $vecB[$key] ?? 0;
        $dotProduct += $valueA * $valueB;
        $normA += $valueA * $valueA;
        $normB += $valueB * $valueB;
    }
    
    if ($normA == 0 || $normB == 0) return 0;
    
    return $dotProduct / (sqrt($normA) * sqrt($normB));
}
```

### Karar Mantığı

```php
$similarity = calculateFingerprintSimilarity($currentFp, $historicalFps);

if ($similarity > 0.98) {
    // Çok yüksek benzerlik - Muhtemelen aynı cihaz/kullanıcı
    $decision = 'white'; // İzin ver
} elseif ($similarity < 0.85) {
    // Düşük benzerlik - Yeni cihaz veya bot
    $decision = 'review'; // İnceleme gerekli
} else {
    // Orta benzerlik - Normal kullanıcı
    $decision = 'normal';
}
```

### Veritabanı Yapısı

```sql
CREATE TABLE IF NOT EXISTS `cloacker_fingerprint_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fingerprint_hash` varchar(64) NOT NULL,
  `fingerprint_vector` text NOT NULL COMMENT 'JSON formatında vektör',
  `is_verified_human` tinyint(1) DEFAULT 0,
  `visit_count` int(11) DEFAULT 1,
  `last_seen` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fingerprint_hash` (`fingerprint_hash`),
  KEY `is_verified_human` (`is_verified_human`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. Device Fingerprint Matching (Geçmiş Kontrolü)

### Konsept
Device fingerprint'in geçmişte o platformda görülmüş gerçek kullanıcı fingerprint'lerine ne kadar benzediği kontrol edilmeli.

### Uygulama

```php
function checkFingerprintHistory($fingerprintHash, $siteId = null) {
    $pdo = DB::connect();
    
    // Site bazlı veya global geçmiş kontrolü
    $sql = "
        SELECT 
            fh.*,
            COUNT(DISTINCT v.id) as visit_count,
            MAX(v.created_at) as last_visit,
            AVG(CASE WHEN v.is_bot = 0 THEN 1 ELSE 0 END) as human_ratio
        FROM cloacker_fingerprint_history fh
        LEFT JOIN cloacker_visitors v ON v.fingerprint_hash = fh.fingerprint_hash
        WHERE fh.fingerprint_hash = :hash
    ";
    
    if ($siteId) {
        $sql .= " AND (v.site_id = :site_id OR v.site_id IS NULL)";
    }
    
    $sql .= " GROUP BY fh.id";
    
    $stmt = $pdo->prepare($sql);
    $params = [':hash' => $fingerprintHash];
    if ($siteId) {
        $params[':site_id'] = $siteId;
    }
    $stmt->execute($params);
    $history = $stmt->fetch();
    
    if (!$history) {
        return [
            'status' => 'new',
            'confidence' => 0.5, // Yeni fingerprint, belirsiz
            'recommendation' => 'review'
        ];
    }
    
    // İnsan oranına göre karar
    $humanRatio = (float)$history['human_ratio'];
    $visitCount = (int)$history['visit_count'];
    
    if ($humanRatio > 0.8 && $visitCount > 5) {
        return [
            'status' => 'verified_human',
            'confidence' => min(0.95, 0.5 + ($humanRatio * 0.4)),
            'recommendation' => 'allow'
        ];
    } elseif ($humanRatio < 0.2 && $visitCount > 3) {
        return [
            'status' => 'likely_bot',
            'confidence' => 1 - $humanRatio,
            'recommendation' => 'block'
        ];
    } else {
        return [
            'status' => 'mixed',
            'confidence' => 0.5,
            'recommendation' => 'review'
        ];
    }
}
```

### Fingerprint Spoofing Detection

```php
function detectFingerprintSpoofing($currentFp, $historicalFps) {
    $suspiciousSignals = [];
    
    // 1. Çok yüksek benzerlik ama farklı IP
    $similarity = calculateFingerprintSimilarity($currentFp, $historicalFps);
    if ($similarity > 0.99) {
        // Aynı fingerprint, farklı IP = spoofing şüphesi
        $suspiciousSignals[] = 'high_similarity_different_ip';
    }
    
    // 2. Fingerprint değişikliği pattern'i
    // Gerçek kullanıcılar fingerprint'lerini nadiren değiştirir
    // Botlar sık sık değiştirir (spoofing)
    $changeFrequency = calculateFingerprintChangeFrequency($historicalFps);
    if ($changeFrequency > 0.5) {
        $suspiciousSignals[] = 'high_fingerprint_change_rate';
    }
    
    // 3. İmkansız kombinasyonlar
    // Örn: Mobile fingerprint ama desktop screen size
    if (isset($currentFp['user_agent']) && isset($currentFp['screen_width'])) {
        $isMobileUA = preg_match('/Mobile|Android|iPhone/i', $currentFp['user_agent']);
        $isMobileScreen = ($currentFp['screen_width'] ?? 0) < 768;
        if ($isMobileUA && !$isMobileScreen) {
            $suspiciousSignals[] = 'ua_screen_mismatch';
        }
    }
    
    return [
        'is_suspicious' => count($suspiciousSignals) > 0,
        'signals' => $suspiciousSignals,
        'confidence' => min(1.0, count($suspiciousSignals) * 0.3)
    ];
}
```

---

## 5. rDNS (Reverse DNS) Kontrolü

### Konsept
IP'nin reverse DNS kaydını kontrol ederek bot IP'lerini tespit etme.

### Uygulama

```php
function checkReverseDNS($ip) {
    // IPv4 için
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $reverseIP = implode('.', array_reverse(explode('.', $ip)));
        $hostname = gethostbyaddr($ip);
        
        // Reverse DNS kaydı var mı?
        if ($hostname && $hostname !== $ip) {
            // Forward lookup kontrolü (double-check)
            $forwardIPs = gethostbynamel($hostname);
            $isValid = $forwardIPs && in_array($ip, $forwardIPs);
            
            return [
                'hostname' => $hostname,
                'is_valid' => $isValid,
                'is_bot' => isBotHostname($hostname)
            ];
        }
    }
    
    // IPv6 için
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // IPv6 reverse DNS daha karmaşık
        $reverseIP = implode('.', array_reverse(str_split(str_replace(':', '', $ip))));
        $hostname = gethostbyaddr($ip);
        
        if ($hostname && $hostname !== $ip) {
            return [
                'hostname' => $hostname,
                'is_valid' => true,
                'is_bot' => isBotHostname($hostname)
            ];
        }
    }
    
    return [
        'hostname' => null,
        'is_valid' => false,
        'is_bot' => false
    ];
}

function isBotHostname($hostname) {
    $botPatterns = [
        '/googlebot\.com$/i',
        '/google\.com$/i',
        '/crawl\.yahoo\./i',
        '/search\.msn\.com$/i',
        '/baidu\.com$/i',
        '/yandex\.com$/i',
        '/facebookexternalhit/i',
        '/twitterbot/i',
        '/linkedinbot/i',
        '/slurp/i', // Yahoo
        '/duckduckbot/i',
        '/bingbot/i',
        '/ahrefs/i',
        '/semrush/i',
        '/mj12bot/i',
        '/dotbot/i',
    ];
    
    foreach ($botPatterns as $pattern) {
        if (preg_match($pattern, $hostname)) {
            return true;
        }
    }
    
    return false;
}

function verifyGooglebot($ip, $userAgent) {
    // Googlebot için özel kontrol
    if (!preg_match('/Googlebot/i', $userAgent)) {
        return false;
    }
    
    $rdns = checkReverseDNS($ip);
    
    // Googlebot'un IP'si googlebot.com veya google.com ile bitmeli
    if ($rdns['hostname'] && 
        (preg_match('/\.googlebot\.com$/i', $rdns['hostname']) ||
         preg_match('/\.google\.com$/i', $rdns['hostname']))) {
        
        // Forward lookup ile doğrula
        $forwardIPs = gethostbynamel($rdns['hostname']);
        if ($forwardIPs && in_array($ip, $forwardIPs)) {
            return true;
        }
    }
    
    return false;
}
```

### Veritabanı Yapısı

```sql
CREATE TABLE IF NOT EXISTS `cloacker_rdns_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `is_bot` tinyint(1) DEFAULT 0,
  `is_valid` tinyint(1) DEFAULT 0,
  `last_checked` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`),
  KEY `is_bot` (`is_bot`),
  KEY `last_checked` (`last_checked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Cache Mekanizması

```php
function getCachedRDNS($ip) {
    $pdo = DB::connect();
    
    // 24 saat içinde kontrol edilmişse cache'den döndür
    $stmt = $pdo->prepare("
        SELECT * FROM cloacker_rdns_cache 
        WHERE ip = :ip 
        AND last_checked > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        LIMIT 1
    ");
    $stmt->execute([':ip' => $ip]);
    $cached = $stmt->fetch();
    
    if ($cached) {
        return [
            'hostname' => $cached['hostname'],
            'is_bot' => (bool)$cached['is_bot'],
            'is_valid' => (bool)$cached['is_valid'],
            'cached' => true
        ];
    }
    
    // Cache'de yoksa kontrol et
    $rdns = checkReverseDNS($ip);
    
    // Cache'e kaydet
    $stmt = $pdo->prepare("
        INSERT INTO cloacker_rdns_cache (ip, hostname, is_bot, is_valid, last_checked)
        VALUES (:ip, :hostname, :is_bot, :is_valid, NOW())
        ON DUPLICATE KEY UPDATE
            hostname = VALUES(hostname),
            is_bot = VALUES(is_bot),
            is_valid = VALUES(is_valid),
            last_checked = NOW()
    ");
    $stmt->execute([
        ':ip' => $ip,
        ':hostname' => $rdns['hostname'],
        ':is_bot' => $rdns['is_bot'] ? 1 : 0,
        ':is_valid' => $rdns['is_valid'] ? 1 : 0
    ]);
    
    return $rdns;
}
```

---

## Özet ve Öneriler

### Öncelik Sırası:

1. **rDNS Kontrolü** ⭐⭐⭐
   - En kolay uygulanabilir
   - Hızlı sonuç verir
   - Özellikle Googlebot gibi bilinen botlar için etkili

2. **Fingerprint Similarity** ⭐⭐⭐
   - Orta zorlukta
   - Yüksek doğruluk oranı
   - Veritabanı yükü artar

3. **Behavioral Analysis** ⭐⭐
   - En karmaşık
   - En yüksek doğruluk potansiyeli
   - Client-side kod gerektirir
   - ML modeli eğitimi gerekir

4. **Redirect Yöntemleri** ⭐
   - Mevcut sistem yeterli
   - Ek yöntemler eklenebilir (opsiyonel)

### Uygulama Planı:

1. **Faz 1**: rDNS kontrolü ekle
2. **Faz 2**: Fingerprint similarity sistemi
3. **Faz 3**: Behavioral analysis (temel)
4. **Faz 4**: ML modeli entegrasyonu

