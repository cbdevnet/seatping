<?php
	/*
	seatping map  code v2
	
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
	
	$hall_data=$db->prepare("
		SELECT
			hall_id,
			hall_img,
			hall_name
		FROM halls
		WHERE
			hall_id=:hallid
	");
	
	$hall_data->execute(array(":hallid"=>intval($_GET["hall"])));
	$hall_data=$hall_data->fetch(PDO::FETCH_ASSOC);
	
	if($hall_data===false){
		$img=imagecreatefrompng("failed.png");
		imagepng($img);
		die();
	}
	
	if(!$img=imagecreatefrompng("img/".$hall_data["hall_img"])){
		$img=imagecreatefrompng("failed.png");
		imagepng($img);
		die();
	}
	
	$transparent=imagecolorallocate($img,255,255,255);
	imagecolortransparent($img,$transparent);
	
	$black=imagecolorallocate($img,0,0,0);
	$red=imagecolorallocate($img,255,0,0);
	
	imagestring($img,1,0,0,"Sketch of ".$hall_data["hall_name"],$black);
	
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
		
		$heat_data=$db->prepare("
			SELECT
				ping_x,
				ping_y
			FROM pings
			WHERE
				ping_hall=:hallid
				AND ping_date>:maxtime
				AND ping_public
		");
		
		$heat_data->execute(array(":hallid"=>$hall_data["hall_id"], ":maxtime"=>$maxtime));
		$heat_data=$heat_data->fetchAll(PDO::FETCH_ASSOC);
		
		imagestring($img,1,0,10,"Last ".$last_minutes." minutes",$black);
		//imagestring($img,1,0,20,"hallid=".$hall["hallid"]." date>".$maxtime,$black);
		
		if($heat_data!==false){
			foreach($heat_data as $point){
				//draw names
				//if($it["uid"]!=0){
				//	imagestring($img,1,$it["x"]-10,$it["y"]-15,$it["username"],$red);
				//}
				imagefilledellipse($img,$point["ping_x"],$point["ping_y"],10,10,$red);
			}
		}
	}
	
	imagepng($img);
	
?>