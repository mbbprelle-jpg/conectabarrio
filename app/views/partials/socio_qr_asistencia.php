<?php
/** Tarjeta QR de asistencia para socios. Variables: $qr_payload, $qr_migration_pending */
if (empty($qr_payload)) {
    return;
}
?>
<div class="card card-info cb-qr-asistencia-card" id="cbQrAsistenciaCard">
    <div class="cb-qr-asistencia-inner">
        <div class="cb-qr-asistencia-visual">
            <div id="cbSocioQrCanvas" class="cb-qr-canvas" aria-label="Código QR de asistencia"></div>
        </div>
        <div class="cb-qr-asistencia-info">
            <h3 class="card-title" style="margin-bottom:0.35rem;">Mi código de asistencia</h3>
            <p style="font-size:0.85rem;color:var(--text-muted);margin:0 0 0.75rem;">
                Muestre este QR al ingresar a una asamblea. El secretario o administrador lo escaneará para registrar su presencia al instante.
            </p>
            <?php if (!empty($qr_migration_pending)): ?>
            <p class="cb-qr-hint-warn">Ejecute <code>sql/add_asistencia_qr_token.sql</code> para activar el QR.</p>
            <?php else: ?>
            <button type="button" class="btn btn-secondary btn-sm" id="cbQrFullscreenBtn">Pantalla completa</button>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const payload = <?php echo json_encode($qr_payload, JSON_UNESCAPED_UNICODE); ?>;
    const el = document.getElementById('cbSocioQrCanvas');
    if (!el || !payload || typeof QRCode === 'undefined') return;
    el.innerHTML = '';
    new QRCode(el, {
        text: payload,
        width: 168,
        height: 168,
        colorDark: '#0f172a',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });
    document.getElementById('cbQrFullscreenBtn')?.addEventListener('click', function() {
        const overlay = document.createElement('div');
        overlay.className = 'cb-qr-fullscreen';
        overlay.innerHTML = '<div class="cb-qr-fullscreen-box"><p>Presente este código en la entrada</p><div id="cbQrFsTarget"></div><button type="button" class="btn btn-secondary btn-sm">Cerrar</button></div>';
        document.body.appendChild(overlay);
        const target = overlay.querySelector('#cbQrFsTarget');
        new QRCode(target, { text: payload, width: 280, height: 280, colorDark: '#0f172a', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
        overlay.querySelector('button').addEventListener('click', () => overlay.remove());
        overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
    });
});
</script>
