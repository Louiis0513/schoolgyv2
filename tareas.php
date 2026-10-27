<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
enviarHeadersCache('privado');
requiereLogin();

$id = (int) $_SESSION['id_usuario'];

$filtroMateria   = (int) ($_GET['materia'] ?? 0);
$filtroEstado    = $_GET['estado'] ?? '';
$filtroPrioridad = $_GET['prioridad'] ?? '';

$conservarFiltros = array_filter([
    'materia'   => $filtroMateria > 0 ? $filtroMateria : null,
    'estado'    => in_array($filtroEstado, ['pendiente', 'completada'], true) ? $filtroEstado : null,
    'prioridad' => in_array($filtroPrioridad, ['alta', 'media', 'baja'], true) ? $filtroPrioridad : null,
], static fn ($v) => $v !== null && $v !== '');

function redirectTareas(array $filtros): never
{
    $qs = $filtros ? ('?' . http_build_query($filtros)) : '';
    header('Location: tareas.php' . $qs);
    exit;
}

if (isset($_GET['completar'])) {
    $idTarea = (int) $_GET['completar'];
    $antes = cargarTareaConMateria($mysqli, $id, $idTarea);
    $mysqli->begin_transaction();
    try {
        $s = $mysqli->prepare(
            "SELECT id_tarea, puntos, estado FROM tarea
             WHERE id_tarea = ? AND id_usuario = ? AND estado = 'pendiente' LIMIT 1 FOR UPDATE"
        );
        $s->bind_param('ii', $idTarea, $id);
        $s->execute();
        $tarea = $s->get_result()->fetch_assoc();
        if (!$tarea) {
            throw new RuntimeException('Tarea no encontrada o ya completada.');
        }
        $pts = (int) $tarea['puntos'];
        $up = $mysqli->prepare(
            "UPDATE tarea SET estado = 'completada', completada_en = NOW()
             WHERE id_tarea = ? AND id_usuario = ?"
        );
        $up->bind_param('ii', $idTarea, $id);
        $up->execute();

        $pu = $mysqli->prepare(
            'UPDATE usuario SET puntos_totales = puntos_totales + ? WHERE id_usuario = ?'
        );
        $pu->bind_param('ii', $pts, $id);
        $pu->execute();

        $mysqli->commit();
        $_SESSION['puntos_totales'] = (int) ($_SESSION['puntos_totales'] ?? 0) + $pts;
        cacheParcheTarea($mysqli, $id, $idTarea, $antes);
        cacheParchePuntosUsuario($id, (int) $_SESSION['puntos_totales']);
        guardarMensaje('¡Tarea completada! +' . $pts . ' puntos.');
    } catch (Throwable $e) {
        $mysqli->rollback();
        guardarMensaje($e->getMessage(), 'error');
    }
    redirectTareas($conservarFiltros);
}

if (isset($_GET['eliminar'])) {
    $idTarea = (int) $_GET['eliminar'];
    $antes = cargarTareaConMateria($mysqli, $id, $idTarea);
    $d = $mysqli->prepare('DELETE FROM tarea WHERE id_tarea = ? AND id_usuario = ?');
    $d->bind_param('ii', $idTarea, $id);
    $d->execute();
    if ($d->affected_rows) {
        cacheParcheTareaEliminada($mysqli, $id, $idTarea, $antes);
        guardarMensaje('Tarea eliminada.');
    } else {
        guardarMensaje('No se pudo eliminar la tarea.', 'error');
    }
    redirectTareas($conservarFiltros);
}

$todas = obtenerTareasFiltradas($mysqli, $id, $filtroMateria, $filtroEstado, $filtroPrioridad);
$totalTareas = count($todas);
$pag = calcularPaginacion($totalTareas, 8);
$tareas = array_slice($todas, $pag['offset'], $pag['por_pagina']);

$materias = obtenerMateriasUsuario($mysqli, $id, false);

$qsFiltros = $conservarFiltros ? ('&' . http_build_query($conservarFiltros)) : '';

$tituloPagina = 'Mis tareas';
require __DIR__ . '/includes/layout_inicio.php';
?>

<div class="page-heading">
  <div>
    <h1>Mis tareas</h1>
    <p>Filtra por materia, estado y prioridad. Solo ves tus propias tareas.</p>
  </div>
  <a class="btn btn-primary" href="tarea_form.php">+ Registrar tarea</a>
</div>

<section class="section-card">
  <form class="toolbar" method="get">
    <select name="materia">
      <option value="0">Todas las materias</option>
      <?php foreach ($materias as $m): ?>
        <option value="<?= (int) $m['id_materia'] ?>" <?= $filtroMateria === (int) $m['id_materia'] ? 'selected' : '' ?>>
          <?= e($m['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="estado">
      <option value="">Todos los estados</option>
      <option value="pendiente" <?= $filtroEstado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
      <option value="completada" <?= $filtroEstado === 'completada' ? 'selected' : '' ?>>Completada</option>
    </select>
    <select name="prioridad">
      <option value="">Todas las prioridades</option>
      <option value="alta" <?= $filtroPrioridad === 'alta' ? 'selected' : '' ?>>Alta</option>
      <option value="media" <?= $filtroPrioridad === 'media' ? 'selected' : '' ?>>Media</option>
      <option value="baja" <?= $filtroPrioridad === 'baja' ? 'selected' : '' ?>>Baja</option>
    </select>
    <button class="btn btn-primary" type="submit">Filtrar</button>
    <a class="btn btn-outline" href="tareas.php">Limpiar</a>
  </form>

  <div class="table-wrap table-desktop">
    <table>
      <thead>
        <tr>
          <th>Título</th>
          <th>Materia</th>
          <th>Entrega</th>
          <th>Prioridad</th>
          <th>Estado</th>
          <th>Pts</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$tareas): ?>
          <tr><td colspan="7" class="empty">No hay tareas con esos filtros.</td></tr>
        <?php else: ?>
          <?php foreach ($tareas as $t): ?>
            <tr>
              <td>
                <div class="task-title"><?= e($t['titulo']) ?></div>
                <?php if ($t['descripcion']): ?>
                  <small class="task-desc"><?= e(mb_strimwidth($t['descripcion'], 0, 60, '…')) ?></small>
                <?php endif; ?>
              </td>
              <td>
                <span class="materia-dot" style="background:<?= e($t['color']) ?>"></span>
                <?= e($t['materia']) ?>
              </td>
              <td><?= e(formatearFecha($t['fecha_entrega'])) ?></td>
              <td><?= badgePrioridad(prioridadEfectiva($t)) ?></td>
              <td><?= badgeEstado($t) ?></td>
              <td><?= (int) $t['puntos'] ?></td>
              <td class="acciones-cell">
                <?php if ($t['estado'] === 'pendiente'): ?>
                  <a class="small-action" href="tareas.php?completar=<?= (int) $t['id_tarea'] ?><?= e($qsFiltros) ?>">Completar</a>
                  <a class="small-action" href="tarea_form.php?id=<?= (int) $t['id_tarea'] ?>">Editar</a>
                <?php endif; ?>
                <a class="small-action danger-action"
                   href="tareas.php?eliminar=<?= (int) $t['id_tarea'] ?><?= e($qsFiltros) ?>"
                   onclick="return confirm('¿Eliminar esta tarea?');">Eliminar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="task-cards">
    <?php if (!$tareas): ?>
      <p class="empty">No hay tareas con esos filtros.</p>
    <?php else: ?>
      <?php foreach ($tareas as $t): ?>
        <article class="task-card">
          <div class="task-card-top">
            <div class="task-card-heading">
              <div class="task-title"><?= e($t['titulo']) ?></div>
              <?php if ($t['descripcion']): ?>
                <p class="task-desc"><?= e(mb_strimwidth($t['descripcion'], 0, 100, '…')) ?></p>
              <?php endif; ?>
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
          <div class="acciones-cell task-card-actions">
            <?php if ($t['estado'] === 'pendiente'): ?>
              <a class="small-action" href="tareas.php?completar=<?= (int) $t['id_tarea'] ?><?= e($qsFiltros) ?>">Completar</a>
              <a class="small-action" href="tarea_form.php?id=<?= (int) $t['id_tarea'] ?>">Editar</a>
            <?php endif; ?>
            <a class="small-action danger-action"
               href="tareas.php?eliminar=<?= (int) $t['id_tarea'] ?><?= e($qsFiltros) ?>"
               onclick="return confirm('¿Eliminar esta tarea?');">Eliminar</a>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?= renderPaginacion($pag, $conservarFiltros) ?>
</section>

<?php require __DIR__ . '/includes/layout_fin.php'; ?>
