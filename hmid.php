<?php
	require("connect-sqli.php");
	require("head.php");
?>

<script>
	var table=0;
	var hlvotno=0;		
</script>

<div id="t_controls" class="controls">

<?php require("menu.php"); ?>	

<script> setActive("id"); </script>

<form method=post enctype="multipart/form-data">
	<table style="margin:0 auto">
		<tr style="background:#375ba2;" class="no_style">
			<td><br>
				<table>
					<tr style="background:#375ba2;color:#fff">
						<td style="padding:0 0 0 15px;" ><input  type=text name="t_search" value="<?php echo $_POST["t_search"];?>" placeholder="Type a keyword" autofocus size=40 /></td>
						<td><input type=submit name='b_search' value="Search" /></td>
						<td><input type=button onclick="jump('hmid.php?barangay=<?php echo $_GET["barangay"]; ?>&type=<?php echo $_GET["type"]; ?>')" value="Refresh" /></td>
						<td><input type=button value='Print' onclick="$('.nav').hide();getID('t_controls').style.visibility='hidden'; window.print(); getID('t_controls').style.visibility='visible'; $('.nav').show();" /></td>
						<td align=right>
							<select style="padding:5px;"  onchange="jump('?barangay=<?php echo $_GET["barangay"]; ?>&type='+this.value)" >
								<option>Select ID Type</option>
								<option <?php if($_GET["type"]=="MEC")echo "selected"; ?> >MEC</option>
								<option <?php if($_GET["type"]=="BEC")echo "selected"; ?> >BEC</option>
								<option <?php if($_GET["type"]=="BCG")echo "selected"; ?> >BCG</option>
								<option <?php if($_GET["type"]=="PL")echo "selected"; ?> >PL</option>	
								<option <?php if($_GET["type"]=="HL")echo "selected"; ?> >HL</option>
								<option <?php if($_GET["type"]=="HM")echo "selected"; ?> >HM</option>
								<option <?php if($_GET["type"]=="Black")echo "selected"; ?> >Black</option>
								<option <?php if($_GET["type"]=="Blue")echo "selected"; ?> >Blue</option>
								<option <?php if($_GET["type"]=="Red")echo "selected"; ?> >Red</option>
								<option <?php if($_GET["type"]=="White")echo "selected"; ?> >White</option>
							</select>
						</td>
						
						<td align=right >
							<select style="padding:5px;" onchange="jump('?barangay='+this.value+'&type=<?php echo $_GET["type"]; ?>')" >
								<option>All barangays</option>
								<?php
									$ex = $link->query("select barangay from voters group by barangay order by barangay");
									while($rs=$ex->fetch(PDO::FETCH_BOTH)){
										echo "<option ";
									if($_GET["barangay"]===$rs[0])
										echo "selected";
										echo" >".$rs["0"]."</option>";
									}
								?>
							</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</div>

<div style="padding:55px;" ></div><center>

<div style="width:1000px;margin:0 auto">
<div style="padding:3px;" ></div> 
	<?php		
		$rec=20;
		$p=$_GET['page'];
		if($p>1){
			$to=$rec;
			$from=($p*$rec)-$rec;
			$i=(($p-1)*$rec)+1;
		}else{
			$to=$rec;
			$from=0;
			$i=1;
			$p=1;
		}	
			
		$bar="";
		if($_GET["barangay"]!="" && $_GET["barangay"]!="All barangays" )
			$bar=" v.barangay='".$_GET["barangay"]."'  and ";
			
		$search_value=explode("=>",$_POST["t_search"]);
		$sv="v.vname like '%".$search_value["0"]."%'";
		for($xx=1;$xx<count($search_value);$xx++){
			$sv.=" or v.vname like '%".$search_value[$xx]."%'";
		}
					
		$type=$_GET["type"];
			if($type=="HM"){
				$type="Member";
				$stmt = $link->prepare("select * from hl_children hc, voters v where {$bar} hc.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex = $stmt;
				$stmt = $link->prepare("select * from hl_children hc, voters v where {$bar} hc.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex2 = $stmt;
				
				if(isset($_POST["b_search"])){
					$search_value=explode("=>",$_POST["t_search"]);
					$sv="v.vname like '%".$search_value["0"]."%'";
					for($xx=1;$xx<count($search_value);$xx++){
						$sv.=" or v.vname like '%".$search_value[$xx]."%'";
					}
					$stmt = $link->prepare("select * from hl_children hc, voters v where ({$sv}) and {$bar} hc.vin=v.vin order by v.vname  limit {$from},{$to}");
	$stmt->execute([]);
	$ex = $stmt;
				}
			} else if($type=="MEC"){
				$type="Mun. Electoral Council";
				$stmt = $link->prepare("select * from mce m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex = $stmt;
				$stmt = $link->prepare("select * from mce m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex2 = $stmt;
				
				if(isset($_POST["b_search"])){
				$stmt = $link->prepare("select * from mce m, voters v where ({$sv}) and {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
				$stmt->execute([]);
				$ex = $stmt;
			}
			}
			else if($type=="BEC"){
				$type="Brgy. Electoral Council";
				$stmt = $link->prepare("select * from bce m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex = $stmt;
				$stmt = $link->prepare("select * from pl m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex2 = $stmt;
				if(isset($_POST["b_search"])){
				$stmt = $link->prepare("select * from bce m, voters v where ({$sv}) and {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
				$stmt->execute([]);
				$ex = $stmt;
			}
			}
			else if($type=="BCG"){
				$type="Brgy. Core Group";

				$stmt = $link->prepare("select * from bcg m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex = $stmt;
				$stmt = $link->prepare("select * from bcg m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex2 = $stmt;
				if(isset($_POST["b_search"])){
				$stmt = $link->prepare("select * from bcg m, voters v where ({$sv}) and {$bar} ? m.vin=v.vin order by v.vname limit {$from},{$to}");
				$stmt->execute([$type]);
				$ex = $stmt;
			}

			}
			else if($type=="Black"){
				$type="Black Ninja";
				$stmt = $link->prepare("select * from black m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex = $stmt;
				$stmt = $link->prepare("select * from black m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex2 = $stmt;
				if(isset($_POST["b_search"])){
				$stmt = $link->prepare("select * from black m, voters v where ({$sv}) and {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
				$stmt->execute([]);
				$ex = $stmt;
			}
			}

			else if($type=="Blue"){
				$type="Blue Warrior";
				$stmt = $link->prepare("select * from blue m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex = $stmt;
				$stmt = $link->prepare("select * from blue m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex2 = $stmt;
				if(isset($_POST["b_search"])){
				$stmt = $link->prepare("select * from blue m, voters v where ({$sv}) and {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
				$stmt->execute([]);
				$ex = $stmt;
			}
			}

			else if($type=="Red"){
				$type="Red Lady";
				$stmt = $link->prepare("select * from red m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex = $stmt;
				$stmt = $link->prepare("select * from red m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex2 = $stmt;
				if(isset($_POST["b_search"])){
				$stmt = $link->prepare("select * from red m, voters v where ({$sv}) and {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
				$stmt->execute([]);
				$ex = $stmt;
			}
			}

			else if($type=="White"){
				$type="White Angel";
				$stmt = $link->prepare("select * from white m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex = $stmt;
				$stmt = $link->prepare("select * from white m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex2 = $stmt;
				if(isset($_POST["b_search"])){
				$stmt = $link->prepare("select * from white m, voters v where ({$sv}) and {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
				$stmt->execute([]);
				$ex = $stmt;
			}
			}

			else if($type=="PL"){
				$type="Precinct Leader";
				$stmt = $link->prepare("select * from pl m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex = $stmt;
				$stmt = $link->prepare("select * from hl m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex2 = $stmt;
				if(isset($_POST["b_search"])){
				$stmt = $link->prepare("select * from pl m, voters v where ({$sv}) and {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
				$stmt->execute([]);
				$ex = $stmt;
			}
			}
			else if($type=="HL"){
				$type="Household Leader";
				$stmt = $link->prepare("select * from hl m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex = $stmt;
				$stmt = $link->prepare("select * from hl m, voters v where {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
	$stmt->execute([]);
	$ex2 = $stmt;
				if(isset($_POST["b_search"])){
				$stmt = $link->prepare("select * from hl m, voters v where ({$sv}) and {$bar} m.vin=v.vin order by v.vname limit {$from},{$to}");
				$stmt->execute([]);
				$ex = $stmt;
			}
			}
						
			$i=1;

			$value=strtoupper($_POST["t_search"]);
			$rep="<b style='color:#0014d0;background:#ffa0a0'>".$value."</b>";

			while($rs=$ex->fetch(PDO::FETCH_BOTH)){
												
			echo"
				<div style=\"position:relative;width:910px;height:290px;\" >
					<img src='images/id.coalition.png' width=100% />
					<div style='background:transparent;border:1px solid #545454;border-radius:5px;position:absolute;left:25px;top:88px;width:143px;height:143px;overflow:hidden' >";
						if(file_exists("images/voters/".$rs["vin"].".jpg")){
							echo"<img src='images/voters/".$rs["vin"].".jpg' style='width:100%' />";
						}else
							echo"<img src='images/blank.jpg' style='width:100%' />";
						echo"

					</div>";
						
				$stmt = $link->prepare('select * from voters where vin=?');
	$stmt->execute([$rs["1"]]);
	$exHL = $stmt;
				$rsHL=$exHL->fetch(PDO::FETCH_BOTH);
						
				$id=$rs["vin"];
			
				echo"<div style='text-align:left;position:absolute;left:180px;top:85px;width:255px;height:143px;color:transparent;padding:2px;'>
					<div style='font-size:18px;color:#FFF;background:transparent'><b>".$rs["vname"]."</b></div>
					<div style='color:#FFF;background:transparent'>".$type."</div>
					<div style='font-size:12px;background:transparent;color:#FFF'>".$rs["address"].", ".$rs["barangay"]."</div>
				</div>
				<div style='text-align:center;width:142px;margin-left:13px;background:#fff;border-radius:4px;padding-left:2px;padding-right:2px;position:absolute;left:12px;bottom:25px;color:#b30000;font-size:20px;' >
					<b style='font-size:18px;'>IDN: ".$id."</b> 
				</div>
									
			</div>";
		}
	?>
</form>	<br> <br> </center>
	<div style="position:fixed; left:0;width:100%;bottom:0;height:60px;background:#b61212;z-index:1000" class="nav" >
		<div style="padding:1px;text-align:center;" >
			<table style="margin:0 auto;">
				<tr style="background:transparent" >
					<td><input <?php if($_GET["page"]<=1)echo" disabled "; ?> type=image value="Previous" src="images/prev.png" onclick="jump('?page=<?php echo ($_GET["page"]-1)."&barangay=".$_GET["barangay"]; ?>&type=<?php echo $_GET["type"] ?>')" /></td>
					<td><input <?php if($_GET["page"]>=mysqli_num_rows)echo"disabled"; ?>  type=image value="Next" src="images/next.png" onclick="jump('?page=<?php echo ($_GET["page"]+1)."&barangay=".$_GET["barangay"]; ?>&type=<?php echo $_GET["type"] ?>')" /></td>
				</tr>
			</table>
		</div>
	</div>
</div>

</body>

</html>