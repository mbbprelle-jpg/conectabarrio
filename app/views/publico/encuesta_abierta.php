<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
require_once APPROOT . '/core/SocioInput.php';
$v = $data['votacion'];
$socio = $data['socio'] ?? null;
$old = $data['old'] ?? [];
$ok = $data['ok'] ?? null;
$activa = !empty($data['activa']);
$inscribe = !empty($v->inscribe_ninos);
$valor = static function (string $clave, string $defecto = '') use ($old): string {
    return (string)($old[$clave] ?? $defecto);
};
$nombreSocio = $valor('nombres', $socio ? (string)($socio->nombre ?? '') : '');
$apPat = $valor('apellido_paterno', $socio ? (string)($socio->apellido_paterno ?? '') : '');
$apMat = $valor('apellido_materno', $socio ? (string)($socio->apellido_materno ?? '') : '');
$email = $valor('email', $socio ? (string)($socio->email ?? '') : '');
$telefono = $valor('telefono', $socio ? (SocioInput::formatTelefonoDisplay($socio->telefono ?? '') ?: '') : '');
$cantidad = (int)$valor('cantidad_ninos', '1');
if ($cantidad < 1) {
    $cantidad = 1;
}
$edadesOld = is_array($old['edad'] ?? null) ? array_values($old['edad']) : [];
$generosOld = is_array($old['genero'] ?? null) ? array_values($old['genero']) : [];
?>
<div style="max-width:720px;margin:2rem auto;padding:0 1rem 3rem;">
    <p style="color:var(--text-muted);margin:0 0 0.35rem;"><?php echo htmlspecialchars((string)($v->junta_nombre ?? '')); ?></p>
    <h1 style="font-size:1.45rem;line-height:1.3;margin:0 0 0.75rem;"><?php echo htmlspecialchars((string)$v->titulo); ?></h1>

    <?php if ($ok): ?>
        <div class="card card-primary">
            <div class="alert alert-success"><span>Su inscripción quedó registrada<?php echo !empty($ok['mail_ok']) ? ' y enviamos un correo de confirmación a ' . htmlspecialchars((string)$ok['email']) : ''; ?>.</span></div>
            <p>Gracias, <strong><?php echo htmlspecialchars((string)$ok['nombre']); ?></strong>.</p>
            <?php if ($inscribe): ?>
                <p>Niños y niñas registrados: <strong><?php echo (int)$ok['ninos']; ?></strong>.</p>
            <?php endif; ?>
            <?php if (empty($ok['mail_ok'])): ?>
                <p style="color:var(--text-muted);font-size:0.9rem;">La inscripción sí quedó guardada. El correo de confirmación no pudo enviarse en este momento.</p>
            <?php endif; ?>
        </div>
    <?php elseif (!$activa): ?>
        <div class="card card-primary">
            <?php $inicio = strtotime((string)$v->fecha_inicio); ?>
            <?php if ($inicio !== false && $inicio > time()): ?>
                <p>Esta encuesta abre el <?php echo date('d/m/Y H:i', $inicio); ?> (hora de Chile).</p>
            <?php else: ?>
                <p>Esta encuesta ya está cerrada.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card card-primary">
            <?php if (!empty($v->descripcion)): ?>
                <p style="color:var(--text-muted);"><?php echo nl2br(htmlspecialchars((string)$v->descripcion)); ?></p>
            <?php endif; ?>
            <?php if (!empty($data['error'])): ?>
                <div class="alert alert-danger"><span><?php echo htmlspecialchars((string)$data['error']); ?></span></div>
            <?php endif; ?>

            <?php $paso = (string)($data['paso'] ?? 'formulario'); ?>
            <?php if ($paso === 'pregunta'): ?>
                <p style="font-size:0.95rem;">Antes de responder, indique si es socio de esta organización.</p>
                <div style="display:flex;flex-direction:column;gap:0.65rem;margin-top:1rem;">
                    <a class="btn btn-secondary" href="<?php echo htmlspecialchars((string)$data['invitado_url']); ?>">No soy socio</a>
                    <a class="btn btn-primary" href="<?php echo htmlspecialchars((string)$data['socio_url']); ?>">Sí, soy socio</a>
                </div>
            <?php elseif ($paso === 'socio'): ?>
                <p style="font-size:0.95rem;">Puede ingresar para completar la encuesta con el nombre, correo y teléfono que ya tiene la organización. Si prefiere no ingresar, continúe y escriba esos datos usted.</p>
                <?php if (!empty($data['sesion_otra'])): ?>
                    <p style="font-size:0.85rem;color:var(--text-muted);">La sesión actual no corresponde a esta organización. Ingrese con el RUT del socio o continúe escribiendo los datos.</p>
                <?php endif; ?>
                <div style="display:flex;flex-direction:column;gap:0.65rem;margin-top:1rem;">
                    <a class="btn btn-primary" href="<?php echo htmlspecialchars((string)$data['login_url']); ?>">Ingresar y completar con mis datos</a>
                    <a class="btn btn-secondary" href="<?php echo htmlspecialchars((string)$data['invitado_url']); ?>">Seguir sin ingresar</a>
                    <a href="<?php echo htmlspecialchars((string)$data['reiniciar_url']); ?>" style="font-size:0.85rem;">Volver</a>
                </div>
            <?php else: ?>
            <?php if ($socio): ?>
                <p style="font-size:0.9rem;">Confirme o corrija su nombre, correo y teléfono. Si cambia el correo o el teléfono, se actualiza su ficha.</p>
            <?php else: ?>
                <p style="font-size:0.9rem;">Indique su nombre y apellidos, correo y teléfono.</p>
            <?php endif; ?>
            <p style="font-size:0.85rem;margin-top:0;"><a href="<?php echo htmlspecialchars((string)$data['reiniciar_url']); ?>">Cambiar si soy socio</a></p>

            <form method="POST" action="<?php echo URLROOT; ?>/votacion/l/<?php echo rawurlencode((string)$v->token_publico); ?>" id="formEncuesta">
                <?php if ($socio): ?>
                    <div class="form-group">
                        <label class="form-label">Nombres *</label>
                        <input type="text" name="nombres" class="form-control" required maxlength="120" value="<?php echo htmlspecialchars($nombreSocio); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido paterno *</label>
                        <input type="text" name="apellido_paterno" class="form-control" required maxlength="80" value="<?php echo htmlspecialchars($apPat); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido materno</label>
                        <input type="text" name="apellido_materno" class="form-control" maxlength="80" value="<?php echo htmlspecialchars($apMat); ?>">
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label class="form-label">Nombre y apellidos *</label>
                        <input type="text" name="nombre_apellidos" class="form-control" required maxlength="200" value="<?php echo htmlspecialchars($valor('nombre_apellidos')); ?>" placeholder="Ej. Ana Karina Pérez Soto">
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label class="form-label">Correo *</label>
                    <input type="email" name="email" class="form-control" required maxlength="150" value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono *</label>
                    <input type="tel" name="telefono" class="form-control" required maxlength="20" value="<?php echo htmlspecialchars($telefono); ?>" placeholder="+56 9 1234 5678">
                </div>

                <?php if ($inscribe): ?>
                    <div class="form-group">
                        <label class="form-label">¿Cuántos niños o niñas va a registrar? *</label>
                        <input type="text" name="cantidad_ninos" id="cantidadNinos" class="form-control" inputmode="numeric" maxlength="2" required value="<?php echo $cantidad; ?>" autocomplete="off">
                        <p style="font-size:0.75rem;color:var(--text-muted);margin:0.35rem 0 0;">Por cada uno se pide la edad y el género: Femenino, Masculino o No responder.</p>
                    </div>
                    <div id="ninosBox"></div>
                <?php else: ?>
                    <?php foreach ($data['opciones'] as $o):
                        $tipoSeg = $o->seguimiento_tipo ?? 'ninguno';
                        $tieneSeg = $tipoSeg !== 'ninguno' && trim((string)($o->seguimiento_pregunta ?? '')) !== '';
                    ?>
                    <div class="opcion-block" style="margin-bottom:0.65rem;">
                        <label class="opcion-label">
                            <input type="radio" name="opcion_id" value="<?php echo (int)$o->id; ?>" required <?php echo (int)$valor('opcion_id') === (int)$o->id ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars($o->texto); ?></span>
                        </label>
                        <?php if ($tieneSeg): ?>
                        <div class="followup">
                            <p style="margin:0 0 0.5rem;font-size:0.9rem;"><strong><?php echo htmlspecialchars($o->seguimiento_pregunta); ?></strong></p>
                            <?php if ($tipoSeg === 'texto'): ?>
                                <textarea name="seguimiento_texto[<?php echo (int)$o->id; ?>]" class="form-control seg-input" rows="3"></textarea>
                            <?php else: ?>
                                <?php foreach ($o->seguimiento_opciones ?? [] as $s): ?>
                                <label style="display:flex;gap:0.6rem;margin-bottom:0.45rem;cursor:pointer;">
                                    <input type="radio" class="seg-input" name="seguimiento_opcion_id[<?php echo (int)$o->id; ?>]" value="<?php echo (int)$s->id; ?>">
                                    <span><?php echo htmlspecialchars($s->texto); ?></span>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (($v->tipo ?? '') === 'encuesta'): ?>
                    <div class="form-group">
                        <label class="form-label">Comentario adicional (opcional)</label>
                        <textarea name="respuesta_texto" class="form-control" rows="3"><?php echo htmlspecialchars($valor('respuesta_texto')); ?></textarea>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">Enviar inscripción</button>
            </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php if ($activa && !$ok && $inscribe && (($data['paso'] ?? '') === 'formulario')): ?>
<script>
(function () {
    var cantidad = document.getElementById('cantidadNinos');
    var box = document.getElementById('ninosBox');
    var edades = <?php echo json_encode(array_map('strval', $edadesOld), JSON_UNESCAPED_UNICODE); ?>;
    var generos = <?php echo json_encode(array_map('strval', $generosOld), JSON_UNESCAPED_UNICODE); ?>;
    function esc(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }
    function fila(i, edad, genero) {
        var wrap = document.createElement('div');
        wrap.className = 'nino-row';
        wrap.style.cssText = 'border:1px solid var(--border-color);border-radius:8px;padding:0.75rem;margin-bottom:0.65rem;';
        wrap.innerHTML = '<p style="margin:0 0 0.5rem;font-weight:650;">Niño o niña ' + (i + 1) + '</p>'
            + '<div class="form-group"><label class="form-label">Edad *</label><input type="number" name="edad[]" class="form-control" min="0" max="17" required value="' + esc(edad) + '"></div>'
            + '<div class="form-group" style="margin-bottom:0;"><label class="form-label">Género *</label><select name="genero[]" class="form-control" required>'
            + '<option value="">Seleccione</option>'
            + '<option value="femenino"' + (genero === 'femenino' ? ' selected' : '') + '>Femenino</option>'
            + '<option value="masculino"' + (genero === 'masculino' ? ' selected' : '') + '>Masculino</option>'
            + '<option value="no_responder"' + (genero === 'no_responder' ? ' selected' : '') + '>No responder</option>'
            + '</select></div>';
        return wrap;
    }
    function leerNumero() {
        var raw = String(cantidad.value).trim();
        if (!/^\d+$/.test(raw)) return 0;
        return parseInt(raw, 10);
    }
    function dibujar() {
        var n = leerNumero();
        if (n < 1) return;
        if (n > 12) n = 12;
        var previas = [];
        box.querySelectorAll('.nino-row').forEach(function (row) {
            previas.push({
                edad: row.querySelector('input').value,
                genero: row.querySelector('select').value
            });
        });
        if (previas.length === n) return;
        box.innerHTML = '';
        for (var i = 0; i < n; i++) {
            var edad = previas[i] ? previas[i].edad : (edades[i] || '');
            var genero = previas[i] ? previas[i].genero : (generos[i] || '');
            box.appendChild(fila(i, edad, genero));
        }
        edades = [];
        generos = [];
    }
    cantidad.addEventListener('input', dibujar);
    cantidad.addEventListener('blur', function () {
        var n = leerNumero();
        if (n < 1) n = box.querySelectorAll('.nino-row').length || 1;
        if (n > 12) n = 12;
        cantidad.value = String(n);
        dibujar();
    });
    dibujar();
})();
</script>
<?php endif; ?>
<?php if ($activa && !$ok && !$inscribe && (($data['paso'] ?? '') === 'formulario')): ?>
<style>
.opcion-label { display:flex; align-items:flex-start; gap:0.75rem; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; }
.opcion-block .followup { display:none; margin:0.5rem 0 0.25rem 0.35rem; padding:0.75rem; border-left:2px solid var(--primary, #4f46e5); }
.opcion-block.is-selected .followup { display:block; }
</style>
<script>
(function () {
    var blocks = document.querySelectorAll('.opcion-block');
    function sync() {
        var selected = document.querySelector('input[name="opcion_id"]:checked');
        blocks.forEach(function (block) {
            var radio = block.querySelector('input[name="opcion_id"]');
            var on = !!(radio && selected && radio.value === selected.value);
            block.classList.toggle('is-selected', on);
            block.querySelectorAll('.seg-input').forEach(function (field) { field.required = on; });
        });
    }
    document.querySelectorAll('input[name="opcion_id"]').forEach(function (radio) {
        radio.addEventListener('change', sync);
    });
    sync();
})();
</script>
<?php endif; ?>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
