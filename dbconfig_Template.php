<?php
	/**
	 * Created by PhpStorm.
	 * User: Luka
	 * Date: 17.02.2019
	 * Time: 10:27
	 */
	$db_host = "localhost";
	$db_name = "";
	$db_user = "";
	$db_pass = "";
	
	try{
		$db_con = new PDO("mysql:host={$db_host};dbname={$db_name}",$db_user,$db_pass);
		$db_con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db_con->exec("set names utf8");
	}catch(PDOException $e){
		echo $e->getMessage();
	}
?>