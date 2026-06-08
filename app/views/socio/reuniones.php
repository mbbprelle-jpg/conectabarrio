<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/models/Reunion.php'; ?>
<?php $rm = new Reunion(); ?>

<?php
$proxima = $data['proxima'] ?? null;
$reuniones = $data['reuniones'] ?? [];
?>

<?php if ($proxima): ?>
<div class="alert alert-info cb-reunion-next-banner">
    <strong>Próxima convocatoria:</strong>
    <?php echo htmlspecialchars($proxima->titulo); ?> —
    <?php echo date('d/m/Y H:i', strtotime($proxima->fecha_reunion)); ?>
</div>
<?php endif; ?>

<div class="grid-2col">
    <div class="card card-primary">
        <h3 class="card-title">Mi calendario de convocatorias</h3>
        <?php
        $cal_mes = $data['cal_mes'];
        $cal_anio = $data['cal_anio'];
        $eventos_por_dia = $data['eventos_por_dia'];
        $url_base = URLROOT . '/socio/reuniones';
        $hint = 'Los días con color muestran actividades a las que fue convocado/a.';
        require APPROOT . '/views/partials/calendario_actividades.php';
        ?>
    </div>

    <div class="card card-primary">
        <h3 class="card-title">Mis convocatorias</h3>
        <?php if (empty($reuniones)): ?>
            <p style="color:var(--text-muted);text-align:center;padding:1.5rem;">No tiene convocatorias registradas.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table cb-reunion-table">
                <thead>
                    <tr><th>Fecha</th><th>Título</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($reuniones as $r):
                    $futura = strtotime($r->fecha_reunion) >= time();
                ?>
                    <tr>
                        <td class="cb-reunion-date"><?php echo date('d/m/Y H:i', strtotime($r->fecha_reunion)); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($r->titulo); ?></strong>
                            <?php if ($futura): ?><span class="badge badge-info" style="font-size:0.65rem;">Próxima</span><?php endif; ?>
                        </td>
                        <td><span class="badge <?php echo $r->estado === 'realizada' ? 'badge-success' : 'badge-warning'; ?>"><?php echo htmlspecialchars($r->estado); ?></span></td>
                        <td>
                            <?php if ($r->estado === 'realizada'): ?>
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

<?php if ($proxima): ?>
<div class="card" style="margin-top:1.5rem;">
    <h3 class="card-title">Detalle próxima reunión</h3>
    <p><strong><?php echo htmlspecialchars($proxima->titulo); ?></strong></p>
    <p style="color:var(--text-muted);font-size:0.85rem;"><?php echo date('d/m/Y H:i', strtotime($proxima->fecha_reunion)); ?></p>
    <div class="cb-reunion-temas-box"><?php echo nl2br(htmlspecialchars($rm->getTemasText($proxima))); ?></div>
</div>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
