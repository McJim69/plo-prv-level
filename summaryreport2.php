<?php
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

	$trv      = round($totalVoters * 0.8);
	$eto      = round($trv * 0.65);
	$solid    = round($trv * 0.55);
	$solTarget = round($trv * 0.10);
	$orgTotal = $totalMCE + $totalBCE + $totalHL + $totalHLC;
	$pct      = $trv > 0 ? round($orgTotal / $trv * 100, 1) : 0;
?>

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
    if (!sel) return; // Protects against silent JS thread crashes!
    var opts = sel.querySelectorAll('option[data-district]');

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

<script>
// Run only AFTER the entire DOM structure has completely loaded
document.addEventListener("DOMContentLoaded", function() {
    // Safely check if the filter element exists on the active page layout
    if (document.getElementById('munSelect')) {
        filterMunOptions(currentDistrict);
    }
});
</script>