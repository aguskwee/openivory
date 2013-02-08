<?php
	// show warning message
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	if(isset($_REQUEST['email'])) $email = $_REQUEST['email'];
	if(isset($_REQUEST['password'])) $password = $_REQUEST['password'];
	if(!isset($email) || !isset($password)) {echo 0; return;}
	
	// include library
	include 'lib/utils.php';
	
	// connect to mysql 
	$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
	if(!$cid) {echo 2; return;}
	
	// verify password
	$is_valid = 0;
	$sql = 'select author_id, concat(given_name, \' \', last_name) as name, password from authors where email = \'' . $email . '\'';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo 2; return;}
	if($row = mysqli_fetch_assoc($result)) {
		$pass = $row['password'];
		$name = $row['name'];
		$author_id = $row['author_id'];
		if($pass == $password) $is_valid = 1;
	}
	mysqli_free_result($result);
	
	// close connection
	mysqli_close($cid);
	
	// check whether name and author id exist
	if(!isset($author_id)) $author_id = '-1';
	if(!isset($name)) $name = 'NA';
	
	// set response
	$response = array('id' => $author_id, 'name' => $name, 'is_valid' => $is_valid);
	
	// send status
	echo json_encode($response);
?>