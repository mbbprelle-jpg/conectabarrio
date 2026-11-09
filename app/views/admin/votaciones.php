<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$edit = $data['edit'] ?? null;
$miembros = $data['miembros'] ?? [];
$draft = $data['form_draft'] ?? null;
$usarDraft = is_array($draft);
$editElectores = $usarDraft
    ? array_map('intval', $draft['electores'] ?? [])
    : ($data['edit_electores'] ?? []);
$campo = static function (string $clave, $editValor) use ($draft, $usarDraft): string {
    if ($usarDraft) {
        return (string)($draft[$clave] ?? '');
    }
    return (string)($editValor ?? '');
};
$fechaInicio = $usarDraft
    ? (string)($draft['fecha_inicio'] ?? '')
    : (!empty($edit->fecha_inicio) ? date('Y-m-d\TH:i', strtotime($edit->fecha_inicio)) : '');
$fechaFin = $usarDraft
    ? (string)($draft['fecha_fin'] ?? '')
    : (!empty($edit->fecha_fin) ? date('Y-m-d\TH:i', strtotime($edit->fecha_fin)) : '');
$tipoVal = $campo('tipo', $edit?->tipo ?? 'votacion') ?: 'votacion';
$audienciaVal = $campo('audiencia_tipo', $edit?->audiencia_tipo ?? 'todos_socios') ?: 'todos_socios';
$vis = $campo('resultados_visibilidad', $edit?->resultados_visibilidad ?? 'al_votar') ?: 'al_votar';
$publicarMarcado = $usarDraft ? !empty($draft['publicar']) : true;
$filas = [];
if ($usarDraft) {
    foreach ($draft['opciones'] ?? [] as $row) {
        $filas[] = [
            'id' => (int)($row['id'] ?? 0),
            'texto' => (string)($row['texto'] ?? ''),
            'tipo' => (string)($row['tipo'] ?? 'ninguno'),
            'pregunta' => (string)($row['pregunta'] ?? ''),
            'lineas' => (string)($row['lineas'] ?? ''),
        ];
    }
} else {
    foreach ($data['edit_opciones'] ?? [] as $o) {
        $lineas = '';
        if (!empty($o->seguimiento_opciones)) {
            $lineas = implode("\n", array_map(static fn($s) => $s->texto, $o->seguimiento_opciones));
        }
        $filas[] = [
            'id' => (int)$o->id,
            'texto' => (string)$o->texto,
            'tipo' => (string)($o->seguimiento_tipo ?? 'ninguno'),
            'pregunta' => (string)($o->seguimiento_pregunta ?? ''),
            'lineas' => $lineas,
        ];
    }
}
while (count($filas) < 2) {
    $filas[] = ['id' => 0, 'texto' => '', 'tipo' => 'ninguno', 'pregunta' => '', 'lineas' => ''];
}
?>

<?php if (!empty($data['success'])): ?><div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div><?php endif; ?>
<?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div><?php endif; ?>
<?php if (isset($data['seguimiento_listo']) && !$data['seguimiento_listo']): ?>
<div class="alert alert-warning"><span>No se pudo actualizar la base para preguntas de seguimiento. Ejecute <code>sql/add_votacion_seguimiento.sql</code>.</span></div>
<?php endif; ?>

<div class="grid-2col" style="align-items:start;">
    <div class="card card-primary">
        <h3 class="card-title"><?php echo $edit ? 'Editar consulta' : 'Nueva votación o encuesta'; ?></h3>
        <form id="formVotacion" action="<?php echo URLROOT; ?>/admin/<?php echo $edit ? 'votacion_actualizar' : 'votacion_crear'; ?>" method="POST" novalidate>
            <div id="formErrores" class="alert alert-danger" style="display:none;"><span></span></div>
            <?php if ($edit): ?><input type="hidden" name="votacion_id" value="<?php echo (int)$edit->id; ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">Pregunta principal *</label>
                <textarea name="titulo" id="preguntaPrincipal" class="form-control" maxlength="500" rows="4"
                    placeholder="Ej. ¿Está de acuerdo con que la junta realice la actividad de Navidad usando fondos de la comunidad?"><?php echo htmlspecialchars($campo('titulo', $edit?->titulo ?? '')); ?></textarea>
                <p id="contadorPregunta" style="font-size:0.75rem;color:var(--text-muted);margin:0.35rem 0 0;">Es el texto que verán los socios. Hasta 500 caracteres.</p>
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($campo('descripcion', $edit?->descripcion ?? '')); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="votacion" <?php echo $tipoVal !== 'encuesta' ? 'selected' : ''; ?>>Votación (una opción)</option>
                    <option value="encuesta" <?php echo $tipoVal === 'encuesta' ? 'selected' : ''; ?>>Encuesta</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Quién puede votar</label>
                <select name="audiencia_tipo" class="form-control" id="audienciaTipo">
                    <option value="todos_socios" <?php echo $audienciaVal === 'todos_socios' ? 'selected' : ''; ?>>Todos los socios</option>
                    <option value="directiva" <?php echo $audienciaVal === 'directiva' ? 'selected' : ''; ?>>Solo directiva</option>
                    <option value="seleccionados" <?php echo $audienciaVal === 'seleccionados' ? 'selected' : ''; ?>>Socios seleccionados</option>
                </select>
            </div>
            <div class="form-group" id="electoresBox" style="<?php echo $audienciaVal === 'seleccionados' ? '' : 'display:none;'; ?>">
                <label class="form-label">Electores</label>
                <div style="max-height:140px;overflow:auto;border:1px solid var(--border-color);border-radius:8px;padding:0.5rem;">
                    <?php foreach ($miembros as $m): ?>
                    <label style="display:flex;gap:0.5rem;font-size:0.85rem;margin:0.25rem 0;">
                        <input type="checkbox" name="electores[]" value="<?php echo (int)$m->id; ?>"
                            <?php echo in_array((int)$m->id, $editElectores, true) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($m->nombre); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Inicio *</label>
                <input type="datetime-local" name="fecha_inicio" id="fechaInicio" class="form-control"
                    value="<?php echo htmlspecialchars($fechaInicio); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Cierre *</label>
                <input type="datetime-local" name="fecha_fin" id="fechaFin" class="form-control"
                    value="<?php echo htmlspecialchars($fechaFin); ?>">
                <p id="avisoFechas" style="display:none;font-size:0.75rem;color:#b91c1c;margin:0.35rem 0 0;"></p>
                <p style="font-size:0.75rem;color:var(--text-muted);margin:0.35rem 0 0;">Hora de Chile (Santiago). Ahora: <?php echo date('d/m/Y H:i'); ?>. Puede dejar el cierre en días o semanas.</p>
            </div>
            <div class="form-group">
                <label class="form-label">Quién ve los porcentajes</label>
                <select name="resultados_visibilidad" class="form-control">
                    <option value="al_votar" <?php echo $vis === 'al_votar' ? 'selected' : ''; ?>>El socio, en vivo, después de responder</option>
                    <option value="todos" <?php echo $vis === 'todos' ? 'selected' : ''; ?>>Todos los electores, incluso antes de responder</option>
                    <option value="directiva" <?php echo $vis === 'directiva' ? 'selected' : ''; ?>>Solo directiva y quien creó la consulta</option>
                </select>
                <p style="font-size:0.75rem;color:var(--text-muted);margin:0.35rem 0 0;">Los porcentajes nunca muestran el nombre de quien votó.</p>
            </div>
            <div class="form-group">
                <label class="form-label">Opciones de respuesta * (mínimo 2)</label>
                <p style="font-size:0.75rem;color:var(--text-muted);margin-top:0;">Si alguien elige Sí, No u otra opción, puede hacerle una segunda pregunta solo en ese caso.</p>
                <div id="opcionesList">
                <?php foreach ($filas as $i => $fila):
                    $tipoSeg = in_array($fila['tipo'], ['texto', 'opciones'], true) ? $fila['tipo'] : 'ninguno';
                ?>
                <div class="opcion-editor" style="border:1px solid var(--border-color);border-radius:8px;padding:0.65rem;margin-bottom:0.5rem;">
                    <input type="hidden" name="opcion_id[]" value="<?php echo (int)$fila['id']; ?>">
                    <input type="text" name="opciones[]" class="form-control opcion-texto" maxlength="500" placeholder="Opción <?php echo $i + 1; ?> (ej. Sí, estoy de acuerdo)"
                        value="<?php echo htmlspecialchars($fila['texto']); ?>">
                    <label class="form-label" style="margin-top:0.5rem;">Si eligen esta opción, preguntar además</label>
                    <select name="seguimiento_tipo[]" class="form-control seg-tipo">
                        <option value="ninguno" <?php echo $tipoSeg === 'ninguno' ? 'selected' : ''; ?>>No preguntar más</option>
                        <option value="texto" <?php echo $tipoSeg === 'texto' ? 'selected' : ''; ?>>Una respuesta escrita</option>
                        <option value="opciones" <?php echo $tipoSeg === 'opciones' ? 'selected' : ''; ?>>Otras alternativas</option>
                    </select>
                    <div class="seg-fields" style="margin-top:0.45rem;<?php echo $tipoSeg === 'ninguno' ? 'display:none;' : ''; ?>">
                        <input type="text" name="seguimiento_pregunta[]" class="form-control seg-pregunta" maxlength="500" placeholder="Enunciado. Ej. ¿Participaría en el comité organizador?"
                            value="<?php echo htmlspecialchars($fila['pregunta']); ?>">
                        <label class="form-label seg-lineas-label" style="margin-top:0.45rem;<?php echo $tipoSeg === 'opciones' ? '' : 'display:none;'; ?>">Alternativas, una por línea</label>
                        <textarea name="seguimiento_opciones_texto[]" class="form-control seg-lineas" rows="3" style="<?php echo $tipoSeg === 'opciones' ? '' : 'display:none;'; ?>" placeholder="Sí, participaría&#10;No, por ahora no"><?php echo htmlspecialchars($fila['lineas']); ?></textarea>
                    </div>
                    <p class="opcion-ayuda" style="display:none;font-size:0.75rem;color:#b91c1c;margin:0.4rem 0 0;"></p>
                </div>
                <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" id="agregarOpcion">Agregar opción</button>
            </div>
            <?php if (!$edit): ?>
            <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;margin-bottom:1rem;">
                <input type="checkbox" name="publicar" value="1" <?php echo $publicarMarcado ? 'checked' : ''; ?>> Publicar de inmediato
            </label>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary"><?php echo $edit ? 'Guardar cambios' : 'Crear consulta'; ?></button>
            <?php if ($edit): ?><a href="<?php echo URLROOT; ?>/admin/votaciones" class="btn btn-secondary">Cancelar</a><?php endif; ?>
        </form>
    </div>

    <div class="card card-success">
        <h3 class="card-title">Consultas registradas</h3>
        <?php if (empty($data['votaciones'])): ?>
            <p style="color:var(--text-muted);">Aún no hay votaciones ni encuestas.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Título</th><th>Estado</th><th>Votos</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($data['votaciones'] as $v): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($v->titulo); ?></strong><br><span style="font-size:0.75rem;color:var(--text-muted);"><?php echo ucfirst($v->tipo); ?></span></td>
                        <td><span class="badge badge-<?php echo $v->estado === 'activa' ? 'success' : ($v->estado === 'cerrada' ? 'secondary' : 'warning'); ?>"><?php echo htmlspecialchars($v->estado); ?></span></td>
                        <td><?php echo (int)$v->total_votos; ?></td>
                        <td>
                            <a href="<?php echo URLROOT; ?>/admin/votacion_ver/<?php echo (int)$v->id; ?>" class="btn btn-secondary btn-sm">Ver</a>
                            <?php if (in_array($v->estado, ['borrador', 'activa', 'cerrada'], true)): ?>
                            <a href="<?php echo URLROOT; ?>/admin/votaciones?editar=<?php echo (int)$v->id; ?>" class="btn btn-secondary btn-sm"><?php echo $v->estado === 'cerrada' ? 'Reabrir / editar' : 'Editar'; ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function marcarCampo(el, mal) {
    if (!el) return;
    el.style.borderColor = mal ? '#b91c1c' : '';
}

function lineasDe(texto) {
    return String(texto || '').split(/\r\n|\r|\n/).map(function (l) { return l.trim(); }).filter(Boolean);
}

function syncSeguimiento(editor) {
    var tipo = editor.querySelector('.seg-tipo').value;
    var fields = editor.querySelector('.seg-fields');
    var lineas = editor.querySelector('.seg-lineas');
    var etiqueta = editor.querySelector('.seg-lineas-label');
    fields.style.display = tipo === 'ninguno' ? 'none' : '';
    lineas.style.display = tipo === 'opciones' ? '' : 'none';
    if (etiqueta) etiqueta.style.display = tipo === 'opciones' ? '' : 'none';
}

function revisarOpcion(editor) {
    var texto = editor.querySelector('.opcion-texto').value.trim();
    var tipo = editor.querySelector('.seg-tipo').value;
    var pregunta = editor.querySelector('.seg-pregunta');
    var lineas = editor.querySelector('.seg-lineas');
    var ayuda = editor.querySelector('.opcion-ayuda');
    var mensaje = '';
    if (texto !== '' && tipo === 'texto' && pregunta.value.trim() === '') {
        mensaje = 'Escriba el enunciado de la pregunta adicional, o vuelva a «No preguntar más».';
        marcarCampo(pregunta, true);
    } else {
        marcarCampo(pregunta, false);
    }
    if (texto !== '' && tipo === 'opciones') {
        var partes = [];
        if (pregunta.value.trim() === '') {
            partes.push('falta el enunciado');
            marcarCampo(pregunta, true);
        } else {
            marcarCampo(pregunta, false);
        }
        var n = lineasDe(lineas.value).length;
        if (n < 2) {
            partes.push(n === 0 ? 'faltan las alternativas, una por línea' : 'falta una alternativa más, en otra línea');
            marcarCampo(lineas, true);
        } else {
            marcarCampo(lineas, false);
        }
        if (partes.length) mensaje = 'En esta opción ' + partes.join(' y ') + '.';
    } else {
        marcarCampo(lineas, false);
    }
    ayuda.style.display = mensaje ? '' : 'none';
    ayuda.textContent = mensaje;
    return mensaje;
}

function revisarFormulario() {
    var errores = [];
    var pregunta = document.getElementById('preguntaPrincipal');
    var inicio = document.getElementById('fechaInicio');
    var fin = document.getElementById('fechaFin');
    var avisoFechas = document.getElementById('avisoFechas');
    if (!pregunta.value.trim()) {
        errores.push('Escriba la pregunta principal.');
        marcarCampo(pregunta, true);
    } else {
        marcarCampo(pregunta, false);
    }
    if (!inicio.value || !fin.value) {
        errores.push('Indique la fecha de inicio y la de cierre.');
        avisoFechas.style.display = '';
        avisoFechas.textContent = 'Indique inicio y cierre.';
    } else if (fin.value <= inicio.value) {
        errores.push('El cierre debe ser posterior al inicio.');
        avisoFechas.style.display = '';
        avisoFechas.textContent = 'El cierre debe ser posterior al inicio.';
    } else {
        avisoFechas.style.display = 'none';
    }
    var conTexto = 0;
    document.querySelectorAll('.opcion-editor').forEach(function (editor) {
        if (editor.querySelector('.opcion-texto').value.trim() !== '') conTexto++;
        var msg = revisarOpcion(editor);
        if (msg) errores.push(msg);
    });
    if (conTexto < 2) errores.push('Escriba al menos 2 opciones de respuesta.');
    return errores;
}

function mostrarErrores(errores) {
    var caja = document.getElementById('formErrores');
    if (!errores.length) {
        caja.style.display = 'none';
        return;
    }
    caja.style.display = '';
    caja.querySelector('span').textContent = errores[0];
    var malo = document.querySelector('#formVotacion [style*="rgb(185, 28, 28)"], #formVotacion [style*="#b91c1c"]');
    (malo || caja).scrollIntoView({ behavior: 'smooth', block: 'center' });
}

document.getElementById('audienciaTipo')?.addEventListener('change', function() {
    document.getElementById('electoresBox').style.display = this.value === 'seleccionados' ? '' : 'none';
});

document.getElementById('preguntaPrincipal')?.addEventListener('input', function () {
    var n = this.value.length;
    var contador = document.getElementById('contadorPregunta');
    contador.textContent = n + ' de 500 caracteres.';
    contador.style.color = n > 500 ? '#b91c1c' : '';
    marcarCampo(this, !this.value.trim());
});

document.getElementById('opcionesList')?.addEventListener('input', function (event) {
    var editor = event.target.closest('.opcion-editor');
    if (editor) revisarOpcion(editor);
});
document.getElementById('opcionesList')?.addEventListener('change', function (event) {
    var editor = event.target.closest('.opcion-editor');
    if (!editor) return;
    if (event.target.classList.contains('seg-tipo')) syncSeguimiento(editor);
    revisarOpcion(editor);
});

document.getElementById('agregarOpcion')?.addEventListener('click', function () {
    var list = document.getElementById('opcionesList');
    var n = list.querySelectorAll('.opcion-editor').length + 1;
    var wrap = document.createElement('div');
    wrap.className = 'opcion-editor';
    wrap.style.cssText = 'border:1px solid var(--border-color);border-radius:8px;padding:0.65rem;margin-bottom:0.5rem;';
    wrap.innerHTML = ''
        + '<input type="hidden" name="opcion_id[]" value="0">'
        + '<input type="text" name="opciones[]" class="form-control opcion-texto" maxlength="500" placeholder="Opción ' + n + '">'
        + '<label class="form-label" style="margin-top:0.5rem;">Si eligen esta opción, preguntar además</label>'
        + '<select name="seguimiento_tipo[]" class="form-control seg-tipo">'
        + '<option value="ninguno" selected>No preguntar más</option>'
        + '<option value="texto">Una respuesta escrita</option>'
        + '<option value="opciones">Otras alternativas</option>'
        + '</select>'
        + '<div class="seg-fields" style="margin-top:0.45rem;display:none;">'
        + '<input type="text" name="seguimiento_pregunta[]" class="form-control seg-pregunta" maxlength="500" placeholder="Enunciado. Ej. ¿Participaría en el comité organizador?">'
        + '<label class="form-label seg-lineas-label" style="margin-top:0.45rem;display:none;">Alternativas, una por línea</label>'
        + '<textarea name="seguimiento_opciones_texto[]" class="form-control seg-lineas" rows="3" style="display:none;" placeholder="Sí, participaría&#10;No, por ahora no"></textarea>'
        + '</div>'
        + '<p class="opcion-ayuda" style="display:none;font-size:0.75rem;color:#b91c1c;margin:0.4rem 0 0;"></p>';
    list.appendChild(wrap);
});

document.getElementById('formVotacion')?.addEventListener('submit', function (event) {
    var errores = revisarFormulario();
    if (!errores.length) return;
    event.preventDefault();
    mostrarErrores(errores);
});

document.querySelectorAll('.opcion-editor').forEach(revisarOpcion);
</script>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
