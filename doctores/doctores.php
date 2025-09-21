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
                        <!-- 7 días sin generación dinámica -->
                        <div class="schedule-day">
                            <div class="day-header">
                                <input type="checkbox" id="dia1" name="dias[]" value="1">
                                <label for="dia1">Lunes</label>
                            </div>
                            <div class="time-inputs">
                                <div class="time-group">
                                    <label>AM</label>
                                    <select name="hora_inicio_am[1]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_am[1]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                                <div class="time-group">
                                    <label>PM</label>
                                    <select name="hora_inicio_pm[1]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_pm[1]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="schedule-day">
                            <div class="day-header">
                                <input type="checkbox" id="dia2" name="dias[]" value="2">
                                <label for="dia2">Martes</label>
                            </div>
                            <div class="time-inputs">
                                <div class="time-group">
                                    <label>AM</label>
                                    <select name="hora_inicio_am[2]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_am[2]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                                <div class="time-group">
                                    <label>PM</label>
                                    <select name="hora_inicio_pm[2]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_pm[2]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="schedule-day">
                            <div class="day-header">
                                <input type="checkbox" id="dia3" name="dias[]" value="3">
                                <label for="dia3">Miércoles</label>
                            </div>
                            <div class="time-inputs">
                                <div class="time-group">
                                    <label>AM</label>
                                    <select name="hora_inicio_am[3]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_am[3]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                                <div class="time-group">
                                    <label>PM</label>
                                    <select name="hora_inicio_pm[3]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_pm[3]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="schedule-day">
                            <div class="day-header">
                                <input type="checkbox" id="dia4" name="dias[]" value="4">
                                <label for="dia4">Jueves</label>
                            </div>
                            <div class="time-inputs">
                                <div class="time-group">
                                    <label>AM</label>
                                    <select name="hora_inicio_am[4]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_am[4]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                                <div class="time-group">
                                    <label>PM</label>
                                    <select name="hora_inicio_pm[4]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_pm[4]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="schedule-day">
                            <div class="day-header">
                                <input type="checkbox" id="dia5" name="dias[]" value="5">
                                <label for="dia5">Viernes</label>
                            </div>
                            <div class="time-inputs">
                                <div class="time-group">
                                    <label>AM</label>
                                    <select name="hora_inicio_am[5]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_am[5]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                                <div class="time-group">
                                    <label>PM</label>
                                    <select name="hora_inicio_pm[5]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_pm[5]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="schedule-day">
                            <div class="day-header">
                                <input type="checkbox" id="dia6" name="dias[]" value="6">
                                <label for="dia6">Sábado</label>
                            </div>
                            <div class="time-inputs">
                                <div class="time-group">
                                    <label>AM</label>
                                    <select name="hora_inicio_am[6]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_am[6]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                                <div class="time-group">
                                    <label>PM</label>
                                    <select name="hora_inicio_pm[6]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_pm[6]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="schedule-day">
                            <div class="day-header">
                                <input type="checkbox" id="dia7" name="dias[]" value="7">
                                <label for="dia7">Domingo</label>
                            </div>
                            <div class="time-inputs">
                                <div class="time-group">
                                    <label>AM</label>
                                    <select name="hora_inicio_am[7]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_am[7]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                                <div class="time-group">
                                    <label>PM</label>
                                    <select name="hora_inicio_pm[7]" disabled>
                                        <option value="">Inicio</option>
                                    </select>
                                    <select name="hora_fin_pm[7]" disabled>
                                        <option value="">Fin</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="btnCancelar">Cancelar</button>
                    <button type="submit" class="btn-primary" id="btnGuardar">Crear Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Horarios -->
<div id="modalHorarios" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="tituloHorarios">Horarios del Doctor</h2>
            <span class="close" id="cerrarModalHorarios">&times;</span>
        </div>
        <div class="modal-body">
            <div id="contenidoHorarios">
            </div>
        </div>
    </div>
</div>

</body>
<script src="doctores.js"></script>
</html>