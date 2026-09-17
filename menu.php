<div id="cssmenu-wrapper">
	<div id="cssmenu-container">
		<a href="index.php" class="brand-title">
			<i class="fa-solid fa-users-rectangle"></i> PLO <span>SYSTEM</span>
		</a>
		<div class="header-right-controls">
			<button onclick="toggleTheme()" class="theme-toggle-btn" aria-label="Toggle Theme">
				<i class="fa-solid fa-circle-half-stroke"></i>
			</button>
			
			<button class="menu-toggle-btn" id="menu-toggle-btn" aria-label="Toggle Navigation">
				<i class="fa-solid fa-bars"></i>
			</button>
		</div>

		<nav id="cssmenu">
			<?php
			if(isset($_SESSION['user'])){
				$muni = isset($_SESSION["city_mun"]) ? urlencode($_SESSION["city_mun"]) : "TABINA";
				
				echo "
				<ul>
					<li><a href='index.php' id='home' title='Home Page'><i class='fa-solid fa-house'></i> Home</a></li>
					<li><a href='voterslist.php' id='voters' title='Registered Voters'>Voters</a></li>
					<li><a href='mcelist.php' id='mce' title='MCE List'>MCE List</a></li>
					<li><a href='bcelist.php' id='bce' title='BCE List'>BCE List</a></li>
					<li><a href='hllist.php' id='hl' title='Household Leaders'>HH Leaders</a></li>
					<li><a href='hmlist.php' id='hm' title='Household Members'>HH Members</a></li>
					<li class='has-sub'>
						<a id='sum' href='#' onclick='return false;'>Reports <i class='fa-solid fa-chevron-down' style='font-size: 10px;'></i></a>
						<ul>
							<li><a href='voter_id_cards.php' id='cards'>Voter ID Cards</a></li>
							<li><a href='summaryreport.php' id='sum'>Summary Report</a></li>
							<li><a href='counter.php' id='counter'>Exit Pool Counter</a></li>
							<li><a href='hllist_grid.php' id='sumhl'>HL & Members List</a></li>
							<li><a href='hl-form-all.php?barangay=ABONG-ABONG' id='sumform'>HL & Members Form</a></li>
						</ul>
					</li>";
					
			if($_SESSION["access"] === "SuperAdmin"){
				echo"<li class='has-sub'>";
				echo"<a id='admin' href='#' onclick='return false;'>Admin <i class='fa-solid fa-chevron-down' style='font-size: 10px;'></i></a>";
				echo"	<ul>";
				echo"      <li><a href='users.php' id='users' title='Users'>Users</a></li>";
				echo"      <li><a href='backup.php' id='back'>Backup</a></li>";
				echo"	   <li><a onclick='switchDatabase()' title='PLO Records 2019' style='cursor:pointer;'>PLO 2019</a></li>";
				echo"	   <li><a onclick='sessionEnd()' title='Logout' style='cursor:pointer;'><i class='fa-solid fa-right-from-bracket'></i> Logout</a></li>";
				echo"	<ul>";
				echo"</li>";					}					
				echo"</ul>";
			}
			?>
		</nav>
	</div>
</div>

<div style="margin-top: 80px;"></div> <!-- Spacer to offset fixed header -->

<script>
	jQuery(document).ready(function($) {
		$('#menu-toggle-btn').click(function() {
			$('#cssmenu').slideToggle(200);
		});
		
		// Dropdown toggle on mobile
		if ($(window).width() <= 1200) {
			$('.has-sub > a').click(function(e) {
				e.preventDefault();
				$(this).siblings('ul').slideToggle(200);
			});
		}
	});

	function sessionEnd(){	
		if(confirm("Are you sure you want to Logout?")){
			window.location.href = 'logout.php';
		}
	}
	
	function switchDatabase(){	
		if(confirm("Switching to SK Voters Database requires you logout this page. Are you sure you want to Switch and Logout?")){
			window.location.href = 'switch.php';
		}
	}
</script>
