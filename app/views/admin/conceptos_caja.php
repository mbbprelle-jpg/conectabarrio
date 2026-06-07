<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php if (!empty($data['migration_pending'])): ?>
<div class="alert alert-warning" style="margin-bottom: 1.25rem;">
    <span>
        Falta aplicar la migración SQL <strong>sql/add_finanzas_saldo_conceptos.sql</strong> en la base de datos de producción.
        Sin esa tabla (<code>finanzas_conceptos</code>) no puede usar Conceptos de Caja ni el saldo inicial de arranque.
        Contacte a quien administra el servidor o ejecútela en MySQL y vuelva a desplegar la aplicación.
    </span>
</div>
<div class="cb-conceptos-nav-footer">
    <a href="<?php echo URLROOT; ?>/admin/finanzas" class="btn btn-secondary">← Movimientos</a>
</div>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
<?php return; ?>
<?php endif; ?>

<?php
$tabActiva = $data['tab_activa'] ?? 'ingreso';
$bloques = [
    'ingreso' => [
        'titulo' => 'Conceptos de Ingreso',
        'items' => $data['conceptos_ingreso'] ?? [],
        'card' => 'card-success',
        'color' => 'var(--success)',
    ],
    'egreso' => [
        'titulo' => 'Conceptos de Egreso',
        'items' => $data['conceptos_egreso'] ?? [],
        'card' => 'card-danger',
        'color' => 'var(--danger)',
    ],
];
$bloque = $bloques[$tabActiva];
?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<div class="cb-tabs" role="tablist" aria-label="Tipo de concepto">
    <a href="<?php echo URLROOT; ?>/admin/conceptos_caja?tab=ingreso"
       class="cb-tab <?php echo $tabActiva === 'ingreso' ? 'is-active' : ''; ?>"
       role="tab" aria-selected="<?php echo $tabActiva === 'ingreso' ? 'true' : 'false'; ?>">
        <span class="cb-tab-dot" style="background: var(--success);"></span>
        Ingresos
        <span class="cb-tab-count"><?php echo count($data['conceptos_ingreso'] ?? []); ?></span>
    </a>
    <a href="<?php echo URLROOT; ?>/admin/conceptos_caja?tab=egreso"
       class="cb-tab <?php echo $tabActiva === 'egreso' ? 'is-active' : ''; ?>"
       role="tab" aria-selected="<?php echo $tabActiva === 'egreso' ? 'true' : 'false'; ?>">
        <span class="cb-tab-dot" style="background: var(--danger);"></span>
        Egresos
        <span class="cb-tab-count"><?php echo count($data['conceptos_egreso'] ?? []); ?></span>
    </a>
</div>

<div class="card <?php echo $bloque['card']; ?> cb-conceptos-panel">
    <div class="cb-conceptos-panel-head">
        <div>
            <h3 class="card-title" style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($bloque['titulo']); ?></h3>
            <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0; line-height: 1.4;">
                Agrupa los movimientos en Finanzas y en el flujo de caja. Solo puede <strong>eliminar</strong> conceptos sin movimientos; si ya se usó, <strong>desactívelo</strong>.
            </p>
        </div>
    </div>

    <form action="<?php echo URLROOT; ?>/admin/concepto_caja_crear" method="POST" class="cb-conceptos-add-form">
        <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tabActiva); ?>">
        <input type="text" name="nombre" class="form-control" placeholder="Nombre del nuevo concepto…" required maxlength="100" autocomplete="off">
        <button type="submit" class="btn btn-primary">+ Agregar concepto</button>
    </form>

    <?php if (empty($bloque['items'])): ?>
        <p class="cb-conceptos-empty">No hay conceptos en esta categoría. Agregue el primero arriba.</p>
    <?php else: ?>
        <div class="table-responsive cb-conceptos-table-wrap">
            <table class="table cb-conceptos-table">
                <thead>
                    <tr>
                        <th style="width: 36px;">#</th>
                        <th>Concepto</th>
                        <th style="width: 100px; text-align: center;">Movimientos</th>
                        <th style="width: 110px; text-align: center;">Estado</th>
                        <th style="width: 200px; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bloque['items'] as $i => $c): ?>
                    <tr class="<?php echo empty($c->activo) ? 'is-inactive' : ''; ?>" data-concepto-id="<?php echo (int)$c->id; ?>">
                        <td style="color: var(--text-muted); font-size: 0.8rem;"><?php echo $i + 1; ?></td>
                        <td>
                            <span class="cb-concepto-nombre"><?php echo htmlspecialchars($c->nombre); ?></span>
                        </td>
                        <td style="text-align: center;">
                            <?php if ((int)$c->uso_count > 0): ?>
                                <span class="badge badge-info" title="Movimientos registrados con este concepto"><?php echo (int)$c->uso_count; ?></span>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.8rem;">0</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if (!empty($c->activo)): ?>
                                <span class="cb-status-pill cb-status-pill--active">Activo</span>
                            <?php else: ?>
                                <span class="cb-status-pill cb-status-pill--inactive">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div class="cb-conceptos-actions">
                                <button type="button" class="btn btn-secondary btn-sm cb-btn-edit-concepto"
                                    data-id="<?php echo (int)$c->id; ?>"
                                    data-nombre="<?php echo htmlspecialchars($c->nombre, ENT_QUOTES); ?>"
                                    data-activo="<?php echo !empty($c->activo) ? '1' : '0'; ?>"
                                    data-uso="<?php echo (int)$c->uso_count; ?>">
                                    Editar
                                </button>
                                <form action="<?php echo URLROOT; ?>/admin/concepto_caja_actualizar" method="POST" style="display: inline;">
                                    <input type="hidden" name="concepto_id" value="<?php echo (int)$c->id; ?>">
                                    <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($c->nombre); ?>">
                                    <input type="hidden" name="activo" value="<?php echo !empty($c->activo) ? '0' : '1'; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo !empty($c->activo) ? 'btn-warning' : 'btn-success'; ?>" title="<?php echo !empty($c->activo) ? 'Desactivar' : 'Activar'; ?>">
                                        <?php echo !empty($c->activo) ? 'Desactivar' : 'Activar'; ?>
                                    </button>
                                </form>
                                <?php if (!empty($c->puede_eliminar)): ?>
                                <form action="<?php echo URLROOT; ?>/admin/concepto_caja_eliminar" method="POST" style="display: inline;" class="cb-form-delete-concepto">
                                    <input type="hidden" name="concepto_id" value="<?php echo (int)$c->id; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                                <?php else: ?>
                                <button type="button" class="btn btn-danger btn-sm" disabled title="Tiene movimientos asociados — use Desactivar">Eliminar</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="cb-conceptos-nav-footer">
    <a href="<?php echo URLROOT; ?>/admin/finanzas" class="btn btn-secondary">← Movimientos</a>
    <?php require_once APPROOT . '/core/AuthContext.php'; ?>
    <?php if (AuthContext::canViewFlujoCaja()): ?>
    <a href="<?php echo URLROOT; ?>/admin/flujo_caja" class="btn btn-secondary">Ver Flujo de Caja</a>
    <?php endif; ?>
</div>

<!-- Modal editar concepto -->
<div id="cbConceptoModal" class="cb-modal-overlay" aria-hidden="true">
    <div class="cb-modal-box" role="dialog" aria-labelledby="cbConceptoModalTitle">
        <h3 id="cbConceptoModalTitle" class="cb-modal-title">Editar concepto</h3>
        <form id="cbConceptoEditForm" action="<?php echo URLROOT; ?>/admin/concepto_caja_actualizar" method="POST">
            <input type="hidden" name="concepto_id" id="edit_concepto_id">
            <div class="form-group">
                <label for="edit_concepto_nombre" class="form-label">Nombre *</label>
                <input type="text" name="nombre" id="edit_concepto_nombre" class="form-control" required maxlength="100">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="switch-container" style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="activo" id="edit_concepto_activo" value="1" style="width: 18px; height: 18px;">
                    <span style="font-size: 0.9rem;">Concepto activo (visible al registrar movimientos)</span>
                </label>
            </div>
            <p id="edit_concepto_uso_hint" class="cb-modal-hint" hidden></p>
            <div class="cb-modal-actions">
                <button type="button" class="btn btn-secondary" id="cbConceptoModalCancel">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('cbConceptoModal');
    const form = document.getElementById('cbConceptoEditForm');
    const cancelBtn = document.getElementById('cbConceptoModalCancel');
    const usoHint = document.getElementById('edit_concepto_uso_hint');

    document.querySelectorAll('.cb-btn-edit-concepto').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('edit_concepto_id').value = this.dataset.id;
            document.getElementById('edit_concepto_nombre').value = this.dataset.nombre;
            document.getElementById('edit_concepto_activo').checked = this.dataset.activo === '1';
            const uso = parseInt(this.dataset.uso || '0', 10);
            if (uso > 0) {
                usoHint.hidden = false;
                usoHint.textContent = 'Este concepto tiene ' + uso + ' movimiento(s). Si lo desactiva, no aparecerá en nuevos registros pero el historial se conserva.';
            } else {
                usoHint.hidden = true;
            }
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }

    document.querySelectorAll('.cb-form-delete-concepto').forEach(function(f) {
        f.addEventListener('submit', function(e) {
            if (!confirm('¿Eliminar este concepto? Solo es posible porque no tiene movimientos asociados.')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
