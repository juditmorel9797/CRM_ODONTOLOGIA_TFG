<?php
include("../assets/PHP/crm_lib.php");
include("../includes/header.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

// --- Filtros ---
$busqueda = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? '';
$id_paciente = $_GET['id_paciente'] ?? '';

// Armar WHERE dinámico
$where = "1";
if ($busqueda !== '') {
    $busq = $conn->real_escape_string($busqueda);
    $where .= " AND (pac.nombre LIKE '%$busq%' OR pac.apellido1 LIKE '%$busq%' OR u.user_name LIKE '%$busq%')";
}
if ($estado !== '') {
    $where .= " AND p.estado = '$estado'";
}
if ($id_paciente !== '') {
    $where .= " AND p.id_paciente = '$id_paciente'";
}

// Consulta principal de presupuestos (máx 50)
$sql = "
    SELECT p.uuid, p.fecha_creacion, p.estado, t.nombre AS tarifa, 
           u.user_name AS creado_por, pac.nombre AS paciente, pac.apellido1, p.id_paciente
    FROM presupuesto p
    LEFT JOIN tarifa t ON p.id_tarifa = t.id
    LEFT JOIN usuario u ON p.id_usuario = u.id
    LEFT JOIN paciente pac ON p.id_paciente = pac.id
    WHERE $where
    ORDER BY p.fecha_creacion DESC
    LIMIT 50
";
$result = $conn->query($sql);

// Opcional: obtener nombre paciente filtrado para mostrarlo arriba (si aplica)
$nombre_paciente = "";
if ($id_paciente !== '') {
    $sql_pac = "SELECT nombre, apellido1, apellido2, nhc FROM paciente WHERE id = '$id_paciente'";
    $res_pac = $conn->query($sql_pac);
    if ($res_pac && $res_pac->num_rows > 0) {
        $p = $res_pac->fetch_assoc();
        $nombre_paciente = $p['nombre'] . " " . $p['apellido1'] . " " . $p['apellido2'] . " (NHC " . $p['nhc'] . ")";
    }
}

// Estados posibles
$estados = [
    'ENTREGADO','ACEPTADO','RECHAZADO','EN_CURSO','DOCUMENTACION',
    'FINANCIERA','KO_FINANCIERA','NO_LOCALIZADO'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Presupuestos</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">
    <h2>Gestión de Presupuestos <?= $nombre_paciente ? "de <span style='color: #007BFF;'>" . htmlspecialchars($nombre_paciente) . "</span>" : "" ?></h2>
    <!-- Formulario de búsqueda y filtros -->
    <form method="get" class="form-inline" style="display:flex;align-items:baseline;gap:10px;">
        <input type="text" name="q" placeholder="Buscar Paciente" value="<?= htmlspecialchars($busqueda) ?>" style="min-width:200px;">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach ($estados as $est): ?>
                <option value="<?= $est ?>" <?= $est == $estado ? 'selected' : '' ?>><?= ucfirst(strtolower($est)) ?></option>
            <?php endforeach; ?>
        </select>
        <!-- Si se filtra por paciente, mantenerlo oculto en el form para no perderlo al buscar -->
        <?php if ($id_paciente !== ''): ?>
            <input type="hidden" name="id_paciente" value="<?= htmlspecialchars($id_paciente) ?>">
        <?php endif; ?>
        <button type="submit">Filtrar</button>

        <!-- Solo mostrar botón de nuevo presupuesto si hay paciente filtrado -->
        <?php if ($id_paciente !== ''): ?>
            <a href="crear_presupuesto.php?id_paciente=<?= urlencode($id_paciente) ?>" class="primary-btn" style="margin-left:auto;">+ Nuevo Presupuesto</a>
        <?php endif; ?>
    </form>
    <br>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Tarifa</th>
                <th>Estado</th>
                <th>Creado por</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= date("d/m/Y", strtotime($row['fecha_creacion'])) ?></td>
                        <td><?= htmlspecialchars($row['paciente'] . ' ' . $row['apellido1']) ?></td>
                        <td><?= htmlspecialchars($row['tarifa'] ?? 'N/A') ?></td>
                        <td>
                            <span class="presupuesto-estado estado-<?= strtolower($row['estado']) ?>">
                                <?= ucfirst(strtolower($row['estado'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['creado_por'] ?? 'Desconocido') ?></td>
                        <td>
                            <a href="detalle_presupuesto.php?id=<?= $row['uuid'] ?>&id_paciente=<?= urlencode($row['id_paciente']) ?>" class="view-btn">Ver</a>
                            <a href="eliminar_presupuesto.php?id=<?= $row['uuid'] ?>&id_paciente=<?= urlencode($row['id_paciente']) ?>" class="delete-btn" onclick="return confirm('¿Seguro que quieres eliminar este presupuesto?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No hay presupuestos registrados<?= $id_paciente ? " para este paciente" : "" ?>.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($id_paciente == ''): ?>
        <p style="margin-top:15px;color:#b33;">Para crear un presupuesto, busca primero un paciente y selecciona.</p>
    <?php endif; ?>
</main>
</body>
</html>