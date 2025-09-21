<?php
require_once __DIR__ . '/operaciones_panel.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_panel ?></title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../estilos.css">
    <script src="panel.js" defer></script>
</head>
<body>
    <div class="menu-dashboard" id="menuDashboard">
        <div class="top-menu">
                <span>Sala de Rehabilitación</span>
        </div>
        
        <div class="user-profile">
            <div class="user-info">
                <i class="bx bx-user-circle"></i>
                <span><?= htmlspecialchars($nombre_usuario) ?></span>
            </div>
            <div class="user-role">
                <span><?= htmlspecialchars($rol_texto) ?></span>
            </div>
        </div>
        
        <div class="menu">
            <?= $menu_html ?>
        </div>
    </div>
    
    <div class="panel-content">
        <div class="panel-unico">
            <div id="contenidoDinamico" class="contenido-dinamico"></div>
        </div>
    </div>
</body>
</html>