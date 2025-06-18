<?php
if (session_status() === PHP_SESSION_NONE) {
session_start();
}
// Verificar sesión activa
if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit;
}
// Aseguramos nombre visible desde la sesión
if (!isset($_SESSION["nombre_visible"])) {
    $_SESSION["nombre_visible"] = "Usuario";
}
$perfilUsuario = $_SESSION["perfil"] ?? "?";
$nombreUsuario = $_SESSION["nombre_visible"];
$idUsuario = $_SESSION["usuario"] ?? "?";

$nombrePerfil = match($perfilUsuario) {
    1 => "Admin",
    2 => "Ventas",
    3 => "Doctor",
    4 => "Recepción",
    default => "Desconocido"
};

// Congiguración del menú.
$menuItems = [
    [
        "menuTitle" => "INICIO",
        "icon" => "fas fa-home",
        "url" => "/index.php"
    ],
    [   
        "menuTitle" => "Agendas",
        "icon" => "fas fa-calendar-alt",
        "url" => "/cita/agenda.php",
        "popup" => true
    ],
    [
        "menuTitle" => "Pacientes",
        "icon" => "fas fa-file-medical",
        "url" => "/paciente/consulta_pacientes.php"
    ],
    [
        "menuTitle" => "Presupuestos",
        "icon" => "fas fa-euro-sign",
        "url" => "/presupuesto/presupuestos.php"
    ],
    [
        "menuTitle" => "IA",
        "icon" => "fas fa-brain",
        "url" => "/dashboard/diagnosticos_pacientes.php"
    ],
    [
        "menuTitle" => "MKT",
        "icon" => "fas fa-chart-line",
        "url" => "/dashboard/informe_mkt.php",
        "allowedProfiles" => [1, 2]
    ],
    [
        "menuTitle" => "Admin",
        "icon" => "fas fa-user-cog",
        "url" => "/dashboard/backoffice.php",
        "allowedProfiles" => [1]
    ]   
];
// Obtenemos el nombre del archivo PHP actual. 
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRM Odontológico</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<!-- Este bloque protege el acceso (requiere sesión iniciada), 
 obtiene y muestra el nombre del usuario, genera un menú lateral dinámico según el perfil,
resalta la página actual y ofrece la opción de cerrar sesión.-->
<div class="layout">
    <nav class="menu-lateral">
        <div class="usuario-conectado">
            Usuario conectado: <strong><?= htmlspecialchars(string: $nombreUsuario) ?></strong>
        </div>

<ul>
<?php foreach ($menuItems as $item): ?>
    <?php
    if (isset($item["allowedProfiles"]) && !in_array($perfilUsuario, $item["allowedProfiles"])) continue;
    $activeClass = (basename($item['url']) === $currentPage) ? 'active' : '';
    $isPopup = !empty($item["popup"]);
    ?>
    <li class="<?= $activeClass ?>">
        <a href="<?= $isPopup ? 'javascript:void(0);' : $item['url'] ?>"
           <?php if ($isPopup): ?>
               onclick="win_agenda('<?= $item['url'] ?>', 'popupAgenda'); return false;"
           <?php endif; ?>
        >
            <?php if (isset($item['icon'])): ?><i class="<?= $item['icon'] ?>"></i><?php endif; ?>
            <?= $item['menuTitle'] ?>
        </a>
    </li>
<?php endforeach; ?>
</ul>


        <div class="cerrar-sesion">
            <a href="/logout.php" class="btn-cerrar">Cerrar sesión</a>
        </div>
    </nav>