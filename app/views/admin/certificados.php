<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php if (!empty($data['success'])): ?><div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div><?php endif; ?>
<?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div><?php endif; ?>

<div class="card card-warning" style="margin-bottom:1.5rem;">
    <h3 class="card-title">Enlace para quien escribe desde fuera</h3>
    <p style="font-size:0.85rem;color:var(--text-muted);">Si alguien le pide el certificado por mensaje, envíe este enlace. La persona completa nombre, RUT, calle, número y motivo.</p>
    <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars((string)$data['link_publico']); ?>" onclick="this.select();">
    <form method="POST" action="<?php echo URLROOT; ?>/admin/certificado_folio" style="margin-top:1rem;display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;">
            <label class="form-label">Último folio ya emitido</label>
            <input type="number" name="folio_actual" class="form-control" min="0" max="999999" required value="<?php echo (int)$data['folio_actual']; ?>">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">Guardar folio</button>
        <p style="font-size:0.8rem;color:var(--text-muted);margin:0;">El próximo impreso será el <strong><?php echo (int)$data['folio_actual'] + 1; ?></strong>.</p>
    </form>
</div>

<div class="card card-primary">
    <h3 class="card-title">Solicitudes</h3>
    <?php if (empty($data['solicitudes'])): ?>
        <p style="color:var(--text-muted);">No hay solicitudes.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>Ingreso</th><th>Persona</th><th>Dirección</th><th>Origen</th><th>Valor</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($data['solicitudes'] as $s): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime((string)$s->created_at)); ?></td>
                    <td><?php echo htmlspecialchars((string)$s->beneficiario_nombre); ?><br><span style="font-size:0.75rem;color:var(--text-muted);"><?php echo htmlspecialchars((string)$s->beneficiario_rut); ?></span></td>
                    <td><?php echo htmlspecialchars((string)$s->direccion); ?></td>
                    <td><?php echo $s->origen === 'publico' ? 'Enlace' : ($s->para === 'domicilio' ? 'Domicilio del socio' : 'Socio'); ?></td>
                    <td>$<?php echo number_format((int)$s->monto, 0, ',', '.'); ?><?php if ($s->metodo_pago): ?><br><span style="font-size:0.75rem;"><?php echo $s->metodo_pago === 'transferencia' ? 'Transferencia' : 'Efectivo'; ?></span><?php endif; ?></td>
                    <td>
                        <?php if ($s->estado === 'emitido'): ?>Folio <?php echo (int)$s->correlativo; ?>
                        <?php elseif ($s->estado === 'rechazado'): ?>Rechazado
                        <?php else: ?>Pendiente<?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s->estado === 'emitido'): ?>
                            <a class="btn btn-secondary btn-sm" href="<?php echo URLROOT; ?>/admin/certificado_imprimir/<?php echo (int)$s->id; ?>">Imprimir</a>
                        <?php elseif ($s->estado === 'pendiente'): ?>
                            <a class="btn btn-primary btn-sm" href="<?php echo URLROOT; ?>/admin/certificado_ver/<?php echo (int)$s->id; ?>">Revisar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
