<?php
session_start();
include("../assets/PHP/crm_lib.php");
$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

$id_presupuesto = $_GET['id'] ?? '';
if ($id_presupuesto === '') {
    echo "ID de presupuesto no proporcionado.";
    exit;
}

// PROCESAR CAMBIO DE ESTADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
    $nuevo_estado = $conn->real_escape_string($_POST['nuevo_estado']);
    $sql_upd = "UPDATE presupuesto SET estado = '$nuevo_estado' WHERE uuid = '$id_presupuesto'";
    $conn->query($sql_upd);
    header("Location: detalle_presupuesto.php?id=$id_presupuesto");
    exit;
}

// Consulta el presupuesto principal (por UUID)
$sql = "SELECT p.*, t.nombre AS tarifa, u.user_name AS creado_por, 
               pac.nombre AS nombre_paciente, pac.apellido1, pac.apellido2
        FROM presupuesto p
        LEFT JOIN tarifa t ON p.id_tarifa = t.id
        LEFT JOIN usuario u ON p.id_usuario = u.id
        JOIN paciente pac ON p.id_paciente = pac.id
        WHERE p.uuid = '$id_presupuesto'";
$res = $conn->query($sql);
$presupuesto = $res->fetch_assoc();

$id_paciente = $presupuesto['id_paciente'] ?? '';

// Consulta tratamientos de ese presupuesto
$sql_tratamientos = "SELECT pt.*, tr.nombre AS tratamiento, tr.requiere_diente
                     FROM presupuesto_tratamiento pt
                     JOIN tratamiento tr ON pt.id_tratamiento = tr.id
                     WHERE pt.id_presupuesto = '$id_presupuesto'";
$res_trat = $conn->query($sql_tratamientos);

// Lista de estados permitidos
$estados = [
    'ENTREGADO', 'ACEPTADO', 'RECHAZADO', 'EN_CURSO',
    'DOCUMENTACION', 'FINANCIERA', 'KO_FINANCIERA', 'NO_LOCALIZADO'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Presupuesto</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .estado-form { display: inline-block; margin-left: 1em; }
        .btn-back { position: absolute; left: 20px; top: 20px; }
    </style>
</head>
<body>
<main class="container">
    <!-- Botón de regreso al listado de presupuestos -->
    <button class="btn-back" onclick="window.location.href='presupuestos_paciente.php?id_paciente=<?= urlencode($id_paciente) ?>'">
        <img src="../assets/images/back3.png" alt="Volver"> Volver
    </button>
    <br/>
    <div class="card presupuesto-card">
        <h2>Presupuesto de <?= htmlspecialchars($presupuesto['nombre_paciente'] . ' ' . $presupuesto['apellido1'] . ' ' . $presupuesto['apellido2']) ?></h2>
        <p><strong>Fecha:</strong> <?= date("d/m/Y", strtotime($presupuesto['fecha_creacion'])) ?></p>
        <p>
            <strong>Estado actual:</strong>
            <span class="presupuesto-estado estado-<?= strtolower($presupuesto['estado']) ?>">
                <?= ucfirst(strtolower($presupuesto['estado'])) ?>
            </span>
            <!-- Formulario de cambio de estado -->
            <form method="post" class="estado-form" style="display:inline;">
                <select name="nuevo_estado" required>
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?= $estado ?>" <?= $estado == $presupuesto['estado'] ? 'selected' : '' ?>>
                            <?= ucfirst(strtolower($estado)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="primary-btn" style="padding:0.3em 1em;">Actualizar</button>
            </form>
        </p>
        <p><strong>Tarifa:</strong> <?= htmlspecialchars($presupuesto['tarifa'] ?? 'N/A') ?></p>
        <p><strong>Creado por:</strong> <?= htmlspecialchars($presupuesto['creado_por'] ?? 'Desconocido') ?></p>
        <p><strong>Observaciones:</strong> <?= nl2br(htmlspecialchars($presupuesto['observaciones'])) ?></p>

        <h3>Tratamientos incluidos</h3>
        <table>
            <thead>
                <tr>
                    <th>Tratamiento</th>
                    <th>Precio unitario</th>
                    <th>Cantidad</th>
                    <th>Diente</th>
                </tr>
            </thead>
            <tbody>
                <?php $total = 0; ?>
                <?php if ($res_trat->num_rows > 0): ?>
                    <?php while ($row = $res_trat->fetch_assoc()): ?>
                        <?php $subtotal = $row['precio_unitario'] * $row['cantidad']; $total += $subtotal; ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tratamiento']) ?></td>
                            <td><?= number_format($row['precio_unitario'], 2) ?> €</td>
                            <td><?= intval($row['cantidad']) ?></td>
                            <td><?= $row['diente'] ?? '-' ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4">No hay tratamientos asociados.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" style="text-align:right;">TOTAL:</th>
                    <th><?= number_format($total, 2) ?> €</th>
                </tr>
            </tfoot>
        </table>
        <div style="margin-top:24px;">
            <a href="editar_presupuesto.php?id=<?= $presupuesto['uuid'] ?>&id_paciente=<?= urlencode($id_paciente) ?>" class="edit-btn">Editar</a>
            <a href="eliminar_presupuesto.php?id=<?= $presupuesto['uuid'] ?>&id_paciente=<?= urlencode($id_paciente) ?>" class="delete-btn" onclick="return confirm('¿Seguro que quieres eliminar este presupuesto?')">Eliminar</a>
        </div>
    </div>
</main>
</body>
</html>