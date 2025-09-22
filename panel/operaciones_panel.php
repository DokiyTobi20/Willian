<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. VERIFICACIÓN DE AUTENTICACIÓN
if (!isset($_SESSION['usuario'])) {
    header('Location: ../acceso/acceso.php');
    exit;
}

// 2. INCLUSIÓN DE DEPENDENCIAS
define('UTIL_VERIFICAR_ACCESO_AS_LIB', true);
require_once __DIR__ . '/../utiles/verificar_acceso.php';

// 3. CONFIGURACIÓN DEL PANEL
const CONFIGURACION_PANEL = [
    'titulo' => 'Panel General',
    'secciones' => [
        'inicio' => [
            'titulo' => 'Inicio',
            'opciones' => [
                'inicio' => [
                    'vista' => 'inicio/inicio',
                    'icono' => 'bx-home-circle',
                    'texto' => 'Inicio',
                    'descripcion' => 'Panel principal de inicio'
                ]
            ]
        ],
        'personal' => [
            'titulo' => 'Gestión de Personal',
            'opciones' => [
                'doctores' => [
                    'vista' => 'doctores/doctores',
                    'icono' => 'bx-user-voice',
                    'texto' => 'Doctores',
                    'descripcion' => 'Gestionar lista de doctores'
                ],
                'especialidades' => [
                    'vista' => 'especialidades/especialidades',
                    'icono' => 'bx-plus-medical',
                    'texto' => 'Especialidades',
                    'descripcion' => 'Gestionar especialidades médicas'
                ]
            ]
        ],
        'citas' => [
            'titulo' => 'Gestión de Citas',
            'opciones' => [
                'gestion_citas' => [
                    'vista' => 'citas/citas',
                    'icono' => 'bx-calendar-check',
                    'texto' => 'Gestión de Citas',
                    'descripcion' => 'Gestionar citas, listas de espera y colas'
                ]
            ]
        ],
        'consultas' => [
            'titulo' => 'Gestión de Consultas',
            'opciones' => [
                'consultas' => [
                    'vista' => 'consultas/consultas',
                    'icono' => 'bx-book-content',
                    'texto' => 'Consultas',
                    'descripcion' => 'Ver todas las consultas'
                ]
            ]
        ]
    ]
];

const ROLES = [
    1 => 'Paciente',
    2 => 'Doctor',
    3 => 'Administrador'
];

// 4. FUNCIONES HELPERS

function obtenerConfiguracionPanel($rol = null): array {
    return CONFIGURACION_PANEL;
}

function generarMenuHTML($rol = null): string {
    $config = obtenerConfiguracionPanel($rol);
    $html = '';

    foreach ($config['secciones'] as $seccion) {
        $opcionesVisibles = [];
        foreach ($seccion['opciones'] as $opcion) {
            if (tieneAccesoAVista($rol ?? 1, $opcion['vista'])) {
                $opcionesVisibles[] = $opcion;
            }
        }

        if (!empty($opcionesVisibles)) {
            $html .= '<div class="menu-section">';
            $html .= '<h3 class="section-title">' . htmlspecialchars($seccion['titulo']) . '</h3>';
            foreach ($opcionesVisibles as $opcion) {
                // Generamos automáticamente la ruta del script según la vista
                $scriptPath = $opcion['vista'] . '.js';

                $html .= '<a href="#" class="enlace"'
                      . ' data-vista="' . htmlspecialchars($opcion['vista']) . '"'
                      . ' data-script="' . htmlspecialchars($scriptPath) . '"'
                      . ' title="' . htmlspecialchars($opcion['descripcion']) . '">';
                $html .= '<i class="bx ' . htmlspecialchars($opcion['icono']) . '"></i>';
                $html .= '<span>' . htmlspecialchars($opcion['texto']) . '</span>';
                $html .= '</a>';
            }
            $html .= '</div>';
        }
    }

    // Sección de sistema
    $html .= '<div class="menu-section">';
    $html .= '<h3 class="section-title">Sistema</h3>';
    $html .= '<a href="../acceso/salir.php" class="enlace"><i class="bx bx-log-out"></i><span>Salir</span></a>';
    $html .= '</div>';

    return $html;
}

// 5. PREPARACIÓN DE DATOS PARA LA VISTA
$rol_usuario   = $_SESSION['rol'] ?? 1;
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';

$config_panel  = obtenerConfiguracionPanel($rol_usuario);
$titulo_panel  = htmlspecialchars($config_panel['titulo']);
$rol_texto     = ROLES[$rol_usuario] ?? 'Usuario';
$menu_html     = generarMenuHTML($rol_usuario);