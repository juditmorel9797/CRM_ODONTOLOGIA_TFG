<?php
session_start();
include("../assets/PHP/crm_lib.php");
// Solo admin
if (!isset($_SESSION["usuario"]) || $_SESSION["perfil"] != 1) {
    echo "<h2>Acceso denegado. Solo disponible para administradores.</h2>";
    exit;
}

$conn = conecta_db();
if ($conn === "KO") {
    echo "Error de conexión.";
    exit;
}

$id_tarifa = $_GET['id'] ?? '';
if ($id_tarifa === '') {
    echo "ID de tarifa no proporcionado.";
    exit;
}

// Generar código incremental tipo TTO001
function generar_codigo_incremental($conn) {
    $res = $conn->query("SELECT MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)) AS max_cod FROM tratamiento WHERE codigo LIKE 'TTO%'");
    if ($res && $row = $res->fetch_assoc()) {
        $num = intval($row['max_cod']) + 1;
    } else {
        $num = 1;
    }
    return 'TTO' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

// Procesar alta de nuevo tratamiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_tratamiento'])) {
    $id_tratamiento = generate_uuid_v4();
    $codigo = generar_codigo_incremental($conn);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $id_categoria = $_POST['id_categoria'];
    $requiere_diente = isset($_POST['requiere_diente']) ? 1 : 0;

    $sql_insert_trat = "INSERT INTO tratamiento (
        id, codigo, nombre, precio, id_tarifa, id_categoria, requiere_diente
    ) VALUES (
        '$id_tratamiento', '$codigo', '$nombre', $precio, '$id_tarifa', '$id_categoria', $requiere_diente
    )";

    if ($conn->query($sql_insert_trat)) {
        $msg_success = "Tratamiento creado correctamente.";
    } else {
        $msg_error = "Error: {$conn->error}";
    }
}

// Procesar asignación de tratamientos a la tarifa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['nuevo_tratamiento'])) {
    $conn->query("UPDATE tratamiento SET id_tarifa = NULL WHERE id_tarifa = '$id_tarifa'");
    if (!empty($_POST['tratamientos']) && is_array($_POST['tratamientos'])) {
        foreach ($_POST['tratamientos'] as $id_trat) {
            $conn->query("UPDATE tratamiento SET id_tarifa = '$id_tarifa' WHERE id = '$id_trat'");
        }
    }
    header("Location: tarifas_presupuestos.php");
    exit;
}
include("../includes/header.php");
// Datos para la vista
$sql_tarifa = "SELECT nombre FROM tarifa WHERE id = '$id_tarifa'";
$tarifa = $conn->query($sql_tarifa)->fetch_assoc();

$sql_categorias = "SELECT id, nombre FROM categoria_tratamiento ORDER BY nombre";
$result_categorias = $conn->query($sql_categorias);
$categorias = [];
while ($cat = $result_categorias->fetch_assoc()) {
    $categorias[$cat['id']] = $cat['nombre'];
}

$sql_tratamientos = "
    SELECT t.id, t.nombre, t.precio, t.id_categoria, t.id_tarifa
    FROM tratamiento t
    ORDER BY t.id_categoria, t.nombre";
$result_tratamientos = $conn->query($sql_tratamientos);

$agrupados = [];
while ($trat = $result_tratamientos->fetch_assoc()) {
    $categoria = $categorias[$trat['id_categoria']] ?? 'Sin categoría';
    $agrupados[$categoria][] = $trat;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Tarifa – <?= htmlspecialchars($tarifa['nombre']) ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">

    <!-- Botón de regreso arriba a la izquierda -->
    <button class="btn-back" onclick="window.location.href='tarifas_presupuestos.php'">
        <img src="../assets/images/back3.png" alt="Volver">
        Volver
    </button>

    <h2 class="titulo-tarifa">Tarifa: <span><?= htmlspecialchars($tarifa['nombre']) ?></span></h2>
    <section class="card" style="margin-bottom: 32px;">
        <h3>Crear Nuevo Tratamiento</h3>
        <?php if (!empty($msg_success)): ?>
            <div class="success-message"><?= $msg_success ?></div>
        <?php endif; ?>
        <?php if (!empty($msg_error)): ?>
            <div class="error-message"><?= $msg_error ?></div>
        <?php endif; ?>
        <form method="post" class="form-card">
            <input type="hidden" name="nuevo_tratamiento" value="1">
            <div class="form-row">
                <label>Grupo/Categoría:</label>
                <select name="id_categoria" required>
                    <option value="">Seleccione categoría</option>
                    <?php foreach ($categorias as $id_cat => $nombre_cat): ?>
                        <option value="<?= $id_cat ?>"><?= htmlspecialchars($nombre_cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label>Nombre del Tratamiento:</label>
                <input type="text" name="nombre" required maxlength="100">
            </div>
            <div class="form-row">
                <label>Precio (€):</label>
                <input type="number" name="precio" min="0" step="0.01" required>
            </div>
            <div class="form-row">
                <label><input type="checkbox" name="requiere_diente"> ¿Requiere número de diente?</label>
            </div>
            <div class="form-row">
                <input type="submit" value="Agregar Tratamiento" class="primary-btn">
            </div>
        </form>
    </section>

    <section class="card">
        <h3>Asignar Tratamientos a la Tarifa</h3>
        <form method="post" class="form-tratamientos">
            <?php foreach ($agrupados as $categoria => $tratamientos): ?>
                <h4 style="margin-top:16px;"><?= htmlspecialchars($categoria) ?></h4>
                <div class="tratamientos-categoria">
                    <?php foreach ($tratamientos as $trat): ?>
                        <div class="checkbox-row">
                            <input type="checkbox" name="tratamientos[]" value="<?= $trat['id'] ?>"
                                <?= $trat['id_tarifa'] === $id_tarifa ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($trat['nombre']) ?></span>
                            <span style="color:#888; font-size:0.9em;"> (<?= number_format($trat['precio'], 2) ?> €)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <div style="margin-top:20px;">
                <input type="submit" value="Guardar Cambios" class="primary-btn">
                <a href="tarifas_presupuestos.php" class="secondary-btn">Cancelar</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>