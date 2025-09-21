<?php
require_once __DIR__ . '/../BDD/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class DoctoresRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    private function isAdmin(): bool {
        return isset($_SESSION['rol']) && $_SESSION['rol'] == 3; // 3 = Administrador
    }

    public function listar(): array {
        $sql = "SELECT d.*, e.nombre AS especialidad 
                FROM doctores d
                LEFT JOIN especialidades e ON d.id_especialidad = e.id
                ORDER BY d.apellido, d.nombre";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM doctores WHERE id = ?");
        $stmt->execute([$id]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doctor) return null;

        // obtener horarios
        $stmt = $this->pdo->prepare("SELECT * FROM horarios_doctores WHERE id_doctor = ?");
        $stmt->execute([$id]);
        $doctor['horarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $doctor;
    }

    public function crear(array $data): array {
        if (!$this->isAdmin()) {
            return ['status' => 'error', 'message' => 'Acceso no autorizado.'];
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                INSERT INTO doctores (nombre, apellido, cedula, correo, telefono, fecha_nacimiento, id_especialidad, direccion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['nombre'],
                $data['apellido'],
                $data['cedula'],
                $data['correo'],
                $data['telefono'] ?? null,
                $data['fecha_nacimiento'] ?? null,
                $data['id_especialidad'],
                $data['direccion'] ?? null
            ]);

            $doctorId = $this->pdo->lastInsertId();

            // Insertar horarios si existen
            if (!empty($data['horarios'])) {
                $stmtHorario = $this->pdo->prepare("
                    INSERT INTO horarios_doctores (id_doctor, dia, hora_inicio_am, hora_fin_am, hora_inicio_pm, hora_fin_pm)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                foreach ($data['horarios'] as $h) {
                    $stmtHorario->execute([
                        $doctorId, $h['dia'],
                        $h['hora_inicio_am'] ?? null,
                        $h['hora_fin_am'] ?? null,
                        $h['hora_inicio_pm'] ?? null,
                        $h['hora_fin_pm'] ?? null
                    ]);
                }
            }

            $this->pdo->commit();
            return ['status' => 'success', 'message' => 'Doctor creado con éxito.', 'id' => $doctorId];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => 'Error en la BD: ' . $e->getMessage()];
        }
    }

    public function editar(int $id, array $data): array {
        if (!$this->isAdmin()) {
            return ['status' => 'error', 'message' => 'Acceso no autorizado.'];
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                UPDATE doctores 
                SET nombre=?, apellido=?, cedula=?, correo=?, telefono=?, fecha_nacimiento=?, id_especialidad=?, direccion=?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['nombre'], $data['apellido'], $data['cedula'], $data['correo'],
                $data['telefono'] ?? null, $data['fecha_nacimiento'] ?? null,
                $data['id_especialidad'], $data['direccion'] ?? null, $id
            ]);

            // limpiar horarios y volver a insertar
            $this->pdo->prepare("DELETE FROM horarios_doctores WHERE id_doctor = ?")->execute([$id]);

            if (!empty($data['horarios'])) {
                $stmtHorario = $this->pdo->prepare("
                    INSERT INTO horarios_doctores (id_doctor, dia, hora_inicio_am, hora_fin_am, hora_inicio_pm, hora_fin_pm)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                foreach ($data['horarios'] as $h) {
                    $stmtHorario->execute([
                        $id, $h['dia'],
                        $h['hora_inicio_am'] ?? null,
                        $h['hora_fin_am'] ?? null,
                        $h['hora_inicio_pm'] ?? null,
                        $h['hora_fin_pm'] ?? null
                    ]);
                }
            }

            $this->pdo->commit();
            return ['status' => 'success', 'message' => 'Doctor actualizado con éxito.'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => 'Error en la BD: ' . $e->getMessage()];
        }
    }

    public function eliminar(int $id): array {
        if (!$this->isAdmin()) {
            return ['status' => 'error', 'message' => 'Acceso no autorizado.'];
        }

        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare("DELETE FROM horarios_doctores WHERE id_doctor = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM doctores WHERE id = ?")->execute([$id]);
            $this->pdo->commit();

            return ['status' => 'success', 'message' => 'Doctor eliminado con éxito.'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => 'Error en la BD: ' . $e->getMessage()];
        }
    }

    public function obtenerEstadisticas(): array {
        $total = $this->pdo->query("SELECT COUNT(*) FROM doctores")->fetchColumn();
        $especialidades = $this->pdo->query("SELECT COUNT(DISTINCT id_especialidad) FROM doctores")->fetchColumn();

        return [
            'total' => $total,
            'especialidades' => $especialidades
        ];
    }
}

header('Content-Type: application/json');
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = Conexion::conectar();
    $repo = new DoctoresRepository($pdo);
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
                $data = $_POST;
                // Decodificar horarios si vienen como JSON
                if (isset($_POST['horarios'])) {
                    $data['horarios'] = json_decode($_POST['horarios'], true);
                } else {
                    $data['horarios'] = [];
                }
                $response = $repo->crear($data);
            } else {
                $response = ['status' => 'error', 'message' => 'Método no permitido'];
            }
            break;
        case 'editar':
            if ($metodo === 'POST') {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $data = $_POST;
                if (isset($_POST['horarios'])) {
                    $data['horarios'] = json_decode($_POST['horarios'], true);
                } else {
                    $data['horarios'] = [];
                }
                $response = $id ? $repo->editar($id, $data) : ['status' => 'error', 'message' => 'ID inválido'];
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
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la BD: ' . $e->getMessage()]);
}