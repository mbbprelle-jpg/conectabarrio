<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/AuthContext.php'; ?>

<?php
$mapa = $data['mapa'] ?? [];
$total = (int)($mapa['total'] ?? 0);
$geo = (int)($mapa['geolocalizados'] ?? 0);
$sinGeo = (int)($mapa['sin_geolocalizar'] ?? 0);
$pctGeo = $total > 0 ? round(($geo / $total) * 100) : 0;
$latSede = $_SESSION['user_junta_lat_sede'] ?? null;
$lngSede = $_SESSION['user_junta_lng_sede'] ?? null;
$comuna = $_SESSION['user_junta_comuna'] ?? '';
?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<?php if (!empty($data['is_full_admin'])): ?>
<div class="card mapa-socios-config-card">
    <div class="mapa-socios-config-body">
        <div>
            <h3 class="mapa-socios-config-title">Configuración del mapa</h3>
            <p class="mapa-socios-config-text">
                Al habilitarlo, <strong>todos los miembros</strong> de la organización (administrador y socios) podrán ver el mapa comunitario en su menú lateral.
            </p>
        </div>
        <form action="<?php echo URLROOT; ?>/admin/mapa_socios_config" method="POST" class="mapa-socios-config-form">
            <input type="hidden" name="redirect_mapa" value="1">
            <label class="mapa-socios-toggle">
                <input type="checkbox" name="mapa_socios_habilitado" value="1" <?php echo !empty($data['mapa_habilitado']) ? 'checked' : ''; ?>>
                <span>Mapa comunitario habilitado</span>
            </label>
            <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="mapa-socios-page">
    <div class="mapa-socios-stats">
        <div class="mapa-stat-card">
            <span class="mapa-stat-label">Total miembros activos</span>
            <strong class="mapa-stat-value"><?php echo number_format($total, 0, ',', '.'); ?></strong>
        </div>
        <div class="mapa-stat-card mapa-stat-card--ok">
            <span class="mapa-stat-label">Geolocalizados</span>
            <strong class="mapa-stat-value"><?php echo number_format($geo, 0, ',', '.'); ?></strong>
            <span class="mapa-stat-meta"><?php echo $pctGeo; ?>% del padrón</span>
        </div>
        <div class="mapa-stat-card mapa-stat-card--warn">
            <span class="mapa-stat-label">Sin geolocalizar</span>
            <strong class="mapa-stat-value"><?php echo number_format($sinGeo, 0, ',', '.'); ?></strong>
            <?php if ($sinGeo > 0): ?>
                <span class="mapa-stat-meta">Actualice domicilios en el padrón</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mapa-socios-map-card">
        <div class="mapa-socios-map-toolbar">
            <div>
                <h3 class="card-title" style="margin-bottom: 0.25rem;">Concentración de miembros</h3>
                <p class="mapa-socios-map-hint">
                    Las zonas más cálidas indican mayor concentración. Incluye socios y administradores con domicilio georreferenciado.
                </p>
            </div>
            <div class="mapa-socios-layer-toggles">
                <label class="mapa-layer-toggle">
                    <input type="checkbox" id="toggleHeat" checked>
                    <span>Mapa de calor</span>
                </label>
                <label class="mapa-layer-toggle">
                    <input type="checkbox" id="toggleMarkers" checked>
                    <span>Marcadores</span>
                </label>
            </div>
        </div>

        <?php if ($geo === 0): ?>
            <div class="mapa-socios-empty">
                <p>Aún no hay miembros con coordenadas registradas.</p>
                <p class="mapa-socios-empty-hint">Complete el domicilio y la ubicación en el mapa al inscribir o editar socios y administradores.</p>
                <?php if (AuthContext::canManageSocios()): ?>
                    <a href="<?php echo URLROOT; ?>/admin/socios" class="btn btn-primary btn-sm">Ir al padrón</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div id="mapaSociosContainer" class="mapa-socios-container"></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($geo > 0): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="<?php echo URLROOT; ?>/js/mapa-socios.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initMapaSocios === 'function') {
        initMapaSocios({
            containerId: 'mapaSociosContainer',
            puntos: <?php echo json_encode($mapa['puntos'] ?? [], JSON_UNESCAPED_UNICODE); ?>,
            sede: {
                lat: <?php echo json_encode($latSede); ?>,
                lng: <?php echo json_encode($lngSede); ?>,
                label: <?php echo json_encode($_SESSION['user_junta_nombre'] ?? 'Sede'); ?>
            },
            comuna: <?php echo json_encode($comuna); ?>
        });
    }
});
</script>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
