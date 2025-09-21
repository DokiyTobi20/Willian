<?php
/**
 * Verificación de acceso a vistas del panel
 * - Puede usarse como librería (define funciones)
 * - O como endpoint HTTP que responde JSON
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Matriz de permisos por rol
// 1 => Paciente, 2 => Doctor, 3 => Administrador
const PERMISOS_PANEL = [
    1 => [ // Paciente
        'inicio/inicio',
        'citas/citas',
        'consultas/consultas_main',
    ],
    2 => [ // Doctor
        'inicio/inicio',
        'citas/citas',
        'consultas/consultas_main',
        'especialidades/especialidades',
        'doctores/doctores', // Ajusta según tus reglas reales
    ],
    3 => [ // Administrador
        '*' // acceso total
    ],
];

/**
 * Determina si un rol tiene acceso a una vista
 * @param int $rol
 * @param string $vista (ej: 'doctores/doctores')
 * @return bool
 */
function tieneAccesoAVista($rol, $vista) {
    $vista = trim($vista);
    if ($vista === '') return false;

    $permisos = PERMISOS_PANEL[$rol] ?? [];

    // Acceso total
    if (in_array('*', $permisos, true)) {
        return true;
    }

    // Coincidencia exacta
    if (in_array($vista, $permisos, true)) {
        return true;
    }

    // Soporte prefijo (ej: 'consultas/*') si deseas ampliarlo en el futuro
    foreach ($permisos as $patron) {
        if (str_ends_with($patron, '/*')) {
            $prefijo = substr($patron, 0, -2);
            if (str_starts_with($vista, $prefijo . '/')) {
                return true;
            }
        }
    }

    return false;
}

// Si se incluye como librería, no actuar como endpoint
if (defined('VERIFICAR_ACCESO_AS_LIB')) {
    return;
}

// --- Endpoint JSON ---
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$vista = isset($_GET['vista']) ? trim($_GET['vista']) : '';
if ($vista === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Vista no especificada']);
    exit;
}

$rol_actual = $_SESSION['rol'] ?? 1;

if (!tieneAccesoAVista($rol_actual, $vista)) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado a esta vista', 'vista' => $vista]);
    exit;
}

echo json_encode([
    'success' => true,
    'vista' => $vista,
    'rol' => $rol_actual
]);