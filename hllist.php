<?php
	require("connect-pdo.php");
	require("head.php");
	require("menu.php");	
	
	$get_barangay = $_GET["barangay"] ?? "";
	if (empty($get_barangay) || $get_barangay === "All barangays") {
		$bar = "ALL BARANGAYS";
	} else {
		$bar = "BARANGAY " . strtoupper($get_barangay);
	}

	$rec=20;
	$p=isset($_GET['page']) ? $_GET['page'] : 1;
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
						
	$filter="";
	if(isset($_GET["barangay"]) && $_GET["barangay"]!="All barangays" && $_GET["barangay"]!="" )
		$filter="v.barangay='".$_GET["barangay"]."' and";
			
	if(isset($_POST["b_search"]) && !empty($_POST["t_search"])){
		$search_query_part = " (v.vname LIKE CONCAT('%', ?, '%') or
			v.remarks LIKE CONCAT('%', ?, '%') or
			v.birth LIKE CONCAT('%', ?, '%') or
			v.sex LIKE CONCAT('%', ?, '%') or
			v.precinct LIKE CONCAT('%', ?, '%') or
			v.address LIKE CONCAT('%', ?, '%') or
			v.city_mun LIKE CONCAT('%', ?, '%')) and ";
		$params = [$_POST["t_search"], $_POST["t_search"], $_POST["t_search"], $_POST["t_search"], $_POST["t_search"], $_POST["t_search"], $_POST["t_search"], $_SESSION["city_mun"]];
	} else {
		$search_query_part = "";
		$params = [$_SESSION["city_mun"]];
	}

	// Paginated query
	$stmt = $link->prepare("select * from voters v, hl h where {$filter} {$search_query_part} h.vin=v.vin and v.city_mun=? order by v.vname limit {$from},{$to}");
	$stmt->execute($params);
	$ex = $stmt;

	// Total count query (without limit) for pagination and print
	$stmt_total = $link->prepare("select * from voters v, hl h where {$filter} {$search_query_part} h.vin=v.vin and v.city_mun=? order by v.vname");
	$stmt_total->execute($params);
	$exz = $stmt_total;

	$value=isset($_POST["t_search"]) ? strtoupper($_POST["t_search"]) : "";
	$rep="<b style='color:#0014d0;background:#ffa0a0'>".$value."</b>";
?>

<script> setActive("hl"); </script>

<div class="container main-content">
	<!-- Search & Controls Card -->
	<form method="post" enctype="multipart/form-data" class="mb-4" id="searchForm">
		<div class="card-glass p-3">
			<div class="row g-3 align-items-center">
				<div class="col-md-3">
					<div class="input-group">
						<span class="input-group-text border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
						<input type="text" name="t_search" id="t_search" class="form-control border-start-0 ps-2" 
							placeholder="Type a keyword..." value="<?php echo isset($_POST["t_search"]) ? htmlspecialchars($_POST["t_search"]) : ''; ?>" />
					</div>
				</div>
				<div class="col-md-5 d-flex gap-2">
					<button type="submit" name="b_search" class="btn btn-danger flex-fill"><i class="fa-solid fa-search"></i> Search</button>
					<button type="submit" class="btn btn-secondary" onclick="document.getElementById('t_search').value=''"><i class="fa-solid fa-rotate"></i></button>
					<button type="button" class="btn btn-success flex-fill" onclick="getID('div_hl').style.display='block';"><i class="fa-solid fa-plus"></i> Add HL</button>
					<button type="button" class="btn btn-dark flex-fill" onclick="jump('hllist_grid.php?barangay=<?php echo urlencode($bar);?>')"><i class="fa-solid fa-list"></i> List View</button>
					<button type="button" class="btn btn-primary" onclick="printF()"><i class="fa-solid fa-print"></i> Print</button>
				</div>
				<div class="col-md-2">
					<select class="form-select" onchange="jump('?barangay='+encodeURIComponent(this.value))">
						<option value="All barangays">All barangays</option>
						<?php
							$stmt = $link->prepare('select barangay from voters where city_mun=? group by barangay order by barangay');
							$stmt->execute([$_SESSION["city_mun"]]);
							$ex_bar = $stmt;
							while($rs=$ex_bar->fetch(PDO::FETCH_BOTH)){
								$sel = (isset($_GET["barangay"]) && $_GET["barangay"] === $rs[0]) ? "selected" : "";
								echo "<option value='".htmlspecialchars($rs[0])."' $sel>".htmlspecialchars($rs[0])."</option>";
							}
						?>
					</select>
				</div>
				<?php
					$i = $from + 1;			
					$total_pages = ceil($exz->rowCount() / $rec);
					if($total_pages < 1) $total_pages = 1;
				?>
				<div class="col-md-2 d-flex gap-2 text-center">
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
		$h_search = isset($_POST["h_search"]) ? $_POST["h_search"] : "";
		$div_hl_style = "display:none;";
		if (isset($_POST["l_search"])) {
			$div_hl_style = "display:block;";
		}

		if (isset($_POST["l_search"]) && $h_search != "") {
			$stmt_hl = $link->prepare("select * from voters v, bce b where (v.vname LIKE CONCAT('%', ?, '%') or v.barangay LIKE CONCAT('%', ?, '%') or v.precinct LIKE CONCAT('%', ?, '%') or v.address LIKE CONCAT('%', ?, '%')) and b.vin=v.vin and v.city_mun=? order by vname");
			$stmt_hl->execute([$h_search, $h_search, $h_search, $h_search, $_SESSION["city_mun"]]);
		} else {
			$stmt_hl = $link->prepare('select * from voters v, bce b where b.vin=v.vin and v.city_mun=? order by vname');
			$stmt_hl->execute([$_SESSION["city_mun"]]);
		}
		$ex_hl = $stmt_hl;

		$h_value = strtoupper($h_search);
		$h_rep = "<b class='text-primary bg-warning'>".$h_value."</b>";
	?>

	<div class="container-fluid" id="div_hl" style="margin-top:-185px;<?php echo $div_hl_style; ?>position:absolute;left:0;z-index:2;width:100%;height:100%;background:url('images/blank_bg.png')no-repeat;background-fill:cover;background-size:100%">
		<form method="post" class="m-0">
			<div class="mx-auto border border-danger p-3" style="border-radius:10px;background:#000;position:relative;max-width:990px; height:600px; top:100px; overflow-y:auto; overflow-x:hidden;">
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
							<b style="margin-left:-10px">SELECT BCE TO BE ASSIGNED</b>
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
						$modal_i=1;

						while($rs=$ex_hl->fetch(PDO::FETCH_BOTH)){
							$birthDate = $rs["birth"];
							$age = "-";
							if (!empty($birthDate) && $birthDate !== '0000-00-00') {
								$birthObj = date_create($birthDate);
								if ($birthObj !== false) {
									$age = date_diff($birthObj, date_create('today'))->y;
								}
							}
							
							$stmt = $link->prepare('select count(*) from hl where bcevin=?');
							$stmt->execute([$rs["vin"]]);
							$ex1 = $stmt;
							$rshl=$ex1->fetch(PDO::FETCH_BOTH);
							$hl=$rshl[0];
							
							$img_src = file_exists("images/voters/".$rs["vin"].".jpg") ? "images/voters/".$rs["vin"].".jpg" : "images/blank.jpg";

							echo "
							<div class='col-md-3 mb-4'>
								<div class='card h-100 shadow-sm' id='div_".$modal_i."' 
									 onmouseout=\"getID('div_controls_".$rs["vin"]."').style.visibility='hidden';getID('div_browse_".$rs["vin"]."').style.visibility='hidden';\" 
									 onmousemove=\"getID('div_controls_".$rs["vin"]."').style.visibility='visible';getID('div_browse_".$rs["vin"]."').style.visibility='visible';\">
								  
								  <div class='card-body text-center' onclick=\"jump('bceinfo.php?bce=".$rs["vin"]."')\" style='cursor:pointer'>
									<div class='position-relative mb-2' style='height:200px; overflow:hidden;'>
									  <img class='img-fluid' style='object-fit:cover;height:100%;width:100%;' src='$img_src?".date("h:i:s")."' />
									</div>
									<div style='text-align:center;padding:5px;font-size:12px'>
										<b class='text-truncate'>".str_ireplace($h_value,$h_rep,$rs["vname"])."</b><br>
										<b class='text-danger mb-1'>Precinct Leader</b><br>
										Sex: <b class='text-danger'>".(($rs["sex"]=="M")?"Male":"Female")."</b> | Age: <b>".$age."</b><br>
										ID No: <b>" . sprintf("%04d", $rs["vin"]) . "</b> | Precinct: <b>" . str_ireplace($h_value,$h_rep,$rs["precinct"]) . "</b><br>
										<span style='color:#ffa86c'>
											Purok: ".str_ireplace($h_value,$h_rep,$rs["address"])."
										</span><br>
										Total HL: <b>".$hl."</b>
									</div>
								  </div>
								</div>
							</div>";
						$modal_i++;
						}
					?>
				</div>
			</div>
		</form>
	</div>

	<div id="grid" style="display:none;width:1000px;margin:0 auto;">
		<table width=100% style='border:1px solid #000;background:#fff;'>
			<tr style='background:#aaa;color:#000;'>
				<th style='padding:5px;'>NO.</th>
				<th>VOTER'S NAME</th>
				<th>SEX</th>
				<th>AGE</th>
				<th>PRECINCT</th>
				<th>PUROK</th>
				<th>BARANGAY</th>
				<th>SIGNATURE</th>
			</tr>
			<?php
				$stmt_total->execute($params);
				$ex_print = $stmt_total;
				$print_i = 1;
				while($rs_print=$ex_print->fetch(PDO::FETCH_BOTH)){
					$birthDate = $rs_print["birth"];
					$age = "-";
					if (!empty($birthDate) && $birthDate !== '0000-00-00') {
						$birthObj = date_create($birthDate);
						if ($birthObj !== false) {
							$age = date_diff($birthObj, date_create('today'))->y;
						}
					}
					echo "
					<tr style='border-bottom:1px solid #000;height:45px;'>
						<td style='padding:5px;'>".$print_i.".</td>
						<td><b>".htmlspecialchars($rs_print["vname"])."</b></td>
						<td>".$rs_print["sex"]."</td>
						<td>".$age."</td>
						<td>".$rs_print["precinct"]."</td>
						<td>".$rs_print["address"]."</td>
						<td>".$rs_print["barangay"]."</td>
						<td></td>
					</tr>";
					$print_i++;
				}
			?>
		</table>
	</div>
<style>
.btn-action:hover{margin-top:40px !important;}
</style>
	<div id="thumbnails" class="tomb-grid" style="margin-top:-5px">
		<?php
			$i = $from + 1;
			while($rs=$ex->fetch(PDO::FETCH_BOTH)){
				$birthDate = $rs["birth"];
				$age = "-";
				if (!empty($birthDate) && $birthDate !== '0000-00-00') {
					$birthObj = date_create($birthDate);
					if ($birthObj !== false) {
						$age = date_diff($birthObj, date_create('today'))->y;
					}
				}

				$stmt = $link->prepare("select cluster from clusters where precinct LIKE CONCAT('%', ?, '%')");
				$stmt->execute([$rs["precinct"]]);
				$rsc = $stmt->fetch(PDO::FETCH_BOTH);
				$cluster_num = $rsc ? $rsc[0] : "-";
				
				$stmt = $link->prepare('select count(*) from hl_children where hlvin=?');
				$stmt->execute([$rs["vin"]]);
				$rsmemb = $stmt->fetch(PDO::FETCH_BOTH);
				$members_count = $rsmemb ? $rsmemb[0] : 0;
				
				if(isset($_POST["b_upImg_".$rs["vin"]])){
					move_uploaded_file($_FILES["b_file_".$rs["vin"]]["tmp_name"], "images/voters/".$rs["vin"].".jpg");
					jump("");
				}

				$img_src = file_exists("images/voters/".$rs["vin"].".jpg") ? "images/voters/".$rs["vin"].".jpg" : "images/blank.jpg";
				
				$stmt = $link->prepare('select * from voters v, hl h where h.vin=? and h.bcevin=v.vin');
				$stmt->execute([$rs["vin"]]);
				$rsbce = $stmt->fetch(PDO::FETCH_BOTH);
				$bce_name = $rsbce ? $rsbce["vname"] : "NOT SET";
				$bce_vin = $rsbce ? $rsbce["bcevin"] : "";
				
				echo "
				<div class='tomb card-glass' id='div_".$rs["vin"]."' 
					onmouseout=\"getID('div_controls_".$rs["vin"]."').style.visibility='hidden';\" 
					onmousemove=\"getID('div_controls_".$rs["vin"]."').style.visibility='visible';\">
					
					<span 
						class='translate-right badge rounded-pill bg-danger shadow' 
						style='position:absolute; top:7px; left:7px; font-size: 16px; 
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
						<div>Precinct: <strong>".str_replace($value, $rep, $rs["precinct"])."</strong> &nbsp; Cluster: <strong>".$cluster_num."</strong></div>
						<div>Address: <strong>".str_replace($value, $rep, $rs["address"]).", ".str_replace($value, $rep, $rs["barangay"])."</strong></div>
					</div>
					
					<hr class='my-2'>
					<div class='small text-muted space-y-1' style='font-size: 13px;'>
						<div>Total HL Members: <strong class='text-primary'>$members_count</strong></div>
						<div>BCE: <a style='color:#00940e; font-weight:600;' href='bceinfo.php?bce=".$bce_vin."'>".str_replace($value, $rep, $bce_name)."</a></div>
					</div>
					<div style='margin-top:40px'></div>";
					if(($_SESSION["access"]=="SuperAdmin") or ($_SESSION["access"]=="Admin")){
						echo "
						<div class='btn-action d-flex gap-2 flex-wrap justify-content-center' style='visibility: hidden; transition: var(--transition);' id='div_controls_".$rs["vin"]."'>
							<button style='font-size:11px;' type='button' onclick=\"deletehl('".$rs["vin"]."',".$i.")\" class='btn btn-sm btn-danger px-2 py-1'><i class='fa-solid fa-trash-can'></i> Remove</button>
							<button  style='font-size:11px;' type='button' onclick=\"jump('hlinfo.php?hl=".$rs["vin"]."')\" class='btn btn-sm btn-success px-2 py-1'><i class='fa-solid fa-eye'></i> Members</button>
							<a style='font-size:11px;' href='hl-form-indiv.php?hl=".$rs["vin"]."&barangay=".$rs["barangay"]."' target='_blank' class='btn btn-sm btn-secondary px-2 py-1'><i class='fa-solid fa-print'></i> Print</a>
						</div>";
					}
				echo "
				</div>";
				$i++;
			}
		?>
	</div>


</div>

<script>
	var table=0;
	var hlvotno=0;

	function printF(){
		$('#header').show();
		$('#spacer').hide();
		$('.hid').hide();																						
		$('#searchForm').hide(); 
		$('#thumbnails').hide(); 
		$('#grid').show(); 
	
		window.print(); 																					
		$('#grid').hide(); 
		$('#thumbnails').show(); 
		$('#searchForm').show();
		$('#header').hide();
		$('#spacer').show();
		$('.hid').show();
	}

	function deletehl(hlno,id){	
		if(confirm("Are you Sure?")){
			var url = "ajax/deletehl.php?vin="+hlno;
			fetch(url)
				.then(response => response.text())
				.then(data => {
					if(data.trim() == "Success"){
						$("#div_"+hlno).fadeOut(500);
					}else{
						alert(data);
					}
				})
			.catch(err => console.error("Fetch error in deletehl:", err));
		}
	}
</script>

<?php include 'footer.php';?>

