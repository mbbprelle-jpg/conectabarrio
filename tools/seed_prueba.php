<?php
require_once dirname(__DIR__) . '/app/core/RutChile.php';

function dvBody(string $body): string {
    $rut = $body . '-0';
    for ($i = 0; $i <= 9; $i++) {
        $try = $body . '-' . $i;
        if (RutChile::validate($try)) {
            return RutChile::format($try);
        }
    }
    if (RutChile::validate($body . '-K')) {
        return RutChile::format($body . '-K');
    }
    throw new RuntimeException('Sin DV para ' . $body);
}

$pdo = new PDO('mysql:host=127.0.0.1;dbname=conectabarrio_prueba;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$alters = [
    "ALTER TABLE juntas_vecinos ADD COLUMN tipo VARCHAR(40) NOT NULL DEFAULT 'Junta de Vecinos'",
    "ALTER TABLE juntas_vecinos ADD COLUMN plan VARCHAR(40) NOT NULL DEFAULT 'basico'",
    "ALTER TABLE juntas_vecinos ADD COLUMN precio_anual INT NOT NULL DEFAULT 0",
    "ALTER TABLE juntas_vecinos ADD COLUMN lat_sede DECIMAL(10,7) NULL",
    "ALTER TABLE juntas_vecinos ADD COLUMN lng_sede DECIMAL(10,7) NULL",
    "ALTER TABLE juntas_vecinos ADD COLUMN personalidad_juridica_num VARCHAR(50) NULL",
    "ALTER TABLE usuarios ADD COLUMN apellido_paterno VARCHAR(80) NULL",
    "ALTER TABLE usuarios ADD COLUMN apellido_materno VARCHAR(80) NULL",
    "ALTER TABLE usuario_membresias ADD COLUMN calle_id INT NULL",
    "ALTER TABLE usuario_membresias ADD COLUMN numero_casa VARCHAR(20) NULL",
    "ALTER TABLE usuario_membresias ADD COLUMN direccion_texto VARCHAR(255) NULL",
    "ALTER TABLE usuario_membresias MODIFY cargo ENUM('PRESIDENTE','SECRETARIO','TESORERO','DIRECTOR') NULL",
];
foreach ($alters as $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
    }
}

$rutPresidente = dvBody('16123456');
$rutSocio = dvBody('17123456');
$rutMaria = dvBody('18123456');
$rutHogar = dvBody('19123456');
$rutPublico = dvBody('20123456');

$pdo->exec("UPDATE juntas_vecinos SET
    nombre = 'Junta de Vecinos N° 136 Valle de Peñaflor',
    rut_junta = '65128546-1',
    direccion = 'Valle de Peñaflor',
    comuna = 'Peñaflor',
    personalidad_juridica_num = '03-906-214'
    WHERE id = 1");

$pdo->exec("DELETE FROM calles WHERE junta_id = 1");
$pdo->exec("INSERT INTO calles (junta_id, nombre) VALUES (1, 'Los Aromos'), (1, 'El Valle'), (1, 'Las Palmeras')");
$calle = (int)$pdo->query("SELECT id FROM calles WHERE junta_id = 1 AND nombre = 'Los Aromos'")->fetchColumn();

$pdo->prepare("UPDATE usuarios SET
    nombre = 'Sebastián', apellido_paterno = 'González', apellido_materno = 'Rocuant',
    email = 'presidente@prueba.local', rut = :rut, telefono = '+56911111111'
    WHERE id = 2")->execute(['rut' => $rutPresidente]);

$pdo->prepare("UPDATE usuarios SET
    nombre = 'Camila', apellido_paterno = 'Soto', apellido_materno = 'Rivas',
    email = 'socio@prueba.local', rut = :rut, telefono = '+56922222222',
    calle_id = :calle, numero_casa = '245'
    WHERE id = 3")->execute(['rut' => $rutSocio, 'calle' => $calle]);

$pdo->prepare("UPDATE usuarios SET
    nombre = 'María', apellido_paterno = 'Carrasco', apellido_materno = 'Díaz',
    email = 'maria@prueba.local', rut = :rut
    WHERE id = 4")->execute(['rut' => $rutMaria]);

$pdo->exec("UPDATE usuario_membresias SET cargo = 'PRESIDENTE' WHERE usuario_id = 2 AND junta_id = 1");
$pdo->prepare("UPDATE usuario_membresias SET calle_id = :calle, numero_casa = '245' WHERE usuario_id = 3 AND junta_id = 1")
    ->execute(['calle' => $calle]);

file_put_contents(__DIR__ . '/prueba_cuentas.txt', implode("\n", [
    'base=conectabarrio_prueba',
    'clave=admin123',
    'presidente=' . $rutPresidente . ' presidente@prueba.local',
    'socio=' . $rutSocio . ' socio@prueba.local',
    'maria_socio=' . $rutMaria . ' maria@prueba.local',
    'rut_hogar_no_socio=' . $rutHogar,
    'rut_publico_no_socio=' . $rutPublico,
    'calle_aromos_id=' . $calle,
]) . "\n");

echo "ok\n";
