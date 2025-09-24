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

        <button class="btn-primary" id="btnListaEspera">
            <i class='bx bx-time-five'></i> Registrarme en lista de espera
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
    <?php
    require_once __DIR__ . '/../BDD/conexion.php';
    $pdo = Conexion::conectar();

    // Fecha de hoy (YYYY-MM-DD)
    $hoy = date('Y-m-d');

    // Buscamos la lista de espera creada hoy (si existe)
    $stmt = $pdo->prepare('SELECT id FROM listas_espera WHERE DATE(fecha_creacion) = ? LIMIT 1');
    $stmt->execute([$hoy]);
    $lista = $stmt->fetch(PDO::FETCH_ASSOC);

    $inscripciones = [];
    if ($lista) {
        $id_lista = $lista['id'];

        // SELECT con alias claro
        $stmt = $pdo->prepare('
            SELECT 
                le.numero AS turno,
                u.cedula AS cedula,
                CONCAT(u.nombre, " ", u.apellido) AS nombre_completo
            FROM lista_espera_inscripciones le
            JOIN usuarios u ON le.id_usuario = u.id
            WHERE le.id_lista = ?
            ORDER BY le.numero ASC
        ');
        $stmt->execute([$id_lista]);
        $inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    ?>
    <div class="citas-table">
        <div class="table-header">
            <h2>Lista de espera</h2>
        </div>
        <div class="table-container">
            <table id="tablaListaEspera">
                <thead>
                    <tr>
                        <th>Turno</th>
                        <th>Hora</th>
                        <th>Nombre y apellido</th>
                        <th>Cédula</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($inscripciones) > 0): ?>
                    <?php foreach ($inscripciones as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['turno']) ?></td>
                            <td>
                                <?= is_numeric($item['turno'])
                                    ? htmlspecialchars(sprintf('%02d:00', 7 + (int)$item['turno']))
                                    : '' ?>
                            </td>
                            <td><?= htmlspecialchars($item['nombre_completo']) ?></td>
                            <td><?= htmlspecialchars($item['cedula']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No hay inscripciones en la lista de espera hoy.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="citas.js"></script>
</body>
</html>