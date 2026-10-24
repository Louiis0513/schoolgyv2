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

/**
 * Calcula offset y metadatos de paginación.
 * @return array{pagina:int,por_pagina:int,total:int,total_paginas:int,offset:int,desde:int,hasta:int}
 */
function calcularPaginacion(int $total, int $porPagina = 8, string $param = 'pagina'): array
{
    $porPagina = max(1, $porPagina);
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = max(1, (int) ($_GET[$param] ?? 1));
    if ($pagina > $totalPaginas) {
        $pagina = $totalPaginas;
    }
    $offset = ($pagina - 1) * $porPagina;
    $desde = $total === 0 ? 0 : $offset + 1;
    $hasta = min($offset + $porPagina, $total);

    return [
        'pagina'        => $pagina,
        'por_pagina'    => $porPagina,
        'total'         => $total,
        'total_paginas' => $totalPaginas,
        'offset'        => $offset,
        'desde'         => $desde,
        'hasta'         => $hasta,
    ];
}

/**
 * Construye URL relativa conservando filtros GET y cambiando la página.
 */
function urlPaginacion(int $pagina, array $conservar = [], string $param = 'pagina'): string
{
    $query = $conservar;
    foreach ($_GET as $k => $v) {
        if ($k === $param || $k === 'completar' || $k === 'eliminar' || $k === 'editar') {
            continue;
        }
        if (!array_key_exists($k, $query)) {
            $query[$k] = $v;
        }
    }
    $query[$param] = $pagina;
    $base = basename($_SERVER['PHP_SELF']);
    return $base . '?' . http_build_query($query);
}

/** HTML de controles de paginación (vacío si solo hay 1 página). */
function renderPaginacion(array $pag, array $conservar = [], string $param = 'pagina'): string
{
    if ($pag['total'] === 0) {
        return '';
    }

    $info = '<div class="paginacion-info">Mostrando '
        . (int) $pag['desde'] . '–' . (int) $pag['hasta']
        . ' de ' . (int) $pag['total'] . '</div>';

    if ($pag['total_paginas'] <= 1) {
        return '<nav class="paginacion" aria-label="Paginación">' . $info . '</nav>';
    }

    $actual = (int) $pag['pagina'];
    $totalP = (int) $pag['total_paginas'];
    $links = '';

    $prevDisabled = $actual <= 1 ? ' aria-disabled="true" class="page-btn is-disabled"' : ' class="page-btn"';
    $nextDisabled = $actual >= $totalP ? ' aria-disabled="true" class="page-btn is-disabled"' : ' class="page-btn"';

    if ($actual <= 1) {
        $links .= '<span' . $prevDisabled . '>Anterior</span>';
    } else {
        $links .= '<a href="' . e(urlPaginacion($actual - 1, $conservar, $param)) . '" class="page-btn">Anterior</a>';
    }

    $ventana = 2;
    $inicio = max(1, $actual - $ventana);
    $fin = min($totalP, $actual + $ventana);

    if ($inicio > 1) {
        $links .= '<a href="' . e(urlPaginacion(1, $conservar, $param)) . '" class="page-num">1</a>';
        if ($inicio > 2) {
            $links .= '<span class="page-ellipsis">…</span>';
        }
    }

    for ($i = $inicio; $i <= $fin; $i++) {
        if ($i === $actual) {
            $links .= '<span class="page-num is-current" aria-current="page">' . $i . '</span>';
        } else {
            $links .= '<a href="' . e(urlPaginacion($i, $conservar, $param)) . '" class="page-num">' . $i . '</a>';
        }
    }

    if ($fin < $totalP) {
        if ($fin < $totalP - 1) {
            $links .= '<span class="page-ellipsis">…</span>';
        }
        $links .= '<a href="' . e(urlPaginacion($totalP, $conservar, $param)) . '" class="page-num">' . $totalP . '</a>';
    }

    if ($actual >= $totalP) {
        $links .= '<span' . $nextDisabled . '>Siguiente</span>';
    } else {
        $links .= '<a href="' . e(urlPaginacion($actual + 1, $conservar, $param)) . '" class="page-btn">Siguiente</a>';
    }

    return '<nav class="paginacion" aria-label="Paginación">'
        . $info
        . '<div class="paginacion-links">' . $links . '</div>'
        . '</nav>';
}
