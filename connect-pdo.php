<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
	ini_set('log_errors', 1);
	ini_set('error_log', 'php-error.log');
	
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	// Always initialize session variables to prevent undefined index notices
	if (!isset($_SESSION["city_mun"])) {
		$_SESSION["city_mun"] = "";
	}

	// Enforce scoping: Admins and SuperAdmins can switch municipality/barangay via GET parameters.
	// Restricted users are locked to their profile assignment.
	if (isset($_SESSION["access"]) && ($_SESSION["access"] === 'SuperAdmin' || $_SESSION["access"] === 'Admin')) {
		if (isset($_GET["municipality"]) && $_GET["municipality"] !== "") {
			$_SESSION["city_mun"] = $_GET["municipality"];
		}
	} else if (isset($_SESSION["user"]) && $_SESSION["user"] !== "") {
		// Fetch assignment directly to prevent tampering
		$_SESSION["city_mun"] = isset($_SESSION["city_mun_assigned"]) ? $_SESSION["city_mun_assigned"] : $_SESSION["city_mun"];
	}

	require("config.php");
	
	try {
		$link = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_BOTH,
			PDO::ATTR_EMULATE_PREPARES => true, // Allows multiple queries and parameter binding in LIMIT
		]);
	} catch (PDOException $e) {
		die("ERROR: Could not connect. " . $e->getMessage());
	}

	// Authentication & Authorization Check
	$current_script = basename($_SERVER['SCRIPT_NAME']);
	$is_ajax = (strpos($_SERVER['SCRIPT_NAME'], '/ajax/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/scripts/') !== false);

	// 1. Enforce Authentication (except for login.php, entrance.php, and get_barangays.php)
	if ($current_script !== 'login.php' && $current_script !== 'entrance.php' && $current_script !== 'get_barangays.php') {
		if (!isset($_SESSION["user"]) || $_SESSION["user"] === "") {
			if ($is_ajax) {
				http_response_code(401);
				exit("Unauthorized access. Please log in.");
			} else {
				http_response_code(302);
				header("Location: login.php");
				exit();
			}
		}

		// 2. Enforce Role-Based Authorization
		$user_access = isset($_SESSION["access"]) ? $_SESSION["access"] : '';

		// SuperAdmin only actions: user management and database backup
		$superadmin_pages = ['users.php', 'usersupdate.php', 'backup.php'];
		$superadmin_ajax = ['addusers.php', 'deleteusers.php', 'getusers.php'];

		if (in_array($current_script, $superadmin_pages) || in_array($current_script, $superadmin_ajax)) {
			if ($user_access !== 'SuperAdmin') {
				if ($is_ajax) {
					http_response_code(403);
					exit("Forbidden. SuperAdmin access required.");
				} else {
					http_response_code(302);
					header("Location: index.php");
					exit();
				}
			}
		}

		// Admin / SuperAdmin only actions: modifying precinct leaders, household leaders, BCEs, etc.
		// (Excluding exit pool counter actions and read-only searches)
		$modification_ajax = [
			'addbce.php', 'deletebce.php', 'changebce.php',
			'addhl.php', 'deletehl.php', 'changehl.php', 'addhlmember.php', 'deletehlmember.php',
			'addmce.php', 'deletemce.php', 'changemce.php',
			'deletevoter.php'
		];

		if (in_array($current_script, $modification_ajax)) {
			if ($user_access !== 'SuperAdmin' && $user_access !== 'Admin') {
				http_response_code(403);
				exit("Forbidden. Administrative access required.");
			}
		}
	}
?>