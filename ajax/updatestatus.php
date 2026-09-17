<?php
	require("../connect-sqli.php");
	$link->query("update voters set ".$_GET["target"]."='".$_GET["value"]."' where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
	$link->query("update hl_children set remarks='' where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
?>