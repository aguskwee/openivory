<?php
	// display error message
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	// include library
	include 'lib/utils.php';
	
	$is_success = 0;
	
	// get user input
	if(isset($_REQUEST['fid'])) $fid = $_REQUEST['fid'];
	if(!isset($fid)) {echo $is_success; return;}
	if(isset($_REQUEST['follow'])) $action = $_REQUEST['follow'];
	if(!isset($action)) {echo $is_success; return;}
	if(isset($_COOKIE['author_id'])) $author_id = $_COOKIE['author_id'];
	if(!isset($author_id)) {echo $is_success; return;}
	
	// connect to database
	$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
	if(!$cid) {echo $is_success; return;}
	
	// set character set
	mysqli_set_charset($cid, 'utf8');
	
	$sql = 'insert ignore into following (from_id, to_id, timestamp, is_active) values(\'' . $author_id . '\', \'' . $fid . '\', '
		   . 'current_timestamp(), \'' . $action . '\');';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo $is_success; return;}
	$is_success = 1;
	
	// close connection
	mysqli_close($cid);
	
	echo $is_success;
?>