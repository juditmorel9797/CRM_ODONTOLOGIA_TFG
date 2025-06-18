<?php
// Leer y decodificar el cuerpo de la petición
$D = file_get_contents('php://input');
$data = json_decode($D, true);

$id_llamada_api = $data["id_llamada_api"] ?? null;
$edad = $data["edad_paciente"] ?? null;
$image64 = $data["imagen"] ?? null;

$code = 200;

// Obtener la API KEY desde variable de entorno
$api_key = getenv('MISTRAL_API_KEY');

// URL de la API de Mistral
$url = "https://api.mistral.ai/v1/chat/completions";

// Cabeceras de autenticación (puedes mover el token a un .env si quieres mayor seguridad)
$HD = [
    "Content-Type: application/json",
    "Accept: application/json",
    "Authorization: Bearer $api_key"
];

// Mensaje con imagen base64 y texto en español para el diagnóstico
$content = [
    [ "type" => "text", "text" => "Analiza esta radiografía panorámica dental. Devuelve primero el diagnóstico, luego el tratamiento recomendado. Separa claramente ambas secciones y responde en español." ],
    [ "type" => "image_url", "image_url" => "data:image/png;base64,$image64" ]
];

$msg = [
    [ "role" => "user", "content" => $content ]
];

// Cuerpo de la petición
$body = [
    "model" => "pixtral-12b-2409",
    "messages" => $msg
];

$body_j = json_encode($body);

// Configurar y lanzar la llamada CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $HD);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body_j);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$result = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decodificar la respuesta
$R = json_decode($result);

// Extraer el contenido del diagnóstico completo (puede incluir tratamiento)
$raw = $R->choices[0]->message->content ?? "Diagnóstico no disponible";
$tok = $R->usage->total_tokens ?? 0;

// Separar diagnóstico y tratamiento si es posible
$diag = $raw;
$tratamiento = null;

// Buscar separador explícito
if (stripos($raw, "Tratamiento recomendado:") !== false) {
    [$parte1, $parte2] = explode("Tratamiento recomendado:", $raw, 2);
    $diag = trim($parte1);
    $tratamiento = trim($parte2);
} elseif (stripos($raw, "Tratamiento:") !== false) {
    [$parte1, $parte2] = explode("Tratamiento:", $raw, 2);
    $diag = trim($parte1);
    $tratamiento = trim($parte2);
}

// Respuesta final como JSON
$Rx = [
    "id_llamada_api" => $id_llamada_api,
    "diagnostico" => $diag,
    "tratamiento_recomendado" => $tratamiento,
    "tokens" => $tok
];

$RxJ = json_encode($Rx);
http_response_code($code);
echo $RxJ;
?>