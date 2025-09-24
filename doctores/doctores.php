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
    $sql = "SELECT d.*, e.nombre AS especialidad FROM doctores d LEFT JOIN especialidades e ON d.id_especialidad = e.id ORDER BY d.apellido, d.nombre";
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
            <input type="text" id="busquedaDoctor" placeholder="Buscar por nombre, cédula, especialidad...">
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
            <h3 id="statTotalDoctores">0</h3>
            <p>Total Doctores</p>
        </div>
        <div class="stat-card">
            <i class='bx bx-plus-medical'></i>
            <h3 id="statEspecialidades">0</h3>
            <p>Especialidades Activas</p>
        </div>
        <div class="stat-card">
            <i class='bx bx-search-alt'></i>
            <h3 id="contadorResultados">0</h3>
            <p>Doctores Mostrados</p>
        </div>
    </div>

    <div class="doctors-grid" id="doctoresGrid">
        <!-- Deja este contenedor vacío para que agregues tarjetas de doctores a mano -->
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
                <!-- Sin campos ocultos ni backend -->

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
                            5 => 'Viernes',
                            6 => 'Sábado',
                            7 => 'Domingo'
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
                                    <input type="time" name="hora_inicio[<?= $num ?>]" disabled>
                                </div>
                                <div class="time-group">
                                    <label>Hora fin</label>
                                    <input type="time" name="hora_fin[<?= $num ?>]" disabled>
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