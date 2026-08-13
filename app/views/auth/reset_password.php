<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="card" style="max-width: 480px; margin: 1rem auto 2rem;">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <div style="width: 56px; height: 56px; margin: 0 auto 1rem; background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.35); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary);">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        </div>
        <h2 style="font-family: var(--font-heading); font-size: 1.35rem; margin-bottom: 0.35rem;">Defina su nueva contraseña</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
            Por seguridad debe reemplazar la clave temporal antes de continuar.
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
        <p style="text-align:center; color:var(--text-muted); font-size:0.85rem;">Redirigiendo al portal…</p>
        <script>
            setTimeout(function () {
                window.location.href = '<?php echo URLROOT; ?>/<?php
                    $rol = $_SESSION['user_rol'] ?? 'socio';
                    echo $rol === 'maestro' ? 'maestro/dashboard' : ($rol === 'admin' ? 'admin/dashboard' : 'socio/dashboard');
                ?>';
            }, 1200);
        </script>
    <?php else: ?>
        <form action="<?php echo URLROOT; ?>/auth/resetPassword" method="POST" autocomplete="off">
            <div class="form-group">
                <label for="new_password" class="form-label">Nueva contraseña *</label>
                <input type="password" name="new_password" id="new_password" class="form-control" minlength="8" required autofocus placeholder="Mínimo 8 caracteres">
            </div>
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirmar contraseña *</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="8" required placeholder="Repita la contraseña">
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                Use una contraseña segura que solo usted conozca. No comparta su clave con terceros.
            </p>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
                Guardar y continuar
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
