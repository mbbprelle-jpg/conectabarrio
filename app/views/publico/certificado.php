<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$junta = $data['junta'] ?? null;
$old = $data['old'] ?? [];
$valor = static function (string $clave) use ($old): string {
    return htmlspecialchars((string)($old[$clave] ?? ''));
};
?>
<div style="max-width:720px;margin:2rem auto;padding:0 1rem 3rem;">
    <h1 style="font-size:1.45rem;margin:0 0 0.4rem;">Certificado de residencia</h1>
    <?php if ($junta): ?><p style="color:var(--text-muted);margin-top:0;"><?php echo htmlspecialchars((string)$junta->nombre); ?></p><?php endif; ?>

    <?php if (!empty($data['ok'])): ?>
        <div class="card card-primary">
            <div class="alert alert-success"><span>Recibimos su solicitud. La directiva revisará los datos y emitirá el certificado.</span></div>
            <p>El valor es $1.000 si el RUT corresponde a un socio y $2.000 si no lo es. El pago se registra cuando se imprime el documento.</p>
        </div>
    <?php elseif (!$junta): ?>
        <div class="card card-primary"><p><?php echo htmlspecialchars((string)($data['error'] ?? 'Enlace no disponible.')); ?></p></div>
    <?php else: ?>
        <div class="card card-primary">
            <p>Complete sus datos. La directiva validará la información antes de imprimir el certificado.</p>
            <?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars((string)$data['error']); ?></span></div><?php endif; ?>
            <form method="POST" action="<?php echo URLROOT; ?>/publico/certificado_guardar">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars((string)$junta->certificado_token); ?>">
                <div class="form-group">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" name="nombre" class="form-control" required maxlength="200" value="<?php echo $valor('nombre'); ?>" placeholder="Nombre y apellidos">
                </div>
                <div class="form-group">
                    <label class="form-label">RUT *</label>
                    <input type="text" name="rut" id="rutPublico" class="form-control" required maxlength="12" value="<?php echo $valor('rut'); ?>" placeholder="11111111-1" autocomplete="off">
                    <p style="font-size:0.75rem;color:var(--text-muted);margin:0.35rem 0 0;">Se escribe solo y queda en formato 11111111-1.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Calle *</label>
                    <select name="calle_id" class="form-control" required>
                        <option value="">Seleccione la calle</option>
                        <?php foreach ($data['calles'] as $calle): ?>
                            <option value="<?php echo (int)$calle->id; ?>" <?php echo (string)($old['calle_id'] ?? '') === (string)$calle->id ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$calle->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Número del domicilio *</label>
                    <input type="text" name="numero" class="form-control" required maxlength="20" value="<?php echo $valor('numero'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Motivo *</label>
                    <textarea name="motivo" class="form-control" rows="3" required maxlength="500" placeholder="¿Para qué necesita presentar el certificado?"><?php echo $valor('motivo'); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar solicitud</button>
            </form>
        </div>
    <?php endif; ?>
</div>
<script>
(function () {
    var rut = document.getElementById('rutPublico');
    if (!rut) return;
    rut.addEventListener('input', function () {
        var v = rut.value.replace(/[^0-9kK]/g, '').toUpperCase();
        if (v.length > 9) v = v.slice(0, 9);
        rut.value = v.length > 1 ? v.slice(0, -1) + '-' + v.slice(-1) : v;
    });
})();
</script>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
