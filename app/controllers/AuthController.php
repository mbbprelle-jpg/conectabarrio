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

    // Acción temporal segura para ejecutar todas las migraciones contables y comerciales desde el navegador
    public function migrar_planes() {
        if (($_GET['key'] ?? '') !== 'migrar123') {
            die('Llave de seguridad incorrecta. Acceso denegado.');
        }

        try {
            echo "<h2>Iniciando migración unificada para ConectaBarrio...</h2>";
            $db = new Database();

            // 1. Verificar y agregar columna 'mes_inicio' en juntas_vecinos
            echo "Verificando columnas en 'juntas_vecinos'...<br>";
            $db->query("DESCRIBE juntas_vecinos");
            $fields = $db->resultSet();
            
            $hasMesInicio = false;
            $hasPlan = false;
            $hasPrecioAnual = false;
            foreach ($fields as $f) {
                if ($f->Field === 'mes_inicio') $hasMesInicio = true;
                if ($f->Field === 'plan') $hasPlan = true;
                if ($f->Field === 'precio_anual') $hasPrecioAnual = true;
            }

            if (!$hasMesInicio) {
                echo "Añadiendo columna 'mes_inicio' a 'juntas_vecinos'...<br>";
                $db->query("ALTER TABLE juntas_vecinos ADD COLUMN mes_inicio VARCHAR(7) NOT NULL DEFAULT '2026-01' AFTER comuna");
                $db->execute();
                echo "✓ Columna 'mes_inicio' agregada con éxito.<br>";
            } else {
                echo "✓ La columna 'mes_inicio' ya existe.<br>";
            }

            // 2. Verificar y agregar columnas 'plan' y 'precio_anual' en juntas_vecinos
            if (!$hasPlan) {
                echo "Añadiendo columna 'plan' a 'juntas_vecinos'...<br>";
                $db->query("ALTER TABLE juntas_vecinos ADD COLUMN plan ENUM('basico', 'mediano', 'premium') NOT NULL DEFAULT 'basico' AFTER mes_inicio");
                $db->execute();
                echo "✓ Columna 'plan' agregada con éxito.<br>";
            } else {
                echo "✓ La columna 'plan' ya existe.<br>";
            }

            if (!$hasPrecioAnual) {
                echo "Añadiendo columna 'precio_anual' a 'juntas_vecinos'...<br>";
                $db->query("ALTER TABLE juntas_vecinos ADD COLUMN precio_anual INT NOT NULL DEFAULT 0 AFTER plan");
                $db->execute();
                echo "✓ Columna 'precio_anual' agregada con éxito.<br>";
            } else {
                echo "✓ La columna 'precio_anual' ya existe.<br>";
            }

            // 3. Crear tabla cierres_mensuales si no existe
            echo "Verificando tabla 'cierres_mensuales'...<br>";
            $sqlCierres = "CREATE TABLE IF NOT EXISTS cierres_mensuales (
                id INT AUTO_INCREMENT PRIMARY KEY,
                junta_id INT NOT NULL,
                mes VARCHAR(7) NOT NULL,
                ingresos INT NOT NULL,
                egresos INT NOT NULL,
                saldo_anterior INT NOT NULL DEFAULT 0,
                saldo_final INT NOT NULL DEFAULT 0,
                saldo_neto INT NOT NULL,
                estado TINYINT(1) DEFAULT 1,
                cerrado_por INT NOT NULL,
                comentario TEXT NULL,
                enviado_correo TINYINT(1) DEFAULT 0,
                fecha_cierre TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_junta_mes (junta_id, mes),
                FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE,
                FOREIGN KEY (cerrado_por) REFERENCES usuarios(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            $db->query($sqlCierres);
            $db->execute();
            echo "✓ Tabla 'cierres_mensuales' creada o ya existente.<br>";

            // 4. Configurar junta semilla (ID: 1) como Premium por defecto con precio de oferta
            echo "Actualizando junta semilla (ID: 1) a Plan Premium...<br>";
            $db->query("UPDATE juntas_vecinos SET plan = 'premium', precio_anual = 119880 WHERE id = 1");
            $db->execute();
            echo "✓ Junta semilla configurada con éxito.<br>";

            echo "<h3>🎉 ¡MIGRACIÓN DE BASE DE DATOS COMPLETADA CON ÉXITO!</h3>";
        } catch (Exception $e) {
            echo "<h3 style='color:red;'>❌ Error durante la migración: " . $e->getMessage() . "</h3>";
        }
    }
}
