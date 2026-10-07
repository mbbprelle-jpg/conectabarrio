<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$personas = $data['personas'] ?? [];
$pendientes = (int)($data['pendientes_nacimiento'] ?? 0);
$soloPendientes = !empty($data['solo_pendientes']);
$puedeGestionar = !empty($data['puede_gestionar']);
$tieneFecha = !empty($data['tiene_fecha_nacimiento']);
$hoy = date('Y-m-d');
$tipoLabel = [
    'hijo' => 'Hijo/a (0 a 8)',
    'discapacidad' => 'Discapacidad (0 a 18)',
];
?>
<style>
.cb-fnac-preview { display:block; margin-top:0.2rem; font-size:0.75rem; color:var(--text-muted); }
.cb-fnac-preview.is-error { color: var(--danger, #ef4444); }
tr.censo-fnac-pendiente td { background: rgba(240, 198, 116, 0.08); }
</style>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<div class="card card-primary" style="margin-bottom:1rem;">
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:0.75rem;">
        <div>
            <h3 style="font-family:var(--font-heading); font-size:1.1rem; margin:0 0 0.35rem;">Fechas de nacimiento</h3>
            <p style="margin:0; font-size:0.85rem; color:var(--text-muted);">
                Los registros anteriores solo tienen la edad. Aquí puede completar o corregir la fecha.
                Al guardar, la edad se recalcula con los años cumplidos a hoy.
                Hijos: 0 a 8 años. Personas con discapacidad: 0 a 18 años.
            </p>
        </div>
        <a href="<?php echo URLROOT; ?>/admin/censo_familiar" class="btn btn-secondary btn-sm">Volver al listado</a>
    </div>
</div>

<?php if (!$tieneFecha): ?>
    <div class="card card-primary">
        <p style="margin:0;">Para guardar la fecha de nacimiento ejecute una vez en MySQL:</p>
        <code style="display:block; margin-top:0.75rem;">sql/add_censo_personas_fecha_nacimiento.sql</code>
    </div>
<?php elseif (empty($personas) && !$soloPendientes): ?>
    <div class="card card-primary">
        <p style="margin:0; color:var(--text-muted);">No hay hijos ni personas con discapacidad inscritas.</p>
    </div>
<?php else: ?>
    <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; margin-bottom:0.75rem;">
        <a class="btn btn-sm <?php echo $soloPendientes ? 'btn-secondary' : 'btn-primary'; ?>"
           href="<?php echo URLROOT; ?>/admin/censo_familiar_fechas">Todas</a>
        <a class="btn btn-sm <?php echo $soloPendientes ? 'btn-primary' : 'btn-secondary'; ?>"
           href="<?php echo URLROOT; ?>/admin/censo_familiar_fechas?pendientes=1">
            Sin fecha (<?php echo $pendientes; ?>)
        </a>
    </div>

    <?php if (empty($personas)): ?>
        <div class="card card-primary">
            <p style="margin:0;">Todas las personas inscritas ya tienen fecha de nacimiento.</p>
        </div>
    <?php elseif (!$puedeGestionar): ?>
        <div class="card card-primary">
            <p style="margin:0; color:var(--text-muted);">Puede ver el listado, pero no editar las fechas.</p>
        </div>
    <?php else: ?>
        <form method="post" action="<?php echo URLROOT; ?>/admin/censo_familiar_fechas">
            <?php if ($soloPendientes): ?>
                <input type="hidden" name="solo_pendientes" value="1">
            <?php endif; ?>
            <div class="card card-primary">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Adulto responsable</th>
                                <th>Tipo</th>
                                <th>Persona</th>
                                <th>Edad registrada</th>
                                <th>Fecha de nacimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($personas as $p): ?>
                                <?php
                                $max = $p->tipo === 'hijo' ? 8 : 18;
                                $fnac = !empty($p->fecha_nacimiento) ? date('Y-m-d', strtotime($p->fecha_nacimiento)) : '';
                                $pendiente = $fnac === '';
                                ?>
                                <tr class="<?php echo $pendiente ? 'censo-fnac-pendiente' : ''; ?>">
                                    <td>
                                        <a href="<?php echo URLROOT; ?>/admin/censo_familiar?id=<?php echo (int)$p->registro_id; ?>">
                                            <?php echo htmlspecialchars($p->adulto_nombre ?? ''); ?>
                                        </a>
                                        <div style="font-size:0.75rem; color:var(--text-muted); font-family:monospace;">
                                            <?php echo htmlspecialchars($p->adulto_rut ?? ''); ?>
                                        </div>
                                    </td>
                                    <td style="font-size:0.82rem;"><?php echo htmlspecialchars($tipoLabel[$p->tipo] ?? $p->tipo); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($p->nombre_completo); ?>
                                        <div style="font-size:0.75rem; color:var(--text-muted); font-family:monospace;">
                                            <?php echo htmlspecialchars($p->rut); ?>
                                        </div>
                                    </td>
                                    <td><?php echo ($p->edad !== null && $p->edad !== '') ? ((int)$p->edad . ' años') : '—'; ?></td>
                                    <td style="min-width:12rem;">
                                        <input type="date"
                                               class="form-control cb-fnac-admin"
                                               name="fechas[<?php echo (int)$p->id; ?>]"
                                               data-max="<?php echo (int)$max; ?>"
                                               max="<?php echo htmlspecialchars($hoy); ?>"
                                               value="<?php echo htmlspecialchars($fnac); ?>">
                                        <span class="cb-fnac-preview"></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:0.85rem;">Guardar fechas</button>
            </div>
        </form>
        <?php require APPROOT . '/views/partials/censo_fnac_admin_script.php'; ?>
    <?php endif; ?>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
