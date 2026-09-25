<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$link = $data['link'] ?? null;
$old = $data['old'] ?? [];
$usesCalles = !empty($data['uses_calles']);
$calles = $data['calles'] ?? [];
$hoy = date('Y-m-d');
$maxParto = '2026-12-31';
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
            <h1 style="font-family:var(--font-heading); font-size:1.35rem; margin:0 0 0.35rem;">
                <?php echo htmlspecialchars($link->titulo ?: 'Registro familiar'); ?>
            </h1>
            <p style="color:var(--text-muted); font-size:0.88rem; margin:0 0 1rem;">
                Complete los datos del padre o adulto responsable. Luego podrá registrar hijos, personas con discapacidad o embarazo.
            </p>

            <?php if (!empty($data['error'])): ?>
                <div class="alert alert-danger" style="margin-bottom:1rem;"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
            <?php endif; ?>

            <form method="post" action="<?php echo URLROOT; ?>/publico/censo_guardar" id="formCenso" autocomplete="off">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($link->token); ?>">

                <h3 style="font-size:1rem; margin:0 0 0.75rem;">Datos del padre / adulto</h3>

                <div class="form-group">
                    <label class="form-label" for="rut">RUT *</label>
                    <input type="text" name="rut" id="rut" class="form-control cb-rut-chile" required maxlength="12"
                           placeholder="11222333-K" value="<?php echo htmlspecialchars($old['rut'] ?? ''); ?>">
                    <small style="color:var(--text-muted); font-size:0.75rem;">Sin puntos. Ejemplo: 11222333-K</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre completo *</label>
                    <input type="text" name="nombre" id="nombre" class="form-control cb-uppercase" required
                           placeholder="NOMBRE Y APELLIDOS"
                           value="<?php echo htmlspecialchars($old['nombre'] ?? ''); ?>">
                </div>

                <?php if ($usesCalles): ?>
                <div class="form-group">
                    <label class="form-label" for="calle_id">Dirección (calle) *</label>
                    <select name="calle_id" id="calle_id" class="form-control" required>
                        <option value="">-- Seleccionar calle --</option>
                        <?php foreach ($calles as $c): ?>
                            <option value="<?php echo (int)$c->id; ?>" <?php echo ((string)($old['calle_id'] ?? '') === (string)$c->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c->nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                                <?php echo !empty($old['embarazo_usa_adulto']) || !isset($old['embarazo_usa_adulto']) ? 'checked' : ''; ?>>
                            Usar los mismos datos del padre/adulto de arriba
                        </label>
                        <div id="embOtros" style="<?php echo (empty($old['registra_embarazo']) || !empty($old['embarazo_usa_adulto']) || !isset($old['embarazo_usa_adulto'])) ? 'display:none;' : ''; ?>">
                            <div class="form-group">
                                <label class="form-label">RUT *</label>
                                <input type="text" name="embarazo_rut" class="form-control cb-rut-chile" maxlength="12"
                                       value="<?php echo htmlspecialchars($old['embarazo_rut'] ?? ''); ?>">
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
<script>
(function () {
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
        var row = document.createElement('div');
        row.className = 'censo-grid-row';
        row.innerHTML =
            '<div class="form-group" style="margin:0;"><label class="form-label">RUT</label>' +
            '<input type="text" name="' + prefix + '_rut[]" class="form-control cb-rut-chile" maxlength="12" required placeholder="11222333-K" value="' + (prefill.rut || '') + '"></div>' +
            '<div class="form-group" style="margin:0;"><label class="form-label">Nombre completo</label>' +
            '<input type="text" name="' + prefix + '_nombre[]" class="form-control cb-uppercase" required value="' + (prefill.nombre || '') + '"></div>' +
            '<div class="form-group" style="margin:0;"><label class="form-label">Sexo</label>' + sexoSelect(prefix + '_sexo[]', prefill.sexo) + '</div>' +
            '<div class="form-group" style="margin:0;"><label class="form-label">Edad</label>' +
            '<input type="number" name="' + prefix + '_edad[]" class="form-control" min="0" max="' + maxEdad + '" required value="' + (prefill.edad || '') + '"></div>' +
            '<button type="button" class="btn btn-danger btn-sm" style="margin-bottom:0.1rem;" onclick="this.parentNode.remove()">Quitar</button>';
        container.appendChild(row);
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
    }
    chkMismo?.addEventListener('change', syncEmb);
    syncEmb();

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
})();
</script>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
