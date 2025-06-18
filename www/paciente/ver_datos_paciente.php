<?php
  $id=$_POST["id"];
  include ("assets/PHP/crm_lib.php");
  
   $conn= conecta_db ();
   if ($conn=="KO")
   {
       echo "Fallo en conex a DB";
       exit;
   }
   $sql = "SELECT * FROM paciente where id='$id'";
   $result = $conn->query($sql);
   $conn->close();
   $row = $result->fetch_assoc();
   $nombre=$row['nombre'];
   $ape1=$row['apellido1'];
   $ape2=$row['apellido2'];
   

  echo "<h1>Aqui se ven los datos del paciente $id </h1>";
  echo "<table><tr><th>Nombre</th><th>Apellido 1</th><th>Apellido 2</th></tr>";
  echo "<tr><td>".$nombre."</td><td>".$ape1."</td><td>".$ape2."</td>";

  echo "</tr></table>";

?>
