<?php
class SocioController extends Controller {
    private $userModel;
    private $transaccionModel;
    private $cuotaModel;
    private $membresiaModel;
    private $cambioModel;
    private $db;

    public function __construct() {
        $this->userModel = $this->model('User');
        $this->transaccionModel = $this->model('Transaccion');
        $this->cuotaModel = $this->model('CuotaConfig');
        $this->membresiaModel = $this->model('Membresia');
        $this->cambioModel = $this->model('SocioCambioSolicitud');
        $this->reunionConvocadoModel = $this->model('ReunionConvocado');
        $this->reunionModel = $this->model('Reunion');
        $this->db = new Database();
    }

    public function dashboard() {
        require_once APPROOT . '/core/OrgHelper.php';
        $socioId = $_SESSION['user_id'];
        $juntaId = $_SESSION['user_junta_id'];

        $socio = $this->userModel->getSocioById($socioId);
        if (!$socio) {
            $socio = $this->userModel->getUserById($socioId);
        }
        if (!$socio) {
            die('Error al cargar la información del socio.');
        }
        $socio = $this->membresiaModel->overlayDomicilioOnUser($socio, (int)$juntaId);

        $transacciones = $this->transaccionModel->getTransaccionesBySocio($socioId, $juntaId);
        $mesActual = date('Y-m');
        $cuotaVigente = $this->cuotaModel->getCuotaVigente($juntaId, $mesActual);

        $totalAportado = 0;
        $cantidadCuotasPagadas = 0;
        foreach ($transacciones as $t) {
            if ($t->tipo === 'ingreso' && $t->categoria !== 'Cuota Condonada') {
                $totalAportado += $t->monto;
            }
            if ($t->categoria === 'Cuota Socio') {
                $cantidadCuotasPagadas++;
            }
        }

        $cambioPendiente = $this->cambioModel->getPendingForUsuarioJunta((int)$socioId, (int)$juntaId);

        $data = [
            'title' => 'Mi Perfil Vecinal',
            'header_title' => 'Panel de Socio Vecino',
            'header_subtitle' => 'Revise su información de afiliación, estado de cuotas y descargue comprobantes oficiales',
            'active_menu' => 'dashboard',
            'socio' => $socio,
            'transacciones' => $transacciones,
            'cuota_vigente' => $cuotaVigente ? $cuotaVigente->monto : 0,
            'total_pagado' => $totalAportado,
            'cantidad_pagos' => $cantidadCuotasPagadas,
            'cambio_pendiente' => $cambioPendiente,
            'org_tipo' => $_SESSION['user_junta_tipo'] ?? 'Junta de Vecinos',
            'uses_calles' => OrgHelper::usesCallesJurisdiccion($_SESSION['user_junta_tipo'] ?? 'Junta de Vecinos'),
        ];

        $this->view('socio/dashboard', $data);
    }

    public function reuniones() {
        $socioId = (int)$_SESSION['user_id'];
        $juntaId = (int)$_SESSION['user_junta_id'];
        $calMes = max(1, min(12, (int)($_GET['mes'] ?? date('n'))));
        $calAnio = (int)($_GET['anio'] ?? date('Y'));

        $reuniones = [];
        $proxima = null;
        try {
            $reuniones = $this->reunionConvocadoModel->getReunionesForUsuario($juntaId, $socioId);
            $proxima = $this->reunionConvocadoModel->getProximaForUsuario($juntaId, $socioId);
        } catch (Exception $e) {
            // Tabla reunion_convocados aún no migrada
        }

        $diasConEvento = [];
        foreach ($reuniones as $r) {
            $ts = strtotime($r->fecha_reunion);
            if ((int)date('n', $ts) === $calMes && (int)date('Y', $ts) === $calAnio) {
                $diasConEvento[] = (int)date('j', $ts);
            }
        }
        $diasConEvento = array_values(array_unique($diasConEvento));

        $this->view('socio/reuniones', [
            'title' => 'Mis Reuniones',
            'header_title' => 'Convocatorias y reuniones',
            'header_subtitle' => 'Calendario e historial de asambleas a las que fue convocado',
            'active_menu' => 'reuniones',
            'reuniones' => $reuniones,
            'proxima' => $proxima,
            'cal_mes' => $calMes,
            'cal_anio' => $calAnio,
            'dias_con_evento' => $diasConEvento,
        ]);
    }

    public function solicitar_cambio() {
        require_once APPROOT . '/core/OrgHelper.php';
        require_once APPROOT . '/models/SocioCambioSolicitud.php';
        $socioId = (int)$_SESSION['user_id'];
        $juntaId = (int)$_SESSION['user_junta_id'];
        $membresia = $this->membresiaModel->getById((int)($_SESSION['membership_id'] ?? 0));
        if (!$membresia) {
            $membresia = $this->membresiaModel->getByUsuarioJunta($socioId, $juntaId);
        }

        $socio = $this->userModel->getUserById($socioId);
        $socio = $this->membresiaModel->overlayDomicilioOnUser($socio, $juntaId);
        $cambioPendiente = $this->cambioModel->getPendingForUsuarioJunta($socioId, $juntaId);

        $this->db->query('SELECT * FROM calles WHERE junta_id = :junta_id ORDER BY nombre ASC');
        $this->db->bind(':junta_id', $juntaId);
        $calles = $this->db->resultSet();

        $values = [
            'email' => $socio->email ?? '',
            'telefono' => $socio->telefono ?? '',
            'genero' => $socio->genero ?? '',
            'fecha_nacimiento' => !empty($socio->fecha_nacimiento) ? substr($socio->fecha_nacimiento, 0, 10) : '',
            'estado_civil' => $socio->estado_civil ?? '',
            'nacionalidad' => $socio->nacionalidad ?? '',
            'profesion' => $socio->profesion ?? '',
            'calle_id' => $socio->calle_id ?? '',
            'numero_casa' => $socio->numero_casa ?? '',
            'direccion_texto' => $socio->direccion_texto ?? '',
            'latitud' => $socio->latitud ?? '',
            'longitud' => $socio->longitud ?? '',
            'link_google' => $socio->link_google ?? '',
        ];
        if ($cambioPendiente) {
            $values = array_merge($values, SocioCambioSolicitud::decodeDatos($cambioPendiente));
        }

        $data = [
            'title' => 'Solicitar cambio de datos',
            'header_title' => 'Actualizar mis datos',
            'header_subtitle' => 'Los cambios quedarán pendientes hasta que un administrador los apruebe',
            'active_menu' => 'solicitar_cambio',
            'socio' => $socio,
            'membresia' => $membresia,
            'calles' => $calles,
            'values' => $values,
            'cambio_pendiente' => $cambioPendiente,
            'org_tipo' => $_SESSION['user_junta_tipo'] ?? 'Junta de Vecinos',
            'uses_calles' => OrgHelper::usesCallesJurisdiccion($_SESSION['user_junta_tipo'] ?? 'Junta de Vecinos'),
            'junta_comuna' => $_SESSION['user_junta_comuna'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
            'success' => $_SESSION['success_msg'] ?? '',
        ];
        unset($_SESSION['error_msg'], $_SESSION['success_msg']);
        $this->view('socio/solicitar_cambio', $data);
    }

    public function enviar_solicitud() {
        require_once APPROOT . '/core/SocioInput.php';
        require_once APPROOT . '/core/SocioGeoref.php';
        require_once APPROOT . '/core/OrgHelper.php';
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/socio/solicitar_cambio');
            return;
        }

        $socioId = (int)$_SESSION['user_id'];
        $juntaId = (int)$_SESSION['user_junta_id'];
        $post = $this->sanitizePost();
        $profile = SocioInput::parseProfileFromPost($post);
        $georef = SocioGeoref::parseFromPost($post);

        $datos = [
            'email' => mb_strtolower(trim($post['email'] ?? ''), 'UTF-8'),
            'telefono' => $profile['telefono'],
            'genero' => $profile['genero'],
            'fecha_nacimiento' => $profile['fecha_nacimiento'],
            'estado_civil' => $profile['estado_civil'],
            'nacionalidad' => $profile['nacionalidad'],
            'profesion' => $profile['profesion'],
            'calle_id' => $post['calle_id'] ?? null,
            'numero_casa' => trim($post['numero_casa'] ?? ''),
            'direccion_texto' => trim($post['direccion_texto'] ?? ''),
            'latitud' => $georef['latitud'],
            'longitud' => $georef['longitud'],
            'link_google' => $georef['link_google'],
        ];

        if ($datos['email'] === '' || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_msg'] = 'Ingrese un correo electrónico válido.';
            $this->redirect('/socio/solicitar_cambio');
            return;
        }
        if ($profileError = SocioInput::validateProfile($datos, true)) {
            $_SESSION['error_msg'] = $profileError;
            $this->redirect('/socio/solicitar_cambio');
            return;
        }
        if (OrgHelper::usesCallesJurisdiccion($_SESSION['user_junta_tipo'] ?? '')) {
            if (empty($datos['calle_id']) || $datos['numero_casa'] === '') {
                $_SESSION['error_msg'] = 'Seleccione calle e indique número de casa.';
                $this->redirect('/socio/solicitar_cambio');
                return;
            }
        } elseif ($datos['direccion_texto'] === '') {
            $_SESSION['error_msg'] = 'Indique su dirección.';
            $this->redirect('/socio/solicitar_cambio');
            return;
        }

        $membresia = $this->membresiaModel->getByUsuarioJunta($socioId, $juntaId);
        $created = $this->cambioModel->create($socioId, $juntaId, $membresia ? (int)$membresia->id : null, $datos);
        if ($created !== null) {
            $_SESSION['success_msg'] = 'Su solicitud fue enviada. Un administrador revisará los cambios antes de aplicarlos.';
            $this->redirect('/socio/dashboard');
            return;
        }
        $detail = trim($this->cambioModel->getLastError());
        $_SESSION['error_msg'] = $detail !== ''
            ? $detail
            : 'No se pudo registrar la solicitud. Verifique que ejecutó la migración SQL.';
        $this->redirect('/socio/solicitar_cambio');
    }

    public function comprobantes() {
        $socioId = $_SESSION['user_id'];
        $juntaId = $_SESSION['user_junta_id'];

        $transacciones = $this->transaccionModel->getTransaccionesBySocio($socioId, $juntaId);

        $data = [
            'title' => 'Mis Comprobantes de Pago',
            'header_title' => 'Historial de Comprobantes',
            'header_subtitle' => 'Visualice e imprima los comprobantes de sus cuotas registradas por la directiva',
            'active_menu' => 'comprobantes',
            'transacciones' => $transacciones,
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ];
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        $this->view('socio/comprobantes', $data);
    }

    public function comprobante($id) {
        $socioId = (int)$_SESSION['user_id'];
        $pago = $this->transaccionModel->getComprobanteById($id);

        if (!$pago || (int)$pago->socio_id !== $socioId
            || (int)$pago->junta_id !== (int)($_SESSION['user_junta_id'] ?? 0)) {
            $_SESSION['error_msg'] = 'No tiene autorización para visualizar este comprobante.';
            $this->redirect('/socio/comprobantes');
            return;
        }

        $data = [
            'title' => 'Comprobante Folio #' . str_pad($pago->id, 6, '0', STR_PAD_LEFT),
            'pago' => $pago,
            'is_socio_view' => true,
            'back_url' => URLROOT . '/socio/comprobantes',
        ];

        $this->view('admin/comprobante_detalle', $data);
    }
}
