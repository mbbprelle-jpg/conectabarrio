<?php
class CensoFamiliar extends Model {

    /** Preferencia de ID de campaña (JV N° 136 Valle de Peñaflor). */
    public const CAMPAIGN_JUNTA_ID_PREF = 6;

    public function hasTables(): bool {
        try {
            $this->db->query("SHOW TABLES LIKE 'censo_registros'");
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Junta de la campaña pública de Navidad.
     * Prefiere ID 6 si existe; si no, busca por nombre (136 / Valle / Peñaflor).
     */
    public function resolveCampaignJuntaId(): int {
        try {
            $this->db->query("SELECT id, nombre FROM juntas_vecinos WHERE id = :id LIMIT 1");
            $this->db->bind(':id', self::CAMPAIGN_JUNTA_ID_PREF);
            $byId = $this->db->single();
            if ($byId) {
                $n = mb_strtoupper((string)($byId->nombre ?? ''), 'UTF-8');
                if (strpos($n, '136') !== false || strpos($n, 'VALLE') !== false || strpos($n, 'PE') !== false) {
                    return (int)$byId->id;
                }
            }

            $this->db->query("SELECT id FROM juntas_vecinos
                WHERE UPPER(nombre) LIKE '%136%'
                  AND (UPPER(nombre) LIKE '%VALLE%' OR UPPER(nombre) LIKE '%PE_AFLOR%' OR UPPER(nombre) LIKE '%PENAFLOR%')
                ORDER BY id ASC LIMIT 1");
            $byName = $this->db->single();
            if ($byName) {
                return (int)$byName->id;
            }
            if ($byId) {
                return (int)$byId->id;
            }
        } catch (Throwable $e) {
            // fallback
        }
        return self::CAMPAIGN_JUNTA_ID_PREF;
    }

    public function getOrCreateLink(int $juntaId, int $createdBy = 0): ?object {
        $this->db->query("SELECT * FROM censo_links WHERE junta_id = :junta_id AND activo = 1 ORDER BY id DESC LIMIT 1");
        $this->db->bind(':junta_id', $juntaId);
        $link = $this->db->single();
        if ($link) {
            return $link;
        }
        $token = bin2hex(random_bytes(24));
        $this->db->query("INSERT INTO censo_links (junta_id, token, activo, titulo, created_by)
            VALUES (:junta_id, :token, 1, 'Registro para juguetes de Navidad 2026', :created_by)");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':token', $token);
        $this->db->bind(':created_by', $createdBy > 0 ? $createdBy : null);
        if (!$this->db->execute()) {
            return null;
        }
        $this->db->query("SELECT * FROM censo_links WHERE token = :token LIMIT 1");
        $this->db->bind(':token', $token);
        return $this->db->single();
    }

    public function getValidLinkByToken(string $token): ?object {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $this->db->query("SELECT l.*, j.nombre AS junta_nombre, j.comuna AS junta_comuna, j.tipo AS junta_tipo
            FROM censo_links l
            INNER JOIN juntas_vecinos j ON j.id = l.junta_id
            WHERE l.token = :token AND l.activo = 1
            LIMIT 1");
        $this->db->bind(':token', $token);
        $row = $this->db->single();
        return $row ?: null;
    }

    public function rutNinoYaRegistrado(int $juntaId, string $rut, string $tipo = 'hijo'): bool {
        $this->db->query("SELECT p.id
            FROM censo_personas p
            INNER JOIN censo_registros r ON r.id = p.registro_id
            WHERE r.junta_id = :junta_id AND p.rut = :rut AND p.tipo = :tipo
            LIMIT 1");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':rut', $rut);
        $this->db->bind(':tipo', $tipo);
        return (bool)$this->db->single();
    }

    /**
     * @param array $adulto
     * @param array $personas list of ['tipo','rut','nombre_completo','sexo','edad','fecha_parto','usa_datos_adulto']
     */
    public function createRegistro(array $adulto, array $personas): array {
        $this->db->query("INSERT INTO censo_registros
            (junta_id, link_id, rut, nombre, calle_id, direccion_texto, telefono,
             registra_hijos, registra_discapacidad, registra_embarazo)
            VALUES
            (:junta_id, :link_id, :rut, :nombre, :calle_id, :direccion_texto, :telefono,
             :registra_hijos, :registra_discapacidad, :registra_embarazo)");
        $this->db->bind(':junta_id', (int)$adulto['junta_id']);
        $this->db->bind(':link_id', $adulto['link_id'] ?? null);
        $this->db->bind(':rut', $adulto['rut']);
        $this->db->bind(':nombre', $adulto['nombre']);
        $this->db->bind(':calle_id', $adulto['calle_id'] ?? null);
        $this->db->bind(':direccion_texto', $adulto['direccion_texto'] ?? null);
        $this->db->bind(':telefono', $adulto['telefono']);
        $this->db->bind(':registra_hijos', !empty($adulto['registra_hijos']) ? 1 : 0);
        $this->db->bind(':registra_discapacidad', !empty($adulto['registra_discapacidad']) ? 1 : 0);
        $this->db->bind(':registra_embarazo', !empty($adulto['registra_embarazo']) ? 1 : 0);
        if (!$this->db->execute()) {
            return ['ok' => false, 'error' => 'No se pudo guardar el registro del adulto.'];
        }

        $registroId = (int)$this->db->lastInsertId();
        if ($registroId <= 0) {
            return ['ok' => false, 'error' => 'No se obtuvo el ID del registro.'];
        }

        foreach ($personas as $p) {
            $this->db->query("INSERT INTO censo_personas
                (registro_id, tipo, rut, nombre_completo, sexo, edad, fecha_parto, usa_datos_adulto)
                VALUES
                (:registro_id, :tipo, :rut, :nombre_completo, :sexo, :edad, :fecha_parto, :usa_datos_adulto)");
            $this->db->bind(':registro_id', $registroId);
            $this->db->bind(':tipo', $p['tipo']);
            $this->db->bind(':rut', $p['rut']);
            $this->db->bind(':nombre_completo', $p['nombre_completo']);
            $this->db->bind(':sexo', $p['sexo']);
            $this->db->bind(':edad', $p['edad'] ?? null);
            $this->db->bind(':fecha_parto', $p['fecha_parto'] ?? null);
            $this->db->bind(':usa_datos_adulto', !empty($p['usa_datos_adulto']) ? 1 : 0);
            if (!$this->db->execute()) {
                return ['ok' => false, 'error' => 'Error al guardar una persona asociada (RUT ' . ($p['rut'] ?? '') . ').'];
            }
        }

        return ['ok' => true, 'id' => $registroId];
    }

    public function listByJunta(int $juntaId): array {
        $this->db->query("SELECT r.*, c.nombre AS calle_nombre
            FROM censo_registros r
            LEFT JOIN calles c ON c.id = r.calle_id
            WHERE r.junta_id = :junta_id
            ORDER BY r.created_at DESC, r.id DESC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function getPersonasByRegistro(int $registroId): array {
        $this->db->query("SELECT * FROM censo_personas WHERE registro_id = :id ORDER BY tipo ASC, id ASC");
        $this->db->bind(':id', $registroId);
        return $this->db->resultSet();
    }

    /**
     * Personas asociadas para exportación, con datos del adulto (vínculo por id_registro).
     * @return object[]
     */
    public function listPersonasExportByJunta(int $juntaId, ?int $registroId = null): array {
        $sql = "SELECT p.id AS id_persona,
                p.registro_id AS id_registro,
                r.rut AS adulto_rut,
                r.nombre AS adulto_nombre,
                p.tipo,
                p.rut AS persona_rut,
                p.nombre_completo,
                p.sexo,
                p.edad,
                p.fecha_parto,
                p.usa_datos_adulto,
                p.created_at
            FROM censo_personas p
            INNER JOIN censo_registros r ON r.id = p.registro_id
            WHERE r.junta_id = :junta_id";
        if ($registroId !== null && $registroId > 0) {
            $sql .= " AND r.id = :registro_id";
        }
        $sql .= " ORDER BY p.registro_id ASC, p.tipo ASC, p.id ASC";
        $this->db->query($sql);
        $this->db->bind(':junta_id', $juntaId);
        if ($registroId !== null && $registroId > 0) {
            $this->db->bind(':registro_id', $registroId);
        }
        return $this->db->resultSet();
    }

    /** @return object[] */
    public function listRegistrosExportByJunta(int $juntaId, ?int $registroId = null): array {
        $sql = "SELECT r.*, c.nombre AS calle_nombre
            FROM censo_registros r
            LEFT JOIN calles c ON c.id = r.calle_id
            WHERE r.junta_id = :junta_id";
        if ($registroId !== null && $registroId > 0) {
            $sql .= " AND r.id = :registro_id";
        }
        $sql .= " ORDER BY r.id ASC";
        $this->db->query($sql);
        $this->db->bind(':junta_id', $juntaId);
        if ($registroId !== null && $registroId > 0) {
            $this->db->bind(':registro_id', $registroId);
        }
        return $this->db->resultSet();
    }

    public function getRegistroDetalle(int $registroId, int $juntaId): ?object {
        $this->db->query("SELECT r.*, c.nombre AS calle_nombre
            FROM censo_registros r
            LEFT JOIN calles c ON c.id = r.calle_id
            WHERE r.id = :id AND r.junta_id = :junta_id
            LIMIT 1");
        $this->db->bind(':id', $registroId);
        $this->db->bind(':junta_id', $juntaId);
        $row = $this->db->single();
        return $row ?: null;
    }

    public function getResumenJunta(int $juntaId): array {
        $this->db->query("SELECT
            COUNT(*) AS total_registros,
            SUM(registra_hijos) AS con_hijos,
            SUM(registra_discapacidad) AS con_discapacidad,
            SUM(registra_embarazo) AS con_embarazo
            FROM censo_registros WHERE junta_id = :junta_id");
        $this->db->bind(':junta_id', $juntaId);
        $row = $this->db->single();

        $this->db->query("SELECT p.tipo, COUNT(*) AS total
            FROM censo_personas p
            INNER JOIN censo_registros r ON r.id = p.registro_id
            WHERE r.junta_id = :junta_id
            GROUP BY p.tipo");
        $this->db->bind(':junta_id', $juntaId);
        $byTipo = ['hijo' => 0, 'discapacidad' => 0, 'embarazo' => 0];
        foreach ($this->db->resultSet() as $t) {
            $byTipo[$t->tipo] = (int)$t->total;
        }

        return [
            'total_registros' => (int)($row->total_registros ?? 0),
            'con_hijos' => (int)($row->con_hijos ?? 0),
            'con_discapacidad' => (int)($row->con_discapacidad ?? 0),
            'con_embarazo' => (int)($row->con_embarazo ?? 0),
            'total_hijos' => $byTipo['hijo'],
            'total_discapacidad' => $byTipo['discapacidad'],
            'total_embarazo' => $byTipo['embarazo'],
        ];
    }
}
