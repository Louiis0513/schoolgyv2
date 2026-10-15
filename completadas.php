<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
requiereLogin();

$id = (int) $_SESSION['id_usuario'];

$stmt = $mysqli->prepare(
    "SELECT t.*, m.nombre AS materia, m.color
     FROM tarea t
     INNER JOIN materia m ON m.id_materia = t.id_materia
     WHERE t.id_usuario = ? AND t.estado = 'completada'
     ORDER BY t.completada_en DESC, t.fecha_entrega DESC"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$tareas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalPts = 0;
foreach ($tareas as $t) {
    $totalPts += (int) $t['puntos'];
}

$tituloPagina = 'Tareas completadas';
require __DIR__ . '/includes/layout_inicio.php';
?>

<div class="page-heading">
  <div>
    <h1>Tareas completadas</h1>
    <p>Historial del trabajo validado y puntos ganados.</p>
  </div>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
  <div class="stat-card">
    <div class="stat-heading"><span>Completadas</span><span>✓</span></div>
    <strong><?= count($tareas) ?></strong>
  </div>
  <div class="stat-card">
    <div class="stat-heading"><span>Puntos de estas tareas</span><span>★</span></div>
    <strong><?= $totalPts ?></strong>
  </div>
</div>

<section class="section-card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tarea</th>
          <th>Materia</th>
          <th>Entrega</th>
          <th>Completada</th>
          <th>Prioridad</th>
          <th>Puntos</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$tareas): ?>
          <tr><td colspan="6" class="empty">Aún no has completado tareas.</td></tr>
        <?php else: ?>
          <?php foreach ($tareas as $t): ?>
            <tr>
              <td class="task-title"><?= e($t['titulo']) ?></td>
              <td>
                <span class="materia-dot" style="background:<?= e($t['color']) ?>"></span>
                <?= e($t['materia']) ?>
              </td>
              <td><?= e(formatearFecha($t['fecha_entrega'])) ?></td>
              <td><?= e(formatearFecha($t['completada_en'])) ?></td>
              <td><?= badgePrioridad($t['prioridad']) ?></td>
              <td>+<?= (int) $t['puntos'] ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/includes/layout_fin.php'; ?>
