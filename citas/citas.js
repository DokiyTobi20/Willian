// Encapsulado para evitar conflictos globales
(function() {
	let modalAgendar = null;
	let modalListaEspera = null;
	let handleWindowClick = null;

	function boot() {
		inicializarModales();
		inicializarEventos();
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
		const btnAbrirAgendar = document.getElementById('btnAbrirAgendar');
		if (btnAbrirAgendar) {
			btnAbrirAgendar.addEventListener('click', abrirModalAgendarCita);
		}

		let btnAbrirListaEspera = document.getElementById('btnAbrirListaEspera');
		if (!btnAbrirListaEspera) {
			btnAbrirListaEspera = document.createElement('button');
			btnAbrirListaEspera.className = 'btn-primary';
			btnAbrirListaEspera.innerHTML = "<i class='bx bx-time-five'></i> Registrarme en lista de espera";
			btnAbrirListaEspera.id = 'btnAbrirListaEspera';
			document.querySelector('.controls').appendChild(btnAbrirListaEspera);
		}
		btnAbrirListaEspera.addEventListener('click', abrirModalListaEspera);

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
				fetch('operaciones_citas.php', {
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
		if (!modalListaEspera) {
			modalListaEspera = document.getElementById('modalListaEspera');
		}
		if (!modalListaEspera) return;
		modalListaEspera.style.display = 'block';
		document.body.style.overflow = 'hidden';
		setTimeout(() => {
			const input = modalListaEspera.querySelector('input');
			if (input) input.focus();
		}, 100);
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