<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit;
}
include("../assets/PHP/crm_lib.php");
$conn = conecta_db();
if ($conn == "KO") {
    die("Error de conexión");
}
$id_cita        = generate_uuid_v4();
$id_paciente    = trim($_POST['id_paciente'] ?? '');
$nombre_manual  = trim($_POST['nombre_paciente_manual'] ?? '');
$id_agenda      = $_POST['id_agenda'] ?? '';
$fecha          = $_POST['fecha'] ?? '';
$hora_inicio    = $_POST['hora_inicio'] ?? '';
$id_tratamiento = $_POST['id_tratamiento'] ?? '';
$duracion_manual = $_POST['duracion_minutos'] ?? '';
$id_estado_cita = $_POST['id_estado_cita'] ?? 1;
$observaciones  = $_POST['observaciones'] ?? '';
$id_usuario     = $_SESSION['id_usuario'] ?? '';

// Validación: Debe haber id_paciente o nombre_manual, pero no ambos vacíos
if (
    (empty($id_paciente) && $nombre_manual === '') ||
    empty($id_agenda) || empty($fecha) ||
    empty($hora_inicio) || empty($id_tratamiento) || empty($duracion_manual)
) {
    echo "Debes seleccionar un paciente o escribir el nombre, y rellenar todos los datos obligatorios.";
    exit;
}
if ($hora_inicio === 'undefined') {
    echo "Error: hora_inicio no está definido correctamente.";
    exit;
}

// Calcular duración y hora_fin
$duracion = (is_numeric($duracion_manual) && intval($duracion_manual) > 0)
    ? intval($duracion_manual)
    : 30;
try {
    $dt_inicio = new DateTime($hora_inicio);
    $dt_fin = clone $dt_inicio;
    $dt_fin->modify("+{$duracion} minutes");
    $hora_fin = $dt_fin->format("H:i:s");
} catch (Exception $e) {
    echo "Error procesando hora: " . $e->getMessage();
    exit;
}

// 1. CONTROL DE SOLAPAMIENTO
$sql_solape = "SELECT COUNT(*) as total FROM cita
    WHERE id_agenda = ? AND fecha = ? AND hora_inicio = ?";
$stmt_check = $conn->prepare($sql_solape);
$stmt_check->bind_param("sss", $id_agenda, $fecha, $hora_inicio);
$stmt_check->execute();
$res_check = $stmt_check->get_result();
$row_check = $res_check->fetch_assoc();
if ($row_check['total'] > 0) {
    echo "Ya existe una cita en esa franja para este doctor. No se puede duplicar.";
    exit;
}
$stmt_check->close();

// 2. CONTROL DE FRANJA HORARIA ASIGNADA AL DOCTOR
$stmt_doctor = $conn->prepare("SELECT id_doctor FROM agenda WHERE id = ?");
$stmt_doctor->bind_param("s", $id_agenda);
$stmt_doctor->execute();
$res_doctor = $stmt_doctor->get_result();
$row_doctor = $res_doctor->fetch_assoc();
$id_doctor = $row_doctor['id_doctor'] ?? null;
$stmt_doctor->close();

if (!$id_doctor) {
    echo "Agenda no encontrada o inválida.";
    exit;
}
$days_map = ['Monday'=>'L','Tuesday'=>'M','Wednesday'=>'X','Thursday'=>'J','Friday'=>'V','Saturday'=>'S','Sunday'=>'D'];
$dow_full = date('l', strtotime($fecha));
$dow = $days_map[$dow_full] ?? null;
$sql_franja = "SELECT df.id FROM doctor_franja df
    JOIN franja_horaria fh ON df.id_franja = fh.id
    WHERE df.id_doctor = ? AND df.dia = ? AND fh.hora_inicio = ? AND df.activo = 1";
$stmt_franja = $conn->prepare($sql_franja);
$stmt_franja->bind_param("sss", $id_doctor, $dow, $hora_inicio);
$stmt_franja->execute();
$res_franja = $stmt_franja->get_result();
if ($res_franja->num_rows == 0) {
    echo "El doctor no tiene esa franja activa ese día.";
    exit;
}
$stmt_franja->close();

// 3. DETERMINAR CAMPOS PACIENTE (registrado o primera visita)
$id_pv = null;
if (empty($id_paciente)) {
    // Crear registro en paciente_primerasv
    $id_pv = generate_uuid_v4();
    $stmt_pv = $conn->prepare("INSERT INTO paciente_primerasv (id, nombre) VALUES (?, ?)");
    $stmt_pv->bind_param("ss", $id_pv, $nombre_manual);
    if (!$stmt_pv->execute()) {
        echo "Error creando primer registro de visita.";
        exit;
    }
    $stmt_pv->close();
}

// ... Después de crear $id_pv si es necesario ...

// 4. INSERTAR LA CITA con el campo correcto
$id_paciente_final = !empty($id_paciente) ? $id_paciente : null;
$id_pv_final       = !empty($id_paciente) ? null        : $id_pv;

$stmt = $conn->prepare("INSERT INTO cita (
    id, id_paciente, id_pv, id_agenda, id_tratamiento_base,
    fecha, hora_inicio, hora_fin, observaciones,
    id_estado_cita, creado_por
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sssssssssis",
    $id_cita,
    $id_paciente_final,
    $id_pv_final,
    $id_agenda,
    $id_tratamiento,
    $fecha,
    $hora_inicio,
    $hora_fin,
    $observaciones,
    $id_estado_cita,
    $id_usuario
);

if ($stmt->execute()) {
    echo '<script>
        if (window.opener) {
            window.opener.location.reload();
        }
        window.close();
    </script>';
    exit;
} else {
    echo "Error al guardar la cita: " . $conn->error;
}

$stmt->close();
$conn->close();
?>