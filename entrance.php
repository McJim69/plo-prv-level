<?php
	session_start();	

	require("config.php");
	
	try {
		$link = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_BOTH,
			PDO::ATTR_EMULATE_PREPARES => true,
		]);
	} catch (PDOException $e) {
		die("ERROR: Could not connect. " . $e->getMessage());
	}
	
	require("head.php");	
	
	$m="";
	if(isset($_POST["login"])){
		$stmt = $link->prepare("select * from users where username=? and password=?");
		$stmt->execute([$_POST["user"], $_POST["pass"]]);
		$ex = $stmt;
		if($rs=$ex->fetch(PDO::FETCH_BOTH)){
			$stmt = $link->prepare("select * from validity where validity>?");
			$stmt->execute([date("Y-m-d")]);
			$exx = $stmt;
			if($rs1=$exx->fetch(PDO::FETCH_BOTH)){
				$_SESSION["city_mun"]=!empty($_POST["t_city_mun"]) ? $_POST["t_city_mun"] : $rs["city_mun"];
				$_SESSION["barangay"]=$rs["barangay"];
				$_SESSION["city_mun_assigned"]=$rs["city_mun"];
				$_SESSION["barangay_assigned"]=$rs["barangay"];
				$_SESSION["vin"]=$rs["vin"];
				$_SESSION["user"]=$rs["username"];
				$_SESSION["name"]=$rs["fullname"];
				$_SESSION["access"]=$rs["access"];
				$_SESSION["gender"]=$rs["gender"];
				
				echo"<script>window.location='index.php';</script>";
				exit();
			}else {
				$m="<div class='alert alert-danger'><b>DENIED!</b> Your access validity has expired. Contact your system administrator for assistance.</div>";
			}
		}		
		else {
			$m="<div class='alert alert-danger'><b>DENIED!</b> Either username or password is invalid.</div>";
			$err=1;
		}
	}
?>

<style>
	body {
		background: #121212 url(images/back.webp) !important; background-size:cover; background-position:center center;
		align-items: center;
		justify-content: center;
	}
</style>


<style>
#slidecontainer{
	position:relative;
}
#slidecontainer div{
	display:table-cell;
	width:500px;vertical-align:top;
}
#slidecontainer div .main{
	width:100%;
}
</style>

<div class="login-card">
	<div style="text-align: center; margin-bottom: 20px;">
		<h2 style="font-weight: 700; color: #ffffff; letter-spacing: 0.5px;">PLO SYSTEM</h2>
		<p style="color: #94a3b8; font-size: 14px;">Please login to access your portal</p>
	</div>
	
	<?php if($m != "") echo $m; ?>
	
	<form method="post" class="space-y-4">
		<div>
			<label class="form-label mb-2">Municipality</label>
			<select class="login-input" name="t_city_mun" required>
				<?php
					$ex = $link->query("select city_mun from voters group by city_mun");
					while($rs=$ex->fetch(PDO::FETCH_BOTH)) {
						echo "<option style='color:#000;'>".$rs["city_mun"]."</option>";
					}
				?>
			</select>
		</div>
		
		<div>
			<label class="form-label mb-2">Username</label>
			<input class="login-input" type="text" placeholder="Enter username" autofocus required name="user" />
		</div>
		
		<div>
			<label class="form-label mb-2">Password</label>
			<input class="login-input" type="password" placeholder="Enter password" required name="pass" />
		</div>
		
		<div style="padding-top: 10px;">
			<button class="btn-login" type="submit" name="login">Sign In</button>
		</div>
	</form>
	<div style="width:100px;position:relative;margin:0 auto;top:-10px;border-radius:14px">
		<div style="background:transparent;border:0;" onclick="clearInterval(t);gotoSlide('next');t=setInterval('gotoSlide(\'next\')',3000)">
			<div style="overflow:hidden;position:relative;border-radius:14px">
				<div id="slidecontainer">
					<?php
						for($i=1;$i<10;$i++)
						echo"<div><img style='width:100px;' src='images/logos/$i.png?".date("h:i:s")."'></div>";
					?>
				</div>
			</div>
		</div>
	</div>	
	<div class="system-version" style="margin: -10px 0 -20px 0">
		PLO System v5.19
	</div>
</div>
	
<script>
	var t;
	var currentLeft=0;
	var slides=5;
	var speed=1000;
	
		function gotoSlide(s){
			if(s=="next"){
				if(currentLeft<=((slides-1)*100*-1)){
					$("#slidecontainer").animate({
						left:currentLeft-100
					},function(){
						currentLeft=0;
						$("#slidecontainer").animate({
							left:currentLeft
						},speed);
					});
				}
				else{
					$("#slidecontainer").animate({
						left:currentLeft+100
					},function(){
						currentLeft-=100;
						$("#slidecontainer").animate({
							left:currentLeft
						},speed);
					});
				}
			}
			else{
				if(currentLeft==0){
					$("#slidecontainer").animate({
						left:currentLeft+100
					},function(){
						currentLeft=(slides-1)*100*-1;
						$("#slidecontainer").animate({
							left:currentLeft
						},speed);
					});
				}
				else{
					$("#slidecontainer").animate({
						left:currentLeft-100
					},function(){
						currentLeft+=100;
						$("#slidecontainer").animate({
							left:currentLeft
						},speed);
					});
				}
			}
		}
	t=setInterval("gotoSlide('next')",3000);
</script>
