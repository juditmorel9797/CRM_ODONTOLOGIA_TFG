<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("../assets/PHP/crm_lib.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

$id = $_GET['id'];

// Obtener datos del paciente y nombre del referido
$sql = "SELECT p.*, r.nombre AS nombre_referido 
        FROM paciente p 
        LEFT JOIN referido r ON p.id_referido = r.id 
        WHERE p.id = '$id'";
$result = $conn->query($sql);
$paciente = $result->fetch_assoc();

// Obtener todos los referidos para el <select>
$sql_ref = "SELECT * FROM referido ORDER BY nombre";
$ref_result = $conn->query($sql_ref);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Paciente</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">
    <h1>Editar Paciente</h1>

    <form action="actualizar_paciente.php" method="post">
        <input type="hidden" name="id" value="<?= $paciente['id'] ?>">

        <div class="ficha-section">
            <!-- Datos Personales -->
            <div class="ficha-card">
                <h3>Datos Personales</h3>
                <label>Nombres:</label>
                <input type="text" name="nombre" value="<?= $paciente['nombre'] ?>" required>
                <label>Primer Apellido:</label>
                <input type="text" name="apellido1" value="<?= $paciente['apellido1'] ?>" required>
                <label>Segundo Apellido:</label>
                <input type="text" name="apellido2" value="<?= $paciente['apellido2'] ?>">
                <label>Fecha de nacimiento:</label>
                <input type="date" name="fecha_nacimiento" value="<?= $paciente['fecha_nacimiento'] ?>" required>
                <label>DNI/NIE/PAS:</label>
                <input type="text" name="dni" value="<?= $paciente['dni'] ?>" required>
            </div>

            <!-- Contacto -->
            <div class="ficha-card">
                <h3>Contacto</h3>
                <label>Teléfono:</label>
                <input type="text" name="telefono" value="<?= $paciente['telefono'] ?>">
                <label>Correo electrónico:</label>
                <input type="email" name="correo" value="<?= $paciente['correo'] ?>">
            </div>

            <!-- Dirección -->
            <div class="ficha-card">
                <h3>Dirección</h3>
                <label>Dirección:</label>
                <input type="text" name="direccion" value="<?= $paciente['direccion'] ?>">
                <label>Código Postal:</label>
                <input type="text" name="cp" value="<?= $paciente['cp'] ?>">
                <label>Provincia:</label>
                <input type="text" name="provincia" value="<?= $paciente['provincia'] ?>">
                <label>Localidad:</label>
                <input type="text" name="localidad" value="<?= $paciente['localidad'] ?>">
            </div>

            <!-- Otros -->
            <div class="ficha-card">
                <h3>Otros Datos</h3>
                <label>Sexo:</label>
                <select name="sexo">
                    <option value="">Seleccione</option>
                    <option value="M" <?= $paciente['sexo'] == 'M' ? 'selected' : '' ?>>M</option>
                    <option value="F" <?= $paciente['sexo'] == 'F' ? 'selected' : '' ?>>F</option>
                </select>

                <label>Referido por:</label>
                <select name="id_referido">
                    <option value="">Seleccione</option>
                    <?php while ($row = $ref_result->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= ($row['id'] == $paciente['id_referido']) ? 'selected' : '' ?>>
                            <?= $row['nombre'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <!-- Botón -->
        <br>
        <input type="submit" value="Guardar cambios" class="primary-btn">
        <a href="consulta_pacientes.php" class="secondary-btn">Volver</a>
    </form>
</main>
</body>
</html>
                        