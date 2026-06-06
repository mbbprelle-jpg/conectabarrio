document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Manejo del Menú Responsive (Sidebar)
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // Cerrar sidebar al hacer click fuera en pantallas móviles
        document.addEventListener('click', function(event) {
            const isClickInside = sidebar.contains(event.target) || menuToggle.contains(event.target);
            if (!isClickInside && sidebar.classList.contains('active') && window.innerWidth <= 768) {
                sidebar.classList.remove('active');
            }
        });
    }

    // 2. Desvanecimiento automático de Alertas (excepto las marcadas como persistentes)
    const alerts = document.querySelectorAll('.alert:not(.alert-persistent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.6s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 600);
        }, 4000);
    });

    // 3. Confirmaciones para acciones críticas (modal moderno)
    const confirmOverlay = document.getElementById('cbConfirmModal');
    const confirmTitle = document.getElementById('cbConfirmTitle');
    const confirmMessage = document.getElementById('cbConfirmMessage');
    const confirmIcon = document.getElementById('cbConfirmIcon');
    const confirmOk = document.getElementById('cbConfirmOk');
    const confirmCancel = document.getElementById('cbConfirmCancel');
    let pendingConfirmAction = null;

    function closeConfirmModal(skipOverflowReset) {
        if (!confirmOverlay) return;
        confirmOverlay.classList.remove('is-open');
        confirmOverlay.setAttribute('aria-hidden', 'true');
        if (!skipOverflowReset) {
            document.body.style.overflow = '';
        }
        pendingConfirmAction = null;
    }

    function openConfirmModal(options) {
        if (!confirmOverlay) {
            if (options.onConfirm) options.onConfirm();
            return;
        }
        confirmTitle.textContent = options.title || 'Confirmar acción';
        confirmMessage.textContent = options.message || '¿Está seguro de continuar?';
        const variant = options.variant || 'info';
        confirmIcon.className = 'cb-confirm-icon ' + variant;
        if (variant === 'success') {
            confirmIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        } else if (variant === 'danger') {
            confirmIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        } else if (variant === 'warning') {
            confirmIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
        } else {
            confirmIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
        }
        if (variant === 'danger') {
            confirmOk.className = 'btn btn-danger';
            confirmOk.textContent = options.confirmLabel || 'Eliminar';
        } else if (variant === 'warning') {
            confirmOk.className = 'btn btn-warning';
            confirmOk.textContent = options.confirmLabel || 'Continuar';
        } else if (variant === 'success') {
            confirmOk.className = 'btn btn-success';
            confirmOk.textContent = options.confirmLabel || 'Confirmar';
        } else {
            confirmOk.className = 'btn btn-primary';
            confirmOk.textContent = options.confirmLabel || 'Confirmar';
        }
        pendingConfirmAction = options.onConfirm || null;
        confirmOverlay.classList.add('is-open');
        confirmOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        confirmOk.focus();
    }

    if (confirmOk) {
        confirmOk.addEventListener('click', function() {
            const action = pendingConfirmAction;
            pendingConfirmAction = null;
            closeConfirmModal(true);
            if (typeof action === 'function') {
                requestAnimationFrame(function() {
                    requestAnimationFrame(action);
                });
            } else {
                document.body.style.overflow = '';
            }
        });
    }
    if (confirmCancel) {
        confirmCancel.addEventListener('click', closeConfirmModal);
    }
    if (confirmOverlay) {
        confirmOverlay.addEventListener('click', function(e) {
            if (e.target === confirmOverlay) closeConfirmModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && confirmOverlay.classList.contains('is-open')) {
                closeConfirmModal();
            }
        });
    }

    window.cbOpenConfirm = openConfirmModal;

    var loadingOverlay = document.getElementById('cbLoadingOverlay');
    var loadingTitle = document.getElementById('cbLoadingTitle');
    var loadingMessage = document.getElementById('cbLoadingMessage');
    var loadingStatus = document.getElementById('cbLoadingStatus');
    var loadingElapsed = document.getElementById('cbLoadingElapsed');
    var loadingProgressBar = document.getElementById('cbLoadingProgressBar');
    var loadingPercent = document.getElementById('cbLoadingPercent');
    var loadingActive = false;
    var loadingWarnOnUnload = false;
    var loadingStatusTimer = null;
    var loadingElapsedTimer = null;
    var loadingStartedAt = 0;
    var loadingStatusSteps = [
        'Preparando datos…',
        'Validando información…',
        'Registrando socios…',
        'Georreferenciando domicilios…',
        'Casi listo…'
    ];

    function ensureLoadingOverlay() {
        if (loadingOverlay) {
            return loadingOverlay;
        }
        var wrapper = document.createElement('div');
        wrapper.id = 'cbLoadingOverlay';
        wrapper.className = 'cb-loading-overlay';
        wrapper.setAttribute('aria-hidden', 'true');
        wrapper.setAttribute('role', 'alertdialog');
        wrapper.setAttribute('aria-modal', 'true');
        wrapper.innerHTML = ''
            + '<div class="cb-loading-backdrop" aria-hidden="true"></div>'
            + '<div class="cb-loading-box">'
            + '  <div class="cb-loading-visual" aria-hidden="true">'
            + '    <div class="cb-loading-ring"></div>'
            + '    <div class="cb-loading-spinner"></div>'
            + '  </div>'
            + '  <p class="cb-loading-eyebrow">ConectaBarrio</p>'
            + '  <h3 id="cbLoadingTitle" class="cb-loading-title">Por favor espere…</h3>'
            + '  <p id="cbLoadingMessage" class="cb-loading-message">Estamos procesando su solicitud.</p>'
            + '  <div class="cb-loading-progress" aria-hidden="true"><div id="cbLoadingProgressBar" class="cb-loading-progress-bar"></div></div>'
            + '  <p id="cbLoadingPercent" class="cb-loading-percent" hidden>0%</p>'
            + '  <p id="cbLoadingStatus" class="cb-loading-status">Iniciando…</p>'
            + '  <p id="cbLoadingElapsed" class="cb-loading-elapsed" hidden></p>'
            + '  <p class="cb-loading-hint">No cierre ni recargue esta pestaña. La página se actualizará sola al terminar.</p>'
            + '</div>';
        document.body.appendChild(wrapper);
        loadingOverlay = wrapper;
        loadingTitle = document.getElementById('cbLoadingTitle');
        loadingMessage = document.getElementById('cbLoadingMessage');
        loadingStatus = document.getElementById('cbLoadingStatus');
        loadingElapsed = document.getElementById('cbLoadingElapsed');
        loadingProgressBar = document.getElementById('cbLoadingProgressBar');
        loadingPercent = document.getElementById('cbLoadingPercent');
        return loadingOverlay;
    }

    function resetLoadingProgressIndeterminate() {
        loadingProgressBar = document.getElementById('cbLoadingProgressBar');
        loadingPercent = document.getElementById('cbLoadingPercent');
        if (loadingProgressBar) {
            loadingProgressBar.classList.remove('cb-loading-progress-bar--determinate');
            loadingProgressBar.style.width = '';
            loadingProgressBar.style.animation = '';
        }
        if (loadingPercent) {
            loadingPercent.hidden = true;
            loadingPercent.textContent = '0%';
        }
    }

    function setLoadingProgress(percent, statusText) {
        ensureLoadingOverlay();
        loadingProgressBar = document.getElementById('cbLoadingProgressBar');
        loadingPercent = document.getElementById('cbLoadingPercent');
        stopLoadingTimers();
        var safePercent = Math.min(100, Math.max(0, Number(percent) || 0));
        if (loadingProgressBar) {
            loadingProgressBar.classList.add('cb-loading-progress-bar--determinate');
            loadingProgressBar.style.animation = 'none';
            loadingProgressBar.style.width = safePercent + '%';
        }
        if (loadingPercent) {
            loadingPercent.hidden = false;
            loadingPercent.textContent = Math.round(safePercent) + '%';
        }
        if (loadingStatus && statusText) {
            loadingStatus.textContent = statusText;
        }
        if (loadingElapsed && loadingElapsed.hidden) {
            startLoadingElapsed();
        }
    }

    window.cbUpdateLoadingProgress = setLoadingProgress;

    function stopLoadingTimers() {
        if (loadingStatusTimer) {
            window.clearInterval(loadingStatusTimer);
            loadingStatusTimer = null;
        }
        if (loadingElapsedTimer) {
            window.clearInterval(loadingElapsedTimer);
            loadingElapsedTimer = null;
        }
    }

    function formatElapsed(seconds) {
        if (seconds < 60) {
            return seconds + ' s';
        }
        var mins = Math.floor(seconds / 60);
        var secs = seconds % 60;
        return mins + ' min' + (secs > 0 ? ' ' + secs + ' s' : '');
    }

    function startLoadingElapsed() {
        if (!loadingElapsed) {
            return;
        }
        loadingStartedAt = Date.now();
        loadingElapsed.hidden = false;
        loadingElapsed.textContent = 'Tiempo transcurrido: 0 s';
        loadingElapsedTimer = window.setInterval(function() {
            var seconds = Math.floor((Date.now() - loadingStartedAt) / 1000);
            loadingElapsed.textContent = 'Tiempo transcurrido: ' + formatElapsed(seconds);
        }, 1000);
    }

    function closeUiForLoading() {
        document.querySelectorAll('.glass-modal-overlay.is-open').forEach(function(el) {
            el.classList.remove('is-open');
        });
        closeConfirmModal();
        document.body.style.overflow = 'hidden';
    }

    function startLoadingStatusCycle() {
        if (!loadingStatus) {
            return;
        }
        stopLoadingTimers();
        var step = 0;
        loadingStatus.textContent = loadingStatusSteps[0];
        loadingStatusTimer = window.setInterval(function() {
            step = (step + 1) % loadingStatusSteps.length;
            loadingStatus.textContent = loadingStatusSteps[step];
        }, 2800);
        startLoadingElapsed();
    }

    function allowPageLeave() {
        loadingWarnOnUnload = false;
        loadingActive = false;
    }

    function showLoadingOverlay(title, message, longRunning) {
        ensureLoadingOverlay();
        if (!loadingOverlay) {
            return;
        }
        stopLoadingTimers();
        closeUiForLoading();
        resetLoadingProgressIndeterminate();
        if (loadingOverlay.parentNode !== document.body) {
            document.body.appendChild(loadingOverlay);
        }
        loadingActive = true;
        loadingWarnOnUnload = !!longRunning;
        loadingOverlay.classList.remove('is-open');
        if (loadingTitle) {
            loadingTitle.textContent = title || 'Por favor espere…';
        }
        if (loadingMessage) {
            loadingMessage.textContent = message || 'Estamos procesando su solicitud.';
        }
        if (loadingElapsed) {
            loadingElapsed.hidden = !longRunning;
            loadingElapsed.textContent = '';
        }
        document.body.classList.add('cb-loading-active');
        document.body.style.overflow = 'hidden';
        loadingOverlay.setAttribute('aria-hidden', 'false');
        void loadingOverlay.offsetWidth;
        loadingOverlay.classList.add('is-open');
        if (longRunning) {
            startLoadingStatusCycle();
        } else if (loadingStatus) {
            loadingStatus.textContent = 'Un momento…';
        }
        void loadingOverlay.offsetHeight;
    }

    window.cbShowLoading = showLoadingOverlay;

    function nativeFormSubmit(form) {
        HTMLFormElement.prototype.submit.call(form);
    }

    function triggerFormLoadingSubmit(form) {
        if (!form || form.dataset.cbLoading === '1') {
            return;
        }
        form.dataset.cbLoading = '1';
        var longRunning = form.classList.contains('cb-loading-form--long');
        showLoadingOverlay(
            form.getAttribute('data-loading-title'),
            form.getAttribute('data-loading-message'),
            longRunning
        );
        form.querySelectorAll('button, input[type="submit"]').forEach(function(btn) {
            btn.disabled = true;
        });
        window.setTimeout(function() {
            loadingWarnOnUnload = false;
            nativeFormSubmit(form);
        }, 180);
    }

    async function runBulkImportChunked(form) {
        var chunkUrl = form.getAttribute('data-chunk-url');
        if (!chunkUrl) {
            triggerFormLoadingSubmit(form);
            return;
        }
        if (form.dataset.cbLoading === '1') {
            return;
        }
        form.dataset.cbLoading = '1';
        showLoadingOverlay(
            form.getAttribute('data-loading-title'),
            form.getAttribute('data-loading-message'),
            true
        );
        setLoadingProgress(0, 'Iniciando importación…');
        form.querySelectorAll('button').forEach(function(btn) {
            btn.disabled = true;
        });

        var first = true;
        while (true) {
            var response = await fetch(chunkUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: first ? 'reset=1' : ''
            });
            first = false;
            var data;
            try {
                data = await response.json();
            } catch (err) {
                allowPageLeave();
                alert('Error de comunicación al importar. Recargue la página e intente de nuevo.');
                window.location.reload();
                return;
            }
            if (!data.ok) {
                allowPageLeave();
                alert(data.error || 'Error al importar');
                window.location.reload();
                return;
            }
            setLoadingProgress(data.percent, data.status);
            if (data.done) {
                setLoadingProgress(100, 'Importación finalizada');
                allowPageLeave();
                window.location.href = data.redirect || form.getAttribute('action') || window.location.href;
                return;
            }
        }
    }

    window.cbRunBulkImportChunked = runBulkImportChunked;

    window.cbTriggerFormLoadingSubmit = triggerFormLoadingSubmit;

    window.addEventListener('beforeunload', function(e) {
        if (loadingWarnOnUnload) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    document.querySelectorAll('form.cb-loading-form--light').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (form.dataset.cbLoading === '1') {
                return;
            }
            e.preventDefault();
            form.dataset.cbLoading = '1';
            var btn = form.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('cb-btn-is-loading');
                btn.dataset.originalLabel = btn.textContent;
                btn.textContent = 'Validando…';
            }
            nativeFormSubmit(form);
        });
    });

    document.querySelectorAll('form.cb-loading-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (form.dataset.cbLoading === '1') {
                return;
            }
            e.preventDefault();
            triggerFormLoadingSubmit(form);
        });
    });

    const confirmButtons = document.querySelectorAll('.confirm-action');
    confirmButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.getAttribute('data-confirm-message') || '¿Está seguro de realizar esta acción?';
            const title = this.getAttribute('data-confirm-title') || 'Confirmar acción';
            const variant = this.getAttribute('data-confirm-variant') || 'info';
            const confirmLabel = this.getAttribute('data-confirm-label');
            const form = this.closest('form');
            const href = this.getAttribute('href');
            openConfirmModal({
                title: title,
                message: message,
                variant: variant,
                confirmLabel: confirmLabel,
                onConfirm: function() {
                    if (form && form.getAttribute('data-chunk-url')) {
                        runBulkImportChunked(form);
                    } else if (form) {
                        triggerFormLoadingSubmit(form);
                    } else if (href) {
                        window.location.href = href;
                    }
                }
            });
        });
    });

    // 4. Filtrado en tiempo real para la lista de socios
    const searchInput = document.getElementById('searchSocio');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.table-searchable tbody tr');

            tableRows.forEach(function(row) {
                const nombre = row.querySelector('.search-name').textContent.toLowerCase();
                const rut = row.querySelector('.search-rut').textContent.toLowerCase();

                if (nombre.includes(filter) || rut.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // 6. Control de Modal de Autenticación
    const authModalOverlay = document.getElementById('authModalOverlay');
    const triggerButtons = document.querySelectorAll('.trigger-login');
    const modalCloseBtn = document.getElementById('modalCloseBtn');

    if (authModalOverlay) {
        triggerButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                authModalOverlay.classList.add('active');
                // Poner el foco en el primer input al abrir
                const inputRut = document.getElementById('rut_or_email');
                if (inputRut) {
                    setTimeout(function() {
                        inputRut.focus();
                    }, 100);
                }
            });
        });

        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', function() {
                authModalOverlay.classList.remove('active');
            });
        }

        // Cerrar con la tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && authModalOverlay.classList.contains('active')) {
                authModalOverlay.classList.remove('active');
            }
        });
    }

    // Teléfono Chile (+56 + 9 dígitos ingresados por el usuario)
    document.querySelectorAll('.cb-telefono-cl').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 9);
        });
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            this.value = text.replace(/\D/g, '').slice(0, 9);
        });
    });

    // Texto en mayúsculas mientras el usuario escribe
    document.querySelectorAll('.cb-uppercase').forEach(function(input) {
        input.addEventListener('input', function() {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(start, end);
        });
    });

    // Mostrar / ocultar contraseña en campos con .password-toggle-btn
    document.querySelectorAll('.password-toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const wrap = this.closest('.password-input-wrap');
            const input = wrap ? wrap.querySelector('input') : null;
            if (!input) return;

            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            this.classList.toggle('is-visible', !visible);
            this.setAttribute('aria-pressed', visible ? 'false' : 'true');
            this.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            this.setAttribute('title', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            input.focus();
        });
    });
});

// 5. Animación interactiva de la simulación de digitalización y transmisión municipal
function iniciarSimulacionMunicipal(jsonData) {
    const screen = document.getElementById('simulationScreen');
    const logContainer = document.getElementById('simulationLog');
    const btnEnviar = document.getElementById('btnEnviarMunicipal');
    const resultCard = document.getElementById('certificateResult');

    if (!screen || !logContainer || !btnEnviar) return;

    btnEnviar.disabled = true;
    logContainer.innerHTML = '';
    resultCard.style.display = 'none';

    const logs = [
        "[INFO] Iniciando digitalización de la información...",
        "[OK] Padrón de socios activos compilado correctamente.",
        "[OK] Estado de asistencias de asambleas anuales consolidado.",
        "[OK] Flujo de caja e informe contable financiero verificado.",
        "[INFO] Generando paquete estructurado en formato JSON seguro...",
        "[DEBUG] " + JSON.stringify(jsonData, null, 2).substring(0, 100) + "...",
        "[INFO] Estableciendo conexión con el API Gateway de la Ilustre Municipalidad...",
        "[CONNECTING] Conectando a https://api.municipalidad.gov/v1/recepcion/conectabarrio...",
        "[INFO] Encriptando paquete de datos con protocolo TLS 1.3...",
        "[SENDING] Transmitiendo información en tiempo real...",
        "[OK] Datos recibidos por el servidor central de la Municipalidad.",
        "[INFO] Procesando firma digital de la Dirección de Desarrollo Comunitario (DIDECO)...",
        "[OK] Firma digital y folio de seguimiento generados exitosamente.",
        "[SUCCESS] Proceso completado. Documento certificado de recepción disponible."
    ];

    let currentLog = 0;

    function printNextLog() {
        if (currentLog < logs.length) {
            const line = document.createElement('div');
            line.className = 'simulation-log';
            line.textContent = logs[currentLog];
            logContainer.appendChild(line);
            screen.scrollTop = screen.scrollHeight;
            currentLog++;
            
            // Intervalo simulado variable para que parezca más natural
            let speed = 400 + Math.random() * 400;
            if (logs[currentLog - 1].includes("DEBUG")) speed = 1200; // Demorar más el JSON
            if (logs[currentLog - 1].includes("SENDING")) speed = 1000;
            
            setTimeout(printNextLog, speed);
        } else {
            // Guardar en la base de datos mediante envío de formulario real
            const formGuardar = document.getElementById('formGuardarReporte');
            if (formGuardar) {
                setTimeout(function() {
                    formGuardar.submit();
                }, 800);
            } else {
                btnEnviar.disabled = false;
                resultCard.style.display = 'block';
                resultCard.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }

    printNextLog();
}
