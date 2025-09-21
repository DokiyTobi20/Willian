<?php
require_once __DIR__ . '/../utiles/seguridad.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario'])) {
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'></head><body><script>window.top.location.href = '../panel/panel.php';</script></body></html>";
    exit;
}

$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
$formType = (isset($_GET['form']) && $_GET['form'] === 'register') ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala de Rehabilitación - Acceso</title>
    <link rel="stylesheet" href="acceso.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="container <?= $formType === 'register' ? 'toggle' : '' ?>" id="container">
        <?php if ($error): ?>
            <div class="message error"><?= escaparHtml($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?= escaparHtml($success) ?></div>
        <?php endif; ?>
        
        <div class="container-form">
            <form class="sing-in" method="POST" action="operaciones_acceso.php" id="formLogin" novalidate>
                <input type="hidden" name="csrf" value="<?= generarTokenCsrf(); ?>">
                <input type="hidden" name="login" value="1">
                <h2>Iniciar Sesión</h2>
                <span>Ingresa tu usuario y contraseña para acceder</span>
                <div class="container-input">
                    <ion-icon name="person-outline"></ion-icon>
                    <input type="text" name="usuario" placeholder="Usuario">
                </div>
                <div class="container-input">
                    <ion-icon name="lock-closed-outline"></ion-icon>
                    <input type="password" name="clave" placeholder="Contraseña">
                </div>
                <a href="#">¿Olvidaste tu contraseña?</a>
                <button type="submit" class="button">Iniciar Sesión</button>
            </form>
        </div>
        
        <div class="container-form">
            <form class="sing-up" method="POST" action="operaciones_acceso.php" id="formRegistro" novalidate>
                <input type="hidden" name="csrf" value="<?= generarTokenCsrf(); ?>">
                <input type="hidden" name="register" value="1">
                <h2>Registrarse</h2>
                <span>Completa tus datos para crear tu cuenta</span>

                <div id="registroPaso1">
                    <div class="container-input">
                        <ion-icon name="person-outline"></ion-icon>
                        <input type="text" name="usuario" placeholder="Usuario">
                    </div>
                    <div class="container-input">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="email" name="correo" placeholder="Correo Electrónico">
                    </div>
                    <div class="container-input">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="clave" placeholder="Contraseña">
                    </div>
                    <div class="botonera">
                      <button type="button" class="button" id="btnPaso1" disabled>Siguiente</button>
                    </div>
                </div>

                <div id="registroPaso2" class="hidden">
                    <div class="container-input">
                        <ion-icon name="person-outline"></ion-icon>
                        <input type="text" name="nombre" placeholder="Nombre">
                    </div>
                    <div class="container-input">
                        <ion-icon name="person-outline"></ion-icon>
                        <input type="text" name="apellido" placeholder="Apellido">
                    </div>
                    <div class="container-input">
                        <ion-icon name="id-card-outline"></ion-icon>
                        <input type="text" name="cedula" placeholder="Cédula">
                    </div>
                    <div class="botonera">
                      <button type="button" class="button" id="btnAtras2">Atrás</button>
                      <button type="button" class="button" id="btnPaso2" disabled>Siguiente</button>
                    </div>
                </div>

                <div id="registroPaso3" class="hidden">
                    <div class="container-input">
                        <ion-icon name="calendar-outline"></ion-icon>
                        <input type="date" name="fecha_nacimiento" placeholder="Fecha de Nacimiento">
                    </div>
                    <div class="container-input">
                        <ion-icon name="call-outline"></ion-icon>
                        <input type="text" name="telefono" placeholder="Teléfono">
                    </div>
                    <div class="container-input">
                        <ion-icon name="home-outline"></ion-icon>
                        <input type="text" name="direccion" placeholder="Dirección">
                    </div>
                    <div class="botonera">
                      <button type="button" class="button" id="btnAtras3">Atrás</button>
                      <button type="submit" class="button" id="btnSubmitRegistro" disabled>Registrarse</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="container-welcome">
            <div class="welcome welcome-sign-in <?= $formType === 'login' ? 'active' : '' ?>">
                <h3>¡Bienvenido!</h3>
                <p>¿Ya tienes una cuenta? Inicia sesión para acceder a tu perfil personal y continuar donde lo dejaste.</p>
                <button type="button" class="button" id="btn-sign-in">Iniciar Sesión</button>
            </div>
            
            <div class="welcome welcome-sign-up <?= $formType === 'register' ? 'active' : '' ?>">
                <h3>¡Hola!</h3>
                <p>¿Eres nuevo? Crea tu cuenta y únete a nuestra comunidad. Disfruta de todas las funcionalidades que tenemos para ti.</p>
                <button type="button" class="button" id="btn-sign-up">Registrarse</button>
            </div>
        </div>
    </div>
    
    <script src="acceso.js"></script>
</body>
</html>