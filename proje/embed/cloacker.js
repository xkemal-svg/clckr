(function () {
    var scriptEl = document.currentScript || (function () {
        var scripts = document.getElementsByTagName('script');
        return scripts[scripts.length - 1];
    })();

    if (!scriptEl) {
        console.warn('cloacker: current script not found.');
        return;
    }

    var endpoint = scriptEl.getAttribute('data-endpoint') || '/api/cloaker_decision.php';
    var siteId = scriptEl.getAttribute('data-site-id');
    var autoRedirect = scriptEl.getAttribute('data-auto-redirect');
    var shouldRedirect = autoRedirect === null || autoRedirect === 'true';

    function appendParam(url, key, value) {
        var hasQuery = url.indexOf('?') !== -1;
        var sep = hasQuery ? '&' : '?';
        return url + sep + encodeURIComponent(key) + '=' + encodeURIComponent(value);
    }

    try {
        var originHost = window.location.hostname || '';
        if (originHost) {
            endpoint = appendParam(endpoint, 'origin', originHost);
        }
        if (siteId) {
            endpoint = appendParam(endpoint, 'site_id', siteId);
        }
    } catch (e) {
        console.warn('cloacker: origin param eklenemedi', e);
    }

    function handleResponse(payload) {
        if (!payload || payload.status !== 'ok') {
            console.warn('cloacker: beklenmeyen yanıt', payload);
            return;
        }

        window.__cloackerDecision = payload;

        if (shouldRedirect && payload.redirect_url) {
            window.location.replace(payload.redirect_url);
        }
    }

    function handleError(err) {
        console.warn('cloacker: API isteği başarısız', err);
    }

    if (window.fetch) {
        fetch(endpoint, {
            credentials: 'include',
            cache: 'no-store'
        })
            .then(function (res) { return res.json(); })
            .then(handleResponse)
            .catch(handleError);
    } else {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', endpoint, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        handleResponse(JSON.parse(xhr.responseText));
                    } catch (parseErr) {
                        handleError(parseErr);
                    }
                } else {
                    handleError(xhr.statusText || 'HTTP ' + xhr.status);
                }
            }
        };
        xhr.withCredentials = true;
        xhr.send();
    }
})();

