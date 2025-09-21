document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('container');
    const btnSignUp = document.getElementById('btn-sign-up');
    const btnSignIn = document.getElementById('btn-sign-in');
    const btnPaso1 = document.getElementById('btnPaso1');
    const btnPaso2 = document.getElementById('btnPaso2');
    const registroPaso1 = document.getElementById('registroPaso1');
    const registroPaso2 = document.getElementById('registroPaso2');
    const registroPaso3 = document.getElementById('registroPaso3');
    const btnAtras2 = document.getElementById('btnAtras2');
    const btnAtras3 = document.getElementById('btnAtras3');

    // --- Lógica de login y registro (AJAX con redirección al panel) ---
    function manejarSubmit(form) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            const datos = new FormData(form);

            try {
                const res = await fetch(form.action, {
                    method: "POST",
                    body: datos
                });

                // Si el backend nos da una URL para redirigir (errores), vamos allí.
                if (res.redirected) {
                    window.location.href = res.url;
                    return;
                }

                // Si la respuesta fue exitosa, intentamos leerla como JSON.
                if (res.ok) {
                    const data = await res.json();
                    if (data.redirect) {
                        window.top.location.href = data.redirect;
                        return;
                    }
                }

                // Si algo falló o no hubo redirección, mostramos el error.
                const texto = await res.text();
                let resultado = document.getElementById("ajax-resultado");
                if (!resultado) {
                    resultado = document.createElement('div');
                    resultado.id = 'ajax-resultado';
                    container.prepend(resultado); // Añadir al contenedor principal
                }
                resultado.className = 'message error active'; // Usar 'active' para mostrarlo
                resultado.textContent = texto || "Ocurrió un error inesperado.";

                // Ocultar el mensaje después de unos segundos
                setTimeout(() => {
                    resultado.classList.remove('active');
                }, 5000);

            } catch (err) {
                console.error("Error en login/registro:", err);
                let resultado = document.getElementById("ajax-resultado");
                if (!resultado) {
                    resultado = document.createElement('div');
                    resultado.id = 'ajax-resultado';
                    container.prepend(resultado); // Añadir al contenedor principal
                }
                resultado.className = 'message error active'; // Usar 'active' para mostrarlo
                resultado.textContent = "Ocurrió un error de conexión. Intenta de nuevo.";

                // Ocultar el mensaje después de unos segundos
                setTimeout(() => {
                    resultado.classList.remove('active');
                }, 5000);
            }
        });
    }

    // Detectar y conectar los formularios
    const formLogin = document.getElementById("formLogin");
    const formRegistro = document.getElementById("formRegistro");
    if (formLogin) manejarSubmit(formLogin);
    if (formRegistro) manejarSubmit(formRegistro);

    // --- Lo demás: UI de login/registro y validaciones ---
    if (btnSignUp) {
        btnSignUp.addEventListener('click', () => {
            container.classList.add('toggle');
            limpiarErroresGlobales();
            const url = new URL(window.location);
            url.searchParams.set('form', 'register');
            window.history.replaceState({}, '', url);
        });
    }

    if (btnSignIn) {
        btnSignIn.addEventListener('click', () => {
            container.classList.remove('toggle');
            limpiarErroresGlobales();
            const url = new URL(window.location);
            url.searchParams.set('form', 'login');
            window.history.replaceState({}, '', url);
        });
    }

    // Helpers de validación visual
    function marcarError(input, mensaje) {
        input.parentElement.classList.add('input-error');
        let help = input.parentElement.nextElementSibling;
        if (!help || !help.classList || !help.classList.contains('mensaje-error')) {
            help = document.createElement('div');
            help.className = 'mensaje-error';
            input.parentElement.insertAdjacentElement('afterend', help);
        }
        help.textContent = mensaje;
    }

    function limpiarErroresGlobales() {
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
        document.querySelectorAll('.mensaje-error').forEach(el => el.remove());
    }

    function limpiarError(input) {
        input.parentElement.classList.remove('input-error');
        const help = input.parentElement.nextElementSibling;
        if (help && help.classList && help.classList.contains('mensaje-error')) {
            help.remove();
        }
    }

    function antiRebote(fn, delay) {
        let timerId;
        return function debounced(...args) {
            clearTimeout(timerId);
            timerId = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // --- Lógica de validación para el formulario multi-paso ---
    const formRegistroInputs = formRegistro ? Array.from(formRegistro.querySelectorAll('input:not([type="hidden"])')) : [];
    const btnSubmitRegistro = document.getElementById('btnSubmitRegistro');

    function validarPaso(paso) {
        const inputsPaso = Array.from(document.querySelectorAll(`#registroPaso${paso} input`));
        return inputsPaso.every(input => input.value.trim() !== '');
    }

    function actualizarEstadoBotones() {
        if (!formRegistro) return;

        // Botón del paso 1
        if (btnPaso1) {
            btnPaso1.disabled = !validarPaso(1);
        }

        // Botón del paso 2
        if (btnPaso2) {
            btnPaso2.disabled = !validarPaso(2);
        }

        // Botón final de registro
        if (btnSubmitRegistro) {
            const todosLosPasosValidos = validarPaso(1) && validarPaso(2) && validarPaso(3);
            btnSubmitRegistro.disabled = !todosLosPasosValidos;
        }
    }

    if (formRegistroInputs.length > 0) {
        formRegistroInputs.forEach(input => {
            input.addEventListener('input', antiRebote(actualizarEstadoBotones, 300));
        });
        // Llamada inicial por si el navegador autocompleta los campos
        actualizarEstadoBotones();
    }

    // --- Navegación entre pasos ---
    if (btnPaso1) {
        btnPaso1.addEventListener('click', () => {
            registroPaso1.classList.add('hidden');
            registroPaso2.classList.remove('hidden');
        });
    }

    if (btnPaso2) {
        btnPaso2.addEventListener('click', () => {
            registroPaso2.classList.add('hidden');
            registroPaso3.classList.remove('hidden');
        });
    }

    if (btnAtras2) {
        btnAtras2.addEventListener('click', () => {
            registroPaso2.classList.add('hidden');
            registroPaso1.classList.remove('hidden');
        });
    }

    if (btnAtras3) {
        btnAtras3.addEventListener('click', () => {
            registroPaso3.classList.add('hidden');
            registroPaso2.classList.remove('hidden');
        });
    }

    // Ocultar mensajes automáticamente después de 4 segundos
    const messages = document.querySelectorAll('.message');
    if (messages.length) {
        setTimeout(() => {
            messages.forEach(message => {
                message.style.display = 'none';
            });
        }, 4000);
    }

    // Si hay un parámetro 'form' en la URL, configurar el modo
    const urlParams = new URLSearchParams(window.location.search);
    if (container && urlParams.get('form') === 'register') {
        container.classList.add('toggle');
    }

    limpiarErroresGlobales();

    // ---- Modal overlay logic ----
    (function () {
      const overlay = document.getElementById('overlay');
      const modalFrame = document.getElementById('modalFrame');
      const container = document.querySelector('.container');

      if (!overlay || !modalFrame) return;

      function abrir(url) {
        if (url) {
          modalFrame.src = url;
        }
        overlay.classList.add('active');
        document.body.classList.add('modal-open');
        if (container) container.classList.add('blurred');
      }
      window.openModal = abrir;

      function closeModal() {
        overlay.classList.remove('active');
        document.body.classList.remove('modal-open');
        if (container) container.classList.remove('blurred');
        setTimeout(() => {
          modalFrame.src = 'about:blank';
        }, 150);
      }

      overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
          closeModal();
        }
      });

      const modalContent = overlay.querySelector('.modal-content');
      if (modalContent) {
        modalContent.addEventListener('click', function (event) {
          event.stopPropagation();
        });
      }

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && overlay.classList.contains('active')) {
          closeModal();
        }
      });
    })();
});