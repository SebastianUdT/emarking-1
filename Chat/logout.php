<?php
session_start();
include "config.php";
if(isset($_SESSION['users']))
{
	session_destroy();
	header("Location: login.php");
}else {
	echo "Error";
}
?>