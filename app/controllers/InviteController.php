<?php
class InviteController extends Controller {
    private $invitationModel;
    private $userModel;
    private $membresiaModel;
    private $db;

    public function __construct() {
        $this->invitationModel = $this->model('Invitation');
        $this->userModel = $this->model('User');
        $this->membresiaModel = $this->model('Membresia');
        $this->db = new Database();
    }

    public function registro($token = '') {
        $token = trim((string)$token);
        $invitation = $this->invitationModel->getValidByToken($token);
        if (!$invitation) {
            $this->viewRegistro([
                'title' => 'Invitación no válida',
                'error' => 'El enlace de invitación expiró, fue revocado o no es válido. Solicite uno nuevo a la directiva de su organización.',
                'invitation' => null,
                'calles' => [],
            ]);
            return;
        }

        $this->viewRegistro([
            'title' => 'Registro de Socio',
            'invitation' => $invitation,
            'token' => $token,
            'calles' => $this->getCalles($invitation->junta_id),
            'proposed_id_socio' => $this->getProposedIdSocio($invitation->junta_id),
            'step' => 'rut',
            'rut_check' => null,
            'error' => '',
            'success' => '',
        ]);
    }

    public function verificar_rut() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/auth/login');
            return;
        }
        require_once APPROOT . '/core/RutChile.php';
        require_once APPROOT . '/core/InviteRutCheck.php';

        $post = $this->sanitizePost();
        $token = trim($post['token'] ?? '');
        $invitation = $this->invitationModel->getValidByToken($token);
        if (!$invitation) {
            $this->viewRegistro([
                'title' => 'Invitación no válida',
                'error' => 'El enlace ya no es válido.',
                'invitation' => null,
                'calles' => [],
            ]);
            return;
        }

        $calles = $this->getCalles($invitation->junta_id);
        $rutCheck = InviteRutCheck::evaluate(
            $this->userModel,
            $this->membresiaModel,
            trim($post['rut'] ?? ''),
            (int)$invitation->junta_id
        );

        if ($rutCheck['action'] === InviteRutCheck::ACTION_REGISTER
            || $rutCheck['action'] === InviteRutCheck::ACTION_COMPLETE_PREVALIDAR) {
            $step = 'form';
        } elseif ($rutCheck['action'] === InviteRutCheck::ACTION_BLOCKED) {
            $step = (($rutCheck['title'] ?? '') === 'RUT inválido') ? 'rut' : 'status';
        }

        $this->viewRegistro([
            'title' => 'Registro de Socio',
            'invitation' => $invitation,
            'token' => $token,
            'calles' => $calles,
            'proposed_id_socio' => $this->getProposedIdSocio($invitation->junta_id),
            'step' => $step,
            'rut_check' => $rutCheck,
            'old' => $rutCheck['prefill'] ?? [],
            'error' => $rutCheck['action'] === InviteRutCheck::ACTION_BLOCKED && ($rutCheck['title'] ?? '') === 'RUT inválido'
                ? ($rutCheck['detail'] ?? 'RUT inválido')
                : '',
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
        require_once APPROOT . '/core/InviteRutCheck.php';

        $post = $this->sanitizePost();
        $token = trim($post['token'] ?? '');
        $invitation = $this->invitationModel->getValidByToken($token);
        if (!$invitation) {
            $this->viewRegistro([
                'title' => 'Invitación no válida',
                'error' => 'El enlace ya no es válido.',
                'invitation' => null,
                'calles' => [],
            ]);
            return;
        }

        $juntaId = (int)$invitation->junta_id;
        $calles = $this->getCalles($juntaId);
        $dataSocio = $this->parseRegistrationPost($post, $juntaId);

        $renderForm = function ($error) use ($invitation, $token, $calles, $dataSocio) {
            $this->viewRegistro([
                'title' => 'Registro de Socio',
                'invitation' => $invitation,
                'token' => $token,
                'calles' => $calles,
                'proposed_id_socio' => $this->getProposedIdSocio($invitation->junta_id),
                'step' => 'form',
                'rut_check' => ['action' => InviteRutCheck::ACTION_REGISTER, 'prefill' => $dataSocio],
                'error' => $error,
                'success' => '',
                'old' => $dataSocio,
            ]);
        };

        if ($dataSocio['rut'] === '' || $dataSocio['nombres'] === '' || $dataSocio['apellido_paterno'] === ''
            || $dataSocio['email'] === ''
            || empty($dataSocio['calle_id']) || $dataSocio['numero_casa'] === ''
            || empty($dataSocio['genero']) || empty($dataSocio['fecha_nacimiento'])
            || empty($dataSocio['estado_civil']) || empty($dataSocio['nacionalidad'])
            || empty($dataSocio['profesion'])) {
            $renderForm('Complete todos los campos obligatorios.');
            return;
        }

        if ($profileError = SocioInput::validateProfile($dataSocio, false)) {
            $renderForm($profileError);
            return;
        }
        if (trim($post['telefono'] ?? '') !== '' && $dataSocio['telefono'] === '') {
            $renderForm('El teléfono debe tener 9 dígitos (ej: 912345678).');
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

        $rutStatus = InviteRutCheck::evaluate($this->userModel, $this->membresiaModel, $dataSocio['rut'], $juntaId);
        if ($rutStatus['action'] === InviteRutCheck::ACTION_BLOCKED) {
            $renderForm($rutStatus['detail'] ?? 'No puede registrarse con este RUT.');
            return;
        }

        if (!empty($dataSocio['id_socio'])) {
            $this->db->query('SELECT id FROM usuarios WHERE junta_id = :junta_id AND id_socio = :id_socio LIMIT 1');
            $this->db->bind(':junta_id', $juntaId);
            $this->db->bind(':id_socio', (int)$dataSocio['id_socio']);
            $existingIdSocio = $this->db->single();
            $prevalidarId = (int)($post['prevalidar_user_id'] ?? 0);
            if ($existingIdSocio && (int)$existingIdSocio->id !== $prevalidarId) {
                $renderForm('El ID Socio #' . (int)$dataSocio['id_socio'] . ' ya está en uso en esta organización.');
                return;
            }
        }

        $prevalidarUser = $this->userModel->getPrevalidarByRutAndJunta($dataSocio['rut'], $juntaId);
        $prevalidarId = (int)($post['prevalidar_user_id'] ?? 0);

        try {
            if ($prevalidarUser) {
                if ($prevalidarId > 0 && (int)$prevalidarUser->id !== $prevalidarId) {
                    $renderForm('Los datos no coinciden con la ficha pre-validada.');
                    return;
                }
                $otherEmail = $this->userModel->findUserByEmail($dataSocio['email']);
                if ($otherEmail && (int)$otherEmail->id !== (int)$prevalidarUser->id) {
                    $renderForm('El correo electrónico ya está registrado en el sistema.');
                    return;
                }
                if (!$this->userModel->promotePrevalidarToPending((int)$prevalidarUser->id, $dataSocio, (int)$invitation->id)) {
                    $renderForm('No se pudo enviar la solicitud. Intente nuevamente.');
                    return;
                }
            } else {
                if ($this->userModel->findUserByRut($dataSocio['rut'])) {
                    $renderForm('El RUT ya está registrado en el sistema.');
                    return;
                }
                if ($this->userModel->findUserByEmail($dataSocio['email'])) {
                    $renderForm('El correo electrónico ya está registrado en el sistema.');
                    return;
                }
                if (!$this->userModel->createPending($dataSocio, (int)$invitation->id)) {
                    $renderForm('No se pudo enviar la solicitud. Intente nuevamente.');
                    return;
                }
            }
        } catch (Exception $e) {
            $renderForm('Error al registrar la solicitud. Contacte a la directiva.');
            return;
        }

        $this->viewRegistro([
            'title' => 'Solicitud enviada',
            'invitation' => null,
            'calles' => [],
            'error' => '',
            'success' => 'Su solicitud fue enviada correctamente. La directiva revisará sus datos y le notificará por correo cuando sea aprobada.',
        ]);
    }

    private function viewRegistro(array $data) {
        $data['public_layout'] = true;
        $this->view('auth/registro_invitacion', $data);
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
        $profile = SocioInput::parseProfileFromPost($post);

        $data = [
            'junta_id' => $juntaId,
            'id_socio' => ($idSocio && $idSocio > 0) ? $idSocio : null,
            'rut' => trim($post['rut'] ?? ''),
            'nombres' => trim($post['nombres'] ?? ''),
            'apellido_paterno' => trim($post['apellido_paterno'] ?? ''),
            'apellido_materno' => trim($post['apellido_materno'] ?? '') ?: null,
            'email' => mb_strtolower(trim($post['email'] ?? ''), 'UTF-8'),
            'password' => bin2hex(random_bytes(16)),
            'rol' => 'socio',
            'telefono' => $profile['telefono'],
            'estado' => 1,
            'calle_id' => $post['calle_id'] ?? null,
            'numero_casa' => trim($post['numero_casa'] ?? ''),
            'fecha_inicio' => !empty($post['fecha_inicio']) ? $post['fecha_inicio'] : date('Y-m-d'),
            'genero' => $profile['genero'],
            'fecha_nacimiento' => $profile['fecha_nacimiento'],
            'estado_civil' => $profile['estado_civil'],
            'nacionalidad' => $profile['nacionalidad'],
            'profesion' => $profile['profesion'],
        ];

        return SocioInput::normalizeTextFields($data);
    }
}
