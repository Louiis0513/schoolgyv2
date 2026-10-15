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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión | Schoolgy</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/app.css">
</head>
<body>
<section class="auth-screen">
  <a class="back-link" href="index.php">← Volver al inicio</a>
  <div class="auth-card">
    <div class="auth-brand">
      <span class="logo-icon" style="margin:0 auto 12px;">🎓</span>
      <h1>Schoolgy</h1>
      <p>Ingresa a tu cuenta para continuar con tu progreso.</p>
    </div>
    <form method="post" autocomplete="on">
      <?php if ($error): ?>
        <p class="form-error"><?= e($error) ?></p>
      <?php endif; ?>
      <label for="correo">Correo electrónico</label>
      <input id="correo" name="correo" type="email" placeholder="estudiante@liceo.edu.co"
             value="<?= e($_POST['correo'] ?? '') ?>" required>
      <label for="clave">Contraseña</label>
      <input id="clave" name="clave" type="password" placeholder="Mínimo 8 caracteres" required minlength="8">
      <button class="btn btn-primary full" type="submit">Iniciar sesión</button>
      <p class="login-hint">Demo: <strong>sara@schoolgy.com</strong> / <strong>alumno123</strong></p>
    </form>
    <p class="auth-switch">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
  </div>
</section>
</body>
</html>
