<?php
class MaestroController extends Controller {
    private $juntaModel;
    private $paymentModel;
    private $userModel;
    private $cuotaModel;

    public function __construct() {
        $this->juntaModel = $this->model('JuntaVecinos');
        $this->paymentModel = $this->model('Payment');
        $this->userModel = $this->model('User');
        $this->cuotaModel = $this->model('CuotaConfig');
    }

    // Cargar Dashboard Maestro
    public function dashboard() {
        $stats = $this->juntaModel->getStatsGlobal();
        $juntas = $this->juntaModel->getJuntas();

        $data = [
            'title' => 'Dashboard Maestro',
            'header_title' => 'Portal de Gestión Global',
            'header_subtitle' => 'Administración general de Juntas de Vecinos asociadas',
            'active_menu' => 'dashboard',
            'stats' => $stats,
            'juntas' => $juntas,
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? ''
        ];

        // Limpiar mensajes flash
        unset($_SESSION['success_msg']);
        unset($_SESSION['error_msg']);

        $this->view('maestro/dashboard', $data);
    }

    // Cargar Vista de Creación de Junta
    public function crear_junta() {
        $data = [
            'title' => 'Crear Organización Vecinal',
            'header_title' => 'Registrar Nueva Organización',
            'header_subtitle' => 'Cree una nueva junta, comité u organización y su respectiva cuenta de administrador',
            'active_menu' => 'crear_junta',
            'junta_nombre' => '',
            'junta_tipo' => 'Junta de Vecinos',
            'junta_rut' => '',
            'junta_direccion' => '',
            'junta_comuna' => '',
            'admin_nombres' => '',
            'admin_apellido_paterno' => '',
            'admin_apellido_materno' => '',
            'admin_rut' => '',
            'admin_email' => '',
            'admin_telefono' => '',
            'junta_mes_inicio' => date('Y-m'),
            'junta_suscripcion_mes_inicio' => date('Y-m'),
            'junta_plan' => 'basico',
            'junta_precio_anual' => 59880,
            'cuota_inicial' => '5000',
            'cuota_mes_inicio' => date('Y-m'),
            'error' => ''
        ];

        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->sanitizePost();

            // Rellenar datos para preservar inputs en caso de error
            $data['junta_nombre'] = $post['junta_nombre'] ?? '';
            $data['junta_tipo'] = $post['junta_tipo'] ?? 'Junta de Vecinos';
            $data['junta_rut'] = $post['junta_rut'] ?? '';
            $data['junta_direccion'] = $post['junta_direccion'] ?? '';
            $data['junta_comuna'] = $post['junta_comuna'] ?? '';
            $data['admin_nombres'] = $post['admin_nombres'] ?? '';
            $data['admin_apellido_paterno'] = $post['admin_apellido_paterno'] ?? '';
            $data['admin_apellido_materno'] = $post['admin_apellido_materno'] ?? '';
            $data['admin_rut'] = $post['admin_rut'] ?? '';
            $data['admin_email'] = $post['admin_email'] ?? '';
            $data['admin_telefono'] = $post['admin_telefono'] ?? '';
            $data['junta_mes_inicio'] = $post['junta_mes_inicio'] ?? date('Y-m');
            $data['junta_suscripcion_mes_inicio'] = $post['junta_suscripcion_mes_inicio'] ?? date('Y-m');
            $data['junta_plan'] = $post['junta_plan'] ?? 'basico';
            $data['junta_precio_anual'] = isset($post['junta_precio_anual']) ? (int)$post['junta_precio_anual'] : 59880;
            $data['cuota_inicial'] = $post['cuota_inicial'] ?? '0';
            $data['cuota_mes_inicio'] = $post['cuota_mes_inicio'] ?? date('Y-m');

            // Validar RUT de la organización (único)
            if ($this->juntaModel->getJuntaByRut($data['junta_rut'])) {
                $data['error'] = 'Ya existe una Organización con ese RUT.';
                $this->view('maestro/crear_junta', $data);
                return;
            }
            // NOT checking admin RUT/Email here – we will reuse an existing admin if it exists

            // Iniciar Transacción de Base de Datos para asegurar atomicidad
            $db = new Database();
            try {
                $db->beginTransaction();

                // 1. Crear Junta de Vecinos/Organización
                $this->juntaModel->db->query("INSERT INTO juntas_vecinos (nombre, tipo, rut_junta, direccion, comuna, mes_inicio, plan, precio_anual, mes_inicio_suscripcion) VALUES (:nombre, :tipo, :rut_junta, :direccion, :comuna, :mes_inicio, :plan, :precio_anual, :mes_inicio_suscripcion)");
                $this->juntaModel->db->bind(':nombre', $data['junta_nombre']);
                $this->juntaModel->db->bind(':tipo', $data['junta_tipo']);
                $this->juntaModel->db->bind(':rut_junta', $data['junta_rut']);
                $this->juntaModel->db->bind(':direccion', $data['junta_direccion']);
                $this->juntaModel->db->bind(':comuna', $data['junta_comuna']);
                $this->juntaModel->db->bind(':mes_inicio', $data['junta_mes_inicio']);
                $this->juntaModel->db->bind(':plan', $data['junta_plan']);
                $this->juntaModel->db->bind(':precio_anual', $data['junta_precio_anual']);
                $this->juntaModel->db->bind(':mes_inicio_suscripcion', $data['junta_suscripcion_mes_inicio']);
                $this->juntaModel->db->execute();
                $juntaId = $this->juntaModel->db->lastInsertId();

                // 2. Registrar Configuración de Cuota Inicial de la Junta
                $this->cuotaModel->db->query("INSERT INTO configuracion_cuotas (junta_id, monto, mes_inicio) VALUES (:junta_id, :monto, :mes_inicio)");
                $this->cuotaModel->db->bind(':junta_id', $juntaId);
                $this->cuotaModel->db->bind(':monto', $data['cuota_inicial']);
                $this->cuotaModel->db->bind(':mes_inicio', $data['cuota_mes_inicio']);
                $this->cuotaModel->db->execute();

                // 3. Asociar o crear Usuario Administrador de esa Junta
                // Intentamos reusar un admin existente (por RUT o Email). Si no existe, lo creamos.
                $existingAdmin = $this->userModel->findUserByRut($data['admin_rut']);
                if (!$existingAdmin) {
                    $existingAdmin = $this->userModel->findUserByEmail($data['admin_email']);
                }
                if ($existingAdmin) {
                    // Actualizar su junta_id y asegurar rol admin
                    $this->userModel->db->query("UPDATE usuarios SET junta_id = :junta_id, rol = 'admin' WHERE id = :id");
                    $this->userModel->db->bind(':junta_id', $juntaId);
                    $this->userModel->db->bind(':id', $existingAdmin->id);
                    $this->userModel->db->execute();
                } else {
                    // Crear nuevo admin con contraseña por defecto "admin123"
                    $adminPass = 'admin123';
                    $hashedPass = password_hash($adminPass, PASSWORD_BCRYPT);
                    $this->userModel->db->query("INSERT INTO usuarios (junta_id, rut, nombre, apellido_paterno, apellido_materno, email, password, rol, telefono, estado) VALUES (:junta_id, :rut, :nombre, :apellido_paterno, :apellido_materno, :email, :password, :rol, :telefono, :estado)");
                    $this->userModel->db->bind(':junta_id', $juntaId);
                    $this->userModel->db->bind(':rut', $data['admin_rut']);
                    $this->userModel->db->bind(':nombre', $data['admin_nombres']);
                    $this->userModel->db->bind(':apellido_paterno', $data['admin_apellido_paterno']);
                    $this->userModel->db->bind(':apellido_materno', $data['admin_apellido_materno']);
                    $this->userModel->db->bind(':email', $data['admin_email']);
                    $this->userModel->db->bind(':password', $hashedPass);
                    $this->userModel->db->bind(':rol', 'admin');
                    $this->userModel->db->bind(':telefono', $data['admin_telefono']);
                    $this->userModel->db->bind(':estado', 1);
                    $this->userModel->db->execute();
                }

                // Confirmar transacción
                $db->commit();
                
                $_SESSION['success_msg'] = 'Junta de Vecinos "' . $data['junta_nombre'] . '" creada exitosamente. Administrador: ' . $data['admin_email'] . ' (Clave: admin123)';
                $this->redirect('/maestro/dashboard');
                
            } catch (Exception $e) {
                // Revertir en caso de fallos
                $db->rollBack();
                $data['error'] = 'Error al procesar la creación sistemática: ' . $e->getMessage();
                $this->view('maestro/crear_junta', $data);
            }
            return;
        }

        $this->view('maestro/crear_junta', $data);
    }

    // Actualizar Plan y Precio de una Organización (POST)
    public function actualizar_plan() {
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->sanitizePost();
            $juntaId = isset($post['junta_id']) ? (int)$post['junta_id'] : 0;
            $plan = $post['plan'] ?? 'basico';
            $precioAnual = isset($post['precio_anual']) ? (int)$post['precio_anual'] : 0;
            $mesInicioSuscripcion = $post['mes_inicio_suscripcion'] ?? null;

            if ($juntaId > 0 && in_array($plan, ['basico', 'mediano', 'premium'])) {
                if ($this->juntaModel->updatePlanAndPrice($juntaId, $plan, $precioAnual, $mesInicioSuscripcion)) {
                    $_SESSION['success_msg'] = 'Plan y precio de la organización actualizados exitosamente.';
                } else {
                    $_SESSION['error_msg'] = 'Error al actualizar el plan en la base de datos.';
                }
            } else {
                $_SESSION['error_msg'] = 'Datos de actualización no válidos.';
            }
        }
        $this->redirect('/maestro/dashboard');
    }
    // ==================== Pagos de Organizaciones (Suscripción ConectaBarrio) ====================

    public function payments() {
        $data = [
            'title' => 'Historial de Pagos',
            'header_title' => 'Historial de Pagos',
            'header_subtitle' => 'Registro de suscripciones pagadas por las organizaciones',
            'active_menu' => 'payments',
            'payments' => $this->paymentModel->getAllWithOrg(),
            'summary' => $this->paymentModel->summarizeGlobal(),
            'metodo_labels' => Payment::metodoPagoLabels()
        ];
        $this->view('maestro/payments', $data);
    }

    public function get_org_pagos($orgId = null) {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $orgId = (int)$orgId;
            if ($orgId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de organización inválido']);
                return;
            }

            $junta = $this->juntaModel->getJuntaById($orgId);
            if (!$junta) {
                echo json_encode(['success' => false, 'message' => 'Organización no encontrada']);
                return;
            }

            $startMonth = $this->resolveSubscriptionStartMonth($junta);
            $currentMonthStr = date('Y-m');
            $monthlyAmount = Payment::monthlyAmountForOrg($junta);

            $startYear = (int)substr($startMonth, 0, 4);
            $startMonthNum = (int)substr($startMonth, 5, 2);
            $endYear = (int)date('Y') + 1;
            $endMonthNum = (int)date('m');

            $y = $startYear;
            $m = $startMonthNum;
            $meses = [];

            while ($y < $endYear || ($y == $endYear && $m <= $endMonthNum)) {
                $mes = sprintf('%04d-%02d', $y, $m);

                if ($mes < $startMonth) {
                    $m++;
                    if ($m > 12) {
                        $m = 1;
                        $y++;
                    }
                    continue;
                }

                $record = $this->paymentModel->getByOrgMonth($orgId, $mes);

                if ($record && $record->status === 'paid') {
                    $estado = 'pagado';
                    $descripcion = 'Pagado el ' . date('d-m-Y', strtotime($record->paid_at ?? $record->due_date));
                    $monto = (int)$record->amount;
                } else {
                    $monto = $monthlyAmount;
                    $estado = ($mes <= $currentMonthStr) ? 'pendiente' : 'futuro';
                    $descripcion = '';
                }

                $meses[] = [
                    'mes' => $mes,
                    'monto' => $monto,
                    'estado' => $estado,
                    'descripcion' => $descripcion
                ];

                $m++;
                if ($m > 12) {
                    $m = 1;
                    $y++;
                }
            }

            echo json_encode([
                'success' => true,
                'org' => [
                    'id' => (int)$junta->id,
                    'nombre' => $junta->nombre,
                    'plan' => $junta->plan ?? 'basico',
                    'mes_inicio_suscripcion' => $startMonth,
                    'monto_mensual' => $monthlyAmount
                ],
                'meses' => $meses
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al cargar meses: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    private function resolveSubscriptionStartMonth($junta) {
        if (isset($junta->mes_inicio_suscripcion) && $junta->mes_inicio_suscripcion !== '') {
            $candidate = substr(trim((string)$junta->mes_inicio_suscripcion), 0, 7);
            if (preg_match('/^\d{4}-\d{2}$/', $candidate)) {
                return $candidate;
            }
        }
        if (!empty($junta->created_at)) {
            $candidate = substr(trim((string)$junta->created_at), 0, 7);
            if (preg_match('/^\d{4}-\d{2}$/', $candidate)) {
                return $candidate;
            }
        }
        return date('Y-m');
    }

    public function registrar_pago_org() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/maestro/dashboard');
            return;
        }

        $post = $this->sanitizePost();
        $orgId = isset($post['org_id']) ? (int)$post['org_id'] : 0;
        $meses = $post['mes_pagado'] ?? [];
        $fechaPago = $post['fecha_pago'] ?? date('Y-m-d');
        $metodoPago = $post['metodo_pago'] ?? '';
        $metodosValidos = array_keys(Payment::metodoPagoLabels());

        if (!is_array($meses)) {
            $meses = !empty($meses) ? [$meses] : [];
        }

        if ($orgId <= 0 || empty($meses)) {
            $_SESSION['error_msg'] = 'Debe seleccionar una organización y al menos un mes a registrar.';
            $this->redirect('/maestro/dashboard');
            return;
        }

        if (!in_array($metodoPago, $metodosValidos, true)) {
            $_SESSION['error_msg'] = 'Debe seleccionar un método de pago (Transferencia, Efectivo o Webpay).';
            $this->redirect('/maestro/dashboard');
            return;
        }

        $junta = $this->juntaModel->getJuntaById($orgId);
        if (!$junta) {
            $_SESSION['error_msg'] = 'Organización no válida.';
            $this->redirect('/maestro/dashboard');
            return;
        }

        $defaultAmount = Payment::monthlyAmountForOrg($junta);
        $montosPorMes = is_array($post['monto_mes'] ?? null) ? $post['monto_mes'] : [];
        $mesAmounts = [];

        foreach ($meses as $mes) {
            $mes = trim((string)$mes);
            if (isset($montosPorMes[$mes]) && $montosPorMes[$mes] !== '') {
                $mesAmounts[$mes] = max(0, (int)$montosPorMes[$mes]);
            } else {
                $mesAmounts[$mes] = $defaultAmount;
            }
        }

        $result = $this->paymentModel->registerMonths($orgId, $mesAmounts, $fechaPago, $metodoPago);

        if ($result['registered'] > 0) {
            $metodoLabel = Payment::metodoPagoLabels()[$metodoPago];
            $_SESSION['success_msg'] = 'Se registraron ' . $result['registered'] . ' mes(es) de suscripción para "' . $junta->nombre . '" por un total de $' . number_format($result['total'], 0, ',', '.') . ' CLP (' . $metodoLabel . ').';
        } else {
            $_SESSION['error_msg'] = 'No se registraron pagos. Los meses seleccionados ya estaban pagados.';
        }

        $this->redirect('/maestro/dashboard');
    }

}
