<?php
$callesList = $callesList ?? [];
$listId = $listId ?? 'callesList';
$showDelete = !empty($showDelete);
$deleteUrlPrefix = $deleteUrlPrefix ?? (URLROOT . '/admin/calle_eliminar/');
?>
<div class="calles-list-scroll" id="<?php echo htmlspecialchars($listId); ?>">
    <?php if (empty($callesList)): ?>
        <p class="calles-list-empty">Aún no hay calles registradas en la junta.</p>
    <?php else: ?>
        <?php foreach ($callesList as $calle): ?>
            <div class="calles-list-item" data-calle-search="<?php echo htmlspecialchars(mb_strtoupper(trim($calle->nombre ?? ''), 'UTF-8')); ?>">
                <span class="calles-list-item-name"><?php echo htmlspecialchars($calle->nombre); ?></span>
                <?php if ($showDelete): ?>
                    <form action="<?php echo htmlspecialchars($deleteUrlPrefix . (int)$calle->id); ?>" method="POST" class="calles-list-item-action">
                        <button type="submit" class="btn btn-danger btn-sm confirm-action"
                                data-confirm-message="¿Eliminar la calle '<?php echo htmlspecialchars($calle->nombre); ?>'? Los socios quedarán sin calle asociada.">
                            Eliminar
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
