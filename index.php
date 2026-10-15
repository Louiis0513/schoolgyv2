<?php
session_start();
require_once __DIR__ . '/includes/funciones.php';
$logueado = usuarioLogueado();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Schoolgy — Organiza, cumple, gana puntos</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/app.css">
</head>
<body>
<div id="landing">
  <header class="landing-nav">
    <div class="logo"><span class="logo-icon">🎓</span> Schoolgy</div>
    <div class="nav-buttons">
      <?php if ($logueado): ?>
        <a class="btn btn-primary" href="dashboard.php">Ir al panel</a>
      <?php else: ?>
        <a class="btn btn-ghost" href="login.php">Iniciar sesión</a>
        <a class="btn btn-primary" href="registro.php">Registrarse</a>
      <?php endif; ?>
    </div>
  </header>

  <section class="hero">
    <div>
      <span class="tagline">¡Transforma tu control académico!</span>
      <h1>Organiza tus tareas, suma puntos y supera tus niveles</h1>
      <p>
        Schoolgy ayuda a los estudiantes a gestionar entregas, proyectos y evaluaciones en un solo lugar.
        Convierte la rutina escolar en una experiencia visual, motivadora y libre de estrés.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= $logueado ? 'dashboard.php' : 'registro.php' ?>">Comenzar ahora</a>
        <a class="btn btn-outline" href="#caracteristicas">Saber más</a>
      </div>
    </div>
    <div class="mockup-card">
      <div class="trophy">🏆</div>
      <h3>¡Nivel 3 alcanzado!</h3>
      <p>+100 puntos por completar “Taller de derivadas”</p>
    </div>
  </section>

  <section id="caracteristicas" class="features">
    <article>
      <strong style="color:var(--danger)">✕</strong>
      <h3>Adiós al desorden</h3>
      <p>Olvídate de las notas sueltas y los olvidos de fechas límite que te causan estrés.</p>
    </article>
    <article>
      <strong style="color:var(--primary)">★</strong>
      <h3>Gamificación escolar</h3>
      <p>Gana puntos al cumplir tus tareas a tiempo y sube de nivel académico en tu perfil.</p>
    </article>
    <article>
      <strong style="color:var(--success)">◇</strong>
      <h3>Organización por colores</h3>
      <p>Crea tus materias personales con colores para identificar todo más fácil.</p>
    </article>
  </section>

  <footer class="landing-footer">
    Schoolgy · Ficha 3260309 · “La forma más fácil y divertida de cumplir con tus tareas.”
  </footer>
</div>
</body>
</html>
