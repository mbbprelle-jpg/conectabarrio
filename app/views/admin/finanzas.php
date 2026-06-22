<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/AuthContext.php'; ?>

<?php
function cbFinanzasSocioFullName($socio) {
    return trim(implode(' ', array_filter([
        $socio->nombre ?? '',
        $socio->apellido_paterno ?? '',
        $socio->apellido_materno ?? '',
    ], static fn($p) => trim((string)$p) !== '')));
}

function cbFinanzasSocioLabel($socio) {
    $label = cbFinanzasSocioFullName($socio);
    if (!empty($socio->rut)) {
        $label .= ' — ' . $socio->rut;
    }
    if (($socio->status ?? '') === 'prevalidar') {
        $label .= ' (Alta provisional)';
    }
    if (($socio->rol ?? '') === 'admin') {
        $label .= ' (Administrador)';
    }
    return $label;
}

function cbFinanzasSocioDisplayFromTx($t) {
    if (!empty($t->socio_id)) {
        $fake = (object)[
            'nombre' => $t->socio_nombre ?? '',
            'apellido_paterno' => $t->socio_apellido_paterno ?? '',
            'apellido_materno' => $t->socio_apellido_materno ?? '',
            'rut' => $t->socio_rut ?? '',
        ];
        return cbFinanzasSocioFullName($fake) ?: ($t->socio_nombre ?? 'Desafiliado');
    }
    return '';
}

$sociosPickerJson = array_map(static function ($socio) {
    $full = cbFinanzasSocioFullName($socio);
    return [
        'id' => (int)$socio->id,
        'label' => cbFinanzasSocioLabel($socio),
        'search' => mb_strtolower($full . ' ' . ($socio->rut ?? ''), 'UTF-8'),
        'prevalidar' => ($socio->status ?? '') === 'prevalidar',
    ];
}, $data['socios'] ?? []);
?>

<style>
/* Estilos para el selector de meses multi-select */
.mes-group-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-top: 0.8rem;
    margin-bottom: 0.4rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.mes-group-title:first-of-type {
    margin-top: 0;
}
.mes-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.4rem;
    margin-bottom: 0.75rem;
}
.mes-card-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem 0.85rem;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    cursor: pointer;
    transition: var(--transition);
    user-select: none;
}
.mes-card-label:hover:not(.disabled) {
    background: rgba(255, 255, 255, 0.05);
    border-color: var(--border-hover);
}
.mes-card-label.checked {
    background: rgba(99, 102, 241, 0.08);
    border-color: var(--primary);
    box-shadow: 0 0 10px rgba(99, 102, 241, 0.15);
}
.mes-card-label.checked-condonado {
    background: rgba(245, 158, 11, 0.08);
    border-color: var(--warning);
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.15);
}
.mes-card-label.disabled {
    cursor: not-allowed;
    opacity: 0.6;
    background: rgba(255, 255, 255, 0.01);
}
.mes-card-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.mes-checkbox {
    width: 17px;
    height: 17px;
    accent-color: var(--primary);
    cursor: pointer;
}
.mes-checkbox:disabled {
    cursor: not-allowed;
}
.mes-card-name {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-main);
}
.mes-card-badge {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.15rem 0.45rem;
    border-radius: 4px;
}
.badge-pendiente {
    color: var(--danger);
    background: var(--danger-bg);
    border: 1px solid rgba(239, 68, 68, 0.2);
}
.badge-futuro {
    color: var(--info);
    background: var(--info-bg);
    border: 1px solid rgba(6, 182, 212, 0.2);
}
.badge-pagado {
    color: var(--success);
    background: var(--success-bg);
    border: 1px solid rgba(16, 185, 129, 0.2);
}
.badge-condonado {
    color: var(--warning);
    background: var(--warning-bg);
    border: 1px solid rgba(245, 158, 11, 0.2);
}
</style>

<?php require APPROOT . '/views/partials/maestro_finanzas_banner.php'; ?>

<!-- Mensajes Flash de Éxito / Error -->
<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span><?php echo htmlspecialchars($data['success']); ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?php echo htmlspecialchars($data['error']); ?></span>
    </div>
<?php endif; ?>

<?php
$rango = $data['rango_fechas'] ?? ['min' => date('Y-m-d'), 'max' => date('Y-m-d'), 'meses_cerrados' => [], 'mes_inicio' => date('Y-m')];
$fechaMin = $rango['min'];
$fechaMax = $rango['max'];
$fechaDefault = ($fechaMax >= $fechaMin) ? $fechaMax : $fechaMin;
$mesInicioLabel = $data['mes_inicio'] ?? date('Y-m');
?>

<div class="cb-finanzas-nav" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
    <span class="badge badge-primary" style="padding: 0.45rem 0.85rem;">Movimientos</span>
    <?php if (AuthContext::canViewFlujoCaja()): ?>
    <a href="<?php echo URLROOT; ?>/admin/flujo_caja" class="btn btn-secondary btn-sm">Flujo de Caja anual</a>
    <?php endif; ?>
    <a href="<?php echo URLROOT; ?>/admin/conceptos_caja" class="btn btn-secondary btn-sm">Conceptos de Caja</a>
</div>

<?php if (!empty($data['conceptos_migration_pending'])): ?>
<div class="alert alert-warning" style="margin-bottom: 1.25rem;">
    <span>
        Falta aplicar la migración SQL en la base de datos de producción
        (<code>sql/add_finanzas_saldo_conceptos.sql</code> o solo <code>sql/add_finanzas_conceptos_only.sql</code>).
        Sin la tabla <code>finanzas_conceptos</code> algunas funciones de Finanzas pueden fallar hasta ejecutarla en MySQL y volver a desplegar.
    </span>
</div>
<?php endif; ?>

<?php if (!empty($data['puede_editar_saldo_inicial'])): ?>
<div class="card card-primary cb-saldo-inicial-setup" style="margin-bottom: 1.5rem; border-color: rgba(99,102,241,0.25); background: radial-gradient(100% 100% at 0% 0%, rgba(99,102,241,0.08) 0%, transparent 100%), var(--bg-card);">
    <h3 class="card-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
        Configuración de arranque — Saldo inicial de caja
    </h3>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem; line-height: 1.45;">
        Declare una sola vez el dinero contable con el que inicia su organización en ConectaBarrio (caja + banco).
        Puede editarlo hasta realizar el <strong>primer cierre mensual</strong>; después quedará bloqueado.
    </p>
    <form action="<?php echo URLROOT; ?>/admin/guardar_saldo_inicial" method="POST" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
        <div class="form-group" style="flex: 1; min-width: 220px; margin-bottom: 0;">
            <label for="saldo_inicial" class="form-label">Saldo inicial ($) *</label>
            <input type="number" name="saldo_inicial" id="saldo_inicial" class="form-control" min="0" step="1" placeholder="Ej: 150000" required
                value="<?php echo $data['saldo_inicial'] !== null ? (int)$data['saldo_inicial'] : ''; ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="min-width: 160px;">
            <?php echo !empty($data['saldo_inicial_declarado']) ? 'Actualizar saldo' : 'Guardar saldo inicial'; ?>
        </button>
    </form>
    <div style="margin-top: 0.85rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;">
        <small style="color: var(--text-muted); font-size: 0.72rem;">
            Inicio de actividades: <?php echo htmlspecialchars($mesInicioLabel); ?> · Personalice sus categorías en
            <a href="<?php echo URLROOT; ?>/admin/conceptos_caja" style="color: var(--primary);">Conceptos de Caja</a>.
        </small>
        <?php if (!empty($data['saldo_inicial_declarado'])): ?>
            <span class="badge badge-info">Saldo declarado: $<?php echo number_format((int)$data['saldo_inicial'], 0, ',', '.'); ?></span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="grid-2col">

    <!-- COLUMNA REGISTRO FINANCIERO (IZQUIERDA) -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- CARD 1: REGISTRAR RECAUDACIÓN CUOTA SOCIO -->
        <div class="card card-success">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                Registrar Pago de Cuota Socio
            </h3>

            <form action="<?php echo URLROOT; ?>/admin/registrar_pago_cuota" method="POST">
                
                <div class="form-group">
                    <label for="socio_id" class="form-label">Seleccione Socio Vecino *</label>
                    <div id="socio_provisional_alert" class="alert alert-warning" style="display: none; padding: 0.65rem 0.85rem; font-size: 0.78rem; margin-bottom: 0.65rem;">
                        Este socio está en <strong>alta provisional</strong> (sin correo). Puede registrar el pago; el vecino aún no tiene acceso al portal hasta activar su cuenta.
                    </div>
                    <div class="cb-socio-picker" id="picker_cuota_socio" data-required="1" data-placeholder="Escriba nombre, apellido o RUT…">
                        <input type="hidden" name="socio_id" id="socio_id" value="" required>
                        <input type="text" class="form-control cb-socio-picker-input" id="socio_id_input" autocomplete="off" placeholder="Escriba nombre, apellido o RUT…">
                        <ul class="cb-socio-picker-list" hidden></ul>
                    </div>
                </div>

                <div class="grid-2col" style="margin-bottom: 1rem;">
                    <div class="form-group cb-date-field-wrap" style="margin-bottom: 0;">
                        <label for="fecha_pago" class="form-label">Fecha de Pago *</label>
                        <input type="date" name="fecha_pago" id="fecha_pago" class="form-control cb-finanzas-date" value="<?php echo htmlspecialchars($fechaDefault); ?>" min="<?php echo htmlspecialchars($fechaMin); ?>" max="<?php echo htmlspecialchars($fechaMax); ?>" required>
                        <small class="cb-date-hint">Permitido entre <?php echo date('d-m-Y', strtotime($fechaMin)); ?> y hoy. No se admiten meses cerrados.</small>
                    </div>
                    <div class="form-group" style="margin-bottom: 0; display: flex; align-items: flex-end; padding-bottom: 0.65rem;">
                        <label class="switch-container" style="display: inline-flex; align-items: center; cursor: pointer; gap: 0.6rem; user-select: none;">
                            <input type="checkbox" name="es_condonado" id="es_condonado" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-color);">Condonar / Eximir Cuota (Monto $0)</span>
                        </label>
                    </div>
                </div>

                <!-- Selector de Meses Multi-select -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label class="form-label" style="margin-bottom: 0;">Meses a Pagar / Eximir *</label>
                        <div id="quick_actions_container" style="display: none; gap: 0.4rem;">
                            <button type="button" id="btn_select_pendientes" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.72rem; border-radius: 4px; background: rgba(99, 102, 241, 0.15); border-color: var(--primary); color: var(--text-main);">✓ Todos los Pendientes</button>
                            <button type="button" id="btn_clear_selection" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.72rem; border-radius: 4px; background: transparent; border-color: var(--border-color);">✕ Limpiar</button>
                        </div>
                    </div>
                    <div id="meses_checkboxes_container" style="max-height: 230px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.75rem; background: var(--bg-input); display: flex; flex-direction: column; gap: 0.5rem;">
                        <p style="color: var(--text-muted); text-align: center; font-size: 0.85rem; margin: 1rem 0;">-- Primero seleccione un socio vecino --</p>
                    </div>
                </div>

                <!-- Campo Justificación Condonación (oculto por defecto) -->
                <div class="form-group" id="justificacion_container" style="display: none; margin-bottom: 1.25rem;">
                    <label for="justificacion" class="form-label" style="color: var(--warning);">Justificación / Motivo de Exención *</label>
                    <input type="text" name="justificacion" id="justificacion" class="form-control" placeholder="Ej: Adulto mayor eximido, socio en cesantía aprobado en asamblea">
                </div>

                <!-- Alerta dinámica de monto -->
                <div id="monto_info_alert" class="alert alert-success" style="padding: 0.8rem; background-color: rgba(16,185,129,0.05); color: var(--success); border-left-color: var(--success); font-size: 0.8rem; margin-top: 0.5rem; margin-bottom: 1.5rem; display: none;">
                    Monto a aplicar: <strong id="monto_cuota_visual">$0</strong>
                </div>

                <button type="submit" id="btn_submit_cuota" class="btn btn-success" style="width: 100%;">
                    Registrar Cuota
                </button>
            </form>
        </div>

        <!-- CARD 2: OTROS MOVIMIENTOS (INGRESOS GENERALES / EGRESOS DETALLADOS) -->
        <div class="card card-primary">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                Otros Movimientos de Caja
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: -0.5rem 0 1rem;">
                Categorías definidas por su organización —
                <a href="<?php echo URLROOT; ?>/admin/conceptos_caja" style="color: var(--primary);">administrar conceptos</a>.
            </p>

            <form action="<?php echo URLROOT; ?>/admin/registrar_transaccion" method="POST">
                
                <div class="grid-2col" style="margin-bottom: 1rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="tipo" class="form-label">Tipo de Operación *</label>
                        <select name="tipo" id="tipo" class="form-control" required>
                            <option value="ingreso">Ingreso (+)</option>
                            <option value="egreso">Egreso (-)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="categoria" class="form-label">Categoría *</label>
                        <select name="categoria" id="categoria" class="form-control" required>
                            <!-- Se puebla dinámicamente con JS -->
                        </select>
                    </div>
                </div>

                <div class="grid-2col" style="margin-bottom: 1rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="monto" class="form-label">Monto ($) *</label>
                        <input type="number" name="monto" id="monto" class="form-control" placeholder="Ej: 25000" min="1" required>
                    </div>
                    <div class="form-group cb-date-field-wrap" style="margin-bottom: 0;">
                        <label for="fecha" class="form-label">Fecha del Movimiento *</label>
                        <input type="date" name="fecha" id="fecha" class="form-control cb-finanzas-date" value="<?php echo htmlspecialchars($fechaDefault); ?>" min="<?php echo htmlspecialchars($fechaMin); ?>" max="<?php echo htmlspecialchars($fechaMax); ?>" required>
                        <small class="cb-date-hint">Permitido entre <?php echo date('d-m-Y', strtotime($fechaMin)); ?> y hoy. No se admiten meses cerrados.</small>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="asociar_socio_id" class="form-label">Asociar a Socio Vecino (Opcional)</label>
                    <div class="cb-socio-picker" id="picker_asociar_socio" data-placeholder="No asociar — escriba para buscar socio…">
                        <input type="hidden" name="socio_id" id="asociar_socio_id" value="">
                        <input type="text" class="form-control cb-socio-picker-input" id="asociar_socio_id_input" autocomplete="off" placeholder="No asociar — escriba para buscar socio…">
                        <button type="button" class="cb-socio-picker-clear" title="Quitar socio" hidden aria-label="Quitar socio">&times;</button>
                        <ul class="cb-socio-picker-list" hidden></ul>
                    </div>
                    <small style="font-size: 0.72rem; color: var(--text-muted); display: block; margin-top: 0.25rem;">
                        Permite registrar un ingreso (donación) o egreso a cuenta de un socio específico para su visualización en el portal vecinal.
                    </small>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="descripcion" class="form-label">Descripción / Justificación</label>
                    <input type="text" name="descripcion" id="descripcion" class="form-control" placeholder="Ej: Donación comercial por bazar navideño">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Registrar Movimiento
                </button>
            </form>
        </div>

    </div>

    <!-- COLUMNA HISTORIAL DE CAJA (DERECHA) -->
    <div class="card card-warning" style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Historial de Transacciones
        </h3>

        <!-- Caja Resumen Rápido -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 0.75rem; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.75rem 1rem;">
            <?php if ($data['saldo_inicial'] !== null): ?>
            <div style="text-align: center;">
                <div style="font-size: 0.75rem; color: var(--text-muted);">Saldo inicial</div>
                <strong style="color: var(--info); font-size: 0.9rem;">$<?php echo number_format($data['saldo_inicial'], 0, ',', '.'); ?></strong>
            </div>
            <?php endif; ?>
            <div style="text-align: center;">
                <div style="font-size: 0.75rem; color: var(--text-muted);">Ingresos</div>
                <strong style="color: var(--success); font-size: 0.95rem;">+$<?php echo number_format($data['balance']['ingresos'], 0, ',', '.'); ?></strong>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 0.75rem; color: var(--text-muted);">Egresos</div>
                <strong style="color: var(--danger); font-size: 0.95rem;">-$<?php echo number_format($data['balance']['egresos'], 0, ',', '.'); ?></strong>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $data['saldo_inicial'] !== null ? 'Saldo contable' : 'Balance'; ?></div>
                <strong style="color: var(--primary); font-size: 0.95rem;">$<?php echo number_format($data['balance']['contable'] ?? $data['balance']['neto'], 0, ',', '.'); ?></strong>
            </div>
        </div>

        <?php if (empty($data['transacciones'])): ?>
            <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No se han registrado ingresos ni egresos en esta junta aún.</p>
        <?php else: ?>
            <?php $mesesCerrados = $rango['meses_cerrados'] ?? []; ?>
            <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Categoría / Detalle</th>
                            <th>Monto</th>
                            <th>Comprobante</th>
                            <th style="text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['transacciones'] as $t):
                            $mesTx = substr($t->fecha, 0, 7);
                            $puedeEditar = !in_array($mesTx, $mesesCerrados, true);
                            $socioDisplay = cbFinanzasSocioDisplayFromTx($t);
                        ?>
                            <tr>
                                <td style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace;"><?php echo date('d-m-Y', strtotime($t->fecha)); ?></td>
                                <td>
                                    <div style="font-weight: 600; font-size: 0.85rem;">
                                        <?php if ($t->categoria === 'Cuota Condonada'): ?>
                                            <span style="color: var(--warning); font-size: 0.75rem; border: 1px solid var(--warning); padding: 0.1rem 0.3rem; border-radius: 4px; display: inline-block; margin-bottom: 0.15rem;">EXENTO</span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($t->categoria); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        <?php 
                                        if ($t->categoria === 'Cuota Socio' || $t->categoria === 'Cuota Condonada') {
                                            echo 'Socio: ' . htmlspecialchars($socioDisplay ?: 'Desafiliado') . ' (' . htmlspecialchars($t->mes_pagado) . ')';
                                        } else {
                                            $detalles = htmlspecialchars($t->descripcion ?? 'Sin detalle');
                                            if ($socioDisplay !== '') {
                                                $detalles .= ' | <span style="color:var(--primary)">Asoc: ' . htmlspecialchars($socioDisplay) . '</span>';
                                            }
                                            echo $detalles;
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td style="font-weight: 700; text-align: right; white-space: nowrap;">
                                    <span style="color: <?php echo $t->categoria === 'Cuota Condonada' ? 'var(--warning)' : ($t->tipo === 'ingreso' ? 'var(--success)' : 'var(--danger)'); ?>;">
                                        <?php echo $t->categoria === 'Cuota Condonada' ? 'Exento' : ($t->tipo === 'ingreso' ? '+$' . number_format($t->monto, 0, ',', '.') : '-$' . number_format($t->monto, 0, ',', '.')); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($t->categoria === 'Cuota Socio'): ?>
                                        <a href="<?php echo URLROOT; ?>/admin/comprobante/<?php echo $t->id; ?>" 
                                           target="_blank" 
                                           class="btn btn-secondary btn-sm" 
                                           style="padding: 0.3rem 0.5rem; font-size: 0.75rem;" 
                                           title="Ver Comprobante Oficial Imprimible">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                            Ver Recibo
                                        </a>
                                    <?php elseif ($t->categoria === 'Cuota Condonada'): ?>
                                        <span style="font-size: 0.72rem; color: var(--warning); font-style: italic;" title="<?php echo htmlspecialchars($t->descripcion); ?>">Condonado</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Interno</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <?php if ($puedeEditar): ?>
                                    <button type="button" class="btn btn-secondary btn-sm btn-edit-tx" style="padding:0.25rem 0.45rem;font-size:0.72rem;margin:0.1rem;"
                                        data-tx="<?php echo htmlspecialchars(json_encode([
                                            'id' => (int)$t->id,
                                            'tipo' => $t->tipo,
                                            'categoria' => $t->categoria,
                                            'monto' => (int)$t->monto,
                                            'fecha' => $t->fecha,
                                            'descripcion' => $t->descripcion ?? '',
                                            'socio_id' => $t->socio_id ? (int)$t->socio_id : null,
                                            'socio_label' => $socioDisplay,
                                            'mes_pagado' => $t->mes_pagado ?? '',
                                            'es_cuota' => in_array($t->categoria, ['Cuota Socio', 'Cuota Condonada'], true),
                                        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>">
                                        Editar
                                    </button>
                                    <form action="<?php echo URLROOT; ?>/admin/transaccion_eliminar" method="POST" style="display:inline;" class="form-delete-tx"
                                        onsubmit="return confirm('¿Eliminar este movimiento? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="transaccion_id" value="<?php echo (int)$t->id; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" style="padding:0.25rem 0.45rem;font-size:0.72rem;margin:0.1rem;">Eliminar</button>
                                    </form>
                                    <?php else: ?>
                                    <span style="font-size:0.68rem;color:var(--text-muted);" title="Mes cerrado">Bloqueado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="font-size:0.72rem;color:var(--text-muted);margin:0.5rem 0 0;">Solo puede editar o eliminar movimientos de meses aún no cerrados.</p>
        <?php endif; ?>

    </div>

</div>

<!-- Modal editar transacción -->
<div id="modalEditTx" class="cb-modal-overlay">
    <div class="cb-modal-box" role="dialog" aria-labelledby="modalEditTxTitle" style="max-width:520px;">
        <h3 class="cb-modal-title" id="modalEditTxTitle">Editar movimiento</h3>
        <form action="<?php echo URLROOT; ?>/admin/transaccion_actualizar" method="POST" id="formEditTx">
            <input type="hidden" name="transaccion_id" id="edit_tx_id" value="">
            <div id="edit_tx_cuota_info" class="alert alert-info" style="display:none;font-size:0.82rem;padding:0.65rem 0.85rem;margin-bottom:1rem;"></div>

            <div class="grid-2col" id="edit_tx_general_fields" style="margin-bottom:1rem;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" id="edit_tx_tipo" class="form-control">
                        <option value="ingreso">Ingreso (+)</option>
                        <option value="egreso">Egreso (-)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Categoría</label>
                    <select name="categoria" id="edit_tx_categoria" class="form-control"></select>
                </div>
            </div>

            <div class="grid-2col" style="margin-bottom:1rem;">
                <div class="form-group" id="edit_tx_monto_wrap" style="margin-bottom:0;">
                    <label class="form-label">Monto ($)</label>
                    <input type="number" name="monto" id="edit_tx_monto" class="form-control" min="1" step="1">
                </div>
                <div class="form-group cb-date-field-wrap" style="margin-bottom:0;">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="edit_tx_fecha" class="form-control cb-finanzas-date" min="<?php echo htmlspecialchars($fechaMin); ?>" max="<?php echo htmlspecialchars($fechaMax); ?>" required>
                </div>
            </div>

            <div class="form-group" id="edit_tx_mes_wrap" style="display:none;margin-bottom:1rem;">
                <label class="form-label">Mes de cuota (YYYY-MM)</label>
                <input type="month" name="mes_pagado" id="edit_tx_mes_pagado" class="form-control">
            </div>

            <div class="form-group" id="edit_tx_socio_wrap" style="margin-bottom:1rem;">
                <label class="form-label">Socio asociado (opcional)</label>
                <div class="cb-socio-picker" id="picker_edit_tx_socio" data-placeholder="Sin socio asociado…">
                    <input type="hidden" name="socio_id" id="edit_tx_socio_id" value="">
                    <input type="text" class="form-control cb-socio-picker-input" id="edit_tx_socio_input" autocomplete="off">
                    <button type="button" class="cb-socio-picker-clear" title="Quitar socio" hidden aria-label="Quitar socio">&times;</button>
                    <ul class="cb-socio-picker-list" hidden></ul>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" id="edit_tx_desc_label">Descripción</label>
                <input type="text" name="descripcion" id="edit_tx_descripcion" class="form-control">
            </div>

            <div style="display:flex;gap:0.5rem;justify-content:flex-end;flex-wrap:wrap;">
                <button type="button" class="btn btn-secondary" id="btnCloseEditTx">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript de Motor de Cuotas e Interacción Dinámica -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    const SOCIOS_DATA = <?php echo json_encode($sociosPickerJson, JSON_UNESCAPED_UNICODE); ?>;

    function initSocioPicker(root, onSelect) {
        if (!root) return null;
        const hidden = root.querySelector('input[type="hidden"]');
        const input = root.querySelector('.cb-socio-picker-input');
        const list = root.querySelector('.cb-socio-picker-list');
        const clearBtn = root.querySelector('.cb-socio-picker-clear');
        const isRequired = root.dataset.required === '1';
        let activeIdx = -1;

        const renderList = (query) => {
            const q = (query || '').trim().toLowerCase();
            const items = q === ''
                ? SOCIOS_DATA.slice(0, 40)
                : SOCIOS_DATA.filter(s => s.search.includes(q)).slice(0, 40);

            list.innerHTML = '';
            if (items.length === 0) {
                const li = document.createElement('li');
                li.className = 'cb-socio-picker-empty';
                li.textContent = 'Sin coincidencias';
                list.appendChild(li);
            } else {
                items.forEach((s, idx) => {
                    const li = document.createElement('li');
                    li.className = 'cb-socio-picker-item' + (idx === activeIdx ? ' is-active' : '');
                    li.dataset.id = s.id;
                    li.dataset.prevalidar = s.prevalidar ? '1' : '0';
                    li.innerHTML = s.label + (s.prevalidar ? ' <span class="badge badge-warning" style="font-size:0.62rem;">Provisional</span>' : '');
                    li.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        selectSocio(s);
                    });
                    list.appendChild(li);
                });
            }
            list.hidden = false;
        };

        const selectSocio = (s) => {
            hidden.value = s.id;
            input.value = s.label;
            list.hidden = true;
            activeIdx = -1;
            if (clearBtn) clearBtn.hidden = false;
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
            if (typeof onSelect === 'function') onSelect(s);
        };

        const clearSocio = () => {
            hidden.value = '';
            input.value = '';
            list.hidden = true;
            activeIdx = -1;
            if (clearBtn) clearBtn.hidden = true;
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
            if (typeof onSelect === 'function') onSelect(null);
        };

        input.addEventListener('focus', () => renderList(input.value));
        input.addEventListener('input', () => {
            hidden.value = '';
            if (clearBtn) clearBtn.hidden = true;
            activeIdx = -1;
            renderList(input.value);
        });
        input.addEventListener('keydown', (e) => {
            const items = list.querySelectorAll('.cb-socio-picker-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIdx = Math.min(activeIdx + 1, items.length - 1);
                renderList(input.value);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIdx = Math.max(activeIdx - 1, 0);
                renderList(input.value);
            } else if (e.key === 'Enter' && activeIdx >= 0 && items[activeIdx]) {
                e.preventDefault();
                const id = parseInt(items[activeIdx].dataset.id, 10);
                const s = SOCIOS_DATA.find(x => x.id === id);
                if (s) selectSocio(s);
            } else if (e.key === 'Escape') {
                list.hidden = true;
            }
        });
        input.addEventListener('blur', () => {
            setTimeout(() => { list.hidden = true; }, 150);
            if (isRequired && !hidden.value && input.value.trim() !== '') {
                input.value = '';
            }
        });
        if (clearBtn) clearBtn.addEventListener('click', clearSocio);

        return {
            setValue(id, label) {
                hidden.value = id || '';
                input.value = label || '';
                if (clearBtn) clearBtn.hidden = !id;
            },
            clear: clearSocio,
            getHidden: () => hidden,
        };
    }

    const pickerCuota = initSocioPicker(document.getElementById('picker_cuota_socio'));
    initSocioPicker(document.getElementById('picker_asociar_socio'));
    const pickerEditTx = initSocioPicker(document.getElementById('picker_edit_tx_socio'));

    // ==========================================
    // SECCIÓN 1: PAGO DE CUOTAS Y CONDONACIÓN
    // ==========================================
    const socioHidden = document.getElementById('socio_id');
    const socioProvisionalAlert = document.getElementById('socio_provisional_alert');
    const mesesContainer = document.getElementById('meses_checkboxes_container');
    const quickActions = document.getElementById('quick_actions_container');
    const btnSelectPendientes = document.getElementById('btn_select_pendientes');
    const btnClearSelection = document.getElementById('btn_clear_selection');
    const esCondonadoCheck = document.getElementById('es_condonado');
    const justificacionContainer = document.getElementById('justificacion_container');
    const justificacionInput = document.getElementById('justificacion');
    const alertInfo = document.getElementById('monto_info_alert');
    const visualMonto = document.getElementById('monto_cuota_visual');
    const btnSubmitCuota = document.getElementById('btn_submit_cuota');

    if (socioHidden && mesesContainer) {
        const syncProvisionalAlert = (prevalidar) => {
            if (!socioProvisionalAlert) return;
            socioProvisionalAlert.style.display = prevalidar ? 'block' : 'none';
        };
        socioHidden.addEventListener('change', function() {
            const socioId = socioHidden.value;
            const s = SOCIOS_DATA.find(x => String(x.id) === String(socioId));
            syncProvisionalAlert(s && s.prevalidar);
            
            if (!socioId) {
                mesesContainer.innerHTML = '<p style="color: var(--text-muted); text-align: center; font-size: 0.85rem; margin: 1rem 0;">-- Primero seleccione un socio vecino --</p>';
                quickActions.style.display = 'none';
                alertInfo.style.display = 'none';
                if (socioProvisionalAlert) socioProvisionalAlert.style.display = 'none';
                return;
            }

            // Consultar AJAX al backend
            mesesContainer.innerHTML = '<p style="color: var(--text-muted); text-align: center; font-size: 0.85rem; margin: 1rem 0;">Cargando cuotas...</p>';
            quickActions.style.display = 'none';
            alertInfo.style.display = 'none';
            
            fetch('<?php echo URLROOT; ?>/admin/get_socio_cuotas/' + socioId)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        mesesContainer.innerHTML = '<p style="color: var(--danger); text-align: center; font-size: 0.85rem; margin: 1rem 0;">Error al cargar cuotas</p>';
                        return;
                    }

                    mesesContainer.innerHTML = '';

                    let hasPendings = false;
                    let hasFutures = false;
                    let hasResolved = false;

                    const pendingTitle = document.createElement('div');
                    pendingTitle.className = 'mes-group-title';
                    pendingTitle.innerHTML = '⚠️ Atrasados / Pendientes';
                    
                    const pendingGrid = document.createElement('div');
                    pendingGrid.className = 'mes-grid';

                    const futureTitle = document.createElement('div');
                    futureTitle.className = 'mes-group-title';
                    futureTitle.innerHTML = '🔮 Adelantar Meses Futuros';
                    
                    const futureGrid = document.createElement('div');
                    futureGrid.className = 'mes-grid';

                    const resolvedTitle = document.createElement('div');
                    resolvedTitle.className = 'mes-group-title';
                    resolvedTitle.innerHTML = '✅ Pagados / Eximidos (Historial)';
                    
                    const resolvedGrid = document.createElement('div');
                    resolvedGrid.className = 'mes-grid';

                    data.meses.forEach(item => {
                        const parts = item.mes.split('-');
                        const dateObj = new Date(parts[0], parts[1] - 1, 1);
                        const mesLabel = dateObj.toLocaleDateString('es-CL', { month: 'long', year: 'numeric' });
                        const mesCapitalized = mesLabel.charAt(0).toUpperCase() + mesLabel.slice(1);

                        const label = document.createElement('label');
                        label.className = 'mes-card-label';

                        const leftDiv = document.createElement('div');
                        leftDiv.className = 'mes-card-left';

                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.name = 'mes_pagado[]';
                        checkbox.value = item.mes;
                        checkbox.className = 'mes-checkbox';
                        checkbox.dataset.monto = item.monto;
                        checkbox.dataset.estado = item.estado;

                        const nameSpan = document.createElement('span');
                        nameSpan.className = 'mes-card-name';
                        nameSpan.textContent = mesCapitalized;

                        const badgeSpan = document.createElement('span');
                        badgeSpan.className = `mes-card-badge badge-${item.estado}`;

                        leftDiv.appendChild(checkbox);
                        leftDiv.appendChild(nameSpan);
                        label.appendChild(leftDiv);
                        label.appendChild(badgeSpan);

                        if (item.estado === 'pagado') {
                            checkbox.disabled = true;
                            label.classList.add('disabled');
                            badgeSpan.textContent = `PAGADO: $${item.monto.toLocaleString('es-CL')}`;
                            resolvedGrid.appendChild(label);
                            hasResolved = true;
                        } else if (item.estado === 'condonado') {
                            checkbox.disabled = true;
                            label.classList.add('disabled');
                            badgeSpan.textContent = `EXENTO: ${item.descripcion}`;
                            resolvedGrid.appendChild(label);
                            hasResolved = true;
                        } else if (item.estado === 'pendiente') {
                            badgeSpan.textContent = `PENDIENTE: $${item.monto.toLocaleString('es-CL')}`;
                            pendingGrid.appendChild(label);
                            hasPendings = true;

                            checkbox.addEventListener('change', function() {
                                if (this.checked) {
                                    label.classList.add(esCondonadoCheck && esCondonadoCheck.checked ? 'checked-condonado' : 'checked');
                                } else {
                                    label.classList.remove('checked', 'checked-condonado');
                                }
                                updateCuotaMontoVisual();
                            });
                        } else if (item.estado === 'futuro') {
                            badgeSpan.textContent = `ADELANTADO: $${item.monto.toLocaleString('es-CL')}`;
                            futureGrid.appendChild(label);
                            hasFutures = true;

                            checkbox.addEventListener('change', function() {
                                if (this.checked) {
                                    label.classList.add(esCondonadoCheck && esCondonadoCheck.checked ? 'checked-condonado' : 'checked');
                                } else {
                                    label.classList.remove('checked', 'checked-condonado');
                                }
                                updateCuotaMontoVisual();
                            });
                        }
                    });

                    if (hasPendings) {
                        mesesContainer.appendChild(pendingTitle);
                        mesesContainer.appendChild(pendingGrid);
                    }
                    if (hasFutures) {
                        mesesContainer.appendChild(futureTitle);
                        mesesContainer.appendChild(futureGrid);
                    }
                    if (hasResolved) {
                        mesesContainer.appendChild(resolvedTitle);
                        mesesContainer.appendChild(resolvedGrid);
                    }

                    if (hasPendings || hasFutures) {
                        quickActions.style.display = 'flex';
                        const firstPending = pendingGrid.querySelector('.mes-checkbox:not([disabled])');
                        if (firstPending) {
                            firstPending.checked = true;
                            firstPending.dispatchEvent(new Event('change'));
                        } else {
                            const firstFuture = futureGrid.querySelector('.mes-checkbox:not([disabled])');
                            if (firstFuture) {
                                firstFuture.checked = true;
                                firstFuture.dispatchEvent(new Event('change'));
                            }
                        }
                    } else {
                        quickActions.style.display = 'none';
                        mesesContainer.innerHTML = '<p style="color: var(--success); text-align: center; font-size: 0.85rem; margin: 1rem 0;">✅ Socio al día (Sin meses pendientes ni futuros)</p>';
                        alertInfo.style.display = 'none';
                    }
                })
                .catch(err => {
                    console.error('Error fetching socio cuotas:', err);
                    mesesContainer.innerHTML = '<p style="color: var(--danger); text-align: center; font-size: 0.85rem; margin: 1rem 0;">Error al conectar con el servidor</p>';
                });
        });
    }

    if (btnSelectPendientes) {
        btnSelectPendientes.addEventListener('click', function() {
            const checkboxes = mesesContainer.querySelectorAll('.mes-checkbox[data-estado="pendiente"]');
            checkboxes.forEach(cb => {
                if (!cb.checked) {
                    cb.checked = true;
                    cb.dispatchEvent(new Event('change'));
                }
            });
        });
    }

    if (btnClearSelection) {
        btnClearSelection.addEventListener('click', function() {
            const checkboxes = mesesContainer.querySelectorAll('.mes-checkbox:not([disabled])');
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    cb.checked = false;
                    cb.dispatchEvent(new Event('change'));
                }
            });
        });
    }

    if (esCondonadoCheck) {
        esCondonadoCheck.addEventListener('change', function() {
            const isCondonado = this.checked;
            const checkedLabels = mesesContainer.querySelectorAll('.mes-card-label.checked, .mes-card-label.checked-condonado');
            checkedLabels.forEach(label => {
                if (isCondonado) {
                    label.classList.remove('checked');
                    label.classList.add('checked-condonado');
                } else {
                    label.classList.remove('checked-condonado');
                    label.classList.add('checked');
                }
            });

            if (isCondonado) {
                justificacionContainer.style.display = 'block';
                justificacionInput.required = true;
                btnSubmitCuota.className = 'btn btn-warning';
                btnSubmitCuota.textContent = 'Registrar Condonación (Eximir)';
                alertInfo.style.backgroundColor = 'rgba(245, 158, 11, 0.05)';
                alertInfo.style.color = 'var(--warning)';
                alertInfo.style.borderLeftColor = 'var(--warning)';
            } else {
                justificacionContainer.style.display = 'none';
                justificacionInput.required = false;
                justificacionInput.value = '';
                btnSubmitCuota.className = 'btn btn-success';
                btnSubmitCuota.textContent = 'Registrar Cuota';
                alertInfo.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
                alertInfo.style.color = 'var(--success)';
                alertInfo.style.borderLeftColor = 'var(--success)';
            }
            updateCuotaMontoVisual();
        });
    }

    function updateCuotaMontoVisual() {
        const checkedCheckboxes = mesesContainer.querySelectorAll('.mes-checkbox:checked');
        
        if (checkedCheckboxes.length === 0) {
            alertInfo.style.display = 'none';
            return;
        }

        const isExempt = esCondonadoCheck && esCondonadoCheck.checked;
        
        if (isExempt) {
            visualMonto.textContent = '$0 (Condonado - ' + checkedCheckboxes.length + ' ' + (checkedCheckboxes.length === 1 ? 'mes' : 'meses') + ')';
            alertInfo.style.display = 'block';
        } else {
            let total = 0;
            checkedCheckboxes.forEach(cb => {
                total += parseInt(cb.dataset.monto || 0);
            });
            visualMonto.textContent = '$' + total.toLocaleString('es-CL') + ' CLP (' + checkedCheckboxes.length + ' ' + (checkedCheckboxes.length === 1 ? 'mes' : 'meses') + ' seleccionado' + (checkedCheckboxes.length === 1 ? '' : 's') + ')';
            alertInfo.style.display = 'block';
        }
    }

    const formCuota = document.querySelector('form[action*="registrar_pago_cuota"]');
    if (formCuota) {
        formCuota.addEventListener('submit', function(e) {
            if (!socioHidden.value) {
                e.preventDefault();
                alert('Debe seleccionar un socio de la lista.');
                return;
            }
            const checkedCheckboxes = mesesContainer.querySelectorAll('.mes-checkbox:checked');
            if (checkedCheckboxes.length === 0) {
                e.preventDefault();
                alert('Debe seleccionar al menos un mes para registrar el pago o condonación.');
            }
        });
    }


    // ==========================================
    // SECCIÓN 2: CATEGORÍAS DINÁMICAS OTROS MOV.
    // ==========================================
    const tipoSelect = document.getElementById('tipo');
    const catSelect = document.getElementById('categoria');

    const ingresosCategorias = <?php echo json_encode(array_map(static fn($c) => ['val' => $c->nombre, 'text' => $c->nombre . ' (Ingreso)'], $data['conceptos_ingreso'] ?? []), JSON_UNESCAPED_UNICODE); ?>;
    const egresosCategorias = <?php echo json_encode(array_map(static fn($c) => ['val' => $c->nombre, 'text' => $c->nombre . ' (Egreso)'], $data['conceptos_egreso'] ?? []), JSON_UNESCAPED_UNICODE); ?>;

    const finanzasFechaMin = <?php echo json_encode($fechaMin); ?>;
    const finanzasFechaMax = <?php echo json_encode($fechaMax); ?>;
    const finanzasMesesCerrados = <?php echo json_encode($rango['meses_cerrados'] ?? []); ?>;

    function validarFechaFinanzasInput(input) {
        if (!input || !input.value) return true;
        const val = input.value;
        if (val < finanzasFechaMin || val > finanzasFechaMax) {
            alert('La fecha debe estar entre ' + finanzasFechaMin.split('-').reverse().join('-') + ' y hoy.');
            input.value = val > finanzasFechaMax ? finanzasFechaMax : finanzasFechaMin;
            return false;
        }
        const mes = val.substring(0, 7);
        if (finanzasMesesCerrados.includes(mes)) {
            alert('No puede registrar movimientos en el mes ' + mes + ' porque ya fue cerrado.');
            input.value = finanzasFechaMax;
            return false;
        }
        return true;
    }

    document.querySelectorAll('.cb-finanzas-date').forEach(function(input) {
        input.addEventListener('change', function() { validarFechaFinanzasInput(this); });
    });

    [formCuota, document.querySelector('form[action*="registrar_transaccion"]')].forEach(function(form) {
        if (!form) return;
        form.addEventListener('submit', function(e) {
            const dateInput = form.querySelector('.cb-finanzas-date');
            if (dateInput && !validarFechaFinanzasInput(dateInput)) {
                e.preventDefault();
            }
        });
    });

    function populateCategorias() {
        if (!tipoSelect || !catSelect) return;
        
        const tipo = tipoSelect.value;
        catSelect.innerHTML = '';

        const list = (tipo === 'ingreso') ? ingresosCategorias : egresosCategorias;
        if (list.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- Configure conceptos en Conceptos de Caja --';
            catSelect.appendChild(opt);
            catSelect.required = false;
            return;
        }
        catSelect.required = true;
        list.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.val;
            opt.textContent = c.text;
            catSelect.appendChild(opt);
        });
    }

    if (tipoSelect) {
        tipoSelect.addEventListener('change', populateCategorias);
        populateCategorias();
    }

    // ==========================================
    // SECCIÓN 3: EDITAR / ELIMINAR TRANSACCIONES
    // ==========================================
    const modalEditTx = document.getElementById('modalEditTx');
    const formEditTx = document.getElementById('formEditTx');
    const editTxTipo = document.getElementById('edit_tx_tipo');
    const editTxCat = document.getElementById('edit_tx_categoria');
    const editTxGeneral = document.getElementById('edit_tx_general_fields');
    const editTxMontoWrap = document.getElementById('edit_tx_monto_wrap');
    const editTxMesWrap = document.getElementById('edit_tx_mes_wrap');
    const editTxSocioWrap = document.getElementById('edit_tx_socio_wrap');
    const editTxCuotaInfo = document.getElementById('edit_tx_cuota_info');
    const editTxDescLabel = document.getElementById('edit_tx_desc_label');

    function populateEditCategorias() {
        if (!editTxTipo || !editTxCat) return;
        const tipo = editTxTipo.value;
        editTxCat.innerHTML = '';
        const list = (tipo === 'ingreso') ? ingresosCategorias : egresosCategorias;
        list.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.val;
            opt.textContent = c.text;
            editTxCat.appendChild(opt);
        });
    }

    if (editTxTipo) {
        editTxTipo.addEventListener('change', populateEditCategorias);
    }

    function openEditModal(tx) {
        document.getElementById('edit_tx_id').value = tx.id;
        document.getElementById('edit_tx_fecha').value = tx.fecha;
        document.getElementById('edit_tx_descripcion').value = tx.descripcion || '';

        if (tx.es_cuota) {
            editTxGeneral.style.display = 'none';
            editTxMontoWrap.style.display = 'none';
            editTxMesWrap.style.display = 'block';
            editTxSocioWrap.style.display = 'none';
            editTxCuotaInfo.style.display = 'block';
            editTxCuotaInfo.innerHTML = '<strong>' + tx.categoria + '</strong> — Socio: ' + (tx.socio_label || '—') + ' (no editable)';
            document.getElementById('edit_tx_mes_pagado').value = tx.mes_pagado || '';
            document.getElementById('edit_tx_mes_pagado').required = true;
            document.getElementById('edit_tx_mes_pagado').disabled = false;
            document.getElementById('edit_tx_monto').required = false;
            editTxDescLabel.textContent = tx.categoria === 'Cuota Condonada' ? 'Justificación / Motivo *' : 'Descripción';
            const editSocioHidden = document.getElementById('edit_tx_socio_id');
            if (editSocioHidden) editSocioHidden.disabled = true;
        } else {
            editTxGeneral.style.display = 'grid';
            editTxMontoWrap.style.display = 'block';
            editTxMesWrap.style.display = 'none';
            editTxSocioWrap.style.display = 'block';
            editTxCuotaInfo.style.display = 'none';
            document.getElementById('edit_tx_mes_pagado').required = false;
            document.getElementById('edit_tx_mes_pagado').disabled = true;
            document.getElementById('edit_tx_monto').required = true;
            editTxDescLabel.textContent = 'Descripción / Justificación';
            const editSocioHidden = document.getElementById('edit_tx_socio_id');
            if (editSocioHidden) editSocioHidden.disabled = false;
            editTxTipo.value = tx.tipo;
            populateEditCategorias();
            editTxCat.value = tx.categoria;
            document.getElementById('edit_tx_monto').value = tx.monto;
            if (pickerEditTx) {
                if (tx.socio_id) {
                    const s = SOCIOS_DATA.find(x => x.id === tx.socio_id);
                    pickerEditTx.setValue(tx.socio_id, s ? s.label : (tx.socio_label || ''));
                } else {
                    pickerEditTx.clear();
                }
            }
        }

        modalEditTx.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    document.querySelectorAll('.btn-edit-tx').forEach(btn => {
        btn.addEventListener('click', function() {
            try {
                openEditModal(JSON.parse(this.dataset.tx));
            } catch (e) {
                console.error(e);
            }
        });
    });

    function closeEditModal() {
        modalEditTx.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    document.getElementById('btnCloseEditTx')?.addEventListener('click', closeEditModal);
    modalEditTx?.addEventListener('click', (e) => {
        if (e.target === modalEditTx) closeEditModal();
    });

    if (formEditTx) {
        formEditTx.addEventListener('submit', function(e) {
            const dateInput = formEditTx.querySelector('.cb-finanzas-date');
            if (dateInput && !validarFechaFinanzasInput(dateInput)) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>

