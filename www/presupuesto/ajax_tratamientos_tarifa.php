<?php
include("../assets/PHP/crm_lib.php");
$conn = conecta_db();
if ($conn == "KO") {
    echo "<p>Error de conexión.</p>";
    exit;
}

$id_tarifa = $_GET['id_tarifa'] ?? $_POST['id_tarifa'] ?? '';
if ($id_tarifa === '') {
    echo "<p>No se ha seleccionado ninguna tarifa.</p>";
    exit;
}

$sql = "SELECT t.id, t.nombre, t.precio, t.requiere_diente, c.nombre AS categoria
        FROM tratamiento t
        LEFT JOIN categoria_tratamiento c ON t.id_categoria = c.id
        WHERE t.id_tarifa = '$id_tarifa' AND t.activo = 1
        ORDER BY c.nombre, t.nombre";
$res = $conn->query($sql);

$grupos = [];
while ($row = $res->fetch_assoc()) {
    $cat = $row['categoria'] ?? 'Sin categoría';
    $grupos[$cat][] = $row;
}
?>
<?php foreach ($grupos as $categoria => $tratamientos): ?>
    <h4><?= htmlspecialchars($categoria) ?></h4>
    <table class="tratamientos-table">
        <thead>
            <tr>
                <th></th>
                <th>Tratamiento</th>
                <th>Precio (€)</th>
                <th>Diente / Selección</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tratamientos as $t): ?>
            <tr class="tratamiento-row">
                <td>
                    <input type="checkbox" name="tratamiento[]" value="<?= $t['id'] ?>">
                    <input type="hidden" name="precio[]" value="<?= number_format($t['precio'], 2, '.', '') ?>">
                </td>
                <td><?= htmlspecialchars($t['nombre']) ?></td>
                <td><?= number_format($t['precio'], 2) ?></td>
                <td>
                    <?php if ($t['requiere_diente']): ?>
                        <div class="selector-dientes" style="display:none;">
                            <div class="arcada-superior">
                                <?php foreach (['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28'] as $d): ?>
                                    <button type="button" class="btn-diente" data-diente="<?= $d ?>"><?= $d ?></button>
                                <?php endforeach; ?>
                            </div>
                            <div class="arcada-inferior">
                                <?php foreach (['48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'] as $d): ?>
                                    <button type="button" class="btn-diente" data-diente="<?= $d ?>"><?= $d ?></button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="diente[]" value="">
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="diente[]" value="">
                        <span>-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($t['requiere_diente']): ?>
                        <input type="number" name="cantidad[]" value="0" min="1" class="cantidad_trat" readonly style="width:45px; display:none;">
                    <?php else: ?>
                        <input type="number" name="cantidad[]" value="1" min="1" class="cantidad_trat" disabled>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>