<?php
require_once APPROOT . '/core/SocioInput.php';
$prefix = $prefix ?? '';
$values = $values ?? [];
$required = !empty($required);
$reqAttr = $required ? 'required' : '';
$fieldId = htmlspecialchars($prefix . 'nacionalidad', ENT_QUOTES);
$selectId = htmlspecialchars($prefix . 'nacionalidad_select', ENT_QUOTES);
$otraId = htmlspecialchars($prefix . 'nacionalidad_otra', ENT_QUOTES);
$wrapId = htmlspecialchars($prefix . 'nacionalidad_otra_wrap', ENT_QUOTES);
$listId = htmlspecialchars($prefix . 'nacionalidad_datalist', ENT_QUOTES);

$stored = trim((string)($values['nacionalidad'] ?? ''));
$enListado = SocioInput::isNacionalidadEnListado($stored);
$selectValue = $enListado ? $stored : ($stored !== '' ? SocioInput::NACIONALIDAD_OTRA : '');
$otraValue = $enListado ? '' : $stored;
$nacionalidades = SocioInput::getNacionalidadesSorted();
?>
<div class="form-group cb-nacionalidad-field" data-prefix="<?php echo htmlspecialchars($prefix); ?>">
    <label for="<?php echo $selectId; ?>" class="form-label">Nacionalidad<?php echo $required ? ' *' : ''; ?></label>
    <select name="nacionalidad_select" id="<?php echo $selectId; ?>" class="form-control cb-nacionalidad-select" <?php echo $reqAttr; ?>>
        <option value="">-- Seleccionar --</option>
        <?php foreach ($nacionalidades as $pais): ?>
            <option value="<?php echo htmlspecialchars($pais); ?>" <?php echo $selectValue === $pais ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($pais); ?>
            </option>
        <?php endforeach; ?>
        <option value="<?php echo SocioInput::NACIONALIDAD_OTRA; ?>" <?php echo $selectValue === SocioInput::NACIONALIDAD_OTRA ? 'selected' : ''; ?>>
            Otra (buscar o escribir)
        </option>
    </select>
    <div id="<?php echo $wrapId; ?>" class="cb-nacionalidad-otra-wrap" <?php echo $selectValue === SocioInput::NACIONALIDAD_OTRA ? '' : 'hidden'; ?>>
        <label for="<?php echo $otraId; ?>" class="form-label" style="margin-top: 0.65rem; font-size: 0.82rem;">Especifique nacionalidad</label>
        <input type="text"
               name="nacionalidad_otra"
               id="<?php echo $otraId; ?>"
               class="form-control cb-nacionalidad-otra-input"
               list="<?php echo $listId; ?>"
               placeholder="Escriba para filtrar: ej. Chilena, Japonesa…"
               autocomplete="off"
               maxlength="80"
               value="<?php echo htmlspecialchars($otraValue); ?>"
               <?php echo ($required && $selectValue === SocioInput::NACIONALIDAD_OTRA) ? 'required' : ''; ?>>
        <datalist id="<?php echo $listId; ?>">
            <?php foreach ($nacionalidades as $pais): ?>
                <option value="<?php echo htmlspecialchars($pais); ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <small class="cb-nacionalidad-hint">Escriba letras para acotar el listado o indique una nacionalidad no listada.</small>
    </div>
    <input type="hidden" name="nacionalidad" id="<?php echo $fieldId; ?>" value="<?php echo htmlspecialchars($stored); ?>">
</div>
