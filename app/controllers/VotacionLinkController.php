<?php
class VotacionLinkController extends Controller {
    public function ingresar($token = '') {
        require_once APPROOT . '/models/Votacion.php';
        $model = new Votacion();
        if (!$model->tablesExist()) {
            die('Módulo de votaciones no disponible.');
        }
        $model->syncEstadosActivas();
        $v = $model->getByToken((string)$token);
        if (!$v) {
            $this->view('auth/login_votacion', [
                'title' => 'Enlace de consulta',
                'public_layout' => true,
                'skip_chartjs' => true,
                'votacion' => null,
                'error' => '',
                'rut_or_email' => '',
                'aviso' => '',
            ]);
            return;
        }
        $_SESSION['pending_votacion_token'] = (string)$token;
        if (isset($_SESSION['user_id'])) {
            $this->redirectToVote($v);
            return;
        }
        $error = (string)($_SESSION['votacion_login_error'] ?? '');
        $rut = (string)($_SESSION['votacion_login_rut'] ?? '');
        unset($_SESSION['votacion_login_error'], $_SESSION['votacion_login_rut']);
        $this->view('auth/login_votacion', [
            'title' => 'Ingreso a la consulta',
            'public_layout' => true,
            'skip_chartjs' => true,
            'votacion' => $v,
            'abierta' => $model->isActiva($v),
            'error' => $error,
            'rut_or_email' => $rut,
            'aviso' => $this->avisoIngreso($v, $model->isActiva($v)),
        ]);
    }

    private function avisoIngreso(object $v, bool $abierta): string {
        $nombre = ($v->tipo ?? '') === 'encuesta' ? 'encuesta' : 'votación';
        if ($abierta) {
            return 'El enlace está bien. Para participar en esta ' . $nombre . ' primero debe autenticarse con su RUT o correo. Al ingresar se abre la consulta.';
        }
        $inicio = strtotime((string)$v->fecha_inicio);
        if ($inicio !== false && $inicio > time()) {
            return 'El enlace está bien. Esta ' . $nombre . ' aún no está abierta. Comienza el ' . date('d/m/Y H:i', $inicio) . '. Autentíquese para entrar cuando esté disponible.';
        }
        return 'El enlace está bien. Esta ' . $nombre . ' ya cerró. Si ya participó, autentíquese para ver el resultado cuando corresponda.';
    }

    private function redirectToVote(object $v): void {
        unset($_SESSION['pending_votacion_token']);
        require_once APPROOT . '/core/AuthContext.php';
        $rol = $_SESSION['user_rol'] ?? '';
        if ($rol === 'admin' || ($rol === 'socio' && AuthContext::canManageVotaciones())) {
            header('location: ' . URLROOT . '/admin/votacion_votar/' . (int)$v->id);
        } else {
            header('location: ' . URLROOT . '/socio/votacion_votar/' . (int)$v->id);
        }
        exit;
    }
}
