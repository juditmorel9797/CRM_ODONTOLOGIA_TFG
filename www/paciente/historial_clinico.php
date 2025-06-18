<?php
session_start();
include("../assets/PHP/crm_lib.php");

// Comprobar usuario logueado
if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit;
}
$id_usuario = $_SESSION['id_usuario'] ?? '';
$nombreUsuario = $_SESSION['usuario'] ?? 'Usuario';

$id_paciente = $_GET['id_paciente'] ?? '';
if ($id_paciente === '') {
    echo "ID de paciente no proporcionado.";
    exit;
}

// Datos básicos del paciente
$conn = conecta_db();
$sql_pac = "SELECT nombre, apellido1, apellido2, nhc FROM paciente WHERE id = '$id_paciente'";
$res_pac = $conn->query($sql_pac);
$p = $res_pac->fetch_assoc();



// Cambiar estado de tratamiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado_tratamiento'])) {
    $id_presu_trat = $conn->real_escape_string($_POST['id_presu_trat']);
    $nuevo_estado = $conn->real_escape_string($_POST['nuevo_estado']);
    $conn->query("UPDATE presupuesto_tratamiento SET estado = '$nuevo_estado' WHERE id = '$id_presu_trat'");
    header("Location: historial_clinico.php?id_paciente=$id_paciente");
    exit;
}

// Añadir comentario (inline)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comentario']) && !empty($_POST['comentario_texto'])) {
    $texto = $conn->real_escape_string($_POST['comentario_texto']);
    // Busca o crea historial_clinico principal
    $sql_historial = "SELECT id FROM historial_clinico WHERE id_paciente = '$id_paciente' ORDER BY fecha DESC LIMIT 1";
    $res_historial = $conn->query($sql_historial);
    $row = $res_historial->fetch_assoc();
    $historial_id = $row ? $row['id'] : generate_uuid_v4();
    if (!$row) {
        $conn->query("INSERT INTO historial_clinico (id, id_paciente, creado_por) VALUES ('$historial_id', '$id_paciente', '$id_usuario')");
    }
    $comentario_id = generate_uuid_v4();
    $conn->query("INSERT INTO comentario_historial (id, id_historial, id_usuario, texto) VALUES ('$comentario_id', '$historial_id', '$id_usuario', '$texto')");
    header("Location: historial_clinico.php?id_paciente=$id_paciente");
    exit;
}

// Cargar historial (ahora incluye usuario)
$sql_hist = "
    SELECT 
        'tratamiento' AS tipo,
        pt.fecha AS fecha, 
        t.nombre AS nota, 
        pt.diente,
        pt.estado AS estado,
        pt.id AS id_presu_trat,
        u.nombre_visible AS colaborador
    FROM presupuesto_tratamiento pt
    JOIN presupuesto p ON pt.id_presupuesto = p.uuid
    JOIN tratamiento t ON pt.id_tratamiento = t.id
    LEFT JOIN usuario u ON p.id_usuario = u.id
    WHERE p.id_paciente = '$id_paciente' AND p.estado = 'ACEPTADO'

    UNION ALL

    SELECT
        'comentario' AS tipo,
        c.fecha AS fecha,
        c.texto AS nota,
        '' AS diente,
        '' AS estado,
        '' AS id_presu_trat,
        u.nombre_visible AS colaborador
    FROM comentario_historial c
    JOIN historial_clinico h ON c.id_historial = h.id
    LEFT JOIN usuario u ON c.id_usuario = u.id
    WHERE h.id_paciente = '$id_paciente'

    ORDER BY fecha DESC
";
$res_hist = $conn->query($sql_hist);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Paciente</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">
    <!-- Formulario tipo chat para añadir comentario -->
    <form action="historial_clinico.php?id_paciente=<?= $id_paciente ?>" method="post" class="chat-add-row">
        <input type="hidden" name="add_comentario" value="1">
        <input type="text" name="comentario_texto" required maxlength="255" placeholder="Escribe un comentario..." class="chat-add-input">
        <button type="submit" class="chat-add-btn">
            <img src="../assets/images/add_verde.png" alt="Añadir">Añadir
        </button>
    </form>

    <table class="historial-table">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Fecha</th>
<th class="notas-col">Notas</th>
                <th>Diente</th>
                <th>Estado</th>
                <th>Colaborador</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($res_hist->num_rows > 0): ?>
                <?php while ($h = $res_hist->fetch_assoc()): ?>
                    <?php
                        $class_row = '';
                        if ($h['tipo'] === 'tratamiento') {
                            if ($h['estado'] == 'pendiente') $class_row = 'fila-pendiente';
                            elseif ($h['estado'] == 'realizado') $class_row = 'fila-realizado';
                            elseif ($h['estado'] == 'cancelado') $class_row = 'fila-cancelado';
                        }
                    ?>
                    <tr class="<?= $class_row ?>">
                        <td>
                            <?php if ($h['tipo'] === 'comentario'): ?>
                                <span class="tipo-comentario">Comentario</span>
                            <?php else: ?>
                                <span class="tipo-tratamiento">Tratamiento</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date("d/m/Y", strtotime($h['fecha'])) ?></td>
                        <td><?= htmlspecialchars($h['nota']) ?></td>
                        <td><?= $h['diente'] ?></td>
                        <td>
                        <?php if ($h['tipo'] === 'tratamiento'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="cambiar_estado_tratamiento" value="1">
                                <input type="hidden" name="id_presu_trat" value="<?= $h['id_presu_trat'] ?>">
                                <select name="nuevo_estado" class="select-estado" onchange="this.form.submit()">
                                    <option value="pendiente" <?= $h['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="realizado" <?= $h['estado'] == 'realizado' ? 'selected' : '' ?>>Realizado</option>
                                    <option value="cancelado" <?= $h['estado'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </form>
                        <?php endif; ?>
                        </td>
                        <td>
                            <span class="colab-badge"><?= htmlspecialchars($h['colaborador'] ?? '') ?></span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No hay historial registrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>