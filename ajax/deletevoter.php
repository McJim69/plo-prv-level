<?php
	require("../connect-sqli.php");
	$link->query("delete from voters where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
	echo "Success";
?>