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

$id_cita = $_GET["id_cita"] ?? '';
if (!$id_cita) {
    echo "Cita no encontrada";
    exit;
}

// Consulta cita + datos de paciente
$sql = "SELECT c.id, c.id_paciente, c.id_agenda, c.fecha, c.hora_inicio, c.hora_fin, 
               c.id_tratamiento_base, c.observaciones, c.id_estado_cita,
               p.nombre, p.apellido1, p.telefono1, p.nhc
        FROM cita c
        JOIN paciente p ON c.id_paciente = p.id
        WHERE c.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_cita);
$stmt->execute();
$result = $stmt->get_result();
$cita = $result->fetch_assoc();

if (!$cita) {
    echo "Cita no encontrada";
    exit;
}

// Consulta lista de tratamientos
$sql_tratamientos = "SELECT id, nombre, duracion_minutos FROM tratamiento_base ORDER BY nombre";
$res_tratamientos = $conn->query($sql_tratamientos);

// Procesar actualización
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_tratamiento = $_POST["id_tratamiento"] ?? '';
    $id_estado_cita = $_POST["id_estado_cita"] ?? '';
    $duracion = $_POST["duracion_minutos"] ?? 30;
    $observaciones = $_POST["observaciones"] ?? '';

    // Calcular nueva hora_fin según hora_inicio + duración
    $hora_inicio = $cita["hora_inicio"];
    try {
        $dt_inicio = new DateTime($hora_inicio);
        $dt_fin = clone $dt_inicio;
        $dt_fin->modify("+{$duracion} minutes");
        $hora_fin = $dt_fin->format("H:i:s");
    } catch (Exception $e) {
        $hora_fin = $hora_inicio;
    }

    $sql_update = "UPDATE cita SET id_tratamiento_base=?, id_estado_cita=?, duracion_minutos=?, hora_fin=?, observaciones=?
                   WHERE id=?";
    $stmt_upd = $conn->prepare($sql_update);
    $stmt_upd->bind_param("siisss", $id_tratamiento, $id_estado_cita, $duracion, $hora_fin, $observaciones, $id_cita);

    if ($stmt_upd->execute()) {
        echo "<script>
            if (window.opener) { window.opener.location.reload(); }
            window.close();
        </script>";
        exit;
    } else {
        $msg_error = "Error al actualizar: " . $stmt_upd->error;
    }
}

// Selecciona el tratamiento de la cita
function selected($a, $b) { return $a == $b ? "selected" : ""; }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Cita</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container" style="max-width:600px;">
    <h1>Editar Cita</h1>

    <form method="post" class="form-card">
        <div class="form-group">
            <label>Paciente:</label>
            <input type="text" readonly value="<?= htmlspecialchars($cita["apellido1"] . " " . $cita["nombre"]) ?>">
        </div>
        <div class="form-group">
            <label>NHC:</label>
            <input type="text" readonly value="<?= htmlspecialchars($cita["nhc"]) ?>">
        </div>
        <div class="form-group">
            <label>Teléfono:</label>
            <input type="text" readonly value="<?= htmlspecialchars($cita["telefono1"]) ?>">
        </div>
        <div class="form-row-flex">
            <div class="form-group">
                <label>Fecha:</label>
                <input type="text" readonly value="<?= htmlspecialchars($cita["fecha"]) ?>">
            </div>
            <div class="form-group">
                <label>Hora de inicio:</label>
                <input type="text" readonly value="<?= htmlspecialchars($cita["hora_inicio"]) ?>">
            </div>
        </div>
        <div class="form-row-flex">
            <div class="form-group">
                <label>Tratamiento:</label>
                <select name="id_tratamiento" id="id_tratamiento" required>
                    <option value="">-- Seleccionar Tratamiento --</option>
                    <?php while ($t = $res_tratamientos->fetch_assoc()): ?>
                        <option value="<?= $t['id'] ?>" <?= selected($t['id'], $cita['id_tratamiento_base']) ?>>
                            <?= htmlspecialchars($t['nombre']) ?> (<?= $t['duracion_minutos'] ?> min)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Estado de la Cita:</label>
                <select name="id_estado_cita" required>
                    <option value="1" <?= selected(1, $cita['id_estado_cita']) ?>>Planificada</option>
                    <option value="2" <?= selected(2, $cita['id_estado_cita']) ?>>Confirmada</option>
                    <option value="3" <?= selected(3, $cita['id_estado_cita']) ?>>Consulta</option>
                    <option value="4" <?= selected(4, $cita['id_estado_cita']) ?>>Finalizada</option>
                    <option value="5" <?= selected(5, $cita['id_estado_cita']) ?>>Fallada</option>
                    <option value="6" <?= selected(6, $cita['id_estado_cita']) ?>>OK Recordatorio</option>
                </select>
            </div>
        </div>
        <div class="form-row-flex">
            <div class="form-group">
                <label>Duración (minutos):</label>
                <input type="number" name="duracion_minutos" id="duracion_minutos"
                    min="5" step="5"
                    value="<?= htmlspecialchars($cita["hora_fin"] && $cita["hora_inicio"]
                            ? (strtotime($cita["hora_fin"]) - strtotime($cita["hora_inicio"])) / 60
                            : 30) ?>">
            </div>
            <div class="form-group">
                <label>Observaciones:</label>
                <input type="text" name="observaciones" value="<?= htmlspecialchars($cita["observaciones"]) ?>">
            </div>
        </div>
        <?php if (!empty($msg_error)): ?>
            <div style="color: red;"><?= htmlspecialchars($msg_error) ?></div>
        <?php endif; ?>
        <div class="form-group">
            <button type="submit" class="primary-btn">Guardar Cambios</button>
            <button type="button" class="secondary-btn" onclick="window.close()">Cancelar</button>
        </div>
    </form>
</main>
</body>
</html>