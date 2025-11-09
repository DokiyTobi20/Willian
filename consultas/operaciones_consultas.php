<?php
require_once __DIR__ . '/../BDD/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

class ConsultasRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    private function isAdmin(): bool {
        return isset($_SESSION['rol']) && $_SESSION['rol'] == 3; // 3 = Administrador
    }

    private function isDoctor(): bool {
        return isset($_SESSION['rol']) && $_SESSION['rol'] == 2; // 2 = Doctor
    }

    private function isAdminOrDoctor(): bool {
        return $this->isAdmin() || $this->isDoctor();
    }

    public function listar(): array {
        $sql = "SELECT c.id, c.id_paciente, c.id_doctor, c.fecha_consulta, 
                       c.diagnostico, c.receta, c.observaciones, c.fecha_creacion,
                       u.nombre AS paciente_nombre, u.apellido AS paciente_apellido, u.cedula AS paciente_cedula,
                       d.id AS doctor_id, du.nombre AS doctor_nombre, du.apellido AS doctor_apellido,
                       e.nombre AS especialidad
                FROM consultas c
                INNER JOIN usuarios u ON c.id_paciente = u.id
                INNER JOIN doctores d ON c.id_doctor = d.id
                INNER JOIN usuarios du ON d.id = du.id
                LEFT JOIN especialidades e ON d.id_especialidad = e.id
                ORDER BY c.fecha_consulta DESC, c.fecha_creacion DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.id_paciente, c.id_doctor, c.fecha_consulta, 
                   c.diagnostico, c.receta, c.observaciones,
                   u.nombre AS paciente_nombre, u.apellido AS paciente_apellido, u.cedula AS paciente_cedula,
                   d.id AS doctor_id, du.nombre AS doctor_nombre, du.apellido AS doctor_apellido
            FROM consultas c
            INNER JOIN usuarios u ON c.id_paciente = u.id
            INNER JOIN doctores d ON c.id_doctor = d.id
            INNER JOIN usuarios du ON d.id = du.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $consulta = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($consulta && isset($consulta['doctor_id']) && !isset($consulta['id_doctor'])) {
            $consulta['id_doctor'] = $consulta['doctor_id'];
        }
        return $consulta ?: null;
    }

    public function crear(array $data): array {
        if (!$this->isAdminOrDoctor()) {
            return ['status' => 'error', 'message' => 'Acceso no autorizado.'];
        }

        try {
            $this->pdo->beginTransaction();

            // Obtener ID del doctor de la sesión (el usuario logueado)
            $id_doctor = $_SESSION['id'] ?? null;
            if (!$id_doctor) {
                throw new Exception('No se pudo obtener el ID del doctor de la sesión');
            }

            // Verificar que el usuario logueado es un doctor
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM doctores WHERE id = ?");
            $stmt->execute([$id_doctor]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('El usuario logueado no es un doctor');
            }

            // Validar campos requeridos
            if (empty($data['id_usuario']) || empty($data['diagnostico'])) {
                throw new Exception('Faltan campos requeridos: id_usuario, diagnostico');
            }

            // Verificar que el usuario (paciente) existe
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ?");
            $stmt->execute([$data['id_usuario']]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('El usuario seleccionado no existe');
            }

            // Insertar consulta con fecha/hora actual automática
            $stmt = $this->pdo->prepare("
                INSERT INTO consultas (id_paciente, id_doctor, fecha_consulta, diagnostico, receta, observaciones)
                VALUES (?, ?, NOW(), ?, ?, ?)
            ");
            $stmt->execute([
                $data['id_usuario'],
                $id_doctor,
                $data['diagnostico'],
                $data['receta'] ?? null,
                $data['observaciones'] ?? null
            ]);

            $idConsulta = $this->pdo->lastInsertId();
            $this->pdo->commit();

            return ['status' => 'success', 'message' => 'Consulta creada con éxito.', 'id' => $idConsulta];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => 'Error en la BD: ' . $e->getMessage()];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function editar(array $data): array {
        if (!$this->isAdminOrDoctor()) {
            return ['status' => 'error', 'message' => 'Acceso no autorizado.'];
        }

        try {
            $this->pdo->beginTransaction();

            if (empty($data['id'])) {
                throw new Exception('ID de consulta no proporcionado');
            }

            // Validar campos requeridos
            if (empty($data['id_usuario']) || empty($data['id_doctor']) || empty($data['fecha_cita']) || empty($data['diagnostico'])) {
                throw new Exception('Faltan campos requeridos: id_usuario, id_doctor, fecha_cita, diagnostico');
            }

            // Verificar que la consulta existe
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM consultas WHERE id = ?");
            $stmt->execute([$data['id']]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('La consulta no existe');
            }

            // Actualizar consulta
            $stmt = $this->pdo->prepare("
                UPDATE consultas 
                SET id_paciente = ?, id_doctor = ?, fecha_consulta = ?, 
                    diagnostico = ?, receta = ?, observaciones = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['id_usuario'],
                $data['id_doctor'],
                $data['fecha_cita'],
                $data['diagnostico'],
                $data['receta'] ?? null,
                $data['observaciones'] ?? null,
                $data['id']
            ]);

            $this->pdo->commit();
            return ['status' => 'success', 'message' => 'Consulta actualizada con éxito.'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => 'Error en la BD: ' . $e->getMessage()];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function eliminar(int $id): array {
        if (!$this->isAdminOrDoctor()) {
            return ['status' => 'error', 'message' => 'Acceso no autorizado.'];
        }

        try {
            $this->pdo->beginTransaction();

            // Verificar que la consulta existe
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM consultas WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('La consulta no existe');
            }

            // Eliminar consulta
            $stmt = $this->pdo->prepare("DELETE FROM consultas WHERE id = ?");
            $stmt->execute([$id]);

            $this->pdo->commit();
            return ['status' => 'success', 'message' => 'Consulta eliminada con éxito.'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => 'Error en la BD: ' . $e->getMessage()];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function buscarUsuarios(string $query): array {
        if (strlen($query) < 2) {
            return [];
        }
        $searchTerm = '%' . $query . '%';
        $stmt = $this->pdo->prepare("
            SELECT id, nombre, apellido, cedula, correo, telefono
            FROM usuarios
            WHERE nombre LIKE ? OR apellido LIKE ? OR cedula LIKE ?
            ORDER BY apellido, nombre
            LIMIT 10
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Procesar peticiones
try {
    $pdo = Conexion::conectar();
    $repository = new ConsultasRepository($pdo);
    $accion = $_REQUEST['accion'] ?? null;
    $response = [];

    switch ($accion) {
        case 'listar':
            $response = $repository->listar();
            break;

        case 'obtener':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id > 0) {
                $consulta = $repository->obtenerPorId($id);
                $response = $consulta ?: ['status' => 'error', 'message' => 'Consulta no encontrada'];
            } else {
                $response = ['status' => 'error', 'message' => 'ID no válido'];
            }
            break;

        case 'crear':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'id_usuario' => $_POST['id_usuario'] ?? null,
                    'diagnostico' => $_POST['diagnostico'] ?? null,
                    'receta' => $_POST['receta'] ?? null,
                    'observaciones' => $_POST['observaciones'] ?? null
                ];
                $response = $repository->crear($data);
            } else {
                $response = ['status' => 'error', 'message' => 'Método no permitido'];
            }
            break;

        case 'editar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'id' => $_POST['id'] ?? null,
                    'id_usuario' => $_POST['id_usuario'] ?? null,
                    'id_doctor' => $_POST['id_doctor'] ?? null,
                    'fecha_cita' => $_POST['fecha_cita'] ?? null,
                    'diagnostico' => $_POST['diagnostico'] ?? null,
                    'receta' => $_POST['receta'] ?? $_POST['medicacion'] ?? null,
                    'observaciones' => $_POST['observaciones'] ?? null
                ];
                $response = $repository->editar($data);
            } else {
                $response = ['status' => 'error', 'message' => 'Método no permitido'];
            }
            break;

        case 'eliminar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                if ($id > 0) {
                    $response = $repository->eliminar($id);
                } else {
                    $response = ['status' => 'error', 'message' => 'ID no válido'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'Método no permitido'];
            }
            break;

        case 'buscar_usuarios':
            $query = $_GET['q'] ?? '';
            $response = $repository->buscarUsuarios($query);
            break;

        default:
            http_response_code(400);
            $response = ['status' => 'error', 'message' => 'Acción no válida'];
            break;
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
