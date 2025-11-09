<?php

// Sesión solo si actuamos como endpoint
if (!defined('UTIL_VERIFICAR_ACCESO_AS_LIB')) {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

// Dependencias de BD
require_once __DIR__ . '/../BDD/conexion.php';

// --- Permisos por rol ---
// 1 => Paciente, 2 => Doctor, 3 => Admin
const PERMISOS_PANEL = [
    1 => [ // Paciente
        'inicio/inicio',
        'citas/citas',
        'consultas/consultas_main',
    ],
    2 => [ // Doctor
        'inicio/inicio',
        'consultas/consultas',
    ],
    3 => [ // Administrador
        '*',
    ],
];

/**
 * Determina si un rol tiene acceso a una vista
 */
function tieneAccesoAVista($rol, $vista) {
    $vista = trim((string)$vista);
    if ($vista === '') return false;
    $permisos = PERMISOS_PANEL[$rol] ?? [];

    if (in_array('*', $permisos, true)) return true;
    if (in_array($vista, $permisos, true)) return true;

    foreach ($permisos as $patron) {
        if (str_ends_with($patron, '/*')) {
            $prefijo = substr($patron, 0, -2);
            if (str_starts_with($vista, $prefijo . '/')) return true;
        }
    }
    return false;
}

/**
 * Verifica unicidad de un campo permitido en la tabla usuarios
 * @return array ['ok'=>bool, 'exists'=>bool]
 */
function verificarUnicidadUsuario(string $campo, string $valor): array {
    $campo = trim($campo);
    $valor = trim($valor);

    $permitidos = ['usuario', 'correo'];
    if ($valor === '' || !in_array($campo, $permitidos, true)) {
        return ['ok' => false, 'exists' => false];
    }

    try {
        $pdo = Conexion::conectar();
        $sql = $campo === 'usuario'
            ? 'SELECT COUNT(*) FROM usuarios WHERE usuario = :v'
            : 'SELECT COUNT(*) FROM usuarios WHERE correo = :v';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['v' => $valor]);
        $existe = (int)$stmt->fetchColumn() > 0;
        return ['ok' => true, 'exists' => $existe];
    } catch (Throwable $e) {
        return ['ok' => false, 'exists' => false];
    }
}

// Si se incluye como librería, no actuar como endpoint
if (defined('UTIL_VERIFICAR_ACCESO_AS_LIB')) {
    return;
}

// --- Endpoint JSON ---
header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? strtolower(trim((string)$_GET['action'])) : '';

// Compatibilidad retro: si viene ?vista y no hay action, asumir verificación de acceso
if ($action === '' && isset($_GET['vista'])) {
    $action = 'access';
}

switch ($action) {
    case 'access': {
        // Verificar autenticación
        if (!isset($_SESSION['usuario'])) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        $vista = isset($_GET['vista']) ? trim((string)$_GET['vista']) : '';
        if ($vista === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Vista no especificada']);
            exit;
        }
        $rol = (int)($_SESSION['rol'] ?? 1);
        if (!tieneAccesoAVista($rol, $vista)) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado a esta vista', 'vista' => $vista]);
            exit;
        }
        echo json_encode(['success' => true, 'vista' => $vista, 'rol' => $rol]);
        exit;
    }

    case 'unique': {
        $campo = isset($_GET['field']) ? (string)$_GET['field'] : '';
        $valor = isset($_GET['value']) ? (string)$_GET['value'] : '';
        $resultado = verificarUnicidadUsuario($campo, $valor);
        echo json_encode($resultado);
        exit;
    }

    default: {
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida', 'allowed' => ['access', 'unique']]);
        exit;
    }
}
