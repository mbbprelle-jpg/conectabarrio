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

    // 2. Desvanecimiento automático de Alertas
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.6s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 600);
        }, 4000); // 4 segundos y se desvanece
    });

    // 3. Confirmaciones para acciones críticas
    const confirmButtons = document.querySelectorAll('.confirm-action');
    confirmButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-message') || '¿Estás seguro de realizar esta acción?';
            if (!confirm(message)) {
                e.preventDefault();
            }
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

        // Cerrar al hacer click en el overlay oscuro
        authModalOverlay.addEventListener('click', function(e) {
            if (e.target === authModalOverlay) {
                authModalOverlay.classList.remove('active');
            }
        });

        // Cerrar con la tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && authModalOverlay.classList.contains('active')) {
                authModalOverlay.classList.remove('active');
            }
        });
    }
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
