<?php
	require("../connect-sqli.php");
	$ex=$link->query("select * from bce where bno=".$_GET["bno"])or die(mysqli_error($link));
	$rs=mysqli_fetch_array($ex);
	$bce=$rs["vin"];
	
	$ex=$link->query("select * from hl where bcevin='".$bce."'")or die(mysqli_error($link));
	while($rs=mysqli_fetch_array($ex)){
		$link->query("update voters set ato='' where vin='".$rs["vin"]."'");
		$hl=$rs["vin"];
		$ex2=$link->query("select * from hl_children where hlvin='".$hl."'")or die(mysqli_error($link));
		while($rs2=mysqli_fetch_array($ex2)){
			$link->query("update voters set ato='' where vin='".$rs2["vin"]."'")or die(mysqli_error($link));
		}
	}
	$link->query("update voters set ato='' where vin='".$bce."'")or die(mysqli_error($link));
	
	$link->query("delete from bce where bno=".$_GET["bno"]."")or die(mysqli_error($link));
	echo "Success";
?>