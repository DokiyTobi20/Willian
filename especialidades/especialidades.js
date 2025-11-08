// Encapsulado para evitar conflictos globales
(function() {
    let modalEditar = null;
    let modalCrear = null;
    let handleWindowClick = null;

    function boot() {
        inicializarModales();
        inicializarEventos();
        configurarValidaciones();
    }

    function inicializarModales() {
        modalEditar = document.getElementById('modalEditar');
        modalCrear = document.getElementById('modalCrear');

        document.querySelectorAll('.close').forEach(close => {
            close.addEventListener('click', function() {
                cerrarModal();
            });
        });

        handleWindowClick = function(event) {
            if (event.target === modalEditar || event.target === modalCrear) {
                cerrarModal();
            }
        };
        window.addEventListener('click', handleWindowClick);
    }

    function inicializarEventos() {
        const formCrear = document.getElementById('formCrearEspecialidad');
        if (formCrear) {
            formCrear.addEventListener('submit', function(e) {
                e.preventDefault();
                if (validarFormulario(this)) {
                    const data = new FormData(this);
                    procesarFormulario(data, 'crear');
                }
            });
        }

        const formEditar = document.getElementById('formEditarEspecialidad');
        if (formEditar) {
            formEditar.addEventListener('submit', function(e) {
                e.preventDefault();
                if (validarFormulario(this)) {
                    const data = new FormData(this);
                    procesarFormulario(data, 'actualizar');
                }
            });
        }

        const searchInput = document.getElementById('nombreEspecialidad') || document.getElementById('busquedaEspecialidades');
        if (searchInput) {
            const debouncedFilter = debounce((value) => {
                filtrarEspecialidades(value);
            }, 250);
            searchInput.addEventListener('input', (e) => {
                debouncedFilter(e.target.value);
            });
            if (searchInput.value && searchInput.value.trim().length > 0) {
                filtrarEspecialidades(searchInput.value.trim());
            }
        }

        // Mostrar el modal al hacer clic en el botón "Nueva Especialidad"
        const botonNuevaEspecialidad = document.getElementById('botonNuevaEspecialidad');
        if (botonNuevaEspecialidad) {
            botonNuevaEspecialidad.addEventListener('click', function() {
                const modalCrear = document.getElementById('modalCrear');
                if (modalCrear) {
                    modalCrear.classList.add('show');
                    modalCrear.style.display = 'flex'; // Asegurar que se muestre centrado
                    document.body.style.overflow = 'hidden';
                }
            });
        }

        // Cerrar el modal al hacer clic en el botón de cerrar
        const botonesCerrar = document.querySelectorAll('.close');
        botonesCerrar.forEach(boton => {
            boton.addEventListener('click', function() {
                const modal = boton.closest('.modal');
                if (modal) {
                    modal.classList.remove('show');
                    modal.style.display = 'none'; // Ocultar el modal correctamente
                    document.body.style.overflow = 'auto';
                }
            });
        });
    }

    function configurarValidaciones() {
        const inputs = document.querySelectorAll('input[name="nombre"]');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                validarNombreEspecialidad(this);
            });
            input.addEventListener('blur', function() {
                validarNombreEspecialidad(this);
            });
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function validarFormulario(form) {
        const nombre = form.querySelector('[name="nombre"]').value.trim();
        if (!nombre) {
            mostrarAlerta('El nombre de la especialidad es obligatorio', 'error');
            return false;
        }
        if (nombre.length < 3) {
            mostrarAlerta('El nombre debe tener al menos 3 caracteres', 'error');
            return false;
        }
        if (nombre.length > 100) {
            mostrarAlerta('El nombre no puede exceder 100 caracteres', 'error');
            return false;
        }
        if (!validarCaracteresPermitidos(nombre)) {
            mostrarAlerta('El nombre contiene caracteres no permitidos', 'error');
            return false;
        }
        return true;
    }

    function validarNombreEspecialidad(input) {
        const nombre = input.value.trim();
        const container = input.closest('.form-group');
        const errorExistente = container.querySelector('.error-message');
        if (errorExistente) {
            errorExistente.remove();
        }
        input.classList.remove('error');
        if (nombre && !validarCaracteresPermitidos(nombre)) {
            mostrarErrorCampo(input, 'Solo se permiten letras, números y espacios');
            return false;
        }
        if (nombre.length > 100) {
            mostrarErrorCampo(input, 'Máximo 100 caracteres');
            return false;
        }
        return true;
    }

    function validarCaracteresPermitidos(texto) {
        const regex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-&\/\(\)]+$/;
        return regex.test(texto);
    }

    function mostrarErrorCampo(input, mensaje) {
        input.classList.add('error');
        const container = input.closest('.form-group');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = mensaje;
        container.appendChild(errorDiv);
    }

    async function procesarFormulario(formData, accion) {
        try {
            const submitBtn = document.querySelector(`#form${accion === 'crear' ? 'Crear' : 'Editar'}Especialidad button[type="submit"]`);
            const originalText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = accion === 'crear' ? 'Creando...' : (accion === 'actualizar' ? 'Actualizando...' : 'Procesando...');
            }
            const accionRepo = accion === 'crear' ? 'crear' : accion === 'actualizar' ? 'editar' : 'eliminar';
            const url = `../especialidades/operaciones_especialidades.php?accion=${accionRepo}`;
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            const result = await response.json();
            const ok = result && (result.success === true || result.status === 'success');
            const msg = result && (result.message || result.error) || 'Operación realizada.';
            if (ok) {
                mostrarAlerta(msg, 'success');
                if (accion === 'crear') {
                    const form = document.getElementById('formCrearEspecialidad');
                    if (form) form.reset();
                    cerrarModal();
                    // Siempre refrescar la vista completa para obtener datos actualizados del servidor
                    refrescarGridEspecialidades();
                    actualizarEstadisticas();
                } else if (accion === 'actualizar') {
                    cerrarModal();
                    // Refrescar la vista completa para asegurar sincronización
                    refrescarGridEspecialidades();
                } else if (accion === 'eliminar') {
                    // Refrescar la vista completa después de eliminar
                    refrescarGridEspecialidades();
                    actualizarEstadisticas();
                }
            } else {
                mostrarAlerta(msg, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión: ' + error.message, 'error');
        } finally {
            const submitBtn = document.querySelector(`#form${accion === 'crear' ? 'Crear' : 'Editar'}Especialidad button[type="submit"]`);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = submitBtn.getAttribute('data-original-text') || (accion === 'crear' ? 'Crear' : 'Actualizar');
            }
        }
    }

    function agregarCardEspecialidad(esp) {
        const grid = document.querySelector('.especialidades-grid');
        if (!grid) return;
        const card = document.createElement('div');
        card.className = 'especialidad-card';
        const totalDoctores = parseInt(esp.total_doctores || esp.totalDoctores || 0);
        const escNombre = escapeHtmlAttr(esp.nombre);
        const escNombreHtml = escapeHtml(esp.nombre);
        
        let botonEliminar = '';
        if (totalDoctores === 0) {
            botonEliminar = `<button class="btn-delete" onclick="eliminarEspecialidad(${Number(esp.id)}, '${escNombre}')">
                <i class='bx bx-trash'></i> Eliminar
            </button>`;
        } else {
            botonEliminar = `<button class="btn-secondary" disabled title="No se puede eliminar: tiene doctores asignados">
                <i class='bx bx-lock'></i> Protegida
            </button>`;
        }
        
        card.innerHTML = `
            <div class="especialidad-info">
                <h4>${escNombreHtml}</h4>
            </div>
            <div class="especialidad-actions">
                <button class="btn-edit" onclick="editarEspecialidad(${Number(esp.id)}, '${escNombre}')">
                    <i class='bx bx-edit'></i> Editar
                </button>
                ${botonEliminar}
            </div>
        `;
        // Agregar al final del grid para mantener el orden alfabético del servidor
        grid.appendChild(card);
    }

    function actualizarCardEspecialidad(id, nuevoNombre) {
        id = String(id);
        const editBtn = document.querySelector(`.especialidad-actions .btn-edit[onclick^="editarEspecialidad(${id},"]`);
        if (!editBtn) return;
        const card = editBtn.closest('.especialidad-card');
        if (!card) return;
        const title = card.querySelector('.especialidad-info h4');
        if (title) title.textContent = nuevoNombre;
        const escNombre = escapeHtmlAttr(nuevoNombre);
        editBtn.setAttribute('onclick', `editarEspecialidad(${id}, '${escNombre}')`);
        const delBtn = card.querySelector('.btn-delete');
        if (delBtn) delBtn.setAttribute('onclick', `eliminarEspecialidad(${id}, '${escNombre}')`);
    }

    function eliminarCardEspecialidad(id) {
        id = String(id);
        const editBtn = document.querySelector(`.especialidad-actions .btn-edit[onclick^="editarEspecialidad(${id},"]`);
        const card = editBtn ? editBtn.closest('.especialidad-card') : null;
        if (card && card.parentNode) {
            card.parentNode.removeChild(card);
        }
    }

    function actualizarContador() {
        const contador = document.querySelector('.contador');
        const visibles = document.querySelectorAll('.especialidades-grid .especialidad-card').length;
        if (contador) {
            contador.textContent = `${visibles} especialidad${visibles !== 1 ? 'es' : ''} registrada${visibles !== 1 ? 's' : ''}`;
        }
    }

    async function refrescarGridEspecialidades() {
        try {
            const resp = await fetch('../especialidades/operaciones_especialidades.php?accion=listar', { credentials: 'same-origin' });
            if (!resp.ok) {
                console.warn('Error al refrescar: respuesta no OK');
                return;
            }
            const lista = await resp.json();
            const grid = document.querySelector('.especialidades-grid');
            if (!grid) {
                console.warn('No se encontró el grid de especialidades');
                return;
            }
            if (!Array.isArray(lista)) {
                console.warn('La respuesta no es un array válido');
                return;
            }
            grid.innerHTML = '';
            if (lista.length === 0) {
                // Si no hay especialidades después de refrescar, recargar página para mostrar mensaje vacío
                window.location.reload();
                return;
            }
            // Agregar todas las especialidades en el orden que vienen del servidor
            lista.forEach(esp => {
                agregarCardEspecialidad(esp);
            });
            actualizarContador();
            // Si hay un término de búsqueda activo, volver a aplicarlo
            const searchInput = document.getElementById('nombreEspecialidad') || document.getElementById('busquedaEspecialidades');
            if (searchInput && searchInput.value && searchInput.value.trim().length > 0) {
                filtrarEspecialidades(searchInput.value.trim());
            }
        } catch (e) {
            console.error('Error al refrescar el grid:', e);
            // Si falla, recargar la página para asegurar sincronización
            window.location.reload();
        }
    }

    function actualizarEstadisticas() {
        // Actualizar contador de total de especialidades
        const grid = document.querySelector('.especialidades-grid');
        if (grid) {
            const totalCards = grid.querySelectorAll('.especialidad-card').length;
            const contador = document.querySelector('.contador');
            if (contador) {
                contador.textContent = `${totalCards} especialidad${totalCards !== 1 ? 'es' : ''} registrada${totalCards !== 1 ? 's' : ''}`;
            }
            // También actualizar las estadísticas en las cards superiores si es necesario
            // Para una actualización completa de estadísticas, sería mejor recargar la página
            // o hacer una petición adicional al servidor
        }
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;' };
        return String(text).replace(/[&<>]/g, ch => map[ch]);
    }

    function escapeHtmlAttr(text) {
        const map = { '&': '&amp;', '"': '&quot;', "'": '&#39;', '<': '&lt;', '>': '&gt;' };
        return String(text).replace(/[&"'<>]/g, ch => map[ch]);
    }

    function abrirModalCrear() {
        if (!modalCrear) {
            modalCrear = document.getElementById('modalCrear');
        }
        if (!modalCrear) return;
        modalCrear.style.display = 'block';
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const input = document.getElementById('nombre');
            if (input) input.focus();
        }, 100);
    }

    function editarEspecialidad(id, nombre) {
        if (!modalEditar) {
            modalEditar = document.getElementById('modalEditar');
        }
        if (!modalEditar) return;
        const idInput = document.getElementById('edit_id');
        const nombreInput = document.getElementById('edit_nombre');
        if (idInput) idInput.value = id;
        if (nombreInput) nombreInput.value = nombre;
        modalEditar.style.display = 'block';
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const input = document.getElementById('edit_nombre');
            if (input) {
                input.focus();
                input.select();
            }
        }, 100);
    }

    function eliminarEspecialidad(id, nombre) {
        if (!confirm(`¿Está seguro de eliminar la especialidad "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
            return;
        }
        const formData = new FormData();
        formData.append('accion', 'eliminar');
        formData.append('id', id);
        procesarFormulario(formData, 'eliminar');
    }

    function cerrarModal() {
        let cerrado = false;
        if (modalEditar && modalEditar.style.display !== 'none') {
            modalEditar.style.display = 'none';
            cerrado = true;
        }
        if (modalCrear && modalCrear.style.display !== 'none') {
            modalCrear.style.display = 'none';
            cerrado = true;
        }
        if (cerrado) {
            document.body.style.overflow = 'auto';
        }
        const forms = ['formEditarEspecialidad', 'formCrearEspecialidad'];
        forms.forEach(id => {
            const form = document.getElementById(id);
            if (form) {
                form.reset();
                const errores = form.querySelectorAll('.error-message');
                errores.forEach(error => error.remove());
                const inputs = form.querySelectorAll('.error');
                inputs.forEach(input => input.classList.remove('error'));
            }
        });
    }

    function filtrarEspecialidades(termino) {
        const grid = document.querySelector('.especialidades-grid');
        const cards = grid ? grid.querySelectorAll('.especialidad-card') : [];
        const terminoLower = (termino || '').toLowerCase();
        let visibles = 0;
        cards.forEach(card => {
            const nombreEl = card.querySelector('.especialidad-info h4') || card.querySelector('.especialidad-info span');
            const nombre = nombreEl ? nombreEl.textContent.toLowerCase() : '';
            const coincide = terminoLower.length === 0 || nombre.includes(terminoLower);
            if (coincide) {
                card.style.display = 'flex';
                visibles++;
            } else {
                card.style.display = 'none';
            }
        });
        const mensajeNoResultados = document.querySelector('.no-resultados-busqueda');
        if (mensajeNoResultados) {
            mensajeNoResultados.remove();
        }
        const contador = document.querySelector('.contador');
        if (contador) {
            if (terminoLower.length === 0) {
                const total = grid ? grid.querySelectorAll('.especialidad-card').length : 0;
                contador.textContent = `${total} especialidad${total !== 1 ? 'es' : ''} registrada${total !== 1 ? 's' : ''}`;
            } else {
                contador.textContent = `${visibles} especialidad${visibles !== 1 ? 'es' : ''} encontrada${visibles !== 1 ? 's' : ''}`;
            }
        }
    }

    function mostrarAlerta(mensaje, tipo = 'info') {
        const alertasExistentes = document.querySelectorAll('.alerta, .mensaje');
        alertasExistentes.forEach(alerta => alerta.remove());
        const alerta = document.createElement('div');
        alerta.className = `alerta ${tipo === 'success' ? 'exito' : tipo}`;
        alerta.innerHTML = `
            <i class="bx ${tipo === 'success' ? 'bx-check-circle' : tipo === 'error' ? 'bx-error-circle' : 'bx-info-circle'}"></i>
            <span>${mensaje}</span>
            <button class="btn-cerrar-alerta" onclick="cerrarAlerta(this)">&times;</button>
        `;
        const container = document.querySelector('.container') || document.body;
        if (container) {
            container.insertBefore(alerta, container.firstChild);
            setTimeout(() => {
                if (alerta && alerta.parentNode) {
                    alerta.remove();
                }
            }, 5000);
        }
    }

    function cerrarAlerta(button) {
        const alerta = button ? button.parentNode : null;
        if (alerta) {
            alerta.remove();
        }
    }

    // Hacer funciones globales para uso en HTML
    window.abrirModalCrear = abrirModalCrear;
    window.editarEspecialidad = editarEspecialidad;
    window.eliminarEspecialidad = eliminarEspecialidad;
    window.cerrarModal = cerrarModal;
    window.cerrarAlerta = cerrarAlerta;

    if (window.AppCore && typeof window.AppCore.registerModule === 'function') {
        window.AppCore.registerModule('especialidades', {
            init() {
                boot();
            },
            destroy() {
                if (handleWindowClick) {
                    window.removeEventListener('click', handleWindowClick);
                    handleWindowClick = null;
                }
                modalEditar = null;
                modalCrear = null;
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