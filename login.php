<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

soloVisitantes();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['clave'] ?? '';

    if ($correo === '' || $clave === '') {
        $error = 'Correo y contraseña son obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } else {
        $stmt = $mysqli->prepare(
            'SELECT id_usuario, nombre, correo, contrasena, puntos_totales, rol
             FROM usuario WHERE correo = ? LIMIT 1'
        );
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();

        if (!$usuario || !password_verify($clave, $usuario['contrasena'])) {
            $error = 'Correo o contraseña incorrectos.';
        } else {
            session_regenerate_id(true);
            $_SESSION['id_usuario']     = (int) $usuario['id_usuario'];
            $_SESSION['nombre']         = $usuario['nombre'];
            $_SESSION['correo']         = $usuario['correo'];
            $_SESSION['puntos_totales'] = (int) $usuario['puntos_totales'];
            $_SESSION['rol']            = $usuario['rol'];
            guardarMensaje('¡Bienvenida/o, ' . $usuario['nombre'] . '!');
            header('Location: dashboard.php');
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
  <title>Iniciar sesión | Schoolgy</title>
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
      <h2>Tu agenda escolar, clara y motivadora</h2>
      <p>Ingresa para ver entregas urgentes, recordatorios y tu nivel de puntos.</p>
    </div>
  </aside>
  <div class="auth-panel">
    <div class="auth-card">
      <div class="auth-brand">
        <img class="brand-logo" src="img/logo.svg" alt="">
        <h1>Iniciar sesión</h1>
        <p>Accede a tu panel de tareas y progreso.</p>
      </div>
      <form method="post" autocomplete="on">
        <?php if ($error): ?>
          <p class="form-error"><?= e($error) ?></p>
        <?php endif; ?>
        <label for="correo">Correo electrónico</label>
        <input id="correo" name="correo" type="email" placeholder="sara@schoolgy.com"
               value="<?= e($_POST['correo'] ?? 'sara@schoolgy.com') ?>" required>
        <label for="clave">Contraseña</label>
        <input id="clave" name="clave" type="password" placeholder="alumno123" required minlength="8" value="">
        <button class="btn btn-primary full" type="submit">Entrar al panel</button>
        <p class="login-hint">Cuenta demo lista para presentar:</p>
        <div class="demo-chip">
          <code>sara@schoolgy.com</code>
          <code>alumno123</code>
        </div>
      </form>
      <p class="auth-switch">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
    </div>
  </div>
</section>
</body>
</html>
