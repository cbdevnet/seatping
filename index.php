<?php
	/*
	seatping code.
	The main part was hacked together in about 4-6 hours (including prototypes)
	and	is therefore a mixture of shit and lulz. You have been warned.
	
	Licensed as WTFPL (see COPYING.txt)
	Have fun.
	*/
	require_once("settings.php");
	
	try{
		$db=new SQLite3($dbfile);
	}
	catch(Exception $e){
		print($e->getMessage());
		die();
	}
	
	$db->busyTimeout(500);
	
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
						$items=$db->query("select * from composite where public=1 limit 50");
						$it=$items->fetchArray();
						while($it!==FALSE){
							?>
								<item>
									<title><?php print($it["username"]); ?> checked in to <?php print($it["hall"]); ?></title>
									<link>http://seatping.kitinfo.de/?cid=<?php print($it["cid"]); ?></link>
									<description>
										<?php 
											if(!empty($it["loctext"])){print(htmlspecialchars(html_entity_decode($it["loctext"])));}
											else{print("No further information.");}
										?>
									</description>
									<pubDate><?php print(date("r",$it["cdate"])); ?></pubDate>
									<author><?php print($it["username"]); ?></author>
									<category>Checkin</category>
									<guid isPermaLink="false"><?php print($it["cid"]); ?></guid>
									
								</item>
							<?php
							$it=$items->fetchArray();
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
						$halls=$db->query("select * from halls where 1");
						$it=$halls->fetchArray();
						while($it!==FALSE){
							?>
								<item>
									<title>Last 90 minutes in <?php print($it["name"]); ?></title>
									<link>http://seatping.kitinfo.de/</link>
									<description><![CDATA[<img src="http://seatping.kitinfo.de/map.php?hall=<?php print($it["hallid"]); ?>&last=90" /> ]]></description>
									<pubDate><?php print(date("r")); ?></pubDate>
									<author>seatping</author>
									<category>LiveView</category>
									<guid isPermaLink="false"><?php print(sha1($it["hallid"])); ?></guid>
								</item>
							<?php
							$it=$halls->fetchArray();
						}
					?>
			</channel>
		<?php
		}
		
		print("</rss>");
		die();
	}
	
	session_start();
	
	if(isset($_GET["l"])){
		session_destroy();
		unset($_SESSION);
	}
	
	if(isset($_POST["l"])){
		$u=$db->escapeString(htmlentities($_POST["u"]));
		$p=hash("sha256",($salt.sha1($_POST["p"])));
		if(!empty($u)){
			$user=$db->querySingle("select * from users where name='".$u."'",TRUE);
			
			if($user==NULL){
				//reg
				if($db->exec("insert into users (name,pw) values ('".$u."','".$p."')")){
					$loginState="Account created.";
					$_SESSION["uid"]=$db->lastInsertRowID();
					$_SESSION["name"]=$u;
				}
				else{
					$loginState="Something strange happened.";
				}
			}
			else if($user["pw"]==$p){
				$_SESSION["uid"]=$user["uid"];
				$_SESSION["name"]=$user["name"];
			}
			else{
				$loginState="That seemed wrong.";
			}
		}
	}
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>seatping</title>
		<meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />
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
		<meta name="keywords" content="seatping, location sharing, hoersaal, hörsaal, lecture hall, kit, #kitinfo, kitinfo, studium, irc, karlsruhe" />
		<meta name="robots" content="index,follow" />
	</head>
	<body>
	
		<div id="head" class="box bottomcorner">
			
			<div style="text-align:right;width:100%;margin-bottom:-15px;font-size:90%;">
				<span style="float:left;">RSS: <a href="?rss">Checkins</a> <a href="?rss=lv">LiveView</a></span>
				<span>
					<?php if(isset($_SESSION["uid"])){ ?>
						&lt;<?php print($_SESSION["name"]); ?>&gt; <a href="?l">Logout</a>
					<?php } else { ?>
						Not logged in.
					<?php } ?>
				</span>
			</div>
			

			<h1>seatping - share your location</h1>
			seatping makes it easy to share your location in lecture halls at the KIT
		</div>
		
		<?php
			if(isset($_GET["cid"])){
				$cid=$db->escapeString($_GET["cid"]);
				$cdata=$db->querySingle("select * from composite where cid='".$cid."'",TRUE);
				if($cdata!=NULL){
					?>
						<div id="view" class="box bottomcorner topcorner">
								<strong><?php print(($cdata["uid"]==0)?($cdata["cid"]):($cdata["username"])); ?></strong><br /> checked in to <?php print($cdata["hall"]); ?> (<?php print($cdata["x"]."|".$cdata["y"]); ?>) at <?php print(date("d.m.Y H:i",$cdata["cdate"])); ?><br />
								<img src="map.php?hall=<?php print($cdata["hallid"]."&x=".$cdata["x"]."&y=".$cdata["y"]); ?>" alt="<?php print($cdata["hall"]); ?>"/><br /><?php if(!empty($cdata["loctext"])){print("<em>".$cdata["loctext"]."</em>");} ?>
						</div>
					<?php
				}
				else{
					?>
						<div id="view" class="box bottomcorner topcorner">
								This seems to be an invalid CID. Last error was <?php print($db->lastErrorMsg()); ?>
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
						$data=$db->query("select * from halls where 1");
						$it=$data->fetchArray();
						while($it!==FALSE){
							print("<option value='".$it["hallid"]."'>".$it["name"]."</option>");
							$it=$data->fetchArray();
						}
						print("</select>");
						$nextButtonText="Next";
					}
					else{
						if(!isset($_GET["x"])||!isset($_GET["y"])){
							$hall=$db->querySingle("select * from halls where hallid=".intval($_GET["hall"]),TRUE);
							if($hall==NULL){
								die("Nice try.");
							}
							?>
								<h3>Step 2 of 4: Select Spot</h3>
								Click your approximate position on the map of <?php print($hall["name"]); ?>
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
								<img id="pointer_img" onclick="point_it(event)" src="map.php?hall=<?php print($hall["hallid"]); ?>" alt="<?php print($hall["name"]); ?>"/><br />
								
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
									$hall=$db->querySingle("select * from halls where hallid=".intval($_GET["hall"]),TRUE);
									if($hall==NULL){
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
										<strong>You will be checking in <?php ($_GET["public"]=="y")?print("publicly"):print("anonymously"); ?> to <?php print($hall["name"]); ?>.</strong><br />
										<img src="map.php?hall=<?php print($hall["hallid"]); ?>&x=<?php print($_GET["x"]);?>&y=<?php print($_GET["y"]); ?>" alt="<?php print($hall["name"]); ?>"/><br />
									<?php
									$nextButtonText="Confirm";
								}
								else{
									//sanitize input
									$hall=intval($_GET["hall"]);
									$date=time();
									$x=intval($_GET["x"]);
									$y=intval($_GET["y"]);
									$extinfo=htmlentities($db->escapeString($_GET["addinfo"]));
									$cid=md5("checkin".$extinfo.$hall.$x."-".$y);
									$pubstate=($_GET["public"]=="y")?1:0;
									
									if($db->exec("insert into checkins (user,saal,cdate,pointx,pointy,extinfo,public,cid) values (".((!isset($_SESSION["uid"]))?0:$_SESSION["uid"]).",".$hall.",".$date.",".$x.",".$y.",'".$extinfo."',".$pubstate.",'".$cid."')")){										
										?>
										<h3>All done!</h3>
										You were successfully checked in.<br />
										Use the Link <a href="http://<?php print($_SERVER["SERVER_NAME"]); ?>/?cid=<?php print($cid); ?>">http://<?php print($_SERVER["SERVER_NAME"]); ?>/?cid=<?php print($cid); ?></a> to share your checkin!
										<br />
										<?php
									}
									else{
										if($db->lastErrorCode()==19){
											print("Nah dude. No F5 checkins.<br />");
										}
										else{
											print("checkin failed with ".$db->lastErrorMsg()."<br />");
										}
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
						$data=$db->query("select * from halls where 1");
						$it=$data->fetchArray();
						while($it!==FALSE){
							print("<option value='".$it["hallid"]."'>".$it["name"]."</option>");
							$it=$data->fetchArray();
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
				$last=$db->query("select * from composite where public=1 limit 10");
				$it=$last->fetchArray();
			?>
			<h2>Last 10 public checkins</h2>
			<?php
				while($it!==FALSE){
					print("[".date("d.m.Y H:i",$it["cdate"])."] ");
					print('<a href="?cid='.$it["cid"].'">');
					print((($it["uid"]==0)?$it["cid"]:$it["username"])."</a> checked in at ".$it["hall"]);
					if(!empty($it["loctext"])){
						print(' <em>'.$it["loctext"].'</em>');
					}
					print("<br />");
					$it=$last->fetchArray();
				}
			?>
		</div>
		
		<?php
			if(!isset($_SESSION["uid"])){
				?>
				<div id="login" class="box">
					<form action="" method="post">
						<span style="float:left;">Login/Register</span>
						<?php
							if(isset($loginState)){
								print($loginState);
							}
						?>
						<input type="text" name="u" size="7" />
						<input type="password" name="p" size="7"/>
						<input type="submit" name="l" value="Send" />
					</form>
				</div>
				<?php
			}
		?>
		
		<div id="footer" class="box bottomcorner">
			<a href="source.7z">Get source (PHP/SQLite)</a>  | <em>Source may contain strong language. Not suitable for children (emotional or otherwise).</em> Hall sketch vectorization by klaxa
		</div>
	</body>
</html>