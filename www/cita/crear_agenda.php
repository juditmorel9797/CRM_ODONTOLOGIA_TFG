<?php
include("../includes/menuagenda.php");
include("../assets/PHP/crm_lib.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

$mensaje = "";

// Obtener doctores con perfil 'doctor'
$sql_doctores = "SELECT u.id, u.nombre_visible 
                 FROM usuario u 
                 JOIN perfil p ON u.id_perfil = p.id 
                 WHERE LOWER(p.nombre) = 'doctor'";
$doctores = $conn->query($sql_doctores);

// Obtener franjas horarias
$sql_franjas = "SELECT id, hora_inicio, hora_fin 
                FROM franja_horaria 
                ORDER BY hora_inicio";
$result_franjas = $conn->query($sql_franjas);
$franjas = [];
while ($row = $result_franjas->fetch_assoc()) {
    $franjas[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_agenda = generate_uuid_v4();
    $id_doctor = $_POST["id_doctor"];
    $nombre_agenda = trim($_POST["nombre_agenda"]);
    $dias = $_POST["dias"] ?? [];

    // Insertar en agenda
    $dias_laborales = implode(",", $dias);
    $stmt = $conn->prepare("INSERT INTO agenda (id, id_doctor, nombre_agenda, dias_laborales, activo) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $id_agenda, $id_doctor, $nombre_agenda, $dias_laborales);

    if ($stmt->execute()) {
        // Insertar relaciones doctor_franja por cada día y franja seleccionada
        foreach ($dias as $dia) {
            $campo_franjas = "franjas_" . $dia;
            if (!empty($_POST[$campo_franjas])) {
                foreach ($_POST[$campo_franjas] as $id_franja) {
                    $stmt_f = $conn->prepare("INSERT INTO doctor_franja (id_doctor, id_franja, dia, activo) VALUES (?, ?, ?, 1)");
                    $stmt_f->bind_param("sis", $id_doctor, $id_franja, $dia);
                    $stmt_f->execute();
                }
            }
        }
        $mensaje = "Agenda creada correctamente.";
    } else {
        $mensaje = "Error al crear la agenda: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Agenda</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script>
        function toggleFranjas(dia) {
            const check = document.getElementById("check_" + dia);
            const contenedor = document.getElementById("franjas_" + dia);
            contenedor.style.display = check.checked ? "block" : "none";
        }
    </script>
</head>
<body>
<main class="container">
    <h1>Crear Agenda</h1>
    <?php if (!empty($mensaje)) echo "<p><strong>$mensaje</strong></p>"; ?>
    <form method="post" class="form-card">
        <div class="form-group">
            <label>Doctor:</label>
            <select name="id_doctor" required>
                <option value="">-- Seleccione --</option>
                <?php while ($doc = $doctores->fetch_assoc()): ?>
                    <option value="<?= $doc['id'] ?>"><?= $doc['nombre_visible'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Nombre de la agenda:</label>
            <input type="text" name="nombre_agenda" required>
        </div>

        <div class="form-group">
            <label>Días Laborales y Franjas:</label><br>
            <?php
            $dias_semana = ["L" => "Lunes", "M" => "Martes", "X" => "Miércoles", "J" => "Jueves", "V" => "Viernes", "S" => "Sábado"];
            foreach ($dias_semana as $codigo => $nombre):
            ?>
                <label>
                    <input type="checkbox" id="check_<?= $codigo ?>" name="dias[]" value="<?= $codigo ?>" onclick="toggleFranjas('<?= $codigo ?>')">
                    <?= $nombre ?>
                </label><br>
                <div id="franjas_<?= $codigo ?>" style="display:none; margin-left:20px;">
                    <label>Franjas para <?= $nombre ?>:</label><br>
                    <select name="franjas_<?= $codigo ?>[]" multiple size="5">
                        <?php foreach ($franjas as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= substr($f['hora_inicio'], 0, 5) ?> - <?= substr($f['hora_fin'], 0, 5) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-group">
            <button type="submit" class="primary-btn">Crear Agenda</button>
        </div>
    </form>
</main>
</body>
</html>