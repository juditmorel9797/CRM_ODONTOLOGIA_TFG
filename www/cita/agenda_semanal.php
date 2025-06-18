<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../assets/PHP/crm_lib.php");
include("../includes/menuagenda.php");

$conn = conecta_db();
if ($conn === "KO") {
    echo "<p>Error de conexión.</p>";
    return;
}

// Cambiar estado de cita (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'], $_POST['id_cita'], $_POST['nuevo_estado'])) {
    $id_cita = $conn->real_escape_string($_POST['id_cita']);
    $nuevo_estado = $conn->real_escape_string($_POST['nuevo_estado']);
    $conn->query("UPDATE cita SET id_estado_cita = '$nuevo_estado' WHERE id = '$id_cita'");
    header("Location: agenda_semanal.php?fecha=" . urlencode($_GET['fecha'] ?? date('Y-m-d')));
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

// Variables
$id_usuario = $_SESSION["id_usuario"];
$perfil = $_SESSION["perfil"];
$fecha_base = $_GET['fecha'] ?? date('Y-m-d');
$id_doctor_filtro = $_GET['id_doctor'] ?? 'todos';

// Calcular lunes de la semana
$lunes = date('Y-m-d', strtotime('monday this week', strtotime($fecha_base)));
$dias_semana = [];
for ($i = 0; $i < 5; $i++) {
    $dias_semana[] = date('Y-m-d', strtotime("+$i days", strtotime($lunes)));
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
$sql_doctores = "SELECT u.id, u.nombre_visible
                 FROM usuario u
                 JOIN agenda a ON u.id = a.id_doctor
                 WHERE a.activo = 1
                 GROUP BY u.id
                 ORDER BY u.nombre_visible";
$result_doctores = $conn->query($sql_doctores);
while ($row = $result_doctores->fetch_assoc()) {
    $doctores_activos[] = $row;
}

// Citas de la semana (paciente o primeras visitas, y tratamiento)
$citas = [];
$fin_semana = end($dias_semana);

if ($perfil == 3) {
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
            WHERE a.id_doctor = ? AND c.fecha BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $id_usuario, $lunes, $fin_semana);
} else {
    if ($id_doctor_filtro !== 'todos') {
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
                WHERE a.id_doctor = ? AND c.fecha BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $id_doctor_filtro, $lunes, $fin_semana);
    } else {
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
                WHERE c.fecha BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $lunes, $fin_semana);
    }
}
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

function pintar_tabla($franjas, $dias_semana, $doctor, $citas, $estados, $rango = 'am') {
    $rango_title = $rango === 'am' ? 'Franja Mañana' : 'Franja Tarde';
    $franjas_filtradas = array_values(array_filter($franjas, function($f) use ($rango) {
        return $rango === 'am' ? $f['hora_inicio'] < '16:00:00' : $f['hora_inicio'] >= '16:00:00';
    }));

    if (empty($franjas_filtradas)) return;

    echo "<h3>".htmlspecialchars($doctor['nombre_visible'])." - $rango_title</h3>";
    echo "<table class='agenda-diaria'>";
    echo "<thead><tr><th>Hora</th>";
    foreach ($dias_semana as $dia) {
        echo "<th>" . traducir_dia($dia) . " " . date('d/m', strtotime($dia)) . "</th>";
    }
    echo "</tr></thead><tbody>";

    foreach ($franjas_filtradas as $franja) {
        echo "<tr>";
        echo "<td>" . substr($franja['hora_inicio'], 0, 5) . " - " . substr($franja['hora_fin'], 0, 5) . "</td>";
        foreach ($dias_semana as $dia) {
            $clave = $dia . '_' . $franja['hora_inicio'] . '_' . $doctor['id'];
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
    <title>Agenda Semanal</title>
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
    <script src="../assets/JS/crm_lib.js"></script>
</head>
<body>
<main class="container">
    <h1 class="titulo-agenda">Agenda Semanal por Doctores</h1>

    <!-- Controles de fecha -->
    <div class="fecha-navegacion">
        <button onclick="cambiarSemana(-7)">←</button>
        <input type="date" id="selector_fecha" value="<?= htmlspecialchars($fecha_base) ?>" onchange="irASemana(this.value)">
        <button onclick="cambiarSemana(7)">→</button>
    </div>

    <!-- Filtro por doctor -->
    <form method="GET" class="filtro-doctor">
        <label for="id_doctor">Filtrar por doctor:</label>
        <select name="id_doctor" id="id_doctor" onchange="this.form.submit()">
            <option value="todos" <?= $id_doctor_filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
            <?php foreach ($doctores_activos as $doc): ?>
                <option value="<?= $doc['id'] ?>" <?= $id_doctor_filtro == $doc['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($doc['nombre_visible']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha_base) ?>">
    </form>

<?php
foreach ($doctores_activos as $doctor):
    // Si hay filtro de doctor y no coincide, saltar
    if ($id_doctor_filtro !== 'todos' && $id_doctor_filtro != $doctor['id']) continue;
    // Si eres doctor y el filtro es "todos", mostrar solo tu agenda
    if ($perfil == 3 && $id_doctor_filtro === 'todos' && $doctor['id'] != $id_usuario) continue;

    pintar_tabla($franjas, $dias_semana, $doctor, $citas, $estados, 'am');
    pintar_tabla($franjas, $dias_semana, $doctor, $citas, $estados, 'pm');
endforeach;
?>
</main>
</body>
</html>