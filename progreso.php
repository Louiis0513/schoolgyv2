<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
enviarHeadersCache('privado');
requiereLogin();

$id = (int) $_SESSION['id_usuario'];

$puntos = obtenerPuntosUsuario($mysqli, $id);
$_SESSION['puntos_totales'] = $puntos;
$nivel = calcularNivel($puntos);
$barra = progresoNivel($puntos);

$stats = obtenerStatsUsuario($mysqli, $id);
$materias = obtenerProgresoPorMateria($mysqli, $id);

$tituloPagina = 'Mi progreso';
require __DIR__ . '/includes/layout_inicio.php';
?>

<div class="page-heading">
  <div>
    <h1>Mi progreso</h1>
    <p>Puntos, nivel y tareas completadas para mantener la motivación.</p>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-heading"><span>Nivel</span><span>★</span></div>
    <strong><?= $nivel ?></strong>
    <small><?= $barra ?>/100 hacia el nivel <?= $nivel + 1 ?></small>
    <div class="progress-bar"><span style="width:<?= $barra ?>%"></span></div>
  </div>
  <div class="stat-card">
    <div class="stat-heading"><span>Puntos totales</span></div>
    <strong><?= $puntos ?></strong>
  </div>
  <div class="stat-card">
    <div class="stat-heading"><span>Completadas</span></div>
    <strong><?= (int) ($stats['completadas'] ?? 0) ?></strong>
  </div>
  <div class="stat-card">
    <div class="stat-heading"><span>Pendientes / vencidas</span></div>
    <strong><?= (int) ($stats['pendientes'] ?? 0) ?></strong>
    <small><?= (int) ($stats['vencidas'] ?? 0) ?> vencidas</small>
  </div>
</div>

<section class="section-card">
  <div class="card-heading">
    <div>
      <h3>Avance por materia</h3>
      <p>Completadas vs total en cada asignatura.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Materia</th>
          <th>Completadas</th>
          <th>Total</th>
          <th>Progreso</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$materias): ?>
          <tr><td colspan="4" class="empty">Crea materias para ver tu avance.</td></tr>
        <?php else: ?>
          <?php foreach ($materias as $m):
              $total = (int) $m['total'];
              $hechas = (int) $m['hechas'];
              $pct = $total > 0 ? (int) round(($hechas / $total) * 100) : 0;
          ?>
            <tr>
              <td>
                <span class="materia-dot" style="background:<?= e($m['color']) ?>"></span>
                <?= e($m['nombre']) ?>
              </td>
              <td><?= $hechas ?></td>
              <td><?= $total ?></td>
              <td style="min-width:160px">
                <div class="progress-bar"><span style="width:<?= $pct ?>%"></span></div>
                <small style="color:var(--muted)"><?= $pct ?>%</small>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/includes/layout_fin.php'; ?>
