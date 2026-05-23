<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($data['title']) ? htmlspecialchars($data['title']) . ' - ' . SITENAME : SITENAME; ?></title>
    <!-- Estilos Premium con Cache Buster -->
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php if (isset($_SESSION['user_id'])): ?>
<div class="app-container">
    
    <!-- Botón Menú Móvil -->
    <button class="menu-toggle" id="menuToggle" style="position: absolute; top: 1rem; left: 1rem; padding: 0.5rem; background: var(--bg-sidebar); border: 1px solid var(--border-color); border-radius: 6px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <!-- Sidebar Reutilizable -->
    <?php require_once APPROOT . '/views/layouts/sidebar.php'; ?>

    <!-- Contenido Principal -->
    <div class="main-content">
        
        <!-- Encabezado de Página Dinámico -->
        <div class="page-header">
            <div class="page-title-group">
                <h1><?php echo isset($data['header_title']) ? htmlspecialchars($data['header_title']) : 'Dashboard'; ?></h1>
                <p><?php echo isset($data['header_subtitle']) ? htmlspecialchars($data['header_subtitle']) : 'Bienvenido al panel de control'; ?></p>
            </div>
            
            <div class="page-header-actions">
                <span class="badge badge-info" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                    <?php 
                    if (isset($_SESSION['user_junta_nombre'])) {
                        echo htmlspecialchars($_SESSION['user_junta_nombre']);
                    } else {
                        echo 'Administrador Global';
                    }
                    ?>
                </span>
            </div>
        </div>
<?php else: ?>
    <!-- Si no está logueado, no renderiza la estructura de Dashboard -->
<?php endif; ?>
