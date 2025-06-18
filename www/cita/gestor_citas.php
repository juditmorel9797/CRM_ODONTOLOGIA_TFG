<?php
include("../assets/PHP/crm_lib.php");
include("../includes/menuagenda.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

// Filtros
$busqueda = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? '';
$id_paciente = $_GET['id_paciente'] ?? '';

// WHERE dinámico
$where = "1";
if ($busqueda !== '') {
    $busq = $conn->real_escape_string($busqueda);
    $where .= " AND (pac.nombre LIKE '%$busq%' OR pac.apellido1 LIKE '%$busq%' OR pac.nhc LIKE '%$busq%')";
}
if ($estado !== '') {
    $where .= " AND c.id_estado_cita = '$estado'";
}
if ($id_paciente !== '') {
    $where .= " AND c.id_paciente = '$id_paciente'";
}

// Consulta principal (máx 50)
$sql = "
    SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.observaciones,
           c.id_estado_cita, e.nombre AS estado, e.color_hex,
           pac.nombre, pac.apellido1, pac.nhc, a.nombre_visible AS doctor, c.id_agenda
    FROM cita c
    LEFT JOIN paciente pac ON c.id_paciente = pac.id
    LEFT JOIN estado_cita e ON c.id_estado_cita = e.id
    LEFT JOIN agenda ag ON c.id_agenda = ag.id
    LEFT JOIN usuario a ON ag.id_doctor = a.id
    WHERE c.id_paciente IS NOT NULL AND $where
    ORDER BY c.fecha DESC, c.hora_inicio DESC
    LIMIT 50
";
$result = $conn->query($sql);

// Obtener nombre paciente para mostrarlo arriba (si filtra por paciente)
$nombre_paciente = "";
if ($id_paciente !== '') {
    $sql_pac = "SELECT nombre, apellido1, apellido2, nhc FROM paciente WHERE id = '$id_paciente'";
    $res_pac = $conn->query($sql_pac);
    if ($res_pac && $res_pac->num_rows > 0) {
        $p = $res_pac->fetch_assoc();
        $nombre_paciente = $p['nombre'] . " " . $p['apellido1'] . " " . $p['apellido2'] . " (NHC " . $p['nhc'] . ")";
    }
}

// Estados posibles (traer de la base de datos)
$estados = [];
$res_est = $conn->query("SELECT id, nombre FROM estado_cita ORDER BY id");
while ($row = $res_est->fetch_assoc()) {
    $estados[$row['id']] = $row['nombre'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta Citas</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .estado-cita-badge {
            padding: 3px 10px;
            border-radius: 10px;
            font-weight: 600;
            color: #fff;
            display: inline-block;
            font-size: 13px;
        }
    </style>
</head>
<body>
<main class="container">
    <h2>Consulta Citas <?= $nombre_paciente ? "de <span style='color: #007BFF;'>" . htmlspecialchars($nombre_paciente) . "</span>" : "" ?></h2>
    <!-- Formulario de búsqueda y filtros -->
    <form method="get" class="form-inline" style="display:flex;align-items:baseline;gap:10px;">
        <input type="text" name="q" placeholder="Buscar paciente o NHC" value="<?= htmlspecialchars($busqueda) ?>" style="min-width:200px;">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach ($estados as $id => $nombre): ?>
                <option value="<?= $id ?>" <?= $id == $estado ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($id_paciente !== ''): ?>
            <input type="hidden" name="id_paciente" value="<?= htmlspecialchars($id_paciente) ?>">
        <?php endif; ?>
        <button type="submit">Filtrar</button>
    </form>
    <br>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>NHC</th>
                <th>Hora</th>
                <th>Doctor</th>
                <th>Estado</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= date("d/m/Y", strtotime($row['fecha'])) ?></td>
                        <td><?= htmlspecialchars($row['nombre'] . ' ' . $row['apellido1']) ?></td>
                        <td><?= htmlspecialchars($row['nhc'] ?? '-') ?></td>
                        <td><?= htmlspecialchars(substr($row['hora_inicio'], 0, 5) . ' - ' . substr($row['hora_fin'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars($row['doctor'] ?? '-') ?></td>
                        <td>
                            <span class="estado-cita-badge" style="background:<?= htmlspecialchars($row['color_hex'] ?? '#888') ?>;">
                                <?= htmlspecialchars($row['estado']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['observaciones']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No hay citas registradas<?= $id_paciente ? " para este paciente" : "" ?>.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($id_paciente == ''): ?>
        <p style="margin-top:15px;color:#b33;">Para registrar una cita, use la agenda correspondiente.</p>
    <?php endif; ?>
</main>
</body>
</html>