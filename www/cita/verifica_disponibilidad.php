<?php
header('Content-Type: application/json');
include("../assets/PHP/crm_lib.php");
$conn = conecta_db();
if ($conn == "KO") {
    echo json_encode(["ok" => false, "error" => "Error de conexión"]);
    exit;
}

// Recibir parámetros vía POST (mejor que GET)
$id_agenda   = $_POST['id_agenda']   ?? '';
$fecha       = $_POST['fecha']       ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';

// Validación básica
if (empty($id_agenda) || empty($fecha) || empty($hora_inicio)) {
    echo json_encode([
        "ok" => false,
        "disponible" => false,
        "motivo" => "Faltan datos obligatorios"
    ]);
    exit;
}

// 1. ¿Ya existe una cita en esa agenda, fecha y franja?
$sql_solape = "SELECT COUNT(*) as total FROM cita 
    WHERE id_agenda = ? AND fecha = ? AND hora_inicio = ?";
$stmt_check = $conn->prepare($sql_solape);
$stmt_check->bind_param("sss", $id_agenda, $fecha, $hora_inicio);
$stmt_check->execute();
$res_check = $stmt_check->get_result();
$row_check = $res_check->fetch_assoc();
$stmt_check->close();

if ($row_check['total'] > 0) {
    echo json_encode([
        "ok" => true,
        "disponible" => false,
        "motivo" => "Ya existe una cita en esta franja"
    ]);
    exit;
}

// 2. ¿El doctor tiene esa franja activa ese día?
$stmt_doctor = $conn->prepare("SELECT id_doctor FROM agenda WHERE id = ?");
$stmt_doctor->bind_param("s", $id_agenda);
$stmt_doctor->execute();
$res_doctor = $stmt_doctor->get_result();
$row_doctor = $res_doctor->fetch_assoc();
$id_doctor = $row_doctor['id_doctor'] ?? null;
$stmt_doctor->close();

if (!$id_doctor) {
    echo json_encode([
        "ok" => false,
        "disponible" => false,
        "motivo" => "Agenda no encontrada o inválida"
    ]);
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
    echo json_encode([
        "ok" => true,
        "disponible" => false,
        "motivo" => "El doctor no tiene esta franja activa ese día"
    ]);
    exit;
}
$stmt_franja->close();

// Todo OK
echo json_encode([
    "ok" => true,
    "disponible" => true,
    "motivo" => "Franja disponible"
]);
$conn->close();
?>
