<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$nombreSocio = static function ($row): string {
    return trim(implode(' ', array_filter([
        $row->nombre ?? '',
        $row->apellido_paterno ?? '',
        $row->apellido_materno ?? '',
    ], static fn($p) => trim((string)$p) !== '')));
};
$cuando = static function ($raw): string {
    if (empty($raw)) {
        return 'Nunca';
    }
    $ts = strtotime((string)$raw);
    return $ts ? date('d-m-Y H:i', $ts) : 'Nunca';
};
?>

<div class="metrics-grid">
    <div class="card metric-card card-success">
        <div class="metric-info">
            <span class="metric-label">Han ingresado</span>
            <span class="metric-value"><?php echo (int)($data['usaron'] ?? 0); ?></span>
        </div>
    </div>
    <div class="card metric-card card-warning">
        <div class="metric-info">
            <span class="metric-label">Nunca han entrado</span>
            <span class="metric-value"><?php echo (int)($data['nunca'] ?? 0); ?></span>
        </div>
    </div>
    <div class="card metric-card card-primary">
        <div class="metric-info">
            <span class="metric-label">Socios activos</span>
            <span class="metric-value"><?php echo count($data['socios'] ?? []); ?></span>
        </div>
    </div>
</div>

<div class="grid-2col" style="align-items: start;">
    <div class="card card-primary">
        <h3 class="card-title">Uso por socio</h3>
        <p style="margin: -0.5rem 0 1rem; font-size: 0.82rem; color: var(--text-muted);">El registro parte desde ahora. Los ingresos anteriores a este cambio no aparecen.</p>
        <?php if (empty($data['socios'])): ?>
            <p style="color: var(--text-muted);">No hay socios activos.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Socio</th>
                        <th>Último ingreso</th>
                        <th>Ingresos</th>
                        <th>Última sección</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($data['socios'] as $s): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($nombreSocio($s)); ?></strong>
                            <?php if (!empty($s->id_socio)): ?>
                                <span style="color: var(--primary); font-size: 0.75rem;"> #<?php echo (int)$s->id_socio; ?></span>
                            <?php endif; ?>
                            <?php if (($s->status ?? '') === 'prevalidar'): ?>
                                <span class="badge badge-warning" style="font-size: 0.65rem;">Alta provisional</span>
                            <?php endif; ?>
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;"><?php echo htmlspecialchars($s->rut ?? ''); ?></div>
                        </td>
                        <td><?php echo $cuando($s->ultimo_ingreso ?? null); ?></td>
                        <td><?php echo (int)$s->ingresos; ?></td>
                        <td><?php echo $s->ultima_seccion !== '' ? htmlspecialchars($s->ultima_seccion) : '—'; ?></td>
                        <td><?php echo (int)$s->visitas; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="card card-success">
        <h3 class="card-title">Qué revisan</h3>
        <?php if (empty($data['secciones'])): ?>
            <p style="color: var(--text-muted);">Todavía no hay visitas a secciones.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Sección</th><th>Socios</th><th>Visitas</th></tr>
                </thead>
                <tbody>
                <?php foreach ($data['secciones'] as $sec): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sec->seccion); ?></td>
                        <td><?php echo (int)$sec->socios; ?></td>
                        <td><?php echo (int)$sec->visitas; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-primary">
    <h3 class="card-title">Últimos movimientos</h3>
    <?php if (empty($data['recientes'])): ?>
        <p style="color: var(--text-muted);">Aún no hay conexiones registradas.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>Fecha</th><th>Socio</th><th>Qué hizo</th></tr>
            </thead>
            <tbody>
            <?php foreach ($data['recientes'] as $r): ?>
                <tr>
                    <td><?php echo $cuando($r->created_at ?? null); ?></td>
                    <td>
                        <?php echo htmlspecialchars($nombreSocio($r)); ?>
                        <?php if (!empty($r->id_socio)): ?><span style="color: var(--text-muted);"> #<?php echo (int)$r->id_socio; ?></span><?php endif; ?>
                    </td>
                    <td>
                        <?php if (($r->tipo ?? '') === 'ingreso'): ?>
                            <span class="badge badge-success">Ingresó</span>
                        <?php else: ?>
                            <span class="badge badge-info"><?php echo htmlspecialchars($r->seccion); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
