<?php
  function conecta_db ()
  {
     if ($cnx=new mysqli("database", "root", $_ENV['MYSQL_ROOT_PASSWORD'], "CRM_TFE"))
       return $cnx;
     else
       return "KO";
  }
// Función global para generar UUID versión 4
function generate_uuid_v4() {
  return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
  );
}
?>