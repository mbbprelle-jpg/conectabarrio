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
        <a href="<?php echo URLROOT; ?>/auth/login" class="btn btn-secondary" style="display:flex; align-items:center; justify-content:center; gap:0.45rem; width:100%; margin-top:0.75rem; padding:0.85rem; text-decoration:none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Volver atrás
        </a>
    </section>
</div>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
