<?php
	// warning messages
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	// get user inputs
	if(isset($_REQUEST['author_id'])) $author_id = $_REQUEST['author_id'];
	if(!isset($author_id)) {echo 0; return;}
	
	// include library
	include 'lib/utils.php';
	
	// create db connection
	$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
	if(!$cid) {echo 0; return;}
	
	// set charset
	mysqli_set_charset($cid, 'utf8');
	
	// setup sql
	$time = time();
	$sql = 'update authors set password = \'\', email = \'\', deleted_at = from_unixtime(' . $time . ') where author_id = \'' . $author_id . '\'';
	
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo 0; return;}
	
	// close connection 
	mysqli_close($cid);
	
	echo 1; 
?>