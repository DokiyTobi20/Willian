<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Especialidades - Sistema Médico</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../especialidades/especialidades.css?v=20250904-2256">
</head>

<body>
    <?php
    require_once __DIR__ . '/../BDD/conexion.php';

    session_start();
    if (!isset($_SESSION['usuario'])) {
        header('Location: ../acceso/acceso.php');
        exit;
    }

    // Obtener lista de especialidades y estadísticas con la lógica funcional
    try {
        $pdo = Conexion::conectar();
        $sql = "SELECT * FROM especialidades ORDER BY nombre ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $especialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Estadísticas
        $totalEspecialidades = count($especialidades);
        $sqlDoctores = "SELECT e.id, e.nombre, COUNT(d.id) as total_doctores 
                    FROM especialidades e 
                    LEFT JOIN doctores d ON e.id = d.id_especialidad 
                    GROUP BY e.id, e.nombre";
        $stmtDoctores = $pdo->prepare($sqlDoctores);
        $stmtDoctores->execute();
        $estadisticasDoctores = $stmtDoctores->fetchAll(PDO::FETCH_ASSOC);
        $doctoresPorEspecialidad = [];
        foreach ($estadisticasDoctores as $stat) {
            $doctoresPorEspecialidad[$stat['id']] = $stat['total_doctores'];
        }
    } catch (PDOException $e) {
        $especialidades = [];
        $error = 'Error al cargar las especialidades';
        $totalEspecialidades = 0;
        $doctoresPorEspecialidad = [];
    }
    ?>
    <div class="container" data-module="especialidades">
        <!-- Header -->
        <div class="header">
            <h1><i class='bx bx-plus-medical'></i> Gestión de Especialidades Médicas</h1>
            <p>Administra las especialidades médicas registradas en el sistema</p>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="search-box">
                <input type="text" id="nombreEspecialidad" placeholder="Buscar especialidades...">
                <i class='bx bx-search-alt'></i>
            </div>
            <button class="btn-primary" onclick="abrirModalCrear()">
                <i class='bx bx-plus'></i>
                Nueva Especialidad
            </button>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <i class='bx bx-collection'></i>
                <h3><?= $totalEspecialidades ?></h3>
                <p>Total de Especialidades</p>
            </div>
            <div class="stat-card">
                <i class='bx bx-user-voice'></i>
                <h3><?= array_sum($doctoresPorEspecialidad) ?></h3>
                <p>Doctores Registrados</p>
            </div>
            <div class="stat-card">
                <i class='bx bx-check-circle'></i>
                <h3><?= count(array_filter($doctoresPorEspecialidad, fn($count) => $count > 0)) ?></h3>
                <p>Especialidades Activas</p>
            </div>
        </div>

        <!-- Especialidades Table/Grid -->
        <div class="especialidades-table">
            <div class="table-header">
                <h2>Especialidades Registradas</h2>
            </div>
            <div class="table-container">

                <?php if (isset($error)): ?>
                    <div class="alerta error">
                        <i class="bx bx-error-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>

                <?php elseif (empty($especialidades)): ?>
                    <div class="no-especialidades">
                        <i class='bx bx-health'></i>
                        <h3>No hay especialidades registradas</h3>
                        <p>Utiliza el botón "Nueva Especialidad" en la parte superior para agregar una.</p>
                    </div>
                    
                <?php else: ?>
                    <div class="resultados-info" style="margin-bottom: 15px; color:#6c757d;">
                        <span class="contador"><?= $totalEspecialidades ?> especialidad<?= $totalEspecialidades !== 1 ? 'es' : '' ?> registrada<?= $totalEspecialidades !== 1 ? 's' : '' ?></span>
                    </div>
                    <div class="especialidades-grid">
                        <?php foreach ($especialidades as $esp): ?>
                            <div class="especialidad-card">
                                <div class="especialidad-info">
                                    <h4><?= htmlspecialchars($esp['nombre']) ?></h4>
                                </div>
                                <div class="especialidad-actions">
                                    <button class="btn-edit" onclick="editarEspecialidad(<?= (int)$esp['id'] ?>, '<?= htmlspecialchars($esp['nombre'], ENT_QUOTES) ?>')">
                                        <i class='bx bx-edit'></i> Editar
                                    </button>
                                    <?php if (($doctoresPorEspecialidad[$esp['id']] ?? 0) === 0): ?>
                                        <button class="btn-delete" onclick="eliminarEspecialidad(<?= (int)$esp['id'] ?>, '<?= htmlspecialchars($esp['nombre'], ENT_QUOTES) ?>')">
                                            <i class='bx bx-trash'></i> Eliminar
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-secondary" disabled title="No se puede eliminar: tiene doctores asignados">
                                            <i class='bx bx-lock'></i> Protegida
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Crear -->
    <div id="modalCrear" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class='bx bx-plus'></i> Nueva Especialidad</h3>
                <span class="close" title="Cerrar">&times;</span>
            </div>
            <div class="modal-body">
                <form id="formCrearEspecialidad">
                    <div class="form-group">
                        <label for="nombre"><i class='bx bx-health'></i> Nombre de la Especialidad: *</label>
                        <input type="text" id="nombre" name="nombre" required maxlength="100" placeholder="Ej: Cardiología, Pediatría, Neurología...">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="cerrarModal()">
                            <i class='bx bx-x'></i> Cancelar
                        </button>
                        <button type="submit" class="btn-primary" data-original-text="Crear">
                            <i class='bx bx-save'></i> Crear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class='bx bx-edit'></i> Editar Especialidad</h3>
                <span class="close" title="Cerrar">&times;</span>
            </div>
            <div class="modal-body">
                <form id="formEditarEspecialidad">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label for="edit_nombre"><i class='bx bx-health'></i> Nombre de la Especialidad: *</label>
                        <input type="text" id="edit_nombre" name="nombre" required maxlength="100" placeholder="Ej: Cardiología, Pediatría, Neurología...">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="cerrarModal()">
                            <i class='bx bx-x'></i> Cancelar
                        </button>
                        <button type="submit" class="btn-primary" data-original-text="Actualizar">
                            <i class='bx bx-save'></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="especialidades.js"></script>
</body>

</html>