<?php
	require("../connect-sqli.php");
	$ex=$link->query("select * from prkldr where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
	if(!($rs=mysqli_fetch_array($ex))){
		$link->query("insert into prkldr
			values('".$_GET["vin"]."',0,0,0,'','','','','')"
		)or die(mysqli_error($link));
		$link->query("update voters set _4p='".$_GET["_4p"]."',ato='Sure' where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
		echo "Success";
	}else{
		echo "UNABLE to add Purok Coordinator.\nVoter is already a Purok Coordinator";
	}
?>