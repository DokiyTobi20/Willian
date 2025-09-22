<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Consultas</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../consultas/consultas.css">
</head>
<body>
<div class="container" data-module="consultas">
    <!-- Header -->
    <div class="header">
        <h1><i class='bx bx-clipboard'></i> Gestión de Consultas</h1>
        <p>Administra las consultas médicas del sistema</p>
    </div>

    <!-- Controles -->
    <div class="controls">
        <div class="search-box">
            <input type="text" id="busquedaConsulta" placeholder="Buscar por usuario, diagnóstico, medicación...">
            <i class='bx bx-search'></i>
        </div>
        <button class="btn-primary" id="btnNuevaConsulta">
            <i class='bx bx-plus'></i> Registrar Consulta
        </button>
    </div>

    <!-- Estadísticas -->
    <div class="stats">
        <div class="stat-card">
            <i class='bx bx-clipboard'></i>
            <h3 id="statTotalConsultas">0</h3>
            <p>Total Consultas</p>
        </div>
        <div class="stat-card">
            <i class='bx bx-check-circle'></i>
            <h3 id="statCompletadas">0</h3>
            <p>Completadas</p>
        </div>
        <div class="stat-card">
            <i class='bx bx-time'></i>
            <h3 id="statPendientes">0</h3>
            <p>Pendientes</p>
        </div>
    </div>

    <!-- Lista de Consultas -->
    <div class="doctors-grid" id="consultasGrid">
        <!-- Aquí se mostrarán las tarjetas de consultas, usando la clase doctor-card para cada consulta -->
        <!-- Ejemplo de tarjeta:
        <div class="doctor-card">
            <div class="doctor-header">
                <div class="doctor-avatar">C</div>
                <div class="doctor-info">
                    <h3>Usuario Ejemplo</h3>
                    <span class="doctor-specialty">Diagnóstico: Gripe</span>
                </div>
            </div>
            <div class="doctor-details">
                <div class="doctor-detail"><i class='bx bx-capsule'></i> Medicación: Paracetamol</div>
                <div class="doctor-detail"><i class='bx bx-comment'></i> Observaciones: Reposo 3 días</div>
            </div>
            <div class="doctor-actions">
                <button class="btn-edit"><i class='bx bx-edit'></i> Editar</button>
                <button class="btn-delete"><i class='bx bx-trash'></i> Eliminar</button>
            </div>
        </div>
        -->
    </div>
</div>

<!-- Modal Crear Consulta -->
<div id="modalCrear" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class='bx bx-plus-circle'></i> Nueva Consulta</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formCrearConsulta">
                <div class="form-group">
                    <label for="id_cita_crear">Cita:</label>
                    <select id="id_cita_crear" name="id_cita" required>
                        <option value="">Seleccione una cita...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="diagnostico_crear">Diagnóstico:</label>
                    <textarea id="diagnostico_crear" name="diagnostico" required 
                              placeholder="Escriba el diagnóstico de la consulta..."></textarea>
                </div>
                <div class="form-group">
                    <label for="receta_crear">Receta:</label>
                    <textarea id="receta_crear" name="receta" 
                              placeholder="Escriba la receta médica..."></textarea>
                </div>
                <div class="form-group">
                    <label for="observaciones_crear">Observaciones:</label>
                    <textarea id="observaciones_crear" name="observaciones" 
                              placeholder="Observaciones adicionales..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class='bx bx-check'></i> Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Consulta -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTituloConsulta">Editar Consulta</h2>
            <span class="close" id="cerrarModalConsulta">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formEditarConsulta">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_usuario">Usuario:</label>
                    <input type="text" id="edit_usuario" name="usuario" placeholder="Buscar usuario..." required autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="edit_diagnostico">Diagnóstico:</label>
                    <textarea id="edit_diagnostico" name="diagnostico" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_medicacion">Medicación:</label>
                    <textarea id="edit_medicacion" name="medicacion" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_observaciones">Observaciones:</label>
                    <textarea id="edit_observaciones" name="observaciones"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class='bx bx-check'></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Consulta -->
<div id="modalVer" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class='bx bx-show'></i> Detalles de la Consulta</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="detallesConsulta"></div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary">Cerrar</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>