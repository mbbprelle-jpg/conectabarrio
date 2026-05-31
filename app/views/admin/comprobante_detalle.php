<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['title']); ?></title>
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/style.css">
    <style>
        body {
            background-color: #0f172a;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1.5rem;
        }
        @media print {
            body {
                background: #fff;
                color: #000;
            }
            .no-print {
                display: none !important;
            }
            .receipt-wrapper {
                border: none;
                box-shadow: none;
                background: #fff;
                color: #000;
                max-width: 100%;
                width: 100%;
                padding: 0;
            }
            .receipt-header-left h3 {
                -webkit-text-fill-color: initial !important;
                color: #000 !important;
            }
            .receipt-total {
                background: #f3f4f6 !important;
                border: 1px solid #d1d5db !important;
            }
            .receipt-total-value {
                color: #000 !important;
            }
        }
    </style>
</head>
<body>

<div style="display: flex; flex-direction: column; gap: 1.5rem; width: 100%; max-width: 600px;">
    
    <!-- Botones de Acción (No imprimibles) -->
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center;">
        <?php if (!empty($data['back_url'])): ?>
            <a href="<?php echo htmlspecialchars($data['back_url']); ?>" class="btn btn-secondary btn-sm">
                ← Volver a mis comprobantes
            </a>
        <?php else: ?>
            <button onclick="window.close();" class="btn btn-secondary btn-sm">
                Cerrar ventana
            </button>
        <?php endif; ?>
        <button onclick="window.print();" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Imprimir Comprobante
        </button>
    </div>

    <!-- Comprobante Físico -->
    <div class="receipt-wrapper">
        <div class="receipt-header">
            <div class="receipt-header-left">
                <h3><?php echo htmlspecialchars($data['pago']->junta_nombre); ?></h3>
                <p>Comuna: <?php echo htmlspecialchars($data['pago']->junta_comuna); ?></p>
                <p>Dirección: <?php echo htmlspecialchars($data['pago']->junta_direccion); ?></p>
            </div>
            <div class="receipt-header-right">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.05em;">Comprobante de Pago</div>
                <div class="receipt-folio">Folio #<?php echo str_pad($data['pago']->id, 6, '0', STR_PAD_LEFT); ?></div>
            </div>
        </div>

        <div class="receipt-body">
            <div class="receipt-row">
                <span class="receipt-row-label">RUT Junta:</span>
                <span class="receipt-row-value"><?php echo htmlspecialchars($data['pago']->junta_rut_id ?? '—'); ?></span>
            </div>
            <?php if (!empty($data['pago']->id_socio)): ?>
            <div class="receipt-row">
                <span class="receipt-row-label">N° Socio:</span>
                <span class="receipt-row-value">#<?php echo (int)$data['pago']->id_socio; ?></span>
            </div>
            <?php endif; ?>
            <div class="receipt-row">
                <span class="receipt-row-label">Socio Vecino:</span>
                <span class="receipt-row-value" style="font-size: 1.05rem;"><?php echo htmlspecialchars($data['pago']->socio_nombre); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-row-label">RUT Socio:</span>
                <span class="receipt-row-value" style="font-family: monospace;"><?php echo htmlspecialchars($data['pago']->socio_rut); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-row-label">Concepto:</span>
                <span class="receipt-row-value"><?php echo htmlspecialchars($data['pago']->categoria); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-row-label">Mes de Cobertura:</span>
                <span class="receipt-row-value" style="font-weight: 700; color: var(--primary);"><?php echo htmlspecialchars($data['pago']->mes_pagado ?? '—'); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-row-label">Fecha de Recaudación:</span>
                <span class="receipt-row-value"><?php echo date('d-m-Y', strtotime($data['pago']->fecha)); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-row-label">Medio de Pago:</span>
                <span class="receipt-row-value">Efectivo / Transferencia Digital</span>
            </div>
            <div class="receipt-row" style="border: none;">
                <span class="receipt-row-label">Registrado por:</span>
                <span class="receipt-row-value" style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($data['pago']->admin_nombre ?? 'Directiva'); ?></span>
            </div>

            <?php if ($data['pago']->categoria === 'Cuota Condonada'): ?>
            <div class="receipt-total" style="border-color: var(--warning);">
                <span class="receipt-total-label">Estado:</span>
                <span class="receipt-total-value" style="color: var(--warning);">Cuota eximida ($0)</span>
            </div>
            <?php if (!empty($data['pago']->descripcion)): ?>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.75rem;">
                Motivo: <?php echo htmlspecialchars($data['pago']->descripcion); ?>
            </p>
            <?php endif; ?>
            <?php else: ?>
            <div class="receipt-total">
                <span class="receipt-total-label">Total Recaudado:</span>
                <span class="receipt-total-value">$<?php echo number_format($data['pago']->monto, 0, ',', '.'); ?> CLP</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="receipt-footer">
            <p>Este documento constituye un comprobante formal de recepción de cuota.</p>
            <p style="margin-top: 0.25rem; font-weight: 600;">CONECTABARRIO - Digitalizando el Progreso Local</p>
            <p style="margin-top: 0.5rem; font-size: 0.72rem; color: var(--text-muted);">
                Emitido el <?php echo date('d-m-Y H:i'); ?> · Folio #<?php echo str_pad($data['pago']->id, 6, '0', STR_PAD_LEFT); ?>
            </p>
        </div>
    </div>

</div>

</body>
</html>
