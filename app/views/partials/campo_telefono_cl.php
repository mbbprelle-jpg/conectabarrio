<?php
require_once APPROOT . '/core/SocioInput.php';
$id = $id ?? 'telefono';
$name = $name ?? 'telefono';
$label = $label ?? 'Teléfono';
$required = !empty($required);
$value = $value ?? '';
$digits = SocioInput::telefonoDigits($value);
?>
<div class="form-group">
    <label for="<?php echo htmlspecialchars($id); ?>" class="form-label"><?php echo htmlspecialchars($label); ?><?php echo $required ? ' *' : ''; ?></label>
    <div class="telefono-cl-wrap">
        <span class="telefono-cl-prefix" aria-hidden="true">+56</span>
        <input type="tel"
               name="<?php echo htmlspecialchars($name); ?>"
               id="<?php echo htmlspecialchars($id); ?>"
               class="form-control cb-telefono-cl"
               inputmode="numeric"
               maxlength="9"
               placeholder="912345678"
               autocomplete="tel-national"
               value="<?php echo htmlspecialchars($digits); ?>"
               <?php echo $required ? 'required' : ''; ?>>
    </div>
    <small style="color: var(--text-muted); font-size: 0.75rem;">Ingrese los 9 dígitos de su celular (sin el +56).</small>
</div>
