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

<div class="grid-2col">
    
    <!-- COLUMNA PRINCIPAL (IZQUIERDA): REUNIONES E INTERFAZ DE LISTA -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- CARD 1: LISTADO DE ASAMBLEAS -->
        <div class="card card-primary">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Asambleas y Reuniones Convocadas
            </h3>

            <?php if (empty($data['reuniones'])): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">Aún no ha convocado ninguna asamblea. Utilice el panel de la derecha.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha / Hora</th>
                                <th>Título Asamblea</th>
                                <th>Estado</th>
                                <th>Presentes</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['reuniones'] as $r): ?>
                                <tr style="<?php echo ($data['reunion_detalle'] && $data['reunion_detalle']->id == $r->id) ? 'background-color: rgba(99, 102, 241, 0.08); border-left: 3px solid var(--primary);' : ''; ?>">
                                    <td style="font-size: 0.8rem; font-family: monospace;"><?php echo date('d-m-Y H:i', strtotime($r->fecha_reunion)); ?></td>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($r->titulo); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($r->descripcion ?? 'Sin descripción'); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $r->estado === 'realizada' ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo htmlspecialchars($r->estado); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-weight: 700;">
                                        <?php if ($r->estado === 'realizada'): ?>
                                            <?php echo $r->presentes; ?> / <?php echo $r->total_registrados; ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo URLROOT; ?>/admin/asistencia/<?php echo $r->id; ?>" 
                                           class="btn btn-secondary btn-sm" 
                                           style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                            Pase Lista
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- CARD 2: PASE DE LISTA INTERACTIVO (SOLO SI SE SELECCIONA REUNIÓN) -->
        <?php if ($data['reunion_detalle']): ?>
            <div class="card card-success" id="listaAsistenciaSeccion">
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.05em;">Pase de Lista Digitalizado</div>
                    <h3 style="font-size: 1.35rem; margin-top: 0.25rem;">
                        <?php echo htmlspecialchars($data['reunion_detalle']->titulo); ?>
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                        Fecha: <?php echo date('d-m-Y \a \l\a\s H:i \h\r\s', strtotime($data['reunion_detalle']->fecha_reunion)); ?>
                    </p>
                </div>

                <form action="<?php echo URLROOT; ?>/admin/asistencia_guardar/<?php echo $data['reunion_detalle']->id; ?>" method="POST">
                    
                    <div style="max-height: 400px; overflow-y: auto; margin-bottom: 1.5rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: rgba(0,0,0,0.15);">
                        <table class="table" style="background: transparent;">
                            <thead>
                                <tr>
                                    <th style="width: 80px; text-align: center;">Asistió</th>
                                    <th>Vecino Socio</th>
                                    <th>RUT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['asistentes'] as $s): ?>
                                    <tr>
                                        <td style="text-align: center;">
                                            <input type="checkbox" 
                                                   name="asistencia[]" 
                                                   value="<?php echo $s->socio_id; ?>" 
                                                   style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--success);"
                                                   <?php echo $s->asistio == 1 ? 'checked' : ''; ?>>
                                        </td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($s->nombre); ?></td>
                                        <td style="font-family: monospace; font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($s->rut); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                        <a href="<?php echo URLROOT; ?>/admin/asistencia" class="btn btn-secondary btn-sm">Cancelar</a>
                        <button type="submit" class="btn btn-success btn-sm">
                            Guardar y Firmar Asistencia
                        </button>
                    </div>

                </form>
            </div>
        <?php endif; ?>

    </div>

    <!-- COLUMNA LATERAL (DERECHA): CONVOCAR REUNIÓN -->
    <div class="card card-warning" style="height: fit-content;">
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><polyline points="12 6 12 12 16 14"></polyline></svg>
            Convocar Asamblea
        </h3>

        <form action="<?php echo URLROOT; ?>/admin/reunion_crear" method="POST">
            
            <div class="form-group">
                <label for="titulo" class="form-label">Título de la Asamblea *</label>
                <input type="text" name="titulo" id="titulo" class="form-control" placeholder="Ej: Asamblea Ordinaria Mayo 2026" required>
            </div>

            <div class="form-group">
                <label for="fecha_reunion" class="form-label">Fecha y Hora Convocatoria *</label>
                <input type="datetime-local" name="fecha_reunion" id="fecha_reunion" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="descripcion" class="form-label">Tabla / Temas a tratar</label>
                <textarea name="descripcion" id="descripcion" class="form-control" placeholder="Ej: 1. Aprobación acta anterior. 2. Ajuste cuota. 3. Votación portón de seguridad." style="height: 120px; resize: none;"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Publicar Convocatoria
            </button>
        </form>
    </div>

</div>

<!-- Autodesplazarse a la lista si se selecciona una reunión -->
<?php if ($data['reunion_detalle']): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('listaAsistenciaSeccion');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
});
</script>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
