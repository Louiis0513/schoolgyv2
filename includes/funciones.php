<?php
/**
 * Funciones compartidas de Schoolgy.
 * Incluir DESPUÉS de session_start() y (si aplica) conexion.php.
 */

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function usuarioLogueado(): bool
{
    return isset($_SESSION['id_usuario']);
}

function requiereLogin(): void
{
    if (!usuarioLogueado()) {
        guardarMensaje('Inicia sesión para continuar.', 'error');
        header('Location: login.php');
        exit;
    }
}

function soloVisitantes(): void
{
    if (usuarioLogueado()) {
        header('Location: dashboard.php');
        exit;
    }
}

function claseActiva(string $archivo): string
{
    return basename($_SERVER['PHP_SELF']) === $archivo ? 'active' : '';
}

function guardarMensaje(string $texto, string $tipo = 'ok'): void
{
    $_SESSION['flash'] = ['texto' => $texto, 'tipo' => $tipo];
}

function obtenerMensaje(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $msg = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $msg;
}

function formatearFecha(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha)
        ?: DateTime::createFromFormat('Y-m-d', $fecha)
        ?: DateTime::createFromFormat('Y-m-d\TH:i', $fecha);
    return $dt ? $dt->format('d/m/Y H:i') : $fecha;
}

/** Puntos según prioridad (gamificación). */
function puntosPorPrioridad(string $prioridad): int
{
    return match ($prioridad) {
        'alta'  => 30,
        'media' => 20,
        'baja'  => 10,
        default => 10,
    };
}

/** Nivel: cada 100 puntos sube un nivel (mínimo 1). */
function calcularNivel(int $puntos): int
{
    return max(1, (int) floor($puntos / 100) + 1);
}

function progresoNivel(int $puntos): int
{
    return $puntos % 100;
}

/**
 * Prioridad efectiva: si está pendiente y vencida → alta
 * (regla del análisis: tarea pasada se marca urgente).
 */
function prioridadEfectiva(array $tarea): string
{
    if (($tarea['estado'] ?? '') === 'pendiente') {
        $entrega = strtotime($tarea['fecha_entrega'] ?? '');
        if ($entrega !== false && $entrega < time()) {
            return 'alta';
        }
    }
    return $tarea['prioridad'] ?? 'media';
}

function estaVencida(array $tarea): bool
{
    if (($tarea['estado'] ?? '') !== 'pendiente') {
        return false;
    }
    $entrega = strtotime($tarea['fecha_entrega'] ?? '');
    return $entrega !== false && $entrega < time();
}

function badgePrioridad(string $prioridad): string
{
    $mapa = [
        'alta'  => ['alta', 'badge-alta'],
        'media' => ['media', 'badge-media'],
        'baja'  => ['baja', 'badge-baja'],
    ];
    [$texto, $clase] = $mapa[$prioridad] ?? ['media', 'badge-media'];
    return '<span class="badge ' . $clase . '">' . e($texto) . '</span>';
}

function badgeEstado(array $tarea): string
{
    if (($tarea['estado'] ?? '') === 'completada') {
        return '<span class="badge badge-ok">completada</span>';
    }
    if (estaVencida($tarea)) {
        return '<span class="badge badge-vencida">vencida</span>';
    }
    return '<span class="badge badge-pendiente">pendiente</span>';
}

/** ¿La materia pertenece al usuario logueado? */
function materiaDelUsuario(mysqli $mysqli, int $idMateria, int $idUsuario): bool
{
    $stmt = $mysqli->prepare(
        'SELECT id_materia FROM materia WHERE id_materia = ? AND id_usuario = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $idMateria, $idUsuario);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}
