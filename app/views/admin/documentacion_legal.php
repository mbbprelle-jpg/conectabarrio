<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/AuthContext.php'; ?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<?php if (!empty($data['migration_pending'])): ?>
<div class="alert alert-warning">
    Ejecute la migración <code>sql/add_junta_personalidad_juridica_docs.sql</code> para habilitar esta sección.
</div>
<?php else: ?>

<div class="card card-primary" style="margin-bottom: 1.25rem;">
    <h3 class="card-title">Personalidad jurídica</h3>
    <?php $pj = $data['junta']->personalidad_juridica_num ?? null; ?>
    <?php if ($pj): ?>
        <p style="margin:0;font-size:1.1rem;"><strong>N° <?php echo htmlspecialchars($pj); ?></strong></p>
    <?php else: ?>
        <p style="margin:0;color:var(--text-muted);">No registrado. El usuario maestro puede indicarlo al crear la organización.</p>
    <?php endif; ?>
</div>

<div class="card card-primary" style="margin-bottom: 1.25rem;">
    <h3 class="card-title">Adjuntar documento legal</h3>
    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1rem;">
        Resolución, estatutos, personalidad jurídica u otro documento oficial. Solo visible para la directiva.
    </p>
    <form action="<?php echo URLROOT; ?>/admin/junta_documento_legal_subir" method="POST" enctype="multipart/form-data" class="cb-legal-upload-form">
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Título del documento</label>
                <input type="text" name="titulo" class="form-control" maxlength="200" placeholder="Ej: Resolución personalidad jurídica" required>
            </div>
            <div class="form-group">
                <label class="form-label">Archivo (PDF o imagen, máx. 10 MB)</label>
                <input type="file" name="archivo" class="form-control" accept=".pdf,image/*" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Subir documento</button>
    </form>
</div>

<div class="card card-primary">
    <h3 class="card-title">Documentos registrados</h3>
    <?php if (empty($data['documentos'])): ?>
        <p style="color:var(--text-muted);text-align:center;padding:1.5rem;">Aún no hay documentos legales adjuntos.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Subido por</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data['documentos'] as $doc): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($doc->titulo); ?></strong><br>
                        <small style="color:var(--text-muted);"><?php echo htmlspecialchars($doc->archivo_nombre_original); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars(trim(($doc->subidor_nombre ?? '') . ' ' . ($doc->subidor_apellido ?? ''))); ?></td>
                    <td><?php echo !empty($doc->created_at) ? date('d/m/Y H:i', strtotime($doc->created_at)) : '—'; ?></td>
                    <td style="text-align:right;white-space:nowrap;">
                        <a href="<?php echo URLROOT; ?>/admin/junta_documento_legal_descargar/<?php echo (int)$doc->id; ?>" class="btn btn-secondary btn-sm">Descargar</a>
                        <?php if ((int)$doc->subido_por === (int)$_SESSION['user_id']): ?>
                        <form action="<?php echo URLROOT; ?>/admin/junta_documento_legal_eliminar" method="POST" style="display:inline;" class="cb-form-delete-legal">
                            <input type="hidden" name="documento_id" value="<?php echo (int)$doc->id; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.cb-form-delete-legal').forEach(function(f) {
    f.addEventListener('submit', function(e) {
        if (!confirm('¿Eliminar este documento legal? Solo usted puede hacerlo porque fue quien lo subió.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
