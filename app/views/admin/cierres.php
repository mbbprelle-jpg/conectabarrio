<?php require_once APPROOT . '/views/layouts/header.php'; ?>

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

<!-- Fila superior: Resumen del mes seleccionado -->
<div class="card card-primary" style="margin-bottom: 2rem; background: radial-gradient(100% 100% at 0% 0%, rgba(99,102,241,0.05) 0%, transparent 100%), var(--bg-card);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="font-size: 1.4rem; font-family: var(--font-heading); margin-bottom: 0.25rem; color: var(--primary);">Resumen Mensual Proyectado</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Visualice el flujo del mes seleccionado antes de realizar el cierre definitivo.</p>
        </div>
        
        <div>
            <label for="mes_selector" class="form-label" style="display: inline-block; margin-right: 0.5rem; vertical-align: middle;">Mes a Consultar:</label>
            <select id="mes_selector" class="form-control" style="display: inline-block; width: auto; vertical-align: middle; background: var(--bg-input);">
                <?php if (empty($data['meses_disponibles'])): ?>
                    <option value="<?php echo htmlspecialchars($data['mes_seleccionado']); ?>">
                        <?php 
                        $parts = explode('-', $data['mes_seleccionado']);
                        $meses = ['01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'];
                        echo htmlspecialchars(($meses[$parts[1]] ?? 'Mes') . ' ' . $parts[0]);
                        ?>
                    </option>
                <?php else: ?>
                    <?php 
                    $meses = ['01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'];
                    ?>
                    <?php foreach ($data['meses_disponibles'] as $m): ?>
                        <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $m === $data['mes_seleccionado'] ? 'selected' : ''; ?>>
                            <?php 
                            $parts = explode('-', $m);
                            echo htmlspecialchars(($meses[$parts[1]] ?? 'Mes') . ' ' . $parts[0]);
                            ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <!-- Panel de Tarjetas de Balance -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; margin-top: 1.5rem;">
        <!-- Saldo Mes Anterior -->
        <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-sm); padding: 1.25rem; text-align: center; backdrop-filter: blur(10px);">
            <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: bold; letter-spacing: 0.05em;">Saldo Mes Anterior</div>
            <strong id="card_saldo_anterior" style="color: var(--text-main); font-size: 1.7rem; font-family: var(--font-heading); display: block; margin-top: 0.5rem;">$<?php echo number_format($data['resumen_mes']['saldo_anterior'], 0, ',', '.'); ?></strong>
        </div>
        <!-- Ingresos del Mes -->
        <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.15); border-radius: var(--radius-sm); padding: 1.25rem; text-align: center; backdrop-filter: blur(10px);">
            <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: bold; letter-spacing: 0.05em;">Ingresos del Mes</div>
            <strong style="color: var(--success); font-size: 1.7rem; font-family: var(--font-heading); display: block; margin-top: 0.5rem;">+$<?php echo number_format($data['resumen_mes']['ingresos'], 0, ',', '.'); ?></strong>
        </div>
        <!-- Egresos del Mes -->
        <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.15); border-radius: var(--radius-sm); padding: 1.25rem; text-align: center; backdrop-filter: blur(10px);">
            <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: bold; letter-spacing: 0.05em;">Egresos del Mes</div>
            <strong style="color: var(--danger); font-size: 1.7rem; font-family: var(--font-heading); display: block; margin-top: 0.5rem;">-$<?php echo number_format($data['resumen_mes']['egresos'], 0, ',', '.'); ?></strong>
        </div>
        <!-- Saldo Final (Saldo Anterior + Ingresos - Egresos) -->
        <div style="background: rgba(6, 182, 212, 0.05); border: 1px solid rgba(6, 182, 212, 0.15); border-radius: var(--radius-sm); padding: 1.25rem; text-align: center; backdrop-filter: blur(10px);">
            <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: bold; letter-spacing: 0.05em;">Saldo Final</div>
            <strong id="card_saldo_final" style="color: <?php echo $data['resumen_mes']['saldo_final'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; font-size: 1.7rem; font-family: var(--font-heading); display: block; margin-top: 0.5rem;">
                $<?php echo number_format($data['resumen_mes']['saldo_final'], 0, ',', '.'); ?>
            </strong>
        </div>
    </div>
</div>

<div class="grid-2col">
    <!-- Columna Izquierda: Cerrar Mes y Categorías del Mes -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <!-- CARD: CERRAR MES -->
        <div class="card card-success">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Realizar Cierre de Mes
            </h3>
            
            <?php if (!empty($data['mes_previo_sin_cerrar'])): ?>
                <!-- Alerta: Mes Previo Sin Cerrar (Bloqueo Contable) -->
                <div style="background: rgba(245, 158, 11, 0.05); border: 1px dashed var(--warning); border-radius: var(--radius-sm); padding: 1.5rem; text-align: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 0.75rem;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <h4 style="color: var(--warning); font-size: 1.15rem; font-family: var(--font-heading); margin-bottom: 0.5rem;">Cierre Bloqueado Temporalmente</h4>
                    <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; margin-bottom: 1rem;">
                        Para mantener la consistencia de los libros contables, no está permitido cerrar el mes <strong><?php 
                        $parts = explode('-', $data['mes_seleccionado']);
                        echo htmlspecialchars(($meses[$parts[1]] ?? 'Mes') . ' ' . $parts[0]);
                        ?></strong> sin antes haber cerrado el mes anterior:
                    </p>
                    <strong style="display: block; font-size: 1.25rem; color: var(--text-main); margin: 0.75rem 0; font-family: var(--font-heading);">
                        <?php 
                        $partsPrev = explode('-', $data['mes_previo_sin_cerrar']);
                        echo htmlspecialchars(($meses[$partsPrev[1]] ?? 'Mes') . ' ' . $partsPrev[0]);
                        ?>
                    </strong>
                    <a href="<?php echo URLROOT; ?>/admin/cierres?mes=<?php echo htmlspecialchars($data['mes_previo_sin_cerrar']); ?>" class="btn btn-warning btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; text-decoration: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><path d="M12 6v6l4 2"></path></svg>
                        Ir a cerrar mes pendiente
                    </a>
                </div>
            
            <?php elseif ($data['es_futuro_o_mes_en_curso']): ?>
                <!-- Alerta: Mes En Curso / Futuro (Bloqueo) -->
                <div style="background: rgba(6, 182, 212, 0.05); border: 1px dashed var(--info); border-radius: var(--radius-sm); padding: 1.5rem; text-align: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--info)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 0.75rem;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <h4 style="color: var(--info); font-size: 1.15rem; font-family: var(--font-heading); margin-bottom: 0.5rem;">Periodo Contable en Curso</h4>
                    <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5;">
                        El mes de <strong><?php 
                        $parts = explode('-', $data['mes_seleccionado']);
                        echo htmlspecialchars(($meses[$parts[1]] ?? 'Mes') . ' ' . $parts[0]);
                        ?></strong> se encuentra actualmente activo. Solo está permitido realizar el cierre financiero de meses que ya hayan completado su ciclo calendario.
                    </p>
                </div>
                
            <?php elseif (empty($data['meses_disponibles'])): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 1.5rem 0;">
                    No hay meses disponibles para cerrar en este momento. Todos los periodos anteriores han sido bloqueados y auditados correctamente.
                </p>
            <?php else: ?>
                <form id="formCierreMensual" action="<?php echo URLROOT; ?>/admin/cerrar_mes" method="POST">
                    <input type="hidden" name="mes" value="<?php echo htmlspecialchars($data['mes_seleccionado']); ?>">
                    
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 1rem; margin-bottom: 1.5rem;">
                        <span style="color: var(--text-muted); font-size: 0.85rem; display: block;">Periodo a Congelar:</span>
                        <strong style="font-size: 1.15rem; color: var(--success); font-family: var(--font-heading); display: block; margin-top: 0.25rem;">
                            <?php 
                            $parts = explode('-', $data['mes_seleccionado']);
                            echo htmlspecialchars(($meses[$parts[1]] ?? 'Mes') . ' ' . $parts[0]);
                            ?>
                        </strong>
                        <span style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.5rem; line-height: 1.4;">
                            * Al cerrar, se guardarán de forma permanente los ingresos de <strong>+$<?php echo number_format($data['resumen_mes']['ingresos'], 0, ',', '.'); ?></strong> y egresos de <strong>-$<?php echo number_format($data['resumen_mes']['egresos'], 0, ',', '.'); ?></strong>.
                        </span>
                    </div>

                    <?php if ($data['es_primer_cierre']): ?>
                        <!-- Entrada Saldo Inicial Manual (Solo primer cierre) -->
                        <div class="form-group" style="background: rgba(99,102,241,0.05); border: 1px solid rgba(99,102,241,0.15); padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem;">
                            <label for="saldo_anterior_manual" class="form-label" style="color: var(--primary); font-weight: bold; font-family: var(--font-heading);">
                                Saldo Inicial que viene del Mes Anterior ($) *
                            </label>
                            <input type="number" name="saldo_anterior_manual" id="saldo_anterior_manual" class="form-control" style="background: var(--bg-main); border-color: rgba(99,102,241,0.3); font-size: 1.1rem; font-weight: bold; color: var(--text-main);" placeholder="Ej: 100000" min="0" value="0" required>
                            <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 0.25rem; line-height: 1.3;">
                                ⚠️ <strong>Primer cierre histórico de la organización</strong>. Escriba manualmente el monto contable total con el que cuenta la junta antes de realizar este primer cierre. En los meses posteriores, el saldo se calculará automáticamente.
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="comentario" class="form-label">Comentario de Cierre / Mensaje para Socios *</label>
                        <textarea name="comentario" id="comentario" class="form-control" rows="4" placeholder="Ej: Estimados socios, este mes logramos recaudar un 90% de las cuotas e invertimos en el pintado de la fachada de la sede social. Quedamos con saldo positivo." required></textarea>
                        <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 0.25rem;">
                            Este mensaje se incluirá en el cuerpo del correo del balance mensual enviado a todos los socios activos.
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 1rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: middle;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        <span style="vertical-align: middle;">Efectuar Cierre Financiero del Mes</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php 
        $ingresos_detalle = [];
        $egresos_detalle = [];
        if (!empty($data['resumen_mes']['transacciones'])) {
            foreach ($data['resumen_mes']['transacciones'] as $t) {
                if ($t->tipo === 'ingreso') {
                    $ingresos_detalle[] = $t;
                } elseif ($t->tipo === 'egreso') {
                    $egresos_detalle[] = $t;
                }
            }
        }
        $desglose_egresos = [];
        if (!empty($data['resumen_mes']['desglose'])) {
            foreach ($data['resumen_mes']['desglose'] as $d) {
                if ($d->tipo === 'egreso') {
                    $desglose_egresos[] = $d;
                }
            }
        }
        ?>

        <!-- CARD: DETALLE DE INGRESOS DEL MES -->
        <div class="card card-success" style="margin-bottom: 2rem;">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--success);"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                1. Detalle de Ingresos del Mes
            </h3>
            <?php if (empty($ingresos_detalle)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No se registran ingresos en este periodo.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Socio / Vecino</th>
                                <th>Categoría</th>
                                <th>Descripción</th>
                                <th style="text-align: right;">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_ingresos_calc = 0;
                            foreach ($ingresos_detalle as $t): 
                                $total_ingresos_calc += (int)$t->monto;
                            ?>
                                <tr>
                                    <td style="font-size: 0.8rem; white-space: nowrap;"><?php echo date('d-m-Y', strtotime($t->fecha)); ?></td>
                                    <td style="font-weight: 600; font-size: 0.85rem; color: var(--text-main);"><?php echo !empty($t->socio_nombre) ? htmlspecialchars($t->socio_nombre) : 'N/A'; ?></td>
                                    <td style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($t->categoria); ?></td>
                                    <td style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($t->descripcion); ?></td>
                                    <td style="font-weight: 700; text-align: right; white-space: nowrap; color: var(--success);">
                                        +$<?php echo number_format($t->monto, 0, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 2px solid var(--success); font-weight: bold; background: rgba(16, 185, 129, 0.05);">
                                <td colspan="4" style="text-align: right; font-size: 0.85rem; color: var(--text-main); padding: 0.75rem;">Total Ingresos:</td>
                                <td style="text-align: right; font-size: 0.95rem; color: var(--success); padding: 0.75rem; white-space: nowrap;">
                                    +$<?php echo number_format($total_ingresos_calc, 0, ',', '.'); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- CARD: DETALLE DE EGRESOS DEL MES -->
        <div class="card card-primary" style="margin-bottom: 2rem; border-left: 4px solid var(--danger);">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--danger);"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg>
                2. Detalle de Egresos del Mes
            </h3>
            <?php if (empty($egresos_detalle)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No se registran egresos en este periodo.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Categoría</th>
                                <th>Descripción</th>
                                <th style="text-align: right;">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_egresos_calc = 0;
                            foreach ($egresos_detalle as $t): 
                                $total_egresos_calc += (int)$t->monto;
                            ?>
                                <tr>
                                    <td style="font-size: 0.8rem; white-space: nowrap;"><?php echo date('d-m-Y', strtotime($t->fecha)); ?></td>
                                    <td style="font-weight: 600; font-size: 0.85rem; color: var(--text-main);"><?php echo htmlspecialchars($t->categoria); ?></td>
                                    <td style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($t->descripcion); ?></td>
                                    <td style="font-weight: 700; text-align: right; white-space: nowrap; color: var(--danger);">
                                        -$<?php echo number_format($t->monto, 0, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 2px solid var(--danger); font-weight: bold; background: rgba(239, 68, 68, 0.05);">
                                <td colspan="3" style="text-align: right; font-size: 0.85rem; color: var(--text-main); padding: 0.75rem;">Total Egresos:</td>
                                <td style="text-align: right; font-size: 0.95rem; color: var(--danger); padding: 0.75rem; white-space: nowrap;">
                                    -$<?php echo number_format($total_egresos_calc, 0, ',', '.'); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- CARD: RESUMEN DE EGRESOS POR CATEGORÍA -->
        <div class="card card-primary" style="margin-bottom: 2rem; border-left: 4px solid var(--primary);">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                3. Resumen de Gastos por Categoría
            </h3>
            <?php if (empty($desglose_egresos)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No se registran gastos por categoría en este periodo.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Concepto / Categoría</th>
                                <th style="text-align: center;">Movimientos</th>
                                <th style="text-align: right;">Monto Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_desglose_calc = 0;
                            foreach ($desglose_egresos as $d): 
                                $total_desglose_calc += (int)$d->total_monto;
                            ?>
                                <tr>
                                    <td style="font-weight: 600; font-size: 0.85rem; color: var(--text-main);"><?php echo htmlspecialchars($d->categoria); ?></td>
                                    <td style="font-size: 0.8rem; color: var(--text-muted); text-align: center;"><?php echo $d->cantidad; ?></td>
                                    <td style="font-weight: 700; text-align: right; white-space: nowrap; color: var(--danger);">
                                        -$<?php echo number_format($d->total_monto, 0, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 2px solid var(--primary); font-weight: bold; background: rgba(99, 102, 241, 0.05);">
                                <td colspan="2" style="text-align: right; font-size: 0.85rem; color: var(--text-main); padding: 0.75rem;">Total Consolidado Egresos:</td>
                                <td style="text-align: right; font-size: 0.95rem; color: var(--danger); padding: 0.75rem; white-space: nowrap;">
                                    -$<?php echo number_format($total_desglose_calc, 0, ',', '.'); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Columna Derecha: Historial de Cierres realizados -->
    <div class="card card-warning" style="display: flex; flex-direction: column; gap: 1.5rem;">
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><path d="M12 6v6l4 2"></path></svg>
            Cierres Mensuales Históricos
        </h3>
        
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">
            Aquí se encuentran los periodos contables cerrados de su organización. Puede auditar sus cifras y enviar el balance mensual por correo electrónico a todos los socios vecinos.
        </p>
        
        <?php if (empty($data['cierres'])): ?>
            <p style="color: var(--text-muted); text-align: center; padding: 3rem 0;">
                No se registran cierres de mes efectuados en esta Junta de Vecinos aún.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th>Flujo de Caja</th>
                            <th>Auditoría / Envío</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['cierres'] as $c): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--text-main); font-size: 0.9rem;">
                                        <?php 
                                        $parts = explode('-', $c->mes);
                                        echo htmlspecialchars(($meses[$parts[1]] ?? 'Mes') . ' ' . $parts[0]);
                                        ?>
                                    </strong>
                                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.15rem;" title="Cerrado el: <?php echo date('d-m-Y H:i:s', strtotime($c->fecha_cierre)); ?>">
                                        Cerró: <?php echo htmlspecialchars($c->admin_nombre); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.15rem;">
                                        <div><span style="font-weight: 600;">Ant:</span> $<?php echo number_format($c->saldo_anterior, 0, ',', '.'); ?></div>
                                        <div style="color: var(--success); font-weight: 500;"><span style="font-weight: 600;">Ing:</span> +$<?php echo number_format($c->ingresos, 0, ',', '.'); ?></div>
                                        <div style="color: var(--danger); font-weight: 500;"><span style="font-weight: 600;">Egr:</span> -$<?php echo number_format($c->egresos, 0, ',', '.'); ?></div>
                                        <div style="font-weight: 700; color: <?php echo $c->saldo_final >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; border-top: 1px solid var(--border-color); margin-top: 0.15rem; padding-top: 0.15rem; font-size: 0.78rem;">
                                            <span style="font-weight: 800;">Fin:</span> $<?php echo number_format($c->saldo_final, 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <!-- Comentario en popup/title -->
                                    <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-style: italic; margin-bottom: 0.4rem;" title="<?php echo htmlspecialchars($c->comentario); ?>">
                                        "<?php echo htmlspecialchars($c->comentario); ?>"
                                    </div>
                                    
                                    <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                                        <!-- Botón Ver Boletín (Nueva pestaña) -->
                                        <a href="<?php echo URLROOT; ?>/admin/visualizar_boletin/<?php echo $c->id; ?>" target="_blank" class="btn btn-info btn-sm" style="width: 100%; font-size: 0.72rem; padding: 0.35rem 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.3rem; text-decoration: none; background: rgba(6, 182, 212, 0.15); border: 1px solid rgba(6, 182, 212, 0.3); color: #06b6d4;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            Ver Boletín
                                        </a>

                                        <!-- Botón Enviar Balance por Correo -->
                                        <form action="<?php echo URLROOT; ?>/admin/enviar_balance_email/<?php echo $c->id; ?>" method="POST" class="confirm-action" data-confirm-message="¿Está seguro de que desea enviar este balance mensual vía correo electrónico a TODOS los socios activos de su comunidad?">
                                            <button type="submit" class="btn <?php echo $c->enviado_correo ? 'btn-secondary' : 'btn-primary'; ?> btn-sm" style="width: 100%; font-size: 0.72rem; padding: 0.35rem 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.3rem;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                                <?php echo $c->enviado_correo ? 'Reenviar Balance' : 'Enviar Balance'; ?>
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <?php if ($c->enviado_correo): ?>
                                        <div style="color: var(--success); font-size: 0.68rem; text-align: center; margin-top: 0.25rem; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 0.2rem;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            Transmitido por email
                                        </div>
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

<!-- Modal de Confirmación Glassmorphic de Cierre de Mes -->
<div id="cierreModal" class="glass-modal-overlay">
    <div class="glass-modal-container">
        <!-- Botón cerrar modal -->
        <button id="closeModalBtn" type="button" style="position: absolute; top: 1.25rem; right: 1.25rem; background: none; border: none; color: var(--text-muted); cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='var(--text-main)'" onmouseout="this.style.color='var(--text-muted)'">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>

        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 64px; height: 64px; background: rgba(6, 182, 212, 0.1); border: 1px solid var(--primary); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: var(--primary); margin-bottom: 1rem; box-shadow: 0 0 15px rgba(6, 182, 212, 0.2);">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <h3 style="font-family: var(--font-heading); color: var(--text-main); font-size: 1.6rem; margin-bottom: 0.5rem;">Confirmar Cierre de Cuentas</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Revise atentamente las cifras consolidadas del periodo antes de congelar las transacciones.</p>
        </div>

        <!-- Tabla de balances resumida -->
        <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: var(--radius-sm); padding: 1.5rem; margin-bottom: 2rem; display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.75rem;">
                <span style="color: var(--text-muted);">Periodo a Cerrar:</span>
                <strong id="modal_mes_nombre" style="color: var(--primary); font-family: var(--font-heading);">Mes</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem;">
                <span style="color: var(--text-muted);">Saldo Anterior (Arrastrado):</span>
                <span id="modal_saldo_anterior" style="color: var(--text-main); font-weight: bold; font-family: var(--font-heading);">$0</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem;">
                <span style="color: var(--text-muted);">Ingresos del Mes (+):</span>
                <span id="modal_ingresos" style="color: var(--success); font-weight: bold; font-family: var(--font-heading);">+$0</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem;">
                <span style="color: var(--text-muted);">Egresos del Mes (-):</span>
                <span id="modal_egresos" style="color: var(--danger); font-weight: bold; font-family: var(--font-heading);">-$0</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 1.15rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.75rem;">
                <strong style="color: var(--text-main);">Saldo Final Neto:</strong>
                <strong id="modal_saldo_final" style="color: var(--success); font-family: var(--font-heading);">$0</strong>
            </div>
        </div>

        <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.15); border-radius: var(--radius-sm); padding: 1rem; margin-bottom: 2rem; display: flex; align-items: flex-start; gap: 0.75rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 0.1rem;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <p style="color: rgba(239, 68, 68, 0.9); font-size: 0.78rem; line-height: 1.4; margin: 0;">
                <strong>ADVERTENCIA:</strong> Esta acción es <strong>IRREVERSIBLE</strong>. Una vez congelado el mes, no se podrán modificar ni añadir transacciones (ingresos/egresos) a este periodo.
            </p>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button id="cancelCierreBtn" type="button" class="btn btn-secondary" style="padding: 0.75rem 1.5rem; font-size: 0.9rem;">
                Cancelar
            </button>
            <button id="confirmCierreBtn" type="button" class="btn btn-success" style="padding: 0.75rem 1.75rem; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.5rem; background: var(--success); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
                Congelar Cuentas
            </button>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}
.glass-modal-overlay {
    display: none; 
    position: fixed; 
    top: 0; 
    left: 0; 
    width: 100%; 
    height: 100%; 
    background: rgba(10, 10, 12, 0.65); 
    backdrop-filter: blur(15px); 
    z-index: 9999; 
    align-items: center; 
    justify-content: center; 
    opacity: 0; 
    transition: opacity 0.3s ease;
}
.glass-modal-container {
    background: rgba(20, 20, 25, 0.75); 
    border: 1px solid rgba(255, 255, 255, 0.08); 
    border-radius: var(--radius-md); 
    padding: 2.5rem; 
    width: 90%; 
    max-width: 550px; 
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 40px rgba(6, 182, 212, 0.1); 
    backdrop-filter: blur(25px); 
    transform: scale(0.9); 
    transition: transform 0.3s ease; 
    position: relative;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Selector de meses - Redirección
    const mesSelector = document.getElementById('mes_selector');
    if (mesSelector) {
        mesSelector.addEventListener('change', function() {
            window.location.href = '<?php echo URLROOT; ?>/admin/cierres?mes=' + this.value;
        });
    }

    // 2. Cálculos reactivos y formato chileno para saldo anterior manual
    const ingresosMes = <?php echo (int)($data['resumen_mes']['ingresos'] ?? 0); ?>;
    const egresosMes = <?php echo (int)($data['resumen_mes']['egresos'] ?? 0); ?>;
    const saldoNetoMes = <?php echo (int)($data['resumen_mes']['saldo_neto'] ?? 0); ?>;
    const esPrimerCierre = <?php echo $data['es_primer_cierre'] ? 'true' : 'false'; ?>;
    const saldoAnteriorInicial = <?php echo (int)($data['resumen_mes']['saldo_anterior'] ?? 0); ?>;
    
    // Obtener nombres de meses de PHP
    const mesNombreSeleccionado = "<?php 
        $parts = explode('-', $data['mes_seleccionado']);
        echo htmlspecialchars(($meses[$parts[1]] ?? 'Mes') . ' ' . $parts[0]);
    ?>";

    const saldoAnteriorInput = document.getElementById('saldo_anterior_manual');
    const cardSaldoAnterior = document.getElementById('card_saldo_anterior');
    const cardSaldoFinal = document.getElementById('card_saldo_final');

    function formatCLP(val) {
        return '$' + new Intl.NumberFormat('es-CL').format(val);
    }

    if (saldoAnteriorInput && cardSaldoAnterior && cardSaldoFinal) {
        saldoAnteriorInput.addEventListener('input', function() {
            let val = parseInt(this.value) || 0;
            if (val < 0) {
                val = 0;
                this.value = 0;
            }
            
            let finalVal = val + saldoNetoMes;
            
            cardSaldoAnterior.textContent = formatCLP(val);
            cardSaldoFinal.textContent = formatCLP(finalVal);
            
            if (finalVal >= 0) {
                cardSaldoFinal.style.color = 'var(--success)';
            } else {
                cardSaldoFinal.style.color = 'var(--danger)';
            }
        });
    }

    // 3. Interceptar submit de Cierre Mensual y mostrar Modal Glassmorphic
    const form = document.getElementById('formCierreMensual');
    const modalOverlay = document.getElementById('cierreModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelCierreBtn = document.getElementById('cancelCierreBtn');
    const confirmCierreBtn = document.getElementById('confirmCierreBtn');

    const modalMesNombre = document.getElementById('modal_mes_nombre');
    const modalSaldoAnterior = document.getElementById('modal_saldo_anterior');
    const modalIngresos = document.getElementById('modal_ingresos');
    const modalEgresos = document.getElementById('modal_egresos');
    const modalSaldoFinal = document.getElementById('modal_saldo_final');

    function openModal() {
        modalOverlay.style.display = 'flex';
        setTimeout(() => {
            modalOverlay.style.opacity = '1';
            modalOverlay.querySelector('.glass-modal-container').style.transform = 'scale(1)';
        }, 10);
    }

    function closeModal() {
        modalOverlay.style.opacity = '0';
        modalOverlay.querySelector('.glass-modal-container').style.transform = 'scale(0.9)';
        setTimeout(() => {
            modalOverlay.style.display = 'none';
        }, 300);
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Detener envío por defecto
            
            let ant = esPrimerCierre && saldoAnteriorInput ? (parseInt(saldoAnteriorInput.value) || 0) : saldoAnteriorInicial;
            let fin = ant + saldoNetoMes;
            
            // Poblar valores formateados
            if (modalMesNombre) modalMesNombre.textContent = mesNombreSeleccionado;
            if (modalSaldoAnterior) modalSaldoAnterior.textContent = formatCLP(ant);
            if (modalIngresos) modalIngresos.textContent = '+' + formatCLP(ingresosMes);
            if (modalEgresos) modalEgresos.textContent = '-' + formatCLP(egresosMes);
            if (modalSaldoFinal) {
                modalSaldoFinal.textContent = formatCLP(fin);
                if (fin >= 0) {
                    modalSaldoFinal.style.color = 'var(--success)';
                } else {
                    modalSaldoFinal.style.color = 'var(--danger)';
                }
            }
            
            openModal();
        });
    }

    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (cancelCierreBtn) cancelCierreBtn.addEventListener('click', closeModal);
    
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });
    }

    if (confirmCierreBtn && form) {
        confirmCierreBtn.addEventListener('click', function() {
            confirmCierreBtn.disabled = true;
            confirmCierreBtn.innerHTML = `
                <svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; display: inline-block; vertical-align: middle;"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
                Congelando...
            `;
            form.submit(); // Enviar formulario de verdad
        });
    }
});
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
