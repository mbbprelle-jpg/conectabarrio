<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/SocioInput.php'; ?>

<!-- Grid de Información y Estadísticas -->
<div class="metrics-grid">
    
    <!-- Cuota del Mes Vigente -->
    <div class="card metric-card card-primary">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Cuota del Mes Actual</span>
            <span class="metric-value">$<?php echo number_format($data['cuota_vigente'], 0, ',', '.'); ?> CLP</span>
        </div>
    </div>

    <!-- Total Aportado Histórico -->
    <div class="card metric-card card-success">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Total Aportado</span>
            <span class="metric-value">$<?php echo number_format($data['total_pagado'], 0, ',', '.'); ?> CLP</span>
        </div>
    </div>

    <!-- Cantidad de Pagos Registrados -->
    <div class="card metric-card card-warning">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Mensualidades Pagadas</span>
            <span class="metric-value"><?php echo $data['cantidad_pagos']; ?> meses</span>
        </div>
    </div>

    <!-- Estado del Socio -->
    <div class="card metric-card card-info">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Estado de Afiliación</span>
            <span class="metric-value" style="color: var(--success); font-weight: 700; text-transform: uppercase;">
                <?php echo $data['socio']->estado == 1 ? 'Activo' : 'Inactivo'; ?>
            </span>
        </div>
    </div>

</div>

<!-- Sección de Detalles del Perfil y Actividad Reciente -->
<div class="grid-2col" style="margin-top: 1.5rem;">
    
    <!-- Ficha Personal del Socio -->
    <div class="card card-primary">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
            <h3 class="card-title" style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Ficha Personal del Vecino
            </h3>
            <span class="badge badge-info" style="font-size: 0.75rem;">Socio #<?php echo $data['socio']->id; ?></span>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; flex-direction: column; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 0.5rem;">
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Nombre Completo</span>
                <span style="font-size: 1.1rem; font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($data['socio']->nombre); ?></span>
            </div>
            
            <div style="display: flex; flex-direction: column; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 0.5rem;">
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">RUT (Identificación)</span>
                <span style="font-size: 1.05rem; font-family: monospace; color: var(--text-color);"><?php echo htmlspecialchars($data['socio']->rut); ?></span>
            </div>

            <div style="display: flex; flex-direction: column; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 0.5rem;">
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Correo Electrónico</span>
                <span style="font-size: 1rem; color: var(--text-color);"><?php echo htmlspecialchars($data['socio']->email); ?></span>
            </div>

            <div style="display: flex; flex-direction: column; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 0.5rem;">
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Teléfono de Contacto</span>
                <span style="font-size: 1rem; color: var(--text-color);"><?php
                    $telDisplay = SocioInput::formatTelefonoDisplay($data['socio']->telefono ?? '');
                    echo $telDisplay !== '' ? htmlspecialchars($telDisplay) : 'No Registrado';
                ?></span>
            </div>

            <?php if (!empty($data['socio']->genero) || !empty($data['socio']->fecha_nacimiento)): ?>
            <div style="display: flex; flex-direction: column; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 0.5rem;">
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Género / Nacimiento</span>
                <span style="font-size: 1rem; color: var(--text-color);">
                    <?php echo htmlspecialchars(SocioInput::generoLabel($data['socio']->genero ?? '') ?: '—'); ?>
                    <?php if (!empty($data['socio']->fecha_nacimiento)): ?>
                        · <?php echo date('d-m-Y', strtotime($data['socio']->fecha_nacimiento)); ?>
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['socio']->estado_civil) || !empty($data['socio']->nacionalidad)): ?>
            <div style="display: flex; flex-direction: column; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 0.5rem;">
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Estado Civil / Nacionalidad</span>
                <span style="font-size: 1rem; color: var(--text-color);">
                    <?php echo htmlspecialchars(SocioInput::estadoCivilLabel($data['socio']->estado_civil ?? '') ?: '—'); ?>
                    <?php if (!empty($data['socio']->nacionalidad)): ?>
                        · <?php echo htmlspecialchars($data['socio']->nacionalidad); ?>
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>

            <div style="display: flex; flex-direction: column; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 0.5rem;">
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Dirección Jurisdicción</span>
                <span style="font-size: 1rem; color: var(--text-color);">
                    <?php 
                    if (!empty($data['socio']->calle_nombre)) {
                        echo htmlspecialchars($data['socio']->calle_nombre) . ' #' . htmlspecialchars($data['socio']->numero_casa);
                    } else {
                        echo 'Dirección No Registrada';
                    }
                    ?>
                </span>
            </div>

            <div style="display: flex; flex-direction: column; padding-bottom: 0.5rem;">
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Junta de Vecinos Afiliada</span>
                <span style="font-size: 1.1rem; font-weight: 700; color: var(--primary);"><?php echo htmlspecialchars($data['socio']->junta_nombre); ?></span>
                <?php if (isset($_SESSION['user_junta_comuna'])): ?>
                    <span style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">Comuna: <?php echo htmlspecialchars($_SESSION['user_junta_comuna']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mis Aportes y Contribuciones Recientes -->
    <div class="card card-success">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
            <h3 class="card-title" style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                Contribuciones y Aportes Recientes
            </h3>
            <a href="<?php echo URLROOT; ?>/socio/comprobantes" class="btn btn-secondary btn-sm" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">Ver Todo el Historial</a>
        </div>

        <?php if (empty($data['transacciones'])): ?>
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px; text-align: center; color: var(--text-muted);">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem; color: rgba(255,255,255,0.1);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <p>No se registran aportes o cuotas en su cuenta aún.</p>
                <p style="font-size: 0.8rem; margin-top: 0.25rem;">Comuníquese con el administrador de su junta para cualquier recaudación.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 280px; overflow-y: auto; padding-right: 0.25rem;">
                <?php 
                $counter = 0;
                foreach ($data['transacciones'] as $t): 
                    if ($counter >= 4) break; 
                    $counter++;
                ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 8px; padding: 0.75rem 1rem; transition: transform 0.2s, background-color 0.2s; cursor: default;" onmouseover="this.style.background='rgba(255,255,255,0.04)'; this.style.transform='translateX(3px)';" onmouseout="this.style.background='rgba(255,255,255,0.02)'; this.style.transform='none';">
                        <div style="display: flex; flex-direction: column; gap: 0.15rem; max-width: 65%;">
                            <span style="font-weight: 700; color: var(--primary); font-size: 0.95rem;">
                                <?php 
                                if ($t->categoria === 'Cuota Socio') {
                                    echo 'Cuota Mensual: ' . htmlspecialchars($t->mes_pagado);
                                } elseif ($t->categoria === 'Cuota Condonada') {
                                    echo 'Cuota Eximida: ' . htmlspecialchars($t->mes_pagado);
                                } else {
                                    echo htmlspecialchars($t->categoria);
                                }
                                ?>
                            </span>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                Fecha: <?php echo date('d-m-Y', strtotime($t->fecha)); ?> | 
                                <?php 
                                if ($t->categoria === 'Cuota Condonada') {
                                    echo 'Motivo: <strong style="color:var(--warning)">' . htmlspecialchars($t->descripcion) . '</strong>';
                                } elseif ($t->categoria === 'Cuota Socio') {
                                    echo 'Recaudador: ' . htmlspecialchars($t->admin_nombre);
                                } else {
                                    echo htmlspecialchars($t->descripcion ?? 'Aporte vecinal');
                                }
                                ?>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <?php if ($t->categoria === 'Cuota Condonada'): ?>
                                <span class="badge badge-warning" style="font-size: 0.7rem; text-transform: uppercase;">Exento</span>
                                <span style="font-weight: 800; color: var(--warning); font-family: var(--font-heading);">$0</span>
                                <a href="<?php echo URLROOT; ?>/socio/comprobante/<?php echo $t->id; ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding: 0.35rem 0.5rem; display: flex; align-items: center; gap: 0.25rem;" title="Ver comprobante">
                                    Comprobante
                                </a>
                            <?php elseif ($t->categoria === 'Cuota Socio'): ?>
                                <span style="font-weight: 800; color: var(--success); font-family: var(--font-heading);">$<?php echo number_format($t->monto, 0, ',', '.'); ?></span>
                                <a href="<?php echo URLROOT; ?>/socio/comprobante/<?php echo $t->id; ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding: 0.35rem 0.5rem; display: flex; align-items: center; gap: 0.25rem;" title="Descargar Recibo">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                    Recibo
                                </a>
                            <?php else: ?>
                                <span class="badge badge-info" style="font-size: 0.7rem; text-transform: uppercase;">Aporte</span>
                                <span style="font-weight: 800; color: var(--primary); font-family: var(--font-heading);">$<?php echo number_format($t->monto, 0, ',', '.'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
