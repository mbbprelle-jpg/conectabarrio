<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php $v = $data['votacion']; $totalVotos = array_sum(array_map(static fn($o) => (int)$o->votos, $data['opciones'])); ?>

<?php if (!empty($data['success'])): ?><div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div><?php endif; ?>
<?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div><?php endif; ?>

<div class="card card-primary" style="margin-bottom:1.5rem;">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <p style="font-size:0.85rem;color:var(--text-muted);margin:0;"><?php echo nl2br(htmlspecialchars($v->descripcion ?? '')); ?></p>
            <p style="font-size:0.8rem;color:var(--text-muted);margin-top:0.75rem;">
                <?php echo date('d/m/Y H:i', strtotime($v->fecha_inicio)); ?> — <?php echo date('d/m/Y H:i', strtotime($v->fecha_fin)); ?>
                · Audiencia: <?php echo htmlspecialchars(str_replace('_', ' ', $v->audiencia_tipo)); ?>
            </p>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <?php if ($v->estado === 'borrador'): ?>
            <form action="<?php echo URLROOT; ?>/admin/votacion_publicar" method="POST"><input type="hidden" name="votacion_id" value="<?php echo (int)$v->id; ?>"><button class="btn btn-primary btn-sm">Activar</button></form>
            <?php endif; ?>
            <?php if ($v->estado === 'activa'): ?>
            <form action="<?php echo URLROOT; ?>/admin/votacion_cerrar" method="POST"><input type="hidden" name="votacion_id" value="<?php echo (int)$v->id; ?>"><button class="btn btn-secondary btn-sm">Cerrar ahora</button></form>
            <a href="<?php echo URLROOT; ?>/admin/votacion_votar/<?php echo (int)$v->id; ?>" class="btn btn-success btn-sm">Participar</a>
            <?php endif; ?>
            <a href="<?php echo URLROOT; ?>/admin/votaciones" class="btn btn-secondary btn-sm">Volver</a>
        </div>
    </div>
</div>

<div class="card card-warning" style="margin-bottom:1.5rem;">
    <h3 class="card-title">Enlace para electores</h3>
    <p style="font-size:0.85rem;color:var(--text-muted);">Comparta este enlace por correo o WhatsApp. Si no están logueados, ingresarán al portal y serán dirigidos a votar.</p>
    <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($data['link_publico']); ?>" onclick="this.select();">
</div>

<div class="card card-success">
    <h3 class="card-title">Resultados (<?php echo $totalVotos; ?> respuesta<?php echo $totalVotos === 1 ? '' : 's'; ?>)</h3>
    <?php foreach ($data['opciones'] as $o):
        $pct = $totalVotos > 0 ? round(((int)$o->votos / $totalVotos) * 100) : 0;
    ?>
    <div style="margin-bottom:1rem;">
        <div style="display:flex;justify-content:space-between;font-size:0.9rem;margin-bottom:0.25rem;">
            <span><?php echo htmlspecialchars($o->texto); ?></span>
            <strong><?php echo (int)$o->votos; ?> (<?php echo $pct; ?>%)</strong>
        </div>
        <div style="height:8px;background:rgba(255,255,255,0.06);border-radius:4px;overflow:hidden;">
            <div style="width:<?php echo $pct; ?>%;height:100%;background:var(--gradient-primary);"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($data['puede_ver_detalle'] && !empty($data['detalle_votantes'])): ?>
<div class="card card-primary" style="margin-top:1.5rem;">
    <h3 class="card-title">Detalle de votantes (solo administrador y creador)</h3>
    <p style="font-size:0.8rem;color:var(--text-muted);margin:-0.5rem 0 1rem;">Información confidencial resguardada según política de la organización.</p>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Persona</th><th>RUT</th><th>Respuesta</th></tr></thead>
            <tbody>
            <?php foreach ($data['detalle_votantes'] as $d): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($d->created_at)); ?></td>
                    <td><?php echo htmlspecialchars($d->nombre); ?></td>
                    <td style="font-family:monospace;font-size:0.85rem;"><?php echo htmlspecialchars($d->rut); ?></td>
                    <td><?php echo htmlspecialchars($d->opcion_texto ?: ($d->respuesta_texto ?: '—')); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
