<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya hay una sesión activa, redirigir al panel automáticamente
if (isset($_SESSION['usuario'])) {
    header("Location: panel/panel.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="estilos.css" />
    <title>Sistema Médico</title>
  </head>
  <body>
    <div class="container">
      <nav>
        <div class="nav__logo">
          Sala de Rehabilitación Integral<br />Padre Chacín
        </div>
        <ul class="nav__links">
          <li class="link"><a href="#">Inicio</a></li>
          <li class="link"><a href="#">Sobre Nosotros</a></li>
          <li class="link"><a href="#">Blog</a></li>
          <li class="link"><a href="#">Contacto</a></li>
        </ul>
        <div class="nav__buttons">
          <button class="btn" onclick="openModal('acceso/acceso.php')">Iniciar Sesión</button>
        </div>
      </nav>
      <header class="header">
        <div class="content">
          <h1><span>Obtén Rápido</span><br />Servicios Médicos</h1>
          <p>
            soy gay.
          </p>
          <button class="btn" onclick="openModal('acceso/acceso.php?form=register')">Regístrate para obtener nuestros servicios</button>
        </div>
        <div class="image">
          <span class="image__bg"></span>
          <img src="imagenes/imagen_inicio.png" alt="Imagen de inicio" />
          <div class="image__content image__content__1">
            <span><i class="ri-user-3-line"></i></span>
            <div class="details">
              <h4>1520+</h4>
              <p>Pacientes atendidos</p>
            </div>
          </div>
          <div class="image__content image__content__2">
            <ul>
              <li>
                <span><i class="ri-check-line"></i></span>
                Doctores de la mejor calidad
              </li>
              <li>
                <span><i class="ri-check-line"></i></span>
                Doctores Expertos
              </li>
            </ul>
          </div>
        </div>
      </header>
    </div>

    <div id="overlay" class="overlay" aria-hidden="true">
      <div class="modal-content" role="dialog" aria-modal="true" aria-label="Acceso">
        <iframe id="modalFrame" class="modal-frame" src="about:blank"></iframe>
      </div>
    </div>

    <script src="acceso/acceso.js"></script>
  </body>
</html>