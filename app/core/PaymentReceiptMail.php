<?php
class PaymentReceiptMail {

    public static function sendToOrgAdmins($junta, array $mesAmounts, $fechaPago, $metodoPago, $metodoLabel) {
        if (!Mailer::isConfigured()) {
            return ['ok' => false, 'error' => 'SMTP no configurado'];
        }

        $userModel = new User();
        $admins = $userModel->getAdminsByJunta($junta->id);
        if (empty($admins)) {
            return ['ok' => false, 'error' => 'Sin administradores con email'];
        }

        $mesesHtml = '';
        $total = 0;
        foreach ($mesAmounts as $mes => $amount) {
            $total += (int)$amount;
            $mesesHtml .= '<tr><td style="padding:8px;border-bottom:1px solid #334155;">' . htmlspecialchars($mes) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #334155;text-align:right;">$' . number_format($amount, 0, ',', '.') . '</td></tr>';
        }

        $subject = 'Comprobante de pago ConectaBarrio - ' . htmlspecialchars($junta->nombre);
        $html = self::buildHtml($junta->nombre, $mesesHtml, $total, $fechaPago, $metodoLabel);

        $sent = 0;
        $errors = [];
        foreach ($admins as $admin) {
            if (empty($admin->email) || !filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $personalized = str_replace('{{NOMBRE_ADMIN}}', htmlspecialchars($admin->nombre), $html);
            $result = Mailer::send($admin->email, $subject, $personalized, SMTP_FROM_EMAIL);
            if ($result['ok']) {
                $sent++;
            } else {
                $errors[] = $admin->email;
            }
        }

        if ($sent === 0) {
            return ['ok' => false, 'error' => 'No se pudo enviar a ningún administrador'];
        }
        return ['ok' => true, 'sent' => $sent, 'errors' => $errors];
    }

    private static function buildHtml($orgNombre, $mesesHtml, $total, $fechaPago, $metodoLabel) {
        $fechaFmt = date('d-m-Y', strtotime($fechaPago));
        return '<div style="font-family:Arial,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px;">'
            . '<div style="max-width:560px;margin:0 auto;background:#1e293b;border-radius:12px;overflow:hidden;border:1px solid #334155;">'
            . '<div style="background:linear-gradient(135deg,#0891b2,#6366f1);padding:24px;text-align:center;">'
            . '<h2 style="margin:0;color:#fff;">Comprobante de Pago</h2>'
            . '<p style="margin:8px 0 0;color:rgba(255,255,255,0.9);">Suscripción ConectaBarrio</p></div>'
            . '<div style="padding:24px;">'
            . '<p>Estimado(a) <strong>{{NOMBRE_ADMIN}}</strong>,</p>'
            . '<p>Confirmamos la recepción del pago de suscripción para <strong>' . htmlspecialchars($orgNombre) . '</strong>.</p>'
            . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
            . '<thead><tr><th style="text-align:left;padding:8px;color:#94a3b8;">Período</th>'
            . '<th style="text-align:right;padding:8px;color:#94a3b8;">Monto</th></tr></thead><tbody>'
            . $mesesHtml
            . '</tbody></table>'
            . '<p style="font-size:18px;font-weight:bold;color:#22c55e;">Total: $' . number_format($total, 0, ',', '.') . ' CLP</p>'
            . '<p><strong>Fecha de pago:</strong> ' . $fechaFmt . '<br>'
            . '<strong>Método:</strong> ' . htmlspecialchars($metodoLabel) . '</p>'
            . '<p style="color:#94a3b8;font-size:13px;margin-top:24px;">Equipo ConectaBarrio</p>'
            . '</div></div></div>';
    }
}
