<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php
$c = $data['cert'];
$j = $data['junta'];
$f = $data['fecha'];
$comuna = trim((string)($j->comuna ?? ''));
$pj = trim((string)($j->personalidad_juridica_num ?? ''));
$presidente = trim((string)($data['presidente'] ?? ''));
$org = trim((string)($j->nombre ?? 'Junta de Vecinos'));
?>
<div class="cert-hoja">
    <div class="cert-top">
        <div>
            <strong><?php echo htmlspecialchars($org); ?></strong>
            <?php if (!empty($j->rut_junta)): ?><div class="cert-menor"><?php echo htmlspecialchars((string)$j->rut_junta); ?></div><?php endif; ?>
        </div>
        <div class="cert-fecha">En <?php echo htmlspecialchars($comuna !== '' ? $comuna : 'la comuna'); ?>, <?php echo htmlspecialchars((string)$f['dia']); ?> de <?php echo htmlspecialchars((string)$f['mes']); ?> de <?php echo htmlspecialchars((string)$f['anio']); ?></div>
    </div>
    <h1>CERTIFICADO DE RESIDENCIA</h1>
    <?php if ($pj !== ''): ?><h2>PERSONALIDAD JURÍDICA <?php echo htmlspecialchars($pj); ?></h2><?php endif; ?>

    <p class="cert-linea">Certifica que:</p>
    <p class="cert-campo"><span>Señor(a):</span> <strong><?php echo htmlspecialchars((string)$c->beneficiario_nombre); ?></strong></p>
    <p class="cert-campo"><span>RUT o Pasaporte:</span> <strong><?php echo htmlspecialchars((string)$c->beneficiario_rut); ?></strong></p>
    <p class="cert-campo"><span>Mantiene domicilio vigente en:</span> <strong><?php echo htmlspecialchars((string)$c->direccion); ?></strong><?php if ($comuna !== ''): ?>, comuna de <?php echo htmlspecialchars($comuna); ?>.<?php endif; ?></p>
    <p class="cert-campo">Se extiende el presente certificado a solicitud del interesado(a) para ser presentado para: <strong><?php echo htmlspecialchars((string)$c->motivo); ?></strong>, para los efectos de acreditar domicilio.</p>
    <p class="cert-validez">La validez del certificado es de un mes a contar de la fecha de su emisión.</p>

    <div class="cert-firma">
        <div class="cert-linea-firma"></div>
        <?php if ($presidente !== ''): ?><strong><?php echo htmlspecialchars($presidente); ?></strong><br><?php endif; ?>
        Presidente<br>
        <?php echo htmlspecialchars($org); ?>
        <?php if ($pj !== ''): ?><br>Pers. jurídica: <?php echo htmlspecialchars($pj); ?><?php endif; ?>
        <?php if (!empty($j->rut_junta)): ?><br><?php echo htmlspecialchars((string)$j->rut_junta); ?><?php endif; ?>
    </div>

    <div class="cert-pie">
        <div>
            <strong>Nota:</strong> Este documento es válido solo para acreditar residencia.<br>
            Ley N°19.418, Título V, Párrafo 2°, Artículo 43, Inciso 1°, Letra F.
        </div>
        <div class="cert-folio">Folio N° <?php echo (int)$c->correlativo; ?></div>
    </div>
</div>
<p class="cert-acciones">
    <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir</button>
    <a class="btn btn-secondary" href="<?php echo URLROOT; ?>/admin/certificados">Volver</a>
</p>
<style>
.cert-hoja { max-width: 760px; margin: 1.5rem auto; background: #fff; color: #111; padding: 2.2rem 2.4rem; border: 1px solid #ddd; font-family: "Times New Roman", Times, serif; }
.cert-top { display: flex; justify-content: space-between; gap: 1.5rem; align-items: flex-start; margin-bottom: 2rem; }
.cert-menor { font-size: 0.85rem; }
.cert-fecha { text-align: right; margin-top: 1.2rem; }
.cert-hoja h1, .cert-hoja h2 { text-align: center; font-size: 1.15rem; letter-spacing: 0.02em; margin: 0.2rem 0; }
.cert-hoja h2 { margin-bottom: 1.6rem; }
.cert-campo { margin: 0.7rem 0; line-height: 1.45; }
.cert-campo span { display: inline-block; min-width: 15rem; }
.cert-validez { margin: 1.4rem 0; font-weight: 700; }
.cert-firma { text-align: center; margin: 2.5rem auto 1.5rem; max-width: 320px; line-height: 1.35; }
.cert-linea-firma { border-top: 1px solid #111; margin: 2.2rem 0 0.4rem; }
.cert-pie { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-end; font-size: 0.82rem; margin-top: 2rem; }
.cert-folio { font-weight: 700; white-space: nowrap; }
.cert-acciones { max-width: 760px; margin: 1rem auto; display: flex; gap: 0.5rem; }
@media print {
    .cert-acciones, .menu-toggle, .sidebar, .page-header { display: none !important; }
    body { background: #fff !important; }
    .cert-hoja { border: 0; margin: 0; max-width: none; }
}
</style>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
