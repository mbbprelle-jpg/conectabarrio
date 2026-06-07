<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
$matriz = $data['matriz'];
$anio = (int)$data['anio_seleccionado'];
$mesesLabel = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

function cbFlujoFmt(?int $monto, bool $aplica): string {
    if (!$aplica) {
        return '<span class="cb-flujo-na">—</span>';
    }
    if ($monto === null || $monto === 0) {
        return '<span class="cb-flujo-zero">—</span>';
    }
    return '$' . number_format($monto, 0, ',', '.');
}

function cbFlujoMesValido(int $anio, int $mes, string $mesInicio): bool {
    $y0 = (int)substr($mesInicio, 0, 4);
    $m0 = (int)substr($mesInicio, 5, 2);
    if ($anio < $y0) {
        return false;
    }
    if ($anio === $y0 && $mes < $m0) {
        return false;
    }
    return true;
}
?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<div class="card card-primary cb-flujo-toolbar">
    <div class="cb-flujo-toolbar-inner">
        <div>
            <h3 style="font-family: var(--font-heading); font-size: 1.15rem; margin: 0 0 0.25rem;">Resumen <?php echo $anio; ?></h3>
            <p style="margin: 0; font-size: 0.82rem; color: var(--text-muted);">
                Montos por concepto según la <strong>fecha del movimiento</strong>. Inicio actividades: <?php echo htmlspecialchars($data['mes_inicio']); ?>.
            </p>
        </div>
        <?php if (count($data['anios']) > 1): ?>
        <div class="cb-flujo-year-select">
            <label for="anio_selector" class="form-label" style="margin-bottom: 0.35rem;">Año</label>
            <select id="anio_selector" class="form-control">
                <?php foreach ($data['anios'] as $y): ?>
                    <option value="<?php echo (int)$y; ?>" <?php echo (int)$y === $anio ? 'selected' : ''; ?>><?php echo (int)$y; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
        <div class="cb-flujo-year-badge">
            <span class="form-label" style="display: block; margin-bottom: 0.25rem;">Año</span>
            <strong style="font-size: 1.25rem; font-family: var(--font-heading); color: var(--primary);"><?php echo $anio; ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <div class="cb-flujo-kpis">
        <div class="cb-flujo-kpi cb-flujo-kpi--success">
            <span class="cb-flujo-kpi-label">Total ingresos</span>
            <strong>$<?php echo number_format($matriz['total_ingresos_anio'], 0, ',', '.'); ?></strong>
        </div>
        <div class="cb-flujo-kpi cb-flujo-kpi--danger">
            <span class="cb-flujo-kpi-label">Total egresos</span>
            <strong>$<?php echo number_format($matriz['total_egresos_anio'], 0, ',', '.'); ?></strong>
        </div>
        <div class="cb-flujo-kpi cb-flujo-kpi--primary">
            <span class="cb-flujo-kpi-label">Neto del año</span>
            <strong style="color: <?php echo $matriz['neto_anio'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                $<?php echo number_format($matriz['neto_anio'], 0, ',', '.'); ?>
            </strong>
        </div>
    </div>
</div>

<div class="card cb-flujo-matrix-card">
    <div class="cb-flujo-matrix-scroll">
        <table class="cb-flujo-matrix">
            <thead>
                <tr>
                    <th class="cb-flujo-sticky-col cb-flujo-col-concepto">Concepto</th>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <th class="cb-flujo-col-mes"><?php echo $mesesLabel[$m]; ?></th>
                    <?php endfor; ?>
                    <th class="cb-flujo-col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matriz['secciones'] as $seccion): ?>
                <tr class="cb-flujo-section-row">
                    <td colspan="14" class="cb-flujo-section-title cb-flujo-sticky-col">
                        <?php echo htmlspecialchars($seccion['titulo']); ?>
                    </td>
                </tr>
                <?php foreach ($seccion['filas'] as $fila):
                    $allZero = ($fila['total'] === 0);
                ?>
                <tr class="cb-flujo-data-row <?php echo $allZero ? 'is-empty' : ''; ?>">
                    <td class="cb-flujo-sticky-col cb-flujo-col-concepto">
                        <?php echo htmlspecialchars($fila['categoria']); ?>
                    </td>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $aplica = cbFlujoMesValido($anio, $m, $data['mes_inicio']);
                        $val = (int)$fila['meses'][$m];
                    ?>
                    <td class="cb-flujo-col-mes <?php echo $seccion['tipo'] === 'ingreso' ? 'is-ingreso' : 'is-egreso'; ?>">
                        <?php echo cbFlujoFmt($val, $aplica); ?>
                    </td>
                    <?php endfor; ?>
                    <td class="cb-flujo-col-total">
                        <strong><?php echo $fila['total'] > 0 ? '$' . number_format($fila['total'], 0, ',', '.') : '—'; ?></strong>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>

                <tr class="cb-flujo-total-row cb-flujo-total-row--ingreso">
                    <td class="cb-flujo-sticky-col">Total ingresos</td>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $aplica = cbFlujoMesValido($anio, $m, $data['mes_inicio']);
                        $val = (int)$matriz['totales_ingreso_mes'][$m];
                    ?>
                    <td class="cb-flujo-col-mes is-ingreso"><?php echo cbFlujoFmt($val, $aplica); ?></td>
                    <?php endfor; ?>
                    <td class="cb-flujo-col-total"><strong>$<?php echo number_format($matriz['total_ingresos_anio'], 0, ',', '.'); ?></strong></td>
                </tr>
                <tr class="cb-flujo-total-row cb-flujo-total-row--egreso">
                    <td class="cb-flujo-sticky-col">Total egresos</td>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $aplica = cbFlujoMesValido($anio, $m, $data['mes_inicio']);
                        $val = (int)$matriz['totales_egreso_mes'][$m];
                    ?>
                    <td class="cb-flujo-col-mes is-egreso"><?php echo cbFlujoFmt($val, $aplica); ?></td>
                    <?php endfor; ?>
                    <td class="cb-flujo-col-total"><strong>$<?php echo number_format($matriz['total_egresos_anio'], 0, ',', '.'); ?></strong></td>
                </tr>
                <tr class="cb-flujo-total-row cb-flujo-total-row--neto">
                    <td class="cb-flujo-sticky-col">Neto mensual</td>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $aplica = cbFlujoMesValido($anio, $m, $data['mes_inicio']);
                        $val = (int)$matriz['neto_mes'][$m];
                    ?>
                    <td class="cb-flujo-col-mes">
                        <?php if (!$aplica): ?>
                            <?php echo cbFlujoFmt(null, false); ?>
                        <?php elseif ($val === 0): ?>
                            <span class="cb-flujo-zero">—</span>
                        <?php else: ?>
                            <span style="color: <?php echo $val >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; font-weight: 600;">
                                $<?php echo number_format(abs($val), 0, ',', '.'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <?php endfor; ?>
                    <td class="cb-flujo-col-total">
                        <strong style="color: <?php echo $matriz['neto_anio'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                            $<?php echo number_format($matriz['neto_anio'], 0, ',', '.'); ?>
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<p style="margin-top: 1.25rem; text-align: center; display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
    <a href="<?php echo URLROOT; ?>/admin/finanzas" class="btn btn-secondary">Registrar movimientos</a>
    <a href="<?php echo URLROOT; ?>/admin/conceptos_caja" class="btn btn-secondary">Conceptos de Caja</a>
</p>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('anio_selector');
    if (sel) {
        sel.addEventListener('change', function() {
            window.location.href = '<?php echo URLROOT; ?>/admin/flujo_caja?anio=' + encodeURIComponent(this.value);
        });
    }
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
