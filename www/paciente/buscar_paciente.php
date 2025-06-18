<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
$nombre_buscar = $_POST["nombre_buscar"];
include("../assets/PHP/crm_lib.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Fallo en conex a DB";
    exit;
}

$sql = "SELECT * FROM paciente WHERE nombre LIKE '%$nombre_buscar%'";
$result = $conn->query($sql);
$conn->close();

echo "<h2>Resultados para \"$nombre_buscar\"</h2>";
echo "<table><tr><th>NHC</th><th>Nombre</th><th>Apellido 1</th><th>Apellido 2</th><th>Acción</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
        <td>{$row['nhc']}</td>
        <td>{$row['nombre']}</td>
        <td>{$row['apellido1']}</td>
        <td>{$row['apellido2']}</td>
        <td>
            <form action='perfil_paciente.php' method='get' style='display:inline;'>
                <input type='hidden' name='id' value='{$row['id']}'>
                <button class='view-btn'>Ver</button>
            </form>
        </td>
    </tr>";
}

echo "</table>";
?>
