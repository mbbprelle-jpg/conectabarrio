<?php
require_once APPROOT . '/views/layouts/header.php';
?>
<div class="page-header">
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
<div class="page-header-actions">
    <a href="<?php echo URLROOT; ?>/admin/dashboard" class="btn btn-primary">Ir al Panel</a>
</div>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
