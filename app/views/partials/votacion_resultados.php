<?php
/**
 * Resultados agregados de una consulta.
 * Espera $resultados (array), $votacion (object) y, opcional, $parciales_url (string).
 */
if (!function_exists('cb_votacion_bar')) {
    function cb_votacion_bar(string $texto, int $votos, int $pct): string {
        $pct = max(0, min(100, $pct));
        return '<div style="margin-bottom:0.85rem;">'
            . '<div style="display:flex;justify-content:space-between;gap:1rem;font-size:0.9rem;margin-bottom:0.25rem;">'
            . '<span>' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</span>'
            . '<strong>' . $votos . ' (' . $pct . '%)</strong></div>'
            . '<div style="height:8px;background:rgba(255,255,255,0.08);border-radius:4px;overflow:hidden;">'
            . '<div style="width:' . $pct . '%;height:100%;background:var(--gradient-primary);"></div>'
            . '</div></div>';
    }
}

$resultados = $resultados ?? ['total' => 0, 'sin_opcion' => 0, 'opciones' => [], 'actualizado' => ''];
$total = (int)($resultados['total'] ?? 0);
$estado = $votacion->estado ?? '';
$tituloResultado = $estado === 'cerrada' ? 'Resultado final' : 'Resultados parciales';
?>
<div class="card card-success" id="votacion-resultados"<?php echo !empty($parciales_url) ? ' data-url="' . htmlspecialchars($parciales_url, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
    <h3 class="card-title"><?php echo $tituloResultado; ?> (<span data-total><?php echo $total; ?></span> respuesta<?php echo $total === 1 ? '' : 's'; ?>)</h3>
    <p style="font-size:0.8rem;color:var(--text-muted);margin:-0.35rem 0 1rem;">
        Los porcentajes no identifican a las personas.
        <?php if ($estado === 'activa' && !empty($parciales_url)): ?>
            Se actualizan solos. Última lectura: <span data-time><?php echo htmlspecialchars((string)($resultados['actualizado'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
    </p>
    <div data-body>
        <?php if ($total === 0): ?>
            <p style="color:var(--text-muted);">Todavía no hay respuestas.</p>
        <?php else: ?>
            <?php foreach ($resultados['opciones'] as $o): ?>
                <?php echo cb_votacion_bar((string)$o['texto'], (int)$o['votos'], (int)$o['pct']); ?>
                <?php if (($o['tipo'] ?? 'ninguno') !== 'ninguno' && ($o['pregunta'] ?? '') !== '' && (int)$o['votos'] > 0): ?>
                    <div style="margin:-0.35rem 0 1rem 1rem;padding-left:0.75rem;border-left:2px solid var(--border-color);">
                        <p style="font-size:0.8rem;margin:0 0 0.5rem;color:var(--text-muted);">
                            Entre quienes eligieron «<?php echo htmlspecialchars((string)$o['texto'], ENT_QUOTES, 'UTF-8'); ?>»:
                            <?php echo htmlspecialchars((string)$o['pregunta'], ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                        <?php if (($o['tipo'] ?? '') === 'opciones'): ?>
                            <?php foreach ($o['items'] as $item): ?>
                                <?php echo cb_votacion_bar((string)$item['texto'], (int)$item['votos'], (int)$item['pct']); ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="font-size:0.85rem;margin:0 0 0.75rem;"><?php echo (int)($o['textos'] ?? 0); ?> respuesta<?php echo (int)($o['textos'] ?? 0) === 1 ? '' : 's'; ?> escrita<?php echo (int)($o['textos'] ?? 0) === 1 ? '' : 's'; ?>. El texto solo lo ve la directiva.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ((int)($resultados['sin_opcion'] ?? 0) > 0): ?>
                <p style="font-size:0.8rem;color:var(--text-muted);"><?php echo (int)$resultados['sin_opcion']; ?> respuesta(s) solo con comentario, sin opción marcada.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($parciales_url) && ($votacion->estado ?? '') === 'activa'): ?>
<script>
(function () {
    var root = document.getElementById('votacion-resultados');
    if (!root) return;
    var url = root.getAttribute('data-url');
    if (!url) return;
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }
    function bar(texto, votos, pct) {
        pct = Math.max(0, Math.min(100, parseInt(pct, 10) || 0));
        votos = parseInt(votos, 10) || 0;
        return '<div style="margin-bottom:0.85rem;">'
            + '<div style="display:flex;justify-content:space-between;gap:1rem;font-size:0.9rem;margin-bottom:0.25rem;">'
            + '<span>' + esc(texto) + '</span><strong>' + votos + ' (' + pct + '%)</strong></div>'
            + '<div style="height:8px;background:rgba(255,255,255,0.08);border-radius:4px;overflow:hidden;">'
            + '<div style="width:' + pct + '%;height:100%;background:var(--gradient-primary);"></div></div></div>';
    }
    function body(data) {
        var total = parseInt(data.total, 10) || 0;
        if (!total) return '<p style="color:var(--text-muted);">Todavía no hay respuestas.</p>';
        var html = '';
        (data.opciones || []).forEach(function (o) {
            html += bar(o.texto, o.votos, o.pct);
            if (o.tipo && o.tipo !== 'ninguno' && o.pregunta && (parseInt(o.votos, 10) || 0) > 0) {
                html += '<div style="margin:-0.35rem 0 1rem 1rem;padding-left:0.75rem;border-left:2px solid var(--border-color);">';
                html += '<p style="font-size:0.8rem;margin:0 0 0.5rem;color:var(--text-muted);">Entre quienes eligieron «' + esc(o.texto) + '»: ' + esc(o.pregunta) + '</p>';
                if (o.tipo === 'opciones') {
                    (o.items || []).forEach(function (item) { html += bar(item.texto, item.votos, item.pct); });
                } else {
                    var n = parseInt(o.textos, 10) || 0;
                    html += '<p style="font-size:0.85rem;margin:0 0 0.75rem;">' + n + (n === 1 ? ' respuesta escrita.' : ' respuestas escritas.') + ' El texto solo lo ve la directiva.</p>';
                }
                html += '</div>';
            }
        });
        var sin = parseInt(data.sin_opcion, 10) || 0;
        if (sin > 0) {
            html += '<p style="font-size:0.8rem;color:var(--text-muted);">' + sin + ' respuesta(s) solo con comentario, sin opción marcada.</p>';
        }
        return html;
    }
    function tick() {
        if (document.hidden) return;
        fetch(url, {credentials: 'same-origin', headers: {'Accept': 'application/json'}})
            .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
            .then(function (data) {
                var slot = root.querySelector('[data-body]');
                if (slot) slot.innerHTML = body(data);
                var totalEl = root.querySelector('[data-total]');
                if (totalEl) totalEl.textContent = String(parseInt(data.total, 10) || 0);
                var timeEl = root.querySelector('[data-time]');
                if (timeEl && data.actualizado) timeEl.textContent = data.actualizado;
            })
            .catch(function () {});
    }
    setInterval(tick, 10000);
})();
</script>
<?php endif; ?>
