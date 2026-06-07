<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success">
        <span><?php echo htmlspecialchars($data['success']); ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger">
        <span><?php echo htmlspecialchars($data['error']); ?></span>
    </div>
<?php endif; ?>

<div class="alert alert-info" style="margin-bottom: 1.5rem;">
    Los conceptos definen cómo se agrupan los movimientos en Finanzas y en los cierres mensuales. Puede activar, renombrar o agregar los suyos propios. Las cuotas de socios usan categorías del sistema y no aparecen aquí.
</div>

<div class="grid-2col">
    <?php
    $bloques = [
        ['tipo' => 'ingreso', 'titulo' => 'Conceptos de Ingreso', 'items' => $data['conceptos_ingreso'], 'card' => 'card-success'],
        ['tipo' => 'egreso', 'titulo' => 'Conceptos de Egreso', 'items' => $data['conceptos_egreso'], 'card' => 'card-danger'],
    ];
    foreach ($bloques as $bloque):
    ?>
    <div class="card <?php echo $bloque['card']; ?>">
        <h3 class="card-title"><?php echo htmlspecialchars($bloque['titulo']); ?></h3>

        <form action="<?php echo URLROOT; ?>/admin/concepto_caja_crear" method="POST" style="display: flex; gap: 0.5rem; margin-bottom: 1.25rem;">
            <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($bloque['tipo']); ?>">
            <input type="text" name="nombre" class="form-control" placeholder="Nuevo concepto…" required maxlength="100">
            <button type="submit" class="btn btn-primary btn-sm" style="white-space: nowrap;">Agregar</button>
        </form>

        <?php if (empty($bloque['items'])): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Sin conceptos registrados.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th style="width: 90px;">Estado</th>
                            <th style="width: 140px; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bloque['items'] as $c): ?>
                        <tr>
                            <td>
                                <form action="<?php echo URLROOT; ?>/admin/concepto_caja_actualizar" method="POST" style="display: flex; gap: 0.4rem; align-items: center;">
                                    <input type="hidden" name="concepto_id" value="<?php echo (int)$c->id; ?>">
                                    <input type="hidden" name="activo" value="<?php echo !empty($c->activo) ? '1' : '0'; ?>">
                                    <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($c->nombre); ?>" required maxlength="100" style="padding: 0.45rem 0.65rem; font-size: 0.85rem;">
                                    <button type="submit" class="btn btn-secondary btn-sm" title="Guardar nombre">✓</button>
                                </form>
                            </td>
                            <td>
                                <form action="<?php echo URLROOT; ?>/admin/concepto_caja_actualizar" method="POST">
                                    <input type="hidden" name="concepto_id" value="<?php echo (int)$c->id; ?>">
                                    <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($c->nombre); ?>">
                                    <input type="hidden" name="activo" value="<?php echo !empty($c->activo) ? '0' : '1'; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo !empty($c->activo) ? 'btn-success' : 'btn-secondary'; ?>" style="font-size: 0.72rem; padding: 0.25rem 0.5rem;">
                                        <?php echo !empty($c->activo) ? 'Activo' : 'Inactivo'; ?>
                                    </button>
                                </form>
                            </td>
                            <td style="text-align: right;">
                                <form action="<?php echo URLROOT; ?>/admin/concepto_caja_eliminar" method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este concepto? Los movimientos ya registrados conservarán el nombre histórico.');">
                                    <input type="hidden" name="concepto_id" value="<?php echo (int)$c->id; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" style="font-size: 0.72rem;">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<p style="margin-top: 1.5rem; text-align: center;">
    <a href="<?php echo URLROOT; ?>/admin/finanzas" class="btn btn-secondary">← Volver a Finanzas</a>
</p>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
