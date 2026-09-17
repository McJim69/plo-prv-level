<?php
    require("connect-sqli.php");
    require("head.php");

    $value = isset($_GET['value']) ? $_GET['value'] : "";

    $mun = "";
    if(isset($_GET["municipality"]) && $_GET["municipality"]!="All municipality" && $_GET["municipality"]!="")
        $mun = " and city_mun='" . $_GET["municipality"] . "'";

    $bar = "";
    if(isset($_GET["barangay"]) && $_GET["barangay"]!="All barangays" && $_GET["barangay"]!="")
        $bar = " and barangay='" . $_GET["barangay"] . "'";

    $prec = "";
    if(isset($_GET["precinct"]) && $_GET["precinct"]!="All precincts" && $_GET["precinct"]!="")
        $prec = " and precinct='" . $_GET["precinct"] . "'";

    $ato = "";
    if(isset($_GET["ato"]) && $_GET["ato"]!="Ato o dili -All" && $_GET["ato"]!="")
        $ato = " and ato='" . $_GET["ato"] . "'";

    $_4p = "";
    if(isset($_GET["_4p"]) && $_GET["_4p"]!="4Ps member -All" && $_GET["_4p"]!="")
        $_4p = " and _4p='" . $_GET["_4p"] . "'";

    $lit = "";
    if(isset($_GET["lit"]) && $_GET["lit"]!="Literacy-All" && $_GET["lit"]!="")
        $lit = " and lit='" . $_GET["lit"] . "'";

    if(isset($_POST["b_search"])){
        $value = $_POST["t_search"];
    }

    $rec = 200;
    $p = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if($p > 1){
        $to = $p * $rec;
        $from = $to - $rec;
        $i = $to + 1 - $rec;
    } else {
        $to = $rec;
        $from = 0;
        $i = 1;
        $p = 1;
    }

    $qry1 = Q("SELECT * FROM voters WHERE 
        (vname LIKE '%".$value."%' OR
        remarks LIKE '%".$value."%' OR
        ato LIKE '%".$value."%' OR
        vin LIKE '%".$value."%' OR
        lit LIKE '%".$value."%' OR
        birth LIKE '%".$value."%' OR
        sex LIKE '%".$value."%') $bar $prec $ato $_4p $lit $mun ") or die(mysqli_error($link));

    $qry = Q("SELECT * FROM voters WHERE 
        (vname LIKE '%".$value."%' OR
        remarks LIKE '%".$value."%' OR
        ato LIKE '%".$value."%' OR
        vin LIKE '%".$value."%' OR
        lit LIKE '%".$value."%' OR
        birth LIKE '%".$value."%' OR
        sex LIKE '%".$value."%') $bar $prec $ato $_4p $lit $mun
    ORDER BY vname LIMIT $from,$to ") or die(mysqli_error($link));
?>

<style>

/* Screen Layout (Hide print header by default) */
#header {
    display: none;
}

/* Print Layout Override */
@media print {
    #header {
        display: block !important;
    }
    
    /* Automatically hide all web management elements */
    #trcontrols, .glass-panel, #nav, #topbar, #header-inner-pages {
        display: none !important;
    }
}

#trcontrols select{
	text-align:left;padding:7px !important;
}

</style>	
	
<?php require("menu.php"); ?>

<script>setActive("voters");</script>

<?php
// 1. Centralize and Sanitize all input states with uniform fallbacks
$get_municipality = $_GET["municipality"] ?? "";
$get_barangay     = $_GET["barangay"] ?? "";
$get_precinct     = $_GET["precinct"] ?? "";
$get_ato          = $_GET["ato"] ?? "";
$get_4p           = $_GET["_4p"] ?? "";
$get_lit          = $_GET["lit"] ?? "";
$value            = $value ?? ""; // Handled from your search calculations

// Calculate total pages safely based on total records found
$total_rows  = isset($qry1) ? mysqli_num_rows($qry1) : 0;
$total_pages = ($total_rows > 0) ? ceil($total_rows / $rec) : 1;
?>

<!-- Javascript Utility Helper Functions -->
<script>
if (typeof urlencode !== 'function') {
    function urlencode(str) {
        return encodeURIComponent(str || '');
    }
}
function updateFilters(key, val) {
    let params = new URLSearchParams(window.location.search);
    params.set(key, val);
    if (key === 'municipality') {
        params.delete('barangay');
        params.delete('precinct');
    } else if (key === 'barangay') {
        params.delete('precinct');
    }
    params.set('page', '1'); // Reset to page 1 on filter shifts
    jump('voterslist.php?' + params.toString());
}
</script>

<div class="container-fluid main-content">
	<div id="trcontrols" class="d-flex justify-content-center align-items-center flex-wrap gap-2 glass-panel p-2 rounded-3 shadow-lg d-print-none">
		<div class="d-flex justify-content-center flex-wrap gap-2 align-items-center">
			<form method="post" class="d-flex gap-2 align-items-center m-0">
				<input  type='text' class='form-control d-inline-block w-auto' name="t_search" id="t_search" value="<?php echo htmlspecialchars($value); ?>" size="30" placeholder="Type a keyword" />
				<button type='submit' class='btn btn-success btn-lg' name='b_search'><i class="fa fa-search"></i></button>
				<button type='button' class='btn btn-primary btn-lg' onclick="document.getElementById('t_search').value='';jump('voterslist.php')"><i class="fa fa-sync"></i></button>
				<button type='button' class='btn btn-danger btn-lg' onclick="printF()"><i class="fa fa-print"></i></button>
			</form>

			<select class='btn btn-primary d-inline-block w-auto' onchange="if(this.value=='All municipality') jump('voterslist.php'); else updateFilters('municipality', this.value)">
				<option value="All municipality">All municipality</option>
				<?php
					$qry_mun = Q("SELECT city_mun FROM voters WHERE city_mun <> '' GROUP BY city_mun ORDER BY city_mun") or d;
					while($rs_mun = fetch($qry_mun)){
						$selected = ($get_municipality === $rs_mun[0]) ? "selected" : "";
						echo "<option value='".htmlspecialchars($rs_mun[0])."' $selected>".htmlspecialchars($rs_mun[0])."</option>";
					}
				?>
			</select>

			<select class='btn btn-primary w-auto' onchange="updateFilters('barangay', this.value)">
				<option value="All barangays">All barangays</option>
				<?php
					$safe_mun = addslashes($get_municipality);
					$qry_bar = Q("SELECT barangay FROM voters WHERE city_mun LIKE '$safe_mun%' AND barangay <> '' GROUP BY barangay ORDER BY barangay") or d;
					while($rs_bar = fetch($qry_bar)){
						$selected = ($get_barangay === $rs_bar[0]) ? "selected" : "";
						echo "<option value='".htmlspecialchars($rs_bar[0])."' $selected>".htmlspecialchars($rs_bar[0])."</option>";
					}
				?>
			</select>

			<select class='btn btn-primary d-inline-block w-auto' onchange="updateFilters('precinct', this.value)">
				<option value="">All precincts</option>
				<?php
					if (empty($get_barangay) || $get_barangay === "All barangays") {
						$qry_pre = Q("SELECT precinct FROM voters WHERE precinct <> '' GROUP BY precinct ORDER BY precinct") or d;										
					} else {
						$safe_bar = addslashes($get_barangay);
						$qry_pre = Q("SELECT precinct FROM voters WHERE barangay='$safe_bar' AND precinct <> '' GROUP BY precinct ORDER BY precinct") or d;
					}
					while ($rs_pre = fetch($qry_pre)) {
						$selected = ($get_precinct === $rs_pre[0]) ? "selected" : "";
						echo "<option value='" . htmlspecialchars($rs_pre[0]) . "' $selected>" . htmlspecialchars($rs_pre[0]) . "</option>";
					}
				?>
			</select>

			<select class='btn btn-primary d-inline-block w-auto' onchange="updateFilters('ato', this.value)">
				<option value="">Ato -All</option>
				<?php
					$qry_ato = Q("SELECT ato FROM voters WHERE ato <> '' GROUP BY ato ORDER BY ato") or d;
					while($rs_ato = fetch($qry_ato)){
						$selected = ($get_ato === $rs_ato[0]) ? "selected" : "";
						echo "<option value='".htmlspecialchars($rs_ato[0])."' $selected>".htmlspecialchars($rs_ato[0])."</option>";
					}
				?>
			</select>

			<select class='btn btn-primary d-inline-block w-auto' onchange="updateFilters('_4p', this.value)">
				<option value="">4Ps -All</option>
				<option value="Yes" <?php echo ($get_4p === "Yes") ? "selected" : ""; ?>>Yes</option>
				<option value="No" <?php echo ($get_4p === "No") ? "selected" : ""; ?>>No</option>
			</select>

			<select class='btn btn-primary d-inline-block w-auto' onchange="updateFilters('lit', this.value)">
				<option value="Literacy-All" <?php echo ($get_lit === "Literacy-All" || $get_lit === "") ? "selected" : ""; ?>>Literacy-All</option>
				<option value="Literate" <?php echo ($get_lit === "Literate") ? "selected" : ""; ?>>Literate</option>
				<option value="Illiterate" <?php echo ($get_lit === "Illiterate") ? "selected" : ""; ?>>Illiterate</option>
				<option value="Disabled" <?php echo ($get_lit === "Disabled") ? "selected" : ""; ?>>Disabled</option>
			</select>
		</div>
	</div>

	<!-- Pagination Toolbar -->
	<div class="container d-flex justify-content-between align-items-center flex-wrap gap-2 glass-panel p-2 rounded-3 shadow-lg d-print-none">
		<div class="text-muted small fw-semibold">
			Showing page <span class="text-primary fw-bold"><?php echo $p; ?></span> of <span class="fw-bold"><?php echo $total_pages; ?></span> pages 
			<span class="text-secondary mx-1">•</span> 
			Total Records: <span class="badge bg-secondary px-2 py-1"><?php echo number_format($total_rows, 0); ?></span>
		</div>
		
		<nav aria-label="Voter list pagination">
			<ul class="pagination pagination-sm m-0 shadow-xs">
				<li class="page-item <?php if($p <= 1) echo 'disabled'; ?>">
					<button class="page-link px-2.5" onclick="jump('?municipality=<?php echo urlencode($get_municipality); ?>&barangay=<?php echo urlencode($get_barangay); ?>&precinct=<?php echo urlencode($get_precinct); ?>&ato=<?php echo urlencode($get_ato); ?>&_4p=<?php echo urlencode($get_4p); ?>&lit=<?php echo urlencode($get_lit); ?>&value=<?php echo urlencode($value); ?>&page=1')" title="First Page">
						<i class="fa-solid fa-angles-left small"></i>
					</button>
				</li>
				
				<li class="page-item <?php if($p <= 1) echo 'disabled'; ?>">
					<button class="page-link" onclick="jump('?municipality=<?php echo urlencode($get_municipality); ?>&barangay=<?php echo urlencode($get_barangay); ?>&precinct=<?php echo urlencode($get_precinct); ?>&ato=<?php echo urlencode($get_ato); ?>&_4p=<?php echo urlencode($get_4p); ?>&lit=<?php echo urlencode($get_lit); ?>&value=<?php echo urlencode($value); ?>&page=<?php echo ($p - 1); ?>')">
						<i class="fa-solid fa-angle-left me-1"></i> Prev
					</button>
				</li>

				<?php 
				$start_loop = max(1, $p - 2);
				$end_loop = min($total_pages, $p + 2);
				
				for ($m = $start_loop; $m <= $end_loop; $m++): 
					$active_class = ($m == $p) ? 'active fw-bold' : '';
				?>
					<li class="page-item <?php echo $active_class; ?>">
						<button class="page-link px-3" onclick="jump('?municipality=<?php echo urlencode($get_municipality); ?>&barangay=<?php echo urlencode($get_barangay); ?>&precinct=<?php echo urlencode($get_precinct); ?>&ato=<?php echo urlencode($get_ato); ?>&_4p=<?php echo urlencode($get_4p); ?>&lit=<?php echo urlencode($get_lit); ?>&value=<?php echo urlencode($value); ?>&page=<?php echo $m; ?>')">
							<?php echo $m; ?>
						</button>
					</li>
				<?php endfor; ?>
				
				<li class="page-item <?php if($p >= $total_pages) echo 'disabled'; ?>">
					<button class="page-link" onclick="jump('?municipality=<?php echo urlencode($get_municipality); ?>&barangay=<?php echo urlencode($get_barangay); ?>&precinct=<?php echo urlencode($get_precinct); ?>&ato=<?php echo urlencode($get_ato); ?>&_4p=<?php echo urlencode($get_4p); ?>&lit=<?php echo urlencode($get_lit); ?>&value=<?php echo urlencode($value); ?>&page=<?php echo ($p + 1); ?>')">
						Next <i class="fa-solid fa-angle-right ms-1"></i>
					</button>
				</li>
				
				<li class="page-item <?php if($p >= $total_pages) echo 'disabled'; ?>">
					<button class="page-link px-2.5" onclick="jump('?municipality=<?php echo urlencode($get_municipality); ?>&barangay=<?php echo urlencode($get_barangay); ?>&precinct=<?php echo urlencode($get_precinct); ?>&ato=<?php echo urlencode($get_ato); ?>&_4p=<?php echo urlencode($get_4p); ?>&lit=<?php echo urlencode($get_lit); ?>&value=<?php echo urlencode($value); ?>&page=<?php echo $total_pages; ?>')" title="Last Page">
						<i class="fa-solid fa-angles-right small"></i>
					</button>
				</li>
			</ul>
		</nav>
		
		<div class="d-flex align-items-center gap-2 small fw-semibold text-muted">
			<label for="s_pn" class="m-0 text-nowrap"><i class="fa-solid fa-arrow-right-to-bracket text-secondary me-1"></i> Goto page:</label>
			<select class='form-select form-select-sm border-secondary-subtle bg-body-tertiary fw-bold text-center' id='s_pn' style="width: 70px;" onchange="jump('?municipality=<?php echo urlencode($get_municipality); ?>&barangay=<?php echo urlencode($get_barangay); ?>&precinct=<?php echo urlencode($get_precinct); ?>&ato=<?php echo urlencode($get_ato); ?>&_4p=<?php echo urlencode($get_4p); ?>&lit=<?php echo urlencode($get_lit); ?>&value=<?php echo urlencode($value); ?>&page='+this.value)">
				<?php
				for($m = 1; $m <= $total_pages; $m++) {
					$p_sel = ($m == $p) ? "selected" : "";
					echo "<option value='$m' $p_sel>$m</option>";
				}
				?>
			</select>
		</div>
	</div>
</div>

<?php
// 1. Safely initialize parameters with fallback defaults
$print_municipality = $_GET["municipality"] ?? "";
$print_barangay     = $_GET["barangay"] ?? "";
$print_precinct     = $_GET["precinct"] ?? "";

// Format the display title strings
$mun_display = (!empty($print_municipality) && $print_municipality !== "All municipality") 
    ? htmlspecialchars(strtoupper($print_municipality)) 
    : "ALL MUNICIPALITIES";
?>

<div id='header' class="d-none text-center">
	LIST OF REGISTERED VOTERS<br>
	<b>MUNICIPALITY OF <?php echo $mun_display; ?></b>
	
	<?php
		if (!empty($print_barangay) && $print_barangay !== "All barangays") {
			echo "<br>BARANGAY " . htmlspecialchars(strtoupper($print_barangay));
		}
		if (!empty($print_precinct) && $print_precinct !== "All precincts") {
			echo "<br>PRECINCT " . htmlspecialchars(strtoupper($print_precinct));
		}
	?>
	<hr>
</div>

<div class="table-responsive" style="padding-right:20px;margin-left:20px">
	<table class='table table-striped table-hover table-bordered' width=100% id="tab">		
	<tr>
		<th>No.</th>
		<th>NAME OF VOTERS</th>
		<th>VIN</th>
		<th>PRECINCT</th>
		<th>SEQ.</th>
		<th>ADDRESS</th>
		<th class="hid">SEX</th>
		<th>BIRTH DATE</th>
		<th>AGE</th>
		<th class='hid'>LIT</th>
		<th class='hid'>4P's?</th>
		<th class='hid'>ATO?</th>
		<th class='hid'>CITY_MUN.</th>
		<th class='hid'>BARANGAY</th>
	</tr>
	
	<?php
		$val=strtoupper($value);
		$rep="<b>$value</b>";
		$ctr=1;
					
		while($rs=mysqli_fetch_array($qry)){
			if($ctr<=$rec){

				$birthDate = $rs["birth"];;
				$birthDate = explode("-", $birthDate);
				$age = (date("md", date("U", mktime(0, 0, 0, $birthDate[1], $birthDate[2], $birthDate[0])))> date("md") ? ((date("Y")-$birthDate[0])-0):(date("Y")-$birthDate[0]));
				
				if($i%2==0)
					echo "<tr class='odd' id='tr_".$rs[0]."'>";
				else
					echo "<tr class='even' id='tr_".$rs[0]."'>";
				
				
					if(isset($_POST["b_search"])){
						echo"<td><onclick=\"('".$rs[0]."');\" /> $i.</td>";
						echo"<td>".str_replace($val,"$rep",str_replace("¥","Ñ",$rs["vname"]))."</td>";
						echo"
						<td>".str_replace($val,"$rep".$value."</b>",$rs["vin"])."</td>
						<td>".str_replace($val,"$rep",$rs["precinct"])."</td>
						<td>".str_replace($val,"$rep".$value."</b>",$rs["seq"])."</td>
						<td>".str_replace($val,"$rep",$rs["address"])."</td>
						<td  class='hid'>".str_replace($val,"$rep",$rs["sex"])."</td>
						<td>".str_replace($val,"$rep",$rs["birth"])."</td>
						<td>$age</td>";
						IF($rs["lit"]!="Literate")
							echo"<td _class='hid'>".str_replace($val,"$rep",$rs["lit"])."</td>";
						else
							echo"<td _class='hid'></td>";
						echo"
						<td class='hid'>";
							if($rs["_4p"]=="" || $rs["_4p"]=="0")
								echo "-";
							else
								echo $rs["_4p"];
						echo"</td>
						<td class='hid'>";
							$qryxx1=$link->query("select * from mce where vin='".$rs["vin"]."'")or die(mysqli_error($link));
							$qryxx2=$link->query("select * from bce where vin='".$rs["vin"]."'")or die(mysqli_error($link));
							$qryxx3=$link->query("select * from hl where vin='".$rs["vin"]."'")or die(mysqli_error($link));
							$qryxx4=$link->query("select * from pc where vin='".$rs["vin"]."'")or die(mysqli_error($link));
							if($rsxx=mysqli_fetch_array($qryxx1))
								echo "Sure-MCE";
							else if($rsxx=mysqli_fetch_array($qryxx2))
								echo "Sure-BCE";
							else if($rsxx=mysqli_fetch_array($qryxx3))
								echo "Sure-HL";
							else if($rsxx=mysqli_fetch_array($qryxx4))
								echo "Sure-MC";
							else{
								if($rs["ato"]=="" || $rs["ato"]=="0")
									echo "-";
								else
									echo $rs["ato"];
							}
						echo"</td>
						<td  class='hid'>".str_replace($val,"$rep",$rs["city_mun"])."</td>
						<td class='hid'>".str_replace($val,"$rep",$rs["barangay"])."</td>";
					}
					else{
						echo"<td>$i.</td>";
						echo"<td>".str_replace($val,"$rep".$value."</b>",$rs["vname"])."</td>";
						echo"
						<td>".str_replace($val,"$rep".$value."</b>",$rs["vin"])."</td>
						<td>".str_replace($val,"$rep".$value."</b>",$rs["precinct"])."</td>
						<td>".str_replace($val,"$rep".$value."</b>",$rs["seq"])."</td>
						<td>".str_replace($val,"$rep".$value."</b>",$rs["address"])."</td>
						<td  class='hid'>".$rs["sex"]."</td>
						<td>".str_replace($val,"$rep".$value."</b>",$rs["birth"])."</td>";
						echo"
						<td>$age</td>";
						IF($rs["lit"]!="Literate")
							echo"<td _class='hid'>".str_replace($val,"$rep",$rs["lit"])."</td>";
						else
							echo"<td _class='hid'></td>";
						echo"
						<td class='hid'>";
							if($rs["_4p"]=="" || $rs["_4p"]=="0")
								echo "-";
							else
								echo $rs["_4p"];
						echo"</td>
						<td class='hid'>";
							if($rs["ato"]=="" || $rs["ato"]=="0")
								echo "-";
							else
								echo $rs["ato"];
						echo"</td>
						<td  class='hid'>".$rs["city_mun"]."</td>
						<td class='hid'>".$rs["barangay"]."</td>";
					}
				echo"
				</tr>";			
				}else{
					break;
				}
			$i++;
			$ctr++;
			}
		?>
	</table>
</div>

<Script>
	function printF() {
		// 1. Force the print module setup
		window.print();
	}
</script>

<script>
	function deleteVoter(vin){	
		if(confirm("Are you sure?")){
			xmlhttp.onreadystatechange=function()
			{
				if (xmlhttp.readyState==4 && xmlhttp.status==200){
					//alert(xmlhttp.responseText);
					if(xmlhttp.responseText=="Success"){
						$("#tr_"+vin).animate({
							opacity:0
						},500);
					}else{
						alert(xmlhttp.responseText);
					}
				}
			}						
			xmlhttp.open("GET","ajax/deletevoter.php?vin="+vin,true);
			xmlhttp.send();
		}
	}
</script>

<?php include 'footer.php';?>

