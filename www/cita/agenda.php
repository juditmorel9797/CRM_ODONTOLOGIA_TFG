<?php
// Iniciar sesión SOLO si no está ya iniciada (esto es universal y seguro)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../assets/PHP/crm_lib.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}
$id_usuario = $_SESSION["id_usuario"];
$perfil = $_SESSION["perfil"];
$id_agenda_usuario = '';

if ($perfil == 3) {
    $stmt = $conn->prepare("SELECT id FROM agenda WHERE id_doctor = ? AND activo = 1 LIMIT 1");
    $stmt->bind_param("s", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($id_agenda_usuario);
    $stmt->fetch();
    $stmt->close();
}
$fecha_base = $_GET['fecha'] ?? date('Y-m-d');


// CAMBIO DE ESTADO DE CITA (inline)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'], $_POST['id_cita'], $_POST['nuevo_estado'])) {
    $id_cita = $conn->real_escape_string($_POST['id_cita']);
    $nuevo_estado = $conn->real_escape_string($_POST['nuevo_estado']);
    $conn->query("UPDATE cita SET id_estado_cita = '$nuevo_estado' WHERE id = '$id_cita'");
    header("Location: agenda.php?fecha=" . urlencode($_GET['fecha'] ?? date('Y-m-d')));
    exit;
}
include("../includes/menuagenda.php");

// TRAER LISTA DE ESTADOS DINÁMICAMENTE
$estados = [];
$res_estados = $conn->query("SELECT id, nombre, color_hex FROM estado_cita ORDER BY id");
while ($row = $res_estados->fetch_assoc()) {
    $estados[$row['id']] = [
        'nombre' => $row['nombre'],
        'color' => $row['color_hex']
    ];
}

// Calcular lunes de la semana
$lunes = date('Y-m-d', strtotime('monday this week', strtotime($fecha_base)));
$dias_semana = [];
for ($i = 0; $i < 5; $i++) {
    $dias_semana[] = date('Y-m-d', strtotime("+$i days", strtotime($lunes)));
}

// Obtener franjas horarias
$sql_franjas = "SELECT hora_inicio, hora_fin FROM franja_horaria ORDER BY hora_inicio";
$result_franjas = $conn->query($sql_franjas);
$franjas = [];
while ($row = $result_franjas->fetch_assoc()) {
    $franjas[] = $row;
}

// Obtener doctores activos con su agenda
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

// Obtener citas
// Obtener citas
$citas = [];
$fin_semana = end($dias_semana);

$sql_base = "SELECT c.fecha, c.hora_inicio, c.hora_fin, 
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
            ";

if ($perfil == 3) {
    $sql = $sql_base . " WHERE a.id_doctor = ? AND c.fecha BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $id_usuario, $lunes, $fin_semana);
} else {
    $sql = $sql_base . " WHERE c.fecha BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $lunes, $fin_semana);
}
$stmt->execute();
$result_citas = $stmt->get_result();
while ($row = $result_citas->fetch_assoc()) {
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

// ----------- FUNCIÓN PINTAR TABLA -------------
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
            // Comprobar si alguna cita cubre esta franja
            $cita_encontrada = null;
            foreach ($citas as $cita) {
                if (
                    $cita['fecha'] === $fecha_base &&
                    $cita['id_doctor'] == $doc['id'] &&
                    $franja['hora_inicio'] >= $cita['hora_inicio'] &&
                    $franja['hora_inicio'] < $cita['hora_fin']
                ) {
                    $cita_encontrada = $cita;
                    break;
                }
            }

            // Si es el inicio de la cita, calculamos el rowspan
            if ($cita_encontrada && $franja['hora_inicio'] === $cita_encontrada['hora_inicio']) {
                // Calcular cuántas franjas abarca
                $rowspan = 1;
                $fin_cita = $cita_encontrada['hora_fin'];
                for ($j = $i + 1; $j < $total_franjas; $j++) {
                    if ($franjas_filtradas[$j]['hora_inicio'] < $fin_cita) {
                        $rowspan++;
                    } else {
                        break;
                    }
                }
                echo "<td rowspan=\"$rowspan\" style=\"vertical-align:middle;\">";
                // Paciente
echo "<div style='font-weight:bold;'>" . htmlspecialchars($cita_encontrada['nombre_paciente']);
if (!empty($cita_encontrada['apellido1'])) {
    echo " " . htmlspecialchars($cita_encontrada['apellido1']);
}
echo "</div>";
// Mostrar tratamiento debajo si existe
if (!empty($cita_encontrada['tratamiento'])) {
    echo "<div style='font-size:13px;font-style:italic;color:#555;'>" . htmlspecialchars($cita_encontrada['tratamiento']) . "</div>";
}
                // Select estado inline
                echo "<form method='post' style='margin:5px 0 0 0; display:flex; align-items:center; gap:8px;'>";
                echo "<input type='hidden' name='cambiar_estado' value='1'>";
                echo "<input type='hidden' name='id_cita' value='".htmlspecialchars($cita_encontrada['id_cita'])."'>";
                echo "<select name='nuevo_estado' class='badge-select-estado' style='background:" . htmlspecialchars($estados[$cita_encontrada['id_estado_cita']]['color']) . ";color:#fff;border-radius:8px;font-weight:600;font-size:14px;padding:2px 14px; border:none;' onchange='this.form.submit()'>";
                foreach ($estados as $id_estado => $data) {
                    $selected = $cita_encontrada['id_estado_cita'] == $id_estado ? "selected" : "";
                    echo "<option value='$id_estado' style='background:" . htmlspecialchars($data['color']) . ";color:#222;' $selected>" . htmlspecialchars($data['nombre']) . "</option>";
                }
                echo "</select>";
                // Icono de reloj si está en espera (id 7 o nombre contiene "espera")
                if (
                    (isset($cita_encontrada['id_estado_cita']) && $cita_encontrada['id_estado_cita'] == 7) ||
                    (isset($cita_encontrada['estado_nombre']) && stripos($cita_encontrada['estado_nombre'], 'espera') !== false)
                ) {
                    echo "<img src='../assets/images/reloj.png' alt='En Espera' style='width:20px;height:20px;vertical-align:middle;margin-left:4px;'>";
                }
                echo "</form>";
                echo "</td>";
            } elseif ($cita_encontrada) {
                // Si la franja está cubierta pero no es el inicio, **no pintamos la celda**
                continue;
            } else {
                // Si no hay cita, pintamos "Libre"
                echo "<td>";
                echo "<a href='#' class='libre'
                    data-fecha='$fecha_base'
                    data-hora='" . $franja['hora_inicio'] . "'
                    data-id-agenda='" . $doc['id_agenda'] . "'>
                    Libre
                </a>";
                echo "</td>";
            }
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
    <title>Agenda del Día</title>
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
        .badge-select-estado option {
            color: #222;
            font-weight: 500;
        }
        .agenda-diaria td form { margin: 0; }
    </style>
</head>
<body>
<main class="container">
    <h1 class="titulo-agenda">Agenda del Día</h1>
    <div class="fecha-navegacion">
        <button onclick="cambiardeDia(-1)">←</button>
        <input type="date" id="selector_fecha" value="<?= htmlspecialchars($fecha_base) ?>" onchange="irAunaFecha(this.value)">
        <button onclick="cambiardeDia(1)">→</button>
    </div>

    <section id="tab_agenda" class="tab-content active">
        <h2 class="titulo-agenda"><?= traducir_dia($fecha_base) . " " . date('d/m/Y', strtotime($fecha_base)) ?></h2>
        <?php
        if ($perfil == 3 && !empty($id_agenda_usuario)) {
            pintar_tabla($franjas, [[
                'id' => $id_usuario,
                'nombre_visible' => $_SESSION["usuario"],
                'id_agenda' => $id_agenda_usuario
            ]], $fecha_base, $citas, $estados, 'am');
            pintar_tabla($franjas, [[
                'id' => $id_usuario,
                'nombre_visible' => $_SESSION["usuario"],
                'id_agenda' => $id_agenda_usuario
            ]], $fecha_base, $citas, $estados, 'pm');
        } else {
            pintar_tabla($franjas, $doctores_activos, $fecha_base, $citas, $estados, 'am');
            pintar_tabla($franjas, $doctores_activos, $fecha_base, $citas, $estados, 'pm');
        }
        ?>
    </section>
    <script src="../assets/JS/crm_lib.js"></script>
</main>
</body>
</html>