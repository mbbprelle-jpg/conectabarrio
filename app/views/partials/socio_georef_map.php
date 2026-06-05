<?php
$georefPrefix = $georefPrefix ?? '';
$calleSelectId = $calleSelectId ?? 'calle_id';
$numeroInputId = $numeroInputId ?? 'numero_casa';
$georefValues = $georefValues ?? [];
$georefComuna = trim((string)($georefComuna ?? ''));
$latSede = $latSede ?? ($_SESSION['user_junta_lat_sede'] ?? '');
$lngSede = $lngSede ?? ($_SESSION['user_junta_lng_sede'] ?? '');
$usesFreeText = !empty($usesFreeText);
$latitud = $georefValues['latitud'] ?? '';
$longitud = $georefValues['longitud'] ?? '';
$linkGoogle = $georefValues['link_google'] ?? '';
?>
<div class="form-group socio-georef-group" data-socio-georef
     data-prefix="<?php echo htmlspecialchars($georefPrefix); ?>"
     data-calle-select="<?php echo htmlspecialchars($calleSelectId); ?>"
     data-numero-input="<?php echo htmlspecialchars($numeroInputId); ?>"
     data-comuna="<?php echo htmlspecialchars($georefComuna); ?>"
     data-lat-sede="<?php echo htmlspecialchars((string)$latSede); ?>"
     data-lng-sede="<?php echo htmlspecialchars((string)$lngSede); ?>"
     data-free-text="<?php echo $usesFreeText ? '1' : '0'; ?>">
    <label class="form-label">Ubicación en mapa</label>
    <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0 0 0.5rem;">
        <?php if ($usesFreeText): ?>
            Escriba su dirección y haga clic en el mapa para marcar el punto exacto.
        <?php else: ?>
            Seleccione calle y número; luego haga clic en el mapa para marcar el punto exacto.
        <?php endif; ?>
    </p>
    <div id="<?php echo htmlspecialchars($georefPrefix); ?>georef_map" class="socio-georef-map" role="region" aria-label="Mapa de ubicación del domicilio"></div>
    <div id="<?php echo htmlspecialchars($georefPrefix); ?>georef_coords" class="socio-georef-coords" aria-live="polite">
        <span class="socio-georef-coords-label">Coordenadas del pin:</span>
        <span id="<?php echo htmlspecialchars($georefPrefix); ?>georef_coords_text" class="socio-georef-coords-value">
            <?php if ($latitud !== '' && $longitud !== ''): ?>
                <?php echo htmlspecialchars((string)$latitud); ?>, <?php echo htmlspecialchars((string)$longitud); ?>
            <?php else: ?>
                Sin ubicación — seleccione dirección o haga clic en el mapa
            <?php endif; ?>
        </span>
        <?php if ($linkGoogle !== ''): ?>
            <a id="<?php echo htmlspecialchars($georefPrefix); ?>georef_coords_link" class="socio-georef-coords-link" href="<?php echo htmlspecialchars($linkGoogle); ?>" target="_blank" rel="noopener noreferrer">Ver en mapa</a>
        <?php else: ?>
            <a id="<?php echo htmlspecialchars($georefPrefix); ?>georef_coords_link" class="socio-georef-coords-link" href="#" target="_blank" rel="noopener noreferrer" style="display: none;">Ver en mapa</a>
        <?php endif; ?>
    </div>
    <input type="hidden" name="latitud" id="<?php echo htmlspecialchars($georefPrefix); ?>latitud" value="<?php echo htmlspecialchars((string)$latitud); ?>">
    <input type="hidden" name="longitud" id="<?php echo htmlspecialchars($georefPrefix); ?>longitud" value="<?php echo htmlspecialchars((string)$longitud); ?>">
    <input type="hidden" name="link_google" id="<?php echo htmlspecialchars($georefPrefix); ?>link_google" value="<?php echo htmlspecialchars($linkGoogle); ?>">
    <small id="<?php echo htmlspecialchars($georefPrefix); ?>georef_status" class="socio-georef-status" style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 0.35rem;"></small>
</div>
