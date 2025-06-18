<?php
session_start();
include("../assets/PHP/crm_lib.php");
$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

$id_presupuesto = $_GET['id'] ?? '';
if ($id_presupuesto === '') {
    echo "ID de presupuesto no proporcionado.";
    exit;
}

// Trae presupuesto actual (para confirmar)
$sql = "SELECT uuid, id_paciente FROM presupuesto WHERE uuid = '$id_presupuesto'";
$res = $conn->query($sql);
$presupuesto = $res->fetch_assoc();

if (!$presupuesto) {
    echo "Presupuesto no encontrado.";
    exit;
}

// Eliminar si se ha confirmado por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar']) && $_POST['confirmar'] === "SI") {
    // Elimina los tratamientos asociados (ON DELETE CASCADE planificado  en la tabla)
    $conn->query("DELETE FROM presupuesto WHERE uuid = '$id_presupuesto'");
    header("Location: presupuestos_paciente.php?id_paciente=" . $presupuesto['id_paciente']);
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Presupuesto</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">
    <h2>¿Estás seguro de que quieres eliminar este presupuesto?</h2>
    <form method="post">
        <input type="hidden" name="confirmar" value="SI">
        <button type="submit" class="delete-btn">Sí, eliminar</button>
        <a href="detalle_presupuesto.php?id=<?= $id_presupuesto ?>" class="secondary-btn">Cancelar</a>
    </form>
</main>
</body>
</html>