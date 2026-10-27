<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
enviarHeadersCache('privado');
requiereLogin();

$id = (int) $_SESSION['id_usuario'];
$error = '';

$stmt = $mysqli->prepare(
    'SELECT nombre, correo, puntos_totales, rol, creado_en FROM usuario WHERE id_usuario = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
if ($usuario) {
    cacheParchePuntosUsuario($id, (int) $usuario['puntos_totales']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $clave  = $_POST['clave'] ?? '';
    $clave2 = $_POST['clave2'] ?? '';

    if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
        $error = 'El nombre debe tener entre 3 y 100 caracteres.';
    } elseif ($clave !== '' && strlen($clave) < 8) {
        $error = 'La nueva contraseña debe tener mínimo 8 caracteres.';
    } elseif ($clave !== '' && $clave !== $clave2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        if ($clave !== '') {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $up = $mysqli->prepare(
                'UPDATE usuario SET nombre = ?, contrasena = ? WHERE id_usuario = ?'
            );
            $up->bind_param('ssi', $nombre, $hash, $id);
        } else {
            $up = $mysqli->prepare('UPDATE usuario SET nombre = ? WHERE id_usuario = ?');
            $up->bind_param('si', $nombre, $id);
        }
        $up->execute();
        $_SESSION['nombre'] = $nombre;
        if ($usuario) {
            cacheParchePuntosUsuario($id, (int) $usuario['puntos_totales']);
        }
        guardarMensaje('Perfil actualizado.');
        header('Location: perfil.php');
        exit;
    }
}

$tituloPagina = 'Perfil';
require __DIR__ . '/includes/layout_inicio.php';
?>

<div class="page-heading">
  <div>
    <h1>Mi perfil</h1>
    <p>Datos personales y cierre seguro de sesión.</p>
  </div>
  <a class="btn btn-danger" href="logout.php">Cerrar sesión</a>
</div>

<div class="split-layout">
  <section class="section-card form-card">
    <h3>Datos de la cuenta</h3>
    <?php if ($error): ?>
      <p class="form-error" style="margin-top:12px"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post">
      <label for="nombre">Nombre</label>
      <input id="nombre" name="nombre" minlength="3" maxlength="100" required
             value="<?= e($_POST['nombre'] ?? $usuario['nombre']) ?>">
      <label for="correo">Correo</label>
      <input id="correo" type="email" value="<?= e($usuario['correo']) ?>" disabled>
      <label for="clave">Nueva contraseña (opcional)</label>
      <input id="clave" name="clave" type="password" minlength="8" autocomplete="new-password">
      <label for="clave2">Confirmar contraseña</label>
      <input id="clave2" name="clave2" type="password" minlength="8" autocomplete="new-password">
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Guardar cambios</button>
      </div>
    </form>
  </section>

  <section class="section-card form-card">
    <h3>Resumen</h3>
    <p style="margin-top:14px;color:var(--muted);font-size:.9rem">
      Rol: <strong style="color:var(--dark)"><?= e($usuario['rol']) ?></strong><br>
      Puntos: <strong style="color:var(--dark)"><?= (int) $usuario['puntos_totales'] ?></strong><br>
      Nivel: <strong style="color:var(--dark)"><?= calcularNivel((int) $usuario['puntos_totales']) ?></strong><br>
      Cuenta desde: <strong style="color:var(--dark)"><?= e(formatearFecha($usuario['creado_en'])) ?></strong>
    </p>
  </section>
</div>

<?php require __DIR__ . '/includes/layout_fin.php'; ?>
