<?php
class Votacion extends Model {

    private static $tablesReady = null;

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

    public function generateToken(): string {
        return bin2hex(random_bytes(24));
    }

    public function create(array $data): ?int {
        if (!$this->tablesExist()) {
            return null;
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
        $this->db->query('UPDATE votaciones SET titulo = :titulo, descripcion = :descripcion, tipo = :tipo,
            audiencia_tipo = :audiencia_tipo, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin,
            resultados_visibilidad = :resultados_visibilidad
            WHERE id = :id AND junta_id = :junta_id AND estado IN (\'borrador\', \'activa\')');
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
        $this->db->query("UPDATE votaciones SET estado = 'activa'
            WHERE estado = 'borrador' AND fecha_inicio <= NOW() AND fecha_fin >= NOW()");
        $this->db->execute();
        $this->db->query("UPDATE votaciones SET estado = 'cerrada'
            WHERE estado IN ('borrador', 'activa') AND fecha_fin < NOW()");
        $this->db->execute();
    }

    public function isActiva(object $v): bool {
        $now = time();
        $inicio = strtotime($v->fecha_inicio);
        $fin = strtotime($v->fecha_fin);
        return ($v->estado === 'activa' || ($v->estado === 'borrador' && $inicio <= $now && $fin >= $now))
            && $inicio <= $now && $fin >= $now;
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
        if ($v->resultados_visibilidad === 'directiva' && $isDirectiva) {
            return in_array($v->estado, ['activa', 'cerrada'], true);
        }
        return false;
    }

    public function canViewVoterDetail(object $v, int $usuarioId, bool $isFullAdmin): bool {
        return $isFullAdmin || (int)$v->creado_por === $usuarioId;
    }

    public function registerVote(int $votacionId, int $usuarioId, ?int $opcionId, ?string $texto): bool {
        $this->db->query('INSERT INTO votacion_respuestas (votacion_id, usuario_id, opcion_id, respuesta_texto)
            VALUES (:vid, :uid, :oid, :txt)');
        $this->db->bind(':vid', $votacionId);
        $this->db->bind(':uid', $usuarioId);
        $this->db->bind(':oid', $opcionId);
        $this->db->bind(':txt', $texto);
        return $this->db->execute();
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
        $this->db->query('SELECT r.created_at, u.nombre, u.rut, u.email, o.texto AS opcion_texto, r.respuesta_texto
            FROM votacion_respuestas r
            INNER JOIN usuarios u ON u.id = r.usuario_id
            LEFT JOIN votacion_opciones o ON o.id = r.opcion_id
            INNER JOIN usuario_membresias m ON m.usuario_id = u.id AND m.junta_id = :jid AND m.estado = 1
            WHERE r.votacion_id = :vid
            ORDER BY r.created_at DESC');
        $this->db->bind(':vid', $votacionId);
        $this->db->bind(':jid', $juntaId);
        return $this->db->resultSet();
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

    public function getElectoresIds(int $votacionId): array {
        $this->db->query('SELECT usuario_id FROM votacion_electores WHERE votacion_id = :vid');
        $this->db->bind(':vid', $votacionId);
        return array_map(static fn($r) => (int)$r->usuario_id, $this->db->resultSet());
    }
}
