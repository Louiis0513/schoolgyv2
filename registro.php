<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

soloVisitantes();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['clave'] ?? '';

    if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
        $error = 'El nombre debe tener entre 3 y 100 caracteres.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } elseif (strlen($clave) < 8) {
        $error = 'La contraseña debe tener mínimo 8 caracteres.';
    } else {
        $check = $mysqli->prepare('SELECT id_usuario FROM usuario WHERE correo = ? LIMIT 1');
        $check->bind_param('s', $correo);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) {
            $error = 'Ese correo ya está registrado.';
        } else {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare(
                'INSERT INTO usuario (nombre, correo, contrasena, puntos_totales, rol)
                 VALUES (?, ?, ?, 0, \'estudiante\')'
            );
            $stmt->bind_param('sss', $nombre, $correo, $hash);
            $stmt->execute();

            $id = (int) $mysqli->insert_id;
            session_regenerate_id(true);
            $_SESSION['id_usuario']     = $id;
            $_SESSION['nombre']         = $nombre;
            $_SESSION['correo']         = $correo;
            $_SESSION['puntos_totales'] = 0;
            $_SESSION['rol']            = 'estudiante';
            guardarMensaje('Cuenta creada. ¡Empieza creando tus materias!');
            header('Location: materias.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Registro | Schoolgy</title>
  <link rel="icon" href="img/logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="css/app.css">
</head>
<body>
<section class="auth-screen">
  <aside class="auth-visual">
    <a class="back-link" href="index.php">← Volver al inicio</a>
    <img class="auth-bg" src="img/auth-study.jpg" alt="">
    <div class="auth-visual-inner">
      <img class="brand-logo" src="img/logo.svg" alt="">
      <h2>Crea tu cuenta en segundos</h2>
      <p>Organiza materias, registra entregas y gana puntos por cada tarea cumplida.</p>
    </div>
  </aside>
  <div class="auth-panel">
    <div class="auth-card">
      <div class="auth-brand">
        <img class="brand-logo" src="img/logo.svg" alt="">
        <h1>Crear cuenta</h1>
        <p>Regístrate para organizar tus tareas y ganar puntos.</p>
      </div>
      <form method="post" autocomplete="on">
        <?php if ($error): ?>
          <p class="form-error"><?= e($error) ?></p>
        <?php endif; ?>
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" type="text" minlength="3" maxlength="100"
               placeholder="Tu nombre completo"
               value="<?= e($_POST['nombre'] ?? '') ?>" required>
        <label for="correo">Correo electrónico</label>
        <input id="correo" name="correo" type="email" placeholder="estudiante@liceo.edu.co"
               value="<?= e($_POST['correo'] ?? '') ?>" required>
        <label for="clave">Contraseña</label>
        <input id="clave" name="clave" type="password" minlength="8" placeholder="Mínimo 8 caracteres" required>
        <button class="btn btn-primary full" type="submit">Registrarme</button>
      </form>
      <p class="auth-switch">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
    </div>
  </div>
</section>
</body>
</html>
