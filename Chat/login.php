<?php
session_start();
include "config.php";
if(isset($_SESSION['users']))
{
	?>
	Hola <?=$_SESSION['users'] ?> Ya has iniciado sesion <a href="logout.php">Cerrar Sesion</a>
	<?php
}else{
	?>
	<table width="300">
		<form method="post" action="logeo.php">
	<tr>
	<td>User:</td>
	<td><input type="text" name="users"/></td>
	</tr>
	<tr>
	<td>Pass:</td>
	<td><input type="password" name="pass"></td>
	</tr>
	<tr>
		<td align="center" colspan="2"><input type="submit" name="enviar" value="Acceder"</td>
	</tr>
	</form>
	</table>
	<?php
}
?>
