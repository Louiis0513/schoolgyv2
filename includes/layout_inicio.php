<?php
if (!isset($tituloPagina)) {
    $tituloPagina = 'Inicio';
}
$usuarioNombre = $_SESSION['nombre'] ?? 'Estudiante';
$puntosSesion  = (int) ($_SESSION['puntos_totales'] ?? 0);
$nivelSesion   = calcularNivel($puntosSesion);
$inicial       = mb_strtoupper(mb_substr($usuarioNombre, 0, 1));
$flash         = obtenerMensaje();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($tituloPagina) ?> | Schoolgy</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/app.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <a class="logo side-logo" href="dashboard.php">
      <span class="logo-icon">🎓</span>School<span class="accent">gy</span>
    </a>
    <p class="menu-label">MENÚ PRINCIPAL</p>
    <nav class="side-nav">
      <a class="side-link <?= claseActiva('dashboard.php') ?>" href="dashboard.php"><span>⌂</span>Dashboard</a>
      <a class="side-link <?= claseActiva('tareas.php') ?> <?= claseActiva('tarea_form.php') ?>" href="tareas.php"><span>☑</span>Mis tareas</a>
      <a class="side-link <?= claseActiva('materias.php') ?>" href="materias.php"><span>◇</span>Materias</a>
      <a class="side-link <?= claseActiva('completadas.php') ?>" href="completadas.php"><span>✓</span>Completadas</a>
      <a class="side-link <?= claseActiva('progreso.php') ?>" href="progreso.php"><span>★</span>Mi progreso</a>
      <a class="side-link <?= claseActiva('perfil.php') ?>" href="perfil.php"><span>●</span>Perfil</a>
    </nav>
    <div class="sidebar-bottom">
      <div class="sidebar-tip">
        <span>✦</span>
        <strong>Nivel <?= (int) $nivelSesion ?></strong>
        <small><?= (int) $puntosSesion ?> puntos · ¡sigue cumpliendo!</small>
      </div>
      <a class="side-link logout" href="logout.php"><span>↪</span>Cerrar sesión</a>
    </div>
  </aside>

  <main class="main-area">
    <header class="topbar">
      <div>
        <span class="topbar-label">ORGANIZADOR DE TAREAS</span>
        <h2><?= e($tituloPagina) ?></h2>
      </div>
      <div class="user-chip">
        <div class="avatar"><?= e($inicial) ?></div>
        <div>
          <strong><?= e($usuarioNombre) ?></strong>
          <small>Nivel <?= (int) $nivelSesion ?> · <?= (int) $puntosSesion ?> pts</small>
        </div>
      </div>
    </header>

    <div class="content-area">
      <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div>
      <?php endif; ?>
