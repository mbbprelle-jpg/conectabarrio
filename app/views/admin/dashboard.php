<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
$mostrar_calendario = $data['mostrar_calendario'] ?? false;
if ($mostrar_calendario) {
    extract([
        'cal_mes' => $data['cal_mes'],
        'cal_anio' => $data['cal_anio'],
        'eventos_por_dia' => $data['eventos_por_dia'],
        'proximas' => $data['proximas'],
        'url_calendario' => $data['url_calendario'],
        'url_base_mes' => $data['url_base_mes'],
        'es_socio' => $data['es_socio'] ?? false,
    ], EXTR_SKIP);
    require APPROOT . '/views/partials/dashboard_actividades.php';
}
?>

<!-- Grid de Métricas Financieras -->
<div class="metrics-grid">
    
    <div class="card metric-card card-success">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Ingresos Totales</span>
            <span class="metric-value">$<?php echo number_format($data['balance']['ingresos'], 0, ',', '.'); ?></span>
            <span class="metric-hint">Solo movimientos registrados (no incluye saldo inicial)</span>
        </div>
    </div>

    <div class="card metric-card card-danger">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Egresos Totales</span>
            <span class="metric-value">$<?php echo number_format($data['balance']['egresos'], 0, ',', '.'); ?></span>
        </div>
    </div>

    <div class="card metric-card <?php echo ($data['balance']['contable'] ?? $data['balance']['neto']) >= 0 ? 'card-primary' : 'card-danger'; ?>">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="metric-info">
            <?php if ($data['balance']['saldo_inicial'] !== null): ?>
            <span class="metric-label">Saldo contable (Caja)</span>
            <span class="metric-value">$<?php echo number_format($data['balance']['contable'], 0, ',', '.'); ?></span>
            <span class="metric-hint">Saldo inicial $<?php echo number_format($data['balance']['saldo_inicial'], 0, ',', '.'); ?> + neto movimientos</span>
            <?php else: ?>
            <span class="metric-label">Saldo Neto (Caja)</span>
            <span class="metric-value">$<?php echo number_format($data['balance']['neto'], 0, ',', '.'); ?></span>
            <span class="metric-hint">Ingresos − egresos (sin saldo inicial declarado)</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card metric-card card-warning">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Asistencia Promedio</span>
            <span class="metric-value"><?php echo $data['promedio_asistencia']; ?>%</span>
        </div>
    </div>

</div>

<!-- Sección de Gráficos y Desglose -->
<div class="grid-2col">
    
    <!-- Gráfico del Flujo de Caja (Chart.js) -->
    <div class="card card-primary">
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            Flujo de Caja (Últimos Meses)
        </h3>
        
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="flujoCajaChart"></canvas>
        </div>
    </div>

    <!-- Widgets Informativos Secundarios -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Tarjeta de Distribución de Ingresos -->
        <div class="card card-warning" style="padding: 1.5rem;">
            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 1rem;">
                Composición de Ingresos
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.25rem;">
                        <span>Cuotas de Socios</span>
                        <span style="font-weight: 700; color: var(--primary);">$<?php echo number_format($data['balance']['cuotas'], 0, ',', '.'); ?></span>
                    </div>
                    <div style="height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden;">
                        <?php 
                        $pctCuotas = $data['balance']['ingresos'] > 0 ? ($data['balance']['cuotas'] / $data['balance']['ingresos']) * 100 : 0;
                        ?>
                        <div style="width: <?php echo $pctCuotas; ?>%; height: 100%; background: var(--gradient-primary); border-radius: 3px;"></div>
                    </div>
                </div>

                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.25rem;">
                        <span>Otros Conceptos</span>
                        <span style="font-weight: 700; color: var(--warning);">$<?php echo number_format($data['balance']['otros'], 0, ',', '.'); ?></span>
                    </div>
                    <div style="height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden;">
                        <?php 
                        $pctOtros = $data['balance']['ingresos'] > 0 ? ($data['balance']['otros'] / $data['balance']['ingresos']) * 100 : 0;
                        ?>
                        <div style="width: <?php echo $pctOtros; ?>%; height: 100%; background: var(--gradient-warning); border-radius: 3px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Vecinos Resumen -->
        <div class="card card-success" style="padding: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h4 style="font-size: 1rem; color: var(--text-muted); font-weight: 500;">Padrón Vecinal</h4>
                <div style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-heading); margin-top: 0.25rem;">
                    <?php echo htmlspecialchars($data['total_socios']); ?> vecinos
                </div>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Socios activos en el sistema</p>
            </div>
            
            <a href="<?php echo URLROOT; ?>/admin/socios" class="btn btn-secondary btn-sm" style="padding: 0.5rem 0.8rem; font-size: 0.8rem;">
                Administrar
            </a>
        </div>

    </div>

</div>

<!-- Configuración e Inicialización de Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Recuperar datos estructurados de PHP
    <?php
    $labels = [];
    $ingresos = [];
    $egresos = [];
    foreach ($data['flujo_historico'] as $item) {
        $labels[] = date('M Y', strtotime($item->mes . '-01'));
        $ingresos[] = (int)$item->ingresos;
        $egresos[] = (int)$item->egresos;
    }
    
    // Si no hay movimientos, mostrar meses en cero (no datos ficticios)
    if (empty($labels)) {
        $labels = [];
        $ingresos = [];
        $egresos = [];
        for ($i = 5; $i >= 0; $i--) {
            $labels[] = date('M Y', strtotime("-{$i} months"));
            $ingresos[] = 0;
            $egresos[] = 0;
        }
    }
    ?>

    const labels = <?php echo json_encode($labels); ?>;
    const ingresosData = <?php echo json_encode($ingresos); ?>;
    const egresosData = <?php echo json_encode($egresos); ?>;

    const ctx = document.getElementById('flujoCajaChart').getContext('2d');
    
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Ingresos ($)',
                    data: ingresosData,
                    backgroundColor: '#6366f1',
                    borderColor: '#4f46e5',
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.6
                },
                {
                    label: 'Egresos ($)',
                    data: egresosData,
                    backgroundColor: '#f59e0b',
                    borderColor: '#d97706',
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#f3f4f6',
                        font: {
                            family: 'Inter',
                            size: 12
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)'
                    },
                    ticks: {
                        color: '#9ca3af',
                        font: {
                            family: 'Inter'
                        }
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)'
                    },
                    ticks: {
                        color: '#9ca3af',
                        font: {
                            family: 'Inter'
                        },
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
