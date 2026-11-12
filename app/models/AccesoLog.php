<?php
class AccesoLog extends Model {

    private static bool $tablaLista = false;

    public function ensureTable(): bool {
        if (self::$tablaLista) {
            return true;
        }
        try {
            $ok = $this->db->exec("CREATE TABLE IF NOT EXISTS socio_accesos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                junta_id INT NOT NULL,
                usuario_id INT NOT NULL,
                tipo ENUM('ingreso', 'seccion') NOT NULL,
                seccion VARCHAR(80) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_socio_accesos_junta (junta_id, created_at),
                INDEX idx_socio_accesos_usuario (usuario_id, tipo, created_at)
            ) ENGINE=InnoDB");
            self::$tablaLista = (bool)$ok;
            return self::$tablaLista;
        } catch (Throwable $e) {
            error_log('No se pudo crear socio_accesos: ' . $e->getMessage());
            return false;
        }
    }

    public static function registrarIngreso(): void {
        $rol = $_SESSION['user_rol'] ?? '';
        if ($rol !== 'socio') {
            return;
        }
        self::insertar('ingreso', 'Ingreso');
    }

    public static function registrarVista(string $view): void {
        if (($_SESSION['user_rol'] ?? '') !== 'socio') {
            return;
        }
        if ($view === 'admin/actividad_socios' || str_starts_with($view, 'auth/') || str_starts_with($view, 'layouts/') || str_starts_with($view, 'partials/')) {
            return;
        }
        $seccion = self::seccionDeVista($view);
        if ($seccion === null) {
            return;
        }
        self::insertar('seccion', $seccion);
    }

    private static function insertar(string $tipo, string $seccion): void {
        $usuarioId = (int)($_SESSION['user_id'] ?? 0);
        $juntaId = (int)($_SESSION['user_junta_id'] ?? 0);
        if ($usuarioId <= 0 || $juntaId <= 0) {
            return;
        }
        try {
            $log = new self();
            if (!$log->ensureTable()) {
                return;
            }
            if ($tipo === 'seccion' && $log->visitaReciente($usuarioId, $juntaId, $seccion)) {
                return;
            }
            $log->db->query("INSERT INTO socio_accesos (junta_id, usuario_id, tipo, seccion)
                VALUES (:junta_id, :usuario_id, :tipo, :seccion)");
            $log->db->bind(':junta_id', $juntaId);
            $log->db->bind(':usuario_id', $usuarioId);
            $log->db->bind(':tipo', $tipo);
            $log->db->bind(':seccion', mb_substr($seccion, 0, 80));
            $log->db->execute();
        } catch (Throwable $e) {
            error_log('No se pudo registrar el acceso: ' . $e->getMessage());
        }
    }

    private function visitaReciente(int $usuarioId, int $juntaId, string $seccion): bool {
        $this->db->query("SELECT id FROM socio_accesos
            WHERE usuario_id = :usuario_id AND junta_id = :junta_id AND tipo = 'seccion' AND seccion = :seccion
            AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
            LIMIT 1");
        $this->db->bind(':usuario_id', $usuarioId);
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':seccion', mb_substr($seccion, 0, 80));
        return (bool)$this->db->single();
    }

    public static function seccionDeVista(string $view): ?string {
        $map = [
            'socio/dashboard' => 'Mi Perfil',
            'socio/reuniones' => 'Reuniones',
            'socio/votaciones' => 'Votaciones',
            'socio/votacion_votar' => 'Votaciones',
            'socio/votacion_resultados' => 'Votaciones',
            'socio/solicitar_cambio' => 'Actualizar mis datos',
            'socio/comprobantes' => 'Mis Comprobantes',
            'socio/comprobante' => 'Mis Comprobantes',
            'admin/socios' => 'Gestión de Socios',
            'admin/censo_familiar' => 'Registro Navidad',
            'admin/censo_familiar_fechas' => 'Registro Navidad',
            'admin/mapa_socios' => 'Mapa comunitario',
            'admin/finanzas' => 'Movimientos',
            'admin/reporte_movimientos' => 'Reporte movimientos',
            'admin/traspasar_cuotas' => 'Traspasar cuotas',
            'admin/cuotas_condonar' => 'Exención masiva',
            'admin/flujo_caja' => 'Flujo de Caja',
            'admin/conceptos_caja' => 'Conceptos',
            'admin/cierres' => 'Cierres mensuales',
            'admin/documentos' => 'Documentos',
            'admin/documento_ver' => 'Documentos',
            'admin/calendario' => 'Calendario',
            'admin/documentacion_legal' => 'Doc. legal',
            'admin/votaciones' => 'Gestionar votaciones',
            'admin/votacion_ver' => 'Gestionar votaciones',
            'admin/votacion_votar' => 'Gestionar votaciones',
            'admin/asistencia' => 'Reuniones',
        ];
        if (isset($map[$view])) {
            return $map[$view];
        }
        if (str_starts_with($view, 'socio/')) {
            return 'Mi Perfil';
        }
        return null;
    }

    public function resumenPorSocio(int $juntaId): array {
        if (!$this->ensureTable()) {
            return [];
        }
        $this->db->query("SELECT usuario_id,
                SUM(tipo = 'ingreso') AS ingresos,
                MAX(CASE WHEN tipo = 'ingreso' THEN created_at END) AS ultimo_ingreso,
                SUM(tipo = 'seccion') AS visitas,
                MAX(CASE WHEN tipo = 'seccion' THEN created_at END) AS ultima_visita
            FROM socio_accesos
            WHERE junta_id = :junta_id
            GROUP BY usuario_id");
        $this->db->bind(':junta_id', $juntaId);
        $stats = [];
        foreach ($this->db->resultSet() as $row) {
            $stats[(int)$row->usuario_id] = $row;
        }

        $this->db->query("SELECT a.usuario_id, a.seccion
            FROM socio_accesos a
            INNER JOIN (
                SELECT usuario_id, MAX(id) AS id
                FROM socio_accesos
                WHERE junta_id = :junta_id AND tipo = 'seccion'
                GROUP BY usuario_id
            ) ult ON ult.id = a.id");
        $this->db->bind(':junta_id', $juntaId);
        $ultima = [];
        foreach ($this->db->resultSet() as $row) {
            $ultima[(int)$row->usuario_id] = (string)$row->seccion;
        }

        $this->db->query("SELECT u.id, u.id_socio, u.nombre, u.apellido_paterno, u.apellido_materno, u.rut, u.status
            FROM usuarios u
            WHERE u.junta_id = :junta_id AND u.rol = 'socio' AND u.estado = 1
            AND (u.status IS NULL OR u.status IN ('active', 'prevalidar'))");
        $this->db->bind(':junta_id', $juntaId);
        $lista = [];
        foreach ($this->db->resultSet() as $u) {
            $id = (int)$u->id;
            $st = $stats[$id] ?? null;
            $u->ingresos = (int)($st->ingresos ?? 0);
            $u->ultimo_ingreso = $st->ultimo_ingreso ?? null;
            $u->visitas = (int)($st->visitas ?? 0);
            $u->ultima_visita = $st->ultima_visita ?? null;
            $u->ultima_seccion = $ultima[$id] ?? '';
            $lista[] = $u;
        }
        usort($lista, static function ($a, $b): int {
            $aNunca = empty($a->ultimo_ingreso);
            $bNunca = empty($b->ultimo_ingreso);
            if ($aNunca !== $bNunca) {
                return $aNunca ? -1 : 1;
            }
            return strcmp((string)($b->ultimo_ingreso ?? ''), (string)($a->ultimo_ingreso ?? ''));
        });
        return $lista;
    }

    public function interesPorSeccion(int $juntaId): array {
        if (!$this->ensureTable()) {
            return [];
        }
        $this->db->query("SELECT seccion, COUNT(*) AS visitas, COUNT(DISTINCT usuario_id) AS socios
            FROM socio_accesos
            WHERE junta_id = :junta_id AND tipo = 'seccion'
            GROUP BY seccion
            ORDER BY visitas DESC, seccion ASC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function recientes(int $juntaId, int $limite = 80): array {
        if (!$this->ensureTable()) {
            return [];
        }
        $limite = max(1, min(200, $limite));
        $this->db->query("SELECT a.tipo, a.seccion, a.created_at, u.id_socio, u.nombre, u.apellido_paterno, u.apellido_materno, u.rut
            FROM socio_accesos a
            INNER JOIN usuarios u ON u.id = a.usuario_id
            WHERE a.junta_id = :junta_id
            ORDER BY a.id DESC
            LIMIT {$limite}");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }
}
