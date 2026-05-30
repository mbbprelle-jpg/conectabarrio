<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="card" style="max-width: 640px; margin: 2rem auto;">
    <h2 style="text-align: center; margin-bottom: 0.5rem;">Seleccione cómo ingresar</h2>
    <p style="text-align: center; color: var(--text-muted); margin-bottom: 2rem;">
        Hola <?php echo htmlspecialchars($data['nombre']); ?>, tiene acceso a más de una organización o rol.
    </p>

    <?php if (!empty($data['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($data['error']); ?></div>
    <?php endif; ?>

    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php foreach ($data['membresias'] as $m): ?>
            <form action="<?php echo URLROOT; ?>/auth/set_context" method="POST" style="margin: 0;">
                <input type="hidden" name="membership_id" value="<?php echo (int)$m->id; ?>">
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: space-between; padding: 1rem 1.25rem;">
                    <span style="text-align: left;">
                        <strong><?php echo htmlspecialchars($m->junta_nombre); ?></strong><br>
                        <small style="opacity: 0.85;">
                            <?php echo $m->rol === 'admin' ? 'Administrador' : 'Socio'; ?>
                            <?php if (!empty($m->cargo)): ?> · <?php echo htmlspecialchars($m->cargo); ?><?php endif; ?>
                        </small>
                    </span>
                    <span>Ingresar →</span>
                </button>
            </form>
        <?php endforeach; ?>
    </div>

    <p style="text-align: center; margin-top: 1.5rem;">
        <a href="<?php echo URLROOT; ?>/auth/logout" style="color: var(--text-muted); font-size: 0.85rem;">Cerrar sesión</a>
    </p>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
