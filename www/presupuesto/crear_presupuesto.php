<?php
include("../assets/PHP/crm_lib.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

$id_paciente = $_GET['id_paciente'] ?? '';
if ($id_paciente === '') {
    echo "ID de paciente no proporcionado.";
    exit;
}

// Obtener datos del paciente
$sql_paciente = "SELECT nhc, nombre, apellido1, apellido2 FROM paciente WHERE id = '$id_paciente'";
$result_paciente = $conn->query($sql_paciente);
$paciente = $result_paciente->fetch_assoc();

// Obtener tarifas disponibles
$sql_tarifas = "SELECT id, nombre FROM tarifa ORDER BY nombre";
$result_tarifas = $conn->query($sql_tarifas);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Presupuesto</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">
    <button class="btn-back" onclick="window.history.back()">
        <img src="../assets/images/back3.png" alt="Volver"> Volver
    </button>
    </br>
    <div class="card presupuesto-card">
        <h2>Nuevo Presupuesto para <?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido1']) ?> (NHC <?= $paciente['nhc'] ?>)</h2>
        <form action="insertar_presupuesto.php" method="post" id="formPresupuesto">
            <input type="hidden" name="id_paciente" value="<?= $id_paciente ?>">

            <div class="form-row">
                <label><strong>Tarifa:</strong></label>
                <select name="id_tarifa" id="tarifa" required>
                    <option value="">Seleccione Tarifa</option>
                    <?php while ($row = $result_tarifas->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div id="tratamientos_contenedor"></div>

            <div id="totalPresupuesto" class="subtotal">Total: 0.00 €</div>

            <label>Observaciones:</label>
            <textarea name="observaciones" rows="2"></textarea>
            <br>
            <input type="submit" value="Guardar Presupuesto" class="primary-btn">
        </form>
    </div>
</main>
<script src="../assets/JS/crm_lib.js"></script>
</body>
</html>
