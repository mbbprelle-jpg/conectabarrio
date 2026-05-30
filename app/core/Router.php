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
            }
        }

        // 3. Obtener parámetros restantes
        $this->params = $url ? array_values($url) : [];

        // 4. Filtro de Seguridad de Acceso (RBAC)
        $this->checkAccessControl();

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
            'AuthController' => ['login', 'logout', 'authenticate', 'recover', 'select_context', 'set_context']
        ];

        // Si la ruta no es pública y el usuario no está logueado, forzar login
        $isPublic = isset($publicRoutes[$controllerClass]) && in_array($method, $publicRoutes[$controllerClass]);

        if (!$isPublic && !$isLoggedIn) {
            header('location: ' . URLROOT . '/auth/login');
            exit;
        }

        // Si está logueado e intenta entrar al login, redirigir a su dashboard correspondiente
        if ($isLoggedIn && $controllerClass === 'AuthController' && $method === 'login') {
            $this->redirectByUserRole($userRole);
        }

        // Restringir accesos basados en el nombre del controlador
        if ($isLoggedIn) {
            if ($controllerClass === 'MaestroController' && $userRole !== 'maestro') {
                $this->unauthorized();
            }
            if ($controllerClass === 'AdminController' && $userRole !== 'admin') {
                if ($userRole === 'socio') {
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
}
