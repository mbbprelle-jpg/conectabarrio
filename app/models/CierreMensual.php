<?php
class CierreMensual extends Model {

    // Obtener todos los cierres mensuales de una Junta de Vecinos
    public function getCierresByJunta($juntaId) {
        $this->db->query("SELECT c.*, u.nombre as admin_nombre 
                         FROM cierres_mensuales c 
                         LEFT JOIN usuarios u ON c.cerrado_por = u.id 
                         WHERE c.junta_id = :junta_id 
                         ORDER BY c.mes DESC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    // Obtener un cierre por su ID
    public function getCierreById($id) {
        $this->db->query("SELECT c.*, u.nombre as admin_nombre, j.nombre as junta_nombre, j.rut_junta 
                         FROM cierres_mensuales c 
                         LEFT JOIN usuarios u ON c.cerrado_por = u.id 
                         LEFT JOIN juntas_vecinos j ON c.junta_id = j.id
                         WHERE c.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Verificar si un mes ya está cerrado para una Junta
    public function checkCierreExist($juntaId, $mes) {
        $this->db->query("SELECT * FROM cierres_mensuales 
                         WHERE junta_id = :junta_id AND mes = :mes LIMIT 1");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':mes', $mes);
        return $this->db->single() ? true : false;
    }

    // Crear un nuevo registro de cierre mensual
    public function createCierre($data) {
        $this->db->query("INSERT INTO cierres_mensuales (junta_id, mes, ingresos, egresos, saldo_anterior, saldo_final, saldo_neto, cerrado_por, comentario, estado) 
                         VALUES (:junta_id, :mes, :ingresos, :egresos, :saldo_anterior, :saldo_final, :saldo_neto, :cerrado_por, :comentario, 1)");
        
        $this->db->bind(':junta_id', $data['junta_id']);
        $this->db->bind(':mes', $data['mes']); // YYYY-MM
        $this->db->bind(':ingresos', $data['ingresos']);
        $this->db->bind(':egresos', $data['egresos']);
        $this->db->bind(':saldo_anterior', $data['saldo_anterior']);
        $this->db->bind(':saldo_final', $data['saldo_final']);
        $this->db->bind(':saldo_neto', $data['saldo_neto']);
        $this->db->bind(':cerrado_por', $data['cerrado_por']);
        $this->db->bind(':comentario', $data['comentario'] ?? null);

        return $this->db->execute();
    }

    // Marcar cierre como enviado por correo
    public function updateEnviadoCorreo($id) {
        $this->db->query("UPDATE cierres_mensuales SET enviado_correo = 1 WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Obtener balance e información agregada de transacciones para un mes específico (fecha del movimiento)
    public function getResumenFinancieroMes($juntaId, $mes) {
        // mes en formato YYYY-MM
        $likePattern = $mes . '-%';

        // 1. Calcular total de ingresos del mes
        $this->db->query("SELECT SUM(monto) as total FROM transacciones 
                         WHERE junta_id = :junta_id AND tipo = 'ingreso' AND fecha LIKE :mes_like");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':mes_like', $likePattern);
        $ingresosRes = $this->db->single();
        $ingresos = $ingresosRes ? (int)$ingresosRes->total : 0;

        // 2. Calcular total de egresos del mes
        $this->db->query("SELECT SUM(monto) as total FROM transacciones 
                         WHERE junta_id = :junta_id AND tipo = 'egreso' AND fecha LIKE :mes_like");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':mes_like', $likePattern);
        $egresosRes = $this->db->single();
        $egresos = $egresosRes ? (int)$egresosRes->total : 0;

        // 3. Desglose por categoría
        $this->db->query("SELECT categoria, tipo, SUM(monto) as total_monto, COUNT(*) as cantidad 
                         FROM transacciones 
                         WHERE junta_id = :junta_id AND fecha LIKE :mes_like
                         GROUP BY categoria, tipo 
                         ORDER BY tipo DESC, total_monto DESC");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':mes_like', $likePattern);
        $desglose = $this->db->resultSet();

        return [
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'saldo_neto' => $ingresos - $egresos,
            'desglose' => $desglose
        ];
    }

    // Obtener los meses que tienen transacciones pero que aún no están cerrados
    // Además permite cerrar meses vacíos de transacciones desde que la junta se creó
    // Se restringe a partir del mes_inicio de la junta y se bloquea el mes en curso
    public function getMesesDisponiblesParaCerrar($juntaId) {
        $mesInicio = $this->getMesInicioJunta($juntaId);
        
        $startYear = (int)date('Y', strtotime($mesInicio . '-01'));
        $startMonth = (int)date('m', strtotime($mesInicio . '-01'));
        
        // El mes final disponible es el mes inmediatamente anterior al mes en curso
        $hoy = new DateTime();
        $hoy->modify('-1 month');
        $endYear = (int)$hoy->format('Y');
        $endMonth = (int)$hoy->format('m');

        $meses = [];
        $y = $startYear;
        $m = $startMonth;

        while ($y < $endYear || ($y == $endYear && $m <= $endMonth)) {
            $mesStr = sprintf('%04d-%02d', $y, $m);
            
            // Verificar si el mes ya está cerrado
            if (!$this->checkCierreExist($juntaId, $mesStr)) {
                $meses[] = $mesStr;
            }
            
            $m++;
            if ($m > 12) {
                $m = 1;
                $y++;
            }
        }
        
        return array_reverse($meses); // Primero los más recientes
    }

    // Calcular saldo contable anterior (arrastrando el saldo final del mes cerrado anterior)
    public function getSaldoAnterior($juntaId, $mes) {
        // Encontramos el mes inmediatamente anterior
        $dt = new DateTime($mes . '-01');
        $dt->modify('-1 month');
        $mesPrevio = $dt->format('Y-m');
        
        $this->db->query("SELECT saldo_final FROM cierres_mensuales 
                          WHERE junta_id = :junta_id AND mes = :mes LIMIT 1");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':mes', $mesPrevio);
        $res = $this->db->single();
        
        if ($res) {
            return (int)$res->saldo_final;
        }

        if ($this->esPrimerCierre($juntaId)) {
            return $this->getSaldoInicialJunta($juntaId);
        }
        
        return 0;
    }

    public function getSaldoInicialJunta($juntaId) {
        try {
            $this->db->query('SELECT saldo_inicial FROM juntas_vecinos WHERE id = :id LIMIT 1');
            $this->db->bind(':id', $juntaId);
            $res = $this->db->single();
            if ($res && $res->saldo_inicial !== null) {
                return (int)$res->saldo_inicial;
            }
        } catch (Exception $e) {
            // Columna aún no migrada
        }
        return 0;
    }

    public function getMesesCerrados($juntaId) {
        $this->db->query('SELECT mes FROM cierres_mensuales WHERE junta_id = :junta_id ORDER BY mes ASC');
        $this->db->bind(':junta_id', $juntaId);
        $rows = $this->db->resultSet();
        return array_map(static fn($row) => $row->mes, $rows);
    }

    public function getRangoFechasPermitidas($juntaId) {
        $mesInicio = $this->getMesInicioJunta($juntaId);
        $min = $mesInicio . '-01';
        $mesesCerrados = $this->getMesesCerrados($juntaId);

        if (!empty($mesesCerrados)) {
            $ultimoCerrado = $mesesCerrados[count($mesesCerrados) - 1];
            $dt = new DateTime($ultimoCerrado . '-01');
            $dt->modify('+1 month');
            $minTrasCierre = $dt->format('Y-m-d');
            if ($minTrasCierre > $min) {
                $min = $minTrasCierre;
            }
        }

        return [
            'min' => $min,
            'max' => date('Y-m-d'),
            'meses_cerrados' => $mesesCerrados,
            'mes_inicio' => $mesInicio,
        ];
    }

    public function validarFechaMovimiento($juntaId, $fecha) {
        $fecha = substr(trim((string)$fecha), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return 'La fecha del movimiento no es válida.';
        }

        $rango = $this->getRangoFechasPermitidas($juntaId);

        if ($fecha > $rango['max']) {
            return 'La fecha no puede ser superior a hoy (' . date('d-m-Y') . ').';
        }

        if ($fecha < $rango['min']) {
            if (!empty($rango['meses_cerrados'])) {
                return 'La fecha cae en un mes ya cerrado o fuera del periodo abierto. Solo puede registrar movimientos desde el ' . date('d-m-Y', strtotime($rango['min'])) . '.';
            }
            $parts = explode('-', $rango['mes_inicio']);
            return 'No puede registrar movimientos anteriores al inicio de actividades de la organización (' . ($parts[1] ?? '') . '-' . ($parts[0] ?? '') . ').';
        }

        $mesFecha = substr($fecha, 0, 7);
        if ($this->checkCierreExist($juntaId, $mesFecha)) {
            return 'El mes ' . $mesFecha . ' ya fue cerrado. No puede registrar movimientos en periodos cerrados.';
        }

        return null;
    }

    // Obtener la lista de transacciones del mes detallada
    public function getTransaccionesDetalleMes($juntaId, $mes) {
        $likePattern = $mes . '-%';
        $this->db->query("SELECT t.*, u.nombre as socio_nombre 
                          FROM transacciones t 
                          LEFT JOIN usuarios u ON t.socio_id = u.id 
                          WHERE t.junta_id = :junta_id AND t.fecha LIKE :mes_like 
                          ORDER BY t.fecha ASC");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':mes_like', $likePattern);
        return $this->db->resultSet();
    }

    // Obtener el mes de inicio de actividades de la junta
    public function getMesInicioJunta($juntaId) {
        $this->db->query("SELECT mes_inicio FROM juntas_vecinos WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $juntaId);
        $res = $this->db->single();
        return $res ? $res->mes_inicio : '2026-01';
    }

    // Verificar si es el primer cierre histórico
    public function esPrimerCierre($juntaId) {
        $this->db->query("SELECT COUNT(*) as total FROM cierres_mensuales WHERE junta_id = :junta_id");
        $this->db->bind(':junta_id', $juntaId);
        $res = $this->db->single();
        return ($res && $res->total == 0);
    }

    // Verificar si hay algún mes previo sin cerrar
    public function tieneMesPrevioSinCerrar($juntaId, $mes) {
        $mesInicio = $this->getMesInicioJunta($juntaId);
        
        $start = new DateTime($mesInicio . '-01');
        $target = new DateTime($mes . '-01');
        
        if ($target <= $start) {
            return false; // No hay mes previo
        }
        
        $curr = clone $start;
        while ($curr < $target) {
            $mesStr = $curr->format('Y-m');
            if (!$this->checkCierreExist($juntaId, $mesStr)) {
                return $mesStr; // Retorna el mes más antiguo sin cerrar
            }
            $curr->modify('+1 month');
        }
        
        return false;
    }
}
