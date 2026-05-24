<?php
class MaestroController extends Controller {
    private $juntaModel;
    private $userModel;
    private $cuotaModel;

    public function __construct() {
        $this->juntaModel = $this->model('JuntaVecinos');
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
                $this->juntaModel->db->query("INSERT INTO juntas_vecinos (nombre, tipo, rut_junta, direccion, comuna, mes_inicio, plan, precio_anual) VALUES (:nombre, :tipo, :rut_junta, :direccion, :comuna, :mes_inicio, :plan, :precio_anual)");
                $this->juntaModel->db->bind(':nombre', $data['junta_nombre']);
                $this->juntaModel->db->bind(':tipo', $data['junta_tipo']);
                $this->juntaModel->db->bind(':rut_junta', $data['junta_rut']);
                $this->juntaModel->db->bind(':direccion', $data['junta_direccion']);
                $this->juntaModel->db->bind(':comuna', $data['junta_comuna']);
                $this->juntaModel->db->bind(':mes_inicio', $data['junta_mes_inicio']);
                $this->juntaModel->db->bind(':plan', $data['junta_plan']);
                $this->juntaModel->db->bind(':precio_anual', $data['junta_precio_anual']);
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
                    $this->userModel->db->query("INSERT INTO usuarios (junta_id, rut, nombres, apellido_paterno, apellido_materno, email, password, rol, telefono, estado) VALUES (:junta_id, :rut, :nombres, :apellido_paterno, :apellido_materno, :email, :password, :rol, :telefono, :estado)");
                    $this->userModel->db->bind(':junta_id', $juntaId);
                    $this->userModel->db->bind(':rut', $data['admin_rut']);
                    $this->userModel->db->bind(':nombres', $data['admin_nombres']);
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

            if ($juntaId > 0 && in_array($plan, ['basico', 'mediano', 'premium'])) {
                if ($this->juntaModel->updatePlanAndPrice($juntaId, $plan, $precioAnual)) {
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
}
