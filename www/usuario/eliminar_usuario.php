
<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["perfil"] != 1) {
    header("Location: login.php");
    exit;
}
include("../assets/PHP/crm_lib.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id_usuario = $_GET['id'];

    $conn = conecta_db();
    if ($conn == "KO") {
        die("Error de conexión a la base de datos");
    }

    // Borramos el usuario
    $sql = "DELETE FROM usuario WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_usuario);

    if ($stmt->execute()) {
        echo "<script>
            alert('Usuario eliminado correctamente.');
            window.location.href = 'listado_usuarios.php';
        </script>";
    } else {
        echo "<script>
            alert('Error al eliminar el usuario.');
            window.location.href = 'listado_usuarios.php';
        </script>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: listado_usuarios.php");
    exit;
}
?>
