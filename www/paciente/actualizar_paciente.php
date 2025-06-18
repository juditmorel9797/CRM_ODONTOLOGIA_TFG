<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
?>

<?php
// Librería de conexión
include("../assets/PHP/crm_lib.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

// Recolección de datos del formulario
$id = $_POST['id'];
$nombre = $_POST['nombre'];
$apellido1 = $_POST['apellido1'];
$apellido2 = $_POST['apellido2'];
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$dni = $_POST['dni'];


$telefono = $_POST['telefono'];
$correo = $_POST['correo'];
$direccion = $_POST['direccion'];
$cp = $_POST['cp'];
$provincia = $_POST['provincia'];
$localidad = $_POST['localidad'];
$sexo = $_POST['sexo'];
$id_referido = $_POST['id_referido'];

// Confirmación visual para evitar cambios accidentales
echo "<script type=\"text/javascript\">";
echo "if (!confirm(\"¿Estás seguro de que deseas guardar los cambios?\")) window.history.back();";
echo "</script>";

// Consulta de actualización
$sql = "UPDATE paciente SET
            nombre = '$nombre',
            apellido1 = '$apellido1',
            apellido2 = '$apellido2',
            fecha_nacimiento = '$fecha_nacimiento',
            dni = '$dni',
            telefono = '$telefono',
            correo = '$correo',
            direccion = '$direccion',
            cp = '$cp',
            provincia = '$provincia',
            localidad = '$localidad',
            sexo = '$sexo',
            id_referido = '$id_referido'
        WHERE id = '$id'";

// Ejecutar y verificar el resultado
if ($conn->query($sql) === TRUE) {
    // Cierra la ventana emergente y recarga la principal
    echo "<script type=\"text/javascript\">window.close(); opener.location.reload();</script>";
    exit();
} else {
    echo "Error al actualizar: " . $conn->error;
}

$conn->close();
?>
