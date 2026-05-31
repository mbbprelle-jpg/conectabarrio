<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<!-- Contenedor Principal de Comprobantes -->
<div class="card card-primary" style="margin-top: 1rem;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <h3 class="card-title" style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Historial de Comprobantes de Mensualidad
        </h3>
        
        <!-- Input de Filtro Interactivo Rápido -->
        <div style="position: relative; width: 100%; max-width: 250px;">
            <input type="text" id="comprobanteSearch" placeholder="Buscar mes o folio..." class="form-control" style="padding-left: 2.25rem; font-size: 0.85rem;">
            <div style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); display: flex; align-items: center; pointer-events: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
        </div>
    </div>

    <!-- Mensajes Vacíos o Tabla -->
    <?php if (empty($data['transacciones'])): ?>
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 300px; text-align: center; color: var(--text-muted);">
            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1.5rem; color: rgba(255,255,255,0.1);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <p style="font-size: 1.1rem; font-weight: 500;">Aún no se registran comprobantes de pago o aportes.</p>
            <p style="font-size: 0.85rem; margin-top: 0.35rem;">Toda cuota, condonación o donación que registre el administrador de su junta aparecerá en este listado digital de manera inmediata.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" id="comprobantesTable">
                <thead>
                    <tr>
                        <th style="width: 100px;">Folio</th>
                        <th>Concepto / Cobertura</th>
                        <th>Monto Aportado</th>
                        <th>Fecha de Operación</th>
                        <th>Registrado Por</th>
                        <th style="text-align: right; width: 170px;">Detalles / Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['transacciones'] as $t): ?>
                        <tr class="comprobante-row">
                            <td style="font-family: monospace; font-weight: 700; color: var(--text-muted);">
                                #<?php echo str_pad($t->id, 6, '0', STR_PAD_LEFT); ?>
                            </td>
                            <td>
                                <?php if ($t->categoria === 'Cuota Socio'): ?>
                                    <span class="badge badge-success" style="font-size: 0.78rem; padding: 0.25rem 0.5rem; margin-right: 0.5rem; text-transform: uppercase;">Cuota</span>
                                    <strong>Mes: <?php echo htmlspecialchars($t->mes_pagado); ?></strong>
                                <?php elseif ($t->categoria === 'Cuota Condonada'): ?>
                                    <span class="badge badge-warning" style="font-size: 0.78rem; padding: 0.25rem 0.5rem; margin-right: 0.5rem; text-transform: uppercase;">Exento</span>
                                    <strong>Mes: <?php echo htmlspecialchars($t->mes_pagado); ?></strong>
                                <?php else: ?>
                                    <span class="badge badge-info" style="font-size: 0.78rem; padding: 0.25rem 0.5rem; margin-right: 0.5rem; text-transform: uppercase;">Aporte Extra</span>
                                    <strong><?php echo htmlspecialchars($t->categoria); ?></strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t->categoria === 'Cuota Condonada'): ?>
                                    <strong style="color: var(--warning); font-family: var(--font-heading); font-size: 0.95rem;">
                                        $0 CLP (Eximido)
                                    </strong>
                                <?php elseif ($t->categoria === 'Cuota Socio'): ?>
                                    <strong style="color: var(--success); font-family: var(--font-heading); font-size: 0.98rem;">
                                        $<?php echo number_format($t->monto, 0, ',', '.'); ?> CLP
                                    </strong>
                                <?php else: ?>
                                    <strong style="color: var(--primary); font-family: var(--font-heading); font-size: 0.98rem;">
                                        $<?php echo number_format($t->monto, 0, ',', '.'); ?> CLP
                                    </strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('d-m-Y', strtotime($t->fecha)); ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.9rem;">
                                <?php echo htmlspecialchars($t->admin_nombre ?? 'Sistema'); ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if (in_array($t->categoria, ['Cuota Socio', 'Cuota Condonada'], true)): ?>
                                    <a href="<?php echo URLROOT; ?>/socio/comprobante/<?php echo $t->id; ?>" target="_blank" class="btn btn-primary btn-sm" style="padding: 0.4rem 0.8rem; display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                        Ver comprobante
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 0.78rem; color: var(--text-muted); font-style: italic;">
                                        <?php echo htmlspecialchars($t->descripcion ?? 'Registrado'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<!-- Script interactivo local para buscar instantáneamente en la tabla -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('comprobanteSearch');
    const tableRows = document.querySelectorAll('.comprobante-row');

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            
            tableRows.forEach(row => {
                const textContent = row.textContent.toLowerCase();
                if (textContent.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
