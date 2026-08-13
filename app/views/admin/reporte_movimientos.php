<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/AuthContext.php'; ?>

<?php
$filtros = $data['filtros'] ?? [];
$totales = $data['totales'] ?? ['ingresos' => 0, 'egresos' => 0, 'neto' => 0, 'cuotas' => 0, 'cantidad' => 0];
$movimientos = $data['movimientos'] ?? [];
$junta = $data['junta'] ?? null;
$qsBase = http_build_query(array_filter([
    'desde' => $filtros['desde'] ?? '',
    'hasta' => $filtros['hasta'] ?? '',
    'tipo' => $filtros['tipo'] ?? '',
    'categoria' => $filtros['categoria'] ?? '',
], static fn($v) => $v !== '' && $v !== null));

$periodoTxt = 'Todos los movimientos';
if (($filtros['desde'] ?? '') !== '' || ($filtros['hasta'] ?? '') !== '') {
    $desdeLbl = ($filtros['desde'] ?? '') !== '' ? date('d-m-Y', strtotime($filtros['desde'])) : 'inicio';
    $hastaLbl = ($filtros['hasta'] ?? '') !== '' ? date('d-m-Y', strtotime($filtros['hasta'])) : 'hoy';
    $periodoTxt = $desdeLbl . ' → ' . $hastaLbl;
}
?>

<style>
.cb-rep-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem 1rem;
    align-items: flex-end;
    margin-bottom: 0.25rem;
}
.cb-rep-toolbar .form-group { margin: 0; min-width: 140px; }
.cb-rep-toolbar .form-label { font-size: 0.72rem; margin-bottom: 0.3rem; }
.cb-rep-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.75rem;
    margin: 1rem 0 1.25rem;
}
.cb-rep-kpi {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 0.85rem 1rem;
}
.cb-rep-kpi span { display: block; font-size: 0.72rem; color: var(--text-muted); margin-bottom: 0.2rem; }
.cb-rep-kpi strong { font-family: var(--font-heading); font-size: 1.15rem; }
.cb-rep-meta { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem; }
.cb-rep-socio { font-weight: 600; color: var(--text-main); }
.cb-rep-rut { font-family: monospace; font-size: 0.82rem; color: var(--primary); }
.cb-rep-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
@media print {
    .sidebar, .topbar, .cb-no-print, .alert, .maestro-finanzas-banner { display: none !important; }
    .main-content, .content-area, .page-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .card { border: none !important; box-shadow: none !important; background: #fff !important; color: #111 !important; }
    .cb-rep-kpi { border: 1px solid #ccc !important; }
    table { font-size: 11px; }
    a { text-decoration: none; color: inherit; }
}
</style>

<?php require APPROOT . '/views/partials/maestro_finanzas_banner.php'; ?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<div class="card card-primary">
    <div class="cb-no-print" style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
        <div>
            <h3 style="font-family: var(--font-heading); font-size: 1.15rem; margin: 0 0 0.25rem;">Filtros del reporte</h3>
            <p style="margin:0; font-size:0.82rem; color:var(--text-muted);">
                Use fechas y tipo para cuadrar caja. En cuotas verá RUT y nombre del socio.
            </p>
        </div>
        <div class="cb-rep-actions">
            <a href="<?php echo URLROOT; ?>/admin/finanzas" class="btn btn-secondary btn-sm">Volver a Movimientos</a>
            <a href="<?php echo URLROOT; ?>/admin/reporte_movimientos?<?php echo htmlspecialchars($qsBase . ($qsBase !== '' ? '&' : '') . 'export=csv'); ?>" class="btn btn-secondary btn-sm">Descargar CSV</a>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Imprimir</button>
        </div>
    </div>

    <form method="get" action="<?php echo URLROOT; ?>/admin/reporte_movimientos" class="cb-rep-toolbar cb-no-print">
        <div class="form-group">
            <label class="form-label" for="desde">Desde</label>
            <input type="date" id="desde" name="desde" class="form-control" value="<?php echo htmlspecialchars($filtros['desde'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label class="form-label" for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" class="form-control" value="<?php echo htmlspecialchars($filtros['hasta'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label class="form-label" for="tipo">Tipo</label>
            <select id="tipo" name="tipo" class="form-control">
                <option value="" <?php echo ($filtros['tipo'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                <option value="ingreso" <?php echo ($filtros['tipo'] ?? '') === 'ingreso' ? 'selected' : ''; ?>>Solo ingresos</option>
                <option value="egreso" <?php echo ($filtros['tipo'] ?? '') === 'egreso' ? 'selected' : ''; ?>>Solo egresos</option>
            </select>
        </div>
        <div class="form-group" style="min-width:180px;">
            <label class="form-label" for="categoria">Categoría</label>
            <select id="categoria" name="categoria" class="form-control">
                <option value="">Todas</option>
                <?php foreach (($data['categorias'] ?? []) as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($filtros['categoria'] ?? '') === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="display:flex; gap:0.45rem; align-items:flex-end;">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="<?php echo URLROOT; ?>/admin/reporte_movimientos?todos=1" class="btn btn-secondary">Ver todos</a>
        </div>
    </form>
</div>

<div class="card card-primary" style="margin-top:1rem;">
    <div class="cb-rep-print-header" style="margin-bottom:0.75rem;">
        <h3 style="font-family: var(--font-heading); font-size: 1.2rem; margin: 0 0 0.2rem;">
            <?php echo htmlspecialchars($junta->nombre ?? 'Organización'); ?>
        </h3>
        <p class="cb-rep-meta" style="margin:0;">
            Reporte de movimientos · Periodo: <strong><?php echo htmlspecialchars($periodoTxt); ?></strong>
            <?php if (($filtros['tipo'] ?? '') !== ''): ?>
                · Tipo: <strong><?php echo htmlspecialchars($filtros['tipo']); ?></strong>
            <?php endif; ?>
            <?php if (($filtros['categoria'] ?? '') !== ''): ?>
                · Categoría: <strong><?php echo htmlspecialchars($filtros['categoria']); ?></strong>
            <?php endif; ?>
            · Generado: <?php echo date('d-m-Y H:i'); ?>
        </p>
    </div>

    <div class="cb-rep-kpis">
        <div class="cb-rep-kpi">
            <span>Ingresos</span>
            <strong style="color: var(--success);">$<?php echo number_format((int)$totales['ingresos'], 0, ',', '.'); ?></strong>
        </div>
        <div class="cb-rep-kpi">
            <span>Egresos</span>
            <strong style="color: var(--danger);">$<?php echo number_format((int)$totales['egresos'], 0, ',', '.'); ?></strong>
        </div>
        <div class="cb-rep-kpi">
            <span>Neto del periodo</span>
            <strong style="color: <?php echo ((int)$totales['neto'] >= 0) ? 'var(--info)' : 'var(--danger)'; ?>;">
                $<?php echo number_format((int)$totales['neto'], 0, ',', '.'); ?>
            </strong>
        </div>
        <div class="cb-rep-kpi">
            <span>Recaudado en cuotas</span>
            <strong style="color: var(--primary);">$<?php echo number_format((int)$totales['cuotas'], 0, ',', '.'); ?></strong>
        </div>
        <div class="cb-rep-kpi">
            <span>Cantidad de movimientos</span>
            <strong><?php echo (int)$totales['cantidad']; ?></strong>
        </div>
    </div>

    <?php if (empty($movimientos)): ?>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.5rem 0 0;">
            No hay movimientos con los filtros seleccionados.
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Categoría</th>
                        <th>Socio (RUT / Nombre)</th>
                        <th>Mes cuota</th>
                        <th>Detalle</th>
                        <th style="text-align:right;">Monto</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $i => $t):
                        $esCuota = in_array($t->categoria ?? '', ['Cuota Socio', 'Cuota Condonada'], true);
                        $nombreSocio = Transaccion::socioNombreCompleto($t);
                        $rutSocio = trim((string)($t->socio_rut ?? ''));
                        $admin = trim(($t->admin_nombre ?? '') . ' ' . ($t->admin_apellido_paterno ?? ''));
                        $monto = (int)($t->monto ?? 0);
                        $esExento = ($t->categoria ?? '') === 'Cuota Condonada';
                    ?>
                        <tr>
                            <td style="color:var(--text-muted); font-size:0.75rem;"><?php echo $i + 1; ?></td>
                            <td style="font-family:monospace; font-size:0.82rem; white-space:nowrap;">
                                <?php echo !empty($t->fecha) ? date('d-m-Y', strtotime($t->fecha)) : '—'; ?>
                            </td>
                            <td>
                                <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; color: <?php echo ($t->tipo ?? '') === 'ingreso' ? 'var(--success)' : 'var(--danger)'; ?>;">
                                    <?php echo htmlspecialchars($t->tipo ?? ''); ?>
                                </span>
                            </td>
                            <td style="font-weight:600; font-size:0.85rem;">
                                <?php if ($esExento): ?>
                                    <span style="color:var(--warning); font-size:0.7rem; border:1px solid var(--warning); padding:0.05rem 0.3rem; border-radius:4px; margin-right:0.25rem;">EXENTO</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($t->categoria ?? ''); ?>
                            </td>
                            <td>
                                <?php if ($esCuota || !empty($t->socio_id)): ?>
                                    <?php if ($rutSocio !== ''): ?>
                                        <div class="cb-rep-rut"><?php echo htmlspecialchars($rutSocio); ?></div>
                                    <?php endif; ?>
                                    <div class="cb-rep-socio"><?php echo htmlspecialchars($nombreSocio !== '' ? $nombreSocio : 'Socio no disponible'); ?></div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-family:monospace; font-size:0.8rem;">
                                <?php echo !empty($t->mes_pagado) ? htmlspecialchars($t->mes_pagado) : '—'; ?>
                            </td>
                            <td style="font-size:0.8rem; color:var(--text-muted); max-width:220px;">
                                <?php echo htmlspecialchars($t->descripcion ?? '') !== '' ? htmlspecialchars($t->descripcion) : '—'; ?>
                            </td>
                            <td style="text-align:right; font-weight:700; white-space:nowrap; color: <?php echo $esExento ? 'var(--warning)' : (($t->tipo ?? '') === 'ingreso' ? 'var(--success)' : 'var(--danger)'); ?>;">
                                <?php
                                if ($esExento) {
                                    echo 'Exento';
                                } else {
                                    echo (($t->tipo ?? '') === 'ingreso' ? '+' : '-') . '$' . number_format($monto, 0, ',', '.');
                                }
                                ?>
                            </td>
                            <td style="font-size:0.78rem; color:var(--text-muted);">
                                <?php echo $admin !== '' ? htmlspecialchars($admin) : '—'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" style="text-align:right; font-weight:700;">Totales del periodo</td>
                        <td style="text-align:right; font-weight:800; white-space:nowrap;">
                            <div style="color:var(--success); font-size:0.8rem;">+ $<?php echo number_format((int)$totales['ingresos'], 0, ',', '.'); ?></div>
                            <div style="color:var(--danger); font-size:0.8rem;">− $<?php echo number_format((int)$totales['egresos'], 0, ',', '.'); ?></div>
                            <div style="margin-top:0.25rem;">Neto $<?php echo number_format((int)$totales['neto'], 0, ',', '.'); ?></div>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="cb-no-print" style="font-size:0.72rem; color:var(--text-muted); margin:0.75rem 0 0;">
            Tip: filtre por categoría <strong>Cuota Socio</strong> para revisar solo pagos de cuotas con RUT y nombre.
            Use <strong>Descargar CSV</strong> para cruzar montos en Excel.
        </p>
    <?php endif; ?>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
