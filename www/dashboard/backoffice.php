<?php 
include("../includes/header.php");
include("../assets/PHP/crm_lib.php");
?>

<main class="container"> 
    <div class="dashboard">

        <div class="card">
            <h2>Alta Usuarios</h2>
            <p>Crear nuevos usuarios con sus perfiles y permisos.</p>
            <a href="../usuario/alta_usuario.php" class="primary-btn">Añadir Usuario</a>
        </div>

        <div class="card">
            <h2>Listado Usuarios</h2>
            <p>Consulta y administra los usuarios registrados.</p>
            <a href="../usuario/listado_usuarios.php" class="primary-btn">Ver Usuarios</a>
        </div>

        <div class="card">
            <h2>Crear Agenda</h2>
            <p>Configura agendas de doctores y horarios personalizados.</p>
            <a href="../cita/crear_agenda.php" class="primary-btn">Crear Agenda</a>
        </div>

        <div class="card">
            <h2>Crear Tarifas para Presupuestos</h2>
            <p>Gestiona tarifas y tratamientos asociados para presupuestos.</p>
            <a href="../presupuesto/tarifas_presupuestos.php" class="primary-btn">Abrir Gestión de Tarifas</a>
        </div>
    </div>
</main>

<script src="/assets/JS/crm_lib.js"></script>