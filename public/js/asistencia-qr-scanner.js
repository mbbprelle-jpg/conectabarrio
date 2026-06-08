/**
 * Escáner QR de asistencia — usa html5-qrcode (cámara del dispositivo).
 */
(function () {
    let scanner = null;
    let scanning = false;
    const recentTokens = new Map();
    const DEDUPE_MS = 4000;

    function playBeep(success) {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = success ? 880 : 220;
            gain.gain.value = 0.08;
            osc.start();
            osc.stop(ctx.currentTime + (success ? 0.12 : 0.2));
        } catch (e) { /* sin audio */ }
    }

    function showFeedback(el, message, type) {
        if (!el) return;
        el.textContent = message;
        el.className = 'cb-qr-scan-feedback is-' + (type || 'info');
    }

    function prependLog(logEl, text, type) {
        if (!logEl) return;
        const li = document.createElement('li');
        li.className = 'is-' + (type || 'ok');
        li.textContent = text;
        logEl.prepend(li);
        while (logEl.children.length > 8) {
            logEl.removeChild(logEl.lastChild);
        }
    }

    function markRowPresent(socioId) {
        const row = document.querySelector('.cb-asistencia-row[data-socio-id="' + socioId + '"]');
        if (!row) return;
        const cb = row.querySelector('.cb-asistencia-check');
        if (cb) cb.checked = true;
        row.classList.add('is-present');
    }

    window.cbInitAsistenciaQrScanner = function (cfg) {
        if (scanning || typeof Html5Qrcode === 'undefined') return;
        const readerEl = document.getElementById(cfg.readerId);
        if (!readerEl || readerEl.dataset.initialized === '1') return;
        readerEl.dataset.initialized = '1';

        scanner = new Html5Qrcode(cfg.readerId);
        scanning = true;

        const feedback = document.getElementById(cfg.feedbackId);
        const logEl = document.getElementById(cfg.logId);
        let busy = false;

        const onScan = async function (decodedText) {
            const tokenKey = decodedText.slice(0, 64);
            const now = Date.now();
            if (busy) return;
            if (recentTokens.has(tokenKey) && now - recentTokens.get(tokenKey) < DEDUPE_MS) return;

            busy = true;
            recentTokens.set(tokenKey, now);

            try {
                const body = new URLSearchParams();
                body.set('reunion_id', String(cfg.reunionId));
                body.set('payload', decodedText);

                const res = await fetch(cfg.endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    body: body.toString()
                });
                const data = await res.json();

                if (data.ok) {
                    playBeep(true);
                    showFeedback(feedback, data.message, data.ya_presente ? 'warn' : 'ok');
                    prependLog(logEl, data.message, data.ya_presente ? 'warn' : 'ok');
                    if (data.socio_id) markRowPresent(data.socio_id);
                    const pLive = document.getElementById(cfg.presentesId);
                    const pHead = document.getElementById(cfg.presentesHeaderId);
                    if (typeof data.presentes === 'number') {
                        if (pLive) pLive.textContent = String(data.presentes);
                        if (pHead) pHead.textContent = String(data.presentes);
                    }
                } else {
                    playBeep(false);
                    showFeedback(feedback, data.message || 'Error al registrar', 'err');
                    prependLog(logEl, data.message || 'Error', 'err');
                }
            } catch (err) {
                playBeep(false);
                showFeedback(feedback, 'Error de conexión', 'err');
            } finally {
                setTimeout(function () { busy = false; }, 300);
            }
        };

        Html5Qrcode.getCameras().then(function (cameras) {
            if (!cameras || !cameras.length) {
                showFeedback(feedback, 'No se detectó cámara. Use HTTPS o permisos del navegador.', 'err');
                scanning = false;
                return;
            }
            const backCam = cameras.find(function (c) {
                return /back|rear|environment/i.test(c.label);
            });
            const camId = (backCam || cameras[cameras.length - 1]).id;

            scanner.start(
                camId,
                { fps: 12, qrbox: { width: 240, height: 240 }, aspectRatio: 1 },
                onScan,
                function () { /* frame sin QR */ }
            ).then(function () {
                showFeedback(feedback, 'Cámara activa — escaneando…', 'info');
            }).catch(function () {
                showFeedback(feedback, 'No se pudo iniciar la cámara.', 'err');
                scanning = false;
            });
        }).catch(function () {
            showFeedback(feedback, 'Permita el acceso a la cámara para escanear.', 'err');
            scanning = false;
        });
    };
})();
