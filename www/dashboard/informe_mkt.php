<?php
include("../assets/PHP/crm_lib.php");
include("../includes/header.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

// --- FUNCIONES DE AYUDA ---
function suma_presupuestado($conn, $where) {
    $sql = "SELECT SUM(pt.cantidad * pt.precio_unitario) as total
            FROM presupuesto_tratamiento pt
            JOIN presupuesto p ON pt.id_presupuesto = p.uuid
            WHERE $where";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    return floatval($row['total'] ?? 0);
}
function cuenta_presupuestos($conn, $where) {
    $sql = "SELECT COUNT(*) as total FROM presupuesto p WHERE $where";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    return intval($row['total'] ?? 0);
}

// --- FILTROS DE FECHA ---
$fecha_hoy = new DateTime();
$semana_actual = $fecha_hoy->format("o-W");
$semana_pasada = (clone $fecha_hoy)->modify('-7 days')->format("o-W");
$mes_actual = $fecha_hoy->format("Y-m");
$mes_pasado = (clone $fecha_hoy)->modify('-1 month')->format("Y-m");

// --- CÁLCULOS SEMANA ACTUAL ---
$where_semana = "YEARWEEK(p.fecha_creacion, 1) = YEARWEEK(CURDATE(), 1)";
$total_presup_semana = cuenta_presupuestos($conn, $where_semana);
$aceptados_semana = cuenta_presupuestos($conn, "$where_semana AND estado = 'ACEPTADO'");
$presupuestado_semana = suma_presupuestado($conn, $where_semana);
$caja_semana = suma_presupuestado($conn, "$where_semana AND p.estado = 'ACEPTADO'");

// --- CÁLCULOS SEMANA PASADA ---
$where_semana_p = "YEARWEEK(p.fecha_creacion, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)";
$total_presup_semana_p = cuenta_presupuestos($conn, $where_semana_p);
$aceptados_semana_p = cuenta_presupuestos($conn, "$where_semana_p AND estado = 'ACEPTADO'");
$presupuestado_semana_p = suma_presupuestado($conn, $where_semana_p);
$caja_semana_p = suma_presupuestado($conn, "$where_semana_p AND p.estado = 'ACEPTADO'");

// --- CÁLCULOS MES ACTUAL ---
$where_mes = "DATE_FORMAT(p.fecha_creacion, '%Y-%m') = '" . $fecha_hoy->format("Y-m") . "'";
$total_presup_mes = cuenta_presupuestos($conn, $where_mes);
$aceptados_mes = cuenta_presupuestos($conn, "$where_mes AND estado = 'ACEPTADO'");
$presupuestado_mes = suma_presupuestado($conn, $where_mes);
$caja_mes = suma_presupuestado($conn, "$where_mes AND p.estado = 'ACEPTADO'");

// --- CÁLCULOS MES PASADO ---
$where_mes_p = "DATE_FORMAT(p.fecha_creacion, '%Y-%m') = '" . (clone $fecha_hoy)->modify('-1 month')->format("Y-m") . "'";
$total_presup_mes_p = cuenta_presupuestos($conn, $where_mes_p);
$aceptados_mes_p = cuenta_presupuestos($conn, "$where_mes_p AND estado = 'ACEPTADO'");
$presupuestado_mes_p = suma_presupuestado($conn, $where_mes_p);
$caja_mes_p = suma_presupuestado($conn, "$where_mes_p AND p.estado = 'ACEPTADO'");

// % CIERRE
function porc($num, $den) {
    return ($den > 0) ? round(100 * $num / $den, 2) : null;
}
$pc_semana   = porc($aceptados_semana, $total_presup_semana);
$pc_semana_p = porc($aceptados_semana_p, $total_presup_semana_p);
$pc_mes      = porc($aceptados_mes, $total_presup_mes);
$pc_mes_p    = porc($aceptados_mes_p, $total_presup_mes_p);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Comercial / Marketing</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="container">
    <h1 style="margin-bottom: 24px;">Panel Comercial: Seguimiento de Presupuestos y Caja</h1>
    <div class="kpi-dashboard">
        <div class="kpi-card">
            <div class="kpi-title">% Cierre esta semana</div>
            <div class="kpi-value"><?= $pc_semana !== null ? number_format($pc_semana, 2) . " %" : "No hay datos" ?></div>
            <div class="kpi-title" style="margin-top: 32px;">% Cierre semana pasada</div>
            <div class="kpi-value"><?= $pc_semana_p !== null ? number_format($pc_semana_p, 2) . " %" : "No hay datos" ?></div>
            <div class="kpi-title" style="margin-top: 32px;">% Cierre este mes</div>
            <div class="kpi-value"><?= $pc_mes !== null ? number_format($pc_mes, 2) . " %" : "No hay datos" ?></div>
            <div class="kpi-title" style="margin-top: 32px;">% Cierre total mes pasado</div>
            <div class="kpi-value"><?= $pc_mes_p !== null ? number_format($pc_mes_p, 2) . " %" : "No hay datos" ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Presupuestado esta semana</div>
            <div class="kpi-value"><?= $presupuestado_semana > 0 ? number_format($presupuestado_semana, 2, ',', '.') . " €" : "No hay datos" ?></div>
            <div class="kpi-title" style="margin-top: 32px;">Presupuestado semana pasada</div>
            <div class="kpi-value"><?= $presupuestado_semana_p > 0 ? number_format($presupuestado_semana_p, 2, ',', '.') . " €" : "No hay datos" ?></div>
            <div class="kpi-title" style="margin-top: 32px;">Presupuestado este mes</div>
            <div class="kpi-value"><?= $presupuestado_mes > 0 ? number_format($presupuestado_mes, 2, ',', '.') . " €" : "No hay datos" ?></div>
            <div class="kpi-title" style="margin-top: 32px;">Presupuestado mes pasado</div>
            <div class="kpi-value"><?= $presupuestado_mes_p > 0 ? number_format($presupuestado_mes_p, 2, ',', '.') . " €" : "No hay datos" ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Caja esta semana</div>
            <div class="kpi-value"><?= $caja_semana > 0 ? number_format($caja_semana, 2, ',', '.') . " €" : "No hay datos" ?></div>
            <div class="kpi-title" style="margin-top: 32px;">Caja semana pasada</div>
            <div class="kpi-value"><?= $caja_semana_p > 0 ? number_format($caja_semana_p, 2, ',', '.') . " €" : "No hay datos" ?></div>
            <div class="kpi-title" style="margin-top: 32px;">Caja este mes</div>
            <div class="kpi-value"><?= $caja_mes > 0 ? number_format($caja_mes, 2, ',', '.') . " €" : "No hay datos" ?></div>
            <div class="kpi-title" style="margin-top: 32px;">Caja mes pasado</div>
            <div class="kpi-value"><?= $caja_mes_p > 0 ? number_format($caja_mes_p, 2, ',', '.') . " €" : "No hay datos" ?></div>
        </div>
    </div>
</main>
</body>
</html>