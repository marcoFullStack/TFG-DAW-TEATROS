<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="styles/headStyle.css" rel="stylesheet">
	<title>Naregador</title>
</head>
<header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <div class="logo">N</div>
                <a href="index.php" class="site-name">Naregador</a>
            </div>
            
            <div class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <nav class="main-nav" id="mainNav">
                <ul>
                    <li><a href="index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Inicio</a></li>
                    <li><a href="servicios.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'servicios.php') ? 'active' : ''; ?>">Servicios</a></li>
                    <li><a href="nosotros.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'nosotros.php') ? 'active' : ''; ?>">Nosotros</a></li>
                    <li><a href="contacto.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'contacto.php') ? 'active' : ''; ?>">Contacto</a></li>
                </ul>
            </nav>
            
            <button class="header-action" id="actionButton">Acceder</button>
        </div>
    </header>


