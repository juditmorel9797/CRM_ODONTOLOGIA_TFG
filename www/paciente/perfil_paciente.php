<?php
include("../assets/PHP/crm_lib.php");
include("../includes/header.php");

$conn = conecta_db();
if ($conn == "KO") {
    echo "Error de conexión";
    exit;
}

$id = $_GET['id'];

// Obtener datos del paciente
$sql = "SELECT p.*, r.nombre AS nombre_referido
        FROM paciente p
        LEFT JOIN referido r ON p.id_referido = r.id
        WHERE p.id = '$id'";
$result = $conn->query($sql);
$paciente = $result->fetch_assoc();

$fecha_nacimiento = new DateTime($paciente['fecha_nacimiento']);
$hoy = new DateTime();
$edad = $hoy->diff($fecha_nacimiento)->y;

// Radiografías
$sql_rad = "SELECT * FROM radiografias WHERE id_paciente = '$id' ORDER BY fecha DESC";
$result_rad = $conn->query($sql_rad);

// Diagnósticos
$sql_diag = "SELECT d.*, r.fecha AS fecha_radiografia FROM diagnostico d
              JOIN radiografias r ON d.id_radiografia = r.id
              WHERE d.id_paciente = '$id' ORDER BY d.fecha DESC";
$result_diag = $conn->query($sql_diag);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil del Paciente</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script type="text/javascript" src="../assets/JS/crm_lib.js"></script>
</head>
<body>
<main class="container">
<h2> PACIENTE: <?= $paciente['nombre']?> <?= $paciente['apellido1']?> <?= $paciente['apellido2']?> – NHC: <?= $paciente['nhc'] ?></h2>

    <!-- PESTAÑAS -->
    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('registro')">Ficha Registro</button>
        <button class="tab-btn" onclick="showTab('historial')">Historial Clínico</button>
        <button class="tab-btn" onclick="showTab('presupuestos')">Presupuestos</button>
        <button class="tab-btn" onclick="showTab('radiografias')">Radiografías</button>
        <button class="tab-btn" onclick="showTab('diagnosticos')">Diagnósticos</button>
        <button class="tab-btn" onclick="showTab('consentimientos')">Consentimientos</button>
        <button class="tab-btn" onclick="showTab('documentos')">Documentos</button>
    </div>

    <!-- FICHA DE REGISTRO -->
    <section id="registro" class="tab-content active">
    <div class="ficha-header">
        <div>
            <p><strong>NHC:</strong> <?= $paciente['nhc'] ?></p>
            <p><strong>Edad:</strong> <?= $edad ?> años</p>
        </div>
        <div class="btn-container">
            <form action="javascript:win('../paciente/editar_paciente.php','editar','<?= $paciente['id'] ?>')">
                <input type="hidden" id="<?= $paciente['id'] ?>" value="<?= $paciente['id'] ?>">
                <button class="edit-btn">Modificar</button>
            </form>
            <?php if ($_SESSION["perfil"] == 1): ?>
    <form action="../paciente/borrar_paciente.php" method="post" onsubmit="return confirm('Confirma borrado?');">
        <input type="hidden" name="id" value="<?= $paciente['id'] ?>">
        <button class="delete-btn">Borrar</button>
    </form>
    <?php endif; ?>
    
    </div>

    <div class="ficha-section">
        <div class="ficha-card">
            <h3>Datos Personales</h3>
            <p><strong>Nombres:</strong> <?= $paciente['nombre'] ?></p>
            <p><strong>Apellidos:</strong> <?= $paciente['apellido1'] ?> <?= $paciente['apellido2'] ?></p>
            <p><strong>Fecha nacimiento:</strong> <?= $paciente['fecha_nacimiento'] ?></p>
            <p><strong>DNI:</strong> <?= $paciente['dni'] ?></p>
        </div>

        <div class="ficha-card">
            <h3>Contacto</h3>
            <p><strong>Teléfono:</strong> <?= $paciente['telefono'] ?></p>
            <p><strong>Correo:</strong> <?= $paciente['correo'] ?></p>
        </div>

        <div class="ficha-card">
            <h3>Dirección</h3>
            <p><strong>Dirección:</strong> <?= $paciente['direccion'] ?></p>
            <p><strong>CP:</strong> <?= $paciente['cp'] ?> <strong>Provincia:</strong> <?= $paciente['provincia'] ?> <strong>Localidad:</strong> <?= $paciente['localidad'] ?></p>
        </div>

        <div class="ficha-card">
            <h3>Otros Datos</h3>
            <p><strong>Sexo:</strong> <?= $paciente['sexo'] ?></p>
            <p><strong>Referido:</strong> <?= $paciente['nombre_referido'] ?></p>
        </div>
    </div>
</section>

<!-- RADIOGRAFÍAS -->
<section id="radiografias" class="tab-content">
    <iframe src="../IA/subir_y_analizar_radiografia.php?id_paciente=<?= $paciente['id'] ?>" style="width:100%; height:400px; border:none;"></iframe>
</section>

<!-- DIAGNÓSTICOS -->
<section id="diagnosticos" class="tab-content">
    <iframe src="../paciente/diagnosticos.php?id_paciente=<?= $paciente['id'] ?>" style="width:100%; height:300px; border:none;"></iframe>
</section>

<!-- PESTAÑA DE HISTORIAL CLÍNICO -->
<section id="historial" class="tab-content">
    <iframe src="../paciente/historial_clinico.php?id_paciente=<?= $paciente['id'] ?>" style="width:100%; height:400px; border:none;"></iframe>
</section>

<!-- PESTAÑA DE PRESUPUESTOS -->
<section id="presupuestos" class="tab-content">
    <div style="display: flex; justify-content: space-between; align-items: center;">
    </div>
    <iframe src="../presupuesto/presupuestos_paciente.php?id_paciente=<?= $paciente['id'] ?>" style="width:100%; height:400px; border:none;"></iframe>
</section>

<!-- PESTAÑA CONSENTIMIENTOS -->
    <section id="consentimientos" class="tab-content">
    <div style="display: flex; justify-content: space-between; align-items: center;">
    </div>
    <iframe src="../documentos/consentimientos.php?id_paciente=<?= $paciente['id'] ?>" style="width:100%; height:400px; border:none;"></iframe>    </section>

<!-- PESTAÑA DOCUMENTOS -->
    <section id="documentos" class="tab-content">
    <div style="display: flex; justify-content: space-between; align-items: center;">
    </div>
    <iframe src="../documentos/doc_administrativos.php?id_paciente=<?= $paciente['id'] ?>" style="width:100%; height:400px; border:none;"></iframe>    </section>

</main>

</body>
</html>
