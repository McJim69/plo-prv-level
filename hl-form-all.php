<?php
	require("connect-pdo.php");
	require("head.php");

	// Safe parameter handling
	$t_search = isset($_POST["t_search"]) ? $_POST["t_search"] : "";
	$value = strtoupper($t_search);
	$rep = "<b style='color:#0014d0;background:#ffa0a0'>" . htmlspecialchars($value) . "</b>";
	
	$barangay = isset($_GET["barangay"]) ? $_GET["barangay"] : "";
	$hl_val = isset($_GET["hl"]) ? $_GET["hl"] : "";
	$ato = isset($_GET["ato"]) ? $_GET["ato"] : "";
	$p = isset($_GET["page"]) ? intval($_GET["page"]) : 1;

	$bar = "BARANGAY " . $barangay;
	if (empty($barangay) || $barangay === "All barangays") {
		$bar = "ALL BARANGAYS";
	}
		
	$hl_clause = "";
	if (!empty($hl_val)) {
		$hl_clause = " and v.vin='" . $hl_val . "' ";	
	}
	
	$filter = "";
	if (!empty($barangay) && $barangay !== "All barangays") {
		$filter = " and v.barangay='" . $barangay . "'";
	}
		
	if (isset($_POST["b_search"]) && !empty($t_search)) {
		$stmt = $link->prepare("select * from voters v, hl h where v.vname LIKE CONCAT('%', ?, '%') and h.vin=v.vin {$hl_clause} {$filter} order by v.vname");
		$stmt->execute([$t_search]);
		$ex1 = $stmt;
		
		$stmt = $link->prepare("select * from voters v, hl h where v.vname LIKE CONCAT('%', ?, '%') and h.vin=v.vin {$hl_clause} {$filter} order by v.vname");
		$stmt->execute([$t_search]);
		$ex2 = $stmt;
	} else {
		$stmt = $link->prepare("select * from voters v, hl h where h.vin=v.vin {$hl_clause} {$filter} order by v.vname");
		$stmt->execute([]);
		$ex1 = $stmt;
		
		$stmt = $link->prepare("select * from voters v, hl h where h.vin=v.vin {$hl_clause} {$filter} order by v.vname");
		$stmt->execute([]);
		$ex2 = $stmt;
	}
	
	require("menu.php");	
?>

<!-- Print-specific overrides to guarantee clean paper margins and hide screen UI -->
<style>
	@media print {
		body {
			background-color: #fff !important;
			color: #000 !important;
		}
		.d-print-none {
			display: none !important;
		}
		.print-container {
			margin: 0 !important;
			padding: 0 !important;
			width: 100% !important;
			box-shadow: none !important;
			background: transparent !important;
		}
		.info, .info th, .info td {
			border: 1px solid #000 !important;
			border-collapse: collapse !important;
			color: #000 !important;
		}
	}
</style>

<script>
	setActive("sum");
	setActive("sumform");

	function printF() {
		window.print();
	}
</script>

<div style="margin-top:-20px"></div>
<form method="post" enctype="multipart/form-data">
	<div class="container d-print-none">
	<!-- Search & Filters Card -->
		<div class="card-glass p-3">
			<div class="row g-3 align-items-center justify-content-between">
				<div class="col-md-4">
					<div class="input-group">
						<span class="input-group-text border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
						<input type="text" name="t_search" class="form-control border-start-0 ps-2" 
							placeholder="Search for HL to Print..." value="<?php echo htmlspecialchars($t_search); ?>" />
					</div>
				</div>
				<div class="col-md-3 d-flex gap-2">
					<button type="submit" name="b_search" class="btn btn-danger flex-fill fw-bold"><i class="fa-solid fa-search"></i> Search</button>
					<button type="button" class="btn btn-dark flex-fill fw-bold" onclick="printF()"><i class="fa-solid fa-print"></i> Print</button>
				</div>
				<div class="col-md-2">
					<select class="form-select" onchange="jump('?barangay='+encodeURIComponent(this.value))">
						<!--<option value="All barangays">All barangays</option>-->
						<?php
							$ex = $link->query("select barangay from voters group by barangay order by barangay");
							while($rs = $ex->fetch(PDO::FETCH_BOTH)){
								$sel = ($barangay === $rs[0]) ? "selected" : "";
								echo "<option value='".htmlspecialchars($rs[0])."' $sel>".htmlspecialchars($rs[0])."</option>";
							}
						?>
					</select>
				</div>				
				<div class="col-md-3 d-flex align-items-center gap-1 justify-content-end">
					<a href="index.php" class="btn btn-outline-danger">
						<i class="fa fa-home"></i> Back to Home
					</a>
				</div>
			</div>
		</div>
	</div>
</form>

<!-- Receipt Presentation Container -->
<div class="main-content container print-container" style="max-width: 1000px; background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-md);">
	<?php
		while($rs = $ex2->fetch(PDO::FETCH_BOTH)){
			$precinct = $rs["precinct"];
			$name = $rs["vname"];
			$sex = $rs["sex"];
			
			$stmt = $link->prepare('select * from voters v where v.vin=?');
			$stmt->execute([$rs["bcevin"]]);
			$ppp = $stmt;
			$rsbce = $ppp->fetch(PDO::FETCH_BOTH);
			$bce_name = $rsbce ? $rsbce["vname"] : "";

			$stmt = $link->prepare('select b.mcevin, v.vname from bce b, voters v where b.vin = v.vin and b.vin = ?');
			$stmt->execute([$rs["bcevin"]]);
			$rsbce = $stmt->fetch(PDO::FETCH_BOTH);
			
			$bce_name = $rsbce ? $rsbce["vname"] : "";
			$mcevin = $rsbce ? $rsbce["mcevin"] : null; // Track down the mcevin from this BCE record

			// 2. Get the MCE name using the mcevin we just found above
			$mce_name = "";
			if ($mcevin) {
				$stmt = $link->prepare('select v.vname from voters v where v.vin = ?');
				$stmt->execute([$mcevin]);
				$rsmce = $stmt->fetch(PDO::FETCH_BOTH);
				$mce_name = $rsmce ? $rsmce["vname"] : "";
			}
		?>
		<div id="div_<?php echo htmlspecialchars($rs["vin"]); ?>" class="p-3">
			<!-- Campaign Header Logo -->
			<div class="text-center mb-4">
				<img src="images/logo.png" style="height: 70px;" alt="Campaign Banner" />
			</div>
			
			<!-- Receipt Identification Header -->
			<div class="text-center mb-4">
				<h3 class="fw-bold mb-1" style="font-size: 26px; letter-spacing: 1px;">ACKNOWLEDGEMENT RECEIPT</h3>
				<h5 class="fw-bold mb-0 text-uppercase" style="font-size: 19px;">BARANGAY <?php echo htmlspecialchars($rs["barangay"]); ?></h5>
				<span class="text-muted fw-bold text-uppercase" style="font-size: 14px;">MUNICIPALITY OF <?php echo htmlspecialchars($_SESSION["city_mun"]); ?></span>
			</div>

			<div class="row align-items-center justify-content-between mb-3 g-2">
				<div class="col-auto">
					<span class="badge bg-danger text-white px-3 py-2 fs-6 fw-bold">HL ID: <?php printf("%04d", $rs["vin"]); ?></span>
				</div>
				<div class="col-auto text-end">
					<span class="badge bg-danger text-white px-3 py-2 fs-6 fw-bold">First Release</span>
				</div>
			</div>
			
			<?php
				$birthDate = $rs["birth"];
				$age = "-";
				if (!empty($birthDate) && $birthDate !== '0000-00-00') {
					$birthObj = date_create($birthDate);
					if ($birthObj !== false) {
						$age = date_diff($birthObj, date_create('today'))->y;
					}
				}

				$stmt = $link->prepare("SELECT cluster FROM clusters WHERE precinct LIKE CONCAT('%', ?, '%')");
				$stmt->execute([$precinct]);
				$cluster = $stmt;
				$rsc = $cluster->fetch(PDO::FETCH_BOTH);
				$hl_cluster = $rsc ? $rsc[0] : "";
			?>
			
			<div style="min-height: 900px;border:1px solid #000;border-radius:10px">
				<!-- Acknowledgement Details Table -->
				<table class="table table-bordered align-middle info mb-0" id="info">
					<thead class="table-light">
						<tr class="align-middle text-center fw-bold" style="background:#bbb; font-weight:bold;">
							<th style="padding: 22px 5px !important">#</th>
							<th>PHOTO</th>
							<th class="text-start ps-3">NAME OF VOTERS</th>
							<th>SEX</th>
							<th>AGE</th>
							<th>PREC</th>
							<th>CLUS</th>
							<th style="width: 200px;">SIGNATURE</th>
						</tr>
					</thead>
					<tbody>
						<!-- Household Leader Row -->
						<tr class="fw-bold align-middle text-center">
							<td>HL</td>
							<td style="padding:5px !important">
								<?php if (file_exists("images/voters/" . $rs["vin"] . ".jpg")): ?>
									<img src="images/voters/<?php echo $rs["vin"]; ?>.jpg" style="width: 50px; aspect-ratio:2/2;" class="rounded border" />
								<?php else: ?>
									<img src="images/blank.jpg" height="50" width="50" class="rounded border" />
								<?php endif; ?>
							</td>
							<td class="text-start ps-3">
								<?php 
									if (!empty($t_search)) {
										echo str_replace($value, $rep, $name);
									} else {
										echo htmlspecialchars($name);
									}
								?>
							</td>
							<td><?php echo htmlspecialchars($sex); ?></td>
							<td><?php echo $age; ?></td>
							<td><?php echo htmlspecialchars($precinct); ?></td>
							<td><?php echo htmlspecialchars($hl_cluster); ?></td>
							<td></td>
						</tr>

						<!-- Household Members Rows -->
						<?php
							$ii = 1;
							$stmt = $link->prepare('select * from hl_children hl, voters v where hl.hlvin=? and hl.vin=v.vin');
							$stmt->execute([$rs["vin"]]);
							$exhlc = $stmt;
							
							while($rshlc = $exhlc->fetch(PDO::FETCH_BOTH)){
								$hmname = $rshlc["vname"];
								$hmsex = $rshlc["sex"];
								$hmprec = $rshlc["precinct"];
								
								$stmt = $link->prepare("select cluster from clusters where precinct LIKE CONCAT('%', ?, '%')");
								$stmt->execute([$hmprec]);
								$cluster2 = $stmt;
								$rsc2 = $cluster2->fetch(PDO::FETCH_BOTH);
								$hm_cluster = $rsc2 ? $rsc2[0] : "";
										
								$hmBirthDate = $rshlc["birth"];
								$hmAge = "-";
								if (!empty($hmBirthDate) && $hmBirthDate !== '0000-00-00') {
									$hmBirthObj = date_create($hmBirthDate);
									if ($hmBirthObj !== false) {
										$hmAge = date_diff($hmBirthObj, date_create('today'))->y;
									}
								}
									
								$clusterColor = ($hl_cluster !== $hm_cluster) ? "color: red;" : "color: #000;";
							?>
							<tr class="fw-bold align-middle text-center">
								<td><?php echo $ii; ?>.</td>
								<td style="padding:5px !important">
									<?php if (file_exists("images/voters/" . $rshlc["vin"] . ".jpg")): ?>
										<img src="images/voters/<?php echo $rshlc["vin"]; ?>.jpg" style="width: 50px; aspect-ratio:2/2;" class="rounded border" />
									<?php else: ?>
										<img src="images/blank.jpg" height="50" class="rounded border" />
									<?php endif; ?>
								</td>
								<td class="text-start ps-3"><?php echo htmlspecialchars($hmname); ?></td>
								<td><?php echo htmlspecialchars($hmsex); ?></td>
								<td><?php echo $hmAge; ?></td>
								<td><?php echo htmlspecialchars($hmprec); ?></td>
								<td style="<?php echo $clusterColor; ?>"><?php echo htmlspecialchars($hm_cluster); ?></td>
								<td></td>
							</tr>
						<?php
							$ii++;
							}
						?>
					</tbody>
				</table>
			</div>

			<!-- Verification Signatures Block -->
			<div class="row text-center mt-5 mb-4 g-4" style="font-size: 13px;">
				<div class="col-md-4">
					<div class="fw-bold mb-1"><?php echo htmlspecialchars($bce_name); ?></div>
					<div class="border-top border-dark pt-1 mx-auto" style="max-width: 220px;">Supervising BCE</div>
				</div>
				<div class="col-md-4">
					<div class="fw-bold mb-1"></div>
					<div class="pt-1 mx-auto" style="max-width: 220px;"></div>
				</div>
				<div class="col-md-4">
					<div class="fw-bold mb-1"><?php echo htmlspecialchars($mce_name); ?></div>
					<div class="border-top border-dark pt-1 mx-auto" style="max-width: 220px;">Supervising MCE</div>
				</div>
			</div>
			<!-- Office Signatures Block -->
			<div class="row text-center mt-5 g-4" style="font-size: 13px;">
				<div class="col-md-4" style="margin-top:-60px">
					<div class="mb-1"><img src="images/no_signature.png" height="55" alt="Signature" /></div>
					<div class="fw-bold mb-1">AILYN OYOA SULAD</div>
					<div class="border-top border-dark pt-1 mx-auto" style="max-width: 220px;">Supervising Facilitator</div>
				</div>
				<div class="col-md-4 d-flex align-items-center justify-content-center" style="margin-top:-100px">
					<div class="border border-secondary border-dashed p-4 text-muted" style="width: 200px; border-style: dotted !important;">
						PAID STAMP
					</div>
				</div>
				<div class="col-md-4" style="margin-top:-60px">
					<div class="mb-1"><img src="images/no_signature.png" height="55" alt="Signature" /></div>
					<div class="fw-bold mb-1">AMANDA SEYFRIED</div>
					<div class="border-top border-dark pt-1 mx-auto" style="max-width: 220px;">Cashier</div>
				</div><br>
			</div><br>
		</div><br>
	<?php } ?>
</div><br>

<?php include 'footer.php'; ?>
