<?php require("../connect-pdo.php");?>

<style>
	.t_voters td{
		border-bottom:1px dotted #aaa;
		padding:1px;
	}
	a{
		color:red;
		text-decoration:none;
	}
	a:hover{
		text-decoration:underline;color:#fff;
	}
</style>

<table width=100% class='t_voters' >
	<tr STYLE='FONT:bold 12px arial;color:#FFF'>
		<th>NO.</th>
		<th>VOTER'S NAME</th>				
		<th>SEX</th>
		<th>AGE</th>
		<th>PRECINCT</th>
		<th>PUROK</th>
		<th>ACTIONS</th>
	</tr>

	<?php
				
		$table=$_GET["table"];
		$votno=$_GET["id"];
		if($votno==""){
			$votno=0;
		}
		
		$value=strtoupper($_GET["value"]);
		$rep="<b style='color:#0014d0;background:#ffa0a0'>".$value."</b>";
		
		if (!empty($_SESSION["barangay"]) && $_SESSION["barangay"] !== "All barangays") {
			$stmt = $link->prepare("select * from voters v where v.city_mun=? and v.barangay=? and (v.ato LIKE CONCAT('%', ?, '%') or v.vname LIKE CONCAT('%', ?, '%')) order by vname limit 0,20");
			$stmt->execute([$_SESSION["city_mun"], $_SESSION["barangay"], $_GET["value"], $_GET["value"]]);
		} else {
			$stmt = $link->prepare("select * from voters v where v.city_mun=? and (v.ato LIKE CONCAT('%', ?, '%') or v.vname LIKE CONCAT('%', ?, '%')) order by vname limit 0,20");
			$stmt->execute([$_SESSION["city_mun"], $_GET["value"], $_GET["value"]]);
		}
		$ex = $stmt;
		
		$ctr=1;
						
		if($ex->rowCount()<1){
			echo "<tr><td colspan=6><B style='color:red'>No records found! :(</B></td></tr>";
		}else{
			while($rs=$ex->fetch(PDO::FETCH_BOTH)){
				$found=false;
				$rem="";

				$stmt = $link->prepare('select * from counter where vin=?');
				$stmt->execute([$rs["vin"]]);
				$qq = $stmt;

				if($rss=$qq->fetch(PDO::FETCH_BOTH)){
					$found=true;
					$rem=" - Counted!";
				}

				$birthDate = $rs["birth"];
				$age = "-";
				if (!empty($birthDate) && $birthDate !== '0000-00-00') {
					$birthObj = date_create($birthDate);
					if ($birthObj !== false) {
						$age = date_diff($birthObj, date_create('today'))->y;
					}
				}

				echo"
				<tr class='odd' id='q_tr_".$ctr."' style=\"height:0px;\">
					<td style='padding:8px'>".$ctr.".</td>
					<td>".$rs["vname"]." <b style='color:red'>".$rem."</b></td>
					<td>".$rs["sex"]."</td>
					<td>".$age."</td>
					<td>".$rs["precinct"]."</td>
					<td>".$rs["address"]."</td>
					<td style='display:none'>
						<div>
							<select id='q_sel_4p_".$ctr."' style='padding:1px'  onchange=\"changeStats('".$rs["0"]."','hl_children','_4p',this.value)\" >
								<option>No</option>
								<option ";
								if($rs["_4p"]==1){echo "selected";}
								echo">Yes</option>
							</select>
						</div>
					</td>
					<td style='display:none'>
						<select id='q_sel_sure_".$ctr."' style='padding:1px;width:70px' >
							<option>Sure</option>
							<option>Sure Wala Diri</option>
							<option>Undecided</option>
						</select>
					</td>
					<td style='text-align:left' >";
					if($found==false)
						echo"<input alt='Add' type='image' src='images/accept.png' title='Click to Add' onclick=\"addmce('".$rs["0"]."',".$ctr.");\" />";
					echo"</td>
				</tr>";
				$ctr++;
				
				if($ctr==21){
					break;
				}
			}
		}
	?>

</table>