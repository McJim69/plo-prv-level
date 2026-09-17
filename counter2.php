<?php
	require("connect-pdo.php");
	require("head.php");

	// Safely fetch municipality and barangay selections, defaulting to session context
	$municipality = isset($_GET["municipality"]) ? $_GET["municipality"] : $_SESSION["city_mun"];
	$barangay = isset($_GET["barangay"]) ? $_GET["barangay"] : $_SESSION["barangay"];

	$mun = "";
	if ($municipality != "MUNICIPALITY" && $municipality != "") {
		$mun = "city_mun='" . $municipality . "'";
		$mun_ = "CITY OF " . $municipality;
	} else {
		$mun_ = $municipality;
	}

	$bar = "";
	if ($barangay != "BARANGAY" && $barangay != "") {
		$bar = "barangay='" . $barangay . "'";
		$bar_ = "BARANGAY " . $barangay;
	} else {
		$bar_ = $barangay;
	}

	$where_clauses = [];
	if (!empty($municipality) && $municipality !== "MUNICIPALITY") {
		$where_clauses[] = "v.city_mun = " . $link->quote($municipality);
	}
	if (!empty($barangay) && $barangay !== "BARANGAY" && $barangay !== "All barangays") {
		$where_clauses[] = "v.barangay = " . $link->quote($barangay);
	}

	$where_str = "";
	if (count($where_clauses) > 0) {
		$where_str = "WHERE " . implode(" AND ", $where_clauses);
	}

	// Prepare variables for table iteration
	$totvoters = 0;
	$totTRV = 0;
	$totTO = 0;
	$solidTot = 0;
	$preTot = 0;
	$totMCE = 0;
	$tBCE = 0;
	$tHL = 0;
	$tHLC = 0;
	$undTotal = 0;
	$swdTotal = 0;
	$tOverTot = 0;
	$totCNT = 0;
	$tCNT = 0;
	$hltarget = 0;
	$hltargetTot = 0;
	$targetMemTot = 0;
?>

<script>
	var table = 0;
	var hlvotno = 0;
								
	function getVoters(value){	
		table = "mce";
		xmlhttp.onreadystatechange = function(){
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200){
				getID("query_voters").innerHTML = xmlhttp.responseText;
			}
		}						
		xmlhttp.open("GET", "ajax/getcounter.php?value=" + encodeURIComponent(value) + "&id=" + hlvotno + "&table=" + table, true);
		xmlhttp.send();
	}
				
	function add(){
		var addModal = new bootstrap.Modal(document.getElementById('votersModal'));
		addModal.show();
		
		// Reload when modal is closed to show newly added votes in the list
		document.getElementById('votersModal').addEventListener('hidden.bs.modal', function () {
			window.location.reload();
		});
	}

	function printF() {
		window.print();
	}
</script>

<!-- CSS print styles to automatically hide non-printable headers -->
<style>
	@media print {
		body {
			background-color: #fff !important;
			color: #000 !important;
		}
		.d-print-none {
			display: none !important;
		}
		.card-glass {
			background: transparent !important;
			box-shadow: none !important;
			border: 0 !important;
		}
	}
</style>

<?php require("menu.php"); ?>
<script>
	setActive("counter");
</script>

<!-- Voters Lookup Modal -->
<div class="modal fade" id="votersModal" tabindex="-1" aria-labelledby="votersModalLabel" aria-hidden="true" style="margin-top:50px;">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content" style="background: rgba(255,255,255,0.98); backdrop-filter: blur(10px); border-radius: var(--radius-md);">
			<div class="modal-header bg-danger text-white">
				<h5 class="modal-title" id="votersModalLabel"><i class="fa-solid fa-user-check me-2"></i> Add Votes / Count Voter</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<input type="text" class="form-control" placeholder="Type a name or keyword to search..." onfocus="this.value=''" onkeyup="getVoters(this.value)" />
				</div>
				<div id="query_voters" style="max-height: 500px; overflow-y: auto;"></div>
			</div>
		</div>
	</div>
</div>

<div class="container my-4">
	<!-- Dashboard Title & Header -->
	<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
		<div class="d-flex align-items-center gap-3">
			<img src="images/logo.png" height="65" alt="Logo" class="d-none d-sm-block" />
			<div>
				<h1 class="display-6 fw-bold text-danger mb-0">EXIT POLL COUNTER</h1>
				<span class="text-muted">Real-time Turnout Analytics</span>
			</div>
		</div>
		<img src="images/app-logo.png" height="65" alt="Campaign Logo" class="d-none d-sm-block" />
	</div>

	<!-- Search & Controls Card -->
	<div class="card-glass p-3 mb-4 d-print-none">
		<div class="row g-3 align-items-center justify-content-between">
			<div class="col-lg-7 d-flex gap-2 flex-wrap">
				<a href="counter.php" class="btn btn-outline-danger rounded-pill px-3 fw-bold"><i class="fa-solid fa-chart-pie me-2"></i> Overview</a>
				<button type="button" class="btn btn-success rounded-pill px-3 fw-bold" onclick="getVoters('');add();"><i class="fa-solid fa-plus me-2"></i> Add Votes</button>
				<button type="button" class="btn btn-dark rounded-pill px-3 fw-bold" onclick="printF()"><i class="fa-solid fa-print me-2"></i> Print Result</button>
				<a href="viewvoters.php" class="btn btn-outline-secondary rounded-pill px-3 fw-bold"><i class="fa-solid fa-list-check me-2"></i> Counted List</a>
			</div>
			<div class="col-lg-5">
				<form method="get" class="row g-2 justify-content-lg-end align-items-center">
					<div class="col-auto">
						<select name="municipality" class="form-select rounded-pill px-3" onchange="this.form.submit()">
							<option value="">All municipalities</option>
							<?php
								$stmt = $link->prepare('select city_mun from voters group by city_mun order by city_mun');
								$stmt->execute([]);
								$ex_mun = $stmt;
								while($rs = $ex_mun->fetch(PDO::FETCH_BOTH)){
									$sel = ($municipality === $rs[0]) ? "selected" : "";
									echo "<option value='".htmlspecialchars($rs[0])."' $sel>".htmlspecialchars($rs[0])."</option>";
								}
							?>
						</select>
					</div>
					<div class="col-auto">
						<select name="barangay" class="form-select rounded-pill px-3" onchange="this.form.submit()">
							<option value="">All barangays</option>
							<?php
								if (!empty($municipality)) {
									$stmt = $link->prepare('select barangay from voters where city_mun=? group by barangay order by barangay');
									$stmt->execute([$municipality]);
									$ex_bar = $stmt;
									while($rs = $ex_bar->fetch(PDO::FETCH_BOTH)){
										$sel = ($barangay === $rs[0]) ? "selected" : "";
										echo "<option value='".htmlspecialchars($rs[0])."' $sel>".htmlspecialchars($rs[0])."</option>";
									}
								}
							?>
						</select>
					</div>
				</form>
			</div>
		</div>
	</div>

	<?php
		// DETAILED BREAKDOWN OF PUROKS FOR A SELECTED BARANGAY
		if (!empty($municipality) && $municipality !== "DISTRICT 1" && $municipality !== "DISTRICT 2" && $municipality !== "PROVINCE" && !empty($barangay) && $barangay !== "All barangays") {
	?>
		<!-- Purok Breakdown Table Card -->
		<div class="card-glass p-0 overflow-hidden shadow-sm">
			<div class="bg-danger text-white px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
				<div>
					<h5 class="mb-0 fw-bold"><i class="fa-solid fa-map-location-dot me-2"></i> Breakdown: <?php echo htmlspecialchars($bar_); ?></h5>
					<span class="small text-white-50"><?php echo htmlspecialchars($mun_); ?></span>
				</div>
				<div class="text-end">
					<?php include("time.php"); ?>
				</div>
			</div>
			<div class="table-responsive">
				<table class="table table-hover align-middle mb-0">
					<thead class="table-dark">
						<tr class="align-middle text-center">
							<th>NO.</th>
							<th class="text-start">PUROK</th>						
							<th>REGISTERED (TRV)</th>
							<th>EST. TURNOUT (ETO)</th>
							<th>TARGET (ETO * 65%)</th>
							<th>TARGET (ETO * 55%)</th>
							<th>TOTAL PRECINCT</th>
							<th>TOTAL MEMBERS</th>		
							<th>VOTES COUNTER</th>
							<th>REMARKS</th>				
						</tr>
					</thead>
					<tbody>
						<?php
							$stmt = $link->prepare("select address from voters v {$where_str} group by address order by address");
							$stmt->execute([]);
							$ex = $stmt;
							$i = 1;
							while($rs = $ex->fetch(PDO::FETCH_BOTH)){
								$total = 0;
								
								// 1. Registered Voters (TRV)
								$stmt = $link->prepare('select count(*) from voters v where v.address=? and v.barangay=? ');
								$stmt->execute([$rs[0], $barangay]);
								$extv = $stmt;
								$rstv = $extv->fetch(PDO::FETCH_BOTH);
								$totvoters += $rstv[0];
							
								// 2. Est Turnout (ETO)
								$t_out = $rstv[0] * 0.80;
								$tro = $t_out * 0.65;
								$totTRV += $t_out;
								$totTO += $tro;
								
								// 3. Targets
								$solid = $t_out * 0.55;
								$solidTot += $solid;
								$sol = $t_out * 0.10;
								$solTot += $sol;
								
								// 4. Total Precinct
								$stmt = $link->prepare('select precinct from voters v where v.address=? and v.precinct<>\'no precinct\' and v.barangay=? group by v.precinct ');
								$stmt->execute([$rs[0], $barangay]);
								$exprec = $stmt;
								$rsprec = $exprec->rowCount();
								$preTot += $rsprec;
								
								// 5. Total Members Calculation
								$stmt = $link->prepare('select count(*) from mce m, voters v where m.vin=v.vin and v.address=? and v.barangay=? ');
								$stmt->execute([$rs[0], $barangay]);
								$exmce = $stmt;
								$rsmce = $exmce->fetch(PDO::FETCH_BOTH);
								$totMCE += $rsmce[0];
								$total += $rsmce[0];

								$stmt = $link->prepare('select count(*) from bce b, voters v where b.vin=v.vin and v.address=? and v.barangay=? ');
								$stmt->execute([$rs[0], $barangay]);
								$exbce = $stmt;
								$rsbce = $exbce->fetch(PDO::FETCH_BOTH);
								$tBCE += $rsbce[0];
								$total += $rsbce[0];
													
								$stmt = $link->prepare('select count(*) from pl m, voters v where m.vin=v.vin and v.address=? and v.barangay=? ');
								$stmt->execute([$rs[0], $barangay]);
								$expl = $stmt;
								$rspl = $expl->fetch(PDO::FETCH_BOTH);
								$totPL += $rspl[0];
								$total += $rspl[0];
								
								$stmt = $link->prepare('select count(*) from hl h, voters v where h.vin=v.vin and v.address=? and v.barangay=? ');
								$stmt->execute([$rs[0], $barangay]);
								$exhl = $stmt;
								$rshl = $exhl->fetch(PDO::FETCH_BOTH);
								$total += $rshl[0];
								$tHL += $rshl[0];
								
								$stmt = $link->prepare('select count(*) from hl_children hlc, voters v where hlc.vin=v.vin and v.address=? and v.barangay=? ');
								$stmt->execute([$rs[0], $barangay]);
								$exhlc = $stmt;
								$rshlc = $exhlc->fetch(PDO::FETCH_BOTH);
								$total += $rshlc[0];
								$tHLC += $rshlc[0];

								$stmt = $link->prepare('select count(*) from voters v, hl_children h where v.vin=h.vin and v.ato=\'undecided\' and v.address=?');
								$stmt->execute([$rs[0]]);
								$exUnd = $stmt;
								$rsUnd = $exUnd->fetch(PDO::FETCH_BOTH);
								$undTotal += $rsUnd[0];
								
								$stmt = $link->prepare('select count(*) from voters v, hl_children h where v.vin=h.vin and v.ato=\'Sure wala diri\' and v.address=?');
								$stmt->execute([$rs[0]]);
								$exSwd = $stmt;
								$rsSwd = $exSwd->fetch(PDO::FETCH_BOTH);
								$swdTotal += $rsSwd[0];
								
								$total = $total - $rsSwd[0] - $rsUnd[0];
								$tOverTot += $total;

								// 6. Votes Counter
								$stmt = $link->prepare('select count(*) from counter hlc, voters v where hlc.vin=v.vin COLLATE utf8_unicode_ci and v.address=? and v.barangay=? ');
								$stmt->execute([$rs[0], $barangay]);
								$excnt = $stmt;
								$rscnt = $excnt->fetch(PDO::FETCH_BOTH);
								$tCNT += $rscnt[0];
													
								// 7. Remarks
								$percent = ($t_out > 0) ? ($rscnt[0] / $t_out * 100) : 0;
								$rem = "HAYAG NA!";
								$badge_class = "bg-success";
								if ($percent < 55) {
									$rem = "APEKI";
									$badge_class = "bg-danger";
								}
									
								echo "
								<tr class='text-center'>
									<td>$i</td>
									<td class='text-start fw-bold text-secondary'>".htmlspecialchars($rs[0])."</td>
									<td>".number_format($rstv[0],0)."</td>
									<td>".number_format($t_out,0)."</td>
									<td>".number_format($tro,0)."</td>
									<td>".number_format($solid,0)."</td>
									<td>".$rsprec."</td>
									<td>".number_format($total,0)." <span class='text-primary small'>(".number_format(($t_out > 0 ? $total/$t_out*100 : 0),1)."%)</span></td>
									<td class='fw-bold'>".number_format($rscnt[0],0)." <span class='text-success small'>(".number_format($percent,1)."%)</span></td>
									<td><span class='badge $badge_class px-3 py-2'>$rem</span></td>
								</tr>";
								$i++;
							}

							$overall_percent = ($totTRV > 0) ? ($tCNT / $totTRV * 100) : 0;
							$overall_rem = "HAYAG NA!";
							$overall_badge = "bg-success";
							if ($overall_percent < 55) {
								$overall_rem = "APEKI";
								$overall_badge = "bg-danger";
							}
						?>
						<tr class="table-dark font-weight-bold align-middle text-center fs-6 fw-bold">
							<td></td>
							<td class="text-start">TOTALS</td>
							<td><?php echo number_format($totvoters,0); ?></td>
							<td><?php echo number_format($totTRV,0); ?></td>
							<td><?php echo number_format($totTO,0); ?></td>
							<td><?php echo number_format($solidTot,0); ?></td>
							<td><?php echo number_format($preTot,0); ?></td>			
							<td><?php echo number_format($tOverTot,0); ?> <span class="text-white small fw-normal">(<?php echo number_format(($totTRV > 0 ? $tOverTot/$totTRV*100 : 0),1); ?>%)</span></td>
							<td><?php echo number_format($tCNT,0); ?> <span class="text-white small fw-normal">(<?php echo number_format($overall_percent,1); ?>%)</span></td>
							<td><span class="badge <?php echo $overall_badge; ?> px-3 py-2 fs-6"><?php echo $overall_rem; ?></span></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

	<?php
		// HIGH LEVEL OVERVIEW FOR MUNICIPALITIES/DISTRICTS
		} else {
			// Pull totals across the scoped region
			$stmt = $link->prepare('select city_mun from voters group by city_mun order by city_mun');
			$stmt->execute([]);
			$ex = $stmt;
			while($rs = $ex->fetch(PDO::FETCH_BOTH)){
				$stmt = $link->prepare('select count(*) from voters v where v.city_mun=?');
				$stmt->execute([$rs["city_mun"]]);
				$extv = $stmt;
				$rstv = $extv->fetch(PDO::FETCH_BOTH);
				$totvoters += $rstv[0];

				$t_out = $rstv[0] * 0.80;
				$totTRV += $t_out;

				$stmt = $link->prepare('select count(*) from counter hlc, voters v where hlc.vin=v.vin COLLATE utf8_unicode_ci and v.city_mun=?');
				$stmt->execute([$rs["city_mun"]]);
				$excnt = $stmt;
				$rscnt = $excnt->fetch(PDO::FETCH_BOTH);
				$tCNT += $rscnt[0];
			}
	?>

		<!-- Statistics Widgets -->
		<div class="row g-4 mb-5">
			<!-- Total Registered Voters -->
			<div class="col-md-4">
				<div class="card card-glass h-100 text-center p-3 border-0">
					<div class="card-body">
						<div class="text-primary mb-3">
							<i class="fa-solid fa-users fa-3x"></i>
						</div>
						<h5 class="text-muted fw-bold">Total Registered Voters (TRV)</h5>
						<h2 class="display-5 fw-bold text-muted mt-2"><?php echo number_format($totvoters,0); ?></h2>
					</div>
				</div>
			</div>

			<!-- Tally Counter Widget -->
			<div class="col-md-4">
				<div class="card sr-hero card-glass h-100 text-center p-3 border-0 bg-danger text-white bg-opacity-95 shadow">
					<div class="card-body d-flex flex-column justify-content-between align-items-center">
						<h5 class="fw-bold text-light">Current Votes Counted</h5>
						<div class="my-3 pointer-cursor" onclick="getVoters('');add();" style="cursor: pointer;">
							<span class="display-1 fw-bold font-stencil"><?php echo number_format($tCNT,0); ?></span>
						</div>
						<button type="button" class="btn btn-white text-danger fw-bold rounded-pill px-4" onclick="getVoters('');add();">
							<i class="fa-solid fa-plus-circle me-1"></i> Register Vote
						</button>
					</div>
				</div>
			</div>

			<!-- Estimated Turn Out -->
			<div class="col-md-4">
				<div class="card card-glass h-100 text-center p-3 border-0">
					<div class="card-body">
						<div class="text-success mb-3">
							<i class="fa-solid fa-chart-line fa-3x"></i>
						</div>
						<h5 class="text-muted fw-bold">Estimated Turnout (ETO)</h5>
						<h2 class="display-5 fw-bold text-muted mt-2"><?php echo number_format($totTRV,0); ?></h2>
						<span class="text-muted small">TRV * 80% baseline</span>
					</div>
				</div>
			</div>
		</div>

		<!-- Percentage progress visualizations -->
		<div class="row g-4 mb-4">
			<div class="col-md-6">
				<div class="card card-glass p-4 border-0">
					<h5 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-pie-chart me-2"></i> Percentage of Total Registered (TRV)</h5>
					<?php 
						$trv_percentage = ($totvoters > 0) ? ($tCNT / $totvoters * 100) : 0; 
					?>
					<div class="progress rounded-pill mb-2" style="height: 25px;">
						<div class="progress-bar bg-primary progress-bar-striped progress-bar-animated fw-bold fs-6 realtime-progress" 
							role="progressbar" 
							style="width: 0%; transition: width 1.2s cubic-bezier(0.1, 1.0, 0.1, 1.0) !important;" 
							data-target="<?php echo $trv_percentage; ?>" 
							aria-valuenow="0" 
							aria-valuemin="0" 
							aria-valuemax="100">0.0%</div>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card card-glass p-4 border-0">
					<h5 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-pie-chart me-2"></i> Percentage of Estimated Turnout (ETO)</h5>
					<?php 
						$eto_percentage = ($totTRV > 0) ? ($tCNT / $totTRV * 100) : 0; 
					?>
					<div class="progress rounded-pill mb-2" style="height: 25px;">
						<div class="progress-bar bg-success progress-bar-striped progress-bar-animated fw-bold fs-6 realtime-progress" 
							role="progressbar" 
							style="width: 0%; transition: width 1.2s cubic-bezier(0.1, 1.0, 0.1, 1.0) !important;" 
							data-target="<?php echo $eto_percentage; ?>" 
							aria-valuenow="0" 
							aria-valuemin="0" 
							aria-valuemax="100">0.0%</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Script to animate progress bars and numbers on load -->
		<script>
			document.addEventListener("DOMContentLoaded", function() {
				document.querySelectorAll('.realtime-progress').forEach(function(bar) {
					let target = parseFloat(bar.getAttribute('data-target'));
					
					// Animate width expansion
					setTimeout(function() {
						bar.style.width = target + '%';
					}, 100);
					
					// Animate text counter increase
					if (target > 0) {
						let duration = 1200; // 1.2 seconds matching transition duration
						let startTime = null;
						
						function animateText(timestamp) {
							if (!startTime) startTime = timestamp;
							let progress = timestamp - startTime;
							let fraction = Math.min(progress / duration, 1);
							// Apply easeOutQuad easing
							let ease = fraction * (2 - fraction);
							let current = ease * target;
							bar.innerText = current.toFixed(1) + '%';
							
							if (progress < duration) {
								requestAnimationFrame(animateText);
							} else {
								bar.innerText = target.toFixed(1) + '%';
							}
						}
						requestAnimationFrame(animateText);
					} else {
						bar.innerText = '0.0%';
					}
				});
			});
		</script>

		<!-- Optional Countdown Display Section -->
		<?php
			$ex = $link->query("SELECT * FROM countdown");
			if ($rs = $ex->fetch(PDO::FETCH_BOTH)){
				$name = "".$rs['name']."";
				$target = "".$rs['target']."";
				$y = "".$rs['year']."";	
				$m = "".$rs['month']."";
				$d = "".$rs['day']."";
				$h = "".$rs['hour']."";
				$i = "".$rs['min']."";
				$s = "".$rs['sec']."";	
				$date = $y."-".$m."-".$d." ".$h.":".$i.":".$s;
		?>
			<div class="card card-glass text-center p-4 border-0 mb-5">
				<h5 class="fw-bold text-danger"><i class="fa-solid fa-hourglass-half me-2"></i> <?php echo htmlspecialchars($name); ?></h5>
				<div id="defaultCountdown" class="fs-4 my-3 fw-bold text-secondary"></div>
				
				<?php if ($date < date('Y-m-d H:i:s')): ?>
					<div class="alert alert-danger max-width-lg mx-auto py-2">
						<marquee behavior="scroll" direction="left" scrollamount="2"><?php echo htmlspecialchars($target); ?></marquee>
					</div>
					<div class="mt-2">
						<a href="viewvoters.php" id="ViewVoters" class="btn btn-outline-danger btn-sm rounded-pill px-4"><i class="fa-solid fa-users-viewfinder"></i> Respondents</a>
					</div>
				<?php else: ?>
					<div class="mt-2">
						<a href="ajax/clearcounter.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4"><i class="fa-solid fa-trash-can"></i> Clear Counter</a>
					</div>
				<?php endif; ?>
			</div>

			<script type="text/javascript" src="fancybox/jquery.fancybox-1.3.3.pack.js"></script>
			<link rel="stylesheet" type="text/css" href="fancybox/jquery.fancybox-1.3.3.css" media="screen" />
			<script type="text/javascript">
				$(document).ready(function() {
					$("#ViewVoters").fancybox({
						'titlePosition' : 'inside',
						'transitionIn' : 'none',
						'transitionOut' : 'none',
						'hideOnContentClick' : false,
						'hideOnOverlayClick' : false
					});                    
				});
			</script>
			<script type='text/javascript' src='scripts/jquery.counter.js?ver=1.10.2'></script>
			<script type="text/javascript" src="scripts/jquery.countdown.js"></script>
			<script type="text/javascript">
				var $j = jQuery.noConflict();
				$j(function () {
					var austDay = new Date("<?php echo"".$y." ".$m." ".$d." ".$h.":".$i.":".$s.""?>");
					$j('#defaultCountdown').countdown({until: austDay, layout: '{dn} {dl}, {hn} {hl}, {mn} {ml}, and {sn} {sl}'});
				});
			</script>
		<?php } ?>
	<?php } ?>
</div>

<!-- Scripts for Accept and Remove Actions -->
<script>
	function addmce(id,row){
		table = "mce";
		var vin = id;
		xmlhttp.onreadystatechange = function(){
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200){
				if(xmlhttp.responseText == "Success"){
					$("#q_tr_"+row).animate({
						opacity:0
					},500,function(){
						$("#q_tr_"+row).css("display","none");
					});
				}else{
					alert(xmlhttp.responseText);
				}
			}
		}						
		xmlhttp.open("GET", "ajax/addcounter.php?table=" + table + "&vin=" + vin, true);
		xmlhttp.send();
	}
			
	function deletecounter(vin){	
		if(confirm("Are you Sure?")){
			xmlhttp.onreadystatechange = function(){
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200){
					if(xmlhttp.responseText == "Success"){
						$("#div_"+vin).animate({
							opacity:0
						},500);
					}
					$("#div_"+vin).animate({
						opacity:0
					},500);
				}
			}						
			xmlhttp.open("GET", "ajax/deletecounter.php?vin=" + vin, true);
			xmlhttp.send();
		}
	}
</script>

<?php require("footer.php"); ?>
</body>
</html>