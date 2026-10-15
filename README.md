# Schoolgy — Organizador de tareas escolares

Proyecto productivo · Ficha **3260309** · Liceo Gran Virrey · SENA 2026  
Stack: **HTML + CSS + PHP + MySQL (XAMPP)**

Slogan: *“La forma más fácil y divertida de cumplir con tus tareas.”*

## Qué es

App web para estudiantes: registran materias y tareas con fecha/prioridad, ganan puntos al completarlas y ven su nivel en el dashboard. Cada usuario solo ve y edita **sus** datos.

## Cómo arrancarlo (XAMPP)

1. Activa **Apache** y **MySQL** en XAMPP.
2. Abre [phpMyAdmin](http://localhost/phpmyadmin).
3. Ve a **Importar** y elige `bd/schoolgy.sql`.
4. En el navegador: **http://localhost/Schoolgy/**

### Usuario de prueba

| Campo | Valor |
|-------|--------|
| Correo | `sara@schoolgy.com` |
| Contraseña | `alumno123` |

## Estructura

```
Schoolgy/
├── bd/schoolgy.sql
├── includes/
│   ├── conexion.php
│   ├── funciones.php
│   ├── layout_inicio.php
│   └── layout_fin.php
├── css/app.css
├── index.php              ← portada
├── login.php / registro.php / logout.php
├── dashboard.php
├── materias.php
├── tareas.php / tarea_form.php
├── completadas.php
├── progreso.php
├── perfil.php
├── schoolgy.html / schoolgy.css   ← maqueta original (referencia)
└── practicas/             ← ejercicios JS/HTML sueltos del repo
```

## Tablas

- `usuario` → `materia` → `tarea`
- Un usuario tiene muchas materias y muchas tareas.
- Una materia agrupa muchas tareas.
- No se borra una materia si aún tiene tareas (RESTRICT).

## Reglas de negocio (implementadas)

1. Solo ves/editas tus propias tareas y materias.
2. Tarea pendiente con fecha pasada se muestra como **vencida** y prioridad efectiva **alta**.
3. Al completar: `UPDATE` estado + sumar `puntos` al usuario (transacción).
4. Completada → ya no aparece en recordatorios del dashboard.
5. Puntos por prioridad: alta 30 · media 20 · baja 10. Nivel = cada 100 pts.
6. Validaciones de registro y nueva tarea según el análisis del grupo.

## Integrantes (análisis)

Leidy Juliana Uribe Escobar · Sara Gabriela Rivillas Mikan · Laura Daniela Gonzales Gonzales · Isabela Silva Erazo · Thanya Gisel Diaz Gonzalez

## Para el equipo

- `schoolgy.html` era solo la maqueta (landing + login visual).
- El sistema real son los `.php` conectados a MySQL.
- Los archivos de `practicas/` no forman parte del producto; son ejercicios de clase.
