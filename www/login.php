<?php
session_start();
include("assets/PHP/crm_lib.php");

$conn = conecta_db();
if ($conn == "KO") {
    die("Error al conectar con la base de datos.");
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["user"];
    $pass = $_POST["pass"];
    $pass_encriptada = substr(md5($pass), 0, 15);

    $sql = "SELECT * FROM usuario WHERE user_name = ? AND password = ? AND activo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usuario, $pass_encriptada);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $_SESSION["usuario"] = $usuario;
        $_SESSION["id_usuario"] = $row["id"];
        $_SESSION["perfil"] = $row["id_perfil"];
        $_SESSION["nombre_visible"] = $row["nombre_visible"];
        header("Location: index.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - CDH CRM</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script type="text/javascript" src="../assets/JS/crm_lib.js"></script>
</head>
<body>
    <div class="login-container">
        <h2>CDH - CRM</h2>
        <h1>Inicio de sesión</h1>
        <form method="post" action="login.php" autocomplete="off">
            <input type="text" name="user" placeholder="Usuario" required>
            <input type="password" name="pass" id="pass" placeholder="Contraseña" required>
            <div class="show-pass" onclick="togglePass()">Mostrar contraseña</div>
            <input type="submit" value="Entrar" class="primary-btn">
        </form>
        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
    </div>

</body>
</html>