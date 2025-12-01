/**
 * Cloaker API - Gelişmiş JavaScript Örneği
 * 
 * Bu örnek, API'yi daha detaylı kullanmayı gösterir
 */

class CloakerAPI {
    constructor(apiKey, apiUrl) {
        this.apiKey = apiKey;
        this.apiUrl = apiUrl;
        this.decision = null;
    }

    /**
     * Ziyaretçiyi kontrol et
     */
    async checkVisitor(siteId = null) {
        try {
            const headers = {
                'X-API-Key': this.apiKey,
                'Content-Type': 'application/json'
            };

            const body = siteId ? { site_id: siteId } : {};

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(body)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.status === 'error') {
                throw new Error(data.message);
            }

            this.decision = data;
            return data;

        } catch (error) {
            console.error('Cloaker API Error:', error);
            this.onError(error);
            return null;
        }
    }

    /**
     * Otomatik yönlendirme yap
     */
    redirect() {
        if (this.decision && this.decision.redirect_url) {
            window.location.replace(this.decision.redirect_url);
        }
    }

    /**
     * Ziyaretçi izinli mi?
     */
    isAllowed() {
        return this.decision && this.decision.allowed === true;
    }

    /**
     * Bot tespit edildi mi?
     */
    isBot() {
        return this.decision && this.decision.detection.is_bot === true;
    }

    /**
     * Proxy/VPN tespit edildi mi?
     */
    isProxy() {
        return this.decision && this.decision.detection.is_proxy === true;
    }

    /**
     * Ziyaretçi bilgilerini al
     */
    getVisitorInfo() {
        return this.decision ? this.decision.visitor : null;
    }

    /**
     * Tespit bilgilerini al
     */
    getDetectionInfo() {
        return this.decision ? this.decision.detection : null;
    }

    /**
     * Hata callback
     */
    onError(error) {
        console.error('Cloaker API Error:', error);
        // Hata durumunda ne yapılacağını burada belirleyin
    }
}

// Kullanım Örneği
const cloaker = new CloakerAPI(
    'YOUR_API_KEY_HERE',
    'https://yourdomain.com/api/cloaker_api.php'
);

// Ziyaretçiyi kontrol et
cloaker.checkVisitor().then(data => {
    if (data) {
        console.log('Visitor Info:', cloaker.getVisitorInfo());
        console.log('Detection Info:', cloaker.getDetectionInfo());
        
        if (cloaker.isAllowed()) {
            console.log('Normal visitor - showing content');
            // Normal içeriği göster
        } else {
            console.log('Bot/VPN detected - redirecting');
            // Fake sayfaya yönlendir
            cloaker.redirect();
        }
    }
});

// Export (ES6 modules için)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CloakerAPI;
}
















