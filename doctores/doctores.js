// console.log('doctores.js cargado');

let modalDoctor = null;
let modalHorarios = null;
let handleWindowClick = null;

function boot() {
    // console.log('boot() ejecutado: inicializando modales y eventos');
    inicializarModales();
    inicializarEventos();
}

(function registerOrBootstrap() {
    if (window.AppCore && typeof window.AppCore.registerModule === 'function') {
        console.log('Registrando módulo doctores en AppCore');
        // console.log('Registrando módulo doctores en AppCore');
        window.AppCore.registerModule('doctores', {
            init() {
                console.log('AppCore módulo doctores: init() llamado');
                // console.log('AppCore módulo doctores: init() llamado');
                boot();
            },
            destroy() {
                if (handleWindowClick) {
                    window.removeEventListener('click', handleWindowClick);
                    handleWindowClick = null;
                }
                modalDoctor = null;
                modalHorarios = null;
            }
        });
    } else {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    }
})();

function inicializarModales() {
    modalDoctor = document.getElementById('modalDoctor');
    modalHorarios = document.getElementById('modalHorarios');

    document.querySelectorAll('.close').forEach(close => {
        close.addEventListener('click', function() {
            cerrarModal();
        });
    });

    handleWindowClick = function(event) {
        if (event.target === modalDoctor || event.target === modalHorarios) {
            cerrarModal();
        }
    };
    window.addEventListener('click', handleWindowClick);
}

function inicializarEventos() {
    const btnNuevo = document.getElementById('btnNuevoDoctor');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            // console.log('Botón Registrar Doctor pulsado');
            abrirModalDoctor('Registrar Nuevo Doctor');
        });
    }

    const btnCancelar = document.getElementById('btnCancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', cerrarModal);
    }

    const form = document.getElementById('formDoctor');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            // Aquí iría la lógica de envío (procesarDoctor)
            // await procesarDoctor(new FormData(this), this.querySelector('[name="id"]')?.value ? 'editar' : 'crear');
        });
    }
}

function abrirModalDoctor(titulo = 'Registrar Nuevo Doctor') {
    if (!modalDoctor) {
        modalDoctor = document.getElementById('modalDoctor');
    }
    if (!modalDoctor) return;
    const tituloEl = document.getElementById('modalTitulo');
    if (tituloEl) tituloEl.textContent = titulo;
    // Cargar especialidades dinámicamente
    cargarEspecialidades();
    modalDoctor.style.display = 'block';
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        const input = modalDoctor.querySelector('input');
        if (input) input.focus();
    }, 100);
}

async function cargarEspecialidades() {
    const select = document.getElementById('id_especialidad');
    if (!select) return;
    select.innerHTML = '<option value="">Cargando especialidades...</option>';
    try {
        const resp = await fetch('../especialidades/operaciones_especialidades.php?accion=listar', { credentials: 'same-origin' });
        if (!resp.ok) throw new Error('No se pudo cargar especialidades');
        const lista = await resp.json();
        let options = '<option value="">Seleccione una especialidad...</option>';
        if (Array.isArray(lista)) {
            for (const esp of lista) {
                options += `<option value="${esp.id}">${esp.nombre}</option>`;
            }
        }
        select.innerHTML = options;
    } catch (e) {
        select.innerHTML = '<option value="">Error al cargar especialidades</option>';
    }
}

function cerrarModal() {
    let cerrado = false;
    if (modalDoctor && modalDoctor.style.display !== 'none') {
        modalDoctor.style.display = 'none';
        cerrado = true;
    }
    if (modalHorarios && modalHorarios.style.display !== 'none') {
        modalHorarios.style.display = 'none';
        cerrado = true;
    }
    if (cerrado) document.body.style.overflow = 'auto';

    const form = document.getElementById('formDoctor');
    if (form) {
        form.reset();
        form.querySelectorAll('.error-message').forEach(n => n.remove());
        form.querySelectorAll('.error').forEach(i => i.classList.remove('error'));
    }
}

window.abrirModalDoctor = abrirModalDoctor;
window.cerrarModal = cerrarModal;

function boot() {
    inicializarModales();
    inicializarEventos();
}

(function registerOrBootstrap() {
    if (window.AppCore && typeof window.AppCore.registerModule === 'function') {
        window.AppCore.registerModule('doctores', {
            init() { boot(); },
            destroy() {
                if (handleWindowClick) {
                    window.removeEventListener('click', handleWindowClick);
                    handleWindowClick = null;
                }
                modalDoctor = null;
                modalHorarios = null;
            }
        });
    } else {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    }
})();

function inicializarModales() {
    modalDoctor = document.getElementById('modalDoctor');
    modalHorarios = document.getElementById('modalHorarios');

    document.querySelectorAll('.close').forEach(btn => {
        btn.addEventListener('click', () => cerrarModal());
    });

    handleWindowClick = function(event) {
        if (event.target === modalDoctor || event.target === modalHorarios) {
            cerrarModal();
        }
    };
    window.addEventListener('click', handleWindowClick);
}

function inicializarEventos() {
    const btnNuevo = document.getElementById('btnNuevoDoctor');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            window.abrirModalDoctor('Registrar Nuevo Doctor');
        });
    }

    const btnCancelar = document.getElementById('btnCancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', cerrarModal);
    }

    const form = document.getElementById('formDoctor');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            // Aquí iría la lógica de envío (procesarDoctor)
            // await procesarDoctor(new FormData(this), this.querySelector('[name="id"]')?.value ? 'editar' : 'crear');
        });
    }
}

function abrirModalDoctor(titulo = 'Registrar Nuevo Doctor') {
    if (!modalDoctor) {
        modalDoctor = document.getElementById('modalDoctor');
    }
    if (!modalDoctor) return;
    // Título
    const tituloEl = document.getElementById('modalTitulo');
    if (tituloEl) tituloEl.textContent = titulo;
    // Cargar especialidades dinámicamente
    cargarEspecialidades();
    // Mostrar modal
    modalDoctor.style.display = 'block';
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        const input = modalDoctor.querySelector('input');
        if (input) input.focus();
    }, 100);
}

function cerrarModal() {
    let cerrado = false;
    if (modalDoctor && modalDoctor.style.display !== 'none') {
        modalDoctor.style.display = 'none';
        cerrado = true;
    }
    if (modalHorarios && modalHorarios.style.display !== 'none') {
        modalHorarios.style.display = 'none';
        cerrado = true;
    }
    if (cerrado) document.body.style.overflow = 'auto';

    const form = document.getElementById('formDoctor');
    if (form) {
        form.reset();
        form.querySelectorAll('.error-message').forEach(n => n.remove());
        form.querySelectorAll('.error').forEach(i => i.classList.remove('error'));
    }
}

window.abrirModalDoctor = abrirModalDoctor;
window.cerrarModal = cerrarModal;