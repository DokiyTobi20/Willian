<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Consultas</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="consultas.css">
</head>
<body>
<div class="container" data-module="consultas">
    <!-- Header -->
    <div class="header">
        <h1><i class='bx bx-clipboard'></i> Gestión de Consultas</h1>
        <p>Gestiona las consultas médicas del sistema</p>
        <div class="role-badge role-doctor">
            <i class='bx bx-user-voice'></i>
            Doctor
        </div>
    </div>

    <!-- Controles -->
    <div class="controls">
        <div class="search-box">
            <input type="text" id="buscarConsulta" placeholder="Buscar por paciente, doctor o diagnóstico...">
            <i class='bx bx-search'></i>
        </div>

        <button class="btn-primary">
            <i class='bx bx-plus'></i> Nueva Consulta
        </button>
    </div>

    <!-- Estadísticas -->
    <div class="stats">
        <div class="stat-card">
            <i class='bx bx-clipboard'></i>
            <h3>0</h3>
            <p>Total Consultas</p>
        </div>
        <div class="stat-card">
            <i class='bx bx-check-circle'></i>
            <h3>0</h3>
            <p>Completadas</p>
        </div>
        <div class="stat-card">
            <i class='bx bx-time'></i>
            <h3>0</h3>
            <p>Pendientes</p>
        </div>
    </div>

    <!-- Lista de Consultas -->
    <div class="consultas-table">
        <div class="table-header">
            <h2>Consultas Registradas</h2>
        </div>

        <div class="table-container">
            <div class="no-consultas">
                <i class='bx bx-folder-open'></i>
                <h3>No hay consultas registradas</h3>
                <p>Haz clic en "Nueva Consulta" para agregar la primera</p>
            </div>
        </div>
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
            <h3><i class='bx bx-edit'></i> Editar Consulta</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formEditarConsulta">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_diagnostico">Diagnóstico:</label>
                    <textarea id="edit_diagnostico" name="diagnostico" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_receta">Receta:</label>
                    <textarea id="edit_receta" name="receta"></textarea>
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