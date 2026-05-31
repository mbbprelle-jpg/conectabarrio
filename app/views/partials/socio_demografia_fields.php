<?php
require_once APPROOT . '/core/SocioInput.php';
$prefix = $prefix ?? '';
$values = $values ?? [];
$required = !empty($required);
$reqAttr = $required ? 'required' : '';
?>
<div class="form-grid-2">
    <div class="form-group">
        <label for="<?php echo htmlspecialchars($prefix); ?>genero" class="form-label">Género<?php echo $required ? ' *' : ''; ?></label>
        <select name="genero" id="<?php echo htmlspecialchars($prefix); ?>genero" class="form-control" <?php echo $reqAttr; ?>>
            <option value="">-- Seleccionar --</option>
            <option value="MASCULINO" <?php echo (($values['genero'] ?? '') === 'MASCULINO') ? 'selected' : ''; ?>>Masculino</option>
            <option value="FEMENINO" <?php echo (($values['genero'] ?? '') === 'FEMENINO') ? 'selected' : ''; ?>>Femenino</option>
            <option value="NO ESPECIFICAR" <?php echo (($values['genero'] ?? '') === 'NO ESPECIFICAR') ? 'selected' : ''; ?>>No especificar</option>
        </select>
    </div>
    <div class="form-group">
        <label for="<?php echo htmlspecialchars($prefix); ?>fecha_nacimiento" class="form-label">Fecha de Nacimiento<?php echo $required ? ' *' : ''; ?></label>
        <input type="date"
               name="fecha_nacimiento"
               id="<?php echo htmlspecialchars($prefix); ?>fecha_nacimiento"
               class="form-control"
               max="<?php echo date('Y-m-d'); ?>"
               value="<?php echo htmlspecialchars($values['fecha_nacimiento'] ?? ''); ?>"
               <?php echo $reqAttr; ?>>
    </div>
</div>
<div class="form-grid-2">
    <div class="form-group">
        <label for="<?php echo htmlspecialchars($prefix); ?>estado_civil" class="form-label">Estado Civil<?php echo $required ? ' *' : ''; ?></label>
        <select name="estado_civil" id="<?php echo htmlspecialchars($prefix); ?>estado_civil" class="form-control" <?php echo $reqAttr; ?>>
            <option value="">-- Seleccionar --</option>
            <?php foreach (SocioInput::ESTADOS_CIVILES as $key => $label): ?>
                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (($values['estado_civil'] ?? '') === $key) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="<?php echo htmlspecialchars($prefix); ?>nacionalidad" class="form-label">Nacionalidad<?php echo $required ? ' *' : ''; ?></label>
        <select name="nacionalidad" id="<?php echo htmlspecialchars($prefix); ?>nacionalidad" class="form-control" <?php echo $reqAttr; ?>>
            <option value="">-- Seleccionar --</option>
            <?php foreach (SocioInput::NACIONALIDADES as $pais): ?>
                <option value="<?php echo htmlspecialchars($pais); ?>" <?php echo (($values['nacionalidad'] ?? '') === $pais) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($pais); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="form-group">
    <label for="<?php echo htmlspecialchars($prefix); ?>profesion" class="form-label">Profesión u oficio<?php echo $required ? ' *' : ''; ?></label>
    <input type="text"
           name="profesion"
           id="<?php echo htmlspecialchars($prefix); ?>profesion"
           class="form-control cb-uppercase"
           placeholder="EJ: ENFERMERA, COMERCIANTE, TÉCNICO EN MINERÍA"
           maxlength="120"
           value="<?php echo htmlspecialchars($values['profesion'] ?? ''); ?>"
           <?php echo $reqAttr; ?>>
</div>
