<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<!-- Mensajes Flash -->
<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span><?php echo htmlspecialchars($data['success']); ?></span>
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
                                <button class="btn btn-warning btn-sm btn-cambiar-plan" 
                                        data-id="<?php echo $junta->id; ?>" 
                                        data-nombre="<?php echo htmlspecialchars($junta->nombre); ?>" 
                                        data-plan="<?php echo htmlspecialchars($junta->plan ?? 'basico'); ?>" 
                                        data-precio="<?php echo htmlspecialchars($junta->precio_anual ?? 0); ?>" 
                                        style="font-size: 0.72rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    Configurar Plan
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('planModal');
    const closeBtn = document.getElementById('closePlanModalBtn');
    const cancelBtn = document.getElementById('cancelPlanBtn');
    const form = document.getElementById('formActualizarPlan');

    const modalJuntaId = document.getElementById('modal_junta_id');
    const modalJuntaNombre = document.getElementById('modal_junta_nombre');
    const modalPlan = document.getElementById('modal_plan');
    const modalPrecioAnual = document.getElementById('modal_precio_anual');

    function openModal() {
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.style.opacity = '1';
            modal.querySelector('.glass-modal-container').style.transform = 'scale(1)';
        }, 10);
    }

    function closeModal() {
        modal.style.opacity = '0';
        modal.querySelector('.glass-modal-container').style.transform = 'scale(0.9)';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    // Escuchador para botones de cambio de plan
    document.querySelectorAll('.btn-cambiar-plan').forEach(btn => {
        btn.addEventListener('click', function() {
            modalJuntaId.value = this.getAttribute('data-id');
            modalJuntaNombre.textContent = this.getAttribute('data-nombre');
            modalPlan.value = this.getAttribute('data-plan');
            modalPrecioAnual.value = this.getAttribute('data-precio');
            openModal();
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Autocalcular precio anual en el modal reactivamente
    if (modalPlan && modalPrecioAnual) {
        modalPlan.addEventListener('change', function() {
            const val = this.value;
            if (val === 'basico') {
                modalPrecioAnual.value = 59880; // $4.990 * 12
            } else if (val === 'mediano') {
                modalPrecioAnual.value = 95880; // $7.990 * 12
            } else if (val === 'premium') {
                modalPrecioAnual.value = 119880; // $9.990 * 12
            }
        });
    }
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
