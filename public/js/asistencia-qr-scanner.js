/**
 * Escáner QR de asistencia — html5-qrcode, optimizado para móvil.
 */
(function () {
    let scanner = null;
    let scanning = false;
    let activeContainerId = null;
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

    function qrBoxSize(viewfinderWidth, viewfinderHeight) {
        var w = viewfinderWidth || 300;
        var h = viewfinderHeight || 300;
        var minEdge = Math.min(w, h);
        var size = Math.floor(minEdge * 0.92);
        return { width: size, height: size };
    }

    function buildScanConfig() {
        return {
            fps: 15,
            /* Sin qrbox fijo pequeño: usa casi todo el visor */
            qrbox: qrBoxSize,
            aspectRatio: 1.333333,
            disableFlip: false,
            videoConstraints: {
                facingMode: { ideal: 'environment' },
                width: { min: 640, ideal: 1280, max: 1920 },
                height: { min: 480, ideal: 720, max: 1080 }
            }
        };
    }

    function stopScanner() {
        if (!scanner || !scanning) return Promise.resolve();
        scanning = false;
        return scanner.stop().then(function () {
            return scanner.clear();
        }).catch(function () { /* ya detenido */ });
    }

    function startOnContainer(containerId, cfg, onScan, feedback) {
        activeContainerId = containerId;
        scanner = new Html5Qrcode(containerId, /* verbose= */ false);
        scanning = true;

        var cameraConfig = { facingMode: 'environment' };

        return scanner.start(cameraConfig, buildScanConfig(), onScan, function () { /* sin QR en frame */ })
            .then(function () {
                showFeedback(feedback, 'Cámara activa — encuadre el QR en pantalla', 'info');
            });
    }

    window.cbInitAsistenciaQrScanner = function (cfg) {
        if (typeof Html5Qrcode === 'undefined') return;

        var readerEl = document.getElementById(cfg.readerId);
        if (!readerEl) return;
        if (readerEl.dataset.initialized === '1' && scanning) return;

        var feedback = document.getElementById(cfg.feedbackId);
        var logEl = document.getElementById(cfg.logId);
        var busy = false;

        var onScan = async function (decodedText) {
            var tokenKey = decodedText.slice(0, 64);
            var now = Date.now();
            if (busy) return;
            if (recentTokens.has(tokenKey) && now - recentTokens.get(tokenKey) < DEDUPE_MS) return;

            busy = true;
            recentTokens.set(tokenKey, now);

            try {
                var body = new URLSearchParams();
                body.set('reunion_id', String(cfg.reunionId));
                body.set('payload', decodedText);

                var res = await fetch(cfg.endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    body: body.toString()
                });
                var data = await res.json();

                if (data.ok) {
                    playBeep(true);
                    showFeedback(feedback, data.message, data.ya_presente ? 'warn' : 'ok');
                    prependLog(logEl, data.message, data.ya_presente ? 'warn' : 'ok');
                    if (data.socio_id) markRowPresent(data.socio_id);
                    var pLive = document.getElementById(cfg.presentesId);
                    var pHead = document.getElementById(cfg.presentesHeaderId);
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

        readerEl.dataset.initialized = '1';

        Html5Qrcode.getCameras().then(function (cameras) {
            if (!cameras || !cameras.length) {
                showFeedback(feedback, 'No se detectó cámara. Use HTTPS y permita el acceso.', 'err');
                return;
            }
            return startOnContainer(cfg.readerId, cfg, onScan, feedback);
        }).catch(function () {
            showFeedback(feedback, 'Permita el acceso a la cámara para escanear.', 'err');
            scanning = false;
        });

        /* Pantalla completa — visor mucho más grande en móvil */
        var fsBtn = document.getElementById('cbQrScannerFullscreenBtn');
        var overlay = document.getElementById('cbQrScannerFullscreen');
        if (fsBtn && overlay && !fsBtn.dataset.bound) {
            fsBtn.dataset.bound = '1';
            fsBtn.addEventListener('click', function () {
                overlay.hidden = false;
                document.body.classList.add('cb-qr-scanner-fs-open');

                stopScanner().then(function () {
                    var fsReader = document.getElementById('cbQrReaderFs');
                    if (fsReader) fsReader.innerHTML = '';
                    return startOnContainer('cbQrReaderFs', cfg, onScan, feedback);
                }).catch(function () {
                    showFeedback(feedback, 'No se pudo abrir la cámara en pantalla completa.', 'err');
                });
            });

            overlay.querySelector('.cb-qr-scanner-fs-close')?.addEventListener('click', function () {
                stopScanner().then(function () {
                    overlay.hidden = true;
                    document.body.classList.remove('cb-qr-scanner-fs-open');
                    var inlineReader = document.getElementById(cfg.readerId);
                    if (inlineReader) inlineReader.innerHTML = '';
                    readerEl.dataset.initialized = '0';
                    return startOnContainer(cfg.readerId, cfg, onScan, feedback);
                }).then(function () {
                    readerEl.dataset.initialized = '1';
                });
            });
        }
    };
})();
