<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/models/Reunion.php'; ?>
<?php $rm = new Reunion(); ?>

<?php
$proxima = $data['proxima'] ?? null;
$reuniones = $data['reuniones'] ?? [];
$calMes = (int)($data['cal_mes'] ?? date('n'));
$calAnio = (int)($data['cal_anio'] ?? date('Y'));
$diasConEvento = $data['dias_con_evento'] ?? [];
$nombresMes = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$primerDia = (int)date('N', mktime(0, 0, 0, $calMes, 1, $calAnio));
$diasMes = (int)date('t', mktime(0, 0, 0, $calMes, 1, $calAnio));
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
        <h3 class="card-title">Calendario — <?php echo $nombresMes[$calMes] . ' ' . $calAnio; ?></h3>
        <div class="cb-cal-nav">
            <?php
            $prevM = $calMes === 1 ? 12 : $calMes - 1;
            $prevY = $calMes === 1 ? $calAnio - 1 : $calAnio;
            $nextM = $calMes === 12 ? 1 : $calMes + 1;
            $nextY = $calMes === 12 ? $calAnio + 1 : $calAnio;
            ?>
            <a href="?mes=<?php echo $prevM; ?>&anio=<?php echo $prevY; ?>" class="btn btn-secondary btn-sm">←</a>
            <a href="?mes=<?php echo $nextM; ?>&anio=<?php echo $nextY; ?>" class="btn btn-secondary btn-sm">→</a>
        </div>
        <div class="cb-cal-grid">
            <?php foreach (['L','M','X','J','V','S','D'] as $d): ?>
                <div class="cb-cal-head"><?php echo $d; ?></div>
            <?php endforeach; ?>
            <?php for ($i = 1; $i < $primerDia; $i++): ?><div class="cb-cal-cell is-empty"></div><?php endfor; ?>
            <?php for ($d = 1; $d <= $diasMes; $d++):
                $hasEv = in_array($d, $diasConEvento, true);
                $isToday = ($d === (int)date('j') && $calMes === (int)date('n') && $calAnio === (int)date('Y'));
            ?>
            <div class="cb-cal-cell <?php echo $hasEv ? 'has-event' : ''; ?> <?php echo $isToday ? 'is-today' : ''; ?>">
                <span><?php echo $d; ?></span>
            </div>
            <?php endfor; ?>
        </div>
        <p style="font-size:0.75rem;color:var(--text-muted);margin:0.75rem 0 0;">
            Los días resaltados tienen una convocatoria a su nombre.
        </p>
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
    <p style="color:var(--text-muted);font-size:0.85rem;"><?php echo date('l d/m/Y H:i', strtotime($proxima->fecha_reunion)); ?></p>
    <div class="cb-reunion-temas-box"><?php echo nl2br(htmlspecialchars($rm->getTemasText($proxima))); ?></div>
</div>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
