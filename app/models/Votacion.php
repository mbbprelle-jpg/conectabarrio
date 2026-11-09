<?php
class Votacion extends Model {

    private static $tablesReady = null;
    private static $schemaReady = null;
    private static $ensureAttempted = false;

    public function tablesExist(): bool {
        if (self::$tablesReady === null) {
            try {
                $this->db->query('SELECT id FROM votaciones LIMIT 1');
                $this->db->execute();
                self::$tablesReady = true;
            } catch (Exception $e) {
                self::$tablesReady = false;
            }
        }
        return self::$tablesReady;
    }

    public function ensureSeguimiento(): bool {
        if (!$this->tablesExist()) {
            return false;
        }
        if ($this->schemaReady()) {
            return true;
        }
        if (self::$ensureAttempted) {
            return false;
        }
        self::$ensureAttempted = true;
        try {
            if (!$this->columnExists('votacion_opciones', 'seguimiento_tipo')) {
                $this->db->query("ALTER TABLE votacion_opciones
                    ADD COLUMN seguimiento_pregunta VARCHAR(500) NULL AFTER orden,
                    ADD COLUMN seguimiento_tipo ENUM('ninguno','texto','opciones') NOT NULL DEFAULT 'ninguno' AFTER seguimiento_pregunta");
                $this->db->execute();
            }
            if (!$this->tableExists('votacion_seguimiento_opciones')) {
                $this->db->query("CREATE TABLE votacion_seguimiento_opciones (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    opcion_id INT NOT NULL,
                    texto VARCHAR(500) NOT NULL,
                    orden INT NOT NULL DEFAULT 0,
                    KEY idx_seg_opcion (opcion_id),
                    CONSTRAINT fk_seg_opcion FOREIGN KEY (opcion_id) REFERENCES votacion_opciones(id) ON DELETE CASCADE
                ) ENGINE=InnoDB");
                $this->db->execute();
            }
            if (!$this->columnExists('votacion_respuestas', 'seguimiento_texto')) {
                $this->db->query('ALTER TABLE votacion_respuestas ADD COLUMN seguimiento_texto TEXT NULL AFTER respuesta_texto');
                $this->db->execute();
            }
            if (!$this->columnExists('votacion_respuestas', 'seguimiento_opcion_id')) {
                $this->db->query('ALTER TABLE votacion_respuestas ADD COLUMN seguimiento_opcion_id INT NULL AFTER seguimiento_texto');
                $this->db->execute();
            }
            if (!$this->visibilidadSupportsAlVotar()) {
                $this->db->query("ALTER TABLE votaciones MODIFY COLUMN resultados_visibilidad ENUM('directiva','todos','al_votar') NOT NULL DEFAULT 'directiva'");
                $this->db->execute();
            }
            if (!$this->tituloPermitePreguntaLarga()) {
                $this->db->query('ALTER TABLE votaciones MODIFY COLUMN titulo VARCHAR(500) NOT NULL');
                $this->db->execute();
            }
            try {
                $this->db->query('ALTER TABLE votacion_respuestas ADD CONSTRAINT fk_resp_seguimiento FOREIGN KEY (seguimiento_opcion_id) REFERENCES votacion_seguimiento_opciones(id) ON DELETE SET NULL');
                $this->db->execute();
            } catch (Throwable $e) {
                // La restricción ya existe.
            }
        } catch (Throwable $e) {
            return $this->schemaReady();
        }
        self::$schemaReady = null;
        return $this->schemaReady();
    }

    private function schemaReady(): bool {
        if (self::$schemaReady === true) {
            return true;
        }
        if (!$this->tablesExist()) {
            return false;
        }
        $ok = $this->columnExists('votacion_opciones', 'seguimiento_tipo')
            && $this->columnExists('votacion_respuestas', 'seguimiento_texto')
            && $this->columnExists('votacion_respuestas', 'seguimiento_opcion_id')
            && $this->tableExists('votacion_seguimiento_opciones')
            && $this->visibilidadSupportsAlVotar()
            && $this->tituloPermitePreguntaLarga();
        if ($ok) {
            self::$schemaReady = true;
        }
        return $ok;
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

    public function tituloMaxLength(): int {
        try {
            $this->db->query("SHOW COLUMNS FROM votaciones LIKE 'titulo'");
            $col = $this->db->single();
            $type = strtolower((string)($col->Type ?? ''));
            if (str_contains($type, 'text')) {
                return 5000;
            }
            if (preg_match('/varchar\((\d+)\)/', $type, $m)) {
                return (int)$m[1];
            }
        } catch (Throwable $e) {
            return 200;
        }
        return 200;
    }

    private function tituloPermitePreguntaLarga(): bool {
        return $this->tituloMaxLength() >= 500;
    }

    private function visibilidadSupportsAlVotar(): bool {
        try {
            $this->db->query("SHOW COLUMNS FROM votaciones LIKE 'resultados_visibilidad'");
            $col = $this->db->single();
            $type = strtolower((string)($col->Type ?? ''));
            return str_contains($type, 'al_votar');
        } catch (Throwable $e) {
            return false;
        }
    }

    public function generateToken(): string {
        return bin2hex(random_bytes(24));
    }

    public function create(array $data): ?int {
        if (!$this->tablesExist()) {
            return null;
        }
        $this->ensureSeguimiento();
        if (($data['resultados_visibilidad'] ?? '') === 'al_votar' && !$this->schemaReady()) {
            $data['resultados_visibilidad'] = 'directiva';
        }
        $token = $data['token_publico'] ?? $this->generateToken();
        $this->db->query('INSERT INTO votaciones
            (junta_id, titulo, descripcion, tipo, creado_por, token_publico, audiencia_tipo,
             fecha_inicio, fecha_fin, resultados_visibilidad, estado)
            VALUES (:junta_id, :titulo, :descripcion, :tipo, :creado_por, :token_publico, :audiencia_tipo,
                    :fecha_inicio, :fecha_fin, :resultados_visibilidad, :estado)');
        $this->db->bind(':junta_id', $data['junta_id']);
        $this->db->bind(':titulo', $data['titulo']);
        $this->db->bind(':descripcion', $data['descripcion'] ?? null);
        $this->db->bind(':tipo', $data['tipo'] ?? 'votacion');
        $this->db->bind(':creado_por', $data['creado_por']);
        $this->db->bind(':token_publico', $token);
        $this->db->bind(':audiencia_tipo', $data['audiencia_tipo']);
        $this->db->bind(':fecha_inicio', $data['fecha_inicio']);
        $this->db->bind(':fecha_fin', $data['fecha_fin']);
        $this->db->bind(':resultados_visibilidad', $data['resultados_visibilidad'] ?? 'directiva');
        $this->db->bind(':estado', $data['estado'] ?? 'borrador');
        if (!$this->db->execute()) {
            return null;
        }
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $juntaId, array $data): bool {
        if (!$this->tablesExist()) {
            return false;
        }
        $this->ensureSeguimiento();
        if (($data['resultados_visibilidad'] ?? '') === 'al_votar' && !$this->schemaReady()) {
            $data['resultados_visibilidad'] = 'directiva';
        }
        $this->db->query('UPDATE votaciones SET titulo = :titulo, descripcion = :descripcion, tipo = :tipo,
            audiencia_tipo = :audiencia_tipo, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin,
            resultados_visibilidad = :resultados_visibilidad
            WHERE id = :id AND junta_id = :junta_id AND estado IN (\'borrador\', \'activa\', \'cerrada\')');
        $this->db->bind(':titulo', $data['titulo']);
        $this->db->bind(':descripcion', $data['descripcion'] ?? null);
        $this->db->bind(':tipo', $data['tipo'] ?? 'votacion');
        $this->db->bind(':audiencia_tipo', $data['audiencia_tipo']);
        $this->db->bind(':fecha_inicio', $data['fecha_inicio']);
        $this->db->bind(':fecha_fin', $data['fecha_fin']);
        $this->db->bind(':resultados_visibilidad', $data['resultados_visibilidad'] ?? 'directiva');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function updateEstado(int $id, int $juntaId, string $estado): bool {
        $this->db->query('UPDATE votaciones SET estado = :estado WHERE id = :id AND junta_id = :junta_id');
        $this->db->bind(':estado', $estado);
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function getByIdAndJunta(int $id, int $juntaId) {
        $this->db->query('SELECT v.*, u.nombre AS creador_nombre
            FROM votaciones v
            INNER JOIN usuarios u ON u.id = v.creado_por
            WHERE v.id = :id AND v.junta_id = :junta_id LIMIT 1');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function getByToken(string $token) {
        $this->db->query('SELECT v.*, j.nombre AS junta_nombre
            FROM votaciones v
            INNER JOIN juntas_vecinos j ON j.id = v.junta_id
            WHERE v.token_publico = :token LIMIT 1');
        $this->db->bind(':token', $token);
        return $this->db->single();
    }

    public function getByJunta(int $juntaId): array {
        if (!$this->tablesExist()) {
            return [];
        }
        $this->db->query('SELECT v.*, u.nombre AS creador_nombre,
            (SELECT COUNT(*) FROM votacion_respuestas WHERE votacion_id = v.id) AS total_votos
            FROM votaciones v
            INNER JOIN usuarios u ON u.id = v.creado_por
            WHERE v.junta_id = :junta_id
            ORDER BY v.fecha_inicio DESC');
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function syncEstadosActivas(): void {
        if (!$this->tablesExist()) {
            return;
        }
        $this->db->query("UPDATE votaciones SET estado = 'cerrada'
            WHERE estado IN ('borrador', 'activa') AND fecha_fin < NOW()");
        $this->db->execute();
        $this->db->query("UPDATE votaciones SET estado = 'activa'
            WHERE estado IN ('borrador', 'cerrada') AND fecha_inicio <= NOW() AND fecha_fin >= NOW()");
        $this->db->execute();
    }

    public function actualizarHorario(int $id, int $juntaId, string $fechaInicio, string $fechaFin): bool {
        $this->db->query('UPDATE votaciones
            SET fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin
            WHERE id = :id AND junta_id = :junta_id AND estado IN (\'borrador\', \'activa\', \'cerrada\')');
        $this->db->bind(':fecha_inicio', $fechaInicio);
        $this->db->bind(':fecha_fin', $fechaFin);
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    /** Cierre manual: deja la hora de término en este instante para que no se reabra sola. */
    public function cerrarAhora(int $id, int $juntaId): bool {
        $this->db->query("UPDATE votaciones
            SET estado = 'cerrada', fecha_fin = DATE_SUB(NOW(), INTERVAL 1 SECOND)
            WHERE id = :id AND junta_id = :junta_id AND estado IN ('borrador', 'activa')");
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function isActiva(object $v): bool {
        if (($v->estado ?? '') === 'cancelada') {
            return false;
        }
        $now = time();
        $inicio = strtotime((string)$v->fecha_inicio);
        $fin = strtotime((string)$v->fecha_fin);
        if ($inicio === false || $fin === false) {
            return false;
        }
        return $inicio <= $now && $fin >= $now
            && in_array($v->estado, ['activa', 'borrador', 'cerrada'], true);
    }

    public function canUserParticipate(object $v, int $usuarioId, int $juntaId): bool {
        if ((int)$v->junta_id !== $juntaId || !$this->isActiva($v)) {
            return false;
        }
        if ($this->hasUserVoted((int)$v->id, $usuarioId)) {
            return false;
        }
        return $this->isUserElector((int)$v->id, $usuarioId, $v->audiencia_tipo, $juntaId);
    }

    public function isUserElector(int $votacionId, int $usuarioId, string $audienciaTipo, int $juntaId): bool {
        if ($audienciaTipo === 'todos_socios') {
            return $this->userIsActiveMember($usuarioId, $juntaId);
        }
        if ($audienciaTipo === 'directiva') {
            return $this->userIsDirectiva($usuarioId, $juntaId);
        }
        $this->db->query('SELECT id FROM votacion_electores WHERE votacion_id = :vid AND usuario_id = :uid LIMIT 1');
        $this->db->bind(':vid', $votacionId);
        $this->db->bind(':uid', $usuarioId);
        return (bool)$this->db->single();
    }

    /** Electores que todavía no registran respuesta. */
    public function getElectoresSinVotar(int $votacionId, int $juntaId, string $audienciaTipo): array {
        $pendiente = 'AND NOT EXISTS (
            SELECT 1 FROM votacion_respuestas r
            WHERE r.votacion_id = :vid AND r.usuario_id = u.id
        )';
        if ($audienciaTipo === 'seleccionados') {
            $sql = "SELECT u.id, u.nombre, u.email, u.estado, u.status
                FROM votacion_electores e
                INNER JOIN usuarios u ON u.id = e.usuario_id
                INNER JOIN usuario_membresias m ON m.usuario_id = u.id AND m.junta_id = :jid AND m.estado = 1
                WHERE e.votacion_id = :vid2 {$pendiente}
                ORDER BY u.nombre ASC";
        } elseif ($audienciaTipo === 'directiva') {
            $sql = "SELECT u.id, u.nombre, u.email, u.estado, u.status
                FROM usuario_membresias m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                WHERE m.junta_id = :jid AND m.estado = 1
                AND (m.rol = 'admin' OR m.permiso_todos = 1 OR m.cargo IN ('SECRETARIO','TESORERO','DIRECTOR'))
                {$pendiente}
                ORDER BY u.nombre ASC";
        } else {
            $sql = "SELECT u.id, u.nombre, u.email, u.estado, u.status
                FROM usuario_membresias m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                WHERE m.junta_id = :jid AND m.estado = 1 AND u.rol != 'maestro'
                {$pendiente}
                ORDER BY u.nombre ASC";
        }
        $this->db->query($sql);
        $this->db->bind(':jid', $juntaId);
        $this->db->bind(':vid', $votacionId);
        if ($audienciaTipo === 'seleccionados') {
            $this->db->bind(':vid2', $votacionId);
        }
        return $this->db->resultSet();
    }

    private function userIsActiveMember(int $usuarioId, int $juntaId): bool {
        $this->db->query('SELECT id FROM usuario_membresias
            WHERE usuario_id = :uid AND junta_id = :jid AND estado = 1 LIMIT 1');
        $this->db->bind(':uid', $usuarioId);
        $this->db->bind(':jid', $juntaId);
        return (bool)$this->db->single();
    }

    private function userIsDirectiva(int $usuarioId, int $juntaId): bool {
        $this->db->query("SELECT id FROM usuario_membresias
            WHERE usuario_id = :uid AND junta_id = :jid AND estado = 1
            AND (rol = 'admin' OR permiso_todos = 1 OR cargo IN ('SECRETARIO','TESORERO','DIRECTOR'))
            LIMIT 1");
        $this->db->bind(':uid', $usuarioId);
        $this->db->bind(':jid', $juntaId);
        return (bool)$this->db->single();
    }

    public function hasUserVoted(int $votacionId, int $usuarioId): bool {
        $this->db->query('SELECT id FROM votacion_respuestas WHERE votacion_id = :vid AND usuario_id = :uid LIMIT 1');
        $this->db->bind(':vid', $votacionId);
        $this->db->bind(':uid', $usuarioId);
        return (bool)$this->db->single();
    }

    public function getActivasForUsuario(int $juntaId, int $usuarioId): array {
        if (!$this->tablesExist()) {
            return [];
        }
        $this->syncEstadosActivas();
        $all = $this->getByJunta($juntaId);
        $pending = [];
        foreach ($all as $v) {
            if ($this->canUserParticipate($v, $usuarioId, $juntaId)) {
                $pending[] = $v;
            }
        }
        return $pending;
    }

    public function canViewResults(object $v, int $usuarioId, int $juntaId, bool $isFullAdmin, bool $isDirectiva): bool {
        if ((int)$v->junta_id !== $juntaId) {
            return false;
        }
        if ($isFullAdmin || (int)$v->creado_por === $usuarioId) {
            return true;
        }
        if ($v->resultados_visibilidad === 'todos' && in_array($v->estado, ['activa', 'cerrada'], true)) {
            return $this->isUserElector((int)$v->id, $usuarioId, $v->audiencia_tipo, $juntaId);
        }
        if ($v->resultados_visibilidad === 'al_votar' && in_array($v->estado, ['activa', 'cerrada'], true)) {
            return $this->hasUserVoted((int)$v->id, $usuarioId)
                && $this->isUserElector((int)$v->id, $usuarioId, $v->audiencia_tipo, $juntaId);
        }
        if ($v->resultados_visibilidad === 'directiva' && $isDirectiva) {
            return in_array($v->estado, ['activa', 'cerrada'], true);
        }
        return false;
    }

    public function canViewVoterDetail(object $v, int $usuarioId, bool $isFullAdmin): bool {
        return $isFullAdmin || (int)$v->creado_por === $usuarioId;
    }

    /**
     * Registra la respuesta principal y, si la opción lo pide, la pregunta de seguimiento.
     * Devuelve null si quedó guardada, o un mensaje de error.
     */
    public function submitVote(object $v, int $usuarioId, int $juntaId, array $post): ?string {
        $this->ensureSeguimiento();
        if (!$this->canUserParticipate($v, $usuarioId, $juntaId)) {
            return 'No puede votar en esta consulta.';
        }
        $opcionId = (int)($post['opcion_id'] ?? 0);
        $texto = trim((string)($post['respuesta_texto'] ?? ''));
        $opciones = $this->getOpcionesConSeguimiento((int)$v->id);
        $chosen = null;
        foreach ($opciones as $o) {
            if ((int)$o->id === $opcionId) {
                $chosen = $o;
                break;
            }
        }
        if ($v->tipo === 'votacion' && !$chosen) {
            return 'Seleccione una opción.';
        }
        if ($v->tipo === 'encuesta' && !$chosen && $texto === '') {
            return 'Indique su respuesta.';
        }
        if ($opcionId > 0 && !$chosen) {
            return 'La opción elegida no pertenece a esta consulta.';
        }

        $segTexto = null;
        $segOpcionId = null;
        $tipoSeg = (string)($chosen->seguimiento_tipo ?? 'ninguno');
        if ($chosen && $tipoSeg !== 'ninguno' && $tipoSeg !== '') {
            if ($tipoSeg === 'texto') {
                $segFuente = $post['seguimiento_texto'] ?? '';
                $segTexto = trim((string)(is_array($segFuente) ? ($segFuente[$opcionId] ?? '') : $segFuente));
                if ($segTexto === '') {
                    return 'Responda la pregunta adicional.';
                }
            } else {
                $segFuente = $post['seguimiento_opcion_id'] ?? 0;
                $segOpcionId = (int)(is_array($segFuente) ? ($segFuente[$opcionId] ?? 0) : $segFuente);
                $valid = false;
                foreach ($chosen->seguimiento_opciones ?? [] as $s) {
                    if ((int)$s->id === $segOpcionId) {
                        $valid = true;
                        break;
                    }
                }
                if (!$valid) {
                    return 'Seleccione una alternativa de la pregunta adicional.';
                }
            }
        }

        try {
            if ($this->schemaReady()) {
                $this->db->query('INSERT INTO votacion_respuestas
                    (votacion_id, usuario_id, opcion_id, respuesta_texto, seguimiento_texto, seguimiento_opcion_id)
                    VALUES (:vid, :uid, :oid, :txt, :stxt, :soid)');
                $this->db->bind(':stxt', $segTexto !== null && $segTexto !== '' ? $segTexto : null);
                $this->db->bind(':soid', $segOpcionId > 0 ? $segOpcionId : null);
            } else {
                $this->db->query('INSERT INTO votacion_respuestas (votacion_id, usuario_id, opcion_id, respuesta_texto)
                    VALUES (:vid, :uid, :oid, :txt)');
            }
            $this->db->bind(':vid', (int)$v->id);
            $this->db->bind(':uid', $usuarioId);
            $this->db->bind(':oid', $opcionId > 0 ? $opcionId : null);
            $this->db->bind(':txt', $texto !== '' ? $texto : null);
            $this->db->execute();
        } catch (Throwable $e) {
            return 'Ya registró su participación o no se pudo guardar la respuesta.';
        }
        return null;
    }

    public function getResultadosPayload(int $votacionId): array {
        $this->ensureSeguimiento();
        $this->db->query('SELECT COUNT(*) AS total FROM votacion_respuestas WHERE votacion_id = :vid');
        $this->db->bind(':vid', $votacionId);
        $total = (int)($this->db->single()->total ?? 0);

        $this->db->query('SELECT COUNT(*) AS total FROM votacion_respuestas WHERE votacion_id = :vid AND opcion_id IS NULL');
        $this->db->bind(':vid', $votacionId);
        $sinOpcion = (int)($this->db->single()->total ?? 0);

        $seguimiento = $this->schemaReady();
        if ($seguimiento) {
            $this->db->query('SELECT o.id, o.texto, o.orden, o.seguimiento_pregunta, o.seguimiento_tipo,
                (SELECT COUNT(*) FROM votacion_respuestas r WHERE r.opcion_id = o.id) AS votos,
                (SELECT COUNT(*) FROM votacion_respuestas r
                    WHERE r.opcion_id = o.id AND r.seguimiento_texto IS NOT NULL AND TRIM(r.seguimiento_texto) <> \'\') AS textos
                FROM votacion_opciones o
                WHERE o.votacion_id = :vid
                ORDER BY o.orden ASC, o.id ASC');
        } else {
            $this->db->query('SELECT o.id, o.texto, o.orden,
                (SELECT COUNT(*) FROM votacion_respuestas r WHERE r.opcion_id = o.id) AS votos
                FROM votacion_opciones o
                WHERE o.votacion_id = :vid
                ORDER BY o.orden ASC, o.id ASC');
        }
        $this->db->bind(':vid', $votacionId);
        $ops = $this->db->resultSet();

        $segByOpcion = [];
        if ($seguimiento) {
            $this->db->query('SELECT s.id, s.opcion_id, s.texto, s.orden,
                (SELECT COUNT(*) FROM votacion_respuestas r WHERE r.seguimiento_opcion_id = s.id) AS votos
                FROM votacion_seguimiento_opciones s
                INNER JOIN votacion_opciones o ON o.id = s.opcion_id
                WHERE o.votacion_id = :vid
                ORDER BY s.orden ASC, s.id ASC');
            $this->db->bind(':vid', $votacionId);
            foreach ($this->db->resultSet() as $s) {
                $segByOpcion[(int)$s->opcion_id][] = $s;
            }
        }

        $opciones = [];
        foreach ($ops as $o) {
            $votos = (int)$o->votos;
            $items = [];
            foreach ($segByOpcion[(int)$o->id] ?? [] as $s) {
                $sv = (int)$s->votos;
                $items[] = [
                    'texto' => (string)$s->texto,
                    'votos' => $sv,
                    'pct' => $votos > 0 ? (int)round($sv * 100 / $votos) : 0,
                ];
            }
            $opciones[] = [
                'texto' => (string)$o->texto,
                'votos' => $votos,
                'pct' => $total > 0 ? (int)round($votos * 100 / $total) : 0,
                'pregunta' => (string)($o->seguimiento_pregunta ?? ''),
                'tipo' => (string)($o->seguimiento_tipo ?? 'ninguno'),
                'items' => $items,
                'textos' => (int)($o->textos ?? 0),
            ];
        }

        return [
            'total' => $total,
            'sin_opcion' => $sinOpcion,
            'opciones' => $opciones,
            'actualizado' => date('H:i:s'),
        ];
    }

    public function getResultadosAgregados(int $votacionId): array {
        $this->db->query('SELECT o.id, o.texto, o.orden,
            (SELECT COUNT(*) FROM votacion_respuestas r WHERE r.opcion_id = o.id) AS votos
            FROM votacion_opciones o
            WHERE o.votacion_id = :vid
            ORDER BY o.orden ASC, o.id ASC');
        $this->db->bind(':vid', $votacionId);
        return $this->db->resultSet();
    }

    public function getDetalleVotantes(int $votacionId, int $juntaId): array {
        $this->ensureSeguimiento();
        if ($this->schemaReady()) {
            $this->db->query('SELECT r.created_at, u.nombre, u.apellido_paterno, u.apellido_materno, u.rut, u.email, o.texto AS opcion_texto, r.respuesta_texto,
                o.seguimiento_pregunta, r.seguimiento_texto, s.texto AS seguimiento_opcion_texto
                FROM votacion_respuestas r
                INNER JOIN usuarios u ON u.id = r.usuario_id
                LEFT JOIN votacion_opciones o ON o.id = r.opcion_id
                LEFT JOIN votacion_seguimiento_opciones s ON s.id = r.seguimiento_opcion_id
                INNER JOIN usuario_membresias m ON m.usuario_id = u.id AND m.junta_id = :jid AND m.estado = 1
                WHERE r.votacion_id = :vid
                ORDER BY r.created_at DESC');
        } else {
            $this->db->query('SELECT r.created_at, u.nombre, u.apellido_paterno, u.apellido_materno, u.rut, u.email, o.texto AS opcion_texto, r.respuesta_texto,
                NULL AS seguimiento_pregunta, NULL AS seguimiento_texto, NULL AS seguimiento_opcion_texto
                FROM votacion_respuestas r
                INNER JOIN usuarios u ON u.id = r.usuario_id
                LEFT JOIN votacion_opciones o ON o.id = r.opcion_id
                INNER JOIN usuario_membresias m ON m.usuario_id = u.id AND m.junta_id = :jid AND m.estado = 1
                WHERE r.votacion_id = :vid
                ORDER BY r.created_at DESC');
        }
        $this->db->bind(':vid', $votacionId);
        $this->db->bind(':jid', $juntaId);
        $rows = $this->db->resultSet();
        foreach ($rows as $row) {
            $row->nombre_completo = self::nombreYApellidos($row);
        }
        return $rows;
    }

    /** Nombres (columna usuarios.nombre) + apellido paterno + apellido materno. */
    public static function nombreYApellidos(object $usuario): string {
        $partes = [
            trim((string)($usuario->nombre ?? '')),
            trim((string)($usuario->apellido_paterno ?? '')),
            trim((string)($usuario->apellido_materno ?? '')),
        ];
        return implode(' ', array_filter($partes, static fn(string $p): bool => $p !== ''));
    }

    public function replaceOpciones(int $votacionId, array $textos): void {
        $this->db->query('DELETE FROM votacion_opciones WHERE votacion_id = :vid');
        $this->db->bind(':vid', $votacionId);
        $this->db->execute();
        $orden = 0;
        foreach ($textos as $texto) {
            $texto = trim((string)$texto);
            if ($texto === '') {
                continue;
            }
            $this->db->query('INSERT INTO votacion_opciones (votacion_id, texto, orden) VALUES (:vid, :txt, :ord)');
            $this->db->bind(':vid', $votacionId);
            $this->db->bind(':txt', $texto);
            $this->db->bind(':ord', $orden++);
            $this->db->execute();
        }
    }

    public function replaceElectores(int $votacionId, array $usuarioIds): void {
        $this->db->query('DELETE FROM votacion_electores WHERE votacion_id = :vid');
        $this->db->bind(':vid', $votacionId);
        $this->db->execute();
        foreach (array_unique(array_map('intval', $usuarioIds)) as $uid) {
            if ($uid <= 0) {
                continue;
            }
            $this->db->query('INSERT INTO votacion_electores (votacion_id, usuario_id) VALUES (:vid, :uid)');
            $this->db->bind(':vid', $votacionId);
            $this->db->bind(':uid', $uid);
            $this->db->execute();
        }
    }

    public function getOpciones(int $votacionId): array {
        $this->db->query('SELECT * FROM votacion_opciones WHERE votacion_id = :vid ORDER BY orden ASC, id ASC');
        $this->db->bind(':vid', $votacionId);
        return $this->db->resultSet();
    }

    public function getOpcionesConSeguimiento(int $votacionId): array {
        $this->ensureSeguimiento();
        $ops = $this->getOpciones($votacionId);
        foreach ($ops as $o) {
            $o->seguimiento_tipo = $o->seguimiento_tipo ?? 'ninguno';
            $o->seguimiento_pregunta = $o->seguimiento_pregunta ?? '';
            $o->seguimiento_opciones = ($this->schemaReady() && ($o->seguimiento_tipo ?? 'ninguno') === 'opciones')
                ? $this->getSeguimientoOpciones((int)$o->id)
                : [];
        }
        return $ops;
    }

    public function getSeguimientoOpciones(int $opcionId): array {
        if (!$this->schemaReady() && !$this->tableExists('votacion_seguimiento_opciones')) {
            return [];
        }
        $this->db->query('SELECT * FROM votacion_seguimiento_opciones WHERE opcion_id = :oid ORDER BY orden ASC, id ASC');
        $this->db->bind(':oid', $opcionId);
        return $this->db->resultSet();
    }

    /**
     * @param array<int, array{id?:int, texto:string, seguimiento_tipo?:string, seguimiento_pregunta?:?string, seguimiento_lineas?:string[]}> $items
     */
    public function syncOpciones(int $votacionId, array $items): void {
        $this->ensureSeguimiento();
        if (!$this->schemaReady()) {
            $this->replaceOpciones($votacionId, array_map(static fn($item) => (string)($item['texto'] ?? ''), $items));
            return;
        }
        $existingIds = array_map(static fn($o) => (int)$o->id, $this->getOpciones($votacionId));
        $keep = [];
        $orden = 0;
        foreach ($items as $item) {
            $texto = trim((string)($item['texto'] ?? ''));
            if ($texto === '') {
                continue;
            }
            $tipo = (string)($item['seguimiento_tipo'] ?? 'ninguno');
            if (!in_array($tipo, ['ninguno', 'texto', 'opciones'], true)) {
                $tipo = 'ninguno';
            }
            $pregunta = trim((string)($item['seguimiento_pregunta'] ?? ''));
            if ($tipo === 'ninguno' || $pregunta === '') {
                $tipo = 'ninguno';
                $pregunta = '';
            }
            $id = (int)($item['id'] ?? 0);
            if ($id > 0 && in_array($id, $existingIds, true)) {
                $this->db->query('UPDATE votacion_opciones
                    SET texto = :txt, orden = :ord, seguimiento_pregunta = :preg, seguimiento_tipo = :tipo
                    WHERE id = :id AND votacion_id = :vid');
                $this->db->bind(':id', $id);
                $this->db->bind(':vid', $votacionId);
            } else {
                $this->db->query('INSERT INTO votacion_opciones
                    (votacion_id, texto, orden, seguimiento_pregunta, seguimiento_tipo)
                    VALUES (:vid, :txt, :ord, :preg, :tipo)');
                $this->db->bind(':vid', $votacionId);
            }
            $this->db->bind(':txt', $texto);
            $this->db->bind(':ord', $orden++);
            $this->db->bind(':preg', $pregunta !== '' ? $pregunta : null);
            $this->db->bind(':tipo', $tipo);
            $this->db->execute();
            if (!($id > 0 && in_array($id, $existingIds, true))) {
                $id = (int)$this->db->lastInsertId();
            }
            $keep[] = $id;
            $lineas = $tipo === 'opciones' ? ($item['seguimiento_lineas'] ?? []) : [];
            $this->syncSeguimientoLineas($id, is_array($lineas) ? $lineas : []);
        }
        foreach ($existingIds as $old) {
            if (!in_array($old, $keep, true)) {
                $this->db->query('DELETE FROM votacion_opciones WHERE id = :id AND votacion_id = :vid');
                $this->db->bind(':id', $old);
                $this->db->bind(':vid', $votacionId);
                $this->db->execute();
            }
        }
    }

    /** @param string[] $lineas */
    private function syncSeguimientoLineas(int $opcionId, array $lineas): void {
        $existing = $this->getSeguimientoOpciones($opcionId);
        $used = [];
        $orden = 0;
        foreach ($lineas as $texto) {
            $texto = trim((string)$texto);
            if ($texto === '') {
                continue;
            }
            $match = null;
            foreach ($existing as $e) {
                if (isset($used[(int)$e->id])) {
                    continue;
                }
                if ((string)$e->texto === $texto) {
                    $match = $e;
                    break;
                }
            }
            if ($match) {
                $this->db->query('UPDATE votacion_seguimiento_opciones SET orden = :ord WHERE id = :id AND opcion_id = :oid');
                $this->db->bind(':ord', $orden);
                $this->db->bind(':id', (int)$match->id);
                $this->db->bind(':oid', $opcionId);
                $this->db->execute();
                $used[(int)$match->id] = true;
            } else {
                $this->db->query('INSERT INTO votacion_seguimiento_opciones (opcion_id, texto, orden) VALUES (:oid, :txt, :ord)');
                $this->db->bind(':oid', $opcionId);
                $this->db->bind(':txt', $texto);
                $this->db->bind(':ord', $orden);
                $this->db->execute();
                $used[(int)$this->db->lastInsertId()] = true;
            }
            $orden++;
        }
        foreach ($existing as $e) {
            if (!isset($used[(int)$e->id])) {
                $this->db->query('DELETE FROM votacion_seguimiento_opciones WHERE id = :id AND opcion_id = :oid');
                $this->db->bind(':id', (int)$e->id);
                $this->db->bind(':oid', $opcionId);
                $this->db->execute();
            }
        }
    }

    public function getElectoresIds(int $votacionId): array {
        $this->db->query('SELECT usuario_id FROM votacion_electores WHERE votacion_id = :vid');
        $this->db->bind(':vid', $votacionId);
        return array_map(static fn($r) => (int)$r->usuario_id, $this->db->resultSet());
    }
}
