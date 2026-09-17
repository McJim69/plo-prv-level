<?php
	require("connect-pdo.php");
	require("head.php");
	require("menu.php");

	// Add COLLATE utf8_unicode_ci to join to avoid collation mismatch error
	$stmt = $link->prepare('select * from voters v, counter m where m.vin=v.vin COLLATE utf8_unicode_ci and v.city_mun=? order by precinct');
	$stmt->execute([$_SESSION["city_mun"]]);
	$ex = $stmt;
?>

<div class="container main-content">
	<div class="card-glass p-0 overflow-hidden shadow-sm">
		<div class="bg-danger text-white px-4 py-3">
			<h4 class="mb-0 fw-bold">
				<i class="fa-solid fa-list-check me-2"></i> Counted Respondents List &nbsp; 
				<a style="text-decoration:none;" href="counter.php">Back<i class="fa fa-arrow-right"></i></a>
			</h4>
			<span class="small text-white-50">Voters registered under current session: <?php echo htmlspecialchars($_SESSION["city_mun"]); ?></span>
		</div>
		
		<div class="table-responsive">
			<table class="table table-hover align-middle mb-0">
				<thead class="table-dark">
					<tr class="align-middle">
						<th class="ps-4">NO</th>
						<th>VOTER'S NAME</th>
						<th>PRECINCT</th>
						<th>ADDRESS</th>
						<th>BARANGAY</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$i = 1;
						while($rs = $ex->fetch(PDO::FETCH_BOTH)){
							echo "
							<tr>
								<td class='ps-4 fw-bold text-secondary'>$i.</td>
								<td class='fw-bold'>".htmlspecialchars($rs["vname"])."</td>
								<td>".htmlspecialchars($rs["precinct"])."</td>
								<td>".htmlspecialchars($rs["address"])."</td>
								<td>".htmlspecialchars($rs["barangay"])."</td>
							</tr>";
							$i++;
						}
						if ($i == 1) {
							echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No counted voters found.</td></tr>";
						}
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php require("footer.php"); ?>
</body>
</html>