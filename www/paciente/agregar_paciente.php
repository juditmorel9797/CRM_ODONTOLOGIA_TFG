<?php
include("../assets/PHP/crm_lib.php");
include("../includes/header.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

// Cargar lista de referidos desde la tabla referidos
$sql_referidos = "SELECT id, nombre FROM referido ORDER BY nombre ASC";
$result_referidos = $conn->query($sql_referidos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Paciente - CDH CRM</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<main class="container">
    <h1>Añadir Nuevo Paciente</h1>

    <form action="insertar_paciente.php" method="post">
        <div class="ficha-section">
            <!-- Datos personales -->
            <div class="ficha-card">
                <h3>Datos Personales</h3>
                <label>Nombres:</label>
                <input type="text" name="nombre" required>
                <label>Primer Apellido:</label>
                <input type="text" name="apellido1" required>
                <label>Segundo Apellido:</label>
                <input type="text" name="apellido2">
            </div>

            <!-- Identificación -->
            <div class="ficha-card">
                <h3>Identificación</h3>
                <label>Fecha de nacimiento:</label>
                <input type="date" name="fecha_nacimiento" required>
                <label>DNI/NIE/PAS:</label>
                <input type="text" name="dni" required>
            </div>

            <!-- Contacto -->
            <div class="ficha-card">
                <h3>Contacto</h3>
                <label>Teléfono:</label>
                <input type="text" name="telefono" maxlength="15">
                <label>Correo electrónico:</label>
                <input type="email" name="correo" maxlength="100">
            </div>

            <!-- Dirección -->
            <div class="ficha-card">
                <h3>Dirección</h3>
                <label>Dirección:</label>
                <input type="text" name="direccion" maxlength="255" style="width: 100%;">
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <div style="flex: 1;">
                        <label>Código Postal:</label>
                        <input type="text" name="cp" maxlength="5">
                    </div>
                    <div style="flex: 1;">
                        <label>Provincia:</label>
                        <input type="text" name="provincia" maxlength="100">
                    </div>
                    <div style="flex: 1;">
                        <label>Localidad:</label>
                        <input type="text" name="localidad" maxlength="100">
                    </div>
                </div>
            </div>

            <!-- Otros Datos -->
            <div class="ficha-card">
                <h3>Otros Datos</h3>
                <label>Sexo:</label>
                <select name="sexo">
                    <option value="">Seleccione</option>
                    <option value="M">M</option>
                    <option value="F">F</option>
                </select>

                <label>Referido por:</label>
                <select name="id_referido">
                    <option value="">Seleccione</option>
                    <?php while ($ref = $result_referidos->fetch_assoc()): ?>
                        <option value="<?= $ref['id'] ?>"><?= $ref['nombre'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <br>
        <input type="submit" value="Guardar Paciente" class="primary-btn">
    </form>
</main>
</body>
</html>