<?php
include("../assets/PHP/crm_lib.php");
include("../includes/header.php");

// Solo admins pueden acceder
if (!isset($_SESSION["usuario"]) || $_SESSION["perfil"] != 1) {
    echo "<h2>Acceso denegado. Solo disponible para administradores.</h2>";
    exit;
}

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

$mensaje = "";

// Crear nueva tarifa si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nombre'])) {
    $id_tarifa = generate_uuid_v4();
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion'] ?? '');

    $sql_insert = "INSERT INTO tarifa (id, nombre, descripcion) VALUES ('$id_tarifa', '$nombre', '$descripcion')";
    if ($conn->query($sql_insert)) {
        $mensaje = "Tarifa creada correctamente.";
    } else {
        $mensaje = "Error al insertar tarifa: " . $conn->error;
    }
}

// Listar tarifas existentes
$tarifas = $conn->query("SELECT id, nombre, descripcion FROM tarifa ORDER BY nombre ASC");
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Tarifas - CDH CRM</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">

    <!-- Botón de regreso a la pantalla principal del CRM -->
    <button class="btn-back" onclick="window.location.href='../index.php'">
        <img src="../assets/images/back3.png" alt="Volver">
        Volver
    </button>

    <h1>Crear Nueva Tarifa para Presupuestos</h1>

    <?php if (!empty($mensaje)): ?>
        <p><strong><?= htmlspecialchars($mensaje) ?></strong></p>
    <?php endif; ?>

    <!-- Formulario nueva tarifa -->
    <form method="post" class="card presupuesto-card">
        <label>Nombre de la Tarifa <span style="color: red;">*</span>:</label>
        <input type="text" name="nombre" required>

        <label>Descripción (opcional):</label>
        <textarea name="descripcion" rows="2" style="width: 100%;"></textarea>
        <br>
        <input type="submit" value="Crear Tarifa" class="primary-btn">
    </form>

    <hr>
    <h2>Tarifas Existentes</h2>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($tarifas && $tarifas->num_rows > 0): ?>
                <?php while ($t = $tarifas->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['nombre']) ?></td>
                        <td><?= nl2br(htmlspecialchars($t['descripcion'])) ?></td>
                        <td>
                            <a href="agregar_tarifa.php?id=<?= $t['id'] ?>" class="view-btn">Ver/Añadir Tratamientos</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3">No hay tarifas registradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>