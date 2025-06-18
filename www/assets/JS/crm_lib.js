    // Función ventana para edición de pacientes.
    function win(theURL,winName,id)
    {
        var id=document.getElementById(id).value;
        var new_url=theURL+"?id="+id; 
        var features='toolbar=no,status=0,scrollbars=1,resizable=1,left=216,top=60,Width=970,Height=600,menubar=no';
        mywin=window.open(new_url,winName,features);
        mywin.focus()
    }

    // Esta ventana controla la vista de agenda, se verá en pantalla completa. Por tanto se manejará en una nueva ventana y lo demás seguirá corriendo en la ventana principal.
    function win_agenda(theURL, winName) {
    // Resta 80px tanto a ancho como a alto (aprox 3cm menos cada dimensión)
    const padding = 80;
    const ancho = screen.availWidth - padding;
    const alto = screen.availHeight - padding;
    const left = Math.round((screen.availWidth - ancho) / 2);
    const top = Math.round((screen.availHeight - alto) / 2);

    const features = `toolbar=no,status=0,scrollbars=1,resizable=1,top=${top},left=${left},width=${ancho},height=${alto},menubar=no`;
    const mywin = window.open(theURL, winName, features);
    if (mywin) {
        mywin.focus();
    } else {
        alert("Por favor, permite las ventanas emergentes para abrir la agenda.");
    }
   }

    //Login

        function togglePass() {
        var passInput = document.getElementById('pass');
        var showBtn = document.querySelector('.show-pass');
        if (passInput.type === "password") {
            passInput.type = "text";
            showBtn.textContent = "Ocultar contraseña";
        } else {
            passInput.type = "password";
            showBtn.textContent = "Mostrar contraseña";
        }
    }
    
    // Cambiar entre pestañas en perfil_paciente.php
    function showTab(id) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelector(`[onclick="showTab('${id}')"]`).classList.add('active');
    }

    function creaAjax(){
            var objetoAjax=false;
            try {
            /*Para navegadores distintos a internet explorer*/
            objetoAjax = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
            try {
                    /*Para explorer*/
                    objetoAjax = new ActiveXObject("Microsoft.XMLHTTP");
                    }
                    catch (E) {
                    objetoAjax = false;
            }
            }

            if (!objetoAjax && typeof XMLHttpRequest!='undefined') {
            objetoAjax = new XMLHttpRequest();
            }
            return objetoAjax;
    }

    function FAjax (url,capa,valores,metodo)
    {
            var ajax=creaAjax();
            var capaContenedora = document.getElementById(capa);
            capaContenedora.innerHTML="PROCESANDO .......";

            /*Creamos y ejecutamos la instancia si el metodo elegido es POST*/
    if(metodo.toUpperCase()=='POST'){
            ajax.open ('POST', url, true);
            ajax.onreadystatechange = function() {
            if (ajax.readyState==1) {
                            capaContenedora.innerHTML="Cargando.......";
            }
            else if (ajax.readyState==4){
                    if(ajax.status==200)
                    {
                            document.getElementById(capa).innerHTML=ajax.responseText;
                    }
                    else if(ajax.status==404)
                                                {

                                capaContenedora.innerHTML = "La direccion no existe";
                                                }
                            else
                                                {
                                capaContenedora.innerHTML = "Error: ".ajax.status;
                                                }
                                        }
                    }
            ajax.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            ajax.send(valores);
            return;
    }
    /*Creamos y ejecutamos la instancia si el metodo elegido es GET*/
    if (metodo.toUpperCase()=='GET'){

            ajax.open ('GET', url, true);
            ajax.onreadystatechange = function() {
            if (ajax.readyState==1) {
                                        capaContenedora.innerHTML="Cargando.......";
            }
            else if (ajax.readyState==4){
                    if(ajax.status==200){
                                                document.getElementById(capa).innerHTML=ajax.responseText;
                    }
                    else if(ajax.status==404)
                                                {

                                capaContenedora.innerHTML = "La direccion no existe";
                                                }
                                                else
                                                {
                                capaContenedora.innerHTML = "Error: ".ajax.status;
                                                }
                                        }
                    }
            ajax.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            ajax.send(null);
            return
    }

    }


    function ver_radiografia (id)
    {
    var id_nombre="N"+id;
    alert (id_nombre);
    var nombre=document.getElementById(id_nombre).value;
    param="id="+id+"&nombre="+nombre;
    //alert (param);
    FAjax ('ver_radiografias.php','detalle_radiografias',param,'post');
    }

    function ver_datos (id)
    {
    param="id="+id;
    //alert (param);
    FAjax ('ver_datos_paciente.php','detalle_datos',param,'post');
    }  
    //Función filtro de búsqueda pacientes

    function buscar_paciente() {
    var input = document.getElementById("busca_paciente");
    var nombre_buscar = input.value.trim();

    if (nombre_buscar === "") {
        alert("Por favor introduce un nombre para buscar.");
        return;
    }

    var param = "nombre_buscar=" + encodeURIComponent(nombre_buscar);
    FAjax('buscar_paciente.php', 'listado_paciente', param, 'post');

    // Limpiar campo después de la búsqueda
    input.value = "";
    }


    //JavaScript para mostrar el modal personalizado de ALTA USUARIO (ventanas de crear nuevo usuario o volver al inicio)
    function mostrarModalCreacionUsuario() {
    const modalHTML = `
        <div class="custom-modal-overlay" id="modalCreacionUsuario">
            <div class="custom-modal">
                <p>Usuario creado correctamente.<br><br>¿Quieres crear otro usuario?</p>
            <button class="modal-btn modal-btn-volver" onclick="window.location.href = window.location.origin + '/index.php';">
                Volver al inicio
            </button>
            <button class="modal-btn modal-btn-crear" onclick="window.location.href='../usuario/alta_usuario.php'">Crear nuevo usuario</button>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    // --- Gestión de agendas por doctor ---

    function cargarAgendaDoctor() {
    var doctorId = document.getElementById("doctor").value;
    if (doctorId !== "") {
        // Petición AJAX para cargar el calendario del doctor seleccionado
        var ajax = creaAjax();
        ajax.open('GET', 'cargar_agenda.php?id=' + doctorId, true);
        ajax.onreadystatechange = function() {
            if (ajax.readyState === 4 && ajax.status === 200) {
                document.getElementById("agenda_semanal").innerHTML = ajax.responseText;
            }
        };
        ajax.send();
    } else {
        document.getElementById("agenda_semanal").innerHTML = "<p>Selecciona un doctor para ver su agenda.</p>";
    }
    }

    let calendarioCargado = false;

    // Cargar FullCalendar con la agenda seleccionada
    function cargarCalendario(agenda_id) {
        $('#calendar').fullCalendar({
            locale: 'es',
            defaultView: 'agendaWeek',
            allDaySlot: false,
            minTime: "08:00:00",
            maxTime: "20:00:00",
            slotDuration: '00:15:00',
            events: 'eventos.php?agenda_id=' + agenda_id,
            editable: false,
            eventClick: function(event) {
                alert("Paciente: " + event.title);
            }
        });
        calendarioCargado = true;
    }

    // Iniciar FullCalendar cuando el DOM esté listo
    document.addEventListener("DOMContentLoaded", function () {
        const agendaSelect = document.getElementById('agenda_select');
        if (agendaSelect) {
            const agendaInicial = agendaSelect.value;

            // Si la pestaña de agenda está visible, carga
            if (document.getElementById("tab_agenda").classList.contains("active")) {
                cargarCalendario(agendaInicial);
            }

            $('#agenda_select').change(function () {
                const id = $(this).val();
                if (calendarioCargado) {
                    $('#calendar').fullCalendar('removeEvents');
                    $('#calendar').fullCalendar('addEventSource', 'eventos.php?agenda_id=' + id);
                } else {
                    cargarCalendario(id);
                }
            });
        }
    });


    // Mostrar alerta de éxito o error tras acciones específicas
    function mostrarMensaje(tipo, mensaje) {
        const div = document.createElement('div');
        div.className = tipo === 'ok' ? 'alert-success' : 'alert-error';
        div.textContent = mensaje;
        div.style.padding = '10px';
        div.style.margin = '10px 0';
        div.style.borderRadius = '5px';
        div.style.fontWeight = 'bold';

        if (tipo === 'ok') {
            div.style.backgroundColor = '#d4edda';
            div.style.color = '#155724';
            div.style.border = '1px solid #c3e6cb';
        } else {
            div.style.backgroundColor = '#f8d7da';
            div.style.color = '#721c24';
            div.style.border = '1px solid #f5c6cb';
        }

        const main = document.querySelector('main.container') || document.body;
        main.insertBefore(div, main.firstChild);

        setTimeout(() => {
            div.remove();
        }, 6000);
    }

    // Comprobar si hay parámetros ?ok=1 o ?error=1 y lanzar mensaje
    document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        if (params.has('ok')) {
            mostrarMensaje('ok', 'Imagen subida correctamente.');
        }
        if (params.has('diagnosticado')) {
            mostrarMensaje('ok', 'Has solicitado un diagnóstico. Podrás verlo en la pestaña "Diagnósticos".');
        }
        if (params.has('error')) {
            mostrarMensaje('error', 'Hubo un error. Por favor, intenta nuevamente.');
        }
    });


    // PESTAÑAS "AGENDA" Controladores
    function initAgendaTabs(id) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelector(`[onclick="showTab('${id}')"]`).classList.add('active');
    }

    function creaAjax(){
            var objetoAjax=false;
            try {
            /*Para navegadores distintos a internet explorer*/
            objetoAjax = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
            try {
                    /*Para explorer*/
                    objetoAjax = new ActiveXObject("Microsoft.XMLHTTP");
                    }
                    catch (E) {
                    objetoAjax = false;
            }
            }

            if (!objetoAjax && typeof XMLHttpRequest!='undefined') {
            objetoAjax = new XMLHttpRequest();
            }
            return objetoAjax;
    }

    function FAjax (url,capa,valores,metodo)
    {
            var ajax=creaAjax();
            var capaContenedora = document.getElementById(capa);
            capaContenedora.innerHTML="PROCESANDO .......";

            /*Creamos y ejecutamos la instancia si el metodo elegido es POST*/
    if(metodo.toUpperCase()=='POST'){
            ajax.open ('POST', url, true);
            ajax.onreadystatechange = function() {
            if (ajax.readyState==1) {
                            capaContenedora.innerHTML="Cargando.......";
            }
            else if (ajax.readyState==4){
                    if(ajax.status==200)
                    {
                            document.getElementById(capa).innerHTML=ajax.responseText;
                    }
                    else if(ajax.status==404)
                                                {

                                capaContenedora.innerHTML = "La direccion no existe";
                                                }
                            else
                                                {
                                capaContenedora.innerHTML = "Error: ".ajax.status;
                                                }
                                        }
                    }
            ajax.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            ajax.send(valores);
            return;
    }
    /*Creamos y ejecutamos la instancia si el metodo elegido es GET*/
    if (metodo.toUpperCase()=='GET'){

            ajax.open ('GET', url, true);
            ajax.onreadystatechange = function() {
            if (ajax.readyState==1) {
                                        capaContenedora.innerHTML="Cargando.......";
            }
            else if (ajax.readyState==4){
                    if(ajax.status==200){
                                                document.getElementById(capa).innerHTML=ajax.responseText;
                    }
                    else if(ajax.status==404)
                                                {

                                capaContenedora.innerHTML = "La direccion no existe";
                                                }
                                                else
                                                {
                                capaContenedora.innerHTML = "Error: ".ajax.status;
                                                }
                                        }
                    }
            ajax.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            ajax.send(null);
            return
    }

    }

    // Control de fechas
    function cambiarDia(delta) {
        const selector = document.getElementById('selector_fecha');
        const fechaActual = new Date(selector.value);
        fechaActual.setDate(fechaActual.getDate() + delta);
        const nuevaFecha = fechaActual.toISOString().split('T')[0];
        selector.value = nuevaFecha;
        irAFecha(nuevaFecha);
    }

    function irAFecha(fecha) {
        window.location.href = `agenda.php?fecha=${fecha}`;
    }

    // Solo si estamos en agenda
    document.addEventListener("DOMContentLoaded", () => {
        if (window.location.pathname.includes("agenda")) {
            initAgendaTabs('tab_agenda');
        }
    });

    //FILTRO DE CALENDARIO EN AGENDA
    function cambiardeDia(delta) {
        const selector = document.getElementById('selector_fecha');
        const fechaActual = new Date(selector.value);
        fechaActual.setDate(fechaActual.getDate() + delta);
        const nuevaFecha = fechaActual.toISOString().split('T')[0];
        selector.value = nuevaFecha;
        irAunaFecha(nuevaFecha);
    }

    function irAunaFecha(fecha) {
        const urlParams = new URLSearchParams(window.location.search);
        const doctor = urlParams.get('id_doctor') || 'todos';
        window.location.href = `agenda.php?fecha=${fecha}&id_doctor=${doctor}`;
    }


    //FILTRO DE CALENDARIO EN AGENDA--> Pestañadiaria
    function cambiarDia(delta) {
        const selector = document.getElementById('selector_fecha');
        const fechaActual = new Date(selector.value);
        fechaActual.setDate(fechaActual.getDate() + delta);
        const nuevaFecha = fechaActual.toISOString().split('T')[0];
        selector.value = nuevaFecha;
        irAFecha(nuevaFecha);
    }

    function irAFecha(fecha) {
        const urlParams = new URLSearchParams(window.location.search);
        const doctor = urlParams.get('id_doctor') || 'todos';
        window.location.href = `agenda_diaria.php?fecha=${fecha}&id_doctor=${doctor}`;
    }

    //FILTRO DE CALENDARIO EN AGENDA Pestaña Semanal
    function cambiarSemana(delta) {
        const selector = document.getElementById('selector_fecha');
        const fechaActual = new Date(selector.value);
        fechaActual.setDate(fechaActual.getDate() + delta);
        const nuevaFecha = fechaActual.toISOString().split('T')[0];
        selector.value = nuevaFecha;
        irASemana(nuevaFecha);
    }

    function irASemana(fecha) {
        const urlParams = new URLSearchParams(window.location.search);
        const doctor = urlParams.get('id_doctor') || 'todos';
        window.location.href = `agenda_semanal.php?fecha=${fecha}&id_doctor=${doctor}`;
    }

    //Función filtro de búsqueda para agendar una cita paciente.
    function buscar_agendar() {
        const input = document.getElementById("busca_y_agenda");
        const valor = input.value.trim();

        if (valor === "") {
            alert("Por favor introduce un nombre, apellido o NHC para buscar.");
            return;
        }

        const param = "termino=" + encodeURIComponent(valor);
        FAjax("agendar_paciente.php", "listado_paciente", param, "post");

        // No limpiamos el campo aún para mantener referencia del texto
    }
    function seleccionar_paciente(id, nombre_completo) {
        const campo = document.getElementById("id_paciente");
        if (campo) {
            campo.innerHTML = `<option value="${id}" selected>${nombre_completo}</option>`;
        }
        window.close();
    }

// Abre el buscador de pacientes en una ventana popup compacta y ancha
function abrirBuscadorPacientes() {
    window.open("agendar_paciente.php", "BuscadorPacientes", "width=900,height=300");
}

// Selecciona un paciente desde el popup y lo inserta en el formulario
function seleccionarPaciente(id, nombreCompleto) {
    // Para crear_cita.php (formulario principal)
    if (document.getElementById("id_paciente")) {
        document.getElementById("id_paciente").value = id;
        document.getElementById("nombre_paciente").value = nombreCompleto;
    }
    // Para el popup: cerrar ventana si existe
    if (window.close && window.name === "BuscadorPacientes") {
        window.close();
    }
}

// Cambia la duración del tratamiento automáticamente al seleccionarlo
function actualizarDuracion() {
    const select = document.getElementById("id_tratamiento");
    if (!select) return;
    const duracion = select.options[select.selectedIndex].getAttribute("data-duracion");
    if (duracion) {
        document.getElementById("duracion_minutos").value = duracion;
    }
}


  // Abrir ventana emergente para insertar una nueva cita (ventana pequeña y cuadrada)
function win_crearcita(theURL, winName) {
    var features = 'toolbar=no,status=0,scrollbars=1,resizable=1,left=216,top=60,width=730,height=560,menubar=no';
    var mywin = window.open(theURL, winName, features);
    if (mywin) {
        mywin.focus();
    } else {
        alert("Por favor, permite las ventanas emergentes para abrir el formulario de cita.");
    }
}

// --- LISTENER PRINCIPAL para "Libre" ---
// Al hacer clic en 'Libre', verifica disponibilidad y abre el popup SOLO si está libre
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("a.libre").forEach(el => {
        el.addEventListener("click", function (e) {
            e.preventDefault();
            const idAgenda = el.getAttribute("data-id-agenda");
            const fecha = el.getAttribute("data-fecha");
            const hora = el.getAttribute("data-hora");

            // Verifica disponibilidad antes de abrir popup
            fetch('verifica_disponibilidad.php', {
                method: 'POST',
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "id_agenda=" + encodeURIComponent(idAgenda)
                    + "&fecha=" + encodeURIComponent(fecha)
                    + "&hora_inicio=" + encodeURIComponent(hora)
            })
            .then(r => r.json())
            .then(res => {
                if (res.ok && res.disponible) {
                    // Abre en ventana emergente
                    const url = `crear_cita.php?id_agenda=${idAgenda}&fecha=${fecha}&hora=${hora}`;
                    win_crearcita(url, 'popupCita');
                } else {
                    alert("No disponible: " + (res.motivo || "Franja ocupada o no válida"));
                }
            })
            .catch(() => alert("Error verificando disponibilidad. Intenta de nuevo."));
        });
    });
});

// --- FUNCIÓN REUTILIZABLE PARA OTROS COMPONENTES ---
function verificarDisponibilidad(id_agenda, fecha, hora_inicio, callback) {
    fetch('verifica_disponibilidad.php', {
        method: 'POST',
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id_agenda=" + encodeURIComponent(id_agenda)
            + "&fecha=" + encodeURIComponent(fecha)
            + "&hora_inicio=" + encodeURIComponent(hora_inicio)
    })
    .then(r => r.json())
    .then(callback);
}

// Ejemplo de uso verificarDisponibilidad en otros formularios:
// verificarDisponibilidad(id_agenda, fecha, hora_inicio, function(res) {
//     if (res.disponible) {
//         // Habilitar formulario o botón
//     } else {
//         alert("No disponible: " + res.motivo);
//     }
// });

// POP UP PARA GESTIONAR CAMBIO DE ESTADOS DE CITA, ETC.
// Ventana para editar/gestionar cita desde la agenda
function win_gestionacita(theURL, winName) {
    var features = 'toolbar=no,status=0,scrollbars=1,resizable=1,left=216,top=60,width=720,height=560,menubar=no';
    var mywin = window.open(theURL, winName, features);
    if (mywin) {
        mywin.focus();
    } else {
        alert("Por favor, permite las ventanas emergentes para editar la cita.");
    }
}

// -- ICONO Y COLOR EN TIEMPO REAL PARA EL SELECT DE ESTADO --
function actualizaVistaEstado() {
    var select = document.getElementById('estado-cita-select');
    if (!select) return;
    var idx = select.selectedIndex;
    var opt = select.options[idx];
    var color = opt.getAttribute('data-color');
    var icono = opt.getAttribute('data-icono');
    // Aplica color de fondo y color de letra según fondo
    select.style.backgroundColor = color;
    if (["#007BFF", "#28A745", "#FF0000"].includes(color)) {
        select.style.color = "#fff";
    } else {
        select.style.color = "#222";
    }
    // Cambia el icono
    document.getElementById('icono-estado').innerHTML =
        `<img src="../assets/images/${icono}" alt="Estado">`;
}

document.addEventListener('DOMContentLoaded', function() {
    actualizaVistaEstado();
    var select = document.getElementById('estado-cita-select');
    if (select) {
        select.addEventListener('change', actualizaVistaEstado);
    }
});

// --- JS PRESUPUESTOS ---

// Al cambiar la tarifa, recarga los tratamientos vía AJAX y después inicializa eventos
document.addEventListener('DOMContentLoaded', function () {
    const tarifaSel = document.getElementById('tarifa');
    if (!tarifaSel) return;

    tarifaSel.addEventListener('change', function() {
        const id_tarifa = this.value;
        const contenedor = document.getElementById('tratamientos_contenedor');
        contenedor.innerHTML = '';
        if (!id_tarifa) return;
        fetch('ajax_tratamientos_tarifa.php?id_tarifa=' + encodeURIComponent(id_tarifa))
            .then(response => response.text())
            .then(html => {
                contenedor.innerHTML = html;
                inicializarEventosTratamientos();
            });
    });

    // Si la tarifa ya está seleccionada al entrar (caso reload), dispara el evento
    if (tarifaSel.value) {
        tarifaSel.dispatchEvent(new Event('change'));
    }
});

// Inicializa todos los eventos de selectores y cantidades
function inicializarEventosTratamientos() {
    document.querySelectorAll('.tratamiento-row input[type=checkbox]').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            toggleTratamientoRow(this);
        });
    });
    document.querySelectorAll('.tratamiento-row input[name="cantidad[]"]').forEach(function(input) {
        input.addEventListener('input', actualizaTotalPresupuesto);
    });
}

function inicializarSelectorDientes(contenedor) {
    const buttons = contenedor.querySelectorAll('.btn-diente');
    const inputDiente = contenedor.querySelector('input[name="diente[]"]');
    const row = contenedor.closest('tr');
    const inputCantidad = row.querySelector('input[name="cantidad[]"]');
    buttons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            btn.classList.toggle('activo');
            const seleccionados = Array.from(buttons)
                .filter(b => b.classList.contains('activo'))
                .map(b => b.dataset.diente);
            inputDiente.value = seleccionados.join(',');
            if (inputCantidad) inputCantidad.value = seleccionados.length;
            actualizaTotalPresupuesto();
        });
    });
}

function toggleTratamientoRow(checkbox) {
    const row = checkbox.closest('tr');
    const selectorDientes = row.querySelector('.selector-dientes');
    const cantidad = row.querySelector('.cantidad_trat');
    if (checkbox.checked) {
        if (selectorDientes) {
            selectorDientes.style.display = "block";
            cantidad.style.display = "inline-block";
            cantidad.value = 0;
            cantidad.readOnly = true;
            if (!selectorDientes.dataset.inicializado) {
                inicializarSelectorDientes(selectorDientes);
                selectorDientes.dataset.inicializado = "1";
            }
        } else if (cantidad) {
            cantidad.disabled = false;
        }
    } else {
        if (selectorDientes) {
            selectorDientes.style.display = "none";
            row.querySelector('input[name="diente[]"]').value = "";
        }
        if (cantidad) {
            cantidad.value = 1;
            cantidad.style.display = "";
            cantidad.disabled = true;
        }
    }
    actualizaTotalPresupuesto();
}

function actualizaTotalPresupuesto() {
    let total = 0;
    document.querySelectorAll('.tratamiento-row').forEach(function(row) {
        const checkbox = row.querySelector('input[type=checkbox]');
        const precio = parseFloat(row.querySelector('input[name="precio[]"]').value || 0);
        const cantidadInput = row.querySelector('input[name="cantidad[]"]');
        let cantidad = 1;
        if (checkbox && checkbox.checked) {
            if (cantidadInput && !cantidadInput.disabled && cantidadInput.style.display !== "none") {
                cantidad = parseInt(cantidadInput.value || 1);
            }
            total += precio * cantidad;
        }
    });
    const totalElem = document.getElementById('totalPresupuesto');
    if (totalElem) totalElem.innerText = "Total: " + total.toFixed(2) + " €";
}


// PARA LAS CONSULTAS DE CITAS POR PACIENTE.
function popupCitas(id_paciente) {
    var url = "../cita/gestor_citas.php?id_paciente=" + encodeURIComponent(id_paciente);
    // Tamaño similar al popup de crear cita
    var ancho = 950;
    var alto = 640;
    var left = Math.max(0, (screen.width - ancho) / 2);
    var top = Math.max(0, (screen.height - alto) / 2);
    window.open(url, "popupCitas" + id_paciente, "width=" + ancho + ",height=" + alto + ",left=" + left + ",top=" + top + ",resizable=1,scrollbars=1");
}

// PARA LAS CONSULTAS DE RADIOGRAFÍAS POR PACIENTE. 
function popupRadiografias(id_paciente) {
    var url = "../IA/subir_y_analizar_radiografia.php?id_paciente=" + encodeURIComponent(id_paciente);
    // Tamaño recomendable (puedes ajustarlo a lo que uses normalmente)
    var ancho = 950;
    var alto = 670;
    var left = Math.max(0, (screen.width - ancho) / 2);
    var top = Math.max(0, (screen.height - alto) / 2);
    window.open(url, "popupRadiografias" + id_paciente, "width=" + ancho + ",height=" + alto + ",left=" + left + ",top=" + top + ",resizable=1,scrollbars=1");
}

// Loader para "Solicitar Diagnóstico" Para que el usuario espere unos segundos a recibir el diagnódstico
document.addEventListener('DOMContentLoaded', function () {
    var loader = document.getElementById('diagnostico-loader');
    if (loader) {
        document.querySelectorAll('a.secondary-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                loader.style.display = 'flex';
                // NO cancelamos el click, dejamos que vaya al enlace
                // El loader se queda visible hasta que el navegador cambie de página
            });
        });
    }
});
