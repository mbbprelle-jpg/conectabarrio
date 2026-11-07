<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$edit = $data['edit'] ?? null;
$miembros = $data['miembros'] ?? [];
$editElectores = $data['edit_electores'] ?? [];
?>

<?php if (!empty($data['success'])): ?><div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div><?php endif; ?>
<?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div><?php endif; ?>
<?php if (isset($data['seguimiento_listo']) && !$data['seguimiento_listo']): ?>
<div class="alert alert-warning"><span>No se pudo actualizar la base para preguntas de seguimiento. Ejecute <code>sql/add_votacion_seguimiento.sql</code>.</span></div>
<?php endif; ?>

<div class="grid-2col" style="align-items:start;">
    <div class="card card-primary">
        <h3 class="card-title"><?php echo $edit ? 'Editar consulta' : 'Nueva votación o encuesta'; ?></h3>
        <form action="<?php echo URLROOT; ?>/admin/<?php echo $edit ? 'votacion_actualizar' : 'votacion_crear'; ?>" method="POST">
            <?php if ($edit): ?><input type="hidden" name="votacion_id" value="<?php echo (int)$edit->id; ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">Pregunta principal *</label>
                <textarea name="titulo" class="form-control" required maxlength="500" rows="4"
                    placeholder="Ej. ¿Está de acuerdo con que la junta realice la actividad de Navidad usando fondos de la comunidad?"><?php echo htmlspecialchars($edit->titulo ?? ''); ?></textarea>
                <p style="font-size:0.75rem;color:var(--text-muted);margin:0.35rem 0 0;">Es el texto que verán los socios. Hasta 500 caracteres.</p>
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($edit->descripcion ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="votacion" <?php echo ($edit->tipo ?? '') === 'votacion' ? 'selected' : ''; ?>>Votación (una opción)</option>
                    <option value="encuesta" <?php echo ($edit->tipo ?? '') === 'encuesta' ? 'selected' : ''; ?>>Encuesta</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Quién puede votar</label>
                <select name="audiencia_tipo" class="form-control" id="audienciaTipo">
                    <option value="todos_socios" <?php echo ($edit->audiencia_tipo ?? '') === 'todos_socios' ? 'selected' : ''; ?>>Todos los socios</option>
                    <option value="directiva" <?php echo ($edit->audiencia_tipo ?? '') === 'directiva' ? 'selected' : ''; ?>>Solo directiva</option>
                    <option value="seleccionados" <?php echo ($edit->audiencia_tipo ?? '') === 'seleccionados' ? 'selected' : ''; ?>>Socios seleccionados</option>
                </select>
            </div>
            <div class="form-group" id="electoresBox" style="<?php echo ($edit->audiencia_tipo ?? '') === 'seleccionados' ? '' : 'display:none;'; ?>">
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
                <input type="datetime-local" name="fecha_inicio" class="form-control" required
                    value="<?php echo !empty($edit->fecha_inicio) ? date('Y-m-d\TH:i', strtotime($edit->fecha_inicio)) : ''; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Cierre *</label>
                <input type="datetime-local" name="fecha_fin" class="form-control" required
                    value="<?php echo !empty($edit->fecha_fin) ? date('Y-m-d\TH:i', strtotime($edit->fecha_fin)) : ''; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Quién ve los porcentajes</label>
                <?php $vis = $edit->resultados_visibilidad ?? 'al_votar'; ?>
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
                <?php
                $opts = $data['edit_opciones'] ?? [];
                $slots = max(3, count($opts));
                for ($i = 0; $i < $slots; $i++):
                    $o = $opts[$i] ?? null;
                    $val = $o ? (string)$o->texto : '';
                    $tipoSeg = $o ? (string)($o->seguimiento_tipo ?? 'ninguno') : 'ninguno';
                    $preg = $o ? (string)($o->seguimiento_pregunta ?? '') : '';
                    $lineas = '';
                    if ($o && !empty($o->seguimiento_opciones)) {
                        $lineas = implode("\n", array_map(static fn($s) => $s->texto, $o->seguimiento_opciones));
                    }
                ?>
                <div class="opcion-editor" style="border:1px solid var(--border-color);border-radius:8px;padding:0.65rem;margin-bottom:0.5rem;">
                    <input type="hidden" name="opcion_id[]" value="<?php echo $o ? (int)$o->id : 0; ?>">
                    <input type="text" name="opciones[]" class="form-control" maxlength="500" placeholder="Opción <?php echo $i + 1; ?> (ej. Sí)"
                        value="<?php echo htmlspecialchars($val); ?>">
                    <label class="form-label" style="margin-top:0.5rem;">Si eligen esta opción, preguntar además</label>
                    <select name="seguimiento_tipo[]" class="form-control seg-tipo">
                        <option value="ninguno" <?php echo $tipoSeg === 'ninguno' ? 'selected' : ''; ?>>No preguntar más</option>
                        <option value="texto" <?php echo $tipoSeg === 'texto' ? 'selected' : ''; ?>>Una respuesta escrita</option>
                        <option value="opciones" <?php echo $tipoSeg === 'opciones' ? 'selected' : ''; ?>>Otras alternativas</option>
                    </select>
                    <div class="seg-fields" style="margin-top:0.45rem;<?php echo $tipoSeg === 'ninguno' ? 'display:none;' : ''; ?>">
                        <input type="text" name="seguimiento_pregunta[]" class="form-control" maxlength="500" placeholder="Ej. ¿Cuántas personas de su hogar asistirían?"
                            value="<?php echo htmlspecialchars($preg); ?>">
                        <textarea name="seguimiento_opciones_texto[]" class="form-control seg-lineas" rows="3" style="margin-top:0.35rem;<?php echo $tipoSeg === 'opciones' ? '' : 'display:none;'; ?>" placeholder="Una alternativa por línea"><?php echo htmlspecialchars($lineas); ?></textarea>
                    </div>
                </div>
                <?php endfor; ?>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" id="agregarOpcion">Agregar opción</button>
            </div>
            <?php if (!$edit): ?>
            <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;margin-bottom:1rem;">
                <input type="checkbox" name="publicar" value="1" checked> Publicar de inmediato
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
                            <?php if (in_array($v->estado, ['borrador', 'activa'], true)): ?>
                            <a href="<?php echo URLROOT; ?>/admin/votaciones?editar=<?php echo (int)$v->id; ?>" class="btn btn-secondary btn-sm">Editar</a>
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
document.getElementById('audienciaTipo')?.addEventListener('change', function() {
    document.getElementById('electoresBox').style.display = this.value === 'seleccionados' ? '' : 'none';
});

function syncSeguimiento(editor) {
    var tipo = editor.querySelector('.seg-tipo').value;
    var fields = editor.querySelector('.seg-fields');
    var lineas = editor.querySelector('.seg-lineas');
    fields.style.display = tipo === 'ninguno' ? 'none' : '';
    lineas.style.display = tipo === 'opciones' ? '' : 'none';
}

document.getElementById('opcionesList')?.addEventListener('change', function (event) {
    if (event.target.classList.contains('seg-tipo')) {
        syncSeguimiento(event.target.closest('.opcion-editor'));
    }
});

document.getElementById('agregarOpcion')?.addEventListener('click', function () {
    var list = document.getElementById('opcionesList');
    var n = list.querySelectorAll('.opcion-editor').length + 1;
    var wrap = document.createElement('div');
    wrap.className = 'opcion-editor';
    wrap.style.cssText = 'border:1px solid var(--border-color);border-radius:8px;padding:0.65rem;margin-bottom:0.5rem;';
    wrap.innerHTML = ''
        + '<input type="hidden" name="opcion_id[]" value="0">'
        + '<input type="text" name="opciones[]" class="form-control" maxlength="500" placeholder="Opción ' + n + '">'
        + '<label class="form-label" style="margin-top:0.5rem;">Si eligen esta opción, preguntar además</label>'
        + '<select name="seguimiento_tipo[]" class="form-control seg-tipo">'
        + '<option value="ninguno" selected>No preguntar más</option>'
        + '<option value="texto">Una respuesta escrita</option>'
        + '<option value="opciones">Otras alternativas</option>'
        + '</select>'
        + '<div class="seg-fields" style="margin-top:0.45rem;display:none;">'
        + '<input type="text" name="seguimiento_pregunta[]" class="form-control" maxlength="500" placeholder="Ej. ¿Cuántas personas de su hogar asistirían?">'
        + '<textarea name="seguimiento_opciones_texto[]" class="form-control seg-lineas" rows="3" style="margin-top:0.35rem;display:none;" placeholder="Una alternativa por línea"></textarea>'
        + '</div>';
    list.appendChild(wrap);
});
</script>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
