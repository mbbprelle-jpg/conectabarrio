<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/AuthContext.php'; ?>

<?php
function cbTraspasoSocioLabel($s): string {
    $nombre = trim(implode(' ', array_filter([
        $s->nombre ?? '',
        $s->apellido_paterno ?? '',
        $s->apellido_materno ?? '',
    ])));
    $rut = $s->rut ?? '';
    return trim($nombre . ($rut !== '' ? ' — ' . $rut : ''));
}
$origenId = (int)($data['origen_id'] ?? 0);
$cuotas = $data['cuotas_origen'] ?? [];
?>

<?php require APPROOT . '/views/partials/maestro_finanzas_banner.php'; ?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<div class="card card-primary" style="margin-bottom:1rem;">
    <h3 style="font-family:var(--font-heading); font-size:1.1rem; margin:0 0 0.5rem;">¿Para qué sirve?</h3>
    <p style="margin:0; font-size:0.88rem; color:var(--text-muted); line-height:1.5;">
        Si registró cuotas de varios meses a un socio por error (por ejemplo 6 meses a uno cuando eran 3 y 3),
        aquí puede <strong>traspasar</strong> esos meses al socio correcto.
        <strong>No se modifica el monto ni la fecha contable</strong>, por lo que los cierres mensuales ya hechos
        mantienen el mismo total de ingresos. Solo cambia a qué socio queda asignada la cuota.
    </p>
    <div style="margin-top:0.85rem;">
        <a href="<?php echo URLROOT; ?>/admin/finanzas" class="btn btn-secondary btn-sm">Volver a Movimientos</a>
        <a href="<?php echo URLROOT; ?>/admin/reporte_movimientos" class="btn btn-secondary btn-sm">Ver reporte</a>
    </div>
</div>

<div class="card card-primary">
    <form method="get" action="<?php echo URLROOT; ?>/admin/traspasar_cuotas" style="display:flex; flex-wrap:wrap; gap:0.85rem; align-items:flex-end; margin-bottom:1.25rem;">
        <div class="form-group" style="margin:0; min-width:260px; flex:1;">
            <label class="form-label" for="origen">1. Socio de origen (quien tiene las cuotas mal asignadas)</label>
            <select name="origen" id="origen" class="form-control" required onchange="this.form.submit()">
                <option value="">-- Seleccionar socio --</option>
                <?php foreach (($data['socios'] ?? []) as $s): ?>
                    <option value="<?php echo (int)$s->id; ?>" <?php echo $origenId === (int)$s->id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(cbTraspasoSocioLabel($s)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <noscript><button type="submit" class="btn btn-secondary">Cargar cuotas</button></noscript>
    </form>

    <?php if ($origenId <= 0): ?>
        <p style="color:var(--text-muted); margin:0;">Seleccione el socio de origen para ver sus cuotas.</p>
    <?php elseif (empty($cuotas)): ?>
        <p style="color:var(--text-muted); margin:0;">Este socio no tiene cuotas registradas para traspasar.</p>
    <?php else: ?>
        <form method="post" action="<?php echo URLROOT; ?>/admin/traspasar_cuotas_aplicar" id="formTraspaso"
              onsubmit="return confirm('¿Confirma el traspaso de las cuotas seleccionadas al socio destino? Esta acción reasigna el padrón de cuotas.');">
            <input type="hidden" name="origen_id" value="<?php echo $origenId; ?>">

            <div class="form-group">
                <label class="form-label">2. Seleccione las cuotas (meses) a traspasar</label>
                <div style="border:1px solid var(--border-color); border-radius:10px; padding:0.75rem; max-height:320px; overflow:auto;">
                    <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.65rem; font-size:0.85rem;">
                        <input type="checkbox" id="checkAllCuotas"> Seleccionar todas
                    </label>
                    <?php foreach ($cuotas as $c): ?>
                        <label style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding:0.45rem 0.35rem; border-top:1px solid rgba(255,255,255,0.06); font-size:0.85rem;">
                            <span style="display:flex; align-items:center; gap:0.5rem;">
                                <input type="checkbox" name="cuota_ids[]" value="<?php echo (int)$c->id; ?>" class="cuota-check">
                                <strong><?php echo htmlspecialchars($c->mes_pagado ?? '—'); ?></strong>
                                <span style="color:var(--text-muted);"><?php echo htmlspecialchars($c->categoria ?? ''); ?></span>
                            </span>
                            <span style="white-space:nowrap;">
                                <?php if (($c->categoria ?? '') === 'Cuota Condonada'): ?>
                                    <span style="color:var(--warning);">Exento</span>
                                <?php else: ?>
                                    $<?php echo number_format((int)$c->monto, 0, ',', '.'); ?>
                                <?php endif; ?>
                                <span style="color:var(--text-muted); font-size:0.75rem; margin-left:0.4rem;">
                                    (fecha <?php echo !empty($c->fecha) ? date('d-m-Y', strtotime($c->fecha)) : '—'; ?>)
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="destino_id">3. Socio de destino (quien debió tener esas cuotas)</label>
                <select name="destino_id" id="destino_id" class="form-control" required>
                    <option value="">-- Seleccionar socio destino --</option>
                    <?php foreach (($data['socios'] ?? []) as $s): ?>
                        <?php if ((int)$s->id === $origenId) continue; ?>
                        <option value="<?php echo (int)$s->id; ?>"><?php echo htmlspecialchars(cbTraspasoSocioLabel($s)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="motivo">Motivo / nota (opcional)</label>
                <input type="text" name="motivo" id="motivo" class="form-control" maxlength="200"
                       placeholder="Ej: Error al registrar: 3 meses correspondían a otro socio">
            </div>

            <button type="submit" class="btn btn-primary">Traspasar cuotas seleccionadas</button>
        </form>
    <?php endif; ?>
</div>

<script>
(function () {
    var all = document.getElementById('checkAllCuotas');
    if (!all) return;
    all.addEventListener('change', function () {
        document.querySelectorAll('.cuota-check').forEach(function (cb) {
            cb.checked = all.checked;
        });
    });
})();
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
