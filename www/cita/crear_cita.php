<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit;
}
include("../assets/PHP/crm_lib.php");
$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

$sql_tratamientos = "SELECT id, nombre, duracion_minutos FROM tratamiento_base ORDER BY nombre";
$result_tratamientos = $conn->query($sql_tratamientos);

$fecha = htmlspecialchars($_GET['fecha'] ?? '');
$hora = htmlspecialchars($_GET['hora'] ?? '');
$id_agenda = htmlspecialchars($_GET['id_agenda'] ?? '');

// Estados visuales
$estados = [
    1 => ['nombre' => 'Planificada',     'color_hex' => '#FFFFFF', 'icono' => 'calendar2.png'],
    2 => ['nombre' => 'Confirmada',      'color_hex' => '#007BFF', 'icono' => 'check_1.png'],
    3 => ['nombre' => 'Consulta',        'color_hex' => '#FFC0CB', 'icono' => '35.gif'],
    4 => ['nombre' => 'Finalizada',      'color_hex' => '#28A745', 'icono' => 'okok.png'],
    5 => ['nombre' => 'Fallada',         'color_hex' => '#FF0000', 'icono' => 'del.png'],
    6 => ['nombre' => 'OK Recordatorio', 'color_hex' => '#FFFF00', 'icono' => 'sms_01.png'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Nueva Cita</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .full.paciente-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .paciente-row input[type="text"] { min-width: 160px; }
    </style>
</head>
<body>
<main class="container">
    <h1 class="titulo-agenda">Registrar Nueva Cita</h1>

    <form action="insertar_cita.php" method="post" class="form-cita-grid">
        <!-- Paciente registrado o primera visita -->
        <div class="full paciente-row">
            <label for="nombre_paciente">Paciente:</label>
            <input type="text" id="nombre_paciente" name="nombre_paciente_manual" placeholder="Nombre (para Primera Visita)">
            <input type="hidden" name="id_paciente" id="id_paciente">
            <button type="button" onclick="abrirBuscadorPacientes()">🔍</button>
            <span style="font-size:13px;color:#888;">(Si es primera visita, escribe el nombre. Si ya existe, usa la lupa.)</span>
        </div>

        <div>
            <label>Fecha:</label>
            <input type="text" value="<?= $fecha ?>" readonly>
            <input type="hidden" name="fecha" value="<?= $fecha ?>">
        </div>
        <div>
            <label>Hora de inicio:</label>
            <input type="text" value="<?= $hora ?>" readonly>
            <input type="hidden" name="hora_inicio" value="<?= $hora ?>">
        </div>
        <input type="hidden" name="id_agenda" value="<?= $id_agenda ?>">

        <div>
            <label>Tratamiento:</label>
            <select name="id_tratamiento" id="id_tratamiento" required onchange="actualizarDuracion()">
                <option value="">-- Seleccionar Tratamiento --</option>
                <?php while ($t = $result_tratamientos->fetch_assoc()): ?>
                    <option value="<?= $t['id'] ?>" data-duracion="<?= $t['duracion_minutos'] ?>">
                        <?= $t['nombre'] ?> (<?= $t['duracion_minutos'] ?> min)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label for="estado-cita-select">Estado de la Cita:</label>
            <div class="estado-cita-row">
                <span id="icono-estado" class="icono-estado-select">
                    <img src="../assets/images/okok.png" alt="Estado">
                </span>
                <select name="id_estado_cita" id="estado-cita-select" required>
                    <?php foreach ($estados as $k => $est): ?>
                        <option value="<?= $k ?>"
                            data-color="<?= $est['color_hex'] ?>"
                            data-icono="<?= $est['icono'] ?>"
                            <?= $k == 1 ? 'selected' : '' ?>>
                            <?= $est['nombre'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label>Duración (minutos):</label>
            <input type="number" name="duracion_minutos" id="duracion_minutos" min="5" step="5" required>
        </div>
        <div>
            <label>Observaciones:</label>
            <textarea name="observaciones" rows="3"></textarea>
        </div>
        <div class="full acciones">
            <button type="submit" class="primary-btn">Guardar Cita</button>
            <button type="button" class="secondary-btn" onclick="window.close();">Cancelar</button>
        </div>
    </form>
</main>
<script src="../assets/JS/crm_lib.js"></script>
</body>
</html>