<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../citas/citas.css">
</head>
<body>
<div class="container" data-module="citas">
    <!-- Header -->
    <div class="header">
        <h1><i class='bx bx-calendar-check'></i> Gestión de Citas</h1>
        <p>Administra y agenda citas médicas de manera eficiente</p>
    </div>

    <!-- Controles -->
    <div class="controls">
        <div class="search-box">
            <input type="text" id="busqueda" placeholder="Buscar por paciente o doctor...">
            <i class='bx bx-search'></i>
        </div>

        <div class="date-picker">
            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha">
        </div>

        <button class="btn-primary" id="btnAbrirAgendar">
            <i class='bx bx-plus'></i> Agendar Cita
        </button>
    </div>

    <!-- Estadísticas -->
    <div class="stats">
        <div class="stat-card"><i class='bx bx-calendar'></i><h3>0</h3><p>Citas de hoy</p></div>
        <div class="stat-card"><i class='bx bx-time'></i><h3>0</h3><p>Pendientes</p></div>
        <div class="stat-card"><i class='bx bx-user-check'></i><h3>0</h3><p>En consulta</p></div>
        <div class="stat-card"><i class='bx bx-check-circle'></i><h3>0</h3><p>Finalizadas</p></div>
    </div>

    <!-- Tabla de Citas -->
    <div class="citas-table">
        <div class="table-header">
            <h2>Citas del día</h2>
        </div>

        <div class="table-container">
            <div class="no-citas">
                <i class='bx bx-calendar-x'></i>
                <h3>No hay citas programadas para este día</h3>
<!-- Modal Lista de Espera -->
<div id="modalListaEspera" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class='bx bx-time-five'></i> Registro en Lista de Espera</h3>
            <span class="close" id="closeListaEspera">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formListaEspera">
                <div class="form-group">
                    <label for="usuario_espera">Usuario:</label>
                    <input type="text" id="usuario_espera" name="usuario" required placeholder="Ingrese su nombre">
                </div>
                <div class="form-group">
                    <label for="hora_registro">Hora de registro:</label>
                    <input type="time" id="hora_registro" name="hora_registro" required>
                </div>
                <div class="form-group">
                    <label for="hora_disponible">Hora disponible:</label>
                    <input type="time" id="hora_disponible" name="hora_disponible" readonly>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelarListaEspera">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class='bx bx-check'></i> Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
                <p>Haz clic en "Agendar Cita" para programar la primera cita</p>
            </div>
            <!-- Si lo prefieres, puedes poner una tabla estática de ejemplo
            <table>
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Paciente</th>
                        <th>Doctor</th>
                        <th>Especialidad</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            -->
        </div>
    </div>
</div>

<!-- Modal Agendar Cita -->
<div id="modalAgendar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class='bx bx-calendar-plus'></i> Agendar Nueva Cita</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formAgendarCita">
                <div class="form-group">
                    <label for="paciente">Paciente:</label>
                    <select id="paciente" name="id_paciente" required>
                        <option value="">Seleccione un paciente...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="especialidad">Especialidad:</label>
                    <select id="especialidad" name="id_especialidad" required>
                        <option value="">Seleccione una especialidad...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="doctor">Doctor:</label>
                    <select id="doctor" name="id_doctor" required disabled>
                        <option value="">Primero seleccione una especialidad...</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_cita">Fecha:</label>
                        <input type="date" id="fecha_cita" name="fecha" required>
                    </div>
                    <div class="form-group">
                        <label for="hora_cita">Hora:</label>
                        <select id="hora_cita" name="hora" required>
                            <option value="">Seleccione hora...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class='bx bx-check'></i> Agendar</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
<script src="citas.js"></script>