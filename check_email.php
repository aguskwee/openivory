<?php
	// show warning / error messages
	error_reporting(E_ALL);
	ini_set('display_errors', true);
		
	// include library
	include 'lib/utils.php';

	$is_exist = 0;
	
	// get email
	if(isset($_REQUEST['addr'])) $email = $_REQUEST['addr'];
	if(!isset($email)) {return;}

	// connect to database
	$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
	if(!$cid) return;
		
	// set character set
	mysqli_set_charset($cid, 'utf8');

	// check whether email has already existed
	$sql = 'select concat(given_name, \' \', last_name) as name from authors where email = \'' . $email . '\'';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo json_encode(array('error_msg' => 'Error processing your request!')); mysqli_close($cid); return;}
	if($row = mysqli_fetch_assoc($result)) {
		$name = $row['name'];
	}
	mysqli_free_result($result);
		
	// close mysqli
	mysqli_close($cid);
	
	if(isset($name)) {
		$is_exist = 1;
	}
	
	echo $is_exist;
?>