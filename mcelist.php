<?php
	require("connect-pdo.php");
	require("head.php");
	
	$bar_ = "";
	if(isset($_GET["barangay"]) && $_GET["barangay"] != "All barangays" && $_GET["barangay"] != "") {
		$bar_ = " and v.barangay='" . $_GET["barangay"] . "'";
	}

	require("menu.php"); 
	require("popvoters.php");
?>

<script> setActive("mce"); </script>

<div class="container main-content">
	<!-- Search & Controls Card -->
	<form method="post" enctype="multipart/form-data" class="mb-4">
		<div class="card-glass p-3">
			<div class="row g-3 align-items-center">
				<div class="col-md-4">
					<div class="input-group">
						<span class="input-group-text border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
						<input type="text" name="t_search" id="t_search" class="form-control border-start-0 ps-2" 
							placeholder="Type a keyword..." value="<?php echo isset($_POST["t_search"]) ? htmlspecialchars($_POST["t_search"]) : ''; ?>" />
					</div>
				</div>
				<div class="col-md-4 d-flex gap-2">
					<button type="submit" name="b_search" class="btn btn-danger flex-fill"><i class="fa-solid fa-search"></i> Search</button>
					<a class="btn btn-secondary pt-2" href="mcelist.php"><i class="fa-solid fa-rotate"></i></a>
					<button type='button' type="button" class="btn btn-success flex-fill" onclick="add();"><i class="fa-solid fa-plus"></i> Add MCE</button>
				</div>
				<div class="col-md-4">
					<select class="form-select" onchange="jump('?barangay='+urlencode(this.value))">
						<option value="All barangays">All barangays</option>
						<?php
							$stmt = $link->prepare('select barangay from voters where city_mun=? group by barangay order by barangay');
							$stmt->execute([$_SESSION["city_mun"]]);
							$ex = $stmt;
							while($rs=$ex->fetch(PDO::FETCH_BOTH)){
								$sel = (isset($_GET["barangay"]) && $_GET["barangay"] === $rs[0]) ? "selected" : "";
								echo "<option value='".htmlspecialchars($rs[0])."' $sel>".htmlspecialchars($rs[0])."</option>";
							}
						?>
					</select>
				</div>
			</div>
		</div>
	</form>

	<!-- Print Grid (Hidden by default) -->
	<div id="grid" class="d-none">
		<div class="text-center mb-4">
			<h3>MUNICIPAL COUNCIL OF ELDERS (MCE) </h3>
			<h5><?php echo htmlspecialchars($_SESSION["city_mun"]); ?></h5>
			<?php if(isset($_GET["barangay"]) && $_GET["barangay"] != "All barangays" && $_GET["barangay"] != ""): ?>
				<h6><?php echo htmlspecialchars($_GET["barangay"]); ?></h6>
			<?php endif; ?>
		</div>
		
		<table class="table table-bordered">
			<thead>
				<tr>
					<th>NO.</th>
					<th>NAME OF MCE</th>
					<th>SEX</th>
					<th>BIRTHDATE</th>
					<th>AGE</th>
					<th>PRECINCT</th>
					<th>PUROK</th>
					<th>BARANGAY</th>
				</tr>
			</thead>
			<tbody>
				<?php
					$stmt = $link->prepare("select * from mce m, voters v where m.vin=v.vin {$bar_} and v.city_mun=? order by vname");
					$stmt->execute([$_SESSION["city_mun"]]);
					$ex = $stmt;
					$i=1;
					while($rs3=$ex->fetch(PDO::FETCH_BOTH)){
						$birthDate = $rs3["birth"];
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
						echo "<tr>
							<td>".$i.".</td>
							<td>".$rs3["vname"]."</td>
							<td>".$rs3["sex"]."</td>
							<td>".$rs3["birth"]."</td>
							<td>".$age."</td>
							<td>".$rs3["precinct"]."</td>
							<td>".$rs3["address"]."</td>
							<td>".$rs3["barangay"]."</td>
						</tr>";
						$i++;
					}
				?>
			</tbody>
		</table>
	</div>

	<!-- Cards Grid -->
	<form method="post" enctype="multipart/form-data" class="m-0">
		<div id="thumbnails" class="tomb-grid">
			<?php
				if(isset($_POST["b_search"]) && !empty($_POST["t_search"])){
					$stmt = $link->prepare("select * from mce m, voters v where 
					   (v.vname LIKE CONCAT('%', ?, '%') or
						v.remarks LIKE CONCAT('%', ?, '%') or
						v.birth LIKE CONCAT('%', ?, '%') or
						v.sex LIKE CONCAT('%', ?, '%') or
						v.precinct LIKE CONCAT('%', ?, '%') or
						v.address LIKE CONCAT('%', ?, '%') or
						v.city_mun LIKE CONCAT('%', ?, '%')) and m.vin=v.vin {$bar_} and v.city_mun=?
					order by vname");
					$stmt->execute([$_POST["t_search"], $_POST["t_search"], $_POST["t_search"], $_POST["t_search"], $_POST["t_search"], $_POST["t_search"], $_POST["t_search"], $_SESSION["city_mun"]]);
					$ex = $stmt;
				} else {
					$stmt = $link->prepare("select * from voters v, mce m where m.vin=v.vin {$bar_} and v.city_mun=? order by vname");
					$stmt->execute([$_SESSION["city_mun"]]);
					$ex = $stmt;
				}
				
				$i=1;
				$value = isset($_POST["t_search"]) ? strtoupper($_POST["t_search"]) : "";
				$rep = "<b style='color:#0014d0;background:#ffa0a0'>".$value."</b>";
				
				while($rs=$ex->fetch(PDO::FETCH_BOTH)){
					
					$stmt = $link->prepare('select count(*) from bce where mcevin=?');
					$stmt->execute([$rs["vin"]]);
					$rsbce=$stmt->fetch(PDO::FETCH_BOTH);
					$bce = $rsbce ? $rsbce[0] : 0;
					
					if(isset($_POST["b_remove_".$rs["0"]])){
						$stmt = $link->prepare('delete from mce where vin=?');
						$stmt->execute([$rs["0"]]);
						jump("");
					}
					if(isset($_POST["b_upImg_".$rs["0"]])){
						move_uploaded_file($_FILES["b_file_".$rs["0"]]["tmp_name"], "images/voters/".$rs[0].".jpg");
						$stmt = $link->prepare('update voters set ispicset=1 where vin=?');
						$stmt->execute([$rs["0"]]);
						jump("");
					}

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
					
					$gender="";
					if($rs["sex"]=="M") {
						$gender="Male";
					}else{
						$gender="Female";
					}						

					$img_src = file_exists("images/voters/".$rs[0].".jpg") ? "images/voters/".$rs[0].".jpg" : "images/blank.jpg";
					
					echo "
					<div class='tomb card-glass' id='div_".$rs["vin"]."' 
						onmouseout=\"getID('div_controls_".$rs["vin"]."').style.visibility='hidden';\" 
						onmousemove=\"getID('div_controls_".$rs["vin"]."').style.visibility='visible';\">
						
						<span 
							class='translate-right badge rounded-pill bg-danger shadow' 
							style='position:absolute; top:7px; left:7px; font-size: 16px; 
							border: 2px solid #fff; padding: 6px 10px; z-index:3'>$i
						</span>
						
						<div class='tomb-img-container' onclick=\"$('#b_file_".$rs["0"]."').click();\">
							<img src='$img_src?".date("h:i:s")."' />
							<div class='tomb-img-overlay'>
								<i class='fa-solid fa-camera'></i>
								<span>Change Photo</span>
							</div>
						</div>
						<input type='file' name='b_file_".$rs["0"]."' id='b_file_".$rs["0"]."' style='display:none;' onchange=\"if(this.value!='') $('#b_upImg_".$rs["0"]."').click();\" />
						<input type='submit' name='b_upImg_".$rs["0"]."' id='b_upImg_".$rs["0"]."' style='display:none;' />
						
						<div class='mt-2'>
							<h5 style='font-size: 15px; font-weight: 700; margin-bottom: 2px; text-transform: uppercase;'>".str_ireplace($value, $rep, $rs["vname"])."</h5>
							<p class='text-muted mb-2 text-bold'><i class='fa-solid fa-id-card'></i> 
								ID: " . sprintf("%04d", $rs["vin"]) . " &bull;
								Precinct: ".str_ireplace($value, $rep, $rs["precinct"])."
							</p>
							<p class='text-muted small mb-2' style='font-size: 12px;'>Birth: " . $rs["birth"] . " &bull; Age: $age yo &bull; Sex: " . $gender . "</p>
						</div>
						
						<div class='small text-muted space-y-1' style='font-size: 13px;'>
							<div>Barangay: <strong>".str_ireplace($value, $rep, $rs["barangay"])."</strong></div>
						</div>
						
						<hr class='my-2'>
						<div style='font-size: 13px; font-weight: 600;' class='text-primary mb-2'>
							<i class='fa-solid fa-users'></i> Total Members: $bce
						</div>";
						
						// Render BCE Members list inside the card as small avatar list
						$stmt = $link->prepare('select * from bce b, voters v where mcevin=? and b.vin=v.vin');
						$stmt->execute([$rs["vin"]]);
						$exbce = $stmt;
						if($exbce->rowCount() > 0){
							echo "<div class='d-flex flex-column gap-1 bg-white p-2 rounded border' style='max-height: 120px; overflow-y: auto;'>";
							while($rsbce=$exbce->fetch(PDO::FETCH_BOTH)){
								$avatar = file_exists("images/voters/".$rsbce["vin"].".jpg") ? "images/voters/".$rsbce["vin"].".jpg" : "images/blank.jpg";
								echo "
								<div class='d-flex align-items-center gap-2' onclick=\"jump('bceinfo.php?bce=".$rsbce["vin"]."')\" style='cursor:pointer;'>
									<img src='$avatar' style='width:24px; height:24px; border-radius:50%; object-fit:cover;' />
									<span style='font-size:11px;' class='text-dark text-truncate'>".str_ireplace($value, $rep, $rsbce["vname"])."</span>
								</div>";
							}
							echo "</div><br><br>";
						}
						if(($_SESSION["access"]=="SuperAdmin") or ($_SESSION["access"]=="Admin")){
						echo"	
						<div class='text-center mt-3 d-flex gap-2' style='visibility: hidden; transition: var(--transition);' id='div_controls_".$rs["vin"]."'>
							<button style='width:50%' type='button' onclick=\"deletemce('".$rs["vin"]."')\" class='btn btn-sm btn-danger px-3'><i class='fa-solid fa-trash-can'></i> Remove</button>
							<button style='width:50%' type='button' onclick=\"jump('mceinfo.php?mce=".$rs["vin"]."')\" type='button' class='btn btn-sm btn-warning px-3'><i class='fa-solid fa-eye'></i> View BCE</button>
						</div>";
						}
					echo"</div>";					
				$i++;
				}
			?>
		</div>
	</form>
</div>

<script>
	function addmce(id,row){
		table="mce";
		var vin=id;
		var url = "ajax/addmce.php?table="+table+"&vin="+vin;
		fetch(url)
			.then(response => response.text())
			.then(data => {
				if(data.trim() == "Success"){
					$("#q_tr_"+row).animate({
						opacity:0
					},500,function(){
						$("#q_tr_"+row).css("display","none");
					});
				}else{
					Swal.fire('Error', data, 'error');
				}
			})
		.catch(err => console.error("Fetch error in addmce:", err));
	}
			
	function deletemce(vin){	
		Swal.fire({
			title: 'Are you sure?',
			text: "You won't be able to revert this!",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#dc3545',
			cancelButtonColor: '#6c757d',
			confirmButtonText: 'Yes, remove it!'
		}).then((result) => {
			if (result.isConfirmed) {
				var url = "ajax/deletemce.php?vin="+vin;
				fetch(url)
					.then(response => response.text())
					.then(data => {
						if(data.trim() == "Success"){
							$("#div_"+vin).animate({
								opacity:0
							},500,function(){
								$(this).hide();
							});
							Swal.fire('Removed!', 'Record has been removed.', 'success');
						} else {
							Swal.fire('Error', data, 'error');
						}
					})
				.catch(err => console.error("Fetch error in deletemce:", err));
			}
		});
	}
	
	function urlencode(str) {
		return encodeURIComponent(str).replace(/[!'()*]/g, function(c) {
			return '%' + c.charCodeAt(0).toString(16);
		});
	}
</script>

<?php include 'footer.php';?>