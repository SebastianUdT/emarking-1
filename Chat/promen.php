<?php
session_start();
include "config.php";


	$users = $_SESSION['users'];
	$rango = $_SESSION['rango'];
	$mensaje = $_POST['mensaje'];
	$insert = "INSERT INTO chat (users,mensaje)";
	$insert.= "VALUES ('".$users."','".$mensaje."')";
	mysql_query($insert, $conex);
	header("Location: index.php");

?>