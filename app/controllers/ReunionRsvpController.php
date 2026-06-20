<?php
class ReunionRsvpController extends Controller {
    public function responder($token = '', $accion = '') {
        require_once APPROOT . '/models/ReunionConvocado.php';
        $model = new ReunionConvocado();
        $accion = strtolower((string)$accion);
        if (!in_array($accion, ['confirmar', 'rechazar'], true)) {
            $this->renderMessage('Acción no válida', 'Use los enlaces Confirmar o Rechazar del correo.');
            return;
        }
        $estado = $accion === 'confirmar' ? 'confirmado' : 'rechazado';
        $row = $model->updateRsvpByToken((string)$token, $estado);
        if (!$row) {
            $this->renderMessage('Enlace no válido', 'Esta convocatoria ya no acepta respuestas o el enlace expiró.');
            return;
        }
        $titulo = htmlspecialchars((string)$row->titulo);
        $fecha = date('d/m/Y H:i', strtotime($row->fecha_reunion));
        $msg = $estado === 'confirmado'
            ? "Ha confirmado su asistencia a «{$titulo}» ({$fecha})."
            : "Ha indicado que no asistirá a «{$titulo}» ({$fecha}).";
        $this->renderMessage('Respuesta registrada', $msg, true);
    }

    private function renderMessage(string $title, string $message, bool $success = false): void {
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>ConectaBarrio</title><link rel="stylesheet" href="' . URLROOT . '/css/style.css"></head>';
        echo '<body class="landing-body" style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1.5rem;">';
        echo '<div class="card" style="max-width:480px;width:100%;padding:2rem;text-align:center;">';
        echo '<h1 style="font-size:1.25rem;margin-bottom:0.75rem;">' . htmlspecialchars($title) . '</h1>';
        echo '<p style="color:var(--text-muted);margin-bottom:1.5rem;">' . htmlspecialchars($message) . '</p>';
        echo '<a href="' . URLROOT . '/auth/login" class="btn btn-primary">Ingresar al portal</a>';
        echo '</div></body></html>';
        exit;
    }
}
