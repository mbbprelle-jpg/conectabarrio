<?php if (isset($_SESSION['user_id']) && empty($data['public_layout'] ?? false)): ?>
    </div> <!-- Cierre de main-content -->
</div> <!-- Cierre de app-container -->
<?php endif; ?>

<!-- Gráficas Premium (Chart.js via CDN) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Scripts Personalizados -->
<div id="cbConfirmModal" class="cb-confirm-overlay" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="cb-confirm-box">
        <div id="cbConfirmIcon" class="cb-confirm-icon info">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        </div>
        <h3 id="cbConfirmTitle" class="cb-confirm-title">Confirmar acción</h3>
        <p id="cbConfirmMessage" class="cb-confirm-message"></p>
        <div class="cb-confirm-actions">
            <button type="button" id="cbConfirmCancel" class="btn btn-secondary">Cancelar</button>
            <button type="button" id="cbConfirmOk" class="btn btn-primary">Confirmar</button>
        </div>
    </div>
</div>

<div id="cbLoadingOverlay" class="cb-loading-overlay" aria-hidden="true" role="alertdialog" aria-modal="true" aria-labelledby="cbLoadingTitle">
    <div class="cb-loading-box">
        <div class="cb-loading-spinner" aria-hidden="true"></div>
        <h3 id="cbLoadingTitle" class="cb-loading-title">Procesando…</h3>
        <p id="cbLoadingMessage" class="cb-loading-message">Espere un momento, por favor.</p>
        <p class="cb-loading-hint">No cierre ni recargue esta pestaña hasta que finalice.</p>
    </div>
</div>

<script src="<?php echo URLROOT; ?>/js/main.js"></script>

</body>
</html>
