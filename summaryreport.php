<?php
	require("connect-sqli.php");
	require "head.php";
	require "menu.php";

	$municipality = isset($_GET["municipality"]) ? $_GET["municipality"] : "";

	// ── District municipality lists (canonical reference) ──
	$d1 = "aurora,dumingag,josefina,labangan,mahayag,midsalip,ramon magsaysay,pagadian city,sominot,tukuran,tambulig,molave";
	$d2 = "bayog,dinas,dimataling,dumalinao,guipos,kumalarang,lakewood,lapuyan,margosatubig,pitogo,san pablo,tabina,tigbao,vincenzo sagun";

	$mun = "";
	$mun_ = "ALL MUNICIPALITIES";
	if ($municipality !== "DISTRICT 1" && $municipality !== "DISTRICT 2" && $municipality !== "PROVINCE" && $municipality !== "") {
		$mun = "city_mun='" . $municipality . "'";
		$mun_ = strtoupper($municipality);
	} elseif ($municipality !== "") {
		$mun_ = $municipality;
	}

	// ── Pre-load all summary data for KPI cards & charts ──
	$isMun = ($municipality !== "DISTRICT 1" && $municipality !== "DISTRICT 2" && $municipality !== "PROVINCE" && $municipality !== "");

	// Build WHERE clause from district or province scope
	function buildWhereClause($municipality, $d1, $d2) {
		if ($municipality !== "DISTRICT 1" && $municipality !== "DISTRICT 2" && $municipality !== "PROVINCE" && $municipality !== "") {
			return "v.city_mun='" . $municipality . "'";
		} else if ($municipality === "DISTRICT 1") {
			$muns = explode(",", $d1);
			return implode(" OR ", array_map(function($m) { return "v.city_mun='" . trim($m) . "'"; }, $muns));
		} else if ($municipality === "DISTRICT 2") {
			$muns = explode(",", $d2);
			return implode(" OR ", array_map(function($m) { return "v.city_mun='" . trim($m) . "'"; }, $muns));
		} else {
			return "1=1";
		}
	}

	function buildRowQuery($link, $municipality, $isMun, $mun, $d1, $d2) {
		if ($isMun) {
			$q = $link->query("SELECT barangay FROM voters WHERE $mun GROUP BY barangay ORDER BY barangay");
			$filter = "v.barangay=? AND v.city_mun='$municipality'";
			return array($q, $filter);
		}
		if ($municipality === "DISTRICT 1") {
			$muns = explode(",", $d1);
			$q2 = implode(" OR ", array_map(function($m) { return "city_mun='" . trim($m) . "'"; }, $muns));
		} elseif ($municipality === "DISTRICT 2") {
			$muns = explode(",", $d2);
			$q2 = implode(" OR ", array_map(function($m) { return "city_mun='" . trim($m) . "'"; }, $muns));
		} else {
			$q2 = "1=1";
		}
		$q = $link->query("SELECT city_mun FROM voters WHERE $q2 GROUP BY city_mun ORDER BY city_mun");
		$filter = "v.city_mun=?";
		return array($q, $filter);
	}

	$whereClause = buildWhereClause($municipality, $d1, $d2);

	function safeCount($link, $sql) {
		try {
			$r = $link->query($sql);
			if (!$r) return 0;
			$row = $r->fetch_array(MYSQLI_BOTH);
			return isset($row[0]) ? (int)$row[0] : 0;
		} catch (Exception $e) { return 0; }
	}

	$totalVoters = safeCount($link, "SELECT COUNT(*) FROM voters v WHERE $whereClause");
	$totalMCE    = safeCount($link, "SELECT COUNT(*) FROM mce m, voters v WHERE m.vin=v.vin AND $whereClause");
	$totalBCE    = safeCount($link, "SELECT COUNT(*) FROM bce b, voters v WHERE b.vin=v.vin AND $whereClause");
	$totalHL     = safeCount($link, "SELECT COUNT(*) FROM hl h, voters v WHERE h.vin=v.vin AND $whereClause");
	$totalHLC    = safeCount($link, "SELECT COUNT(*) FROM hl_children hlc, voters v WHERE hlc.vin=v.vin AND $whereClause");
	$totalSol    = 0; // safeCount($link, "SELECT COUNT(*) FROM sollist s, voters v WHERE s.vin=v.vin AND $whereClause");

	$trv      = round($totalVoters * 0.8);
	$eto      = round($trv * 0.65);
	$solid    = round($trv * 0.55);
	$solTarget = round($trv * 0.10);
	$orgTotal = $totalMCE + $totalBCE + $totalHL + $totalHLC;
	$pct      = $trv > 0 ? round($orgTotal / $trv * 100, 1) : 0;
?>

<!-- Assets already loaded in head.php / CDN -->
<script> setActive("sum"); </script>

<div class="container px-4 py-3 main-content">
<!-- ── HERO ── -->
<div class="sr-hero">
	<div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
		<div>
			<div class="sr-hero-title">
				<i class="fa-solid fa-chart-pie me-2"></i><?php echo $mun_; ?>
			</div>
			<div class="sr-hero-sub">Precinct-Level Organization — Summary Dashboard</div>
		</div>
		<div class="text-end">
			<div style="font-size:28px;font-weight:800;color:#fff;"><?php echo number_format($pct, 1); ?>%</div>
			<div style="color:rgba(255,255,255,0.6);font-size:12px;">Organization Rate</div>
		</div>
	</div>
	<div class="sr-filter-row">
		<!-- District Selector -->
		<div style="display:flex;flex-direction:column;gap:4px;">
			<label style="color:rgba(255,255,255,0.5);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;">📍 District</label>
			<select id="districtSelect" onchange="onDistrictChange(this.value)">
				<option value="PROVINCE" <?php if($municipality===''||$municipality==='PROVINCE') echo 'selected'; ?>>🌏 Province-wide</option>
				<option value="DISTRICT 1" <?php if($municipality==='DISTRICT 1') echo 'selected'; ?>>District 1</option>
				<option value="DISTRICT 2" <?php if($municipality==='DISTRICT 2') echo 'selected'; ?>>District 2</option>
			</select>
		</div>

		<div style="color:rgba(255,255,255,0.3);font-size:20px;align-self:flex-end;padding-bottom:8px;">›</div>

		<!-- Municipality Selector — auto-populated by JS based on district -->
		<div style="display:flex;flex-direction:column;gap:4px;">
			<label style="color:rgba(255,255,255,0.5);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;">🏘 Municipality</label>
			<select id="munSelect" onchange="if(this.value) jump('summaryreport.php?municipality='+this.value)">
				<option value="">— All —</option>
				<?php
					// Fetch which city_mun values actually have voter data
					$existingMuns = [];
					$exMuns = $link->query("SELECT DISTINCT city_mun FROM voters ORDER BY city_mun");
					while ($rm = $exMuns->fetch_array(MYSQLI_BOTH)) {
						$existingMuns[] = strtolower(trim($rm[0]));
					}

					$d1muns = array_map('trim', explode(',', $d1));
					$d2muns = array_map('trim', explode(',', $d2));

					// Render D1 options with data-district attribute, skip if no data
					foreach ($d1muns as $m) {
						if (!in_array(strtolower($m), $existingMuns)) continue;
						$sel = ($municipality === $m) ? 'selected' : '';
						echo "<option value='" . htmlspecialchars($m) . "' data-district='D1' $sel>" . strtoupper($m) . "</option>";
					}

					// Render D2 options with data-district attribute, skip if no data
					foreach ($d2muns as $m) {
						if (!in_array(strtolower($m), $existingMuns)) continue;
						$sel = ($municipality === $m) ? 'selected' : '';
						echo "<option value='" . htmlspecialchars($m) . "' data-district='D2' $sel>" . strtoupper($m) . "</option>";
					}
				?>
			</select>
		</div>

		<button class="sr-btn sr-btn-print" onclick="window.print()" style="align-self:flex-end;">
			<i class="fa-solid fa-print me-1"></i> Print
		</button>
	</div>
</div>

<script>
var currentDistrict = '<?php
	if ($municipality === "DISTRICT 1") echo "D1";
	elseif ($municipality === "DISTRICT 2") echo "D2";
	elseif ($isMun) {
		// Determine which district the current municipality is in
		$d1muns_js = array_map('trim', explode(',', $d1));
		$d2muns_js = array_map('trim', explode(',', $d2));
		if (in_array($municipality, $d1muns_js)) echo "D1";
		elseif (in_array($municipality, $d2muns_js)) echo "D2";
		else echo "ALL";
	} else echo "ALL";
?>';

function filterMunOptions(district) {
	var sel = document.getElementById('munSelect');
	var opts = sel.querySelectorAll('option[data-district]');
	var hasVisible = false;

	opts.forEach(function(opt) {
		var show = (district === 'ALL' || opt.getAttribute('data-district') === district);
		opt.style.display = show ? '' : 'none';
		opt.disabled = !show;
		if (show) hasVisible = true;
	});

	// Reset placeholder text
	sel.options[0].textContent = hasVisible ? '— Select Municipality —' : '— No data available —';

	// If current selected option is hidden, reset to blank
	if (sel.selectedIndex > 0 && sel.options[sel.selectedIndex].style.display === 'none') {
		sel.selectedIndex = 0;
	}
}

function onDistrictChange(val) {
	if (val === 'PROVINCE') {
		filterMunOptions('ALL');
		jump('summaryreport.php');
	} else if (val === 'DISTRICT 1') {
		filterMunOptions('D1');
		jump('summaryreport.php?municipality=DISTRICT+1');
	} else if (val === 'DISTRICT 2') {
		filterMunOptions('D2');
		jump('summaryreport.php?municipality=DISTRICT+2');
	}
}

// Run on page load to set correct initial state
(function() {
	filterMunOptions(currentDistrict);
})();
</script>

<!-- ── KPI CARDS ── -->
<div class="kpi-grid">
	<div class="kpi-card kpi-voters">
		<div class="kpi-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6;"><i class="fa-solid fa-users"></i></div>
		<div class="kpi-val" style="color:#3b82f6;"><?php echo number_format($totalVoters); ?></div>
		<div class="kpi-label">Registered Voters</div>
	</div>
	<div class="kpi-card kpi-trv">
		<div class="kpi-icon" style="background:rgba(139,92,246,0.15);color:#8b5cf6;"><i class="fa-solid fa-person-walking"></i></div>
		<div class="kpi-val" style="color:#8b5cf6;"><?php echo number_format($trv); ?></div>
		<div class="kpi-label">Est. Turnout (80%)</div>
	</div>
	<div class="kpi-card kpi-mce">
		<div class="kpi-icon" style="background:rgba(220,38,38,0.15);color:#dc2626;"><i class="fa-solid fa-user-shield"></i></div>
		<div class="kpi-val" style="color:#dc2626;"><?php echo number_format($totalMCE); ?></div>
		<div class="kpi-label">MCE Members</div>
		<span class="kpi-badge" style="background:rgba(220,38,38,0.15);color:#dc2626;"><?php echo $trv > 0 ? round($totalMCE/$trv*100,1) : 0; ?>%</span>
	</div>
	<div class="kpi-card kpi-bce">
		<div class="kpi-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b;"><i class="fa-solid fa-star"></i></div>
		<div class="kpi-val" style="color:#f59e0b;"><?php echo number_format($totalBCE); ?></div>
		<div class="kpi-label">BCE Members</div>
		<span class="kpi-badge" style="background:rgba(245,158,11,0.15);color:#f59e0b;"><?php echo $trv > 0 ? round($totalBCE/$trv*100,1) : 0; ?>%</span>
	</div>
	<div class="kpi-card kpi-hl">
		<div class="kpi-icon" style="background:rgba(6,182,212,0.15);color:#06b6d4;"><i class="fa-solid fa-house"></i></div>
		<div class="kpi-val" style="color:#06b6d4;"><?php echo number_format($totalHL); ?></div>
		<div class="kpi-label">House Leaders</div>
	</div>
	<div class="kpi-card kpi-members">
		<div class="kpi-icon" style="background:rgba(236,72,153,0.15);color:#ec4899;"><i class="fa-solid fa-user-group"></i></div>
		<div class="kpi-val" style="color:#ec4899;"><?php echo number_format($totalHLC); ?></div>
		<div class="kpi-label">HLC Members</div>
	</div>
</div>

<!-- ── CHARTS ── -->
<div class="charts-row">
	<div class="chart-card">
		<h6><i class="fa-solid fa-chart-pie me-1"></i> Organization Breakdown</h6>
		<canvas id="orgPieChart" height="220"></canvas>
	</div>
	<div class="chart-card">
		<h6><i class="fa-solid fa-chart-bar me-1"></i> Organization vs Target</h6>
		<canvas id="orgBarChart" height="220"></canvas>
	</div>
</div>

<?php
	// ── Build row data arrays for both chart and table ──
	list($rowQuery, $rowFilter) = buildRowQuery($link, $municipality, $isMun, $mun, $d1, $d2);
	$rowLabel = $isMun ? "barangay" : "city_mun";

	$rows = [];
	while ($r = $rowQuery->fetch_array(MYSQLI_BOTH)) {
		$name = $r[0];
		$filterVal = $name;
		$escFilter = $link->real_escape_string($filterVal);
		$currFilter = str_replace("?", "'$escFilter'", $rowFilter);

		$tv = safeCount($link, "SELECT COUNT(*) FROM voters v WHERE $currFilter");

		$trvR   = round($tv * 0.8);
		$etoR   = round($trvR * 0.65);
		$solidR = round($trvR * 0.55);

		$mce = safeCount($link, "SELECT COUNT(*) FROM mce m, voters v WHERE m.vin=v.vin AND $currFilter");
		$bce = safeCount($link, "SELECT COUNT(*) FROM bce b, voters v WHERE b.vin=v.vin AND $currFilter");
		$hl = safeCount($link, "SELECT COUNT(*) FROM hl h, voters v WHERE h.vin=v.vin AND $currFilter");
		$hlc = safeCount($link, "SELECT COUNT(*) FROM hl_children hlc, voters v WHERE hlc.vin=v.vin AND $currFilter");
		$prec = safeCount($link, "SELECT COUNT(DISTINCT v.precinct) FROM voters v WHERE $currFilter AND v.precinct <> 'no precinct'");

		$orgTot = $mce + $bce + $hl + $hlc;
		$pctR = $trvR > 0 ? round($orgTot / $trvR * 100, 1) : 0;
		$tr = $pctR >= 55;

		$rows[] = compact('name','tv','trvR','etoR','solidR','mce','bce','hl','hlc','prec','orgTot','pctR','tr');
	}

	// Totals
	$tot = ['tv'=>0,'trvR'=>0,'etoR'=>0,'solidR'=>0,'mce'=>0,'bce'=>0,'hl'=>0,'hlc'=>0,'prec'=>0,'orgTot'=>0];
	foreach ($rows as $row) foreach ($tot as $k => &$v) $v += $row[$k];
	$tot['pctR'] = $tot['trvR'] > 0 ? round($tot['orgTot'] / $tot['trvR'] * 100, 1) : 0;

	$chartLabels = json_encode(array_column($rows, 'name'));
	$chartMce    = json_encode(array_column($rows, 'mce'));
	$chartBce    = json_encode(array_column($rows, 'bce'));
	$chartHl     = json_encode(array_column($rows, 'hl'));
	$chartHlc    = json_encode(array_column($rows, 'hlc'));
?>

<!-- ── DATA TABLE ── -->
<div class="sr-table-wrap">
	<div class="sr-table-header">
		<h6><i class="fa-solid fa-table me-2 text-danger"></i>
			<?php echo $isMun ? "Barangay-Level Breakdown — $mun_" : "Municipality-Level Breakdown — $mun_"; ?>
		</h6>
		<span class="badge" style="background:rgba(127,29,29,0.2);color:#dc2626;font-size:11px;"><?php echo count($rows); ?> rows</span>
	</div>
	<div class="table-responsive">
	<table class="sr-table">
		<thead>
			<tr>
				<th>#</th>
				<th style="text-align:left;"><?php echo $isMun ? 'BARANGAY' : 'MUNICIPALITY'; ?></th>
				<th title="Total Registered Voters">TRV</th>
				<th title="Estimated Turnout (80%)">ETO</th>
				<th title="Target Votes (65% of ETO)">TARGET</th>
				<th title="Solid Vote Target (55%)">SOLID</th>
				<th title="No. of Precincts">PREC</th>
				<th title="MCE Members">MCE</th>
				<th title="BCE Members">BCE</th>
				<th title="House Leaders">HL</th>
				<th title="HLC Members">HLC</th>
				<th title="Total Organization">TOTAL ORG</th>
				<th title="Organization Rate %">RATE</th>
				<th title="Status">STATUS</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($rows as $i => $row): ?>
		<tr>
			<td><?php echo $i+1; ?></td>
			<td><?php echo htmlspecialchars($row['name']); ?></td>
			<td><?php echo number_format($row['tv']); ?></td>
			<td><?php echo number_format($row['trvR']); ?></td>
			<td><?php echo number_format($row['etoR']); ?></td>
			<td><?php echo number_format($row['solidR']); ?></td>
			<td><?php echo $row['prec']; ?></td>
			<td><strong style="color:#dc2626;"><?php echo number_format($row['mce']); ?></strong></td>
			<td><strong style="color:#f59e0b;"><?php echo number_format($row['bce']); ?></strong></td>
			<td><strong style="color:#06b6d4;"><?php echo number_format($row['hl']); ?></strong></td>
			<td><strong style="color:#ec4899;"><?php echo number_format($row['hlc']); ?></strong></td>
			<td>
				<strong><?php echo number_format($row['orgTot']); ?></strong>
				<div class="progress-bar-wrap">
					<div class="progress-bar-fill" style="width:<?php echo min($row['pctR'], 100); ?>%"></div>
				</div>
			</td>
			<td><?php echo $row['pctR']; ?>%</td>
			<td><?php echo $row['tr'] ? "<span class='badge-tr'>✓ TR</span>" : "<span class='badge-tnr'>✗ TNR</span>"; ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="2">TOTALS</td>
				<td><?php echo number_format($tot['tv']); ?></td>
				<td><?php echo number_format($tot['trvR']); ?></td>
				<td><?php echo number_format($tot['etoR']); ?></td>
				<td><?php echo number_format($tot['solidR']); ?></td>
				<td><?php echo number_format($tot['prec']); ?></td>
				<td><?php echo number_format($tot['mce']); ?></td>
				<td><?php echo number_format($tot['bce']); ?></td>
				<td><?php echo number_format($tot['hl']); ?></td>
				<td><?php echo number_format($tot['hlc']); ?></td>
				<td><?php echo number_format($tot['orgTot']); ?></td>
				<td><?php echo $tot['pctR']; ?>%</td>
				<td><?php echo $tot['pctR'] >= 55 ? "<span class='badge-tr'>✓ OTR</span>" : "<span class='badge-tnr'>✗ OTNR</span>"; ?></td>
			</tr>
		</tfoot>
	</table>
	</div>

	<!-- Legend -->
	<div class="legend-grid">
		<div class="legend-item"><div class="legend-dot" style="background:#475569"></div><strong>TRV</strong> — Total Registered Voters</div>
		<div class="legend-item"><div class="legend-dot" style="background:#8b5cf6"></div><strong>ETO</strong> — Estimated Turnout (80%)</div>
		<div class="legend-item"><div class="legend-dot" style="background:#374151"></div><strong>TARGET</strong> — 65% of ETO</div>
		<div class="legend-item"><div class="legend-dot" style="background:#dc2626"></div><strong>MCE</strong> — Mun. Campaign Executive</div>
		<div class="legend-item"><div class="legend-dot" style="background:#f59e0b"></div><strong>BCE</strong> — Brgy. Campaign Executive</div>
		<div class="legend-item"><div class="legend-dot" style="background:#06b6d4"></div><strong>HL</strong> — House Leader</div>
		<div class="legend-item"><div class="legend-dot" style="background:#ec4899"></div><strong>HLC</strong> — House Leader Children</div>
		<div class="legend-item"><div class="legend-dot" style="background:#10b981"></div><strong>TR</strong> — Target Reached (≥55%)</div>
		<div class="legend-item"><div class="legend-dot" style="background:#ef4444"></div><strong>TNR</strong> — Target Not Reached</div>
	</div>
</div>

</div>

<!-- Chart.js CDN -->
<script src="assets/chartjs/chart.js"></script>
<script>
Chart.defaults.color = '#94a3b8';
Chart.defaults.font.family = "'Outfit', sans-serif";

// ── Donut Chart ──
new Chart(document.getElementById('orgPieChart'), {
	type: 'doughnut',
	data: {
		labels: ['MCE', 'BCE', 'HL', 'HLC'],
		datasets: [{
			data: [<?php echo $totalMCE; ?>, <?php echo $totalBCE; ?>, <?php echo $totalHL; ?>, <?php echo $totalHLC; ?>],
			backgroundColor: ['#dc2626','#10b981','#f59e0b','#06b6d4','#ec4899'],
			borderWidth: 3,
			borderColor: 'transparent',
			hoverOffset: 10
		}]
	},
	options: {
		responsive: true,
		cutout: '68%',
		plugins: {
			legend: {
				position: 'bottom',
				labels: { padding: 16, font: { size: 11 } }
			},
			tooltip: {
				callbacks: {
					label: ctx => ` ${ctx.label}: ${ctx.parsed.toLocaleString()}`
				}
			}
		}
	}
});

// ── Stacked Bar Chart ──
new Chart(document.getElementById('orgBarChart'), {
	type: 'bar',
	data: {
		labels: <?php echo $chartLabels; ?>,
		datasets: [
			{ label: 'MCE', data: <?php echo $chartMce; ?>, backgroundColor: '#dc2626', borderRadius: 3 },
			{ label: 'BCE', data: <?php echo $chartBce; ?>, backgroundColor: '#f59e0b', borderRadius: 3 },
			{ label: 'HL',  data: <?php echo $chartHl;  ?>, backgroundColor: '#06b6d4', borderRadius: 3 },
			{ label: 'HLC', data: <?php echo $chartHlc; ?>, backgroundColor: '#ec4899', borderRadius: 3 }
		]
	},
	options: {
		responsive: true,
		interaction: { mode: 'index', intersect: false },
		scales: {
			x: {
				stacked: true,
				ticks: { font: { size: 10 }, maxRotation: 45 },
				grid: { color: 'rgba(255,255,255,0.05)' }
			},
			y: {
				stacked: true,
				ticks: { font: { size: 10 } },
				grid: { color: 'rgba(255,255,255,0.05)' }
			}
		},
		plugins: {
			legend: { position: 'top', labels: { font: { size: 11 }, padding: 12 } }
		}
	}
});
</script>

<?php include 'footer.php';?>