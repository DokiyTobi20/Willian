// Encapsulado para evitar conflictos globales
(function() {
    let modalDoctor = null;
    let modalHorarios = null;
    let handleWindowClick = null;

    function boot() {
        inicializarModales();
        inicializarEventos();
        cargarDoctores();
    }

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
                abrirModalDoctor('Registrar Nuevo Doctor');
            });
        }

        const btnCancelar = document.getElementById('btnCancelar');
        if (btnCancelar) {
            btnCancelar.addEventListener('click', cerrarModal);
        }

        // Habilitar/deshabilitar los campos de hora según el checkbox de cada día
        document.querySelectorAll('.schedule-day').forEach(day => {
            const checkbox = day.querySelector('input[type="checkbox"]');
            const horaInicio = day.querySelector('input[name^="hora_inicio"]');
            const horaFin = day.querySelector('input[name^="hora_fin"]');
            if (checkbox && horaInicio && horaFin) {
                checkbox.addEventListener('change', function() {
                    horaInicio.disabled = !checkbox.checked;
                    horaFin.disabled = !checkbox.checked;
                    if (!checkbox.checked) {
                        horaInicio.value = '';
                        horaFin.value = '';
                    }
                });
            }
        });

        const form = document.getElementById('formDoctor');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (validarFormulario(this)) {
                    await procesarDoctor(new FormData(this), this.querySelector('[name="id"]')?.value ? 'editar' : 'crear');
                }
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
        cargarEspecialidades();
        modalDoctor.style.display = 'block';
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const input = modalDoctor.querySelector('input');
            if (input) input.focus();
        }, 100);
    }

    function getRutaOperaciones(modulo) {
        // Usar la misma estructura que especialidades
        // Siempre usar ../modulo/operaciones_modulo.php
        // Esto funciona tanto desde el panel como desde acceso directo
        return `../${modulo}/operaciones_${modulo}.php`;
    }

    async function cargarEspecialidades() {
        const select = document.getElementById('id_especialidad');
        if (!select) return;
        select.innerHTML = '<option value="">Cargando especialidades...</option>';
        try {
            const url = getRutaOperaciones('especialidades') + '?accion=listar';
            const resp = await fetch(url, { credentials: 'same-origin' });
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
            console.error('Error cargando especialidades:', e);
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
            // Resetear checkboxes de días y deshabilitar campos de hora
            document.querySelectorAll('.schedule-day input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
                const day = checkbox.closest('.schedule-day');
                const horaInicio = day.querySelector('input[name^="hora_inicio"]');
                const horaFin = day.querySelector('input[name^="hora_fin"]');
                if (horaInicio) horaInicio.disabled = true;
                if (horaFin) horaFin.disabled = true;
            });
            // Resetear el título del modal
            const tituloEl = document.getElementById('modalTitulo');
            if (tituloEl) tituloEl.textContent = 'Registrar Nuevo Doctor';
            // Limpiar campo oculto de ID
            const idInput = form.querySelector('[name="id"]');
            if (idInput) idInput.value = '';
            // Resetear texto del botón
            const btnGuardar = document.getElementById('btnGuardar');
            if (btnGuardar) btnGuardar.textContent = 'Crear Doctor';
        }
    }

    function validarFormulario(form) {
        const nombre = form.querySelector('[name="nombre"]')?.value.trim();
        const apellido = form.querySelector('[name="apellido"]')?.value.trim();
        const cedula = form.querySelector('[name="cedula"]')?.value.trim();
        const correo = form.querySelector('[name="correo"]')?.value.trim();
        const idEspecialidad = form.querySelector('[name="id_especialidad"]')?.value;

        // Limpiar errores previos
        form.querySelectorAll('.error-message').forEach(n => n.remove());
        form.querySelectorAll('.error').forEach(i => i.classList.remove('error'));

        let valido = true;

        if (!nombre || nombre.length < 2) {
            mostrarErrorCampo(form.querySelector('[name="nombre"]'), 'El nombre es obligatorio y debe tener al menos 2 caracteres');
            valido = false;
        }

        if (!apellido || apellido.length < 2) {
            mostrarErrorCampo(form.querySelector('[name="apellido"]'), 'El apellido es obligatorio y debe tener al menos 2 caracteres');
            valido = false;
        }

        if (!cedula || cedula.length < 5) {
            mostrarErrorCampo(form.querySelector('[name="cedula"]'), 'La cédula es obligatoria y debe tener al menos 5 caracteres');
            valido = false;
        }

        if (!correo || !validarEmail(correo)) {
            mostrarErrorCampo(form.querySelector('[name="correo"]'), 'El correo electrónico es obligatorio y debe ser válido');
            valido = false;
        }

        if (!idEspecialidad) {
            mostrarErrorCampo(form.querySelector('[name="id_especialidad"]'), 'Debe seleccionar una especialidad');
            valido = false;
        }

        // Validar horarios: si un día está marcado, debe tener horas
        const diasMarcados = form.querySelectorAll('input[name="dias[]"]:checked');
        diasMarcados.forEach(checkbox => {
            const day = checkbox.closest('.schedule-day');
            const dia = checkbox.value;
            const horaInicio = day.querySelector(`input[name="hora_inicio[${dia}]"]`);
            const horaFin = day.querySelector(`input[name="hora_fin[${dia}]"]`);
            
            if (checkbox.checked) {
                if (!horaInicio.value || !horaFin.value) {
                    mostrarAlerta('Los días marcados deben tener hora de inicio y fin', 'error');
                    valido = false;
                } else if (horaInicio.value >= horaFin.value) {
                    mostrarAlerta(`La hora de fin debe ser mayor que la hora de inicio para el día seleccionado`, 'error');
                    valido = false;
                }
            }
        });

        return valido;
    }

    function validarEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
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

    async function procesarDoctor(formData, accion) {
        try {
            const submitBtn = document.querySelector('#formDoctor button[type="submit"]');
            const originalText = submitBtn ? submitBtn.textContent : '';
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = accion === 'crear' ? 'Creando...' : 'Actualizando...';
            }

            // Recopilar datos básicos
            const data = {
                nombre: formData.get('nombre'),
                apellido: formData.get('apellido'),
                cedula: formData.get('cedula'),
                correo: formData.get('correo'),
                telefono: formData.get('telefono') || null,
                fecha_nacimiento: formData.get('fecha_nacimiento') || null,
                id_especialidad: formData.get('id_especialidad'),
                direccion: formData.get('direccion') || null
            };

            // Si es edición, agregar el ID
            if (accion === 'editar') {
                const idInput = document.querySelector('#formDoctor [name="id"]');
                if (idInput) {
                    data.id = idInput.value;
                }
            }

            // Procesar horarios - obtener directamente del formulario
            const horarios = [];
            const form = document.getElementById('formDoctor');
            const diasMarcados = Array.from(form.querySelectorAll('input[name="dias[]"]:checked'));
            
            diasMarcados.forEach(checkbox => {
                const dia = checkbox.value;
                const dayContainer = checkbox.closest('.schedule-day');
                const horaInicioInput = dayContainer.querySelector(`input[name="hora_inicio[${dia}]"]`);
                const horaFinInput = dayContainer.querySelector(`input[name="hora_fin[${dia}]"]`);
                
                const horaInicio = horaInicioInput ? horaInicioInput.value : null;
                const horaFin = horaFinInput ? horaFinInput.value : null;
                
                if (horaInicio && horaFin) {
                    horarios.push({
                        dia: parseInt(dia),
                        hora_inicio_am: horaInicio,
                        hora_fin_am: horaFin,
                        hora_inicio_pm: null,
                        hora_fin_pm: null
                    });
                }
            });

            data.horarios = horarios;

            // Crear FormData para enviar
            const sendData = new FormData();
            
            // Agregar campos básicos (obligatorios)
            sendData.append('nombre', data.nombre);
            sendData.append('apellido', data.apellido);
            sendData.append('cedula', data.cedula);
            sendData.append('correo', data.correo);
            sendData.append('id_especialidad', data.id_especialidad);
            
            // Agregar campos opcionales solo si tienen valor
            if (data.telefono && data.telefono.trim() !== '') {
                sendData.append('telefono', data.telefono);
            }
            if (data.fecha_nacimiento && data.fecha_nacimiento.trim() !== '') {
                sendData.append('fecha_nacimiento', data.fecha_nacimiento);
            }
            if (data.direccion && data.direccion.trim() !== '') {
                sendData.append('direccion', data.direccion);
            }
            
            // Agregar ID si es edición
            if (data.id) {
                sendData.append('id', data.id);
            }
            
            // Agregar horarios como JSON
            sendData.append('horarios', JSON.stringify(data.horarios));

            const accionUrl = accion === 'crear' ? 'crear' : 'editar';
            const url = `${getRutaOperaciones('doctores')}?accion=${accionUrl}`;
            
            console.log('Enviando datos al servidor:', {
                url: url,
                location: window.location.pathname,
                accion: accionUrl,
                data: data,
                horarios: data.horarios
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
                cerrarModal();
                // Refrescar la lista de doctores
                cargarDoctores();
            } else {
                mostrarAlerta(msg, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión: ' + error.message, 'error');
        } finally {
            const submitBtn = document.querySelector('#formDoctor button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText || (accion === 'crear' ? 'Crear Doctor' : 'Actualizar Doctor');
            }
        }
    }

    function mostrarAlerta(mensaje, tipo = 'info') {
        // Eliminar alertas existentes
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

    async function cargarDoctores() {
        try {
            const url = getRutaOperaciones('doctores') + '?accion=listar';
            const resp = await fetch(url, { credentials: 'same-origin' });
            if (!resp.ok) {
                console.warn('Error al cargar doctores: respuesta no OK');
                return;
            }
            const lista = await resp.json();
            const grid = document.getElementById('doctoresGrid');
            if (!grid) {
                console.warn('No se encontró el grid de doctores');
                return;
            }
            if (!Array.isArray(lista)) {
                console.warn('La respuesta no es un array válido');
                return;
            }
            
            // Limpiar grid
            grid.innerHTML = '';
            
            if (lista.length === 0) {
                grid.innerHTML = `
                    <div class="no-doctores" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <i class='bx bx-user-x' style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
                        <h3>No hay doctores registrados</h3>
                        <p>Comienza agregando el primer doctor médico.</p>
                    </div>
                `;
            } else {
                lista.forEach(doctor => {
                    agregarCardDoctor(doctor);
                });
            }
            
            actualizarContadores(lista);
        } catch (e) {
            console.error('Error al cargar doctores:', e);
        }
    }

    function agregarCardDoctor(doctor) {
        const grid = document.getElementById('doctoresGrid');
        if (!grid) return;
        
        const card = document.createElement('div');
        card.className = 'doctor-card';
        card.setAttribute('data-doctor-id', doctor.id);
        
        const nombreCompleto = escapeHtml(doctor.nombre + ' ' + doctor.apellido);
        const nombreCompletoAttr = escapeHtmlAttr(doctor.nombre + ' ' + doctor.apellido);
        
        let telefonoHtml = '';
        if (doctor.telefono) {
            telefonoHtml = `<p><i class='bx bx-phone'></i> ${escapeHtml(doctor.telefono)}</p>`;
        }
        
        card.innerHTML = `
            <div class="doctor-info">
                <h4>${nombreCompleto}</h4>
                <p><i class='bx bx-id-card'></i> ${escapeHtml(doctor.cedula)}</p>
                <p><i class='bx bx-envelope'></i> ${escapeHtml(doctor.correo || 'N/A')}</p>
                <p><i class='bx bx-plus-medical'></i> ${escapeHtml(doctor.especialidad || 'Sin especialidad')}</p>
                ${telefonoHtml}
            </div>
            <div class="doctor-actions">
                <button class="btn-edit" onclick="editarDoctor(${doctor.id})">
                    <i class='bx bx-edit'></i> Editar
                </button>
                <button class="btn-delete" onclick="eliminarDoctor(${doctor.id}, '${nombreCompletoAttr}')">
                    <i class='bx bx-trash'></i> Eliminar
                </button>
            </div>
        `;
        
        grid.appendChild(card);
    }

    function actualizarContadores(doctores) {
        const total = doctores.length;
        const especialidadesUnicas = new Set(doctores.map(d => d.id_especialidad).filter(id => id));
        
        const statTotal = document.getElementById('statTotalDoctores');
        const statEspecialidades = document.getElementById('statEspecialidades');
        const contadorResultados = document.getElementById('contadorResultados');
        
        if (statTotal) statTotal.textContent = total;
        if (statEspecialidades) statEspecialidades.textContent = especialidadesUnicas.size;
        if (contadorResultados) contadorResultados.textContent = total;
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return String(text).replace(/[&<>"']/g, ch => map[ch]);
    }

    function escapeHtmlAttr(text) {
        const map = { '&': '&amp;', '"': '&quot;', "'": '&#39;', '<': '&lt;', '>': '&gt;' };
        return String(text).replace(/[&"'<>]/g, ch => map[ch]);
    }

    async function editarDoctor(id) {
        try {
            // Cargar datos del doctor desde el servidor
            const url = getRutaOperaciones('doctores') + `?accion=obtener&id=${id}`;
            const resp = await fetch(url, { credentials: 'same-origin' });
            
            if (!resp.ok) {
                throw new Error('No se pudo cargar los datos del doctor');
            }
            
            const doctor = await resp.json();
            
            if (!doctor || !doctor.id) {
                mostrarAlerta('No se encontraron los datos del doctor', 'error');
                return;
            }
            
            // Llenar el formulario con los datos del doctor
            const form = document.getElementById('formDoctor');
            if (!form) return;
            
            // Limpiar formulario primero
            form.reset();
            
            // Llenar campos básicos
            document.getElementById('doctor_id').value = doctor.id;
            document.getElementById('nombre').value = doctor.nombre || '';
            document.getElementById('apellido').value = doctor.apellido || '';
            document.getElementById('cedula').value = doctor.cedula || '';
            document.getElementById('correo').value = doctor.correo || '';
            document.getElementById('telefono').value = doctor.telefono || '';
            document.getElementById('fecha_nacimiento').value = doctor.fecha_nacimiento || '';
            document.getElementById('direccion').value = doctor.direccion || '';
            
            // Cargar especialidades y seleccionar la del doctor
            await cargarEspecialidades();
            const selectEspecialidad = document.getElementById('id_especialidad');
            if (selectEspecialidad && doctor.id_especialidad) {
                selectEspecialidad.value = doctor.id_especialidad;
            }
            
            // Limpiar y cargar horarios
            document.querySelectorAll('.schedule-day input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
                const day = checkbox.closest('.schedule-day');
                const dia = checkbox.value;
                const horaInicio = day.querySelector(`input[name="hora_inicio[${dia}]"]`);
                const horaFin = day.querySelector(`input[name="hora_fin[${dia}]"]`);
                if (horaInicio) {
                    horaInicio.disabled = true;
                    horaInicio.value = '';
                }
                if (horaFin) {
                    horaFin.disabled = true;
                    horaFin.value = '';
                }
            });
            
            // Cargar horarios del doctor si existen
            if (doctor.horarios && Array.isArray(doctor.horarios)) {
                doctor.horarios.forEach(horario => {
                    // Usar el número de día directamente
                    const diaNum = horario.dia || horario.id_dia_semana;
                    
                    const checkbox = document.querySelector(`input[name="dias[]"][value="${diaNum}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                        const day = checkbox.closest('.schedule-day');
                        const horaInicio = day.querySelector(`input[name="hora_inicio[${diaNum}]"]`);
                        const horaFin = day.querySelector(`input[name="hora_fin[${diaNum}]"]`);
                        
                        if (horaInicio && horario.hora_inicio_am) {
                            horaInicio.disabled = false;
                            // Convertir hora TIME a formato HH:MM para input type="time"
                            const horaInicioValue = horario.hora_inicio_am;
                            if (horaInicioValue) {
                                // Si viene en formato "HH:MM:SS", tomar solo "HH:MM"
                                horaInicio.value = horaInicioValue.substring(0, 5);
                            }
                        }
                        
                        if (horaFin && horario.hora_fin_am) {
                            horaFin.disabled = false;
                            const horaFinValue = horario.hora_fin_am;
                            if (horaFinValue) {
                                horaFin.value = horaFinValue.substring(0, 5);
                            }
                        }
                    }
                });
            }
            
            // Cambiar título del modal y texto del botón
            const tituloEl = document.getElementById('modalTitulo');
            if (tituloEl) tituloEl.textContent = 'Editar Doctor';
            
            const btnGuardar = document.getElementById('btnGuardar');
            if (btnGuardar) btnGuardar.textContent = 'Actualizar Doctor';
            
            // Abrir modal
            abrirModalDoctor('Editar Doctor');
            
        } catch (error) {
            console.error('Error al cargar doctor:', error);
            mostrarAlerta('Error al cargar los datos del doctor: ' + error.message, 'error');
        }
    }

    async function eliminarDoctor(id, nombre) {
        if (!confirm(`¿Está seguro de eliminar al doctor "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
            return;
        }
        
        try {
            const url = getRutaOperaciones('doctores') + '?accion=eliminar';
            const formData = new FormData();
            formData.append('id', id);
            
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
                cargarDoctores();
            } else {
                mostrarAlerta(msg, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión: ' + error.message, 'error');
        }
    }

    // Hacer funciones globales para uso en HTML
    window.abrirModalDoctor = abrirModalDoctor;
    window.cerrarModal = cerrarModal;
    window.cerrarAlerta = cerrarAlerta;
    window.editarDoctor = editarDoctor;
    window.eliminarDoctor = eliminarDoctor;

    if (window.AppCore && typeof window.AppCore.registerModule === 'function') {
        console.log('Registrando módulo doctores en AppCore');
        window.AppCore.registerModule('doctores', {
            init() {
                console.log('AppCore módulo doctores: init() llamado');
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