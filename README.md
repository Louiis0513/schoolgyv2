# Schoolgy — Organizador de tareas escolares

Proyecto productivo · Ficha **3260309** · Liceo Gran Virrey · SENA 2026  
Stack: **HTML + CSS + PHP + MySQL (XAMPP)**

Slogan: *“La forma más fácil y divertida de cumplir con tus tareas.”*

## Arranque (XAMPP)

1. Activa **Apache** y **MySQL** en XAMPP.
2. Importa la base: `bd/schoolgy.sql` (phpMyAdmin o CLI).
3. Abre **http://localhost/Schoolgy/**

### Credenciales demo

| Cuenta | Correo | Contraseña | Contenido |
|--------|--------|------------|-----------|
| Principal | `sara@schoolgy.com` | `alumno123` | 7 materias · 14 tareas · 120 pts (nivel 2) |
| Vacía | `demo@schoolgy.com` | `alumno123` | Sin materias (flujo “empezar de cero”) |

## Qué incluye (presentación)

- UI índigo con sidebar fijo, tipografía Sora/Manrope y drawer móvil
- Landing, login/registro con panel visual e imágenes escolares
- Dashboard con banner, stats, accesos rápidos y recordatorios
- Tareas con filtros + vista tabla (desktop) / cards (móvil)
- Seed realista: Matemáticas, Español, Ciencias, Sociales, Inglés, Tecnología, Ed. Física

## Estructura

```
Schoolgy/
├── bd/schoolgy.sql
├── css/app.css
├── js/app.js
├── img/                 ← logo, iconos, fotos escolares
├── includes/
│   ├── conexion.php
│   ├── funciones.php
│   ├── layout_inicio.php
│   └── layout_fin.php
├── index.php · login.php · registro.php · logout.php
├── dashboard.php · materias.php · tareas.php · tarea_form.php
├── completadas.php · progreso.php · perfil.php
└── practicas/           ← ejercicios de clase (no producto)
```

## Reglas de negocio

1. Solo ves/editas tus propias tareas y materias.
2. Pendiente con fecha pasada → **vencida** y prioridad efectiva **alta**.
3. Completar = transacción (estado + puntos al usuario).
4. Completada → no genera más recordatorios.
5. Puntos: alta 30 · media 20 · baja 10. Nivel = cada 100 pts.
