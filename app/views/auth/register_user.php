<?php
session_start();
require_once __DIR__ . '/../../dao/UsuarioDAO.php';
$usuarioDao = new UsuarioDAO();
$errors = [];

if (!empty($_POST['btnRegister'])) {
    $nombre = trim($_POST['Nombre'] ?? '');
    $email = trim($_POST['Email'] ?? '');
    $pass = $_POST['Password'] ?? '';
    
    if (empty($nombre)) $errors[] = "El nombre es obligatorio.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email no válido.";
    if (strlen($pass) < 4) $errors[] = "La contraseña debe tener al menos 4 caracteres.";

    if (!$errors && $usuarioDao->buscarPorEmail($email)) {
        $errors[] = "El email ya está registrado.";
    }

    if (!$errors) {
        $fotoNombre = null;
        if (!empty($_FILES['Foto']['name']) && $_FILES['Foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['Foto']['name'], PATHINFO_EXTENSION));
            $fotoNombre = "user_" . time() . "." . $ext;
            move_uploaded_file($_FILES['Foto']['tmp_name'], __DIR__ . "/../../uploads/" . $fotoNombre);
        }

        if ($usuarioDao->insertar($nombre, $email, password_hash($pass, PASSWORD_DEFAULT), $fotoNombre)) {
            header("Location: login_user.php?success=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Red de Teatros</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="form-box">
        <h1>Registro de Usuario</h1>
        <div id="js-errors" class="notice" style="display:none;"></div>
        <?php if($errors): ?>
            <div class="notice"><?php foreach($errors as $e) echo $e . "<br>"; ?></div>
        <?php endif; ?>

        <form id="registerForm" method="post" enctype="multipart/form-data">
            <div class="form-row"><label>Nombre</label><input type="text" name="Nombre" id="Nombre"></div>
            <div class="form-row"><label>Email</label><input type="email" name="Email" id="Email"></div>
            <div class="form-row"><label>Contraseña</label><input type="password" name="Password" id="Password"></div>
            <div class="form-row"><label>Foto Perfil</label><input type="file" name="Foto" accept="image/*"></div>
            <input type="submit" name="btnRegister" value="Registrarme">
        </form>
    </div>
    <script src="../../js/validator.js"></script>
</body>
</html>