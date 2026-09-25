<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$link = $data['link'] ?? null;
$old = $data['old'] ?? [];
$usesCalles = !empty($data['uses_calles']);
$calles = $data['calles'] ?? [];
$hoy = date('Y-m-d');
$maxParto = '2026-12-31';

$calleSeleccionadaId = (int)($old['calle_id'] ?? 0);
$calleSeleccionadaLabel = '';
foreach ($calles as $c) {
    if ((int)$c->id === $calleSeleccionadaId) {
        $calleSeleccionadaLabel = (string)$c->nombre;
        break;
    }
}
$callesPickerJson = array_map(static function ($c) {
    return [
        'id' => (int)$c->id,
        'label' => (string)$c->nombre,
        'search' => mb_strtolower((string)$c->nombre, 'UTF-8'),
    ];
}, $calles);
?>
<style>
.censo-wrap { max-width: 720px; margin: 0 auto; padding: 1.25rem 1rem 3rem; }
.censo-card { background: var(--bg-card, #111827); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.25rem 1.35rem; }
.censo-grid-row { display: grid; grid-template-columns: 1.1fr 1.4fr 1fr 0.7fr auto; gap: 0.45rem; margin-bottom: 0.45rem; align-items: end; }
@media (max-width: 720px) {
  .censo-grid-row { grid-template-columns: 1fr; }
}
.censo-section { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px dashed rgba(255,255,255,0.12); }
.censo-toggle-box { display: none; margin-top: 0.75rem; }
.censo-toggle-box.open { display: block; }
.cb-rut-chile.is-invalid,
.cb-edad-censo.is-invalid { border-color: var(--danger, #ef4444) !important; }
.cb-field-hint { display: block; margin-top: 0.25rem; font-size: 0.72rem; color: var(--text-muted); }
.cb-field-hint.is-error { color: var(--danger, #ef4444); }

.censo-intro-overlay {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 10050;
  background: rgba(8, 10, 14, 0.78);
  backdrop-filter: blur(10px);
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.censo-intro-overlay.is-open { display: flex; }
.censo-intro-box {
  width: 100%;
  max-width: 520px;
  max-height: min(90vh, 720px);
  overflow: auto;
  background: linear-gradient(165deg, rgba(28, 32, 40, 0.98) 0%, rgba(16, 18, 24, 0.99) 100%);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  padding: 1.6rem 1.5rem 1.35rem;
  box-shadow: 0 28px 56px rgba(0, 0, 0, 0.5);
  animation: censoIntroIn 0.28s ease;
}
@keyframes censoIntroIn {
  from { opacity: 0; transform: translateY(12px) scale(0.96); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.censo-intro-badge {
  display: inline-block;
  font-size: 0.72rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #f0c674;
  background: rgba(240, 198, 116, 0.12);
  border: 1px solid rgba(240, 198, 116, 0.35);
  border-radius: 999px;
  padding: 0.28rem 0.7rem;
  margin-bottom: 0.85rem;
}
.censo-intro-title {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  line-height: 1.25;
  margin: 0 0 0.75rem;
  color: var(--text-main);
}
.censo-intro-lead {
  margin: 0 0 1rem;
  font-size: 0.92rem;
  line-height: 1.55;
  color: var(--text-muted);
}
.censo-intro-plazo {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  margin: 0 0 1.15rem;
  padding: 0.85rem 0.95rem;
  border-radius: 12px;
  background: rgba(240, 198, 116, 0.08);
  border: 1px solid rgba(240, 198, 116, 0.28);
}
.censo-intro-plazo strong {
  display: block;
  font-size: 0.88rem;
  color: var(--text-main);
  margin-bottom: 0.15rem;
}
.censo-intro-plazo span {
  font-size: 0.82rem;
  color: var(--text-muted);
  line-height: 1.4;
}
.censo-intro-who {
  margin: 0 0 0.45rem;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--text-main);
}
.censo-intro-list {
  margin: 0 0 1.35rem;
  padding: 0;
  list-style: none;
}
.censo-intro-list li {
  position: relative;
  padding: 0.45rem 0 0.45rem 1.35rem;
  font-size: 0.86rem;
  line-height: 1.45;
  color: var(--text-muted);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}
.censo-intro-list li:last-child { border-bottom: 0; }
.censo-intro-list li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0.85rem;
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 50%;
  background: #f0c674;
  box-shadow: 0 0 0 3px rgba(240, 198, 116, 0.18);
}
.censo-intro-actions { display: flex; justify-content: center; }
.censo-intro-actions .btn {
  min-width: 160px;
  padding: 0.75rem 1.25rem;
  font-weight: 600;
}
.censo-org-banner {
  margin: 0 0 1rem;
  padding: 0.9rem 1rem;
  border-radius: 12px;
  background: rgba(56, 189, 248, 0.1);
  border: 2px solid rgba(56, 189, 248, 0.55);
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.12);
}
.censo-org-banner-label {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #7dd3fc;
  margin-bottom: 0.35rem;
}
.censo-org-banner-name {
  display: block;
  font-family: var(--font-heading);
  font-size: 1.05rem;
  line-height: 1.3;
  color: var(--text-main);
  font-weight: 700;
}
.censo-intro-org {
  margin: 0 0 1rem;
  padding: 1rem 1.05rem;
  border-radius: 12px;
  background: rgba(56, 189, 248, 0.12);
  border: 2px solid rgba(56, 189, 248, 0.6);
  box-shadow: inset 0 0 0 1px rgba(125, 211, 252, 0.15);
  text-align: center;
}
.censo-intro-org-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: #7dd3fc;
  margin-bottom: 0.4rem;
}
.censo-intro-org-name {
  display: block;
  font-family: var(--font-heading);
  font-size: 1.12rem;
  line-height: 1.3;
  color: #fff;
  font-weight: 700;
}
</style>

<div class="censo-wrap">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; gap:0.75rem; flex-wrap:wrap;">
        <div>
            <strong style="font-family:var(--font-heading); font-size:1.15rem;">ConectaBarrio</strong>
            <?php if ($link): ?>
                <div style="font-size:0.82rem; color:var(--text-muted);"><?php echo htmlspecialchars($link->junta_nombre ?? ''); ?></div>
            <?php endif; ?>
        </div>
        <a href="<?php echo URLROOT; ?>/auth/login" class="btn btn-secondary btn-sm">Ir al portal</a>
    </div>

    <div class="censo-card">
        <?php if (!empty($data['success'])): ?>
            <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
            <a href="<?php echo URLROOT; ?>/auth/login" class="btn btn-primary" style="margin-top:1rem;">Volver al inicio</a>
        <?php elseif (!$link): ?>
            <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error'] ?? 'Enlace no válido'); ?></span></div>
        <?php else: ?>
            <h1 style="font-family:var(--font-heading); font-size:1.35rem; margin:0 0 0.65rem;">
                Registro para juguetes de Navidad 2026
            </h1>
            <div class="censo-org-banner" role="note">
                <span class="censo-org-banner-label">Este formulario solo aplica para</span>
                <span class="censo-org-banner-name">Junta de Vecinos N° 136 Valle de Peñaflor</span>
            </div>
            <p style="color:var(--text-muted); font-size:0.88rem; margin:0 0 1rem;">
                Complete los datos del adulto responsable y luego inscriba a quienes correspondan (hijos, discapacidad o embarazo).
                Plazo: hasta el 12 de octubre de 2026 a las 23:59.
            </p>

            <?php if (!empty($data['error'])): ?>
                <div class="alert alert-danger" style="margin-bottom:1rem;"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
            <?php endif; ?>

            <form method="post" action="<?php echo URLROOT; ?>/publico/censo_guardar" id="formCenso" autocomplete="off" novalidate>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($link->token); ?>">

                <h3 style="font-size:1rem; margin:0 0 0.75rem;">Datos del padre / adulto</h3>

                <div class="form-group">
                    <label class="form-label" for="rut">RUT *</label>
                    <input type="text" name="rut" id="rut" class="form-control cb-rut-chile" required maxlength="12"
                           inputmode="text" autocomplete="off"
                           placeholder="11111111-1" value="<?php echo htmlspecialchars($old['rut'] ?? ''); ?>">
                    <small class="cb-field-hint" data-rut-hint>Formato: 11111111-1 (sin puntos). Se valida al escribir.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre completo *</label>
                    <input type="text" name="nombre" id="nombre" class="form-control cb-uppercase" required
                           placeholder="NOMBRE Y APELLIDOS"
                           value="<?php echo htmlspecialchars($old['nombre'] ?? ''); ?>">
                </div>

                <?php if ($usesCalles): ?>
                <div class="form-group">
                    <label class="form-label" for="calle_input">Dirección (calle) *</label>
                    <div class="cb-socio-picker" id="picker_calle" data-required="1" data-placeholder="Escriba para buscar la calle…">
                        <input type="hidden" name="calle_id" id="calle_id" value="<?php echo $calleSeleccionadaId > 0 ? $calleSeleccionadaId : ''; ?>" required>
                        <input type="text" class="form-control cb-socio-picker-input" id="calle_input" autocomplete="off"
                               placeholder="Escriba para buscar la calle…"
                               value="<?php echo htmlspecialchars($calleSeleccionadaLabel); ?>">
                        <button type="button" class="cb-socio-picker-clear" title="Quitar calle" <?php echo $calleSeleccionadaId > 0 ? '' : 'hidden'; ?> aria-label="Quitar calle">&times;</button>
                        <ul class="cb-socio-picker-list" hidden></ul>
                    </div>
                    <small class="cb-field-hint">Escriba parte del nombre para acotar el listado.</small>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label class="form-label" for="direccion_texto">Dirección *</label>
                    <input type="text" name="direccion_texto" id="direccion_texto" class="form-control" required
                           value="<?php echo htmlspecialchars($old['direccion_texto'] ?? ''); ?>">
                </div>
                <?php endif; ?>

                <?php
                $id = 'telefono';
                $name = 'telefono';
                $telefonoLabel = 'Teléfono';
                $required = true;
                $value = $old['telefono'] ?? '';
                require APPROOT . '/views/partials/campo_telefono_cl.php';
                ?>

                <div class="censo-section">
                    <h3 style="font-size:1rem; margin:0 0 0.75rem;">¿Desea registrar…?</h3>

                    <label style="display:flex; gap:0.5rem; align-items:center; margin-bottom:0.5rem;">
                        <input type="checkbox" name="registra_hijos" value="1" id="chkHijos"
                            <?php echo !empty($old['registra_hijos']) ? 'checked' : ''; ?>>
                        Hijos (0 a 8 años)
                    </label>
                    <div id="boxHijos" class="censo-toggle-box <?php echo !empty($old['registra_hijos']) ? 'open' : ''; ?>">
                        <div id="gridHijos"></div>
                        <button type="button" class="btn btn-secondary btn-sm" id="btnAddHijo">+ Agregar hijo</button>
                    </div>

                    <label style="display:flex; gap:0.5rem; align-items:center; margin:0.85rem 0 0.5rem;">
                        <input type="checkbox" name="registra_discapacidad" value="1" id="chkDisc"
                            <?php echo !empty($old['registra_discapacidad']) ? 'checked' : ''; ?>>
                        Persona con discapacidad (0 a 18 años)
                    </label>
                    <div id="boxDisc" class="censo-toggle-box <?php echo !empty($old['registra_discapacidad']) ? 'open' : ''; ?>">
                        <div id="gridDisc"></div>
                        <button type="button" class="btn btn-secondary btn-sm" id="btnAddDisc">+ Agregar persona</button>
                    </div>

                    <label style="display:flex; gap:0.5rem; align-items:center; margin:0.85rem 0 0.5rem;">
                        <input type="checkbox" name="registra_embarazo" value="1" id="chkEmb"
                            <?php echo !empty($old['registra_embarazo']) ? 'checked' : ''; ?>>
                        Madre embarazada
                    </label>
                    <div id="boxEmb" class="censo-toggle-box <?php echo !empty($old['registra_embarazo']) ? 'open' : ''; ?>">
                        <label style="display:flex; gap:0.5rem; align-items:center; margin-bottom:0.65rem;">
                            <input type="checkbox" name="embarazo_usa_adulto" value="1" id="chkEmbMismo"
                                <?php echo !empty($old['embarazo_usa_adulto']) ? 'checked' : ''; ?>>
                            Usar los mismos datos del padre/adulto de arriba
                        </label>
                        <div id="embOtros" style="<?php echo !empty($old['embarazo_usa_adulto']) ? 'display:none;' : ''; ?>">
                            <div class="form-group">
                                <label class="form-label">RUT *</label>
                                <input type="text" name="embarazo_rut" class="form-control cb-rut-chile" maxlength="12"
                                       placeholder="11111111-1"
                                       value="<?php echo htmlspecialchars($old['embarazo_rut'] ?? ''); ?>">
                                <small class="cb-field-hint" data-rut-hint>Formato: 11111111-1</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nombre completo *</label>
                                <input type="text" name="embarazo_nombre" class="form-control cb-uppercase"
                                       value="<?php echo htmlspecialchars($old['embarazo_nombre'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sexo *</label>
                                <select name="embarazo_sexo" class="form-control">
                                    <option value="FEMENINO" selected>Femenino</option>
                                    <option value="MASCULINO">Masculino</option>
                                    <option value="NO ESPECIFICAR">No especificar</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha probable de parto *</label>
                            <input type="date" name="embarazo_fecha_parto" class="form-control"
                                   min="<?php echo $hoy; ?>" max="<?php echo $maxParto; ?>"
                                   value="<?php echo htmlspecialchars($old['embarazo_fecha_parto'] ?? ''); ?>">
                            <small style="color:var(--text-muted); font-size:0.75rem;">Desde hoy hasta diciembre 2026.</small>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1.25rem; padding:0.85rem;">
                    Enviar registro
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($link && empty($data['success'])): ?>
<div id="censoIntroModal" class="censo-intro-overlay is-open" role="dialog" aria-modal="true" aria-labelledby="censoIntroTitle">
    <div class="censo-intro-box">
        <div class="censo-intro-badge">Municipalidad de Peñaflor</div>
        <h2 id="censoIntroTitle" class="censo-intro-title">Registro para juguetes de Navidad 2026</h2>
        <div class="censo-intro-org" role="note">
            <span class="censo-intro-org-label">Este formulario solo aplica para la organización</span>
            <span class="censo-intro-org-name">Junta de Vecinos N° 136 Valle de Peñaflor</span>
        </div>
        <p class="censo-intro-lead">
            La Municipalidad de Peñaflor, a través de las organizaciones sociales,
            está recopilando la información de niños, niñas y jóvenes de nuestra comunidad
            para entregarles un presente en esta Navidad.
            Si usted no pertenece a esta junta, no utilice este formulario.
        </p>
        <div class="censo-intro-plazo">
            <div aria-hidden="true" style="flex-shrink:0; width:28px; height:28px; color:#f0c674; margin-top:0.1rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div>
                <strong>Plazo de inscripción</strong>
                <span>Hasta el <strong style="color:var(--text-main);">12 de octubre de 2026</strong>, a las 23:59:59.
                Después de esa hora no se recibirán más registros por este formulario.</span>
            </div>
        </div>
        <p class="censo-intro-who">¿Quiénes pueden ser inscritos?</p>
        <ul class="censo-intro-list">
            <li>Niños y niñas de <strong>0 a 8 años</strong>, residentes en el sector correspondiente de la organización.</li>
            <li>Personas con discapacidad de hasta <strong>18 años</strong>.</li>
            <li>Madres embarazadas cuya fecha estimada de parto sea en <strong>diciembre de 2026</strong>.</li>
            <li>Personas cuya ficha del Registro Social de Hogares pertenezca a la <strong>comuna de Peñaflor</strong>.</li>
        </ul>
        <div class="censo-intro-actions">
            <button type="button" class="btn btn-primary" id="censoIntroOk">Entendido</button>
        </div>
    </div>
</div>
<script>
(function () {
    var intro = document.getElementById('censoIntroModal');
    var introOk = document.getElementById('censoIntroOk');
    function closeIntro() {
        if (!intro) return;
        intro.classList.remove('is-open');
        intro.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        var first = document.getElementById('rut');
        if (first) first.focus();
    }
    if (intro) {
        document.body.style.overflow = 'hidden';
        intro.setAttribute('aria-hidden', 'false');
        if (introOk) {
            introOk.focus();
            introOk.addEventListener('click', closeIntro);
        }
        // Solo se cierra con «Entendido» (no con clic fuera ni Escape).
    }

    var CALLES = <?php echo json_encode($callesPickerJson, JSON_UNESCAPED_UNICODE); ?>;
    var HOY = <?php echo json_encode($hoy); ?>;
    var MAX_PARTO = <?php echo json_encode($maxParto); ?>;

    function formatRutChile(raw) {
        var v = String(raw || '').replace(/[^0-9kK]/g, '').toUpperCase();
        if (v.length > 9) v = v.slice(0, 9);
        if (v.length <= 1) return v;
        return v.slice(0, -1) + '-' + v.slice(-1);
    }

    function validateRutChile(rut) {
        var formatted = formatRutChile(rut);
        var m = formatted.match(/^(\d{7,8})-([\dK])$/);
        if (!m) return false;
        var body = m[1];
        var dv = m[2];
        var sum = 0;
        var mult = 2;
        for (var i = body.length - 1; i >= 0; i--) {
            sum += parseInt(body.charAt(i), 10) * mult;
            mult = mult === 7 ? 2 : mult + 1;
        }
        var rest = 11 - (sum % 11);
        var expected = rest === 11 ? '0' : (rest === 10 ? 'K' : String(rest));
        return dv === expected;
    }

    function setRutState(input, ok, msg) {
        if (!input) return;
        input.classList.toggle('is-invalid', !ok && input.value.trim() !== '');
        var hint = input.parentNode ? input.parentNode.querySelector('[data-rut-hint]') : null;
        if (hint) {
            hint.textContent = msg || 'Formato: 11111111-1 (sin puntos). Se valida al escribir.';
            hint.classList.toggle('is-error', !ok && input.value.trim() !== '');
        }
        input.setCustomValidity(ok || input.value.trim() === '' ? '' : (msg || 'RUT inválido'));
    }

    function bindRutInput(input) {
        if (!input || input.dataset.rutBound === '1') return;
        input.dataset.rutBound = '1';
        if (!input.parentNode.querySelector('[data-rut-hint]')) {
            var h = document.createElement('small');
            h.className = 'cb-field-hint';
            h.setAttribute('data-rut-hint', '');
            h.textContent = 'Formato: 11111111-1';
            input.parentNode.appendChild(h);
        }
        input.addEventListener('input', function () {
            var pos = this.selectionStart;
            var before = this.value.length;
            this.value = formatRutChile(this.value);
            var after = this.value.length;
            var newPos = Math.max(0, (pos || 0) + (after - before));
            try { this.setSelectionRange(newPos, newPos); } catch (e) {}
            var v = this.value.trim();
            if (v === '') {
                setRutState(this, true, '');
                return;
            }
            if (!/^\d{7,8}-[\dK]$/.test(v)) {
                setRutState(this, false, 'Complete el RUT con formato 11111111-1');
                return;
            }
            setRutState(this, validateRutChile(v), validateRutChile(v) ? 'RUT válido' : 'RUT chileno incorrecto (dígito verificador)');
        });
        input.addEventListener('blur', function () {
            var v = this.value.trim();
            if (v === '') {
                setRutState(this, true, 'Formato: 11111111-1');
                return;
            }
            this.value = formatRutChile(v);
            setRutState(this, validateRutChile(this.value), validateRutChile(this.value) ? 'RUT válido' : 'RUT chileno incorrecto');
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === ' ' || e.key === '.') e.preventDefault();
        });
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text');
            this.value = formatRutChile(text);
            this.dispatchEvent(new Event('input'));
        });
        if (input.value) {
            input.value = formatRutChile(input.value);
            input.dispatchEvent(new Event('input'));
        }
    }

    function bindUppercase(input) {
        if (!input || input.dataset.upBound === '1') return;
        input.dataset.upBound = '1';
        input.addEventListener('input', function () {
            var start = this.selectionStart;
            var end = this.selectionEnd;
            this.value = this.value.toUpperCase();
            try { this.setSelectionRange(start, end); } catch (e) {}
        });
    }

    function bindEdadInput(input, maxEdad, label) {
        if (!input || input.dataset.edadBound === '1') return;
        input.dataset.edadBound = '1';
        input.min = '0';
        input.max = String(maxEdad);
        input.step = '1';
        var hint = document.createElement('small');
        hint.className = 'cb-field-hint';
        hint.textContent = 'Entre 0 y ' + maxEdad + ' años';
        input.parentNode.appendChild(hint);

        function check() {
            var raw = String(input.value || '').trim();
            if (raw === '') {
                input.classList.remove('is-invalid');
                hint.textContent = 'Entre 0 y ' + maxEdad + ' años';
                hint.classList.remove('is-error');
                input.setCustomValidity('');
                return true;
            }
            var n = parseInt(raw, 10);
            if (isNaN(n) || n < 0 || n > maxEdad) {
                input.classList.add('is-invalid');
                hint.textContent = label + ': la edad debe ser entre 0 y ' + maxEdad + ' años';
                hint.classList.add('is-error');
                input.setCustomValidity('Edad fuera de rango (0–' + maxEdad + ')');
                return false;
            }
            input.classList.remove('is-invalid');
            hint.textContent = 'Entre 0 y ' + maxEdad + ' años';
            hint.classList.remove('is-error');
            input.setCustomValidity('');
            return true;
        }
        input.addEventListener('input', check);
        input.addEventListener('change', check);
        input.addEventListener('blur', function () {
            if (!check()) {
                alert(label + ': la edad debe ser entre 0 y ' + maxEdad + ' años.');
            }
        });
    }

    function sexoSelect(name, val) {
        val = val || '';
        return '<select name="' + name + '" class="form-control" required>' +
            '<option value="">Sexo</option>' +
            '<option value="MASCULINO"' + (val === 'MASCULINO' ? ' selected' : '') + '>Masculino</option>' +
            '<option value="FEMENINO"' + (val === 'FEMENINO' ? ' selected' : '') + '>Femenino</option>' +
            '<option value="NO ESPECIFICAR"' + (val === 'NO ESPECIFICAR' ? ' selected' : '') + '>No especificar</option>' +
            '</select>';
    }

    function addRow(container, prefix, maxEdad, prefill) {
        prefill = prefill || {};
        var label = prefix === 'hijo' ? 'Hijo' : 'Persona';
        var row = document.createElement('div');
        row.className = 'censo-grid-row';
        row.innerHTML =
            '<div class="form-group" style="margin:0;"><label class="form-label">RUT</label>' +
            '<input type="text" name="' + prefix + '_rut[]" class="form-control cb-rut-chile" maxlength="12" required placeholder="11111111-1" value="' + (prefill.rut || '') + '"></div>' +
            '<div class="form-group" style="margin:0;"><label class="form-label">Nombre completo</label>' +
            '<input type="text" name="' + prefix + '_nombre[]" class="form-control cb-uppercase" required value="' + (prefill.nombre || '') + '"></div>' +
            '<div class="form-group" style="margin:0;"><label class="form-label">Sexo</label>' + sexoSelect(prefix + '_sexo[]', prefill.sexo) + '</div>' +
            '<div class="form-group" style="margin:0;"><label class="form-label">Edad</label>' +
            '<input type="number" name="' + prefix + '_edad[]" class="form-control cb-edad-censo" min="0" max="' + maxEdad + '" step="1" required value="' + (prefill.edad || '') + '"></div>' +
            '<button type="button" class="btn btn-danger btn-sm" style="margin-bottom:0.1rem;" onclick="this.parentNode.remove()">Quitar</button>';
        container.appendChild(row);
        bindRutInput(row.querySelector('.cb-rut-chile'));
        bindUppercase(row.querySelector('.cb-uppercase'));
        bindEdadInput(row.querySelector('.cb-edad-censo'), maxEdad, label);
    }

    function bindToggle(chkId, boxId, onOpen) {
        var chk = document.getElementById(chkId);
        var box = document.getElementById(boxId);
        if (!chk || !box) return;
        function sync() {
            box.classList.toggle('open', chk.checked);
            if (chk.checked && onOpen) onOpen();
        }
        chk.addEventListener('change', sync);
        sync();
    }

    function initCallePicker(root, dataSource) {
        if (!root) return;
        var hidden = root.querySelector('input[type="hidden"]');
        var input = root.querySelector('.cb-socio-picker-input');
        var list = root.querySelector('.cb-socio-picker-list');
        var clearBtn = root.querySelector('.cb-socio-picker-clear');
        var activeIdx = -1;
        var source = dataSource || [];

        function renderList(query) {
            var q = (query || '').trim().toLowerCase();
            var items = q === ''
                ? source.slice(0, 50)
                : source.filter(function (s) { return s.search.indexOf(q) !== -1; }).slice(0, 50);
            list.innerHTML = '';
            if (items.length === 0) {
                var empty = document.createElement('li');
                empty.className = 'cb-socio-picker-empty';
                empty.textContent = 'Sin coincidencias';
                list.appendChild(empty);
            } else {
                items.forEach(function (s, idx) {
                    var li = document.createElement('li');
                    li.className = 'cb-socio-picker-item' + (idx === activeIdx ? ' is-active' : '');
                    li.dataset.id = s.id;
                    li.textContent = s.label;
                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        selectCalle(s);
                    });
                    list.appendChild(li);
                });
            }
            list.hidden = false;
        }

        function selectCalle(s) {
            hidden.value = s.id;
            input.value = s.label;
            list.hidden = true;
            activeIdx = -1;
            if (clearBtn) clearBtn.hidden = false;
            hidden.setCustomValidity('');
        }

        function clearCalle() {
            hidden.value = '';
            input.value = '';
            list.hidden = true;
            activeIdx = -1;
            if (clearBtn) clearBtn.hidden = true;
        }

        input.addEventListener('focus', function () { renderList(input.value); });
        input.addEventListener('input', function () {
            hidden.value = '';
            if (clearBtn) clearBtn.hidden = true;
            activeIdx = -1;
            renderList(input.value);
        });
        input.addEventListener('keydown', function (e) {
            var items = list.querySelectorAll('.cb-socio-picker-item');
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
                var id = parseInt(items[activeIdx].dataset.id, 10);
                var s = source.find(function (x) { return x.id === id; });
                if (s) selectCalle(s);
            } else if (e.key === 'Escape') {
                list.hidden = true;
            }
        });
        input.addEventListener('blur', function () {
            setTimeout(function () { list.hidden = true; }, 150);
            if (!hidden.value && input.value.trim() !== '') {
                input.value = '';
            }
        });
        if (clearBtn) clearBtn.addEventListener('click', clearCalle);
    }

    var gridH = document.getElementById('gridHijos');
    var gridD = document.getElementById('gridDisc');
    bindToggle('chkHijos', 'boxHijos', function () {
        if (gridH && gridH.children.length === 0) addRow(gridH, 'hijo', 8);
    });
    bindToggle('chkDisc', 'boxDisc', function () {
        if (gridD && gridD.children.length === 0) addRow(gridD, 'disc', 18);
    });
    bindToggle('chkEmb', 'boxEmb');

    document.getElementById('btnAddHijo')?.addEventListener('click', function () { addRow(gridH, 'hijo', 8); });
    document.getElementById('btnAddDisc')?.addEventListener('click', function () { addRow(gridD, 'disc', 18); });

    var chkMismo = document.getElementById('chkEmbMismo');
    var embOtros = document.getElementById('embOtros');
    function syncEmb() {
        if (!chkMismo || !embOtros) return;
        embOtros.style.display = chkMismo.checked ? 'none' : 'block';
        if (!chkMismo.checked) {
            embOtros.querySelectorAll('.cb-rut-chile').forEach(bindRutInput);
            embOtros.querySelectorAll('.cb-uppercase').forEach(bindUppercase);
        }
    }
    chkMismo?.addEventListener('change', syncEmb);
    syncEmb();

    document.querySelectorAll('.cb-rut-chile').forEach(bindRutInput);
    document.querySelectorAll('.cb-uppercase').forEach(bindUppercase);
    initCallePicker(document.getElementById('picker_calle'), CALLES);

    // Prefill rows if server returned old arrays
    <?php
    $oldHijos = [];
    if (!empty($old['hijo_rut']) && is_array($old['hijo_rut'])) {
        foreach ($old['hijo_rut'] as $i => $r) {
            $oldHijos[] = [
                'rut' => $r,
                'nombre' => $old['hijo_nombre'][$i] ?? '',
                'sexo' => $old['hijo_sexo'][$i] ?? '',
                'edad' => $old['hijo_edad'][$i] ?? '',
            ];
        }
    }
    $oldDisc = [];
    if (!empty($old['disc_rut']) && is_array($old['disc_rut'])) {
        foreach ($old['disc_rut'] as $i => $r) {
            $oldDisc[] = [
                'rut' => $r,
                'nombre' => $old['disc_nombre'][$i] ?? '',
                'sexo' => $old['disc_sexo'][$i] ?? '',
                'edad' => $old['disc_edad'][$i] ?? '',
            ];
        }
    }
    ?>
    var preH = <?php echo json_encode($oldHijos, JSON_UNESCAPED_UNICODE); ?>;
    var preD = <?php echo json_encode($oldDisc, JSON_UNESCAPED_UNICODE); ?>;
    if (preH.length && gridH) {
        preH.forEach(function (p) { addRow(gridH, 'hijo', 8, p); });
    }
    if (preD.length && gridD) {
        preD.forEach(function (p) { addRow(gridD, 'disc', 18, p); });
    }

    document.getElementById('formCenso')?.addEventListener('submit', function (e) {
        var errors = [];

        var rutAdulto = document.getElementById('rut');
        if (!rutAdulto || !validateRutChile(rutAdulto.value)) {
            errors.push('El RUT del adulto no es válido (formato 11111111-1).');
            if (rutAdulto) setRutState(rutAdulto, false, 'RUT chileno incorrecto');
        }

        var calleId = document.getElementById('calle_id');
        if (calleId && !calleId.value) {
            errors.push('Seleccione una calle del listado (escriba para buscar).');
            calleId.setCustomValidity('Seleccione una calle');
        }

        var chkH = document.getElementById('chkHijos');
        if (chkH && chkH.checked) {
            var hRows = gridH ? gridH.querySelectorAll('.censo-grid-row') : [];
            if (!hRows.length) errors.push('Agregue al menos un hijo o desmarque la opción.');
            hRows.forEach(function (row, idx) {
                var r = row.querySelector('.cb-rut-chile');
                var ed = row.querySelector('.cb-edad-censo');
                if (r && !validateRutChile(r.value)) {
                    errors.push('Hijo #' + (idx + 1) + ': RUT inválido.');
                    setRutState(r, false, 'RUT inválido');
                }
                if (ed) {
                    var n = parseInt(ed.value, 10);
                    if (isNaN(n) || n < 0 || n > 8) {
                        errors.push('Hijo #' + (idx + 1) + ': la edad debe ser entre 0 y 8 años.');
                        ed.classList.add('is-invalid');
                    }
                }
            });
        }

        var chkD = document.getElementById('chkDisc');
        if (chkD && chkD.checked) {
            var dRows = gridD ? gridD.querySelectorAll('.censo-grid-row') : [];
            if (!dRows.length) errors.push('Agregue al menos una persona con discapacidad o desmarque la opción.');
            dRows.forEach(function (row, idx) {
                var r = row.querySelector('.cb-rut-chile');
                var ed = row.querySelector('.cb-edad-censo');
                if (r && !validateRutChile(r.value)) {
                    errors.push('Discapacidad #' + (idx + 1) + ': RUT inválido.');
                    setRutState(r, false, 'RUT inválido');
                }
                if (ed) {
                    var n = parseInt(ed.value, 10);
                    if (isNaN(n) || n < 0 || n > 18) {
                        errors.push('Discapacidad #' + (idx + 1) + ': la edad debe ser entre 0 y 18 años.');
                        ed.classList.add('is-invalid');
                    }
                }
            });
        }

        var chkE = document.getElementById('chkEmb');
        if (chkE && chkE.checked) {
            var fecha = document.querySelector('input[name="embarazo_fecha_parto"]');
            if (!fecha || !fecha.value || fecha.value < HOY || fecha.value > MAX_PARTO) {
                errors.push('Embarazo: indique fecha probable de parto entre hoy y diciembre 2026.');
            }
            if (!chkMismo || !chkMismo.checked) {
                var er = document.querySelector('input[name="embarazo_rut"]');
                var en = document.querySelector('input[name="embarazo_nombre"]');
                if (!er || !validateRutChile(er.value)) {
                    errors.push('Embarazo: RUT inválido.');
                    if (er) setRutState(er, false, 'RUT inválido');
                }
                if (!en || !en.value.trim()) {
                    errors.push('Embarazo: indique el nombre completo.');
                }
            }
        }

        if (errors.length) {
            e.preventDefault();
            alert(errors.join('\n'));
            return false;
        }
    });
})();
</script>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
