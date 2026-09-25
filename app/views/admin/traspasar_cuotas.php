<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/AuthContext.php'; ?>

<?php
function cbTraspasoSocioFullName($s): string {
    return trim(implode(' ', array_filter([
        $s->nombre ?? '',
        $s->apellido_paterno ?? '',
        $s->apellido_materno ?? '',
    ], static fn($p) => trim((string)$p) !== '')));
}

function cbTraspasoSocioLabel($s): string {
    $nombre = cbTraspasoSocioFullName($s);
    $rut = $s->rut ?? '';
    $label = trim($nombre . ($rut !== '' ? ' — ' . $rut : ''));
    if (($s->status ?? '') === 'prevalidar') {
        $label .= ' (Alta provisional)';
    }
    return $label;
}

$origenId = (int)($data['origen_id'] ?? 0);
$cuotas = $data['cuotas_origen'] ?? [];
$socios = $data['socios'] ?? [];

$origenLabel = '';
foreach ($socios as $s) {
    if ((int)$s->id === $origenId) {
        $origenLabel = cbTraspasoSocioLabel($s);
        break;
    }
}

$sociosPickerJson = array_map(static function ($socio) {
    $full = cbTraspasoSocioFullName($socio);
    return [
        'id' => (int)$socio->id,
        'label' => cbTraspasoSocioLabel($socio),
        'search' => mb_strtolower($full . ' ' . ($socio->rut ?? ''), 'UTF-8'),
        'prevalidar' => ($socio->status ?? '') === 'prevalidar',
    ];
}, $socios);
?>

<?php require APPROOT . '/views/partials/maestro_finanzas_banner.php'; ?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<div class="card card-primary" style="margin-bottom:1rem;">
    <h3 style="font-family:var(--font-heading); font-size:1.1rem; margin:0 0 0.5rem;">¿Para qué sirve?</h3>
    <p style="margin:0; font-size:0.88rem; color:var(--text-muted); line-height:1.5;">
        Si registró cuotas a un socio por error, aquí puede <strong>traspasarlas</strong> al socio correcto.
        El pago se <strong>abona a las cuotas que le faltan</strong> al destino (meses pendientes, luego futuros),
        no se conserva el mes original (ej.: sep–nov pueden pasar a abr–jun).
        <strong>No se modifica el monto ni la fecha contable</strong>, así que los cierres mensuales mantienen el mismo total.
    </p>
    <div style="margin-top:0.85rem;">
        <a href="<?php echo URLROOT; ?>/admin/finanzas" class="btn btn-secondary btn-sm">Volver a Movimientos</a>
        <a href="<?php echo URLROOT; ?>/admin/reporte_movimientos" class="btn btn-secondary btn-sm">Ver reporte</a>
    </div>
</div>

<div class="card card-primary">
    <form method="get" action="<?php echo URLROOT; ?>/admin/traspasar_cuotas" id="formOrigenTraspaso" style="margin-bottom:1.25rem;">
        <div class="form-group" style="margin:0; max-width:520px;">
            <label class="form-label" for="origen_input">1. Socio de origen (quien tiene las cuotas mal asignadas)</label>
            <div class="cb-socio-picker" id="picker_origen" data-required="1" data-placeholder="Escriba nombre, apellido o RUT…">
                <input type="hidden" name="origen" id="origen" value="<?php echo $origenId > 0 ? $origenId : ''; ?>">
                <input type="text" class="form-control cb-socio-picker-input" id="origen_input" autocomplete="off"
                       placeholder="Escriba nombre, apellido o RUT…"
                       value="<?php echo htmlspecialchars($origenLabel); ?>">
                <button type="button" class="cb-socio-picker-clear" title="Quitar socio" <?php echo $origenId > 0 ? '' : 'hidden'; ?> aria-label="Quitar socio">&times;</button>
                <ul class="cb-socio-picker-list" hidden></ul>
            </div>
            <small style="color:var(--text-muted); font-size:0.75rem;">Al elegir un socio se cargan automáticamente sus cuotas.</small>
        </div>
        <noscript><button type="submit" class="btn btn-secondary" style="margin-top:0.5rem;">Cargar cuotas</button></noscript>
    </form>

    <?php if ($origenId <= 0): ?>
        <p style="color:var(--text-muted); margin:0;">Busque y seleccione el socio de origen para ver sus cuotas.</p>
    <?php elseif (empty($cuotas)): ?>
        <p style="color:var(--text-muted); margin:0;">Este socio no tiene cuotas registradas para traspasar.</p>
    <?php else: ?>
        <form method="post" action="<?php echo URLROOT; ?>/admin/traspasar_cuotas_aplicar" id="formTraspaso"
              onsubmit="return confirmTraspaso();">
            <input type="hidden" name="origen_id" value="<?php echo $origenId; ?>">

            <div class="form-group">
                <label class="form-label">2. Seleccione las cuotas (meses) a traspasar</label>
                <div style="border:1px solid var(--border-color); border-radius:10px; padding:0.75rem; max-height:320px; overflow:auto;">
                    <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.65rem; font-size:0.85rem;">
                        <input type="checkbox" id="checkAllCuotas"> Seleccionar todas
                    </label>
                    <?php foreach ($cuotas as $c): ?>
                        <label style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding:0.45rem 0.35rem; border-top:1px solid rgba(255,255,255,0.06); font-size:0.85rem;">
                            <span style="display:flex; align-items:center; gap:0.5rem;">
                                <input type="checkbox" name="cuota_ids[]" value="<?php echo (int)$c->id; ?>"
                                       class="cuota-check"
                                       data-mes="<?php echo htmlspecialchars($c->mes_pagado ?? ''); ?>">
                                <strong><?php echo htmlspecialchars($c->mes_pagado ?? '—'); ?></strong>
                                <span style="color:var(--text-muted);"><?php echo htmlspecialchars($c->categoria ?? ''); ?></span>
                            </span>
                            <span style="white-space:nowrap;">
                                <?php if (($c->categoria ?? '') === 'Cuota Condonada'): ?>
                                    <span style="color:var(--warning);">Exento</span>
                                <?php else: ?>
                                    $<?php echo number_format((int)$c->monto, 0, ',', '.'); ?>
                                <?php endif; ?>
                                <span style="color:var(--text-muted); font-size:0.75rem; margin-left:0.4rem;">
                                    (fecha <?php echo !empty($c->fecha) ? date('d-m-Y', strtotime($c->fecha)) : '—'; ?>)
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group" style="max-width:520px;">
                <label class="form-label" for="destino_input">3. Socio de destino (quien debió tener esas cuotas)</label>
                <div class="cb-socio-picker" id="picker_destino" data-required="1" data-placeholder="Escriba nombre, apellido o RUT…">
                    <input type="hidden" name="destino_id" id="destino_id" value="">
                    <input type="text" class="form-control cb-socio-picker-input" id="destino_input" autocomplete="off"
                           placeholder="Escriba nombre, apellido o RUT…">
                    <button type="button" class="cb-socio-picker-clear" title="Quitar socio" hidden aria-label="Quitar socio">&times;</button>
                    <ul class="cb-socio-picker-list" hidden></ul>
                </div>
            </div>

            <div id="previewTraspaso" hidden style="margin:1rem 0; border:1px solid var(--border-color); border-radius:12px; padding:1rem;">
                <h4 style="font-family:var(--font-heading); font-size:1rem; margin:0 0 0.75rem;">Vista previa del abono</h4>
                <p id="previewHint" style="margin:0 0 0.75rem; font-size:0.82rem; color:var(--text-muted); line-height:1.45;"></p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <div style="font-size:0.78rem; font-weight:600; margin-bottom:0.4rem; color:var(--text-muted);">Ya tiene pagado / condonado</div>
                        <div id="previewPagados" style="font-size:0.85rem; line-height:1.55; min-height:2rem;"></div>
                    </div>
                    <div>
                        <div style="font-size:0.78rem; font-weight:600; margin-bottom:0.4rem; color:var(--text-muted);">Meses que se van a abonar</div>
                        <div id="previewAbonar" style="font-size:0.85rem; line-height:1.55; min-height:2rem;"></div>
                    </div>
                </div>
                <div style="margin-top:0.9rem; padding-top:0.75rem; border-top:1px solid var(--border-color);">
                    <div style="font-size:0.78rem; font-weight:600; margin-bottom:0.4rem; color:var(--text-muted);">Mapeo origen → destino</div>
                    <div id="previewMapa" style="font-size:0.85rem; line-height:1.55;"></div>
                </div>
                <p id="previewError" hidden style="margin:0.75rem 0 0; font-size:0.85rem; color:var(--danger);"></p>
            </div>

            <div class="form-group">
                <label class="form-label" for="motivo">Motivo / nota (opcional)</label>
                <input type="text" name="motivo" id="motivo" class="form-control" maxlength="200"
                       placeholder="Ej: Error al registrar: 3 meses correspondían a otro socio">
            </div>

            <button type="submit" class="btn btn-primary" id="btnTraspasar">Traspasar cuotas seleccionadas</button>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ORIGEN_ID = <?php echo (int)$origenId; ?>;
    const URLROOT = <?php echo json_encode(URLROOT); ?>;
    const SOCIOS_DATA = <?php echo json_encode($sociosPickerJson, JSON_UNESCAPED_UNICODE); ?>;
    const SOCIOS_DESTINO = SOCIOS_DATA.filter(function (s) { return s.id !== ORIGEN_ID; });

    const MES_NOMBRES = {
        '01': 'Ene', '02': 'Feb', '03': 'Mar', '04': 'Abr',
        '05': 'May', '06': 'Jun', '07': 'Jul', '08': 'Ago',
        '09': 'Sep', '10': 'Oct', '11': 'Nov', '12': 'Dic'
    };

    function labelMes(ym) {
        if (!ym || ym.length < 7) return ym || '—';
        const y = ym.slice(0, 4);
        const m = ym.slice(5, 7);
        return (MES_NOMBRES[m] || m) + ' ' + y;
    }

    function initSocioPicker(root, dataSource, onSelect) {
        if (!root) return null;
        const hidden = root.querySelector('input[type="hidden"]');
        const input = root.querySelector('.cb-socio-picker-input');
        const list = root.querySelector('.cb-socio-picker-list');
        const clearBtn = root.querySelector('.cb-socio-picker-clear');
        const isRequired = root.dataset.required === '1';
        let activeIdx = -1;
        const source = dataSource || SOCIOS_DATA;

        const renderList = (query) => {
            const q = (query || '').trim().toLowerCase();
            const items = q === ''
                ? source.slice(0, 40)
                : source.filter(s => s.search.includes(q)).slice(0, 40);

            list.innerHTML = '';
            if (items.length === 0) {
                const li = document.createElement('li');
                li.className = 'cb-socio-picker-empty';
                li.textContent = 'Sin coincidencias';
                list.appendChild(li);
            } else {
                items.forEach((s, idx) => {
                    const li = document.createElement('li');
                    li.className = 'cb-socio-picker-item' + (idx === activeIdx ? ' is-active' : '');
                    li.dataset.id = s.id;
                    li.textContent = s.label;
                    li.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        selectSocio(s);
                    });
                    list.appendChild(li);
                });
            }
            list.hidden = false;
        };

        const selectSocio = (s) => {
            hidden.value = s.id;
            input.value = s.label;
            list.hidden = true;
            activeIdx = -1;
            if (clearBtn) clearBtn.hidden = false;
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
            if (typeof onSelect === 'function') onSelect(s);
        };

        const clearSocio = () => {
            hidden.value = '';
            input.value = '';
            list.hidden = true;
            activeIdx = -1;
            if (clearBtn) clearBtn.hidden = true;
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
            if (typeof onSelect === 'function') onSelect(null);
        };

        input.addEventListener('focus', () => renderList(input.value));
        input.addEventListener('input', () => {
            hidden.value = '';
            if (clearBtn) clearBtn.hidden = true;
            activeIdx = -1;
            renderList(input.value);
        });
        input.addEventListener('keydown', (e) => {
            const items = list.querySelectorAll('.cb-socio-picker-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIdx = Math.min(activeIdx + 1, items.length - 1);
                renderList(input.value);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIdx = Math.max(activeIdx - 1, 0);
                renderList(input.value);
            } else if (e.key === 'Enter' && activeIdx >= 0 && items[activeIdx]) {
                e.preventDefault();
                const id = parseInt(items[activeIdx].dataset.id, 10);
                const s = source.find(x => x.id === id);
                if (s) selectSocio(s);
            } else if (e.key === 'Escape') {
                list.hidden = true;
            }
        });
        input.addEventListener('blur', () => {
            setTimeout(() => { list.hidden = true; }, 150);
            if (isRequired && !hidden.value && input.value.trim() !== '') {
                input.value = '';
            }
        });
        if (clearBtn) clearBtn.addEventListener('click', clearSocio);

        return { selectSocio, clear: clearSocio };
    }

    initSocioPicker(document.getElementById('picker_origen'), SOCIOS_DATA, function (s) {
        if (!s) return;
        const form = document.getElementById('formOrigenTraspaso');
        if (form) form.submit();
    });

    initSocioPicker(document.getElementById('picker_destino'), SOCIOS_DESTINO, function () {
        refreshPreview();
    });

    const all = document.getElementById('checkAllCuotas');
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.cuota-check').forEach(function (cb) {
                cb.checked = all.checked;
            });
            refreshPreview();
        });
    }
    document.querySelectorAll('.cuota-check').forEach(function (cb) {
        cb.addEventListener('change', refreshPreview);
    });

    let destinoMesesCache = null;
    let destinoIdCache = null;
    let lastMapaOk = false;

    function selectedCuotas() {
        const checks = Array.from(document.querySelectorAll('.cuota-check:checked'));
        return checks
            .map(function (cb) {
                return { id: parseInt(cb.value, 10), mes: cb.dataset.mes || '' };
            })
            .sort(function (a, b) {
                if (a.mes === b.mes) return a.id - b.id;
                return a.mes < b.mes ? -1 : 1;
            });
    }

    function chip(text, tone) {
        const colors = {
            ok: 'background:rgba(34,197,94,0.15); color:#4ade80;',
            warn: 'background:rgba(234,179,8,0.15); color:#facc15;',
            info: 'background:rgba(59,130,246,0.15); color:#93c5fd;',
            muted: 'background:rgba(148,163,184,0.12); color:var(--text-muted);'
        };
        return '<span style="display:inline-block; margin:0 0.3rem 0.3rem 0; padding:0.15rem 0.5rem; border-radius:6px; font-size:0.78rem; ' +
            (colors[tone] || colors.muted) + '">' + text + '</span>';
    }

    function refreshPreview() {
        const box = document.getElementById('previewTraspaso');
        const destinoEl = document.getElementById('destino_id');
        const destinoId = destinoEl ? parseInt(destinoEl.value || '0', 10) : 0;
        const seleccion = selectedCuotas();
        const btn = document.getElementById('btnTraspasar');

        if (!box) return;
        lastMapaOk = false;

        if (!destinoId || seleccion.length === 0) {
            box.hidden = true;
            if (btn) btn.disabled = false;
            return;
        }

        box.hidden = false;
        document.getElementById('previewHint').textContent = 'Cargando estado de cuotas del socio destino…';
        document.getElementById('previewPagados').innerHTML = '';
        document.getElementById('previewAbonar').innerHTML = '';
        document.getElementById('previewMapa').innerHTML = '';
        const errEl = document.getElementById('previewError');
        errEl.hidden = true;
        errEl.textContent = '';

        const applyMeses = function (meses) {
            const pagados = meses.filter(m => m.estado === 'pagado' || m.estado === 'condonado');
            const pendientes = meses.filter(m => m.estado === 'pendiente');
            const futuros = meses.filter(m => m.estado === 'futuro');
            const abonables = pendientes.concat(futuros);

            const pagadosEl = document.getElementById('previewPagados');
            if (pagados.length === 0) {
                pagadosEl.innerHTML = '<span style="color:var(--text-muted);">Ningún mes registrado aún.</span>';
            } else {
                pagadosEl.innerHTML = pagados.map(function (m) {
                    return chip(labelMes(m.mes) + (m.estado === 'condonado' ? ' (exento)' : ''), m.estado === 'condonado' ? 'warn' : 'ok');
                }).join('');
            }

            document.getElementById('previewHint').textContent =
                'Se abonarán primero los meses pendientes del destino y, si hacen falta, meses futuros. ' +
                'Orden: cronológico según las cuotas que eligió del origen.';

            if (abonables.length < seleccion.length) {
                document.getElementById('previewAbonar').innerHTML =
                    '<span style="color:var(--danger);">Solo hay ' + abonables.length +
                    ' mes(es) disponible(s); necesita ' + seleccion.length + '.</span>';
                document.getElementById('previewMapa').innerHTML = '';
                errEl.hidden = false;
                errEl.textContent = 'No hay suficientes meses pendientes/futuros en el destino para este traspaso.';
                if (btn) btn.disabled = true;
                lastMapaOk = false;
                return;
            }

            const aAbonar = abonables.slice(0, seleccion.length);
            document.getElementById('previewAbonar').innerHTML = aAbonar.map(function (m) {
                const tone = m.estado === 'pendiente' ? 'info' : 'muted';
                const tag = m.estado === 'pendiente' ? 'pendiente' : 'futuro';
                return chip(labelMes(m.mes) + ' · ' + tag, tone);
            }).join('');

            document.getElementById('previewMapa').innerHTML = seleccion.map(function (c, i) {
                return '<div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">' +
                    '<strong>' + labelMes(c.mes) + '</strong>' +
                    '<span style="color:var(--text-muted);">→</span>' +
                    '<strong style="color:#93c5fd;">' + labelMes(aAbonar[i].mes) + '</strong>' +
                    '</div>';
            }).join('');

            if (btn) btn.disabled = false;
            lastMapaOk = true;
        };

        if (destinoIdCache === destinoId && destinoMesesCache) {
            applyMeses(destinoMesesCache);
            return;
        }

        fetch(URLROOT + '/admin/get_socio_cuotas/' + destinoId)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success || !Array.isArray(data.meses)) {
                    errEl.hidden = false;
                    errEl.textContent = data.message || 'No se pudo cargar el estado de cuotas del destino.';
                    if (btn) btn.disabled = true;
                    return;
                }
                destinoIdCache = destinoId;
                destinoMesesCache = data.meses;
                applyMeses(data.meses);
            })
            .catch(function () {
                errEl.hidden = false;
                errEl.textContent = 'Error de red al consultar cuotas del destino.';
                if (btn) btn.disabled = true;
            });
    }

    const destinoHidden = document.getElementById('destino_id');
    if (destinoHidden) {
        destinoHidden.addEventListener('change', function () {
            destinoIdCache = null;
            destinoMesesCache = null;
            refreshPreview();
        });
    }

    window.confirmTraspaso = function () {
        const destino = document.getElementById('destino_id');
        const checks = document.querySelectorAll('.cuota-check:checked');
        if (!destino || !destino.value) {
            alert('Seleccione el socio de destino escribiendo su nombre o RUT.');
            return false;
        }
        if (!checks.length) {
            alert('Seleccione al menos una cuota (mes) a traspasar.');
            return false;
        }
        if (!lastMapaOk) {
            alert('Revise la vista previa: no se puede traspasar con el mapeo actual.');
            return false;
        }
        const mapa = document.getElementById('previewMapa');
        const resumen = mapa ? mapa.innerText.replace(/\s+/g, ' ').trim() : '';
        return confirm(
            '¿Confirma el traspaso?\n\nLas cuotas se abonarán a los meses pendientes del destino:\n' +
            resumen +
            '\n\nMontos y fechas contables no cambian.'
        );
    };
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
