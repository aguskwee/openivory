<html>
<head>
<title>openIvory - Login Page</title>
<style type="text/css">
	.custom_input {margin:auto auto 10px 30px !important; display:block !important; height:auto !important;}
	#signin_btn {margin-left: 55px;}
	#err_txt {margin-left:30px;color:red; display:none; line-height:2}
	#signup_lnk {font-size:0.9em; margin-left:30px;}
</style>
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />

<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/bootstrap.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/date_format.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery.cookie.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
$(function() {
	// go to home if user has already logged in
	if($.cookie('author_id')) {document.location.href = 'home.php'; return;}

	// load header
	$('#header').load('header.php');
	
	// load sidebar
	$('#sidebar').load('sidebar.php');
	
	// add action listener
	$('#signin_btn').off('click').on('click', function() {
		// validate username and password
		if(($.trim($('#email').val()) == '') || ($.trim($('#password').val()) == '')) {
			$('#err_txt').text('Email or password cannot be empty!').css({'height': 'auto', 'display': 'block', 'margin': '-10px auto 10px auto !important'})
						 .delay(2000).fadeOut('slow', function() {$(this).css({'display': 'none', 'margin' : '0px'});});
			return;
		}
		
		$(this).text('Sign in').attr('disabled', 'disabled');
		$('#password_cb').attr('disabled', 'disabled');
		
		// send to server to verify
		$.ajax({
			'url': 'verify_user.php',
			'type': 'POST',
			'data': {'email': $('#email').val(), 'password': $('#password').val()},
			'success': function(status) {
				if(!status || (status == 2)) {
					$('#err_txt').text('Oops! Error verifying your account!').css({'height': 'auto', 'display': 'block', 'margin': '-10px auto 10px auto !important'})
					.delay(2000).fadeOut('slow', function() {$(this).css({'display': 'none', 'margin' : '0px'});});
					$('#signin_btn').text('Sign in').removeAttr('disabled');
					return;
				}
				status = $.parseJSON(status);
				
				if(status['is_valid'] == 0) {
					$('#err_txt').text('Incorrect email address or password!').css({'height': 'auto', 'display': 'block', 'margin': '-10px auto 10px auto !important'})
					.delay(2000).fadeOut('slow', function() {$(this).css({'display': 'none', 'margin' : '0px'});});
					$('#signin_btn').text('Sign in').removeAttr('disabled');
					$('#password_cb').removeAttr('disabled');
					return;
				}
				
				// set cookie
				$.cookie('email', $('#email').val(), {expires: 20 * 365});
				$.cookie('author', status['name'], {expires: 20 * 365});
				$.cookie('author_id', status['id'], {expires: 20 * 365});
				
				// check whether user wants to change the current password
				if(!$('#password_cb').is(':checked')) document.location.href = 'home.php';
				else document.location.href = 'changepassword.php';
			}
		});
	});
	
	$('#password').off('keyup').on('keyup', function(e) {
		if(e.keyCode == 13) $('#signin_btn').click();
	});
	
	// add action listener to add new account
	$('#signup_lnk').attr('href', 'signup.php');
});	
</script>
</head>
<body>
<div id="header"></div>
<div id="sidebar"></div>
<div id="main">
	<h2 style='margin:20px auto 20px 30px'>User Login</h2>
	<div class='form-horizontal'>
		<input class='custom_input input-xlarge' type='text' id='email' placeholder='Email'>
		<input class='custom_input input-xlarge' type='password' id='password' placeholder='Password'>
		<span id='err_txt'></span></div>
		<label class='checkbox' style='margin-left:30px;font-size:0.85em;line-height:1.9'><input id='password_cb' type='checkbox' value='changepass'>Change current password</label>
		<a id='signup_lnk'>Sign up for new account!</a>
		<button id='signin_btn' type='button' class='btn'>Sign in</button>
	</div>		
</div>
</body>
</html>
