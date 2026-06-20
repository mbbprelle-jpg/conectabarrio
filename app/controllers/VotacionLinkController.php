<?php
class VotacionLinkController extends Controller {
    public function ingresar($token = '') {
        require_once APPROOT . '/models/Votacion.php';
        $model = new Votacion();
        if (!$model->tablesExist()) {
            die('Módulo de votaciones no disponible.');
        }
        $v = $model->getByToken((string)$token);
        if (!$v) {
            die('Enlace de votación no válido.');
        }
        $_SESSION['pending_votacion_token'] = (string)$token;
        if (isset($_SESSION['user_id'])) {
            $this->redirectToVote($v);
            return;
        }
        header('location: ' . URLROOT . '/auth/login');
        exit;
    }

    private function redirectToVote(object $v): void {
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
