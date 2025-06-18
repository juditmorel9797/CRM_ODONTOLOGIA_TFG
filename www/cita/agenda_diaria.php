<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../assets/PHP/crm_lib.php");
include("../includes/menuagenda.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}
$id_usuario = $_SESSION["id_usuario"];
$perfil = $_SESSION["perfil"];
$fecha_base = $_GET['fecha'] ?? date('Y-m-d');

// Cambiar estado de cita (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'], $_POST['id_cita'], $_POST['nuevo_estado'])) {
    $id_cita = $conn->real_escape_string($_POST['id_cita']);
    $nuevo_estado = $conn->real_escape_string($_POST['nuevo_estado']);
    $conn->query("UPDATE cita SET id_estado_cita = '$nuevo_estado' WHERE id = '$id_cita'");
    header("Location: agenda_diaria.php?fecha=" . urlencode($fecha_base));
    exit;
}

// Estados
$estados = [];
$res_estados = $conn->query("SELECT id, nombre, color_hex FROM estado_cita ORDER BY id");
while ($row = $res_estados->fetch_assoc()) {
    $estados[$row['id']] = [
        'nombre' => $row['nombre'],
        'color' => $row['color_hex']
    ];
}

// Franjas
$sql_franjas = "SELECT hora_inicio, hora_fin FROM franja_horaria ORDER BY hora_inicio";
$result_franjas = $conn->query($sql_franjas);
$franjas = [];
while ($row = $result_franjas->fetch_assoc()) {
    $franjas[] = $row;
}

// Doctores activos
$doctores_activos = [];
$sql_doctores = "SELECT u.id, u.nombre_visible, a.id AS id_agenda
                 FROM usuario u 
                 JOIN agenda a ON u.id = a.id_doctor 
                 WHERE a.activo = 1 
                 ORDER BY u.nombre_visible";
$result_doctores = $conn->query($sql_doctores);
while ($row = $result_doctores->fetch_assoc()) {
    $doctores_activos[] = $row;
}

// Citas del día (paciente o primeras visitas, y tratamiento)
$citas = [];
$sql = "SELECT c.fecha, c.hora_inicio, c.hora_fin, 
               COALESCE(p.nombre, pv.nombre) AS nombre_paciente,
               p.apellido1, a.id_doctor, c.id AS id_cita, 
               c.id_estado_cita, e.nombre AS estado_nombre, 
               e.color_hex, t.nombre AS tratamiento
        FROM cita c
        JOIN agenda a ON c.id_agenda = a.id
        JOIN estado_cita e ON c.id_estado_cita = e.id
        LEFT JOIN paciente p ON c.id_paciente = p.id
        LEFT JOIN paciente_primerasv pv ON c.id_pv = pv.id
        LEFT JOIN tratamiento_base t ON c.id_tratamiento_base = t.id
        WHERE c.fecha = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $fecha_base);
$stmt->execute();
$result_citas = $stmt->get_result();
while ($row = $result_citas->fetch_assoc()) {
    // clave: fecha_hora_inicio_id_doctor
    $clave = $row['fecha'] . '_' . $row['hora_inicio'] . '_' . $row['id_doctor'];
    $citas[$clave] = $row;
}
$stmt->close();
$conn->close();

function traducir_dia($fecha) {
    $dias_completos = [
        "Monday"    => "Lunes",
        "Tuesday"   => "Martes",
        "Wednesday" => "Miércoles",
        "Thursday"  => "Jueves",
        "Friday"    => "Viernes",
        "Saturday"  => "Sábado",
        "Sunday"    => "Domingo"
    ];
    $nombre_ingles = date('l', strtotime($fecha));
    return $dias_completos[$nombre_ingles] ?? $nombre_ingles;
}

function pintar_tabla($franjas, $doctores_activos, $fecha_base, $citas, $estados, $rango = 'am') {
    $rango_title = $rango === 'am' ? 'Franja Mañana' : 'Franja Tarde';
    $franjas_filtradas = array_values(array_filter($franjas, function($f) use ($rango) {
        return $rango === 'am' ? $f['hora_inicio'] < '16:00:00' : $f['hora_inicio'] >= '16:00:00';
    }));

    if (empty($franjas_filtradas)) return;

    echo "<h3>$rango_title</h3>";
    echo "<table class='agenda-diaria'>";
    echo "<thead><tr><th>Hora</th>";
    foreach ($doctores_activos as $doc) {
        echo "<th>" . htmlspecialchars($doc['nombre_visible']) . "</th>";
    }
    echo "</tr></thead><tbody>";

    $total_franjas = count($franjas_filtradas);
    for ($i = 0; $i < $total_franjas; $i++) {
        $franja = $franjas_filtradas[$i];
        echo "<tr>";
        echo "<td>" . substr($franja['hora_inicio'], 0, 5) . " - " . substr($franja['hora_fin'], 0, 5) . "</td>";

        foreach ($doctores_activos as $doc) {
            $clave = $fecha_base . '_' . $franja['hora_inicio'] . '_' . $doc['id'];
            $cita = $citas[$clave] ?? null;
            echo "<td>";
            if ($cita) {
                echo "<div style='font-weight:bold;'>" . htmlspecialchars($cita['nombre_paciente']);
                if (!empty($cita['apellido1'])) {
                    echo " " . htmlspecialchars($cita['apellido1']);
                }
                echo "</div>";
                // Tratamiento debajo si existe
                if (!empty($cita['tratamiento'])) {
                    echo "<div style='font-size:13px;font-style:italic;color:#555;'>" . htmlspecialchars($cita['tratamiento']) . "</div>";
                }
                echo "<form method='post' style='margin:5px 0 0 0; display:flex; align-items:center; gap:8px;'>";
                echo "<input type='hidden' name='cambiar_estado' value='1'>";
                echo "<input type='hidden' name='id_cita' value='".htmlspecialchars($cita['id_cita'])."'>";
                echo "<select name='nuevo_estado' class='badge-select-estado' style='background:" . htmlspecialchars($estados[$cita['id_estado_cita']]['color']) . ";color:#fff;border-radius:8px;font-weight:600;font-size:14px;padding:2px 14px; border:none;' onchange='this.form.submit()'>";
                foreach ($estados as $id_estado => $data) {
                    $selected = $cita['id_estado_cita'] == $id_estado ? "selected" : "";
                    echo "<option value='$id_estado' style='background:" . htmlspecialchars($data['color']) . ";color:#222;' $selected>" . htmlspecialchars($data['nombre']) . "</option>";
                }
                echo "</select>";
                echo "</form>";
            } else {
                echo "<span class='libre'>Libre</span>";
            }
            echo "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agenda Diaria</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .badge-select-estado {
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            padding: 2px 14px;
            border: none;
            color: #fff;
            margin-top: 2px;
            margin-bottom: 2px;
        }
        .agenda-diaria td form { margin: 0; }
    </style>
</head>
<body>
<main class="container">
    <h1 class="titulo-agenda">Agenda Diaria por Doctores</h1>
    <div class="fecha-navegacion">
        <button onclick="cambiarDia(-1)">←</button>
        <input type="date" id="selector_fecha" value="<?= htmlspecialchars($fecha_base) ?>" onchange="irAFecha(this.value)">
        <button onclick="cambiarDia(1)">→</button>
    </div>
    <section id="tab_agenda" class="tab-content active">
     <h2 class="titulo-agenda"><?= traducir_dia($fecha_base) . " " . date('d/m/Y', strtotime($fecha_base)) ?></h2>
      <?php
        pintar_tabla($franjas, $doctores_activos, $fecha_base, $citas, $estados, 'am');
        pintar_tabla($franjas, $doctores_activos, $fecha_base, $citas, $estados, 'pm');
      ?>
    </section>
</main>
<script src="../assets/JS/crm_lib.js"></script>
</body>
</html>