<?php
/**
 * Widget de actividades para el inicio del dashboard.
 * Variables: $cal_mes, $cal_anio, $eventos_por_dia, $proximas, $url_calendario,
 * $url_base_mes (navegación del mini-calendario), $es_socio (bool), $mostrar_calendario (bool)
 */
if (empty($mostrar_calendario)) {
    return;
}
$proximas = $proximas ?? [];
$urlCal = $url_calendario ?? '#';
$urlBaseMes = $url_base_mes ?? $urlCal;
?>
<section class="cb-dashboard-actividades" aria-label="Actividades del mes">
    <div class="cb-dashboard-actividades-header">
        <div>
            <h2 class="cb-dashboard-actividades-title">Actividades del mes</h2>
            <p class="cb-dashboard-actividades-sub">Convocatorias y reuniones <?php echo !empty($es_socio) ? 'a las que fue convocado/a' : 'de su organización'; ?></p>
        </div>
        <a href="<?php echo htmlspecialchars($urlCal); ?>" class="btn btn-primary btn-sm">Ver calendario completo</a>
    </div>

    <div class="grid-2col cb-dashboard-actividades-grid">
        <div class="card card-primary cb-dashboard-cal-wrap">
            <?php
            $cal_mes = $cal_mes ?? (int)date('n');
            $cal_anio = $cal_anio ?? (int)date('Y');
            $eventos_por_dia = $eventos_por_dia ?? [];
            $url_base = $urlBaseMes;
            $hint = '';
            require APPROOT . '/views/partials/calendario_actividades.php';
            ?>
        </div>

        <div class="card card-primary">
            <h3 class="card-title">Próximas actividades</h3>
            <?php if (empty($proximas)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:1.25rem 0.5rem;font-size:0.88rem;">
                    No hay convocatorias programadas próximamente.
                </p>
            <?php else: ?>
            <ul class="cb-proximas-list">
                <?php foreach ($proximas as $r):
                    $futura = strtotime($r->fecha_reunion) >= time();
                ?>
                <li class="cb-proximas-item">
                    <div class="cb-proximas-date">
                        <span class="cb-proximas-day"><?php echo date('d', strtotime($r->fecha_reunion)); ?></span>
                        <span class="cb-proximas-mes"><?php echo strtoupper(date('M', strtotime($r->fecha_reunion))); ?></span>
                    </div>
                    <div class="cb-proximas-body">
                        <strong><?php echo htmlspecialchars($r->titulo); ?></strong>
                        <span><?php echo date('d/m/Y · H:i', strtotime($r->fecha_reunion)); ?> h</span>
                        <?php if ($futura): ?><span class="badge badge-info" style="font-size:0.62rem;margin-top:0.2rem;">Próxima</span><?php endif; ?>
                    </div>
                    <?php if (empty($es_socio) && !empty($r->id)): ?>
                    <a href="<?php echo URLROOT; ?>/admin/asistencia/<?php echo (int)$r->id; ?>?tab=listado" class="btn btn-secondary btn-sm">Gestionar</a>
                    <?php elseif (!empty($es_socio)): ?>
                    <a href="<?php echo URLROOT; ?>/socio/reuniones" class="btn btn-secondary btn-sm">Ver</a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
