<?php
require_once __DIR__ . '/../BDD/conexion.php';
require_once __DIR__ . '/../BDD/operaciones_bd.php';
require_once __DIR__ . '/../utiles/seguridad.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// función para redirigir con parámetros (se usa en errores o feedbacks)
function redirect_with(string $path, array $params = []): void {
    $qs = http_build_query($params);
    header('Location: ' . $path . ($qs ? ('?' . $qs) : ''));
    exit;
}

// solo aceptar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with('acceso.php');
}

// validar token CSRF
$csrf = $_POST['csrf'] ?? null;
if (!validarTokenCsrf($csrf)) {
    redirect_with('acceso.php', [
        'error' => 'Sesión inválida. Refresca la página e inténtalo de nuevo.',
        'form'  => isset($_POST['register']) ? 'register' : 'login',
    ]);
}

// ---------------- LOGIN ----------------
if (isset($_POST['login'])) {
    $usuario = limpiarCadena($_POST['usuario'] ?? '');
    $clave   = $_POST['clave'] ?? '';

    if ($usuario === '' || $clave === '') {
        redirect_with('acceso.php', [
            'error' => 'Debes completar todos los campos',
            'form'  => 'login',
        ]);
    }

    try {
        $pdo = Conexion::conectar();
        $sql = "SELECT * FROM usuarios WHERE BINARY usuario = :usuario LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // si existe y la clave es correcta
        if ($user && password_verify($clave, $user['clave'])) {
            session_regenerate_id(true);
            $_SESSION['usuario']  = $user['usuario'];
            $_SESSION['id']       = $user['id'];
            $_SESSION['rol']      = $user['id_rol'];
            $_SESSION['nombre']   = $user['nombre'];
            $_SESSION['apellido'] = $user['apellido'];

            // Devolver JSON para que JS redirija
            header('Content-Type: application/json');
            echo json_encode(['redirect' => '../panel/panel.php']);
            exit;
        }

        redirect_with('acceso.php', [
            'error' => 'Usuario o contraseña incorrectos',
            'form'  => 'login',
        ]);
    } catch (PDOException $e) {
        redirect_with('acceso.php', [
            'error' => 'Error en la conexión con la base de datos',
            'form'  => 'login',
        ]);
    }
}

// ---------------- REGISTRO ----------------
if (isset($_POST['register'])) {
    // recoger datos del formulario
    $usuario   = limpiarCadena($_POST['usuario'] ?? '');
    $clave     = $_POST['clave'] ?? '';
    $correo    = filter_var(limpiarCadena($_POST['correo'] ?? ''), FILTER_VALIDATE_EMAIL);
    $nombre    = limpiarCadena($_POST['nombre'] ?? '');
    $apellido  = limpiarCadena($_POST['apellido'] ?? '');
    $cedula    = limpiarCadena($_POST['cedula'] ?? '');
    $fecha_nacimiento = limpiarCadena($_POST['fecha_nacimiento'] ?? '');
    $telefono  = limpiarCadena($_POST['telefono'] ?? '');
    $direccion = limpiarCadena($_POST['direccion'] ?? '');

    // validar datos básicos
    $errores = [];

    if ($usuario === '' || strlen($usuario) > 25) {
        $errores[] = 'Usuario inválido (máx 25 caracteres)';
    }
    if ($clave === '' || strlen($clave) < 8) {
        $errores[] = 'Contraseña inválida (mín 8 caracteres)';
    }
    if (!$correo) {
        $errores[] = 'Correo electrónico inválido';
    }
    if ($nombre === '') { $errores[] = 'Nombre requerido'; }
    if ($apellido === '') { $errores[] = 'Apellido requerido'; }
    if ($cedula === '' || !preg_match('/^\d{6,12}$/', $cedula)) {
        $errores[] = 'Cédula inválida (6 a 12 dígitos)';
    }
    if ($fecha_nacimiento === '') { $errores[] = 'Fecha de nacimiento requerida'; }
    if ($telefono === '' || !preg_match('/^\d{11}$/', $telefono)) {
        $errores[] = 'Teléfono inválido (11 dígitos)';
    }
    if ($direccion === '' || strlen($direccion) < 5) {
        $errores[] = 'Dirección inválida';
    }

    // si hay errores, regreso al form
    if (!empty($errores)) {
        redirect_with('acceso.php', [
            'error' => implode('\n', $errores),
            'form'  => 'register',
        ]);
    }

    try {
        $pdo = Conexion::conectar();
        $crud = new OperacionesBD($pdo);

        // comprobar si usuario o correo ya existen
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario OR correo = :correo';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['usuario' => $usuario, 'correo' => $correo]);
        if ($stmt->fetchColumn() > 0) {
            redirect_with('acceso.php', [
                'error' => 'El usuario o correo ya están registrados',
                'form'  => 'register',
            ]);
        }

        // encriptar la clave
        $clave_hash = password_hash($clave, PASSWORD_BCRYPT);

        // datos completos para registrar
        $datos = [
            'usuario' => $usuario,
            'clave' => $clave_hash,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'correo' => $correo,
            'cedula' => $cedula,
            'fecha_nacimiento' => $fecha_nacimiento,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'id_rol' => 1, // rol paciente
            'fecha_creacion' => date('Y-m-d H:i:s'),
        ];

        // esto registra
        if ($crud->insertar('usuarios', $datos)) {
            // Obtenemos el usuario recién creado para iniciar sesión
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
            $stmt->execute(['usuario' => $usuario]);
            $newUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($newUser) {
                session_regenerate_id(true);
                $_SESSION['usuario']  = $newUser['usuario'];
                $_SESSION['id']       = $newUser['id'];
                $_SESSION['rol']      = $newUser['id_rol'];
                $_SESSION['nombre']   = $newUser['nombre'];
                $_SESSION['apellido'] = $newUser['apellido'];

                // Devolver JSON para que JS redirija
                header('Content-Type: application/json');
                echo json_encode(['redirect' => '../panel/panel.php']);
                exit;
            }
        }

        redirect_with('acceso.php', [
            'error' => 'Error al crear el usuario',
            'form'  => 'register',
        ]);
    } catch (PDOException $e) {
        redirect_with('acceso.php', [
            'error' => 'Error en la base de datos',
            'form'  => 'register',
        ]);
    }
}

// si llega aquí redirigir
redirect_with('acceso.php');