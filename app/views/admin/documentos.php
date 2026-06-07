<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/core/AuthContext.php'; ?>
<?php require_once APPROOT . '/core/DocumentStorage.php'; ?>

<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success"><span><?php echo htmlspecialchars($data['success']); ?></span></div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><span><?php echo htmlspecialchars($data['error']); ?></span></div>
<?php endif; ?>

<?php if (AuthContext::isFullAdmin() && empty($data['documentos_habilitado'])): ?>
<div class="alert alert-warning" style="margin-bottom: 1rem;">
    <span>
        El módulo de <strong>Documentos</strong> aún no está habilitado para su organización.
        Actívelo en <a href="<?php echo URLROOT; ?>/admin/socios" style="color: inherit; text-decoration: underline;">Socios y Ajustes</a>.
    </span>
</div>
<?php endif; ?>

<div class="cb-docs-toolbar">
    <div class="cb-docs-toolbar-left">
        <form method="GET" action="<?php echo URLROOT; ?>/admin/documentos" class="cb-docs-filter">
            <label for="categoria_filtro" class="form-label">Categoría</label>
            <select name="categoria" id="categoria_filtro" class="form-control" onchange="this.form.submit()">
                <option value="0">Todas las categorías</option>
                <?php foreach ($data['categorias'] as $cat): ?>
                    <option value="<?php echo (int)$cat->id; ?>" <?php echo (int)$data['categoria_filtro'] === (int)$cat->id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat->nombre); ?>
                        (<?php echo $cat->visibilidad === 'directorio' ? 'Directiva' : 'Organización'; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php if (!empty($data['puede_subir'])): ?>
    <button type="button" class="btn btn-primary" id="btnAbrirSubirDoc">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Subir documento
    </button>
    <?php endif; ?>
</div>

<div class="card cb-docs-table-card">
    <?php if (empty($data['documentos'])): ?>
        <p class="cb-docs-empty">No hay documentos<?php echo $data['categoria_filtro'] ? ' en esta categoría' : ''; ?> visibles para su perfil.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table cb-docs-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Categoría</th>
                    <th>Visibilidad</th>
                    <th>Tipo</th>
                    <th>Tamaño</th>
                    <th>Subido</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['documentos'] as $doc):
                    $isPdf = $doc->mime_type === 'application/pdf';
                    $isImg = str_starts_with($doc->mime_type, 'image/');
                ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($doc->titulo); ?></strong>
                        <div class="cb-docs-filename"><?php echo htmlspecialchars($doc->archivo_nombre_original); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($doc->categoria_nombre); ?></td>
                    <td>
                        <?php if ($doc->categoria_visibilidad === 'directorio'): ?>
                            <span class="badge badge-warning">Directiva</span>
                        <?php else: ?>
                            <span class="badge badge-info">Organización</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isPdf): ?>PDF<?php elseif ($isImg): ?>Imagen<?php else: ?>Archivo<?php endif; ?>
                    </td>
                    <td><?php echo DocumentStorage::formatBytes((int)$doc->tamano_bytes); ?></td>
                    <td>
                        <span style="font-size: 0.82rem;"><?php echo date('d/m/Y', strtotime($doc->created_at)); ?></span>
                        <div class="cb-docs-filename"><?php echo htmlspecialchars($doc->subido_por_nombre ?? ''); ?></div>
                    </td>
                    <td class="cb-docs-actions">
                        <?php if (DocumentStorage::isPreviewable($doc->mime_type)): ?>
                        <a href="<?php echo URLROOT; ?>/admin/documento_ver/<?php echo (int)$doc->id; ?>" class="btn btn-secondary btn-sm">Ver</a>
                        <?php endif; ?>
                        <a href="<?php echo URLROOT; ?>/admin/documento_descargar/<?php echo (int)$doc->id; ?>" class="btn btn-secondary btn-sm">Descargar</a>
                        <?php if (!empty($data['puede_subir'])): ?>
                        <form action="<?php echo URLROOT; ?>/admin/documento_eliminar" method="POST" class="cb-docs-inline-form confirm-action" data-confirm-message="¿Eliminar este documento de forma permanente?">
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

<?php if (!empty($data['puede_subir'])): ?>
<div class="card cb-docs-categorias-card" id="categorias">
    <h3 class="card-title">Categorías</h3>
    <p style="color: var(--text-muted); font-size: 0.82rem; margin: -0.5rem 0 1rem;">
        Organice los documentos. <strong>Organización</strong> = visible para todos los socios;
        <strong>Directiva</strong> = solo secretario, tesorero, director y administrador.
    </p>

    <form action="<?php echo URLROOT; ?>/admin/documento_categoria_crear" method="POST" class="cb-docs-cat-add">
        <input type="text" name="nombre" class="form-control" placeholder="Nueva categoría…" required maxlength="100">
        <select name="visibilidad" class="form-control">
            <option value="publico">Organización (público)</option>
            <option value="directorio">Directiva</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">+ Crear</button>
    </form>

    <?php if (!empty($data['categorias_gestion'])): ?>
    <div class="table-responsive" style="margin-top: 1rem;">
        <table class="table cb-docs-cat-table">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Visibilidad</th>
                    <th>Documentos</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['categorias_gestion'] as $cat): ?>
                <tr class="<?php echo empty($cat->activo) ? 'is-inactive' : ''; ?>">
                    <td>
                        <form action="<?php echo URLROOT; ?>/admin/documento_categoria_actualizar" method="POST" class="cb-docs-cat-edit-form">
                            <input type="hidden" name="categoria_id" value="<?php echo (int)$cat->id; ?>">
                            <input type="text" name="nombre" class="form-control form-control-sm" value="<?php echo htmlspecialchars($cat->nombre); ?>" required maxlength="100">
                    </td>
                    <td>
                            <select name="visibilidad" class="form-control form-control-sm">
                                <option value="publico" <?php echo $cat->visibilidad === 'publico' ? 'selected' : ''; ?>>Organización</option>
                                <option value="directorio" <?php echo $cat->visibilidad === 'directorio' ? 'selected' : ''; ?>>Directiva</option>
                            </select>
                    </td>
                    <td style="text-align: center;"><?php echo (int)($cat->num_documentos ?? 0); ?></td>
                    <td class="cb-docs-actions">
                            <label class="cb-docs-active-toggle">
                                <input type="checkbox" name="activo" value="1" <?php echo !empty($cat->activo) ? 'checked' : ''; ?>>
                                Activa
                            </label>
                            <button type="submit" class="btn btn-secondary btn-sm">Guardar</button>
                        </form>
                        <?php if ((int)($cat->num_documentos ?? 0) === 0): ?>
                        <form action="<?php echo URLROOT; ?>/admin/documento_categoria_eliminar" method="POST" class="cb-docs-inline-form confirm-action" data-confirm-message="¿Eliminar esta categoría?">
                            <input type="hidden" name="categoria_id" value="<?php echo (int)$cat->id; ?>">
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

<!-- Modal subir documento -->
<div id="cbDocUploadModal" class="cb-modal-overlay" aria-hidden="true">
    <div class="cb-modal-box cb-docs-upload-modal" role="dialog">
        <h3 class="cb-modal-title">Subir documento</h3>
        <form action="<?php echo URLROOT; ?>/admin/documento_subir" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="doc_titulo" class="form-label">Título *</label>
                <input type="text" name="titulo" id="doc_titulo" class="form-control" required maxlength="200" placeholder="Ej: Comprobante pago luz marzo 2026">
            </div>
            <div class="form-group">
                <label for="doc_categoria" class="form-label">Categoría *</label>
                <select name="categoria_id" id="doc_categoria" class="form-control" required>
                    <?php foreach ($data['categorias_subida'] as $cat): ?>
                        <option value="<?php echo (int)$cat->id; ?>">
                            <?php echo htmlspecialchars($cat->nombre); ?>
                            — <?php echo $cat->visibilidad === 'directorio' ? 'Directiva' : 'Organización'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="doc_archivo" class="form-label">Archivo * (PDF o imagen, máx. 10 MB)</label>
                <input type="file" name="archivo" id="doc_archivo" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,application/pdf,image/*">
                <p class="cb-docs-upload-hint">Las fotos JPG/PNG se optimizan automáticamente a WebP para ahorrar espacio sin perder legibilidad.</p>
            </div>
            <div class="cb-modal-actions">
                <button type="button" class="btn btn-secondary" id="btnCerrarSubirDoc">Cancelar</button>
                <button type="submit" class="btn btn-primary">Subir</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    const modal = document.getElementById('cbDocUploadModal');
    const openBtn = document.getElementById('btnAbrirSubirDoc');
    const closeBtn = document.getElementById('btnCerrarSubirDoc');
    if (!modal || !openBtn) return;
    openBtn.addEventListener('click', () => { modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); });
    closeBtn?.addEventListener('click', () => { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); });
    modal.addEventListener('click', e => { if (e.target === modal) { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); } });
})();
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
