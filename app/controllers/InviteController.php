<?php
class InviteController extends Controller {
    private $invitationModel;
    private $userModel;
    private $db;

    public function __construct() {
        $this->invitationModel = $this->model('Invitation');
        $this->userModel = $this->model('User');
        $this->db = new Database();
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

        $calles = $this->getCalles($invitation->junta_id);

        $this->view('auth/registro_invitacion', [
            'title' => 'Registro de Socio',
            'invitation' => $invitation,
            'token' => $token,
            'calles' => $calles,
            'proposed_id_socio' => $this->getProposedIdSocio($invitation->junta_id),
            'error' => '',
            'success' => '',
        ]);
    }

    public function registrar() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/auth/login');
            return;
        }
        require_once APPROOT . '/core/RutChile.php';
        require_once APPROOT . '/core/SocioInput.php';

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

        $calles = $this->getCalles($invitation->junta_id);
        $dataSocio = $this->parseRegistrationPost($post, $invitation->junta_id);

        $renderForm = function ($error) use ($invitation, $token, $calles, $dataSocio) {
            $this->view('auth/registro_invitacion', [
                'title' => 'Registro de Socio',
                'invitation' => $invitation,
                'token' => $token,
                'calles' => $calles,
                'proposed_id_socio' => $this->getProposedIdSocio($invitation->junta_id),
                'error' => $error,
                'success' => '',
                'old' => $dataSocio,
            ]);
        };

        if ($dataSocio['rut'] === '' || $dataSocio['nombres'] === '' || $dataSocio['apellido_paterno'] === ''
            || $dataSocio['apellido_materno'] === '' || $dataSocio['email'] === ''
            || empty($dataSocio['calle_id']) || $dataSocio['numero_casa'] === ''
            || empty($dataSocio['genero']) || empty($dataSocio['fecha_nacimiento'])) {
            $renderForm('Complete todos los campos obligatorios.');
            return;
        }

        $rutNormalizado = RutChile::normalize($dataSocio['rut']);
        if ($rutNormalizado === false) {
            $renderForm('El RUT ingresado no es válido. Use el formato 126667777-6 (sin puntos ni espacios).');
            return;
        }
        $dataSocio['rut'] = $rutNormalizado;

        if (!filter_var($dataSocio['email'], FILTER_VALIDATE_EMAIL)) {
            $renderForm('Ingrese un correo electrónico válido.');
            return;
        }

        if (!empty($dataSocio['id_socio'])) {
            $this->db->query('SELECT id FROM usuarios WHERE junta_id = :junta_id AND id_socio = :id_socio LIMIT 1');
            $this->db->bind(':junta_id', $invitation->junta_id);
            $this->db->bind(':id_socio', (int)$dataSocio['id_socio']);
            if ($this->db->single()) {
                $renderForm('El ID Socio #' . (int)$dataSocio['id_socio'] . ' ya está en uso en esta organización.');
                return;
            }
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

    private function getCalles($juntaId) {
        $this->db->query('SELECT * FROM calles WHERE junta_id = :junta_id ORDER BY nombre ASC');
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    private function getProposedIdSocio($juntaId) {
        $this->db->query('SELECT MAX(id_socio) as max_id FROM usuarios WHERE junta_id = :junta_id');
        $this->db->bind(':junta_id', $juntaId);
        $row = $this->db->single();
        return ($row && $row->max_id) ? (int)$row->max_id + 1 : 1;
    }

    private function parseRegistrationPost(array $post, $juntaId) {
        require_once APPROOT . '/core/SocioInput.php';

        $idSocioRaw = trim($post['id_socio'] ?? '');
        $idSocio = ($idSocioRaw !== '') ? (int)$idSocioRaw : null;

        $data = [
            'junta_id' => $juntaId,
            'id_socio' => ($idSocio && $idSocio > 0) ? $idSocio : null,
            'rut' => trim($post['rut'] ?? ''),
            'nombres' => trim($post['nombres'] ?? ''),
            'apellido_paterno' => trim($post['apellido_paterno'] ?? ''),
            'apellido_materno' => trim($post['apellido_materno'] ?? ''),
            'email' => mb_strtolower(trim($post['email'] ?? ''), 'UTF-8'),
            'password' => bin2hex(random_bytes(16)),
            'rol' => 'socio',
            'telefono' => trim($post['telefono'] ?? ''),
            'estado' => 1,
            'calle_id' => $post['calle_id'] ?? null,
            'numero_casa' => trim($post['numero_casa'] ?? ''),
            'fecha_inicio' => !empty($post['fecha_inicio']) ? $post['fecha_inicio'] : date('Y-m-d'),
            'genero' => SocioInput::normalizeGenero($post['genero'] ?? ''),
            'fecha_nacimiento' => !empty($post['fecha_nacimiento']) ? $post['fecha_nacimiento'] : null,
        ];

        return SocioInput::normalizeTextFields($data);
    }
}
