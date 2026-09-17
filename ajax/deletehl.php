<?php
	require("../connect-sqli.php");
	$ex=$link->query("select * from hl_children where hlvin='".$_GET["vin"]."'")or die(mysqli_error($link));
	while($rs=mysqli_fetch_array($ex)){
		$link->query("update voters set ato='' where vin='".$rs["vin"]."'");
	}
	$link->query("update voters set ato='' where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
	$link->query("delete from hl where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
	echo "Success";
?>