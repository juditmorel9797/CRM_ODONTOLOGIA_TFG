<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("../assets/PHP/crm_lib.php");
$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

// Generar el NHC como el máximo actual + 1
$nhc_result = $conn->query("SELECT MAX(nhc) as max_nhc FROM paciente");
$next_nhc = 1;
if ($nhc_result && $row = $nhc_result->fetch_assoc()) {
    $next_nhc = ((int)$row['max_nhc']) + 1;
}

// Recoger datos del formulario
$id = generate_uuid_v4();
$nombre = $_POST['nombre'] ?? '';
$apellido1 = $_POST['apellido1'] ?? '';
$apellido2 = $_POST['apellido2'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$dni = $_POST['dni'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$correo = $_POST['correo'] ?? '';
$direccion = $_POST['direccion'] ?? '';
$cp = $_POST['cp'] ?? '';
$provincia = $_POST['provincia'] ?? '';
$localidad = $_POST['localidad'] ?? '';
$sexo = $_POST['sexo'] ?? '';

// Verificar si se ha enviado un ID de referido válido
$id_referido = isset($_POST['id_referido']) && $_POST['id_referido'] !== '' ? intval($_POST['id_referido']) : "NULL";

// Construir consulta SQL (nota: sin comillas alrededor de $id_referido si es NULL)
$sql = "INSERT INTO paciente (
            id, nombre, apellido1, apellido2, fecha_nacimiento, dni,
            telefono, correo, direccion, cp, provincia,
            localidad, sexo, id_referido, nhc
        ) VALUES (
            '$id', '$nombre', '$apellido1', '$apellido2', '$fecha_nacimiento', '$dni',
            '$telefono', '$correo', '$direccion', '$cp', '$provincia',
            '$localidad', '$sexo', $id_referido, '$next_nhc'
        )";

if ($conn->query($sql) === TRUE) {
        // modifico para que al crear el paciente, se proceda a redirigir directamente al perfil del nuevo paciente
    header("Location: perfil_paciente.php?id=$id");
    exit();
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>