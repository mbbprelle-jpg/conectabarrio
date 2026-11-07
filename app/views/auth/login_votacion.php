<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$v = $data['votacion'] ?? null;
$abierta = !empty($data['abierta']);
$nombre = ($v && ($v->tipo ?? '') === 'encuesta') ? 'encuesta' : 'votación';
?>
<div class="auth-modal-overlay active" style="position:fixed;">
    <div class="auth-modal login-card" style="transform:none;max-width:480px;">
        <div class="login-header">
            <div class="login-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <h2 style="font-family: var(--font-heading); font-weight: 700;">ConectaBarrio</h2>
            <p><?php echo $v ? 'Ingreso a la ' . htmlspecialchars($nombre) : 'Enlace de consulta'; ?></p>
        </div>

        <?php if (!$v): ?>
            <div class="alert alert-danger" style="margin-bottom: 1rem;">
                <span>Este enlace no corresponde a una consulta registrada.</span>
            </div>
            <a href="<?php echo URLROOT; ?>/auth/login" class="btn btn-primary" style="width:100%;">Ir al portal</a>
        <?php else: ?>
            <p style="font-size:0.78rem;color:var(--text-muted);margin:0 0 0.35rem;"><?php echo htmlspecialchars((string)($v->junta_nombre ?? '')); ?></p>
            <p style="font-size:0.95rem;font-weight:650;line-height:1.4;margin:0 0 0.85rem;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden;"><?php echo htmlspecialchars((string)$v->titulo); ?></p>
            <div class="alert alert-info" style="margin-bottom: 1rem;">
                <span><?php echo htmlspecialchars((string)($data['aviso'] ?? '')); ?></span>
            </div>

            <?php if (!empty($data['error'])): ?>
                <div class="alert alert-danger" style="margin-bottom: 1rem;">
                    <span><?php echo htmlspecialchars($data['error']); ?></span>
                </div>
            <?php endif; ?>

            <form action="<?php echo URLROOT; ?>/auth/authenticate" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="rut_or_email" class="form-label">RUT o correo electrónico</label>
                    <input type="text" name="rut_or_email" id="rut_or_email" class="form-control"
                           placeholder="Ej: 11.111.111-1 o correo@junta.cl"
                           value="<?php echo htmlspecialchars((string)($data['rut_or_email'] ?? '')); ?>"
                           required autocomplete="username" autocapitalize="off" autocorrect="off" spellcheck="false" autofocus>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="password-input-wrap">
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" aria-label="Mostrar contraseña" aria-pressed="false" title="Mostrar contraseña">
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                <p style="font-size: 0.72rem; color: var(--text-muted); margin: -0.25rem 0 0.75rem;">
                    Si fue registrado en alta provisional, use su RUT y los primeros 6 dígitos numéricos del RUT como contraseña.
                </p>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
                    <?php echo $abierta ? 'Autenticarme y abrir la consulta' : 'Autenticarme'; ?>
                </button>
                <div class="text-center" style="margin-top:0.75rem;">
                    <a href="<?php echo URLROOT; ?>/auth/recover" class="link-underline link-primary" style="font-size:0.85rem;">¿Olvidó su contraseña?</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
