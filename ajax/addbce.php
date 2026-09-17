<?php
	require("../connect-sqli.php");
	$ex=$link->query("select * from bce where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
	if(!($rs=mysqli_fetch_array($ex))){
		$link->query("insert into bce
			values(0,'".$_GET["mcevin"]."','".$_GET["vin"]."',0,0,0,'','','','','')"
		)or die(mysqli_error($link));
		$link->query("update voters set _4p='".$_GET["_4p"]."',ato='Sure' where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
		echo "Success";
	}else{
		echo "UNABLE to add BCE Member.\nVoter is already assigned to a MCE";
	}
?>