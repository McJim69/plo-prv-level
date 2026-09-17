<?php
	require("connect-pdo.php");
	require("head.php");
	
	$stmt = $link->prepare('select * from voters where vin=?');
	$stmt->execute([$_GET["hl"]]);
	$ex = $stmt;
	$rshl=$ex->fetch(PDO::FETCH_BOTH);

	$stmt = $link->prepare('select count(*) from hl_children where hlvin=?');
	$stmt->execute([$_GET["hl"]]);
	$ex = $stmt;
	$rsbce=$ex->fetch(PDO::FETCH_BOTH);
	$hlm=$rsbce[0];
	
	$stmt = $link->prepare('select * from voters v, hl h where h.vin=? and v.vin=h.bcevin ');
	$stmt->execute([$_GET["hl"]]);
	$ex2 = $stmt;
	$rsbce_data=$ex2->fetch(PDO::FETCH_BOTH);
	
	$hl="'".$_GET["hl"]."'";
	
	$stmt = $link->prepare("select cluster from clusters where precinct LIKE CONCAT('%', ?, '%')");
	$stmt->execute([$rshl["precinct"]]);
	$cluster = $stmt;
	$rsc=$cluster->fetch(PDO::FETCH_BOTH);
	$hl_cluster = $rsc ? $rsc[0] : "-";
	
	require("menu.php");
	require("popvoters.php");
?>

<script>setActive("hl");</script>

<div id="toprint" class="container d-none" style="max-width: 1000px;">
	<div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
		<div>
			<h2 style="font-weight: 700;"><?php echo htmlspecialchars($rshl["vname"]); ?></h2>
			<h4 class="text-muted">Household Leader (HL)</h4>
			<p class="mb-0 text-muted"><?php echo htmlspecialchars($rshl["barangay"]) . ", " . htmlspecialchars($rshl["city_mun"]); ?></p>
		</div>
		<div>
			<h5>LIST OF HOUSEHOLD MEMBERS</h5>
		</div>
	</div>
	
	<table class="table table-bordered">
		<thead>
			<tr>
				<th>NO.</th>
				<th>NAME OF MEMBERS</th>
				<th>IDN</th>
				<th>SEX</th>
				<th>AGE</th>
				<th>PRECINCT</th>
				<th>PUROK</th>
				<th>BARANGAY</th>
				<th>CLUSTER</th>
			</tr>
		</thead>
		<tbody>
			<?php
				$stmt = $link->prepare('select * from hl_children hc, voters v where hc.hlvin=? and hc.vin=v.vin order by v.vname');
				$stmt->execute([$_GET["hl"]]);
				$ex1 = $stmt;
				$k=1;
				while($rs=$ex1->fetch(PDO::FETCH_BOTH)){
					$stmt = $link->prepare("select cluster from clusters where precinct LIKE CONCAT('%', ?, '%')");
					$stmt->execute([$rs["precinct"]]);
					$rsc2=$stmt->fetch(PDO::FETCH_BOTH);
					$hm_cluster = $rsc2 ? $rsc2[0] : "-";
					
					$birthDate = $rs["birth"];
					$birthDate = explode("-", $birthDate);
					$age = "-";
					if(count($birthDate) === 3) {
						$age_temp_date = $birthDate[0] . '-' . $birthDate[1] . '-' . $birthDate[2];
						if ($birthDate[0] !== '0000' && !empty($birthDate[0])) {
							$birthObj = date_create($age_temp_date);
							if ($birthObj !== false) {
								$age = date_diff($birthObj, date_create('today'))->y;
							}
						}
					}
					
					echo "<tr>
						<td>$k</td>
						<td class='text-uppercase'>".htmlspecialchars($rs["vname"])."</td>
						<td>".sprintf("%04d", $rs["vin"])."</td>
						<td>".$rs["sex"]."</td>
						<td>".$age."</td>
						<td>".$rs["precinct"]."</td>
						<td>".htmlspecialchars($rs["address"])."</td>
						<td>".htmlspecialchars($rs["barangay"])."</td>
						<td>".$hm_cluster."</td>
					</tr>";
					$k++;
				}
			?>
		</tbody>
	</table>
</div>

<div id="bottom" class="main-content">	
	<div class="container my-4">
		<!-- HL Glass Card -->
		<div class="sr-hero card-glass mb-4 text-white">
			<div class="row align-items-center g-4">
				<div class="col-md-3 text-center">
					<?php
						if(file_exists("images/voters/".$_GET["hl"].".jpg")){
							echo "<img style='border: 3px solid rgba(255,255,255,0.3); border-radius: var(--radius-md); max-width: 100%; max-height: 220px; object-fit: cover;' src='images/voters/".$_GET["hl"].".jpg?".date("h:i:s")."' />"; 
						}else{
							echo "<img style='border: 3px solid rgba(255,255,255,0.3); border-radius: var(--radius-md); max-width: 100%; max-height: 220px; object-fit: cover;' src='images/blank.jpg' />"; 
						}
					?>
				</div>
				<div class="col-md-9">
					<h2 class="text-uppercase mb-1" style="font-weight: 700; color: #ffffff;"><?php echo $rshl["vname"]; ?></h2>
					<h5 style="color: rgba(255,255,255,0.8); font-weight: 500;">HOUSEHOLD LEADER (HL)</h5>
					<hr style="border-color: rgba(255,255,255,0.2);">
					<div class="row g-2 mb-3" style="font-size: 14px;">
						<div class="col-sm-6">ID No: <strong class="text-warning"><?php printf("%04d", $rshl["vin"]); ?></strong></div>
						<div class="col-sm-6">Precinct: <strong class="text-warning"><?php echo $rshl["precinct"]; ?></strong> &nbsp; Cluster: <strong class="text-warning"><?php echo $hl_cluster; ?></strong></div>
						<div class="col-sm-6">Address: <strong><?php echo $rshl["address"] . ", " . $rshl["barangay"]; ?></strong></div>
						<div class="col-sm-6">Total Household Members: <strong class="text-warning"><?php echo $hlm; ?></strong></div>
					</div>
					
					<div id="d_controls" class="d-flex gap-2 flex-wrap">
						<button class="btn btn-success btn-sm" onclick="add()"><i class="fa-solid fa-user-plus"></i> Add Household Member</button>
						<button class="btn btn-light btn-sm text-danger" onclick="printF()"><i class="fa-solid fa-print"></i> Print</button>
						<button class="btn btn-outline-light btn-sm" onclick="jump('hllist.php')"><i class="fa-solid fa-list"></i> HL List</button>
						<button class="btn btn-outline-light btn-sm" onclick="jump('index.php')"><i class="fa-solid fa-house"></i> Home</button>
					</div>
					<script>
						function printF(){
							$('#d_controls').addClass('d-none');
							$('#bottom').addClass('d-none');
							$('#toprint').removeClass('d-none');
							$('.hid').addClass('d-none');
							window.print();
							$('#toprint').addClass('d-none');
							$('#bottom').removeClass('d-none');
							$('#d_controls').removeClass('d-none');
							$('.hid').removeClass('d-none');
						}
					</script>
				</div>
			</div>
		</div>

		<!-- Title Section -->
		<div class="mb-4">
			<h4 style="font-weight: 700; color: var(--text-main);">
				Household Members belonged to <span class="text-danger"><?php echo htmlspecialchars($rshl["vname"]); ?></span>:
			</h4>
		</div>

		<!-- Cards Grid Container -->
		<form method="post" enctype="multipart/form-data" class="m-0 hid">
			<div id="thumbnails" class="tomb-grid">
				<?php
					$stmt = $link->prepare('select * from hl_children hc, voters v where hc.hlvin=? and hc.vin=v.vin order by v.vname');
					$stmt->execute([$_GET["hl"]]);
					$ex = $stmt;
					
					$i=1;
					$value = isset($_POST["t_search"]) ? strtoupper($_POST["t_search"]) : "";
					$rep = "<b style='color:#0014d0;background:#ffa0a0'>".$value."</b>";
					
					while($rs=$ex->fetch(PDO::FETCH_BOTH)){
						$stmt = $link->prepare("select cluster from clusters where precinct LIKE CONCAT('%', ?, '%')");
						$stmt->execute([$rs["precinct"]]);
						$rsc2=$stmt->fetch(PDO::FETCH_BOTH);
						$hm_cluster = $rsc2 ? $rsc2[0] : "-";
						
						$birthDate = $rs["birth"];
						$birthDate = explode("-", $birthDate);
						$age = "-";
						if(count($birthDate) === 3) {
							$age_temp_date = $birthDate[0] . '-' . $birthDate[1] . '-' . $birthDate[2];
							$age = "-";
							if ($birthDate[0] !== '0000' && !empty($birthDate[0])) {
								$birthObj = date_create($age_temp_date);
								if ($birthObj !== false) {
									$age = date_diff($birthObj, date_create('today'))->y;
								}
							}
						}
										
						if(isset($_POST["b_remove_".$rs["0"]])){
							$stmt = $link->prepare('delete from mce where vin=?');
							$stmt->execute([$rs["2"]]);
							$stmt = $link->prepare('update voters set ato=\'Dili\' where vin=?');
							$stmt->execute([$rs["2"]]);
							jump("");
						}
						if(isset($_POST["b_upImg_".$rs["0"]])){
							move_uploaded_file($_FILES["b_file_".$rs["0"]]["tmp_name"], "images/voters/".$rs[0].".jpg");
							$stmt = $link->prepare('update voters set ispicset=1 where vin=?');
							$stmt->execute([$rs["0"]]);
							jump("");
						}

						$color = ($hl_cluster != $hm_cluster) ? "color:red;" : "";
						
						$bck="background:#375ba2;color:#fff;";
						if($rs["ato"]==="Undecided")
							$bck="background:#ff6700;color:#fff;";
						else if($rs["ato"]==="Sure-OT")
							$bck="background:#aba100;color:#fff;";
						else if($rs["ato"]==="Dili-Ato")
							$bck="background:#d32727;color:#fff;";

						$img_src = file_exists("images/voters/".$rs["2"].".jpg") ? "images/voters/".$rs["2"].".jpg" : "images/blank.jpg";
						
						$col = "";
						$naa = "";
						$ato_val = $rs["ato"];
						if($ato_val == "Sure"){
							$naa = "Naa";
						} elseif($ato_val == "Sure-OT") {
							$naa = "Wala";
						}
						
						echo "
						<div class='tomb card-glass' id='div_".$rs["vin"]."' 
							onmouseout=\"getID('div_controls_".$rs["vin"]."').style.visibility='hidden';\" 
							onmousemove=\"getID('div_controls_".$rs["vin"]."').style.visibility='visible';\">
							
							<div style='$bck border-radius:15px;text-align:center;font-size:14px;padding:5px;margin-bottom:6px'><b>".$rs["ato"]."</b></div>
							<span 
								class='translate-right badge rounded-pill shadow' 
								style='$bck position:absolute; top:15px; left:13px; font-size: 17px; 
								border: 2px solid #fff; padding: 6px 10px; z-index:3'>$i
							</span>
				
							<div class='tomb-img-container' onclick=\"$('#b_file_".$rs["0"]."').click();\">
								<img src='$img_src?".date("i:s")."' style='border-radius: 4px;' />
								<div class='tomb-img-overlay'>
									<i class='fa-solid fa-camera'></i>
									<span>Change Photo</span>
								</div>
							</div>
							<input type='file' name='b_file_".$rs["0"]."' id='b_file_".$rs["0"]."' style='display:none;' onchange=\"if(this.value!='') $('#b_upImg_".$rs["0"]."').click();\" />
							<input type='submit' name='b_upImg_".$rs["0"]."' id='b_upImg_".$rs["0"]."' style='display:none;' />
							
							<div class='mt-2'>
								<h5 style='font-size: 15px; font-weight: 700; margin-bottom: 2px; text-transform: uppercase;'>".str_ireplace($value, $rep, $rs["vname"])."</h5>
								<p class='text-muted small mb-2' style='font-size: 12px;'><i class='fa-solid fa-id-card'></i> ID: " . sprintf("%04d", $rs["vin"]) . "</p>
							</div>
							
							<div class='small text-muted space-y-1' style='font-size: 13px;'>
								<div>Sex: <strong>" . ($rs["sex"] == "M" ? "Male" : "Female") . "</strong></div>
								<div>Age: <strong>$age y.o.</strong></div>
								<div>Precinct: <strong>".str_ireplace($value, $rep, $rs["precinct"])."</strong> &nbsp; Cluster: <strong style='$color'>$hm_cluster</strong></div>
								<div>Address: <strong>".str_ireplace($value, $rep, $rs["address"])."</strong></div>
							</div>
							
							<hr class='my-2'>
							<div class='small text-muted space-y-1' style='font-size: 12px;'>
								<div>Sure: <strong>$ato_val</strong></div>
								<div>Naa/Wala: <strong>$naa</strong></div>
								<div>Remarks: <strong class='text-dark'>".$rs["3"]."</strong></div>
							</div>";
							if(($_SESSION["access"]=="SuperAdmin") or ($_SESSION["access"]=="Admin")){
							echo"
							<div class='mt-3 d-flex gap-2 justify-content-between align-items-center' style='visibility: hidden; transition: var(--transition);' id='div_controls_".$rs["vin"]."'>
								<button style='width:45%' onclick=\"deletehlc('".$rs["vin"]."','".$rs["2"]."')\" class='btn btn-sm btn-danger px-2'><i class='fa-solid fa-trash-can'></i> Remove</button>
								<select class='form-select form-select-sm p-1' style='width: 130px; font-size: 12px;' onchange=\"updateStatus(this.value,'".$rs["2"]."','ato')\">
									<option " . ($rs["ato"] === "Sure" ? "selected" : "") . ">Sure</option>
									<option " . ($rs["ato"] === "Sure-OT" ? "selected" : "") . ">Sure-OT</option>
									<option " . ($rs["ato"] === "Undecided" ? "selected" : "") . ">Undecided</option>
									<option>Add Remarks</option>
								</select>
							</div>";
							}
						echo"</div>";
						$i++;
					}
					if(isset($_GET["add"]) && $_GET["add"] == "auto"){
						echo "<script>$('#bAddMem').click()</script>";
					}
				?>
			</div>
		</form>
	</div>
</div>

</body>
</html>

<script>
	function addmce(id,row){
		var vin=id;
		var sure=getID("q_sel_sure_"+row).value;
		var rem=getID("q_sel_rem_"+row).value;

		var url = "ajax/addhlmember.php?hlvin="+<?php echo $hl; ?>+"&vin="+vin+"&sure="+sure+"&remarks="+rem;
		fetch(url)
			.then(response => response.text())
			.then(data => {
				if(data.trim() == "Success"){
					$("#q_tr_"+row).animate({
						opacity:0
					},500,function(){
						$("#q_tr_"+row).css("display","none");
					});
				}
			})
			.catch(err => console.error("Fetch error in addmce:", err));
	}

	function deletehlc(hlcno,vin){	
		if(confirm("Are you sure?")){
			var url = "ajax/deletehlmember.php?hlcno="+hlcno+"&vin="+vin;
			fetch(url)
				.then(response => response.text())
				.then(data => {
					if(data.trim() == "Success"){
						$("#div_"+hlcno).fadeOut(500);
					}else{
						alert(data);
					}
				})
				.catch(err => console.error("Fetch error in deletehlc:", err));
		}
	}
			
	function updateStatus(value,vin,t){	
		if(value=="Add Remarks"){
			var rem=prompt("Remarks:");
			updateRemarks(vin,rem);
		}else{
			if(confirm("Are you Sure?")){
				var url = "ajax/updatestatus.php?vin="+vin+"&value="+value+"&target="+t;
				fetch(url)
					.then(response => response.text())
					.then(data => {
						if(value!="Sure" && t!="_4p"){
							var rem=prompt("Remarks:");
							updateRemarks(vin,rem);
						}
					})
					.catch(err => console.error("Fetch error in updateStatus:", err));
			}
		}
	}

	function updateRemarks(vin,remarks){	
		var url = "ajax/updateremarks.php?vin="+vin+"&remarks="+remarks;
		fetch(url)
			.then(response => response.text())
			.then(data => {
				if(data.trim() == ""){
					jump("");
				}else
					alert(data);
			})
			.catch(err => console.error("Fetch error in updateRemarks:", err));
	}
</script>