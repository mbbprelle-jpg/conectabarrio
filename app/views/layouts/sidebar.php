<!-- Sidebar -->
<?php require_once APPROOT . '/core/AuthContext.php'; ?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-top">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
        </div>
        <div class="sidebar-brand-text">
            <div class="sidebar-brand-name">ConectaBarrio</div>
            <div class="sidebar-brand-tag">Plataforma comunitaria</div>
        </div>
    </div>

    <!-- Perfil de Usuario en Sidebar -->
    <?php
    $sidebarNombreRaw = $_SESSION['user_nombre'] ?? '';
    $sidebarDisplayName = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $sidebarNombreRaw));
    if ($sidebarDisplayName === '') {
        $sidebarDisplayName = $sidebarNombreRaw;
    }

    $cargoLabels = [
        'SECRETARIO' => 'Secretario/a',
        'TESORERO' => 'Tesorero/a',
        'DIRECTOR' => 'Director/a',
    ];
    $sidebarRoleLabel = 'Socio';
    if ($_SESSION['user_rol'] === 'maestro') {
        $sidebarRoleLabel = 'Maestro del sistema';
    } elseif ($_SESSION['user_rol'] === 'admin') {
        $sidebarRoleLabel = 'Administrador';
    } elseif (!empty($_SESSION['user_cargo'])) {
        $cargoKey = strtoupper((string) $_SESSION['user_cargo']);
        $sidebarRoleLabel = $cargoLabels[$cargoKey] ?? ucfirst(strtolower((string) $_SESSION['user_cargo']));
    }

    $sidebarInitials = '';
    foreach (array_slice(preg_split('/\s+/', $sidebarDisplayName), 0, 2) as $part) {
        if ($part !== '') {
            $sidebarInitials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
    }
    if ($sidebarInitials === '') {
        $sidebarInitials = '?';
    }
    ?>
    <div class="sidebar-profile">
        <div class="sidebar-profile-avatar" aria-hidden="true"><?php echo htmlspecialchars($sidebarInitials); ?></div>
        <div class="sidebar-profile-body">
            <div class="sidebar-profile-name"><?php echo htmlspecialchars($sidebarDisplayName); ?></div>
            <span class="sidebar-profile-badge"><?php echo htmlspecialchars($sidebarRoleLabel); ?></span>
            <?php if (!empty($_SESSION['user_junta_nombre']) && $_SESSION['user_rol'] !== 'maestro'): ?>
                <div class="sidebar-profile-org">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <span><?php echo htmlspecialchars($_SESSION['user_junta_nombre']); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <nav class="sidebar-nav" aria-label="Menú principal">
    <ul class="sidebar-menu">
        <?php if ($_SESSION['user_rol'] === 'maestro'): ?>
            <!-- Menú Perfil Maestro -->
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'dashboard') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/maestro/dashboard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                    <span>Dashboard Maestro</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'crear_junta') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/maestro/crear_junta">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                    <span>Nueva Organización</span>
                </a>
            </li>
                    <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'payments') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/maestro/payments">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2" ry="2"></rect><line x1="3" y1="11" x2="21" y2="11"></line></svg>
                    <span>Historial de Pagos</span>
                </a>
            </li>
            <?php elseif ($_SESSION['user_rol'] === 'admin'): ?>
            <li class="sidebar-menu-group"><span>Panel</span></li>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'dashboard') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/dashboard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-menu-group"><span>Comunidad</span></li>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'socios') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/socios">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>Socios y Ajustes</span>
                </a>
            </li>
            <?php if (AuthContext::canViewMapaSocios()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'mapa_socios') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/mapa_socios">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                    <span>Mapa comunitario</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (AuthContext::canRegisterPayments() || AuthContext::canViewFlujoCaja()): ?>
            <li class="sidebar-menu-group"><span>Finanzas</span></li>
            <?php endif; ?>
            <?php if (AuthContext::canRegisterPayments()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'finanzas') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/finanzas">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Movimientos</span>
                </a>
            </li>
            <?php if (AuthContext::canViewFlujoCaja()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'flujo_caja') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/flujo_caja">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span>Flujo de Caja</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'conceptos_caja') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/conceptos_caja">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                    <span>Conceptos</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'cierres') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/cierres">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <span>Cierres mensuales</span>
                </a>
            </li>
            <?php elseif (AuthContext::canViewFlujoCaja()): ?>
            <li class="sidebar-menu-group"><span>Finanzas</span></li>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'flujo_caja') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/flujo_caja">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span>Flujo de Caja</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-menu-group"><span>Operaciones</span></li>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'asistencia') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/asistencia">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Reuniones</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'calendario') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/calendario">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span>Calendario</span>
                </a>
            </li>
            <?php if (AuthContext::isDirectivo()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'documentacion_legal') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/documentacion_legal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <span>Doc. legal</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (AuthContext::canViewDocumentos()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'documentos') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/documentos">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                    <span>Documentos</span>
                </a>
            </li>
            <?php endif; ?>
        <?php elseif ($_SESSION['user_rol'] === 'socio'): ?>
            <!-- Menú Perfil Socio (con permisos delegados) -->
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'dashboard') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/socio/dashboard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                    <span>Mi Perfil</span>
                </a>
            </li>
            <?php if (AuthContext::canManageSocios()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'socios') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/socios">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>Gestión de Socios</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (AuthContext::canViewMapaSocios()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'mapa_socios') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/mapa_socios">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                    <span>Mapa comunitario</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (AuthContext::canRegisterPayments() || AuthContext::canViewFlujoCaja()): ?>
            <li class="sidebar-menu-group"><span>Finanzas</span></li>
            <?php endif; ?>
            <?php if (AuthContext::canRegisterPayments()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'finanzas') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/finanzas">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Movimientos</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'conceptos_caja') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/conceptos_caja">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                    <span>Conceptos</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (AuthContext::canViewFlujoCaja()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'flujo_caja') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/flujo_caja">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span>Flujo de Caja</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (AuthContext::canViewDocumentos()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'documentos') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/documentos">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                    <span>Documentos</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'reuniones') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/socio/reuniones">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span>Reuniones</span>
                </a>
            </li>
            <?php if (AuthContext::isDirectivo() || AuthContext::canManageReuniones()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'calendario') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/calendario">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span>Calendario</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (AuthContext::isDirectivo()): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'documentacion_legal') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/admin/documentacion_legal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <span>Doc. legal</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'solicitar_cambio') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/socio/solicitar_cambio">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    <span>Actualizar mis datos</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'comprobantes') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/socio/comprobantes">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Mis Comprobantes</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
    </nav>

    <div class="sidebar-footer">
        <ul class="sidebar-menu sidebar-menu--footer">
            <?php if (empty($_SESSION['must_change'])): ?>
            <li class="sidebar-menu-item <?php echo (isset($data['active_menu']) && $data['active_menu'] === 'password') ? 'active' : ''; ?>">
                <a href="<?php echo URLROOT; ?>/auth/changePassword">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <span>Cambiar contraseña</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-menu-item sidebar-menu-item--logout">
                <a href="<?php echo URLROOT; ?>/auth/logout" class="confirm-action" data-confirm-message="¿Estás seguro de que quieres cerrar tu sesión?">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    <span>Cerrar sesión</span>
                </a>
            </li>
        </ul>
    </div>
</div>
