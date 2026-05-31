<?php
class TempPasswordMail {

    public static function send($user, $tempPassword) {
        $nombre = htmlspecialchars($user->nombre ?? 'Usuario');
        $loginUrl = URLROOT . '/auth/login';
        $subject = 'Nueva contraseña temporal - ConectaBarrio';
        $html = self::buildHtml($nombre, htmlspecialchars($tempPassword), $loginUrl);
        return Mailer::send($user->email, $subject, $html, SMTP_FROM_EMAIL);
    }

    private static function buildHtml($nombre, $tempPassword, $loginUrl) {
        return '<div style="font-family:Arial,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px;">'
            . '<div style="max-width:520px;margin:0 auto;background:#1e293b;border-radius:12px;overflow:hidden;border:1px solid #334155;">'
            . '<div style="background:linear-gradient(135deg,#6366f1,#0891b2);padding:24px;text-align:center;">'
            . '<h2 style="margin:0;color:#fff;font-size:1.25rem;">Contraseña temporal</h2></div>'
            . '<div style="padding:24px;">'
            . '<p>Hola <strong>' . $nombre . '</strong>,</p>'
            . '<p>Se ha restablecido su acceso a <strong>ConectaBarrio</strong>. Utilice la siguiente clave temporal para ingresar:</p>'
            . '<p style="text-align:center;margin:20px 0;">'
            . '<span style="display:inline-block;font-family:monospace;font-size:22px;letter-spacing:2px;'
            . 'background:rgba(99,102,241,0.15);border:1px dashed rgba(99,102,241,0.5);padding:12px 20px;border-radius:8px;color:#a5b4fc;">'
            . $tempPassword . '</span></p>'
            . '<p style="font-size:14px;color:#94a3b8;">Por seguridad, al iniciar sesión deberá definir una contraseña nueva. '
            . 'Esta clave es de un solo uso y <strong>no debe compartirse</strong> con terceros.</p>'
            . '<p style="text-align:center;margin-top:24px;">'
            . '<a href="' . htmlspecialchars($loginUrl) . '" style="display:inline-block;background:#6366f1;color:#fff;'
            . 'text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:bold;">Ingresar a ConectaBarrio</a></p>'
            . '<p style="font-size:12px;color:#64748b;margin-top:24px;">Si usted no solicitó este cambio, contacte a la directiva de su organización.</p>'
            . '</div></div></div>';
    }
}
