<?php
session_start();
include("../assets/PHP/crm_lib.php");
$conn = conecta_db();

$tabla = ($_GET['tabla'] == 'documento') ? 'documento_administrativo' : 'consentimiento';
$id = intval($_GET['id']);
$row = $conn->query("SELECT archivo_nombre, archivo_tipo, archivo FROM $tabla WHERE id=$id")->fetch_assoc();

if (!$row) { echo "No encontrado."; exit; }
$contenido = base64_decode($row['archivo']);
header('Content-Type: '.$row['archivo_tipo']);
header('Content-Disposition: inline; filename="'.$row['archivo_nombre'].'"');
echo $contenido;
?>