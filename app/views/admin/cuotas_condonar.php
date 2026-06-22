<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
function cbCondonarMiembroLabel($m) {
    $nombre = trim(implode(' ', array_filter([
        $m->nombre ?? '',
        $m->apellido_paterno ?? '',
        $m->apellido_materno ?? '',
    ], static fn($p) => trim((string)$p) !== '')));
    if (!empty($m->rut)) {
        $nombre .= ' — ' . $m->rut;
    }
    if (($m->rol ?? '') === 'admin') {
        $nombre .= ' (Administrador)';
    }
    return $nombre;
}

$rango = $data['rango_fechas'] ?? ['min' => date('Y-m-d'), 'max' => date('Y-m-d')];
$fechaMin = $rango['min'];
$fechaMax = $rango['max'];
$fechaDefault = $fechaMax;
$miembros = $data['miembros'] ?? [];
?>

<?php require APPROOT . '/views/partials/maestro_finanzas_banner.php'; ?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<div class="card card-warning" style="margin-bottom: 1.25rem;">
    <h3 class="card-title" style="margin-bottom: 0.5rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        ¿Cuándo usar exención masiva?
    </h3>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0; line-height: 1.5;">
        Use este módulo cuando deba eximir <strong>varios socios</strong> en <strong>varios meses</strong> a la vez — por ejemplo, si la organización inició actividades en
        <strong><?php echo htmlspecialchars($data['mes_inicio'] ?? ''); ?></strong>
        <?php if (!empty($data['primera_cuota_mes'])): ?>
            pero la cuota mensual solo rige desde <strong><?php echo htmlspecialchars($data['primera_cuota_mes']); ?></strong>.
        <?php else: ?>
            y necesita marcar meses sin cobro.
        <?php endif; ?>
        Para un solo socio, siga usando <a href="<?php echo URLROOT; ?>/admin/finanzas" style="color: var(--primary);">Movimientos → Registrar cuota</a>.
    </p>
</div>

<form id="formCondonarMasivo" action="<?php echo URLROOT; ?>/admin/cuotas_condonar_aplicar" method="POST">
    <div class="grid-2col" style="align-items: start;">
        <div class="card card-primary">
            <h3 class="card-title">Rango de meses a eximir</h3>

            <div class="grid-2col" style="margin-bottom: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="mes_desde" class="form-label">Desde (mes) *</label>
                    <input type="month" name="mes_desde" id="mes_desde" class="form-control" required
                        value="<?php echo htmlspecialchars($data['mes_desde_default'] ?? ''); ?>"
                        min="<?php echo htmlspecialchars($data['mes_inicio'] ?? ''); ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="mes_hasta" class="form-label">Hasta (mes) *</label>
                    <input type="month" name="mes_hasta" id="mes_hasta" class="form-control" required
                        value="<?php echo htmlspecialchars($data['mes_hasta_default'] ?? ''); ?>">
                </div>
            </div>

            <?php if (!empty($data['primera_cuota_mes']) && ($data['mes_hasta_default'] ?? '') !== ($data['mes_inicio'] ?? '')): ?>
            <button type="button" id="btn_preset_prev_cuota" class="btn btn-secondary btn-sm" style="margin-bottom: 1rem;">
                Usar meses anteriores a la primera cuota (<?php echo htmlspecialchars($data['mes_inicio']); ?> → <?php echo htmlspecialchars($data['mes_hasta_default']); ?>)
            </button>
            <?php endif; ?>

            <div class="form-group">
                <label for="fecha_pago" class="form-label">Fecha del registro *</label>
                <input type="date" name="fecha_pago" id="fecha_pago" class="form-control" required
                    value="<?php echo htmlspecialchars($fechaDefault); ?>"
                    min="<?php echo htmlspecialchars($fechaMin); ?>"
                    max="<?php echo htmlspecialchars($fechaMax); ?>">
                <small style="color: var(--text-muted); font-size: 0.72rem;">Fecha contable del movimiento (no el mes eximido).</small>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="justificacion" class="form-label" style="color: var(--warning);">Justificación / Motivo *</label>
                <input type="text" name="justificacion" id="justificacion" class="form-control" required maxlength="500"
                    placeholder="Ej: Sin cuota vigente hasta abril 2026 — periodo de arranque exento por acuerdo directiva">
            </div>
        </div>

        <div class="card card-success">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                <h3 class="card-title" style="margin: 0;">Socios y administradores</h3>
                <div style="display: flex; gap: 0.35rem;">
                    <button type="button" id="btn_sel_todos" class="btn btn-secondary btn-sm">Todos</button>
                    <button type="button" id="btn_sel_ninguno" class="btn btn-secondary btn-sm">Ninguno</button>
                </div>
            </div>

            <div id="miembros_list" style="max-height: 320px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.65rem; background: var(--bg-input);">
                <?php if (empty($miembros)): ?>
                    <p style="color: var(--text-muted); text-align: center; font-size: 0.85rem; margin: 1rem 0;">No hay socios ni administradores activos.</p>
                <?php else: ?>
                    <?php foreach ($miembros as $m): ?>
                        <label class="cb-cond-miembro-row">
                            <input type="checkbox" name="miembros[]" value="<?php echo (int)$m->id; ?>" class="cb-miembro-check" checked>
                            <span><?php echo htmlspecialchars(cbCondonarMiembroLabel($m)); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 0.5rem;">
                <?php echo count($miembros); ?> persona(s) en el padrón de cuotas.
            </small>
        </div>
    </div>

    <div id="preview_box" class="alert alert-info" style="margin-top: 1.25rem; display: none;">
        <strong>Vista previa:</strong> <span id="preview_text">—</span>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center; margin-top: 1.25rem;">
        <a href="<?php echo URLROOT; ?>/admin/finanzas" class="btn btn-secondary">← Volver a Movimientos</a>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button type="button" id="btn_preview" class="btn btn-secondary">Vista previa</button>
            <button type="submit" id="btn_aplicar" class="btn btn-warning" <?php echo empty($miembros) ? 'disabled' : ''; ?>>
                Aplicar exención masiva
            </button>
        </div>
    </div>
</form>

<style>
.cb-cond-miembro-row {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.45rem 0.35rem;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 0.84rem;
    line-height: 1.35;
}
.cb-cond-miembro-row:hover {
    background: rgba(255, 255, 255, 0.04);
}
.cb-cond-miembro-row input {
    margin-top: 0.2rem;
    flex-shrink: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCondonarMasivo');
    const mesDesde = document.getElementById('mes_desde');
    const mesHasta = document.getElementById('mes_hasta');
    const previewBox = document.getElementById('preview_box');
    const previewText = document.getElementById('preview_text');
    const URLROOT_JS = '<?php echo URLROOT; ?>';

    function getSelectedMemberIds() {
        return Array.from(document.querySelectorAll('.cb-miembro-check:checked')).map(cb => cb.value);
    }

    function runPreview() {
        const ids = getSelectedMemberIds();
        if (!ids.length) {
            previewBox.style.display = 'block';
            previewText.textContent = 'Seleccione al menos un socio o administrador.';
            previewBox.className = 'alert alert-warning';
            return;
        }
        const body = new FormData();
        body.append('mes_desde', mesDesde.value);
        body.append('mes_hasta', mesHasta.value);
        ids.forEach(id => body.append('miembros[]', id));

        previewText.textContent = 'Calculando…';
        previewBox.style.display = 'block';
        previewBox.className = 'alert alert-info';

        fetch(URLROOT_JS + '/admin/cuotas_condonar_preview', {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                previewBox.className = 'alert alert-danger';
                previewText.textContent = data.message || 'Error en la vista previa.';
                return;
            }
            const p = data.preview;
            previewBox.className = 'alert alert-info';
            previewText.textContent =
                'Se registrarán ' + p.crear + ' exenciones (' + p.miembros + ' persona(s) × ' + p.meses.length + ' mes(es)). ' +
                (p.omitidos_ya_registrados ? p.omitidos_ya_registrados + ' ya pagadas o exentas. ' : '') +
                (p.omitidos_cerrados ? p.omitidos_cerrados + ' en meses cerrados (omitidas).' : '');
        })
        .catch(() => {
            previewBox.className = 'alert alert-danger';
            previewText.textContent = 'No se pudo obtener la vista previa.';
        });
    }

    document.getElementById('btn_preview')?.addEventListener('click', runPreview);
    mesDesde?.addEventListener('change', () => { previewBox.style.display = 'none'; });
    mesHasta?.addEventListener('change', () => { previewBox.style.display = 'none'; });
    document.querySelectorAll('.cb-miembro-check').forEach(cb => {
        cb.addEventListener('change', () => { previewBox.style.display = 'none'; });
    });

    document.getElementById('btn_sel_todos')?.addEventListener('click', () => {
        document.querySelectorAll('.cb-miembro-check').forEach(cb => { cb.checked = true; });
        previewBox.style.display = 'none';
    });
    document.getElementById('btn_sel_ninguno')?.addEventListener('click', () => {
        document.querySelectorAll('.cb-miembro-check').forEach(cb => { cb.checked = false; });
        previewBox.style.display = 'none';
    });

    document.getElementById('btn_preset_prev_cuota')?.addEventListener('click', () => {
        mesDesde.value = '<?php echo htmlspecialchars($data['mes_inicio'] ?? ''); ?>';
        mesHasta.value = '<?php echo htmlspecialchars($data['mes_hasta_default'] ?? ''); ?>';
        previewBox.style.display = 'none';
    });

    form?.addEventListener('submit', function(e) {
        if (!getSelectedMemberIds().length) {
            e.preventDefault();
            alert('Seleccione al menos un socio o administrador.');
            return;
        }
        if (!document.getElementById('justificacion').value.trim()) {
            e.preventDefault();
            alert('Indique la justificación de la exención.');
            return;
        }
        const crear = previewText.textContent.match(/Se registrarán (\d+)/);
        const count = crear ? parseInt(crear[1], 10) : null;
        const msg = count !== null && count > 0
            ? '¿Confirma registrar ' + count + ' exenciones de cuota?'
            : '¿Confirma aplicar la exención masiva? Los meses ya pagados o cerrados se omitirán.';
        if (!confirm(msg)) {
            e.preventDefault();
        }
    });
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
