<?php
session_start();
include "config.php";
if(isset($_SESSION['users']) && ($_SESSION['rango'])==2)
{
$id = $_GET['id'];
$delete = "DELETE FROM chat WHERE id = '".$id."'";
mysql_query ($delete, $conex);
header ("Location: mensajes.php");
}
else{
	echo"error";
	header('refresh:2; mensajes.php');
}
?>