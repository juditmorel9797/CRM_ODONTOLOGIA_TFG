<?php
include("../assets/PHP/crm_lib.php");
$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

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
    <title>Buscar Paciente para Agendar</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script>
        function buscarAgendar() {
            const input = document.getElementById("busca_y_agenda");
            const valor = input.value.trim();
            if (valor === "") {
                alert("Por favor introduce un nombre, apellido o NHC para buscar.");
                return;
            }
            document.getElementById("form_busqueda").submit();
        }

        function seleccionar_paciente(id, nombre_completo) {
            window.opener.seleccionarPaciente(id, nombre_completo);
            window.close();
        }
    </script>
</head>
<body>
<main class="container">
    <h1 class="titulo-agenda">Buscar Paciente para Agendar Cita</h1>

    <form method="post" id="form_busqueda" class="form-inline">
        <input type="text" name="termino" id="busca_y_agenda" placeholder="Buscar por nombre, apellido o NHC" size="40" value="<?= htmlspecialchars($termino) ?>">
        <button type="button" onclick="buscarAgendar()">🔍 Buscar</button>
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
                        <?php $nombre_completo = $row['apellido1'] . " " . $row['nombre']; ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nhc']) ?></td>
                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                            <td><?= htmlspecialchars($row['apellido1']) ?></td>
                            <td>
                                <button onclick="seleccionar_paciente('<?= $row['id'] ?>', '<?= $nombre_completo ?>')">Seleccionar</button>
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