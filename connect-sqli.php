<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
	ini_set('log_errors', 1);
	ini_set('error_log', 'php-error.log');
	
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}
	
	include 'config.php';

	$link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

	if ($link->connect_error) {
		die("Database engine communication failure: " . $conn->connect_error);
	}

	if (!isset($_SESSION["city_mun"])) {
		$_SESSION["city_mun"] = "";
	}
	
	if($_SESSION["user"]=="")
	echo"<script>window.location='entrance.php';</script>";	
?>



