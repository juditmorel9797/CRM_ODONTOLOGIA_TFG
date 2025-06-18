<?php
session_start();
include("../assets/PHP/crm_lib.php");
$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

$id_presupuesto = $_GET['id'] ?? '';
$id_paciente = $_GET['id_paciente'] ?? '';
if ($id_presupuesto === '') {
    echo "ID de presupuesto no proporcionado.";
    exit;
}

// Traer datos del presupuesto
$sql = "SELECT * FROM presupuesto WHERE uuid = '$id_presupuesto'";
$res = $conn->query($sql);
$presupuesto = $res->fetch_assoc();
if (!$presupuesto) {
    echo "Presupuesto no encontrado.";
    exit;
}

// Traer tratamientos ya asignados a este presupuesto
$sql_pt = "SELECT * FROM presupuesto_tratamiento WHERE id_presupuesto = '$id_presupuesto'";
$res_pt = $conn->query($sql_pt);
$tratamientos_actuales = [];
while ($row = $res_pt->fetch_assoc()) {
    $tratamientos_actuales[$row['id_tratamiento']] = [
        'cantidad' => $row['cantidad'],
        'diente' => $row['diente']
    ];
}

// Procesar POST (guardar cambios)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tarifa = $_POST['id_tarifa'];
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');

    // Actualiza cabecera de presupuesto
    $sql_update = "UPDATE presupuesto SET id_tarifa = '$id_tarifa', observaciones = '$observaciones' WHERE uuid = '$id_presupuesto'";
    if (!$conn->query($sql_update)) {
        echo "Error al actualizar presupuesto: " . $conn->error;
        exit;
    }

    // Eliminar tratamientos anteriores
    $conn->query("DELETE FROM presupuesto_tratamiento WHERE id_presupuesto = '$id_presupuesto'");

    // Insertar los nuevos tratamientos seleccionados
    $tratamientos = $_POST['tratamiento'] ?? [];
    $dientes = $_POST['diente'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $precios = $_POST['precio'] ?? [];

    for ($i = 0; $i < count($tratamientos); $i++) {
        $uuid_pt = generate_uuid_v4();
        $id_tratamiento = $tratamientos[$i];
        $diente = isset($dientes[$i]) && $dientes[$i] !== "" ? "'" . $conn->real_escape_string($dientes[$i]) . "'" : "NULL";
        $cantidad = intval($cantidades[$i] ?? 1);
        $precio_unitario = floatval($precios[$i] ?? 0);

        $sql_pt = "INSERT INTO presupuesto_tratamiento (
            uuid, id_presupuesto, id_tratamiento, diente, cantidad, precio_unitario
        ) VALUES (
            '$uuid_pt', '$id_presupuesto', '$id_tratamiento', $diente, $cantidad, $precio_unitario
        )";
        if (!$conn->query($sql_pt)) {
            echo "Error al insertar tratamiento: " . $conn->error;
            exit;
        }
    }

    // Redirige a detalle
    header("Location: detalle_presupuesto.php?id=$id_presupuesto&id_paciente=$id_paciente");
    exit;
}

// Traer todas las tarifas
$sql_tarifas = "SELECT id, nombre FROM tarifa ORDER BY nombre";
$res_tarifas = $conn->query($sql_tarifas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Presupuesto</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">
    <!-- Botón de regreso -->
    <button class="btn-back" onclick="window.location.href='detalle_presupuesto.php?id=<?= $id_presupuesto ?>&id_paciente=<?= urlencode($id_paciente) ?>'">
        <img src="../assets/images/back3.png" alt="Volver"> Volver
    </button>
    <h2>Editar Presupuesto</h2>
    <form method="post" id="formPresupuesto">
        <label>Tarifa:</label>
        <select name="id_tarifa" id="tarifa" required>
            <?php while ($row = $res_tarifas->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= $row['id'] == $presupuesto['id_tarifa'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <label>Observaciones:</label>
        <textarea name="observaciones" rows="2"><?= htmlspecialchars($presupuesto['observaciones']) ?></textarea>

        <div id="tratamientos_contenedor"></div>

        <div id="totalPresupuesto" class="subtotal">Total: 0.00 €</div>
        <br>
        <button type="submit" class="primary-btn">Guardar Cambios</button>
        <a href="detalle_presupuesto.php?id=<?= $id_presupuesto ?>&id_paciente=<?= urlencode($id_paciente) ?>" class="secondary-btn">Cancelar</a>
    </form>
</main>
<script>
const tratamientos_actuales = <?= json_encode($tratamientos_actuales) ?>;
</script>
<script src="../assets/JS/crm_lib.js"></script>
<script>
// Al cargar la página, cargar los tratamientos de la tarifa (y marcar los que ya estaban)
document.addEventListener('DOMContentLoaded', function() {
    const id_tarifa = document.getElementById('tarifa').value;
    if (id_tarifa) {
        cargarTratamientos(id_tarifa);
    }
    document.getElementById('tarifa').addEventListener('change', function() {
        cargarTratamientos(this.value);
    });
});

function cargarTratamientos(id_tarifa) {
    fetch('ajax_tratamientos_tarifa.php?id_tarifa=' + encodeURIComponent(id_tarifa))
        .then(response => response.text())
        .then(html => {
            document.getElementById('tratamientos_contenedor').innerHTML = html;

            // Marcar y rellenar tratamientos ya seleccionados en este presupuesto
            document.querySelectorAll('.tratamiento-row').forEach(function(row, i) {
                const checkbox = row.querySelector('input[type=checkbox]');
                const inputDiente = row.querySelector('input[name="diente[]"]');
                const inputCantidad = row.querySelector('input[name="cantidad[]"]');
                const id_tratamiento = checkbox.value;

                if (tratamientos_actuales[id_tratamiento]) {
                    checkbox.checked = true;
                    if (inputDiente) inputDiente.value = tratamientos_actuales[id_tratamiento]['diente'] || '';
                    if (inputCantidad) {
                        inputCantidad.value = tratamientos_actuales[id_tratamiento]['cantidad'];
                        inputCantidad.disabled = false;
                    }
                    // Si el tratamiento requiere diente, mostrar selector
                    const selectorDientes = row.querySelector('.selector-dientes');
                    if (selectorDientes) {
                        selectorDientes.style.display = "block";
                        inputCantidad.readOnly = true;
                    }
                }
            });

            // Vuelve a enganchar listeners para recalcular total
            if (window.actualizaTotalPresupuesto) {
                window.actualizaTotalPresupuesto();
            }
            document.querySelectorAll('.tratamiento-row input[name="cantidad[]"]').forEach(function(input) {
                input.addEventListener('input', window.actualizaTotalPresupuesto);
            });
        });
}
</script>
</body>
</html>