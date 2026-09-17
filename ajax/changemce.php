<?php
	require("../connect-sqli.php");
	$ex=$link->query("update bce set mcevin='".$_GET["mce"]."' where vin='".$_GET["bce"]."' ")or die(mysqli_error($link));
?>