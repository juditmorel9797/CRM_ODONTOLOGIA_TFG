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
        "menuTitle" => "Agenda",
        "icon" => "fas fa-agenda",
        "url" => "../cita/agenda.php"
    ],
    [   
        "menuTitle" => "Diario",
        "icon" => "fas fa-agenda-diaria",
        "url" => "../cita/agenda_diaria.php",
        "popup" => true
    ],
    [
        "menuTitle" => "Semanal",
        "icon" => "fas fa-agenda-semanal",
        "url" => "../cita/agenda_semanal.php"
    ],
    [
        "menuTitle" => "Gestor",
        "icon" => "fas fa-gestor-citas",
        "url" => "../cita/gestor_citas.php"
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
                <h1>Agenda de Citas</h1>
            Usuario conectado: <strong><?= htmlspecialchars($nombreUsuario) ?></strong>
        </div>

        <ul>
            <?php foreach ($menuItems as $item): ?>
                <?php
                if (isset($item["allowedProfiles"]) && !in_array($perfilUsuario, $item["allowedProfiles"])) continue;
                $activeClass = (basename($item['url']) === $currentPage) ? 'active' : '';
                ?>
                <li class="<?= $activeClass ?>">
                    <a href="<?= $item['url'] ?>">
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