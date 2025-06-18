<?php
session_start();
include("../assets/PHP/crm_lib.php");

if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit;
}

$conn = conecta_db();
if ($conn === "KO") {
    echo "Error de conexión";
    exit;
}

$id_paciente = $_GET["id_paciente"] ?? $_POST["id_paciente"] ?? null;
if (!$id_paciente) {
    echo "Falta id_paciente";
    exit;
}

// Subida de imagen sin análisis
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['imagen'])) {
    if ($_FILES['imagen']['error'] === 0) {
        $image_data = file_get_contents($_FILES['imagen']['tmp_name']);
        $imagen_base64 = base64_encode($image_data);

        // Fecha del sistema (automática)
        $fecha = (new DateTime())->format('Y-m-d');

        // Insertar la radiografía
        $stmt = $conn->prepare("INSERT INTO radiografias (id_paciente, fecha, imagen_base64) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $id_paciente, $fecha, $imagen_base64);
        $stmt->execute();
        $stmt->close();

        // Redirige con mensaje de éxito
        header("Location: subir_y_analizar_radiografia.php?id_paciente=$id_paciente&ok=1");
        exit;
    } else {
        header("Location: subir_y_analizar_radiografia.php?id_paciente=$id_paciente&error=1");
        exit;
    }
}

// Obtener historial de radiografías del paciente
$sql_rad = "SELECT id, fecha, imagen_base64 FROM radiografias WHERE id_paciente = ? ORDER BY fecha DESC";
$stmt = $conn->prepare($sql_rad);
$stmt->bind_param("s", $id_paciente);
$stmt->execute();
$result_rad = $stmt->get_result();
?>

<!-- ...tu código PHP de antes (no cambia)... -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Radiografía</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script type="text/javascript" src="../assets/JS/crm_lib.js"></script>
</head>
<body>
<main class="container">

    <section id="radiografias" class="tab-content active">
        <h2>Subir Radiografía</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id_paciente" value="<?= htmlspecialchars($id_paciente) ?>">
            <label>Archivo:</label>
            <input type="file" name="imagen" accept=".jpg, .jpeg, .png" required>
            <input type="submit" value="Subir Imagen" class="primary-btn">
        </form>

        <h2>Historial de Radiografías</h2>
        <table>
            <thead>
                <tr><th>Fecha</th><th>Imagen</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if ($result_rad->num_rows > 0): 
                    while ($row = $result_rad->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['fecha'] ?></td>
                        <td><img src="data:image/png;base64,<?= $row['imagen_base64'] ?>" width="100"></td>
                        <td>
                            <a href="../IA/llamar_ia.php?id_radiografia=<?= $row['id'] ?>" class="secondary-btn">Solicitar Diagnóstico</a>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="3">No hay radiografías registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<!-- Loader/mensaje de diagnóstico (dejamos el div aquí, lo controlamos desde el JS global) -->
<div id="diagnostico-loader" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(127, 147, 228, 0.7);z-index:9999;justify-content:center;align-items:center;font-size:2em;color:#333;">
    Consultando Diagnóstico…no recargue la página por favor. Se redirigirá automáticamente.
</div>
</body>
</html>