<?php
if (!isset($tituloPagina)) {
    $tituloPagina = 'Inicio';
}
$usuarioNombre = $_SESSION['nombre'] ?? 'Estudiante';
$puntosSesion  = (int) ($_SESSION['puntos_totales'] ?? 0);
$nivelSesion   = calcularNivel($puntosSesion);
$partesNombre  = preg_split('/\s+/', trim($usuarioNombre)) ?: ['E'];
$inicial       = mb_strtoupper(mb_substr($partesNombre[0], 0, 1));
if (isset($partesNombre[1]) && $partesNombre[1] !== '') {
    $inicial .= mb_strtoupper(mb_substr($partesNombre[1], 0, 1));
}
$flash         = obtenerMensaje();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= e($tituloPagina) ?> | Schoolgy</title>
  <link rel="icon" href="img/logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="css/app.css">
</head>
<body>
<div class="app-shell">
  <div class="sidebar-overlay" id="sidebarOverlay" hidden></div>
  <aside class="sidebar" id="sidebar">
    <a class="side-logo" href="dashboard.php">
      <img class="brand-logo" src="img/logo.svg" alt="">
      <span>
        School<span class="accent">gy</span>
        <span class="brand-tag">Organizador escolar</span>
      </span>
    </a>
    <p class="menu-label">MENÚ PRINCIPAL</p>
    <nav class="side-nav" aria-label="Principal">
      <a class="side-link <?= claseActiva('dashboard.php') ?>" href="dashboard.php"><span class="nav-ico">⌂</span>Dashboard</a>
      <a class="side-link <?= claseActiva('tareas.php') ?> <?= claseActiva('tarea_form.php') ?>" href="tareas.php"><span class="nav-ico">☑</span>Mis tareas</a>
      <a class="side-link <?= claseActiva('materias.php') ?>" href="materias.php"><span class="nav-ico">◇</span>Materias</a>
      <a class="side-link <?= claseActiva('completadas.php') ?>" href="completadas.php"><span class="nav-ico">✓</span>Completadas</a>
      <a class="side-link <?= claseActiva('progreso.php') ?>" href="progreso.php"><span class="nav-ico">★</span>Mi progreso</a>
      <a class="side-link <?= claseActiva('perfil.php') ?>" href="perfil.php"><span class="nav-ico">●</span>Perfil</a>
    </nav>
    <div class="sidebar-bottom">
      <div class="sidebar-tip">
        <span>✦ Nivel actual</span>
        <strong>Nivel <?= (int) $nivelSesion ?></strong>
        <small><?= (int) $puntosSesion ?> puntos · ¡sigue cumpliendo entregas!</small>
      </div>
      <a class="side-link logout" href="logout.php"><span class="nav-ico">↪</span>Cerrar sesión</a>
    </div>
  </aside>

  <main class="main-area">
    <header class="topbar">
      <div class="topbar-left">
        <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
          <img src="img/icon-menu.svg" alt="">
        </button>
        <div>
          <span class="topbar-label">ORGANIZADOR DE TAREAS</span>
          <h2><?= e($tituloPagina) ?></h2>
        </div>
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
