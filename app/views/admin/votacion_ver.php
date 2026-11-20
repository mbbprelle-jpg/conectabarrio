<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php $v = $data['votacion']; ?>

<?php if (!empty($data['success'])): ?><div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div><?php endif; ?>
<?php if (!empty($data['error'])): ?><div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div><?php endif; ?>

<div class="card card-primary" style="margin-bottom:1.5rem;">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <p style="font-size:0.85rem;color:var(--text-muted);margin:0;"><?php echo nl2br(htmlspecialchars($v->descripcion ?? '')); ?></p>
            <p style="font-size:0.8rem;color:var(--text-muted);margin-top:0.75rem;">
                <?php
                $visLabel = [
                    'al_votar' => 'el socio ve los % después de responder',
                    'todos' => 'todos los electores ven los %',
                    'directiva' => 'los % solo los ve la directiva',
                ][$v->resultados_visibilidad ?? 'directiva'] ?? 'resultados reservados';
                ?>
                <?php echo date('d/m/Y H:i', strtotime($v->fecha_inicio)); ?> — <?php echo date('d/m/Y H:i', strtotime($v->fecha_fin)); ?>
                (hora de Chile, ahora <?php echo date('d/m/Y H:i'); ?>)
                · <?php echo ($v->participacion ?? '') === 'abierta' ? 'Abierta al público' : ('Audiencia: ' . htmlspecialchars(str_replace('_', ' ', $v->audiencia_tipo))); ?>
                <?php if (!empty($v->inscribe_ninos)): ?> · Inscripción de niños<?php endif; ?>
                <?php if (!empty($v->bcc_correo)): ?> · Copia oculta a <?php echo htmlspecialchars((string)$v->bcc_correo); ?><?php endif; ?>
                · <?php echo htmlspecialchars($visLabel); ?>
            </p>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <?php if ($v->estado === 'borrador'): ?>
            <form action="<?php echo URLROOT; ?>/admin/votacion_publicar" method="POST"><input type="hidden" name="votacion_id" value="<?php echo (int)$v->id; ?>"><button class="btn btn-primary btn-sm">Activar</button></form>
            <?php endif; ?>
            <?php if ($v->estado === 'activa'): ?>
            <form action="<?php echo URLROOT; ?>/admin/votacion_cerrar" method="POST"><input type="hidden" name="votacion_id" value="<?php echo (int)$v->id; ?>"><button class="btn btn-secondary btn-sm">Cerrar ahora</button></form>
            <a href="<?php echo URLROOT; ?>/admin/votacion_votar/<?php echo (int)$v->id; ?>" class="btn btn-success btn-sm">Participar</a>
            <?php endif; ?>
            <a href="<?php echo URLROOT; ?>/admin/votaciones" class="btn btn-secondary btn-sm">Volver</a>
        </div>
    </div>
</div>

<div class="card card-primary" style="margin-bottom:1.5rem;">
    <h3 class="card-title">Horario de la consulta</h3>
    <p style="font-size:0.85rem;color:var(--text-muted);">Las fechas usan la hora de Chile. Si el cierre queda después de ahora, la consulta vuelve a abrirse y se conservan los votos ya emitidos.</p>
    <form action="<?php echo URLROOT; ?>/admin/votacion_horario" method="POST" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;">
        <input type="hidden" name="votacion_id" value="<?php echo (int)$v->id; ?>">
        <div class="form-group" style="margin:0;min-width:220px;">
            <label class="form-label">Inicio</label>
            <input type="datetime-local" name="fecha_inicio" class="form-control" required
                value="<?php echo date('Y-m-d\TH:i', strtotime($v->fecha_inicio)); ?>">
        </div>
        <div class="form-group" style="margin:0;min-width:220px;">
            <label class="form-label">Cierre</label>
            <input type="datetime-local" name="fecha_fin" class="form-control" required
                value="<?php echo date('Y-m-d\TH:i', strtotime($v->fecha_fin)); ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Guardar horario</button>
    </form>
</div>

<div class="card card-warning" style="margin-bottom:1.5rem;">
    <h3 class="card-title"><?php echo ($v->participacion ?? '') === 'abierta' ? 'Enlace de la encuesta abierta' : 'Enlace para electores'; ?></h3>
    <p style="font-size:0.85rem;color:var(--text-muted);"><?php echo ($v->participacion ?? '') === 'abierta'
        ? 'Comparta este enlace por correo o WhatsApp. Quien es socio y ya inició sesión confirma su nombre, correo y teléfono. Quien no es socio completa nombre y apellidos, correo y teléfono, y puede participar igual.'
        : 'Comparta este enlace por correo o WhatsApp. Quien no haya iniciado sesión verá una pantalla para autenticarse y, al entrar, se abre la consulta.'; ?></p>
    <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($data['link_publico']); ?>" onclick="this.select();">
    <form action="<?php echo URLROOT; ?>/admin/votacion_probar_correo" method="POST" style="margin-top:0.75rem;">
        <input type="hidden" name="votacion_id" value="<?php echo (int)$v->id; ?>">
        <button type="submit" class="btn btn-secondary btn-sm">Enviarme un correo de prueba</button>
    </form>
    <p style="font-size:0.75rem;color:var(--text-muted);margin:0.4rem 0 0;">Llega solo a su correo, para ver el aviso tal como lo recibe un socio. No avisa al resto.</p>
    <?php if ($v->estado === 'activa' && ($v->participacion ?? '') !== 'abierta'): ?>
        <?php $sinVotar = (int)($data['sin_votar'] ?? 0); ?>
        <?php if ($sinVotar > 0): ?>
        <form action="<?php echo URLROOT; ?>/admin/votacion_recordar" method="POST" style="margin-top:1rem;"
            onsubmit="return confirm('Se enviará un correo aparte a cada socio que aún no vota. Ninguno verá las direcciones de los demás.');">
            <input type="hidden" name="votacion_id" value="<?php echo (int)$v->id; ?>">
            <button type="submit" class="btn btn-primary btn-sm">Avisar por correo a quienes no han votado (<?php echo $sinVotar; ?>)</button>
        </form>
        <p style="font-size:0.75rem;color:var(--text-muted);margin:0.5rem 0 0;">Cada mensaje lleva solo la dirección de esa persona. No se usa copia visible ni copia oculta compartida. Quienes aún no activan su cuenta no reciben este aviso.</p>
        <?php else: ?>
        <p style="font-size:0.85rem;margin:0.75rem 0 0;">Todos los electores ya votaron.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (($v->participacion ?? '') === 'abierta'):
    $resumen = $data['resumen_ninos'] ?? ['personas' => 0, 'ninos' => 0, 'femenino' => 0, 'masculino' => 0, 'no_responder' => 0, 'edades' => []];
    $participaciones = $data['participaciones'] ?? [];
?>
<div class="card card-success" style="margin-bottom:1.5rem;">
    <h3 class="card-title"><?php echo !empty($v->inscribe_ninos) ? 'Niños y niñas inscritos' : 'Respuestas de la encuesta abierta'; ?></h3>
    <div class="grid-2col" style="margin-bottom:1rem;">
        <div><strong><?php echo (int)$resumen['personas']; ?></strong><br><span style="color:var(--text-muted);font-size:0.85rem;">Personas que respondieron</span></div>
        <?php if (!empty($v->inscribe_ninos)): ?>
        <div><strong><?php echo (int)$resumen['ninos']; ?></strong><br><span style="color:var(--text-muted);font-size:0.85rem;">Niños y niñas</span></div>
        <div><strong><?php echo (int)$resumen['femenino']; ?></strong> Femenino · <strong><?php echo (int)$resumen['masculino']; ?></strong> Masculino · <strong><?php echo (int)$resumen['no_responder']; ?></strong> No responder</div>
        <?php endif; ?>
    </div>
    <?php if (!empty($v->inscribe_ninos) && !empty($resumen['edades'])): ?>
        <p style="font-size:0.85rem;margin-top:0;">
            <?php
            $trozos = [];
            foreach ($resumen['edades'] as $edad => $cant) {
                $trozos[] = (int)$edad . ' años: ' . (int)$cant;
            }
            echo htmlspecialchars(implode(' · ', $trozos));
            ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($v->inscribe_ninos)): ?>
        <p style="margin:0 0 0.75rem;"><a class="btn btn-secondary btn-sm" href="<?php echo URLROOT; ?>/admin/votacion_ninos_exportar/<?php echo (int)$v->id; ?>">Excel de niños</a></p>
    <?php endif; ?>
    <?php if (empty($participaciones)): ?>
        <p style="color:var(--text-muted);">Todavía no hay respuestas.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre y apellidos</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Socio</th>
                    <?php if (!empty($v->inscribe_ninos)): ?><th>Niños</th><?php endif; ?>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($participaciones as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$p->nombre); ?></td>
                    <td><?php echo htmlspecialchars((string)$p->email); ?></td>
                    <td><?php echo htmlspecialchars((string)$p->telefono); ?></td>
                    <td><?php echo !empty($p->es_socio) ? 'Sí' : 'No'; ?></td>
                    <?php if (!empty($v->inscribe_ninos)): ?>
                    <td>
                        <?php if (empty($p->ninos)): ?>—<?php else: ?>
                            <?php foreach ($p->ninos as $nino): ?>
                                <div><?php echo (int)$nino->edad; ?> años · <?php echo htmlspecialchars(Votacion::etiquetaGenero((string)$nino->genero)); ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td><?php echo date('d/m/Y H:i', strtotime((string)$p->created_at)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div style="margin-bottom:0.75rem;">
    <a href="<?php echo URLROOT; ?>/admin/votacion_exportar/<?php echo (int)$v->id; ?>" class="btn btn-secondary btn-sm">Exportar respuestas (Excel)</a>
    <p style="font-size:0.75rem;color:var(--text-muted);margin:0.4rem 0 0;">
        <?php if (!empty($data['puede_ver_detalle'])): ?>
            El archivo trae el resumen y cada respuesta, con nombre, RUT y la pregunta adicional. Es la misma información confidencial de esta ficha.
        <?php else: ?>
            El archivo trae los totales y porcentajes. El detalle con nombre solo lo exporta el administrador o quien creó la consulta.
        <?php endif; ?>
    </p>
</div>
<?php
$votacion = $v;
$resultados = $data['resultados'];
$parciales_url = $data['parciales_url'] ?? '';
require APPROOT . '/views/partials/votacion_resultados.php';
?>

<?php if ($data['puede_ver_detalle'] && !empty($data['detalle_votantes'])): ?>
<div class="card card-primary" style="margin-top:1.5rem;">
    <h3 class="card-title">Detalle de votantes (solo administrador y creador)</h3>
    <p style="font-size:0.8rem;color:var(--text-muted);margin:-0.5rem 0 1rem;">Información confidencial resguardada según política de la organización.</p>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Nombre y apellidos</th><th>RUT</th><th>Respuesta</th></tr></thead>
            <tbody>
            <?php foreach ($data['detalle_votantes'] as $d): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($d->created_at)); ?></td>
                    <td><?php echo htmlspecialchars($d->nombre_completo ?? ''); ?></td>
                    <td style="font-family:monospace;font-size:0.85rem;"><?php echo htmlspecialchars($d->rut); ?></td>
                    <td><?php
                        $partes = [];
                        if (!empty($d->opcion_texto)) $partes[] = $d->opcion_texto;
                        if (!empty($d->seguimiento_opcion_texto)) $partes[] = $d->seguimiento_opcion_texto;
                        elseif (!empty($d->seguimiento_texto)) $partes[] = $d->seguimiento_texto;
                        if (!empty($d->respuesta_texto)) $partes[] = $d->respuesta_texto;
                        echo htmlspecialchars($partes ? implode(' · ', $partes) : '—');
                    ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
