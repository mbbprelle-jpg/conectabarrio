<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="landing-wrapper" style="min-height: 100vh; display: flex; flex-direction: column;">
    <nav class="landing-navbar">
        <div class="landing-logo">
            <div class="landing-logo-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <span class="landing-logo-name">ConectaBarrio</span>
        </div>
        <a href="<?php echo URLROOT; ?>/auth/login" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Volver al inicio</a>
    </nav>

    <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;">
        <div class="card card-primary" style="width: 100%; max-width: 560px; margin: 0 auto;">

            <?php if (!empty($data['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                    <?php echo htmlspecialchars($data['success']); ?>
                </div>
                <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                    Cuando la directiva apruebe su solicitud, recibirá un correo con sus datos y clave de acceso.
                </p>
            <?php elseif (!empty($data['error']) && empty($data['invitation'])): ?>
                <h2 style="margin-bottom: 1rem;">Invitación no válida</h2>
                <div class="alert alert-danger"><?php echo htmlspecialchars($data['error']); ?></div>
            <?php elseif (!empty($data['invitation'])): ?>
                <?php $inv = $data['invitation']; $old = $data['old'] ?? []; ?>
                <h2 style="margin-bottom: 0.25rem;">Registro de Socio</h2>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.9rem;">
                    Solicitud para <strong style="color: var(--primary);"><?php echo htmlspecialchars($inv->junta_nombre); ?></strong>
                    <?php if (!empty($inv->comuna)): ?> · <?php echo htmlspecialchars($inv->comuna); ?><?php endif; ?>
                </p>

                <?php if (!empty($data['error'])): ?>
                    <div class="alert alert-danger" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($data['error']); ?></div>
                <?php endif; ?>

                <form action="<?php echo URLROOT; ?>/invite/registrar" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($data['token']); ?>">

                    <div class="form-group">
                        <label class="form-label">Nombres *</label>
                        <input type="text" name="nombres" class="form-control" required value="<?php echo htmlspecialchars($old['nombres'] ?? ''); ?>">
                    </div>

                    <div class="grid-2col" style="gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Apellido Paterno *</label>
                            <input type="text" name="apellido_paterno" class="form-control" required value="<?php echo htmlspecialchars($old['apellido_paterno'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Apellido Materno *</label>
                            <input type="text" name="apellido_materno" class="form-control" required value="<?php echo htmlspecialchars($old['apellido_materno'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">RUT *</label>
                        <input type="text" name="rut" class="form-control" placeholder="12.345.678-9" required value="<?php echo htmlspecialchars($old['rut'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Correo Electrónico *</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($old['telefono'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Fecha de Inicio como Socio *</label>
                        <input type="date" name="fecha_inicio" class="form-control" required value="<?php echo htmlspecialchars($old['fecha_inicio'] ?? date('Y-m-d')); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Calle (Jurisdicción) *</label>
                        <?php if (empty($data['calles'])): ?>
                            <div class="alert alert-danger" style="font-size: 0.8rem;">No hay calles configuradas. Contacte a la directiva.</div>
                            <select name="calle_id" class="form-control" disabled required><option value="">—</option></select>
                        <?php else: ?>
                            <select name="calle_id" class="form-control" required>
                                <option value="">-- Seleccionar Calle --</option>
                                <?php foreach ($data['calles'] as $calle): ?>
                                    <option value="<?php echo (int)$calle->id; ?>" <?php echo (($old['calle_id'] ?? '') == $calle->id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($calle->nombre); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Número de Casa *</label>
                        <input type="text" name="numero_casa" class="form-control" required value="<?php echo htmlspecialchars($old['numero_casa'] ?? ''); ?>">
                    </div>

                    <div class="alert alert-success" style="font-size: 0.8rem; margin-bottom: 1.25rem;">
                        Su solicitud quedará <strong>pendiente</strong> hasta que un administrador la revise y apruebe. Recibirá un correo con sus datos y clave de acceso al ser confirmado.
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;" <?php echo empty($data['calles']) ? 'disabled' : ''; ?>>
                        Enviar solicitud de registro
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
