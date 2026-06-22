<?php
class AdminController extends Controller {
    private $userModel;
    private $juntaModel;
    private $cuotaModel;
    private $transaccionModel;
    private $reunionModel;
    private $asistenciaModel;
    private $cierreModel;
    private $membresiaModel;
    private $invitationModel;
    private $cambioModel;
    private $conceptoModel;
    private $documentoModel;
    private $documentoCategoriaModel;
    private $reunionConvocadoModel;
    private $juntaDocumentoLegalModel;
    private $votacionModel;
    private $db;

    public function __construct() {
        $this->userModel = $this->model('User');
        $this->juntaModel = $this->model('JuntaVecinos');
        $this->cuotaModel = $this->model('CuotaConfig');
        $this->transaccionModel = $this->model('Transaccion');
        $this->reunionModel = $this->model('Reunion');
        $this->asistenciaModel = $this->model('Asistencia');
        $this->cierreModel = $this->model('CierreMensual');
        $this->membresiaModel = $this->model('Membresia');
        $this->invitationModel = $this->model('Invitation');
        $this->cambioModel = $this->model('SocioCambioSolicitud');
        $this->conceptoModel = $this->model('FinanzaConcepto');
        $this->documentoModel = $this->model('Documento');
        $this->documentoCategoriaModel = $this->model('DocumentoCategoria');
        $this->reunionConvocadoModel = $this->model('ReunionConvocado');
        $this->juntaDocumentoLegalModel = $this->model('JuntaDocumentoLegal');
        $this->votacionModel = $this->model('Votacion');
        $this->db = new Database();
    }

    private function requireManageSocios() {
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::canManageSocios()) {
            $_SESSION['error_msg'] = 'No tiene permisos para gestionar socios ni calles.';
            if (($_SESSION['user_rol'] ?? '') === 'socio') {
                $this->redirect('/socio/dashboard');
            } else {
                $this->redirect('/admin/dashboard');
            }
            exit;
        }
    }

    private function requireRegisterPayments() {
        require_once APPROOT . '/core/AuthContext.php';
        if ($this->isMaestroFinanzasMode()) {
            return;
        }
        if (!AuthContext::canRegisterPayments()) {
            $_SESSION['error_msg'] = 'No tiene permisos para registrar pagos.';
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    private function requireViewFlujoCaja() {
        require_once APPROOT . '/core/AuthContext.php';
        if ($this->isMaestroFinanzasMode()) {
            return;
        }
        if (!AuthContext::canViewFlujoCaja()) {
            $_SESSION['error_msg'] = 'No tiene permisos para ver el flujo de caja anual.';
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    private function requireViewDocumentos() {
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::canViewDocumentos()) {
            $_SESSION['error_msg'] = 'No tiene permisos para ver documentos.';
            $this->redirectUserHome();
            exit;
        }
        if (!AuthContext::isFullAdmin() && !AuthContext::isDocumentosEnabled()) {
            $_SESSION['error_msg'] = 'El módulo de documentos no está habilitado para su organización.';
            $this->redirectUserHome();
            exit;
        }
    }

    private function requireUploadDocumentos() {
        require_once APPROOT . '/core/AuthContext.php';
        $this->requireViewDocumentos();
        if (!AuthContext::canUploadDocumentos()) {
            $_SESSION['error_msg'] = 'No tiene permisos para subir o gestionar documentos.';
            $this->redirect('/admin/documentos');
            exit;
        }
    }

    private function redirectUserHome(): void {
        if (($_SESSION['user_rol'] ?? '') === 'socio') {
            $this->redirect('/socio/dashboard');
        } else {
            $this->redirect('/admin/dashboard');
        }
    }

    private function getDocumentoOrFail(int $id, int $juntaId) {
        $doc = $this->documentoModel->getById($id, $juntaId);
        if (!$doc) {
            return null;
        }
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::canViewDocumentoVisibilidad($doc->categoria_visibilidad ?? 'publico')) {
            return null;
        }
        return $doc;
    }

    private function streamDocumentoFile(object $doc, bool $download): void {
        require_once APPROOT . '/core/DocumentStorage.php';
        $juntaId = (int)$_SESSION['user_junta_id'];
        if (!DocumentStorage::pathBelongsToJunta($doc->archivo_path, $juntaId)) {
            http_response_code(403);
            die('Acceso denegado.');
        }
        $path = DocumentStorage::absolutePath($doc->archivo_path);
        if (!is_file($path)) {
            http_response_code(404);
            die('Archivo no encontrado.');
        }
        $filename = $doc->archivo_nombre_original ?: basename($path);
        header('Content-Type: ' . ($doc->mime_type ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        if ($download) {
            header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        } else {
            header('Content-Disposition: inline; filename="' . rawurlencode($filename) . '"');
        }
        readfile($path);
        exit;
    }

    private function validarFechaFinanzas(int $juntaId, string $fecha): ?string {
        return $this->cierreModel->validarFechaMovimiento($juntaId, $fecha);
    }

    private function assertTransaccionMesAbierto(int $juntaId, string $fecha): ?string {
        $mes = substr($fecha, 0, 7);
        if ($this->cierreModel->checkCierreExist($juntaId, $mes)) {
            return 'El mes ' . $mes . ' ya fue cerrado. No puede modificar ni eliminar movimientos de periodos cerrados.';
        }
        return null;
    }

    private function esTransaccionCuota(object $tx): bool {
        return in_array($tx->categoria ?? '', ['Cuota Socio', 'Cuota Condonada'], true);
    }

    private function isMaestroFinanzasMode(): bool {
        return ($_SESSION['user_rol'] ?? '') === 'maestro' && !empty($_SESSION['maestro_acting_junta_id']);
    }

    private function activeJuntaId(): int {
        if ($this->isMaestroFinanzasMode()) {
            return (int)$_SESSION['maestro_acting_junta_id'];
        }
        return (int)$_SESSION['user_junta_id'];
    }

    private function finanzasViewExtras(): array {
        if (!$this->isMaestroFinanzasMode()) {
            return ['maestro_mode' => false];
        }
        return [
            'maestro_mode' => true,
            'maestro_junta_nombre' => $_SESSION['maestro_acting_junta_nombre'] ?? 'Organización',
            'maestro_junta_id' => (int)$_SESSION['maestro_acting_junta_id'],
        ];
    }

    private function finanzasContextData(int $juntaId): array {
        $this->conceptoModel->ensureDefaults($juntaId);
        $esPrimerCierre = $this->cierreModel->esPrimerCierre($juntaId);
        $saldoInicial = $this->juntaModel->getSaldoInicial($juntaId);
        $rangoFechas = $this->cierreModel->getRangoFechasPermitidas($juntaId);
        $conceptosIngreso = $this->conceptoModel->getByJunta($juntaId, 'ingreso', true);
        $conceptosEgreso = $this->conceptoModel->getByJunta($juntaId, 'egreso', true);

        return [
            'es_primer_cierre' => $esPrimerCierre,
            'puede_editar_saldo_inicial' => $esPrimerCierre,
            'saldo_inicial' => $saldoInicial,
            'saldo_inicial_declarado' => $saldoInicial !== null,
            'rango_fechas' => $rangoFechas,
            'conceptos_ingreso' => $conceptosIngreso,
            'conceptos_egreso' => $conceptosEgreso,
            'mes_inicio' => $rangoFechas['mes_inicio'],
        ];
    }

    private function requireViewMapaSocios() {
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::canViewMapaSocios()) {
            $_SESSION['error_msg'] = 'El mapa comunitario no está habilitado para su organización. El administrador debe activarlo en Socios y Ajustes.';
            if (($_SESSION['user_rol'] ?? '') === 'socio') {
                $this->redirect('/socio/dashboard');
            } else {
                $this->redirect('/admin/socios');
            }
            exit;
        }
    }

    // Enviar correo de prueba (GET muestra formulario, POST envía)
    public function email_prueba() {
        if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'admin') {
            header('location: ' . URLROOT . '/auth/login');
            exit;
        }

        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->sanitizePost();
            $this->verifyCsrfToken($post['csrf_token'] ?? '');

            $to = trim($post['to'] ?? 'mbbprelle@gmail.com');
            if ($to === '') {
                $to = 'mbbprelle@gmail.com';
            }

            if (!Mailer::isConfigured()) {
                $_SESSION['error_msg'] = 'SMTP no configurado (Brevo). Defina SMTP_HOST, SMTP_USER y SMTP_PASS en Coolify.';
                $this->redirect('/admin/email_prueba');
                return;
            }

            $subject = 'Prueba de correo - ConectaBarrio (Brevo)';
            $html = "<h2>Correo de prueba</h2><p>Si recibiste esto, el envío SMTP está funcionando.</p>";
            $result = Mailer::send($to, $subject, $html, SMTP_FROM_EMAIL);

            if ($result['ok']) {
                $_SESSION['success_msg'] = 'Correo de prueba enviado a ' . $to . '. Revise también Transactional Logs en Brevo.';
            } else {
                $_SESSION['error_msg'] = 'No se pudo enviar el correo: ' . ($result['error'] ?? 'Error desconocido');
            }

            $this->redirect('/admin/email_prueba');
            return;
        }

        $fromDomain = '';
        if (strpos(SMTP_FROM_EMAIL, '@') !== false) {
            $fromDomain = strtolower(substr(SMTP_FROM_EMAIL, strrpos(SMTP_FROM_EMAIL, '@') + 1));
        }

        $data = [
            'title' => 'Correo de Prueba',
            'header_title' => 'Correo de Prueba (Brevo)',
            'header_subtitle' => 'Envío directo usando PHPMailer + SMTP relay de Brevo',
            'active_menu' => 'dashboard',
            'default_to' => 'mbbprelle@gmail.com',
            'csrf_token' => $this->generateCsrfToken(),
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
            'smtp_from' => SMTP_FROM_EMAIL,
            'smtp_host' => SMTP_HOST,
            'smtp_port' => SMTP_PORT,
            'smtp_user' => SMTP_USER,
            'smtp_encryption' => SMTP_ENCRYPTION,
            'smtp_configured' => Mailer::isConfigured(),
            'from_domain' => $fromDomain,
        ];

        unset($_SESSION['success_msg'], $_SESSION['error_msg']);
        $this->view('admin/email_prueba', $data);
    }

    // =========================================================================
    // 1. DASHBOARD FINANCIERO & FLUJO DE CAJA VISUAL
    // =========================================================================
    public function dashboard() {
        $juntaId = (int)$_SESSION['user_junta_id'];
        
        // Obtener balances
        $balance = $this->transaccionModel->getBalanceConsolidado($juntaId);
        $saldoInicial = $this->juntaModel->getSaldoInicial($juntaId);
        if ($saldoInicial !== null) {
            $balance['saldo_inicial'] = $saldoInicial;
            $balance['contable'] = $saldoInicial + (int)$balance['neto'];
        } else {
            $balance['saldo_inicial'] = null;
            $balance['contable'] = (int)$balance['neto'];
        }
        
        // Obtener historial del flujo de caja (para Chart.js)
        $flujoHistorico = $this->transaccionModel->getFlujoCajaHistorico($juntaId);
        
        // Obtener promedio de asistencia de socios
        $promedioAsistencia = $this->asistenciaModel->getPromedioAsistencia($juntaId);

        // Obtener el total de socios activos
        $socios = $this->userModel->getSociosByJunta($juntaId);
        $totalSocios = count($socios);

        $actividades = $this->loadActividadesDashboardData($juntaId, null);

        $data = [
            'title' => 'Dashboard Financiero',
            'header_title' => 'Control de Finanzas y Gestión',
            'header_subtitle' => 'Monitoree el flujo de caja, ingresos, egresos y participación de la junta',
            'active_menu' => 'dashboard',
            'balance' => $balance,
            'flujo_historico' => $flujoHistorico,
            'promedio_asistencia' => $promedioAsistencia,
            'total_socios' => $totalSocios,
        ];
        $data = array_merge($data, $actividades);

        $this->view('admin/dashboard', $data);
    }

    /** Datos de calendario para el inicio del dashboard (Plan Mediano+). */
    private function loadActividadesDashboardData(int $juntaId, ?int $usuarioId = null): array {
        if (($_SESSION['user_junta_plan'] ?? 'basico') === 'basico') {
            return ['mostrar_calendario' => false];
        }
        $calMes = max(1, min(12, (int)($_GET['mes'] ?? date('n'))));
        $calAnio = (int)($_GET['anio'] ?? date('Y'));
        $esSocio = $usuarioId !== null;
        return [
            'mostrar_calendario' => true,
            'cal_mes' => $calMes,
            'cal_anio' => $calAnio,
            'eventos_por_dia' => $this->reunionModel->getEventosCalendarioMes($juntaId, $calMes, $calAnio, $usuarioId),
            'proximas' => $this->reunionModel->getProximasReuniones($juntaId, 5, $usuarioId),
            'url_calendario' => $esSocio ? (URLROOT . '/socio/reuniones') : (URLROOT . '/admin/calendario'),
            'url_base_mes' => $esSocio ? (URLROOT . '/socio/dashboard') : (URLROOT . '/admin/dashboard'),
            'es_socio' => $esSocio,
        ];
    }

    // =========================================================================
    // 2. GESTIÓN DE SOCIOS, RESETEO CLAVE Y AJUSTES DE CUOTA JUNTA
    // =========================================================================

    // Mostrar listado de socios y configuración de cuotas de la Junta
    public function socios() {
        $this->requireManageSocios();
        require_once APPROOT . '/core/OrgHelper.php';
        $juntaId = $_SESSION['user_junta_id'];

        // Obtener historial completo de cuotas
        $cuotasHistorial = $this->cuotaModel->getHistoryByJunta($juntaId);

        // Obtener listado de calles asociadas a la junta
        $this->db->query("SELECT * FROM calles WHERE junta_id = :junta_id ORDER BY nombre ASC");
        $this->db->bind(':junta_id', $juntaId);
        $calles = $this->db->resultSet();

        // Calcular ID Socio correlativo propuesto
        $this->db->query("SELECT MAX(id_socio) as max_id FROM usuarios WHERE junta_id = :junta_id");
        $this->db->bind(':junta_id', $juntaId);
        $row = $this->db->single();
        $proposedIdSocio = $row && $row->max_id ? (int)$row->max_id + 1 : 1;

        $junta = $this->juntaModel->getJuntaById($juntaId);
        $orgTipo = $junta->tipo ?? 'Junta de Vecinos';
        $socios = $this->membresiaModel->overlayDomicilioOnUsers($this->userModel->getPadronByJunta($juntaId), $juntaId);
        $mapaHabilitado = $this->juntaModel->hasMapaSociosColumn()
            && !empty($junta->mapa_socios_habilitado);
        $flujoHabilitado = $this->juntaModel->hasFlujoCajaColumn()
            && !empty($junta->flujo_caja_habilitado);
        $documentosHabilitado = $this->juntaModel->hasDocumentosColumn()
            && !empty($junta->documentos_habilitado);

        $data = [
            'title' => 'Gestión de Socios',
            'header_title' => 'Padrón de Socios y Jurisdicción',
            'header_subtitle' => 'Administre los vecinos afiliados, controle las calles de la junta y programe ajustes de cuotas',
            'active_menu' => 'socios',
            'socios' => $socios,
            'junta' => $junta,
            'mapa_socios_habilitado' => $mapaHabilitado,
            'flujo_caja_habilitado' => $flujoHabilitado,
            'documentos_habilitado' => $documentosHabilitado,
            'socios_inactivos' => $this->userModel->getSociosInactivosByJunta($juntaId),
            'socios_pendientes' => $this->userModel->getPendingByJunta($juntaId),
            'socios_prevalidar' => $this->userModel->getPrevalidarByJunta($juntaId),
            'cambios_pendientes' => $this->cambioModel->getPendingByJunta($juntaId),
            'org_tipo' => $orgTipo,
            'uses_calles' => OrgHelper::usesCallesJurisdiccion($orgTipo),
            'invitaciones_activas' => $this->invitationModel->getActiveByJunta($juntaId),
            'cuotas_historial' => $cuotasHistorial,
            'calles' => $calles,
            'proposed_id_socio' => $proposedIdSocio,
            'membresias_map' => $this->buildMembresiasMap($juntaId),
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
            'link_invitacion' => $_SESSION['link_invitacion'] ?? '',
            'bulk_import_preview' => $_SESSION['bulk_import_preview'] ?? null,
        ];

        unset($_SESSION['success_msg']);
        unset($_SESSION['error_msg']);
        unset($_SESSION['link_invitacion']);

        $this->view('admin/socios', $data);
    }

    // Inscribir un nuevo Socio en la Junta (POST)
    public function socio_crear() {
        $this->requireManageSocios();
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->sanitizePost();

            $plan = $_SESSION['user_junta_plan'] ?? 'basico';
            $juntaId = $_SESSION['user_junta_id'];
            $maxSocios = ($plan === 'basico') ? 50 : (($plan === 'mediano') ? 200 : PHP_INT_MAX);

            require_once APPROOT . '/core/RutChile.php';
            require_once APPROOT . '/core/InviteRutCheck.php';
            require_once APPROOT . '/core/SocioInitialPassword.php';

            $dataSocio = $this->parseSocioFormData($post);
            $dataSocio['junta_id'] = $juntaId;
            $dataSocio['rol'] = 'socio';
            $dataSocio['estado'] = 1;
            $idSocio = $dataSocio['id_socio'];
            $emailRaw = mb_strtolower(trim($post['email'] ?? ''), 'UTF-8');
            $sinCorreo = ($emailRaw === '');

            $validationError = $this->validateSocioFormData($dataSocio, true, true, true, !$sinCorreo);
            if ($validationError) {
                $_SESSION['error_msg'] = $validationError;
                $this->redirect('/admin/socios');
                return;
            }
            if ($telError = $this->validateTelefonoPost($post, $dataSocio)) {
                $_SESSION['error_msg'] = $telError;
                $this->redirect('/admin/socios');
                return;
            }
            $dataSocio['rut'] = RutChile::normalize($dataSocio['rut']);

            $this->db->query('SELECT id, status FROM usuarios WHERE junta_id = :junta_id AND id_socio = :id_socio LIMIT 1');
            $this->db->bind(':junta_id', $dataSocio['junta_id']);
            $this->db->bind(':id_socio', $idSocio);
            $idTaken = $this->db->single();
            if ($idTaken && ($idTaken->status ?? '') !== 'prevalidar') {
                $_SESSION['error_msg'] = 'El ID Socio ' . $idSocio . ' ya está en uso en su organización. Por favor elija otro o use el sugerido.';
                $this->redirect('/admin/socios');
                return;
            }

            $existingRut = $this->userModel->findUserByRut($dataSocio['rut']);
            if ($existingRut && (($existingRut->status ?? '') !== 'prevalidar' || (int)$existingRut->junta_id !== (int)$juntaId)) {
                $_SESSION['error_msg'] = 'Ya existe un usuario registrado con ese RUT.';
                $this->redirect('/admin/socios');
                return;
            }

            if (!$sinCorreo) {
                if ($maxSocios !== PHP_INT_MAX && $this->userModel->getSociosCountByJunta($juntaId) >= $maxSocios) {
                    $_SESSION['error_msg'] = 'Límite de socios alcanzado. Su Plan ' . ucfirst($plan) . ' solo permite registrar hasta ' . $maxSocios . ' socios activos.';
                    $this->redirect('/admin/socios');
                    return;
                }
                if ($this->userModel->findUserByEmail($dataSocio['email'])) {
                    $_SESSION['error_msg'] = 'Ya existe un usuario registrado con ese Correo Electrónico.';
                    $this->redirect('/admin/socios');
                    return;
                }
                $dataSocio['password'] = 'socio123';
                if ($newUserId = $this->userModel->register($dataSocio)) {
                    $this->membresiaModel->upsert($newUserId, $dataSocio['junta_id'], 'socio', ['id_socio' => $idSocio]);
                    $this->syncDomicilioMembresia((int)$newUserId, $juntaId, $dataSocio);
                    $_SESSION['success_msg'] = 'Socio "' . $dataSocio['nombres'] . ' ' . $dataSocio['apellido_paterno'] . '" inscrito con éxito con ID #' . $idSocio . '. Clave inicial: socio123';
                } else {
                    $_SESSION['error_msg'] = 'Error al registrar al socio en la base de datos.';
                }
            } else {
                $dataSocio['email'] = InviteRutCheck::placeholderEmail($dataSocio['rut'], $juntaId);
                $dataSocio['use_rut_initial_password'] = true;
                if ($existingRut && ($existingRut->status ?? '') === 'prevalidar' && (int)$existingRut->junta_id === (int)$juntaId) {
                    $dataSocio['id'] = (int)$existingRut->id;
                    if ($this->userModel->updatePrevalidar($dataSocio)) {
                        $this->membresiaModel->upsert((int)$existingRut->id, $juntaId, 'socio', ['id_socio' => $idSocio]);
                        $this->syncDomicilioMembresia((int)$existingRut->id, $juntaId, $dataSocio);
                        $_SESSION['success_msg'] = 'Alta provisional actualizada para "' . $dataSocio['nombres'] . '". Puede registrar pagos; el vecino ingresa con RUT y clave ' . SocioInitialPassword::fromRut($dataSocio['rut']) . '.';
                    } else {
                        $_SESSION['error_msg'] = 'No se pudo actualizar el registro provisional.';
                    }
                } elseif ($newUserId = $this->userModel->createPrevalidar($dataSocio)) {
                    $this->membresiaModel->upsert($newUserId, $juntaId, 'socio', ['id_socio' => $idSocio]);
                    $this->syncDomicilioMembresia((int)$newUserId, $juntaId, $dataSocio);
                    $_SESSION['success_msg'] = 'Socio "' . $dataSocio['nombres'] . ' ' . $dataSocio['apellido_paterno'] . '" registrado en alta provisional (sin correo). '
                        . 'Puede asociar pagos ya. Clave inicial del vecino: primeros 6 dígitos del RUT (' . SocioInitialPassword::fromRut($dataSocio['rut']) . ').';
                } else {
                    $_SESSION['error_msg'] = 'Error al registrar al socio en la base de datos.';
                }
            }
        }
        $this->redirect('/admin/socios');
    }

    public function generar_invitacion() {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        $juntaId = $_SESSION['user_junta_id'];
        $created = $this->invitationModel->create($juntaId, 24);
        if (!$created) {
            $_SESSION['error_msg'] = 'No se pudo generar el enlace de invitación.';
            $this->redirect('/admin/socios');
            return;
        }
        $link = URLROOT . '/invite/registro/' . $created['token'];
        $_SESSION['link_invitacion'] = $link;
        $_SESSION['success_msg'] = 'Enlace de invitación generado. Válido por 24 horas hasta '
            . date('d-m-Y H:i', strtotime($created['expires_at'])) . '.';
        $this->redirect('/admin/socios');
    }

    public function invitacion_revocar($id) {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        if ($this->invitationModel->revoke((int)$id, $_SESSION['user_junta_id'])) {
            $_SESSION['success_msg'] = 'Enlace de invitación revocado.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo revocar el enlace.';
        }
        $this->redirect('/admin/socios');
    }

    private function parseSocioFormData($post) {
        require_once APPROOT . '/core/SocioInput.php';
        require_once APPROOT . '/core/SocioGeoref.php';
        $profile = SocioInput::parseProfileFromPost($post);
        $georef = SocioGeoref::parseFromPost($post);
        $data = [
            'id_socio' => (int)($post['id_socio'] ?? 0),
            'rut' => trim($post['rut'] ?? ''),
            'nombres' => trim($post['nombres'] ?? ''),
            'apellido_paterno' => trim($post['apellido_paterno'] ?? ''),
            'apellido_materno' => trim($post['apellido_materno'] ?? ''),
            'email' => mb_strtolower(trim($post['email'] ?? ''), 'UTF-8'),
            'telefono' => $profile['telefono'],
            'calle_id' => $post['calle_id'] ?? null,
            'numero_casa' => trim($post['numero_casa'] ?? ''),
            'fecha_inicio' => !empty($post['fecha_inicio']) ? $post['fecha_inicio'] : date('Y-m-d'),
            'genero' => $profile['genero'],
            'fecha_nacimiento' => $profile['fecha_nacimiento'],
            'estado_civil' => $profile['estado_civil'],
            'nacionalidad' => $profile['nacionalidad'],
            'profesion' => $profile['profesion'],
            'latitud' => $georef['latitud'],
            'longitud' => $georef['longitud'],
            'link_google' => $georef['link_google'],
            'direccion_texto' => trim($post['direccion_texto'] ?? ''),
        ];
        return SocioInput::normalizeTextFields($data, ['direccion_texto']);
    }

    private function orgUsesCalles(): bool {
        require_once APPROOT . '/core/OrgHelper.php';
        return OrgHelper::usesCallesJurisdiccion($_SESSION['user_junta_tipo'] ?? 'Junta de Vecinos');
    }

    private function syncDomicilioMembresia(int $userId, int $juntaId, array $data): void {
        try {
            $extra = [];
            if (!empty($data['id_socio'])) {
                $extra['id_socio'] = (int)$data['id_socio'];
            }
            $this->membresiaModel->upsert($userId, $juntaId, 'socio', $extra);
            $this->membresiaModel->updateDomicilio($userId, $juntaId, $data);
        } catch (Exception $e) {
            // Migración opcional
        }
    }

    private function buildCallesGeorefIndex(array $calles): array {
        $index = [];
        foreach ($calles as $c) {
            $index[(int)$c->id] = [
                'nombre' => $c->nombre,
                'lat_centro' => $c->lat_centro ?? null,
                'lng_centro' => $c->lng_centro ?? null,
            ];
        }
        return $index;
    }

    private function finalizeBulkImportValidation(array $result, int $juntaId): array {
        require_once APPROOT . '/core/SocioBulkImport.php';
        require_once APPROOT . '/core/RutChile.php';

        $pending = [];
        $ruts = [];
        $emails = [];
        $idSocios = [];

        foreach ($result['rows'] as $idx => $row) {
            if (!$row['valid']) {
                continue;
            }
            $data = SocioBulkImport::stripInternalFields($row['data']);
            $data['rut'] = RutChile::normalize($data['rut']) ?: $data['rut'];
            $pending[$idx] = $data;
            $ruts[] = $data['rut'];
            if (!empty($data['email']) && !str_contains($data['email'], '@prevalidar.conectabarrio')) {
                $emails[] = $data['email'];
            }
            if (!empty($data['id_socio'])) {
                $idSocios[] = (int)$data['id_socio'];
            }
        }

        $usersByRut = $this->userModel->findUsersByRuts($ruts);
        $usersByEmail = $this->userModel->findUsersByEmails($emails);
        $takenIdSocios = $this->fetchTakenIdSociosForBulk($juntaId, $idSocios);

        $validRows = [];
        foreach ($pending as $idx => $data) {
            $rowErrors = [];
            $rowWarnings = $result['rows'][$idx]['warnings'] ?? [];
            $existing = $usersByRut[$data['rut']] ?? null;
            if ($existing) {
                $sameJunta = (int)$existing->junta_id === $juntaId;
                $isPrevalidar = ($existing->status ?? '') === 'prevalidar';
                if ($isPrevalidar && $sameJunta) {
                    $idSocioLabel = !empty($existing->id_socio) ? (' (ID socio #' . (int)$existing->id_socio . ')') : '';
                    $rowWarnings[] = 'RUT ya está en alta provisional (PRE-VALIDAR) en esta organización' . $idSocioLabel . '. No se volverá a importar.';
                    $result['rows'][$idx]['warnings'] = $rowWarnings;
                    $result['rows'][$idx]['already_prevalidar'] = true;
                    continue;
                }
                if (!$isPrevalidar || !$sameJunta) {
                    $rowErrors[] = 'RUT ya registrado';
                }
            }
            if (!empty($data['email']) && !str_contains($data['email'], '@prevalidar.conectabarrio')) {
                $emailKey = mb_strtolower($data['email'], 'UTF-8');
                $byEmail = $usersByEmail[$emailKey] ?? null;
                if ($byEmail) {
                    $sameJuntaEmail = (int)$byEmail->junta_id === $juntaId;
                    $isPrevalidarEmail = ($byEmail->status ?? '') === 'prevalidar';
                    if ($isPrevalidarEmail && $sameJuntaEmail && ($byEmail->rut ?? '') === $data['rut']) {
                        // mismo registro PRE-VALIDAR, aviso ya agregado por RUT
                    } elseif (!$isPrevalidarEmail || !$sameJuntaEmail) {
                        $rowErrors[] = 'Correo ya registrado';
                    }
                }
            }
            if (!empty($data['id_socio'])) {
                $taken = $takenIdSocios[(int)$data['id_socio']] ?? null;
                if ($taken && ($taken->status ?? '') !== 'prevalidar') {
                    $rowErrors[] = 'ID socio en uso';
                } elseif ($taken && ($taken->status ?? '') === 'prevalidar' && (int)$taken->id !== (int)($existing->id ?? 0)) {
                    $rowErrors[] = 'ID socio en uso por otro registro PRE-VALIDAR';
                }
            }
            if (!empty($rowErrors)) {
                $result['rows'][$idx]['valid'] = false;
                $result['rows'][$idx]['errors'] = array_merge($result['rows'][$idx]['errors'], $rowErrors);
            } else {
                if (!empty($rowWarnings)) {
                    $result['rows'][$idx]['warnings'] = $rowWarnings;
                }
                $validRows[] = $data;
            }
        }

        return [$result, $validRows];
    }

    /** @return array<int, object> id_socio => usuario */
    private function fetchTakenIdSociosForBulk(int $juntaId, array $idSocios): array {
        $idSocios = array_values(array_unique(array_filter(array_map('intval', $idSocios))));
        if (empty($idSocios)) {
            return [];
        }
        $parts = [];
        foreach ($idSocios as $i => $idSocio) {
            $parts[] = ':id' . $i;
        }
        $this->db->query('SELECT id, id_socio, status FROM usuarios WHERE junta_id = :junta_id AND id_socio IN (' . implode(', ', $parts) . ')');
        $this->db->bind(':junta_id', $juntaId);
        foreach ($idSocios as $i => $idSocio) {
            $this->db->bind(':id' . $i, $idSocio);
        }
        $map = [];
        foreach ($this->db->resultSet() as $row) {
            $map[(int)$row->id_socio] = $row;
        }
        return $map;
    }

    private function runBulkImportRows(array $rows, int $juntaId, bool $cacheCalles = true): array {
        require_once APPROOT . '/core/RutChile.php';
        require_once APPROOT . '/core/SocioGeoref.php';

        static $callesById = null;
        if (!$cacheCalles || $callesById === null) {
            $this->db->query('SELECT id, nombre, lat_centro, lng_centro FROM calles WHERE junta_id = :junta_id');
            $this->db->bind(':junta_id', $juntaId);
            $callesById = $this->buildCallesGeorefIndex($this->db->resultSet());
        }

        $inserted = 0;
        $skipped = 0;
        foreach ($rows as $data) {
            $data['junta_id'] = $juntaId;
            $data['rut'] = RutChile::normalize($data['rut']) ?: $data['rut'];
            $data = SocioGeoref::resolveForMembresiaBulk($data, $callesById);

            $existing = $this->userModel->findUserByRut($data['rut']);
            if ($existing) {
                if (($existing->status ?? '') === 'prevalidar' && (int)$existing->junta_id === $juntaId) {
                    $data['id'] = (int)$existing->id;
                    if ($this->userModel->updatePrevalidar($data)) {
                        $this->syncDomicilioMembresia((int)$existing->id, $juntaId, $data);
                        $inserted++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $skipped++;
                }
                continue;
            }

            $newId = $this->userModel->createPrevalidar($data);
            if ($newId) {
                $this->syncDomicilioMembresia((int)$newId, $juntaId, $data);
                $inserted++;
            } else {
                $skipped++;
            }
        }

        return ['inserted' => $inserted, 'skipped' => $skipped];
    }

    private function validateSocioFormData(array $data, $requireIdSocio = true, $requireProfile = true, $requireApellidoMaterno = true, $requireEmail = true) {
        require_once APPROOT . '/core/RutChile.php';
        require_once APPROOT . '/core/SocioInput.php';
        if ($requireIdSocio && $data['id_socio'] <= 0) {
            return 'El ID Socio debe ser mayor a 0.';
        }
        if ($data['rut'] === '' || $data['nombres'] === '' || $data['apellido_paterno'] === ''
            || ($requireApellidoMaterno && $data['apellido_materno'] === '')
            || ($requireEmail && $data['email'] === '')) {
            return 'Complete todos los campos obligatorios.';
        }
        if ($this->orgUsesCalles()) {
            if (empty($data['calle_id']) || ($data['numero_casa'] ?? '') === '') {
                return 'Seleccione calle e indique número de casa.';
            }
        } elseif (trim($data['direccion_texto'] ?? '') === '') {
            return 'Indique la dirección.';
        }
        if ($requireEmail && $data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no es válido.';
        }
        $rutOk = RutChile::normalize($data['rut']);
        if ($rutOk === false) {
            return 'El RUT no es válido. Use el formato 126667777-6 (sin puntos ni espacios).';
        }
        if ($profileError = SocioInput::validateProfile($data, $requireProfile)) {
            return $profileError;
        }
        return null;
    }

    private function validateTelefonoPost($post, array $data) {
        if (trim($post['telefono'] ?? '') !== '' && ($data['telefono'] ?? '') === '') {
            return 'El teléfono debe tener 9 dígitos (ej: 912345678).';
        }
        return null;
    }

    private function applyRutNormalization(array &$data) {
        require_once APPROOT . '/core/RutChile.php';
        $rutOk = RutChile::normalize($data['rut']);
        if ($rutOk !== false) {
            $data['rut'] = $rutOk;
        }
    }

    public function socio_pendiente_actualizar() {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        $post = $this->sanitizePost();
        $socioId = (int)($post['socio_id'] ?? 0);
        $juntaId = $_SESSION['user_junta_id'];
        $socio = $this->userModel->getPendingById($socioId, $juntaId);
        if (!$socio) {
            $_SESSION['error_msg'] = 'Solicitud pendiente no encontrada.';
            $this->redirect('/admin/socios');
            return;
        }
        $data = $this->parseSocioFormData($post);
        $data['id'] = $socioId;
        $err = $this->validateSocioFormData($data, false, false);
        if ($err) {
            $_SESSION['error_msg'] = $err;
            $this->redirect('/admin/socios');
            return;
        }
        $this->applyRutNormalization($data);
        if ($this->emailOrRutTaken($data['email'], $data['rut'], $socioId)) {
            $_SESSION['error_msg'] = 'El RUT o correo ya está en uso por otro usuario.';
            $this->redirect('/admin/socios');
            return;
        }
        if ($this->userModel->updatePending($data)) {
            $_SESSION['success_msg'] = 'Datos de la solicitud actualizados.';
        } else {
            $_SESSION['error_msg'] = 'No se pudieron guardar los cambios.';
        }
        $this->redirect('/admin/socios');
    }

    public function socio_pendiente_aprobar() {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        $post = $this->sanitizePost();
        $socioId = (int)($post['socio_id'] ?? 0);
        $juntaId = $_SESSION['user_junta_id'];
        $socio = $this->userModel->getPendingById($socioId, $juntaId);
        if (!$socio) {
            $_SESSION['error_msg'] = 'Solicitud pendiente no encontrada.';
            $this->redirect('/admin/socios');
            return;
        }

        $plan = $_SESSION['user_junta_plan'] ?? 'basico';
        $maxSocios = ($plan === 'basico') ? 50 : (($plan === 'mediano') ? 200 : PHP_INT_MAX);
        if ($maxSocios !== PHP_INT_MAX && $this->userModel->getSociosCountByJunta($juntaId) >= $maxSocios) {
            $_SESSION['error_msg'] = 'Límite de socios activos alcanzado para su plan.';
            $this->redirect('/admin/socios');
            return;
        }

        $data = $this->parseSocioFormData($post);
        $data['id'] = $socioId;
        if (empty($data['id_socio']) || $data['id_socio'] <= 0) {
            $this->db->query('SELECT MAX(id_socio) as max_id FROM usuarios WHERE junta_id = :junta_id');
            $this->db->bind(':junta_id', $juntaId);
            $row = $this->db->single();
            $data['id_socio'] = ($row && $row->max_id) ? (int)$row->max_id + 1 : 1;
        }
        $err = $this->validateSocioFormData($data, true);
        if ($err) {
            $_SESSION['error_msg'] = $err;
            $this->redirect('/admin/socios');
            return;
        }
        $this->applyRutNormalization($data);
        if ($this->emailOrRutTaken($data['email'], $data['rut'], $socioId)) {
            $_SESSION['error_msg'] = 'El RUT o correo ya está en uso por otro usuario.';
            $this->redirect('/admin/socios');
            return;
        }
        $this->db->query('SELECT id FROM usuarios WHERE junta_id = :junta_id AND id_socio = :id_socio AND id != :id LIMIT 1');
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':id_socio', $data['id_socio']);
        $this->db->bind(':id', $socioId);
        if ($this->db->single()) {
            $_SESSION['error_msg'] = 'El ID Socio #' . $data['id_socio'] . ' ya está en uso en su organización.';
            $this->redirect('/admin/socios');
            return;
        }

        if (!$this->userModel->updatePending($data)) {
            $_SESSION['error_msg'] = 'No se pudieron actualizar los datos antes de aprobar.';
            $this->redirect('/admin/socios');
            return;
        }

        $tempPwd = $this->userModel->approvePending($socioId, $juntaId, (int)$data['id_socio']);
        if (!$tempPwd) {
            $_SESSION['error_msg'] = 'Error al activar la cuenta del socio.';
            $this->redirect('/admin/socios');
            return;
        }

        $this->membresiaModel->upsert($socioId, $juntaId, 'socio', ['id_socio' => (int)$data['id_socio']]);
        $this->syncDomicilioMembresia($socioId, $juntaId, $data);

        $socioActivo = $this->userModel->getSocioById($socioId);
        $juntaNombre = $_SESSION['user_junta_nombre'] ?? 'Su organización';
        $mailResult = SocioApprovalMail::send($socioActivo, $juntaNombre, $tempPwd);
        unset($tempPwd);

        if ($mailResult['ok']) {
            $_SESSION['success_msg'] = 'Socio "' . $data['nombres'] . '" aprobado. Se envió correo con sus datos y clave temporal de acceso.';
        } else {
            $_SESSION['success_msg'] = 'Socio aprobado, pero no se pudo enviar el correo: ' . ($mailResult['error'] ?? 'error desconocido')
                . '. Use "Resetear clave" para enviar acceso.';
        }
        $this->redirect('/admin/socios');
    }

    public function socio_pendiente_rechazar($id) {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        $socio = $this->userModel->getPendingById((int)$id, $_SESSION['user_junta_id']);
        if (!$socio) {
            $_SESSION['error_msg'] = 'Solicitud pendiente no encontrada.';
            $this->redirect('/admin/socios');
            return;
        }
        if ($this->userModel->rejectPending((int)$id, $_SESSION['user_junta_id'])) {
            $_SESSION['success_msg'] = 'Solicitud de "' . $socio->nombre . '" rechazada y eliminada.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo rechazar la solicitud.';
        }
        $this->redirect('/admin/socios');
    }

    public function socio_importar_validar() {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        require_once APPROOT . '/core/SocioBulkImport.php';
        require_once APPROOT . '/core/RutChile.php';
        require_once APPROOT . '/core/OrgHelper.php';

        $post = $this->sanitizePost();
        $raw = trim($post['bulk_data'] ?? '');
        $juntaId = (int)$_SESSION['user_junta_id'];
        $usesCalles = OrgHelper::usesCallesJurisdiccion($_SESSION['user_junta_tipo'] ?? 'Junta de Vecinos');

        $this->db->query('SELECT * FROM calles WHERE junta_id = :junta_id ORDER BY nombre ASC');
        $this->db->bind(':junta_id', $juntaId);
        $calles = $this->db->resultSet();

        $result = SocioBulkImport::parse($raw, $calles, $juntaId, $usesCalles);
        [$result, $validRows] = $this->finalizeBulkImportValidation($result, $juntaId);
        $validCount = count($validRows);
        $errorCount = 0;
        $warningCount = 0;
        $prevalidarDupCount = 0;
        foreach ($result['rows'] as $row) {
            if (!$row['valid']) {
                $errorCount++;
            } elseif (!empty($row['already_prevalidar'])) {
                $prevalidarDupCount++;
            } elseif (!empty($row['warnings'])) {
                $warningCount++;
            }
        }
        $result['valid_count'] = $validCount;
        $result['error_count'] = $errorCount;
        $result['warning_count'] = $warningCount;
        $result['prevalidar_dup_count'] = $prevalidarDupCount;

        $_SESSION['bulk_import_preview'] = [
            'junta_id' => $juntaId,
            'created_at' => time(),
            'valid_rows' => $validRows,
            'result' => $result,
        ];

        $msg = 'Validación completada: ' . (int)$result['valid_count'] . ' fila(s) importable(s), '
            . (int)$result['error_count'] . ' con errores';
        if ($prevalidarDupCount > 0) {
            $msg .= ', ' . $prevalidarDupCount . ' ya en PRE-VALIDAR (no se reimportan)';
        }
        if ($warningCount > 0) {
            $msg .= ', ' . $warningCount . ' con observaciones (se importan, pero revise antes de activar)';
        }
        $msg .= '.';
        $_SESSION['success_msg'] = $msg;
        $this->redirect('/admin/socios');
    }

    public function socio_importar_confirmar() {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }

        $preview = $_SESSION['bulk_import_preview'] ?? null;
        $juntaId = (int)$_SESSION['user_junta_id'];
        if (!$preview || (int)($preview['junta_id'] ?? 0) !== $juntaId || empty($preview['valid_rows'])) {
            $_SESSION['error_msg'] = 'No hay datos validados para importar. Valide la planilla primero.';
            $this->redirect('/admin/socios');
            return;
        }

        $stats = $this->runBulkImportRows($preview['valid_rows'], $juntaId);
        unset($_SESSION['bulk_import_preview'], $_SESSION['bulk_import_offset'], $_SESSION['bulk_import_stats']);
        $_SESSION['success_msg'] = $stats['inserted'] . ' socio(s) quedaron en alta provisional (sin correo). Puede registrar pagos; clave inicial: primeros 6 dígitos del RUT.'
            . ($stats['skipped'] > 0 ? ' ' . $stats['skipped'] . ' fila(s) omitida(s).' : '')
            . ' La georreferencia precisa puede completarse al revisar cada socio.';
        $this->redirect('/admin/socios');
    }

    public function socio_importar_chunk() {
        $this->requireManageSocios();
        header('Content-Type: application/json; charset=utf-8');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $preview = $_SESSION['bulk_import_preview'] ?? null;
        $juntaId = (int)$_SESSION['user_junta_id'];
        if (!$preview || (int)($preview['junta_id'] ?? 0) !== $juntaId || empty($preview['valid_rows'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No hay datos validados para importar. Valide la planilla primero.']);
            return;
        }

        $post = $this->sanitizePost();
        if (!empty($post['reset'])) {
            $_SESSION['bulk_import_offset'] = 0;
            $_SESSION['bulk_import_stats'] = ['inserted' => 0, 'skipped' => 0];
        }

        $rows = $preview['valid_rows'];
        $total = count($rows);
        $offset = (int)($_SESSION['bulk_import_offset'] ?? 0);
        if ($offset < 0) {
            $offset = 0;
        }
        if ($offset >= $total) {
            $offset = 0;
            $_SESSION['bulk_import_stats'] = ['inserted' => 0, 'skipped' => 0];
        }

        $batchSize = 20;
        $slice = array_slice($rows, $offset, $batchSize);
        $stats = $this->runBulkImportRows($slice, $juntaId, false);
        $sessionStats = $_SESSION['bulk_import_stats'] ?? ['inserted' => 0, 'skipped' => 0];
        $sessionStats['inserted'] += $stats['inserted'];
        $sessionStats['skipped'] += $stats['skipped'];
        $_SESSION['bulk_import_stats'] = $sessionStats;

        $processed = $offset + count($slice);
        $_SESSION['bulk_import_offset'] = $processed;
        $done = $processed >= $total;
        $percent = $total > 0 ? (int)round(($processed / $total) * 100) : 100;

        if ($done) {
            unset($_SESSION['bulk_import_preview'], $_SESSION['bulk_import_offset']);
            $_SESSION['success_msg'] = $sessionStats['inserted'] . ' socio(s) quedaron en alta provisional (sin correo). Puede registrar pagos; clave inicial: primeros 6 dígitos del RUT.'
                . ($sessionStats['skipped'] > 0 ? ' ' . $sessionStats['skipped'] . ' fila(s) omitida(s).' : '')
                . ' La georreferencia precisa puede completarse al revisar cada socio.';
            unset($_SESSION['bulk_import_stats']);
        }

        echo json_encode([
            'ok' => true,
            'done' => $done,
            'processed' => $processed,
            'total' => $total,
            'percent' => $percent,
            'inserted' => $sessionStats['inserted'],
            'skipped' => $sessionStats['skipped'],
            'status' => $done
                ? 'Importación finalizada'
                : ('Registrando socios… ' . $processed . ' de ' . $total),
            'redirect' => $done ? (URLROOT . '/admin/socios') : null,
        ]);
    }

    public function socio_prevalidar_actualizar() {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        require_once APPROOT . '/core/RutChile.php';
        $post = $this->sanitizePost();
        $socioId = (int)($post['socio_id'] ?? 0);
        $juntaId = (int)$_SESSION['user_junta_id'];
        $socio = $this->userModel->getPrevalidarById($socioId, $juntaId);
        if (!$socio) {
            $_SESSION['error_msg'] = 'Registro pre-validado no encontrado.';
            $this->redirect('/admin/socios');
            return;
        }

        $data = $this->parseSocioFormData($post);
        $data['id'] = $socioId;
        $err = $this->validateSocioFormData($data, false, false);
        if ($err) {
            $_SESSION['error_msg'] = $err;
            $this->redirect('/admin/socios');
            return;
        }
        $this->applyRutNormalization($data);
        if (empty($data['email'])) {
            require_once APPROOT . '/core/InviteRutCheck.php';
            $data['email'] = InviteRutCheck::placeholderEmail($data['rut'], $juntaId);
        }
        if ($this->emailOrRutTaken($data['email'], $data['rut'], $socioId)) {
            $_SESSION['error_msg'] = 'El RUT o correo ya está en uso por otro usuario.';
            $this->redirect('/admin/socios');
            return;
        }
        if (!empty($data['id_socio'])) {
            $this->db->query('SELECT id FROM usuarios WHERE junta_id = :junta_id AND id_socio = :id_socio AND id != :id LIMIT 1');
            $this->db->bind(':junta_id', $juntaId);
            $this->db->bind(':id_socio', (int)$data['id_socio']);
            $this->db->bind(':id', $socioId);
            if ($this->db->single()) {
                $_SESSION['error_msg'] = 'El ID Socio ya está en uso.';
                $this->redirect('/admin/socios');
                return;
            }
        }
        if ($this->userModel->updatePrevalidar($data)) {
            $_SESSION['success_msg'] = 'Datos pre-validados actualizados.';
        } else {
            $_SESSION['error_msg'] = 'No se pudieron guardar los cambios.';
        }
        $this->redirect('/admin/socios');
    }

    public function socio_prevalidar_aprobar() {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        require_once APPROOT . '/core/SocioApprovalMail.php';
        require_once APPROOT . '/core/InviteRutCheck.php';

        $post = $this->sanitizePost();
        $socioId = (int)($post['socio_id'] ?? 0);
        $juntaId = (int)$_SESSION['user_junta_id'];
        $socio = $this->userModel->getPrevalidarById($socioId, $juntaId);
        if (!$socio) {
            $_SESSION['error_msg'] = 'Registro pre-validado no encontrado.';
            $this->redirect('/admin/socios');
            return;
        }

        $plan = $_SESSION['user_junta_plan'] ?? 'basico';
        $maxSocios = ($plan === 'basico') ? 50 : (($plan === 'mediano') ? 200 : PHP_INT_MAX);
        if ($maxSocios !== PHP_INT_MAX && $this->userModel->getSociosCountByJunta($juntaId) >= $maxSocios) {
            $_SESSION['error_msg'] = 'Límite de socios activos alcanzado para su plan.';
            $this->redirect('/admin/socios');
            return;
        }

        $data = $this->parseSocioFormData($post);
        $data['id'] = $socioId;
        if (empty($data['email'])) {
            $_SESSION['error_msg'] = 'Indique un correo electrónico válido antes de activar al socio.';
            $this->redirect('/admin/socios');
            return;
        }
        if (str_contains($data['email'], '@prevalidar.conectabarrio')) {
            $_SESSION['error_msg'] = 'El socio debe tener un correo real antes de activar la cuenta.';
            $this->redirect('/admin/socios');
            return;
        }
        if (empty($data['id_socio']) || $data['id_socio'] <= 0) {
            $this->db->query('SELECT MAX(id_socio) as max_id FROM usuarios WHERE junta_id = :junta_id');
            $this->db->bind(':junta_id', $juntaId);
            $row = $this->db->single();
            $data['id_socio'] = ($row && $row->max_id) ? (int)$row->max_id + 1 : 1;
        }
        $err = $this->validateSocioFormData($data, true, false);
        if ($err) {
            $_SESSION['error_msg'] = $err;
            $this->redirect('/admin/socios');
            return;
        }
        $this->applyRutNormalization($data);
        if ($this->emailOrRutTaken($data['email'], $data['rut'], $socioId)) {
            $_SESSION['error_msg'] = 'El RUT o correo ya está en uso por otro usuario.';
            $this->redirect('/admin/socios');
            return;
        }
        if (!$this->userModel->updatePrevalidar($data)) {
            $_SESSION['error_msg'] = 'No se pudieron actualizar los datos antes de activar.';
            $this->redirect('/admin/socios');
            return;
        }

        $tempPwd = $this->userModel->approvePending($socioId, $juntaId, (int)$data['id_socio']);
        if (!$tempPwd) {
            $_SESSION['error_msg'] = 'Error al activar la cuenta del socio.';
            $this->redirect('/admin/socios');
            return;
        }

        $this->membresiaModel->upsert($socioId, $juntaId, 'socio', ['id_socio' => (int)$data['id_socio']]);
        $this->syncDomicilioMembresia($socioId, $juntaId, $data);
        $socioActivo = $this->userModel->getSocioById($socioId);
        $juntaNombre = $_SESSION['user_junta_nombre'] ?? 'Su organización';
        $mailResult = SocioApprovalMail::send($socioActivo, $juntaNombre, $tempPwd);
        unset($tempPwd);

        if ($mailResult['ok']) {
            $_SESSION['success_msg'] = 'Socio "' . $data['nombres'] . '" activado. Se envió correo con sus datos y clave temporal.';
        } else {
            $_SESSION['success_msg'] = 'Socio activado, pero no se pudo enviar el correo: ' . ($mailResult['error'] ?? 'error desconocido');
        }
        $this->redirect('/admin/socios');
    }

    public function socio_prevalidar_eliminar($id) {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        $socio = $this->userModel->getPrevalidarById((int)$id, $_SESSION['user_junta_id']);
        if (!$socio) {
            $_SESSION['error_msg'] = 'Registro pre-validado no encontrado.';
            $this->redirect('/admin/socios');
            return;
        }
        if ($this->userModel->rejectPrevalidar((int)$id, $_SESSION['user_junta_id'])) {
            $_SESSION['success_msg'] = 'Registro pre-validado de "' . $socio->nombre . '" eliminado.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo eliminar el registro.';
        }
        $this->redirect('/admin/socios');
    }

    private function emailOrRutTaken($email, $rut, $excludeUserId = null) {
        $byEmail = $this->userModel->findUserByEmail($email);
        if ($byEmail && (!$excludeUserId || (int)$byEmail->id !== (int)$excludeUserId)) {
            return true;
        }
        $byRut = $this->userModel->findUserByRut($rut);
        if ($byRut && (!$excludeUserId || (int)$byRut->id !== (int)$excludeUserId)) {
            return true;
        }
        return false;
    }

    public function socio_actualizar() {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        $post = $this->sanitizePost();
        $socioId = (int)($post['socio_id'] ?? 0);
        $juntaId = $_SESSION['user_junta_id'];
        $socio = $this->userModel->getPadronMiembroById($socioId, $juntaId);
        if (!$socio) {
            $_SESSION['error_msg'] = 'Miembro no encontrado en su organización.';
            $this->redirect('/admin/socios');
            return;
        }
        $isAdminMiembro = ($socio->rol ?? '') === 'admin';
        $idSocio = (int)($post['id_socio'] ?? 0);
        $data = $this->parseSocioFormData($post);
        $data['id'] = $socioId;
        if ($isAdminMiembro && $idSocio <= 0) {
            $data['id_socio'] = !empty($socio->id_socio) ? (int)$socio->id_socio : null;
        } else {
            $data['id_socio'] = $idSocio > 0 ? $idSocio : null;
        }
        $idSocio = !empty($data['id_socio']) ? (int)$data['id_socio'] : 0;

        $validationError = $this->validateSocioFormData($data, !$isAdminMiembro, false, !$isAdminMiembro);
        if ($validationError) {
            $_SESSION['error_msg'] = $validationError;
            $this->redirect('/admin/socios');
            return;
        }
        if ($telError = $this->validateTelefonoPost($post, $data)) {
            $_SESSION['error_msg'] = $telError;
            $this->redirect('/admin/socios');
            return;
        }
        require_once APPROOT . '/core/RutChile.php';
        $data['rut'] = RutChile::normalize($data['rut']);
        if ($idSocio > 0) {
            $this->db->query("SELECT id FROM usuarios WHERE junta_id = :junta_id AND id_socio = :id_socio AND id != :id LIMIT 1");
            $this->db->bind(':junta_id', $juntaId);
            $this->db->bind(':id_socio', $idSocio);
            $this->db->bind(':id', $socioId);
            if ($this->db->single()) {
                $_SESSION['error_msg'] = 'El ID Socio #' . $idSocio . ' ya está en uso por otro vecino.';
                $this->redirect('/admin/socios');
                return;
            }
        }
        $existingRut = $this->userModel->findUserByRut($data['rut']);
        if ($existingRut && (int)$existingRut->id !== $socioId) {
            $_SESSION['error_msg'] = 'El RUT ya pertenece a otro usuario del sistema.';
            $this->redirect('/admin/socios');
            return;
        }
        $existingEmail = $this->userModel->findUserByEmail($data['email']);
        if ($existingEmail && (int)$existingEmail->id !== $socioId) {
            $_SESSION['error_msg'] = 'El correo ya pertenece a otro usuario del sistema.';
            $this->redirect('/admin/socios');
            return;
        }
        if ($this->userModel->updatePadronMiembro($data)) {
            try {
                $memRol = $isAdminMiembro ? 'admin' : 'socio';
                $memExtra = $idSocio > 0 ? ['id_socio' => $idSocio] : [];
                $this->membresiaModel->upsert($socioId, $juntaId, $memRol, $memExtra);
                if (!$isAdminMiembro) {
                    $this->syncDomicilioMembresia($socioId, $juntaId, $data);
                }
            } catch (Exception $e) {
                // Tabla de membresías opcional hasta migración SQL
            }
            $etiqueta = $isAdminMiembro ? 'administrador' : 'socio';
            $_SESSION['success_msg'] = 'Datos del ' . $etiqueta . ' "' . $data['nombres'] . '" actualizados correctamente.';
        } else {
            $_SESSION['error_msg'] = 'No se pudieron guardar los cambios.';
        }
        $this->redirect('/admin/socios');
    }

    // Crear una nueva calle en la jurisdicción de la junta (POST)
    public function calle_crear() {
        $this->requireManageSocios();
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once APPROOT . '/core/SocioGeoref.php';
            $post = $this->sanitizePost();
            $nombre = mb_strtoupper(trim($post['nombre'] ?? ''), 'UTF-8');
            $juntaId = $_SESSION['user_junta_id'];
            $comuna = $_SESSION['user_junta_comuna'] ?? '';

            if ($nombre === '') {
                $_SESSION['error_msg'] = 'El nombre de la calle no puede estar vacío.';
                $this->redirect('/admin/socios');
                return;
            }

            $this->db->query("SELECT * FROM calles WHERE junta_id = :junta_id AND nombre = :nombre");
            $this->db->bind(':junta_id', $juntaId);
            $this->db->bind(':nombre', $nombre);
            if ($this->db->single()) {
                $_SESSION['error_msg'] = 'La calle ya se encuentra registrada en esta Junta.';
                $this->redirect('/admin/socios');
                return;
            }

            $georef = SocioGeoref::geocodeStreet($nombre, $comuna, $this->db);
            try {
                $this->db->query('SELECT lat_centro FROM calles LIMIT 1');
                $this->db->execute();
                $hasGeoref = true;
            } catch (Exception $e) {
                $hasGeoref = false;
            }

            if ($hasGeoref && $georef) {
                $this->db->query('INSERT INTO calles (junta_id, nombre, lat_centro, lng_centro) VALUES (:junta_id, :nombre, :lat, :lng)');
                $this->db->bind(':lat', $georef['latitud']);
                $this->db->bind(':lng', $georef['longitud']);
            } else {
                $this->db->query('INSERT INTO calles (junta_id, nombre) VALUES (:junta_id, :nombre)');
            }
            $this->db->bind(':junta_id', $juntaId);
            $this->db->bind(':nombre', $nombre);

            if ($this->db->execute()) {
                $msg = 'Calle "' . htmlspecialchars($nombre) . '" registrada correctamente en la jurisdicción.';
                if ($georef) {
                    $msg .= ' Ubicación aproximada guardada en el mapa.';
                }
                $_SESSION['success_msg'] = $msg;
            } else {
                $_SESSION['error_msg'] = 'Error al registrar la calle.';
            }
        }
        $this->redirect('/admin/socios');
    }

    // Eliminar una calle de la jurisdicción (POST)
    public function calle_eliminar($id) {
        $this->requireManageSocios();
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $juntaId = $_SESSION['user_junta_id'];

            // Verificar que la calle pertenece a la junta del administrador logueado
            $this->db->query("SELECT * FROM calles WHERE id = :id AND junta_id = :junta_id");
            $this->db->bind(':id', $id);
            $this->db->bind(':junta_id', $juntaId);
            $calle = $this->db->single();

            if (!$calle) {
                $_SESSION['error_msg'] = 'Calle no encontrada o no pertenece a tu jurisdicción.';
                $this->redirect('/admin/socios');
                return;
            }

            // Eliminar la calle (los usuarios asociados quedarán con calle_id = NULL por la restricción ON DELETE SET NULL)
            $this->db->query("DELETE FROM calles WHERE id = :id");
            $this->db->bind(':id', $id);

            if ($this->db->execute()) {
                $_SESSION['success_msg'] = 'Calle "' . htmlspecialchars($calle->nombre) . '" eliminada correctamente.';
            } else {
                $_SESSION['error_msg'] = 'Error al eliminar la calle.';
            }
        }
        $this->redirect('/admin/socios');
    }

    // Resetear contraseña: clave temporal aleatoria enviada al correo del socio (POST)
    public function socio_reset_password($id) {
        $this->requireManageSocios();
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $juntaId = $_SESSION['user_junta_id'];
            $socio = $this->userModel->getPadronMiembroById($id, $juntaId);
            if ($socio) {
                $result = TempPassword::issueToUser($socio);
                if ($result['ok']) {
                    $_SESSION['success_msg'] = 'Se le envió una contraseña temporal al usuario "' . $socio->nombre . '". '
                        . 'Deberá cambiarla al ingresar por primera vez.';
                } else {
                    $_SESSION['error_msg'] = $result['error'] ?? 'Error al restablecer la contraseña.';
                }
            } else {
                $_SESSION['error_msg'] = 'Usuario no encontrado o no pertenece a tu Junta.';
            }
        }
        $this->redirect('/admin/socios');
    }

    // Dar de baja a un socio (POST)
    public function socio_eliminar($id) {
        $this->requireManageSocios();
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $juntaId = $_SESSION['user_junta_id'];
            $socio = $this->userModel->getPadronMiembroById($id, $juntaId);
            if (!$socio) {
                $_SESSION['error_msg'] = 'Socio no encontrado.';
            } elseif (($socio->rol ?? '') === 'admin') {
                $_SESSION['error_msg'] = 'No se puede dar de baja a un administrador desde el padrón.';
            } elseif ($this->userModel->delete($id)) {
                $_SESSION['success_msg'] = 'Socio "' . $socio->nombre . '" dado de baja correctamente (Estado: Inactivo).';
            } else {
                $_SESSION['error_msg'] = 'Error al dar de baja al socio.';
            }
        }
        $this->redirect('/admin/socios');
    }

    // Reactivar / Dar de alta a un socio (POST)
    public function socio_reactivar($id) {
        $this->requireManageSocios();
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener el socio de cualquier estado
            $socio = $this->userModel->getUserById($id);
            if ($socio && $socio->junta_id == $_SESSION['user_junta_id'] && $socio->rol === 'socio') {
                if ($this->userModel->reactivate($id)) {
                    $_SESSION['success_msg'] = 'Socio "' . $socio->nombre . '" reincorporado y dado de alta con éxito.';
                } else {
                    $_SESSION['error_msg'] = 'Error al reactivar al socio.';
                }
            } else {
                $_SESSION['error_msg'] = 'Socio no válido o no pertenece a su Junta.';
            }
        }
        $this->redirect('/admin/socios');
    }

    // Modificar/Ajustar valor de cuota de la Junta (POST)
    public function cuota_ajustar() {
        $this->requireManageSocios();
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->sanitizePost();

            $dataConfig = [
                'junta_id' => $_SESSION['user_junta_id'],
                'monto' => $post['monto'] ?? '0',
                'mes_inicio' => $post['mes_inicio'] ?? date('Y-m') // YYYY-MM
            ];

            if (empty($dataConfig['monto']) || $dataConfig['monto'] < 0 || empty($dataConfig['mes_inicio'])) {
                $_SESSION['error_msg'] = 'Datos de ajuste de cuota inválidos.';
                $this->redirect('/admin/socios');
            }

            if ($this->cuotaModel->createConfig($dataConfig)) {
                $_SESSION['success_msg'] = 'Ajuste de cuota programado correctamente. El nuevo valor de $' . number_format($dataConfig['monto'], 0, ',', '.') . ' rige a partir de: ' . $dataConfig['mes_inicio'];
            } else {
                $_SESSION['error_msg'] = 'Error al registrar el ajuste de cuota.';
            }
        }
        $this->redirect('/admin/socios');
    }

    // =========================================================================
    // 3. REGISTRO FINANCIERO Y CONTROL DE TRANSACCIONES
    // =========================================================================
    public function finanzas() {
        $this->requireRegisterPayments();
        $juntaId = $this->activeJuntaId();
        $ctx = $this->finanzasContextData($juntaId);
        $balance = $this->transaccionModel->getBalanceConsolidado($juntaId);
        if ($ctx['saldo_inicial'] !== null) {
            $balance['saldo_inicial'] = $ctx['saldo_inicial'];
            $balance['contable'] = $ctx['saldo_inicial'] + $balance['neto'];
        } else {
            $balance['saldo_inicial'] = null;
            $balance['contable'] = $balance['neto'];
        }

        $data = array_merge([
            'title' => 'Movimientos',
            'header_title' => 'Registro de Movimientos',
            'header_subtitle' => 'Registre recaudación de cuotas, otros ingresos y gastos generales de la junta',
            'active_menu' => 'finanzas',
            'conceptos_migration_pending' => !$this->conceptoModel->hasConceptosTable(),
            'socios' => $this->userModel->getMiembrosCuotaByJunta($juntaId),
            'transacciones' => $this->transaccionModel->getTransaccionesByJunta($juntaId),
            'balance' => $balance,
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ], $ctx, $this->finanzasViewExtras());

        unset($_SESSION['success_msg']);
        unset($_SESSION['error_msg']);

        $this->view('admin/finanzas', $data);
    }

    public function guardar_saldo_inicial() {
        $this->requireRegisterPayments();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/finanzas');
            return;
        }

        $juntaId = $this->activeJuntaId();
        if (!$this->cierreModel->esPrimerCierre($juntaId)) {
            $_SESSION['error_msg'] = 'El saldo inicial ya no puede modificarse porque existe al menos un cierre mensual.';
            $this->redirect('/admin/finanzas');
            return;
        }

        $post = $this->sanitizePost();
        if (!isset($post['saldo_inicial']) || $post['saldo_inicial'] === '') {
            $_SESSION['error_msg'] = 'Debe indicar el saldo inicial de caja.';
            $this->redirect('/admin/finanzas');
            return;
        }

        $monto = (int)$post['saldo_inicial'];
        if ($monto < 0) {
            $_SESSION['error_msg'] = 'El saldo inicial no puede ser negativo.';
            $this->redirect('/admin/finanzas');
            return;
        }

        if ($this->juntaModel->setSaldoInicial($juntaId, $monto)) {
            $_SESSION['success_msg'] = 'Saldo inicial de caja guardado: $' . number_format($monto, 0, ',', '.') . '. Podrá editarlo hasta realizar el primer cierre mensual.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo guardar el saldo inicial. Verifique que la migración SQL esté aplicada.';
        }
        $this->redirect('/admin/finanzas');
    }

    private function redirectConceptosCaja(?string $tipo = null): void {
        $tab = in_array($tipo, ['ingreso', 'egreso'], true) ? $tipo : 'ingreso';
        $this->redirect('/admin/conceptos_caja?tab=' . $tab);
    }

    public function flujo_caja() {
        $this->requireViewFlujoCaja();
        $juntaId = $this->activeJuntaId();
        require_once APPROOT . '/core/AuthContext.php';
        if (!$this->isMaestroFinanzasMode() && !AuthContext::isFullAdmin() && !AuthContext::isFlujoCajaEnabled()) {
            $_SESSION['error_msg'] = 'El flujo de caja no está habilitado para su organización. El administrador debe activarlo en Socios y Ajustes.';
            $this->redirect('/admin/dashboard');
            return;
        }
        $mesInicio = $this->cierreModel->getMesInicioJunta($juntaId);
        $anios = $this->transaccionModel->getAniosDisponiblesFlujo($juntaId, $mesInicio);

        $anioReq = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
        if (!in_array($anioReq, $anios, true)) {
            $anioReq = in_array((int)date('Y'), $anios, true)
                ? (int)date('Y')
                : (int)$anios[count($anios) - 1];
        }

        $matriz = $this->transaccionModel->getFlujoCajaMatrizAnual(
            $juntaId,
            $anioReq,
            $mesInicio,
            $this->conceptoModel,
            $this->juntaModel->getSaldoInicial($juntaId)
        );

        $data = array_merge([
            'title' => 'Flujo de Caja',
            'header_title' => 'Flujo de Caja Anual',
            'header_subtitle' => 'Vista mensualizada de ingresos y egresos por concepto',
            'active_menu' => 'flujo_caja',
            'anios' => $anios,
            'anio_seleccionado' => $anioReq,
            'matriz' => $matriz,
            'mes_inicio' => $mesInicio,
            'saldo_inicial' => $matriz['saldo_inicial'],
            'flujo_caja_habilitado' => $this->isMaestroFinanzasMode() || AuthContext::isFlujoCajaEnabled(),
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ], $this->finanzasViewExtras());

        unset($_SESSION['success_msg']);
        unset($_SESSION['error_msg']);

        $this->view('admin/flujo_caja', $data);
    }

    public function conceptos_caja() {
        $this->requireRegisterPayments();
        $juntaId = $this->activeJuntaId();

        if (!$this->conceptoModel->hasConceptosTable()) {
            $data = [
                'title' => 'Conceptos de Caja',
                'header_title' => 'Conceptos de Ingreso y Egreso',
                'header_subtitle' => 'Migración de base de datos pendiente',
                'active_menu' => 'conceptos_caja',
                'migration_pending' => true,
                'success' => '',
                'error' => $_SESSION['error_msg'] ?? '',
            ];
            unset($_SESSION['error_msg']);
            $this->view('admin/conceptos_caja', $data);
            return;
        }

        $this->conceptoModel->ensureDefaults($juntaId);

        $tab = $_GET['tab'] ?? 'ingreso';
        if (!in_array($tab, ['ingreso', 'egreso'], true)) {
            $tab = 'ingreso';
        }

        $data = array_merge([
            'title' => 'Conceptos de Caja',
            'header_title' => 'Conceptos de Ingreso y Egreso',
            'header_subtitle' => 'Personalice cómo agrupa los movimientos de caja su organización',
            'active_menu' => 'conceptos_caja',
            'tab_activa' => $tab,
            'conceptos_ingreso' => $this->conceptoModel->getByJuntaWithUso($juntaId, 'ingreso'),
            'conceptos_egreso' => $this->conceptoModel->getByJuntaWithUso($juntaId, 'egreso'),
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ], $this->finanzasViewExtras());

        unset($_SESSION['success_msg']);
        unset($_SESSION['error_msg']);

        $this->view('admin/conceptos_caja', $data);
    }

    /** Alias: /admin/concepto_caja → conceptos_caja (evita error 500 por URL sin «s»). */
    public function concepto_caja() {
        $tab = $_GET['tab'] ?? 'ingreso';
        $qs = in_array($tab, ['ingreso', 'egreso'], true) ? '?tab=' . rawurlencode($tab) : '';
        $this->redirect('/admin/conceptos_caja' . $qs);
    }

    public function concepto_caja_crear() {
        $this->requireRegisterPayments();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/conceptos_caja');
            return;
        }

        $post = $this->sanitizePost();
        $juntaId = $this->activeJuntaId();
        $tipo = $post['tipo'] ?? '';
        $nombre = trim($post['nombre'] ?? '');

        if (!in_array($tipo, ['ingreso', 'egreso'], true) || $nombre === '') {
            $_SESSION['error_msg'] = 'Indique tipo y nombre del concepto.';
            $this->redirect('/admin/conceptos_caja');
            return;
        }

        if ($this->conceptoModel->createConcepto($juntaId, $tipo, $nombre)) {
            $_SESSION['success_msg'] = 'Concepto "' . htmlspecialchars($nombre) . '" creado correctamente.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo crear el concepto. Puede que ya exista con ese nombre.';
        }
        $this->redirectConceptosCaja($tipo);
    }

    public function concepto_caja_actualizar() {
        $this->requireRegisterPayments();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/conceptos_caja');
            return;
        }

        $post = $this->sanitizePost();
        $juntaId = $this->activeJuntaId();
        $id = (int)($post['concepto_id'] ?? 0);
        $nombre = trim($post['nombre'] ?? '');
        $activo = isset($post['activo']) && $post['activo'] === '1';

        if ($id <= 0 || $nombre === '') {
            $_SESSION['error_msg'] = 'Datos incompletos para actualizar el concepto.';
            $this->redirectConceptosCaja();
            return;
        }

        $concepto = $this->conceptoModel->getById($id, $juntaId);
        if (!$concepto) {
            $_SESSION['error_msg'] = 'Concepto no encontrado.';
            $this->redirectConceptosCaja();
            return;
        }

        if ($this->conceptoModel->updateConcepto($id, $juntaId, $nombre, $activo)) {
            $_SESSION['success_msg'] = 'Concepto actualizado correctamente.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo actualizar el concepto.';
        }
        $this->redirectConceptosCaja($concepto->tipo ?? null);
    }

    public function concepto_caja_eliminar() {
        $this->requireRegisterPayments();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/conceptos_caja');
            return;
        }

        $post = $this->sanitizePost();
        $juntaId = $this->activeJuntaId();
        $id = (int)($post['concepto_id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['error_msg'] = 'Concepto no válido.';
            $this->redirectConceptosCaja();
            return;
        }

        $concepto = $this->conceptoModel->getById($id, $juntaId);
        if (!$concepto) {
            $_SESSION['error_msg'] = 'Concepto no encontrado.';
            $this->redirectConceptosCaja();
            return;
        }

        $uso = $this->conceptoModel->countUsoConcepto($juntaId, $concepto->tipo, $concepto->nombre);
        if ($uso > 0) {
            $_SESSION['error_msg'] = 'No se puede eliminar «' . $concepto->nombre . '»: tiene ' . $uso . ' movimiento(s) registrado(s). Desactívelo en su lugar.';
            $this->redirectConceptosCaja($concepto->tipo);
            return;
        }

        if ($this->conceptoModel->deleteConcepto($id, $juntaId)) {
            $_SESSION['success_msg'] = 'Concepto eliminado.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo eliminar el concepto.';
        }
        $this->redirectConceptosCaja($concepto->tipo);
    }

    // Registrar pago de cuota de socio (POST, soporta múltiples meses)
    public function registrar_pago_cuota() {
        $this->requireRegisterPayments();
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->sanitizePost();

            $socioId = $post['socio_id'] ?? '';
            $mesesPagados = $post['mes_pagado'] ?? []; // Array de meses (Formato YYYY-MM)
            $fechaPago = $post['fecha_pago'] ?? date('Y-m-d');
            $esCondonado = isset($_POST['es_condonado']) && $_POST['es_condonado'] == '1';
            $justificacion = trim($_POST['justificacion'] ?? '');

            if (!is_array($mesesPagados)) {
                $mesesPagados = !empty($mesesPagados) ? [$mesesPagados] : [];
            }

            if (empty($socioId) || empty($mesesPagados)) {
                $_SESSION['error_msg'] = 'Debe seleccionar un socio y al menos un mes a procesar.';
                $this->redirect('/admin/finanzas');
                return;
            }

            // 1. Validar que el socio pertenece a la Junta
            $juntaId = $this->activeJuntaId();
            $socio = $this->userModel->getMiembroCuotaById((int)$socioId, $juntaId);
            if (!$socio) {
                $_SESSION['error_msg'] = 'Socio no válido.';
                $this->redirect('/admin/finanzas');
                return;
            }

            if ($esCondonado && empty($justificacion)) {
                $_SESSION['error_msg'] = 'Debe indicar una justificación/motivo para eximir el pago de las cuotas.';
                $this->redirect('/admin/finanzas');
                return;
            }

            $fechaError = $this->validarFechaFinanzas($juntaId, $fechaPago);
            if ($fechaError) {
                $_SESSION['error_msg'] = $fechaError;
                $this->redirect('/admin/finanzas');
                return;
            }

            // 2. Procesar transacciones con integridad transaccional
            try {
                $this->db->beginTransaction();

                $countExitosos = 0;
                $totalMonto = 0;

                foreach ($mesesPagados as $mesPagado) {
                    $mesPagado = trim($mesPagado);
                    
                    // Validar duplicado
                    if ($this->transaccionModel->checkPagoSocio($socioId, $mesPagado, $juntaId)) {
                        throw new Exception('El mes ' . $mesPagado . ' ya registra un pago o exención previamente para ' . $socio->nombre . '.');
                    }

                    if ($esCondonado) {
                        $montoCuota = 0;
                        $categoria = 'Cuota Condonada';
                        $descripcion = $justificacion;
                    } else {
                        $categoria = 'Cuota Socio';
                        $descripcion = 'Pago cuota correspondiente a ' . $mesPagado;

                        // Recuperar el valor de cuota que regía en dicho mes
                        $quotaConfig = $this->cuotaModel->getCuotaVigente($juntaId, $mesPagado);
                        $montoCuota = $quotaConfig ? (int)$quotaConfig->monto : 0;

                        if ($montoCuota <= 0) {
                            throw new Exception('No hay configurado un valor de cuota válido para el mes ' . $mesPagado . '.');
                        }
                    }

                    // Registrar Transacción
                    $dataTransaccion = [
                        'junta_id' => $juntaId,
                        'tipo' => 'ingreso',
                        'categoria' => $categoria,
                        'monto' => $montoCuota,
                        'descripcion' => $descripcion,
                        'fecha' => $fechaPago,
                        'socio_id' => $socioId,
                        'mes_pagado' => $mesPagado,
                        'registrado_por' => $_SESSION['user_id']
                    ];

                    if (!$this->transaccionModel->createTransaccion($dataTransaccion)) {
                        throw new Exception('Error al insertar el registro de cuota para ' . $mesPagado . '.');
                    }

                    $countExitosos++;
                    $totalMonto += $montoCuota;
                }

                $this->db->commit();

                if ($esCondonado) {
                    $_SESSION['success_msg'] = 'Se registraron con éxito ' . $countExitosos . ' condonaciones de cuotas para el socio ' . $socio->nombre . '.';
                } else {
                    $_SESSION['success_msg'] = 'Se registraron con éxito ' . $countExitosos . ' cuotas para el socio ' . $socio->nombre . ' por un total de $' . number_format($totalMonto, 0, ',', '.') . ' CLP.';
                }

            } catch (Exception $e) {
                $this->db->rollBack();
                $_SESSION['error_msg'] = 'Operación cancelada: ' . $e->getMessage();
            }
        }
        $this->redirect('/admin/finanzas');
    }

    // Obtener los meses pendientes, futuros y pagados de un socio (AJAX JSON)
    public function get_socio_cuotas($socioId) {
        header('Content-Type: application/json');
        require_once APPROOT . '/core/AuthContext.php';
        if (!isset($_SESSION['user_id']) || (!$this->isMaestroFinanzasMode() && !AuthContext::canRegisterPayments())) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit;
        }

        // Obtener socio o administrador
        $juntaId = $this->activeJuntaId();
        $socio = $this->userModel->getMiembroCuotaById((int)$socioId, $juntaId);
        if (!$socio) {
            echo json_encode(['success' => false, 'message' => 'Socio no válido']);
            exit;
        }

        // Mes de inicio de actividades de la organización (control de cuotas)
        $startMonth = $this->cierreModel->getMesInicioJunta($juntaId);
        $currentMonthStr = date('Y-m');

        // Generar lista de meses desde inicio hasta el año actual + 1 año
        $startYear = (int)substr($startMonth, 0, 4);
        $startMonthNum = (int)substr($startMonth, 5, 2);
        
        $endYear = (int)date('Y') + 1;
        $endMonthNum = (int)date('m');
        
        $y = $startYear;
        $m = $startMonthNum;
        
        $meses = [];
        $db = new Database();

        while ($y < $endYear || ($y == $endYear && $m <= $endMonthNum)) {
            $mes = sprintf('%04d-%02d', $y, $m);
            
            // 1. Obtener la cuota que regía para ese mes
            $cuotaConfig = $this->cuotaModel->getCuotaVigente($juntaId, $mes);
            $monto = $cuotaConfig ? (int)$cuotaConfig->monto : 0;
            
            // 2. Verificar si está pagada o condonada
            $db->query("SELECT * FROM transacciones 
                        WHERE socio_id = :socio_id 
                        AND junta_id = :junta_id
                        AND categoria IN ('Cuota Socio', 'Cuota Condonada') 
                        AND mes_pagado = :mes_pagado LIMIT 1");
            $db->bind(':socio_id', $socioId);
            $db->bind(':junta_id', $juntaId);
            $db->bind(':mes_pagado', $mes);
            $trans = $db->single();
            
            $estado = '';
            $descripcion = '';
            if ($trans) {
                if ($trans->categoria === 'Cuota Condonada') {
                    $estado = 'condonado';
                    $descripcion = !empty($trans->descripcion) ? $trans->descripcion : 'Exento';
                } else {
                    $estado = 'pagado';
                    $descripcion = 'Pagado el ' . date('d-m-Y', strtotime($trans->fecha));
                }
            } else {
                if ($mes <= $currentMonthStr) {
                    $estado = 'pendiente';
                } else {
                    $estado = 'futuro';
                }
            }
            
            $meses[] = [
                'mes' => $mes,
                'monto' => $monto,
                'estado' => $estado,
                'descripcion' => $descripcion
            ];
            
            $m++;
            if ($m > 12) {
                $m = 1;
                $y++;
            }
        }

        echo json_encode([
            'success' => true,
            'socio' => [
                'id' => $socio->id,
                'nombre' => $socio->nombre,
                'fecha_inicio' => $socio->fecha_inicio,
                'mes_inicio_actividades' => $startMonth,
                'prevalidar' => ($socio->status ?? '') === 'prevalidar',
                'es_admin' => ($socio->rol ?? '') === 'admin',
            ],
            'meses' => $meses
        ]);
        exit;
    }

    // Registrar otros ingresos o egresos (POST)
    public function registrar_transaccion() {
        $this->requireRegisterPayments();
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->sanitizePost();

            $tipo = $post['tipo'] ?? ''; // ingreso / egreso
            $categoria = $post['categoria'] ?? '';
            $monto = $post['monto'] ?? '';
            $descripcion = $post['descripcion'] ?? '';
            $fecha = $post['fecha'] ?? date('Y-m-d');
            $socioId = !empty($post['socio_id']) ? $post['socio_id'] : null;

            if (empty($tipo) || empty($categoria) || empty($monto) || $monto <= 0) {
                $_SESSION['error_msg'] = 'Por favor, rellene todos los campos requeridos correctamente.';
                $this->redirect('/admin/finanzas');
                return;
            }

            $juntaId = $this->activeJuntaId();
            $this->conceptoModel->ensureDefaults($juntaId);
            if (!$this->conceptoModel->isConceptoValido($juntaId, $tipo, $categoria)) {
                $_SESSION['error_msg'] = 'La categoría seleccionada no es válida para su organización.';
                $this->redirect('/admin/finanzas');
                return;
            }

            $fechaError = $this->validarFechaFinanzas($juntaId, $fecha);
            if ($fechaError) {
                $_SESSION['error_msg'] = $fechaError;
                $this->redirect('/admin/finanzas');
                return;
            }

            // Validar que el socio pertenece a la misma junta si se especificó
            if ($socioId) {
                $socio = $this->userModel->getMiembroCuotaById((int)$socioId, $juntaId);
                if (!$socio) {
                    $_SESSION['error_msg'] = 'El socio seleccionado no es válido para su organización.';
                    $this->redirect('/admin/finanzas');
                    return;
                }
            }

            $dataTransaccion = [
                'junta_id' => $juntaId,
                'tipo' => $tipo,
                'categoria' => $categoria,
                'monto' => $monto,
                'descripcion' => $descripcion,
                'fecha' => $fecha,
                'socio_id' => $socioId,
                'registrado_por' => $_SESSION['user_id']
            ];

            if ($this->transaccionModel->createTransaccion($dataTransaccion)) {
                $_SESSION['success_msg'] = 'Movimiento de finanzas registrado exitosamente (' . htmlspecialchars($tipo) . ': $' . number_format($monto, 0, ',', '.') . ').';
            } else {
                $_SESSION['error_msg'] = 'Error al registrar la transacción.';
            }
        }
        $this->redirect('/admin/finanzas');
    }

    public function transaccion_actualizar() {
        $this->requireRegisterPayments();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/finanzas');
            return;
        }

        $post = $this->sanitizePost();
        $id = (int)($post['transaccion_id'] ?? 0);
        $juntaId = $this->activeJuntaId();
        $tx = $this->transaccionModel->getTransaccionByIdAndJunta($id, $juntaId);

        if (!$tx) {
            $_SESSION['error_msg'] = 'Movimiento no encontrado.';
            $this->redirect('/admin/finanzas');
            return;
        }

        $errMes = $this->assertTransaccionMesAbierto($juntaId, $tx->fecha);
        if ($errMes) {
            $_SESSION['error_msg'] = $errMes;
            $this->redirect('/admin/finanzas');
            return;
        }

        $fecha = trim($post['fecha'] ?? $tx->fecha);
        $descripcion = trim($post['descripcion'] ?? '');
        $fechaError = $this->validarFechaFinanzas($juntaId, $fecha);
        if ($fechaError) {
            $_SESSION['error_msg'] = $fechaError;
            $this->redirect('/admin/finanzas');
            return;
        }

        $errNuevoMes = $this->assertTransaccionMesAbierto($juntaId, $fecha);
        if ($errNuevoMes) {
            $_SESSION['error_msg'] = $errNuevoMes;
            $this->redirect('/admin/finanzas');
            return;
        }

        if ($this->esTransaccionCuota($tx)) {
            $mesPagado = trim($post['mes_pagado'] ?? $tx->mes_pagado ?? '');
            if ($mesPagado === '' || !preg_match('/^\d{4}-\d{2}$/', $mesPagado)) {
                $_SESSION['error_msg'] = 'Indique un mes válido para la cuota (YYYY-MM).';
                $this->redirect('/admin/finanzas');
                return;
            }

            if ((int)$tx->socio_id > 0 && $this->transaccionModel->checkPagoSocioExcluding((int)$tx->socio_id, $mesPagado, $juntaId, $id)) {
                $_SESSION['error_msg'] = 'El mes ' . $mesPagado . ' ya tiene un pago o exención registrado para este socio.';
                $this->redirect('/admin/finanzas');
                return;
            }

            if ($tx->categoria === 'Cuota Condonada') {
                if ($descripcion === '') {
                    $_SESSION['error_msg'] = 'Debe indicar la justificación de la condonación.';
                    $this->redirect('/admin/finanzas');
                    return;
                }
                $monto = 0;
            } else {
                $quotaConfig = $this->cuotaModel->getCuotaVigente($juntaId, $mesPagado);
                $monto = $quotaConfig ? (int)$quotaConfig->monto : 0;
                if ($monto <= 0) {
                    $_SESSION['error_msg'] = 'No hay valor de cuota configurado para el mes ' . $mesPagado . '.';
                    $this->redirect('/admin/finanzas');
                    return;
                }
                if ($descripcion === '') {
                    $descripcion = 'Pago cuota correspondiente a ' . $mesPagado;
                }
            }

            $dataUpdate = [
                'tipo' => 'ingreso',
                'categoria' => $tx->categoria,
                'monto' => $monto,
                'descripcion' => $descripcion,
                'fecha' => $fecha,
                'socio_id' => $tx->socio_id,
                'mes_pagado' => $mesPagado,
            ];
        } else {
            $tipo = $post['tipo'] ?? $tx->tipo;
            $categoria = trim($post['categoria'] ?? $tx->categoria);
            $monto = (int)($post['monto'] ?? 0);
            $socioId = !empty($post['socio_id']) ? (int)$post['socio_id'] : null;

            if (!in_array($tipo, ['ingreso', 'egreso'], true) || $categoria === '' || $monto <= 0) {
                $_SESSION['error_msg'] = 'Complete tipo, categoría y monto válidos.';
                $this->redirect('/admin/finanzas');
                return;
            }

            $this->conceptoModel->ensureDefaults($juntaId);
            if (!$this->conceptoModel->isConceptoValido($juntaId, $tipo, $categoria)) {
                $_SESSION['error_msg'] = 'La categoría seleccionada no es válida.';
                $this->redirect('/admin/finanzas');
                return;
            }

            if ($socioId) {
                $socio = $this->userModel->getMiembroCuotaById($socioId, $juntaId);
                if (!$socio) {
                    $_SESSION['error_msg'] = 'El socio seleccionado no es válido.';
                    $this->redirect('/admin/finanzas');
                    return;
                }
            }

            $dataUpdate = [
                'tipo' => $tipo,
                'categoria' => $categoria,
                'monto' => $monto,
                'descripcion' => $descripcion !== '' ? $descripcion : null,
                'fecha' => $fecha,
                'socio_id' => $socioId,
                'mes_pagado' => null,
            ];
        }

        if ($this->transaccionModel->updateTransaccion($id, $juntaId, $dataUpdate)) {
            $_SESSION['success_msg'] = 'Movimiento actualizado correctamente.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo actualizar el movimiento.';
        }
        $this->redirect('/admin/finanzas');
    }

    public function transaccion_eliminar() {
        $this->requireRegisterPayments();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/finanzas');
            return;
        }

        $post = $this->sanitizePost();
        $id = (int)($post['transaccion_id'] ?? 0);
        $juntaId = $this->activeJuntaId();
        $tx = $this->transaccionModel->getTransaccionByIdAndJunta($id, $juntaId);

        if (!$tx) {
            $_SESSION['error_msg'] = 'Movimiento no encontrado.';
            $this->redirect('/admin/finanzas');
            return;
        }

        $errMes = $this->assertTransaccionMesAbierto($juntaId, $tx->fecha);
        if ($errMes) {
            $_SESSION['error_msg'] = $errMes;
            $this->redirect('/admin/finanzas');
            return;
        }

        if ($this->transaccionModel->deleteTransaccion($id, $juntaId)) {
            $_SESSION['success_msg'] = 'Movimiento eliminado correctamente.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo eliminar el movimiento.';
        }
        $this->redirect('/admin/finanzas');
    }

    // =========================================================================
    // 4. REUNIONES Y ASISTENCIA
    // =========================================================================

    private function requireReunionesPlan(): bool {
        return ($_SESSION['user_junta_plan'] ?? 'basico') !== 'basico';
    }

    private function requireManageReuniones(): void {
        require_once APPROOT . '/core/AuthContext.php';
        if (!$this->requireReunionesPlan()) {
            $_SESSION['error_msg'] = 'Las reuniones requieren Plan Mediano o superior.';
            $this->redirect('/admin/dashboard');
            exit;
        }
        if (!AuthContext::canManageReuniones()) {
            $_SESSION['error_msg'] = 'No tiene permisos para convocar reuniones o tomar asistencia.';
            $this->redirectUserHome();
            exit;
        }
    }

    private function requireManageVotaciones(): void {
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::canManageVotaciones()) {
            $_SESSION['error_msg'] = 'No tiene permisos para gestionar votaciones o encuestas.';
            $this->redirectUserHome();
            exit;
        }
        if (!$this->votacionModel->tablesExist()) {
            $_SESSION['error_msg'] = 'Ejecute la migración sql/add_votaciones_rsvp_reunion_fin.sql en la base de datos.';
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    /** @return int[] */
    private function resolveElectoresFromPost(array $post, int $juntaId): array {
        $audiencia = $post['audiencia_tipo'] ?? 'todos_socios';
        if ($audiencia === 'todos_socios') {
            return array_map(static fn($m) => (int)$m->id, $this->membresiaModel->getMiembrosActivosParaConvocatoria($juntaId));
        }
        if ($audiencia === 'directiva') {
            $ids = [];
            foreach ($this->membresiaModel->getMiembrosActivosParaConvocatoria($juntaId) as $m) {
                $cargo = strtoupper((string)($m->cargo ?? ''));
                if (($m->rol ?? '') === 'admin' || !empty($m->permiso_todos)
                    || in_array($cargo, ['SECRETARIO', 'TESORERO', 'DIRECTOR'], true)) {
                    $ids[] = (int)$m->id;
                }
            }
            return $ids;
        }
        $ids = [];
        foreach ($post['electores'] ?? [] as $id) {
            $ids[] = (int)$id;
        }
        return array_values(array_unique(array_filter($ids, static fn($id) => $id > 0)));
    }

    private function parseVotacionPost(array $post): array {
        $inicio = trim($post['fecha_inicio'] ?? '');
        $fin = trim($post['fecha_fin'] ?? '');
        return [
            'titulo' => trim($post['titulo'] ?? ''),
            'descripcion' => trim($post['descripcion'] ?? ''),
            'tipo' => ($post['tipo'] ?? '') === 'encuesta' ? 'encuesta' : 'votacion',
            'audiencia_tipo' => in_array($post['audiencia_tipo'] ?? '', ['directiva', 'seleccionados', 'todos_socios'], true)
                ? $post['audiencia_tipo'] : 'todos_socios',
            'fecha_inicio' => $inicio !== '' ? str_replace('T', ' ', $inicio) . ':00' : '',
            'fecha_fin' => $fin !== '' ? str_replace('T', ' ', $fin) . ':00' : '',
            'resultados_visibilidad' => ($post['resultados_visibilidad'] ?? '') === 'todos' ? 'todos' : 'directiva',
            'opciones' => array_filter(array_map('trim', $post['opciones'] ?? [])),
            'publicar' => !empty($post['publicar']),
        ];
    }

    public function reunion_reenviar_rsvp() {
        $this->requireManageReuniones();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/asistencia');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $id = (int)($_POST['reunion_id'] ?? 0);
        $reunion = $this->reunionModel->getReunionByIdAndJunta($id, $juntaId);
        if (!$reunion || $reunion->estado !== 'programada') {
            $_SESSION['error_msg'] = 'Convocatoria no válida.';
            $this->redirect('/admin/asistencia/' . $id);
            return;
        }
        $pendientes = $this->reunionConvocadoModel->getConvocadosSinRespuesta($id, $juntaId);
        $ids = array_map(static fn($r) => (int)$r->id, $pendientes);
        if (empty($ids)) {
            $_SESSION['success_msg'] = 'Todos los convocados ya respondieron.';
            $this->redirect('/admin/asistencia/' . $id);
            return;
        }
        $mailResult = $this->enviarEmailsConvocatoria($reunion, $ids, $juntaId, true);
        $_SESSION['success_msg'] = 'Recordatorio enviado a ' . $mailResult['enviados'] . ' persona(s) sin respuesta.';
        $this->redirect('/admin/asistencia/' . $id);
    }

    public function votaciones($id = null) {
        $this->requireManageVotaciones();
        $juntaId = (int)$_SESSION['user_junta_id'];
        $this->votacionModel->syncEstadosActivas();
        $editId = $id ? (int)$id : (int)($_GET['editar'] ?? 0);
        $data = [
            'title' => 'Votaciones y encuestas',
            'header_title' => 'Votaciones y encuestas',
            'header_subtitle' => 'Consultas formales con control de electores, plazos y confidencialidad',
            'active_menu' => 'votaciones',
            'votaciones' => $this->votacionModel->getByJunta($juntaId),
            'miembros' => $this->membresiaModel->getMiembrosActivosParaConvocatoria($juntaId),
            'edit' => null,
            'edit_opciones' => [],
            'edit_electores' => [],
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ];
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);
        if ($editId > 0) {
            $edit = $this->votacionModel->getByIdAndJunta($editId, $juntaId);
            if ($edit && in_array($edit->estado, ['borrador', 'activa'], true)) {
                $data['edit'] = $edit;
                $data['edit_opciones'] = $this->votacionModel->getOpciones($editId);
                $data['edit_electores'] = $this->votacionModel->getElectoresIds($editId);
            }
        }
        $this->view('admin/votaciones', $data);
    }

    public function votacion_crear() {
        $this->requireManageVotaciones();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/votaciones');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $parsed = $this->parseVotacionPost($this->sanitizePost());
        if ($parsed['titulo'] === '' || $parsed['fecha_inicio'] === '' || $parsed['fecha_fin'] === '' || count($parsed['opciones']) < 2) {
            $_SESSION['error_msg'] = 'Complete título, fechas y al menos 2 opciones.';
            $this->redirect('/admin/votaciones');
            return;
        }
        $estado = $parsed['publicar'] ? 'activa' : 'borrador';
        $vid = $this->votacionModel->create([
            'junta_id' => $juntaId,
            'titulo' => $parsed['titulo'],
            'descripcion' => $parsed['descripcion'] ?: null,
            'tipo' => $parsed['tipo'],
            'creado_por' => (int)$_SESSION['user_id'],
            'audiencia_tipo' => $parsed['audiencia_tipo'],
            'fecha_inicio' => $parsed['fecha_inicio'],
            'fecha_fin' => $parsed['fecha_fin'],
            'resultados_visibilidad' => $parsed['resultados_visibilidad'],
            'estado' => $estado,
        ]);
        if (!$vid) {
            $_SESSION['error_msg'] = 'No se pudo crear la votación.';
            $this->redirect('/admin/votaciones');
            return;
        }
        $this->votacionModel->replaceOpciones($vid, $parsed['opciones']);
        if ($parsed['audiencia_tipo'] === 'seleccionados') {
            $this->votacionModel->replaceElectores($vid, $this->resolveElectoresFromPost($this->sanitizePost(), $juntaId));
        }
        $_SESSION['success_msg'] = $estado === 'activa' ? 'Votación publicada.' : 'Borrador guardado.';
        $this->redirect('/admin/votacion_ver/' . $vid);
    }

    public function votacion_actualizar() {
        $this->requireManageVotaciones();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/votaciones');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $post = $this->sanitizePost();
        $id = (int)($post['votacion_id'] ?? 0);
        $parsed = $this->parseVotacionPost($post);
        if (!$this->votacionModel->update($id, $juntaId, [
            'titulo' => $parsed['titulo'],
            'descripcion' => $parsed['descripcion'],
            'tipo' => $parsed['tipo'],
            'audiencia_tipo' => $parsed['audiencia_tipo'],
            'fecha_inicio' => $parsed['fecha_inicio'],
            'fecha_fin' => $parsed['fecha_fin'],
            'resultados_visibilidad' => $parsed['resultados_visibilidad'],
        ])) {
            $_SESSION['error_msg'] = 'No se pudo actualizar.';
            $this->redirect('/admin/votaciones?editar=' . $id);
            return;
        }
        if (count($parsed['opciones']) >= 2) {
            $this->votacionModel->replaceOpciones($id, $parsed['opciones']);
        }
        if ($parsed['audiencia_tipo'] === 'seleccionados') {
            $this->votacionModel->replaceElectores($id, $this->resolveElectoresFromPost($post, $juntaId));
        } else {
            $this->votacionModel->replaceElectores($id, []);
        }
        $_SESSION['success_msg'] = 'Votación actualizada.';
        $this->redirect('/admin/votacion_ver/' . $id);
    }

    public function votacion_ver($id = 0) {
        $this->requireManageVotaciones();
        require_once APPROOT . '/core/AuthContext.php';
        $juntaId = (int)$_SESSION['user_junta_id'];
        $id = (int)$id;
        $this->votacionModel->syncEstadosActivas();
        $v = $this->votacionModel->getByIdAndJunta($id, $juntaId);
        if (!$v) {
            $_SESSION['error_msg'] = 'Votación no encontrada.';
            $this->redirect('/admin/votaciones');
            return;
        }
        $link = URLROOT . '/votacion/l/' . $v->token_publico;
        $data = [
            'title' => $v->titulo,
            'header_title' => $v->titulo,
            'header_subtitle' => ucfirst($v->tipo) . ' · ' . ucfirst($v->estado),
            'active_menu' => 'votaciones',
            'votacion' => $v,
            'opciones' => $this->votacionModel->getResultadosAgregados($id),
            'link_publico' => $link,
            'puede_ver_detalle' => $this->votacionModel->canViewVoterDetail($v, (int)$_SESSION['user_id'], AuthContext::isFullAdmin()),
            'detalle_votantes' => [],
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ];
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);
        if ($data['puede_ver_detalle']) {
            $data['detalle_votantes'] = $this->votacionModel->getDetalleVotantes($id, $juntaId);
        }
        $this->view('admin/votacion_ver', $data);
    }

    public function votacion_publicar() {
        $this->requireManageVotaciones();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/votaciones');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $id = (int)($_POST['votacion_id'] ?? 0);
        $this->votacionModel->updateEstado($id, $juntaId, 'activa');
        $_SESSION['success_msg'] = 'Votación activada.';
        $this->redirect('/admin/votacion_ver/' . $id);
    }

    public function votacion_cerrar() {
        $this->requireManageVotaciones();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/votaciones');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $id = (int)($_POST['votacion_id'] ?? 0);
        $this->votacionModel->updateEstado($id, $juntaId, 'cerrada');
        $_SESSION['success_msg'] = 'Votación cerrada.';
        $this->redirect('/admin/votacion_ver/' . $id);
    }

    public function votacion_votar($id = 0) {
        require_once APPROOT . '/core/AuthContext.php';
        $juntaId = (int)$_SESSION['user_junta_id'];
        $uid = (int)$_SESSION['user_id'];
        $id = (int)$id;
        $this->votacionModel->syncEstadosActivas();
        $v = $this->votacionModel->getByIdAndJunta($id, $juntaId);
        if (!$v) {
            $_SESSION['error_msg'] = 'Votación no encontrada.';
            $this->redirectUserHome();
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!$this->votacionModel->canUserParticipate($v, $uid, $juntaId)) {
                $_SESSION['error_msg'] = 'No puede votar en esta consulta.';
                $this->redirectUserHome();
                return;
            }
            $opcionId = (int)($_POST['opcion_id'] ?? 0);
            $texto = trim($_POST['respuesta_texto'] ?? '');
            if ($v->tipo === 'encuesta' && $texto === '' && $opcionId <= 0) {
                $_SESSION['error_msg'] = 'Indique su respuesta.';
                $this->redirect('/admin/votacion_votar/' . $id);
                return;
            }
            if ($v->tipo === 'votacion' && $opcionId <= 0) {
                $_SESSION['error_msg'] = 'Seleccione una opción.';
                $this->redirect('/admin/votacion_votar/' . $id);
                return;
            }
            $this->votacionModel->registerVote($id, $uid, $opcionId > 0 ? $opcionId : null, $texto !== '' ? $texto : null);
            $_SESSION['success_msg'] = 'Su voto fue registrado de forma confidencial.';
            if (AuthContext::canManageVotaciones()) {
                $this->redirect('/admin/votacion_ver/' . $id);
            } else {
                $this->redirect('/socio/votaciones');
            }
            return;
        }
        $data = [
            'title' => 'Participar: ' . $v->titulo,
            'header_title' => $v->titulo,
            'header_subtitle' => 'Su voto es confidencial para el resto de la organización',
            'active_menu' => 'votaciones',
            'votacion' => $v,
            'opciones' => $this->votacionModel->getOpciones($id),
            'ya_voto' => $this->votacionModel->hasUserVoted($id, $uid),
            'puede_votar' => $this->votacionModel->canUserParticipate($v, $uid, $juntaId),
            'back_url' => URLROOT . '/admin/votaciones',
        ];
        $this->view('admin/votacion_votar', $data);
    }

    /** @return int[] */
    private function resolveConvocadosFromPost(array $post, int $juntaId): array {
        $ids = [];
        if (!empty($post['convocar_todos'])) {
            foreach ($this->membresiaModel->getMiembrosActivosParaConvocatoria($juntaId) as $m) {
                $ids[] = (int)$m->id;
            }
        }
        if (!empty($post['convocar_directorio'])) {
            $ids = array_merge($ids, $this->membresiaModel->getDirectivoUsuarioIds($juntaId));
        }
        if (!empty($post['convocados']) && is_array($post['convocados'])) {
            foreach ($post['convocados'] as $uid) {
                $ids[] = (int)$uid;
            }
        }
        return array_values(array_unique(array_filter($ids, static fn($id) => $id > 0)));
    }

    private function enviarEmailsConvocatoria(object $reunion, array $usuarioIds, int $juntaId, bool $soloSinRespuesta = false): array {
        require_once APPROOT . '/core/Mailer.php';
        require_once APPROOT . '/core/ConvocatoriaMail.php';

        if (!Mailer::isConfigured()) {
            return ['enviados' => 0, 'errores' => 0, 'sin_config' => true];
        }

        $juntaNombre = $_SESSION['user_junta_nombre'] ?? 'Organización';
        $temasHtml = ConvocatoriaMail::temasToHtml($this->reunionModel->getTemasText($reunion));
        $fechaFmt = date('d/m/Y \a \l\a\s H:i \h\r\s', strtotime($reunion->fecha_reunion));
        $urlApp = URLROOT . '/socio/reuniones';
        $subject = 'Convocatoria: ' . $reunion->titulo . ' — ' . $juntaNombre;
        $hasRsvp = $this->reunionConvocadoModel->hasRsvpColumns();

        $enviados = 0;
        $errores = 0;
        foreach ($usuarioIds as $uid) {
            if ($soloSinRespuesta && $hasRsvp) {
                $estado = $this->reunionConvocadoModel->getRsvpForUsuario((int)$reunion->id, $uid);
                if ($estado && $estado !== 'pendiente') {
                    continue;
                }
            }
            $user = $this->userModel->getUserById($uid);
            if (!$user || empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $urlConfirmar = null;
            $urlRechazar = null;
            if ($hasRsvp) {
                $token = $this->reunionConvocadoModel->getRsvpToken((int)$reunion->id, $uid);
                if ($token) {
                    $urlConfirmar = URLROOT . '/reunion/rsvp/' . $token . '/confirmar';
                    $urlRechazar = URLROOT . '/reunion/rsvp/' . $token . '/rechazar';
                }
            }
            $html = ConvocatoriaMail::buildHtml(
                $user->nombre ?? 'Socio',
                $juntaNombre,
                $reunion->titulo,
                $fechaFmt,
                $temasHtml,
                $urlApp,
                $urlConfirmar,
                $urlRechazar
            );
            $result = Mailer::send($user->email, $subject, $html, defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : null);
            if ($result['ok']) {
                $enviados++;
                $this->reunionConvocadoModel->markEmailSent((int)$reunion->id, $uid);
            } else {
                $errores++;
            }
        }
        return ['enviados' => $enviados, 'errores' => $errores, 'sin_config' => false];
    }

    public function asistencia($reunionId = null) {
        if (!$this->requireReunionesPlan()) {
            $this->view('admin/upgrade_required', [
                'title' => 'Mejora Requerida',
                'header_title' => 'Módulo de Asistencia Bloqueado',
                'header_subtitle' => 'Se requiere subir de plan para acceder a esta característica',
                'active_menu' => 'asistencia',
                'required_plan' => 'Mediano',
            ]);
            return;
        }
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::canManageReuniones()) {
            $_SESSION['error_msg'] = 'No tiene permisos para gestionar reuniones.';
            $this->redirectUserHome();
            return;
        }

        $juntaId = (int)$_SESSION['user_junta_id'];
        $editId = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;

        $data = [
            'title' => 'Reuniones y Asistencia',
            'header_title' => 'Control de Reuniones y Asistencia',
            'header_subtitle' => 'Convoque asambleas, envíe invitaciones y registre minutas',
            'active_menu' => 'asistencia',
            'reuniones' => $this->reunionModel->getReunionesByJunta($juntaId),
            'reunion_detalle' => null,
            'reunion_editar' => null,
            'asistentes' => [],
            'convocados' => [],
            'convocados_edit' => [],
            'miembros' => $this->membresiaModel->getMiembrosActivosParaConvocatoria($juntaId),
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ];
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        if ($editId > 0) {
            $edit = $this->reunionModel->getReunionByIdAndJunta($editId, $juntaId);
            if ($edit && $edit->estado === 'programada') {
                $data['reunion_editar'] = $edit;
                $data['convocados_edit'] = $this->reunionConvocadoModel->getUsuarioIdsByReunion($editId);
            }
        }

        if ($reunionId) {
            $reunion = $this->reunionModel->getReunionByIdAndJunta((int)$reunionId, $juntaId);
            if ($reunion) {
                $data['reunion_detalle'] = $reunion;
                $convocados = $this->reunionConvocadoModel->getUsuariosByReunion((int)$reunionId, $juntaId);
                $data['convocados'] = $convocados;
                $data['asistentes'] = !empty($convocados)
                    ? $this->asistenciaModel->getAsistenciaConvocadosByReunion((int)$reunionId, $juntaId)
                    : $this->asistenciaModel->getAsistenciaByReunion((int)$reunionId, $juntaId);
                $data['presentes_count'] = $this->asistenciaModel->countPresentesByReunion((int)$reunionId);
                $data['rsvp_stats'] = $this->reunionConvocadoModel->getRsvpStats((int)$reunionId);
            }
        }

        $this->view('admin/asistencia', $data);
    }

    public function reunion_crear() {
        $this->requireManageReuniones();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/asistencia');
            return;
        }

        $juntaId = (int)$_SESSION['user_junta_id'];
        $post = $this->sanitizePost();
        $titulo = trim($post['titulo'] ?? '');
        $fecha = trim($post['fecha_reunion'] ?? '');
        $temas = trim($post['temas_tratar'] ?? '');

        if ($titulo === '' || $fecha === '') {
            $_SESSION['error_msg'] = 'Complete título y fecha de la convocatoria.';
            $this->redirect('/admin/asistencia?tab=convocar');
            return;
        }

        $convocados = $this->resolveConvocadosFromPost($post, $juntaId);
        if (empty($convocados)) {
            $_SESSION['error_msg'] = 'Seleccione al menos un destinatario.';
            $this->redirect('/admin/asistencia?tab=convocar');
            return;
        }

        $reunionId = $this->reunionModel->createReunion([
            'junta_id' => $juntaId,
            'titulo' => $titulo,
            'temas_tratar' => $temas !== '' ? $temas : null,
            'fecha_reunion' => $fecha,
            'estado' => 'programada',
            'convocada_por' => (int)$_SESSION['user_id'],
            'email_convocatoria' => !empty($post['enviar_email']),
        ]);

        if (!$reunionId) {
            $_SESSION['error_msg'] = 'No se pudo crear la convocatoria.';
            $this->redirect('/admin/asistencia?tab=convocar');
            return;
        }

        $this->reunionConvocadoModel->replaceForReunion($reunionId, $convocados);
        $reunion = $this->reunionModel->getReunionById($reunionId);
        $msg = 'Convocatoria registrada para ' . count($convocados) . ' persona(s).';

        if (!empty($post['enviar_email']) && $reunion) {
            $mailResult = $this->enviarEmailsConvocatoria($reunion, $convocados, $juntaId);
            if (!empty($mailResult['sin_config'])) {
                $msg .= ' Correo no configurado; la invitación quedó en el perfil de cada socio.';
            } else {
                $msg .= ' Correos enviados: ' . $mailResult['enviados'] . '.';
            }
        } else {
            $msg .= ' Visible en el perfil de los convocados.';
        }
        $_SESSION['success_msg'] = $msg;
        $this->redirect('/admin/asistencia?tab=listado');
    }

    public function reunion_actualizar() {
        $this->requireManageReuniones();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/asistencia');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $post = $this->sanitizePost();
        $id = (int)($post['reunion_id'] ?? 0);

        if (!$this->reunionModel->updateConvocatoria($id, $juntaId, [
            'titulo' => trim($post['titulo'] ?? ''),
            'fecha_reunion' => trim($post['fecha_reunion'] ?? ''),
            'temas_tratar' => trim($post['temas_tratar'] ?? ''),
        ])) {
            $_SESSION['error_msg'] = 'No se pudo actualizar la convocatoria.';
            $this->redirect('/admin/asistencia?editar=' . $id . '&tab=convocar');
            return;
        }

        $convocados = $this->resolveConvocadosFromPost($post, $juntaId);
        if (!empty($convocados)) {
            $this->reunionConvocadoModel->replaceForReunion($id, $convocados);
        }
        if (!empty($post['reenviar_email'])) {
            $reunion = $this->reunionModel->getReunionById($id);
            $this->enviarEmailsConvocatoria($reunion, $this->reunionConvocadoModel->getUsuarioIdsByReunion($id), $juntaId);
        }

        $_SESSION['success_msg'] = 'Convocatoria actualizada.';
        $this->redirect('/admin/asistencia/' . $id . '?tab=listado');
    }

    public function reunion_resultados() {
        $this->requireManageReuniones();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/asistencia');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $post = $this->sanitizePost();
        $id = (int)($post['reunion_id'] ?? 0);
        $horaInicio = trim($post['hora_inicio_real'] ?? '');
        $horaFin = trim($post['hora_fin_real'] ?? '');
        $horaVal = $horaInicio !== '' ? str_replace('T', ' ', $horaInicio) . ':00' : null;
        $horaFinVal = $horaFin !== '' ? str_replace('T', ' ', $horaFin) . ':00' : null;
        $finalizar = !empty($post['finalizar_reunion']);

        if (!$this->reunionModel->updateResultados($id, $juntaId, trim($post['resultados'] ?? ''), $horaVal, $horaFinVal, $finalizar)) {
            $_SESSION['error_msg'] = 'No se pudieron guardar los resultados.';
            $this->redirect('/admin/asistencia/' . $id . '?tab=listado');
            return;
        }
        $_SESSION['success_msg'] = $finalizar ? 'Reunión finalizada. Puede imprimir la minuta.' : 'Resultados guardados.';
        $this->redirect('/admin/asistencia/' . $id . '?tab=listado');
    }

    public function asistencia_guardar($reunionId) {
        $this->requireManageReuniones();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/asistencia');
            return;
        }

        $juntaId = (int)$_SESSION['user_junta_id'];
        $reunionId = (int)$reunionId;
        if (!$this->reunionModel->getReunionByIdAndJunta($reunionId, $juntaId)) {
            $_SESSION['error_msg'] = 'Reunión no válida.';
            $this->redirect('/admin/asistencia');
            return;
        }

        $sociosAsistentes = $_POST['asistencia'] ?? [];
        $convocadoIds = $this->reunionConvocadoModel->getUsuarioIdsByReunion($reunionId);
        $sociosLista = !empty($convocadoIds)
            ? array_filter($this->userModel->getSociosByJunta($juntaId), static fn($s) => in_array((int)$s->id, $convocadoIds, true))
            : $this->userModel->getSociosByJunta($juntaId);
        foreach ($sociosLista as $socio) {
            $asistio = in_array((string)$socio->id, array_map('strval', $sociosAsistentes), true) ? 1 : 0;
            $this->asistenciaModel->saveAsistencia($reunionId, $socio->id, $asistio);
        }
        $_SESSION['success_msg'] = 'Lista de asistencia guardada.';
        $this->redirect('/admin/asistencia/' . $reunionId . '?tab=listado');
    }

    public function asistencia_qr_registrar() {
        header('Content-Type: application/json; charset=utf-8');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
            return;
        }
        if (!$this->requireReunionesPlan()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Requiere Plan Mediano o superior']);
            return;
        }
        require_once APPROOT . '/core/AuthContext.php';
        require_once APPROOT . '/core/AsistenciaQr.php';
        if (!AuthContext::canManageReuniones()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Sin permisos para registrar asistencia']);
            return;
        }

        $juntaId = (int)$_SESSION['user_junta_id'];
        $post = $this->sanitizePost();
        $reunionId = (int)($post['reunion_id'] ?? 0);
        $rawPayload = trim($post['payload'] ?? '');

        $reunion = $this->reunionModel->getReunionByIdAndJunta($reunionId, $juntaId);
        if (!$reunion) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Reunión no válida']);
            return;
        }

        $token = AsistenciaQr::parseScannedText($rawPayload);
        if (!$token) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Código QR no reconocido']);
            return;
        }

        if (!$this->userModel->hasAsistenciaQrColumn()) {
            http_response_code(503);
            echo json_encode(['ok' => false, 'message' => 'Ejecute sql/add_asistencia_qr_token.sql']);
            return;
        }

        $socio = $this->userModel->findByAsistenciaQrToken($token, $juntaId);
        if (!$socio) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Socio no encontrado en esta organización']);
            return;
        }

        if (!$this->reunionConvocadoModel->isConvocado($reunionId, (int)$socio->id)) {
            $nombreErr = trim(($socio->nombre ?? '') . ' ' . ($socio->apellido_paterno ?? ''));
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => $nombreErr . ' no está en la lista de convocados']);
            return;
        }

        $yaPresente = $this->asistenciaModel->getAsistioForSocio($reunionId, (int)$socio->id);
        $this->asistenciaModel->saveAsistencia($reunionId, (int)$socio->id, 1);
        $nombre = trim(($socio->nombre ?? '') . ' ' . ($socio->apellido_paterno ?? ''));

        echo json_encode([
            'ok' => true,
            'message' => $yaPresente ? ($nombre . ' ya estaba registrado/a') : ($nombre . ' — asistencia registrada'),
            'socio_id' => (int)$socio->id,
            'nombre' => $nombre,
            'ya_presente' => $yaPresente,
            'presentes' => $this->asistenciaModel->countPresentesByReunion($reunionId),
            'total_convocados' => count($this->reunionConvocadoModel->getUsuarioIdsByReunion($reunionId)),
        ]);
    }

    public function reunion_minuta($id) {
        require_once APPROOT . '/core/AuthContext.php';
        $juntaId = (int)$_SESSION['user_junta_id'];
        $reunionId = (int)$id;
        $reunion = $this->reunionModel->getReunionByIdAndJunta($reunionId, $juntaId);
        if (!$reunion) {
            $_SESSION['error_msg'] = 'Reunión no encontrada.';
            $this->redirect('/admin/asistencia');
            return;
        }

        $rol = $_SESSION['user_rol'] ?? '';
        if ($rol === 'socio') {
            if (!$this->reunionConvocadoModel->isConvocado($reunionId, (int)$_SESSION['user_id'])) {
                $_SESSION['error_msg'] = 'No tiene acceso a esta minuta.';
                $this->redirect('/socio/reuniones');
                return;
            }
        } elseif (!AuthContext::canManageReuniones()) {
            $_SESSION['error_msg'] = 'Sin permisos.';
            $this->redirect('/admin/asistencia');
            return;
        }

        $asistentes = $this->asistenciaModel->getAsistenciaByReunion($reunionId, $juntaId);
        $this->view('admin/reunion_minuta', [
            'title' => 'Minuta — ' . $reunion->titulo,
            'reunion' => $reunion,
            'temas' => $this->reunionModel->getTemasText($reunion),
            'presentes' => array_filter($asistentes, static fn($a) => !empty($a->asistio)),
            'total_socios' => count($asistentes),
            'junta_nombre' => $_SESSION['user_junta_nombre'] ?? '',
            'back_url' => $rol === 'socio' ? URLROOT . '/socio/reuniones' : URLROOT . '/admin/asistencia/' . $reunionId,
        ]);
    }

    // =========================================================================
    // 5. DIGITALIZACIÓN Y TRANSMISIÓN DIRECTA A LA MUNICIPALIDAD
    // =========================================================================
    public function municipalidad() {
        if (($_SESSION['user_junta_plan'] ?? 'basico') !== 'premium') {
            $data = [
                'title' => 'Mejora Requerida',
                'header_title' => 'Conexión Municipal Bloqueada',
                'header_subtitle' => 'Se requiere subir de plan para acceder a esta característica',
                'active_menu' => 'municipalidad',
                'required_plan' => 'Premium'
            ];
            $this->view('admin/upgrade_required', $data);
            return;
        }

        $juntaId = $_SESSION['user_junta_id'];
        
        // 1. Obtener datos de la Junta
        $junta = $this->juntaModel->getJuntaById($juntaId);
        
        // 2. Consolidar el padrón de socios
        $socios = $this->userModel->getSociosByJunta($juntaId);
        $padronSocios = [];
        foreach ($socios as $socio) {
            $padronSocios[] = [
                'rut' => $socio->rut,
                'nombre' => $socio->nombre,
                'email' => $socio->email,
                'telefono' => $socio->telefono,
                'estado' => $socio->estado == 1 ? 'Activo' : 'Inactivo'
            ];
        }

        // 3. Consolidar balance financiero
        $balance = $this->transaccionModel->getBalanceConsolidado($juntaId);
        
        // 4. Consolidar asistencia
        $promedioAsistencia = $this->asistenciaModel->getPromedioAsistencia($juntaId);
        
        // Estructura del paquete de datos digitalizado (JSON)
        $paqueteDigitalizado = [
            'conectabarrio_version' => '1.0',
            'fecha_consolidado' => date('Y-m-d H:i:s'),
            'junta_vecinos' => [
                'id' => $junta->id,
                'rut' => $junta->rut_junta,
                'nombre' => $junta->nombre,
                'comuna' => $junta->comuna,
                'direccion' => $junta->direccion
            ],
            'resumen_estadistico' => [
                'total_socios_activos' => count($padronSocios),
                'promedio_asistencia_asambleas' => $promedioAsistencia . '%',
                'balance_financiero' => [
                    'total_ingresos' => $balance['ingresos'],
                    'total_egresos' => $balance['egresos'],
                    'superavit_neto' => $balance['neto']
                ]
            ],
            'padron_socios' => $padronSocios
        ];

        // Obtener historial de reportes ya enviados
        $this->db->query("SELECT r.*, u.nombre as admin_nombre 
                         FROM reportes_municipalidad r 
                         LEFT JOIN usuarios u ON r.enviado_por = u.id 
                         WHERE r.junta_id = :junta_id 
                         ORDER BY r.fecha_envio DESC");
        $this->db->bind(':junta_id', $juntaId);
        $reportesHistorial = $this->db->resultSet();

        $data = [
            'title' => 'Transmisión Municipal',
            'header_title' => 'Conexión Directa con Municipalidad',
            'header_subtitle' => 'Digitalice toda la información de la junta y envíela al portal municipal en un solo clic',
            'active_menu' => 'municipalidad',
            'junta' => $junta,
            'paquete_json' => $paqueteDigitalizado,
            'reportes' => $reportesHistorial,
            'ultimo_certificado' => !empty($reportesHistorial) ? $reportesHistorial[0] : null,
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? ''
        ];

        unset($_SESSION['success_msg']);
        unset($_SESSION['error_msg']);

        $this->view('admin/municipalidad', $data);
    }

    // Procesar almacenamiento de reporte enviado (POST de confirmación de simulación)
    public function municipalidad_guardar_envio() {
        if (($_SESSION['user_junta_plan'] ?? 'basico') !== 'premium') {
            $_SESSION['error_msg'] = 'La Transmisión Municipal no está habilitada en su Plan actual.';
            $this->redirect('/admin/dashboard');
            return;
        }

        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->sanitizePost();
            $juntaId = $_SESSION['user_junta_id'];
            $tipoReporte = $post['tipo_reporte'] ?? 'Consolidado General';
            $datosJson = $post['datos_json'] ?? '{}';

            $this->db->query("INSERT INTO reportes_municipalidad (junta_id, tipo_reporte, datos_json, enviado_por) 
                             VALUES (:junta_id, :tipo_reporte, :datos_json, :enviado_por)");
            $this->db->bind(':junta_id', $juntaId);
            $this->db->bind(':tipo_reporte', $tipoReporte);
            $this->db->bind(':datos_json', $datosJson);
            $this->db->bind(':enviado_por', $_SESSION['user_id']);

            if ($this->db->execute()) {
                $_SESSION['success_msg'] = 'Información enviada exitosamente. Se ha generado un Certificado de Recepción Municipal digital.';
            } else {
                $_SESSION['error_msg'] = 'Error al registrar el reporte municipalizado.';
            }
        }
        $this->redirect('/admin/municipalidad');
    }

    // Visualizar recibo de pago para imprimir
    public function comprobante($id) {
        $this->requireRegisterPayments();
        $pago = $this->transaccionModel->getComprobanteById($id);
        $juntaId = $this->activeJuntaId();

        if (!$pago || (int)$pago->junta_id !== $juntaId) {
            die('Comprobante no válido o no pertenece a su Junta de Vecinos.');
        }

        $data = [
            'title' => 'Comprobante de Pago Folio #' . $pago->id,
            'pago' => $pago
        ];

        // Cargar vista de comprobante limpio (sin sidebar de dashboard)
        $this->view('admin/comprobante_detalle', $data);
    }

    // =========================================================================
    // 6. CIERRES FINANCIEROS MENSUALES Y ENVÍO DE BALANCE
    // =========================================================================
    public function cierres() {
        $juntaId = $_SESSION['user_junta_id'];
        
        $cierres = $this->cierreModel->getCierresByJunta($juntaId);
        $mesesDisponibles = $this->cierreModel->getMesesDisponiblesParaCerrar($juntaId);
        
        // Obtener el balance del mes seleccionado o el primer mes disponible
        $mesSeleccionado = $_GET['mes'] ?? ($mesesDisponibles[0] ?? date('Y-m', strtotime('-1 month')));
        $resumenMes = $this->cierreModel->getResumenFinancieroMes($juntaId, $mesSeleccionado);
        
        // Cargar saldos contables dinámicos
        $saldoAnterior = $this->cierreModel->getSaldoAnterior($juntaId, $mesSeleccionado);
        $resumenMes['saldo_anterior'] = $saldoAnterior;
        $resumenMes['saldo_final'] = $saldoAnterior + $resumenMes['saldo_neto'];
        $resumenMes['transacciones'] = $this->cierreModel->getTransaccionesDetalleMes($juntaId, $mesSeleccionado);
        
        $esPrimerCierre = $this->cierreModel->esPrimerCierre($juntaId);
        $mesInicio = $this->cierreModel->getMesInicioJunta($juntaId);
        $mesPrevioSinCerrar = $this->cierreModel->tieneMesPrevioSinCerrar($juntaId, $mesSeleccionado);
        $esFuturoOMesEnCurso = ($mesSeleccionado >= date('Y-m'));
        $saldoInicialJunta = $this->juntaModel->getSaldoInicial($juntaId);

        $data = [
            'title' => 'Cierres Mensuales',
            'header_title' => 'Cierres Financieros Mensuales',
            'header_subtitle' => 'Realice el balance mensual de caja y notifique de forma transparente a todos sus socios vía correo',
            'active_menu' => 'cierres',
            'cierres' => $cierres,
            'meses_disponibles' => $mesesDisponibles,
            'mes_seleccionado' => $mesSeleccionado,
            'resumen_mes' => $resumenMes,
            'es_primer_cierre' => $esPrimerCierre,
            'mes_inicio' => $mesInicio,
            'mes_previo_sin_cerrar' => $mesPrevioSinCerrar,
            'es_futuro_o_mes_en_curso' => $esFuturoOMesEnCurso,
            'saldo_inicial_junta' => $saldoInicialJunta,
            'saldo_inicial_declarado' => $saldoInicialJunta !== null,
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? ''
        ];
        
        unset($_SESSION['success_msg']);
        unset($_SESSION['error_msg']);
        
        $this->view('admin/cierres', $data);
    }

    // Guardar el cierre mensual de un mes (POST)
    public function cerrar_mes() {
        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->sanitizePost();
            $juntaId = $_SESSION['user_junta_id'];
            $mes = $post['mes'] ?? '';
            $comentario = $post['comentario'] ?? '';
            
            if (empty($mes)) {
                $_SESSION['error_msg'] = 'Debe especificar el mes a cerrar.';
                $this->redirect('/admin/cierres');
            }
            
            // Validar que no sea el mes en curso ni futuro
            if ($mes >= date('Y-m')) {
                $_SESSION['error_msg'] = 'No está permitido realizar el cierre contable del mes en curso o futuro.';
                $this->redirect('/admin/cierres');
            }
            
            // Validar que no esté cerrado
            if ($this->cierreModel->checkCierreExist($juntaId, $mes)) {
                $_SESSION['error_msg'] = 'El mes ' . $mes . ' ya se encuentra cerrado.';
                $this->redirect('/admin/cierres');
            }
            
            // Validar secuencia cronológica
            $mesPrevioSinCerrar = $this->cierreModel->tieneMesPrevioSinCerrar($juntaId, $mes);
            if ($mesPrevioSinCerrar) {
                $_SESSION['error_msg'] = 'No se puede realizar el cierre del mes ' . $mes . ' porque el mes anterior ' . $mesPrevioSinCerrar . ' no está cerrado.';
                $this->redirect('/admin/cierres');
            }
            
            // Obtener el resumen del mes
            $resumen = $this->cierreModel->getResumenFinancieroMes($juntaId, $mes);
            
            $esPrimerCierre = $this->cierreModel->esPrimerCierre($juntaId);
            if ($esPrimerCierre) {
                $saldoInicial = $this->juntaModel->getSaldoInicial($juntaId);
                if ($saldoInicial === null) {
                    $_SESSION['error_msg'] = 'Debe declarar el saldo inicial de caja en Finanzas antes de realizar el primer cierre mensual.';
                    $this->redirect('/admin/cierres');
                    return;
                }
            }

            $saldoAnterior = $this->cierreModel->getSaldoAnterior($juntaId, $mes);
            
            $saldoFinal = $saldoAnterior + $resumen['saldo_neto'];
            
            $dataCierre = [
                'junta_id' => $juntaId,
                'mes' => $mes,
                'ingresos' => $resumen['ingresos'],
                'egresos' => $resumen['egresos'],
                'saldo_anterior' => $saldoAnterior,
                'saldo_final' => $saldoFinal,
                'saldo_neto' => $resumen['saldo_neto'],
                'cerrado_por' => $_SESSION['user_id'],
                'comentario' => $comentario
            ];
            
            if ($this->cierreModel->createCierre($dataCierre)) {
                $_SESSION['success_msg'] = 'Cierre financiero del mes ' . $mes . ' guardado exitosamente.';
            } else {
                $_SESSION['error_msg'] = 'Error al registrar el cierre mensual en la base de datos.';
            }
        }
        $this->redirect('/admin/cierres');
    }

    // Enviar balance mensual por correo a todos los socios (POST)
    public function enviar_balance_email($id) {
        if (($_SESSION['user_junta_plan'] ?? 'basico') === 'basico') {
            $_SESSION['error_msg'] = 'El envío de balances vía email no está habilitado en su Plan Básico.';
            $this->redirect('/admin/cierres');
            return;
        }

        if ($_SERVER['METHOD_POST'] ?? $_SERVER['REQUEST_METHOD'] === 'POST') {
            $cierre = $this->cierreModel->getCierreById($id);
            if (!$cierre || $cierre->junta_id != $_SESSION['user_junta_id']) {
                $_SESSION['error_msg'] = 'Cierre de mes no válido o no tiene permisos.';
                $this->redirect('/admin/cierres');
            }
            
            $juntaId = $_SESSION['user_junta_id'];
            $socios = $this->userModel->getSociosByJunta($juntaId);
            
            if (empty($socios)) {
                $_SESSION['error_msg'] = 'No hay socios activos inscritos en la junta para enviar correos.';
                $this->redirect('/admin/cierres');
            }
            
            $resumen = $this->cierreModel->getResumenFinancieroMes($juntaId, $cierre->mes);
            $transacciones = $this->cierreModel->getTransaccionesDetalleMes($juntaId, $cierre->mes);
            
            // Formatear mes en texto
            $parts = explode('-', $cierre->mes);
            $mesesNombres = [
                '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
            ];
            $mesNombre = ($mesesNombres[$parts[1]] ?? 'Mes') . ' ' . $parts[0];
            $juntaNombre = htmlspecialchars($cierre->junta_nombre);
            
            // Generar el cuerpo HTML premium a través de nuestro generador unificado
            $emailHtml = $this->generar_html_boletin($cierre, $resumen, $transacciones, $mesNombre);
            
            // Guardar copia local del correo enviado como respaldo visual en scratch/
            // (en producción Linux no existe la ruta de XAMPP; usamos la carpeta del proyecto)
            $scratchDir = dirname(APPROOT) . DIRECTORY_SEPARATOR . 'scratch';
            if (!is_dir($scratchDir)) {
                @mkdir($scratchDir, 0775, true);
            }
            if (is_dir($scratchDir) && is_writable($scratchDir)) {
                $respaldoPath = $scratchDir . DIRECTORY_SEPARATOR . "email_prev_{$id}.html";
                @file_put_contents($respaldoPath, $emailHtml);
            }
            
            if (!Mailer::isConfigured()) {
                $_SESSION['error_msg'] = 'Correo no configurado. Defina SMTP_HOST, SMTP_USER y SMTP_PASS (Brevo) en las variables de entorno de Coolify.';
                $this->redirect('/admin/cierres');
                return;
            }

            $countEnviados = 0;
            $erroresEnvio = 0;
            $subject = "Balance Financiero Mensual - {$mesNombre} - {$juntaNombre}";
            $replyTo = SMTP_FROM_EMAIL;

            foreach ($socios as $socio) {
                if (!empty($socio->email) && filter_var($socio->email, FILTER_VALIDATE_EMAIL)) {
                    $socioEmailHtml = str_replace(
                        "Balance Financiero Mensual</h2>",
                        "Balance Financiero Mensual</h2><p style='color: #ffffff; font-size: 14px; text-align: center; margin-top: 10px;'>Estimado(a) vecino(a) <strong>" . htmlspecialchars($socio->nombre) . "</strong>,</p>",
                        $emailHtml
                    );

                    $result = Mailer::send($socio->email, $subject, $socioEmailHtml, $replyTo);
                    if ($result['ok']) {
                        $countEnviados++;
                    } else {
                        $erroresEnvio++;
                    }
                }
            }
            
            // Actualizar base de datos
            $this->cierreModel->updateEnviadoCorreo($id);
            
            $msg = 'Balance de ' . $mesNombre . ' enviado a ' . $countEnviados . ' socios.';
            if ($erroresEnvio > 0) {
                $msg .= ' No se pudieron enviar ' . $erroresEnvio . ' correos (revise SMTP y logs).';
            }
            $_SESSION['success_msg'] = $msg;
        }
        $this->redirect('/admin/cierres');
    }

    // Visualizar el boletín de balance mensual de caja directo en el navegador (GET)
    public function visualizar_boletin($id) {
        // Validar sesión y rol administrativo
        if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
            header('location: ' . URLROOT . '/auth/login');
            exit;
        }
        
        $cierre = $this->cierreModel->getCierreById($id);
        if (!$cierre || $cierre->junta_id != $_SESSION['user_junta_id']) {
            die('Cierre de mes no válido o no tiene permisos de visualización.');
        }
        
        $juntaId = $_SESSION['user_junta_id'];
        $resumen = $this->cierreModel->getResumenFinancieroMes($juntaId, $cierre->mes);
        $transacciones = $this->cierreModel->getTransaccionesDetalleMes($juntaId, $cierre->mes);
        
        // Formatear mes en texto
        $parts = explode('-', $cierre->mes);
        $mesesNombres = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];
        $mesNombre = ($mesesNombres[$parts[1]] ?? 'Mes') . ' ' . $parts[0];
        
        // Generar HTML contable premium
        $emailHtml = $this->generar_html_boletin($cierre, $resumen, $transacciones, $mesNombre);
        
        // Forzar codificación UTF-8 impecable
        header('Content-Type: text/html; charset=UTF-8');
        echo $emailHtml;
        exit;
    }

    // Método de asistencia para construir la plantilla contable HTML interactiva responsiva del boletín
    private function generar_html_boletin($cierre, $resumen, $transacciones, $mesNombre) {
        $juntaNombre = htmlspecialchars($cierre->junta_nombre);
        $adminNombre = htmlspecialchars($cierre->admin_nombre);
        $comentario = !empty($cierre->comentario) ? htmlspecialchars($cierre->comentario) : 'Sin comentarios adicionales.';

        // Formatear montos consolidados de caja
        $saldoAnteriorVal = (int)($cierre->saldo_anterior ?? 0);
        $ingresosVal = (int)($cierre->ingresos ?? 0);
        $egresosVal = (int)($cierre->egresos ?? 0);
        $saldoFinalVal = (int)($cierre->saldo_final ?? 0);

        $saldoAnteriorFmt = '$' . number_format($saldoAnteriorVal, 0, ',', '.');
        $ingresosFmt = '$' . number_format($ingresosVal, 0, ',', '.');
        $egresosFmt = '$' . number_format($egresosVal, 0, ',', '.');
        $saldoFinalFmt = '$' . number_format($saldoFinalVal, 0, ',', '.');
        $saldoFinalColor = $saldoFinalVal >= 0 ? '#10b981' : '#ef4444';

        // 1. Tabla de Ingresos del Mes (detallado con sumatoria)
        $tablaIngresosHtml = '';
        $totalIngresosCalculado = 0;
        foreach ($transacciones as $t) {
            if ($t->tipo === 'ingreso') {
                $fechaFmt = date('d-m-Y', strtotime($t->fecha));
                $socioNombre = !empty($t->socio_nombre) ? htmlspecialchars($t->socio_nombre) : 'N/A';
                $categoria = htmlspecialchars($t->categoria);
                $descripcion = htmlspecialchars($t->descripcion);
                $montoVal = (int)$t->monto;
                $totalIngresosCalculado += $montoVal;
                $montoFmt = '$' . number_format($montoVal, 0, ',', '.');
                
                $tablaIngresosHtml .= "
                <tr style='border-bottom: 1px solid rgba(255,255,255,0.06);'>
                    <td style='padding: 8px 10px; font-size: 13px; color: #e4e4e7;'>{$fechaFmt}</td>
                    <td style='padding: 8px 10px; font-size: 13px; color: #e4e4e7;'>{$socioNombre}</td>
                    <td style='padding: 8px 10px; font-size: 13px; color: #a1a1aa;'>{$categoria}</td>
                    <td style='padding: 8px 10px; font-size: 13px; color: #a1a1aa;'>{$descripcion}</td>
                    <td style='padding: 8px 10px; font-size: 13px; font-weight: bold; text-align: right; color: #10b981;'>+{$montoFmt}</td>
                </tr>";
            }
        }
        if (empty($tablaIngresosHtml)) {
            $tablaIngresosHtml = "<tr><td colspan='5' style='text-align: center; padding: 15px; color: #a1a1aa; font-size: 13px;'>No se registraron ingresos en este periodo.</td></tr>";
        }
        $totalIngresosFmt = '$' . number_format($totalIngresosCalculado, 0, ',', '.');

        // 2. Tabla de Egresos del Mes (detallado con sumatoria)
        $tablaEgresosHtml = '';
        $totalEgresosCalculado = 0;
        foreach ($transacciones as $t) {
            if ($t->tipo === 'egreso') {
                $fechaFmt = date('d-m-Y', strtotime($t->fecha));
                $categoria = htmlspecialchars($t->categoria);
                $descripcion = htmlspecialchars($t->descripcion);
                $montoVal = (int)$t->monto;
                $totalEgresosCalculado += $montoVal;
                $montoFmt = '$' . number_format($montoVal, 0, ',', '.');
                
                $tablaEgresosHtml .= "
                <tr style='border-bottom: 1px solid rgba(255,255,255,0.06);'>
                    <td style='padding: 8px 10px; font-size: 13px; color: #e4e4e7;'>{$fechaFmt}</td>
                    <td style='padding: 8px 10px; font-size: 13px; color: #a1a1aa;'>{$categoria}</td>
                    <td style='padding: 8px 10px; font-size: 13px; color: #a1a1aa;'>{$descripcion}</td>
                    <td style='padding: 8px 10px; font-size: 13px; font-weight: bold; text-align: right; color: #ef4444;'>-{$montoFmt}</td>
                </tr>";
            }
        }
        if (empty($tablaEgresosHtml)) {
            $tablaEgresosHtml = "<tr><td colspan='4' style='text-align: center; padding: 15px; color: #a1a1aa; font-size: 13px;'>No se registraron egresos en este periodo.</td></tr>";
        }
        $totalEgresosFmt = '$' . number_format($totalEgresosCalculado, 0, ',', '.');

        // 3. Tabla de Desglose de Egresos por Categoría
        $tablaCategoriasHtml = '';
        if (!empty($resumen['desglose'])) {
            foreach ($resumen['desglose'] as $d) {
                if ($d->tipo === 'egreso') {
                    $montoFmt = '$' . number_format($d->total_monto, 0, ',', '.');
                    $categoria = htmlspecialchars($d->categoria);
                    
                    $tablaCategoriasHtml .= "
                    <tr style='border-bottom: 1px solid rgba(255,255,255,0.06);'>
                        <td style='padding: 8px 10px; font-size: 13px; color: #ffffff;'>{$categoria}</td>
                        <td style='padding: 8px 10px; font-size: 13px; color: #a1a1aa; text-align: center;'>{$d->cantidad}</td>
                        <td style='padding: 8px 10px; font-size: 13px; font-weight: bold; text-align: right; color: #ef4444;'>-{$montoFmt}</td>
                    </tr>";
                }
            }
        }
        if (empty($tablaCategoriasHtml)) {
            $tablaCategoriasHtml = "<tr><td colspan='3' style='text-align: center; padding: 15px; color: #a1a1aa; font-size: 13px;'>No se registraron categorías de egresos.</td></tr>";
        }

        // Armar el documento completo del boletín con UTF-8
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Balance Financiero Mensual - ConectaBarrio</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; color: #f8fafc; -webkit-font-smoothing: antialiased;'>
            <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #0f172a; padding: 30px 15px;'>
                <tr>
                    <td align='center'>
                        <!-- Contenedor Principal con estilo premium glassmorphism dark -->
                        <table width='650' border='0' cellspacing='0' cellpadding='0' style='background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden; padding: 25px;'>
                            
                            <!-- Encabezado -->
                            <tr>
                                <td align='center' style='border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 20px;'>
                                    <div style='color: #06b6d4; font-size: 24px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase;'>ConectaBarrio</div>
                                    <div style='color: #a1a1aa; font-size: 12px; margin-top: 4px;'>Tu Comunidad, más conectada y eficiente</div>
                                </td>
                            </tr>
                            
                            <!-- Título -->
                            <tr>
                                <td style='padding-top: 25px;'>
                                    <h2 style='color: #ffffff; font-size: 20px; font-weight: 700; margin: 0; text-align: center;'>Balance Financiero Mensual</h2>
                                    <p style='color: #06b6d4; font-size: 16px; font-weight: 600; margin: 5px 0 0 0; text-align: center;'>{$juntaNombre}</p>
                                    <p style='color: #a1a1aa; font-size: 14px; margin: 5px 0 20px 0; text-align: center;'>Periodo cerrado: <strong>{$mesNombre}</strong></p>
                                </td>
                            </tr>
                            
                            <!-- Tarjetas de Balance de Caja -->
                            <tr>
                                <td>
                                    <table width='100%' border='0' cellspacing='6' cellpadding='0' style='margin-bottom: 15px;'>
                                        <tr>
                                            <!-- Saldo Anterior -->
                                            <td width='25%' style='background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 12px; text-align: center;'>
                                                <div style='color: #a1a1aa; font-size: 10px; text-transform: uppercase; font-weight: bold;'>Saldo Anterior</div>
                                                <div style='color: #ffffff; font-size: 15px; font-weight: bold; margin-top: 5px;'>{$saldoAnteriorFmt}</div>
                                            </td>
                                            <!-- Ingresos -->
                                            <td width='25%' style='background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.15); border-radius: 8px; padding: 12px; text-align: center;'>
                                                <div style='color: #a1a1aa; font-size: 10px; text-transform: uppercase; font-weight: bold;'>Ingresos</div>
                                                <div style='color: #10b981; font-size: 15px; font-weight: bold; margin-top: 5px;'>+{$ingresosFmt}</div>
                                            </td>
                                            <!-- Egresos -->
                                            <td width='25%' style='background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.15); border-radius: 8px; padding: 12px; text-align: center;'>
                                                <div style='color: #a1a1aa; font-size: 10px; text-transform: uppercase; font-weight: bold;'>Egresos</div>
                                                <div style='color: #ef4444; font-size: 15px; font-weight: bold; margin-top: 5px;'>-{$egresosFmt}</div>
                                            </td>
                                            <!-- Saldo Final -->
                                            <td width='25%' style='background: rgba(6, 182, 212, 0.05); border: 1px solid rgba(6, 182, 212, 0.15); border-radius: 8px; padding: 12px; text-align: center;'>
                                                <div style='color: #a1a1aa; font-size: 10px; text-transform: uppercase; font-weight: bold;'>Saldo Final</div>
                                                <div style='color: {$saldoFinalColor}; font-size: 15px; font-weight: bold; margin-top: 5px;'>{$saldoFinalFmt}</div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            
                            <!-- Comentarios de Administración -->
                            <tr>
                                <td style='padding-top: 5px;'>
                                    <div style='background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 15px;'>
                                        <div style='color: #06b6d4; font-size: 13px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;'>Mensaje de la Directiva:</div>
                                        <div style='color: #e4e4e7; font-size: 14px; line-height: 1.5; font-style: italic;'>\"{$comentario}\"</div>
                                        <div style='color: #a1a1aa; font-size: 11px; margin-top: 10px; text-align: right;'>Cerrado por: <strong>{$adminNombre}</strong></div>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- TABLA 1: DETALLE DE INGRESOS DEL MES -->
                            <tr>
                                <td style='padding-top: 25px;'>
                                    <h3 style='color: #ffffff; font-size: 14px; font-weight: 700; margin: 0 0 10px 0; border-left: 3px solid #10b981; padding-left: 8px;'>1. Detalle de Ingresos del Mes</h3>
                                    <table width='100%' border='0' cellspacing='0' cellpadding='0' style='border-collapse: collapse; background: rgba(255,255,255,0.01); border: 1px solid rgba(255,255,255,0.05); border-radius: 6px; overflow: hidden;'>
                                        <thead>
                                            <tr style='background: rgba(16, 185, 129, 0.04); border-bottom: 1px solid rgba(255,255,255,0.1);'>
                                                <th align='left' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase;'>Fecha</th>
                                                <th align='left' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase;'>Socio</th>
                                                <th align='left' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase;'>Categoría</th>
                                                <th align='left' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase;'>Descripción</th>
                                                <th align='right' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase;'>Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {$tablaIngresosHtml}
                                        </tbody>
                                        <tfoot>
                                            <tr style='background: rgba(16, 185, 129, 0.08); border-top: 1.5px solid #10b981;'>
                                                <td colspan='4' align='right' style='padding: 10px; font-size: 12px; font-weight: bold; color: #ffffff;'>Total Ingresos:</td>
                                                <td align='right' style='padding: 10px; font-size: 13px; font-weight: bold; color: #10b981;'>{$totalIngresosFmt}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </td>
                            </tr>
                            
                            <!-- TABLA 2: DETALLE DE EGRESOS DEL MES -->
                            <tr>
                                <td style='padding-top: 25px;'>
                                    <h3 style='color: #ffffff; font-size: 14px; font-weight: 700; margin: 0 0 10px 0; border-left: 3px solid #ef4444; padding-left: 8px;'>2. Detalle de Egresos del Mes</h3>
                                    <table width='100%' border='0' cellspacing='0' cellpadding='0' style='border-collapse: collapse; background: rgba(255,255,255,0.01); border: 1px solid rgba(255,255,255,0.05); border-radius: 6px; overflow: hidden;'>
                                        <thead>
                                            <tr style='background: rgba(239, 68, 68, 0.04); border-bottom: 1px solid rgba(255,255,255,0.1);'>
                                                <th align='left' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase;'>Fecha</th>
                                                <th align='left' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase;'>Categoría</th>
                                                <th align='left' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase;'>Descripción</th>
                                                <th align='right' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase;'>Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {$tablaEgresosHtml}
                                        </tbody>
                                        <tfoot>
                                            <tr style='background: rgba(239, 68, 68, 0.08); border-top: 1.5px solid #ef4444;'>
                                                <td colspan='3' align='right' style='padding: 10px; font-size: 12px; font-weight: bold; color: #ffffff;'>Total Egresos:</td>
                                                <td align='right' style='padding: 10px; font-size: 13px; font-weight: bold; color: #ef4444;'>-{$totalEgresosFmt}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </td>
                            </tr>
                            
                            <!-- TABLA 3: DESGLOSE DE EGRESOS POR CATEGORÍA -->
                            <tr>
                                <td style='padding-top: 25px;'>
                                    <h3 style='color: #ffffff; font-size: 14px; font-weight: 700; margin: 0 0 10px 0; border-left: 3px solid #06b6d4; padding-left: 8px;'>3. Resumen de Gastos por Categoría</h3>
                                    <table width='100%' border='0' cellspacing='0' cellpadding='0' style='border-collapse: collapse; background: rgba(255,255,255,0.01); border: 1px solid rgba(255,255,255,0.05); border-radius: 6px; overflow: hidden;'>
                                        <thead>
                                            <tr style='background: rgba(6, 182, 212, 0.04); border-bottom: 1px solid rgba(255,255,255,0.1);'>
                                                <th align='left' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase;'>Concepto / Categoría</th>
                                                <th align='center' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase; width: 80px;'>Movimientos</th>
                                                <th align='right' style='padding: 8px 10px; font-size: 11px; color: #a1a1aa; text-transform: uppercase; width: 150px;'>Monto Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {$tablaCategoriasHtml}
                                        </tbody>
                                        <tfoot>
                                            <tr style='background: rgba(6, 182, 212, 0.08); border-top: 1.5px solid #06b6d4;'>
                                                <td align='right' colspan='2' style='padding: 10px; font-size: 12px; font-weight: bold; color: #ffffff;'>Total Consolidado Egresos:</td>
                                                <td align='right' style='padding: 10px; font-size: 13px; font-weight: bold; color: #ef4444;'>-{$totalEgresosFmt}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </td>
                            </tr>
                            
                            <!-- Mensaje de transparencia -->
                            <tr>
                                <td style='padding-top: 25px; text-align: center;'>
                                    <div style='font-size: 13px; color: #a1a1aa; line-height: 1.5;'>
                                        Este balance mensual ha sido validado bajo control de auditoría interna de la Junta de Vecinos y transmitido de manera transparente a la Municipalidad a través de ConectaBarrio.
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Firma de Soporte e Info de Implementación -->
                            <tr>
                                <td align='center' style='border-top: 1px solid rgba(255,255,255,0.08); margin-top: 30px; padding-top: 20px; color: #a1a1aa; font-size: 12px; line-height: 1.6; text-align: center;'>
                                    Si deseas saber cómo implementar <strong>ConectaBarrio</strong> puedes escribirnos al WhatsApp <a href='https://wa.me/56950001071' style='color: #06b6d4; text-decoration: none; font-weight: bold;'>+56950001071</a> o bien enviarnos un correo a <a href='mailto:contacto@conectatubarrio.cl' style='color: #06b6d4; text-decoration: none; font-weight: bold;'>contacto@conectatubarrio.cl</a>
                                    <div style='margin-top: 15px; font-size: 10px; color: #71717a;'>ConectaBarrio &copy; 2026. Todos los derechos reservados.</div>
                                </td>
                            </tr>
                            
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";

        return $html;
    }

    private function buildMembresiasMap($juntaId) {
        try {
            $socios = $this->userModel->getSociosByJunta($juntaId);
            foreach ($socios as $s) {
                $this->membresiaModel->upsert($s->id, $juntaId, 'socio', ['id_socio' => $s->id_socio ?? null]);
            }
            $equipo = $this->membresiaModel->getEquipoByJunta($juntaId);
            $map = [];
            foreach ($equipo as $m) {
                if ($m->rol === 'socio') {
                    $map[$m->id] = $m;
                }
            }
            return $map;
        } catch (Exception $e) {
            return [];
        }
    }

    public function socio_delegacion() {
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::isFullAdmin()) {
            $_SESSION['error_msg'] = 'Solo el administrador puede delegar cargos y permisos.';
            $this->redirect('/admin/socios');
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        try {
            $post = $this->sanitizePost();
            $usuarioId = (int)($post['usuario_id'] ?? 0);
            $juntaId = $_SESSION['user_junta_id'];
            $membresia = $this->membresiaModel->getByUsuarioJunta($usuarioId, $juntaId);
            if (!$membresia) {
                $user = $this->userModel->getUserById($usuarioId);
                if ($user && (int)$user->junta_id === (int)$juntaId && $user->rol === 'socio') {
                    $this->membresiaModel->upsert($usuarioId, $juntaId, 'socio', ['id_socio' => $user->id_socio]);
                    $membresia = $this->membresiaModel->getByUsuarioJunta($usuarioId, $juntaId);
                }
            }
            if (!$membresia || $membresia->rol !== 'socio') {
                $_SESSION['error_msg'] = 'Socio no válido para delegación.';
                $this->redirect('/admin/socios');
                return;
            }
            $cargo = $post['cargo'] ?? '';
            $validCargos = ['', 'SECRETARIO', 'TESORERO', 'DIRECTOR'];
            if (!in_array($cargo, $validCargos, true)) {
                $_SESSION['error_msg'] = 'Cargo inválido.';
                $this->redirect('/admin/socios');
                return;
            }
            $this->membresiaModel->updateDelegacion($membresia->id, [
                'cargo' => $cargo ?: null,
                'permiso_gestion_socios' => !empty($post['permiso_gestion_socios']),
                'permiso_registro_pagos' => !empty($post['permiso_registro_pagos']),
                'permiso_todos' => !empty($post['permiso_todos']),
                'permiso_mapa_socios' => false,
                'permiso_flujo_caja' => !empty($post['permiso_flujo_caja']),
                'permiso_documentos' => !empty($post['permiso_documentos']),
                'permiso_reuniones' => !empty($post['permiso_reuniones']),
                'permiso_votaciones' => !empty($post['permiso_votaciones']),
            ]);
            if ((int)$usuarioId === (int)$_SESSION['user_id']) {
                AuthContext::refreshMembershipSession();
            }
            $_SESSION['success_msg'] = 'Cargo y permisos del socio actualizados correctamente.';
        } catch (Exception $e) {
            $_SESSION['error_msg'] = 'No se pudo guardar la delegación. Verifique que ejecutó la migración sql/create_membresias_and_permisos.sql en la base de datos.';
        }
        $this->redirect('/admin/socios');
    }

    public function cambio_aprobar() {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        require_once APPROOT . '/models/SocioCambioSolicitud.php';
        $post = $this->sanitizePost();
        $cambioId = (int)($post['cambio_id'] ?? 0);
        $juntaId = (int)$_SESSION['user_junta_id'];
        $solicitud = $this->cambioModel->getPendingById($cambioId, $juntaId);
        if (!$solicitud) {
            $_SESSION['error_msg'] = 'Solicitud de cambio no encontrada.';
            $this->redirect('/admin/socios');
            return;
        }
        $datos = SocioCambioSolicitud::decodeDatos($solicitud);
        $userId = (int)$solicitud->usuario_id;
        $socio = $this->userModel->getPadronMiembroById($userId, $juntaId);
        if (!$socio) {
            $_SESSION['error_msg'] = 'Socio no válido para esta organización.';
            $this->redirect('/admin/socios');
            return;
        }
        $update = [
            'id' => $userId,
            'id_socio' => $socio->id_socio,
            'rut' => $socio->rut,
            'nombres' => $socio->nombre,
            'apellido_paterno' => $socio->apellido_paterno,
            'apellido_materno' => $socio->apellido_materno,
            'email' => $datos['email'] ?? $socio->email,
            'telefono' => $datos['telefono'] ?? $socio->telefono,
            'calle_id' => $datos['calle_id'] ?? $socio->calle_id,
            'numero_casa' => $datos['numero_casa'] ?? $socio->numero_casa,
            'fecha_inicio' => !empty($socio->fecha_inicio) ? substr($socio->fecha_inicio, 0, 10) : date('Y-m-d'),
            'genero' => $datos['genero'] ?? $socio->genero,
            'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? $socio->fecha_nacimiento,
            'estado_civil' => $datos['estado_civil'] ?? $socio->estado_civil,
            'nacionalidad' => $datos['nacionalidad'] ?? $socio->nacionalidad,
            'profesion' => $datos['profesion'] ?? $socio->profesion,
            'latitud' => $datos['latitud'] ?? null,
            'longitud' => $datos['longitud'] ?? null,
            'link_google' => $datos['link_google'] ?? null,
            'direccion_texto' => $datos['direccion_texto'] ?? null,
        ];
        if (!$this->userModel->updatePadronMiembro($update)) {
            $_SESSION['error_msg'] = 'No se pudieron aplicar los cambios al socio.';
            $this->redirect('/admin/socios');
            return;
        }
        $this->syncDomicilioMembresia($userId, $juntaId, $update);
        $this->cambioModel->approve($cambioId, $juntaId, (int)$_SESSION['user_id']);
        $_SESSION['success_msg'] = 'Cambios de datos aprobados para "' . $socio->nombre . '".';
        $this->redirect('/admin/socios');
    }

    public function cambio_rechazar() {
        $this->requireManageSocios();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        $post = $this->sanitizePost();
        $cambioId = (int)($post['cambio_id'] ?? 0);
        $juntaId = (int)$_SESSION['user_junta_id'];
        $motivo = trim($post['motivo_rechazo'] ?? '');

        if ($cambioId <= 0) {
            $_SESSION['error_msg'] = 'Solicitud de cambio inválida.';
            $this->redirect('/admin/socios');
            return;
        }

        if (!$this->cambioModel->getPendingById($cambioId, $juntaId)) {
            $_SESSION['error_msg'] = 'Solicitud no encontrada o ya fue procesada.';
            $this->redirect('/admin/socios');
            return;
        }

        if ($this->cambioModel->reject($cambioId, $juntaId, (int)$_SESSION['user_id'], $motivo)) {
            $_SESSION['success_msg'] = 'Solicitud de cambio rechazada.';
        } else {
            $detail = trim($this->cambioModel->getLastError());
            $_SESSION['error_msg'] = $detail !== ''
                ? $detail
                : 'No se pudo rechazar la solicitud.';
        }
        $this->redirect('/admin/socios');
    }

    public function mapa_socios() {
        $this->requireViewMapaSocios();
        require_once APPROOT . '/core/AuthContext.php';
        $juntaId = (int)$_SESSION['user_junta_id'];
        $junta = $this->juntaModel->getJuntaById($juntaId);
        $padron = $this->membresiaModel->overlayDomicilioOnUsers(
            $this->userModel->getPadronByJunta($juntaId),
            $juntaId
        );
        $mapaData = $this->membresiaModel->buildMapaSociosDataset($juntaId, $padron);

        $data = [
            'title' => 'Mapa comunitario',
            'header_title' => 'Mapa comunitario',
            'header_subtitle' => 'Distribución geográfica de todos los miembros de la organización',
            'active_menu' => 'mapa_socios',
            'junta' => $junta,
            'mapa' => $mapaData,
            'mapa_habilitado' => AuthContext::isMapaSociosEnabled(),
            'is_full_admin' => AuthContext::isFullAdmin(),
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ];
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);
        $this->view('admin/mapa_socios', $data);
    }

    public function mapa_socios_config() {
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::isFullAdmin()) {
            $_SESSION['error_msg'] = 'Solo el administrador puede habilitar el mapa de socios.';
            $this->redirect('/admin/socios');
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $habilitar = !empty($this->sanitizePost()['mapa_socios_habilitado']);
        if (!$this->juntaModel->hasMapaSociosColumn()) {
            $_SESSION['error_msg'] = 'Ejecute la migración sql/add_mapa_socios_permiso.sql en la base de datos.';
            $this->redirect('/admin/socios');
            return;
        }
        if ($this->juntaModel->updateMapaSociosHabilitado($juntaId, $habilitar)) {
            $_SESSION['mapa_socios_habilitado'] = $habilitar ? 1 : 0;
            $_SESSION['success_msg'] = $habilitar
                ? 'Mapa de socios habilitado para su organización.'
                : 'Mapa de socios deshabilitado.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo actualizar la configuración del mapa.';
        }
        $redirect = !empty($this->sanitizePost()['redirect_mapa']) ? '/admin/mapa_socios' : '/admin/socios';
        $this->redirect($redirect);
    }

    public function flujo_caja_config() {
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::isFullAdmin()) {
            $_SESSION['error_msg'] = 'Solo el administrador puede configurar el flujo de caja.';
            $this->redirect('/admin/socios');
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $habilitar = !empty($this->sanitizePost()['flujo_caja_habilitado']);
        if (!$this->juntaModel->hasFlujoCajaColumn()) {
            $_SESSION['error_msg'] = 'Ejecute la migración sql/add_flujo_caja_permiso.sql en la base de datos.';
            $this->redirect('/admin/socios');
            return;
        }
        if ($this->juntaModel->updateFlujoCajaHabilitado($juntaId, $habilitar)) {
            $_SESSION['flujo_caja_habilitado'] = $habilitar ? 1 : 0;
            $_SESSION['success_msg'] = $habilitar
                ? 'Flujo de caja habilitado. Delegue el acceso de visualización a tesorero, director o secretario.'
                : 'Flujo de caja deshabilitado para la organización.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo actualizar la configuración del flujo de caja.';
        }
        $redirect = !empty($this->sanitizePost()['redirect_flujo']) ? '/admin/flujo_caja' : '/admin/socios';
        $this->redirect($redirect);
    }

    public function documentos() {
        $this->requireViewDocumentos();
        require_once APPROOT . '/core/AuthContext.php';
        $juntaId = (int)$_SESSION['user_junta_id'];
        $this->documentoCategoriaModel->ensureDefaults($juntaId);

        $catFilter = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
        $categorias = $this->documentoCategoriaModel->getByJunta($juntaId, true);
        $documentosRaw = $this->documentoModel->getByJunta($juntaId, $catFilter > 0 ? $catFilter : null);
        $documentos = [];
        foreach ($documentosRaw as $doc) {
            if (AuthContext::canViewDocumentoVisibilidad($doc->categoria_visibilidad ?? 'publico')) {
                $documentos[] = $doc;
            }
        }

        $categoriasSubida = AuthContext::canUploadDocumentos() ? $categorias : [];

        $data = [
            'title' => 'Documentos',
            'header_title' => 'Documentos de la organización',
            'header_subtitle' => 'Archivos compartidos con socios o reservados a la directiva',
            'active_menu' => 'documentos',
            'categorias' => $categorias,
            'categorias_gestion' => $categorias,
            'categorias_subida' => $categoriasSubida,
            'documentos' => $documentos,
            'categoria_filtro' => $catFilter,
            'puede_subir' => AuthContext::canUploadDocumentos(),
            'documentos_habilitado' => AuthContext::isDocumentosEnabled(),
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ];
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);
        $this->view('admin/documentos', $data);
    }

    public function documento_subir() {
        $this->requireUploadDocumentos();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/documentos');
            return;
        }
        require_once APPROOT . '/core/DocumentStorage.php';
        $juntaId = (int)$_SESSION['user_junta_id'];
        $post = $this->sanitizePost();
        $titulo = trim($post['titulo'] ?? '');
        $categoriaId = (int)($post['categoria_id'] ?? 0);
        if ($titulo === '' || mb_strlen($titulo) > 200) {
            $_SESSION['error_msg'] = 'Ingrese un título válido (máx. 200 caracteres).';
            $this->redirect('/admin/documentos');
            return;
        }
        $categoria = $this->documentoCategoriaModel->getById($categoriaId, $juntaId);
        if (!$categoria || empty($categoria->activo)) {
            $_SESSION['error_msg'] = 'Seleccione una categoría válida.';
            $this->redirect('/admin/documentos');
            return;
        }
        if (empty($_FILES['archivo'])) {
            $_SESSION['error_msg'] = 'Debe seleccionar un archivo (PDF o imagen, máx. 10 MB).';
            $this->redirect('/admin/documentos');
            return;
        }
        $stored = DocumentStorage::storeUploadedFile($juntaId, $_FILES['archivo']);
        if (!$stored) {
            $_SESSION['error_msg'] = 'No se pudo guardar el archivo. Use PDF, JPG, PNG, WEBP o GIF (máx. 10 MB).';
            $this->redirect('/admin/documentos');
            return;
        }
        $id = $this->documentoModel->create([
            'junta_id' => $juntaId,
            'categoria_id' => $categoriaId,
            'titulo' => $titulo,
            'archivo_nombre_original' => $stored['archivo_nombre_original'],
            'archivo_path' => $stored['path'],
            'mime_type' => $stored['mime_type'],
            'tamano_bytes' => $stored['tamano_bytes'],
            'subido_por' => (int)$_SESSION['user_id'],
        ]);
        if (!$id) {
            DocumentStorage::deleteRelativePath($stored['path']);
            $_SESSION['error_msg'] = 'Error al registrar el documento en la base de datos.';
            $this->redirect('/admin/documentos');
            return;
        }
        $_SESSION['success_msg'] = 'Documento subido correctamente.';
        $this->redirect('/admin/documentos?categoria=' . $categoriaId);
    }

    public function documento_ver($id) {
        $this->requireViewDocumentos();
        require_once APPROOT . '/core/DocumentStorage.php';
        $juntaId = (int)$_SESSION['user_junta_id'];
        $doc = $this->getDocumentoOrFail((int)$id, $juntaId);
        if (!$doc) {
            $_SESSION['error_msg'] = 'Documento no encontrado o sin permiso para verlo.';
            $this->redirect('/admin/documentos');
            return;
        }
        $data = [
            'title' => $doc->titulo,
            'documento' => $doc,
            'previewable' => DocumentStorage::isPreviewable($doc->mime_type),
            'archivo_url' => URLROOT . '/admin/documento_archivo/' . (int)$doc->id,
            'back_url' => URLROOT . '/admin/documentos',
        ];
        $this->view('admin/documento_ver', $data);
    }

    public function documento_archivo($id) {
        $this->requireViewDocumentos();
        $juntaId = (int)$_SESSION['user_junta_id'];
        $doc = $this->getDocumentoOrFail((int)$id, $juntaId);
        if (!$doc) {
            http_response_code(403);
            die('Acceso denegado.');
        }
        $this->streamDocumentoFile($doc, false);
    }

    public function documento_descargar($id) {
        $this->requireViewDocumentos();
        $juntaId = (int)$_SESSION['user_junta_id'];
        $doc = $this->getDocumentoOrFail((int)$id, $juntaId);
        if (!$doc) {
            http_response_code(403);
            die('Acceso denegado.');
        }
        $this->streamDocumentoFile($doc, true);
    }

    public function documento_eliminar() {
        $this->requireUploadDocumentos();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/documentos');
            return;
        }
        require_once APPROOT . '/core/DocumentStorage.php';
        $juntaId = (int)$_SESSION['user_junta_id'];
        $id = (int)($this->sanitizePost()['documento_id'] ?? 0);
        $path = $this->documentoModel->delete($id, $juntaId);
        if ($path === null) {
            $_SESSION['error_msg'] = 'No se pudo eliminar el documento.';
        } else {
            DocumentStorage::deleteRelativePath($path);
            $_SESSION['success_msg'] = 'Documento eliminado.';
        }
        $this->redirect('/admin/documentos');
    }

    public function documento_categoria_crear() {
        $this->requireUploadDocumentos();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/documentos');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $post = $this->sanitizePost();
        $nombre = trim($post['nombre'] ?? '');
        $vis = ($post['visibilidad'] ?? '') === 'directorio' ? 'directorio' : 'publico';
        if ($this->documentoCategoriaModel->create($juntaId, $nombre, $vis)) {
            $_SESSION['success_msg'] = 'Categoría creada.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo crear la categoría (nombre duplicado o inválido).';
        }
        $this->redirect('/admin/documentos#categorias');
    }

    public function documento_categoria_actualizar() {
        $this->requireUploadDocumentos();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/documentos');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $post = $this->sanitizePost();
        $id = (int)($post['categoria_id'] ?? 0);
        $nombre = trim($post['nombre'] ?? '');
        $vis = ($post['visibilidad'] ?? '') === 'directorio' ? 'directorio' : 'publico';
        $activo = !empty($post['activo']);
        if ($this->documentoCategoriaModel->update($id, $juntaId, $nombre, $vis, $activo)) {
            $_SESSION['success_msg'] = 'Categoría actualizada.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo actualizar la categoría.';
        }
        $this->redirect('/admin/documentos#categorias');
    }

    public function documento_categoria_eliminar() {
        $this->requireUploadDocumentos();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/documentos');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $id = (int)($this->sanitizePost()['categoria_id'] ?? 0);
        $result = $this->documentoCategoriaModel->deleteOrDeactivate($id, $juntaId);
        if ($result === 'deleted') {
            $_SESSION['success_msg'] = 'Categoría eliminada.';
        } elseif ($result === 'deactivated') {
            $_SESSION['success_msg'] = 'La categoría tiene documentos; se desactivó en lugar de eliminar.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo eliminar la categoría.';
        }
        $this->redirect('/admin/documentos#categorias');
    }

    public function documentos_config() {
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::isFullAdmin()) {
            $_SESSION['error_msg'] = 'Solo el administrador puede configurar el módulo de documentos.';
            $this->redirect('/admin/socios');
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/socios');
            return;
        }
        $juntaId = (int)$_SESSION['user_junta_id'];
        $habilitar = !empty($this->sanitizePost()['documentos_habilitado']);
        if (!$this->juntaModel->hasDocumentosColumn()) {
            $_SESSION['error_msg'] = 'Ejecute la migración sql/add_documentos_module.sql en la base de datos.';
            $this->redirect('/admin/socios');
            return;
        }
        if ($this->juntaModel->updateDocumentosHabilitado($juntaId, $habilitar)) {
            $_SESSION['documentos_habilitado'] = $habilitar ? 1 : 0;
            $_SESSION['success_msg'] = $habilitar
                ? 'Módulo de documentos habilitado. Delegue quién puede subir archivos.'
                : 'Módulo de documentos deshabilitado para la organización.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo actualizar la configuración de documentos.';
        }
        $redirect = !empty($this->sanitizePost()['redirect_documentos']) ? '/admin/documentos' : '/admin/socios';
        $this->redirect($redirect);
    }

    public function calendario() {
        if (!$this->requireReunionesPlan()) {
            $this->view('admin/upgrade_required', [
                'title' => 'Mejora Requerida',
                'header_title' => 'Calendario de Actividades',
                'header_subtitle' => 'Se requiere Plan Mediano o superior',
                'active_menu' => 'calendario',
                'required_plan' => 'Mediano',
            ]);
            return;
        }
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::isDirectivo() && !AuthContext::canManageReuniones()) {
            $_SESSION['error_msg'] = 'No tiene permisos para ver el calendario de actividades.';
            $this->redirectUserHome();
            return;
        }

        $juntaId = (int)$_SESSION['user_junta_id'];
        $calMes = max(1, min(12, (int)($_GET['mes'] ?? date('n'))));
        $calAnio = (int)($_GET['anio'] ?? date('Y'));
        $usuarioId = null;
        if (($_SESSION['user_rol'] ?? '') === 'socio' && !AuthContext::canManageReuniones()) {
            $usuarioId = (int)$_SESSION['user_id'];
        }

        $eventosPorDia = $this->reunionModel->getEventosCalendarioMes($juntaId, $calMes, $calAnio, $usuarioId);
        $reuniones = $usuarioId !== null
            ? $this->reunionConvocadoModel->getReunionesForUsuario($juntaId, $usuarioId)
            : $this->reunionModel->getReunionesByJunta($juntaId);

        $this->view('admin/calendario', [
            'title' => 'Calendario de Actividades',
            'header_title' => 'Calendario comunitario',
            'header_subtitle' => 'Convocatorias, asambleas y reuniones de su organización',
            'active_menu' => 'calendario',
            'cal_mes' => $calMes,
            'cal_anio' => $calAnio,
            'eventos_por_dia' => $eventosPorDia,
            'reuniones' => $reuniones,
            'es_vista_directorio' => $usuarioId === null,
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ]);
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);
    }

    public function documentacion_legal() {
        require_once APPROOT . '/core/AuthContext.php';
        if (!AuthContext::isDirectivo()) {
            $_SESSION['error_msg'] = 'Solo la directiva puede acceder a la documentación legal de la organización.';
            $this->redirectUserHome();
            return;
        }

        $juntaId = (int)$_SESSION['user_junta_id'];
        $junta = $this->juntaModel->getJuntaById($juntaId);

        $this->view('admin/documentacion_legal', [
            'title' => 'Documentación Legal',
            'header_title' => 'Personalidad jurídica y documentos',
            'header_subtitle' => 'Visible solo para la directiva. Solo quien sube un archivo puede eliminarlo.',
            'active_menu' => 'documentacion_legal',
            'junta' => $junta,
            'documentos' => $this->juntaDocumentoLegalModel->getByJunta($juntaId),
            'migration_pending' => !$this->juntaDocumentoLegalModel->hasTable(),
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ]);
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);
    }

    public function junta_documento_legal_subir() {
        require_once APPROOT . '/core/AuthContext.php';
        require_once APPROOT . '/core/DocumentStorage.php';
        if (!AuthContext::isDirectivo()) {
            $this->redirect('/admin/documentacion_legal');
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/documentacion_legal');
            return;
        }

        $juntaId = (int)$_SESSION['user_junta_id'];
        $post = $this->sanitizePost();
        $titulo = trim($post['titulo'] ?? 'Documento legal');

        if (!$this->juntaDocumentoLegalModel->hasTable()) {
            $_SESSION['error_msg'] = 'Ejecute la migración sql/add_junta_personalidad_juridica_docs.sql';
            $this->redirect('/admin/documentacion_legal');
            return;
        }

        $stored = DocumentStorage::storeLegalFile($juntaId, $_FILES['archivo'] ?? []);
        if (!$stored) {
            $_SESSION['error_msg'] = 'No se pudo adjuntar el archivo. Use PDF o imagen (máx. 10 MB).';
            $this->redirect('/admin/documentacion_legal');
            return;
        }

        if ($this->juntaDocumentoLegalModel->create($juntaId, (int)$_SESSION['user_id'], $stored, $titulo)) {
            $_SESSION['success_msg'] = 'Documento legal adjuntado correctamente.';
        } else {
            DocumentStorage::deleteRelativePath($stored['path']);
            $_SESSION['error_msg'] = 'Error al registrar el documento en la base de datos.';
        }
        $this->redirect('/admin/documentacion_legal');
    }

    public function junta_documento_legal_eliminar() {
        require_once APPROOT . '/core/AuthContext.php';
        require_once APPROOT . '/core/DocumentStorage.php';
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/admin/documentacion_legal');
            return;
        }

        $juntaId = (int)$_SESSION['user_junta_id'];
        $post = $this->sanitizePost();
        $id = (int)($post['documento_id'] ?? 0);
        $doc = $this->juntaDocumentoLegalModel->getById($id, $juntaId);

        if (!$doc) {
            $_SESSION['error_msg'] = 'Documento no encontrado.';
            $this->redirect('/admin/documentacion_legal');
            return;
        }

        $puedeEliminar = ((int)$doc->subido_por === (int)$_SESSION['user_id'])
            || (($_SESSION['user_rol'] ?? '') === 'maestro');
        if (!$puedeEliminar) {
            $_SESSION['error_msg'] = 'Solo quien subió el documento puede eliminarlo.';
            $this->redirect('/admin/documentacion_legal');
            return;
        }

        $path = $this->juntaDocumentoLegalModel->delete($id, $juntaId);
        if ($path) {
            DocumentStorage::deleteRelativePath($path);
            $_SESSION['success_msg'] = 'Documento eliminado.';
        } else {
            $_SESSION['error_msg'] = 'No se pudo eliminar el documento.';
        }
        $this->redirect('/admin/documentacion_legal');
    }

    public function junta_documento_legal_descargar($id = 0) {
        require_once APPROOT . '/core/AuthContext.php';
        require_once APPROOT . '/core/DocumentStorage.php';
        if (!AuthContext::isDirectivo() && ($_SESSION['user_rol'] ?? '') !== 'maestro') {
            http_response_code(403);
            exit;
        }

        $juntaId = (int)$_SESSION['user_junta_id'];
        $id = (int)$id;
        $doc = $this->juntaDocumentoLegalModel->getById($id, $juntaId);
        if (!$doc || !DocumentStorage::pathBelongsToJunta($doc->archivo_path, $juntaId)) {
            http_response_code(404);
            exit;
        }

        $abs = DocumentStorage::absolutePath($doc->archivo_path);
        if (!is_file($abs)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . ($doc->mime_type ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . rawurlencode($doc->archivo_nombre_original) . '"');
        header('Content-Length: ' . filesize($abs));
        readfile($abs);
        exit;
    }
}
