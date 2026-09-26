<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/AuthContext.php'; ?>

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
    $junta = $data['junta'] ?? null;
    $resumen = $data['resumen'] ?? [];
    $puedeGestionar = !empty($data['puede_gestionar']);
    $publicUrl = $link ? (URLROOT . '/publico/censo/' . $link->token) : '';
    $atajoUrl = URLROOT . '/publico/registro_familiar';
    ?>

    <div class="card card-primary" style="margin-bottom:1rem;">
        <h3 style="font-family:var(--font-heading); font-size:1.1rem; margin:0 0 0.35rem;">Junta asociada</h3>
        <p style="margin:0; font-size:0.9rem;">
            <strong><?php echo htmlspecialchars($junta->nombre ?? 'Organización'); ?></strong>
            <span style="color:var(--text-muted);"> · ID <?php echo (int)($junta->id ?? 0); ?></span>
            <?php if (!empty($junta->comuna)): ?>
                <span style="color:var(--text-muted);"> · <?php echo htmlspecialchars($junta->comuna); ?></span>
            <?php endif; ?>
        </p>
        <p style="margin:0.5rem 0 0; font-size:0.8rem; color:var(--text-muted);">
            Todos los registros de este formulario quedan asociados a esta junta. El administrador, el presidente, el secretario y la directiva pueden ver el avance aquí.
        </p>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:0.75rem; margin-bottom:1rem;">
        <div class="card" style="padding:0.85rem 1rem; margin:0;">
            <div style="font-size:0.72rem; color:var(--text-muted);">Adultos registrados</div>
            <strong style="font-size:1.35rem; font-family:var(--font-heading);"><?php echo (int)($resumen['total_registros'] ?? 0); ?></strong>
        </div>
        <div class="card" style="padding:0.85rem 1rem; margin:0;">
            <div style="font-size:0.72rem; color:var(--text-muted);">Hijos</div>
            <strong style="font-size:1.35rem; font-family:var(--font-heading);"><?php echo (int)($resumen['total_hijos'] ?? 0); ?></strong>
        </div>
        <div class="card" style="padding:0.85rem 1rem; margin:0;">
            <div style="font-size:0.72rem; color:var(--text-muted);">Discapacidad</div>
            <strong style="font-size:1.35rem; font-family:var(--font-heading);"><?php echo (int)($resumen['total_discapacidad'] ?? 0); ?></strong>
        </div>
        <div class="card" style="padding:0.85rem 1rem; margin:0;">
            <div style="font-size:0.72rem; color:var(--text-muted);">Embarazo</div>
            <strong style="font-size:1.35rem; font-family:var(--font-heading);"><?php echo (int)($resumen['total_embarazo'] ?? 0); ?></strong>
        </div>
    </div>

    <div class="card card-primary" style="margin-bottom:1rem;">
        <h3 style="font-family:var(--font-heading); font-size:1.1rem; margin:0 0 0.5rem;">Link público para vecinos</h3>
        <p style="margin:0 0 0.75rem; font-size:0.85rem; color:var(--text-muted);">
            Comparta este enlace por WhatsApp. Los datos se guardan en la junta indicada arriba.
        </p>
        <?php if ($publicUrl !== ''): ?>
            <label class="form-label">Link con token</label>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; margin-bottom:0.75rem;">
                <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($publicUrl); ?>"
                       id="censoPublicUrl" style="flex:1; min-width:220px; font-size:0.82rem;">
                <button type="button" class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('censoPublicUrl').value); this.textContent='Copiado';">
                    Copiar
                </button>
                <a href="<?php echo htmlspecialchars($publicUrl); ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Abrir</a>
            </div>
            <?php if ((int)($junta->id ?? 0) === 6): ?>
                <label class="form-label">Atajo corto (junta 6)</label>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
                    <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($atajoUrl); ?>"
                           id="censoAtajoUrl" style="flex:1; min-width:220px; font-size:0.82rem;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('censoAtajoUrl').value); this.textContent='Copiado';">
                        Copiar atajo
                    </button>
                </div>
            <?php endif; ?>
        <?php elseif ($puedeGestionar): ?>
            <form method="post">
                <input type="hidden" name="accion" value="generar_link">
                <button type="submit" class="btn btn-primary">Generar link público</button>
            </form>
        <?php else: ?>
            <p style="color:var(--text-muted); margin:0;">El administrador aún no ha generado el link público.</p>
        <?php endif; ?>
    </div>

    <div class="card card-primary">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:0.75rem; margin-bottom:0.75rem;">
            <h3 style="font-family:var(--font-heading); font-size:1.1rem; margin:0;">
                Respuestas recibidas (<?php echo count($data['registros'] ?? []); ?>)
            </h3>
            <?php if (!empty($data['registros'])): ?>
                <a href="<?php echo URLROOT; ?>/admin/censo_familiar_export" class="btn btn-primary btn-sm">
                    Exportar reporte (Excel)
                </a>
            <?php endif; ?>
        </div>
        <?php if (empty($data['registros'])): ?>
            <p style="color:var(--text-muted); margin:0;">Aún no hay registros.</p>
        <?php else: ?>
            <p style="margin:0 0 0.75rem; font-size:0.8rem; color:var(--text-muted);">
                El Excel tiene 2 hojas: <strong>Responsables</strong> (adultos) y <strong>Detalle</strong> (inscritos),
                vinculadas por la columna <strong>id_registro</strong>.
            </p>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
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
                                <td style="font-family:monospace;"><?php echo (int)$r->id; ?></td>
                                <td style="font-size:0.8rem; white-space:nowrap;">
                                    <?php echo !empty($r->created_at) ? date('d-m-Y H:i', strtotime($r->created_at)) : '—'; ?>
                                </td>
                                <td style="font-family:monospace;"><?php echo htmlspecialchars($r->rut); ?></td>
                                <td><?php echo htmlspecialchars($r->nombre); ?></td>
                                <td><?php echo htmlspecialchars($r->telefono); ?></td>
                                <td style="font-size:0.8rem;">
                                    <?php echo htmlspecialchars($r->direccion_texto ?: ($r->calle_nombre ?? '—')); ?>
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
                                <td style="white-space:nowrap;">
                                    <a class="btn btn-secondary btn-sm" href="<?php echo URLROOT; ?>/admin/censo_familiar?id=<?php echo (int)$r->id; ?>">Ver</a>
                                    <a class="btn btn-primary btn-sm" href="<?php echo URLROOT; ?>/admin/censo_familiar_export?id=<?php echo (int)$r->id; ?>">Excel</a>
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
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:0.75rem; margin-bottom:0.75rem;">
                <h3 style="font-family:var(--font-heading); font-size:1.1rem; margin:0;">
                    Detalle #<?php echo (int)$d->id; ?> — <?php echo htmlspecialchars($d->nombre); ?>
                </h3>
                <a class="btn btn-primary btn-sm" href="<?php echo URLROOT; ?>/admin/censo_familiar_export?id=<?php echo (int)$d->id; ?>">
                    Exportar este registro (Excel)
                </a>
            </div>
            <p style="font-size:0.88rem; margin:0 0 0.75rem;">
                <strong>ID registro:</strong> <?php echo (int)$d->id; ?> ·
                <strong>RUT:</strong> <?php echo htmlspecialchars($d->rut); ?> ·
                <strong>Tel:</strong> <?php echo htmlspecialchars($d->telefono); ?> ·
                <strong>Dir:</strong> <?php echo htmlspecialchars($d->direccion_texto ?: ($d->calle_nombre ?? '—')); ?>
            </p>
            <p style="font-size:0.78rem; color:var(--text-muted); margin:0 0 0.75rem;">
                En el Excel, la hoja Detalle usa <strong>id_registro = <?php echo (int)$d->id; ?></strong> para vincular con el adulto responsable.
            </p>
            <?php if (empty($data['personas'])): ?>
                <p style="color:var(--text-muted);">Sin personas asociadas (solo adulto).</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID persona</th>
                            <th>ID registro</th>
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
                                <td style="font-family:monospace;"><?php echo (int)$p->id; ?></td>
                                <td style="font-family:monospace;"><?php echo (int)$d->id; ?></td>
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
