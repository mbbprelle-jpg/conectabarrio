<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="card card-primary" style="max-width: 760px;">
    <h3 class="card-title">Enviar correo de prueba</h3>

    <?php if (!empty($data['success'])): ?>
        <div class="alert alert-success" style="margin-top: 1rem;"><?php echo htmlspecialchars($data['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($data['error'])): ?>
        <div class="alert alert-danger" style="margin-top: 1rem;"><?php echo htmlspecialchars($data['error']); ?></div>
    <?php endif; ?>

    <form action="<?php echo URLROOT; ?>/admin/email_prueba" method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token']); ?>">

        <div class="form-group" style="margin-bottom: 1rem;">
            <label for="to" class="form-label">Enviar a</label>
            <input
                type="email"
                class="form-control"
                id="to"
                name="to"
                value="<?php echo htmlspecialchars($data['default_to'] ?? 'mbbprelle@gmail.com'); ?>"
                required
            >
            <small style="display:block; margin-top: .4rem; color: var(--text-muted, #94a3b8);">
                Si no llega, revisa en Brevo: Transactional → Logs y valida el remitente configurado en <code>SMTP_FROM_EMAIL</code>.
            </small>
        </div>

        <button type="submit" class="btn btn-primary">Enviar correo de prueba</button>
        <a href="<?php echo URLROOT; ?>/admin/dashboard" class="btn btn-secondary" style="margin-left: .5rem;">Volver</a>
    </form>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>

