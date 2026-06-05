<?php
$georefPrefix = $georefPrefix ?? '';
$calleSelectId = $calleSelectId ?? 'calle_id';
$numeroInputId = $numeroInputId ?? 'numero_casa';
$georefValues = $georefValues ?? [];
$georefComuna = trim((string)($georefComuna ?? ''));
$latitud = $georefValues['latitud'] ?? '';
$longitud = $georefValues['longitud'] ?? '';
$linkGoogle = $georefValues['link_google'] ?? '';
?>
<div class="form-group socio-georef-group" data-socio-georef
     data-prefix="<?php echo htmlspecialchars($georefPrefix); ?>"
     data-calle-select="<?php echo htmlspecialchars($calleSelectId); ?>"
     data-numero-input="<?php echo htmlspecialchars($numeroInputId); ?>"
     data-comuna="<?php echo htmlspecialchars($georefComuna); ?>">
    <label class="form-label">Ubicación en mapa</label>
    <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0 0 0.5rem;">
        Seleccione calle y número; luego ajuste el marcador si la ubicación no es exacta.
    </p>
    <div id="<?php echo htmlspecialchars($georefPrefix); ?>georef_map" class="socio-georef-map" role="region" aria-label="Mapa de ubicación del domicilio"></div>
    <input type="hidden" name="latitud" id="<?php echo htmlspecialchars($georefPrefix); ?>latitud" value="<?php echo htmlspecialchars((string)$latitud); ?>">
    <input type="hidden" name="longitud" id="<?php echo htmlspecialchars($georefPrefix); ?>longitud" value="<?php echo htmlspecialchars((string)$longitud); ?>">
    <input type="hidden" name="link_google" id="<?php echo htmlspecialchars($georefPrefix); ?>link_google" value="<?php echo htmlspecialchars($linkGoogle); ?>">
    <small id="<?php echo htmlspecialchars($georefPrefix); ?>georef_status" class="socio-georef-status" style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 0.35rem;"></small>
</div>
