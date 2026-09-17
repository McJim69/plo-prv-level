<?php
	require("connect-pdo.php");
	require("head.php");

	// Safe parameter handling
	$t_search = isset($_POST["t_search"]) ? $_POST["t_search"] : "";
	$barangay = isset($_GET["barangay"]) ? $_GET["barangay"] : "";
	$p = isset($_GET["page"]) ? intval($_GET["page"]) : 1;
	$rec = 9; // 10 cards per page (fits nicely in 2x5 grid on A4 print layouts)
	if($p < 1) $p = 1;
	$from = ($p - 1) * $rec;

	$filter = "";
	if (!empty($barangay) && $barangay !== "All barangays") {
		$filter = " barangay = " . $link->quote($barangay) . " AND ";
	}

	$search_cond = "";
	if (!empty($t_search)) {
		$search_cond = " (vname LIKE " . $link->quote("%".$t_search."%") . " OR precinct LIKE " . $link->quote("%".$t_search."%") . ") AND ";
	}

	// Count total voters matching criteria
	$count_query = "SELECT count(*) FROM voters WHERE {$filter} {$search_cond} ato = 'Sure' AND city_mun = ?";
	$stmt = $link->prepare($count_query);
	$stmt->execute([$_SESSION["city_mun"]]);
	$total_rows = $stmt->fetchColumn();
	$total_pages = ceil($total_rows / $rec);
	if ($total_pages < 1) $total_pages = 1;

	// Query paginated voters
	$data_query = "SELECT * FROM voters WHERE {$filter} {$search_cond} ato = 'Sure' AND city_mun = ? ORDER BY vname LIMIT $from, $rec";
	$stmt = $link->prepare($data_query);
	$stmt->execute([$_SESSION["city_mun"]]);
	$voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

	require("menu.php");
?>

<div class="container main-content">
	<!-- Search & Filters Card -->
	<form method="post" enctype="multipart/form-data" class="mb-4 d-print-none" id="searchForm">
		<div class="card-glass p-3">
			<div class="row g-3 align-items-center">
				<div class="col-md-3">
					<div class="input-group">
						<span class="input-group-text border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
						<input type="text" name="t_search" id="t_search" class="form-control border-start-0 ps-2" 
							placeholder="Type a keyword..." value="<?php echo htmlspecialchars($t_search); ?>" />
					</div>
				</div>
				<div class="col-md-5 d-flex gap-2">
					<button type="submit" name="b_search" class="btn btn-danger flex-fill fw-bold"><i class="fa-solid fa-search"></i> Search</button>
					<button type="button" class="btn btn-secondary flex-fill fw-bold" onclick="window.print()"><i class="fa-solid fa-print"></i> Print ID Cards</button>
					<button type="button" class="btn btn-success flex-fill fw-bold" onclick="jump('voterslist.php')"><i class="fa-solid fa-users"></i> Voters List</button>
				</div>
				<div class="col-md-2">
					<select class="form-select" onchange="jump('?barangay='+encodeURIComponent(this.value))">
						<option value="All barangays">All barangays</option>
						<?php
							$ex = $link->query("select barangay from voters group by barangay order by barangay");
							while($rs = $ex->fetch(PDO::FETCH_BOTH)){
								$sel = ($barangay === $rs[0]) ? "selected" : "";
								echo "<option value='".htmlspecialchars($rs[0])."' $sel>".htmlspecialchars($rs[0])."</option>";
							}
						?>
					</select>
				</div>	
				<div class="col-md-2">
					<select class="form-select" onchange="jump('?page='+this.value+'&barangay=<?php echo isset($_GET["barangay"]) ? urlencode($_GET["barangay"]) : ''; ?>')">
						<?php
							for($j=1; $j<=$total_pages; $j++){
								$sel = ($p == $j) ? "selected" : "";
								echo "<option value='$j' $sel>Page $j</option>";
							}
						?>
					</select>				
				</div>
			</div>
		</div>
	</form>
	<!-- ID Cards Grid -->
	<div class="id-card-grid">
		<?php
			if (count($voters) > 0) {
				foreach ($voters as $v) {
					// Fetch voter image
					$img_src = file_exists("images/voters/".$v["vin"].".jpg") ? "images/voters/".$v["vin"].".jpg" : "images/blank.jpg";
					
					// Fetch turnout cluster
					$stmt = $link->prepare("select cluster from clusters where precinct LIKE CONCAT('%', ?, '%')");
					$stmt->execute([$v["precinct"]]);
					$c_row = $stmt->fetch(PDO::FETCH_BOTH);
					$v_cluster = $c_row ? $c_row[0] : "-";

					// Fetch role title
					$stmt = $link->prepare("
						SELECT 
							(SELECT COUNT(*) FROM mce WHERE vin = ?) as is_mce,
							(SELECT COUNT(*) FROM bce WHERE vin = ?) as is_bce,
							(SELECT COUNT(*) FROM hl WHERE vin = ?) as is_hl,
							(SELECT COUNT(*) FROM hl_children WHERE vin = ?) as is_hlc
					");
					$stmt->execute([$v["vin"], $v["vin"], $v["vin"], $v["vin"]]);
					$roles = $stmt->fetch(PDO::FETCH_ASSOC);

					$role_title = "Voter";
					if ($roles["is_mce"] > 0) {
						$role_title = "Municipal Council of Elders";
					} elseif ($roles["is_bce"] > 0) {
						$role_title = "Barangay Council of Elders";
					} elseif ($roles["is_hl"] > 0) {
						$role_title = "Household Leader";
					} elseif ($roles["is_hlc"] > 0) {
						$role_title = "Household Member";
					}
			?>
			<div class="id-card-print-wrapper">
				<div class="id-card">
					<div class="id-card-header">
						<div class="id-card-logo-txt">
							<i class="fa-solid fa-users-rectangle"></i> TABINA <span>LIBERTARIANS</span>
						</div>
						<div class="id-card-title">Member Card</div>
					</div>
					
					<div class="id-card-body">
						<div class="id-card-photo-wrapper">
							<img src="<?php echo $img_src; ?>" class="id-card-photo" alt="Voter Photo" />
						</div>
						
						<div class="id-card-info">
							<div class="id-card-name"><?php echo htmlspecialchars($v["vname"]); ?></div>
							<div class="id-card-role"><?php echo htmlspecialchars($role_title); ?></div>
							<div class="id-card-meta">
								ID No: <strong><?php printf("%04d", $v["vin"]); ?></strong><br>
								Precinct: <strong><?php echo htmlspecialchars($v["precinct"]); ?></strong><br> 
								Cluster Number: <strong><?php echo htmlspecialchars($v_cluster); ?></strong><br>
								Barangay: <strong><?php echo htmlspecialchars($v["barangay"]); ?></strong><br>
								Municipality: <strong><?php echo htmlspecialchars($v["city_mun"]); ?></strong>
							</div>
						</div>
					</div>
					
					<div class="id-card-footer">
						<div class="id-card-barcode-placeholder"></div>
						<span class="id-card-badge"><?php echo htmlspecialchars($v["ato"] ?: "Voter"); ?></span>
					</div>
				</div>
			</div>
		<?php
				}
			} else {
				echo "<div class='alert alert-warning w-100 text-center d-print-none'>No voter records found matching parameters.</div>";
			}
		?>
	</div>
</div>

<?php include 'footer.php';?>