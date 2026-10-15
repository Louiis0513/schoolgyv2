<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
requiereLogin();

$id = (int) $_SESSION['id_usuario'];
$error = '';

// Crear / editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'crear';
    $nombre = trim($_POST['nombre'] ?? '');
    $color  = trim($_POST['color'] ?? '#6366f1');

    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $error = 'El nombre de la materia es obligatorio (máx. 80).';
    } elseif (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
        $error = 'El color debe ser un hex válido (#RRGGBB).';
    } elseif ($accion === 'editar') {
        $idMateria = (int) ($_POST['id_materia'] ?? 0);
        if (!materiaDelUsuario($mysqli, $idMateria, $id)) {
            $error = 'No puedes editar esa materia.';
        } else {
            $stmt = $mysqli->prepare(
                'UPDATE materia SET nombre = ?, color = ? WHERE id_materia = ? AND id_usuario = ?'
            );
            $stmt->bind_param('ssii', $nombre, $color, $idMateria, $id);
            $stmt->execute();
            guardarMensaje('Materia actualizada.');
            header('Location: materias.php');
            exit;
        }
    } else {
        $stmt = $mysqli->prepare(
            'INSERT INTO materia (id_usuario, nombre, color) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iss', $id, $nombre, $color);
        $stmt->execute();
        guardarMensaje('Materia creada.');
        header('Location: materias.php');
        exit;
    }
}

// Eliminar
if (isset($_GET['eliminar'])) {
    $idMateria = (int) $_GET['eliminar'];
    if (!materiaDelUsuario($mysqli, $idMateria, $id)) {
        guardarMensaje('No puedes eliminar esa materia.', 'error');
    } else {
        $c = $mysqli->prepare(
            'SELECT COUNT(*) AS n FROM tarea WHERE id_materia = ? AND id_usuario = ?'
        );
        $c->bind_param('ii', $idMateria, $id);
        $c->execute();
        $n = (int) $c->get_result()->fetch_assoc()['n'];
        if ($n > 0) {
            guardarMensaje('No se puede borrar: hay ' . $n . ' tarea(s) asociadas. Elimínalas o reasígnalas primero.', 'error');
        } else {
            $d = $mysqli->prepare('DELETE FROM materia WHERE id_materia = ? AND id_usuario = ?');
            $d->bind_param('ii', $idMateria, $id);
            $d->execute();
            guardarMensaje('Materia eliminada.');
        }
    }
    header('Location: materias.php');
    exit;
}

$editar = null;
if (isset($_GET['editar'])) {
    $idMateria = (int) $_GET['editar'];
    $s = $mysqli->prepare(
        'SELECT * FROM materia WHERE id_materia = ? AND id_usuario = ? LIMIT 1'
    );
    $s->bind_param('ii', $idMateria, $id);
    $s->execute();
    $editar = $s->get_result()->fetch_assoc();
}

$list = $mysqli->prepare(
    "SELECT m.*,
            (SELECT COUNT(*) FROM tarea t WHERE t.id_materia = m.id_materia) AS total_tareas
     FROM materia m
     WHERE m.id_usuario = ?
     ORDER BY m.nombre ASC"
);
$list->bind_param('i', $id);
$list->execute();
$materias = $list->get_result()->fetch_all(MYSQLI_ASSOC);

$tituloPagina = 'Gestión de materias';
require __DIR__ . '/includes/layout_inicio.php';
?>

<div class="page-heading">
  <div>
    <h1>Materias</h1>
    <p>Crea, edita o elimina tus materias con color identificador.</p>
  </div>
</div>

<?php if ($error): ?>
  <div class="flash flash-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="split-layout">
  <section class="section-card">
    <div class="card-heading">
      <div>
        <h3>Mis materias</h3>
        <p><?= count($materias) ?> registradas</p>
      </div>
    </div>
    <div class="materia-list">
      <?php if (!$materias): ?>
        <p class="empty">Aún no tienes materias. Crea la primera a la derecha.</p>
      <?php else: ?>
        <?php foreach ($materias as $mat): ?>
          <div class="materia-item">
            <span class="color-swatch" style="background:<?= e($mat['color']) ?>"></span>
            <strong><?= e($mat['nombre']) ?></strong>
            <small><?= (int) $mat['total_tareas'] ?> tarea(s)</small>
            <a class="small-action" href="materias.php?editar=<?= (int) $mat['id_materia'] ?>">Editar</a>
            <a class="small-action danger-action"
               href="materias.php?eliminar=<?= (int) $mat['id_materia'] ?>"
               onclick="return confirm('¿Eliminar esta materia?');">Eliminar</a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="section-card form-card">
    <h3><?= $editar ? 'Editar materia' : 'Nueva materia' ?></h3>
    <p class="card-heading" style="padding:0;margin:0"><span style="font-size:.78rem;color:var(--muted)">
      <?= $editar ? 'Actualiza nombre o color.' : 'Cada materia pertenece solo a tu cuenta.' ?>
    </span></p>
    <form method="post">
      <input type="hidden" name="accion" value="<?= $editar ? 'editar' : 'crear' ?>">
      <?php if ($editar): ?>
        <input type="hidden" name="id_materia" value="<?= (int) $editar['id_materia'] ?>">
      <?php endif; ?>
      <label for="nombre">Nombre</label>
      <input id="nombre" name="nombre" maxlength="80" required
             value="<?= e($editar['nombre'] ?? '') ?>">
      <label for="color">Color</label>
      <input id="color" name="color" type="color"
             value="<?= e($editar['color'] ?? '#6366f1') ?>">
      <div class="form-actions">
        <?php if ($editar): ?>
          <a class="btn btn-outline" href="materias.php">Cancelar</a>
        <?php endif; ?>
        <button class="btn btn-primary" type="submit"><?= $editar ? 'Guardar' : 'Crear' ?></button>
      </div>
    </form>
  </section>
</div>

<?php require __DIR__ . '/includes/layout_fin.php'; ?>
