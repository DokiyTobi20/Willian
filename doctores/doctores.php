<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Doctores</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../doctores/doctores.css">
</head>
<body>
<?php
require_once __DIR__ . '/../BDD/conexion.php';

session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../acceso/acceso.php');
    exit;
}

// Obtener lista de doctores y estadísticas
try {
    $pdo = Conexion::conectar();
    $sql = "SELECT d.id, d.id_especialidad, 
                   u.nombre, u.apellido, u.cedula, u.correo, u.telefono, 
                   u.fecha_nacimiento, u.direccion,
                   e.nombre AS especialidad 
            FROM doctores d
            INNER JOIN usuarios u ON d.id = u.id
            LEFT JOIN especialidades e ON d.id_especialidad = e.id
            ORDER BY u.apellido, u.nombre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $doctores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas
    $totalDoctores = count($doctores);
    $sqlEspecialidades = "SELECT COUNT(DISTINCT id_especialidad) AS total FROM doctores";
    $stmtEsp = $pdo->prepare($sqlEspecialidades);
    $stmtEsp->execute();
    $rowEsp = $stmtEsp->fetch(PDO::FETCH_ASSOC);
    $totalEspecialidades = $rowEsp ? $rowEsp['total'] : 0;
} catch (PDOException $e) {
    $doctores = [];
    $error = 'Error al cargar los doctores';
    $totalDoctores = 0;
    $totalEspecialidades = 0;
}
?>
<div class="container" data-module="doctores">
    <div class="header">
        <h1><i class='bx bx-user-plus'></i> Gestión de Doctores</h1>
        <p>Administra los doctores médicos del sistema</p>
    </div>

    <div class="controls">
        <div class="search-box">
            <input type="text" id="busquedaDoctor" placeholder="Buscar por doctor (nombre o cédula)...">
            <i class='bx bx-search'></i>
        </div>

        <select id="filtroEspecialidad" class="filter-select">
            <option value="">Todas las especialidades</option>
        </select>

        <button class="btn-primary" id="btnNuevoDoctor">
            <i class='bx bx-plus'></i> Registrar Doctor
        </button>
    </div>

    <div class="stats">
        <div class="stat-card">
            <i class='bx bx-user-check'></i>
            <h3 id="statTotalDoctores"><?= $totalDoctores ?></h3>
            <p>Total Doctores</p>
        </div>
        <div class="stat-card">
            <i class='bx bx-plus-medical'></i>
            <h3 id="statEspecialidades"><?= $totalEspecialidades ?></h3>
            <p>Especialidades Activas</p>
        </div>
        <div class="stat-card">
            <i class='bx bx-search-alt'></i>
            <h3 id="contadorResultados"><?= $totalDoctores ?></h3>
            <p>Doctores Mostrados</p>
        </div>
    </div>

    <!-- Doctores Table/Grid -->
    <div class="doctores-table">
        <div class="table-header">
            <h2>Doctores Registrados</h2>
        </div>
        <div class="table-container">
            <?php if (empty($doctores)): ?>
                <div class="no-doctores">
                    <i class='bx bx-user-x'></i>
                    <h3>No hay doctores registrados</h3>
                    <p>Comienza agregando el primer doctor médico.</p>
                </div>
            <?php else: ?>
                <div class="resultados-info" style="margin-bottom: 15px; color:#6c757d;">
                    <span class="contador"><?= $totalDoctores ?> doctor<?= $totalDoctores !== 1 ? 'es' : '' ?> registrado<?= $totalDoctores !== 1 ? 's' : '' ?></span>
                </div>
                <div class="doctors-grid" id="doctoresGrid">
                    <?php foreach ($doctores as $doctor): ?>
                        <div class="doctor-card" data-doctor-id="<?= $doctor['id'] ?>" <?= !empty($doctor['id_especialidad']) ? 'data-especialidad-id="' . (int)$doctor['id_especialidad'] . '"' : '' ?>>
                            <div class="doctor-info">
                                <h4><?= htmlspecialchars($doctor['nombre'] . ' ' . $doctor['apellido']) ?></h4>
                                <p><i class='bx bx-id-card'></i> <?= htmlspecialchars($doctor['cedula']) ?></p>
                                <p><i class='bx bx-envelope'></i> <?= htmlspecialchars($doctor['correo'] ?? 'N/A') ?></p>
                                <p><i class='bx bx-plus-medical'></i> <?= htmlspecialchars($doctor['especialidad'] ?? 'Sin especialidad') ?></p>
                                <?php if (!empty($doctor['telefono'])): ?>
                                    <p><i class='bx bx-phone'></i> <?= htmlspecialchars($doctor['telefono']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="doctor-actions">
                                <button class="btn-edit" onclick="editarDoctor(<?= $doctor['id'] ?>)">
                                    <i class='bx bx-edit'></i> Editar
                                </button>
                                <button class="btn-delete" onclick="eliminarDoctor(<?= $doctor['id'] ?>, '<?= htmlspecialchars($doctor['nombre'] . ' ' . $doctor['apellido'], ENT_QUOTES) ?>')">
                                    <i class='bx bx-trash'></i> Eliminar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Registro/Edición -->
<div id="modalDoctor" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo">Registrar Nuevo Doctor</h2>
            <span class="close" id="cerrarModal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formDoctor">
                <input type="hidden" name="id" id="doctor_id">

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido *</label>
                        <input type="text" id="apellido" name="apellido" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cedula">Cédula *</label>
                        <input type="text" id="cedula" name="cedula" required>
                    </div>
                    <div class="form-group">
                        <label for="correo">Correo Electrónico *</label>
                        <input type="email" id="correo" name="correo" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono">
                    </div>
                    <div class="form-group">
                        <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento">
                    </div>
                </div>

                <div class="form-group">
                    <label for="id_especialidad">Especialidad *</label>
                    <select id="id_especialidad" name="id_especialidad" required>
                        <option value="">Seleccione una especialidad...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <textarea id="direccion" name="direccion" rows="3"></textarea>
                </div>

                <!-- Horarios -->
                <div class="form-section">
                    <h3>Horarios de Trabajo</h3>
                    <div class="schedule-container">
                        <!-- 7 días, cada uno con dos campos de hora tipo time -->
                        <?php 
                        $dias = [
                            1 => 'Lunes',
                            2 => 'Martes',
                            3 => 'Miércoles',
                            4 => 'Jueves',
                            5 => 'Viernes'
                        ];
                        foreach ($dias as $num => $nombre): ?>
                        <div class="schedule-day">
                            <div class="day-header">
                                <input type="checkbox" id="dia<?= $num ?>" name="dias[]" value="<?= $num ?>">
                                <label for="dia<?= $num ?>"><?= $nombre ?></label>
                            </div>
                            <div class="time-inputs">
                                <div class="time-group">
                                    <label>Hora inicio</label>
                                    <select name="hora_inicio[<?= $num ?>]" disabled style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                                        <option value="">Seleccione...</option>
                                        <?php 
                                        // Horas de 6:00 AM a 11:00 AM
                                        for ($h = 6; $h <= 11; $h++): 
                                            $hora24 = sprintf('%02d:00', $h);
                                            $hora12 = sprintf('%d:00 AM', $h);
                                        ?>
                                            <option value="<?= $hora24 ?>"><?= $hora12 ?></option>
                                        <?php endfor; ?>
                                        <?php 
                                        // 12:00 PM (mediodía)
                                        ?>
                                        <option value="12:00">12:00 PM</option>
                                        <?php 
                                        // Horas de 1:00 PM a 2:00 PM
                                        for ($h = 13; $h <= 14; $h++): 
                                            $hora24 = sprintf('%02d:00', $h);
                                            $hora12 = sprintf('%d:00 PM', $h - 12);
                                        ?>
                                            <option value="<?= $hora24 ?>"><?= $hora12 ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="time-group">
                                    <label>Hora fin</label>
                                    <select name="hora_fin[<?= $num ?>]" disabled style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                                        <option value="">Seleccione...</option>
                                        <?php 
                                        // Horas de 6:00 AM a 11:00 AM
                                        for ($h = 6; $h <= 11; $h++): 
                                            $hora24 = sprintf('%02d:00', $h);
                                            $hora12 = sprintf('%d:00 AM', $h);
                                        ?>
                                            <option value="<?= $hora24 ?>"><?= $hora12 ?></option>
                                        <?php endfor; ?>
                                        <?php 
                                        // 12:00 PM (mediodía)
                                        ?>
                                        <option value="12:00">12:00 PM</option>
                                        <?php 
                                        // Horas de 1:00 PM a 2:00 PM
                                        for ($h = 13; $h <= 14; $h++): 
                                            $hora24 = sprintf('%02d:00', $h);
                                            $hora12 = sprintf('%d:00 PM', $h - 12);
                                        ?>
                                            <option value="<?= $hora24 ?>"><?= $hora12 ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="modal-actions" style="display: flex; flex-direction: row; justify-content: center; gap: 20px;">
                    <button type="button" class="btn-secondary" id="btnCancelar">Cancelar</button>
                    <button type="submit" class="btn-primary" id="btnGuardar">Crear Doctor</button>
                </div>
                <style>
                .modal-actions {
                    display: flex !important;
                    flex-direction: row !important;
                    justify-content: flex-end !important;
                    gap: 20px !important;
                }
                .modal-actions button:first-child {
                    order: 1;
                }
                .modal-actions button:last-child {
                    order: 2;
                }
                </style>
                </div>
            </form>
        </div>
    </div>
</div>


</body>
<script src="doctores.js"></script>
<script>
// Evitar scroll del body cuando el modal está abierto
function toggleBodyScroll(disable) {
    document.body.style.overflow = disable ? 'hidden' : 'auto';
}
// Detectar apertura/cierre del modal
const modalDoctor = document.getElementById('modalDoctor');
if (modalDoctor) {
    const observer = new MutationObserver(() => {
        if (modalDoctor.style.display === 'block') {
            toggleBodyScroll(true);
        } else {
            toggleBodyScroll(false);
        }
    });
    observer.observe(modalDoctor, { attributes: true, attributeFilter: ['style'] });
}
</script>
</html>