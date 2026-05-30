<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<!-- Mensajes Flash -->
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

<!-- Grid de Métricas -->
<div class="metrics-grid">
    
    <div class="card metric-card card-primary">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Organizaciones</span>
            <span class="metric-value"><?php echo htmlspecialchars($data['stats']['total_juntas']); ?></span>
            <div style="font-size: 0.72rem; color: rgba(255,255,255,0.7); display: flex; gap: 0.6rem; margin-top: 0.35rem; font-weight: 500; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.35rem;">
                <span>JV: <strong><?php echo $data['stats']['juntas_de_vecinos']; ?></strong></span>
                <span>Comité: <strong><?php echo $data['stats']['comites']; ?></strong></span>
                <span>Org: <strong><?php echo $data['stats']['organizaciones']; ?></strong></span>
            </div>
        </div>
    </div>

    <div class="card metric-card card-success">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Socios Consolidados</span>
            <span class="metric-value"><?php echo htmlspecialchars($data['stats']['total_socios']); ?></span>
        </div>
    </div>

    <div class="card metric-card card-warning">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Administradores Activos</span>
            <span class="metric-value"><?php echo htmlspecialchars($data['stats']['total_admins']); ?></span>
        </div>
    </div>

</div>

<!-- Listado de Juntas de Vecinos -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <h3 class="card-title" style="margin-bottom: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
            Organizaciones Registradas
        </h3>
        
        <a href="<?php echo URLROOT; ?>/maestro/crear_junta" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Nueva Organización
        </a>
    </div>

    <?php if (empty($data['juntas'])): ?>
        <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No hay organizaciones registradas actualmente.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>RUT</th>
                        <th>Tipo</th>
                        <th>Nombre / Dirección</th>
                        <th style="text-align: center;">Plan Comercial</th>
                        <th style="text-align: right;">Precio Anual</th>
                        <th>Administrador</th>
                        <th style="text-align: center;">Total Socios</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['juntas'] as $junta): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--primary);"><?php echo htmlspecialchars($junta->rut_junta); ?></td>
                            <td>
                                <span class="badge <?php 
                                    $tipoOrg = $junta->tipo ?? 'Junta de Vecinos';
                                    if ($tipoOrg === 'Junta de Vecinos') echo 'badge-info';
                                    elseif ($tipoOrg === 'Comité') echo 'badge-success';
                                    else echo 'badge-warning';
                                ?> shadow-sm">
                                    <?php echo htmlspecialchars($tipoOrg); ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--text-main); font-size: 0.9rem;"><?php echo htmlspecialchars($junta->nombre); ?></strong>
                                <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 0.15rem;">
                                    <?php echo htmlspecialchars($junta->comuna); ?> | <?php echo htmlspecialchars($junta->direccion); ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <?php 
                                $planVal = $junta->plan ?? 'basico';
                                if ($planVal === 'basico'): 
                                ?>
                                    <span class="badge badge-secondary" style="background: rgba(156, 163, 175, 0.15); border: 1px solid rgba(156, 163, 175, 0.3); color: #9ca3af;">Básico</span>
                                <?php elseif ($planVal === 'mediano'): ?>
                                    <span class="badge badge-success" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--success);">Mediano</span>
                                <?php elseif ($planVal === 'premium'): ?>
                                    <span class="badge badge-info" style="background: rgba(6, 182, 212, 0.15); border: 1px solid rgba(6, 182, 212, 0.3); color: var(--primary);">Premium</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--text-main);">
                                $<?php echo number_format($junta->precio_anual ?? 0, 0, ',', '.'); ?>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <?php echo htmlspecialchars($junta->admin_nombre ?? 'Sin Asignar'); ?>
                                </span>
                            </td>
                            <td style="text-align: center; font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($junta->total_socios); ?></td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 0.35rem; justify-content: center; flex-wrap: wrap;">
                                    <button class="btn btn-success btn-sm btn-registrar-pago"
                                            data-id="<?php echo $junta->id; ?>"
                                            data-nombre="<?php echo htmlspecialchars($junta->nombre); ?>"
                                            style="font-size: 0.72rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2" ry="2"></rect><line x1="3" y1="11" x2="21" y2="11"></line></svg>
                                        Registrar Pago
                                    </button>
                                    <button class="btn btn-warning btn-sm btn-cambiar-plan" 
                                            data-id="<?php echo $junta->id; ?>" 
                                            data-nombre="<?php echo htmlspecialchars($junta->nombre); ?>" 
                                            data-plan="<?php echo htmlspecialchars($junta->plan ?? 'basico'); ?>" 
                                            data-precio="<?php echo htmlspecialchars($junta->precio_anual ?? 0); ?>" 
                                            style="font-size: 0.72rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        Configurar Plan
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Registrar Pago de Suscripción -->
<div id="pagoModal" class="glass-modal-overlay">
    <div class="glass-modal-container" style="max-width: 620px;">
        <button id="closePagoModalBtn" type="button" style="position: absolute; top: 1.25rem; right: 1.25rem; background: none; border: none; color: var(--text-muted); cursor: pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>

        <div style="text-align: center; margin-bottom: 1.5rem;">
            <h3 style="font-family: var(--font-heading); color: var(--text-main); font-size: 1.5rem; margin-bottom: 0.25rem;">Registrar Pago de Suscripción</h3>
            <p id="pago_modal_org_nombre" style="color: var(--primary); font-weight: bold; font-size: 0.95rem; margin: 0;">Organización</p>
            <p id="pago_modal_plan_info" style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.35rem;"></p>
        </div>

        <form id="formRegistrarPago" action="<?php echo URLROOT; ?>/maestro/registrar_pago_org" method="POST">
            <input type="hidden" name="org_id" id="pago_modal_org_id">

            <div class="form-group">
                <label for="fecha_pago" class="form-label">Fecha de Pago *</label>
                <input type="date" name="fecha_pago" id="fecha_pago" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label class="form-label" style="margin-bottom: 0;">Meses a Pagar *</label>
                    <div id="pago_quick_actions" style="display: none; gap: 0.4rem;">
                        <button type="button" id="btn_select_pendientes_pago" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.72rem;">✓ Todos los Pendientes</button>
                        <button type="button" id="btn_clear_pago_selection" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.72rem;">✕ Limpiar</button>
                    </div>
                </div>
                <div id="pago_meses_container" style="max-height: 280px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.75rem; background: var(--bg-input);">
                    <p style="color: var(--text-muted); text-align: center; font-size: 0.85rem; margin: 1rem 0;">Cargando meses...</p>
                </div>
            </div>

            <div id="pago_monto_alert" class="alert alert-success" style="padding: 0.8rem; font-size: 0.8rem; margin-bottom: 1.25rem; display: none;">
                Monto a registrar: <strong id="pago_monto_visual">$0</strong>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button id="cancelPagoBtn" type="button" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-success">Registrar Pago</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Edición de Plan y Precio (Glassmorphic) -->
<div id="planModal" class="glass-modal-overlay">
    <div class="glass-modal-container">
        <!-- Botón cerrar modal -->
        <button id="closePlanModalBtn" type="button" style="position: absolute; top: 1.25rem; right: 1.25rem; background: none; border: none; color: var(--text-muted); cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='var(--text-main)'" onmouseout="this.style.color='var(--text-muted)'">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>

        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 56px; height: 56px; background: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: var(--warning); margin-bottom: 1rem; box-shadow: 0 0 15px rgba(245, 158, 11, 0.15);">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
            </div>
            <h3 style="font-family: var(--font-heading); color: var(--text-main); font-size: 1.5rem; margin-bottom: 0.25rem;">Configurar Plan Comercial</h3>
            <p id="modal_junta_nombre" style="color: var(--primary); font-weight: bold; font-size: 0.95rem; margin: 0;">Organización</p>
        </div>

        <form id="formActualizarPlan" action="<?php echo URLROOT; ?>/maestro/actualizar_plan" method="POST">
            <input type="hidden" name="junta_id" id="modal_junta_id">

            <div class="form-group">
                <label for="modal_plan" class="form-label" style="font-weight: bold;">Plan Comercial *</label>
                <select name="plan" id="modal_plan" class="form-control" style="background: var(--bg-input);" required>
                    <option value="basico">Plan Básico - $4.990/mes (Oferta) - Máx 50 socios</option>
                    <option value="mediano">Plan Mediano - $7.990/mes (Oferta) - Máx 200 socios + Reuniones</option>
                    <option value="premium">Plan Premium - $9.990/mes (Oferta) - Ilimitado + Envíos</option>
                </select>
            </div>

            <div class="form-group">
                <label for="modal_precio_anual" class="form-label" style="font-weight: bold;">Precio Anual Fijo ($) *</label>
                <input type="number" name="precio_anual" id="modal_precio_anual" class="form-control" style="background: var(--bg-input);" min="0" required>
                <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 0.25rem;">
                    Se autocalcula al cambiar el plan ($Monto/mes * 12), pero puedes adaptarlo como precio especial de alta.
                </small>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button id="cancelPlanBtn" type="button" class="btn btn-secondary" style="padding: 0.65rem 1.25rem; font-size: 0.88rem;">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.5rem; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.glass-modal-overlay {
    display: none; 
    position: fixed; 
    top: 0; 
    left: 0; 
    width: 100%; 
    height: 100%; 
    background: rgba(10, 10, 12, 0.65); 
    backdrop-filter: blur(15px); 
    z-index: 9999; 
    align-items: center; 
    justify-content: center; 
    opacity: 0; 
    transition: opacity 0.3s ease;
}
.glass-modal-container {
    background: rgba(20, 20, 25, 0.75); 
    border: 1px solid rgba(255, 255, 255, 0.08); 
    border-radius: var(--radius-md); 
    padding: 2.5rem; 
    width: 90%; 
    max-width: 500px; 
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 40px rgba(6, 182, 212, 0.1); 
    backdrop-filter: blur(25px); 
    transform: scale(0.9); 
    transition: transform 0.3s ease; 
    position: relative;
}
.mes-group-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-muted);
    margin: 0.75rem 0 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.mes-grid {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}
.mes-card-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.55rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    background: rgba(255,255,255,0.02);
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}
.mes-card-label:hover:not(.disabled) {
    border-color: var(--primary);
    background: rgba(6, 182, 212, 0.05);
}
.mes-card-label.disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
.mes-card-left {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.mes-card-name {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-main);
}
.mes-card-badge {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.15rem 0.45rem;
    border-radius: 4px;
}
.badge-pendiente { color: var(--danger); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); }
.badge-futuro { color: var(--primary); background: rgba(6, 182, 212, 0.1); border: 1px solid rgba(6, 182, 212, 0.2); }
.badge-pagado { color: var(--success); background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('planModal');
    const pagoModal = document.getElementById('pagoModal');
    const closeBtn = document.getElementById('closePlanModalBtn');
    const cancelBtn = document.getElementById('cancelPlanBtn');
    const closePagoBtn = document.getElementById('closePagoModalBtn');
    const cancelPagoBtn = document.getElementById('cancelPagoBtn');
    const form = document.getElementById('formActualizarPlan');
    const formPago = document.getElementById('formRegistrarPago');

    const modalJuntaId = document.getElementById('modal_junta_id');
    const modalJuntaNombre = document.getElementById('modal_junta_nombre');
    const modalPlan = document.getElementById('modal_plan');
    const modalPrecioAnual = document.getElementById('modal_precio_anual');

    const pagoOrgId = document.getElementById('pago_modal_org_id');
    const pagoOrgNombre = document.getElementById('pago_modal_org_nombre');
    const pagoPlanInfo = document.getElementById('pago_modal_plan_info');
    const pagoMesesContainer = document.getElementById('pago_meses_container');
    const pagoQuickActions = document.getElementById('pago_quick_actions');
    const pagoMontoAlert = document.getElementById('pago_monto_alert');
    const pagoMontoVisual = document.getElementById('pago_monto_visual');

    function openModal(targetModal) {
        targetModal.style.display = 'flex';
        setTimeout(() => {
            targetModal.style.opacity = '1';
            targetModal.querySelector('.glass-modal-container').style.transform = 'scale(1)';
        }, 10);
    }

    function closeModal(targetModal) {
        targetModal.style.opacity = '0';
        targetModal.querySelector('.glass-modal-container').style.transform = 'scale(0.9)';
        setTimeout(() => {
            targetModal.style.display = 'none';
        }, 300);
    }

    function formatMesLabel(mes) {
        const parts = mes.split('-');
        const dateObj = new Date(parts[0], parts[1] - 1, 1);
        const label = dateObj.toLocaleDateString('es-CL', { month: 'long', year: 'numeric' });
        return label.charAt(0).toUpperCase() + label.slice(1);
    }

    function renderMesCard(item) {
        const label = document.createElement('label');
        label.className = 'mes-card-label';

        const leftDiv = document.createElement('div');
        leftDiv.className = 'mes-card-left';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'mes_pagado[]';
        checkbox.value = item.mes;
        checkbox.className = 'mes-checkbox-pago';
        checkbox.dataset.monto = item.monto;
        checkbox.dataset.estado = item.estado;

        const nameSpan = document.createElement('span');
        nameSpan.className = 'mes-card-name';
        nameSpan.textContent = formatMesLabel(item.mes);

        const badgeSpan = document.createElement('span');
        badgeSpan.className = `mes-card-badge badge-${item.estado}`;

        leftDiv.appendChild(checkbox);
        leftDiv.appendChild(nameSpan);
        label.appendChild(leftDiv);
        label.appendChild(badgeSpan);

        if (item.estado === 'pagado') {
            checkbox.disabled = true;
            label.classList.add('disabled');
            badgeSpan.textContent = `PAGADO: $${item.monto.toLocaleString('es-CL')}`;
        } else if (item.estado === 'pendiente') {
            badgeSpan.textContent = `PENDIENTE: $${item.monto.toLocaleString('es-CL')}`;
            checkbox.addEventListener('change', updatePagoMontoVisual);
        } else {
            badgeSpan.textContent = `FUTURO: $${item.monto.toLocaleString('es-CL')}`;
            checkbox.addEventListener('change', updatePagoMontoVisual);
        }

        return label;
    }

    function updatePagoMontoVisual() {
        const selected = pagoMesesContainer.querySelectorAll('.mes-checkbox-pago:checked');
        let total = 0;
        selected.forEach(cb => { total += parseInt(cb.dataset.monto, 10) || 0; });
        if (selected.length > 0) {
            pagoMontoAlert.style.display = 'block';
            pagoMontoVisual.textContent = '$' + total.toLocaleString('es-CL');
        } else {
            pagoMontoAlert.style.display = 'none';
        }
    }

    function loadOrgPagos(orgId) {
        pagoMesesContainer.innerHTML = '<p style="color: var(--text-muted); text-align: center; font-size: 0.85rem; margin: 1rem 0;">Cargando meses...</p>';
        pagoQuickActions.style.display = 'none';
        pagoMontoAlert.style.display = 'none';

        fetch('<?php echo URLROOT; ?>/maestro/get_org_pagos/' + orgId, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(async r => {
                const raw = await r.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    throw new Error('Respuesta inválida del servidor (HTTP ' + r.status + ')');
                }
                if (!r.ok || !data.success) {
                    throw new Error(data.message || ('Error HTTP ' + r.status));
                }
                return data;
            })
            .then(data => {

                pagoPlanInfo.textContent = 'Plan ' + data.org.plan + ' · $' + data.org.monto_mensual.toLocaleString('es-CL') + ' / mes · Suscripción desde ' + data.org.mes_inicio_suscripcion;
                pagoMesesContainer.innerHTML = '';

                const pendingGrid = document.createElement('div');
                pendingGrid.className = 'mes-grid';
                const futureGrid = document.createElement('div');
                futureGrid.className = 'mes-grid';
                const resolvedGrid = document.createElement('div');
                resolvedGrid.className = 'mes-grid';

                let hasPending = false, hasFuture = false, hasResolved = false;

                data.meses.forEach(item => {
                    const card = renderMesCard(item);
                    if (item.estado === 'pagado') { resolvedGrid.appendChild(card); hasResolved = true; }
                    else if (item.estado === 'pendiente') { pendingGrid.appendChild(card); hasPending = true; }
                    else { futureGrid.appendChild(card); hasFuture = true; }
                });

                if (hasPending) {
                    const t = document.createElement('div'); t.className = 'mes-group-title'; t.textContent = '⚠️ Atrasados / Pendientes';
                    pagoMesesContainer.appendChild(t); pagoMesesContainer.appendChild(pendingGrid);
                }
                if (hasFuture) {
                    const t = document.createElement('div'); t.className = 'mes-group-title'; t.textContent = '🔮 Meses Futuros (adelantar pago)';
                    pagoMesesContainer.appendChild(t); pagoMesesContainer.appendChild(futureGrid);
                }
                if (hasResolved) {
                    const t = document.createElement('div'); t.className = 'mes-group-title'; t.textContent = '✅ Pagados';
                    pagoMesesContainer.appendChild(t); pagoMesesContainer.appendChild(resolvedGrid);
                }
                if (!hasPending && !hasFuture && !hasResolved) {
                    pagoMesesContainer.innerHTML = '<p style="color: var(--text-muted); text-align: center; font-size: 0.85rem; margin: 1rem 0;">Sin períodos disponibles.</p>';
                } else if (hasPending || hasFuture) {
                    pagoQuickActions.style.display = 'flex';
                }
            })
            .catch(err => {
                pagoMesesContainer.innerHTML = '<p style="color: var(--danger); text-align: center; font-size: 0.85rem; margin: 1rem 0;">' + (err.message || 'Error de conexión') + '</p>';
            });
    }

    document.querySelectorAll('.btn-cambiar-plan').forEach(btn => {
        btn.addEventListener('click', function() {
            modalJuntaId.value = this.getAttribute('data-id');
            modalJuntaNombre.textContent = this.getAttribute('data-nombre');
            modalPlan.value = this.getAttribute('data-plan');
            modalPrecioAnual.value = this.getAttribute('data-precio');
            openModal(modal);
        });
    });

    document.querySelectorAll('.btn-registrar-pago').forEach(btn => {
        btn.addEventListener('click', function() {
            const orgId = this.getAttribute('data-id');
            pagoOrgId.value = orgId;
            pagoOrgNombre.textContent = this.getAttribute('data-nombre');
            loadOrgPagos(orgId);
            openModal(pagoModal);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', () => closeModal(modal));
    if (cancelBtn) cancelBtn.addEventListener('click', () => closeModal(modal));
    if (closePagoBtn) closePagoBtn.addEventListener('click', () => closeModal(pagoModal));
    if (cancelPagoBtn) cancelPagoBtn.addEventListener('click', () => closeModal(pagoModal));

    [modal, pagoModal].forEach(m => {
        if (m) m.addEventListener('click', e => { if (e.target === m) closeModal(m); });
    });

    document.getElementById('btn_select_pendientes_pago')?.addEventListener('click', () => {
        pagoMesesContainer.querySelectorAll('.mes-checkbox-pago[data-estado="pendiente"]').forEach(cb => { cb.checked = true; });
        updatePagoMontoVisual();
    });
    document.getElementById('btn_clear_pago_selection')?.addEventListener('click', () => {
        pagoMesesContainer.querySelectorAll('.mes-checkbox-pago:checked').forEach(cb => { cb.checked = false; });
        updatePagoMontoVisual();
    });

    if (formPago) {
        formPago.addEventListener('submit', e => {
            if (!pagoMesesContainer.querySelector('.mes-checkbox-pago:checked')) {
                e.preventDefault();
                alert('Debe seleccionar al menos un mes para registrar el pago.');
            }
        });
    }

    if (modalPlan && modalPrecioAnual) {
        modalPlan.addEventListener('change', function() {
            const val = this.value;
            if (val === 'basico') modalPrecioAnual.value = 59880;
            else if (val === 'mediano') modalPrecioAnual.value = 95880;
            else if (val === 'premium') modalPrecioAnual.value = 119880;
        });
    }
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
