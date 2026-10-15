<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
requiereLogin();

$id = (int) $_SESSION['id_usuario'];

// Refrescar puntos en sesión
$u = $mysqli->prepare('SELECT puntos_totales FROM usuario WHERE id_usuario = ?');
$u->bind_param('i', $id);
$u->execute();
$_SESSION['puntos_totales'] = (int) ($u->get_result()->fetch_assoc()['puntos_totales'] ?? 0);

$puntos = (int) $_SESSION['puntos_totales'];
$nivel  = calcularNivel($puntos);

$stmt = $mysqli->prepare(
    "SELECT
       SUM(estado = 'pendiente') AS pendientes,
       SUM(estado = 'completada') AS completadas,
       SUM(estado = 'pendiente' AND fecha_entrega < NOW()) AS vencidas
     FROM tarea WHERE id_usuario = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$m = $mysqli->prepare('SELECT COUNT(*) AS total FROM materia WHERE id_usuario = ?');
$m->bind_param('i', $id);
$m->execute();
$totalMaterias = (int) $m->get_result()->fetch_assoc()['total'];

$urgentes = $mysqli->prepare(
    "SELECT t.*, m.nombre AS materia, m.color
     FROM tarea t
     INNER JOIN materia m ON m.id_materia = t.id_materia
     WHERE t.id_usuario = ? AND t.estado = 'pendiente'
     ORDER BY t.fecha_entrega ASC
     LIMIT 5"
);
$urgentes->bind_param('i', $id);
$urgentes->execute();
$listaUrgentes = $urgentes->get_result()->fetch_all(MYSQLI_ASSOC);

// Recordatorios: fecha_aviso llegó y aún pendiente (no completadas)
$avisos = $mysqli->prepare(
    "SELECT t.titulo, t.fecha_aviso, t.fecha_entrega, m.nombre AS materia
     FROM tarea t
     INNER JOIN materia m ON m.id_materia = t.id_materia
     WHERE t.id_usuario = ?
       AND t.estado = 'pendiente'
       AND t.fecha_aviso IS NOT NULL
       AND t.fecha_aviso <= NOW()
     ORDER BY t.fecha_aviso ASC
     LIMIT 5"
);
$avisos->bind_param('i', $id);
$avisos->execute();
$listaAvisos = $avisos->get_result()->fetch_all(MYSQLI_ASSOC);

$tituloPagina = 'Dashboard';
require __DIR__ . '/includes/layout_inicio.php';
?>

<div class="welcome-row">
  <div>
    <h1>Hola, <?= e($_SESSION['nombre']) ?></h1>
    <p>Tus entregas más urgentes y tu nivel actual.</p>
  </div>
  <a class="btn btn-primary" href="tarea_form.php">+ Nueva tarea</a>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-heading"><span>Pendientes</span><span>☑</span></div>
    <strong><?= (int) ($stats['pendientes'] ?? 0) ?></strong>
    <small>Por entregar</small>
  </div>
  <div class="stat-card">
    <div class="stat-heading"><span>Vencidas</span><span>⚠</span></div>
    <strong><?= (int) ($stats['vencidas'] ?? 0) ?></strong>
    <small>Prioridad alta automática</small>
  </div>
  <div class="stat-card">
    <div class="stat-heading"><span>Completadas</span><span>✓</span></div>
    <strong><?= (int) ($stats['completadas'] ?? 0) ?></strong>
    <small>Ya hechas</small>
  </div>
  <div class="stat-card">
    <div class="stat-heading"><span>Nivel <?= $nivel ?></span><span>★</span></div>
    <strong><?= $puntos ?></strong>
    <small><?= $totalMaterias ?> materias · <?= progresoNivel($puntos) ?>/100 al siguiente nivel</small>
    <div class="progress-bar"><span style="width:<?= progresoNivel($puntos) ?>%"></span></div>
  </div>
</div>

<?php if ($listaAvisos): ?>
<section class="section-card">
  <div class="card-heading">
    <div>
      <h3>Recordatorios</h3>
      <p>Avisos dentro de la app (las tareas completadas no generan más recordatorios).</p>
    </div>
  </div>
  <div style="padding:0 22px 18px">
    <?php foreach ($listaAvisos as $a): ?>
      <div class="recordatorio">
        <strong><?= e($a['titulo']) ?></strong>
        <?= e($a['materia']) ?> · entrega <?= e(formatearFecha($a['fecha_entrega'])) ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section-card">
  <div class="card-heading">
    <div>
      <h3>Próximas entregas</h3>
      <p>Ordenadas por fecha de entrega.</p>
    </div>
    <a class="small-action" href="tareas.php">Ver todas</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tarea</th>
          <th>Materia</th>
          <th>Entrega</th>
          <th>Prioridad</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$listaUrgentes): ?>
          <tr><td colspan="5" class="empty">No tienes tareas pendientes. ¡Crea una!</td></tr>
        <?php else: ?>
          <?php foreach ($listaUrgentes as $t): ?>
            <tr>
              <td class="task-title"><?= e($t['titulo']) ?></td>
              <td>
                <span class="materia-dot" style="background:<?= e($t['color']) ?>"></span>
                <?= e($t['materia']) ?>
              </td>
              <td><?= e(formatearFecha($t['fecha_entrega'])) ?></td>
              <td><?= badgePrioridad(prioridadEfectiva($t)) ?></td>
              <td><?= badgeEstado($t) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/includes/layout_fin.php'; ?>
