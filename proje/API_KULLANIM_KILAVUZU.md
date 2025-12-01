# 🔑 Cloaker API Kullanım Kılavuzu

## 📋 İçindekiler
1. [Genel Bakış](#genel-bakış)
2. [API Key Alma](#api-key-alma)
3. [Endpoint Bilgileri](#endpoint-bilgileri)
4. [Entegrasyon Yöntemleri](#entegrasyon-yöntemleri)
5. [Response Formatı](#response-formatı)
6. [Hata Yönetimi](#hata-yönetimi)
7. [Örnekler](#örnekler)

---

## 🎯 Genel Bakış

Cloaker API, sitenize gelen ziyaretçileri analiz eder ve bot/VPN/proxy tespiti yapar. API'ye istek gönderdiğinizde, ziyaretçinin durumuna göre yönlendirme URL'si döner.

### Özellikler:
- ✅ Bot tespiti (AI destekli)
- ✅ VPN/Proxy tespiti
- ✅ Fingerprint analizi
- ✅ Ülke bazlı filtreleme
- ✅ OS bazlı filtreleme
- ✅ Çoklu site desteği

---

## 🔐 API Key Alma

1. **Admin paneline giriş yapın**
   - URL: `https://yourdomain.com/admin/`

2. **API Anahtarları sayfasına gidin**
   - Menüden: `🔑 API Anahtarları`

3. **Yeni API Key oluşturun**
   - Site seçin
   - İsim verin (opsiyonel)
   - "API Anahtarı Oluştur" butonuna tıklayın

4. **API Key'i kopyalayın**
   - ⚠️ **ÖNEMLİ**: API key sadece bir kez gösterilir, güvenli bir yerde saklayın!

---

## 🌐 Endpoint Bilgileri

### Base URL
```
https://yourdomain.com/api/cloaker_api.php
```

### HTTP Method
- **POST** (Önerilen - güvenli)
- **GET** (Basit kullanım için)

### Authentication
API key'i 3 farklı şekilde gönderebilirsiniz:

1. **Header (Önerilen)**
   ```
   X-API-Key: YOUR_API_KEY
   ```

2. **Query Parameter (GET)**
   ```
   ?api_key=YOUR_API_KEY
   ```

3. **POST Body**
   ```json
   {
     "api_key": "YOUR_API_KEY"
   }
   ```

### Opsiyonel Parametreler

- **site_id**: Belirli bir site için ayarları kullanmak istiyorsanız
  ```
  ?site_id=1
  ```

### ⚠️ ÖNEMLİ: Ziyaretçi Bilgilerini Gönderme

API'ye **mutlaka** ziyaretçi IP'si ve User-Agent göndermelisiniz! Aksi halde sistem sunucunun IP'sini kullanır ve yanlış yönlendirme yapar.

**Header ile gönderme (Önerilen):**
```
X-Visitor-IP: 123.45.67.89
X-Visitor-UA: Mozilla/5.0...
```

**JSON Body ile gönderme:**
```json
{
  "visitor_ip": "123.45.67.89",
  "visitor_ua": "Mozilla/5.0..."
}
```

---

## 💻 Entegrasyon Yöntemleri

### 1. JavaScript (Vanilla JS) - Önerilen

#### Basit Kullanım (Otomatik Yönlendirme)

```html
<!DOCTYPE html>
<html>
<head>
    <title>My Website</title>
</head>
<body>
    <h1>Sayfa yükleniyor...</h1>
    
    <script>
        const API_KEY = 'YOUR_API_KEY_HERE';
        const API_URL = 'https://yourdomain.com/api/cloaker_api.php';
        
        fetch(API_URL, {
            method: 'POST',
            headers: {
                'X-API-Key': API_KEY,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                if (data.allowed) {
                    // Normal ziyaretçi - normal sayfaya yönlendir
                    window.location.href = data.redirect_url;
                } else {
                    // Bot/VPN tespit edildi - fake sayfaya yönlendir
                    window.location.href = data.redirect_url;
                }
            } else {
                console.error('API Hatası:', data.message);
                // Hata durumunda ne yapılacağını belirleyin
            }
        })
        .catch(error => {
            console.error('Network Hatası:', error);
        });
    </script>
</body>
</html>
```

#### Gelişmiş Kullanım (Manuel Kontrol)

```javascript
async function checkVisitor() {
    const API_KEY = 'YOUR_API_KEY_HERE';
    const API_URL = 'https://yourdomain.com/api/cloaker_api.php';
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'X-API-Key': API_KEY,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.status === 'ok') {
            // Ziyaretçi bilgilerini göster
            console.log('IP:', data.visitor.ip);
            console.log('Ülke:', data.visitor.country);
            console.log('OS:', data.visitor.os);
            console.log('Tarayıcı:', data.visitor.browser);
            
            // Bot tespit bilgileri
            console.log('Bot mu?', data.detection.is_bot);
            console.log('Proxy mu?', data.detection.is_proxy);
            console.log('Bot Güven Skoru:', data.detection.bot_confidence);
            console.log('Fingerprint Skoru:', data.detection.fingerprint_score);
            console.log('Tespit Sinyalleri:', data.detection.signals);
            
            // İzinli mi kontrol et
            if (data.allowed) {
                // Normal ziyaretçi - içeriği göster
                showNormalContent();
            } else {
                // Bot/VPN tespit edildi - fake içerik göster veya yönlendir
                showFakeContent();
                // veya
                // window.location.href = data.redirect_url;
            }
        } else {
            console.error('API Hatası:', data.message);
        }
    } catch (error) {
        console.error('Network Hatası:', error);
    }
}

function showNormalContent() {
    document.getElementById('content').innerHTML = '<h1>Hoş Geldiniz!</h1>';
}

function showFakeContent() {
    document.getElementById('content').innerHTML = '<h1>Erişim Reddedildi</h1>';
}

// Sayfa yüklendiğinde kontrol et
checkVisitor();
```

---

### 2. jQuery ile Kullanım

```html
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const API_KEY = 'YOUR_API_KEY_HERE';
    const API_URL = 'https://yourdomain.com/api/cloaker_api.php';
    
    $.ajax({
        url: API_URL,
        method: 'POST',
        headers: {
            'X-API-Key': API_KEY
        },
        dataType: 'json',
        success: function(data) {
            if (data.status === 'ok' && data.allowed) {
                // Normal ziyaretçi
                window.location.href = data.redirect_url;
            } else if (data.status === 'ok') {
                // Bot tespit edildi
                window.location.href = data.redirect_url;
            }
        },
        error: function(xhr, status, error) {
            console.error('API Hatası:', error);
        }
    });
});
</script>
```

---

### 3. PHP ile Kullanım (Server-Side)

```php
<?php
// API Key ve URL
$apiKey = 'YOUR_API_KEY_HERE';
$apiUrl = 'https://yourdomain.com/api/cloaker_api.php';

// ⚠️ ÖNEMLİ: Ziyaretçi IP'sini al
function getRealVisitorIP() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',      // Cloudflare
        'HTTP_X_REAL_IP',              // Nginx reverse proxy
        'HTTP_CLIENT_IP',              // Bazı proxy'ler
        'HTTP_X_FORWARDED_FOR',        // Genel proxy header
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim($_SERVER[$header]);
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? null;
}

// Ziyaretçi bilgilerini al
$visitorIP = getRealVisitorIP();
$visitorUA = $_SERVER['HTTP_USER_AGENT'] ?? '';

// cURL ile istek gönder
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $apiKey,
    'X-Visitor-IP: ' . $visitorIP,        // ✅ Ziyaretçi IP'si
    'X-Visitor-UA: ' . $visitorUA,        // ✅ User-Agent
    'Content-Type: application/json'
]);

// Alternatif: JSON body ile gönderme
// $postData = json_encode([
//     'visitor_ip' => $visitorIP,
//     'visitor_ua' => $visitorUA
// ]);
// curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    error_log('Cloaker API cURL Error: ' . $curlError);
}

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if ($data && $data['status'] === 'ok') {
        // Yönlendirme yap (normal veya fake URL)
        header('Location: ' . $data['redirect_url']);
        exit;
    } else {
        // API hatası
        error_log('Cloaker API Error: ' . ($data['message'] ?? 'Unknown'));
    }
} else {
    // HTTP hatası
    error_log('Cloaker API HTTP Error: ' . $httpCode);
}
?>
```

---

### 4. HTML Sayfasına Basit Entegrasyon

```html
<!DOCTYPE html>
<html>
<head>
    <title>My Website</title>
    <!-- Cloaker API Script -->
    <script>
        (function() {
            const API_KEY = 'YOUR_API_KEY_HERE';
            const API_URL = 'https://yourdomain.com/api/cloaker_api.php';
            
            fetch(API_URL, {
                method: 'POST',
                headers: {
                    'X-API-Key': API_KEY,
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok' && data.redirect_url) {
                    window.location.replace(data.redirect_url);
                }
            })
            .catch(err => console.error('Cloaker Error:', err));
        })();
    </script>
</head>
<body>
    <h1>Sayfa yükleniyor...</h1>
</body>
</html>
```

---

### 5. React ile Kullanım

```jsx
import React, { useEffect } from 'react';

function App() {
    useEffect(() => {
        const checkVisitor = async () => {
            const API_KEY = 'YOUR_API_KEY_HERE';
            const API_URL = 'https://yourdomain.com/api/cloaker_api.php';
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'X-API-Key': API_KEY,
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'ok') {
                    if (data.allowed) {
                        // Normal içerik göster
                        console.log('Normal visitor');
                    } else {
                        // Fake içerik göster veya yönlendir
                        window.location.href = data.redirect_url;
                    }
                }
            } catch (error) {
                console.error('API Error:', error);
            }
        };
        
        checkVisitor();
    }, []);
    
    return (
        <div>
            <h1>My Website</h1>
        </div>
    );
}

export default App;
```

---

### 6. Vue.js ile Kullanım

```vue
<template>
    <div>
        <h1>My Website</h1>
    </div>
</template>

<script>
export default {
    mounted() {
        this.checkVisitor();
    },
    methods: {
        async checkVisitor() {
            const API_KEY = 'YOUR_API_KEY_HERE';
            const API_URL = 'https://yourdomain.com/api/cloaker_api.php';
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'X-API-Key': API_KEY,
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'ok') {
                    if (!data.allowed) {
                        window.location.href = data.redirect_url;
                    }
                }
            } catch (error) {
                console.error('API Error:', error);
            }
        }
    }
}
</script>
```

---

## 📤 Response Formatı

### Başarılı Response

```json
{
    "status": "ok",
    "allowed": true,
    "redirect_url": "https://normal-site.com",
    "redirect_target": "normal",
    "detection": {
        "is_bot": false,
        "is_proxy": false,
        "bot_confidence": 15.5,
        "fingerprint_score": 2,
        "signals": []
    },
    "visitor": {
        "ip": "192.168.1.1",
        "country": "TR",
        "os": "windows",
        "browser": "Chrome"
    }
}
```

### Hata Response

```json
{
    "status": "error",
    "message": "API anahtarı gerekli. X-API-Key header'ı veya api_key parametresi gönderin."
}
```

### Response Alanları Açıklaması

| Alan | Tip | Açıklama |
|------|-----|----------|
| `status` | string | `"ok"` veya `"error"` |
| `allowed` | boolean | Ziyaretçi izinli mi? |
| `redirect_url` | string | Yönlendirilecek URL |
| `redirect_target` | string | `"normal"` veya `"fake"` |
| `detection.is_bot` | boolean | Bot tespit edildi mi? |
| `detection.is_proxy` | boolean | VPN/Proxy tespit edildi mi? |
| `detection.bot_confidence` | number | Bot güven skoru (0-100) |
| `detection.fingerprint_score` | number | Fingerprint sinyal sayısı |
| `detection.signals` | array | Tespit edilen sinyaller |
| `visitor.ip` | string | Ziyaretçi IP adresi |
| `visitor.country` | string | Ülke kodu (ISO2) |
| `visitor.os` | string | İşletim sistemi |
| `visitor.browser` | string | Tarayıcı |

---

## ⚠️ Hata Yönetimi

### Yaygın Hatalar

1. **401 Unauthorized**
   ```json
   {
       "status": "error",
       "message": "API anahtarı gerekli."
   }
   ```
   **Çözüm**: API key'i doğru gönderdiğinizden emin olun.

2. **500 Internal Server Error**
   ```json
   {
       "status": "error",
       "message": "Sunucu hatası oluştu."
   }
   ```
   **Çözüm**: Sunucu loglarını kontrol edin.

3. **Network Error**
   - CORS hatası alıyorsanız, API endpoint'inin CORS header'larını kontrol edin.
   - API URL'inin doğru olduğundan emin olun.

### Hata Yönetimi Örneği

```javascript
async function checkVisitor() {
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'X-API-Key': API_KEY,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.status === 'error') {
            console.error('API Error:', data.message);
            // Hata durumunda varsayılan davranış
            return;
        }
        
        // Başarılı response işle
        if (data.allowed) {
            window.location.href = data.redirect_url;
        } else {
            window.location.href = data.redirect_url;
        }
        
    } catch (error) {
        console.error('Network Error:', error);
        // Hata durumunda ne yapılacağını belirleyin
        // Örn: Normal sayfayı göster, hata mesajı göster, vs.
    }
}
```

---

## 🎯 Kullanım Senaryoları

### Senaryo 1: Basit Yönlendirme
Tüm ziyaretçileri otomatik yönlendir.

```javascript
fetch(API_URL, {
    method: 'POST',
    headers: { 'X-API-Key': API_KEY }
})
.then(res => res.json())
.then(data => {
    if (data.status === 'ok') {
        window.location.href = data.redirect_url;
    }
});
```

### Senaryo 2: İçerik Gösterme
Bot tespit edilirse fake içerik göster.

```javascript
fetch(API_URL, {
    method: 'POST',
    headers: { 'X-API-Key': API_KEY }
})
.then(res => res.json())
.then(data => {
    if (data.status === 'ok') {
        if (data.allowed) {
            showNormalContent();
        } else {
            showFakeContent();
        }
    }
});
```

### Senaryo 3: Analytics Entegrasyonu
Ziyaretçi bilgilerini analytics'e gönder.

```javascript
fetch(API_URL, {
    method: 'POST',
    headers: { 'X-API-Key': API_KEY }
})
.then(res => res.json())
.then(data => {
    if (data.status === 'ok') {
        // Google Analytics'e gönder
        gtag('event', 'visitor_check', {
            'is_bot': data.detection.is_bot,
            'country': data.visitor.country,
            'bot_confidence': data.detection.bot_confidence
        });
    }
});
```

---

## 🔒 Güvenlik Önerileri

1. **API Key'i Güvenli Tutun**
   - API key'i asla public repository'lerde paylaşmayın
   - Environment variable kullanın
   - Client-side'da kullanıyorsanız, rate limiting ekleyin

2. **HTTPS Kullanın**
   - API isteklerini mutlaka HTTPS üzerinden yapın

3. **Rate Limiting**
   - Çok fazla istek göndermeyin
   - Cache mekanizması kullanın

4. **Error Handling**
   - Hata durumlarını mutlaka handle edin
   - Kullanıcıya uygun mesajlar gösterin

---

## 📊 API İstatistikleri

API kullanım istatistiklerinizi admin panelinden görüntüleyebilirsiniz:
- `🔑 API Anahtarları` sayfasından son kullanım zamanını görebilirsiniz
- Her API key için günlük istek sayısı ve bot engelleme sayısı takip edilir

---

## ❓ Sık Sorulan Sorular

**S: API key'i nereden alırım?**
C: Admin panelinden `🔑 API Anahtarları` sayfasından yeni bir API key oluşturabilirsiniz.

**S: API key'i kaybettim, ne yapmalıyım?**
C: Eski key'i silip yeni bir tane oluşturun.

**S: Birden fazla site için farklı API key kullanabilir miyim?**
C: Evet, her site için ayrı API key oluşturabilirsiniz.

**S: API isteği başarısız olursa ne olur?**
C: Hata response döner. Kodunuzda bu durumu handle etmelisiniz.

**S: Bot tespit edildiğinde ne olur?**
C: `allowed: false` döner ve `redirect_url` fake URL'i içerir.

---

## 📞 Destek

Sorularınız için: sunucukrali58@gmail.com

---

**Geliştirici: Kahin**
**Versiyon: 2.0**











