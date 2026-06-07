<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
function cbFinanzasSocioLabel($socio) {
    $label = ($socio->nombre ?? '') . ' (' . ($socio->rut ?? '') . ')';
    if (($socio->status ?? '') === 'prevalidar') {
        $label .= ' — Alta provisional';
    }
    return $label;
}
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
                    <select name="socio_id" id="socio_id" class="form-control" required>
                        <option value="">-- Seleccionar Socio --</option>
                        <?php foreach ($data['socios'] as $socio): ?>
                            <option value="<?php echo $socio->id; ?>" data-prevalidar="<?php echo ($socio->status ?? '') === 'prevalidar' ? '1' : '0'; ?>"><?php echo htmlspecialchars(cbFinanzasSocioLabel($socio)); ?></option>
                        <?php endforeach; ?>
                    </select>
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
                    <select name="socio_id" id="asociar_socio_id" class="form-control">
                        <option value="">-- No asociar (Movimiento General) --</option>
                        <?php foreach ($data['socios'] as $socio): ?>
                            <option value="<?php echo $socio->id; ?>"><?php echo htmlspecialchars(cbFinanzasSocioLabel($socio)); ?></option>
                        <?php endforeach; ?>
                    </select>
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
            Historial de Transacciones (Flujo de Caja)
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
            <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Categoría / Detalle</th>
                            <th>Monto</th>
                            <th>Comprobante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['transacciones'] as $t): ?>
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
                                            echo 'Socio: ' . htmlspecialchars($t->socio_nombre ?? 'Desafiliado') . ' (' . htmlspecialchars($t->mes_pagado) . ')';
                                        } else {
                                            $detalles = htmlspecialchars($t->descripcion ?? 'Sin detalle');
                                            if (!empty($t->socio_nombre)) {
                                                $detalles .= ' | <span style="color:var(--primary)">Asoc: ' . htmlspecialchars($t->socio_nombre) . '</span>';
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- JavaScript de Motor de Cuotas e Interacción Dinámica -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // SECCIÓN 1: PAGO DE CUOTAS Y CONDONACIÓN
    // ==========================================
    const socioSelect = document.getElementById('socio_id');
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

    if (socioSelect && mesesContainer) {
        const syncProvisionalAlert = () => {
            if (!socioProvisionalAlert) return;
            const opt = socioSelect.options[socioSelect.selectedIndex];
            const isProv = opt && opt.dataset.prevalidar === '1';
            socioProvisionalAlert.style.display = isProv ? 'block' : 'none';
        };
        socioSelect.addEventListener('change', function() {
            syncProvisionalAlert();
            const socioId = this.value;
            
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
        // Cargar por defecto
        populateCategorias();
    }
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>

