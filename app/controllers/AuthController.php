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
    private function redirectByRole($role) {
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
}
