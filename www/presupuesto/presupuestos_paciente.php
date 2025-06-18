<?php
session_start();
include("../assets/PHP/crm_lib.php");

// Verificar login
if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit;
}

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

$id_paciente = $_GET['id_paciente'] ?? '';

// ---------- 1. Buscador de paciente si no hay id_paciente ----------
if ($id_paciente === '') {
    $mostrar_resultados = false;
    $termino = "";
    $result = null;

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["termino"])) {
        $termino = trim($_POST["termino"]);
        if ($termino !== "") {
            $mostrar_resultados = true;
            $termino_param = "%" . mb_strtolower($conn->real_escape_string($termino), 'UTF-8') . "%";
            $sql = "SELECT id, nombre, apellido1, nhc FROM paciente 
                    WHERE LOWER(nombre) LIKE ? 
                       OR LOWER(apellido1) LIKE ? 
                       OR LOWER(nhc) LIKE ?
                    ORDER BY apellido1, nombre";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $termino_param, $termino_param, $termino_param);
            $stmt->execute();
            $result = $stmt->get_result();
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Buscar Paciente</title>
        <link rel="stylesheet" href="../assets/css/styles.css">
    </head>
    <body>
    <main class="container">
        <!-- Botón de regreso a la pantalla principal -->
        <button class="btn-back" onclick="window.location.href='../index.php'">
            <img src="../assets/images/back3.png" alt="Volver"> Volver
        </button>
        <h2>Buscar paciente</h2>
        <form method="post" id="form_busqueda" class="form-inline">
            <input type="text" name="termino" id="busca_y_agenda" placeholder="Buscar por nombre, apellido o NHC" size="40" value="<?= htmlspecialchars($termino) ?>">
            <button type="submit">Buscar</button>
        </form>
        <?php if ($mostrar_resultados): ?>
            <h3>Resultados para "<?= htmlspecialchars($termino) ?>"</h3>
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="tabla-resultados">
                    <thead>
                        <tr>
                            <th>NHC</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nhc']) ?></td>
                                <td><?= htmlspecialchars($row['nombre']) ?></td>
                                <td><?= htmlspecialchars($row['apellido1']) ?></td>
                                <td>
                                    <a href="?id_paciente=<?= $row['id'] ?>">Ver presupuestos</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No se encontraron pacientes.</p>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    </body>
    </html>
    <?php
    exit;
}

// ---------- 2. Si hay id_paciente, mostrar filtro y listado de presupuestos de ese paciente ----------

// Datos del paciente seleccionado (nombre para mostrar arriba)
$sql_pac = "SELECT nombre, apellido1, apellido2, nhc FROM paciente WHERE id = '$id_paciente'";
$res_pac = $conn->query($sql_pac);
$p = $res_pac && $res_pac->num_rows > 0 ? $res_pac->fetch_assoc() : null;
$nombre_paciente = $p
    ? $p['nombre'] . " " . $p['apellido1'] . " " . $p['apellido2'] . " (NHC " . $p['nhc'] . ")"
    : "";

// Filtros adicionales
$estado = $_GET['estado'] ?? '';
$busqueda = trim($_GET['q'] ?? '');

// Construir WHERE (solo presupuestos de este paciente)
$where = "p.id_paciente = '$id_paciente'";
if ($estado !== '') {
    $where .= " AND p.estado = '$estado'";
}
if ($busqueda !== '') {
    $busq = $conn->real_escape_string($busqueda);
    $where .= " AND (u.user_name LIKE '%$busq%' OR t.nombre LIKE '%$busq%')";
}

// Consulta principal: presupuestos de ese paciente
$sql = "
    SELECT p.uuid, p.fecha_creacion, p.estado, t.nombre AS tarifa, 
           u.user_name AS creado_por
    FROM presupuesto p
    LEFT JOIN tarifa t ON p.id_tarifa = t.id
    LEFT JOIN usuario u ON p.id_usuario = u.id
    WHERE $where
    ORDER BY p.fecha_creacion DESC
    LIMIT 50
";
$result = $conn->query($sql);

$estados = [
    'ENTREGADO','ACEPTADO','RECHAZADO','EN_CURSO','DOCUMENTACION',
    'FINANCIERA','KO_FINANCIERA','NO_LOCALIZADO'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presupuestos de <?= htmlspecialchars($nombre_paciente) ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">
    <h2>Gestión de Presupuestos <span style="color:#007BFF;"></span></h2>
    <h3><?= htmlspecialchars($nombre_paciente) ?></h3>
    <!-- Formulario de búsqueda y filtro -->
    <form method="get" class="form-inline" style="display:flex;align-items:baseline;gap:10px;">
        <input type="hidden" name="id_paciente" value="<?= htmlspecialchars($id_paciente) ?>">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach ($estados as $est): ?>
                <option value="<?= $est ?>" <?= $est == $estado ? 'selected' : '' ?>><?= ucfirst(strtolower($est)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filtrar</button>
        <!-- + Nuevo Presupuesto solo si hay paciente seleccionado -->
        <a href="crear_presupuesto.php?id_paciente=<?= urlencode($id_paciente) ?>" class="primary-btn" style="margin-left:auto;">+ Nuevo Presupuesto</a>
    </form>
    <br>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
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
                        <td><?= htmlspecialchars($row['tarifa'] ?? 'N/A') ?></td>
                        <td>
                            <span class="presupuesto-estado estado-<?= strtolower($row['estado']) ?>">
                                <?= ucfirst(strtolower($row['estado'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['creado_por'] ?? 'Desconocido') ?></td>
                        <td>
                            <a href="detalle_presupuesto.php?id=<?= $row['uuid'] ?>&id_paciente=<?= urlencode($id_paciente) ?>" class="view-btn">Ver</a>
                            <a href="editar_presupuesto.php?id=<?= $row['uuid'] ?>&id_paciente=<?= urlencode($id_paciente) ?>" class="edit-btn">Editar</a>
                            <a href="eliminar_presupuesto.php?id=<?= $row['uuid'] ?>&id_paciente=<?= urlencode($id_paciente) ?>" class="delete-btn" onclick="return confirm('¿Seguro que quieres eliminar este presupuesto?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No hay presupuestos registrados para este paciente.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>