<?php
	require("../connect-sqli.php");
	$ex=$link->query("update hl_children set hlvin='".$_GET["toHL"]."' where hlvin='".$_GET["fromHL"]."' ")or die(mysqli_error($link));
?>