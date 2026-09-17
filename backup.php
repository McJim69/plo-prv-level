<?php
	require("connect-sqli.php");
	require("head.php");
	require("menu.php"); 
?>	

<script> setActive("back"); </script>

<div class="container py-5 main-content">
	<div class="text-center mb-5">
		<h1 class="display-6 fw-bold text-danger"><i class="fa-solid fa-database me-2"></i> Database Backup & Restore</h1>
		<p class="text-muted fs-5">Securely backup all database tables or restore from a previous archive</p>
	</div>

	<div class="row g-4 justify-content-center">
		<!-- Backup Card -->
		<div class="col-md-5">
			<div class="chart-card h-100 backup-card text-center p-4">
				<div class="card-body d-flex flex-column justify-content-between">
					<div>
						<div class="mb-4 text-primary">
							<i class="fa-solid fa-cloud-arrow-down fa-4x"></i>
						</div>
						<h3 class="card-title fw-bold mb-3">Backup Database</h3>
						<p class="text-muted card-text">Export all schema structures and records into SQL archive. Recommended before any major system upgrades.</p>
					</div>
					<form method="post" class="mt-4">
						<button type="submit" name="backup" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold" onclick="return confirm('Execute backup now?')">
							<i class="fa-solid fa-play me-2"></i> Execute Backup Now
						</button>
					</form>
				</div>
			</div>
		</div>

		<!-- Restore Card -->
		<div class="col-md-5">
			<div class="chart-card h-100 backup-card text-center p-4">
				<div class="card-body d-flex flex-column justify-content-between">
					<div>
						<div class="mb-4 text-success">
							<i class="fa-solid fa-cloud-arrow-up fa-4x"></i>
						</div>
						<h3 class="card-title fw-bold mb-3">Restore Database</h3>
						<p class="text-muted card-text">Upload a previously backed-up SQL archive (`.sql` or `.sql.gz`) to restore the database tables and structure.</p>
					</div>
					<div class="mt-4">
						<form method="post" enctype="multipart/form-data">
							<input type="file" name="file" id="file" onchange="$('#upload').click();" style="display:none" />
							<input type="submit" value="Submit" name="upload" id="upload" style="display:none" />
							<button type="button" class="btn btn-success btn-lg w-100 py-3 rounded-pill fw-bold" onclick="$('#file').click();">
								<i class="fa-solid fa-upload me-2"></i> Upload & Restore Now
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div style="margin-top:-50px"></div>

<?php
//BACKUP
if(isset($_POST["backup"])){

	define("BACKUP_DIR", 'BACKUP'); // Comment this line to use same script's directory ('.')
	define("TABLES", '*'); // Full backup

	//define("TABLES", 'table1, table2, table3'); // Partial backup
	
	define("CHARSET", 'utf8');
	define("GZIP_BACKUP_FILE", true); // Set to false if you want plain SQL backup files (not gzipped)
	define("DISABLE_FOREIGN_KEY_CHECKS", true); // Set to true if you are having foreign key constraint fails
	define("BATCH_SIZE", 1000); // Batch size when selecting rows from database in order to not exhaust system memory
								// Also number of rows per INSERT statement in backup file
	class Backup_Database {
		var $host;
		var $username;
		var $passwd;
		var $dbName;
		var $charset;
		var $conn;
		var $backupDir;
		var $backupFile;
		var $gzipBackupFile;
		var $output;
		var $disableForeignKeyChecks;
		var $batchSize;

		public function __construct($dbHost, $dbUser, $dbPass, $dbName, $charset = 'utf8') {
			$this->host                    = $dbHost;
			$this->username                = $dbUser;
			$this->passwd                  = $dbPass;
			$this->dbName                  = $dbName;
			$this->charset                 = $charset;
			$this->conn                    = $this->initializeDatabase();
			$this->backupDir               = BACKUP_DIR ? BACKUP_DIR : '.';
			$this->backupFile              = $_SESSION['city_mun'].'-'.$this->dbName.'-'.date("Ymd_His", time()).'.sql';
			$this->gzipBackupFile          = defined('GZIP_BACKUP_FILE') ? GZIP_BACKUP_FILE : true;
			$this->disableForeignKeyChecks = defined('DISABLE_FOREIGN_KEY_CHECKS') ? DISABLE_FOREIGN_KEY_CHECKS : true;
			$this->batchSize               = defined('BATCH_SIZE') ? BATCH_SIZE : 1000; // default 1000 rows
			$this->output                  = '';
		}

		protected function initializeDatabase() {
			try {
				$conn = mysqli_connect($this->host, $this->username, $this->passwd, $this->dbName);
				if (mysqli_connect_errno()) {
					throw new Exception('ERROR connecting database: ' . mysqli_connect_error());
					die();
				}
				if (!mysqli_set_charset($conn, $this->charset)) {
					mysqli_query($conn, 'SET NAMES '.$this->charset);
				}
			} catch (Exception $e) {
				print_r($e->getMessage());
				die();
			}
			return $conn;
		}

		public function backupTables($tables = '*') {
			try {
				if($tables == '*') {
					$tables = array();
					$result = mysqli_query($this->conn, 'SHOW TABLES');
					while($row = mysqli_fetch_row($result)) {
						$tables[] = $row[0];
					}
				} else {
					$tables = is_array($tables) ? $tables : explode(',', str_replace(' ', '', $tables));
				}
				$sql = 'CREATE DATABASE IF NOT EXISTS `'.$this->dbName."`;\n\n";
				$sql .= 'USE `'.$this->dbName."`;\n\n";

				if ($this->disableForeignKeyChecks === true) {
					$sql .= "SET foreign_key_checks = 0;\n\n";
				}

				foreach($tables as $table) {
					$this->obfPrint("Backing up `".$table."` table...".str_repeat('.', 50-strlen($table)), 0, 0);
					$sql .= 'DROP TABLE IF EXISTS `'.$table.'`;';
					$row = mysqli_fetch_row(mysqli_query($this->conn, 'SHOW CREATE TABLE `'.$table.'`'));
					$sql .= "\n\n".$row[1].";\n\n";
					$row = mysqli_fetch_row(mysqli_query($this->conn, 'SELECT COUNT(*) FROM `'.$table.'`'));
					$numRows = $row[0];
					$numBatches = intval($numRows / $this->batchSize) + 1; // Number of while-loop calls to perform
					for ($b = 1; $b <= $numBatches; $b++) {

						$query = 'SELECT * FROM `' . $table . '` LIMIT ' . ($b * $this->batchSize - $this->batchSize) . ',' . $this->batchSize;
						$result = mysqli_query($this->conn, $query);
						$realBatchSize = mysqli_num_rows ($result); // Last batch size can be different from $this->batchSize
						$numFields = mysqli_num_fields($result);
						if ($realBatchSize !== 0) {
							$sql .= 'INSERT INTO `'.$table.'` VALUES ';
							for ($i = 0; $i < $numFields; $i++) {
								$rowCount = 1;
								while($row = mysqli_fetch_row($result)) {
									$sql.='(';
									for($j=0; $j<$numFields; $j++) {
										if (isset($row[$j])) {
											$row[$j] = addslashes($row[$j]);
											$row[$j] = str_replace("\n","\\n",$row[$j]);
											$row[$j] = str_replace("\r","\\r",$row[$j]);
											$row[$j] = str_replace("\f","\\f",$row[$j]);
											$row[$j] = str_replace("\t","\\t",$row[$j]);
											$row[$j] = str_replace("\v","\\v",$row[$j]);
											$row[$j] = str_replace("\a","\\a",$row[$j]);
											$row[$j] = str_replace("\b","\\b",$row[$j]);
											if ($row[$j] == 'true' or $row[$j] == 'false' or preg_match('/^-?[0-9]+$/', $row[$j]) or $row[$j] == 'NULL' or $row[$j] == 'null') {
												$sql .= $row[$j];
											} else {
												$sql .= '"'.$row[$j].'"' ;
											}
										} else {
											$sql.= 'NULL';
										}
	 
										if ($j < ($numFields-1)) {
											$sql .= ',';
										}
									}
	 
									if ($rowCount == $realBatchSize) {
										$rowCount = 0;
										$sql.= ");\n"; //close the insert statement
									} else {
										$sql.= "),\n"; //close the row
									}
	 
									$rowCount++;
								}
							}
	 
							$this->saveFile($sql);
							$sql = '';
						}
					}
	 
					$sql.="\n\n";
					$this->obfPrint('SUCCESS!');
				}
				if ($this->disableForeignKeyChecks === true) {
					$sql .= "SET foreign_key_checks = 1;\n";
				}
				$this->saveFile($sql);
				if ($this->gzipBackupFile) {
					$this->gzipBackupFile();
				} else {
					$this->obfPrint('Backup file succesfully saved to ' . $this->backupDir.'/'.$this->backupFile, 1, 1);
				}
			} catch (Exception $e) {
				print_r($e->getMessage());
				return false;
			}
			return true;
		}

		protected function saveFile(&$sql) {
			if (!$sql) return false;
			try {
				if (!file_exists($this->backupDir)) {
					mkdir($this->backupDir, 0777, true);
				}
				file_put_contents($this->backupDir.'/'.$this->backupFile, $sql, FILE_APPEND | LOCK_EX);
			} catch (Exception $e) {
				print_r($e->getMessage());
				return false;
			}
			return true;
		}

		protected function gzipBackupFile($level = 9) {
			if (!$this->gzipBackupFile) {
				return true;
			}
			$source = $this->backupDir . '/' . $this->backupFile;
			$dest =  $source . '.gz';
			$this->obfPrint('Gzipping backup file to ' . $dest . '... ', 1, 0);
			$mode = 'wb' . $level;
			if ($fpOut = gzopen($dest, $mode)) {
				if ($fpIn = fopen($source,'rb')) {
					while (!feof($fpIn)) {
						gzwrite($fpOut, fread($fpIn, 1024 * 256));
					}
					fclose($fpIn);
				} else {
					return false;
				}
				gzclose($fpOut);
				if(!unlink($source)) {
					return false;
				}
			} else {
				return false;
			}
	 
			$this->obfPrint('SUCCESS!');
			echo '<div class="mt-3"><a href="'.$dest.'" class="btn btn-success rounded-pill px-4 py-2 fw-bold"><i class="fa-solid fa-download me-2"></i> Download Backup Archive</a></div><br>';

			return $dest;
		}

		public function obfPrint ($msg = '', $lineBreaksBefore = 0, $lineBreaksAfter = 1) {
			if (!$msg) {
				return false;
			}
			if ($msg != 'SUCCESS!' and $msg != 'FAILED!') {
				$msg = date("Y-m-d H:i:s") . ' - ' . $msg;
			}
			$output = '';
			if (php_sapi_name() != "cli") {
				$lineBreak = "<br />";
			} else {
				$lineBreak = "\n";
			}
			if ($lineBreaksBefore > 0) {
				for ($i = 1; $i <= $lineBreaksBefore; $i++) {
					$output .= $lineBreak;
				}                
			}
			$output .= $msg;
			if ($lineBreaksAfter > 0) {
				for ($i = 1; $i <= $lineBreaksAfter; $i++) {
					$output .= $lineBreak;
				}                
			}
			$this->output .= str_replace('<br />', '\n', $output);
			echo $output;
			if (php_sapi_name() != "cli") {
				if( ob_get_level() > 0 ) {
					ob_flush();
				}
			}
			$this->output .= " ";
			flush();
		}
		public function getOutput() {
			return $this->output;
		}
	}

	error_reporting(E_ALL);
	// Set script max execution time
	set_time_limit(900); // 15 minutes
	if (php_sapi_name() != "cli") {
		echo '<div class="console-window">';
		echo '  <div class="console-header">';
		echo '    <div class="console-dots">';
		echo '      <span class="dot-red"></span>';
		echo '      <span class="dot-yellow"></span>';
		echo '      <span class="dot-green"></span>';
		echo '    </div>';
		echo '    <div class="fw-bold text-muted small">Backup Execution Console</div>';
		echo '    <div><a href="backup.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Clear Console</a></div>';
		echo '  </div>';
		echo '  <div class="console-content" style="max-height: 400px; overflow-y: auto;">';
	}
	$backupDatabase = new Backup_Database(DB_HOST, DB_USER, DB_PASS, DB_NAME, CHARSET);
	$result = $backupDatabase->backupTables(TABLES, BACKUP_DIR) ? 'SUCCESS!' : 'FAILED!';
	$backupDatabase->obfPrint('Backup result: ' . $result, 1);
	// Use $output variable for further processing, for example to send it by email
	$output = $backupDatabase->getOutput();
	if (php_sapi_name() != "cli") {
		echo '  </div>';
		echo '</div>';
	}
	}

//RESTORE
	if(isset($_POST["upload"])){

		if (!file_exists("BACKUP")) {
			mkdir("BACKUP", 0777, true);
		}
		move_uploaded_file($_FILES['file']['tmp_name'], "BACKUP/backup-sql.sql.gz");
		$file = "backup-sql.sql.gz"; 

		// Define database parameters here

		define("BACKUP_DIR", 'BACKUP'); // Comment this line to use same script's directory ('.')
		define("BACKUP_FILE", $file); // Script will autodetect if backup file is gzipped based on .gz extension
		define("CHARSET", 'utf8');
		define("DISABLE_FOREIGN_KEY_CHECKS", true); // Set to true if you are having foreign key constraint fails

		//The Restore_Database class
		class Restore_Database {
			var $host;
			var $username;
			var $passwd;
			var $dbName;
			var $charset;
			var $conn;
			var $disableForeignKeyChecks;
			var $backupDir;
			var $backupFile;

			function __construct($dbHost, $dbUser, $dbPass, $dbName, $charset = 'utf8') {
				$this->host                    = $dbHost;
				$this->username                = $dbUser;
				$this->passwd                  = $dbPass;
				$this->dbName                  = $dbName;
				$this->charset                 = $charset;
				$this->disableForeignKeyChecks = defined('DISABLE_FOREIGN_KEY_CHECKS') ? DISABLE_FOREIGN_KEY_CHECKS : true;
				$this->conn                    = $this->initializeDatabase();
				$this->backupDir               = defined('BACKUP_DIR') ? BACKUP_DIR : '.';
				$this->backupFile              = defined('BACKUP_FILE') ? BACKUP_FILE : null;
			}

			function __destruct() {
				if ($this->disableForeignKeyChecks === true) {
					mysqli_query($this->conn, 'SET foreign_key_checks = 1');
				}
			}

			protected function initializeDatabase() {
				try {
					$conn = mysqli_connect($this->host, $this->username, $this->passwd, $this->dbName);
					if (mysqli_connect_errno()) {
						throw new Exception('ERROR connecting database: ' . mysqli_connect_error());
						die();
					}
					if (!mysqli_set_charset($conn, $this->charset)) {
						mysqli_query($conn, 'SET NAMES '.$this->charset);
					}
					if ($this->disableForeignKeyChecks === true) {
						mysqli_query($conn, 'SET foreign_key_checks = 0');
					}
				} catch (Exception $e) {
					print_r($e->getMessage());
					die();
				}
				return $conn;
			}

			public function restoreDb() {
				try {
					$sql = '';
					$multiLineComment = false;
					$backupDir = $this->backupDir;
					$backupFile = $this->backupFile;
					$backupFileIsGzipped = substr($backupFile, -3, 3) == '.gz' ? true : false;
					if ($backupFileIsGzipped) {
						if (!$backupFile = $this->gunzipBackupFile()) {
							throw new Exception("ERROR: couldn't gunzip backup file " . $backupDir . '/' . $backupFile);
						}
					}
					$handle = fopen($backupDir . '/' . $backupFile, "r");
					if ($handle) {
						while (($line = fgets($handle)) !== false) {
							$line = ltrim(rtrim($line));
							if (strlen($line) > 1) { // avoid blank lines
								$lineIsComment = false;
								if (preg_match('/^\/\*/', $line)) {
									$multiLineComment = true;
									$lineIsComment = true;
								}
								if ($multiLineComment or preg_match('/^\/\//', $line)) {
									$lineIsComment = true;
								}
								if (!$lineIsComment) {
									$sql .= $line;
									if (preg_match('/;$/', $line)) {
										// execute query
										if(mysqli_query($this->conn, $sql)) {
											if (preg_match('/^CREATE TABLE `([^`]+)`/i', $sql, $tableName)) {
												$this->obfPrint("Table succesfully created: `" . $tableName[1] . "`");
											}
											$sql = '';
										} else {
											throw new Exception("ERROR: SQL execution error: " . mysqli_error($this->conn));
										}
									}
								} else if (preg_match('/\*\/$/', $line)) {
									$multiLineComment = false;
								}
							}
						}
						fclose($handle);
					} else {
						throw new Exception("ERROR: couldn't open backup file " . $backupDir . '/' . $backupFile);
					} 
				} catch (Exception $e) {
					print_r($e->getMessage());
					return false;
				}
				if ($backupFileIsGzipped) {
					unlink($backupDir . '/' . $backupFile);
				}
				return true;
			}

			protected function gunzipBackupFile() {
				$bufferSize = 4096; // read 4kb at a time
				$error = false;
				$source = $this->backupDir . '/' . $this->backupFile;
				$dest = $this->backupDir . '/' . date("Ymd_His", time()) . '_' . substr($this->backupFile, 0, -3);
				$this->obfPrint('Gunzipping backup file ' . $source . '... ', 1, 1);
				if (file_exists($dest)) {
					if (!unlink($dest)) {
						return false;
					}
				}
				if (!$srcFile = gzopen($this->backupDir . '/' . $this->backupFile, 'rb')) {
					return false;
				}
				if (!$dstFile = fopen($dest, 'wb')) {
					return false;
				}
				while (!gzeof($srcFile)) {
					if(!fwrite($dstFile, gzread($srcFile, $bufferSize))) {
						return false;
					}
				}
				fclose($dstFile);
				gzclose($srcFile);
				return str_replace($this->backupDir . '/', '', $dest);
			}

			public function obfPrint ($msg = '', $lineBreaksBefore = 0, $lineBreaksAfter = 1) {
				if (!$msg) {
					return false;
				}
				$msg = date("Y-m-d H:i:s") . ' - ' . $msg;
				$output = '';
				if (php_sapi_name() != "cli") {
					$lineBreak = "<br />";
				} else {
					$lineBreak = "\n";
				}
				if ($lineBreaksBefore > 0) {
					for ($i = 1; $i <= $lineBreaksBefore; $i++) {
						$output .= $lineBreak;
					}                
				}
				$output .= $msg;
				if ($lineBreaksAfter > 0) {
					for ($i = 1; $i <= $lineBreaksAfter; $i++) {
						$output .= $lineBreak;
					}                
				}
				if (php_sapi_name() == "cli") {
					$output .= "\n";
				}
				echo $output;
				if (php_sapi_name() != "cli") {
					ob_flush();
				}
				flush();
			}
		}

		error_reporting(E_ALL);
		set_time_limit(900); // 15 minutes
		if (php_sapi_name() != "cli") {
			echo '<div class="console-window">';
			echo '  <div class="console-header">';
			echo '    <div class="console-dots">';
			echo '      <span class="dot-red"></span>';
			echo '      <span class="dot-yellow"></span>';
			echo '      <span class="dot-green"></span>';
			echo '    </div>';
			echo '    <div class="fw-bold text-muted small">Restoration Execution Console</div>';
			echo '    <div><a href="backup.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Clear Console</a></div>';
			echo '  </div>';
			echo '  <div class="console-content" style="max-height: 400px; overflow-y: auto;">';
		}
		$restoreDatabase = new Restore_Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$result = $restoreDatabase->restoreDb(BACKUP_DIR, BACKUP_FILE) ? 'SUCCESS!' : 'FAILED!';
		$restoreDatabase->obfPrint("Restoration result: ".$result, 1);
		if (php_sapi_name() != "cli") {
			echo '  </div>';
			echo '</div>';
			unlink("BACKUP/backup-sql.sql.gz");
		}
	}
?>

<?php require("footer.php");?>
