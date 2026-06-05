<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/SocioInput.php'; ?>

<?php
$socio = $data['socio'];
$membresia = $data['membresia'] ?? null;
$idSocio = $membresia->id_socio ?? $socio->id_socio ?? null;
$nombreCompleto = trim(
    ($socio->nombre ?? '') . ' '
    . ($socio->apellido_paterno ?? '') . ' '
    . ($socio->apellido_materno ?? '')
);
?>

<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger">
        <span><?php echo htmlspecialchars($data['error']); ?></span>
    </div>
<?php endif; ?>
<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success">
        <span><?php echo htmlspecialchars($data['success']); ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($data['cambio_pendiente'])): ?>
    <div class="alert alert-info socio-solicitud-alert">
        Ya tiene una solicitud de cambio en revisión desde el
        <?php echo !empty($data['cambio_pendiente']->created_at) ? date('d-m-Y H:i', strtotime($data['cambio_pendiente']->created_at)) : '—'; ?>.
        Un administrador debe aprobarla antes de que se apliquen los cambios.
    </div>
<?php endif; ?>

<div class="socio-solicitud-page">
    <div class="socio-solicitud-layout">
        <aside class="card socio-ficha-identidad">
            <h3 class="socio-ficha-title">Identificación</h3>
            <p class="socio-ficha-hint">Estos datos no se modifican desde este formulario.</p>

            <dl class="socio-ficha-dl">
                <div class="socio-ficha-row">
                    <dt>RUT</dt>
                    <dd><?php echo htmlspecialchars($socio->rut ?? '—'); ?></dd>
                </div>
                <?php if ($idSocio !== null && $idSocio !== ''): ?>
                <div class="socio-ficha-row">
                    <dt>N° de socio</dt>
                    <dd><?php echo htmlspecialchars((string)$idSocio); ?></dd>
                </div>
                <?php endif; ?>
                <div class="socio-ficha-row socio-ficha-row--full">
                    <dt>Nombre completo</dt>
                    <dd class="socio-ficha-nombre"><?php echo htmlspecialchars($nombreCompleto ?: '—'); ?></dd>
                </div>
                <div class="socio-ficha-row">
                    <dt>Nombres</dt>
                    <dd><?php echo htmlspecialchars(trim($socio->nombre ?? '') ?: '—'); ?></dd>
                </div>
                <div class="socio-ficha-row">
                    <dt>Apellido paterno</dt>
                    <dd><?php echo htmlspecialchars(trim($socio->apellido_paterno ?? '') ?: '—'); ?></dd>
                </div>
                <div class="socio-ficha-row">
                    <dt>Apellido materno</dt>
                    <dd><?php echo htmlspecialchars(trim($socio->apellido_materno ?? '') ?: '—'); ?></dd>
                </div>
                <?php if (!empty($_SESSION['user_junta_nombre'])): ?>
                <div class="socio-ficha-row socio-ficha-row--full">
                    <dt>Organización</dt>
                    <dd><?php echo htmlspecialchars($_SESSION['user_junta_nombre']); ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </aside>

        <div class="card socio-solicitud-form">
            <h3 class="card-title">Datos que puede solicitar actualizar</h3>
            <p class="socio-solicitud-intro">
                Complete o corrija la información de contacto, datos personales y domicilio.
                Los cambios quedarán pendientes hasta que la directiva los apruebe.
            </p>

            <form action="<?php echo URLROOT; ?>/socio/enviar_solicitud" method="POST">
                <section class="form-section">
                    <h4 class="form-section-title">Contacto</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="email" class="form-label">Correo electrónico *</label>
                            <input type="email" name="email" id="email" class="form-control" required
                                   value="<?php echo htmlspecialchars($data['values']['email'] ?? ''); ?>">
                        </div>
                        <?php
                        $id = 'telefono';
                        $name = 'telefono';
                        $telefonoLabel = 'Teléfono';
                        $required = false;
                        $value = $data['values']['telefono'] ?? '';
                        require APPROOT . '/views/partials/campo_telefono_cl.php';
                        ?>
                    </div>
                </section>

                <section class="form-section">
                    <h4 class="form-section-title">Datos personales</h4>
                    <?php
                    $prefix = '';
                    $values = $data['values'];
                    $required = false;
                    require APPROOT . '/views/partials/socio_demografia_fields.php';
                    ?>
                </section>

                <section class="form-section form-section--last">
                    <h4 class="form-section-title">Domicilio</h4>
                    <?php
                    $domPrefix = '';
                    $domValues = $data['values'];
                    $domRequired = true;
                    $orgTipo = $data['org_tipo'];
                    $usesCalles = $data['uses_calles'];
                    $calles = $data['calles'];
                    $georefComuna = $data['junta_comuna'];
                    require APPROOT . '/views/partials/socio_domicilio_fields.php';
                    ?>
                </section>

                <div class="socio-solicitud-actions">
                    <a href="<?php echo URLROOT; ?>/socio/dashboard" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Enviar solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="<?php echo URLROOT; ?>/js/socio-georef.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var callesMap = {};
    <?php foreach ($data['calles'] as $calle): ?>
    callesMap['<?php echo (int)$calle->id; ?>'] = <?php echo json_encode($calle->nombre, JSON_UNESCAPED_UNICODE); ?>;
    <?php endforeach; ?>
    var instances = typeof initSocioGeorefMaps === 'function' ? initSocioGeorefMaps(callesMap) : {};
    var inst = instances[''] || instances['default'];
    if (inst) {
        inst.loadFromValues(
            <?php echo json_encode($data['values']['latitud'] ?? ''); ?>,
            <?php echo json_encode($data['values']['longitud'] ?? ''); ?>,
            <?php echo json_encode($data['values']['link_google'] ?? ''); ?>
        );
        inst.refreshLayout();
    }
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
