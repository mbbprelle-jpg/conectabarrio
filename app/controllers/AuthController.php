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

        $data = [
            'title' => 'Iniciar Sesión',
            'rut_or_email' => '',
            'error' => ''
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
                'error' => ''
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
                // Crear Sesión del Usuario
                $this->createUserSession($loggedInUser);
            } else {
                $data['error'] = 'Credenciales incorrectas o usuario inactivo.';
                $this->view('auth/login', $data);
            }
        } else {
            $this->redirect('/auth/login');
        }
    }

    // Crear sesión e inicializar variables de sesión
    private function createUserSession($user) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_rut'] = $user->rut;
        $_SESSION['user_nombre'] = $user->nombre;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_rol'] = $user->rol;
        $_SESSION['user_junta_id'] = $user->junta_id;
        $_SESSION['must_change'] = $user->must_change ?? false;
        
        // Obtener el nombre de la Junta de Vecinos si tiene una
        if ($user->junta_id) {
            $juntaModel = $this->model('JuntaVecinos');
            $junta = $juntaModel->getJuntaById($user->junta_id);
            if ($junta) {
                $_SESSION['user_junta_nombre'] = $junta->nombre;
                $_SESSION['user_junta_comuna'] = $junta->comuna;
                $_SESSION['user_junta_plan'] = $junta->plan ?? 'basico';
                $_SESSION['user_junta_precio_anual'] = $junta->precio_anual ?? 0;
            } else {
                $_SESSION['user_junta_nombre'] = 'Junta Sin Nombre';
            }
        } else {
            $_SESSION['user_junta_nombre'] = 'Global';
        }

        // Redirigir según el rol del usuario
        $this->redirectByRole($user->rol);
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
        // Si el usuario debe cambiar la contraseña, redirigir al formulario de cambio
        if (isset($_SESSION['must_change']) && $_SESSION['must_change']) {
            $this->redirect('/auth/resetPassword');
            exit;
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
                $this->redirect('/auth/logout');
        }
    }

    // Mostrar formulario de recuperación de contraseña
    public function recover() {
        // Si se envía el formulario (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $data = ['title' => 'Recuperar Contraseña', 'error' => 'Ingrese su correo electrónico.'];
                $this->view('auth/recover', $data);
                return;
            }
            // Verificar si el email existe
            $user = $this->userModel->findUserByEmail($email);
            if (!$user) {
                $data = ['title' => 'Recuperar Contraseña', 'error' => 'Correo no encontrado.'];
                $this->view('auth/recover', $data);
                return;
            }
            // Generar contraseña temporal
            $tempPass = bin2hex(random_bytes(4)); // 8 caracteres hex
            $hash = password_hash($tempPass, PASSWORD_DEFAULT);
            // Guardar temp hash y flag en DB
            $this->userModel->setTempPassword($user->id, $hash);
            // Enviar email (usa configuración existente)
            $subject = 'Recuperación de contraseña - ConectaBarrio';
            $message = "Hola {$user->nombre},\n\nSu contraseña temporal es: {$tempPass}\nPor favor ingrese al portal y cambie su contraseña inmediatamente.\n\nSaludos,\nEquipo ConectaBarrio";
            if (!Mailer::isConfigured()) {
    $data = ['title' => 'Recuperar Contraseña', 'error' => 'SMTP no configurado. Contacte al administrador.'];
    $this->view('auth/recover', $data);
    return;
}
$mailResult = Mailer::send($email, $subject, $message, SMTP_FROM_EMAIL);
if ($mailResult['ok']) {
    $data = ['title' => 'Recuperar Contraseña', 'success' => 'Se ha enviado una contraseña temporal a su correo.'];
} else {
    $data = ['title' => 'Recuperar Contraseña', 'error' => 'Error al enviar el correo: ' . ($mailResult['error'] ?? 'desconocido')];
}
$this->view('auth/recover', $data);
return;
            $data = ['title' => 'Recuperar Contraseña', 'success' => 'Se ha enviado una contraseña temporal a su correo.'];
            $this->view('auth/recover', $data);
            return;
        }
        // Mostrar formulario
        $data = ['title' => 'Recuperar Contraseña'];
        $this->view('auth/recover', $data);
    }

    // Mostrar formulario para cambiar contraseña temporal
    public function resetPassword() {
        // Verificar que el usuario esté logueado y deba cambiar
        if (!isset($_SESSION['user_id']) || empty($_SESSION['must_change'])) {
            $this->redirect('/auth/login');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPass = trim($_POST['new_password'] ?? '');
            $confirm = trim($_POST['confirm_password'] ?? '');
            if (empty($newPass) || $newPass !== $confirm) {
                $data = ['title' => 'Cambiar Contraseña', 'error' => 'Las contraseñas no coinciden o están vacías.'];
                $this->view('auth/reset_password', $data);
                return;
            }
            // Guardar nueva contraseña y quitar flag
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $this->userModel->resetPassword($_SESSION['user_id'], $hash);
            unset($_SESSION['must_change']);
            $data = ['title' => 'Cambiar Contraseña', 'success' => 'Contraseña actualizada correctamente.'];
            $this->view('auth/reset_password', $data);
            return;
        }
        $data = ['title' => 'Cambiar Contraseña'];
        $this->view('auth/reset_password', $data);
    }

}
