<?php
session_start();
include("../assets/PHP/crm_lib.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

// Recolectar datos del formulario
$id_presupuesto = generate_uuid_v4();
$id_paciente = $_POST['id_paciente'];
$id_usuario = $_SESSION['id_usuario'] ?? ''; // Debe ser UUID válido del usuario logueado
$id_tarifa = $_POST['id_tarifa'] ?? null;
$observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
$estado = 'ENTREGADO';

// Recoger tratamientos y detalles desde el formulario
$tratamientos = $_POST['tratamiento'] ?? [];
$dientes = $_POST['diente'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];
$precios = $_POST['precio'] ?? [];

// Validar que hay tratamientos seleccionados
if (empty($tratamientos)) {
    echo "Debes seleccionar al menos un tratamiento.";
    exit;
}

// Insertar presupuesto principal
$sql_presupuesto = "INSERT INTO presupuesto (
    uuid, id_paciente, id_usuario, id_tarifa, estado, observaciones
) VALUES (
    '$id_presupuesto', '$id_paciente', '$id_usuario', '$id_tarifa', '$estado', '$observaciones'
)";

if (!$conn->query($sql_presupuesto)) {
    echo "Error al insertar presupuesto: " . $conn->error;
    exit;
}

// Insertar cada tratamiento asociado
for ($i = 0; $i < count($tratamientos); $i++) {
    $uuid_pt = generate_uuid_v4();
    $id_tratamiento = $tratamientos[$i];
    // Si hay varios dientes seleccionados, irán separados por coma (ejemplo: '14,13,12,21,22,23')
    $diente = isset($dientes[$i]) && $dientes[$i] !== "" ? "'" . $conn->real_escape_string($dientes[$i]) . "'" : "NULL";
    $cantidad = intval($cantidades[$i] ?? 1);
    $precio_unitario = floatval($precios[$i] ?? 0);

    // Seguridad: la cantidad nunca puede ser menor que 1 si el tratamiento está seleccionado
    if ($cantidad < 1) $cantidad = 1;

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

// Redirige al listado de presupuestos del paciente
header("Location: presupuestos_paciente.php?id_paciente=$id_paciente");
exit;
?>