<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
enviarHeadersCache('privado');
requiereLogin();

$id = (int) $_SESSION['id_usuario'];
$idTarea = (int) ($_GET['id'] ?? 0);
$error = '';
$tarea = null;

if ($idTarea > 0) {
    $tarea = obtenerTareaUsuario($mysqli, $id, $idTarea);
    if (!$tarea) {
        guardarMensaje('Tarea no encontrada.', 'error');
        header('Location: tareas.php');
        exit;
    }
}

$materias = obtenerMateriasUsuario($mysqli, $id);

if (!$materias) {
    guardarMensaje('Primero crea al menos una materia.', 'error');
    header('Location: materias.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fechaEnt    = trim($_POST['fecha_entrega'] ?? '');
    $fechaAviso  = trim($_POST['fecha_aviso'] ?? '');
    $prioridad   = $_POST['prioridad'] ?? '';
    $idMateria   = (int) ($_POST['id_materia'] ?? 0);

    $hoy = new DateTime('today');

    if (mb_strlen($titulo) < 3 || mb_strlen($titulo) > 150) {
        $error = 'El título debe tener entre 3 y 150 caracteres.';
    } elseif (!in_array($prioridad, ['alta', 'media', 'baja'], true)) {
        $error = 'La prioridad debe ser alta, media o baja.';
    } elseif (!materiaDelUsuario($mysqli, $idMateria, $id)) {
        $error = 'La materia no existe o no es tuya.';
    } elseif ($fechaEnt === '') {
        $error = 'La fecha de entrega es obligatoria.';
    } else {
        $dtEntrega = DateTime::createFromFormat('Y-m-d\TH:i', $fechaEnt)
            ?: DateTime::createFromFormat('Y-m-d H:i', $fechaEnt);
        if (!$dtEntrega) {
            $error = 'Fecha de entrega inválida.';
        } elseif (!$tarea && $dtEntrega < $hoy) {
            $error = 'La fecha de entrega no puede ser anterior a hoy.';
        } else {
            $avisoSql = null;
            if ($fechaAviso !== '') {
                $dtAviso = DateTime::createFromFormat('Y-m-d\TH:i', $fechaAviso)
                    ?: DateTime::createFromFormat('Y-m-d H:i', $fechaAviso);
                if (!$dtAviso) {
                    $error = 'Fecha de aviso inválida.';
                } elseif ($dtAviso >= $dtEntrega) {
                    $error = 'La fecha de aviso debe ser anterior a la entrega.';
                } else {
                    $avisoSql = $dtAviso->format('Y-m-d H:i:s');
                }
            }

            if ($error === '') {
                $entregaSql = $dtEntrega->format('Y-m-d H:i:s');
                $puntos = puntosPorPrioridad($prioridad);
                $desc = $descripcion !== '' ? $descripcion : null;
                $antes = $tarea ? cargarTareaConMateria($mysqli, $id, $idTarea) : null;

                if ($tarea) {
                    $sql = 'UPDATE tarea
                            SET titulo = ?, descripcion = ?, fecha_entrega = ?, fecha_aviso = ?,
                                prioridad = ?, puntos = ?, id_materia = ?
                            WHERE id_tarea = ? AND id_usuario = ? AND estado = \'pendiente\'';
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param(
                        'sssssiiii',
                        $titulo,
                        $desc,
                        $entregaSql,
                        $avisoSql,
                        $prioridad,
                        $puntos,
                        $idMateria,
                        $idTarea,
                        $id
                    );
                    $stmt->execute();
                    cacheParcheTarea($mysqli, $id, $idTarea, $antes);
                    guardarMensaje('Tarea actualizada.');
                } else {
                    $sql = 'INSERT INTO tarea
                            (titulo, descripcion, fecha_entrega, fecha_aviso, prioridad, estado, puntos, id_usuario, id_materia)
                            VALUES (?, ?, ?, ?, ?, \'pendiente\', ?, ?, ?)';
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param(
                        'sssssiii',
                        $titulo,
                        $desc,
                        $entregaSql,
                        $avisoSql,
                        $prioridad,
                        $puntos,
                        $id,
                        $idMateria
                    );
                    $stmt->execute();
                    $nuevoId = (int) $mysqli->insert_id;
                    if ($nuevoId > 0) {
                        cacheParcheTarea($mysqli, $id, $nuevoId);
                    }
                    guardarMensaje('Tarea registrada.');
                }
                header('Location: tareas.php');
                exit;
            }
        }
    }
}

// Valores del formulario
$vTitulo = $_POST['titulo'] ?? ($tarea['titulo'] ?? '');
$vDesc   = $_POST['descripcion'] ?? ($tarea['descripcion'] ?? '');
$vPrio   = $_POST['prioridad'] ?? ($tarea['prioridad'] ?? 'media');
$vMat    = (int) ($_POST['id_materia'] ?? ($tarea['id_materia'] ?? 0));

function aDatetimeLocal(?string $sqlFecha): string
{
    if (!$sqlFecha) {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $sqlFecha);
    return $dt ? $dt->format('Y-m-d\TH:i') : '';
}

$vEntrega = $_POST['fecha_entrega'] ?? aDatetimeLocal($tarea['fecha_entrega'] ?? null);
$vAviso   = $_POST['fecha_aviso'] ?? aDatetimeLocal($tarea['fecha_aviso'] ?? null);

$tituloPagina = $tarea ? 'Editar tarea' : 'Registrar tarea';
require __DIR__ . '/includes/layout_inicio.php';
?>

<div class="page-heading">
  <div>
    <h1><?= e($tituloPagina) ?></h1>
    <p>Materia, fecha, prioridad y puntos automáticos.</p>
  </div>
  <a class="btn btn-outline" href="tareas.php">← Volver</a>
</div>

<section class="section-card form-card" style="max-width:820px">
  <?php if ($error): ?>
    <p class="form-error"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post">
    <div class="form-grid">
      <div class="form-span">
        <label for="titulo">Título</label>
        <input id="titulo" name="titulo" minlength="3" maxlength="150" required value="<?= e($vTitulo) ?>">
      </div>
      <div class="form-span">
        <label for="descripcion">Descripción (opcional)</label>
        <textarea id="descripcion" name="descripcion" rows="3"><?= e($vDesc) ?></textarea>
      </div>
      <div>
        <label for="fecha_entrega">Fecha de entrega</label>
        <input id="fecha_entrega" name="fecha_entrega" type="datetime-local" required value="<?= e($vEntrega) ?>">
      </div>
      <div>
        <label for="fecha_aviso">Fecha de aviso (opcional)</label>
        <input id="fecha_aviso" name="fecha_aviso" type="datetime-local" value="<?= e($vAviso) ?>">
      </div>
      <div>
        <label for="prioridad">Prioridad</label>
        <select id="prioridad" name="prioridad" required>
          <option value="alta" <?= $vPrio === 'alta' ? 'selected' : '' ?>>Alta (30 pts)</option>
          <option value="media" <?= $vPrio === 'media' ? 'selected' : '' ?>>Media (20 pts)</option>
          <option value="baja" <?= $vPrio === 'baja' ? 'selected' : '' ?>>Baja (10 pts)</option>
        </select>
      </div>
      <div>
        <label for="id_materia">Materia</label>
        <select id="id_materia" name="id_materia" required>
          <?php foreach ($materias as $m): ?>
            <option value="<?= (int) $m['id_materia'] ?>" <?= $vMat === (int) $m['id_materia'] ? 'selected' : '' ?>>
              <?= e($m['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $tarea ? 'Guardar cambios' : 'Registrar tarea' ?></button>
    </div>
  </form>
</section>

<?php require __DIR__ . '/includes/layout_fin.php'; ?>
