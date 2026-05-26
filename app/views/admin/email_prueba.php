<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="card card-primary" style="max-width: 760px;">
    <h3 class="card-title">Enviar correo de prueba</h3>

    <div class="card" style="margin-top: 1rem; padding: 1rem; background: rgba(15,23,42,0.5); border: 1px solid var(--border-color);">
        <strong>Configuración activa en el servidor</strong>
        <ul style="margin: 0.75rem 0 0; padding-left: 1.25rem; line-height: 1.7;">
            <li><strong>Remitente (FROM):</strong> <code><?php echo htmlspecialchars($data['smtp_from'] ?? ''); ?></code></li>
            <li><strong>SMTP:</strong> <code><?php echo htmlspecialchars($data['smtp_host'] ?? ''); ?>:<?php echo (int)($data['smtp_port'] ?? 0); ?></code> (<?php echo htmlspecialchars($data['smtp_encryption'] ?? ''); ?>)</li>
            <li><strong>Usuario SMTP:</strong> <code><?php echo htmlspecialchars($data['smtp_user'] ?? ''); ?></code></li>
            <li><strong>Estado:</strong> <?php echo !empty($data['smtp_configured']) ? '✅ Credenciales cargadas' : '❌ Faltan variables SMTP'; ?></li>
        </ul>
        <?php if (($data['from_domain'] ?? '') !== 'conectabarrio.cl'): ?>
            <p style="margin: 0.75rem 0 0; color: #fbbf24;">
                ⚠️ El dominio del remitente es <code><?php echo htmlspecialchars($data['from_domain'] ?? ''); ?></code>.
                En Brevo el dominio autenticado debe ser <strong>conectabarrio.cl</strong> y el remitente verificado debe coincidir exactamente con <code>contacto@conectabarrio.cl</code> (sin “t” extra: no es conecta<strong>t</strong>ubarrio).
            </p>
        <?php endif; ?>
    </div>

    <?php if (!empty($data['success'])): ?>
        <div class="alert alert-success" style="margin-top: 1rem;"><?php echo htmlspecialchars($data['success']); ?></div>
        <p style="margin-top: 0.5rem; color: var(--text-muted, #94a3b8); font-size: 0.9rem;">
            Si no llega a Gmail: revisa <strong>Spam / Promociones</strong>, espera 5 minutos y en Brevo confirma que <code><?php echo htmlspecialchars($data['smtp_from'] ?? ''); ?></code> aparece como <strong>Remitente verificado</strong> (no solo dominio autenticado).
        </p>
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
        </div>

        <button type="submit" class="btn btn-primary">Enviar correo de prueba</button>
        <a href="<?php echo URLROOT; ?>/admin/dashboard" class="btn btn-secondary" style="margin-left: .5rem;">Volver</a>
    </form>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
