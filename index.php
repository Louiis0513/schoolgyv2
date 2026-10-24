<?php
session_start();
require_once __DIR__ . '/includes/funciones.php';
$logueado = usuarioLogueado();
$cssVer = @filemtime(__DIR__ . '/css/app.css') ?: time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Schoolgy — Organiza, cumple, gana puntos</title>
  <link rel="icon" href="img/logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="css/app.css?v=<?= (int) $cssVer ?>">
</head>
<body>
<div id="landing">
  <header class="landing-nav">
    <div class="logo">
      <img class="brand-logo" src="img/logo.svg" alt="">
      Schoolgy
    </div>
    <nav class="landing-menu" aria-label="Secciones">
      <a href="#propuesta">Propuesta</a>
      <a href="#como-funciona">Cómo funciona</a>
      <a href="#caracteristicas">Beneficios</a>
    </nav>
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
      <span class="tagline">Liceo Gran Virrey · Ficha 3260309</span>
      <h1>Organiza tus tareas, suma puntos y supera tus niveles</h1>
      <p>
        Schoolgy concentra entregas, materias y recordatorios en un solo panel.
        Convierte la rutina escolar en una experiencia visual, motivadora y sin estrés.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= $logueado ? 'dashboard.php' : 'registro.php' ?>">Comenzar ahora</a>
        <a class="btn btn-outline" href="#propuesta">Saber más</a>
      </div>
      <ul class="hero-points">
        <li>Pendientes centralizados</li>
        <li>Prioridades por fecha</li>
        <li>Recordatorios a tiempo</li>
      </ul>
    </div>
    <div class="hero-visual">
      <img src="img/hero-school.jpg" alt="Estudiantes organizando su trabajo escolar">
      <div class="hero-badge">
        <strong>¡Nivel 3 alcanzado!</strong>
        <span>+30 pts por completar “Taller de derivadas”</span>
      </div>
    </div>
  </section>

  <section class="landing-stats" aria-label="Resumen rápido">
    <div class="landing-stats-inner">
      <div>
        <strong>1 panel</strong>
        <span>para todas tus materias</span>
      </div>
      <div>
        <strong>3 prioridades</strong>
        <span>alta, media y baja</span>
      </div>
      <div>
        <strong>+ puntos</strong>
        <span>cada vez que cumples</span>
      </div>
      <div>
        <strong>0 olvidos</strong>
        <span>con avisos de entrega</span>
      </div>
    </div>
  </section>

  <section id="propuesta" class="story-section">
    <div class="story-intro">
      <span class="section-kicker">Propuesta de valor</span>
      <h2>De la carga académica al control</h2>
      <p>Entiende el problema, la solución y lo que puedes hacer dentro de Schoolgy.</p>
    </div>

    <div class="story-grid">
      <article class="story-card story-problema">
        <div class="story-icon" aria-hidden="true">!</div>
        <span class="story-label">Problema</span>
        <h3>Estrés por desorganización</h3>
        <p>
          Los estudiantes suelen experimentar estrés académico por la falta de organización,
          los olvidos de fechas importantes y la acumulación de tareas.
        </p>
        <ul class="story-list">
          <li>Notas sueltas y chats perdidos</li>
          <li>Fechas de entrega que se olvidan</li>
          <li>Tareas acumuladas sin prioridad</li>
        </ul>
      </article>

      <article class="story-card story-solucion">
        <div class="story-icon" aria-hidden="true">★</div>
        <span class="story-label">Solución</span>
        <h3>Organizador interactivo</h3>
        <p>
          Schoolgy ofrece un organizador interactivo donde centraliza los pendientes,
          establece prioridades según las fechas de entrega y envía recordatorios
          para mejorar la organización y reducir el estrés académico.
        </p>
        <ul class="story-list">
          <li>Todo en un solo panel visual</li>
          <li>Prioridad automática si se vence</li>
          <li>Avisos antes de cada entrega</li>
        </ul>
      </article>

      <article class="story-card story-resumen">
        <div class="story-icon" aria-hidden="true">✓</div>
        <span class="story-label">Resumen</span>
        <h3>Qué puedes hacer</h3>
        <p>
          Con Schoolgy se puede crear, editar y eliminar las materias que se necesite
          para organizar actividades académicas. Dentro de cada materia se puede registrar
          las tareas con su título, fecha y prioridad, facilitando el seguimiento de
          pendientes correspondientes.
        </p>
        <ul class="story-list">
          <li>CRUD de materias con color</li>
          <li>Tareas con título, fecha y puntos</li>
          <li>Seguimiento claro de pendientes</li>
        </ul>
      </article>
    </div>
  </section>

  <section id="como-funciona" class="steps-section">
    <div class="steps-intro">
      <span class="section-kicker">Cómo funciona</span>
      <h2>Tres pasos para ordenar tu semana</h2>
      <p>Empieza en minutos y mantén el control sin hojas sueltas.</p>
    </div>
    <ol class="steps-grid">
      <li class="step-card">
        <span class="step-num">01</span>
        <h3>Crea tus materias</h3>
        <p>Registra cada asignatura con un color para identificarlas al instante en el panel.</p>
      </li>
      <li class="step-card">
        <span class="step-num">02</span>
        <h3>Agrega tus tareas</h3>
        <p>Define título, fecha de entrega, prioridad y un recordatorio opcional por actividad.</p>
      </li>
      <li class="step-card">
        <span class="step-num">03</span>
        <h3>Cumple y sube de nivel</h3>
        <p>Marca como completadas, gana puntos según la prioridad y avanza en tu progreso académico.</p>
      </li>
    </ol>
  </section>

  <section id="caracteristicas" class="features-wrap">
    <div class="features-intro">
      <span class="section-kicker">Beneficios</span>
      <h2>Diseñado para estudiantes ocupados</h2>
      <p>Menos caos diario, más claridad sobre qué entregar y cuándo.</p>
    </div>
    <div class="features">
      <article>
        <div class="feat-icon">✕</div>
        <h3>Adiós al desorden</h3>
        <p>Olvídate de las notas sueltas y los olvidos de fechas límite que te causan estrés.</p>
      </article>
      <article>
        <div class="feat-icon">★</div>
        <h3>Gamificación escolar</h3>
        <p>Gana puntos al cumplir tus tareas a tiempo y sube de nivel académico en tu perfil.</p>
      </article>
      <article>
        <div class="feat-icon">◇</div>
        <h3>Materias con color</h3>
        <p>Crea tus materias personales con colores para identificar entregas al instante.</p>
      </article>
      <article>
        <div class="feat-icon">☑</div>
        <h3>Filtros inteligentes</h3>
        <p>Revisa pendientes por materia, estado o prioridad y enfócate en lo urgente primero.</p>
      </article>
      <article>
        <div class="feat-icon">⏱</div>
        <h3>Recordatorios activos</h3>
        <p>Recibe avisos cuando se acerca una entrega para evitar sorpresas de último minuto.</p>
      </article>
      <article>
        <div class="feat-icon">📈</div>
        <h3>Progreso visible</h3>
        <p>Consulta puntos, nivel y avance por materia para mantener la motivación semana a semana.</p>
      </article>
    </div>
  </section>

  <section class="landing-cta">
    <div class="landing-cta-inner">
      <div>
        <h2>Empieza a organizar tu carga académica hoy</h2>
        <p>Crea tu cuenta, registra tus materias y deja de acumular pendientes sin control.</p>
      </div>
      <div class="landing-cta-actions">
        <a class="btn btn-primary" href="<?= $logueado ? 'dashboard.php' : 'registro.php' ?>">
          <?= $logueado ? 'Ir al panel' : 'Crear cuenta gratis' ?>
        </a>
        <?php if (!$logueado): ?>
          <a class="btn btn-outline landing-cta-secondary" href="login.php">Ya tengo cuenta</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <footer class="landing-footer">
    Schoolgy · Ficha 3260309 · “La forma más fácil y divertida de cumplir con tus tareas.”
  </footer>
</div>
</body>
</html>
