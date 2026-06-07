<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($data['title']); ?></title>
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/style.css">
    <style>
        body { background:#0f172a; display:flex; justify-content:center; padding:1.5rem; min-height:100vh; }
        .cb-minuta { max-width:720px; width:100%; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:2rem; }
        .cb-minuta-header { text-align:center; border-bottom:2px solid var(--primary); padding-bottom:1rem; margin-bottom:1.5rem; }
        .cb-minuta-section { margin-bottom:1.25rem; }
        .cb-minuta-section h4 { font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin:0 0 0.5rem; }
        .cb-minuta-temas, .cb-minuta-resultados { white-space:pre-wrap; line-height:1.6; font-size:0.9rem; }
        .cb-minuta-list { margin:0; padding-left:1.25rem; }
        @media print {
            body { background:#fff; color:#000; padding:0; }
            .no-print { display:none !important; }
            .cb-minuta { border:none; box-shadow:none; max-width:100%; color:#000; }
        }
    </style>
</head>
<body>
<?php
$r = $data['reunion'];
$inicio = !empty($r->hora_inicio_real) ? $r->hora_inicio_real : $r->fecha_reunion;
?>
<div style="width:100%;max-width:720px;">
    <div class="no-print" style="display:flex;justify-content:space-between;margin-bottom:1rem;">
        <a href="<?php echo htmlspecialchars($data['back_url']); ?>" class="btn btn-secondary btn-sm">← Volver</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">Imprimir minuta</button>
    </div>
    <div class="cb-minuta">
        <div class="cb-minuta-header">
            <p style="margin:0;font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;">Minuta de reunión</p>
            <h1 style="margin:0.35rem 0;font-size:1.35rem;"><?php echo htmlspecialchars($r->titulo); ?></h1>
            <p style="margin:0.25rem 0 0;font-size:0.9rem;"><?php echo htmlspecialchars($data['junta_nombre']); ?></p>
        </div>

        <div class="cb-minuta-section">
            <h4>Fecha y hora de inicio</h4>
            <p style="margin:0;font-weight:600;"><?php echo date('d/m/Y \a \l\a\s H:i \h\r\s', strtotime($inicio)); ?></p>
        </div>

        <div class="cb-minuta-section">
            <h4>Temas tratados</h4>
            <div class="cb-minuta-temas"><?php echo nl2br(htmlspecialchars($data['temas'])); ?></div>
        </div>

        <div class="cb-minuta-section">
            <h4>Asistentes (<?php echo count($data['presentes']); ?> de <?php echo (int)$data['total_socios']; ?> socios en padrón)</h4>
            <?php if (empty($data['presentes'])): ?>
                <p style="margin:0;color:var(--text-muted);">Sin registro de asistencia.</p>
            <?php else: ?>
                <ul class="cb-minuta-list">
                    <?php foreach ($data['presentes'] as $p): ?>
                        <li><?php echo htmlspecialchars($p->nombre); ?> — <?php echo htmlspecialchars($p->rut); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="cb-minuta-section">
            <h4>Acuerdos, observaciones y resultados</h4>
            <div class="cb-minuta-resultados"><?php
                echo nl2br(htmlspecialchars($r->resultados ?? 'Sin resultados registrados.'));
            ?></div>
        </div>

        <p style="margin-top:2rem;font-size:0.75rem;color:var(--text-muted);text-align:center;">
            Documento generado por ConectaBarrio — <?php echo date('d/m/Y H:i'); ?>
        </p>
    </div>
</div>
</body>
</html>
