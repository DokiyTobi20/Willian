<?php
// operaciones_citas.php - Refactorizado desde cero
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

require_once __DIR__ . '/../BDD/conexion.php';

header('Content-Type: application/json');

// Utilidad: respuesta JSON y salir
function responder($data) {
	echo json_encode($data);
	exit;
}

// Obtener datos de usuario de la sesión activa y horas ocupadas en lista de espera
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion'])) {
	if ($_GET['accion'] === 'usuario_sesion') {
		$nombre = $_SESSION['nombre'] ?? '';
		$apellido = $_SESSION['apellido'] ?? '';
		$cedula = $_SESSION['cedula'] ?? ($_SESSION['usuario'] ?? '');
		responder([
			'nombre' => $nombre,
			'apellido' => $apellido,
			'cedula' => $cedula
		]);
	} elseif ($_GET['accion'] === 'horas_lista_espera') {
		$hoy = date('Y-m-d');
		$pdo = Conexion::conectar();
		$stmt = $pdo->prepare('SELECT id FROM listas_espera WHERE DATE(fecha_creacion) = ? LIMIT 1');
		$stmt->execute([$hoy]);
		$lista = $stmt->fetch(PDO::FETCH_ASSOC);
		$horas = [];
		if ($lista) {
			$id_lista = $lista['id'];
			$stmt = $pdo->prepare('SELECT numero FROM lista_espera_inscripciones WHERE id_lista = ? ORDER BY numero ASC');
			$stmt->execute([$id_lista]);
			$inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
			foreach ($inscripciones as $insc) {
				$hora = sprintf('%02d:00', 7 + $insc['numero']); // Turno 1 = 8:00, 2 = 9:00, ...
				$horas[] = $hora;
			}
		}
		responder($horas);
	} elseif ($_GET['accion'] === 'inscripciones_lista_espera') {
		$hoy = date('Y-m-d');
		$pdo = Conexion::conectar();
		$stmt = $pdo->prepare('SELECT id FROM listas_espera WHERE DATE(fecha_creacion) = ? LIMIT 1');
		$stmt->execute([$hoy]);
		$lista = $stmt->fetch(PDO::FETCH_ASSOC);
		$result = [];
		if ($lista) {
			$id_lista = $lista['id'];
			$stmt = $pdo->prepare('SELECT le.numero, u.nombre, u.apellido, u.cedula FROM lista_espera_inscripciones le JOIN usuarios u ON le.id_usuario = u.id WHERE le.id_lista = ? ORDER BY le.numero ASC');
			$stmt->execute([$id_lista]);
			$inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
			foreach ($inscripciones as $insc) {
				$hora = sprintf('%02d:00', 7 + $insc['numero']); // Turno 1 = 8:00, 2 = 9:00, ...
				$result[] = [
					'numero' => $insc['numero'],
					'hora' => $hora,
					'paciente' => $insc['nombre'] . ' ' . $insc['apellido'],
					'cedula' => $insc['cedula']
				];
			}
		}
		responder($result);
	} elseif ($_GET['accion'] === 'estadisticas_citas') {
		$hoy = date('Y-m-d');
		$pdo = Conexion::conectar();
		
		// 1. Citas de hoy: cantidad de usuarios en lista de espera de hoy
		$stmt = $pdo->prepare('SELECT id FROM listas_espera WHERE DATE(fecha_creacion) = ? LIMIT 1');
		$stmt->execute([$hoy]);
		$lista = $stmt->fetch(PDO::FETCH_ASSOC);
		$citasHoy = 0;
		if ($lista) {
			$id_lista = $lista['id'];
			$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM lista_espera_inscripciones WHERE id_lista = ?');
			$stmt->execute([$id_lista]);
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			$citasHoy = $result ? (int)$result['total'] : 0;
		}
		
		// 2. Consultas: usuarios de lista de espera de hoy que tienen consulta de hoy
		$consultas = 0;
		if ($lista) {
			$id_lista = $lista['id'];
			$stmt = $pdo->prepare('
				SELECT COUNT(DISTINCT le.id_usuario) as total
				FROM lista_espera_inscripciones le
				INNER JOIN consultas c ON le.id_usuario = c.id_paciente
				WHERE le.id_lista = ? AND DATE(c.fecha_consulta) = ?
			');
			$stmt->execute([$id_lista, $hoy]);
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			$consultas = $result ? (int)$result['total'] : 0;
		}
		
		// 3. Finalizadas: consultas de hoy que tienen diagnóstico, receta y observaciones
		$finalizadas = 0;
		$stmt = $pdo->prepare('
			SELECT COUNT(*) as total
			FROM consultas
			WHERE DATE(fecha_consulta) = ?
			AND diagnostico IS NOT NULL AND diagnostico != ""
			AND receta IS NOT NULL AND receta != ""
			AND observaciones IS NOT NULL AND observaciones != ""
		');
		$stmt->execute([$hoy]);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$finalizadas = $result ? (int)$result['total'] : 0;
		
		responder([
			'citas_hoy' => $citasHoy,
			'consultas' => $consultas,
			'finalizadas' => $finalizadas
		]);
	}
}

			// Registrar en lista de espera
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$data = json_decode(file_get_contents('php://input'), true);
				if (!isset($data['accion'])) {
					responder(['mensaje' => 'Acción no especificada.']);
				}
				if ($data['accion'] === 'registrar_lista_espera') {
					$nombre = $data['nombre'] ?? '';
					$apellido = $data['apellido'] ?? '';
					$cedula = $data['cedula'] ?? '';
					$hora_registro = $data['hora_registro'] ?? '';
					$hora_disponible = $data['hora_disponible'] ?? '';
					if (!$nombre || !$apellido || !$cedula || !$hora_registro) {
						responder(['mensaje' => 'Datos incompletos para registrar en lista de espera.']);
					}
					$pdo = Conexion::conectar();
					// Buscar usuario por cédula
					$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE cedula = ? LIMIT 1');
					$stmt->execute([$cedula]);
					$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
					if (!$usuario) {
						responder(['mensaje' => 'Usuario no encontrado.']);
					}
					$id_usuario = $usuario['id'];
					// Buscar o crear lista_espera para hoy
					$hoy = date('Y-m-d');
					$stmt = $pdo->prepare('SELECT id FROM listas_espera WHERE DATE(fecha_creacion) = ? LIMIT 1');
					$stmt->execute([$hoy]);
					$lista = $stmt->fetch(PDO::FETCH_ASSOC);
					if (!$lista) {
						$pdo->prepare('INSERT INTO listas_espera (fecha_creacion) VALUES (NOW())')->execute();
						$id_lista = $pdo->lastInsertId();
					} else {
						$id_lista = $lista['id'];
					}
					// Calcular el siguiente número de orden
					// Verificar si el usuario ya está inscrito hoy
					$stmt = $pdo->prepare('SELECT numero FROM lista_espera_inscripciones WHERE id_lista = ? AND id_usuario = ? LIMIT 1');
					$stmt->execute([$id_lista, $id_usuario]);
					$inscrito = $stmt->fetch(PDO::FETCH_ASSOC);
					if ($inscrito) {
						$numero = $inscrito['numero'];
						responder([
							'mensaje' => "Ya estás inscrito en la lista de espera. Turno: $numero, Hora: $hora_disponible"
						]);
					} else {
						$stmt = $pdo->prepare('SELECT MAX(numero) as max_num FROM lista_espera_inscripciones WHERE id_lista = ?');
						$stmt->execute([$id_lista]);
						$max = $stmt->fetch(PDO::FETCH_ASSOC);
						$numero = ($max && $max['max_num']) ? ($max['max_num'] + 1) : 1;
						// Registrar inscripción
						try {
							try {
								$stmt = $pdo->prepare('INSERT INTO lista_espera_inscripciones (id_lista, id_usuario, numero) VALUES (?, ?, ?)');
								$stmt->execute([$id_lista, $id_usuario, $numero]);
								responder([
									'mensaje' => "Registrado en lista de espera. Turno: $numero, Hora: $hora_disponible"
								]);
							} catch (PDOException $e) {
								responder([
									'mensaje' => 'Error al registrar inscripción: ' . $e->getMessage()
								]);
							}
						} catch (PDOException $e) {
							responder([
								'mensaje' => 'Error al registrar inscripción: ' . $e->getMessage()
							]);
						}
					}
				}

			}
			// Si no coincide ninguna acción
			responder(['mensaje' => 'Acción no válida o método incorrecto.']);
