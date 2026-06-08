<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/AuthContext.php'; ?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<div class="grid-2col">
    <div class="card card-primary">
        <h3 class="card-title">Calendario mensual</h3>
        <?php
        $cal_mes = $data['cal_mes'];
        $cal_anio = $data['cal_anio'];
        $eventos_por_dia = $data['eventos_por_dia'];
        $url_base = URLROOT . '/admin/calendario';
        $hint = !empty($data['es_vista_directorio'])
            ? 'Vista de directiva: todas las convocatorias de la organización.'
            : 'Solo las convocatorias en las que usted fue incluido.';
        require APPROOT . '/views/partials/calendario_actividades.php';
        ?>
    </div>

    <div class="card card-primary">
        <h3 class="card-title">Listado de actividades</h3>
        <?php if (empty($data['reuniones'])): ?>
            <p style="color:var(--text-muted);text-align:center;padding:1.5rem;">No hay convocatorias en este periodo.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table cb-reunion-table">
                <thead>
                    <tr><th>Fecha</th><th>Actividad</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($data['reuniones'] as $r): ?>
                    <tr>
                        <td class="cb-reunion-date"><?php echo date('d/m/Y H:i', strtotime($r->fecha_reunion)); ?></td>
                        <td><strong><?php echo htmlspecialchars($r->titulo); ?></strong></td>
                        <td><span class="badge <?php echo $r->estado === 'realizada' ? 'badge-success' : 'badge-warning'; ?>"><?php echo htmlspecialchars($r->estado); ?></span></td>
                        <td>
                            <?php if (!empty($data['es_vista_directorio']) && AuthContext::canManageReuniones()): ?>
                            <a href="<?php echo URLROOT; ?>/admin/asistencia/<?php echo (int)$r->id; ?>" class="btn btn-secondary btn-sm">Gestionar</a>
                            <?php elseif ($r->estado === 'realizada'): ?>
                            <a href="<?php echo URLROOT; ?>/admin/reunion_minuta/<?php echo (int)$r->id; ?>" target="_blank" class="btn btn-secondary btn-sm">Minuta</a>
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

<?php if (!empty($data['es_vista_directorio']) && AuthContext::canManageReuniones()): ?>
<div style="margin-top:1rem;">
    <a href="<?php echo URLROOT; ?>/admin/asistencia" class="btn btn-primary btn-sm">Convocar reunión / asamblea</a>
</div>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
