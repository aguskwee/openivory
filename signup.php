<html>
<head>
<title>openIvory - Registration Page</title>
<style type="text/css">
	.control-div {margin-left:30px}
	#next_btn {margin-left: 363px;}
	.err-msg {color:red; font-size:0.8em; font-style:italic}
</style>
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />

<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/bootstrap.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/date_format.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery.cookie.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
$(function() {
	// load header
	$('#header').load('header.php');
	
	// load sidebar
	$('#sidebar').load('sidebar.php');
	
	// add action listener to next button
	$('#next_btn').off('click').on('click', function() {		
		// return to normal border
		var div = $('.form-horizontal').first();
		div = div.find('.control-group').removeClass('error');
		
		// check for empty given / last name
		if(($.trim($('#given_name').val()).length == 0) || ($.trim($('#last_name').val()).length == 0)) {
			var span = $('#name_err');
			display_err(span, 'Given / last name cannot be empty!', 0);
			return;
		}
		
		// check for empty email
		if($.trim($('#email').val()).length == 0) {
			var span = $('#email_err');
			display_err(span, 'Email address cannot be empty!', 1);
			return;
		}
		else if($.trim($('#email').val()).length > 0) {
			var regex = /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/gi;
			var match = regex.test($('#email').val());
			
			if(match) { // email is valid
				var ext = $('#email').val().split('@')[1];
				var list = ext.split(".");
				var isValid = false;
				for(var i in list) {
					var str = list[i];
					if(str.toLowerCase() == 'edu') {
						isValid = true;
						break;
					}
				}
				if(!isValid) {
					var span = $('#email_err');
					display_err(span, 'Currently only supports .edu domain!', 1);
					return;
				}
			}
			else {
				var span = $('#email_err');
				display_err(span, 'Invalid email address!', 1);
				return;
			}
		}
		
		// check if pasword is empty
		if(($.trim($('#password').val()).length == 0) && ($.trim($('#confirm_password').val()).length == 0)) {
			var span = $('#password_err');
			display_err(span, 'Password cannot be empty!', 2);
			return;
		}
		else {
			var pass = $.trim($('#password').val());
			var confirmpass = $.trim($('#confirm_password').val());
			if(pass != confirmpass) {
				var span = $('#password_err');
				display_err(span, 'Password does not match!', 2);
				return;
			}
		}
		
		// disable button
		$(this).attr('disabled', 'disabled');
		
		$.get('check_email.php?addr=' + $.trim($('#email').val()), function(rv) {
			$('#next_btn').removeAttr('disabled');
			if(!rv) {
				var span = $.trim('#email_err');
				display_err(span, 'Error processing your request!', 1);
				return;
			}
			else if(rv == 1) {
				var span = $.trim('#email_err');
				display_err(span, 'Email address already exists!', 1);
				return;
			}
			
			// go to step 2 registration
			// set all parameter in cookies
			$.cookie('sign_email', $('#email').val(), {expires: 20 * 365});
			$.cookie('sign_given', $('#given_name').val(), {expires: 20 * 365});
			$.cookie('sign_last', $('#last_name').val(), {expires: 20 * 365});
			$.cookie('sign_pass', $('#password').val(), {expires: 20 * 365});
			document.location = 'signup2.php';
		});
	});
	
	// function to display error
	function display_err(span, err, idx) {
		span = $(span);
		span.text(err);
		span.css('display', 'inline');
		span.delay(2000).fadeOut('slow', function() {$(this).text('');});
		var div = $('.form-horizontal').first();
		div = div.find('.control-group').eq(idx);
		div.addClass('error');
		div.delay(2000).queue(function() {$(div).removeClass('error');});
	}
});	
</script>
</head>
<body>
<div id="header"></div>
<div id="sidebar"></div>
<div id="main">
	<h2 style='margin:20px auto 20px 30px'>Register a new user</h2>
	<div class='form-horizontal'>
		<div class='control-group'>
			<div class='control-div'>
				<input type='text' id='given_name' placeholder='Given name'>
				<input type='text' id='last_name' placeholder='Last name'>
				<span class='err-msg' id='name_err'></span>
			</div>
		</div>
		<div class='control-group'>
			<div class='control-div'>
				<input type='text' id='email' placeholder='Email'>
				<span style='font-size:0.8em;font-style:italic'>* Currently only supports .edu domain</span>
				<span class='err-msg' id='email_err'></span>
			</div>
		</div>
		<div class='control-group'>
			<div class='control-div'>
				<input type='password' id='password' placeholder='Password'>
				<input type='password' id='confirm_password' placeholder='Confirm password'>
				<span class='err-msg' id='password_err'></span>
			</div>
		</div>
		<div class='control-group'>
			<div class='control-div'>
				<button id='next_btn' type='button' class='btn'>Next</button>
			</div>
		</div>
	</div>		
</div>
</body>
</html>
