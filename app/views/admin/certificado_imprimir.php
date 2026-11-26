<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$c = $data['cert'];
$j = $data['junta'];
$f = $data['fecha'];
$comuna = trim((string)($j->comuna ?? ''));
$pj = trim((string)($j->personalidad_juridica_num ?? ''));
$presidente = trim((string)($data['presidente'] ?? ''));
$org = trim((string)($j->nombre ?? 'Junta de Vecinos'));
$direccion = trim((string)$c->direccion);
$pjClave = strtolower(str_replace([' ', '.'], '', $pj));
$orgClave = mb_strtolower($org, 'UTF-8');
$esValle = str_contains($pjClave, '03-906-214')
    || str_contains($orgClave, 'peñaflor')
    || str_contains($orgClave, 'penaflor');
$logoArchivo = dirname(APPROOT) . '/public/img/certificados/jjvv136-logo.png';
$firmaArchivo = dirname(APPROOT) . '/public/img/certificados/jjvv136-firma.png';
$logo = ($esValle && is_file($logoArchivo)) ? URLROOT . '/img/certificados/jjvv136-logo.png' : '';
$firma = ($esValle && is_file($firmaArchivo)) ? URLROOT . '/img/certificados/jjvv136-firma.png' : '';
$cierraComuna = $comuna !== '' && mb_stripos($direccion, $comuna, 0, 'UTF-8') === false;
?>
<div class="cert-pagina">
    <article class="cert-hoja">
        <header class="cert-top">
            <?php if ($logo !== ''): ?>
                <img class="cert-logo" src="<?php echo htmlspecialchars($logo); ?>" alt="<?php echo htmlspecialchars($org); ?>">
            <?php else: ?>
                <div class="cert-org">
                    <strong><?php echo htmlspecialchars($org); ?></strong>
                    <?php if (!empty($j->rut_junta)): ?><div><?php echo htmlspecialchars((string)$j->rut_junta); ?></div><?php endif; ?>
                </div>
            <?php endif; ?>
            <p class="cert-fecha">En <?php echo htmlspecialchars($comuna !== '' ? $comuna : 'la comuna'); ?>, <span class="cert-dato"><?php echo htmlspecialchars((string)$f['dia']); ?> de <?php echo htmlspecialchars((string)$f['mes']); ?></span> de <?php echo htmlspecialchars((string)$f['anio']); ?></p>
        </header>

        <h1>CERTIFICADO DE RESIDENCIA</h1>
        <?php if ($pj !== ''): ?><h2>PERSONALIDAD JURÍDICA <?php echo htmlspecialchars($pj); ?></h2><?php endif; ?>

        <div class="cert-cuerpo">
            <p>Certifica que:</p>
            <p class="cert-campo cert-campo-linea"><span class="cert-etiq">Señor(a):</span> <span class="cert-dato"><?php echo htmlspecialchars((string)$c->beneficiario_nombre); ?></span></p>
            <p class="cert-campo cert-campo-linea"><span class="cert-etiq">RUT o Pasaporte:</span> <span class="cert-dato cert-dato-corto"><?php echo htmlspecialchars((string)$c->beneficiario_rut); ?></span></p>
            <p class="cert-campo cert-campo-linea"><span class="cert-etiq">Mantiene domicilio vigente en:</span> <span class="cert-dato"><?php echo htmlspecialchars($direccion); ?></span><?php if ($cierraComuna): ?><span>comuna de <?php echo htmlspecialchars($comuna); ?>.</span><?php endif; ?></p>
            <p class="cert-campo">Se extiende el presente certificado a solicitud del interesado(a) para ser presentado para: <span class="cert-dato"><?php echo htmlspecialchars((string)$c->motivo); ?></span> para los efectos de acreditar domicilio.</p>
            <p class="cert-validez">La Validez del certificado es de un mes a contar de la fecha de su emisión.</p>
        </div>

        <div class="cert-firma">
            <?php if ($firma !== ''): ?>
                <img class="cert-firma-img" src="<?php echo htmlspecialchars($firma); ?>" alt="Firma y timbre de la presidencia">
            <?php else: ?>
                <div class="cert-firma-linea"></div>
            <?php endif; ?>
            <?php if ($presidente !== ''): ?><div class="cert-firma-nombre"><?php echo htmlspecialchars($presidente); ?></div><?php endif; ?>
            <div>Presidente</div>
            <div><?php echo htmlspecialchars($org); ?></div>
        </div>

        <footer class="cert-pie">
            <div>
                <strong>Nota:</strong> Este documento es válido solo para acreditar residencia.<br>
                Ley N°19.418, Título V, Párrafo 2°, Artículo 43, Inciso 1°, Letra F.
            </div>
            <div class="cert-folio">Folio N° <span class="cert-dato"><?php echo (int)$c->correlativo; ?></span></div>
        </footer>
    </article>
    <p class="cert-acciones">
        <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir</button>
        <a class="btn btn-secondary" href="<?php echo URLROOT; ?>/admin/certificados">Volver</a>
    </p>
</div>
<style>
body.landing-body { background: #d9d9d9 !important; }
.cert-pagina { padding: 1.25rem 1rem 2rem; }
.cert-hoja {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    background: #fff;
    color: #1c1c1c;
    box-sizing: border-box;
    padding: 14mm 16mm 16mm;
    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.12);
    font-family: Calibri, "Segoe UI", Arial, sans-serif;
    font-size: 12.5pt;
    line-height: 1.35;
    display: flex;
    flex-direction: column;
}
.cert-top { display: flex; justify-content: space-between; align-items: flex-end; gap: 1.5rem; min-height: 38mm; }
.cert-logo { width: 48mm; height: auto; display: block; }
.cert-org { font-size: 12pt; line-height: 1.3; }
.cert-fecha { margin: 0 0 2mm; text-align: right; white-space: nowrap; font-size: 12.5pt; }
.cert-fecha .cert-dato { display: inline-block; min-width: 46mm; text-align: center; }
.cert-hoja h1, .cert-hoja h2 {
    text-align: center;
    font-weight: 700;
    letter-spacing: 0.01em;
    margin: 0;
    color: #111;
}
.cert-hoja h1 { font-size: 16.5pt; margin-top: 8mm; }
.cert-hoja h2 { font-size: 14.5pt; margin-top: 1.5mm; margin-bottom: 11mm; }
.cert-cuerpo p { margin: 0 0 4.6mm; }
.cert-etiq { margin-right: 0.15rem; white-space: nowrap; }
.cert-dato { border-bottom: 1px solid #222; padding: 0 0.25rem 0; font-weight: 700; }
.cert-campo-linea { display: flex; align-items: baseline; gap: 0.45rem; }
.cert-campo-linea .cert-dato { flex: 1; }
.cert-campo-linea .cert-dato-corto { flex: 0 1 72mm; }
.cert-validez { font-weight: 700; margin-top: 6mm !important; }
.cert-firma { text-align: center; margin: 8mm auto 0; width: 92mm; line-height: 1.35; }
.cert-firma-img { width: 100%; height: auto; display: block; margin: 0 auto 1mm; }
.cert-firma-linea { border-top: 1px solid #111; margin: 16mm 8mm 2mm; }
.cert-firma-nombre { font-weight: 700; margin-top: 1mm; }
.cert-pie {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 8mm;
    margin-top: auto;
    padding-top: 14mm;
    font-size: 11pt;
}
.cert-folio { white-space: nowrap; font-weight: 700; }
.cert-acciones { width: 210mm; margin: 0.8rem auto 0; display: flex; gap: 0.5rem; }
@media print {
    body.landing-body { background: #fff !important; }
    .cert-pagina { padding: 0; }
    .cert-acciones, .cb-confirm-overlay, .cb-loading-overlay { display: none !important; }
    .cert-hoja { width: auto; min-height: auto; box-shadow: none; padding: 0; }
    @page { size: A4; margin: 12mm 14mm; }
}
</style>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
