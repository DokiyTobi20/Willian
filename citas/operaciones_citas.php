if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$data = json_decode(file_get_contents('php://input'), true);
	if (isset($data['accion']) && $data['accion'] === 'registrar_lista_espera') {
		$usuario = $data['usuario'];
		$hora_registro = $data['hora_registro'];
		$hora_disponible = $data['hora_disponible'];

		// Aquí podrías guardar en la base de datos, por ejemplo:
		// require_once '../BDD/conexion.php';
		// $conn = conectar();
		// $sql = "INSERT INTO lista_espera (usuario, hora_registro, hora_disponible) VALUES (?, ?, ?)";
		// $stmt = $conn->prepare($sql);
		// $stmt->bind_param('sss', $usuario, $hora_registro, $hora_disponible);
		// $stmt->execute();
		// $stmt->close();
		// $conn->close();

		// Respuesta de ejemplo
		echo json_encode([
			'mensaje' => "Usuario $usuario registrado en lista de espera. Próxima disponibilidad: $hora_disponible"
		]);
		exit;
	}
}
