<?php
/** @var object|null $edit */
/** @var array $miembros */
/** @var array $convocadosEdit */
$isEdit = !empty($edit);
$tituloVal = $isEdit ? ($edit->titulo ?? '') : '';
$fechaVal = $isEdit ? date('Y-m-d\TH:i', strtotime($edit->fecha_reunion)) : '';
$temasVal = '';
if ($isEdit) {
    require_once APPROOT . '/models/Reunion.php';
    $temasVal = (new Reunion())->getTemasText($edit);
}
?>
<div class="form-group">
    <label class="form-label">Título *</label>
    <input type="text" name="titulo" class="form-control" required maxlength="150"
        placeholder="Ej: Asamblea Ordinaria Mayo 2026" value="<?php echo htmlspecialchars($tituloVal); ?>">
</div>
<div class="form-group">
    <label class="form-label">Fecha y hora de convocatoria *</label>
    <input type="datetime-local" name="fecha_reunion" class="form-control" required value="<?php echo htmlspecialchars($fechaVal); ?>">
</div>
<div class="form-group">
    <label class="form-label">Tabla / temas a tratar</label>
    <textarea name="temas_tratar" class="form-control" rows="5" placeholder="Un tema por línea, ej:&#10;1. Lectura y aprobación acta anterior&#10;2. Informe de tesorería&#10;3. Varios"><?php echo htmlspecialchars($temasVal); ?></textarea>
</div>

<div class="form-group cb-reunion-destinatarios">
    <label class="form-label">Destinatarios *</label>
    <label class="cb-reunion-check"><input type="checkbox" name="convocar_directorio" value="1"> Directiva (secretario, tesorero, director, admin)</label>
    <label class="cb-reunion-check"><input type="checkbox" name="convocar_todos" value="1"> Todos los socios activos</label>
    <details class="cb-reunion-pick-socios" style="margin-top:0.5rem;">
        <summary style="cursor:pointer;font-size:0.85rem;color:var(--primary);">Seleccionar socios específicos</summary>
        <div class="cb-reunion-socio-list">
            <?php foreach ($miembros as $m): ?>
            <label class="cb-reunion-check">
                <input type="checkbox" name="convocados[]" value="<?php echo (int)$m->id; ?>"
                    <?php echo in_array((int)$m->id, $convocadosEdit, true) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($m->nombre); ?>
                <?php if (!empty($m->cargo)): ?><small>(<?php echo htmlspecialchars($m->cargo); ?>)</small><?php endif; ?>
                <?php if (empty($m->email)): ?><small style="color:var(--warning);">sin email</small><?php endif; ?>
            </label>
            <?php endforeach; ?>
        </div>
    </details>
</div>

<?php if (!$isEdit): ?>
<label class="cb-reunion-check" style="margin-bottom:0.5rem;">
    <input type="checkbox" name="enviar_email" value="1" checked>
    Enviar correo electrónico (quien no tenga email igual verá la invitación en su perfil)
</label>
<?php endif; ?>
