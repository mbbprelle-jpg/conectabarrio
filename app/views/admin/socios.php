<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
require_once APPROOT . '/core/AuthContext.php';
$isFullAdmin = AuthContext::isFullAdmin();
$canManageSocios = AuthContext::canManageSocios();
$membresiasMap = $data['membresias_map'] ?? [];

function cbFormatFechaSocio($socio) {
    $raw = null;
    if (isset($socio->fecha_inicio) && $socio->fecha_inicio) {
        $raw = $socio->fecha_inicio;
    } elseif (!empty($socio->created_at)) {
        $raw = substr($socio->created_at, 0, 10);
    }
    if (!$raw) {
        return 'Sin registrar';
    }
    $ts = strtotime($raw);
    return ($ts !== false) ? date('d-m-Y', $ts) : 'Sin registrar';
}

function cbFechaSocioInput($socio) {
    if (!empty($socio->fecha_inicio)) {
        return substr($socio->fecha_inicio, 0, 10);
    }
    if (!empty($socio->created_at)) {
        return substr($socio->created_at, 0, 10);
    }
    return date('Y-m-d');
}
?>

<!-- Mensajes Flash de Éxito / Error -->
<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span><?php echo htmlspecialchars($data['success']); ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?php echo htmlspecialchars($data['error']); ?></span>
    </div>
<?php endif; ?>

<?php
$sociosPendientes = $data['socios_pendientes'] ?? [];
$invitacionesActivas = $data['invitaciones_activas'] ?? [];
$cuotaVigente = !empty($data['cuotas_historial']) ? $data['cuotas_historial'][0] : null;
$cuotaVigenteMonto = $cuotaVigente ? number_format($cuotaVigente->monto, 0, ',', '.') : '0';
?>

<?php if (!empty($sociosPendientes) && $canManageSocios): ?>
    <div class="alert alert-warning" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span>Hay <strong><?php echo count($sociosPendientes); ?></strong> solicitud(es) de registro pendiente(s) de aprobación.</span>
    </div>
<?php endif; ?>

<div class="socios-page">

    <?php if ($canManageSocios): ?>
    <div class="socios-action-bar">
        <button type="button" class="socios-action-btn socios-action-btn--primary" data-open-modal="inscribirSocioModal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            Inscribir socio
        </button>
        <button type="button" class="socios-action-btn socios-action-btn--accent" data-open-modal="cuotaModal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            Cuota ($<?php echo $cuotaVigenteMonto; ?>)
        </button>
        <button type="button" class="socios-action-btn" data-open-modal="callesModal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon></svg>
            Calles (<?php echo count($data['calles'] ?? []); ?>)
        </button>
        <button type="button" class="socios-action-btn" data-open-modal="invitacionModal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
            Link invitación
            <?php if (!empty($invitacionesActivas)): ?>
                <span class="action-badge"><?php echo count($invitacionesActivas); ?> activo<?php echo count($invitacionesActivas) > 1 ? 's' : ''; ?></span>
            <?php endif; ?>
        </button>
    </div>

    <?php if (!empty($data['link_invitacion']) || !empty($invitacionesActivas)): ?>
    <div class="socios-invite-strip">
        <span>
            <?php if (!empty($invitacionesActivas)): ?>
                <?php echo count($invitacionesActivas); ?> enlace(s) de invitación vigente(s).
            <?php else: ?>
                Enlace recién generado — cópielo y compártalo.
            <?php endif; ?>
        </span>
        <button type="button" class="btn btn-secondary btn-sm" data-open-modal="invitacionModal">Ver / copiar links</button>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="card card-primary" style="display: flex; flex-direction: column; gap: 1.5rem;">

        <?php if ($canManageSocios && !empty($sociosPendientes)): ?>
        <div style="border: 1px solid rgba(245, 158, 11, 0.35); border-radius: var(--radius-sm); padding: 1rem; background: rgba(245, 158, 11, 0.05);">
            <h4 style="margin: 0 0 1rem; font-size: 0.95rem; color: var(--warning); display: flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                Solicitudes pendientes de aprobación
            </h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Solicitante</th>
                            <th>RUT / Contacto</th>
                            <th>Domicilio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sociosPendientes as $pend): ?>
                        <tr>
                            <td style="font-weight: 600;">
                                <?php echo htmlspecialchars($pend->nombre . ' ' . $pend->apellido_paterno); ?>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    Solicitud: <?php echo !empty($pend->created_at) ? date('d-m-Y H:i', strtotime($pend->created_at)) : '—'; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-family: monospace;"><?php echo htmlspecialchars($pend->rut); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($pend->email); ?></div>
                            </td>
                            <td style="font-size: 0.85rem;">
                                <?php echo htmlspecialchars($pend->calle_nombre ?? '—'); ?> #<?php echo htmlspecialchars($pend->numero_casa ?? '—'); ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-primary btn-sm btn-revisar-pendiente"
                                    data-id="<?php echo (int)$pend->id; ?>"
                                    data-id-socio="<?php echo htmlspecialchars($pend->id_socio ?? ''); ?>"
                                    data-nombres="<?php echo htmlspecialchars($pend->nombre); ?>"
                                    data-apellido-paterno="<?php echo htmlspecialchars($pend->apellido_paterno); ?>"
                                    data-apellido-materno="<?php echo htmlspecialchars($pend->apellido_materno); ?>"
                                    data-rut="<?php echo htmlspecialchars($pend->rut); ?>"
                                    data-email="<?php echo htmlspecialchars($pend->email); ?>"
                                    data-telefono="<?php echo htmlspecialchars($pend->telefono ?? ''); ?>"
                                    data-fecha-inicio="<?php echo cbFechaSocioInput($pend); ?>"
                                    data-genero="<?php echo htmlspecialchars($pend->genero ?? ''); ?>"
                                    data-fecha-nacimiento="<?php echo !empty($pend->fecha_nacimiento) ? substr($pend->fecha_nacimiento, 0, 10) : ''; ?>"
                                    data-calle-id="<?php echo (int)($pend->calle_id ?? 0); ?>"
                                    data-numero-casa="<?php echo htmlspecialchars($pend->numero_casa ?? ''); ?>"
                                    style="font-size: 0.75rem;">
                                    Revisar / Aprobar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h3 class="card-title" style="margin-bottom: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Padrón de Socios Vecinos
            </h3>
            
            <!-- Barra de búsqueda dinámica -->
            <input type="text" id="searchSocio" class="form-control" placeholder="Buscar socio por nombre o RUT..." style="max-width: 250px; padding: 0.5rem 0.8rem; font-size: 0.85rem;">
        </div>

        <?php if (empty($data['socios'])): ?>
            <p style="color: var(--text-muted); text-align: center; padding: 2rem;">Aún no ha inscrito ningún socio en su organización.<?php if ($canManageSocios): ?> Use el botón <strong>Inscribir socio</strong> arriba.<?php endif; ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-searchable">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>RUT</th>
                            <th>Contacto / Email</th>
                            <?php if ($isFullAdmin): ?><th>Cargo / Permisos</th><?php endif; ?>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['socios'] as $socio): ?>
                            <tr>
                                <td style="font-weight: 600; display: flex; align-items: center;" class="search-name">
                                    <span style="color: var(--primary); font-size: 0.8rem; margin-right: 0.6rem; font-family: monospace; padding: 0.15rem 0.4rem; background: rgba(99, 102, 241, 0.1); border-radius: 4px; border: 1px solid rgba(99, 102, 241, 0.2); line-height: 1;" title="ID Socio">
                                        #<?php echo htmlspecialchars($socio->id_socio ?? 'N/A'); ?>
                                    </span>
                                    <?php echo htmlspecialchars($socio->nombre); ?>
                                </td>
                                <td style="font-family: monospace;" class="search-rut"><?php echo htmlspecialchars($socio->rut); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($socio->email); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($socio->telefono ?? 'Sin Fono'); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--primary); font-weight: 500; margin-top: 0.15rem; display: flex; align-items: center; gap: 0.25rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        Socio desde: <?php echo cbFormatFechaSocio($socio); ?>
                                    </div>
                                </td>
                                <?php if ($isFullAdmin):
                                    $mem = $membresiasMap[$socio->id] ?? null;
                                    $cargo = $mem->cargo ?? '';
                                    $permSocios = !empty($mem->permiso_gestion_socios) || !empty($mem->permiso_todos);
                                    $permPagos = !empty($mem->permiso_registro_pagos) || !empty($mem->permiso_todos);
                                ?>
                                <td>
                                    <?php if ($cargo): ?>
                                        <span class="badge badge-info" style="margin-bottom: 0.25rem; display: inline-block;"><?php echo htmlspecialchars($cargo); ?></span><br>
                                    <?php endif; ?>
                                    <small style="color: var(--text-muted); font-size: 0.72rem;">
                                        <?php if ($permSocios): ?>Socios<?php endif; ?>
                                        <?php if ($permSocios && $permPagos): ?> · <?php endif; ?>
                                        <?php if ($permPagos): ?>Pagos<?php endif; ?>
                                        <?php if (!$cargo && !$permSocios && !$permPagos): ?>Sin delegación<?php endif; ?>
                                    </small>
                                    <div style="margin-top: 0.35rem;">
                                        <button type="button" class="btn btn-secondary btn-sm btn-delegar-socio"
                                                data-id="<?php echo (int)$socio->id; ?>"
                                                data-nombre="<?php echo htmlspecialchars($socio->nombre); ?>"
                                                data-cargo="<?php echo htmlspecialchars($cargo); ?>"
                                                data-perm-socios="<?php echo $permSocios ? '1' : '0'; ?>"
                                                data-perm-pagos="<?php echo $permPagos ? '1' : '0'; ?>"
                                                data-perm-todos="<?php echo !empty($mem->permiso_todos) ? '1' : '0'; ?>"
                                                style="padding: 0.25rem 0.5rem; font-size: 0.72rem;">
                                            Delegar
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <?php if ($canManageSocios): ?>
                                        <button type="button"
                                                class="btn btn-primary btn-sm btn-editar-socio"
                                                title="Editar datos del socio"
                                                data-id="<?php echo (int)$socio->id; ?>"
                                                data-id-socio="<?php echo (int)($socio->id_socio ?? 0); ?>"
                                                data-nombres="<?php echo htmlspecialchars($socio->nombre ?? ''); ?>"
                                                data-apellido-paterno="<?php echo htmlspecialchars($socio->apellido_paterno ?? ''); ?>"
                                                data-apellido-materno="<?php echo htmlspecialchars($socio->apellido_materno ?? ''); ?>"
                                                data-rut="<?php echo htmlspecialchars($socio->rut ?? ''); ?>"
                                                data-email="<?php echo htmlspecialchars($socio->email ?? ''); ?>"
                                                data-telefono="<?php echo htmlspecialchars($socio->telefono ?? ''); ?>"
                                                data-fecha-inicio="<?php echo htmlspecialchars(cbFechaSocioInput($socio)); ?>"
                                                data-calle-id="<?php echo (int)($socio->calle_id ?? 0); ?>"
                                                data-numero-casa="<?php echo htmlspecialchars($socio->numero_casa ?? ''); ?>"
                                                style="padding: 0.4rem 0.6rem;">
                                            Editar
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Formulario Reseteo de Contraseña -->
                                        <form action="<?php echo URLROOT; ?>/admin/socio_reset_password/<?php echo $socio->id; ?>" method="POST" style="margin: 0;">
                                            <button type="submit" 
                                                    class="btn btn-secondary btn-sm confirm-action" 
                                                    data-confirm-title="Restablecer contraseña"
                                                    data-confirm-variant="warning"
                                                    data-confirm-label="Enviar clave"
                                                    data-confirm-message="Se le enviará una contraseña temporal al usuario <?php echo htmlspecialchars($socio->nombre); ?>. Usted no podrá ver la clave generada."
                                                    title="Enviar clave temporal por correo"
                                                    style="padding: 0.4rem 0.6rem;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                                Resetear
                                            </button>
                                        </form>

                                        <!-- Formulario Dar de Baja Socio (Baja Lógica) -->
                                        <form action="<?php echo URLROOT; ?>/admin/socio_eliminar/<?php echo $socio->id; ?>" method="POST" style="margin: 0;">
                                            <button type="submit" 
                                                    class="btn btn-danger btn-sm confirm-action" 
                                                    data-confirm-message="¿Estás seguro de que deseas dar de baja al socio '<?php echo htmlspecialchars($socio->nombre); ?>'? Se conservará su historial financiero pero no figurará en procesos activos."
                                                    title="Dar de Baja"
                                                    style="padding: 0.4rem 0.6rem; display: flex; align-items: center; gap: 0.25rem;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                                                Dar de Baja
                                            </button>
                                        </form>
                                        
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN PLEGABLE DE SOCIOS INACTIVOS (BAJA LÓGICA) -->
        <?php if (!empty($data['socios_inactivos'])): ?>
            <div style="margin-top: 1.5rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <details style="background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.75rem 1rem;">
                    <summary style="font-weight: 700; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; gap: 0.5rem; user-select: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                        Ver Socios Inactivos / Dados de Baja (<?php echo count($data['socios_inactivos']); ?>)
                    </summary>
                    <div style="margin-top: 1rem;">
                        <table class="table" style="font-size: 0.85rem;">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>RUT</th>
                                    <th>Fecha Inicio</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['socios_inactivos'] as $s_inact): ?>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--text-muted);">
                                            <span style="font-size: 0.75rem; font-family: monospace; padding: 0.15rem 0.35rem; background: rgba(255,255,255,0.04); border-radius: 4px; border: 1px solid var(--border-color); margin-right: 0.4rem; color: var(--text-muted);">
                                                #<?php echo htmlspecialchars($s_inact->id_socio ?? 'N/A'); ?>
                                            </span>
                                            <?php echo htmlspecialchars($s_inact->nombre); ?>
                                        </td>
                                        <td style="font-family: monospace; color: var(--text-muted);"><?php echo htmlspecialchars($s_inact->rut); ?></td>
                                        <td style="color: var(--text-muted); font-size: 0.8rem;"><?php echo cbFormatFechaSocio($s_inact); ?></td>
                                        <td>
                                            <form action="<?php echo URLROOT; ?>/admin/socio_reactivar/<?php echo $s_inact->id; ?>" method="POST" style="margin: 0;">
                                                <button type="submit" 
                                                        class="btn btn-success btn-sm confirm-action" 
                                                        data-confirm-message="¿Estás seguro de que quieres reactivar y dar de alta nuevamente al socio '<?php echo htmlspecialchars($s_inact->nombre); ?>'?"
                                                        style="padding: 0.3rem 0.5rem; font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="16" y1="11" x2="22" y2="11"></line></svg>
                                                    Reactivar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        <?php endif; ?>

    </div>

</div>

<?php if ($canManageSocios): ?>

<!-- Modal: Inscribir Socio -->
<div id="inscribirSocioModal" class="glass-modal-overlay">
    <div class="glass-modal-container glass-modal-container--wide glass-modal-container--scroll">
        <button type="button" class="modal-close-btn" data-close-modal="inscribirSocioModal" aria-label="Cerrar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h3 style="margin-bottom: 1.25rem;">Inscribir Socio Vecino</h3>
        <form action="<?php echo URLROOT; ?>/admin/socio_crear" method="POST">
            <div class="form-group">
                <label for="id_socio" class="form-label">ID Socio (Sugerido o Personalizado) *</label>
                <input type="number" name="id_socio" id="id_socio" class="form-control" placeholder="Ej: 1" value="<?php echo htmlspecialchars($data['proposed_id_socio']); ?>" min="1" required>
            </div>
            <div class="form-group">
                <label for="nombres" class="form-label">Nombres *</label>
                <input type="text" name="nombres" id="nombres" class="form-control" placeholder="Ej: Juan Antonio" required>
            </div>
            <div class="grid-2col" style="margin-bottom: 1rem; gap: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="apellido_paterno" class="form-label">Apellido Paterno *</label>
                    <input type="text" name="apellido_paterno" id="apellido_paterno" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="apellido_materno" class="form-label">Apellido Materno *</label>
                    <input type="text" name="apellido_materno" id="apellido_materno" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label for="rut" class="form-label">RUT del Socio *</label>
                <input type="text" name="rut" id="rut" class="form-control cb-rut-chile" placeholder="126667777-6" maxlength="12" required>
            </div>
            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico *</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="telefono" class="form-label">Teléfono de Contacto</label>
                <input type="text" name="telefono" id="telefono" class="form-control">
            </div>
            <div class="form-group">
                <label for="fecha_inicio" class="form-label">Fecha de Inicio como Socio *</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="calle_id" class="form-label">Calle (Jurisdicción) *</label>
                <?php if (empty($data['calles'])): ?>
                    <div class="alert alert-danger" style="padding: 0.5rem; font-size: 0.75rem; margin-bottom: 0.5rem;">
                        No hay calles registradas. Agréguelas desde el botón <strong>Calles</strong>.
                    </div>
                    <select name="calle_id" id="calle_id" class="form-control" disabled required><option value="">-- Cree una calle primero --</option></select>
                <?php else: ?>
                    <select name="calle_id" id="calle_id" class="form-control" required>
                        <option value="">-- Seleccionar Calle --</option>
                        <?php foreach ($data['calles'] as $calle): ?>
                            <option value="<?php echo $calle->id; ?>"><?php echo htmlspecialchars($calle->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="numero_casa" class="form-label">Número de Casa *</label>
                <input type="text" name="numero_casa" id="numero_casa" class="form-control" required>
            </div>
            <div class="alert alert-success" style="padding: 0.8rem; font-size: 0.75rem; margin-bottom: 1rem;">
                La contraseña inicial asignada será: <strong>socio123</strong>.
            </div>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" data-close-modal="inscribirSocioModal">Cancelar</button>
                <button type="submit" class="btn btn-primary" <?php echo empty($data['calles']) ? 'disabled' : ''; ?>>Inscribir Socio</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Ajustar Cuota -->
<div id="cuotaModal" class="glass-modal-overlay">
    <div class="glass-modal-container glass-modal-container--scroll">
        <button type="button" class="modal-close-btn" data-close-modal="cuotaModal" aria-label="Cerrar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h3 style="margin-bottom: 1rem;">Ajustar Valor de Cuota</h3>
        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.75rem 1rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.85rem; color: var(--text-muted);">Cuota vigente:</span>
            <strong style="font-size: 1.2rem; color: var(--primary);">$<?php echo $cuotaVigenteMonto; ?></strong>
        </div>
        <form action="<?php echo URLROOT; ?>/admin/cuota_ajustar" method="POST">
            <div class="form-group">
                <label for="monto" class="form-label">Nuevo Monto de Cuota ($) *</label>
                <input type="number" name="monto" id="monto" class="form-control" placeholder="Ej: 6000" min="0" required>
            </div>
            <div class="form-group">
                <label for="mes_inicio" class="form-label">Aplicar Desde el Mes *</label>
                <input type="month" name="mes_inicio" id="mes_inicio" class="form-control" value="<?php echo date('Y-m'); ?>" required>
            </div>
            <button type="submit" class="btn btn-success" style="width: 100%; margin-bottom: 1.25rem;">Programar Reajuste</button>
        </form>
        <?php if (!empty($data['cuotas_historial'])): ?>
        <h4 style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Historial de valores</h4>
        <div style="max-height: 160px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.35rem;">
            <?php foreach ($data['cuotas_historial'] as $cuota): ?>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; padding: 0.35rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <span>Desde <?php echo htmlspecialchars($cuota->mes_inicio); ?></span>
                    <strong>$<?php echo number_format($cuota->monto, 0, ',', '.'); ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Calles -->
<div id="callesModal" class="glass-modal-overlay">
    <div class="glass-modal-container glass-modal-container--scroll">
        <button type="button" class="modal-close-btn" data-close-modal="callesModal" aria-label="Cerrar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h3 style="margin-bottom: 1.25rem;">Calles de la Jurisdicción</h3>
        <form action="<?php echo URLROOT; ?>/admin/calle_crear" method="POST" style="margin-bottom: 1.25rem;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="calle_nombre_nuevo" class="form-label">Nueva Calle *</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" name="nombre" id="calle_nombre_nuevo" class="form-control" placeholder="Ej: Manuel Rodríguez" required style="flex: 1;">
                    <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Añadir</button>
                </div>
            </div>
        </form>
        <?php if (empty($data['calles'])): ?>
            <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 1rem 0;">Aún no hay calles creadas.</p>
        <?php else: ?>
            <div style="max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach ($data['calles'] as $calle): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.65rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.85rem;">
                        <span style="font-weight: 600;"><?php echo htmlspecialchars($calle->nombre); ?></span>
                        <form action="<?php echo URLROOT; ?>/admin/calle_eliminar/<?php echo $calle->id; ?>" method="POST" style="margin: 0;">
                            <button type="submit" class="btn btn-danger btn-sm confirm-action"
                                    data-confirm-message="¿Eliminar la calle '<?php echo htmlspecialchars($calle->nombre); ?>'? Los socios quedarán sin calle asociada."
                                    style="padding: 0.25rem 0.4rem; font-size: 0.72rem;">Eliminar</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Link de invitación -->
<div id="invitacionModal" class="glass-modal-overlay">
    <div class="glass-modal-container glass-modal-container--wide glass-modal-container--scroll">
        <button type="button" class="modal-close-btn" data-close-modal="invitacionModal" aria-label="Cerrar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h3 style="margin-bottom: 0.35rem;">Link de invitación (24 h)</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Comparta el enlace para que nuevos vecinos soliciten su registro. Usted aprueba cada solicitud.
        </p>

        <?php if (!empty($data['link_invitacion'])): ?>
            <div class="form-group">
                <label class="form-label">Enlace recién generado</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="linkInvitacionInput" class="form-control" readonly value="<?php echo htmlspecialchars($data['link_invitacion']); ?>" style="font-size: 0.75rem; font-family: monospace;">
                    <button type="button" class="btn btn-secondary" id="btnCopiarLink" style="white-space: nowrap;">Copiar</button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($invitacionesActivas)): ?>
            <h4 style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Enlaces activos</h4>
            <?php foreach ($invitacionesActivas as $inv):
                $linkInv = URLROOT . '/invite/registro/' . $inv->token;
            ?>
                <div style="padding: 0.5rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); margin-bottom: 0.5rem; font-size: 0.75rem;">
                    <div style="margin-bottom: 0.35rem; color: var(--text-muted);">Vence: <?php echo date('d-m-Y H:i', strtotime($inv->expires_at)); ?></div>
                    <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                        <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($linkInv); ?>" style="flex: 1; min-width: 0; font-size: 0.68rem; font-family: monospace; padding: 0.35rem 0.5rem;">
                        <button type="button" class="btn btn-secondary btn-sm btn-copiar-invitacion" data-link="<?php echo htmlspecialchars($linkInv); ?>" style="white-space: nowrap;">Copiar link</button>
                        <form action="<?php echo URLROOT; ?>/admin/invitacion_revocar/<?php echo (int)$inv->id; ?>" method="POST" style="margin: 0;">
                            <button type="submit" class="btn btn-danger btn-sm confirm-action" data-confirm-message="¿Revocar este enlace?" style="white-space: nowrap;">Revocar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif (empty($data['link_invitacion'])): ?>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">No hay enlaces activos. Genere uno nuevo abajo.</p>
        <?php endif; ?>

        <form action="<?php echo URLROOT; ?>/admin/generar_invitacion" method="POST">
            <button type="submit" class="btn btn-primary" style="width: 100%;">Generar nuevo enlace (24 horas)</button>
        </form>
    </div>
</div>

<div id="editarSocioModal" class="glass-modal-overlay">
    <div class="glass-modal-container" style="max-width: 520px;">
        <button type="button" id="closeEditarSocioModal" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--text-muted); cursor: pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h3 style="margin-bottom: 1.25rem;">Editar datos del socio</h3>
        <form action="<?php echo URLROOT; ?>/admin/socio_actualizar" method="POST">
            <input type="hidden" name="socio_id" id="edit_socio_id">
            <div class="form-group">
                <label class="form-label">ID Socio *</label>
                <input type="number" name="id_socio" id="edit_id_socio" class="form-control" min="1" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nombres *</label>
                <input type="text" name="nombres" id="edit_nombres" class="form-control" required>
            </div>
            <div class="grid-2col">
                <div class="form-group">
                    <label class="form-label">Apellido Paterno *</label>
                    <input type="text" name="apellido_paterno" id="edit_apellido_paterno" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Apellido Materno *</label>
                    <input type="text" name="apellido_materno" id="edit_apellido_materno" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">RUT *</label>
                <input type="text" name="rut" id="edit_rut" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Correo *</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="edit_telefono" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Fecha inicio como socio *</label>
                <input type="date" name="fecha_inicio" id="edit_fecha_inicio" class="form-control" required>
            </div>
            <div class="grid-2col">
                <div class="form-group">
                    <label class="form-label">Calle *</label>
                    <select name="calle_id" id="edit_calle_id" class="form-control" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($data['calles'] as $calle): ?>
                            <option value="<?php echo (int)$calle->id; ?>"><?php echo htmlspecialchars($calle->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">N° Casa *</label>
                    <input type="text" name="numero_casa" id="edit_numero_casa" class="form-control" required>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" class="btn btn-secondary" id="cancelEditarSocioModal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($canManageSocios): ?>
<div id="pendienteSocioModal" class="glass-modal-overlay">
    <div class="glass-modal-container" style="max-width: 540px; max-height: 90vh; overflow-y: auto;">
        <button type="button" id="closePendienteSocioModal" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--text-muted); cursor: pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h3 style="margin-bottom: 0.25rem;">Revisar solicitud pendiente</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.25rem;">Edite los datos si es necesario y apruebe para activar la cuenta y enviar el correo de acceso.</p>

        <form id="formPendienteActualizar" action="<?php echo URLROOT; ?>/admin/socio_pendiente_actualizar" method="POST">
            <input type="hidden" name="socio_id" id="pend_socio_id">
            <div class="form-group">
                <label class="form-label">ID Socio (se asigna al aprobar) *</label>
                <input type="number" name="id_socio" id="pend_id_socio" class="form-control" min="1" value="<?php echo htmlspecialchars($data['proposed_id_socio']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Nombres *</label>
                <input type="text" name="nombres" id="pend_nombres" class="form-control" required>
            </div>
            <div class="grid-2col">
                <div class="form-group">
                    <label class="form-label">Apellido Paterno *</label>
                    <input type="text" name="apellido_paterno" id="pend_apellido_paterno" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Apellido Materno *</label>
                    <input type="text" name="apellido_materno" id="pend_apellido_materno" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">RUT *</label>
                <input type="text" name="rut" id="pend_rut" class="form-control cb-rut-chile" required maxlength="12">
            </div>
            <div class="grid-2col">
                <div class="form-group">
                    <label class="form-label">Género</label>
                    <select name="genero" id="pend_genero" class="form-control">
                        <option value="">-- Sin indicar --</option>
                        <option value="MASCULINO">Masculino</option>
                        <option value="FEMENINO">Femenino</option>
                        <option value="NO ESPECIFICAR">No especificar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha nacimiento</label>
                    <input type="date" name="fecha_nacimiento" id="pend_fecha_nacimiento" class="form-control" max="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Correo *</label>
                <input type="email" name="email" id="pend_email" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="pend_telefono" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Fecha inicio *</label>
                <input type="date" name="fecha_inicio" id="pend_fecha_inicio" class="form-control" required>
            </div>
            <div class="grid-2col">
                <div class="form-group">
                    <label class="form-label">Calle *</label>
                    <select name="calle_id" id="pend_calle_id" class="form-control" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($data['calles'] as $calle): ?>
                            <option value="<?php echo (int)$calle->id; ?>"><?php echo htmlspecialchars($calle->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">N° Casa *</label>
                    <input type="text" name="numero_casa" id="pend_numero_casa" class="form-control" required>
                </div>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" class="btn btn-secondary" id="cancelPendienteSocioModal">Cancelar</button>
                <button type="submit" class="btn btn-secondary">Guardar cambios</button>
            </div>
        </form>

        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: space-between; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <form id="formPendienteRechazar" action="" method="POST" style="margin: 0;">
                <button type="submit" class="btn btn-danger confirm-action" data-confirm-message="¿Rechazar y eliminar esta solicitud de registro?">Rechazar solicitud</button>
            </form>
            <form id="formPendienteAprobar" action="<?php echo URLROOT; ?>/admin/socio_pendiente_aprobar" method="POST" style="margin: 0;">
                <input type="hidden" name="socio_id" id="pend_aprobar_socio_id">
                <input type="hidden" name="id_socio" id="pend_aprobar_id_socio">
                <input type="hidden" name="nombres" id="pend_aprobar_nombres">
                <input type="hidden" name="apellido_paterno" id="pend_aprobar_apellido_paterno">
                <input type="hidden" name="apellido_materno" id="pend_aprobar_apellido_materno">
                <input type="hidden" name="rut" id="pend_aprobar_rut">
                <input type="hidden" name="email" id="pend_aprobar_email">
                <input type="hidden" name="genero" id="pend_aprobar_genero">
                <input type="hidden" name="fecha_nacimiento" id="pend_aprobar_fecha_nacimiento">
                <input type="hidden" name="telefono" id="pend_aprobar_telefono">
                <input type="hidden" name="fecha_inicio" id="pend_aprobar_fecha_inicio">
                <input type="hidden" name="calle_id" id="pend_aprobar_calle_id">
                <input type="hidden" name="numero_casa" id="pend_aprobar_numero_casa">
                <button type="submit" class="btn btn-success confirm-action" data-confirm-message="¿Aprobar registro y enviar correo con clave temporal al socio?">Aprobar y enviar acceso</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isFullAdmin): ?>
<div id="delegacionModal" class="glass-modal-overlay">
    <div class="glass-modal-container" style="max-width: 480px;">
        <button type="button" id="closeDelegacionModal" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--text-muted); cursor: pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h3 style="margin-bottom: 0.25rem;">Delegar cargo y permisos</h3>
        <p id="delegacion_socio_nombre" style="color: var(--primary); font-weight: 600; margin-bottom: 1.25rem;"></p>
        <form action="<?php echo URLROOT; ?>/admin/socio_delegacion" method="POST">
            <input type="hidden" name="usuario_id" id="delegacion_usuario_id">
            <div class="form-group">
                <label class="form-label">Cargo en la directiva</label>
                <select name="cargo" id="delegacion_cargo" class="form-control">
                    <option value="">Sin cargo</option>
                    <option value="SECRETARIO">Secretario</option>
                    <option value="TESORERO">Tesorero</option>
                    <option value="DIRECTOR">Director</option>
                </select>
            </div>
            <div class="form-group" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label class="form-label">Permisos delegados</label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                    <input type="checkbox" name="permiso_gestion_socios" id="delegacion_perm_socios" value="1">
                    Gestionar socios (incorporar, editar padrón)
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                    <input type="checkbox" name="permiso_registro_pagos" id="delegacion_perm_pagos" value="1">
                    Registrar pagos y movimientos de caja
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                    <input type="checkbox" name="permiso_todos" id="delegacion_perm_todos" value="1">
                    Todos los permisos (director)
                </label>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem;">
                Los permisos solo aplican si usted los otorga explícitamente. El secretario y tesorero pueden recibir permisos acordes a su cargo.
            </p>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" id="cancelDelegacionModal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($canManageSocios): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const openModal = (el) => { if (el) { el.classList.add('is-open'); document.body.style.overflow = 'hidden'; } };
    const closeModal = (el) => { if (el) { el.classList.remove('is-open'); document.body.style.overflow = ''; } };

    document.querySelectorAll('[data-open-modal]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            openModal(document.getElementById(this.getAttribute('data-open-modal')));
        });
    });
    document.querySelectorAll('[data-close-modal]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            closeModal(document.getElementById(this.getAttribute('data-close-modal')));
        });
    });

    document.querySelectorAll('.glass-modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeModal(overlay);
        });
    });

    <?php if (!empty($data['link_invitacion'])): ?>
    openModal(document.getElementById('invitacionModal'));
    <?php endif; ?>

    const editModal = document.getElementById('editarSocioModal');
    document.querySelectorAll('.btn-editar-socio').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_socio_id').value = this.dataset.id;
            document.getElementById('edit_id_socio').value = this.dataset.idSocio || '';
            document.getElementById('edit_nombres').value = this.dataset.nombres || '';
            document.getElementById('edit_apellido_paterno').value = this.dataset.apellidoPaterno || '';
            document.getElementById('edit_apellido_materno').value = this.dataset.apellidoMaterno || '';
            document.getElementById('edit_rut').value = this.dataset.rut || '';
            document.getElementById('edit_email').value = this.dataset.email || '';
            document.getElementById('edit_telefono').value = this.dataset.telefono || '';
            document.getElementById('edit_fecha_inicio').value = this.dataset.fechaInicio || '';
            document.getElementById('edit_calle_id').value = this.dataset.calleId || '';
            document.getElementById('edit_numero_casa').value = this.dataset.numeroCasa || '';
            openModal(editModal);
        });
    });
    document.getElementById('closeEditarSocioModal')?.addEventListener('click', () => closeModal(editModal));
    document.getElementById('cancelEditarSocioModal')?.addEventListener('click', () => closeModal(editModal));
    editModal?.addEventListener('click', e => { if (e.target === editModal) closeModal(editModal); });

    const pendModal = document.getElementById('pendienteSocioModal');
    const syncPendienteApproveFields = () => {
        document.getElementById('pend_aprobar_socio_id').value = document.getElementById('pend_socio_id').value;
        document.getElementById('pend_aprobar_id_socio').value = document.getElementById('pend_id_socio').value;
        document.getElementById('pend_aprobar_nombres').value = document.getElementById('pend_nombres').value;
        document.getElementById('pend_aprobar_apellido_paterno').value = document.getElementById('pend_apellido_paterno').value;
        document.getElementById('pend_aprobar_apellido_materno').value = document.getElementById('pend_apellido_materno').value;
        document.getElementById('pend_aprobar_rut').value = document.getElementById('pend_rut').value;
        document.getElementById('pend_aprobar_email').value = document.getElementById('pend_email').value;
        document.getElementById('pend_aprobar_genero').value = document.getElementById('pend_genero').value;
        document.getElementById('pend_aprobar_fecha_nacimiento').value = document.getElementById('pend_fecha_nacimiento').value;
        document.getElementById('pend_aprobar_telefono').value = document.getElementById('pend_telefono').value;
        document.getElementById('pend_aprobar_fecha_inicio').value = document.getElementById('pend_fecha_inicio').value;
        document.getElementById('pend_aprobar_calle_id').value = document.getElementById('pend_calle_id').value;
        document.getElementById('pend_aprobar_numero_casa').value = document.getElementById('pend_numero_casa').value;
    };
    document.querySelectorAll('.btn-revisar-pendiente').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            document.getElementById('pend_socio_id').value = id;
            document.getElementById('pend_id_socio').value = this.dataset.idSocio || '<?php echo (int)$data['proposed_id_socio']; ?>';
            document.getElementById('pend_nombres').value = this.dataset.nombres || '';
            document.getElementById('pend_apellido_paterno').value = this.dataset.apellidoPaterno || '';
            document.getElementById('pend_apellido_materno').value = this.dataset.apellidoMaterno || '';
            document.getElementById('pend_rut').value = this.dataset.rut || '';
            document.getElementById('pend_email').value = this.dataset.email || '';
            document.getElementById('pend_telefono').value = this.dataset.telefono || '';
            document.getElementById('pend_fecha_inicio').value = this.dataset.fechaInicio || '';
            document.getElementById('pend_genero').value = this.dataset.genero || '';
            document.getElementById('pend_fecha_nacimiento').value = this.dataset.fechaNacimiento || '';
            document.getElementById('pend_calle_id').value = this.dataset.calleId || '';
            document.getElementById('pend_numero_casa').value = this.dataset.numeroCasa || '';
            document.getElementById('formPendienteRechazar').action = '<?php echo URLROOT; ?>/admin/socio_pendiente_rechazar/' + id;
            syncPendienteApproveFields();
            openModal(pendModal);
        });
    });
    document.getElementById('formPendienteAprobar')?.addEventListener('submit', syncPendienteApproveFields);
    document.getElementById('closePendienteSocioModal')?.addEventListener('click', () => closeModal(pendModal));
    document.getElementById('cancelPendienteSocioModal')?.addEventListener('click', () => closeModal(pendModal));
    pendModal?.addEventListener('click', e => { if (e.target === pendModal) closeModal(pendModal); });

    function copiarLinkInvitacion(btn, link) {
        const url = link || btn.dataset.link;
        if (!url) return;
        const copiar = navigator.clipboard?.writeText(url);
        const ok = () => {
            const prev = btn.textContent;
            btn.textContent = 'Copiado';
            setTimeout(() => { btn.textContent = prev; }, 2000);
        };
        if (copiar) {
            copiar.then(ok).catch(() => {
                const tmp = document.createElement('textarea');
                tmp.value = url;
                document.body.appendChild(tmp);
                tmp.select();
                document.execCommand('copy');
                tmp.remove();
                ok();
            });
        }
    }

    document.getElementById('btnCopiarLink')?.addEventListener('click', function() {
        const input = document.getElementById('linkInvitacionInput');
        copiarLinkInvitacion(this, input ? input.value : '');
    });

    document.querySelectorAll('.btn-copiar-invitacion').forEach(function(btn) {
        btn.addEventListener('click', function() {
            copiarLinkInvitacion(this);
        });
    });

    function formatRutChile(raw) {
        let v = String(raw).replace(/[^0-9kK]/g, '').toUpperCase();
        if (v.length <= 1) return v;
        return v.slice(0, -1) + '-' + v.slice(-1);
    }
    document.querySelectorAll('.cb-rut-chile').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = formatRutChile(this.value);
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === ' ' || e.key === '.') e.preventDefault();
        });
    });

    <?php if ($isFullAdmin): ?>
    const delegacionModal = document.getElementById('delegacionModal');
    document.querySelectorAll('.btn-delegar-socio').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delegacion_usuario_id').value = this.dataset.id;
            document.getElementById('delegacion_socio_nombre').textContent = this.dataset.nombre;
            document.getElementById('delegacion_cargo').value = this.dataset.cargo || '';
            document.getElementById('delegacion_perm_socios').checked = this.dataset.permSocios === '1';
            document.getElementById('delegacion_perm_pagos').checked = this.dataset.permPagos === '1';
            document.getElementById('delegacion_perm_todos').checked = this.dataset.permTodos === '1';
            openModal(delegacionModal);
        });
    });
    document.getElementById('closeDelegacionModal')?.addEventListener('click', () => closeModal(delegacionModal));
    document.getElementById('cancelDelegacionModal')?.addEventListener('click', () => closeModal(delegacionModal));
    delegacionModal?.addEventListener('click', e => { if (e.target === delegacionModal) closeModal(delegacionModal); });
    document.getElementById('delegacion_cargo')?.addEventListener('change', function() {
        if (this.value === 'SECRETARIO') document.getElementById('delegacion_perm_socios').checked = true;
        if (this.value === 'TESORERO') document.getElementById('delegacion_perm_pagos').checked = true;
        if (this.value === 'DIRECTOR') document.getElementById('delegacion_perm_todos').checked = true;
    });
    <?php endif; ?>
});
</script>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
