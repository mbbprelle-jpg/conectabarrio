<?php
class CertificadoResidencia extends Model {

    public const MONTO_SOCIO = 1000;
    public const MONTO_PUBLICO = 2000;
    public const FOLIO_INICIAL = 129;

    private static $ready = null;

    public function ready(): bool {
        if (self::$ready !== null) {
            return self::$ready;
        }
        try {
            if (!$this->tableExists('certificados_residencia')) {
                $this->db->exec("CREATE TABLE certificados_residencia (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    junta_id INT NOT NULL,
                    origen ENUM('socio','publico') NOT NULL,
                    para ENUM('si_mismo','domicilio','externo') NOT NULL,
                    solicitante_usuario_id INT NULL,
                    beneficiario_nombre VARCHAR(200) NOT NULL,
                    beneficiario_rut VARCHAR(20) NOT NULL,
                    calle_id INT NULL,
                    numero_casa VARCHAR(20) NULL,
                    direccion VARCHAR(255) NOT NULL,
                    motivo VARCHAR(500) NOT NULL,
                    es_tarifa_socio TINYINT(1) NOT NULL DEFAULT 0,
                    monto INT NOT NULL,
                    estado ENUM('pendiente','emitido','rechazado') NOT NULL DEFAULT 'pendiente',
                    fecha_certificado DATE NULL,
                    correlativo INT NULL,
                    metodo_pago ENUM('efectivo','transferencia') NULL,
                    revisado_por INT NULL,
                    emitido_at DATETIME NULL,
                    motivo_rechazo VARCHAR(300) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_cert_junta (junta_id, estado),
                    UNIQUE KEY uk_cert_folio (junta_id, correlativo)
                ) ENGINE=InnoDB");
            }
            if (!$this->columnExists('juntas_vecinos', 'folio_certificado')) {
                $this->db->exec('ALTER TABLE juntas_vecinos ADD COLUMN folio_certificado INT NOT NULL DEFAULT ' . self::FOLIO_INICIAL);
            }
            if (!$this->columnExists('juntas_vecinos', 'certificado_token')) {
                $this->db->exec('ALTER TABLE juntas_vecinos ADD COLUMN certificado_token VARCHAR(64) NULL');
            }
        } catch (Throwable $e) {
            self::$ready = false;
            return false;
        }
        self::$ready = $this->tableExists('certificados_residencia')
            && $this->columnExists('juntas_vecinos', 'folio_certificado')
            && $this->columnExists('juntas_vecinos', 'certificado_token');
        return self::$ready;
    }

    public function asegurarToken(int $juntaId): string {
        $this->ready();
        $this->db->query('SELECT certificado_token FROM juntas_vecinos WHERE id = :id LIMIT 1');
        $this->db->bind(':id', $juntaId);
        $row = $this->db->single();
        $token = trim((string)($row->certificado_token ?? ''));
        if ($token !== '') {
            return $token;
        }
        $token = bin2hex(random_bytes(16));
        $this->db->query('UPDATE juntas_vecinos SET certificado_token = :token WHERE id = :id AND (certificado_token IS NULL OR certificado_token = \'\')');
        $this->db->bind(':token', $token);
        $this->db->bind(':id', $juntaId);
        $this->db->execute();
        $this->db->query('SELECT certificado_token FROM juntas_vecinos WHERE id = :id LIMIT 1');
        $this->db->bind(':id', $juntaId);
        $otra = $this->db->single();
        $guardado = trim((string)($otra->certificado_token ?? ''));
        return $guardado !== '' ? $guardado : $token;
    }

    public function juntaPorToken(string $token) {
        if (!$this->ready()) {
            return null;
        }
        $this->db->query('SELECT * FROM juntas_vecinos WHERE certificado_token = :token LIMIT 1');
        $this->db->bind(':token', $token);
        return $this->db->single();
    }

    public function folioActual(int $juntaId): int {
        $this->db->query('SELECT folio_certificado FROM juntas_vecinos WHERE id = :id LIMIT 1');
        $this->db->bind(':id', $juntaId);
        $row = $this->db->single();
        return (int)($row->folio_certificado ?? self::FOLIO_INICIAL);
    }

    public function guardarFolioActual(int $juntaId, int $ultimo): bool {
        if ($ultimo < 0 || $ultimo > 999999) {
            return false;
        }
        $this->db->query('UPDATE juntas_vecinos SET folio_certificado = :folio WHERE id = :id');
        $this->db->bind(':folio', $ultimo);
        $this->db->bind(':id', $juntaId);
        return $this->db->execute();
    }

    public function rutEsSocio(int $juntaId, string $rut): bool {
        require_once APPROOT . '/core/RutChile.php';
        $limpio = RutChile::clean($rut);
        if ($limpio === '') {
            return false;
        }
        $this->db->query("SELECT u.id FROM usuarios u
            INNER JOIN usuario_membresias m ON m.usuario_id = u.id AND m.junta_id = :jid AND m.estado = 1
            WHERE REPLACE(REPLACE(REPLACE(UPPER(u.rut), '.', ''), '-', ''), ' ', '') = :rut
            LIMIT 1");
        $this->db->bind(':jid', $juntaId);
        $this->db->bind(':rut', $limpio);
        return (bool)$this->db->single();
    }

    public function montoPara(bool $esSocio): int {
        return $esSocio ? self::MONTO_SOCIO : self::MONTO_PUBLICO;
    }

    public function crear(array $data): int {
        if (!$this->ready()) {
            return 0;
        }
        $this->db->query('INSERT INTO certificados_residencia
            (junta_id, origen, para, solicitante_usuario_id, beneficiario_nombre, beneficiario_rut,
             calle_id, numero_casa, direccion, motivo, es_tarifa_socio, monto)
            VALUES (:junta, :origen, :para, :solicitante, :nombre, :rut, :calle, :numero, :direccion, :motivo, :tarifa, :monto)');
        $this->db->bind(':junta', (int)$data['junta_id']);
        $this->db->bind(':origen', $data['origen'] === 'publico' ? 'publico' : 'socio');
        $para = (string)($data['para'] ?? 'externo');
        if (!in_array($para, ['si_mismo', 'domicilio', 'externo'], true)) {
            $para = 'externo';
        }
        $this->db->bind(':para', $para);
        $this->db->bind(':solicitante', !empty($data['solicitante_usuario_id']) ? (int)$data['solicitante_usuario_id'] : null);
        $this->db->bind(':nombre', (string)$data['beneficiario_nombre']);
        $this->db->bind(':rut', (string)$data['beneficiario_rut']);
        $this->db->bind(':calle', !empty($data['calle_id']) ? (int)$data['calle_id'] : null);
        $this->db->bind(':numero', ($data['numero_casa'] ?? '') !== '' ? (string)$data['numero_casa'] : null);
        $this->db->bind(':direccion', (string)$data['direccion']);
        $this->db->bind(':motivo', (string)$data['motivo']);
        $this->db->bind(':tarifa', !empty($data['es_tarifa_socio']) ? 1 : 0);
        $this->db->bind(':monto', (int)$data['monto']);
        if (!$this->db->execute()) {
            return 0;
        }
        return (int)$this->db->lastInsertId();
    }

    public function getByIdJunta(int $id, int $juntaId) {
        if (!$this->ready()) {
            return null;
        }
        $this->db->query('SELECT * FROM certificados_residencia WHERE id = :id AND junta_id = :junta LIMIT 1');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta', $juntaId);
        return $this->db->single();
    }

    public function listarJunta(int $juntaId): array {
        if (!$this->ready()) {
            return [];
        }
        $this->db->query("SELECT * FROM certificados_residencia
            WHERE junta_id = :junta
            ORDER BY FIELD(estado, 'pendiente', 'emitido', 'rechazado'), created_at DESC, id DESC");
        $this->db->bind(':junta', $juntaId);
        return $this->db->resultSet() ?: [];
    }

    public function listarDeSocio(int $juntaId, int $usuarioId): array {
        if (!$this->ready()) {
            return [];
        }
        $this->db->query('SELECT * FROM certificados_residencia
            WHERE junta_id = :junta AND solicitante_usuario_id = :uid
            ORDER BY created_at DESC, id DESC');
        $this->db->bind(':junta', $juntaId);
        $this->db->bind(':uid', $usuarioId);
        return $this->db->resultSet() ?: [];
    }

    public function corregir(int $id, int $juntaId, string $nombre, string $rut, string $direccion, string $motivo, bool $esSocio): bool {
        $monto = $this->montoPara($esSocio);
        $this->db->query("UPDATE certificados_residencia
            SET beneficiario_nombre = :nombre, beneficiario_rut = :rut, direccion = :direccion,
                motivo = :motivo, es_tarifa_socio = :tarifa, monto = :monto
            WHERE id = :id AND junta_id = :junta AND estado = 'pendiente'");
        $this->db->bind(':nombre', $nombre);
        $this->db->bind(':rut', $rut);
        $this->db->bind(':direccion', $direccion);
        $this->db->bind(':motivo', $motivo);
        $this->db->bind(':tarifa', $esSocio ? 1 : 0);
        $this->db->bind(':monto', $monto);
        $this->db->bind(':id', $id);
        $this->db->bind(':junta', $juntaId);
        return $this->db->execute();
    }

    public function rechazar(int $id, int $juntaId, int $revisorId, string $nota): bool {
        $this->db->query("UPDATE certificados_residencia
            SET estado = 'rechazado', motivo_rechazo = :nota, revisado_por = :revisor
            WHERE id = :id AND junta_id = :junta AND estado = 'pendiente'");
        $this->db->bind(':nota', $nota !== '' ? $nota : null);
        $this->db->bind(':revisor', $revisorId);
        $this->db->bind(':id', $id);
        $this->db->bind(':junta', $juntaId);
        return $this->db->execute();
    }

    /** Asigna el folio siguiente y marca el certificado como emitido. */
    public function emitir(int $id, int $juntaId, int $revisorId, string $fecha, string $metodo): int {
        $this->db->beginTransaction();
        try {
            $this->db->query("SELECT id FROM certificados_residencia WHERE id = :id AND junta_id = :junta AND estado = 'pendiente' LIMIT 1");
            $this->db->bind(':id', $id);
            $this->db->bind(':junta', $juntaId);
            if (!$this->db->single()) {
                $this->db->rollBack();
                return 0;
            }
            $this->db->query('UPDATE juntas_vecinos SET folio_certificado = folio_certificado + 1 WHERE id = :id');
            $this->db->bind(':id', $juntaId);
            if (!$this->db->execute()) {
                $this->db->rollBack();
                return 0;
            }
            $folio = $this->folioActual($juntaId);
            $this->db->query("UPDATE certificados_residencia
                SET estado = 'emitido', fecha_certificado = :fecha, correlativo = :folio,
                    metodo_pago = :metodo, revisado_por = :revisor, emitido_at = NOW()
                WHERE id = :id AND junta_id = :junta AND estado = 'pendiente'");
            $this->db->bind(':fecha', $fecha);
            $this->db->bind(':folio', $folio);
            $this->db->bind(':metodo', $metodo);
            $this->db->bind(':revisor', $revisorId);
            $this->db->bind(':id', $id);
            $this->db->bind(':junta', $juntaId);
            if (!$this->db->execute()) {
                $this->db->rollBack();
                return 0;
            }
            $this->db->commit();
            return $folio;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return 0;
        }
    }

    public function presidente(int $juntaId): string {
        $this->db->query("SELECT u.nombre, u.apellido_paterno, u.apellido_materno
            FROM usuario_membresias m
            INNER JOIN usuarios u ON u.id = m.usuario_id
            WHERE m.junta_id = :jid AND m.estado = 1
              AND UPPER(REPLACE(m.cargo, ' ', '')) IN ('PRESIDENTE', 'PRESIDENTA')
            ORDER BY m.id ASC
            LIMIT 1");
        $this->db->bind(':jid', $juntaId);
        $row = $this->db->single();
        if (!$row) {
            return '';
        }
        return self::nombreCompleto($row);
    }

    public static function nombreCompleto(object $usuario): string {
        $partes = [
            trim((string)($usuario->nombre ?? '')),
            trim((string)($usuario->apellido_paterno ?? '')),
            trim((string)($usuario->apellido_materno ?? '')),
        ];
        return trim(implode(' ', array_filter($partes, static fn($p) => $p !== '')));
    }

    public static function armarDireccion(string $calle, string $numero): string {
        $calle = trim($calle);
        $numero = trim($numero);
        if ($calle === '') {
            return $numero;
        }
        if ($numero === '') {
            return $calle;
        }
        return $calle . ' N° ' . $numero;
    }

    public static function fechaLarga(string $fecha): array {
        $ts = strtotime($fecha);
        if ($ts === false) {
            $ts = time();
        }
        $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return [
            'dia' => date('j', $ts),
            'mes' => $meses[(int)date('n', $ts)] ?? '',
            'anio' => date('Y', $ts),
        ];
    }

    private function columnExists(string $table, string $column): bool {
        if (!preg_match('/^[a-z_]+$/', $table) || !preg_match('/^[a-z_]+$/', $column)) {
            return false;
        }
        try {
            $this->db->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    private function tableExists(string $table): bool {
        if (!preg_match('/^[a-z_]+$/', $table)) {
            return false;
        }
        try {
            $this->db->query("SHOW TABLES LIKE '{$table}'");
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }
}
