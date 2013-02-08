<?php
	// warning messages
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	// get user input
	if(isset($_REQUEST['id'])) $user_id = $_REQUEST['id'];
	if(isset($_REQUEST['given_name'])) $given_name = $_REQUEST['given_name'];
	if(isset($_REQUEST['last_name'])) $last_name = $_REQUEST['last_name'];
	if(isset($_REQUEST['email'])) $email = $_REQUEST['email'];
	if(isset($_REQUEST['department'])) $department = $_REQUEST['department'];
	if(isset($_REQUEST['institution'])) $institution = $_REQUEST['institution'];
	if(isset($_REQUEST['topics'])) $topics = $_REQUEST['topics'];
	if(!isset($user_id)) {echo 0; return;}
	
	$fields = array();
	if(isset($given_name)) array_push($fields, 'given_name = \'' . $given_name . '\'');
	if(isset($last_name)) array_push($fields, 'last_name = \'' . $last_name . '\'');
	if(isset($email)) array_push($fields, 'email = \'' . $email . '\'');
	if(isset($department)) array_push($fields, 'department = \'' . $department . '\'');
	if(isset($institution)) array_push($fields, 'institution = \'' . $institution . '\'');
	
	// include librarry
	include 'lib/utils.php';
	
	// connect to db
	$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
	if(!$cid) {echo 0; return;}
	
	// select charset
	mysqli_set_charset($cid, 'utf8');
	
	// setup sql
	$sql = 'update authors set ' . implode(', ', $fields) . ' where author_id = \'' . $user_id . '\'';
	
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo 0; mysqli_close($cid); return;}
	
	// add author topic
	$topics = explode(',', $topics);
	$sql = 'delete from author_topics where author_id = \'' . $user_id . '\'';
	mysqli_query($cid, $sql);
	
	foreach($topics as $i => $topic_id) {
		$sql = 'insert ignore into author_topics (`author_id`, `topic_id`) values (\'' . $user_id . '\', \'' . $topic_id . '\')';
		mysqli_query($cid, $sql);
	}
	
	// close connection
	mysqli_close($cid);
	
	echo 1;
	return;
?>