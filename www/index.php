<?php include("includes/header.php");
include("assets/PHP/crm_lib.php");
?>

<main class="container"> 
    <div class="dashboard">

        <div class="card">
            <h2>Agendas</h2>
            <p>Consulta y gestiona las citas de la semana.</p>
            <a href="javascript:win_agenda('/cita/agenda.php','popupAgenda')">Abrir Agenda</a>
        </div>

        <div class="card">
            <h2>Añadir Paciente</h2>
            <p>Registrar un nuevo paciente con sus datos completos.</p>
            <a href="../paciente/agregar_paciente.php">Añadir Paciente</a>
        </div>

        <div class="card">
            <h2>Pacientes</h2>
            <p>Visualizar listado y acceder al perfil de cada paciente.</p>
            <a href="../paciente/consulta_pacientes.php">Ver Pacientes</a>
        </div>

        <?php if ($_SESSION["perfil"] == 1): ?>
        <div class="card">
            <h2>Alta Usuarios</h2>
            <p>Crear nuevos usuarios con sus perfiles y permisos.</p>
            <a href="../usuario/alta_usuario.php">Añadir Usuario</a>
        </div>

        <div class="card">
            <h2>Listado Usuarios</h2>
            <p>Consulta y administra los usuarios registrados.</p>
            <a href="../usuario/listado_usuarios.php">Ver Usuarios</a>
        </div>

        <div class="card">
            <h2>Crear Agenda</h2>
            <p>Configura agendas de doctores y horarios personalizados.</p>
            <a href="../cita/crear_agenda.php">Crear Agenda</a>
        </div>

        <div class="card">
            <h2>Crear Tarifas para Presupuestos</h2>
            <p>Gestiona tarifas y tratamientos asociados para presupuestos.</p>
            <a href="../presupuesto/tarifas_presupuestos.php">Abrir Gestión de Tarifas</a>
        </div>
        <?php endif; ?>

    </div>
</main>

<!-- Cargar script JS del CRM directamente -->
<script src="/assets/JS/crm_lib.js"></script>