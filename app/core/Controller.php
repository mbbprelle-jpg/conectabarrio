<?php
/*
 * Base Controller
 * Carga los modelos y las vistas
 */
class Controller {
    // Cargar modelo
    public function model($model) {
        // Requerir archivo de modelo
        require_once APPROOT . '/models/' . $model . '.php';
        // Instanciar el modelo
        return new $model();
    }

    // Cargar vista
    public function view($view, $data = []) {
        // Verificar si existe el archivo de vista
        if (file_exists(APPROOT . '/views/' . $view . '.php')) {
            require_once APPROOT . '/views/' . $view . '.php';
        } else {
            // La vista no existe
            die('La vista no existe: ' . htmlspecialchars($view));
        }
    }

    // Generar un Token CSRF
    protected function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Verificar Token CSRF
    protected function verifyCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            die('Error de seguridad CSRF detectado.');
        }
        return true;
    }

    // Redirigir a una ruta específica
    protected function redirect($page) {
        header('location: ' . URLROOT . $page);
        exit;
    }

    // Obtener los datos sanitizados de formularios POST
    protected function sanitizePost() {
        $sanitized = [];
        foreach ($_POST as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = filter_var_array($value, FILTER_SANITIZE_SPECIAL_CHARS);
            } else {
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            }
        }
        return $sanitized;
    }
}
