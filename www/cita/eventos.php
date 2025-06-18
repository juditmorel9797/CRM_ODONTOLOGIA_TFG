<?php
include("../assets/PHP/crm_lib.php");
$conn = conecta_db();
if ($conn == "KO") {
    echo json_encode([]);
    exit;
}

$agenda_id = $_GET['agenda_id'] ?? 'todas';

if ($agenda_id === 'todas') {
    $sql = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, 
                   p.nombre, p.apellido1, a.nombre_agenda
            FROM cita c
            JOIN paciente p ON c.id_paciente = p.id
            JOIN agenda a ON c.id_agenda = a.id";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, 
                   p.nombre, p.apellido1, a.nombre_agenda
            FROM cita c
            JOIN paciente p ON c.id_paciente = p.id
            JOIN agenda a ON c.id_agenda = a.id
            WHERE c.id_agenda = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $agenda_id);
}

$stmt->execute();
$result = $stmt->get_result();
$eventos = [];

while ($row = $result->fetch_assoc()) {
    $eventos[] = [
        'id' => $row['id'],
        'title' => $row['nombre'] . ' ' . $row['apellido1'] . ' (' . $row['nombre_agenda'] . ')',
        'start' => $row['fecha'] . 'T' . $row['hora_inicio'],
        'end' => $row['fecha'] . 'T' . $row['hora_fin'],
        'color' => '#4A90E2'
    ];
}

header('Content-Type: application/json');
echo json_encode($eventos);
