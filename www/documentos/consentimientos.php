<?php
session_start();
include("../assets/PHP/crm_lib.php");

if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit;
}
$conn = conecta_db();
$id_paciente = $_GET['id_paciente'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_consentimiento'])) {
    $tipo = $conn->real_escape_string($_POST['tipo']);
    $uuid = generate_uuid_v4();
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == 0) {
        $nombre = $_FILES['archivo']['name'];
        $tipo_mime = $_FILES['archivo']['type'];
        $contenido = base64_encode(file_get_contents($_FILES['archivo']['tmp_name']));
        $sql = "INSERT INTO consentimiento (uuid, id_paciente, tipo, archivo_nombre, archivo_tipo, archivo, subido_por)
                VALUES ('$uuid', '$id_paciente', '$tipo', '$nombre', '$tipo_mime', '$contenido', '$id_usuario')";
        if ($conn->query($sql) === TRUE) {
            header("Location: consentimientos.php?id_paciente=$id_paciente&ok=1");
            exit;
        } else {
            $mensaje = "Error al guardar: " . $conn->error;
        }
    } else {
        $mensaje = "Error de archivo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consentimientos firmados</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">
    <h2>Consentimientos firmados</h2>

    <form action="consentimientos.php?id_paciente=<?= $id_paciente ?>" method="POST" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:end;margin-bottom:20px;">
        <input type="hidden" name="subir_consentimiento" value="1">
        <input type="hidden" name="id_paciente" value="<?= $id_paciente ?>">
        <label>
            Tipo:
            <select name="tipo" required>
                <option value="">Selecciona</option>
                <option value="Consentimiento general">Consentimiento general</option>
                <option value="Cirugía">Cirugía</option>
                <option value="Endodoncia">Endodoncia</option>
                <option value="Ortodoncia">Ortodoncia</option>
                <!-- Añade más según tu necesidad -->
            </select>
        </label>
        <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
        <button type="submit" class="chat-add-btn">Subir</button>
    </form>
    <?php if (isset($_GET['ok'])): ?>
        <div style="color: #4CAF50; font-weight:600; margin-top:6px;">¡Subida correcta!</div>
    <?php endif; ?>
    <?php if (isset($mensaje)): ?>
        <div style="color: #E74C3C; font-weight:600;"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <table class="historial-table">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Archivo</th>
                <th>Fecha</th>
                <th>Subido por</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = $conn->query("SELECT * FROM consentimiento WHERE id_paciente='$id_paciente' ORDER BY fecha_subida DESC");
            while ($row = $res->fetch_assoc()):
            ?>
            <tr>
                <td><?= htmlspecialchars($row['tipo']) ?></td>
                <td><?= htmlspecialchars($row['archivo_nombre']) ?></td>
                <td><?= date("d/m/Y", strtotime($row['fecha_subida'])) ?></td>
                <td>
                    <?php
                    $u = $conn->query("SELECT nombre_visible FROM usuario WHERE id='".$row['subido_por']."'")->fetch_assoc();
                    echo htmlspecialchars($u['nombre_visible'] ?? '');
                    ?>
                </td>
                <td>
                    <a href="descargar_archivo.php?tabla=consentimiento&id=<?= $row['id'] ?>" class="chat-add-btn" style="background:#5CA9E9;padding:4px 12px;font-size:14px;">V</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>