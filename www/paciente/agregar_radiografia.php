<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
?>

<?php
$id_paciente = $_GET['id_paciente'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Radiografía</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <main class="container">
        <h1>Subir Radiografía</h1>
        <form action="insertar_radiografia.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id_paciente" value="<?= $id_paciente ?>">

            <label for="tipo">Tipo de estudio:</label>
            <select name="tipo" required>
                <option value="radiografia">Radiografía</option>
                <option value="scanner_intraoral">Escáner Intraoral</option>
            </select><br>

            <label for="fecha">Fecha:</label>
            <input type="date" name="fecha" required><br>

            <label for="imagen">Imagen (PNG/JPG):</label>
            <input type="file" name="imagen" accept="image/*" required><br><br>

            <input type="submit" value="Subir Radiografía" class="primary-btn">
        </form>
        <br>
        <a href="index.php">Volver al listado</a>
    </main>
</body>
</html>
