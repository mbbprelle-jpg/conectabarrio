<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
require_once APPROOT . '/core/AuthContext.php';
$isFullAdmin = AuthContext::isFullAdmin();
$membresiasMap = $data['membresias_map'] ?? [];
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

<div class="grid-2col">
    
    <!-- SECCIÓN DE SOCIOS (IZQUIERDA) -->
    <div class="card card-primary" style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h3 class="card-title" style="margin-bottom: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Padrón de Socios Vecinos
            </h3>
            
            <!-- Barra de búsqueda dinámica -->
            <input type="text" id="searchSocio" class="form-control" placeholder="Buscar socio por nombre o RUT..." style="max-width: 250px; padding: 0.5rem 0.8rem; font-size: 0.85rem;">
        </div>

        <?php if (empty($data['socios'])): ?>
            <p style="color: var(--text-muted); text-align: center; padding: 2rem;">Aún no ha inscrito ningún socio en su Junta. Utilice el formulario de la derecha.</p>
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
                                        Socio desde: <?php echo date('d-m-Y', strtotime($socio->fecha_inicio)); ?>
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
                                    <div style="display: flex; gap: 0.5rem;">
                                        
                                        <!-- Formulario Reseteo de Contraseña -->
                                        <form action="<?php echo URLROOT; ?>/admin/socio_reset_password/<?php echo $socio->id; ?>" method="POST" style="margin: 0;">
                                            <button type="submit" 
                                                    class="btn btn-secondary btn-sm confirm-action" 
                                                    data-confirm-message="¿Estás seguro de que quieres resetear la contraseña del socio '<?php echo htmlspecialchars($socio->nombre); ?>' a la contraseña por defecto (socio123)?"
                                                    title="Resetear Contraseña"
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
                                        <td style="color: var(--text-muted); font-size: 0.8rem;"><?php echo date('d-m-Y', strtotime($s_inact->fecha_inicio)); ?></td>
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

    <!-- ACCIONES DE FORMULARIOS (DERECHA) -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- CARD 1: INSCRIBIR SOCIO VECINO -->
        <div class="card card-warning">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="16" y1="11" x2="22" y2="11"></line></svg>
                Inscribir Socio Vecino
            </h3>
            
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
                        <input type="text" name="apellido_paterno" id="apellido_paterno" class="form-control" placeholder="Ej: Pérez" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="apellido_materno" class="form-label">Apellido Materno *</label>
                        <input type="text" name="apellido_materno" id="apellido_materno" class="form-control" placeholder="Ej: Muñoz" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="rut" class="form-label">RUT del Socio *</label>
                    <input type="text" name="rut" id="rut" class="form-control" placeholder="Ej: 12.345.678-9" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico *</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Ej: juan@gmail.com" required>
                </div>

                <div class="form-group">
                    <label for="telefono" class="form-label">Teléfono de Contacto</label>
                    <input type="text" name="telefono" id="telefono" class="form-control" placeholder="Ej: +56912345678">
                </div>

                <div class="form-group">
                    <label for="fecha_inicio" class="form-label">Fecha de Inicio como Socio *</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="calle_id" class="form-label">Calle (Jurisdicción) *</label>
                    <?php if (empty($data['calles'])): ?>
                        <div class="alert alert-danger" style="padding: 0.5rem; font-size: 0.75rem; margin-bottom: 0.5rem;">
                            ⚠️ No hay calles registradas en esta junta. Agréguelas en la sección inferior de la derecha antes de inscribir al socio.
                        </div>
                        <select name="calle_id" id="calle_id" class="form-control" disabled required>
                            <option value="">-- Cree una calle primero --</option>
                        </select>
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
                    <input type="text" name="numero_casa" id="numero_casa" class="form-control" placeholder="Ej: 405-B" required>
                </div>

                <div class="alert alert-success" style="padding: 0.8rem; background-color: rgba(245,158,11,0.05); color: var(--warning); border-left-color: var(--warning); font-size: 0.75rem; margin-bottom: 1.5rem;">
                    La contraseña inicial asignada será: <strong>socio123</strong>. El socio podrá ingresar al sistema para ver sus comprobantes.
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Inscribir Socio
                </button>
            </form>
        </div>

        <!-- CARD 2: CONFIGURAR / AJUSTAR VALOR DE CUOTA -->
        <div class="card card-primary">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                Ajustar Valor de Cuota
            </h3>

            <!-- Cuota Actual Informativa -->
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.75rem 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: var(--text-muted);">Cuota Vigente:</span>
                <strong style="font-size: 1.2rem; color: var(--primary); font-family: var(--font-heading);">
                    $<?php 
                    $cuotaVigente = !empty($data['cuotas_historial']) ? $data['cuotas_historial'][0] : null;
                    echo $cuotaVigente ? number_format($cuotaVigente->monto, 0, ',', '.') : '0';
                    ?>
                </strong>
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

                <button type="submit" class="btn btn-success" style="width: 100%;">
                    Programar Reajuste
                </button>
            </form>
            
            <!-- Historial de Cuotas Ajustadas -->
            <div style="margin-top: 1.5rem;">
                <h4 style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Historial de Valores</h4>
                <div style="max-height: 120px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem; padding-right: 0.25rem;">
                    <?php foreach ($data['cuotas_historial'] as $cuota): ?>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; padding: 0.35rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <span>Desde <?php echo htmlspecialchars($cuota->mes_inicio); ?>:</span>
                            <strong style="color: var(--text-main);">$<?php echo number_format($cuota->monto, 0, ',', '.'); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- CARD 3: GESTIÓN DE CALLES DE LA JURISDICCIÓN -->
        <div class="card card-warning">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon><line x1="9" y1="3" x2="9" y2="18"></line><line x1="15" y1="6" x2="15" y2="21"></line></svg>
                Calles de la Jurisdicción
            </h3>

            <!-- Formulario Rápido Crear Calle -->
            <form action="<?php echo URLROOT; ?>/admin/calle_crear" method="POST" style="margin-bottom: 1.5rem;">
                <div class="form-group" style="margin-bottom: 0.75rem;">
                    <label for="calle_nombre_nuevo" class="form-label">Nueva Calle *</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="nombre" id="calle_nombre_nuevo" class="form-control" placeholder="Ej: Manuel Rodríguez" required style="flex: 1;">
                        <button type="submit" class="btn btn-primary" style="padding: 0 1rem; white-space: nowrap;">
                            Añadir
                        </button>
                    </div>
                </div>
            </form>

            <!-- Listado de Calles Existentes -->
            <div>
                <h4 style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Calles Registradas</h4>
                <?php if (empty($data['calles'])): ?>
                    <p style="color: var(--text-muted); font-size: 0.8rem; text-align: center; padding: 1rem 0;">Aún no hay calles creadas. Añada una calle arriba.</p>
                <?php else: ?>
                    <div style="max-height: 200px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem; padding-right: 0.25rem;">
                        <?php foreach ($data['calles'] as $calle): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.6rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.85rem;">
                                <span style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($calle->nombre); ?></span>
                                
                                <form action="<?php echo URLROOT; ?>/admin/calle_eliminar/<?php echo $calle->id; ?>" method="POST" style="margin: 0;">
                                    <button type="submit" 
                                            class="btn btn-danger btn-sm confirm-action" 
                                            data-confirm-message="¿Estás seguro de que quieres eliminar la calle '<?php echo htmlspecialchars($calle->nombre); ?>'? Los socios de esta calle quedarán sin calle asociada."
                                            style="padding: 0.25rem 0.4rem; font-size: 0.75rem; display: flex; align-items: center;"
                                            title="Eliminar Calle">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('delegacionModal');
    const open = m => { if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; } };
    const close = m => { if (m) { m.style.display = 'none'; document.body.style.overflow = ''; } };
    document.querySelectorAll('.btn-delegar-socio').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delegacion_usuario_id').value = this.dataset.id;
            document.getElementById('delegacion_socio_nombre').textContent = this.dataset.nombre;
            document.getElementById('delegacion_cargo').value = this.dataset.cargo || '';
            document.getElementById('delegacion_perm_socios').checked = this.dataset.permSocios === '1';
            document.getElementById('delegacion_perm_pagos').checked = this.dataset.permPagos === '1';
            document.getElementById('delegacion_perm_todos').checked = this.dataset.permTodos === '1';
            open(modal);
        });
    });
    document.getElementById('closeDelegacionModal')?.addEventListener('click', () => close(modal));
    document.getElementById('cancelDelegacionModal')?.addEventListener('click', () => close(modal));
    modal?.addEventListener('click', e => { if (e.target === modal) close(modal); });
    document.getElementById('delegacion_cargo')?.addEventListener('change', function() {
        if (this.value === 'SECRETARIO') document.getElementById('delegacion_perm_socios').checked = true;
        if (this.value === 'TESORERO') document.getElementById('delegacion_perm_pagos').checked = true;
        if (this.value === 'DIRECTOR') document.getElementById('delegacion_perm_todos').checked = true;
    });
});
</script>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
