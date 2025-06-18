<?php
include("../assets/PHP/crm_lib.php");
include("../includes/header.php");

$conn = conecta_db();
if ($conn == "KO") die("Error de conexión");

$id_usuario = $_GET['id'] ?? '';

// Al enviar el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nuevo_perfil = $_POST['perfil'];
    $sql = "UPDATE usuario SET id_perfil = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $nuevo_perfil, $id_usuario);

    if ($stmt->execute()) {
        header("Location: listado_usuarios.php?modificado=1");
        exit;
    } else {
        $error = "Error al modificar el perfil.";
    }
    $stmt->close();
}

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT user_name, id_perfil FROM usuario WHERE id = ?");
$stmt->bind_param("s", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Permiso - CDH CRM</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<main class="container">
    <h1>Modificar Permiso</h1>
    <p>Usuario: <strong><?= htmlspecialchars($usuario['user_name']) ?></strong></p>
    <form method="post">
        <label>Nuevo Perfil:</label>
        <select name="perfil" required>
            <option value="1" <?= $usuario['id_perfil'] == 1 ? 'selected' : '' ?>>Administrador</option>
            <option value="2" <?= $usuario['id_perfil'] == 2 ? 'selected' : '' ?>>Ventas</option>
            <option value="3" <?= $usuario['id_perfil'] == 3 ? 'selected' : '' ?>>Doctor</option>
            <option value="4" <?= $usuario['id_perfil'] == 4 ? 'selected' : '' ?>>Recepción</option>
            <option value="5" <?= $usuario['id_perfil'] == 5 ? 'selected' : '' ?>>Auxiliar</option>
        </select><br><br>
        <input type="submit" value="Guardar Cambios" class="primary-btn">
        <a href="listado_usuarios.php" class="secondary-btn">Cancelar</a>
    </form>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</main>

</body>
</html> 