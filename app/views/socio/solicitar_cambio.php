<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/SocioInput.php'; ?>

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
    <div class="alert alert-info" style="margin-bottom: 1.5rem;">
        Ya tiene una solicitud de cambio en revisión desde el
        <?php echo !empty($data['cambio_pendiente']->created_at) ? date('d-m-Y H:i', strtotime($data['cambio_pendiente']->created_at)) : '—'; ?>.
        Un administrador debe aprobarla antes de que se apliquen los cambios.
    </div>
<?php endif; ?>

<div class="card card-primary" style="max-width: 720px;">
    <h3 class="card-title" style="margin-bottom: 0.5rem;">Datos que puede solicitar actualizar</h3>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">
        RUT, nombres y número de socio no se modifican desde aquí. Los demás campos quedarán pendientes hasta que la directiva los apruebe.
    </p>

    <form action="<?php echo URLROOT; ?>/socio/enviar_solicitud" method="POST">
        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label">RUT</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['socio']->rut ?? ''); ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars(trim(($data['socio']->nombre ?? '') . ' ' . ($data['socio']->apellido_paterno ?? ''))); ?>" disabled>
            </div>
        </div>

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

        <?php
        $prefix = '';
        $values = $data['values'];
        $required = false;
        require APPROOT . '/views/partials/socio_demografia_fields.php';
        ?>

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

        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
            <a href="<?php echo URLROOT; ?>/socio/dashboard" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Enviar solicitud</button>
        </div>
    </form>
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
