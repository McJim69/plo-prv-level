<?php
	require("../connect-sqli.php");
	$link->query("delete from hl_children where hlcno=".$_GET["hlcno"]."")or die(mysqli_error($link));
	$link->query("update voters set ato='' where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
	echo "Success";
?>