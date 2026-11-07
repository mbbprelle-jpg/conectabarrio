<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php $v = $data['votacion']; $back = $data['back_url'] ?? URLROOT . '/admin/votaciones'; ?>

<div class="card card-primary" style="max-width:680px;">
    <?php if (!empty($data['ya_voto'])): ?>
        <p class="alert alert-success">Ya registró su participación en esta consulta.</p>
        <?php if (!empty($data['resultados'])): ?>
            <p style="font-size:0.85rem;color:var(--text-muted);">Así va la consulta hasta ahora. Los porcentajes no muestran su nombre.</p>
        <?php else: ?>
            <p style="font-size:0.85rem;color:var(--text-muted);">Los resultados de esta consulta están reservados a la directiva.</p>
        <?php endif; ?>
        <a href="<?php echo htmlspecialchars($back); ?>" class="btn btn-secondary">Volver</a>
    <?php elseif (empty($data['puede_votar'])): ?>
        <p class="alert alert-warning">Esta consulta no está disponible para usted o ya finalizó.</p>
        <a href="<?php echo htmlspecialchars($back); ?>" class="btn btn-secondary">Volver</a>
    <?php else: ?>
        <?php if (!empty($data['error'])): ?>
            <p class="alert alert-danger"><?php echo htmlspecialchars($data['error']); ?></p>
        <?php endif; ?>
        <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1rem;"><?php echo nl2br(htmlspecialchars($v->descripcion ?? '')); ?></p>
        <form action="<?php echo URLROOT . (strpos($back, '/socio/') !== false ? '/socio/votacion_votar/' : '/admin/votacion_votar/') . (int)$v->id; ?>" method="POST" id="formVoto">
            <?php foreach ($data['opciones'] as $o):
                $tipoSeg = $o->seguimiento_tipo ?? 'ninguno';
                $tieneSeg = $tipoSeg !== 'ninguno' && trim((string)($o->seguimiento_pregunta ?? '')) !== '';
            ?>
            <div class="opcion-block" style="margin-bottom:0.65rem;">
                <label class="opcion-label">
                    <input type="radio" name="opcion_id" value="<?php echo (int)$o->id; ?>" <?php echo $v->tipo === 'votacion' ? 'required' : ''; ?>>
                    <span><?php echo htmlspecialchars($o->texto); ?></span>
                </label>
                <?php if ($tieneSeg): ?>
                <p class="followup-hint">Al marcar esta opción se abre otra pregunta.</p>
                <div class="followup">
                    <p style="margin:0 0 0.5rem;font-size:0.9rem;"><strong><?php echo htmlspecialchars($o->seguimiento_pregunta); ?></strong></p>
                    <?php if ($tipoSeg === 'texto'): ?>
                        <textarea name="seguimiento_texto[<?php echo (int)$o->id; ?>]" class="form-control seg-input" rows="3" placeholder="Su respuesta"></textarea>
                    <?php else: ?>
                        <?php foreach ($o->seguimiento_opciones ?? [] as $s): ?>
                        <label style="display:flex;align-items:flex-start;gap:0.6rem;margin-bottom:0.45rem;cursor:pointer;">
                            <input type="radio" class="seg-input" name="seguimiento_opcion_id[<?php echo (int)$o->id; ?>]" value="<?php echo (int)$s->id; ?>">
                            <span><?php echo htmlspecialchars($s->texto); ?></span>
                        </label>
                        <?php endforeach; ?>
                        <?php if (empty($o->seguimiento_opciones)): ?>
                        <p style="font-size:0.8rem;color:#b91c1c;margin:0;">Esta opción no tiene alternativas cargadas. Edítela y escriba cada alternativa en su propia línea.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if ($v->tipo === 'encuesta'): ?>
            <div class="form-group" style="margin-top:0.75rem;">
                <label class="form-label">Comentario adicional (opcional)</label>
                <textarea name="respuesta_texto" class="form-control" rows="3"></textarea>
            </div>
            <?php endif; ?>
            <p style="font-size:0.75rem;color:var(--text-muted);margin:1rem 0;">Su nombre no aparece en los porcentajes. Solo el administrador y quien creó la consulta pueden ver quién respondió qué.</p>
            <button type="submit" class="btn btn-primary">Enviar respuesta</button>
            <a href="<?php echo htmlspecialchars($back); ?>" class="btn btn-secondary">Cancelar</a>
        </form>
    <?php endif; ?>
</div>

<?php if (!empty($data['ya_voto']) && !empty($data['resultados'])): ?>
<div style="margin-top:1.25rem;max-width:680px;">
<?php
$votacion = $v;
$resultados = $data['resultados'];
$parciales_url = $data['parciales_url'] ?? '';
require APPROOT . '/views/partials/votacion_resultados.php';
?>
</div>
<?php endif; ?>

<style>
.opcion-label { display:flex; align-items:flex-start; gap:0.75rem; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; }
.opcion-block.is-selected .opcion-label { border-color: var(--primary, #4f46e5); box-shadow: 0 0 0 1px var(--primary, #4f46e5); }
.followup-hint { display:block; margin:0.35rem 0 0 0.35rem; font-size:0.75rem; color:var(--text-muted); }
.opcion-block .followup { display:none; margin:0.5rem 0 0.25rem 0.35rem; padding:0.75rem; border-left:2px solid var(--primary, #4f46e5); }
.opcion-block.is-selected .followup { display:block; }
.opcion-block.is-selected .followup-hint { display:none; }
</style>
<script>
(function () {
    var blocks = document.querySelectorAll('.opcion-block');
    function sync() {
        var selected = document.querySelector('input[name="opcion_id"]:checked');
        blocks.forEach(function (block) {
            var radio = block.querySelector('input[name="opcion_id"]');
            var on = !!(radio && selected && radio.value === selected.value);
            block.classList.toggle('is-selected', on);
            block.querySelectorAll('.seg-input').forEach(function (field) {
                field.required = on;
            });
        });
    }
    document.querySelectorAll('input[name="opcion_id"]').forEach(function (radio) {
        radio.addEventListener('change', sync);
    });
    sync();
})();
</script>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
