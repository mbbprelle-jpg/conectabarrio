<?php
class SocioApprovalMail {

    public static function send($user, $juntaNombre, $tempPassword) {
        if (!Mailer::isConfigured()) {
            return ['ok' => false, 'error' => 'SMTP no configurado'];
        }
        if (empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Correo inválido'];
        }

        $nombre = htmlspecialchars($user->nombre ?? '');
        $apPat = htmlspecialchars($user->apellido_paterno ?? '');
        $apMat = htmlspecialchars($user->apellido_materno ?? '');
        $rut = htmlspecialchars($user->rut ?? '');
        $telefono = htmlspecialchars($user->telefono ?? '—');
        $idSocio = !empty($user->id_socio) ? (int)$user->id_socio : '—';
        require_once APPROOT . '/core/SocioInput.php';
        $genero = SocioInput::generoLabel($user->genero ?? '');
        $fechaNac = !empty($user->fecha_nacimiento) ? date('d-m-Y', strtotime($user->fecha_nacimiento)) : '—';
        $generoHtml = htmlspecialchars($genero ?: '—');
        $fechaNacHtml = htmlspecialchars($fechaNac);
        $loginUrl = URLROOT . '/auth/login';
        $org = htmlspecialchars($juntaNombre);

        $subject = 'Registro aprobado - ' . $juntaNombre;
        $html = '<div style="font-family:Arial,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px;">'
            . '<div style="max-width:560px;margin:0 auto;background:#1e293b;border-radius:12px;overflow:hidden;border:1px solid #334155;">'
            . '<div style="background:linear-gradient(135deg,#22c55e,#0891b2);padding:24px;text-align:center;">'
            . '<h2 style="margin:0;color:#fff;">¡Bienvenido/a a ConectaBarrio!</h2>'
            . '<p style="margin:8px 0 0;color:rgba(255,255,255,0.9);">' . $org . '</p></div>'
            . '<div style="padding:24px;">'
            . '<p>Hola <strong>' . $nombre . '</strong>,</p>'
            . '<p>Su solicitud de inscripción como socio fue <strong>aprobada</strong>. Estos son sus datos registrados:</p>'
            . '<table style="width:100%;font-size:14px;margin:16px 0;border-collapse:collapse;">'
            . '<tr><td style="padding:6px 0;color:#94a3b8;">N° Socio</td><td style="padding:6px 0;text-align:right;"><strong>#' . $idSocio . '</strong></td></tr>'
            . '<tr><td style="padding:6px 0;color:#94a3b8;">Nombre</td><td style="padding:6px 0;text-align:right;">' . $nombre . ' ' . $apPat . ' ' . $apMat . '</td></tr>'
            . '<tr><td style="padding:6px 0;color:#94a3b8;">RUT</td><td style="padding:6px 0;text-align:right;font-family:monospace;">' . $rut . '</td></tr>'
            . '<tr><td style="padding:6px 0;color:#94a3b8;">Género</td><td style="padding:6px 0;text-align:right;">' . $generoHtml . '</td></tr>'
            . '<tr><td style="padding:6px 0;color:#94a3b8;">Fecha nacimiento</td><td style="padding:6px 0;text-align:right;">' . $fechaNacHtml . '</td></tr>'
            . '<tr><td style="padding:6px 0;color:#94a3b8;">Teléfono</td><td style="padding:6px 0;text-align:right;">' . $telefono . '</td></tr>'
            . '<tr><td style="padding:6px 0;color:#94a3b8;">Correo</td><td style="padding:6px 0;text-align:right;">' . htmlspecialchars($user->email) . '</td></tr>'
            . '</table>'
            . '<p>Use esta clave temporal para ingresar (deberá cambiarla al entrar):</p>'
            . '<p style="text-align:center;margin:16px 0;">'
            . '<span style="display:inline-block;font-family:monospace;font-size:20px;letter-spacing:2px;background:rgba(99,102,241,0.15);border:1px dashed rgba(99,102,241,0.5);padding:12px 20px;border-radius:8px;color:#a5b4fc;">'
            . htmlspecialchars($tempPassword) . '</span></p>'
            . '<p style="text-align:center;margin-top:20px;">'
            . '<a href="' . htmlspecialchars($loginUrl) . '" style="display:inline-block;background:#6366f1;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:bold;">Ingresar al portal</a></p>'
            . '</div></div></div>';

        return Mailer::send($user->email, $subject, $html, SMTP_FROM_EMAIL);
    }
}
