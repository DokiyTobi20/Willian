// Encapsulado para evitar conflictos globales
(function () {
    let modalEditar = null;
    let handleWindowClick = null;

    function autocompletarUsuario() {
        const inputUsuario = document.getElementById('edit_usuario');
        if (!inputUsuario) return;
        let lista = [];
        let dropdown = null;

        inputUsuario.addEventListener('input', async function () {
            const query = inputUsuario.value.trim();
            if (query.length < 2) {
                cerrarDropdown();
                return;
            }
            try {
                const res = await fetch('../BDD/operaciones_bd.php?accion=buscar_usuario&q=' + encodeURIComponent(query));
                lista = await res.json();
            } catch {
                lista = [];
            }
            mostrarDropdown(lista, inputUsuario);
        });

        function mostrarDropdown(usuarios, input) {
            cerrarDropdown();
            if (!usuarios || usuarios.length === 0) return;
            dropdown = document.createElement('div');
            dropdown.className = 'autocomplete-dropdown';
            dropdown.style.position = 'absolute';
            dropdown.style.background = '#fff';
            dropdown.style.border = '1.5px solid #e1e5e9';
            dropdown.style.borderRadius = '7px';
            dropdown.style.zIndex = '2000';
            dropdown.style.width = input.offsetWidth + 'px';
            usuarios.forEach(usuario => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.textContent = usuario.nombre + ' ' + usuario.apellido + ' (' + usuario.cedula + ')';
                item.style.padding = '8px';
                item.style.cursor = 'pointer';
                item.onclick = function () {
                    input.value = usuario.nombre + ' ' + usuario.apellido;
                    cerrarDropdown();
                };
                dropdown.appendChild(item);
            });
            input.parentNode.appendChild(dropdown);
        }

        function cerrarDropdown() {
            if (dropdown) {
                dropdown.remove();
                dropdown = null;
            }
        }

        document.addEventListener('click', function (e) {
            if (dropdown && !dropdown.contains(e.target) && e.target !== inputUsuario) {
                cerrarDropdown();
            }
        });
    }

    function abrirModalEditarConsulta() {
        if (!modalEditar) {
            modalEditar = document.getElementById('modalEditar');
        }
        if (!modalEditar) return;
        modalEditar.style.display = 'block';
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const input = modalEditar.querySelector('input, textarea');
            if (input) input.focus();
            autocompletarUsuario();
        }, 100);
    }

    function boot() {
        inicializarModales();
        inicializarEventos();
    }

    function inicializarModales() {
        modalEditar = document.getElementById('modalEditar');
        document.querySelectorAll('.close').forEach(close => {
            close.addEventListener('click', cerrarModalConsulta);
        });
        handleWindowClick = function (event) {
            if (event.target === modalEditar) {
                cerrarModalConsulta();
            }
        };
        window.addEventListener('click', handleWindowClick);
    }

    function inicializarEventos() {
        const btnNuevaConsulta = document.getElementById('btnNuevaConsulta');
        if (btnNuevaConsulta) {
            btnNuevaConsulta.addEventListener('click', abrirModalEditarConsulta);
        }
        document.querySelectorAll('.btn-secondary').forEach(btn => {
            btn.addEventListener('click', cerrarModalConsulta);
        });
    }

    function cerrarModalConsulta() {
        if (modalEditar && modalEditar.style.display !== 'none') {
            modalEditar.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    // Hacer funciones globales para uso en HTML si es necesario
    window.abrirModalEditarConsulta = abrirModalEditarConsulta;
    window.cerrarModalConsulta = cerrarModalConsulta;

    if (window.AppCore && typeof window.AppCore.registerModule === 'function') {
        window.AppCore.registerModule('consultas', {
            init() {
                boot();
            },
            destroy() {
                if (handleWindowClick) {
                    window.removeEventListener('click', handleWindowClick);
                    handleWindowClick = null;
                }
                modalEditar = null;
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
