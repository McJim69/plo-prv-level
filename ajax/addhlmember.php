<?php
	require("../connect-sqli.php");
	$ex=$link->query("select * from hl_children where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
	if(!($rs=mysqli_fetch_array($ex))){
		$link->query("insert into hl_children
			values(0,'".$_GET["hlvin"]."','".$_GET["vin"]."','".$_GET["remarks"]."')")or die(mysqli_error($link));
		$link->query("update voters set _4p='".$_GET["_4p"]."',ato='".$_GET["sure"]."' where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
		echo "Success";
	}else{
		echo "UNABLE to add Household Member.\nVoter is already assigned to a Household Leader";
	}
?>