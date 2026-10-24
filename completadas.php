<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
requiereLogin();

$id = (int) $_SESSION['id_usuario'];

$count = $mysqli->prepare(
    "SELECT COUNT(*) AS total FROM tarea WHERE id_usuario = ? AND estado = 'completada'"
);
$count->bind_param('i', $id);
$count->execute();
$totalTareas = (int) $count->get_result()->fetch_assoc()['total'];

$ptsStmt = $mysqli->prepare(
    "SELECT COALESCE(SUM(puntos), 0) AS total_pts
     FROM tarea WHERE id_usuario = ? AND estado = 'completada'"
);
$ptsStmt->bind_param('i', $id);
$ptsStmt->execute();
$totalPts = (int) $ptsStmt->get_result()->fetch_assoc()['total_pts'];

$pag = calcularPaginacion($totalTareas, 8);

$stmt = $mysqli->prepare(
    "SELECT t.*, m.nombre AS materia, m.color
     FROM tarea t
     INNER JOIN materia m ON m.id_materia = t.id_materia
     WHERE t.id_usuario = ? AND t.estado = 'completada'
     ORDER BY t.completada_en DESC, t.fecha_entrega DESC
     LIMIT ? OFFSET ?"
);
$lim = $pag['por_pagina'];
$off = $pag['offset'];
$stmt->bind_param('iii', $id, $lim, $off);
$stmt->execute();
$tareas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$tituloPagina = 'Tareas completadas';
require __DIR__ . '/includes/layout_inicio.php';
?>

<div class="page-heading">
  <div>
    <h1>Tareas completadas</h1>
    <p>Historial del trabajo validado y puntos ganados.</p>
  </div>
</div>

<div class="stat-grid stat-grid-2">
  <div class="stat-card">
    <div class="stat-heading"><span>Completadas</span><span class="stat-ico">✓</span></div>
    <strong><?= $totalTareas ?></strong>
  </div>
  <div class="stat-card">
    <div class="stat-heading"><span>Puntos de estas tareas</span><span class="stat-ico">★</span></div>
    <strong><?= $totalPts ?></strong>
  </div>
</div>

<section class="section-card">
  <div class="table-wrap table-desktop">
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
  <div class="task-cards">
    <?php if (!$tareas): ?>
      <p class="empty">Aún no has completado tareas.</p>
    <?php else: ?>
      <?php foreach ($tareas as $t): ?>
        <article class="task-card">
          <div class="task-card-top">
            <div class="task-card-heading">
              <div class="task-title"><?= e($t['titulo']) ?></div>
            </div>
            <span class="badge badge-ok">+<?= (int) $t['puntos'] ?> pts</span>
          </div>
          <dl class="task-card-grid">
            <div>
              <dt>Materia</dt>
              <dd><span class="materia-dot" style="background:<?= e($t['color']) ?>"></span><?= e($t['materia']) ?></dd>
            </div>
            <div>
              <dt>Entrega</dt>
              <dd><?= e(formatearFecha($t['fecha_entrega'])) ?></dd>
            </div>
            <div>
              <dt>Completada</dt>
              <dd><?= e(formatearFecha($t['completada_en'])) ?></dd>
            </div>
            <div>
              <dt>Prioridad</dt>
              <dd><?= badgePrioridad($t['prioridad']) ?></dd>
            </div>
          </dl>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?= renderPaginacion($pag) ?>
</section>

<?php require __DIR__ . '/includes/layout_fin.php'; ?>
