<?php
require dirname(__DIR__) . '/app/config/config.php';
require_once APPROOT . '/core/Database.php';
require_once APPROOT . '/core/Model.php';
require_once APPROOT . '/models/CertificadoResidencia.php';

$model = new CertificadoResidencia();
$token = $model->asegurarToken(1);
$folio = $model->folioActual(1);
file_put_contents(__DIR__ . '/prueba_token.txt', $token . "\nfolio_ultimo=" . $folio . "\n");
echo "token=$token folio=$folio\n";
