<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="invite-registro-page">
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

    <main class="invite-registro-main">
        <div class="card card-primary invite-registro-card">

            <?php if (!empty($data['success'])): ?>
                <div class="invite-registro-state">
                    <div class="invite-registro-state-icon success" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <h2>Solicitud enviada</h2>
                    <div class="alert alert-success alert-block alert-persistent" style="text-align: left; margin: 1.25rem 0 0;">
                        <span class="alert-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </span>
                        <div class="alert-content"><?php echo htmlspecialchars($data['success']); ?></div>
                    </div>
                    <p style="margin-top: 1rem;">
                        Cuando la directiva apruebe su solicitud, recibirá un correo con sus datos y clave de acceso.
                    </p>
                    <a href="<?php echo URLROOT; ?>/auth/login" class="btn btn-primary" style="margin-top: 1.5rem; display: inline-flex;">Ir al inicio de sesión</a>
                </div>

            <?php elseif (!empty($data['error']) && empty($data['invitation'])): ?>
                <div class="invite-registro-state">
                    <div class="invite-registro-state-icon error" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <h2>Invitación no válida</h2>
                    <div class="alert alert-danger alert-block alert-persistent" style="text-align: left; margin-top: 1.25rem;">
                        <span class="alert-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        </span>
                        <div class="alert-content"><?php echo htmlspecialchars($data['error']); ?></div>
                    </div>
                    <a href="<?php echo URLROOT; ?>/auth/login" class="btn btn-secondary" style="margin-top: 1.5rem; display: inline-flex;">Volver al inicio</a>
                </div>

            <?php elseif (!empty($data['invitation'])): ?>
                <?php
                $inv = $data['invitation'];
                $old = $data['old'] ?? [];
                $step = $data['step'] ?? 'rut';
                $rutCheck = $data['rut_check'] ?? null;
                require_once APPROOT . '/core/InviteRutCheck.php';
                ?>

                <header class="invite-registro-header">
                    <h1>Registro de Socio</h1>
                    <div class="invite-registro-org">
                        <span>Solicitud para</span>
                        <strong><?php echo htmlspecialchars($inv->junta_nombre); ?></strong>
                        <?php if (!empty($inv->comuna)): ?>
                            <span class="invite-registro-org-sep">·</span>
                            <span><?php echo htmlspecialchars($inv->comuna); ?></span>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if ($step === 'rut'): ?>
                    <div class="alert alert-info alert-block alert-persistent" role="status">
                        <span class="alert-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        </span>
                        <div class="alert-content">
                            <strong>Paso 1:</strong> Ingrese su RUT para verificar si ya está registrado en esta organización.
                        </div>
                    </div>

                    <?php if (!empty($data['error'])): ?>
                        <div class="alert alert-danger alert-block alert-persistent">
                            <div class="alert-content"><?php echo htmlspecialchars($data['error']); ?></div>
                        </div>
                    <?php endif; ?>

                    <form class="invite-registro-form" action="<?php echo URLROOT; ?>/invite/verificar_rut" method="POST" autocomplete="off">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($data['token']); ?>">
                        <div class="form-group">
                            <label class="form-label">RUT *</label>
                            <input type="text" name="rut" id="inputRutInvitacion" class="form-control cb-rut-chile"
                                   placeholder="126667777-6" required maxlength="12" inputmode="numeric" autofocus
                                   value="<?php echo htmlspecialchars($old['rut'] ?? ''); ?>">
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Sin puntos ni espacios. Ejemplo: 126667777-6</small>
                        </div>
                        <button type="submit" class="btn btn-primary invite-registro-submit">Verificar RUT y continuar</button>
                    </form>

                <?php elseif ($step === 'status' && $rutCheck): ?>
                    <div class="invite-registro-state" style="padding: 0;">
                        <div class="invite-registro-state-icon <?php echo ($rutCheck['title'] ?? '') === 'RUT inválido' ? 'error' : 'warning'; ?>" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        </div>
                        <h2 style="margin-top: 1rem;"><?php echo htmlspecialchars($rutCheck['title'] ?? 'Estado de su registro'); ?></h2>
                        <p style="color: var(--text-muted); margin: 0.75rem 0 0;">RUT: <strong style="font-family: monospace;"><?php echo htmlspecialchars($rutCheck['rut'] ?? ''); ?></strong></p>
                        <div class="alert alert-warning alert-block alert-persistent" style="text-align: left; margin-top: 1.25rem;">
                            <div class="alert-content"><?php echo htmlspecialchars($rutCheck['detail'] ?? ''); ?></div>
                        </div>
                        <?php if (($rutCheck['title'] ?? '') === 'Ya está registrado como socio activo'): ?>
                            <a href="<?php echo URLROOT; ?>/auth/login" class="btn btn-primary" style="margin-top: 1.5rem; display: inline-flex;">Ir a iniciar sesión</a>
                        <?php else: ?>
                            <a href="<?php echo URLROOT; ?>/invite/registro/<?php echo htmlspecialchars($data['token']); ?>" class="btn btn-secondary" style="margin-top: 1.5rem; display: inline-flex;">Verificar otro RUT</a>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                <?php /* Paso 2: formulario completo */ ?>

                <div class="alert alert-warning alert-block alert-persistent" role="status">
                    <span class="alert-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    </span>
                    <div class="alert-content">
                        <?php if ($rutCheck && ($rutCheck['action'] ?? '') === InviteRutCheck::ACTION_COMPLETE_PREVALIDAR): ?>
                            <strong>Paso 2:</strong> Revise los datos cargados por la directiva, complete lo que falte y envíe su solicitud.
                        <?php else: ?>
                            <strong>Paso 2:</strong> Complete la información con su <strong>nombre completo</strong> y datos personales correctos.
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($data['error'])): ?>
                    <div class="alert alert-danger alert-block alert-persistent">
                        <span class="alert-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        </span>
                        <div class="alert-content"><?php echo htmlspecialchars($data['error']); ?></div>
                    </div>
                <?php endif; ?>

                <form id="formRegistroInvitacion" class="invite-registro-form" action="<?php echo URLROOT; ?>/invite/registrar" method="POST" autocomplete="off">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($data['token']); ?>">
                    <?php if (!empty($old['prevalidar_user_id'])): ?>
                        <input type="hidden" name="prevalidar_user_id" value="<?php echo (int)$old['prevalidar_user_id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">RUT verificado</label>
                        <input type="text" name="rut" class="form-control" readonly
                               value="<?php echo htmlspecialchars($old['rut'] ?? ($rutCheck['rut'] ?? '')); ?>"
                               style="opacity: 0.85; cursor: not-allowed;">
                        <small style="color: var(--text-muted); font-size: 0.75rem;">
                            <a href="<?php echo URLROOT; ?>/invite/registro/<?php echo htmlspecialchars($data['token']); ?>">Cambiar RUT</a>
                        </small>
                    </div>

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

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Apellido Paterno *</label>
                            <input type="text" name="apellido_paterno" class="form-control cb-uppercase" required value="<?php echo htmlspecialchars($old['apellido_paterno'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Apellido Materno <span style="font-weight: normal; color: var(--text-muted);">(opcional)</span></label>
                            <input type="text" name="apellido_materno" id="inputApellidoMaternoInvitacion" class="form-control cb-uppercase"
                                   value="<?php echo htmlspecialchars($old['apellido_materno'] ?? ''); ?>"
                                   placeholder="Dejar vacío si no aplica">
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Si no tiene apellido materno, puede dejarlo en blanco.</small>
                        </div>
                    </div>

                    <?php
                    $prefix = '';
                    $values = $old ?? [];
                    $required = true;
                    require APPROOT . '/views/partials/socio_demografia_fields.php';
                    ?>

                    <div class="form-group">
                        <label class="form-label">Correo Electrónico *</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars(InviteRutCheck::displayEmail($old['email'] ?? '')); ?>">
                    </div>

                    <?php
                    $id = 'telefono_invitacion';
                    $name = 'telefono';
                    $telefonoLabel = 'Teléfono';
                    $required = false;
                    $value = $old['telefono'] ?? '';
                    require APPROOT . '/views/partials/campo_telefono_cl.php';
                    ?>

                    <div class="form-group">
                        <label class="form-label">Fecha de Inicio como Socio *</label>
                        <input type="date" name="fecha_inicio" class="form-control" required value="<?php echo htmlspecialchars($old['fecha_inicio'] ?? date('Y-m-d')); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Calle (Jurisdicción) *</label>
                        <?php if (empty($data['calles'])): ?>
                            <div class="alert alert-danger alert-block alert-persistent" style="font-size: 0.85rem; margin-bottom: 0.75rem;">
                                <div class="alert-content">No hay calles configuradas. Contacte a la directiva.</div>
                            </div>
                            <select name="calle_id" id="calle_id" class="form-control" disabled required><option value="">—</option></select>
                        <?php else: ?>
                            <select name="calle_id" id="calle_id" class="form-control" required>
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
                        <input type="text" name="numero_casa" id="numero_casa" class="form-control cb-uppercase" required value="<?php echo htmlspecialchars($old['numero_casa'] ?? ''); ?>">
                    </div>

                    <?php
                    $georefPrefix = '';
                    $calleSelectId = 'calle_id';
                    $numeroInputId = 'numero_casa';
                    $georefValues = [
                        'latitud' => $old['latitud'] ?? '',
                        'longitud' => $old['longitud'] ?? '',
                        'link_google' => $old['link_google'] ?? '',
                    ];
                    $georefComuna = $data['invitation']->comuna ?? '';
                    require APPROOT . '/views/partials/socio_georef_map.php';
                    ?>

                    <div class="alert alert-info alert-block invite-registro-notice alert-persistent">
                        <div class="alert-content">
                            Su solicitud quedará <strong>pendiente</strong> hasta que un administrador la revise. Al ser aprobada, recibirá un correo con sus datos y clave de acceso.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary invite-registro-submit" <?php echo empty($data['calles']) ? 'disabled' : ''; ?>>
                        Enviar solicitud de registro
                    </button>
                </form>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </main>

    <footer class="invite-registro-footer">
        Desarrollado para Organizaciones Chilenas · ConectaBarrio
    </footer>
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

<?php if (($data['step'] ?? '') === 'form' && !empty($data['calles'])): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="<?php echo URLROOT; ?>/js/socio-georef.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const callesGeorefMap = <?php
        $inviteCallesMapJs = [];
        foreach ($data['calles'] as $calleItem) {
            $inviteCallesMapJs[(string)$calleItem->id] = $calleItem->nombre;
        }
        echo json_encode($inviteCallesMapJs, JSON_UNESCAPED_UNICODE);
    ?>;
    const georefInstances = typeof initSocioGeorefMaps === 'function'
        ? initSocioGeorefMaps(callesGeorefMap)
        : {};
    const instance = georefInstances.default;
    if (instance) {
        const lat = document.getElementById('latitud')?.value || '';
        const lng = document.getElementById('longitud')?.value || '';
        const link = document.getElementById('link_google')?.value || '';
        if (lat && lng) {
            instance.loadFromValues(lat, lng, link);
        }
        setTimeout(function() { instance.refreshLayout(); }, 300);
    }
});
</script>
<?php endif; ?>

<script>
(function() {
    const form = document.getElementById('formRegistroInvitacion');
    if (!form) return;

    const overlay = document.getElementById('cbConfirmModal');
    const titleEl = document.getElementById('cbConfirmTitle');
    const messageEl = document.getElementById('cbConfirmMessage');
    const iconEl = document.getElementById('cbConfirmIcon');
    const okBtn = document.getElementById('cbConfirmOk');
    const cancelBtn = document.getElementById('cbConfirmCancel');
    let pendingSubmit = false;

    function closeModal() {
        if (!overlay) return;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        pendingSubmit = false;
    }

    function openApellidoMaternoConfirm(onConfirm) {
        if (!overlay || !okBtn) {
            if (window.confirm('El apellido materno está vacío. ¿Confirma que desea continuar sin ese dato?')) {
                onConfirm();
            }
            return;
        }
        titleEl.textContent = 'Apellido materno vacío';
        messageEl.textContent = 'No ingresó apellido materno. ¿Confirma que desea continuar sin ese dato?';
        iconEl.className = 'cb-confirm-icon warning';
        okBtn.className = 'btn btn-warning';
        okBtn.textContent = 'Sí, continuar sin apellido materno';
        pendingSubmit = true;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        const handler = function() {
            okBtn.removeEventListener('click', handler);
            closeModal();
            onConfirm();
        };
        okBtn.addEventListener('click', handler);
    }

    cancelBtn?.addEventListener('click', closeModal);
    overlay?.addEventListener('click', function(e) {
        if (e.target === overlay) closeModal();
    });

    form.addEventListener('submit', function(e) {
        if (form.dataset.apellidoMaternoConfirmado === '1') {
            form.dataset.apellidoMaternoConfirmado = '0';
            return;
        }
        const apellidoMaterno = (document.getElementById('inputApellidoMaternoInvitacion')?.value || '').trim();
        if (apellidoMaterno !== '') return;

        e.preventDefault();
        openApellidoMaternoConfirm(function() {
            form.dataset.apellidoMaternoConfirmado = '1';
            form.requestSubmit();
        });
    });
})();
</script>
