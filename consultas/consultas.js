// Encapsulado para evitar conflictos globales
(function () {
    let modalEditar = null;
    let handleWindowClick = null;

    function getRutaOperaciones(modulo) {
        const pathname = window.location.pathname;
        if (pathname.includes('/panel/')) {
            return `../${modulo}/operaciones_${modulo}.php`;
        } else {
            return `../${modulo}/operaciones_${modulo}.php`;
        }
    }

    function inicializarAutocompletado(inputId, dropdownId, idUsuarioInputId) {
        const inputUsuario = document.getElementById(inputId);
        if (!inputUsuario) return;
        
        const dropdown = document.getElementById(dropdownId);
        const idUsuarioInput = document.getElementById(idUsuarioInputId);
        if (!dropdown || !idUsuarioInput) return;

        let timeoutId = null;

        inputUsuario.addEventListener('input', async function () {
            const query = inputUsuario.value.trim();
            
            // Limpiar timeout anterior
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
            
            if (query.length < 2) {
                cerrarDropdown(dropdown);
                idUsuarioInput.value = '';
                return;
            }
            
            // Debounce: esperar 300ms antes de buscar
            timeoutId = setTimeout(async () => {
                try {
                    const url = getRutaOperaciones('consultas') + '?accion=buscar_usuarios&q=' + encodeURIComponent(query);
                    const res = await fetch(url, { credentials: 'same-origin' });
                    if (!res.ok) {
                        throw new Error('Error en la búsqueda');
                    }
                    const usuarios = await res.json();
                    mostrarDropdown(usuarios, inputUsuario, dropdown, idUsuarioInput);
                } catch (error) {
                    console.error('Error al buscar usuarios:', error);
                    cerrarDropdown(dropdown);
                }
            }, 300);
        });

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function (e) {
            if (dropdown && !dropdown.contains(e.target) && e.target !== inputUsuario) {
                cerrarDropdown(dropdown);
            }
        });

        // Manejar teclas del teclado
        inputUsuario.addEventListener('keydown', function(e) {
            const items = dropdown.querySelectorAll('.autocomplete-item');
            const selected = dropdown.querySelector('.autocomplete-item.selected');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (selected) {
                    selected.classList.remove('selected');
                    const next = selected.nextElementSibling || items[0];
                    if (next) {
                        next.classList.add('selected');
                        next.scrollIntoView({ block: 'nearest' });
                    }
                } else if (items[0]) {
                    items[0].classList.add('selected');
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (selected) {
                    selected.classList.remove('selected');
                    const prev = selected.previousElementSibling || items[items.length - 1];
                    if (prev) {
                        prev.classList.add('selected');
                        prev.scrollIntoView({ block: 'nearest' });
                    }
                } else if (items[items.length - 1]) {
                    items[items.length - 1].classList.add('selected');
                }
            } else if (e.key === 'Enter' && selected) {
                e.preventDefault();
                selected.click();
            } else if (e.key === 'Escape') {
                cerrarDropdown(dropdown);
            }
        });
    }

    function mostrarDropdown(usuarios, input, dropdown, idUsuarioInput) {
        cerrarDropdown(dropdown);
        
        if (!usuarios || usuarios.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        
        dropdown.innerHTML = '';
        dropdown.style.display = 'block';
        dropdown.style.position = 'absolute';
        dropdown.style.background = '#fff';
        dropdown.style.border = '1px solid #ddd';
        dropdown.style.borderRadius = '8px';
        dropdown.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
        dropdown.style.zIndex = '2000';
        dropdown.style.maxHeight = '300px';
        dropdown.style.overflowY = 'auto';
        // Asegurar que el contenedor del input tenga position relative
        const formGroup = input.closest('.form-group');
        if (formGroup) {
            formGroup.style.position = 'relative';
        }
        
        dropdown.style.width = input.offsetWidth + 'px';
        dropdown.style.marginTop = '5px';
        
        usuarios.forEach(usuario => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.style.padding = '12px';
            item.style.cursor = 'pointer';
            item.style.borderBottom = '1px solid #f0f0f0';
            item.style.transition = 'background-color 0.2s';
            
            const nombreCompleto = escapeHtml(usuario.nombre + ' ' + usuario.apellido);
            const cedula = escapeHtml(usuario.cedula);
            
            item.innerHTML = `
                <div style="font-weight: 500; color: #333;">${nombreCompleto} - ${cedula}</div>
            `;
            
            item.addEventListener('mouseenter', function() {
                item.style.backgroundColor = '#f5f5f5';
                // Remover selected de otros items
                dropdown.querySelectorAll('.autocomplete-item').forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
            });
            
            item.addEventListener('mouseleave', function() {
                item.style.backgroundColor = '';
            });
            
            item.onclick = function () {
                // Mostrar en el formato: "Nombre Apellido - Cédula"
                input.value = usuario.nombre + ' ' + usuario.apellido + ' - ' + usuario.cedula;
                idUsuarioInput.value = usuario.id;
                cerrarDropdown(dropdown);
            };
            
            dropdown.appendChild(item);
        });
        
        // El dropdown se posicionará automáticamente si el form-group tiene position: relative
        // Si no, usar posición absoluta basada en el input
        if (formGroup && window.getComputedStyle(formGroup).position === 'relative') {
            dropdown.style.position = 'absolute';
            dropdown.style.top = '100%';
            dropdown.style.left = '0';
        } else {
            const rect = input.getBoundingClientRect();
            dropdown.style.position = 'fixed';
            dropdown.style.top = (rect.bottom + window.scrollY) + 'px';
            dropdown.style.left = (rect.left + window.scrollX) + 'px';
        }
    }

    function cerrarDropdown(dropdown) {
        if (dropdown) {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
        }
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return String(text).replace(/[&<>"']/g, ch => map[ch]);
    }


    function abrirModalEditarConsulta() {
        if (!modalEditar) {
            modalEditar = document.getElementById('modalEditar');
        }
        if (!modalEditar) return;
        modalEditar.style.display = 'block';
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const input = document.getElementById('edit_usuario');
            if (input) input.focus();
        }, 100);
    }

    async function cargarConsultas() {
        try {
            const url = getRutaOperaciones('consultas') + '?accion=listar';
            const resp = await fetch(url, { credentials: 'same-origin' });
            if (!resp.ok) {
                console.warn('Error al cargar consultas: respuesta no OK');
                return;
            }
            const lista = await resp.json();
            const grid = document.getElementById('consultasGrid');
            if (!grid) {
                console.warn('No se encontró el grid de consultas');
                return;
            }
            if (!Array.isArray(lista)) {
                console.warn('La respuesta no es un array válido');
                return;
            }
            
            grid.innerHTML = '';
            
            if (lista.length === 0) {
                grid.innerHTML = `
                    <div class="no-consultas" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <i class='bx bx-clipboard' style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
                        <h3>No hay consultas registradas</h3>
                        <p>Comienza agregando la primera consulta médica.</p>
                    </div>
                `;
                return;
            }
            
            lista.forEach(consulta => {
                agregarCardConsulta(consulta);
            });
            
            actualizarContadores(lista);
        } catch (error) {
            console.error('Error al cargar consultas:', error);
        }
    }

    function agregarCardConsulta(consulta) {
        const grid = document.getElementById('consultasGrid');
        if (!grid) return;
        
        const card = document.createElement('div');
        card.className = 'doctor-card';
        card.setAttribute('data-consulta-id', consulta.id);
        
        const pacienteNombre = escapeHtml((consulta.paciente_nombre || '') + ' ' + (consulta.paciente_apellido || ''));
        const pacienteCedula = escapeHtml(consulta.paciente_cedula || 'N/A');
        const diagnostico = escapeHtml(consulta.diagnostico || 'Sin diagnóstico');
        const receta = escapeHtml(consulta.receta || 'Sin receta');
        const observaciones = escapeHtml(consulta.observaciones || 'Sin observaciones');
        const doctorNombre = escapeHtml((consulta.doctor_nombre || '') + ' ' + (consulta.doctor_apellido || ''));
        const especialidad = escapeHtml(consulta.especialidad || 'Sin especialidad');
        
        // Formatear fecha
        let fechaFormateada = 'N/A';
        if (consulta.fecha_consulta) {
            const fecha = new Date(consulta.fecha_consulta);
            fechaFormateada = fecha.toLocaleString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        card.innerHTML = `
            <div class="doctor-info">
                <h4>${pacienteNombre}</h4>
                <p><i class='bx bx-id-card'></i> ${pacienteCedula}</p>
                <p><i class='bx bx-user'></i> Doctor: ${doctorNombre} - ${especialidad}</p>
                <p><i class='bx bx-calendar'></i> ${fechaFormateada}</p>
                <p><i class='bx bx-clipboard'></i> <strong>Diagnóstico:</strong> ${diagnostico.length > 50 ? diagnostico.substring(0, 50) + '...' : diagnostico}</p>
            </div>
            <div class="doctor-actions">
                <button class="btn-view" onclick="verConsulta(${consulta.id})" title="Ver detalles">
                    <i class='bx bx-show'></i> Ver
                </button>
                <button class="btn-edit" onclick="editarConsulta(${consulta.id})">
                    <i class='bx bx-edit'></i> Editar
                </button>
                <button class="btn-delete" onclick="eliminarConsulta(${consulta.id}, '${escapeHtmlAttr(pacienteNombre)}')">
                    <i class='bx bx-trash'></i> Eliminar
                </button>
            </div>
        `;
        
        grid.appendChild(card);
    }

    function actualizarContadores(consultas = null) {
        let total = 0;
        let completadas = 0;
        
        if (consultas && Array.isArray(consultas)) {
            total = consultas.length;
            // Todas las consultas tienen fecha_consulta, así que todas están completadas
            completadas = total;
        } else {
            // Si no se pasan consultas, contar las tarjetas en el grid
            const grid = document.getElementById('consultasGrid');
            if (grid) {
                const cards = grid.querySelectorAll('.doctor-card');
                total = cards.length;
                completadas = total; // Todas las consultas registradas están completadas
            }
        }
        
        const pendientes = 0; // Por ahora no hay pendientes, todas están completadas
        
        const statTotal = document.getElementById('statTotalConsultas');
        if (statTotal) {
            statTotal.textContent = total;
        }
        
        const statCompletadas = document.getElementById('statCompletadas');
        if (statCompletadas) {
            statCompletadas.textContent = completadas;
        }
        
        const statPendientes = document.getElementById('statPendientes');
        if (statPendientes) {
            statPendientes.textContent = pendientes;
        }
    }

    function escapeHtmlAttr(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return String(text).replace(/[&<>"']/g, ch => map[ch]);
    }

    async function verConsulta(id) {
        try {
            const url = getRutaOperaciones('consultas') + `?accion=obtener&id=${id}`;
            const resp = await fetch(url, { credentials: 'same-origin' });
            
            if (!resp.ok) {
                throw new Error('No se pudo cargar los datos de la consulta');
            }
            
            const consulta = await resp.json();
            
            if (!consulta || !consulta.id) {
                mostrarAlerta('No se encontraron los datos de la consulta', 'error');
                return;
            }
            
            // Formatear fecha
            let fechaFormateada = 'N/A';
            if (consulta.fecha_consulta) {
                const fecha = new Date(consulta.fecha_consulta);
                fechaFormateada = fecha.toLocaleString('es-ES', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
            
            const pacienteNombre = escapeHtml((consulta.paciente_nombre || '') + ' ' + (consulta.paciente_apellido || ''));
            const pacienteCedula = escapeHtml(consulta.paciente_cedula || 'N/A');
            const doctorNombre = escapeHtml((consulta.doctor_nombre || '') + ' ' + (consulta.doctor_apellido || ''));
            const diagnostico = escapeHtml(consulta.diagnostico || 'Sin diagnóstico');
            const receta = escapeHtml(consulta.receta || 'No especificada');
            const observaciones = escapeHtml(consulta.observaciones || 'No especificadas');
            
            const detallesHtml = `
                <div class="consulta-detalles" style="padding: 20px;">
                    <div class="detalle-seccion" style="margin-bottom: 25px;">
                        <h3 style="color: #209677; margin-bottom: 15px; border-bottom: 2px solid #209677; padding-bottom: 10px;">
                            <i class='bx bx-user'></i> Información del Paciente
                        </h3>
                        <p style="margin: 8px 0;"><strong>Nombre:</strong> ${pacienteNombre}</p>
                        <p style="margin: 8px 0;"><strong>Cédula:</strong> ${pacienteCedula}</p>
                    </div>
                    
                    <div class="detalle-seccion" style="margin-bottom: 25px;">
                        <h3 style="color: #209677; margin-bottom: 15px; border-bottom: 2px solid #209677; padding-bottom: 10px;">
                            <i class='bx bx-user-md'></i> Información del Doctor
                        </h3>
                        <p style="margin: 8px 0;"><strong>Doctor:</strong> ${doctorNombre}</p>
                        <p style="margin: 8px 0;"><strong>Fecha y Hora:</strong> ${fechaFormateada}</p>
                    </div>
                    
                    <div class="detalle-seccion" style="margin-bottom: 25px;">
                        <h3 style="color: #209677; margin-bottom: 15px; border-bottom: 2px solid #209677; padding-bottom: 10px;">
                            <i class='bx bx-clipboard'></i> Diagnóstico
                        </h3>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #209677;">
                            <p style="margin: 0; white-space: pre-wrap;">${diagnostico}</p>
                        </div>
                    </div>
                    
                    <div class="detalle-seccion" style="margin-bottom: 25px;">
                        <h3 style="color: #209677; margin-bottom: 15px; border-bottom: 2px solid #209677; padding-bottom: 10px;">
                            <i class='bx bx-capsule'></i> Receta
                        </h3>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #209677;">
                            <p style="margin: 0; white-space: pre-wrap;">${receta}</p>
                        </div>
                    </div>
                    
                    <div class="detalle-seccion">
                        <h3 style="color: #209677; margin-bottom: 15px; border-bottom: 2px solid #209677; padding-bottom: 10px;">
                            <i class='bx bx-comment'></i> Observaciones
                        </h3>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #209677;">
                            <p style="margin: 0; white-space: pre-wrap;">${observaciones}</p>
                        </div>
                    </div>
                </div>
            `;
            
            const detallesDiv = document.getElementById('detallesConsulta');
            if (detallesDiv) {
                detallesDiv.innerHTML = detallesHtml;
            }
            
            const modalVer = document.getElementById('modalVer');
            if (modalVer) {
                modalVer.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        } catch (error) {
            console.error('Error al cargar consulta:', error);
            mostrarAlerta('Error al cargar los datos de la consulta: ' + error.message, 'error');
        }
    }

    function cerrarModalVer() {
        const modalVer = document.getElementById('modalVer');
        if (modalVer) {
            modalVer.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    async function editarConsulta(id) {
        // TODO: Implementar edición
        console.log('Editar consulta:', id);
    }

    async function eliminarConsulta(id, nombre) {
        if (!confirm(`¿Está seguro de eliminar la consulta de ${nombre}?`)) {
            return;
        }
        
        try {
            const url = getRutaOperaciones('consultas') + '?accion=eliminar';
            const formData = new FormData();
            formData.append('id', id);
            
            const resp = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            if (!resp.ok) {
                throw new Error('Error al eliminar');
            }
            
            const result = await resp.json();
            
            if (result.status === 'success') {
                mostrarAlerta(result.message || 'Consulta eliminada con éxito', 'success');
                cargarConsultas();
                actualizarContadores();
            } else {
                mostrarAlerta(result.message || 'Error al eliminar la consulta', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión: ' + error.message, 'error');
        }
    }

    function boot() {
        inicializarModales();
        inicializarEventos();
        cargarConsultas();
    }

    function inicializarModales() {
        modalEditar = document.getElementById('modalEditar');
        const modalVer = document.getElementById('modalVer');
        
        document.querySelectorAll('.close').forEach(close => {
            close.addEventListener('click', function() {
                cerrarModalConsulta();
                cerrarModalVer();
            });
        });
        
        handleWindowClick = function (event) {
            if (event.target === modalEditar) {
                cerrarModalConsulta();
            }
            if (event.target === modalVer) {
                cerrarModalVer();
            }
        };
        window.addEventListener('click', handleWindowClick);
        
        // Cerrar modal ver con botón
        const btnCerrarVer = document.querySelector('#modalVer .btn-secondary');
        if (btnCerrarVer) {
            btnCerrarVer.addEventListener('click', cerrarModalVer);
        }
    }

    function abrirModalCrearConsulta() {
        const modalCrear = document.getElementById('modalCrear');
        if (!modalCrear) return;
        modalCrear.style.display = 'block';
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const input = document.getElementById('buscar_usuario_crear');
            if (input) input.focus();
        }, 100);
    }

    function inicializarEventos() {
        const btnNuevaConsulta = document.getElementById('btnNuevaConsulta');
        if (btnNuevaConsulta) {
            btnNuevaConsulta.addEventListener('click', abrirModalCrearConsulta);
        }
        document.querySelectorAll('.btn-secondary').forEach(btn => {
            btn.addEventListener('click', cerrarModalConsulta);
        });
        
        // Inicializar autocompletado para ambos modales
        inicializarAutocompletado('buscar_usuario_crear', 'dropdown_usuarios_crear', 'id_usuario_crear');
        inicializarAutocompletado('edit_usuario', 'dropdown_usuarios_editar', 'id_usuario_editar');
        
        // Event listener para formulario de crear consulta
        const formCrear = document.getElementById('formCrearConsulta');
        if (formCrear) {
            formCrear.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (validarFormularioConsulta(this)) {
                    await procesarConsulta(new FormData(this), 'crear');
                }
            });
        }
        
        // Event listener para formulario de editar consulta
        const formEditar = document.getElementById('formEditarConsulta');
        if (formEditar) {
            formEditar.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (validarFormularioConsulta(this)) {
                    await procesarConsulta(new FormData(this), 'editar');
                }
            });
        }
    }

    function validarFormularioConsulta(form) {
        const idUsuario = form.querySelector('[name="id_usuario"]')?.value;
        const diagnostico = form.querySelector('[name="diagnostico"]')?.value.trim();
        
        // Limpiar errores previos
        form.querySelectorAll('.error-message').forEach(n => n.remove());
        form.querySelectorAll('.error').forEach(i => i.classList.remove('error'));
        
        let valido = true;
        
        if (!idUsuario) {
            mostrarErrorCampo(form.querySelector('[name="buscar_usuario"]') || form.querySelector('#buscar_usuario_crear'), 'Debe seleccionar un usuario');
            valido = false;
        }
        
        if (!diagnostico || diagnostico.length < 3) {
            mostrarErrorCampo(form.querySelector('[name="diagnostico"]'), 'El diagnóstico es obligatorio y debe tener al menos 3 caracteres');
            valido = false;
        }
        
        return valido;
    }

    function mostrarErrorCampo(input, mensaje) {
        if (!input) return;
        input.classList.add('error');
        const container = input.closest('.form-group') || input.parentElement;
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = mensaje;
        errorDiv.style.color = '#dc3545';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        container.appendChild(errorDiv);
    }

    async function procesarConsulta(formData, accion) {
        let submitBtn = null;
        let originalText = '';
        try {
            submitBtn = document.querySelector(`#form${accion === 'crear' ? 'Crear' : 'Editar'}Consulta button[type="submit"]`);
            originalText = submitBtn ? submitBtn.textContent : '';
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = accion === 'crear' ? 'Creando...' : 'Actualizando...';
            }

            // Recopilar datos
            const data = {
                id_usuario: formData.get('id_usuario'),
                diagnostico: formData.get('diagnostico'),
                receta: formData.get('receta') || null,
                observaciones: formData.get('observaciones') || null
            };

            // Si es edición, agregar el ID
            if (accion === 'editar') {
                const idInput = document.querySelector(`#formEditarConsulta [name="id"]`);
                if (idInput) {
                    data.id = idInput.value;
                }
            }

            // Crear FormData para enviar
            const sendData = new FormData();
            Object.keys(data).forEach(key => {
                if (data[key] !== null && data[key] !== undefined) {
                    sendData.append(key, data[key]);
                }
            });

            const accionUrl = accion === 'crear' ? 'crear' : 'editar';
            const url = `${getRutaOperaciones('consultas')}?accion=${accionUrl}`;
            
            console.log('Enviando datos al servidor:', {
                url: url,
                accion: accionUrl,
                data: Object.fromEntries(sendData)
            });

            const response = await fetch(url, {
                method: 'POST',
                body: sendData,
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const result = await response.json();
            console.log('Respuesta del servidor:', result);
            
            const ok = result && (result.success === true || result.status === 'success');
            const msg = result && (result.message || result.error) || 'Operación realizada.';

            if (ok) {
                mostrarAlerta(msg, 'success');
                cerrarModalConsulta();
                cargarConsultas();
                actualizarContadores();
            } else {
                mostrarAlerta(msg, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión: ' + error.message, 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText || (accion === 'crear' ? 'Crear' : 'Actualizar');
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
        alerta.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            ${tipo === 'success' ? 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 
              tipo === 'error' ? 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' :
              'background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;'}
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

    function cerrarModalConsulta() {
        const modalCrear = document.getElementById('modalCrear');
        if (modalCrear && modalCrear.style.display !== 'none') {
            modalCrear.style.display = 'none';
            document.body.style.overflow = 'auto';
            // Limpiar formulario
            const form = document.getElementById('formCrearConsulta');
            if (form) {
                form.reset();
                document.getElementById('id_usuario_crear').value = '';
                // Limpiar errores
                form.querySelectorAll('.error-message').forEach(n => n.remove());
                form.querySelectorAll('.error').forEach(i => i.classList.remove('error'));
            }
        }
        if (modalEditar && modalEditar.style.display !== 'none') {
            modalEditar.style.display = 'none';
            document.body.style.overflow = 'auto';
            // Limpiar formulario
            const form = document.getElementById('formEditarConsulta');
            if (form) {
                form.reset();
                document.getElementById('id_usuario_editar').value = '';
                // Limpiar errores
                form.querySelectorAll('.error-message').forEach(n => n.remove());
                form.querySelectorAll('.error').forEach(i => i.classList.remove('error'));
            }
        }
    }

    // Hacer funciones globales para uso en HTML si es necesario
    window.abrirModalCrearConsulta = abrirModalCrearConsulta;
    window.abrirModalEditarConsulta = abrirModalEditarConsulta;
    window.cerrarModalConsulta = cerrarModalConsulta;
    window.cerrarModalVer = cerrarModalVer;
    window.cerrarAlerta = cerrarAlerta;
    window.verConsulta = verConsulta;
    window.editarConsulta = editarConsulta;
    window.eliminarConsulta = eliminarConsulta;

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
