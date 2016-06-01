<?php
session_start();
include "config.php";
if(isset($_POST['enviar']))
{
	$users = $_POST['users'];
	$pass = $_POST['pass'];
	$select = "SELECT id,users,pass,rango FROM users WHERE users"
                . " = '".$users."' AND pass = '".$pass."'";
	$query = mysql_query($select, $conex);
	$rows = mysql_num_rows($query);
	if($row = mysql_fetch_array($query))
	{
		$_SESSION['users'] = $row['users'];
		$_SESSION['pass'] = $row['pass'];
		$_SESSION['id'] = $row['id'];
		$_SESSION['rango'] = $row['rango'];
		header("Location: index.php");
	}else{
		echo "El useario o contrasena son invalidos";
		header ('refresh:2; login.php');

	}

}
?>