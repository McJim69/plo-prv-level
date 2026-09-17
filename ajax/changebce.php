<?php
	require("../connect-sqli.php");
	$ex=$link->query("update hl set bcevin='".$_GET["tobce"]."' where bcevin='".$_GET["frombce"]."' ")or die(mysqli_error($link));
?>