<?php
require_once APPROOT . '/core/OrgHelper.php';
$id = $id ?? 'comuna';
$name = $name ?? 'comuna';
$selected = $selected ?? '';
$required = !empty($required);
?>
<select name="<?php echo htmlspecialchars($name); ?>" id="<?php echo htmlspecialchars($id); ?>" class="form-control" <?php echo $required ? 'required' : ''; ?>>
    <option value="">-- Seleccionar comuna --</option>
    <?php foreach (OrgHelper::COMUNAS as $comuna): ?>
        <option value="<?php echo htmlspecialchars($comuna); ?>" <?php echo ($selected === $comuna) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($comuna); ?>
        </option>
    <?php endforeach; ?>
</select>
