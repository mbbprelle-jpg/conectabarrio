<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="card card-primary" style="max-width: 480px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <div style="width: 56px; height: 56px; margin: 0 auto 1rem; background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.35); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary);">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        </div>
        <h2 style="font-family: var(--font-heading); font-size: 1.25rem; margin-bottom: 0.35rem;">Actualizar contraseña</h2>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">
            Ingrese su clave actual y defina una nueva contraseña segura.
        </p>
    </div>

    <?php if (!empty($data['error'])): ?>
        <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
            <span><?php echo htmlspecialchars($data['error']); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($data['success'])): ?>
        <div class="alert alert-success" style="margin-bottom: 1.25rem;">
            <span><?php echo htmlspecialchars($data['success']); ?></span>
        </div>
        <div style="text-align: center;">
            <a href="<?php echo URLROOT; ?>/<?php echo $_SESSION['user_rol'] === 'maestro' ? 'maestro/dashboard' : ($_SESSION['user_rol'] === 'admin' ? 'admin/dashboard' : 'socio/dashboard'); ?>" class="btn btn-primary">Volver al panel</a>
        </div>
    <?php else: ?>
        <form action="<?php echo URLROOT; ?>/auth/changePassword" method="POST" autocomplete="off">
            <div class="form-group">
                <label for="current_password" class="form-label">Contraseña actual *</label>
                <div class="password-input-wrap">
                    <input type="password" name="current_password" id="current_password" class="form-control" required autofocus>
                    <button type="button" class="password-toggle-btn" aria-label="Mostrar contraseña" aria-pressed="false">
                        <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="new_password" class="form-label">Nueva contraseña *</label>
                <div class="password-input-wrap">
                    <input type="password" name="new_password" id="new_password" class="form-control" minlength="8" required placeholder="Mínimo 8 caracteres">
                    <button type="button" class="password-toggle-btn" aria-label="Mostrar contraseña" aria-pressed="false">
                        <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirmar nueva contraseña *</label>
                <div class="password-input-wrap">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="8" required>
                    <button type="button" class="password-toggle-btn" aria-label="Mostrar contraseña" aria-pressed="false">
                        <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                Use al menos 8 caracteres. No comparta su clave con terceros.
            </p>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">Guardar nueva contraseña</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
