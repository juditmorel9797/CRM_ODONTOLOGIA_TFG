<?php
session_start();
include("../assets/PHP/crm_lib.php");

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit;
}

// Validar el parámetro obligatorio
$id_radiografia = $_GET["id_radiografia"] ?? null;
if (!$id_radiografia) {
    echo "Falta el parámetro id_radiografia";
    exit;
}

// Conectar a la base de datos
$conn = conecta_db();
if ($conn === "KO") {
    echo "Error de conexión";
    exit;
}

// Obtener la imagen Base64 y datos del paciente
$sql = "SELECT r.imagen_base64, r.id_paciente, p.fecha_nacimiento
        FROM radiografias r
        JOIN paciente p ON r.id_paciente = p.id
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_radiografia);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "Radiografía no encontrada";
    exit;
}

$row = $res->fetch_assoc();
$imagen = $row["imagen_base64"];
$id_paciente = $row["id_paciente"];
$fecha_nacimiento = new DateTime($row["fecha_nacimiento"]);
$edad_paciente = (new DateTime())->diff($fecha_nacimiento)->y;

$stmt->close();

// Crear identificador único para esta llamada a la IA
$id_llamada_api = uniqid("llamada_");

// Preparar el payload JSON para la API de IA
$datos_api_ai = json_encode([
    "imagen" => $imagen,
    "id_llamada_api" => $id_llamada_api,
    "edad_paciente" => $edad_paciente
]);

// Configurar la llamada a la API local
$url = "http://localhost/IA/diagnostica.php";
$headers = [
    "Content-Type: application/json;charset=UTF-8",
    "Accept: application/json"
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $datos_api_ai);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$result = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Validar la respuesta de la API
if ($httpcode === 200 && $result) {
    $R = json_decode($result, true);
    $texto_completo = $R["diagnostico"] ?? "Diagnóstico no disponible";
    $tokens = $R["tokens"] ?? 0;

    // Separar diagnóstico y tratamiento si vienen juntos
    $bloques = preg_split('/### Tratamiento Recomendado/i', $texto_completo);
    $solo_diagnostico = trim($bloques[0] ?? $texto_completo);
    $tratamiento_recomendado = trim($bloques[1] ?? '');

    // Reinsertar conexión para guardar en base de datos
    $conn = conecta_db();
    if ($conn === "KO") {
        echo "Error al reconectar para guardar diagnóstico";
        exit;
    }

    // Insertar diagnóstico (con tratamiento si está presente)
    $stmt = $conn->prepare("INSERT INTO diagnostico (id_radiografia, id_paciente, id_llamada_api, edad_paciente, diagnostico, tratamiento_recomendado, tokens) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississi", $id_radiografia, $id_paciente, $id_llamada_api, $edad_paciente, $solo_diagnostico, $tratamiento_recomendado, $tokens);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // Redirigir con mensaje de éxito
     header("Location: ../paciente/diagnosticos.php?id_paciente=$id_paciente&ok=1");
     exit;

} else {
    // Error al llamar a la API
    echo "<p>Error al llamar a la API. Código HTTP: $httpcode</p>";
    echo "<pre>$result</pre>";
}
?>