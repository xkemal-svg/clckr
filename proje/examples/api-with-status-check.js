/**
 * Cloaker API - Kod Durumu Kontrolü ile Entegrasyon
 * 
 * Bu kod, site aktif olana kadar bekler ve sonra yönlendirme yapar
 */

(function() {
    // ⚠️ AYARLAR - BURAYI DÜZENLEYİN
    const CLOAKER_API_KEY = 'BURAYA_API_KEY_YAZIN';
    const CLOAKER_API_URL = 'https://yourdomain.com/api/cloaker_api.php';
    const STATUS_CHECK_URL = 'https://yourdomain.com/api/check_code_status.php';
    
    // Maksimum bekleme süresi (dakika)
    const MAX_WAIT_MINUTES = 10;
    const CHECK_INTERVAL = 5000; // 5 saniye
    
    let startTime = Date.now();
    let checkCount = 0;
    
    /**
     * Site durumunu kontrol et
     */
    async function checkSiteStatus() {
        try {
            const response = await fetch(STATUS_CHECK_URL, {
                method: 'POST',
                headers: {
                    'X-API-Key': CLOAKER_API_KEY,
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.status === 'ok') {
                return data.ready; // true veya false
            }
            
            return false;
        } catch (error) {
            console.error('Status check error:', error);
            return false;
        }
    }
    
    /**
     * Ana cloaker kontrolü
     */
    async function performCloakerCheck() {
        try {
            const response = await fetch(CLOAKER_API_URL, {
                method: 'POST',
                headers: {
                    'X-API-Key': CLOAKER_API_KEY,
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.status === 'ok' && data.redirect_url) {
                window.location.replace(data.redirect_url);
            } else {
                console.error('Cloaker API error:', data.message);
            }
        } catch (error) {
            console.error('Cloaker API network error:', error);
        }
    }
    
    /**
     * Durum kontrolü döngüsü
     */
    async function waitForSiteActivation() {
        const elapsed = (Date.now() - startTime) / 1000 / 60; // dakika
        
        if (elapsed >= MAX_WAIT_MINUTES) {
            console.warn('Maksimum bekleme süresi aşıldı. Yine de yönlendirme yapılıyor...');
            performCloakerCheck();
            return;
        }
        
        checkCount++;
        const isReady = await checkSiteStatus();
        
        if (isReady) {
            console.log('Site aktif! Yönlendirme yapılıyor...');
            performCloakerCheck();
        } else {
            if (checkCount % 12 === 0) { // Her 1 dakikada bir log
                console.log(`Site henüz aktif değil. Bekleniyor... (${Math.round(elapsed)} dakika)`);
            }
            setTimeout(waitForSiteActivation, CHECK_INTERVAL);
        }
    }
    
    // İlk kontrol
    waitForSiteActivation();
})();
















