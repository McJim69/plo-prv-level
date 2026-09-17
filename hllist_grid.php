<?php
	require("connect-pdo.php");
	require("head.php");
	
	$reg1="regular/sk voter";
	$skv1="Regular/SK";	
	$stmt = $link->prepare('UPDATE hl_children SET remarks = REPLACE (remarks,?,?)');
	$stmt->execute([$reg1, $skv1]);

	$reg2="REGULAR/SK VOTER";
	$stmt = $link->prepare('UPDATE hl_children SET remarks = REPLACE (remarks,?,?)');
	$stmt->execute([$reg2, $skv1]);

	$sko1="sk voter only";
	$sko2="SK Only";
	$stmt = $link->prepare('UPDATE hl_children SET remarks = REPLACE (remarks,?,?)');
	$stmt->execute([$sko1, $sko2]);

	$reg3="r3gular/sk voter";
	$stmt = $link->prepare('UPDATE hl_children SET remarks = REPLACE (remarks,?,?)');
	$stmt->execute([$reg3, $skv1]);

	$snc1="senior citizen";
	$sen1="Senior";	
	$stmt = $link->prepare('UPDATE hl_children SET remarks = REPLACE (remarks,?,?)');
	$stmt->execute([$snc1, $sen1]);

	$snc2="SENIOR CITIZEN";
	$stmt = $link->prepare('UPDATE hl_children SET remarks = REPLACE (remarks,?,?)');
	$stmt->execute([$snc2, $sen1]);

	$snc3="senior citezen";
	$stmt = $link->prepare('UPDATE hl_children SET remarks = REPLACE (remarks,?,?)');
	$stmt->execute([$snc3, $sen1]);
	
	$snc4="IO-SENIOR CITIZEN";
	$sen4="IO-Senior";	
	$stmt = $link->prepare('UPDATE hl_children SET remarks = REPLACE (remarks,?,?)');
	$stmt->execute([$snc4, $sen4]);

	$inf1="information officer";
	$inf2="Info Officer";	
	$stmt = $link->prepare('UPDATE hl_children SET remarks = REPLACE (remarks,?,?)');
	$stmt->execute([$inf1, $inf2]);

	$inf3="INFORMATION OFFICER";
	$stmt = $link->prepare('UPDATE hl_children SET remarks = REPLACE (remarks,?,?)');
	$stmt->execute([$inf3, $inf2]);

	$ot1="ot-negative";
	$ot2="OT-Neg";	
	$stmt = $link->prepare('UPDATE hl_children SET remarks = REPLACE (remarks,?,?)');
	$stmt->execute([$ot1, $ot2]);

	$value = isset($_POST["t_search"]) ? strtoupper($_POST["t_search"]) : "";
	$rep = "<b style='color:#0014d0;background:#ffa0a0'>".$value."</b>";
	
	$bar = isset($_GET["barangay"]) ? "BARANGAY " . $_GET["barangay"] : "";
	if($bar == "BARANGAY " || $bar == "")
		$bar = "ALL BARANGAYS";

	require("menu.php");
?>

<script> setActive("household"); </script>
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
							placeholder="Type a keyword..." value="<?php echo (isset($_POST["t_search"]) && $_POST["t_search"]!="") ? htmlspecialchars($_POST["t_search"]) : ''; ?>" />
					</div>
				</div>
				<div class="col-md-6 d-flex gap-2">
					<button type="submit" name="b_search" class="btn btn-danger flex-fill"><i class="fa-solid fa-search"></i> Search</button>
					<button type="button" class="btn btn-warning flex-fill" onclick="$('#grid table').toggleClass('hide-members');"><i class="fa-solid fa-eye-slash"></i> Hide/Show Members</button>
					<button type="button" class="btn btn-success flex-fill" onclick="jump('hllist.php?barangay=<?php echo isset($_GET["barangay"]) ? urlencode($_GET["barangay"]) : '';?>')"><i class="fa-solid fa-table-cells"></i> Grid View</button>
					<button type="button" class="btn btn-dark" onclick="printF()"><i class="fa-solid fa-print"></i> Print</button>
				</div>
				<div class="col-md-3">
					<select class="form-select" onchange="jump('?barangay='+encodeURIComponent(this.value))">
						<option value="All barangays">All barangays</option>
						<?php
							$stmt = $link->prepare('select barangay from voters where city_mun=? group by barangay order by barangay');
							$stmt->execute([$_SESSION["city_mun"]]);
							$ex2 = $stmt;
							while($rs2=$ex2->fetch(PDO::FETCH_BOTH)){
								$sel = (isset($_GET["barangay"]) && $_GET["barangay"]===$rs2[0]) ? "selected" : "";
								echo "<option value='".htmlspecialchars($rs2[0])."' $sel>".htmlspecialchars($rs2[0])."</option>";
							}
						?>
					</select>
				</div>
			</div>
		</div>
	</form>	

	<div style="padding:3px;" ></div>

	<div id="grid" class="card-glass p-4 overflow-hidden">
		<!-- Print-Only Header -->
		<DIV style="text-align:center;background:#fff;display:none" id='header' class="text-dark mb-4">
			<h4 class="fw-bold">LIST OF HOUSEHOLD LEADERS</h4>
			<h5 class="fw-bold">MUNICIPALITY OF <?PHP echo htmlspecialchars($_SESSION["city_mun"]); ?></h5>
			<?php
				if(isset($_GET["barangay"]) && $_GET["barangay"]!="All barangays" && $_GET["barangay"]!="")
					echo "<h6 class='fw-bold'>BARANGAY ".htmlspecialchars($_GET["barangay"])."</h6>";
			?>
			<hr>
		</div>

		<div class="table-responsive">
			<table class="table align-middle table-bordered mb-0">
				<tbody>
					<?php
						$rec=2000;
						$p=isset($_GET['page']) ? intval($_GET['page']) : 1;
						if($p < 1) $p = 1;
						$from=($p-1)*$rec;
							
						$filter="";
						if(isset($_GET["barangay"]) && $_GET["barangay"]!="All barangays" && $_GET["barangay"]!="" )
							$filter="v.barangay='".$_GET["barangay"]."' and";

						$search_term = isset($_POST["t_search"]) ? $_POST["t_search"] : "";
						
						$stmt = $link->prepare("select * from voters v, hl h where {$filter} 
						   (v.vname LIKE CONCAT('%', ?, '%') or
							v.remarks LIKE CONCAT('%', ?, '%') or
							v.birth LIKE CONCAT('%', ?, '%') or
							v.sex LIKE CONCAT('%', ?, '%') or
							v.precinct LIKE CONCAT('%', ?, '%') or
							v.address LIKE CONCAT('%', ?, '%') or
							v.city_mun LIKE CONCAT('%', ?, '%')) and h.vin=v.vin and v.city_mun=? order by vname ");
						$stmt->execute([$search_term, $search_term, $search_term, $search_term, $search_term, $search_term, $search_term, $_SESSION["city_mun"]]);
						$exz = $stmt;

						if(isset($_POST["b_search"])){
							$stmt = $link->prepare("select * from voters v, hl h where  {$filter} 
							   (v.vname LIKE CONCAT('%', ?, '%') or
								v.remarks LIKE CONCAT('%', ?, '%') or
								v.birth LIKE CONCAT('%', ?, '%') or
								v.sex LIKE CONCAT('%', ?, '%') or
								v.precinct LIKE CONCAT('%', ?, '%') or
								v.address LIKE CONCAT('%', ?, '%') or
								v.city_mun LIKE CONCAT('%', ?, '%')) and h.vin=v.vin and v.city_mun=? order by vname limit {$from},{$rec} ");
							$stmt->execute([$search_term, $search_term, $search_term, $search_term, $search_term, $search_term, $search_term, $_SESSION["city_mun"]]);
							$ex = $stmt;
						} else {
							$stmt = $link->prepare("select * from voters v, hl h where {$filter} h.vin=v.vin and v.city_mun=? order by vname limit {$from},{$rec} ");
							$stmt->execute([$_SESSION["city_mun"]]);
							$ex = $stmt;
						}
						
						$i = 1;
						$totHL = 0;
						while($rs=$ex->fetch(PDO::FETCH_BOTH)){

							$stmt = $link->prepare('select * from voters v where v.vin=?');
							$stmt->execute([$rs["bcevin"]]);
							$eee = $stmt;
							$rsmce=$eee->fetch(PDO::FETCH_BOTH);
							$bce_name = $rsmce ? $rsmce["vname"] : "-";
							$bce_vin  = $rsmce ? $rsmce["vin"] : "-";
						
							$stmt = $link->prepare('select count(*) from hl_children where hlvin=? ');
							$stmt->execute([$rs["vin"]]);
							$exx = $stmt;
							$rsbce=$exx->fetch(PDO::FETCH_BOTH);
							$hlm=$rsbce[0];
							$totHL+=$hlm;

							$stmt = $link->prepare("select cluster from clusters where precinct LIKE CONCAT('%', ?, '%')");
							$stmt->execute([$rs["precinct"]]);
							$cluster = $stmt;
							$rsc=$cluster->fetch(PDO::FETCH_BOTH);
							$hl_cluster = $rsc ? $rsc[0] : "-";	
								
							echo"
								<tr class='table-dark align-middle'>
									<td colspan='2' class='fw-bold px-3 py-2'><a style='font-size:16px;text-decoration:none;' href='hlinfo.php?hl=".$rs["vin"]."'> ".$i.". ".htmlspecialchars($rs["vname"])."</a></td>
									<td class='fw-bold text-center'>".htmlspecialchars($hl_cluster)."</td>
									<td class='fw-bold text-center'>".htmlspecialchars($rs["precinct"])."</td>
									<td class='fw-bold text-center'>(".$hlm.") Members</td>
									<td colspan='3' class='fw-bold px-3 py-2'><a style='font-size:16px;text-decoration:none;' href='bceinfo.php?bce=".$bce_vin."'>BCE: ".htmlspecialchars($bce_name)."</a></td>
								</tr>
								
								<tr class='hlmember'>
									<td colspan='8' class='p-0'> 
										<table class='table table-bordered mb-0 align-middle table-sm' style='font-size:13px;'>
											<thead>
												<tr class='table-secondary'>
													<th colspan='2' class='ps-3'>NAME OF HOUSEHOLD MEMBERS</th>
													<th class='text-center'>SEX</th>
													<th class='text-center'>AGE</th>
													<th class='text-center'>PRECINCT</th>								
													<th class='text-center'>CLUSTER</th>
													<th class='text-center'>ATO?</th>								
													<th class='text-center'>REMARKS</th>
												</tr>
											</thead>
											<tbody>";
										
									$stmt = $link->prepare('select * from hl_children hl, voters v where hl.hlvin=? and hl.vin=v.vin ');
									$stmt->execute([$rs["vin"]]);
									$exhlc = $stmt;
									
									$xx=1;

									while($rshlc=$exhlc->fetch(PDO::FETCH_BOTH)){

										$stmt = $link->prepare("select cluster from clusters where precinct LIKE CONCAT('%', ?, '%')");
										$stmt->execute([$rshlc["precinct"]]);
										$cluster2 = $stmt;
										$rsc2=$cluster2->fetch(PDO::FETCH_BOTH);
										$hm_cluster = $rsc2 ? $rsc2[0] : "-";

										$color = "";
										if($hl_cluster != $hm_cluster) 
											$color = "color:red";

										$birthDate = $rshlc["birth"];
										$age = "-";
										if (!empty($birthDate) && $birthDate !== '0000-00-00') {
											$birthObj = date_create($birthDate);
											if ($birthObj !== false) {
												$age = date_diff($birthObj, date_create('today'))->y;
											}
										}

										$row_style = "";
										if($rshlc["ato"]==="Undecided")
											$row_style = "color:red; font-weight:600;";
										else if($rshlc["ato"]==="Sure-OT")
											$row_style = "color:green; font-weight:600;";

										echo "
											<tr style='background:#f8f8f8; $row_style'>
												<td colspan='2' class='ps-3'>".$xx.". ".htmlspecialchars($rshlc["vname"])."</td>
												<td class='text-center'>".htmlspecialchars($rshlc["sex"])."</td>
												<td class='text-center'>".$age."</td>
												<td class='text-center'>".htmlspecialchars($rshlc["precinct"])."</td>
												<td class='text-center' style='".$color."'>".htmlspecialchars($hm_cluster)."</td>
												<td class='text-center'>".htmlspecialchars($rshlc["ato"])."</td>
												<td>".htmlspecialchars($rshlc[3])."</td>
											</tr> ";
										$xx++;
									}
											
									echo"
											</tbody>
										</table> 
									</td>
								</tr>";
							$i++;
						}
							
						$gtot = ($i - 1) + $totHL;
					?>
				</tbody>
			</table>
		</div>

		<div class="mt-4 p-3 bg-dark text-white rounded d-flex justify-content-around flex-wrap gap-2 text-center fs-5 fw-bold d-print-none">
			<div>Household Leaders (HL): <span class="text-danger"><?php echo ($i-1); ?></span></div>
			<div>Members: <span class="text-warning"><?php echo $totHL; ?></span></div>
			<div>Total: <span class="text-success"><?php echo $gtot; ?></span></div>
		</div>
	</div>
</div>

<script>
	function printF(){
		window.print(); 
	}
</script>

</body>

</html>