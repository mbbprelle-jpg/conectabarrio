<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php $v = $data['votacion']; $back = $data['back_url'] ?? URLROOT . '/admin/votaciones'; ?>

<div class="card card-primary" style="max-width:640px;">
    <?php if (!empty($data['ya_voto'])): ?>
        <p class="alert alert-success">Ya registró su participación en esta consulta.</p>
        <a href="<?php echo htmlspecialchars($back); ?>" class="btn btn-secondary">Volver</a>
    <?php elseif (empty($data['puede_votar'])): ?>
        <p class="alert alert-warning">Esta consulta no está disponible para usted o ya finalizó.</p>
        <a href="<?php echo htmlspecialchars($back); ?>" class="btn btn-secondary">Volver</a>
    <?php else: ?>
        <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1rem;"><?php echo nl2br(htmlspecialchars($v->descripcion ?? '')); ?></p>
        <form action="<?php echo URLROOT . (strpos($back, '/socio/') !== false ? '/socio/votacion_votar/' : '/admin/votacion_votar/') . (int)$v->id; ?>" method="POST">
            <?php if ($v->tipo === 'votacion'): ?>
                <?php foreach ($data['opciones'] as $o): ?>
                <label style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem;margin-bottom:0.5rem;border:1px solid var(--border-color);border-radius:8px;cursor:pointer;">
                    <input type="radio" name="opcion_id" value="<?php echo (int)$o->id; ?>" required>
                    <span><?php echo htmlspecialchars($o->texto); ?></span>
                </label>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="form-group">
                    <label class="form-label">Seleccione una opción o escriba su respuesta</label>
                    <?php foreach ($data['opciones'] as $o): ?>
                    <label style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.35rem;">
                        <input type="radio" name="opcion_id" value="<?php echo (int)$o->id; ?>">
                        <?php echo htmlspecialchars($o->texto); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Comentario adicional (opcional)</label>
                    <textarea name="respuesta_texto" class="form-control" rows="3"></textarea>
                </div>
            <?php endif; ?>
            <p style="font-size:0.75rem;color:var(--text-muted);margin:1rem 0;">Su identidad como votante solo la verán el administrador y quien creó la consulta.</p>
            <button type="submit" class="btn btn-primary">Enviar respuesta</button>
            <a href="<?php echo htmlspecialchars($back); ?>" class="btn btn-secondary">Cancelar</a>
        </form>
    <?php endif; ?>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
