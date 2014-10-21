<?php
/*
	seatping main interface

This program is free software. It comes without any warranty, to
the extent permitted by applicable law. You can redistribute it
and/or modify it under the terms of the Do What The Fuck You Want
To Public License, Version 2, as published by Sam Hocevar and 
reproduced below.

DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE 
Version 2, December 2004 

Copyright (C) 2004 Sam Hocevar <sam@hocevar.net> 

	Everyone is permitted to copy and distribute verbatim or modified 
	copies of this license document, and changing it is allowed as long 
	as the name is changed. 

DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE 
TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION 

	0. You just DO WHAT THE FUCK YOU WANT TO.
*/
	require_once("settings.php");
	require_once("db_conn.php");

	$halls=$db->query("
		SELECT
			hall_id,
			hall_name
		FROM
			halls
		WHERE
			1
	");
	$halls=$halls->fetchAll(PDO::FETCH_ASSOC);
	
	
	if(isset($_GET["rss"])){
		header("Content-Type: application/xml"); 
		print('<?xml version="1.0" encoding="ISO-8859-1"?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">');
		
		if(empty($_GET["rss"])){
			?>	
				<channel>
					<atom:link href="http://seatping.kitinfo.de/?rss" rel="self" type="application/rss+xml" />
					<title>seatping public checkin stream</title>
					<link>http://seatping.kitinfo.de/</link>
					<description>seatping provides location-sharing services for KIT lecture halls.</description>
					<language>en-us</language>
					<copyright>Licensed as WTFPL</copyright>
					<pubDate><?php print(date("r")); ?></pubDate>
					<generator>seatping</generator>
					<ttl>5</ttl>
					<?php
						$checkins=$db->query("
							SELECT
								user_name,
								hall_name,
								ping_hash,
								ping_annotation,
								ping_date
							FROM
								checkins
							WHERE
								ping_public
							LIMIT 50
						");

						$checkins=$checkins->fetchAll(PDO::FETCH_ASSOC);
						foreach($checkins as $ping){
							?>
								<item>
									<title><?php print($ping["user_name"]); ?> checked in to <?php print($ping["hall_name"]); ?></title>
									<link>http://seatping.kitinfo.de/?ping=<?php print($ping["ping_hash"]); ?></link>
									<description>
										<?php 
											if(!empty($ping["ping_annotation"])){print(htmlspecialchars(html_entity_decode($ping["ping_annotation"])));}
											else{print("No further information.");}
										?>
									</description>
									<pubDate><?php print(date("r",$ping["ping_date"])); ?></pubDate>
									<author><?php print($ping["user_name"]); ?></author>
									<category>Checkin</category>
									<guid isPermaLink="false"><?php print($ping["ping_hash"]); ?></guid>
									
								</item>
							<?php
						}
					?>
				</channel>	
			<?php
		}
		else{
			?>
			<channel>
					<atom:link href="http://seatping.kitinfo.de/?rss=lv" rel="self" type="application/rss+xml" />
					<title>seatping public checkin graphs</title>
					<link>http://seatping.kitinfo.de/</link>
					<description>seatping provides location-sharing services for KIT lecture halls.</description>
					<language>en-us</language>
					<copyright>Licensed as WTFPL</copyright>
					<pubDate><?php print(date("r")); ?></pubDate>
					<generator>seatping</generator>
					<ttl>5</ttl>
					<?php
						foreach($halls as $hall){
							?>
								<item>
									<title>Last 90 minutes in <?php print($hall["hall_name"]); ?></title>
									<link>http://seatping.kitinfo.de/</link>
									<description><![CDATA[<img src="http://seatping.kitinfo.de/map.php?hall=<?php print($hall["hall_id"]); ?>&last=90" /> ]]></description>
									<pubDate><?php print(date("r")); ?></pubDate>
									<author>seatping</author>
									<category>LiveView</category>
									<guid isPermaLink="false"><?php print(sha1($hall["hall_id"])); ?></guid>
								</item>
							<?php
						}
					?>
			</channel>
		<?php
		}
		
		print("</rss>");
		die();
	}
	
	session_start();
	
	if(isset($_GET["logout"])){
		session_regenerate_id(TRUE);
		$_SESSION=array();
	}
	
	if(isset($_POST["login"])&&$auth_methods["local"]){
		$_POST["login"]="";
		$user_name=htmlentities($_POST["user_name"]);
		$user_password=hash("sha256",$_POST["user_password"].$pepper);
		if(!empty($user_name)){
			$user_data=$db->prepare("
				SELECT 
					user_id,
					user_name,
					user_identity
				FROM 
					users 
				WHERE 
					user_name=:uname 
					AND user_source='local'
			");
			$user_data->execute(array(":uname"=>$user_name));
			$user_data=$user_data->fetch(PDO::FETCH_ASSOC);
			
			if($user_data===false){
				//register new local user
				$register_user=$db->prepare("
					INSERT INTO users 
					(user_name, user_identity, user_source)
					VALUES
					(:uname, :upass, 'local')
					");
				
				if($register_user->execute(array(
					":uname"=>$user_name,
					":upass"=>$user_password
				))){
					$_POST["login"]="Account created.";
					$_SESSION["user_id"]=$db->lastInsertId();
					$_SESSION["user_name"]=$user_name;
				}
				else{
					$_POST["login"]="Failed to register user.";
				}
			}
			else if($user_data["user_identity"]==$user_password){
				$_SESSION["user_id"]=$user_data["user_id"];
				$_SESSION["user_name"]=$user_data["user_name"];
			}
			else{
				$_POST["login"]="Failed to log in.";
			}
		}
	}
	
	if(!isset($_SESSION["user_id"])&&isset($_GET["system-login"])&&$auth_methods["system"]){
		//system authentication
		$token=hash("sha256", hash("sha256", session_id()).$system_password);
		//check for token in database
		$system_hit=$db->prepare("SELECT system_id, system_name FROM system WHERE system_token=:token");
		$system_hit->execute(array(":token"=>$token));
		$system_hit=$system_hit->fetch(PDO::FETCH_ASSOC);
		
		if($system_hit!==false){
			//if accepted, create or load from user table
			$user_info=$db->prepare("
				SELECT
					user_id,
					user_name
				FROM users
				WHERE
					user_source='system'
					AND user_identity=:system_id
			");
			$user_info->execute(array(":system_id"=>$system_hit["system_id"]));
			$user_info=$user_info->fetch(PDO::FETCH_ASSOC);
			
			if($user_info===false){
				//create local user
				$user_insert=$db->prepare("
					INSERT INTO
					users
					(user_name, user_identity, user_source)
					VALUES
					(:name, :ident, 'system')
				");
				if($user_insert->execute(array(":name"=>$system_hit["system_name"],":ident"=>$system_hit["system_id"]))){
					$user_info=array("user_id"=>$system_hit["system_id"], "user_name"=>$system_hit["system_name"]);
				}
				else{
					//this is awkward. authentication probably failed because a local account
					//using the same name exists.
				}
			}
			
			if($user_info!==false){
				$_SESSION["user_id"]=$user_info["user_id"];
				$_SESSION["user_name"]=htmlentities($user_info["user_name"]);
			}
		}
	}
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>seatping</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style type="text/css">
			h1, h2 {
				margin-top:5px;
				padding-top:5px;
			}
			
			.box {
				padding:10px;
			}
			
			.bottomcorner {
				-webkit-border-bottom-right-radius: 30px;
				-webkit-border-bottom-left-radius: 30px;
				-moz-border-radius-bottomright: 30px;
				-moz-border-radius-bottomleft: 30px;
				border-bottom-right-radius: 30px;
				border-bottom-left-radius: 30px;
			}
			
			.topcorner {
				-webkit-border-top-left-radius: 30px;
				-webkit-border-top-right-radius: 30px;
				-moz-border-radius-topleft: 30px;
				-moz-border-radius-topright: 30px;
				border-top-left-radius: 30px;
				border-top-right-radius: 30px;
			}
			
			html {
				width:100%;
				position:relative;
			}
			
			body{
				width:60%;
				margin:auto;
			}
			
			#head {
				background-color:#e0e0e0;
				text-align:center;
				margin-bottom:10px;
			}
			
			#view {
				background-color:#a0a0a0;
				text-align:center;
				margin-bottom:10px;
			}
			
			#create {
				background-color:#ababab;
				text-align:center;
			}
			
			#groupshow {
				background-color:#dfdfdf;
				text-align:center;
			}
			
			#show {
				background-color:#d0d0d0;
			}
			
			#login {
				background-color:#afafaf;
				text-align:right;
			}
			
			#footer {
				background-color:#e0e0e0;
				text-align:center;
				font-size:80%;
			}
		</style>
		<meta name="description" content="seatping - a location sharing tool for lecture halls at the Karlsruhe Institute of Technology." />
		<meta name="keywords" content="seatping, location sharing, hoersaal, hoersaal, lecture hall, kit, #kitinfo, kitinfo, studium, irc, karlsruhe" />
		<meta name="robots" content="index,follow" />
	</head>
	<body>
	
		<div id="head" class="box bottomcorner">
			
			<div style="text-align:right;width:100%;margin-bottom:-15px;font-size:90%;">
				<span style="float:left;">RSS: <a href="?rss">Checkins</a> <a href="?rss=lv">LiveView</a></span>
				<span>
					<?php if(isset($_SESSION["user_id"])){ ?>
						&lt;<?php print($_SESSION["user_name"]); ?>&gt; <a href="?logout">Logout</a>
					<?php } else { ?>
						Not logged in.
					<?php } ?>
				</span>
			</div>
			

			<h1>seatping - share your location</h1>
			seatping makes it easy to share your location in lecture halls at the KIT
		</div>
		
		<?php
			if(isset($_GET["ping"])){
				//display specific ping
				$ping_data=$db->prepare("
					SELECT 
						user_id,
						user_name,
						ping_hash,
						ping_x,
						ping_y,
						ping_date,
						ping_annotation,
						hall_name,
						hall_id
					FROM checkins
					WHERE
						ping_hash=:hash
					");
				
				$ping_data->execute(array(":hash"=>$_GET["ping"]));
				$ping_data=$ping_data->fetch(PDO::FETCH_ASSOC);
				
				if($ping_data!==false){
					?>
						<div id="view" class="box bottomcorner topcorner">
								<strong>
									<?php print(($ping_data["user_id"]==0)?($ping_data["ping_hash"]):($ping_data["user_name"])); ?>
								</strong>
								<br />
								checked in to 
								<?php print($ping_data["hall_name"]); ?> 
								(<?php print($ping_data["ping_x"]."|".$ping_data["ping_y"]); ?>) 
								at <?php print(date("d.m.Y H:i",$ping_data["ping_date"])); ?>
								<br />
								<img src="map.php?hall=<?php print($ping_data["hall_id"]."&x=".$ping_data["ping_x"]."&y=".$ping_data["ping_y"]); ?>" 
								alt="<?php print($ping_data["hall_name"]); ?>"/>
								<br />
								<?php if(!empty($ping_data["ping_annotation"])){print("<em>".$ping_data["ping_annotation"]."</em>");} ?>
						</div>
					<?php
				}
				else{
					?>
						<div id="view" class="box bottomcorner topcorner">
								This seems to be an invalid CID. (<?php print(json_encode($db->errorInfo())); ?>)
						</div>
					<?php
				}
			}
		?>			
		
		<div id="create" class="box topcorner">
			<form name="checkin" action="" method="get">
				<h2>Check in</h2>
				<?php
					if(!isset($_GET["hall"])||isset($_GET["last"])){
						print('<h3>Step 1 of 4: Select Hall</h2>	<select name="hall">');
						foreach($halls as $hall){
							print("<option value='".$hall["hall_id"]."'>".$hall["hall_name"]."</option>");
						}
						print("</select>");
						$nextButtonText="Next";
					}
					else{
						if(!isset($_GET["x"])||!isset($_GET["y"])){
							$hall_data=$db->prepare("
								SELECT
									hall_id,
									hall_name
								FROM halls
								WHERE
									hall_id=:hallid
							");
							$hall_data->execute(array(":hallid"=>intval($_GET["hall"])));
							$hall_data=$hall_data->fetch(PDO::FETCH_ASSOC);
							if($hall_data===false){
								die("Nice try.");
							}
							?>
								<h3>Step 2 of 4: Select Spot</h3>
								Click your approximate position on the map of <?php print($hall_data["hall_name"]); ?>
								<input type="hidden" name="hall" value="<?php print($_GET["hall"]); ?>" /><br />
								<script language="JavaScript">
								//modified from http://www.emanueleferonato.com/2006/09/02/click-image-and-get-coordinates-with-javascript/
								function point_it(event){
									pos_x = event.offsetX?(event.offsetX):event.pageX-document.getElementById("pointer_img").offsetLeft;
									pos_y = event.offsetY?(event.offsetY):event.pageY-document.getElementById("pointer_img").offsetTop;
									document.checkin.x.value = pos_x;
									document.checkin.y.value = pos_y;
									document.checkin.submit();
								}
								</script>
								<img id="pointer_img" onclick="point_it(event)" 
									src="map.php?hall=<?php print($hall_data["hall_id"]); ?>" 
									alt="<?php print($hall_data["hall_name"]); ?>"/><br />
								
								<input type="text" name="x" size="4" />
								<input type="text" name="y" size="4" />
							<?php
							$nextButtonText="Next";
						}
						else{
							if(!isset($_GET["public"])){
								?>
									<h3>Step 3 of 4: Additional Information</h3>
									<input type="hidden" name="hall" value="<?php print($_GET["hall"]); ?>" />
									<input type="hidden" name="x" value="<?php print($_GET["x"]); ?>" />
									<input type="hidden" name="y" value="<?php print($_GET["y"]); ?>" />
									Enter some additional text to help others identify you<br />
									<input type="text" name="addinfo" /><br />
									Make this a public checkin? 
									<input type="radio" name="public" value="y" checked />Yep <input type="radio" name="public" value="n" /> Nope <br />
								<?php
								$nextButtonText="Continue";
							}
							else{
								if(!isset($_GET["publish"])){
									$hall_data=$db->prepare("
										SELECT
											hall_id,
											hall_name
										FROM halls
										WHERE
											hall_id=:hallid
									");
									$hall_data->execute(array(":hallid"=>intval($_GET["hall"])));
									$hall_data=$hall_data->fetch(PDO::FETCH_ASSOC);
									if($hall_data===false){
										die("Nice try.");
									}
									?>
										<h3>Step 4 of 4: Review your checkin</h3>
										<input type="hidden" name="hall" value="<?php print($_GET["hall"]); ?>" />
										<input type="hidden" name="x" value="<?php print($_GET["x"]); ?>" />
										<input type="hidden" name="y" value="<?php print($_GET["y"]); ?>" />
										<input type="hidden" name="addinfo" value="<?php print($_GET["addinfo"]); ?>" />
										<input type="hidden" name="public" value="<?php print($_GET["public"]); ?>" />
										<input type="hidden" name="publish" value="easteregg" />
										Clicking 'confirm' publishes your checkin.<br /><br/>
										<strong>You will be checking in <?php ($_GET["public"]=="y")?print("publicly"):print("anonymously"); ?> to <?php print($hall_data["hall_name"]); ?>.</strong><br />
										<img src="map.php?hall=<?php print($hall_data["hall_id"]); ?>&x=<?php print($_GET["x"]);?>&y=<?php print($_GET["y"]); ?>" alt="<?php print($hall_data["hall_name"]); ?>"/><br />
									<?php
									$nextButtonText="Confirm";
								}
								else{
									
									
									$insert_ping=$db->prepare("
										INSERT INTO
											pings
										(ping_user, ping_hall, ping_date, ping_y, ping_x, ping_annotation, ping_public, ping_hash)
										VALUES
										(:user, :hall, :date, :y, :x, :annotation, :public, :hash)
									");
									
									$hash=md5(session_id().$_GET["addinfo"].$_GET["hall"].$_GET["x"].$_GET["y"]);
									
									if($insert_ping->execute(array(
										":user"=>((!isset($_SESSION["user_id"]))?0:$_SESSION["user_id"]),
										":hall"=>intval($_GET["hall"]),
										":date"=>time(),
										":x"=>intval($_GET["x"]),
										":y"=>intval($_GET["y"]),
										":annotation"=>htmlentities($_GET["addinfo"]),
										":public"=>($_GET["public"]=="y")?1:0,
										":hash"=>$hash
									))){
										?>
										<h3>All done!</h3>
										You were successfully checked in.<br />
										Use the Link <a href="http://<?php print($_SERVER["SERVER_NAME"]); ?>/?ping=<?php print($hash); ?>">http://<?php print($_SERVER["SERVER_NAME"]); ?>/?ping=<?php print($hash); ?></a> to share your checkin!
										<br />
										<?php
									}
									else{
										print("checkin failed with ".json_encode($db->errorInfo())."<br />");
									}
								}
							}
						}
						
					}
				?>
				<?php if(isset($nextButtonText)){ ?>
				<input type="submit" name="checkin" value="<?php print($nextButtonText); ?>" />
				<?php } ?>
				
			</form>
		</div>
		
		<div id="groupshow" class="box">
			<form action="" name="group" method="get">
				<h2>Show checkins over time</h2>
				Last <input type="text" name="last" value="10" size="4" /> minutes for <select name="hall">
					<?php
						foreach($halls as $hall){
							print("<option value='".$hall["hall_id"]."'>".$hall["hall_name"]."</option>");
						}
						print("</select>");
					?>
					<input type="submit" value="Show!" /><br />
					<?php
						if(isset($_GET["last"])){
							?>
								<img style="padding-top:0.5em;" src="map.php?hall=<?php print($_GET["hall"]); ?>&last=<?php print($_GET["last"]);?>"/>
							<?php
						}
					?>
			</form>
		</div>
		
		<div id="show" class="box">
			<?php				
				$last_pings=$db->query("
					SELECT
						ping_date,
						ping_hash,
						user_id,
						user_name,
						hall_name,
						ping_annotation
					FROM checkins
					WHERE ping_public
					LIMIT 10
				");
				
				$last_pings=$last_pings->fetchAll(PDO::FETCH_ASSOC);
			?>
			<h2>Last 10 public checkins</h2>
			<?php
				if($last_pings!==false){
					foreach($last_pings as $ping){
						print("[".date("d.m.Y H:i",$ping["ping_date"])."] ");
						print('<a href="?ping='.$ping["ping_hash"].'">');
						print((($ping["user_id"]==0)?$ping["ping_hash"]:$ping["user_name"])."</a> checked in at ".$ping["hall_name"]);
						if(!empty($ping["ping_annotation"])){
							print(' <em>'.$ping["ping_annotation"].'</em>');
						}
						print("<br />");
					}
				}
			?>
		</div>
		
		<?php
			if(!isset($_SESSION["user_id"])&&$auth_methods["local"]){
				?>
				<div id="login" class="box">
					<form action="" method="post">
						<span style="float:left;">Login/Register</span>
						<?php
							if(isset($_POST["login"])){
								print($_POST["login"]);
							}
						?>
						<input type="text" name="user_name" size="7" />
						<input type="password" name="user_password" size="7"/>
						<input type="submit" name="login" value="Send" />
					</form>
				</div>
				<?php
			}
			
			if(!isset($_SESSION["user_id"])&&$auth_methods["system"]){
				?>
				<div class="box" style="text-align:right;background-color:#ddd;">
					<a style="text-decoration:none;"
					href="<?php print($system_main); ?>?service=seatping&req=unique_id,username&ident=<?php print(hash("sha256", session_id())); ?>">
						[Sign in with the SYSTEM]
					</a>
				</div>
				<?php
			}
		?>
		
		<div id="footer" class="box bottomcorner">
			<a href="https://github.com/cbdevnet/seatping">Browse source (PHP/SQLite)</a>  | <em>Source may contain strong language. Not suitable for children (emotional or otherwise).</em> Hall sketch vectorization by klaxa
		</div>
	</body>
</html>
