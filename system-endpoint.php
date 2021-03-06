<?php
	/*
	seatping SYSTEM authentication endpoint
	
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

	if($auth_methods["system"]&&$_SERVER["PHP_AUTH_USER"]==$system_user&&$_SERVER["PHP_AUTH_PW"]==$system_password){
		//var_dump($_POST);
		$stmt=$db->prepare("INSERT INTO system (system_token, system_id, system_name) VALUES (:token, :id, :user)");
		if(!$stmt->execute(array(":token"=>$_POST["token"], ":user"=>$_POST["username"], ":id"=>intval($_POST["unique_id"])))){
			die("Failed to insert: ".json_encode($db->errorInfo()));
		}
	}

?>