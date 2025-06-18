<?php
include("../assets/PHP/crm_lib.php");
include("../includes/header.php");
function genera_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = strtolower(trim($_POST['usuario']));
    $password = $_POST['password'];
    $perfil = $_POST['perfil'];
    $nombre_visible = trim($_POST['nombre_visible']);

    // Validación de formato
    if (!preg_match('/^[a-z0-9_]+$/', $usuario)) {
        $mensaje = "ERROR: Formato de nombre de usuario inválido. Solo letras minúsculas, números y guiones bajos.";
    } else {
        $conn = conecta_db();
        if ($conn == "KO") {
            die("Error de conexión a la base de datos");
        }

        // Comprobar duplicado
        $check = $conn->prepare("SELECT id FROM usuario WHERE user_name = ?");
        $check->bind_param("s", $usuario);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $mensaje = "ERROR: Ese nombre de usuario ya está en uso.";
        } else {
            $uuid = genera_uuid();
            $hash = substr(md5($password), 0, 15);

            $sql = "INSERT INTO usuario (id, user_name, password, id_perfil, nombre_visible) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssis", $uuid, $usuario, $hash, $perfil, $nombre_visible);

            if ($stmt->execute()) {
                $mensaje = "Usuario creado correctamente.";
            } else {
                $mensaje = "Error al crear usuario: " . $stmt->error;
            }
            $stmt->close();
        }

        $check->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alta Usuario - CDH CRM</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script type="text/javascript" src="../assets/JS/crm_lib.js"></script>
</head>

<body>
<main class="container">
    <h1>Alta de Usuarios</h1>

    <?php if ($mensaje && strpos($mensaje, 'correctamente') === false): ?>
        <p><strong style="color: red;"><?php echo $mensaje; ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <div class="ficha-section">
            <div class="ficha-card">
                <label>Nombre de Usuario (login):</label>
                <input type="text" name="usuario" required>
                <small style="display: block; margin-top: 6px;">
                    El nombre de usuario debe escribirse en minúsculas, sin espacios y con guión bajo. Ejemplo: <strong>nombreusuario_apellido</strong>
                </small>

                <label>Nombre visible del usuario:</label>
                <input type="text" name="nombre_visible" placeholder="Nombres y apellidos que serán visibles" required>

                <label>Contraseña:</label>
                <input type="password" name="password" required>

                <label>Perfil:</label>
                <select name="perfil" required>
                    <option value="">Seleccione perfil</option>
                    <option value="1">Administrador</option>
                    <option value="2">Ventas</option>
                    <option value="3">Doctor</option>
                    <option value="4">Recepción</option>
                    <option value="5">Auxiliar</option>
                </select>
            </div>
        </div>
        <br>
        <input type="submit" value="Crear Usuario" class="primary-btn">
    </form>
</main>

<?php if ($mensaje && strpos($mensaje, 'correctamente') !== false): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        mostrarModalCreacionUsuario();
    });
</script>
<?php endif; ?>

</body>
</html>