<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="card" style="max-width: 520px; margin: 1rem auto 2rem;">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <div style="width: 56px; height: 56px; margin: 0 auto 1rem; background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.35); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary);">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
        </div>
        <h2 style="font-family: var(--font-heading); font-size: 1.35rem; margin-bottom: 0.35rem;">Complete su cuenta</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
            Su organización lo registró en el padrón. Indique un correo real y defina su contraseña para acceder al portal.
        </p>
    </div>

    <?php if (!empty($data['error'])): ?>
        <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
            <span><?php echo htmlspecialchars($data['error']); ?></span>
        </div>
    <?php endif; ?>

    <form action="<?php echo URLROOT; ?>/auth/completar_cuenta" method="POST" autocomplete="off">
        <div class="form-group">
            <label for="email" class="form-label">Correo electrónico *</label>
            <input type="email" name="email" id="email" class="form-control" required autofocus placeholder="su.correo@ejemplo.cl">
        </div>
        <div class="form-group">
            <label for="new_password" class="form-label">Nueva contraseña *</label>
            <input type="password" name="new_password" id="new_password" class="form-control" minlength="8" required placeholder="Mínimo 8 caracteres">
        </div>
        <div class="form-group">
            <label for="confirm_password" class="form-label">Confirmar contraseña *</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="8" required placeholder="Repita la contraseña">
        </div>
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Al guardar, su cuenta quedará activa en el padrón y podrá consultar sus aportes y actividades.
        </p>
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
            Activar mi cuenta
        </button>
    </form>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
