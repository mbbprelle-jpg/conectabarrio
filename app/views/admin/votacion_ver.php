<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php $v = $data['votacion']; ?>

<?php if (!empty($data['success'])): ?><div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div><?php endif; ?>
<?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div><?php endif; ?>

<div class="card card-primary" style="margin-bottom:1.5rem;">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <p style="font-size:0.85rem;color:var(--text-muted);margin:0;"><?php echo nl2br(htmlspecialchars($v->descripcion ?? '')); ?></p>
            <p style="font-size:0.8rem;color:var(--text-muted);margin-top:0.75rem;">
                <?php
                $visLabel = [
                    'al_votar' => 'el socio ve los % después de responder',
                    'todos' => 'todos los electores ven los %',
                    'directiva' => 'los % solo los ve la directiva',
                ][$v->resultados_visibilidad ?? 'directiva'] ?? 'resultados reservados';
                ?>
                <?php echo date('d/m/Y H:i', strtotime($v->fecha_inicio)); ?> — <?php echo date('d/m/Y H:i', strtotime($v->fecha_fin)); ?>
                · Audiencia: <?php echo htmlspecialchars(str_replace('_', ' ', $v->audiencia_tipo)); ?>
                · <?php echo htmlspecialchars($visLabel); ?>
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

<?php
$votacion = $v;
$resultados = $data['resultados'];
$parciales_url = $data['parciales_url'] ?? '';
require APPROOT . '/views/partials/votacion_resultados.php';
?>

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
                    <td><?php
                        $partes = [];
                        if (!empty($d->opcion_texto)) $partes[] = $d->opcion_texto;
                        if (!empty($d->seguimiento_opcion_texto)) $partes[] = $d->seguimiento_opcion_texto;
                        elseif (!empty($d->seguimiento_texto)) $partes[] = $d->seguimiento_texto;
                        if (!empty($d->respuesta_texto)) $partes[] = $d->respuesta_texto;
                        echo htmlspecialchars($partes ? implode(' · ', $partes) : '—');
                    ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
