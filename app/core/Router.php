<?php
/*
 * App Router Class
 * Parsea la URL y carga el controlador correspondiente.
 * Implementa control de accesos por roles (RBAC).
 */
class Router {
    protected $currentController = 'AuthController';
    protected $currentMethod = 'login';
    protected $params = [];

    public function __construct() {
        $url = $this->getUrl();

        // 1. Determinar el controlador
        if (isset($url[0])) {
            $controllerName = ucwords($url[0]) . 'Controller';
            if (file_exists(APPROOT . '/controllers/' . $controllerName . '.php')) {
                $this->currentController = $controllerName;
                unset($url[0]);
            }
        }

        // Requerir el controlador
        require_once APPROOT . '/controllers/' . $this->currentController . '.php';

        // Instanciar la clase del controlador
        $this->currentController = new $this->currentController;

        // 2. Determinar el método
        if (isset($url[1])) {
            if (method_exists($this->currentController, $url[1])) {
                $this->currentMethod = $url[1];
                unset($url[1]);
            } else {
                $this->notFound($url[1]);
            }
        } elseif ($this->currentController instanceof AdminController && method_exists($this->currentController, 'dashboard')) {
            $this->currentMethod = 'dashboard';
        }

        // 3. Obtener parámetros restantes
        $this->params = $url ? array_values($url) : [];

        // 4. Filtro de Seguridad de Acceso (RBAC)
        $this->checkAccessControl();

        if (!method_exists($this->currentController, $this->currentMethod)) {
            $this->notFound($this->currentMethod);
        }

        // Llamar al callback con los parámetros
        call_user_func_array([$this->currentController, $this->currentMethod], $this->params);
    }

    // Parsea la URL
    public function getUrl() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
        return [];
    }

    // Control de Acceso por Roles (RBAC)
    private function checkAccessControl() {
        $controllerClass = get_class($this->currentController);
        $method = $this->currentMethod;

        $isLoggedIn = isset($_SESSION['user_id']);
        $userRole = $isLoggedIn ? $_SESSION['user_rol'] : null;

        // Rutas públicas que no requieren login
        $publicRoutes = [
            'AuthController' => ['login', 'logout', 'authenticate', 'recover', 'select_context', 'set_context'],
            'InviteController' => ['registro', 'registrar', 'verificar_rut'],
        ];

        // Si la ruta no es pública y el usuario no está logueado, forzar login
        $isPublic = isset($publicRoutes[$controllerClass]) && in_array($method, $publicRoutes[$controllerClass]);

        if (!$isPublic && !$isLoggedIn) {
            header('location: ' . URLROOT . '/auth/login');
            exit;
        }

        if ($isLoggedIn && !empty($_SESSION['needs_account_completion'])) {
            $allowedCompletion = ['completar_cuenta', 'logout'];
            if ($controllerClass !== 'AuthController' || !in_array($method, $allowedCompletion, true)) {
                header('location: ' . URLROOT . '/auth/completar_cuenta');
                exit;
            }
        } elseif ($isLoggedIn && !empty($_SESSION['must_change'])) {
            $allowedWithMustChange = ['resetPassword', 'logout'];
            if ($controllerClass !== 'AuthController' || !in_array($method, $allowedWithMustChange, true)) {
                header('location: ' . URLROOT . '/auth/resetPassword');
                exit;
            }
        }

        // Si está logueado e intenta entrar al login, redirigir a su dashboard correspondiente
        if ($isLoggedIn && $controllerClass === 'AuthController' && $method === 'login') {
            if (!empty($_SESSION['needs_account_completion'])) {
                header('location: ' . URLROOT . '/auth/completar_cuenta');
                exit;
            }
            if (!empty($_SESSION['must_change'])) {
                header('location: ' . URLROOT . '/auth/resetPassword');
                exit;
            }
            $this->redirectByUserRole($userRole);
        }

        // Restringir accesos basados en el nombre del controlador
        if ($isLoggedIn) {
            if ($controllerClass === 'MaestroController' && $userRole !== 'maestro') {
                $this->unauthorized();
            }
            if ($controllerClass === 'AdminController' && $userRole !== 'admin') {
                if ($userRole === 'socio') {
                    if ($method === 'reunion_minuta') {
                        return;
                    }
                    require_once APPROOT . '/core/AuthContext.php';
                    $allowed = AuthContext::adminMethodsForSocioDelegado();
                    if (!in_array($method, $allowed, true)) {
                        $this->unauthorized();
                    }
                } else {
                    $this->unauthorized();
                }
            }
            if ($controllerClass === 'SocioController' && $userRole !== 'socio') {
                $this->unauthorized();
            }
        }
    }

    // Redirección rápida por rol
    private function redirectByUserRole($role) {
        switch ($role) {
            case 'maestro':
                header('location: ' . URLROOT . '/maestro/dashboard');
                break;
            case 'admin':
                header('location: ' . URLROOT . '/admin/dashboard');
                break;
            case 'socio':
                header('location: ' . URLROOT . '/socio/dashboard');
                break;
            default:
                header('location: ' . URLROOT . '/auth/logout');
        }
        exit;
    }

    // Respuesta no autorizado
    private function unauthorized() {
        http_response_code(403);
        die('Acceso Denegado: No tienes permisos para acceder a esta sección.');
    }

    private function notFound($method = null) {
        http_response_code(404);
        $hint = $method ? ' La ruta solicitada no existe: ' . htmlspecialchars((string)$method) . '.' : '';
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>ConectaBarrio</title></head>';
        echo '<body style="font-family:sans-serif;padding:2rem;text-align:center;max-width:520px;margin:0 auto;">';
        echo '<h1>Página no encontrada</h1>';
        echo '<p>No existe esa sección en el sistema.' . $hint . '</p>';
        echo '<p style="font-size:0.9rem;color:#555;">Si buscaba <strong>Conceptos de Caja</strong>, use <a href="' . URLROOT . '/admin/conceptos_caja">/admin/conceptos_caja</a> (con «s»).</p>';
        echo '<p><a href="' . URLROOT . '/admin/dashboard">Ir al panel</a></p>';
        echo '</body></html>';
        exit;
    }
}
