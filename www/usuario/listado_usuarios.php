<?php
include("../assets/PHP/crm_lib.php");
include("../includes/header.php");

$conn = conecta_db();

$sql = "SELECT usuario.id, usuario.user_name, perfil.nombre AS perfil
        FROM usuario
        JOIN perfil ON usuario.id_perfil = perfil.id";

$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Usuarios</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">
    <h1>Listado de Usuarios</h1>

    <table>
        <tr>
            <th>Nombre de Usuario</th>
            <th>Perfil</th>
            <th>Modificar</th>
            <th>Eliminar</th>
        </tr>
        <?php while ($fila = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($fila["user_name"]) ?></td>
                <td><?= htmlspecialchars($fila["perfil"]) ?></td>
                <td>
                    <a href="modificar_permiso.php?id=<?= $fila['id'] ?>">
                        <img src="/assets/images/editW2.png" width="25" title="Modificar Permisos">
                    </a>
                </td>
                <td>
                    <?php if ($fila['user_name'] != 'juditadmin'): ?>
                        <a href="eliminar_usuario.php?id=<?= $fila['id'] ?>" onclick="return confirm('¿Deseas eliminar este usuario?')">
                            <img src="/assets/images/del.png" width="25" title="Eliminar Usuario">
                        </a>
                    <?php else: ?>
                        <span style="color: #888;">Protegido</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</main>
</body>
</html>
