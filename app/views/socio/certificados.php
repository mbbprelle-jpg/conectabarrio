<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$socio = $data['socio'];
$direccion = (string)($data['direccion'] ?? '');
?>
<?php if (!empty($data['success'])): ?><div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div><?php endif; ?>
<?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div><?php endif; ?>

<div class="grid-2col" style="align-items:start;">
    <div class="card card-primary">
        <h3 class="card-title">Nueva solicitud</h3>
        <p style="font-size:0.85rem;color:var(--text-muted);">El certificado lo revisa e imprime presidencia o secretaría. El valor es $1.000 si es socio y $2.000 si no lo es. El pago se registra al imprimir.</p>
        <?php if ($direccion === ''): ?>
            <div class="alert alert-warning"><span>Su ficha no tiene dirección. Actualícela antes de solicitar el certificado.</span></div>
        <?php else: ?>
        <form method="POST" action="<?php echo URLROOT; ?>/socio/certificado_solicitar" id="formCertificado">
            <div class="form-group">
                <label class="form-label">¿Para quién es el certificado?</label>
                <select name="para" id="paraCert" class="form-control">
                    <option value="si_mismo">Para mí</option>
                    <option value="domicilio">Para una persona de mi domicilio</option>
                </select>
            </div>
            <div id="datosPropios">
                <div class="form-group">
                    <label class="form-label">Nombre y apellidos</label>
                    <input class="form-control" value="<?php echo htmlspecialchars((string)$data['nombre']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">RUT</label>
                    <input class="form-control" value="<?php echo htmlspecialchars((string)$data['rut']); ?>" readonly>
                </div>
            </div>
            <div id="datosDomicilio" style="display:none;">
                <div class="form-group">
                    <label class="form-label">Nombre y apellidos *</label>
                    <input type="text" name="nombre" id="nombreDomicilio" class="form-control" maxlength="200" placeholder="Nombre y apellidos">
                </div>
                <div class="form-group">
                    <label class="form-label">RUT *</label>
                    <input type="text" name="rut" id="rutDomicilio" class="form-control" maxlength="12" inputmode="text" placeholder="11111111-1" autocomplete="off">
                    <p style="font-size:0.75rem;color:var(--text-muted);margin:0.35rem 0 0;">Se ordena solo como 11111111-1 y se comprueba el dígito verificador.</p>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Dirección</label>
                <input class="form-control" value="<?php echo htmlspecialchars($direccion); ?>" readonly>
                <p style="font-size:0.75rem;color:var(--text-muted);margin:0.35rem 0 0;">Es el domicilio registrado en su ficha. Si el certificado es para otra persona de la casa, se usa la misma dirección.</p>
            </div>
            <div class="form-group">
                <label class="form-label">Motivo: ¿para qué se presenta? *</label>
                <textarea name="motivo" class="form-control" rows="3" maxlength="500" required placeholder="Ej. trámite municipal, colegio, subsidio"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Enviar solicitud</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="card card-success">
        <h3 class="card-title">Mis solicitudes</h3>
        <?php if (empty($data['solicitudes'])): ?>
            <p style="color:var(--text-muted);">Todavía no ha pedido un certificado.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Fecha</th><th>Persona</th><th>Estado</th><th>Valor</th></tr></thead>
                <tbody>
                <?php foreach ($data['solicitudes'] as $s): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime((string)$s->created_at)); ?></td>
                        <td>
                            <?php echo htmlspecialchars((string)$s->beneficiario_nombre); ?><br>
                            <span style="font-size:0.75rem;color:var(--text-muted);"><?php echo htmlspecialchars((string)$s->beneficiario_rut); ?></span>
                        </td>
                        <td>
                            <?php if ($s->estado === 'emitido'): ?>
                                Emitido · Folio <?php echo (int)$s->correlativo; ?>
                            <?php elseif ($s->estado === 'rechazado'): ?>
                                Rechazado<?php echo $s->motivo_rechazo ? ': ' . htmlspecialchars((string)$s->motivo_rechazo) : ''; ?>
                            <?php else: ?>
                                En revisión
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format((int)$s->monto, 0, ',', '.'); ?><?php echo $s->metodo_pago ? '<br><span style="font-size:0.75rem;">' . ($s->metodo_pago === 'transferencia' ? 'Transferencia' : 'Efectivo') . '</span>' : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
(function () {
    var para = document.getElementById('paraCert');
    var propios = document.getElementById('datosPropios');
    var domicilio = document.getElementById('datosDomicilio');
    var rut = document.getElementById('rutDomicilio');
    if (!para) return;
    function sync() {
        var otro = para.value === 'domicilio';
        propios.style.display = otro ? 'none' : '';
        domicilio.style.display = otro ? '' : 'none';
        document.getElementById('nombreDomicilio').required = otro;
        rut.required = otro;
    }
    para.addEventListener('change', sync);
    rut.addEventListener('input', function () {
        var v = rut.value.replace(/[^0-9kK]/g, '').toUpperCase();
        if (v.length > 9) v = v.slice(0, 9);
        rut.value = v.length > 1 ? v.slice(0, -1) + '-' + v.slice(-1) : v;
    });
    sync();
})();
</script>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
