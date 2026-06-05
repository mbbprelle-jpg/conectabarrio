<?php
require_once APPROOT . '/core/OrgHelper.php';
$domPrefix = $domPrefix ?? '';
$domValues = $domValues ?? [];
$domRequired = !empty($domRequired);
$domReqAttr = $domRequired ? 'required' : '';
$orgTipo = $orgTipo ?? 'Junta de Vecinos';
$usesCalles = isset($usesCalles) ? (bool)$usesCalles : OrgHelper::usesCallesJurisdiccion($orgTipo);
$calles = $calles ?? [];
$georefComuna = $georefComuna ?? '';
$calleSelectId = $domPrefix . 'calle_id';
$numeroInputId = $domPrefix . 'numero_casa';
$direccionInputId = $domPrefix . 'direccion_texto';
?>
<?php if ($usesCalles): ?>
<div class="form-group">
    <label for="<?php echo htmlspecialchars($calleSelectId); ?>" class="form-label">Calle (Jurisdicción)<?php echo $domRequired ? ' *' : ''; ?></label>
    <?php if (empty($calles)): ?>
        <div class="alert alert-danger" style="padding: 0.5rem; font-size: 0.75rem; margin-bottom: 0.5rem;">
            No hay calles registradas. Agréguelas desde el botón <strong>Calles</strong>.
        </div>
        <select name="calle_id" id="<?php echo htmlspecialchars($calleSelectId); ?>" class="form-control" disabled <?php echo $domReqAttr; ?>><option value="">-- Cree una calle primero --</option></select>
    <?php else: ?>
        <select name="calle_id" id="<?php echo htmlspecialchars($calleSelectId); ?>" class="form-control cb-calle-select" <?php echo $domReqAttr; ?>>
            <option value="">-- Seleccionar Calle --</option>
            <?php foreach ($calles as $calle): ?>
                <option value="<?php echo (int)$calle->id; ?>"
                        data-lat-centro="<?php echo htmlspecialchars($calle->lat_centro ?? ''); ?>"
                        data-lng-centro="<?php echo htmlspecialchars($calle->lng_centro ?? ''); ?>"
                        <?php echo (($domValues['calle_id'] ?? '') == $calle->id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($calle->nombre); ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
</div>
<div class="form-group">
    <label for="<?php echo htmlspecialchars($numeroInputId); ?>" class="form-label">Número de Casa<?php echo $domRequired ? ' *' : ''; ?></label>
    <input type="text" name="numero_casa" id="<?php echo htmlspecialchars($numeroInputId); ?>" class="form-control"
           value="<?php echo htmlspecialchars($domValues['numero_casa'] ?? ''); ?>" <?php echo $domReqAttr; ?>>
</div>
<?php else: ?>
<div class="form-group">
    <label for="<?php echo htmlspecialchars($direccionInputId); ?>" class="form-label">Dirección<?php echo $domRequired ? ' *' : ''; ?></label>
    <input type="text" name="direccion_texto" id="<?php echo htmlspecialchars($direccionInputId); ?>" class="form-control cb-direccion-texto"
           placeholder="Ej: Av. Providencia 1234, Depto 5"
           value="<?php echo htmlspecialchars($domValues['direccion_texto'] ?? ''); ?>" <?php echo $domReqAttr; ?>>
    <small style="color: var(--text-muted); font-size: 0.72rem;">Comuna de la organización: <strong><?php echo htmlspecialchars($georefComuna); ?></strong></small>
</div>
<?php endif; ?>
<?php
$georefPrefix = $domPrefix;
$calleSelectId = $usesCalles ? $calleSelectId : $direccionInputId;
$numeroInputId = $usesCalles ? $numeroInputId : $direccionInputId;
$georefValues = [
    'latitud' => $domValues['latitud'] ?? '',
    'longitud' => $domValues['longitud'] ?? '',
    'link_google' => $domValues['link_google'] ?? '',
];
$usesFreeText = !$usesCalles;
if (!isset($latSede)) {
    $latSede = $_SESSION['user_junta_lat_sede'] ?? '';
}
if (!isset($lngSede)) {
    $lngSede = $_SESSION['user_junta_lng_sede'] ?? '';
}
require APPROOT . '/views/partials/socio_georef_map.php';
?>
