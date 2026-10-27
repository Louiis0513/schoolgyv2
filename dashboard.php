<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
enviarHeadersCache('privado');
requiereLogin();

$id = (int) $_SESSION['id_usuario'];

$puntos = obtenerPuntosUsuario($mysqli, $id);
$_SESSION['puntos_totales'] = $puntos;
$nivel  = calcularNivel($puntos);

$stats = obtenerStatsUsuario($mysqli, $id);
$materias = obtenerMateriasUsuario($mysqli, $id);
$totalMaterias = count($materias);
$listaUrgentes = obtenerUrgentes($mysqli, $id, 6);
$listaAvisos = obtenerAvisos($mysqli, $id, 5);

$tituloPagina = 'Dashboard';
require __DIR__ . '/includes/layout_inicio.php';
?>

<div class="welcome-row">
  <div>
    <h1>Hola, <?= e($_SESSION['nombre']) ?></h1>
    <p>Resumen de entregas, recordatorios y tu nivel académico.</p>
  </div>
  <a class="btn btn-primary" href="tarea_form.php">+ Nueva tarea</a>
</div>

<div class="dash-banner">
  <img src="img/dashboard-banner.jpg" alt="">
  <div class="dash-banner-overlay">
    <h2>Semana escolar bajo control</h2>
    <p>
      Tienes <?= (int) ($stats['pendientes'] ?? 0) ?> pendientes ·
      <?= (int) ($stats['vencidas'] ?? 0) ?> vencidas ·
      Nivel <?= $nivel ?> con <?= $puntos ?> puntos.
    </p>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-heading"><span>Pendientes</span><span class="stat-ico">☑</span></div>
    <strong><?= (int) ($stats['pendientes'] ?? 0) ?></strong>
    <small>Por entregar</small>
  </div>
  <div class="stat-card">
    <div class="stat-heading"><span>Vencidas</span><span class="stat-ico">⚠</span></div>
    <strong><?= (int) ($stats['vencidas'] ?? 0) ?></strong>
    <small>Prioridad alta automática</small>
  </div>
  <div class="stat-card">
    <div class="stat-heading"><span>Completadas</span><span class="stat-ico">✓</span></div>
    <strong><?= (int) ($stats['completadas'] ?? 0) ?></strong>
    <small>Ya hechas</small>
  </div>
  <div class="stat-card">
    <div class="stat-heading"><span>Nivel <?= $nivel ?></span><span class="stat-ico">★</span></div>
    <strong><?= $puntos ?></strong>
    <small><?= $totalMaterias ?> materias · <?= progresoNivel($puntos) ?>/100 al siguiente nivel</small>
    <div class="progress-bar"><span style="width:<?= progresoNivel($puntos) ?>%"></span></div>
  </div>
</div>

<div class="quick-grid">
  <a class="quick-link" href="tareas.php">
    <span class="stat-ico">☑</span>
    <div><strong>Ver mis tareas</strong><span>Filtra por materia y prioridad</span></div>
  </a>
  <a class="quick-link" href="materias.php">
    <span class="stat-ico">◇</span>
    <div><strong>Gestionar materias</strong><span><?= $totalMaterias ?> asignaturas activas</span></div>
  </a>
  <a class="quick-link" href="progreso.php">
    <span class="stat-ico">★</span>
    <div><strong>Mi progreso</strong><span>Nivel <?= $nivel ?> · <?= $puntos ?> pts</span></div>
  </a>
</div>

<?php if ($listaAvisos): ?>
<section class="section-card">
  <div class="card-heading">
    <div>
      <h3>Recordatorios</h3>
      <p>Avisos activos (las completadas ya no generan recordatorios).</p>
    </div>
  </div>
  <div style="padding:0 20px 16px">
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
  <div class="table-wrap table-desktop">
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
  <div class="task-cards">
    <?php if (!$listaUrgentes): ?>
      <p class="empty">No tienes tareas pendientes. ¡Crea una!</p>
    <?php else: ?>
      <?php foreach ($listaUrgentes as $t): ?>
        <article class="task-card">
          <div class="task-card-top">
            <div class="task-card-heading">
              <div class="task-title"><?= e($t['titulo']) ?></div>
            </div>
            <?= badgeEstado($t) ?>
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
              <dt>Prioridad</dt>
              <dd><?= badgePrioridad(prioridadEfectiva($t)) ?></dd>
            </div>
            <div>
              <dt>Puntos</dt>
              <dd><?= (int) $t['puntos'] ?> pts</dd>
            </div>
          </dl>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/layout_fin.php'; ?>
