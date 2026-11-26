<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php $c = $data['cert']; ?>
<?php if (!empty($data['success'])): ?><div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div><?php endif; ?>
<?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div><?php endif; ?>

<?php if ($c->estado !== 'pendiente'): ?>
    <div class="card card-primary">
        <p>Esta solicitud está <?php echo $c->estado === 'emitido' ? 'emitida con folio ' . (int)$c->correlativo : 'rechazada'; ?>.</p>
        <?php if ($c->estado === 'emitido'): ?>
            <a class="btn btn-primary" href="<?php echo URLROOT; ?>/admin/certificado_imprimir/<?php echo (int)$c->id; ?>">Imprimir</a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="<?php echo URLROOT; ?>/admin/certificados">Volver</a>
    </div>
<?php else: ?>
<div class="grid-2col" style="align-items:start;">
    <div class="card card-primary">
        <h3 class="card-title">Datos del certificado</h3>
        <p style="font-size:0.8rem;color:var(--text-muted);">Motivo indicado: se puede corregir antes de imprimir. Guarde los cambios y después registre el pago.</p>
        <form method="POST" action="<?php echo URLROOT; ?>/admin/certificado_corregir">
            <input type="hidden" name="id" value="<?php echo (int)$c->id; ?>">
            <div class="form-group">
                <label class="form-label">Nombre y apellidos</label>
                <input type="text" name="beneficiario_nombre" class="form-control" required maxlength="200" value="<?php echo htmlspecialchars((string)$c->beneficiario_nombre); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">RUT</label>
                <input type="text" name="beneficiario_rut" id="rutRevision" class="form-control" required maxlength="12" value="<?php echo htmlspecialchars((string)$c->beneficiario_rut); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Dirección</label>
                <input type="text" name="direccion" class="form-control" required maxlength="255" value="<?php echo htmlspecialchars((string)$c->direccion); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Motivo</label>
                <textarea name="motivo" class="form-control" rows="3" required maxlength="500"><?php echo htmlspecialchars((string)$c->motivo); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Valor</label>
                <select name="tarifa" class="form-control">
                    <option value="socio" <?php echo !empty($c->es_tarifa_socio) ? 'selected' : ''; ?>>Socio · $1.000</option>
                    <option value="publico" <?php echo empty($c->es_tarifa_socio) ? 'selected' : ''; ?>>No socio · $2.000</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Guardar correcciones</button>
        </form>
    </div>
    <div>
        <div class="card card-success" style="margin-bottom:1rem;">
            <h3 class="card-title">Registrar pago e imprimir</h3>
            <p style="font-size:0.85rem;">Se cobrará <strong>$<?php echo number_format((int)$c->monto, 0, ',', '.'); ?></strong> y el folio se asigna al imprimir. El ingreso queda en Movimientos.</p>
            <form method="POST" action="<?php echo URLROOT; ?>/admin/certificado_emitir">
                <input type="hidden" name="id" value="<?php echo (int)$c->id; ?>">
                <div class="form-group">
                    <label class="form-label">Fecha del certificado</label>
                    <input type="date" name="fecha_certificado" class="form-control" required max="<?php echo htmlspecialchars((string)$data['hoy']); ?>" value="<?php echo htmlspecialchars((string)$data['hoy']); ?>">
                    <p style="font-size:0.75rem;color:var(--text-muted);margin:0.35rem 0 0;">Por defecto es hoy. Puede elegir una fecha anterior. No una futura.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Cómo pagó</label>
                    <select name="metodo_pago" class="form-control" required>
                        <option value="">Seleccione</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Registrar pago e imprimir</button>
            </form>
        </div>
        <div class="card">
            <h3 class="card-title">Rechazar</h3>
            <form method="POST" action="<?php echo URLROOT; ?>/admin/certificado_rechazar">
                <input type="hidden" name="id" value="<?php echo (int)$c->id; ?>">
                <div class="form-group">
                    <label class="form-label">Nota para el socio</label>
                    <input type="text" name="motivo_rechazo" class="form-control" maxlength="300">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Rechazar solicitud</button>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var rut = document.getElementById('rutRevision');
    if (!rut) return;
    rut.addEventListener('input', function () {
        var v = rut.value.replace(/[^0-9kK]/g, '').toUpperCase();
        if (v.length > 9) v = v.slice(0, 9);
        rut.value = v.length > 1 ? v.slice(0, -1) + '-' + v.slice(-1) : v;
    });
})();
</script>
<?php endif; ?>
<p style="margin-top:1rem;"><a href="<?php echo URLROOT; ?>/admin/certificados">Volver al listado</a></p>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
