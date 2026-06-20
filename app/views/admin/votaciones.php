<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$edit = $data['edit'] ?? null;
$miembros = $data['miembros'] ?? [];
$editElectores = $data['edit_electores'] ?? [];
?>

<?php if (!empty($data['success'])): ?><div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div><?php endif; ?>
<?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div><?php endif; ?>

<div class="grid-2col" style="align-items:start;">
    <div class="card card-primary">
        <h3 class="card-title"><?php echo $edit ? 'Editar consulta' : 'Nueva votación o encuesta'; ?></h3>
        <form action="<?php echo URLROOT; ?>/admin/<?php echo $edit ? 'votacion_actualizar' : 'votacion_crear'; ?>" method="POST">
            <?php if ($edit): ?><input type="hidden" name="votacion_id" value="<?php echo (int)$edit->id; ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">Título *</label>
                <input type="text" name="titulo" class="form-control" required maxlength="200"
                    value="<?php echo htmlspecialchars($edit->titulo ?? ''); ?>">
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
                <label class="form-label">Resultados visibles para</label>
                <select name="resultados_visibilidad" class="form-control">
                    <option value="directiva" <?php echo ($edit->resultados_visibilidad ?? 'directiva') === 'directiva' ? 'selected' : ''; ?>>Solo directiva y creador</option>
                    <option value="todos" <?php echo ($edit->resultados_visibilidad ?? '') === 'todos' ? 'selected' : ''; ?>>Todos los electores</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Opciones de respuesta * (mínimo 2)</label>
                <?php
                $opts = $data['edit_opciones'] ?? [];
                for ($i = 0; $i < max(3, count($opts)); $i++):
                    $val = isset($opts[$i]) ? $opts[$i]->texto : '';
                ?>
                <input type="text" name="opciones[]" class="form-control" style="margin-bottom:0.35rem;" placeholder="Opción <?php echo $i + 1; ?>"
                    value="<?php echo htmlspecialchars($val); ?>">
                <?php endfor; ?>
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
</script>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
