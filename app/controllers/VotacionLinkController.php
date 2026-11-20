<?php
class VotacionLinkController extends Controller {
    public function ingresar($token = '') {
        require_once APPROOT . '/models/Votacion.php';
        $model = new Votacion();
        if (!$model->tablesExist()) {
            die('Módulo de votaciones no disponible.');
        }
        $model->ensureAbierta();
        $model->syncEstadosActivas();
        $v = $model->getByToken((string)$token);
        if ($v && ($v->participacion ?? 'socios') === 'abierta') {
            $this->encuestaAbierta($model, $v, (string)$token);
            return;
        }
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
        if (!empty($_GET['ingresar']) && empty($_SESSION['user_id'])) {
            $this->mostrarLogin($v, $model);
            return;
        }
        if (isset($_SESSION['user_id'])) {
            $this->redirectToVote($v);
            return;
        }
        $this->mostrarLogin($v, $model);
    }

    private function mostrarLogin(object $v, Votacion $model): void {
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

    private function encuestaAbierta(Votacion $model, object $v, string $token): void {
        $_SESSION['pending_votacion_token'] = $token;
        if (!empty($_GET['ingresar']) && empty($_SESSION['user_id'])) {
            $this->mostrarLogin($v, $model);
            return;
        }

        $claveOk = 'encuesta_ok_' . $token;
        $claveError = 'encuesta_error_' . $token;
        $claveOld = 'encuesta_old_' . $token;
        $claveModo = 'encuesta_modo_' . $token;
        $ok = $_SESSION[$claveOk] ?? null;
        $error = (string)($_SESSION[$claveError] ?? '');
        $old = $_SESSION[$claveOld] ?? [];
        unset($_SESSION[$claveOk], $_SESSION[$claveError], $_SESSION[$claveOld]);

        $como = (string)($_GET['como'] ?? '');
        if ($como === 'reiniciar') {
            unset($_SESSION[$claveModo]);
            header('Location: ' . URLROOT . '/votacion/l/' . rawurlencode($token));
            exit;
        }
        if ($como === 'invitado') {
            $_SESSION[$claveModo] = 'invitado';
            header('Location: ' . URLROOT . '/votacion/l/' . rawurlencode($token));
            exit;
        }
        if ($como === 'socio') {
            $_SESSION[$claveModo] = 'socio';
            if (empty($_SESSION['user_id'])) {
                $this->mostrarLogin($v, $model);
                return;
            }
            header('Location: ' . URLROOT . '/votacion/l/' . rawurlencode($token));
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $model->isActiva($v)) {
            $resultado = $this->guardarEncuestaAbierta($model, $v, $_POST);
            if ($resultado['error'] !== null) {
                $_SESSION[$claveError] = $resultado['error'];
                $_SESSION[$claveOld] = $resultado['old'];
            } else {
                unset($_SESSION['pending_votacion_token'], $_SESSION[$claveModo]);
                $_SESSION[$claveOk] = $resultado['ok'];
            }
            header('Location: ' . URLROOT . '/votacion/l/' . rawurlencode($token));
            exit;
        }

        $modo = (string)($_SESSION[$claveModo] ?? '');
        $socio = $modo === 'socio' ? $this->socioDeLaJunta($model, $v) : null;
        if ($modo === 'invitado' || ($modo === 'socio' && $socio)) {
            $paso = 'formulario';
        } elseif ($modo === 'socio' || (string)($_GET['paso'] ?? '') === 'socio') {
            $paso = 'socio';
        } else {
            $paso = 'pregunta';
        }
        $opciones = !empty($v->inscribe_ninos) ? [] : $model->getOpcionesConSeguimiento((int)$v->id);
        $enlace = URLROOT . '/votacion/l/' . rawurlencode($token);
        $this->view('publico/encuesta_abierta', [
            'title' => (string)$v->titulo,
            'public_layout' => true,
            'skip_chartjs' => true,
            'votacion' => $v,
            'activa' => $model->isActiva($v),
            'socio' => $socio,
            'paso' => $paso,
            'opciones' => $opciones,
            'ok' => is_array($ok) ? $ok : null,
            'error' => $error,
            'old' => is_array($old) ? $old : [],
            'login_url' => $enlace . '?como=socio',
            'invitado_url' => $enlace . '?como=invitado',
            'socio_url' => $enlace . '?paso=socio',
            'reiniciar_url' => $enlace . '?como=reiniciar',
            'sesion_otra' => $modo === 'socio' && !$socio && !empty($_SESSION['user_id']),
        ]);
    }

    /** @return array{error:?string, old:array, ok?:array} */
    private function guardarEncuestaAbierta(Votacion $model, object $v, array $post): array {
        require_once APPROOT . '/core/SocioInput.php';
        require_once APPROOT . '/core/InviteRutCheck.php';
        require_once APPROOT . '/core/Mailer.php';

        $juntaId = (int)$v->junta_id;
        $modo = (string)($_SESSION['encuesta_modo_' . (string)$v->token_publico] ?? '');
        $socio = $modo === 'socio' ? $this->socioDeLaJunta($model, $v) : null;
        $old = $post;
        $old['edad'] = $post['edad'] ?? [];
        $old['genero'] = $post['genero'] ?? [];

        if ($socio) {
            $nombres = trim((string)($post['nombres'] ?? ''));
            $apPat = trim((string)($post['apellido_paterno'] ?? ''));
            $apMat = trim((string)($post['apellido_materno'] ?? ''));
            if ($nombres === '' || $apPat === '') {
                return ['error' => 'Confirme su nombre y apellido.', 'old' => $old];
            }
            $nombre = Votacion::nombreYApellidos((object)[
                'nombre' => $nombres,
                'apellido_paterno' => $apPat,
                'apellido_materno' => $apMat,
            ]);
        } else {
            $nombre = trim((string)($post['nombre_apellidos'] ?? ''));
            $partes = preg_split('/\s+/', $nombre) ?: [];
            if (count($partes) < 2) {
                return ['error' => 'Escriba su nombre y apellidos.', 'old' => $old];
            }
            $nombres = '';
            $apPat = '';
            $apMat = '';
        }

        $email = strtolower(trim((string)($post['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !InviteRutCheck::hasRealEmail($email)) {
            return ['error' => 'Indique un correo electrónico válido.', 'old' => $old];
        }
        $telefono = SocioInput::normalizeTelefono($post['telefono'] ?? '');
        if (!SocioInput::isValidTelefono($telefono) || $telefono === '') {
            return ['error' => 'Indique un teléfono chileno de 9 dígitos.', 'old' => $old];
        }

        if ($model->emailYaParticipo((int)$v->id, $email) || ($socio && $model->usuarioYaParticipo((int)$v->id, (int)$socio->id))) {
            return ['error' => 'Este correo ya quedó registrado en la encuesta.', 'old' => $old];
        }

        $usuarioId = $socio ? (int)$socio->id : 0;
        $esSocio = (bool)$socio;
        if ($esSocio && $socio) {
            $errorContacto = $model->actualizarContactoSocio($usuarioId, $juntaId, $nombres, $apPat, $apMat, $email, $telefono);
            if ($errorContacto !== null) {
                return ['error' => $errorContacto, 'old' => $old];
            }
        }

        $ninos = [];
        $opcionId = 0;
        $respuesta = '';
        $segTexto = null;
        $segOpcionId = 0;
        if (!empty($v->inscribe_ninos)) {
            $cantidad = (int)($post['cantidad_ninos'] ?? 0);
            if ($cantidad < 1 || $cantidad > 12) {
                return ['error' => 'Indique cuántos niños o niñas va a registrar, entre 1 y 12.', 'old' => $old];
            }
            $edades = is_array($post['edad'] ?? null) ? array_values($post['edad']) : [];
            $generos = is_array($post['genero'] ?? null) ? array_values($post['genero']) : [];
            if (count($edades) < $cantidad || count($generos) < $cantidad) {
                return ['error' => 'Complete la edad y el género de cada niño o niña.', 'old' => $old];
            }
            for ($i = 0; $i < $cantidad; $i++) {
                $edad = (int)$edades[$i];
                $genero = (string)$generos[$i];
                if ($edad < 0 || $edad > 17 || !in_array($genero, ['femenino', 'masculino', 'no_responder'], true)) {
                    return ['error' => 'La edad de cada niño o niña debe estar entre 0 y 17 años, y el género debe ser Femenino, Masculino o No responder.', 'old' => $old];
                }
                $ninos[] = ['edad' => $edad, 'genero' => $genero];
            }
        } else {
            $opciones = $model->getOpcionesConSeguimiento((int)$v->id);
            if ($opciones) {
                $opcionId = (int)($post['opcion_id'] ?? 0);
                $chosen = null;
                foreach ($opciones as $op) {
                    if ((int)$op->id === $opcionId) {
                        $chosen = $op;
                        break;
                    }
                }
                if (!$chosen) {
                    return ['error' => 'Elija una opción de la encuesta.', 'old' => $old];
                }
                $tipoSeg = (string)($chosen->seguimiento_tipo ?? 'ninguno');
                if ($tipoSeg === 'texto') {
                    $fuente = $post['seguimiento_texto'] ?? '';
                    $segTexto = trim((string)(is_array($fuente) ? ($fuente[$opcionId] ?? '') : $fuente));
                    if ($segTexto === '') {
                        return ['error' => 'Responda la pregunta adicional.', 'old' => $old];
                    }
                } elseif ($tipoSeg === 'opciones') {
                    $fuente = $post['seguimiento_opcion_id'] ?? 0;
                    $segOpcionId = (int)(is_array($fuente) ? ($fuente[$opcionId] ?? 0) : $fuente);
                    $valid = false;
                    foreach ($chosen->seguimiento_opciones ?? [] as $s) {
                        if ((int)$s->id === $segOpcionId) {
                            $valid = true;
                            break;
                        }
                    }
                    if (!$valid) {
                        return ['error' => 'Seleccione una alternativa de la pregunta adicional.', 'old' => $old];
                    }
                }
            }
            $respuesta = trim((string)($post['respuesta_texto'] ?? ''));
        }

        $partId = $model->guardarParticipacion((int)$v->id, [
            'usuario_id' => $usuarioId,
            'es_socio' => $esSocio,
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'opcion_id' => $opcionId,
            'respuesta_texto' => $respuesta,
            'seguimiento_texto' => $segTexto,
            'seguimiento_opcion_id' => $segOpcionId,
        ], $ninos);
        if ($partId <= 0) {
            return ['error' => 'No se pudo guardar la inscripción. Si el correo ya participó, use otro o revise los datos.', 'old' => $old];
        }

        $mail = $this->enviarConfirmacion($v, $nombre, $email, $telefono, $ninos, $partId);
        return [
            'error' => null,
            'old' => [],
            'ok' => [
                'nombre' => $nombre,
                'email' => $email,
                'ninos' => count($ninos),
                'mail_ok' => !empty($mail['ok']),
            ],
        ];
    }

    /** @param array<int, array{edad:int, genero:string}> $ninos */
    private function enviarConfirmacion(object $v, string $nombre, string $email, string $telefono, array $ninos, int $partId): array {
        if (!Mailer::isConfigured()) {
            return ['ok' => false];
        }
        require_once APPROOT . '/core/SocioInput.php';
        $org = htmlspecialchars((string)($v->junta_nombre ?? 'Su junta de vecinos'));
        $titulo = htmlspecialchars((string)$v->titulo);
        $nombreHtml = htmlspecialchars($nombre);
        $tel = htmlspecialchars(SocioInput::formatTelefonoDisplay($telefono) ?: $telefono);
        $detalle = '';
        if ($ninos) {
            $detalle = '<p>Niños y niñas registrados: <strong>' . count($ninos) . '</strong></p><ul>';
            foreach ($ninos as $i => $nino) {
                $detalle .= '<li>Niño ' . ($i + 1) . ': ' . (int)$nino['edad'] . ' años, '
                    . htmlspecialchars(Votacion::etiquetaGenero($nino['genero'])) . '</li>';
            }
            $detalle .= '</ul>';
        }
        $html = '<div style="font-family:Arial,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px;">'
            . '<div style="max-width:560px;margin:0 auto;background:#1e293b;border-radius:12px;overflow:hidden;border:1px solid #334155;">'
            . '<div style="background:linear-gradient(135deg,#22c55e,#0891b2);padding:24px;text-align:center;">'
            . '<h2 style="margin:0;color:#fff;">Inscripción registrada</h2>'
            . '<p style="margin:8px 0 0;color:rgba(255,255,255,0.9);">' . $org . '</p></div>'
            . '<div style="padding:24px;">'
            . '<p>Hola <strong>' . $nombreHtml . '</strong>,</p>'
            . '<p>Su respuesta a <strong>' . $titulo . '</strong> quedó registrada correctamente.</p>'
            . '<p>Número de inscripción: <strong>' . $partId . '</strong><br>Correo: ' . htmlspecialchars($email) . '<br>Teléfono: ' . $tel . '</p>'
            . $detalle
            . '<p style="color:#94a3b8;font-size:13px;">Guarde este correo como comprobante.</p>'
            . '</div></div></div>';
        $bcc = trim((string)($v->bcc_correo ?? ''));
        return Mailer::send(
            $email,
            'Inscripción registrada - ' . (string)($v->junta_nombre ?? 'ConectaBarrio'),
            $html,
            defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : null,
            $bcc !== '' ? $bcc : null
        );
    }

    private function socioDeLaJunta(Votacion $model, object $v) {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0 || (int)($_SESSION['user_junta_id'] ?? 0) !== (int)$v->junta_id) {
            return null;
        }
        $rol = (string)($_SESSION['user_rol'] ?? '');
        if (!in_array($rol, ['socio', 'admin'], true)) {
            return null;
        }
        return $model->miembroPorId((int)$v->junta_id, $uid);
    }

    private function avisoIngreso(object $v, bool $abierta): string {
        $nombre = ($v->tipo ?? '') === 'encuesta' ? 'encuesta' : 'votación';
        if (($v->participacion ?? '') === 'abierta') {
            return 'Ingrese con su RUT o correo. Al entrar, la encuesta se completa con el nombre, correo y teléfono que ya tiene la organización.';
        }
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
