<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<!-- Botón de Volver -->
<div style="margin-bottom: 1.5rem;">
    <a href="<?php echo URLROOT; ?>/maestro/dashboard" class="btn btn-secondary btn-sm">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Volver al Panel
    </a>
</div>

<!-- Alertas de Error -->
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?php echo htmlspecialchars($data['error']); ?></span>
    </div>
<?php endif; ?>

<form action="<?php echo URLROOT; ?>/maestro/crear_junta" method="POST">
    <div class="grid-2col">
        
        <!-- SECCIÓN 1: DATOS DE LA JUNTA -->
        <div class="card card-primary">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                1. Datos de la Organización / Junta de Socios
            </h3>
            
            <div class="form-group">
                <label for="junta_nombre" class="form-label">Nombre de la Organización *</label>
                <input type="text" name="junta_nombre" id="junta_nombre" class="form-control" placeholder="Ej: Junta de Vecinos Barrio Hermoso" value="<?php echo htmlspecialchars($data['junta_nombre']); ?>" required>
            </div>

            <div class="form-group">
                <label for="junta_tipo" class="form-label">Tipo de Organización *</label>
                <select name="junta_tipo" id="junta_tipo" class="form-control" required>
                    <option value="Junta de Vecinos" <?php echo $data['junta_tipo'] === 'Junta de Vecinos' ? 'selected' : ''; ?>>Junta de Vecinos</option>
                    <option value="Comité" <?php echo $data['junta_tipo'] === 'Comité' ? 'selected' : ''; ?>>Comité</option>
                    <option value="Organización" <?php echo $data['junta_tipo'] === 'Organización' ? 'selected' : ''; ?>>Organización</option>
                </select>
            </div>

            <div class="form-group">
                <label for="junta_rut" class="form-label">RUT de la Organización (Identificador Legal) *</label>
                <input type="text" name="junta_rut" id="junta_rut" class="form-control" placeholder="Ej: 70.123.456-7" value="<?php echo htmlspecialchars($data['junta_rut']); ?>" required>
            </div>

            <div class="form-group">
                <label for="junta_comuna" class="form-label">Comuna *</label>
                <select name="junta_comuna" id="junta_comuna" class="form-control" required>
                    <option value="PEÑAFLOR" <?php echo $data['junta_comuna'] === 'PEÑAFLOR' || empty($data['junta_comuna']) ? 'selected' : ''; ?>>PEÑAFLOR</option>
                    <option value="TALAGANTE" <?php echo $data['junta_comuna'] === 'TALAGANTE' ? 'selected' : ''; ?>>TALAGANTE</option>
                    <option value="EL MONTE" <?php echo $data['junta_comuna'] === 'EL MONTE' ? 'selected' : ''; ?>>EL MONTE</option>
                    <option value="PADRE HURTADO" <?php echo $data['junta_comuna'] === 'PADRE HURTADO' ? 'selected' : ''; ?>>PADRE HURTADO</option>
                    <option value="ISLA DE MAIPO" <?php echo $data['junta_comuna'] === 'ISLA DE MAIPO' ? 'selected' : ''; ?>>ISLA DE MAIPO</option>
                </select>
            </div>

            <div class="form-group">
                <label for="junta_direccion" class="form-label">Dirección Sede *</label>
                <input type="text" name="junta_direccion" id="junta_direccion" class="form-control" placeholder="Ej: Av. Los Leones 450" value="<?php echo htmlspecialchars($data['junta_direccion']); ?>" required>
            </div>

            <div class="form-group">
                <label for="junta_mes_inicio" class="form-label">Mes de Inicio de Actividades *</label>
                <input type="month" name="junta_mes_inicio" id="junta_mes_inicio" class="form-control" value="<?php echo htmlspecialchars($data['junta_mes_inicio'] ?? date('Y-m')); ?>" required>
                <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 0.25rem;">
                    Desde qué mes la organización gestionará cuotas de socios, cierres mensuales y actividades internas.
                </small>
            </div>

            <div class="form-group" style="background: rgba(6,182,212,0.03); border: 1px solid rgba(6,182,212,0.1); padding: 1rem; border-radius: var(--radius-sm);">
                <label for="junta_plan" class="form-label" style="color: var(--primary); font-weight: bold;">Plan Comercial *</label>
                <select name="junta_plan" id="junta_plan" class="form-control" style="background: var(--bg-main);" required>
                    <option value="basico" <?php echo ($data['junta_plan'] ?? 'basico') === 'basico' ? 'selected' : ''; ?>>Plan Básico - $4.990/mes (Oferta, antes $14.990) - Máx 50 socios</option>
                    <option value="mediano" <?php echo ($data['junta_plan'] ?? '') === 'mediano' ? 'selected' : ''; ?>>Plan Mediano - $7.990/mes (Oferta, antes $19.990) - Máx 200 socios + Reuniones</option>
                    <option value="premium" <?php echo ($data['junta_plan'] ?? '') === 'premium' ? 'selected' : ''; ?>>Plan Premium - $9.990/mes (Oferta, antes $24.990) - Ilimitado + Envíos</option>
                </select>
            </div>

            <div class="form-group">
                <label for="junta_precio_anual" class="form-label">Precio Anual Fijo ($) *</label>
                <input type="number" name="junta_precio_anual" id="junta_precio_anual" class="form-control" value="<?php echo htmlspecialchars($data['junta_precio_anual'] ?? '59880'); ?>" min="0" required>
                <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 0.25rem;">
                    Se autocalcula según el plan seleccionado ($Monto/mes * 12), pero puedes editarlo libremente para pactar precios especiales.
                </small>
            </div>

            <div class="form-group">
                <label for="junta_suscripcion_mes_inicio" class="form-label">Mes de Inicio de Suscripción ConectaBarrio *</label>
                <input type="month" name="junta_suscripcion_mes_inicio" id="junta_suscripcion_mes_inicio" class="form-control" value="<?php echo htmlspecialchars($data['junta_suscripcion_mes_inicio'] ?? date('Y-m')); ?>" required>
                <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 0.25rem;">
                    Desde qué mes esta organización debe pagar la suscripción del plan comercial a ConectaBarrio.
                </small>
            </div>

            <div class="grid-2col" style="margin-bottom: 0;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="cuota_inicial" class="form-label">Cuota Mensual Inicial ($) *</label>
                    <input type="number" name="cuota_inicial" id="cuota_inicial" class="form-control" placeholder="Ej: 5000" min="0" value="<?php echo htmlspecialchars($data['cuota_inicial']); ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="cuota_mes_inicio" class="form-label">Vigente Desde (Mes/Año) *</label>
                    <input type="month" name="cuota_mes_inicio" id="cuota_mes_inicio" class="form-control" value="<?php echo htmlspecialchars($data['cuota_mes_inicio']); ?>" required>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: DATOS DEL ADMINISTRADOR -->
        <div class="card card-warning" style="display: flex; flex-direction: column; height: 100%;">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                2. Cuenta de Administrador
            </h3>
            
            <div class="form-group">
                <label for="admin_nombres" class="form-label">Nombres *</label>
                <input type="text" name="admin_nombres" id="admin_nombres" class="form-control" placeholder="Ej: Carlos Andrés" value="<?php echo htmlspecialchars($data['admin_nombres']); ?>" required>
            </div>

            <div class="form-group">
                <label for="admin_apellido_paterno" class="form-label">Apellido Paterno *</label>
                <input type="text" name="admin_apellido_paterno" id="admin_apellido_paterno" class="form-control" placeholder="Ej: Silva" value="<?php echo htmlspecialchars($data['admin_apellido_paterno']); ?>" required>
            </div>

            <div class="form-group">
                <label for="admin_apellido_materno" class="form-label">Apellido Materno *</label>
                <input type="text" name="admin_apellido_materno" id="admin_apellido_materno" class="form-control" placeholder="Ej: Rojas" value="<?php echo htmlspecialchars($data['admin_apellido_materno']); ?>" required>
            </div>

            <div class="form-group">
                <label for="admin_rut" class="form-label">RUT Administrador *</label>
                <input type="text" name="admin_rut" id="admin_rut" class="form-control" placeholder="Ej: 15.678.910-K" value="<?php echo htmlspecialchars($data['admin_rut']); ?>" required>
            </div>

            <div class="form-group">
                <label for="admin_email" class="form-label">Correo Electrónico *</label>
                <input type="email" name="admin_email" id="admin_email" class="form-control" placeholder="Ej: admin@barriohermoso.cl" value="<?php echo htmlspecialchars($data['admin_email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="admin_telefono" class="form-label">Teléfono de Contacto</label>
                <input type="text" name="admin_telefono" id="admin_telefono" class="form-control" placeholder="Ej: +56987654321" value="<?php echo htmlspecialchars($data['admin_telefono']); ?>">
            </div>

            <div class="alert alert-success" style="margin-top: auto; margin-bottom: 1.5rem; border-left-color: var(--warning); background-color: rgba(245,158,11,0.05); color: var(--warning); font-size: 0.8rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                <span>La contraseña temporal por defecto del Administrador será: <strong>admin123</strong>.</span>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Registrar Junta y Enviar Accesos
            </button>
        </div>

    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const planSelect = document.getElementById('junta_plan');
    const precioInput = document.getElementById('junta_precio_anual');
    
    if (planSelect && precioInput) {
        planSelect.addEventListener('change', function() {
            const val = this.value;
            if (val === 'basico') {
                precioInput.value = 59880; // $4.990 * 12
            } else if (val === 'mediano') {
                precioInput.value = 95880; // $7.990 * 12
            } else if (val === 'premium') {
                precioInput.value = 119880; // $9.990 * 12
            }
        });
    }
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
