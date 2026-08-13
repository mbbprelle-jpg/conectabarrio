<?php
class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        // Cargar el modelo del Usuario
        $this->userModel = $this->model('User');
    }

    // Cargar la vista de Login
    public function login() {
        // Si ya está logueado, redirigir según rol
        if (isset($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['user_rol']);
        }

        $success = $_SESSION['success_msg'] ?? '';
        unset($_SESSION['success_msg']);

        $data = [
            'title' => 'Iniciar Sesión',
            'rut_or_email' => '',
            'error' => '',
            'success' => $success,
            'public_layout' => true,
        ];

        $this->view('auth/login', $data);
    }

    // Procesar la autenticación de usuario (POST)
    public function authenticate() {
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Sanitizar datos del POST
            $post = $this->sanitizePost();
            
            $rutOrEmail = $post['rut_or_email'] ?? '';
            $password = $post['password'] ?? '';

            $data = [
                'title' => 'Iniciar Sesión',
                'rut_or_email' => $rutOrEmail,
                'error' => '',
                'public_layout' => true,
            ];

            // Validar campos vacíos
            if (empty($rutOrEmail) || empty($password)) {
                $data['error'] = 'Por favor, ingrese todos los campos requeridos.';
                $this->view('auth/login', $data);
                return;
            }

            // Intentar inicio de sesión
            $loggedInUser = $this->userModel->login($rutOrEmail, $password);

            if ($loggedInUser) {
                $this->beginUserSession($loggedInUser);
            } else {
                $pendingUser = null;
                if (filter_var($rutOrEmail, FILTER_VALIDATE_EMAIL)) {
                    $pendingUser = $this->userModel->findUserByEmail($rutOrEmail);
                } else {
                    $pendingUser = $this->userModel->findUserByRut($rutOrEmail);
                }
                if ($pendingUser && isset($pendingUser->status) && $pendingUser->status === 'pending') {
                    $data['error'] = 'Su solicitud de registro aún está pendiente de aprobación por la directiva.';
                } else {
                    $data['error'] = 'Credenciales incorrectas o usuario inactivo.';
                }
                $this->view('auth/login', $data);
            }
        } else {
            $this->redirect('/auth/login');
        }
    }

    private function beginUserSession($user) {
        if ($user->rol === 'maestro') {
            AuthContext::applyMaestro($user);
            $this->redirectByRole('maestro');
            return;
        }

        $membresiaModel = $this->model('Membresia');
        $membresiaModel->ensureFromUsuario($user);
        $membresias = $membresiaModel->getActiveByUsuario($user->id);

        if (empty($membresias) && !empty($user->junta_id)) {
            $this->createLegacySession($user);
            return;
        }

        if (count($membresias) === 1) {
            AuthContext::applyMembership($membresias[0], $user);
            $this->redirectByRole($membresias[0]->rol);
            return;
        }

        if (count($membresias) > 1) {
            $_SESSION['auth_pending_user_id'] = $user->id;
            $this->redirect('/auth/select_context');
            return;
        }

        $this->createLegacySession($user);
    }

    public function select_context() {
        $userId = $_SESSION['auth_pending_user_id'] ?? null;
        if (!$userId) {
            $this->redirect('/auth/login');
            return;
        }
        $user = $this->userModel->getUserById($userId);
        $membresiaModel = $this->model('Membresia');
        $membresias = $membresiaModel->getActiveByUsuario($userId);
        if (count($membresias) <= 1) {
            $this->redirect('/auth/login');
            return;
        }
        $data = [
            'title' => 'Seleccionar acceso',
            'nombre' => $user->nombre ?? '',
            'membresias' => $membresias,
            'error' => ''
        ];
        $this->view('auth/select_context', $data);
    }

    public function set_context() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/auth/login');
            return;
        }
        $userId = $_SESSION['auth_pending_user_id'] ?? null;
        $membershipId = (int)($_POST['membership_id'] ?? 0);
        if (!$userId || $membershipId <= 0) {
            $this->redirect('/auth/login');
            return;
        }
        $membresiaModel = $this->model('Membresia');
        $membership = $membresiaModel->getById($membershipId);
        if (!$membership || (int)$membership->usuario_id !== (int)$userId) {
            $this->redirect('/auth/login');
            return;
        }
        $user = $this->userModel->getUserById($userId);
        unset($_SESSION['auth_pending_user_id']);
        AuthContext::applyMembership($membership, $user);
        $this->userModel->db->query('UPDATE usuarios SET junta_id = :junta_id, rol = :rol WHERE id = :id');
        $this->userModel->db->bind(':junta_id', $membership->junta_id);
        $this->userModel->db->bind(':rol', $membership->rol);
        $this->userModel->db->bind(':id', $userId);
        $this->userModel->db->execute();
        $this->redirectByRole($membership->rol);
    }

    private function createLegacySession($user) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_rut'] = $user->rut;
        $_SESSION['user_nombre'] = $user->nombre;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_rol'] = $user->rol;
        $_SESSION['user_junta_id'] = $user->junta_id;
        $_SESSION['must_change'] = $user->must_change ?? false;
        require_once APPROOT . '/core/AuthContext.php';
        AuthContext::syncAccountCompletionFlag($user);
        $_SESSION['membership_id'] = null;
        $_SESSION['user_cargo'] = null;
        $_SESSION['permiso_gestion_socios'] = 0;
        $_SESSION['permiso_registro_pagos'] = 0;
        $_SESSION['permiso_todos'] = 0;
        $_SESSION['permiso_mapa_socios'] = 0;
        $_SESSION['permiso_flujo_caja'] = 0;
        $_SESSION['permiso_documentos'] = 0;
        $_SESSION['permiso_reuniones'] = 0;
        $_SESSION['mapa_socios_habilitado'] = 0;
        $_SESSION['flujo_caja_habilitado'] = 0;
        $_SESSION['documentos_habilitado'] = 0;

        if ($user->junta_id) {
            $juntaModel = $this->model('JuntaVecinos');
            $junta = $juntaModel->getJuntaById($user->junta_id);
            if ($junta) {
                $_SESSION['user_junta_nombre'] = $junta->nombre;
                $_SESSION['user_junta_comuna'] = $junta->comuna;
                $_SESSION['user_junta_plan'] = $junta->plan ?? 'basico';
                $_SESSION['user_junta_precio_anual'] = $junta->precio_anual ?? 0;
                if ($juntaModel->hasMapaSociosColumn()) {
                    $_SESSION['mapa_socios_habilitado'] = (int)($junta->mapa_socios_habilitado ?? 0);
                }
                if ($juntaModel->hasFlujoCajaColumn()) {
                    $_SESSION['flujo_caja_habilitado'] = (int)($junta->flujo_caja_habilitado ?? 0);
                }
                if ($juntaModel->hasDocumentosColumn()) {
                    $_SESSION['documentos_habilitado'] = (int)($junta->documentos_habilitado ?? 0);
                }
            } else {
                $_SESSION['user_junta_nombre'] = 'Junta Sin Nombre';
            }
        } else {
            $_SESSION['user_junta_nombre'] = 'Global';
        }

        $this->redirectByRole($user->rol);
    }

    // Crear sesión e inicializar variables de sesión (legacy)
    private function createUserSession($user) {
        $this->beginUserSession($user);
    }

    // Cierre de sesión seguro
    public function logout() {
        // Limpiar todas las variables de sesión
        $_SESSION = array();

        // Destruir la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destruir la sesión
        session_destroy();

        // Redirigir al login
        header('location: ' . URLROOT . '/auth/login');
        exit;
    }

    // Redirección centralizada por rol
    // Redirigir según el rol del usuario
    private function redirectByRole($role) {
        if (!empty($_SESSION['needs_account_completion'])) {
            $this->redirect('/auth/completar_cuenta');
            exit;
        }
        // Si el usuario debe cambiar la contraseña, redirigir al formulario de cambio
        if (!empty($_SESSION['must_change'])) {
            $this->redirect('/auth/resetPassword');
            exit;
        }
        if (!empty($_SESSION['pending_votacion_token'])) {
            $token = $_SESSION['pending_votacion_token'];
            unset($_SESSION['pending_votacion_token']);
            require_once APPROOT . '/models/Votacion.php';
            $vModel = new Votacion();
            $v = $vModel->getByToken($token);
            if ($v) {
                if ($role === 'admin') {
                    $this->redirect('/admin/votacion_votar/' . (int)$v->id);
                } else {
                    $this->redirect('/socio/votacion_votar/' . (int)$v->id);
                }
                exit;
            }
        }
        switch ($role) {
            case 'maestro':
                $this->redirect('/maestro/dashboard');
                break;
            case 'admin':
                $this->redirect('/admin/dashboard');
                break;
            case 'socio':
                $this->redirect('/socio/dashboard');
                break;
            default:
                $this->redirect('/socio/dashboard');
        }
    }

    // Mostrar formulario de recuperación de contraseña
    public function recover() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $email = trim($_POST['email'] ?? '');
            if ($email === '') {
                $this->view('auth/recover', [
                    'title' => 'Recuperar Contraseña',
                    'error' => 'Ingrese su correo electrónico.',
                    'public_layout' => true,
                ]);
                return;
            }

            $user = $this->userModel->findUserByEmail($email);
            if (!$user) {
                $this->view('auth/recover', [
                    'title' => 'Recuperar Contraseña',
                    'error' => 'Correo no encontrado.',
                    'public_layout' => true,
                ]);
                return;
            }

            $tempPass = bin2hex(random_bytes(4));
            $hash = password_hash($tempPass, PASSWORD_DEFAULT);
            $this->userModel->setTempPassword($user->id, $hash);

            if (!Mailer::isConfigured()) {
                $this->view('auth/recover', [
                    'title' => 'Recuperar Contraseña',
                    'error' => 'SMTP no configurado. Contacte al administrador.',
                    'public_layout' => true,
                ]);
                return;
            }

            $subject = 'Recuperación de contraseña - ConectaBarrio';
            $message = "Hola {$user->nombre},\n\nSu contraseña temporal es: {$tempPass}\nPor favor ingrese al portal y cambie su contraseña inmediatamente.\n\nSaludos,\nEquipo ConectaBarrio";
            $mailResult = Mailer::send($email, $subject, nl2br(htmlspecialchars($message)), SMTP_FROM_EMAIL);

            if (!$mailResult['ok']) {
                $this->view('auth/recover', [
                    'title' => 'Recuperar Contraseña',
                    'error' => 'Error al enviar el correo: ' . ($mailResult['error'] ?? 'desconocido'),
                    'public_layout' => true,
                ]);
                return;
            }

            // Éxito: ir al login para que el usuario no quede pegado en esta pantalla
            $_SESSION['success_msg'] = 'Se envió una contraseña temporal a su correo. Inicie sesión con ella y luego defina una nueva clave.';
            $this->redirect('/auth/login');
            return;
        }

        $this->view('auth/recover', [
            'title' => 'Recuperar Contraseña',
            'public_layout' => true,
        ]);
    }

    // Mostrar formulario para cambiar contraseña temporal
    public function completar_cuenta() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth/login');
            return;
        }
        $user = $this->userModel->getUserById($_SESSION['user_id']);
        if (!$user || !$this->userModel->needsAccountCompletion($user)) {
            $this->redirectByRole($_SESSION['user_rol'] ?? 'socio');
            return;
        }

        $viewData = [
            'title' => 'Completar cuenta',
            'header_title' => 'Activar cuenta de socio',
            'header_subtitle' => 'Registre su correo y contraseña definitiva',
            'error' => '',
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $email = mb_strtolower(trim($_POST['email'] ?? ''), 'UTF-8');
            $newPass = trim($_POST['new_password'] ?? '');
            $confirm = trim($_POST['confirm_password'] ?? '');

            require_once APPROOT . '/core/InviteRutCheck.php';
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $viewData['error'] = 'Ingrese un correo electrónico válido.';
                $this->view('auth/completar_cuenta', $viewData);
                return;
            }
            if (InviteRutCheck::isPlaceholderEmail($email)) {
                $viewData['error'] = 'Debe indicar un correo personal real, no un correo provisional.';
                $this->view('auth/completar_cuenta', $viewData);
                return;
            }
            if (strlen($newPass) < 8) {
                $viewData['error'] = 'La contraseña debe tener al menos 8 caracteres.';
                $this->view('auth/completar_cuenta', $viewData);
                return;
            }
            if ($newPass !== $confirm) {
                $viewData['error'] = 'Las contraseñas no coinciden.';
                $this->view('auth/completar_cuenta', $viewData);
                return;
            }
            $existing = $this->userModel->findUserByEmail($email);
            if ($existing && (int)$existing->id !== (int)$user->id) {
                $viewData['error'] = 'Ese correo ya está registrado por otro usuario.';
                $this->view('auth/completar_cuenta', $viewData);
                return;
            }
            if ($this->userModel->completePrevalidarAccount((int)$user->id, $email, $newPass)) {
                $_SESSION['user_email'] = $email;
                $_SESSION['needs_account_completion'] = 0;
                $_SESSION['must_change'] = 0;
                $_SESSION['success_msg'] = 'Cuenta activada correctamente. Bienvenido al portal.';
                $this->redirectByRole($_SESSION['user_rol'] ?? 'socio');
                return;
            }
            $viewData['error'] = 'No se pudo activar la cuenta. Intente nuevamente o contacte a la directiva.';
        }

        $this->view('auth/completar_cuenta', $viewData);
    }

    public function resetPassword() {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['must_change'])) {
            $this->redirect('/auth/login');
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $newPass = trim($_POST['new_password'] ?? '');
            $confirm = trim($_POST['confirm_password'] ?? '');
            $viewBase = [
                'title' => 'Cambiar Contraseña',
                'header_title' => 'Cambio de contraseña obligatorio',
                'header_subtitle' => 'Ingrese una clave segura para continuar en el portal',
            ];
            if (strlen($newPass) < 8) {
                $this->view('auth/reset_password', $viewBase + ['error' => 'La contraseña debe tener al menos 8 caracteres.']);
                return;
            }
            if ($newPass !== $confirm) {
                $this->view('auth/reset_password', $viewBase + ['error' => 'Las contraseñas no coinciden.']);
                return;
            }
            if ($this->userModel->resetPassword($_SESSION['user_id'], $newPass)) {
                // Quitar el flag de forma explícita para no volver a esta pantalla
                $_SESSION['must_change'] = 0;
                unset($_SESSION['must_change']);
                $_SESSION['success_msg'] = 'Contraseña actualizada correctamente. Ya puede usar el portal.';
                $role = $_SESSION['user_rol'] ?? 'socio';
                $this->redirectByRole($role);
                return;
            }
            $this->view('auth/reset_password', $viewBase + ['error' => 'No se pudo guardar la nueva contraseña. Intente nuevamente.']);
            return;
        }
        $this->view('auth/reset_password', [
            'title' => 'Cambiar Contraseña',
            'header_title' => 'Cambio de contraseña obligatorio',
            'header_subtitle' => 'Ingrese una clave segura para continuar en el portal',
        ]);
    }

    public function changePassword() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth/login');
            return;
        }
        if (!empty($_SESSION['must_change'])) {
            $this->redirect('/auth/resetPassword');
            return;
        }

        $viewData = [
            'title' => 'Cambiar Contraseña',
            'header_title' => 'Cambiar contraseña',
            'header_subtitle' => 'Actualice su clave de acceso al portal',
            'active_menu' => 'password',
            'error' => '',
            'success' => '',
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $current = $_POST['current_password'] ?? '';
            $newPass = trim($_POST['new_password'] ?? '');
            $confirm = trim($_POST['confirm_password'] ?? '');

            if ($current === '' || $newPass === '' || $confirm === '') {
                $viewData['error'] = 'Complete todos los campos.';
                $this->view('auth/change_password', $viewData);
                return;
            }
            if (strlen($newPass) < 8) {
                $viewData['error'] = 'La nueva contraseña debe tener al menos 8 caracteres.';
                $this->view('auth/change_password', $viewData);
                return;
            }
            if ($newPass !== $confirm) {
                $viewData['error'] = 'La confirmación no coincide con la nueva contraseña.';
                $this->view('auth/change_password', $viewData);
                return;
            }

            $result = $this->userModel->changePasswordWithCurrent($_SESSION['user_id'], $current, $newPass);
            if ($result['ok']) {
                $_SESSION['success_msg'] = 'Su contraseña fue actualizada correctamente.';
                $this->redirectByRole($_SESSION['user_rol'] ?? 'socio');
                return;
            }
            $viewData['error'] = $result['error'] ?? 'No se pudo cambiar la contraseña.';
        }

        $this->view('auth/change_password', $viewData);
    }

}
