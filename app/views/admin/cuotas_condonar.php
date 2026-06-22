<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
$rango = $data['rango_fechas'] ?? ['min' => date('Y-m-d'), 'max' => date('Y-m-d')];
$fechaMin = $rango['min'];
$fechaMax = $rango['max'];
$fechaDefault = $fechaMax;
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
        Exima <strong>varios socios</strong> en <strong>varios meses</strong> a la vez (monto $0).
        El rango de meses no puede ser anterior al <strong>inicio de actividades</strong> de la organización
        (<strong><?php echo htmlspecialchars($data['mes_inicio'] ?? ''); ?></strong>).
        Solo aparecen personas con meses pendientes de eximir en el rango que usted elija.
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
                        min="<?php echo htmlspecialchars($data['mes_inicio'] ?? ''); ?>">
                    <small style="color: var(--text-muted); font-size: 0.72rem;">No anterior a <?php echo htmlspecialchars($data['mes_inicio'] ?? ''); ?>.</small>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="mes_hasta" class="form-label">Hasta (mes) *</label>
                    <input type="month" name="mes_hasta" id="mes_hasta" class="form-control" required
                        min="<?php echo htmlspecialchars($data['mes_inicio'] ?? ''); ?>">
                </div>
            </div>

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
                    placeholder="Motivo de la exención">
            </div>
        </div>

        <div class="card card-success">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                <h3 class="card-title" style="margin: 0;">Pendientes de eximir</h3>
                <div style="display: flex; gap: 0.35rem;">
                    <button type="button" id="btn_sel_todos" class="btn btn-secondary btn-sm">Todos visibles</button>
                    <button type="button" id="btn_sel_ninguno" class="btn btn-secondary btn-sm">Ninguno</button>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 0.75rem;">
                <label for="buscar_miembro" class="form-label">Buscar socio</label>
                <input type="search" id="buscar_miembro" class="form-control" placeholder="Nombre, apellido o RUT…" autocomplete="off">
            </div>

            <div id="miembros_list" style="max-height: 320px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.65rem; background: var(--bg-input);">
                <p style="color: var(--text-muted); text-align: center; font-size: 0.85rem; margin: 1rem 0;">Seleccione el rango de meses (desde y hasta) para ver los socios pendientes.</p>
            </div>
            <small id="miembros_resumen" style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 0.5rem;">
                Inicio de actividades: <?php echo htmlspecialchars($data['mes_inicio'] ?? ''); ?>.
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
            <button type="submit" id="btn_aplicar" class="btn btn-warning" disabled>
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
.cb-cond-miembro-row.is-hidden {
    display: none;
}
.cb-cond-miembro-row input {
    margin-top: 0.2rem;
    flex-shrink: 0;
}
.cb-cond-pend-badge {
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--warning);
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.25);
    padding: 0.1rem 0.4rem;
    border-radius: 4px;
    white-space: nowrap;
    flex-shrink: 0;
}
.cb-cond-miembro-text {
    flex: 1;
    min-width: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCondonarMasivo');
    const mesDesde = document.getElementById('mes_desde');
    const mesHasta = document.getElementById('mes_hasta');
    const miembrosList = document.getElementById('miembros_list');
    const miembrosResumen = document.getElementById('miembros_resumen');
    const buscarInput = document.getElementById('buscar_miembro');
    const previewBox = document.getElementById('preview_box');
    const previewText = document.getElementById('preview_text');
    const btnAplicar = document.getElementById('btn_aplicar');
    const URLROOT_JS = '<?php echo URLROOT; ?>';
    const MES_INICIO_ORG = '<?php echo htmlspecialchars($data['mes_inicio'] ?? ''); ?>';
    let loadTimer = null;

    function showIdleMiembros(message) {
        miembrosList.innerHTML = '<p style="color: var(--text-muted); text-align: center; font-size: 0.85rem; margin: 1rem 0;">'
            + (message || 'Seleccione el rango de meses (desde y hasta) para ver los socios pendientes.') + '</p>';
        btnAplicar.disabled = true;
    }

    function getVisibleRows() {
        return Array.from(miembrosList.querySelectorAll('.cb-cond-miembro-row:not(.is-hidden)'));
    }

    function getSelectedMemberIds() {
        return Array.from(miembrosList.querySelectorAll('.cb-miembro-check:checked')).map(cb => cb.value);
    }

    function updateAplicarState() {
        const hasPending = miembrosList.querySelector('.cb-miembro-check') !== null;
        const hasSelected = getSelectedMemberIds().length > 0;
        btnAplicar.disabled = !hasPending || !hasSelected;
    }

    function applySearchFilter() {
        const q = (buscarInput?.value || '').trim().toLowerCase();
        miembrosList.querySelectorAll('.cb-cond-miembro-row').forEach(row => {
            const key = row.dataset.search || '';
            row.classList.toggle('is-hidden', q !== '' && !key.includes(q));
        });
    }

    function renderMiembros(miembros, meta) {
        if (!miembros.length) {
            miembrosList.innerHTML = '<p style="color: var(--text-muted); text-align: center; font-size: 0.85rem; margin: 1rem 0;">No hay socios con meses pendientes de eximir en este rango.'
                + (meta.ya_resueltos > 0 ? '<br><span style="font-size:0.78rem;">' + meta.ya_resueltos + ' persona(s) ya tienen todos los meses del rango pagados o exentos.</span>' : '')
                + '</p>';
            miembrosResumen.textContent = '0 pendientes en el rango seleccionado.';
            updateAplicarState();
            return;
        }

        miembrosList.innerHTML = miembros.map(m => `
            <label class="cb-cond-miembro-row" data-search="${escapeAttr(m.search)}">
                <input type="checkbox" name="miembros[]" value="${m.id}" class="cb-miembro-check" checked>
                <span class="cb-cond-miembro-text">${escapeHtml(m.label)}</span>
                <span class="cb-cond-pend-badge">${m.pendientes} mes${m.pendientes === 1 ? '' : 'es'}</span>
            </label>
        `).join('');

        miembrosResumen.textContent = meta.total_pendientes + ' pendiente(s) de ' + meta.total_padron
            + ' en el padrón'
            + (meta.ya_resueltos > 0 ? ' · ' + meta.ya_resueltos + ' ya resuelto(s) en este periodo' : '')
            + '.';

        miembrosList.querySelectorAll('.cb-miembro-check').forEach(cb => {
            cb.addEventListener('change', () => {
                previewBox.style.display = 'none';
                updateAplicarState();
            });
        });

        applySearchFilter();
        updateAplicarState();
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function escapeAttr(str) {
        return escapeHtml(str).replace(/'/g, '&#39;');
    }

    function loadMiembros() {
        if (!mesDesde.value || !mesHasta.value) {
            showIdleMiembros();
            return;
        }
        if (mesDesde.value < MES_INICIO_ORG) {
            showIdleMiembros('El mes «desde» no puede ser anterior al inicio de actividades (' + MES_INICIO_ORG + ').');
            miembrosResumen.textContent = 'Inicio de actividades: ' + MES_INICIO_ORG + '.';
            return;
        }
        if (mesDesde.value > mesHasta.value) {
            showIdleMiembros('El mes «desde» no puede ser posterior al mes «hasta».');
            return;
        }

        const body = new FormData();
        body.append('mes_desde', mesDesde.value);
        body.append('mes_hasta', mesHasta.value);

        miembrosList.innerHTML = '<p style="color: var(--text-muted); text-align: center; font-size: 0.85rem; margin: 1rem 0;">Cargando listado…</p>';
        previewBox.style.display = 'none';
        btnAplicar.disabled = true;

        fetch(URLROOT_JS + '/admin/cuotas_condonar_miembros', {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                miembrosList.innerHTML = '<p style="color: var(--danger); text-align: center; font-size: 0.85rem; margin: 1rem 0;">' + escapeHtml(data.message || 'Error al cargar') + '</p>';
                miembrosResumen.textContent = '—';
                return;
            }
            renderMiembros(data.miembros || [], data);
        })
        .catch(() => {
            miembrosList.innerHTML = '<p style="color: var(--danger); text-align: center; font-size: 0.85rem; margin: 1rem 0;">Error de conexión al cargar socios.</p>';
        });
    }

    function scheduleLoadMiembros() {
        clearTimeout(loadTimer);
        previewBox.style.display = 'none';
        if (!mesDesde.value || !mesHasta.value) {
            showIdleMiembros();
            miembrosResumen.textContent = 'Inicio de actividades: ' + MES_INICIO_ORG + '.';
            return;
        }
        loadTimer = setTimeout(loadMiembros, 280);
    }

    function runPreview() {
        const ids = getSelectedMemberIds();
        if (!ids.length) {
            previewBox.style.display = 'block';
            previewText.textContent = 'Seleccione al menos un socio con meses pendientes.';
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
            previewBox.className = p.crear > 0 ? 'alert alert-info' : 'alert alert-warning';
            previewText.textContent = p.crear > 0
                ? ('Se registrarán ' + p.crear + ' exenciones (' + p.miembros + ' persona(s) × ' + p.meses.length + ' mes(es) en el rango).'
                    + (p.omitidos_ya_registrados ? ' ' + p.omitidos_ya_registrados + ' ya pagadas o exentas se omiten.' : ''))
                : 'Ninguna exención nueva en la selección actual.';
        })
        .catch(() => {
            previewBox.className = 'alert alert-danger';
            previewText.textContent = 'No se pudo obtener la vista previa.';
        });
    }

    mesDesde?.addEventListener('change', scheduleLoadMiembros);
    mesHasta?.addEventListener('change', scheduleLoadMiembros);
    buscarInput?.addEventListener('input', applySearchFilter);

    document.getElementById('btn_preview')?.addEventListener('click', runPreview);

    document.getElementById('btn_sel_todos')?.addEventListener('click', () => {
        getVisibleRows().forEach(row => {
            const cb = row.querySelector('.cb-miembro-check');
            if (cb) cb.checked = true;
        });
        previewBox.style.display = 'none';
        updateAplicarState();
    });
    document.getElementById('btn_sel_ninguno')?.addEventListener('click', () => {
        miembrosList.querySelectorAll('.cb-miembro-check').forEach(cb => { cb.checked = false; });
        previewBox.style.display = 'none';
        updateAplicarState();
    });

    form?.addEventListener('submit', function(e) {
        if (!getSelectedMemberIds().length) {
            e.preventDefault();
            alert('No hay socios seleccionados con meses pendientes en este rango.');
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
            : '¿Confirma aplicar la exención masiva para los socios seleccionados?';
        if (!confirm(msg)) {
            e.preventDefault();
        }
    });
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
