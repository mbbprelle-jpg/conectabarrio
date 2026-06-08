<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="landing-wrapper" style="min-height: 100vh; padding: 2rem 1.5rem 3rem;">
    <nav class="landing-navbar" style="position: relative; margin-bottom: 2rem;">
        <div class="landing-logo">
            <div class="landing-logo-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <span class="landing-logo-name">ConectaBarrio</span>
        </div>
        <div>
            <a href="<?php echo URLROOT; ?>/" class="btn btn-secondary" style="margin-right: 0.5rem;">Inicio</a>
            <a href="<?php echo URLROOT; ?>/auth/login" class="btn btn-primary" style="padding: 0.6rem 1.4rem; font-size: 0.85rem; border-radius: 30px;">Ingresar al Portal</a>
        </div>
    </nav>

    <div class="page-header" style="margin-bottom: 2rem;">
        <div class="page-title-group">
            <h1>Presentación Ejecutiva</h1>
            <p>Descubre rápidamente las ventajas de ConectaBarrio para organizaciones territoriales.</p>
        </div>
    </div>

    <div class="metrics-grid">
        <div class="card card-primary">
            <h2 class="card-title">Gestión Integral</h2>
            <p>Controla socios, documentos y finanzas en una única plataforma.</p>
        </div>
        <div class="card card-success">
            <h2 class="card-title">Transparencia Financiera</h2>
            <p>Informes claros y flujo de caja en tiempo real con visualizaciones atractivas.</p>
        </div>
        <div class="card card-warning">
            <h2 class="card-title">Participación Comunitaria</h2>
            <p>Calendario de reuniones, asistencia vía QR y comunicación directa.</p>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
