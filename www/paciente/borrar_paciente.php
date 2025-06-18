<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
?>

<?php
include("../assets/PHP/crm_lib.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

$id = $_POST['id'];

$sql = "DELETE FROM paciente WHERE id = '$id'";
if ($conn->query($sql) === TRUE) {
    header("Location: index.php");
    exit();
} else {
    echo "Error al borrar: " . $conn->error;
}

$conn->close();
?>
