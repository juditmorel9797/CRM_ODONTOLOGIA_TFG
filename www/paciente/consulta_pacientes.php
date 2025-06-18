<?php
include("../assets/PHP/crm_lib.php");
include("../includes/header.php");

// --- Filtros de búsqueda ---
$conn = conecta_db();
if ($conn == "KO") {
    echo "Fallo en conex a DB";
    exit;
}
$busqueda = trim($_GET['q'] ?? '');
$where = "1";
if ($busqueda !== '') {
    $busq = $conn->real_escape_string($busqueda);
    $where .= " AND (nombre LIKE '%$busq%' OR apellido1 LIKE '%$busq%' OR apellido2 LIKE '%$busq%' OR nhc LIKE '%$busq%')";
}
$sql = "SELECT * FROM paciente WHERE $where ORDER BY nhc ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDH - CRM</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script type="text/javascript">
        // Ventana pequeña para gestión de citas
        function popupCitas(id_paciente) {
            window.open(
                '../cita/gestor_citas.php?id_paciente=' + encodeURIComponent(id_paciente),
                'GestorCitas',
                'width=1000,height=640,top=80,left=260,toolbar=no,status=no,scrollbars=1,resizable=1'
            );
        }
        // Ventana pequeña para radiografías
        function popupRadios(id_paciente) {
            window.open(
                '../IA/subir_y_analizar_radiografia.php?id_paciente=' + encodeURIComponent(id_paciente),
                'RadiosPaciente',
                'width=900,height=640,top=100,left=340,toolbar=no,status=no,scrollbars=1,resizable=1'
            );
        }
    </script>
</head>
<body>
<main class="container">
    <h1>CRM de Gestión de Pacientes</h1>

<section class="patients-section" style="margin-bottom: 24px;">
    <form method="get" action="consulta_pacientes.php" class="form-botones-parejos">
        <input type="text" name="q" id="busca_paciente" placeholder="Buscar por nombre, NHC..." value="<?= htmlspecialchars($busqueda) ?>">
        <button type="submit" class="primary-btn boton-mitad">Filtrar</button>
        <a href="agregar_paciente.php" class="primary-btn boton-mitad btn-verde">+ Añadir Nuevo Paciente</a>
    </form>
</section>

    <section class="table-container">
        <h2>Lista de Pacientes</h2>
        <table>
            <thead>
                <tr>
                    <th>NHC</th>
                    <th>Nombres</th>
                    <th>Primer Apellido</th>
                    <th>Segundo Apellido</th>
                    <th>Fecha de Nacimiento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td class='btn-container'>
                                <form action='perfil_paciente.php' method='get' style='display:inline;'>
                                    <input type='hidden' name='id' value='" . $row['id'] . "'>
                                    <button class='view-btn'>" . $row['nhc'] . "</button>
                                </form>
                            </td>
                            <td>" . htmlspecialchars($row['nombre']) . "</td>
                            <td>" . htmlspecialchars($row['apellido1']) . "</td>
                            <td>" . htmlspecialchars($row['apellido2']) . "</td>
                            <td>" . htmlspecialchars($row['fecha_nacimiento']) . "</td>
                            <td class='icon-actions'>
                                <a href=\"#\" onclick=\"popupRadios('" . $row['id'] . "'); return false;\">
                                    <img src=\"../assets/images/grf4.png\" width=\"27\" height=\"27\" title=\"Ver radiografías\">
                                </a>
                                <a href=\"#\" onclick=\"popupCitas('" . $row['id'] . "'); return false;\">
                                    <img src=\"../assets/images/calendar1.png\" width=\"27\" height=\"27\" title=\"Consultar citas\">
                                </a>
                            </td>
                        </tr>";
                    }                        
                } else {
                    echo "<tr><td colspan='6'>No hay pacientes registrados.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>