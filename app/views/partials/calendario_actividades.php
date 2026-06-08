<?php
/**
 * Calendario mensual con actividades/convocatorias.
 * Variables: $cal_mes, $cal_anio, $eventos_por_dia (array day => events[]),
 * $url_base (ej. /admin/calendario), $hint (string opcional)
 */
$calMes = max(1, min(12, (int)($cal_mes ?? date('n'))));
$calAnio = (int)($cal_anio ?? date('Y'));
$eventosPorDia = $eventos_por_dia ?? [];
$urlBase = $url_base ?? '';
$nombresMes = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$primerDia = (int)date('N', mktime(0, 0, 0, $calMes, 1, $calAnio));
$diasMes = (int)date('t', mktime(0, 0, 0, $calMes, 1, $calAnio));
$prevM = $calMes === 1 ? 12 : $calMes - 1;
$prevY = $calMes === 1 ? $calAnio - 1 : $calAnio;
$nextM = $calMes === 12 ? 1 : $calMes + 1;
$nextY = $calMes === 12 ? $calAnio + 1 : $calAnio;
$qs = static function (int $m, int $y) use ($urlBase): string {
    $sep = str_contains($urlBase, '?') ? '&' : '?';
    return htmlspecialchars($urlBase . $sep . 'mes=' . $m . '&anio=' . $y);
};
?>
<div class="cb-cal-widget">
    <div class="cb-cal-nav">
        <a href="<?php echo $qs($prevM, $prevY); ?>" class="btn btn-secondary btn-sm" aria-label="Mes anterior">←</a>
        <strong class="cb-cal-month-label"><?php echo $nombresMes[$calMes] . ' ' . $calAnio; ?></strong>
        <a href="<?php echo $qs($nextM, $nextY); ?>" class="btn btn-secondary btn-sm" aria-label="Mes siguiente">→</a>
    </div>
    <div class="cb-cal-grid cb-cal-grid--rich">
        <?php foreach (['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $d): ?>
            <div class="cb-cal-head"><?php echo $d; ?></div>
        <?php endforeach; ?>
        <?php for ($i = 1; $i < $primerDia; $i++): ?>
            <div class="cb-cal-cell is-empty"></div>
        <?php endfor; ?>
        <?php for ($d = 1; $d <= $diasMes; $d++):
            $eventos = $eventosPorDia[$d] ?? [];
            $hasEv = !empty($eventos);
            $isToday = ($d === (int)date('j') && $calMes === (int)date('n') && $calAnio === (int)date('Y'));
        ?>
        <div class="cb-cal-cell <?php echo $hasEv ? 'has-event' : ''; ?> <?php echo $isToday ? 'is-today' : ''; ?>"
             title="<?php echo $hasEv ? implode(' · ', array_map(static fn($e) => ($e['hora'] ?? '') . ' ' . ($e['titulo'] ?? ''), $eventos)) : ''; ?>">
            <span class="cb-cal-cell-num"><?php echo $d; ?></span>
            <?php if ($hasEv): ?>
            <div class="cb-cal-cell-events">
                <?php foreach (array_slice($eventos, 0, 3) as $ev):
                    $estado = $ev['estado'] ?? 'programada';
                    $chipClass = $estado === 'realizada' ? 'is-done' : ($estado === 'cancelada' ? 'is-cancel' : 'is-upcoming');
                ?>
                <span class="cb-cal-event-chip <?php echo $chipClass; ?>" title="<?php echo htmlspecialchars(($ev['hora'] ?? '') . ' — ' . ($ev['titulo'] ?? '')); ?>">
                    <span class="cb-cal-event-time"><?php echo htmlspecialchars($ev['hora'] ?? ''); ?></span>
                    <?php echo htmlspecialchars(mb_strlen($ev['titulo'] ?? '') > 18 ? mb_substr($ev['titulo'], 0, 16) . '…' : ($ev['titulo'] ?? '')); ?>
                </span>
                <?php endforeach; ?>
                <?php if (count($eventos) > 3): ?>
                <span class="cb-cal-event-more">+<?php echo count($eventos) - 3; ?> más</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
    <?php if (!empty($hint)): ?>
    <p class="cb-cal-hint"><?php echo htmlspecialchars($hint); ?></p>
    <?php endif; ?>
    <div class="cb-cal-legend">
        <span><i class="cb-cal-legend-dot is-upcoming"></i> Próxima</span>
        <span><i class="cb-cal-legend-dot is-done"></i> Realizada</span>
        <span><i class="cb-cal-legend-dot is-cancel"></i> Cancelada</span>
    </div>
</div>
