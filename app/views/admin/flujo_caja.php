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

function cbFlujoFmtSaldo(?int $monto, bool $aplica): string {
    if (!$aplica || $monto === null) {
        return '<span class="cb-flujo-na">—</span>';
    }
    return '<span style="color: ' . ($monto >= 0 ? 'var(--info)' : 'var(--danger)') . '; font-weight: 600;">$'
        . number_format($monto, 0, ',', '.') . '</span>';
}

function cbFlujoEsPrimerMesActividad(int $anio, int $mes, string $mesInicio): bool {
    return $anio === (int)substr($mesInicio, 0, 4) && $mes === (int)substr($mesInicio, 5, 2);
}

$mesesColumna = $matriz['meses_visibles'] ?? range(1, 12);
$colSpanTabla = count($mesesColumna) + 2;
$mesesLabelLargo = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$periodoLabel = '';
if (!empty($mesesColumna)) {
    $periodoLabel = $mesesLabelLargo[$mesesColumna[0]] . ' – ' . $mesesLabelLargo[$mesesColumna[count($mesesColumna) - 1]] . ' ' . $anio;
}
?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<?php
require_once APPROOT . '/core/AuthContext.php';
if (AuthContext::isFullAdmin() && empty($data['flujo_caja_habilitado'])):
?>
<div class="alert alert-warning" style="margin-bottom: 1rem;">
    <span>
        El módulo de <strong>Flujo de Caja</strong> aún no está habilitado para su organización.
        Active la opción en <a href="<?php echo URLROOT; ?>/admin/socios" style="color: inherit; text-decoration: underline;">Socios y Ajustes</a>
        y delegue el acceso a tesorero, director o secretario según corresponda.
    </span>
</div>
<?php endif; ?>

<div class="card card-primary cb-flujo-toolbar">
    <div class="cb-flujo-toolbar-inner">
        <div>
            <h3 style="font-family: var(--font-heading); font-size: 1.15rem; margin: 0 0 0.25rem;">Resumen <?php echo $anio; ?></h3>
            <p style="margin: 0; font-size: 0.82rem; color: var(--text-muted);">
                Montos por concepto según la <strong>fecha del movimiento</strong>.
                <?php if ($periodoLabel !== ''): ?>
                Periodo visible: <strong><?php echo htmlspecialchars($periodoLabel); ?></strong>
                (desde inicio actividades <?php echo htmlspecialchars($data['mes_inicio']); ?>).
                <?php else: ?>
                Inicio actividades: <?php echo htmlspecialchars($data['mes_inicio']); ?>.
                <?php endif; ?>
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
        <?php if ($matriz['saldo_inicial'] !== null && $anio === (int)substr($data['mes_inicio'], 0, 4)): ?>
        <div class="cb-flujo-kpi cb-flujo-kpi--info">
            <span class="cb-flujo-kpi-label">Saldo inicial (1.er mes)</span>
            <strong style="color: var(--info);">$<?php echo number_format((int)$matriz['saldo_inicial'], 0, ',', '.'); ?></strong>
        </div>
        <?php endif; ?>
        <div class="cb-flujo-kpi cb-flujo-kpi--success">
            <span class="cb-flujo-kpi-label">Total ingresos<?php echo $anio === (int)date('Y') ? ' (a la fecha)' : ''; ?></span>
            <strong>$<?php echo number_format($matriz['total_ingresos_anio'], 0, ',', '.'); ?></strong>
        </div>
        <div class="cb-flujo-kpi cb-flujo-kpi--danger">
            <span class="cb-flujo-kpi-label">Total egresos<?php echo $anio === (int)date('Y') ? ' (a la fecha)' : ''; ?></span>
            <strong>$<?php echo number_format($matriz['total_egresos_anio'], 0, ',', '.'); ?></strong>
        </div>
        <div class="cb-flujo-kpi cb-flujo-kpi--primary">
            <span class="cb-flujo-kpi-label">Neto del periodo</span>
            <strong style="color: <?php echo $matriz['neto_anio'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                $<?php echo number_format($matriz['neto_anio'], 0, ',', '.'); ?>
            </strong>
        </div>
        <?php if ($matriz['saldo_inicial'] !== null || $matriz['saldo_contable_fin_anio'] !== $matriz['neto_anio']): ?>
        <div class="cb-flujo-kpi cb-flujo-kpi--primary">
            <span class="cb-flujo-kpi-label">Saldo final<?php echo !empty($mesesColumna) ? ' (' . $mesesLabel[$mesesColumna[count($mesesColumna) - 1]] . ')' : ''; ?></span>
            <strong style="color: <?php echo $matriz['saldo_contable_fin_anio'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                $<?php echo number_format($matriz['saldo_contable_fin_anio'], 0, ',', '.'); ?>
            </strong>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card cb-flujo-matrix-card">
    <?php if (empty($mesesColumna)): ?>
    <p style="color: var(--text-muted); text-align: center; padding: 2rem; margin: 0;">
        No hay meses visibles para el año <?php echo $anio; ?> según la fecha de inicio de la organización.
    </p>
    <?php else: ?>
    <div class="cb-flujo-matrix-scroll">
        <table class="cb-flujo-matrix">
            <thead>
                <tr>
                    <th class="cb-flujo-sticky-col cb-flujo-col-concepto">Concepto</th>
                    <?php foreach ($mesesColumna as $m): ?>
                        <th class="cb-flujo-col-mes"><?php echo $mesesLabel[$m]; ?></th>
                    <?php endforeach; ?>
                    <th class="cb-flujo-col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matriz['secciones'] as $seccion): ?>
                <tr class="cb-flujo-section-row">
                    <td colspan="<?php echo $colSpanTabla; ?>" class="cb-flujo-section-title cb-flujo-sticky-col">
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
                    <?php foreach ($mesesColumna as $m):
                        $val = (int)$fila['meses'][$m];
                    ?>
                    <td class="cb-flujo-col-mes <?php echo $seccion['tipo'] === 'ingreso' ? 'is-ingreso' : 'is-egreso'; ?>">
                        <?php echo cbFlujoFmt($val, true); ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="cb-flujo-col-total">
                        <strong><?php echo $fila['total'] > 0 ? '$' . number_format($fila['total'], 0, ',', '.') : '—'; ?></strong>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>

                <tr class="cb-flujo-section-row">
                    <td colspan="<?php echo $colSpanTabla; ?>" class="cb-flujo-section-title cb-flujo-sticky-col">Resumen mensual</td>
                </tr>
                <tr class="cb-flujo-total-row cb-flujo-total-row--anterior">
                    <td class="cb-flujo-sticky-col">
                        Saldo anterior
                        <small style="display:block;font-weight:400;font-size:0.68rem;color:var(--text-muted);margin-top:0.15rem;">
                            1.er mes = saldo inicial declarado
                        </small>
                    </td>
                    <?php foreach ($mesesColumna as $m):
                        $val = $matriz['saldo_anterior_mes'][$m] ?? null;
                        $esPrimero = cbFlujoEsPrimerMesActividad($anio, $m, $data['mes_inicio']);
                    ?>
                    <td class="cb-flujo-col-mes">
                        <?php echo cbFlujoFmtSaldo($val !== null ? (int)$val : null, true); ?>
                        <?php if ($esPrimero && $matriz['saldo_inicial'] !== null): ?>
                        <small class="cb-flujo-saldo-hint" title="Saldo inicial de caja">inicial</small>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="cb-flujo-col-total"><span class="cb-flujo-na">—</span></td>
                </tr>
                <tr class="cb-flujo-total-row cb-flujo-total-row--ingreso">
                    <td class="cb-flujo-sticky-col">Total ingresos</td>
                    <?php foreach ($mesesColumna as $m):
                        $val = (int)$matriz['totales_ingreso_mes'][$m];
                    ?>
                    <td class="cb-flujo-col-mes is-ingreso"><?php echo cbFlujoFmt($val, true); ?></td>
                    <?php endforeach; ?>
                    <td class="cb-flujo-col-total"><strong>$<?php echo number_format($matriz['total_ingresos_anio'], 0, ',', '.'); ?></strong></td>
                </tr>
                <tr class="cb-flujo-total-row cb-flujo-total-row--egreso">
                    <td class="cb-flujo-sticky-col">Total egresos</td>
                    <?php foreach ($mesesColumna as $m):
                        $val = (int)$matriz['totales_egreso_mes'][$m];
                    ?>
                    <td class="cb-flujo-col-mes is-egreso"><?php echo cbFlujoFmt($val, true); ?></td>
                    <?php endforeach; ?>
                    <td class="cb-flujo-col-total"><strong>$<?php echo number_format($matriz['total_egresos_anio'], 0, ',', '.'); ?></strong></td>
                </tr>
                <tr class="cb-flujo-total-row cb-flujo-total-row--saldo">
                    <td class="cb-flujo-sticky-col">
                        <strong>Saldo final</strong>
                        <small style="display:block;font-weight:400;font-size:0.68rem;color:var(--text-muted);margin-top:0.15rem;">
                            Ant. + Ing. − Egr.
                        </small>
                    </td>
                    <?php foreach ($mesesColumna as $m):
                        $val = $matriz['saldo_final_mes'][$m] ?? null;
                    ?>
                    <td class="cb-flujo-col-mes">
                        <?php echo cbFlujoFmtSaldo($val !== null ? (int)$val : null, true); ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="cb-flujo-col-total">
                        <strong style="color: <?php echo $matriz['saldo_contable_fin_anio'] >= 0 ? 'var(--info)' : 'var(--danger)'; ?>;">
                            $<?php echo number_format($matriz['saldo_contable_fin_anio'], 0, ',', '.'); ?>
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
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
