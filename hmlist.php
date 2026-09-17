<?php
	require("connect-pdo.php");
	require("head.php");
	require("menu.php");

	// Photo upload handler
	foreach($_FILES as $key => $file) {
		if (strpos($key, 'b_file_') === 0 && $file['tmp_name'] != '') {
			$vin = substr($key, 7);
			move_uploaded_file($file['tmp_name'], "images/voters/".$vin.".jpg");
			$stmt = $link->prepare('update voters set ispicset=1 where vin=?');
			$stmt->execute([$vin]);
			jump("");
		}
	}

	$rec=20;
	$p=isset($_GET['page']) ? intval($_GET['page']) : 1;
	if($p<1) $p=1;
	$from=($p-1)*$rec;
	
	$ato_filter="";
	if(isset($_GET["ato"]) && $_GET["ato"]!="" && $_GET["ato"]!="Ato-All" )
		$ato_filter=" v.ato='".$_GET["ato"]."' and ";
		
	$bar_filter="";
	if(isset($_GET["barangay"]) && $_GET["barangay"]!="" && $_GET["barangay"]!="All barangays" )
		$bar_filter=" v.barangay='".$_GET["barangay"]."'  and ";
	
	$stmt = $link->prepare("select * from hl_children hc, voters v where {$bar_filter} {$ato_filter} hc.vin=v.vin and v.city_mun=? order by v.vname");
	$stmt->execute([$_SESSION["city_mun"]]);
	$exz = $stmt;
	
	$stmt = $link->prepare("select * from hl_children hc, voters v where {$bar_filter} {$ato_filter} hc.vin=v.vin and v.city_mun=? order by v.vname LIMIT {$from},{$rec} ");
	$stmt->execute([$_SESSION["city_mun"]]);
	$ex = $stmt;
	if(isset($_POST["b_search"])){
		$stmt = $link->prepare("select * from hl_children hc, voters v where (v.vname LIKE CONCAT('%', ?, '%') or v.barangay LIKE CONCAT('%', ?, '%')) and {$bar_filter} {$ato_filter} hc.vin=v.vin and v.city_mun=? order by v.vname LIMIT {$from},{$rec} ");
		$stmt->execute([$_POST["t_search"], $_POST["t_search"], $_SESSION["city_mun"]]);
		$ex = $stmt;
	}
		
	$value=isset($_POST["t_search"]) ? strtoupper($_POST["t_search"]) : "";
	$rep="<b style='color:#0014d0;background:#ffa0a0'>".$value."</b>";
?>

<script> setActive("hm"); </script>

<div class="container main-content">
	<!-- Search & Controls Card -->
	<form method="post" enctype="multipart/form-data" id="searchForm">
		<div class="card-glass p-3">
			<div class="row g-3 align-items-center">
				<div class="col-md-3">
					<div class="input-group">
						<span class="input-group-text border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
						<input type="text" name="t_search" id="t_search" class="form-control border-start-0 ps-2" 
							placeholder="Type a keyword..." value="<?php echo isset($_POST["t_search"]) ? htmlspecialchars($_POST["t_search"]) : ''; ?>" />
					</div>
				</div>
				<div class="col-md-3 d-flex gap-2">
					<button type="submit" name="b_search" class="btn btn-danger flex-fill"><i class="fa-solid fa-search"></i> Search</button>
					<button type="submit" class="btn btn-secondary" onclick="document.getElementById('t_search').value=''"><i class="fa-solid fa-rotate"></i></button>
					<button type="button" class="btn btn-success flex-fill" onclick="getID('div_hl').style.display='block';"><i class="fa-solid fa-plus"></i> Add HM</button>
				</div>
				<div class="col-md-4 d-flex gap-2">
					<select class="form-select" onchange="jump('?barangay='+encodeURIComponent(this.value)+'&ato=<?php echo isset($_GET["ato"]) ? htmlspecialchars($_GET["ato"]) : ''; ?>')">
						<option value="All barangays">All barangays</option>
						<?php
							$stmt = $link->prepare('select barangay from voters where city_mun=? group by barangay order by barangay');
							$stmt->execute([$_SESSION["city_mun"]]);
							$ex2 = $stmt;
							while($rs=$ex2->fetch(PDO::FETCH_BOTH)){
								$sel = (isset($_GET["barangay"]) && $_GET["barangay"] === $rs[0]) ? "selected" : "";
								echo "<option value='".htmlspecialchars($rs[0])."' $sel>".htmlspecialchars($rs[0])."</option>";
							}
						?>
					</select>
					<select class="form-select" onchange="jump('?barangay=<?php echo isset($_GET["barangay"]) ? htmlspecialchars($_GET["barangay"]) : ''; ?>&ato='+encodeURIComponent(this.value))">
						<option value="Ato-All">Ato-All</option>
						<option value="Sure-OT" <?php if(isset($_GET["ato"]) && $_GET["ato"]=="Sure-OT") echo "selected"; ?>>Sure-OT</option>
						<option value="Undecided" <?php if(isset($_GET["ato"]) && $_GET["ato"]=="Undecided") echo "selected"; ?>>Undecided</option>
						<option value="Dili-Ato" <?php if(isset($_GET["ato"]) && $_GET["ato"]=="Dili-Ato") echo "selected"; ?>>Dili-Ato</option>
					</select>
				</div>
				<?php
					$i = $from + 1;			
					$total_pages = ceil($exz->rowCount() / $rec);
					if($total_pages < 1) $total_pages = 1;
				?>
				<div class="col-md-2 d-flex gap-2">
					<button class="w-40 btn btn-danger">Goto</button> 
					<select class="w-60 form-select btn btn-danger" style="padding: 0; margin:0;" onchange="jump('?page='+this.value+'&barangay=<?php echo isset($_GET["barangay"]) ? urlencode($_GET["barangay"]) : ''; ?>&ato=<?php echo isset($_GET["ato"]) ? urlencode($_GET["ato"]) : ''; ?>')">
						<?php
							for($j=1; $j<=$total_pages; $j++){
								$sel = ($p == $j) ? "selected" : "";
								echo "<option value='$j'$sel>Page $j</option>";
							}
						?>
					</select>
				</div>
			</div>
		</div>
	</form>	

	<?php
		$i=1;
		$h_search = isset($_POST["h_search"]) ? $_POST["h_search"] : "";
		$div_hl_style = "display:none;";
		if (isset($_POST["l_search"])) {
			$div_hl_style = "display:block;";
		}

		if (isset($_POST["l_search"]) && $h_search != "") {
			$stmt_hl = $link->prepare("select * from voters v, hl h where (v.vname LIKE CONCAT('%', ?, '%') or v.barangay LIKE CONCAT('%', ?, '%') or v.precinct LIKE CONCAT('%', ?, '%') or v.address LIKE CONCAT('%', ?, '%')) and h.vin=v.vin and v.city_mun=? order by vname");
			$stmt_hl->execute([$h_search, $h_search, $h_search, $h_search, $_SESSION["city_mun"]]);
		} else {
			$stmt_hl = $link->prepare('select * from voters v, hl h where h.vin=v.vin and v.city_mun=? order by vname');
			$stmt_hl->execute([$_SESSION["city_mun"]]);
		}
		$ex_hl = $stmt_hl;

		$h_value = strtoupper($h_search);
		$h_rep = "<b class='text-primary bg-warning'>".$h_value."</b>";
	?>

	<div class="container-fluid" id="div_hl" style="margin-top:-185px;<?php echo $div_hl_style; ?>position:absolute;left:0;z-index:2;width:100%;height:100%;background:url('images/blank_bg.png')no-repeat;background-fill:cover;background-size:100%">
		<form method="post" class="m-0">
			<div class="mx-auto border border-danger p-3" style="background:#000;position:relative;max-width:990px; height:600px; 		top:100px; overflow-y:auto; overflow-x:hidden;border-radius:10px">
				<!-- Sticky Header -->
				<div style="
					position:sticky;
					top:-20px;
					left:-20px;
					right:-20px;
					margin:-20px -20px -8px -20px;
					z-index:100;
					background:#d00;
					color:#fff;
					padding:10px;
					font-size:20px;
					border-bottom:5px solid #aa0000;">
					<div class="row" style="display:flex;align-items:center;justify-content:space-between;margin:5px">
						<div class="col" style="max-width:300px">
							<b style="margin-left:-10px">SELECT PL TO BE ASSIGNED</b>
						</div>
						<div class="col" style="max-width:105px">
							<input type="button" class="btn btn-sm font-weight-bold" value="CLOSE" onclick="getID('div_hl').style.display='none';" />
						</div>
					</div>
					<div class="row" style="margin:5px">
						<input style="margin-right:.3%;max-width:74.7%" type="text" name="h_search" id="h_search" class="form-control" placeholder="Type a keyword..." value="<?php echo htmlspecialchars($h_search); ?>" />
						<button style="margin-left:.3%;max-width:24.7%"  type="submit" name="l_search" class="form-control"><i class="fa-solid fa-search"></i> Search</button>
					</div>
				</div>

				<div class="row mt-4">
					<?php
						while($rs=$ex_hl->fetch(PDO::FETCH_BOTH)){
							$birthDate = $rs["birth"];
							$age = "-";
							if (!empty($birthDate) && $birthDate !== '0000-00-00') {
								$birthObj = date_create($birthDate);
								if ($birthObj !== false) {
									$age = date_diff($birthObj, date_create('today'))->y;
								}
							}
							
							$stmt = $link->prepare('select count(*) from hl_children where hlvin=?');
							$stmt->execute([$rs["vin"]]);
							$ex1 = $stmt;
							$rshl=$ex1->fetch(PDO::FETCH_BOTH);
							$hl=$rshl[0];
							
							$img_src = file_exists("images/voters/".$rs["vin"].".jpg") ? "images/voters/".$rs["vin"].".jpg" : "images/blank.jpg";

							echo "
							<div class='col-md-3 mb-4'>
								<div class='card h-100 shadow-sm' id='div_".$i."' 
									 onmouseout=\"getID('div_controls_".$rs["vin"]."').style.visibility='hidden';getID('div_browse_".$rs["vin"]."').style.visibility='hidden';\" 
									 onmousemove=\"getID('div_controls_".$rs["vin"]."').style.visibility='visible';getID('div_browse_".$rs["vin"]."').style.visibility='visible';\">
								  
								  <div class='card-body text-center' onclick=\"jump('hlinfo.php?hl=".$rs["vin"]."')\" style='cursor:pointer'>
									<div class='position-relative mb-2' style='height:200px; overflow:hidden;'>
									  <img class='img-fluid' style='object-fit:cover;height:100%;width:100%;' src='$img_src?".date("h:i:s")."' />
									</div>
									<div style='text-align:center;padding:5px;font-size:12px;overflow:hidden'>
										<b class='text-truncate'>".str_ireplace($h_value,$h_rep,$rs["vname"])."</b><br>
										<b class='text-danger mb-1'>Precinct Leader</b><br>
										Sex: <b class='text-danger'>".(($rs["sex"]=="M")?"Male":"Female")."</b> | Age: <b>".$age."</b><br>
										ID No: <b>" . sprintf("%04d", $rs["vin"]) . "</b> | Precinct: <b>" . str_ireplace($h_value,$h_rep,$rs["precinct"]) . "</b><br>
										<span style='color:#ffa86c'>
											Purok: ".str_ireplace($h_value,$h_rep,$rs["address"])."
										</span><br>
										Total Mebers: <b>".$hl."</b>
									</div>
								  </div>
								</div>
							</div>";
						}
					?>
				</div>
			</div>
		</form>
	</div>

	<!-- Original Pagination Placed Here -->

	<div id="thumbnails" class="tomb-grid">		
	<?php
		while($rs=$ex->fetch(PDO::FETCH_BOTH)){

			$birthDate = $rs["birth"];
			$age = "-";
			if (!empty($birthDate) && $birthDate !== '0000-00-00') {
				$birthObj = date_create($birthDate);
				if ($birthObj !== false) {
					$age = date_diff($birthObj, date_create('today'))->y;
				}
			}
			
			$img_src = file_exists("images/voters/".$rs[2].".jpg") ? "images/voters/".$rs[2].".jpg" : "images/blank.jpg";
			
			$stmt = $link->prepare('select * from voters where vin=?');
			$stmt->execute([$rs["1"]]);
			$exHL = $stmt;
			$rsHL=$exHL->fetch(PDO::FETCH_BOTH);
			
			$stmt = $link->prepare("select cluster from clusters where precinct LIKE CONCAT('%', ?, '%')");
			$stmt->execute([$rs["precinct"]]);
			$rsc2=$stmt->fetch(PDO::FETCH_BOTH);
			$hm_cluster=$rsc2 ? $rsc2[0] : "-";
			
			$bck="background:#375ba2;color:#fff;";
			if($rs["ato"]==="Undecided")
				$bck="background:#ff6700;color:#fff;";
			else if($rs["ato"]==="Sure-OT")
				$bck="background:#aba100;color:#fff;";
			else if($rs["ato"]==="Dili-Ato")
				$bck="background:#d32727;color:#fff;";

			echo "
			<div class='tomb card-glass' id='div_".$rs["0"]."' 
				onmouseout=\"getID('div_controls_".$rs["0"]."').style.visibility='hidden';\" 
				onmousemove=\"getID('div_controls_".$rs["0"]."').style.visibility='visible';\">			
				
				<div style='$bck border-radius:15px;text-align:center;font-size:14px;padding:5px;margin-bottom:6px'><b>".$rs["ato"]."</b></div>
				<span 
					class='translate-right badge rounded-pill shadow' 
					style='$bck position:absolute; top:15px; left:13px; font-size: 17px; 
					border: 2px solid #fff; padding: 6px 10px; z-index:3'>$i
				</span>
				
				<div class='tomb-img-container' onclick=\"$('#b_file_".$rs["vin"]."').click();\">
					<img src='$img_src?".date("h:i:s")."' />
					<div class='tomb-img-overlay'>
						<i class='fa-solid fa-camera'></i>
						<span>Change Photo</span>
					</div>
				</div>
				<input type='file' name='b_file_".$rs["vin"]."' id='b_file_".$rs["vin"]."' style='display:none;' onchange=\"if(this.value!='') $('#b_upImg_".$rs["vin"]."').click();\" />
				<input type='submit' name='b_upImg_".$rs["vin"]."' id='b_upImg_".$rs["vin"]."' style='display:none;' />
				
				<div class='mt-2'>
					<h5 style='font-size: 15px; font-weight: 700; margin-bottom: 2px; text-transform: uppercase;'>".str_replace($value, $rep, $rs["vname"])."</h5>
					<p class='text-muted small mb-2' style='font-size: 12px;'><i class='fa-solid fa-id-card'></i> ID: " . sprintf("%04d", $rs["vin"]) . "</p>
				</div>
				
				<div class='small text-muted space-y-1' style='font-size: 13px;'>
					<div>Sex: <strong>" . ($rs["sex"] == "M" ? "Male" : "Female") . "</strong> &nbsp; Age: <strong>$age y.o.</strong></div>
					<div>Precinct: <strong>".str_replace($value, $rep, $rs["precinct"])."</strong> Cluster: <b>".$hm_cluster."</b></div>
					<div>Address: <strong>".str_replace($value, $rep, $rs["address"]).", ".str_replace($value, $rep, $rs["barangay"])."</strong></div>
				</div>
				
				<hr class='my-2'>
				<div class='small text-muted space-y-1' style='font-size: 13px;'>
					<div>HL Leader: <a style='color:#00940e; font-weight:600;' href='hlinfo.php?hl=".$rsHL["vin"]."'>".$rsHL["vname"]."</a></div>
				</div>
				<div style='margin-top:40px'></div>";
				if(($_SESSION["access"]=="SuperAdmin") or ($_SESSION["access"]=="Admin")){
					echo "
					<div class='d-flex flex-wrap justify-content-center' style='visibility: hidden; transition: var(--transition);' id='div_controls_".$rs["0"]."'>
						<button style='width:50%' type='button' onclick=\"return deletehlc('".$rs["0"]."','".$rs["2"]."');\" class='btn btn-sm btn-danger px-2 py-1' style='font-size:11px;'><i class='fa-solid fa-trash-can'></i> Remove</button>
						<select class='form-select form-select-sm d-inline-block w-auto' style='font-size:11px;' onchange=\"updateStatus(this.value,'".$rs["2"]."','ato')\" >
							<option value='Sure'".($rs["ato"]==="Sure" ? " selected" : "").">Sure</option>
							<option value='Sure-OT'".($rs["ato"]==="Sure-OT" ? " selected" : "").">Sure-OT</option>
							<option value='Undecided'".($rs["ato"]==="Undecided" ? " selected" : "").">Undecided</option>
							<option value='Dili-Ato'".($rs["ato"]==="Dili-Ato" ? " selected" : "").">Dili-Ato</option>
							<option>Add Remarks</option>
						</select>
					</div>";
				}

			echo"</div>";
			$i++;
		}
	?>
</div>

</div>

<script>
	var table=0;
	var hlvotno=0;
								
	function getVoters(value){	
		table="mce";
		var url = "ajax/getvoters.php?value="+encodeURIComponent(value)+"&id="+hlvotno+"&table="+table;
		fetch(url)
			.then(response => response.text())
			.then(data => {
				getID("query_voters").innerHTML = data;
			})
			.catch(err => console.error("Fetch error in getVoters:", err));
	}
				
	function add(){
		getVoters('');
		var addModal = new bootstrap.Modal(document.getElementById('votersModal'));
		addModal.show();
	}
</script>

<script>
	function updateStatus(value,vin,t){	
		if(confirm("Are you sure?")){
			var url = "ajax/updatestatus.php?vin="+vin+"&value="+value+"&target="+t;
			fetch(url)
				.then(response => response.text())
				.then(data => {
					if(value!="Sure"){
						var rem=prompt("Remarks:");
						updateRemarks(vin,rem);
					}
				})
				.catch(err => console.error("Fetch error in updateStatus:", err));
		}
	}
	function updateRemarks(vin,remarks){	
		var url = "ajax/updateremarks.php?vin="+vin+"&remarks="+remarks;
		fetch(url)
			.then(response => response.text())
			.then(data => {
				if(data.trim()==""){
					jump("");
				}else{
					alert(data);
				}
			})
			.catch(err => console.error("Fetch error in updateRemarks:", err));
	}
	
	function deletehlc(hlcno,vin){	
		if(confirm("Are you sure?")){
			var url = "ajax/deletehlmember.php?hlcno="+hlcno+"&vin="+vin;
			fetch(url)
				.then(response => response.text())
				.then(data => {
					if(data.trim() == "Success"){
						$("#div_"+hlcno).animate({
							opacity:0
						},500);
					}else{
						alert(data);
					}
				})
				.catch(err => console.error("Fetch error in deletehlc:", err));
		}
	}
</script>

<?php include 'footer.php';?>