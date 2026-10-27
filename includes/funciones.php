<?php
/**
 * Funciones compartidas de Schoolgy.
 * Incluir DESPUÉS de session_start() y (si aplica) conexion.php.
 */
require_once __DIR__ . '/cache.php';

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

/* ——— Headers HTTP de caché ——— */

function enviarHeadersCache(string $modo = 'privado'): void
{
    if (headers_sent()) {
        return;
    }
    if ($modo === 'publico') {
        header('Cache-Control: public, max-age=120, stale-while-revalidate=60');
        header('Vary: Cookie');
    } else {
        header('Cache-Control: no-store, private');
    }
}

/* ——— Claves y utilidades de caché por usuario ——— */

function cacheKeyMaterias(int $idUsuario): string
{
    return 'materias_u' . $idUsuario;
}

function cacheKeyMateriasCnt(int $idUsuario): string
{
    return 'materias_cnt_u' . $idUsuario;
}

function cacheKeyStats(int $idUsuario): string
{
    return 'stats_u' . $idUsuario;
}

function cacheKeyUrgentes(int $idUsuario): string
{
    return 'urgentes_u' . $idUsuario;
}

function cacheKeyAvisos(int $idUsuario): string
{
    return 'avisos_u' . $idUsuario;
}

function cacheKeyProgreso(int $idUsuario): string
{
    return 'progreso_u' . $idUsuario;
}

function cacheKeyCompletadas(int $idUsuario): string
{
    return 'completadas_u' . $idUsuario;
}

function cacheKeyTarea(int $idUsuario, int $idTarea): string
{
    return 'tarea_u' . $idUsuario . '_' . $idTarea;
}

function cacheKeyUsuario(int $idUsuario): string
{
    return 'usuario_u' . $idUsuario;
}

/** Clave de listado filtrado: tareas_u{uid}_m{mat}_e{est|all}_p{prio|all} */
function cacheKeyTareas(int $idUsuario, int $idMateria = 0, string $estado = '', string $prioridad = ''): string
{
    $e = in_array($estado, ['pendiente', 'completada'], true) ? $estado : 'all';
    $p = in_array($prioridad, ['alta', 'media', 'baja'], true) ? $prioridad : 'all';
    return 'tareas_u' . $idUsuario . '_m' . max(0, $idMateria) . '_e' . $e . '_p' . $p;
}

/**
 * @return array{0:int,1:int,2:string,3:string}|null uid, materia, estado, prioridad
 */
function cacheParseTareasKey(string $key): ?array
{
    if (!preg_match('/^tareas_u(\d+)_m(\d+)_e(all|pendiente|completada)_p(all|alta|media|baja)$/', $key, $m)) {
        return null;
    }
    return [(int) $m[1], (int) $m[2], $m[3], $m[4]];
}

function cacheRegistrarFiltroTareas(int $idUsuario, int $idMateria, string $estado, string $prioridad): void
{
    $keyIdx = 'tareas_filtros_idx_u' . $idUsuario;
    $entry = [
        'm' => max(0, $idMateria),
        'e' => in_array($estado, ['pendiente', 'completada'], true) ? $estado : 'all',
        'p' => in_array($prioridad, ['alta', 'media', 'baja'], true) ? $prioridad : 'all',
    ];
    $hash = $entry['m'] . '_' . $entry['e'] . '_' . $entry['p'];
    $idx = cacheGet($keyIdx);
    if (!is_array($idx)) {
        $idx = [];
    }
    if (isset($idx[$hash])) {
        return;
    }
    $idx[$hash] = $entry;
    cacheSet($keyIdx, $idx, CACHE_TTL_DEFAULT);
}

function tareaEntraEnFiltro(array $tarea, int $idMateria, string $estado, string $prioridad): bool
{
    if ($idMateria > 0 && (int) ($tarea['id_materia'] ?? 0) !== $idMateria) {
        return false;
    }
    if ($estado !== 'all' && ($tarea['estado'] ?? '') !== $estado) {
        return false;
    }
    if ($prioridad !== 'all' && ($tarea['prioridad'] ?? '') !== $prioridad) {
        return false;
    }
    return true;
}

/** @param list<array<string,mixed>> $lista */
function cacheListaQuitarTarea(array $lista, int $idTarea): array
{
    $out = [];
    foreach ($lista as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((int) ($row['id_tarea'] ?? 0) === $idTarea) {
            continue;
        }
        $out[] = $row;
    }
    return $out;
}

/**
 * Inserta/reemplaza tarea ordenada: pendientes primero, luego fecha_entrega ASC.
 * @param list<array<string,mixed>> $lista
 * @return list<array<string,mixed>>
 */
function cacheListaUpsertTarea(array $lista, array $tarea): array
{
    $id = (int) ($tarea['id_tarea'] ?? 0);
    $lista = cacheListaQuitarTarea($lista, $id);
    $lista[] = $tarea;
    usort($lista, static function (array $a, array $b): int {
        $ea = ($a['estado'] ?? '') === 'pendiente' ? 0 : 1;
        $eb = ($b['estado'] ?? '') === 'pendiente' ? 0 : 1;
        if ($ea !== $eb) {
            return $ea <=> $eb;
        }
        return strcmp((string) ($a['fecha_entrega'] ?? ''), (string) ($b['fecha_entrega'] ?? ''));
    });
    return $lista;
}

/**
 * Inserta/reemplaza en historial de completadas (completada_en DESC).
 * @param list<array<string,mixed>> $lista
 * @return list<array<string,mixed>>
 */
function cacheListaUpsertCompletada(array $lista, array $tarea): array
{
    $id = (int) ($tarea['id_tarea'] ?? 0);
    $lista = cacheListaQuitarTarea($lista, $id);
    if (($tarea['estado'] ?? '') !== 'completada') {
        return $lista;
    }
    $lista[] = $tarea;
    usort($lista, static function (array $a, array $b): int {
        return strcmp((string) ($b['completada_en'] ?? ''), (string) ($a['completada_en'] ?? ''));
    });
    return $lista;
}

/** Carga tarea + materia (propia del usuario). */
function cargarTareaConMateria(mysqli $db, int $idUsuario, int $idTarea): ?array
{
    if ($idTarea <= 0 || $idUsuario <= 0) {
        return null;
    }
    $stmt = $db->prepare(
        'SELECT t.*, m.nombre AS materia, m.color
         FROM tarea t
         INNER JOIN materia m ON m.id_materia = t.id_materia
         WHERE t.id_tarea = ? AND t.id_usuario = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $idTarea, $idUsuario);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/* ——— Lecturas cacheadas ——— */

/**
 * Materias del usuario (selects / formularios).
 * @return list<array{id_materia:int|string,nombre:string,color?:string}>
 */
function obtenerMateriasUsuario(mysqli $db, int $idUsuario, bool $conColor = true): array
{
    $key = cacheKeyMaterias($idUsuario);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        if (!$conColor) {
            return array_map(static fn (array $r): array => [
                'id_materia' => $r['id_materia'],
                'nombre' => $r['nombre'],
            ], $cached);
        }
        return $cached;
    }
    $cols = $conColor ? 'id_materia, nombre, color' : 'id_materia, nombre, color';
    $stmt = $db->prepare("SELECT $cols FROM materia WHERE id_usuario = ? ORDER BY nombre");
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    cacheSet($key, $rows, CACHE_TTL_DEFAULT);
    return $rows;
}

/**
 * Materias con conteo de tareas (página materias).
 * @return list<array<string,mixed>>
 */
function obtenerMateriasConConteo(mysqli $db, int $idUsuario): array
{
    $key = cacheKeyMateriasCnt($idUsuario);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    $stmt = $db->prepare(
        "SELECT m.*,
                (SELECT COUNT(*) FROM tarea t WHERE t.id_materia = m.id_materia) AS total_tareas
         FROM materia m
         WHERE m.id_usuario = ?
         ORDER BY m.nombre ASC"
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    cacheSet($key, $rows, CACHE_TTL_DEFAULT);
    return $rows;
}

/**
 * Stats de dashboard/progreso.
 * @return array{pendientes:int|string|null,completadas:int|string|null,vencidas:int|string|null,total?:int|string|null}
 */
function obtenerStatsUsuario(mysqli $db, int $idUsuario): array
{
    $key = cacheKeyStats($idUsuario);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    $stmt = $db->prepare(
        "SELECT
           SUM(estado = 'pendiente') AS pendientes,
           SUM(estado = 'completada') AS completadas,
           SUM(estado = 'pendiente' AND fecha_entrega < NOW()) AS vencidas,
           COUNT(*) AS total
         FROM tarea WHERE id_usuario = ?"
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    cacheSet($key, $row, CACHE_TTL_STATS);
    return $row;
}

/** @return list<array<string,mixed>> */
function obtenerUrgentes(mysqli $db, int $idUsuario, int $limite = 6): array
{
    $key = cacheKeyUrgentes($idUsuario);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return array_slice($cached, 0, $limite);
    }
    $stmt = $db->prepare(
        "SELECT t.*, m.nombre AS materia, m.color
         FROM tarea t
         INNER JOIN materia m ON m.id_materia = t.id_materia
         WHERE t.id_usuario = ? AND t.estado = 'pendiente'
         ORDER BY t.fecha_entrega ASC
         LIMIT 20"
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    cacheSet($key, $rows, CACHE_TTL_STATS);
    return array_slice($rows, 0, $limite);
}

/** @return list<array<string,mixed>> */
function obtenerAvisos(mysqli $db, int $idUsuario, int $limite = 5): array
{
    $key = cacheKeyAvisos($idUsuario);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        // Filtrar avisos aún vigentes respecto a NOW() sin ir a MySQL
        $ahora = date('Y-m-d H:i:s');
        $vivos = array_values(array_filter($cached, static function (array $a) use ($ahora): bool {
            return ($a['estado'] ?? 'pendiente') === 'pendiente'
                && !empty($a['fecha_aviso'])
                && $a['fecha_aviso'] <= $ahora;
        }));
        return array_slice($vivos, 0, $limite);
    }
    $stmt = $db->prepare(
        "SELECT t.id_tarea, t.titulo, t.fecha_aviso, t.fecha_entrega, t.estado, m.nombre AS materia
         FROM tarea t
         INNER JOIN materia m ON m.id_materia = t.id_materia
         WHERE t.id_usuario = ?
           AND t.estado = 'pendiente'
           AND t.fecha_aviso IS NOT NULL
         ORDER BY t.fecha_aviso ASC
         LIMIT 30"
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    cacheSet($key, $rows, CACHE_TTL_STATS);
    $ahora = date('Y-m-d H:i:s');
    $vivos = array_values(array_filter($rows, static fn (array $a): bool => ($a['fecha_aviso'] ?? '') <= $ahora));
    return array_slice($vivos, 0, $limite);
}

/**
 * Listado filtrado completo (paginar en PHP).
 * @return list<array<string,mixed>>
 */
function obtenerTareasFiltradas(
    mysqli $db,
    int $idUsuario,
    int $idMateria = 0,
    string $estado = '',
    string $prioridad = ''
): array {
    $key = cacheKeyTareas($idUsuario, $idMateria, $estado, $prioridad);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }

    $where = ' WHERE t.id_usuario = ?';
    $types = 'i';
    $params = [$idUsuario];
    if ($idMateria > 0) {
        $where .= ' AND t.id_materia = ?';
        $types .= 'i';
        $params[] = $idMateria;
    }
    if (in_array($estado, ['pendiente', 'completada'], true)) {
        $where .= ' AND t.estado = ?';
        $types .= 's';
        $params[] = $estado;
    }
    if (in_array($prioridad, ['alta', 'media', 'baja'], true)) {
        $where .= ' AND t.prioridad = ?';
        $types .= 's';
        $params[] = $prioridad;
    }

    $sql = "SELECT t.*, m.nombre AS materia, m.color
            FROM tarea t
            INNER JOIN materia m ON m.id_materia = t.id_materia
            $where
            ORDER BY FIELD(t.estado, 'pendiente', 'completada'), t.fecha_entrega ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    cacheSet($key, $rows, CACHE_TTL_DEFAULT);
    cacheRegistrarFiltroTareas($idUsuario, $idMateria, $estado, $prioridad);
    return $rows;
}

/** @return list<array<string,mixed>> */
function obtenerCompletadas(mysqli $db, int $idUsuario): array
{
    $key = cacheKeyCompletadas($idUsuario);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    $stmt = $db->prepare(
        "SELECT t.*, m.nombre AS materia, m.color
         FROM tarea t
         INNER JOIN materia m ON m.id_materia = t.id_materia
         WHERE t.id_usuario = ? AND t.estado = 'completada'
         ORDER BY t.completada_en DESC, t.fecha_entrega DESC"
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    cacheSet($key, $rows, CACHE_TTL_DEFAULT);
    return $rows;
}

/** @return list<array<string,mixed>> */
function obtenerProgresoPorMateria(mysqli $db, int $idUsuario): array
{
    $key = cacheKeyProgreso($idUsuario);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    $stmt = $db->prepare(
        "SELECT m.nombre, m.color,
                SUM(t.estado = 'completada') AS hechas,
                COUNT(t.id_tarea) AS total
         FROM materia m
         LEFT JOIN tarea t ON t.id_materia = m.id_materia AND t.id_usuario = m.id_usuario
         WHERE m.id_usuario = ?
         GROUP BY m.id_materia
         ORDER BY m.nombre"
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    cacheSet($key, $rows, CACHE_TTL_DEFAULT);
    return $rows;
}

function obtenerTareaUsuario(mysqli $db, int $idUsuario, int $idTarea): ?array
{
    if ($idTarea <= 0) {
        return null;
    }
    $key = cacheKeyTarea($idUsuario, $idTarea);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    if ($cached === false) {
        return null;
    }
    $stmt = $db->prepare(
        'SELECT * FROM tarea WHERE id_tarea = ? AND id_usuario = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $idTarea, $idUsuario);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        cacheSet($key, $row, CACHE_TTL_DEFAULT);
        return $row;
    }
    cacheSet($key, false, CACHE_TTL_NEGATIVO);
    return null;
}

function obtenerPuntosUsuario(mysqli $db, int $idUsuario): int
{
    $key = cacheKeyUsuario($idUsuario);
    $cached = cacheGet($key);
    if (is_array($cached) && array_key_exists('puntos_totales', $cached)) {
        return (int) $cached['puntos_totales'];
    }
    $stmt = $db->prepare('SELECT puntos_totales FROM usuario WHERE id_usuario = ?');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $pts = (int) ($stmt->get_result()->fetch_assoc()['puntos_totales'] ?? 0);
    cacheSet($key, ['puntos_totales' => $pts], CACHE_TTL_DEFAULT);
    return $pts;
}

/* ——— Parche de caché (write-through): sin wipe total ——— */

/**
 * Recalcula stats/urgentes/avisos/progreso/conteos con queries pequeñas
 * solo si esas claves ya existen (lazy).
 */
function cacheParcheAgregadosUsuario(mysqli $db, int $idUsuario): void
{
    $idUsuario = (int) $idUsuario;
    if ($idUsuario <= 0) {
        return;
    }

    if (cachePeek(cacheKeyStats($idUsuario)) !== null) {
        $stmt = $db->prepare(
            "SELECT
               SUM(estado = 'pendiente') AS pendientes,
               SUM(estado = 'completada') AS completadas,
               SUM(estado = 'pendiente' AND fecha_entrega < NOW()) AS vencidas,
               COUNT(*) AS total
             FROM tarea WHERE id_usuario = ?"
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        cachePatch(cacheKeyStats($idUsuario), $stmt->get_result()->fetch_assoc() ?: [], CACHE_TTL_STATS);
    }

    if (cachePeek(cacheKeyUrgentes($idUsuario)) !== null) {
        $stmt = $db->prepare(
            "SELECT t.*, m.nombre AS materia, m.color
             FROM tarea t
             INNER JOIN materia m ON m.id_materia = t.id_materia
             WHERE t.id_usuario = ? AND t.estado = 'pendiente'
             ORDER BY t.fecha_entrega ASC
             LIMIT 20"
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        cachePatch(cacheKeyUrgentes($idUsuario), $stmt->get_result()->fetch_all(MYSQLI_ASSOC), CACHE_TTL_STATS);
    }

    if (cachePeek(cacheKeyAvisos($idUsuario)) !== null) {
        $stmt = $db->prepare(
            "SELECT t.id_tarea, t.titulo, t.fecha_aviso, t.fecha_entrega, t.estado, m.nombre AS materia
             FROM tarea t
             INNER JOIN materia m ON m.id_materia = t.id_materia
             WHERE t.id_usuario = ?
               AND t.estado = 'pendiente'
               AND t.fecha_aviso IS NOT NULL
             ORDER BY t.fecha_aviso ASC
             LIMIT 30"
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        cachePatch(cacheKeyAvisos($idUsuario), $stmt->get_result()->fetch_all(MYSQLI_ASSOC), CACHE_TTL_STATS);
    }

    if (cachePeek(cacheKeyProgreso($idUsuario)) !== null) {
        $stmt = $db->prepare(
            "SELECT m.nombre, m.color,
                    SUM(t.estado = 'completada') AS hechas,
                    COUNT(t.id_tarea) AS total
             FROM materia m
             LEFT JOIN tarea t ON t.id_materia = m.id_materia AND t.id_usuario = m.id_usuario
             WHERE m.id_usuario = ?
             GROUP BY m.id_materia
             ORDER BY m.nombre"
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        cachePatch(cacheKeyProgreso($idUsuario), $stmt->get_result()->fetch_all(MYSQLI_ASSOC), CACHE_TTL_DEFAULT);
    }

    if (cachePeek(cacheKeyMateriasCnt($idUsuario)) !== null) {
        $stmt = $db->prepare(
            "SELECT m.*,
                    (SELECT COUNT(*) FROM tarea t WHERE t.id_materia = m.id_materia) AS total_tareas
             FROM materia m
             WHERE m.id_usuario = ?
             ORDER BY m.nombre ASC"
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        cachePatch(cacheKeyMateriasCnt($idUsuario), $stmt->get_result()->fetch_all(MYSQLI_ASSOC), CACHE_TTL_DEFAULT);
    }
}

/**
 * Parchea ficha + listados existentes tras create/update/completar.
 * Una sola lectura de la tarea; no regenera listas filtradas desde MySQL.
 */
function cacheParcheTarea(mysqli $db, int $idUsuario, int $idTarea, ?array $antes = null): void
{
    $idUsuario = (int) $idUsuario;
    $idTarea = (int) $idTarea;
    if ($idUsuario <= 0 || $idTarea <= 0) {
        return;
    }

    $ahora = cargarTareaConMateria($db, $idUsuario, $idTarea);

    if ($ahora) {
        // Ficha sin join (como usa el formulario)
        $ficha = $ahora;
        unset($ficha['materia'], $ficha['color']);
        cachePatch(cacheKeyTarea($idUsuario, $idTarea), $ficha, CACHE_TTL_DEFAULT);
    } else {
        cachePatch(cacheKeyTarea($idUsuario, $idTarea), false, CACHE_TTL_NEGATIVO);
    }

    // Listados filtrados ya materializados
    foreach (cacheKeysByPrefix('tareas_u' . $idUsuario . '_') as $key) {
        $parsed = cacheParseTareasKey($key);
        if ($parsed === null || $parsed[0] !== $idUsuario) {
            continue;
        }
        [, $idMat, $est, $prio] = $parsed;
        cachePatchIfPresent($key, static function ($data) use ($ahora, $idTarea, $idMat, $est, $prio) {
            if (!is_array($data)) {
                return $data;
            }
            $data = cacheListaQuitarTarea($data, $idTarea);
            if ($ahora && tareaEntraEnFiltro($ahora, $idMat, $est, $prio)) {
                $data = cacheListaUpsertTarea($data, $ahora);
            }
            return $data;
        });
    }

    // Historial de completadas
    cachePatchIfPresent(cacheKeyCompletadas($idUsuario), static function ($data) use ($ahora, $idTarea) {
        if (!is_array($data)) {
            return $data;
        }
        if ($ahora) {
            return cacheListaUpsertCompletada($data, $ahora);
        }
        return cacheListaQuitarTarea($data, $idTarea);
    });

    // Puntos en caché de usuario (si cambió al completar)
    if ($ahora && ($antes['estado'] ?? '') === 'pendiente' && ($ahora['estado'] ?? '') === 'completada') {
        $pts = (int) ($ahora['puntos'] ?? 0);
        cachePatchIfPresent(cacheKeyUsuario($idUsuario), static function ($data) use ($pts) {
            if (!is_array($data)) {
                return $data;
            }
            $data['puntos_totales'] = (int) ($data['puntos_totales'] ?? 0) + $pts;
            return $data;
        });
    }

    cacheParcheAgregadosUsuario($db, $idUsuario);
}

/** Elimina tarea del caché (tras DELETE). */
function cacheParcheTareaEliminada(mysqli $db, int $idUsuario, int $idTarea, ?array $antes = null): void
{
    $idUsuario = (int) $idUsuario;
    $idTarea = (int) $idTarea;
    cacheForget(cacheKeyTarea($idUsuario, $idTarea));

    foreach (cacheKeysByPrefix('tareas_u' . $idUsuario . '_') as $key) {
        cachePatchIfPresent($key, static function ($data) use ($idTarea) {
            return is_array($data) ? cacheListaQuitarTarea($data, $idTarea) : $data;
        });
    }
    cachePatchIfPresent(cacheKeyCompletadas($idUsuario), static function ($data) use ($idTarea) {
        return is_array($data) ? cacheListaQuitarTarea($data, $idTarea) : $data;
    });

    cacheParcheAgregadosUsuario($db, $idUsuario);
}

/**
 * Tras create/edit/delete de materia: refresca listados de materias
 * y parchea nombre/color en tareas ya cacheadas (sin wipe).
 */
function cacheParcheMaterias(
    mysqli $db,
    int $idUsuario,
    ?int $idMateria = null,
    ?string $nombreNuevo = null,
    ?string $colorNuevo = null,
    bool $eliminada = false
): void {
    $idUsuario = (int) $idUsuario;
    if ($idUsuario <= 0) {
        return;
    }

    $stmt = $db->prepare('SELECT id_materia, nombre, color FROM materia WHERE id_usuario = ? ORDER BY nombre');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    cachePatch(cacheKeyMaterias($idUsuario), $rows, CACHE_TTL_DEFAULT);

    $stmtCnt = $db->prepare(
        "SELECT m.*,
                (SELECT COUNT(*) FROM tarea t WHERE t.id_materia = m.id_materia) AS total_tareas
         FROM materia m
         WHERE m.id_usuario = ?
         ORDER BY m.nombre ASC"
    );
    $stmtCnt->bind_param('i', $idUsuario);
    $stmtCnt->execute();
    cachePatch(cacheKeyMateriasCnt($idUsuario), $stmtCnt->get_result()->fetch_all(MYSQLI_ASSOC), CACHE_TTL_DEFAULT);

    if ($idMateria && $nombreNuevo !== null && !$eliminada) {
        $id = (int) $idMateria;
        $nom = (string) $nombreNuevo;
        $col = $colorNuevo;
        $patchNombre = static function ($data) use ($id, $nom, $col) {
            if (!is_array($data)) {
                return $data;
            }
            foreach ($data as $i => $row) {
                if (!is_array($row) || (int) ($row['id_materia'] ?? 0) !== $id) {
                    continue;
                }
                $data[$i]['materia'] = $nom;
                if ($col !== null) {
                    $data[$i]['color'] = $col;
                }
            }
            return $data;
        };

        foreach (cacheKeysByPrefix('tareas_u' . $idUsuario . '_') as $key) {
            cachePatchIfPresent($key, $patchNombre);
        }
        cachePatchIfPresent(cacheKeyCompletadas($idUsuario), $patchNombre);
        cachePatchIfPresent(cacheKeyUrgentes($idUsuario), $patchNombre);
        cachePatchIfPresent(cacheKeyAvisos($idUsuario), static function ($data) use ($id, $nom) {
            if (!is_array($data)) {
                return $data;
            }
            // avisos no traen id_materia en todas las filas cacheadas antiguas; se refresca vía agregados
            foreach ($data as $i => $row) {
                if (is_array($row) && isset($row['materia']) && (int) ($row['id_materia'] ?? 0) === $id) {
                    $data[$i]['materia'] = $nom;
                }
            }
            return $data;
        });
    }

    if ($eliminada && $idMateria) {
        // Quitar filtros por esa materia
        cacheForgetPrefix('tareas_u' . $idUsuario . '_m' . (int) $idMateria . '_');
    }

    cacheParcheAgregadosUsuario($db, $idUsuario);
}

/** Actualiza puntos cacheados del usuario (perfil / completar). */
function cacheParchePuntosUsuario(int $idUsuario, int $puntos): void
{
    cachePatch(cacheKeyUsuario($idUsuario), ['puntos_totales' => $puntos], CACHE_TTL_DEFAULT);
}

/**
 * Invalidación total del usuario (resync de emergencia). Preferir cacheParche*.
 */
function cacheInvalidarUsuario(int $idUsuario): void
{
    $uid = (int) $idUsuario;
    cacheForget(cacheKeyMaterias($uid));
    cacheForget(cacheKeyMateriasCnt($uid));
    cacheForget(cacheKeyStats($uid));
    cacheForget(cacheKeyUrgentes($uid));
    cacheForget(cacheKeyAvisos($uid));
    cacheForget(cacheKeyProgreso($uid));
    cacheForget(cacheKeyCompletadas($uid));
    cacheForget(cacheKeyUsuario($uid));
    cacheForget('tareas_filtros_idx_u' . $uid);
    cacheForgetPrefix('tareas_u' . $uid . '_');
    cacheForgetPrefix('tarea_u' . $uid . '_');
}
