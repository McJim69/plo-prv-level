<style>
	.t_voters td{
		border-bottom:1px dotted #aaa;
		padding:2px;
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
	<tr STYLE="FONT:bold 12px arial;">
		<th ALIGN=center style='padding:10px;font:bold 15px arial;' >VOTER'S NAME</th>
		<th id=small ALIGN=center  >PRECINCT</th><th id=small ALIGN=center  >SEQ</th><th id=small ALIGN=center  >BARANGAY</th>
		<th id=small  ALIGN=center >LITERATE?</th><th id=small ALIGN=center  >4P'S</th>
		<th>ATO</th>
		<th>REMARKS</th>
		<th id=small ALIGN=center>ACTIONS</th>
	</tr>
	
	<?php
		require("../connect-sqli.php");
		
		$table=$_GET["table"];
		$votno=$_GET["id"];
		if($votno==""){
			$votno=0;
		}
		
		$value=strtoupper($_GET["value"]);
		$rep="<b style='color:#0014d0;background:#ffa0a0'>$value</b>";
		
		$ex=$link->query("select * from voters v where v.city_mun='".$_SESSION["city_mun"]."' and v.lit like '%".$_GET["value"]."%' or (v.vname like '%".$_GET["value"]."%' and v.city_mun='".$_SESSION["city_mun"]."') 
			order by v.vname limit 0,15")or die(mysqli_error($link));
		$ctr=1;
				
		if(mysqli_num_rows($ex)<1){
			echo "<tr><td colspan=6><B style='color:red'>No records found! :(</B></td></tr>";
		}else{
			while($rs=mysqli_fetch_array($ex)){
				$found=false;
				$rem="";
				$qq=$link->query("select *
					from mce where 
					vin='".$rs["vin"]."'")or die(mysqli_error($link));
				if($rss=mysqli_fetch_array($qq)){
					$found=true;
					$rem=" <a href='mceinfo.php?mce=".$rs["vin"]."'>-MCE</a>";
				}else{
					$qq=$link->query("select *
						from pc where 
						vin='".$rs["vin"]."'")or die(mysqli_error($link));
					if($rss=mysqli_fetch_array($qq)){
						$found=true;
						$rem=" <a href='pclist.php?pc=".$rs["vin"]."'> -Mun. Coor.</a>";
					}else{
						$qq=$link->query("select *
							from prkldr where 
							vin='".$rs["vin"]."'")or die(mysqli_error($link));
						if($rss=mysqli_fetch_array($qq)){
							$found=true;
							$rem=" <a href='prkldr.php?prkldr=".$rs["vin"]."'> -Prk. Coor.</a>";
						}else{
							$qq=$link->query("select *
								from bce where 
								vin='".$rs["vin"]."'")or die(mysqli_error($link));
							if($rss=mysqli_fetch_array($qq)){
								$found=true;
								$rem=" <a href='bceinfo.php?bce=".$rs["vin"]."'> -BCE</a>";
							}else{
								$qq=$link->query("select *
									from hl where 
									vin='".$rs["vin"]."'")or die(mysqli_error($link));
								if($rss=mysqli_fetch_array($qq)){
									$found=true;
									$rem=" <a href='hlinfo.php?hl=".$rs["vin"]."'> -HL</a>";
								}else{
									$qq=$link->query("select *
										from hl_children where 
										vin='".$rs["vin"]."'")or die(mysqli_error($link));
									if($rss=mysqli_fetch_array($qq)){
										$found=true;
										$rem=" -Member";
									}
								}
							}
						}
					}
				}
				
				$birthDate = $rs["birth"];;
				$birthDate = explode("-", $birthDate);
				$age = (date("md", date("U", mktime(0, 0, 0, $birthDate[1], $birthDate[2], $birthDate[0]))) > date("md") ? ((date("Y")-$birthDate[0])-1):(date("Y")-$birthDate[0]));
						
				echo"
					<tr class='odd' id='q_tr_$ctr' style=\"height:0px;";
						if($found==true){
							echo "background:#222;color:#fff;";
						}
						echo";font:12px arial;\"  >
						<td style='text-transform:capitalize;text-align:left' >$ctr). &nbsp;&nbsp;&nbsp;".str_replace($value,$rep,$rs["vname"])." <b style='color:red' >$rem</b></td>
						<td>".$rs["precinct"]."</td>
						<td>".$rs["seq"]."</td>
						<td>".$rs["barangay"]."</td>
						<td >
							".$rs["lit"]."
						</td>
						<td >
							<div>
								<select id='q_sel_4p_$ctr' style='padding:1px'  onchange=\"changeStats('$rs[0]','hl_children','_4p',this.value)\" >
									<option>No</option>
									<option ";
										if($rs["_4p"]==1){echo "selected";}
									echo">Yes</option>
								</select>
							</div>
						</td>
						<td>
							<select id='q_sel_sure_$ctr' style='padding:1px;width:70px' >
								<option>Sure</option>
								<option>Sure Wala Diri</option>
								<option>Undecided</option>
							</select>
						</td>
						<td><input type=text id='q_sel_rem_$ctr' style='width:150px' /></td>
						<td style='text-align:center' >";
							if($found==false)
								echo"<input alt='Add' type='image' src='images/accept.png' title='Click to Add' onclick=\"addmce('$rs[0]',$ctr);\" />";
						echo"</td>
					</tr>";
					
				$ctr++;
				
				if($ctr==20){
					break;
				}
			}
		}
	?>
</table>
		
		