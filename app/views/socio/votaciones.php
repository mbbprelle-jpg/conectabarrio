<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/models/Votacion.php'; ?>
<?php require_once APPROOT . '/core/AuthContext.php'; ?>

<?php if (!empty($data['success'])): ?><div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div><?php endif; ?>
<?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div><?php endif; ?>

<?php if (!empty($data['pendientes'])): ?>
<div class="alert alert-info">
    <strong>Tiene <?php echo count($data['pendientes']); ?> consulta(s) pendiente(s).</strong>
    Participe antes de que cierre el plazo.
</div>
<?php endif; ?>

<div class="card card-primary">
    <h3 class="card-title">Consultas activas</h3>
    <?php if (empty($data['pendientes'])): ?>
        <p style="color:var(--text-muted);">No tiene votaciones pendientes en este momento.</p>
    <?php else: ?>
        <?php foreach ($data['pendientes'] as $v): ?>
        <div style="padding:1rem;border:1px solid var(--border-color);border-radius:8px;margin-bottom:0.75rem;">
            <strong><?php echo htmlspecialchars($v->titulo); ?></strong>
            <p style="font-size:0.8rem;color:var(--text-muted);margin:0.35rem 0;">Cierra: <?php echo date('d/m/Y H:i', strtotime($v->fecha_fin)); ?></p>
            <a href="<?php echo URLROOT; ?>/socio/votacion_votar/<?php echo (int)$v->id; ?>" class="btn btn-primary btn-sm">Participar ahora</a>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!empty($data['historial'])): ?>
<div class="card card-success" style="margin-top:1.5rem;">
    <h3 class="card-title">Historial de consultas</h3>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Título</th><th>Estado</th><th>Plazo</th></tr></thead>
            <tbody>
            <?php
            $vModel = new Votacion();
            $uid = (int)$_SESSION['user_id'];
            $jid = (int)$_SESSION['user_junta_id'];
            foreach ($data['historial'] as $v):
                if (!$vModel->isUserElector((int)$v->id, $uid, $v->audiencia_tipo, $jid)) continue;
                $verResultados = $vModel->canViewResults($v, $uid, $jid, false, $data['is_directiva']);
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($v->titulo); ?></td>
                    <td><?php echo htmlspecialchars($v->estado); ?></td>
                    <td>
                        <?php if ($verResultados): ?>
                        <a href="<?php echo URLROOT; ?>/admin/votacion_ver/<?php echo (int)$v->id; ?>" class="btn btn-secondary btn-sm">Ver resultados</a>
                        <?php else: ?>
                        <span style="font-size:0.8rem;color:var(--text-muted);">Resultados reservados</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
