<?php
	require("../connect-sqli.php");
	$ex3=$link->query("select * from bce where mcevin='".$_GET["vin"]."'")or die(mysqli_error($link));
	while($rs3=mysqli_fetch_array($ex3)){
		$link->query("update voters set ato='' where vin='".$rs3["vin"]."'")or die(mysqli_error($link));
		$ex=$link->query("select * from hl where bcevin='".$rs3["vin"]."'")or die(mysqli_error($link));
		while($rs=mysqli_fetch_array($ex)){
			$link->query("update voters set ato='' where vin='".$rs["vin"]."'");
			$hl=$rs["vin"];
			$ex2=$link->query("select * from hl_children where hlvin='".$hl."'")or die(mysqli_error($link));
			while($rs2=mysqli_fetch_array($ex2)){
				$link->query("update voters set ato='' where vin='".$rs2["vin"]."'")or die(mysqli_error($link));
			}
		}
	}
	$link->query("update voters set ato='' where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
	
	$link->query("delete from mce where vin='".$_GET["vin"]."'")or die(mysqli_error($link));
	echo "Success";
?>