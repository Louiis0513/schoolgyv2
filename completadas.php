<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
enviarHeadersCache('privado');
requiereLogin();

$id = (int) $_SESSION['id_usuario'];

$todas = obtenerCompletadas($mysqli, $id);
$totalTareas = count($todas);
$totalPts = 0;
foreach ($todas as $t) {
    $totalPts += (int) ($t['puntos'] ?? 0);
}

$pag = calcularPaginacion($totalTareas, 8);
$tareas = array_slice($todas, $pag['offset'], $pag['por_pagina']);

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
