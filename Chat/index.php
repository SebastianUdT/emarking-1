<?php

session_start();   // iniciar sesion 
include "config.php";
if (isset($_SESSION['users']))
{
	?>
	<table width="800" height="500">  <!--tabla que donde se ven los mensajes enviados-->
		<form method="post" action="promen.php">
		<tr>
		<td><iframe src="mensajes.php" name="iframe" width="700" 
                                    height="400"></iframe></td>
		</tr>
		<tr>
		<td><input type="text" size="90" name="mensaje"/> <button type="submit" name="send">Enviar</button></td>      <!--donde se introduce el mensaje-->
		</tr>
		<tr>
					<td>Estas conctado como <strong>
              <?=$_SESSION['users']?> <a href="logout.php">Cerrar sesion</a></strong>
					</td>
					</tr>
					</form>
		</table>

		<?php
	}else{
		?>
		Debes iniciar sesion para usar el chat <a href="login.php">Click aqui</a>
	<?php
}
?>