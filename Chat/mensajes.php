<?php
session_start();
include "config.php";
if(isset($_SESSION['users']))
{       //$result = mysqli_query($con, "Select users,  from chat");
	$select = "SELECT * FROM chat ORDER BY id DESC";
	$query = mysql_query($select, $conex);
	$rows = mysql_num_rows($query);
	if($rows>0)
	{
		while ($row = mysql_fetch_array($query))
		{
			?>
			<?php
			if (isset ($_SESSION['users'])&&$_SESSION['rango']==2) //administrador es 2
			{
				?>
				<a href="borrar.php?id=<?=$row['id']?>"><font color="red">Borrar</font></a>
				<?php
			}else{
				?>
				&nbsp;
				<?php
			}
			?>
			<strong><?=$row['users']?></strong>:<?=$row['mensaje']?><br><br>
			<?php
		}
	}else{
		?>
		&nbsp;
		<?php
	}
}
header ('refresh:2; mensajes.php');
?>