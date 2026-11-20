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
                        <th>Comprobante</th>
                        <th>Período</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Fecha de pago</th>
                        <th>Estado</th>
                        <th></th>
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
                            <td style="font-family: monospace; font-size: 0.8rem; color: var(--primary);">
                                <?php echo !empty($p->comprobante_correlativo) ? htmlspecialchars($p->comprobante_correlativo) : '—'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($periodo); ?></td>
                            <td>$<?php echo number_format($p->amount, 0, ',', '.'); ?></td>
                            <td><?php echo $metodo ? htmlspecialchars($metodoLabels[$metodo] ?? $metodo) : '—'; ?></td>
                            <td><?php echo !empty($p->paid_at) ? date('d-m-Y', strtotime($p->paid_at)) : '—'; ?></td>
                            <td><span class="badge <?php echo $estadoClasses[$estado] ?? 'badge-secondary'; ?>"><?php echo $estadoLabels[$estado] ?? $estado; ?></span></td>
                            <td>
                                <button type="button" class="btn btn-secondary btn-sm btn-corregir-pago"
                                    data-id="<?php echo (int)$p->id; ?>"
                                    data-org="<?php echo htmlspecialchars($p->org_nombre, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-mes="<?php echo htmlspecialchars($periodo, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-monto="<?php echo (int)$p->amount; ?>"
                                    data-metodo="<?php echo htmlspecialchars((string)($p->metodo_pago ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-fecha="<?php echo !empty($p->paid_at) ? date('Y-m-d', strtotime($p->paid_at)) : ''; ?>"
                                    data-estado="<?php echo htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-comprobante="<?php echo htmlspecialchars((string)($p->comprobante_correlativo ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    Corregir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="corregirPagoModal" class="glass-modal-overlay">
    <div class="glass-modal-container" style="max-width: 480px;">
        <button type="button" class="modal-close-btn" data-close-modal="corregirPagoModal" aria-label="Cerrar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h3 style="margin-bottom: 0.35rem;">Corregir pago</h3>
        <p id="corregirPagoOrg" style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.9rem;"></p>
        <form action="<?php echo URLROOT; ?>/maestro/pago_actualizar" method="POST">
            <input type="hidden" name="payment_id" id="corregir_payment_id">
            <div class="form-group">
                <label class="form-label" for="corregir_mes">Período</label>
                <input type="month" name="mes_periodo" id="corregir_mes" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="corregir_monto">Monto (CLP)</label>
                <input type="number" name="amount" id="corregir_monto" class="form-control" min="0" step="1" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="corregir_fecha">Fecha de pago</label>
                <input type="date" name="paid_at" id="corregir_fecha" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="corregir_metodo">Método</label>
                <select name="metodo_pago" id="corregir_metodo" class="form-control">
                    <option value="">Sin método</option>
                    <?php foreach ($data['metodo_labels'] as $clave => $etiqueta): ?>
                        <option value="<?php echo htmlspecialchars($clave); ?>"><?php echo htmlspecialchars($etiqueta); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="corregir_estado">Estado</label>
                <select name="status" id="corregir_estado" class="form-control">
                    <option value="paid">Pagado</option>
                    <option value="pending">Pendiente</option>
                    <option value="overdue">Vencido</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Guardar corrección</button>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-corregir-pago').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('corregir_payment_id').value = btn.dataset.id || '';
        document.getElementById('corregir_mes').value = btn.dataset.mes || '';
        document.getElementById('corregir_monto').value = btn.dataset.monto || '0';
        document.getElementById('corregir_fecha').value = btn.dataset.fecha || '';
        document.getElementById('corregir_metodo').value = btn.dataset.metodo || '';
        document.getElementById('corregir_estado').value = btn.dataset.estado || 'paid';
        var extra = btn.dataset.comprobante ? ' · Comprobante ' + btn.dataset.comprobante : '';
        document.getElementById('corregirPagoOrg').textContent = (btn.dataset.org || '') + extra;
        var modal = document.getElementById('corregirPagoModal');
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    });
});
document.querySelectorAll('[data-close-modal="corregirPagoModal"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('corregirPagoModal').classList.remove('is-open');
        document.body.style.overflow = '';
    });
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
