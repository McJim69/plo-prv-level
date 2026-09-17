<?php
	require("../connect-sqli.php");
	$link->query("update hl_children set remarks='".$_GET["remarks"]."' where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
?>