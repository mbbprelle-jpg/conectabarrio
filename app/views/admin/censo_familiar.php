<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<?php if (!empty($data['migration_pending'])): ?>
    <div class="card card-primary">
        <p style="margin:0;">Para habilitar este módulo ejecute en MySQL el archivo:</p>
        <code style="display:block; margin-top:0.75rem;">sql/add_censo_familiar.sql</code>
    </div>
<?php else: ?>
    <?php
    $link = $data['link'] ?? null;
    $publicUrl = $link ? (URLROOT . '/publico/censo/' . $link->token) : '';
    ?>
    <div class="card card-primary" style="margin-bottom:1rem;">
        <h3 style="font-family:var(--font-heading); font-size:1.1rem; margin:0 0 0.5rem;">Link público para vecinos</h3>
        <p style="margin:0 0 0.75rem; font-size:0.85rem; color:var(--text-muted);">
            Comparta este enlace por WhatsApp. Cualquier persona puede registrar datos del adulto, hijos, discapacidad y embarazo.
        </p>
        <?php if ($publicUrl !== ''): ?>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
                <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($publicUrl); ?>"
                       id="censoPublicUrl" style="flex:1; min-width:220px; font-size:0.82rem;">
                <button type="button" class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('censoPublicUrl').value); this.textContent='Copiado';">
                    Copiar link
                </button>
                <a href="<?php echo htmlspecialchars($publicUrl); ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Abrir</a>
            </div>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="accion" value="generar_link">
                <button type="submit" class="btn btn-primary">Generar link público</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card card-primary">
        <h3 style="font-family:var(--font-heading); font-size:1.1rem; margin:0 0 0.75rem;">
            Respuestas recibidas (<?php echo count($data['registros'] ?? []); ?>)
        </h3>
        <?php if (empty($data['registros'])): ?>
            <p style="color:var(--text-muted); margin:0;">Aún no hay registros.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>RUT adulto</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Incluye</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['registros'] as $r): ?>
                            <tr>
                                <td style="font-size:0.8rem; white-space:nowrap;">
                                    <?php echo !empty($r->created_at) ? date('d-m-Y H:i', strtotime($r->created_at)) : '—'; ?>
                                </td>
                                <td style="font-family:monospace;"><?php echo htmlspecialchars($r->rut); ?></td>
                                <td><?php echo htmlspecialchars($r->nombre); ?></td>
                                <td><?php echo htmlspecialchars($r->telefono); ?></td>
                                <td style="font-size:0.8rem;">
                                    <?php
                                    echo htmlspecialchars($r->calle_nombre ?: ($r->direccion_texto ?? '—'));
                                    ?>
                                </td>
                                <td style="font-size:0.75rem;">
                                    <?php
                                    $tags = [];
                                    if (!empty($r->registra_hijos)) $tags[] = 'Hijos';
                                    if (!empty($r->registra_discapacidad)) $tags[] = 'Discap.';
                                    if (!empty($r->registra_embarazo)) $tags[] = 'Embarazo';
                                    echo $tags ? htmlspecialchars(implode(', ', $tags)) : '—';
                                    ?>
                                </td>
                                <td>
                                    <a class="btn btn-secondary btn-sm" href="<?php echo URLROOT; ?>/admin/censo_familiar?id=<?php echo (int)$r->id; ?>">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($data['detalle'])): ?>
        <?php $d = $data['detalle']; ?>
        <div class="card card-primary" style="margin-top:1rem;">
            <h3 style="font-family:var(--font-heading); font-size:1.1rem; margin:0 0 0.75rem;">
                Detalle #<?php echo (int)$d->id; ?> — <?php echo htmlspecialchars($d->nombre); ?>
            </h3>
            <p style="font-size:0.88rem; margin:0 0 0.75rem;">
                <strong>RUT:</strong> <?php echo htmlspecialchars($d->rut); ?> ·
                <strong>Tel:</strong> <?php echo htmlspecialchars($d->telefono); ?> ·
                <strong>Dir:</strong> <?php echo htmlspecialchars($d->calle_nombre ?: ($d->direccion_texto ?? '—')); ?>
            </p>
            <?php if (empty($data['personas'])): ?>
                <p style="color:var(--text-muted);">Sin personas asociadas (solo adulto).</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>RUT</th>
                            <th>Nombre</th>
                            <th>Sexo</th>
                            <th>Edad / Parto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['personas'] as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p->tipo); ?></td>
                                <td style="font-family:monospace;"><?php echo htmlspecialchars($p->rut); ?></td>
                                <td><?php echo htmlspecialchars($p->nombre_completo); ?></td>
                                <td><?php echo htmlspecialchars($p->sexo); ?></td>
                                <td>
                                    <?php
                                    if ($p->tipo === 'embarazo') {
                                        echo 'Parto: ' . htmlspecialchars($p->fecha_parto ?? '—');
                                        if (!empty($p->usa_datos_adulto)) echo ' (mismos datos adulto)';
                                    } else {
                                        echo htmlspecialchars((string)($p->edad ?? '—')) . ' años';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <a href="<?php echo URLROOT; ?>/admin/censo_familiar" class="btn btn-secondary btn-sm" style="margin-top:0.75rem;">Cerrar detalle</a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
