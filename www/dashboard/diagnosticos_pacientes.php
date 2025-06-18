<?php
include("../assets/PHP/crm_lib.php");
include("../includes/header.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

// Filtros
$busqueda = trim($_GET['q'] ?? '');
$fecha    = $_GET['fecha'] ?? '';
$id_paciente = $_GET['id_paciente'] ?? '';

// WHERE dinámico
$where = "1";
if ($busqueda !== '') {
    $busq = $conn->real_escape_string($busqueda);
    $where .= " AND (pac.nombre LIKE '%$busq%' OR pac.apellido1 LIKE '%$busq%' OR pac.nhc LIKE '%$busq%')";
}
if ($fecha !== '') {
    $fecha_sql = $conn->real_escape_string($fecha);
    $where .= " AND d.fecha >= '$fecha_sql 00:00:00' AND d.fecha <= '$fecha_sql 23:59:59'";
}
if ($id_paciente !== '') {
    $id_paciente_sql = $conn->real_escape_string($id_paciente);
    $where .= " AND d.id_paciente = '$id_paciente_sql'";
}

// Consulta diagnósticos con JOIN para datos de paciente y radiografía
$sql = "
    SELECT d.*, r.fecha AS fecha_radiografia, pac.nombre AS paciente, pac.apellido1, pac.apellido2, pac.nhc
    FROM diagnostico d
    LEFT JOIN radiografias r ON d.id_radiografia = r.id
    LEFT JOIN paciente pac ON d.id_paciente = pac.id
    WHERE $where
    ORDER BY d.fecha DESC
    LIMIT 50
";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnósticos IA de Pacientes</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">
    <h2>Diagnósticos IA de Pacientes</h2>
    <form method="get" class="form-inline" style="display:flex;align-items:baseline;gap:10px;">
        <input type="text" name="q" placeholder="Buscar por paciente o NHC" value="<?= htmlspecialchars($busqueda) ?>" style="min-width:200px;">
        <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <button type="submit">Filtrar</button>
    </form>
    <br>

    <table>
        <thead>
            <tr>
                <th>Fecha Diagnóstico</th>
                <th>Paciente</th>
                <th>NHC</th>
                <th>Fecha Radiografía</th>
                <th>Diagnóstico IA</th>
                <th>Tratamiento Recomendado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['fecha'] ? date("d/m/Y H:i", strtotime($row['fecha'])) : '-' ?></td>
                    <td><?= htmlspecialchars($row['paciente'] . ' ' . $row['apellido1'] . ' ' . $row['apellido2']) ?></td>
                    <td><?= htmlspecialchars($row['nhc']) ?></td>
                    <td><?= $row['fecha_radiografia'] ? date("d/m/Y", strtotime($row['fecha_radiografia'])) : '-' ?></td>
                    <td class="pre-wrap" style="max-width:300px;"><?= $row['diagnostico'] ?: '-' ?></td>
                    <td class="pre-wrap" style="max-width:200px;"><?= $row['tratamiento_recomendado'] ?: '-' ?></td>
                    <td>
                        <?php if ($row['id_paciente']): ?>
                            <a href="../paciente/perfil_paciente.php?id=<?= urlencode($row['id_paciente']) ?>" class="view-btn">Ver Paciente</a>
                        <?php endif; ?>
                        <?php if ($row['id_radiografia']): ?>
                            <a href="../IA/ver_radiografia.php?id=<?= urlencode($row['id_radiografia']) ?>" class="secondary-btn">Ver RX</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">No hay diagnósticos IA registrados.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>