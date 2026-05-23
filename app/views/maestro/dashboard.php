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
                        <th>Nombre</th>
                        <th>Comuna / Dirección</th>
                        <th>Administrador</th>
                        <th>Total Socios</th>
                        <th>Fecha de Alta</th>
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
                                    else echo 'badge-warning'; // badge-warning or other badge class
                                ?>">
                                    <?php echo htmlspecialchars($tipoOrg); ?>
                                </span>
                            </td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($junta->nombre); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($junta->comuna); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($junta->direccion); ?></div>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <?php echo htmlspecialchars($junta->admin_nombre ?? 'Sin Asignar'); ?>
                                </span>
                            </td>
                            <td style="text-align: center; font-weight: 700;"><?php echo htmlspecialchars($junta->total_socios); ?></td>
                            <td style="color: var(--text-muted);"><?php echo date('d-m-Y', strtotime($junta->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
