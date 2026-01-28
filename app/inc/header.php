<?php
// inc/header.php

// BASE_URL: ajusta SOLO esta línea si tu proyecto está en subcarpeta
// Ejemplo típico en XAMPP: /TFG-DAW-TEATROS/
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="<?= BASE_URL ?>styles/headStyle.css?v=1">
  <link rel="stylesheet" href="<?= BASE_URL ?>styles/styleIndex.css?v=1">
  <script src="js/headScript.js"></script>
  <title>Navegador</title>
</head>
<body>

<header class="main-header">
  <div class="header-container">
    <div class="logo-section">
      <a href="<?= BASE_URL ?>index.php" class="site-name"><img src="images/logo/Logo.png" class="logo">Teatros Nova</a>
    </div>

    <div class="menu-toggle" id="menuToggle">
      <span></span><span></span><span></span>
    </div>

    <nav class="main-nav" id="mainNav">
      <ul>
        <li><a href="<?= BASE_URL ?>index.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Inicio</a></li>
        <li><a href="<?= BASE_URL ?>servicios.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'servicios.php') ? 'active' : ''; ?>">Servicios</a></li>
        <li><a href="<?= BASE_URL ?>nosotros.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'nosotros.php') ? 'active' : ''; ?>">Nosotros</a></li>
        <li><a href="<?= BASE_URL ?>contacto.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'contacto.php') ? 'active' : ''; ?>">Contacto</a></li>
      </ul>
    </nav>

    <a href="<?= BASE_URL ?>views/auth/login_admin.php"><button class="header-action" id="actionButton">Acceso Admin</button></a>
    <a href="<?= BASE_URL ?>views/user/login.php"><button class="header-action" id="actionButton">Acceso Usuario</button></a>

  </div>
</header>
