<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php $v = $data['votacion']; ?>

<?php if (!empty($data['success'])): ?><div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div><?php endif; ?>
<?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div><?php endif; ?>

<p style="color:var(--text-muted);font-size:0.9rem;"><?php echo nl2br(htmlspecialchars($v->descripcion ?? '')); ?></p>
<p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:1rem;">
    Cierra: <?php echo date('d/m/Y H:i', strtotime($v->fecha_fin)); ?>
</p>

<?php
$votacion = $v;
$resultados = $data['resultados'];
$parciales_url = $data['parciales_url'] ?? '';
require APPROOT . '/views/partials/votacion_resultados.php';
?>

<p style="margin-top:1rem;">
    <a href="<?php echo URLROOT; ?>/socio/votaciones" class="btn btn-secondary">Volver a consultas</a>
</p>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
