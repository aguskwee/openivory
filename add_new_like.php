<?php
	// show warning messages
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	// get user input
	if(isset($_REQUEST['author_id'])) $author_id = $_REQUEST['author_id'];
	if(isset($_REQUEST['spu_id'])) $spu_id = $_REQUEST['spu_id'];
	if(!isset($author_id) || !isset($spu_id)) {
		echo 0; 
		return;
	}
	
	// include library
	include 'lib/utils.php';
	
	// connect to mysqli
	$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
	if(!$cid) {echo 0; return;}
	
	// get current timestamp
	$timestamp = time();
	
	// insert record
	$sql = 'insert into likes (author_id, spu_id, `timestamp`) values (\'' . $author_id . '\', \'' . $spu_id . '\', from_unixtime(' . $timestamp . '))';
	mysqli_query($cid, $sql);
	
	// get updated like list
	$sql = 'select l.author_id, concat(a.given_name, \' \', a.last_name) as name from likes l, authors a where l.author_id = a.author_id  and l.spu_id = \'' . $spu_id . '\'';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo 0; return;}
	$list = array();
	while($row = mysqli_fetch_assoc($result)) {
		$author_id = $row['author_id'];
		$name = $row['name'];
		array_push($list, array('id' => $author_id, 'name' => $name));
	}
	mysqli_free_result($result);
	
	if(count($list) == 0) {echo 0; return;}
	
	echo json_encode($list);
?>