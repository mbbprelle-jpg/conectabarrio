<?php if (!empty($data['maestro_mode'])): ?>
<div class="alert alert-info" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.25rem;">
    <div style="display:flex;align-items:center;gap:0.65rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
        <span>
            Modo Maestro — gestionando finanzas de
            <strong><?php echo htmlspecialchars($data['maestro_junta_nombre'] ?? 'Organización'); ?></strong>
        </span>
    </div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <a href="<?php echo URLROOT; ?>/admin/finanzas" class="btn btn-secondary btn-sm">Movimientos</a>
        <a href="<?php echo URLROOT; ?>/admin/cuotas_condonar" class="btn btn-secondary btn-sm">Exención masiva</a>
        <a href="<?php echo URLROOT; ?>/admin/flujo_caja" class="btn btn-secondary btn-sm">Flujo de caja</a>
        <a href="<?php echo URLROOT; ?>/admin/conceptos_caja" class="btn btn-secondary btn-sm">Conceptos</a>
        <a href="<?php echo URLROOT; ?>/maestro/finanzas_salir" class="btn btn-primary btn-sm">Volver al portal maestro</a>
    </div>
</div>
<?php endif; ?>
