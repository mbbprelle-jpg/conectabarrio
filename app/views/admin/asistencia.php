<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/models/Reunion.php'; ?>
<?php $reunionModelHelper = new Reunion(); ?>

<?php if (!empty($data['success'])): ?>
<div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
<div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<?php
$miembros = $data['miembros'] ?? [];
$convocadosEdit = $data['convocados_edit'] ?? [];
$edit = $data['reunion_editar'] ?? null;
$det = $data['reunion_detalle'] ?? null;
$reuniones = $data['reuniones'] ?? [];
$totalReuniones = count($reuniones);

$tabActiva = 'listado';
if (!empty($edit) || (isset($_GET['tab']) && $_GET['tab'] === 'convocar')) {
    $tabActiva = 'convocar';
}

$urlBase = URLROOT . '/admin/asistencia';
$urlListado = $det ? $urlBase . '/' . (int)$det->id . '?tab=listado' : $urlBase . '?tab=listado';
$urlConvocar = $edit
    ? $urlBase . '?editar=' . (int)$edit->id . '&tab=convocar'
    : $urlBase . '?tab=convocar';
?>

<div class="cb-tabs cb-reunion-tabs" role="tablist">
    <a href="<?php echo htmlspecialchars($urlConvocar); ?>"
       class="cb-tab <?php echo $tabActiva === 'convocar' ? 'is-active' : ''; ?>"
       role="tab"
       aria-selected="<?php echo $tabActiva === 'convocar' ? 'true' : 'false'; ?>">
        Convocar Asamblea / Reunión
    </a>
    <a href="<?php echo htmlspecialchars($urlListado); ?>"
       class="cb-tab <?php echo $tabActiva === 'listado' ? 'is-active' : ''; ?>"
       role="tab"
       aria-selected="<?php echo $tabActiva === 'listado' ? 'true' : 'false'; ?>">
        Asambleas y reuniones convocadas
        <?php if ($totalReuniones > 0): ?>
        <span class="cb-tab-count"><?php echo $totalReuniones; ?></span>
        <?php endif; ?>
    </a>
</div>

<div class="cb-reunion-panel" id="panel-convocar" <?php echo $tabActiva !== 'convocar' ? 'hidden' : ''; ?>>
    <div class="card card-warning cb-reunion-convocar-card">
        <?php if ($edit): ?>
        <h3 class="card-title">Editar convocatoria</h3>
        <form action="<?php echo URLROOT; ?>/admin/reunion_actualizar" method="POST">
            <input type="hidden" name="reunion_id" value="<?php echo (int)$edit->id; ?>">
            <?php include APPROOT . '/views/admin/partials/reunion_convocatoria_fields.php'; ?>
            <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.82rem;margin:0.75rem 0;">
                <input type="checkbox" name="reenviar_email" value="1"> Reenviar correo a convocados
            </label>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Guardar cambios</button>
                <a href="<?php echo htmlspecialchars($urlBase . '?tab=listado'); ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
        <?php else: ?>
        <h3 class="card-title">Convocar Asamblea / Reunión</h3>
        <p style="font-size:0.82rem;color:var(--text-muted);margin:-0.5rem 0 1rem;">
            Complete los datos y envíe la convocatoria por correo a los destinatarios seleccionados.
        </p>
        <form action="<?php echo URLROOT; ?>/admin/reunion_crear" method="POST">
            <?php
            $edit = null;
            $convocadosEdit = [];
            include APPROOT . '/views/admin/partials/reunion_convocatoria_fields.php';
            ?>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.5rem;">Enviar convocatoria</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="cb-reunion-panel" id="panel-listado" <?php echo $tabActiva !== 'listado' ? 'hidden' : ''; ?>>
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <div class="card card-primary">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:0.5rem;">
                <h3 class="card-title" style="margin:0;">Asambleas y reuniones convocadas</h3>
                <a href="<?php echo htmlspecialchars($urlBase . '?tab=convocar'); ?>" class="btn btn-primary btn-sm">+ Nueva convocatoria</a>
            </div>
            <?php if (empty($reuniones)): ?>
                <p class="cb-reunion-empty">
                    Aún no hay convocatorias.
                    <a href="<?php echo htmlspecialchars($urlBase . '?tab=convocar'); ?>">Convocar la primera reunión</a>
                </p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table cb-reunion-table">
                    <thead>
                        <tr>
                            <th>Fecha / Hora</th>
                            <th>Título</th>
                            <th>Estado</th>
                            <th>Convocados</th>
                            <th>Asistencia</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reuniones as $r):
                        $isFuture = strtotime($r->fecha_reunion) >= time();
                    ?>
                        <tr class="<?php echo ($det && (int)$det->id === (int)$r->id) ? 'is-selected' : ''; ?>">
                            <td class="cb-reunion-date"><?php echo date('d-m-Y H:i', strtotime($r->fecha_reunion)); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($r->titulo); ?></strong>
                                <?php if ($isFuture): ?><span class="badge badge-info" style="font-size:0.65rem;margin-left:0.35rem;">Próxima</span><?php endif; ?>
                            </td>
                            <td><span class="badge <?php echo $r->estado === 'realizada' ? 'badge-success' : 'badge-warning'; ?>"><?php echo htmlspecialchars($r->estado); ?></span></td>
                            <td style="text-align:center;"><?php echo (int)($r->total_convocados ?? 0); ?></td>
                            <td style="text-align:center;">
                                <?php if ($r->estado === 'realizada'): ?>
                                    <?php echo (int)$r->presentes; ?> / <?php echo (int)$r->total_registrados; ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="cb-reunion-actions">
                                <a href="<?php echo URLROOT; ?>/admin/asistencia/<?php echo (int)$r->id; ?>?tab=listado" class="btn btn-secondary btn-sm">Gestionar</a>
                                <?php if ($r->estado === 'programada'): ?>
                                <a href="<?php echo URLROOT; ?>/admin/asistencia?editar=<?php echo (int)$r->id; ?>&tab=convocar" class="btn btn-secondary btn-sm">Editar</a>
                                <?php endif; ?>
                                <?php if ($r->estado === 'realizada' || !empty($r->resultados)): ?>
                                <a href="<?php echo URLROOT; ?>/admin/reunion_minuta/<?php echo (int)$r->id; ?>" target="_blank" class="btn btn-primary btn-sm">Minuta</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($det): ?>
        <div class="card card-success" id="listaAsistenciaSeccion">
            <h3 class="card-title">Pase de lista — <?php echo htmlspecialchars($det->titulo); ?></h3>
            <p style="font-size:0.82rem;color:var(--text-muted);margin:-0.5rem 0 1rem;">
                Convocatoria: <?php echo date('d/m/Y H:i', strtotime($det->fecha_reunion)); ?>
                · <?php echo count($data['convocados'] ?? []); ?> convocado(s)
            </p>
            <form action="<?php echo URLROOT; ?>/admin/asistencia_guardar/<?php echo (int)$det->id; ?>" method="POST">
                <div class="cb-reunion-roll-wrap">
                    <table class="table">
                        <thead><tr><th style="width:70px;text-align:center;">Asistió</th><th>Socio</th><th>RUT</th></tr></thead>
                        <tbody>
                        <?php foreach ($data['asistentes'] as $s): ?>
                        <tr>
                            <td style="text-align:center;">
                                <input type="checkbox" name="asistencia[]" value="<?php echo (int)$s->socio_id; ?>" <?php echo !empty($s->asistio) ? 'checked' : ''; ?>>
                            </td>
                            <td><?php echo htmlspecialchars($s->nombre); ?></td>
                            <td style="font-family:monospace;font-size:0.85rem;"><?php echo htmlspecialchars($s->rut); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="display:flex;gap:0.75rem;justify-content:flex-end;flex-wrap:wrap;">
                    <a href="<?php echo htmlspecialchars($urlBase . '?tab=listado'); ?>" class="btn btn-secondary btn-sm">Volver al listado</a>
                    <button type="submit" class="btn btn-success btn-sm">Guardar asistencia</button>
                </div>
            </form>
        </div>

        <div class="card card-primary">
            <h3 class="card-title">Temas a tratar</h3>
            <div class="cb-reunion-temas-box"><?php echo nl2br(htmlspecialchars($reunionModelHelper->getTemasText($det))); ?></div>
        </div>

        <div class="card card-warning">
            <h3 class="card-title">Resultados y acuerdos de la reunión</h3>
            <form action="<?php echo URLROOT; ?>/admin/reunion_resultados" method="POST">
                <input type="hidden" name="reunion_id" value="<?php echo (int)$det->id; ?>">
                <div class="form-group">
                    <label class="form-label">Hora real de inicio (opcional)</label>
                    <input type="datetime-local" name="hora_inicio_real" class="form-control"
                        value="<?php echo !empty($det->hora_inicio_real) ? date('Y-m-d\TH:i', strtotime($det->hora_inicio_real)) : date('Y-m-d\TH:i', strtotime($det->fecha_reunion)); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Acuerdos, observaciones y resultados</label>
                    <textarea name="resultados" class="form-control" rows="6" placeholder="Registre los acuerdos adoptados, observaciones y compromisos..."><?php echo htmlspecialchars($det->resultados ?? ''); ?></textarea>
                </div>
                <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;margin-bottom:1rem;">
                    <input type="checkbox" name="finalizar_reunion" value="1" <?php echo $det->estado === 'realizada' ? 'checked' : ''; ?> <?php echo $det->estado === 'realizada' ? 'disabled' : ''; ?>>
                    Marcar reunión como finalizada (habilita minuta oficial)
                </label>
                <?php if ($det->estado === 'realizada'): ?>
                <input type="hidden" name="finalizar_reunion" value="1">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-sm"><?php echo $det->estado === 'realizada' ? 'Actualizar resultados' : 'Guardar resultados'; ?></button>
                <?php if ($det->estado === 'realizada'): ?>
                <a href="<?php echo URLROOT; ?>/admin/reunion_minuta/<?php echo (int)$det->id; ?>" target="_blank" class="btn btn-secondary btn-sm" style="margin-left:0.5rem;">Imprimir minuta</a>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($det && $tabActiva === 'listado'): ?>
<script>document.addEventListener('DOMContentLoaded',()=>document.getElementById('listaAsistenciaSeccion')?.scrollIntoView({behavior:'smooth'}));</script>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
