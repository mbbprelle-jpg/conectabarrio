<?php
class InviteController extends Controller {
    private $invitationModel;
    private $userModel;

    public function __construct() {
        $this->invitationModel = $this->model('Invitation');
        $this->userModel = $this->model('User');
    }

    public function registro($token = '') {
        $token = trim((string)$token);
        $invitation = $this->invitationModel->getValidByToken($token);
        if (!$invitation) {
            $this->view('auth/registro_invitacion', [
                'title' => 'Invitación no válida',
                'error' => 'El enlace de invitación expiró, fue revocado o no es válido. Solicite uno nuevo a la directiva de su organización.',
                'invitation' => null,
                'calles' => [],
            ]);
            return;
        }

        $db = new Database();
        $db->query('SELECT * FROM calles WHERE junta_id = :junta_id ORDER BY nombre ASC');
        $db->bind(':junta_id', $invitation->junta_id);
        $calles = $db->resultSet();

        $this->view('auth/registro_invitacion', [
            'title' => 'Registro de Socio',
            'invitation' => $invitation,
            'token' => $token,
            'calles' => $calles,
            'error' => '',
            'success' => '',
        ]);
    }

    public function registrar() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/auth/login');
            return;
        }
        $post = $this->sanitizePost();
        $token = trim($post['token'] ?? '');
        $invitation = $this->invitationModel->getValidByToken($token);
        if (!$invitation) {
            $this->view('auth/registro_invitacion', [
                'title' => 'Invitación no válida',
                'error' => 'El enlace ya no es válido.',
                'invitation' => null,
                'calles' => [],
            ]);
            return;
        }

        $dataSocio = [
            'junta_id' => $invitation->junta_id,
            'id_socio' => null,
            'rut' => trim($post['rut'] ?? ''),
            'nombres' => trim($post['nombres'] ?? ''),
            'apellido_paterno' => trim($post['apellido_paterno'] ?? ''),
            'apellido_materno' => trim($post['apellido_materno'] ?? ''),
            'email' => trim($post['email'] ?? ''),
            'password' => bin2hex(random_bytes(16)),
            'rol' => 'socio',
            'telefono' => trim($post['telefono'] ?? ''),
            'estado' => 1,
            'calle_id' => $post['calle_id'] ?? null,
            'numero_casa' => trim($post['numero_casa'] ?? ''),
            'fecha_inicio' => !empty($post['fecha_inicio']) ? $post['fecha_inicio'] : date('Y-m-d'),
        ];

        $db = new Database();
        $db->query('SELECT * FROM calles WHERE junta_id = :junta_id ORDER BY nombre ASC');
        $db->bind(':junta_id', $invitation->junta_id);
        $calles = $db->resultSet();

        $renderForm = function ($error) use ($invitation, $token, $calles, $dataSocio) {
            $this->view('auth/registro_invitacion', [
                'title' => 'Registro de Socio',
                'invitation' => $invitation,
                'token' => $token,
                'calles' => $calles,
                'error' => $error,
                'success' => '',
                'old' => $dataSocio,
            ]);
        };

        if ($dataSocio['rut'] === '' || $dataSocio['nombres'] === '' || $dataSocio['apellido_paterno'] === ''
            || $dataSocio['apellido_materno'] === '' || $dataSocio['email'] === ''
            || empty($dataSocio['calle_id']) || $dataSocio['numero_casa'] === '') {
            $renderForm('Complete todos los campos obligatorios.');
            return;
        }

        if ($this->userModel->findUserByRut($dataSocio['rut'])) {
            $renderForm('El RUT ya está registrado en el sistema.');
            return;
        }
        if ($this->userModel->findUserByEmail($dataSocio['email'])) {
            $renderForm('El correo electrónico ya está registrado en el sistema.');
            return;
        }

        try {
            if (!$this->userModel->createPending($dataSocio, (int)$invitation->id)) {
                $renderForm('No se pudo enviar la solicitud. Intente nuevamente.');
                return;
            }
        } catch (Exception $e) {
            $renderForm('Error al registrar la solicitud. Contacte a la directiva.');
            return;
        }

        $this->view('auth/registro_invitacion', [
            'title' => 'Solicitud enviada',
            'invitation' => null,
            'calles' => [],
            'error' => '',
            'success' => 'Su solicitud fue enviada correctamente. La directiva revisará sus datos y le notificará por correo cuando sea aprobada.',
        ]);
    }
}
