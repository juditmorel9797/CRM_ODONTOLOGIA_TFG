<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
?>

<?php
include("assets/PHP/crm_lib.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

// Generar UUID simple
function generate_uuid_v4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$id = generate_uuid_v4();
$id_paciente = $_POST['id_paciente'];
$tipo = $_POST['tipo'];
$fecha = $_POST['fecha'];

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
    $image_data = file_get_contents($_FILES['imagen']['tmp_name']);
    $imagen_base64 = base64_encode($image_data);

    $sql = "INSERT INTO radiografias (id, id_paciente, tipo, fecha, imagen)
            VALUES ('$id', '$id_paciente', '$tipo', '$fecha', '$imagen_base64')";

    if ($conn->query($sql) === TRUE) {
        header("Location: index.php");
        exit();
    } else {
        echo "Error al insertar: " . $conn->error;
    }
} else {
    echo "Error con la imagen";
}

$conn->close();
?>
