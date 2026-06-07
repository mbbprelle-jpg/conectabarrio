<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['title'] ?? 'Documento'); ?> — ConectaBarrio</title>
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/style.css">
    <style>
        body { background: var(--bg-main); margin: 0; min-height: 100vh; }
        .cb-doc-viewer-wrap { max-width: 1100px; margin: 0 auto; padding: 1.25rem; }
        .cb-doc-viewer-head { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .cb-doc-viewer-frame { background: #111; border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; min-height: 70vh; }
        .cb-doc-viewer-frame iframe, .cb-doc-viewer-frame img { display: block; width: 100%; max-height: 85vh; object-fit: contain; margin: 0 auto; }
        .cb-doc-viewer-frame iframe { height: 85vh; border: none; }
    </style>
</head>
<body>
<?php $doc = $data['documento']; ?>
<div class="cb-doc-viewer-wrap">
    <div class="cb-doc-viewer-head">
        <div>
            <a href="<?php echo htmlspecialchars($data['back_url']); ?>" class="btn btn-secondary btn-sm">← Volver</a>
            <h1 style="font-size: 1.15rem; margin: 0.75rem 0 0.25rem;"><?php echo htmlspecialchars($doc->titulo); ?></h1>
            <p style="margin: 0; font-size: 0.82rem; color: var(--text-muted);">
                <?php echo htmlspecialchars($doc->categoria_nombre); ?> · <?php echo htmlspecialchars($doc->archivo_nombre_original); ?>
            </p>
        </div>
        <a href="<?php echo URLROOT; ?>/admin/documento_descargar/<?php echo (int)$doc->id; ?>" class="btn btn-primary btn-sm">Descargar</a>
    </div>
    <?php if (!empty($data['previewable'])): ?>
    <div class="cb-doc-viewer-frame">
        <?php if ($doc->mime_type === 'application/pdf'): ?>
            <iframe src="<?php echo htmlspecialchars($data['archivo_url']); ?>" title="<?php echo htmlspecialchars($doc->titulo); ?>"></iframe>
        <?php else: ?>
            <img src="<?php echo htmlspecialchars($data['archivo_url']); ?>" alt="<?php echo htmlspecialchars($doc->titulo); ?>">
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">Vista previa no disponible. Use Descargar.</div>
    <?php endif; ?>
</div>
</body>
</html>
