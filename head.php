<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<script>
	// Initialize theme immediately to prevent flashing
	(function() {
		const savedTheme = localStorage.getItem('theme') || 'light';
		document.documentElement.setAttribute('data-theme', savedTheme);
		document.documentElement.setAttribute('data-bs-theme', savedTheme);
	})();
</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>PLO Database v.5.23</title>
<link href="images/logo.png" rel="shortcut icon">
<link href="assets/fontawesome/css/all.min.css?v=<?php echo SITE_VERSION; ?>" rel="stylesheet">
<link href="assets/fontawesome/css/font-awesome.min.css?v=<?php echo SITE_VERSION; ?>" rel="stylesheet">
<link href="assets/bootstrap/css/bootstrap.min.css?v=<?php echo SITE_VERSION; ?>" rel="stylesheet">
<link href="assets/customcss/stylesheet.css?v=<?php echo SITE_VERSION; ?>" rel="stylesheet" type="text/css"/>
<link href="assets/sweetalert2/dist/sweetalert2.min.css?v=<?php echo SITE_VERSION; ?>" rel="stylesheet" type="text/css"/>

<script src="assets/jquery/jquery.min.js?v=<?php echo SITE_VERSION; ?>" type="text/javascript"></script>
<script src="assets/sweetalert2/dist/sweetalert2.min.js?v=<?php echo SITE_VERSION; ?>" type="text/javascript" ></script>

<script>
	if (window.XMLHttpRequest)
		xmlhttp=new XMLHttpRequest();
	else
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");				
	function getID(id){
		return document.getElementById(id);
	}
	function conf(){
		return confirm("Are you Sure?");
	}
	function jump(page){
		window.location=page;
	}
	function setActive(id){
		getID(id).style.background="#b61212";
		getID(id).style.color="#fff";
		getID(id).style.fontWeight="bold";
	}
</script>

<script>
	function toggleTheme() {
		const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
		const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
		document.documentElement.setAttribute('data-theme', newTheme);
		document.documentElement.setAttribute('data-bs-theme', newTheme);
		localStorage.setItem('theme', newTheme);
		updateThemeButtonText(newTheme);
	}

	function updateThemeButtonText(theme) {
		const themeToggle = document.querySelector('.theme-toggle-btn');
		if (themeToggle) {
			themeToggle.setAttribute('title', theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode');
		}
	}

	jQuery(document).ready(function($) {
		const savedTheme = localStorage.getItem('theme') || 'light';
		updateThemeButtonText(savedTheme);
	});
</script>

</head>

<?php
	function jump($page){
		echo "<script>window.location='".$page."'</script>";
	}
	function Q($qry){
		global $link;
		return $link->query($qry);
	}
	function d($qry){
		global $link;
		return die(mysqli_error($link));
	}
	function fetch($qry){
		return mysqli_fetch_array($qry);
	}
?>	

<body>
