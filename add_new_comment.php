<?php
	// warning messages
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	// get user inputs
	if($_REQUEST['author_id']) $author_id = $_REQUEST['author_id'];
	if($_REQUEST['spu_id']) $spu_id = $_REQUEST['spu_id'];
	if($_REQUEST['content']) $content = $_REQUEST['content'];
	if($_REQUEST['timestamp']) $timestamp = $_REQUEST['timestamp'];
	
	if(!isset($author_id) || !isset($spu_id) || !isset($content) || !isset($timestamp)) {
		echo 0; return;
	}
	
	// include library
	include('lib/utils.php');
	
	// connect to database
	$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
	if(!$cid) {echo 0; return;}
	
	// post comment
	$sql = 'insert into comments (author_id, spu_id, content, timestamp) values (\'' . $author_id . 
		   '\', \'' . $spu_id . '\', \'' . $content . '\', from_unixtime(' . $timestamp . '));';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo 0; return;}
	
	echo 1;
	
	// close database
	mysqli_close($cid);
?>