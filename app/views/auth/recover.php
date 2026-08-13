<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<div class="landing-wrapper">
    <section class="auth-section" style="max-width:400px; margin:auto; padding:2rem;">
        <h2 style="font-family: var(--font-heading); font-weight:700; text-align:center; margin-bottom:1rem;">Recuperar Contraseña</h2>
        <p style="text-align:center; margin-bottom:1.5rem; color: var(--text-muted);">
            Ingresa tu correo electrónico y recibirás una clave temporal.
        </p>
        <?php if (!empty($data['error'])): ?>
            <div class="alert alert-danger" style="margin-bottom:1.25rem;">
                <span><?php echo htmlspecialchars($data['error']); ?></span>
            </div>
        <?php endif; ?>
        <form action="<?php echo URLROOT; ?>/auth/recover" method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="ejemplo@dominio.cl" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem; padding:0.85rem;">
                Enviar clave temporal
            </button>
        </form>
        <div style="text-align:center; margin-top:1.25rem;">
            <a href="<?php echo URLROOT; ?>/auth/login" class="link-underline link-primary" style="font-size:0.9rem;">Volver al inicio de sesión</a>
        </div>
    </section>
</div>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
