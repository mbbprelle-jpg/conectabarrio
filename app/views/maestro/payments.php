<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span><?php echo htmlspecialchars($data['success']); ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?php echo htmlspecialchars($data['error']); ?></span>
    </div>
<?php endif; ?>

<div class="metrics-grid">
    <div class="card metric-card card-success">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Meses pagados</span>
            <span class="metric-value"><?php echo (int)$data['summary']['paid']; ?></span>
        </div>
    </div>
    <div class="card metric-card card-danger">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Registros vencidos</span>
            <span class="metric-value"><?php echo (int)$data['summary']['overdue']; ?></span>
        </div>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <h3 class="card-title" style="margin-bottom: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2" ry="2"></rect><line x1="3" y1="11" x2="21" y2="11"></line></svg>
            Historial de Suscripciones
        </h3>
        <a href="<?php echo URLROOT; ?>/maestro/dashboard" class="btn btn-secondary btn-sm">Registrar pago desde Dashboard</a>
    </div>

    <?php if (empty($data['payments'])): ?>
        <p style="color: var(--text-muted); text-align: center; padding: 2rem;">Aún no hay pagos registrados. Use la acción <strong>Registrar Pago</strong> en el Dashboard Maestro.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Organización</th>
                        <th>Período</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Fecha de pago</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['payments'] as $p): ?>
                        <?php
                        $periodo = $p->mes_periodo ?? date('Y-m', strtotime($p->due_date));
                        $estadoLabels = ['paid' => 'Pagado', 'pending' => 'Pendiente', 'overdue' => 'Vencido'];
                        $estadoClasses = ['paid' => 'badge-success', 'pending' => 'badge-warning', 'overdue' => 'badge-danger'];
                        $estado = $p->status ?? 'pending';
                        $metodoLabels = $data['metodo_labels'];
                        $metodo = $p->metodo_pago ?? null;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($p->org_nombre); ?></strong></td>
                            <td><?php echo htmlspecialchars($periodo); ?></td>
                            <td>$<?php echo number_format($p->amount, 0, ',', '.'); ?></td>
                            <td><?php echo $metodo ? htmlspecialchars($metodoLabels[$metodo] ?? $metodo) : '—'; ?></td>
                            <td><?php echo !empty($p->paid_at) ? date('d-m-Y', strtotime($p->paid_at)) : '—'; ?></td>
                            <td><span class="badge <?php echo $estadoClasses[$estado] ?? 'badge-secondary'; ?>"><?php echo $estadoLabels[$estado] ?? $estado; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
