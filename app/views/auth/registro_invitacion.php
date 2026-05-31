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
        <div class="card card-primary" style="width: 100%; max-width: 580px; margin: 0 auto;">

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
                <p style="color: var(--text-muted); margin-bottom: 1rem; font-size: 0.9rem;">
                    Solicitud para <strong style="color: var(--primary);"><?php echo htmlspecialchars($inv->junta_nombre); ?></strong>
                    <?php if (!empty($inv->comuna)): ?> · <?php echo htmlspecialchars($inv->comuna); ?><?php endif; ?>
                </p>

                <div class="alert alert-warning" style="font-size: 0.85rem; line-height: 1.55; margin-bottom: 1.25rem;">
                    <strong>Estimado Socio,</strong> es importante que pueda completar la información correctamente, en especial con su <strong>NOMBRE COMPLETO</strong> y los siguientes datos. Cabe mencionar que estos datos serán posteriormente validados con los registros que tenga la organización.
                </div>

                <?php if (!empty($data['error'])): ?>
                    <div class="alert alert-danger" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($data['error']); ?></div>
                <?php endif; ?>

                <form id="formRegistroInvitacion" action="<?php echo URLROOT; ?>/invite/registrar" method="POST" autocomplete="off">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($data['token']); ?>">

                    <div class="form-group">
                        <label class="form-label">ID Socio (opcional)</label>
                        <input type="number" name="id_socio" class="form-control" min="1"
                               placeholder="Ej: <?php echo (int)($data['proposed_id_socio'] ?? 1); ?> — dejar vacío si no lo conoce"
                               value="<?php echo htmlspecialchars($old['id_socio'] ?? ''); ?>">
                        <small style="color: var(--text-muted); font-size: 0.75rem;">Si su organización le asignó un número de socio, puede indicarlo aquí.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nombres *</label>
                        <input type="text" name="nombres" class="form-control cb-uppercase" required
                               placeholder="NOMBRE(S) COMPLETO(S)" value="<?php echo htmlspecialchars($old['nombres'] ?? ''); ?>">
                    </div>

                    <div class="grid-2col" style="gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Apellido Paterno *</label>
                            <input type="text" name="apellido_paterno" class="form-control cb-uppercase" required value="<?php echo htmlspecialchars($old['apellido_paterno'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Apellido Materno *</label>
                            <input type="text" name="apellido_materno" class="form-control cb-uppercase" required value="<?php echo htmlspecialchars($old['apellido_materno'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2col" style="gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Género *</label>
                            <select name="genero" class="form-control" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="MASCULINO" <?php echo (($old['genero'] ?? '') === 'MASCULINO') ? 'selected' : ''; ?>>Masculino</option>
                                <option value="FEMENINO" <?php echo (($old['genero'] ?? '') === 'FEMENINO') ? 'selected' : ''; ?>>Femenino</option>
                                <option value="NO ESPECIFICAR" <?php echo (($old['genero'] ?? '') === 'NO ESPECIFICAR') ? 'selected' : ''; ?>>No especificar</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Fecha de Nacimiento *</label>
                            <input type="date" name="fecha_nacimiento" class="form-control" required
                                   max="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo htmlspecialchars($old['fecha_nacimiento'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">RUT *</label>
                        <input type="text" name="rut" id="inputRutInvitacion" class="form-control cb-rut-chile"
                               placeholder="126667777-6" required maxlength="12" inputmode="numeric"
                               autocomplete="off" value="<?php echo htmlspecialchars($old['rut'] ?? ''); ?>">
                        <small style="color: var(--text-muted); font-size: 0.75rem;">Sin puntos ni espacios. Ejemplo: 126667777-6</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Correo Electrónico *</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control cb-uppercase" value="<?php echo htmlspecialchars($old['telefono'] ?? ''); ?>">
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
                                        <?php echo htmlspecialchars(mb_strtoupper($calle->nombre, 'UTF-8')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Número de Casa *</label>
                        <input type="text" name="numero_casa" class="form-control cb-uppercase" required value="<?php echo htmlspecialchars($old['numero_casa'] ?? ''); ?>">
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

<script>
(function() {
    function formatRutChile(raw) {
        let v = String(raw).replace(/[^0-9kK]/g, '').toUpperCase();
        if (v.length <= 1) return v;
        const body = v.slice(0, -1);
        const dv = v.slice(-1);
        return body + '-' + dv;
    }

    document.querySelectorAll('.cb-rut-chile').forEach(function(input) {
        input.addEventListener('input', function() {
            const pos = this.selectionStart;
            const before = this.value.length;
            this.value = formatRutChile(this.value);
            const after = this.value.length;
            const newPos = Math.max(0, pos + (after - before));
            this.setSelectionRange(newPos, newPos);
        });
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            this.value = formatRutChile(text);
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === ' ' || e.key === '.') e.preventDefault();
        });
    });

    document.querySelectorAll('.cb-uppercase').forEach(function(input) {
        input.addEventListener('input', function() {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(start, end);
        });
    });
})();
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
