<?php
	function process_request() {
		// show warning message
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		
		// get user input
		if(isset($_REQUEST['i'])) $author_id = $_REQUEST['i'];
		if(isset($_COOKIE['sign_given'])) $given_name = $_COOKIE['sign_given'];
		if(isset($_COOKIE['sign_last'])) $last_name = $_COOKIE['sign_last'];
		if(isset($_COOKIE['sign_email'])) $email = $_COOKIE['sign_email'];
		if(isset($_COOKIE['sign_pass'])) $password = $_COOKIE['sign_pass'];

		// include library
		include 'lib/utils.php';
		
		// connect to database
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) {echo json_encode(array('error_msg' => 'Error processing your request!')); return;}
		
		// select character set
		mysqli_set_charset($cid, 'utf8');
		
		// set sql
		if(isset($author_id)) $sql = 'update authors set email = \'' . $email . '\', given_name = \'' . $given_name . '\', last_name = \'' . $last_name . 
									 '\', password = \'' . $password . '\' where author_id = \'' . $author_id . '\'';
		else $sql = 'insert into authors (given_name, last_name, email, password, `timestamp`) values (\'' . $given_name . '\', \'' . $last_name .
					'\', \'' . $email . '\', \'' . $password . '\', now())';
		
		// insert / update database
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo json_encode(array('error_msg' => 'Error processing your request!')); mysqli_close($cid); return;}
		
		// get author id
		if(!isset($author_id)) $author_id = mysqli_insert_id($cid);
		
		// close connection
		mysqli_close($cid);
		
		// remove all cookies
		setcookie('sign_email', '', time() - 3600);
		setcookie('sign_given', '', time() - 3600);
		setcookie('sign_last', '', time() - 3600);
		setcookie('sign_pass', '', time() - 3600);
		
		// set cookies
		setcookie('email', $email, 0, '');
		setrawcookie('author', rawurlencode(trim($given_name . ' ' . $last_name)), 0, '');
		setcookie('author_id', $author_id, 0, '');
		
		echo 1;
		return;
	}
?>

<html>
<head>
<title>Registration - Final</title>
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery.cookie.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
$(function() {
	// add header
	$('#header').load('header.php');
	
	// add sidebar
	$('#sidebar').load('sidebar.php');
	
	// add main content
	var content = <?php process_request(); ?>;
	if(!content || (content.length == 0)) {
		$('#main').append('<h3>Error processing your request!</h3>');
		return;
	}
	
	if(content && content['error_msg']) {
		$('#main').append('<h3>' + content['error_msg'] + '</h3>');
		return;
	}
	
	$('#main').append('<h4>Thank you for registering into our system.</h4><span>Click <a href=\'home.php\'>here</a> to go to your homepage.</span>');
});
</script>
</head>
<body>
	<div id="header"></div> <!-- header -->
	<div id="sidebar"></div> <!-- sidebar -->
	<div id="main"></div> <!-- main -->
</body>
</html>

