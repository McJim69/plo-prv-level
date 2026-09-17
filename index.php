<?php
	session_start();
	require("connect-sqli.php");
	require("head.php");
 	require("menu.php");	

	$ex = Q("select * from voters limit 0,1");
	if($ex) {
		$rs = fetch($ex);
		if($rs) {
			$_SESSION["city_mun"] = $rs["city_mun"];
		}
	}

	function renderCarousel($id, $folder, $count = 18) {
		// Paggamit sa SITE_VERSION isip cache buster sa mga static files
		$v = defined('SITE_VERSION') ? SITE_VERSION : time();
		
		echo "<div id='$id' class='carousel slide carousel-fade shadow-lg overflow-hidden border border-4 border-white' data-bs-ride='carousel' style='border-radius:15px'>";
		echo "<div class='carousel-inner'>";
		for($i=1; $i<=$count; $i++) {
			$activeClass = ($i == 1) ? 'active' : '';
			$src = "images/$folder/$i.jpg?v=$v";
			$alt = "Slide $i";
			
			// Luwas nga fallback: Gi-clear ang onerror handler (this.onerror=null) aron dili mag-infinite loop sa production
			$placeholder = "images/blank.jpg?v=$v"; 
			
			echo "<div class='carousel-item $activeClass'>";
			echo "<img style='aspect-ratio:3/2;width:100%' src='$src' class='d-block w-100' alt='$alt' onerror=\"this.onerror=null; this.src='$placeholder';\">";
			echo "</div>";
		}
		echo "</div>";
		echo "<button class='carousel-control-prev' type='button' data-bs-target='#$id' data-bs-slide='prev'>
				<span class='carousel-control-prev-icon' aria-hidden='true'></span>
				<span class='visually-hidden'>Previous</span>
			  </button>";
		echo "<button class='carousel-control-next' type='button' data-bs-target='#$id' data-bs-slide='next'>
				<span class='carousel-control-next-icon' aria-hidden='true'></span>
				<span class='visually-hidden'>Next</span>
			  </button>";
		echo "</div>";
	}	
?>

<script>setActive("home");</script>

<div class="container main-content">
  <div class="sr-hero card mb-4 shadow-lg text-center" style="padding:5px !important">
    <h3 class="display fw-bold pt-3 fs-2 text-white">
      Welcome, <?php echo isset($_SESSION["name"]) ? $_SESSION["name"] : "Guest"; ?>!
    </h3>
    <p class="col-md-8 mx-auto fs-6" style="color:#eee">PLO Database - Management Dashboard</p>
  </div>

  <!-- Dual Carousel -->
  <div class="row justify-content-center mb-5">
    <div class="col-lg-6">
      <?php renderCarousel("carouselSlides1", "slides1"); ?>
    </div>
    <div class="col-lg-6">
      <?php renderCarousel("carouselSlides2", "slides2"); ?>
    </div>
  </div>  
<?php require_once("summaryreport2.php"); ?>
</div>

<?php require_once("footer.php"); ?>
