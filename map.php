<?php
	/*
	seatping map code. Not quite good. You have been warned.
	Needs php5-gd installed
	
	Licensed as WTFPL
	*/
	require_once("settings.php");
	
	try{
		$db=new SQLite3($dbfile);
	}
	catch(Exception $e){
		$img=imagecreatefrompng("failed.png");
		imagepng($img);
		die();
	}
	
	$db->busyTimeout(500);
	
	$hall=$db->querySingle("select * from halls where hallid=".intval($_GET["hall"]),TRUE);
	if($hall==NULL){
		$img=imagecreatefrompng("failed.png");
		imagepng($img);
		die();
	}
	
	if(!$img=imagecreatefrompng("img/".$hall["imgres"])){
		$img=imagecreatefrompng("failed.png");
		imagepng($img);
		die();
	}
	
	$transparent=imagecolorallocate($img,255,255,255);
	imagecolortransparent($img,$transparent);
	
	$black=imagecolorallocate($img,0,0,0);
	$red=imagecolorallocate($img,255,0,0);
	
	imagestring($img,1,0,0,"Sketch of ".$hall["name"],$black);
	
	if(isset($_GET["x"])&&isset($_GET["y"])){
		imagefilledellipse($img,intval($_GET["x"]),intval($_GET["y"]),10,10,$red);
	}
	
	if(isset($_GET["last"])){
		$last_minutes=intval($_GET["last"]);
		
		if($last_minutes!=0){
			$maxtime=time()-($last_minutes*60);
		}
		else{
			//heatmap
			$maxtime=0;
		}
		
		$data=$db->query("select * from composite where hallid=".$hall["hallid"]." and cdate>".$maxtime." and public=1");
		$it=$data->fetchArray();
		
		imagestring($img,1,0,10,"Last ".$last_minutes." minutes",$black);
		//imagestring($img,1,0,20,"hallid=".$hall["hallid"]." date>".$maxtime,$black);
		
		while($it!==FALSE){
			if($it["uid"]!=0){
				imagestring($img,1,$it["x"]-10,$it["y"]-15,$it["username"],$red);
			}
			imagefilledellipse($img,$it["x"],$it["y"],10,10,$red);
			$it=$data->fetchArray();
		}
	}
	
	imagepng($img);
	
?>