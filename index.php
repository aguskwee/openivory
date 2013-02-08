<html><head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<title>Open Ivory</title>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="css/index.css">

<script language="javascript" type="text/javascript" charset="utf-8" src="js/jquery-1.8.3.min.js"></script>
<script language="javascript" type="text/javascript" charset="utf-8" src="js/jquery.cookie.js"></script>
<script language="javascript" type="text/javascript" charset="utf-8" src="js/bootstrap.js"></script>
<script language="javascript" type="text/javascript" charset="utf-8">
$(function() {
	// check whether user has already login
	// go to home if he / she has already logged in
	if($.cookie('author_id')) {document.location.href = 'home.php'; return;}
	
	// add header
	$('#header').load('header.php');
	
	// add sidebar
	$('#sidebar').load('sidebar.php');
	
	// set loading bar
	$('#main').html('<h2>Retrieving latest publications...</h2>');
	
	// set main content
	$('#main').load('front.php');
});
</script>
</head>
<body>
<div id="header"></div> <!-- header -->
<div id="sidebar"></div> <!-- sidebar -->
<div id="main"></div> <!-- main -->
</body>
</html>
