<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<!-- Mensajes Flash de Éxito / Error -->
<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span><?php echo htmlspecialchars($data['success']); ?></span>
    </div>
<?php endif; ?>

<!-- Introducción al Módulo -->
<div class="card card-primary" style="margin-bottom: 2rem; background: radial-gradient(100% 100% at 0% 0%, rgba(99,102,241,0.05) 0%, transparent 100%), var(--bg-card);">
    <h3 style="font-size: 1.4rem; font-family: var(--font-heading); margin-bottom: 0.5rem; color: var(--primary);">Digitalización Vecinal & Transmisión Municipal</h3>
    <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">
        El sistema **CONECTABARRIO** digitaliza el 100% de la información de su Junta de Vecinos (Padrón electoral de socios, asistencias a asambleas soberanas y balances de flujo de caja). A través de esta interfaz, puede empaquetar estos datos de forma encriptada bajo estándares modernos y enviarlos directamente a los servidores del Municipio para mantener vigentes sus registros municipales y postular a fondos de desarrollo vecinal.
    </p>
</div>

<div class="grid-2col">
    
    <!-- COLUMNA IZQUIERDA: JSON ESTRUCTURADO -->
    <div class="card card-primary" style="display: flex; flex-direction: column; gap: 1rem;">
        <h3 class="card-title" style="margin-bottom: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
            Paquete de Datos Consolidado (JSON)
        </h3>
        <p style="font-size: 0.8rem; color: var(--text-muted);">Esta es la información digitalizada estructurada lista para ser transmitida mediante protocolo seguro:</p>
        
        <textarea style="font-family: monospace; font-size: 0.75rem; background: #020617; border: 1px solid var(--border-color); color: #60a5fa; border-radius: var(--radius-sm); padding: 1rem; height: 350px; resize: none;" readonly><?php echo json_encode($data['paquete_json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></textarea>
    </div>

    <!-- COLUMNA DERECHA: CONSOLA DE SIMULACIÓN Y ENVÍO -->
    <div class="card card-warning" style="display: flex; flex-direction: column; justify-content: space-between; gap: 1.5rem;">
        <div>
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                Transmisión Digital Directa
            </h3>
            
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">Pulse el botón inferior para inicializar el protocolo TLS de comunicación y encriptar los datos para enviarlos a la base de datos municipal.</p>

            <!-- Consola de simulación digital -->
            <div class="simulation-screen" id="simulationScreen">
                <div id="simulationLog" style="color: #10b981;">
                    [SYSTEM] Consola lista para inicializar protocolo. Esperando señal de transmisión...
                </div>
            </div>
        </div>

        <!-- Formulario oculto real que se envía al finalizar la simulación por JavaScript -->
        <form id="formGuardarReporte" action="<?php echo URLROOT; ?>/admin/municipalidad_guardar_envio" method="POST" style="display:none;">
            <input type="hidden" name="tipo_reporte" value="Consolidado General">
            <input type="hidden" name="datos_json" value='<?php echo json_encode($data['paquete_json'], JSON_UNESCAPED_UNICODE); ?>'>
        </form>

        <button onclick='iniciarSimulacionMunicipal(<?php echo json_encode($data['paquete_json'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' 
                class="btn btn-primary" 
                id="btnEnviarMunicipal"
                style="width: 100%;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            Inicializar Envío Municipal
        </button>
    </div>

</div>

<!-- SECCIÓN 3: CERTIFICADO DE RECEPCIÓN (SOLO SI SE HA ENVIADO REPORTES PREVIOS) -->
<?php if ($data['ultimo_certificado']): ?>
    <div id="certificateResult" style="margin-top: 3rem; margin-bottom: 2rem;">
        
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <button onclick="printCertificate();" class="btn btn-secondary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Imprimir Certificado de Recepción Municipal
            </button>
        </div>

        <div class="certificate-frame" id="printableCertificate">
            <div class="certificate-seal">
                <!-- SVG Escudo / Sello Municipal -->
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            
            <h2 class="certificate-title">Certificado de Recepción Digital</h2>
            
            <div class="certificate-body">
                <p>La **Ilustre Municipalidad de <?php echo htmlspecialchars($data['junta']->comuna); ?>** certifica solemnemente que:</p>
                <p style="margin-top: 1rem; font-size: 1.25rem; font-weight: 700; font-family: var(--font-heading); color: var(--success);">
                    <?php echo htmlspecialchars($data['junta']->nombre); ?>
                </p>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.5rem;">RUT Municipalidad Vecinal: <?php echo htmlspecialchars($data['junta']->rut_junta); ?></p>
                
                <p style="text-align: justify; text-justify: inter-word;">
                    Ha cumplido satisfactoriamente con la digitalización y transmisión consolidada de sus registros vecinales para el periodo actual. Se ha recepcionado formalmente el **Padrón de Socios Activos**, la **Asistencia Consolidada a Asambleas** y el **Balance General del Flujo de Caja**, encriptados y firmados digitalmente. Esta información ha quedado archivada bajo los registros del Departamento de Organizaciones Comunitarias.
                </p>
            </div>

            <div style="background: rgba(16,185,129,0.05); border: 1px solid rgba(16,185,129,0.2); border-radius: var(--radius-sm); padding: 1rem; display: flex; justify-content: space-between; align-items: center; font-family: monospace; font-size: 0.85rem; margin-bottom: 2rem;">
                <div>
                    <span style="color: var(--text-muted);">FOLIO DIGITAL:</span>
                    <strong style="color: var(--success); font-weight: 700;">CB-<?php echo str_pad($data['ultimo_certificado']->id, 8, '0', STR_PAD_LEFT); ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted);">FECHA TRANSMISIÓN:</span>
                    <strong style="color: var(--success);"><?php echo date('d-m-Y H:i:s', strtotime($data['ultimo_certificado']->fecha_envio)); ?></strong>
                </div>
            </div>

            <div class="certificate-signatures">
                <div>
                    <div style="height: 50px; font-family: 'Segoe Script', cursive; font-size: 0.9rem; color: var(--text-muted); display: flex; align-items: flex-end; justify-content: center;">
                        Firma Digitalizada
                    </div>
                    <div class="signature-line">
                        <strong><?php echo htmlspecialchars($data['ultimo_certificado']->admin_nombre); ?></strong>
                        <br>Administrador Junta Vecinal
                    </div>
                </div>
                <div>
                    <div style="height: 50px; font-family: 'Segoe Script', cursive; font-size: 0.9rem; color: var(--success); display: flex; align-items: flex-end; justify-content: center; font-weight: 700; text-shadow: 0 0 5px rgba(16,185,129,0.3);">
                        DIDECO SECURE-SIGN v1.3
                    </div>
                    <div class="signature-line">
                        <strong>Dirección de Desarrollo Comunitario</strong>
                        <br>Ilustre Municipalidad de <?php echo htmlspecialchars($data['junta']->comuna); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para imprimir el Certificado Limpio -->
    <script>
    function printCertificate() {
        const printContent = document.getElementById('printableCertificate').outerHTML;
        const originalContent = document.body.innerHTML;
        
        // Crear una ventana limpia para impresión con estilos del certificado
        const win = window.open('', '_blank');
        win.document.write('<html><head><title>Certificado de Recepción Municipal</title>');
        win.document.write('<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/style.css">');
        win.document.write('<style>body{background:#fff; color:#000; display:flex; justify-content:center; align-items:center; min-height:100vh; padding:2rem;} .certificate-frame{border:8px double #10b981; max-width:700px; padding:2rem; box-shadow:none; background:#fff; color:#000;} .certificate-title{color:#10b981 !important;} .certificate-seal{border-color:#10b981; color:#10b981;} .signature-line{color:#4b5563; border-top-color:#d1d5db;}</style>');
        win.document.write('</head><body>');
        win.document.write(printContent);
        win.document.write('</body></html>');
        win.document.close();
        
        // Ejecutar impresión tras pequeña pausa
        setTimeout(function() {
            win.print();
            win.close();
        }, 500);
    }
    </script>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
