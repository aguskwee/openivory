<?php

?>

<html>
<head>
<title>OpenIvory - Changing current password</title>
<style type='text/css'>
	.custom_input {margin:auto auto 10px 30px !important; display:block !important; height:auto !important;}
	#submit_btn {margin-left: 170px;}
	#err_txt {margin-left:30px;color:red; display:none; line-height:2}
</style>
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />

<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/bootstrap.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
$(function() {
	// load header
	$('#header').load('header.php');
	
	// load sidebar
	$('#sidebar').load('sidebar.php');
	
	// add listener to submit button
	$('#submit_btn').off('click').on('click', function() {
		// verify the new password is valid (comparing both passwords)
		if($.trim($('#password').val()).length == 0) {
			$('#err_txt').text('Password cannot be empty!').css('display', 'block');
			$('#err_txt').delay(3000).fadeOut('slow', function() {$(this).text("");});
			return;
		}
		else {
			var pass1 = $.trim($('#password').val());
			var pass2 = $.trim($('#password2').val());
			if(pass1 != pass2) {
				$('#err_txt').text('Password does not match!').css('display', 'block');
				$('#err_txt').delay(3000).fadeOut('slow', function() {$(this).text("");});
				return;
			}
		}
		
		$.ajax({
			'url': 'submitpasswordchange.php',
			'type': 'POST',
			'data': {'newpass': $.trim($('#password').val())},
			'success': function(rv) {
				if((rv == null) || (rv == 0)) {
					$('#err_txt').text("Error updating your password!").css('display', 'block');
					$('#err_txt').delay(3000).fadeOut('slow', function() {$(this).text("");});
					return;
				}
				document.location.href = 'home.php';
			}
		});
	});
});
</script>
</head>
<body>
	<div id="header"></div>
	<div id="sidebar"></div>
	<div id="main">
		<h2 style='margin:20px auto 20px 30px'>Password Change</h2>
		<div class='form-horizontal'>
			<input class='custom_input input-xlarge' type='password' id='password' placeholder='Type your new password...'>
			<input class='custom_input input-xlarge' type='password' id='password2' placeholder='Confirm your new passoword...'>
			<span id='err_txt'>kghjkghjkl</span>
			<button id='submit_btn' type='button' class='btn'>Submit Changes</button>
		</div>		
	</div>
</body>
</html>