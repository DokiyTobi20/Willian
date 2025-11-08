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
        $sql = "SELECT d.id, d.id_especialidad, 
                       u.nombre, u.apellido, u.cedula, u.correo, u.telefono, 
                       u.fecha_nacimiento, u.direccion,
                       e.nombre AS especialidad 
                FROM doctores d
                INNER JOIN usuarios u ON d.id = u.id
                LEFT JOIN especialidades e ON d.id_especialidad = e.id
                ORDER BY u.apellido, u.nombre";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT d.id, d.id_especialidad, 
                   u.nombre, u.apellido, u.cedula, u.correo, u.telefono, 
                   u.fecha_nacimiento, u.direccion
            FROM doctores d
            INNER JOIN usuarios u ON d.id = u.id
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doctor) return null;

        // obtener horarios con JOIN para obtener las horas reales y el número de día
        $stmt = $this->pdo->prepare("
            SELECT hd.id, hd.id_doctor, hd.id_dia_semana, hd.id_hora_inicio, hd.id_hora_fin,
                   ds.numero_dia as dia,
                   h1.hora as hora_inicio_am,
                   h2.hora as hora_fin_am
            FROM horarios_doctor hd
            INNER JOIN dias_semana ds ON hd.id_dia_semana = ds.id
            INNER JOIN horas h1 ON hd.id_hora_inicio = h1.id
            INNER JOIN horas h2 ON hd.id_hora_fin = h2.id
            WHERE hd.id_doctor = ?
        ");
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

            // Verificar que la cédula no exista
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE cedula = ?");
            $stmt->execute([$data['cedula']]);
            if ($stmt->fetchColumn() > 0) {
                $this->pdo->rollBack();
                return ['status' => 'error', 'message' => 'La cédula ya está registrada.'];
            }

            // Verificar que el correo no exista
            if (!empty($data['correo'])) {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE correo = ?");
                $stmt->execute([$data['correo']]);
                if ($stmt->fetchColumn() > 0) {
                    $this->pdo->rollBack();
                    return ['status' => 'error', 'message' => 'El correo electrónico ya está registrado.'];
                }
            }

            // Generar usuario y clave por defecto
            $baseUsuario = strtolower(substr($data['nombre'], 0, 1) . $data['apellido']) . '_' . substr($data['cedula'], -4);
            $usuario = $baseUsuario;
            $contador = 1;
            
            // Asegurar que el usuario sea único
            while (true) {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = ?");
                $stmt->execute([$usuario]);
                if ($stmt->fetchColumn() == 0) {
                    break; // Usuario único encontrado
                }
                $usuario = $baseUsuario . $contador;
                $contador++;
            }
            
            $clave_hash = password_hash($data['cedula'], PASSWORD_BCRYPT); // Clave por defecto: cédula

            // Crear usuario primero
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios (id_rol, usuario, clave, nombre, apellido, cedula, correo, telefono, fecha_nacimiento, direccion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                2, // id_rol = 2 (Doctor)
                $usuario,
                $clave_hash,
                $data['nombre'],
                $data['apellido'],
                $data['cedula'],
                $data['correo'] ?? null,
                $data['telefono'] ?? null,
                $data['fecha_nacimiento'] ?? null,
                $data['direccion'] ?? null
            ]);

            $userId = $this->pdo->lastInsertId();

            // Crear doctor
            $stmt = $this->pdo->prepare("
                INSERT INTO doctores (id, id_especialidad)
                VALUES (?, ?)
            ");
            $stmt->execute([
                $userId,
                $data['id_especialidad']
            ]);

            // Insertar horarios si existen
            if (!empty($data['horarios'])) {
                foreach ($data['horarios'] as $h) {
                    // Convertir número de día a id_dia_semana
                    $dia = intval($h['dia']);
                    $stmtDia = $this->pdo->prepare("SELECT id FROM dias_semana WHERE numero_dia = ?");
                    $stmtDia->execute([$dia]);
                    $idDiaSemana = $stmtDia->fetchColumn();
                    
                    if ($idDiaSemana && $h['hora_inicio_am'] && $h['hora_fin_am']) {
                        $horaInicio = $h['hora_inicio_am'];
                        $horaFin = $h['hora_fin_am'];
                        
                        // Obtener o crear hora de inicio
                        $stmtHora = $this->pdo->prepare("SELECT id FROM horas WHERE hora = ?");
                        $stmtHora->execute([$horaInicio]);
                        $idHoraInicio = $stmtHora->fetchColumn();
                        
                        if (!$idHoraInicio) {
                            $stmtInsertHora = $this->pdo->prepare("INSERT INTO horas (hora) VALUES (?)");
                            $stmtInsertHora->execute([$horaInicio]);
                            $idHoraInicio = $this->pdo->lastInsertId();
                        }
                        
                        // Obtener o crear hora de fin
                        $stmtHoraFin = $this->pdo->prepare("SELECT id FROM horas WHERE hora = ?");
                        $stmtHoraFin->execute([$horaFin]);
                        $idHoraFin = $stmtHoraFin->fetchColumn();
                        
                        if (!$idHoraFin) {
                            $stmtInsertHoraFin = $this->pdo->prepare("INSERT INTO horas (hora) VALUES (?)");
                            $stmtInsertHoraFin->execute([$horaFin]);
                            $idHoraFin = $this->pdo->lastInsertId();
                        }
                        
                        // Insertar horario
                        $stmtHorario = $this->pdo->prepare("
                            INSERT INTO horarios_doctor (id_doctor, id_dia_semana, id_hora_inicio, id_hora_fin)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmtHorario->execute([
                            $userId,
                            $idDiaSemana,
                            $idHoraInicio,
                            $idHoraFin
                        ]);
                    }
                }
            }

            $this->pdo->commit();
            return ['status' => 'success', 'message' => 'Doctor creado con éxito.', 'id' => $userId];
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

            // Verificar que el doctor existe
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM doctores WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() == 0) {
                $this->pdo->rollBack();
                return ['status' => 'error', 'message' => 'Doctor no encontrado.'];
            }

            // Verificar que la cédula no esté en uso por otro usuario
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE cedula = ? AND id != ?");
            $stmt->execute([$data['cedula'], $id]);
            if ($stmt->fetchColumn() > 0) {
                $this->pdo->rollBack();
                return ['status' => 'error', 'message' => 'La cédula ya está registrada por otro usuario.'];
            }

            // Verificar que el correo no esté en uso por otro usuario
            if (!empty($data['correo'])) {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE correo = ? AND id != ?");
                $stmt->execute([$data['correo'], $id]);
                if ($stmt->fetchColumn() > 0) {
                    $this->pdo->rollBack();
                    return ['status' => 'error', 'message' => 'El correo electrónico ya está registrado por otro usuario.'];
                }
            }

            // Actualizar datos del usuario
            $stmt = $this->pdo->prepare("
                UPDATE usuarios 
                SET nombre=?, apellido=?, cedula=?, correo=?, telefono=?, fecha_nacimiento=?, direccion=?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['nombre'], $data['apellido'], $data['cedula'], $data['correo'] ?? null,
                $data['telefono'] ?? null, $data['fecha_nacimiento'] ?? null,
                $data['direccion'] ?? null, $id
            ]);

            // Actualizar especialidad del doctor
            $stmt = $this->pdo->prepare("
                UPDATE doctores 
                SET id_especialidad=?
                WHERE id = ?
            ");
            $stmt->execute([$data['id_especialidad'], $id]);

            // Limpiar horarios y volver a insertar
            $this->pdo->prepare("DELETE FROM horarios_doctor WHERE id_doctor = ?")->execute([$id]);

            if (!empty($data['horarios'])) {
                foreach ($data['horarios'] as $h) {
                    // Convertir número de día a id_dia_semana
                    $dia = intval($h['dia']);
                    $stmtDia = $this->pdo->prepare("SELECT id FROM dias_semana WHERE numero_dia = ?");
                    $stmtDia->execute([$dia]);
                    $idDiaSemana = $stmtDia->fetchColumn();
                    
                    if ($idDiaSemana && $h['hora_inicio_am'] && $h['hora_fin_am']) {
                        $horaInicio = $h['hora_inicio_am'];
                        $horaFin = $h['hora_fin_am'];
                        
                        // Obtener o crear hora de inicio
                        $stmtHora = $this->pdo->prepare("SELECT id FROM horas WHERE hora = ?");
                        $stmtHora->execute([$horaInicio]);
                        $idHoraInicio = $stmtHora->fetchColumn();
                        
                        if (!$idHoraInicio) {
                            $stmtInsertHora = $this->pdo->prepare("INSERT INTO horas (hora) VALUES (?)");
                            $stmtInsertHora->execute([$horaInicio]);
                            $idHoraInicio = $this->pdo->lastInsertId();
                        }
                        
                        // Obtener o crear hora de fin
                        $stmtHora = $this->pdo->prepare("SELECT id FROM horas WHERE hora = ?");
                        $stmtHora->execute([$horaFin]);
                        $idHoraFin = $stmtHora->fetchColumn();
                        
                        if (!$idHoraFin) {
                            $stmtInsertHora = $this->pdo->prepare("INSERT INTO horas (hora) VALUES (?)");
                            $stmtInsertHora->execute([$horaFin]);
                            $idHoraFin = $this->pdo->lastInsertId();
                        }
                        
                        // Insertar horario
                        $stmtHorario = $this->pdo->prepare("
                            INSERT INTO horarios_doctor (id_doctor, id_dia_semana, id_hora_inicio, id_hora_fin)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmtHorario->execute([
                            $id,
                            $idDiaSemana,
                            $idHoraInicio,
                            $idHoraFin
                        ]);
                    }
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
            
            // Verificar si el doctor tiene consultas asociadas
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM consultas WHERE id_doctor = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $this->pdo->rollBack();
                return ['status' => 'error', 'message' => 'No se puede eliminar el doctor porque tiene citas asociadas.'];
            }
            
            // Eliminar horarios (se eliminarán automáticamente por CASCADE, pero lo hacemos explícitamente)
            $this->pdo->prepare("DELETE FROM horarios_doctor WHERE id_doctor = ?")->execute([$id]);
            
            // Eliminar doctor (esto eliminará automáticamente el usuario por CASCADE)
            $this->pdo->prepare("DELETE FROM doctores WHERE id = ?")->execute([$id]);
            
            // Eliminar usuario (si no se eliminó por CASCADE)
            $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
            
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