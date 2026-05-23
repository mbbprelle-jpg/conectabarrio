<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 60vh; padding: 1.5rem;">
    <div class="card" style="background: rgba(20, 20, 25, 0.65); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-md); padding: 3rem; max-width: 600px; text-align: center; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4), 0 0 30px rgba(6, 182, 212, 0.05); backdrop-filter: blur(25px); position: relative; overflow: hidden;">
        
        <!-- Efecto de gradiente de fondo sutil -->
        <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%); pointer-events: none;"></div>
        <div style="position: absolute; bottom: -50px; left: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(6,182,212,0.15) 0%, transparent 70%); pointer-events: none;"></div>

        <!-- Icono de Candado Premium -->
        <div style="width: 80px; height: 80px; background: rgba(6, 182, 212, 0.08); border: 1px solid rgba(6, 182, 212, 0.25); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: var(--primary); margin-bottom: 2rem; box-shadow: 0 0 20px rgba(6, 182, 212, 0.15);">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>

        <h2 style="font-family: var(--font-heading); color: var(--text-main); font-size: 1.8rem; margin-bottom: 0.75rem; font-weight: 700;">
            Módulo Bloqueado por Plan
        </h2>
        
        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 2rem;">
            El plan actual de su organización (<strong>Plan <?php echo ucfirst($_SESSION['user_junta_plan'] ?? 'Básico'); ?></strong>) no incluye los permisos para acceder a esta característica.
        </p>

        <!-- Tabla comparativa rápida -->
        <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: var(--radius-sm); padding: 1.5rem; text-align: left; margin-bottom: 2.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <strong style="color: var(--text-main); font-size: 0.88rem; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.25rem;">Beneficios de Planes Superiores:</strong>
            
            <div style="display: flex; gap: 0.5rem; align-items: flex-start; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                <span style="color: var(--success); font-weight: bold; margin-top: 0.1rem;">✓</span>
                <span><strong>Plan Mediano ($7.990/mes)</strong>: Soporta hasta <strong>200 socios activos</strong>, activa el panel completo de <strong>Reuniones y Asistencias</strong>, y habilita el envío de **Boletines Informativos por Email**.</span>
            </div>
            
            <div style="display: flex; gap: 0.5rem; align-items: flex-start; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4; border-top: 1px solid rgba(255,255,255,0.04); padding-top: 0.75rem;">
                <span style="color: var(--primary); font-weight: bold; margin-top: 0.1rem;">✓</span>
                <span><strong>Plan Premium ($9.990/mes)</strong>: Socios activos <strong>ILIMITADOS</strong>, habilita la **Auditoría de Envío a la Municipalidad**, además de soporte prioritario y todas las funciones activas.</span>
            </div>
        </div>

        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 2rem; line-height: 1.5;">
            💡 <strong>¿Cómo actualizar?</strong> Contacte al Administrador Global de la plataforma (Maestro ConectaBarrio) para solicitar el cambio de nivel y pactar la tarifa de suscripción.
        </p>

        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="<?php echo URLROOT; ?>/admin/dashboard" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 14 4 9 9 4"></polyline><path d="M20 20v-7a4 4 0 0 0-4-4H4"></path></svg>
                Volver al Panel
            </a>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
