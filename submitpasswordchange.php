<?php
	// warning messages
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	// include library
	include 'lib/utils.php';
	
	// get new password and author id
	if(isset($_REQUEST['newpass'])) $newpass = $_REQUEST['newpass'];
	if(isset($_COOKIE['author_id'])) $author_id = $_COOKIE['author_id'];
	if(!isset($newpass) || !isset($author_id)) {echo 0; return;}
	
	// connect to database
	$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
	if(!$cid) {echo 0; return;}
	
	// select character set
	mysqli_set_charset($cid, "utf8");
	
	$sql = 'update authors set password = \'' . $newpass . '\' where author_id = \'' . $author_id . '\'';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo 0; return;}
	mysqli_free_result($result);
	
	// close connection
	mysqli_close($cid);
	
	echo 1;
?>