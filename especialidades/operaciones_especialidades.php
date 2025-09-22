<?php

require_once __DIR__ . '/../BDD/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class EspecialidadesRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function isAdmin(): bool
    {
        return isset($_SESSION['rol']) && $_SESSION['rol'] == 3; // 3 = Administrador
    }

    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT id, nombre FROM especialidades ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM especialidades WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function crear(string $nombre): array
    {
        if (!$this->isAdmin()) {
            return ['status' => 'error', 'message' => 'Acceso no autorizado.'];
        }
        if (empty(trim($nombre))) {
            return ['status' => 'error', 'message' => 'El nombre no puede estar vacío.'];
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO especialidades (nombre) VALUES (?)");
            if ($stmt->execute([$nombre])) {
                return ['status' => 'success', 'message' => 'Especialidad creada con éxito.', 'id' => $this->pdo->lastInsertId()];
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Error de entrada duplicada
                return ['status' => 'error', 'message' => 'La especialidad ya existe.'];
            }
            return ['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
        return ['status' => 'error', 'message' => 'No se pudo crear la especialidad.'];
    }

    public function editar(int $id, string $nombre): array
    {
        if (!$this->isAdmin()) {
            return ['status' => 'error', 'message' => 'Acceso no autorizado.'];
        }
        if (empty(trim($nombre))) {
            return ['status' => 'error', 'message' => 'El nombre no puede estar vacío.'];
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE especialidades SET nombre = ? WHERE id = ?");
            if ($stmt->execute([$nombre, $id])) {
                return ['status' => 'success', 'message' => 'Especialidad actualizada con éxito.'];
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                return ['status' => 'error', 'message' => 'Ese nombre de especialidad ya está en uso.'];
            }
            return ['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
        return ['status' => 'error', 'message' => 'No se pudo actualizar la especialidad.'];
    }

    public function eliminar(int $id): array
    {
        if (!$this->isAdmin()) {
            return ['status' => 'error', 'message' => 'Acceso no autorizado.'];
        }

        // Verificar si la especialidad está en uso
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM doctores WHERE id_especialidad = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return ['status' => 'error', 'message' => 'No se puede eliminar. La especialidad está asignada a uno or más doctores.'];
        }

        $stmt = $this->pdo->prepare("DELETE FROM especialidades WHERE id = ?");
        if ($stmt->execute([$id])) {
            return ['status' => 'success', 'message' => 'Especialidad eliminada con éxito.'];
        }

        return ['status' => 'error', 'message' => 'No se pudo eliminar la especialidad.'];
    }

    public function obtenerEstadisticas(): array
    {
        $total = $this->pdo->query("SELECT COUNT(*) FROM especialidades")->fetchColumn();
        $conDoctores = $this->pdo->query("SELECT COUNT(DISTINCT id_especialidad) FROM doctores")->fetchColumn();
        $sinDoctores = $total - $conDoctores;

        return [
            'total' => $total,
            'con_doctores' => $conDoctores,
            'sin_doctores' => $sinDoctores
        ];
    }
}

header('Content-Type: application/json');
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = Conexion::conectar();
    $repo = new EspecialidadesRepository($pdo);
    $accion = $_REQUEST['accion'] ?? null;
    $response = [];

    switch ($accion) {
        case 'listar':
            $response = $repo->listar();
            break;
        case 'obtener':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $response = $id ? $repo->obtenerPorId($id) : ['error' => 'ID inválido'];
            break;
        case 'crear':
            if ($metodo === 'POST') {
                $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
                $response = $repo->crear($nombre);
            } else {
                $response = ['status' => 'error', 'message' => 'Método no permitido'];
            }
            break;
        case 'editar':
            if ($metodo === 'POST') {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
                $response = ($id && $nombre) ? $repo->editar($id, $nombre) : ['status' => 'error', 'message' => 'Datos incompletos'];
            } else {
                $response = ['status' => 'error', 'message' => 'Método no permitido'];
            }
            break;
        case 'eliminar':
            if ($metodo === 'POST') {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $response = $id ? $repo->eliminar($id) : ['status' => 'error', 'message' => 'ID inválido'];
            } else {
                $response = ['status' => 'error', 'message' => 'Método no permitido'];
            }
            break;
        case 'estadisticas':
            $response = $repo->obtenerEstadisticas();
            break;
        default:
            http_response_code(400);
            $response = ['status' => 'error', 'message' => 'Acción no válida'];
            break;
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
}