<?php
class ConvocatoriaMail {

    public static function buildHtml(
        string $nombreDestinatario,
        string $juntaNombre,
        string $titulo,
        string $fechaFmt,
        string $temasHtml,
        string $urlApp,
        ?string $urlConfirmar = null,
        ?string $urlRechazar = null
    ): string {
        $nombre = htmlspecialchars($nombreDestinatario);
        $org = htmlspecialchars($juntaNombre);
        $tit = htmlspecialchars($titulo);
        $fecha = htmlspecialchars($fechaFmt);
        $rsvpHtml = self::rsvpButtons($urlConfirmar, $urlRechazar);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
<tr><td style="background:linear-gradient(135deg,#6366f1,#a855f7);padding:28px 32px;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:22px;">Convocatoria a reunión</h1>
<p style="margin:8px 0 0;color:rgba(255,255,255,0.9);font-size:14px;">{$org}</p>
</td></tr>
<tr><td style="padding:32px;">
<p style="margin:0 0 16px;color:#374151;font-size:15px;">Estimado(a) <strong>{$nombre}</strong>,</p>
<p style="margin:0 0 20px;color:#4b5563;font-size:14px;line-height:1.6;">
Se le convoca a la siguiente reunión de su organización:
</p>
<table width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:20px;">
<tr><td style="padding:16px 20px;">
<p style="margin:0 0 8px;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:bold;">Título</p>
<p style="margin:0 0 16px;font-size:16px;font-weight:bold;color:#111827;">{$tit}</p>
<p style="margin:0 0 8px;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:bold;">Fecha y hora</p>
<p style="margin:0;font-size:15px;color:#111827;">{$fecha}</p>
</td></tr>
</table>
<p style="margin:0 0 8px;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:bold;">Temas a tratar</p>
<div style="margin:0 0 24px;padding:16px;background:#faf5ff;border-left:4px solid #a855f7;border-radius:4px;color:#374151;font-size:14px;line-height:1.7;">
{$temasHtml}
</div>
<p style="margin:0 0 20px;color:#6b7280;font-size:13px;">También puede revisar esta convocatoria ingresando a su perfil en ConectaBarrio.</p>
{$rsvpHtml}
<a href="{$urlApp}" style="display:inline-block;background:#6366f1;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:bold;font-size:14px;margin-top:12px;">Ver en ConectaBarrio</a>
</td></tr>
<tr><td style="padding:20px 32px;background:#f9fafb;text-align:center;border-top:1px solid #e5e7eb;">
<p style="margin:0;color:#9ca3af;font-size:12px;">Mensaje automático de ConectaBarrio — no responda a este correo.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    public static function temasToHtml(string $temasText): string {
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $temasText)));
        if (empty($lines)) {
            return '<p style="margin:0;color:#6b7280;">Sin temas detallados.</p>';
        }
        $items = '';
        foreach ($lines as $line) {
            $items .= '<li style="margin-bottom:6px;">' . htmlspecialchars($line) . '</li>';
        }
        return '<ol style="margin:0;padding-left:20px;">' . $items . '</ol>';
    }

    private static function rsvpButtons(?string $urlConfirmar, ?string $urlRechazar): string {
        if (!$urlConfirmar || !$urlRechazar) {
            return '';
        }
        return '<table cellpadding="0" cellspacing="0" style="margin:0 0 16px;"><tr>'
            . '<td style="padding-right:8px;"><a href="' . htmlspecialchars($urlConfirmar) . '" '
            . 'style="display:inline-block;background:#10b981;color:#fff;text-decoration:none;padding:10px 18px;border-radius:8px;font-weight:bold;font-size:13px;">Confirmar asistencia</a></td>'
            . '<td><a href="' . htmlspecialchars($urlRechazar) . '" '
            . 'style="display:inline-block;background:#ef4444;color:#fff;text-decoration:none;padding:10px 18px;border-radius:8px;font-weight:bold;font-size:13px;">No podré asistir</a></td>'
            . '</tr></table>'
            . '<p style="margin:0 0 12px;color:#6b7280;font-size:12px;">Indique su disponibilidad antes de la reunión.</p>';
    }
}
