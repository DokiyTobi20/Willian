// Estado del módulo
let modalEditar = null;
let modalCrear = null;
let handleWindowClick = null; // para poder remover el listener global en destroy

function boot() {
    inicializarModales();
    inicializarEventos();
    configurarValidaciones();
}

// Registro con AppCore si está disponible; si no, modo standalone
(function registerOrBootstrap() {
    if (window.AppCore && typeof window.AppCore.registerModule === 'function') {
        window.AppCore.registerModule('especialidades', {
            init() {
                boot();
            },
            destroy() {
                // Remover listeners globales para evitar duplicados al volver a la vista
                if (handleWindowClick) {
                    window.removeEventListener('click', handleWindowClick);
                    handleWindowClick = null;
                }
                modalEditar = null;
                modalCrear = null;
            }
        });
    } else {
        // Standalone (acceso directo a especialidades.php)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    }
})();

function inicializarModales() {
    modalEditar = document.getElementById('modalEditar');
    modalCrear = document.getElementById('modalCrear');
    
    // Cerrar modal al hacer clic en la X
    document.querySelectorAll('.close').forEach(close => {
        close.addEventListener('click', function() {
            cerrarModal();
        });
    });
    
    // Cerrar modal al hacer clic fuera
    handleWindowClick = function(event) {
        if (event.target === modalEditar || event.target === modalCrear) {
            cerrarModal();
        }
    };
    window.addEventListener('click', handleWindowClick);
}

function inicializarEventos() {
    // Formulario de crear especialidad
    const formCrear = document.getElementById('formCrearEspecialidad');
    if (formCrear) {
        formCrear.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validarFormulario(this)) {
                const data = new FormData(this);
                // accion=crear (ya está en input hidden del formulario)
                procesarFormulario(data, 'crear');
            }
        });
    }
    
    // Formulario de editar especialidad
    const formEditar = document.getElementById('formEditarEspecialidad');
    if (formEditar) {
        formEditar.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validarFormulario(this)) {
                const data = new FormData(this);
                // accion=actualizar (ya está en input hidden del formulario)
                procesarFormulario(data, 'actualizar');
            }
        });
    }
    
    // Búsqueda en tiempo real (compatibilidad con ambos IDs)
    const searchInput = document.getElementById('nombreEspecialidad') || document.getElementById('busquedaEspecialidades');
    if (searchInput) {
        const debouncedFilter = debounce((value) => {
            filtrarEspecialidades(value);
        }, 250);
        searchInput.addEventListener('input', (e) => {
            debouncedFilter(e.target.value);
        });
        // Si viene con texto prellenado, aplicar filtro al iniciar
        if (searchInput.value && searchInput.value.trim().length > 0) {
            filtrarEspecialidades(searchInput.value.trim());
        }
    }
}

function configurarValidaciones() {
    // Validación en tiempo real para nombres
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
    
    // Remover mensajes de error anteriores
    const errorExistente = container.querySelector('.error-message');
    if (errorExistente) {
        errorExistente.remove();
    }
    
    // Remover clases de error
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
    // Permitir letras, números, espacios, guiones y algunos caracteres especiales médicos
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
        // Mostrar indicador de carga
        const submitBtn = document.querySelector(`#form${accion === 'crear' ? 'Crear' : 'Editar'}Especialidad button[type="submit"]`);
        const originalText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = accion === 'crear' ? 'Creando...' : (accion === 'actualizar' ? 'Actualizando...' : 'Procesando...');
        }
        
        // Mapear acciones a endpoints del repositorio actual
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
                // Limpiar formulario y cerrar modal
                const form = document.getElementById('formCrearEspecialidad');
                if (form) form.reset();
                if (modalCrear) modalCrear.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Agregar la nueva card al grid si tenemos el id
                const nuevoId = result.id || result.new_id || null;
                const nombre = formData.get('nombre');
                if (nuevoId && nombre) {
                    agregarCardEspecialidad({ id: nuevoId, nombre });
                    actualizarContador();
                } else {
                    // Fallback: intentar refrezcar solo el grid
                    refrescarGridEspecialidades();
                }
            } else if (accion === 'actualizar') {
                // Cerrar modal y actualizar card en el grid
                cerrarModal();
                const id = formData.get('id');
                const nombre = formData.get('nombre');
                if (id && nombre) {
                    actualizarCardEspecialidad(id, nombre);
                } else {
                    refrescarGridEspecialidades();
                }
            } else if (accion === 'eliminar') {
                // Quitar la card eliminada
                const id = formData.get('id');
                if (id) {
                    eliminarCardEspecialidad(id);
                    actualizarContador();
                } else {
                    refrescarGridEspecialidades();
                }
            }
            
            // No recargar la página; quedamos listos para nuevas acciones
            
        } else {
            mostrarAlerta(msg, 'error');
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión: ' + error.message, 'error');
    } finally {
        // Restaurar botón
        const submitBtn = document.querySelector(`#form${accion === 'crear' ? 'Crear' : 'Editar'}Especialidad button[type="submit"]`);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = submitBtn.getAttribute('data-original-text') || (accion === 'crear' ? 'Crear' : 'Actualizar');
        }
    }
}

// Helpers de actualización del DOM (sin recargar la página)
function agregarCardEspecialidad(esp) {
    const grid = document.querySelector('.especialidades-grid');
    if (!grid) return;
    const card = document.createElement('div');
    card.className = 'especialidad-card';
    card.innerHTML = `
        <div class="especialidad-info">
            <h4>${escapeHtml(esp.nombre)}</h4>
        </div>
        <div class="especialidad-actions">
            <button class="btn-edit" onclick="editarEspecialidad(${Number(esp.id)}, '${escapeHtmlAttr(esp.nombre)}')">
                <i class='bx bx-edit'></i> Editar
            </button>
            <button class="btn-delete" onclick="eliminarEspecialidad(${Number(esp.id)}, '${escapeHtmlAttr(esp.nombre)}')">
                <i class='bx bx-trash'></i> Eliminar
            </button>
        </div>
    `;
    grid.prepend(card);
}

function actualizarCardEspecialidad(id, nuevoNombre) {
    id = String(id);
    const editBtn = document.querySelector(`.especialidad-actions .btn-edit[onclick^="editarEspecialidad(${id},"]`);
    if (!editBtn) return;
    const card = editBtn.closest('.especialidad-card');
    if (!card) return;
    const title = card.querySelector('.especialidad-info h4');
    if (title) title.textContent = nuevoNombre;
    // Actualizar los onclicks con el nuevo nombre escapado
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
        if (!resp.ok) return;
        const lista = await resp.json();
        const grid = document.querySelector('.especialidades-grid');
        if (!grid || !Array.isArray(lista)) return;
        grid.innerHTML = '';
        lista.forEach(esp => agregarCardEspecialidad(esp));
        actualizarContador();
    } catch (e) {
        console.warn('No se pudo refrescar el grid:', e);
    }
}

// Utilidades de escape para evitar romper atributos HTML
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
    
    // Llenar el formulario
    const idInput = document.getElementById('edit_id');
    const nombreInput = document.getElementById('edit_nombre');
    if (idInput) idInput.value = id;
    if (nombreInput) nombreInput.value = nombre;
    
    // Mostrar modal
    modalEditar.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Enfocar el campo de nombre
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
    // Cerrar ambos modales si existen
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

    // Limpiar formularios y errores
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
            // Mantener layout original (flex)
            card.style.display = 'flex';
            visibles++;
        } else {
            card.style.display = 'none';
        }
    });

    // No mostrar texto de "sin resultados"; si existiera previamente, removerlo
    const mensajeNoResultados = document.querySelector('.no-resultados-busqueda');
    if (mensajeNoResultados) {
        mensajeNoResultados.remove();
    }

    // Actualizar contador
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
    // Remover alertas existentes
    const alertasExistentes = document.querySelectorAll('.alerta, .mensaje');
    alertasExistentes.forEach(alerta => alerta.remove());
    
    // Crear nueva alerta
    const alerta = document.createElement('div');
    alerta.className = `alerta ${tipo === 'success' ? 'exito' : tipo}`;
    alerta.innerHTML = `
        <i class="bx ${tipo === 'success' ? 'bx-check-circle' : tipo === 'error' ? 'bx-error-circle' : 'bx-info-circle'}"></i>
        <span>${mensaje}</span>
        <button class="btn-cerrar-alerta" onclick="cerrarAlerta(this)">&times;</button>
    `;
    
    // Insertar en el DOM
    const container = document.querySelector('.container') || document.body;
    if (container) {
        container.insertBefore(alerta, container.firstChild);
        
        // Auto-remover después de 5 segundos
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