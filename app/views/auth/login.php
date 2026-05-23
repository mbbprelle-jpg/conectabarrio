<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="landing-wrapper">
    <!-- Barra de Navegación -->
    <nav class="landing-navbar">
        <div class="landing-logo">
            <div class="landing-logo-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <span class="landing-logo-name">ConectaBarrio</span>
        </div>
        <ul class="landing-nav-links">
            <li><a href="#inicio">Inicio</a></li>
            <li><a href="#objetivos">Objetivos</a></li>
            <li><a href="#contacto">Contacto</a></li>
        </ul>
        <div>
            <button class="btn btn-primary trigger-login" style="padding: 0.6rem 1.4rem; font-size: 0.85rem; border-radius: 30px;">
                Autenticarse
            </button>
        </div>
    </nav>

    <!-- Sección Hero -->
    <header class="landing-hero" id="inicio">
        <div class="landing-hero-content">
            <div class="landing-hero-tag">
                Gestión Comunitaria Inteligente
            </div>
            <h1 class="landing-hero-title">
                Tu Junta de Vecinos, Comité u Organización <br><span>más conectada y eficiente</span>
            </h1>
            <p class="landing-hero-desc">
                Moderniza la administración de tu comunidad con ConectaBarrio. Controla asistencias a asambleas, envía recordatorios automáticos de cuotas, publica eventos comunitarios y comparte flujos financieros transparentes directamente con tus socios y además sincroniza información con el municipio.
            </p>
            <div class="landing-hero-ctas">
                <button class="btn btn-primary trigger-login">
                    Ingresar al Portal
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="0" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <a href="#objetivos" class="btn btn-secondary">
                    Conocer Características
                </a>
            </div>
        </div>
    </header>

    <!-- Sección de Objetivos -->
    <section class="landing-features-section" id="objetivos">
        <div class="landing-section-header">
            <h2 class="landing-section-title">
                Objetivos de <span>ConectaBarrio</span>
            </h2>
            <p class="landing-section-desc">
                Una plataforma diseñada para digitalizar la gestión comunitaria de forma robusta, segura y transparente en Chile.
            </p>
        </div>

        <div class="features-grid">
            <!-- Tarjeta 1: Apoyo Administrativo -->
            <div class="feature-card" onmousemove="handleCardHover(event, this)">
                <div class="feature-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Apoyo Administrativo Integral</h3>
                <p class="feature-desc">
                    Simplifica el trabajo diario de la directiva. Digitaliza el padrón de socios, define el valor y vigencia de las cuotas, y administra las calles jurisdiccionales de forma ágil.
                </p>
            </div>

            <!-- Tarjeta 2: Control de Asistencias -->
            <div class="feature-card" onmousemove="handleCardHover(event, this)">
                <div class="feature-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Control de Asistencias</h3>
                <p class="feature-desc">
                    Lleva un registro preciso y digitalizado de la asistencia de los socios a las asambleas ordinarias y extraordinarias, garantizando quórum e integridad comunal.
                </p>
            </div>

            <!-- Tarjeta 3: Correo Actividades -->
            <div class="feature-card" onmousemove="handleCardHover(event, this)">
                <div class="feature-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Recordatorios de Actividades</h3>
                <p class="feature-desc">
                    Notificaciones masivas y seguras vía correo electrónico para recordar a los socios sobre las próximas asambleas, mingas o actividades recreativas comunitarias.
                </p>
            </div>

            <!-- Tarjeta 4: Correo Cuotas -->
            <div class="feature-card" onmousemove="handleCardHover(event, this)">
                <div class="feature-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.1 1.1-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-6 10H6v-2h8v2zm4-4H6V8h12v2z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Recordatorio de Pagos</h3>
                <p class="feature-desc">
                    Mantén al día la tesorería vecinal. El sistema envía recordatorios automáticos por correo a los socios que tienen cuotas pendientes por regularizar.
                </p>
            </div>

            <!-- Tarjeta 5: Portal de Actividades -->
            <div class="feature-card" onmousemove="handleCardHover(event, this)">
                <div class="feature-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 15H3v-2h9v2zm0-4H3v-2h9v2zm9 4h-7v-6h7v6zm0-8H3V7h18v2z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Portal de Actividades</h3>
                <p class="feature-desc">
                    Mural interactivo comunitario donde se publican calendarios de actividades, talleres deportivos, beneficios sociales y anuncios de interés vecinal.
                </p>
            </div>

            <!-- Tarjeta 6: Flujo Financiero -->
            <div class="feature-card" onmousemove="handleCardHover(event, this)">
                <div class="feature-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Estado Financiero Organización</h3>
                <p class="feature-desc">
                    Transparencia absoluta. Los socios y directivas pueden visualizar balances contables, registro de ingresos (cuotas u otros) y egresos de manera didáctica.
                </p>
            </div>
        </div>
    </section>

    <!-- Footer de la Landing -->
    <footer class="landing-footer" id="contacto">
        <div class="landing-footer-logo">
            <div class="landing-logo-icon" style="width: 28px; height: 28px; border-radius: 6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <span class="landing-footer-logo-name">ConectaBarrio</span>
        </div>
        <p class="landing-footer-copy">
            Si deseas saber cómo implementar ConectaBarrio puedes escribirnos al WhatsApp 
            <a href="https://wa.me/56950001071" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: underline;">+56950001071</a> 
            o bien enviarnos un correo a 
            <a href="mailto:contacto@conectatubarrio.cl" style="color: var(--primary); font-weight: 600; text-decoration: underline;">contacto@conectatubarrio.cl</a>.
        </p>
        <div style="font-size: 0.75rem; color: var(--text-muted);">
            © 2026 ConectaBarrio. Todos los derechos reservados.
        </div>
    </footer>
</div>

<!-- Modal de Autenticación (Glassmorphism Premium) -->
<div class="auth-modal-overlay <?php echo !empty($data['error']) ? 'active' : ''; ?>" id="authModalOverlay">
    <div class="auth-modal">
        <!-- Botón de Cerrar -->
        <button class="modal-close" id="modalCloseBtn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="login-header">
            <div class="login-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <h2 style="font-family: var(--font-heading); font-weight: 700;">ConectaBarrio</h2>
            <p>Acceso Seguro al Portal Vecinal</p>
        </div>

        <!-- Alertas de Error -->
        <?php if (!empty($data['error'])): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?php echo htmlspecialchars($data['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulario de Acceso Seguro -->
        <form action="<?php echo URLROOT; ?>/auth/authenticate" method="POST">
            
            <div class="form-group">
                <label for="rut_or_email" class="form-label">RUT o Correo Electrónico</label>
                <input type="text" 
                       name="rut_or_email" 
                       id="rut_or_email" 
                       class="form-control" 
                       placeholder="Ej: 11.111.111-1 o admin@progreso.cl" 
                       value="<?php echo htmlspecialchars($data['rut_or_email']); ?>" 
                       required 
                       autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       class="form-control" 
                       placeholder="••••••••" 
                       required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.85rem;">
                Ingresar al Portal
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.8rem; color: var(--text-muted); line-height: 1.5;">
            Desarrollado para Juntas de Vecinos y Municipalidades en Chile.
            <br>
            <div style="margin-top: 0.5rem; padding: 0.5rem; background: rgba(99, 102, 241, 0.08); border-radius: 6px; border: 1px solid rgba(99, 102, 241, 0.15);">
                <strong style="color: var(--primary);">Acceso Maestro:</strong> <br>maestro@conectabarrio.cl / maestro123
            </div>
        </div>
    </div>
</div>

<script>
    // Manejo interactivo de resplandor (glow) en las tarjetas de objetivos
    function handleCardHover(e, card) {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        card.style.setProperty('--x', `${x}px`);
        card.style.setProperty('--y', `${y}px`);
    }

    // Cambiar opacidad de la Navbar al hacer Scroll
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.landing-navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>

