// Encapsulado para evitar conflictos globales
(function() {
	let modalAgendar = null;
	let modalListaEspera = null;
	let handleWindowClick = null;

	function boot() {
		inicializarModales();
		inicializarEventos();
		mostrarListaEspera();
		actualizarEstadisticas();
	}

	// Actualizar estadísticas
	async function actualizarEstadisticas() {
		try {
			const resp = await fetch('../citas/operaciones_citas.php?accion=estadisticas_citas');
			if (resp.ok) {
				const stats = await resp.json();
				const statCitasHoy = document.getElementById('statCitasHoy');
				const statConsultas = document.getElementById('statConsultas');
				const statFinalizadas = document.getElementById('statFinalizadas');
				
				if (statCitasHoy) {
					statCitasHoy.textContent = stats.citas_hoy || 0;
				}
				if (statConsultas) {
					statConsultas.textContent = stats.consultas || 0;
				}
				if (statFinalizadas) {
					statFinalizadas.textContent = stats.finalizadas || 0;
				}
			}
		} catch (error) {
			console.error('Error al cargar estadísticas:', error);
		}
	}

	// Variable para almacenar todas las inscripciones
	let todasLasInscripciones = [];

	// Mostrar la lista de espera en la tabla
	async function mostrarListaEspera() {
		const tabla = document.getElementById('tablaListaEspera');
		if (!tabla) return;
		const tbody = tabla.querySelector('tbody');
		tbody.innerHTML = '<tr><td colspan="4">Cargando...</td></tr>';
		try {
			// Obtener inscripciones de la lista de espera de hoy
			const resp = await fetch('../citas/operaciones_citas.php?accion=inscripciones_lista_espera');
			if (resp.ok) {
				const inscripciones = await resp.json();
				todasLasInscripciones = Array.isArray(inscripciones) ? inscripciones : [];
				filtrarListaEspera();
			} else {
				tbody.innerHTML = '<tr><td colspan="4">Error al cargar la lista de espera.</td></tr>';
			}
		} catch {
			tbody.innerHTML = '<tr><td colspan="4">Error al cargar la lista de espera.</td></tr>';
		}
		// Actualizar estadísticas después de cargar la lista
		actualizarEstadisticas();
	}

	// Filtrar lista de espera por búsqueda
	function filtrarListaEspera() {
		const tabla = document.getElementById('tablaListaEspera');
		if (!tabla) return;
		const tbody = tabla.querySelector('tbody');
		const busquedaInput = document.getElementById('busqueda');
		const termino = busquedaInput ? busquedaInput.value.trim().toLowerCase() : '';
		
		if (todasLasInscripciones.length === 0) {
			tbody.innerHTML = '<tr><td colspan="4">No hay inscripciones en la lista de espera hoy.</td></tr>';
			return;
		}
		
		// Filtrar por nombre o cédula del paciente
		const inscripcionesFiltradas = todasLasInscripciones.filter(item => {
			if (!termino) return true;
			const nombreCompleto = (item.paciente || '').toLowerCase();
			const cedula = (item.cedula || '').toLowerCase();
			return nombreCompleto.includes(termino) || cedula.includes(termino);
		});
		
		if (inscripcionesFiltradas.length > 0) {
			tbody.innerHTML = '';
			inscripcionesFiltradas.forEach(item => {
				tbody.innerHTML += `<tr><td>${item.numero}</td><td>${item.hora}</td><td>${item.paciente}</td><td>${item.cedula}</td></tr>`;
			});
		} else {
			if (termino) {
				tbody.innerHTML = `<tr><td colspan="4">No se encontraron pacientes que coincidan con "${termino}"</td></tr>`;
			} else {
				tbody.innerHTML = '<tr><td colspan="4">No hay inscripciones en la lista de espera hoy.</td></tr>';
			}
		}
	}

	function inicializarModales() {
		modalAgendar = document.getElementById('modalAgendar');
		modalListaEspera = document.getElementById('modalListaEspera');

		document.querySelectorAll('.close').forEach(close => {
			close.addEventListener('click', cerrarModal);
		});

		handleWindowClick = function(event) {
			if (event.target === modalAgendar || event.target === modalListaEspera) {
				cerrarModal();
			}
		};
		window.addEventListener('click', handleWindowClick);
	}

	function inicializarEventos() {
		// Búsqueda en lista de espera
		const busquedaInput = document.getElementById('busqueda');
		if (busquedaInput) {
			busquedaInput.addEventListener('input', function() {
				filtrarListaEspera();
			});
		}

		const btnAbrirAgendar = document.getElementById('btnAbrirAgendar');
		if (btnAbrirAgendar) {
			btnAbrirAgendar.addEventListener('click', abrirModalAgendarCita);
		}

		const btnListaEspera = document.getElementById('btnListaEspera');
		if (btnListaEspera) {
			btnListaEspera.addEventListener('click', async function() {
				// Obtener datos de usuario de la sesión activa
				let usuario = null;
				try {
					const resp = await fetch('../citas/operaciones_citas.php?accion=usuario_sesion');
					if (resp.ok) {
						usuario = await resp.json();
					}
				} catch (e) {
					console.error('Error al obtener datos de sesión:', e);
				}
				console.log('Datos de usuario de sesión:', usuario);
				if (!usuario || !usuario.nombre || !usuario.apellido || !usuario.cedula) {
					alert('No se pudo obtener los datos de la sesión.');
					return;
				}
				// Obtener la siguiente hora disponible desde las 8am en saltos de 1 hora
				let horaDisponible = await obtenerSiguienteHoraDisponible();
				if (!horaDisponible) {
					alert('No hay más turnos disponibles hoy.');
					return;
				}
				fetch('../citas/operaciones_citas.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({
						accion: 'registrar_lista_espera',
						nombre: usuario.nombre,
						apellido: usuario.apellido,
						cedula: usuario.cedula,
						hora_registro: horaDisponible,
						hora_disponible: horaDisponible
					})
				})
				.then(res => res.json())
				.then(data => {
					alert(data.mensaje || 'Registrado en lista de espera para las ' + horaDisponible);
					mostrarListaEspera();
					actualizarEstadisticas();
				})
				.catch(() => {
					alert('Error al registrar en lista de espera');
				});
			});
		}

		// Función para obtener la siguiente hora disponible desde las 8am en saltos de 1 hora
		async function obtenerSiguienteHoraDisponible() {
			// Aquí deberías consultar al backend las horas ya ocupadas, pero para ejemplo local:
			// Simula obtener la lista de horas ocupadas (debería venir de la BD)
			let horasOcupadas = [];
			try {
				const resp = await fetch('../citas/operaciones_citas.php?accion=horas_lista_espera');
				if (resp.ok) {
					const data = await resp.json();
					// Si el backend devuelve un objeto, conviértelo en array
					if (Array.isArray(data)) {
						horasOcupadas = data;
					} else if (data && typeof data === 'object') {
						horasOcupadas = Object.values(data);
					}
				}
			} catch {}
			// Generar horas desde 08:00 hasta 18:00 (10 turnos)
			for (let h = 8; h <= 18; h++) {
				let hora = (h < 10 ? '0' : '') + h + ':00';
				if (!horasOcupadas.includes(hora)) {
					return hora;
				}
			}
			return null;
		}

		document.querySelectorAll('.btn-secondary').forEach(btn => {
			btn.addEventListener('click', cerrarModal);
		});

		const horaRegistro = document.getElementById('hora_registro');
		const horaDisponible = document.getElementById('hora_disponible');
		if (horaRegistro && horaDisponible) {
			horaRegistro.addEventListener('input', function() {
				if (horaRegistro.value) {
					let [h, m] = horaRegistro.value.split(':').map(Number);
					h = h + 1;
					if (h < 10) h = '0' + h;
					horaDisponible.value = h + ':' + m.toString().padStart(2, '0');
				} else {
					horaDisponible.value = '';
				}
			});
		}

		const formListaEspera = document.getElementById('formListaEspera');
		if (formListaEspera) {
			formListaEspera.onsubmit = function(e) {
				e.preventDefault();
				const usuario = document.getElementById('usuario_espera').value;
				const hora_registro = document.getElementById('hora_registro').value;
				const hora_disponible = document.getElementById('hora_disponible').value;
				fetch('../citas/operaciones_citas.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({
						accion: 'registrar_lista_espera',
						usuario,
						hora_registro,
						hora_disponible
					})
				})
				.then(res => res.json())
				.then(data => {
					alert(data.mensaje || 'Registrado en lista de espera');
					cerrarModal();
				})
				.catch(() => {
					alert('Error al registrar en lista de espera');
				});
			};
		}
	}

	function abrirModalAgendarCita() {
		if (!modalAgendar) {
			modalAgendar = document.getElementById('modalAgendar');
		}
		if (!modalAgendar) return;
		modalAgendar.style.display = 'block';
		document.body.style.overflow = 'hidden';
		setTimeout(() => {
			const input = modalAgendar.querySelector('input, select');
			if (input) input.focus();
		}, 100);
	}

	function abrirModalListaEspera() {
		// Eliminado: ya no se usa modal para lista de espera
	}

	function cerrarModal() {
		let cerrado = false;
		if (modalAgendar && modalAgendar.style.display !== 'none') {
			modalAgendar.style.display = 'none';
			cerrado = true;
		}
		if (modalListaEspera && modalListaEspera.style.display !== 'none') {
			modalListaEspera.style.display = 'none';
			cerrado = true;
		}
		if (cerrado) {
			document.body.style.overflow = 'auto';
		}
	}

	// Hacer funciones globales para uso en HTML si es necesario
	window.abrirModalAgendarCita = abrirModalAgendarCita;
	window.abrirModalListaEspera = abrirModalListaEspera;
	window.cerrarModal = cerrarModal;

	if (window.AppCore && typeof window.AppCore.registerModule === 'function') {
		window.AppCore.registerModule('citas', {
			init() {
				boot();
			},
			destroy() {
				if (handleWindowClick) {
					window.removeEventListener('click', handleWindowClick);
					handleWindowClick = null;
				}
				modalAgendar = null;
				modalListaEspera = null;
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