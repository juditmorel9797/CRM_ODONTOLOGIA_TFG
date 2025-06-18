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

$id_paciente = $_GET["id_paciente"] ?? null;
if (!$id_paciente) {
    echo "Falta id_paciente";
    exit;
}

// Obtener todos los diagnósticos con info de radiografía
$sql = "SELECT d.*, r.fecha AS fecha_radiografia
        FROM diagnostico d
        JOIN radiografias r ON d.id_radiografia = r.id
        WHERE d.id_paciente = ?
        ORDER BY d.fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_paciente);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnósticos IA</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script type="text/javascript" src="../assets/JS/crm_lib.js"></script>
</head>
<body>
<main class="container">
    <section id="diagnosticos" class="tab-content active">
        <h2>Diagnósticos IA</h2>

        <table>
            <thead>
                <tr>
                    <th>Fecha Radiografía</th>
                    <th>Diagnóstico</th>
                    <th>Tratamiento Recomendado</th>
                    <th>Fecha Diagnóstico</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): 
                    while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['fecha_radiografia'] ?></td>
                        <td class="pre-wrap"><?= $row['diagnostico'] ?: '-' ?></td>
                        <td class="pre-wrap"><?= $row['tratamiento_recomendado'] ?: '-' ?></td>
                        <td><?= $row['fecha'] ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4">No hay diagnósticos disponibles.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

</main>
</body>
</html>