<?php
	function check_user() {
		// show warning / error messages
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		
		// include library
		include 'lib/utils.php';

		// connect to database
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) return;
		
		// set character set
		mysqli_set_charset($cid, 'utf8');

		// check for the same name
		if(isset($_COOKIE['sign_email'])) $email = $_COOKIE['sign_email'];
		if(isset($_COOKIE['sign_given'])) $given_name = $_COOKIE['sign_given'];
		if(isset($_COOKIE['sign_last'])) $last_name = $_COOKIE['sign_last'];
		if(!isset($given_name) || !isset($last_name)) {
			echo json_encode(array('error_msg' => 'Error processing your request!')); 
			mysqli_close($cid); 
			return;
		}
		$sql = 'select author_id, concat(given_name, \' \', last_name) as name from authors where email <> \'' . $email . '\' and given_name = \'' . $given_name . '\' and last_name = \'' . $last_name . '\'';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo json_encode(array('error_msg' => 'Error processing your request!')); mysqli_close($cid); return;}
		$existing_authors = array();
		while($row = mysqli_fetch_assoc($result)) {
			$aid = $row['author_id'];
			$name = $row['name'];
			$existing_authors[$aid] = $name;
		}
		mysqli_free_result($result);
		
		// get latest spu if any
		if(count($existing_authors) == 0) {
			echo json_encode(array('new_user' => 1));
			return;
		}
		
		$sql = 'select spu_id, author_id from authorships where author_id in (\'' . implode('\', \'', array_keys($existing_authors)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo json_encode(array('error_msg' => 'Error processing your request!')); mysqli_close($cid); return;}
		$spus = array();
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$author_id = $row['author_id'];
			if(!isset($spus[$spu_id])) $spus[$spu_id] = array();
			if(isset($spus[$spu_id]['authors'])) $authors = $spus[$spu_id]['authors'];
			else $authors = array();
			$authors[$author_id] = array();
			$spus[$spu_id]['authors'] = $authors;
		}
		mysqli_free_result($result);

		// get title
		$sql = 'select spu_id, title from spu where spu_id in (\'' . implode('\', \'', array_keys($spus)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo json_encode(array('error_msg' => 'Error processing your request!')); mysqli_close($cid); return;}
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$title = $row['title'];
			if(!isset($spus[$spu_id])) continue;
			$spus[$spu_id]['title'] = $title;
		}
		mysqli_free_result($result);

		// get all authors
		$sql = 'select spu_id, author_id, rank from authorships where spu_id in (\'' . implode('\', \'', array_keys($spus)) . '\');';	
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo json_encode(array('error_msg' => 'Error processing your request!')); mysqli_close($cid); return;}
		$allauthors = array();
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$author_id = $row['author_id'];
			$rank = $row['rank'];
			if(isset($spus[$spu_id]['authors'])) $authors = $spus[$spu_id]['authors'];
			else $authors = array();
			$authors[$author_id] = array('id' => $author_id, 'rank' => $rank);
			$spus[$spu_id]['authors'] = $authors;
			$allauthors[$author_id] = '';
		}
		mysqli_free_result($result);

		// get all author's name
		$sql = 'select author_id, concat(given_name, \' \', last_name) as name from authors where author_id in (\'' . implode('\', \'', array_keys($allauthors)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo json_encode(array('error_msg' => 'Error processing your request!')); mysqli_close($cid); return;}
		while($row = mysqli_fetch_assoc($result)) {
			$author_id = $row['author_id'];
			$name = $row['name'];
			if(isset($allauthors[$author_id])) $allauthors[$author_id] = $name;
		}
		mysqli_free_result($result);

		// set author name
		foreach($spus as $spu_id => $obj) {
			$authors = $obj['authors'];
			foreach($authors as $author_id => $arr) {
				if(!isset($allauthors[$author_id])) continue;
				$authors[$author_id]['name'] = $allauthors[$author_id];
			}
			$spus[$spu_id]['authors'] = $authors;
		}
		
		// close connection
		mysqli_close($cid);
		
		$existauthors = array();
		foreach($existing_authors as $author_id => $name) {
			$existauthors[$author_id] = array();
			foreach($spus as $spu_id => $obj) {
				$authors = $obj['authors'];
				if(!isset($authors[$author_id])) continue;
				array_push($existauthors[$author_id], $spus[$spu_id]);
			}
		}
		
		echo json_encode($existauthors);
	}
?>

<html>
<head>
<title>Registration - Step 2</title>
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery.cookie.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
var topk = 2;
$(function() {
	// add header
	$('#header').load('header.php');
	
	// add sidebar
	$('#sidebar').load('sidebar.php');
	
	// add main content, depends whether name is already existed, or not
	var content = <?php check_user(); ?>;
	if((content == null) || (content.length == 0)) return;
	
	var div = $('<div></div>');
	if(size(content) > 0) {
		// 1. new user
		if(content['new_user'] == 1) document.location = 'signup3.php';
		// 2. similar name exists
		else {
			var title = $('<h2>Is this you?</h2>');
			var list = $('<div></div>');
			$('#main').append(title).append(list);
			display_list(list, content);
		}
	}	
});

function display_list(list, content) {
	// get author name
	var author_name = $.cookie('sign_given') + ' ' + $.cookie('sign_last');
	
	for(var author_id in content) {
		var spus = content[author_id];
		var div = $('<div><h4 style=\'display:inline-block; margin-right:15px\'>' + author_name + '</h4><a href=\'signup3.php?i=' + author_id + '\'><span>This is me!</span></a></div>');
		for(var idx in spus) {
			var spu = spus[idx];
			var title = spu['title'];
			var spu_id = spu['id'];
			var authors = spu['authors'];
			var authorarr = new Array();
			for(var author_id in authors) {
				var name = authors[author_id]['name'];
				authorarr.push('<a>' + name + '</a>');
			}
			var spuDiv = $('<div></div>');
			spuDiv.append('<div class=\'spu_box\'><p>' + title + '<br>' + authorarr.join(', ') + '</p></div><br>');
			div.append(spuDiv);
			if(div.find('.spu_box').length == topk) break;
		}
		if($(div).find('.spu_box').length > 0) list.append(div);
	}
	
	list.append('<a href=\'signup3.php\'>None of the above, continue >></a>');
}

function size(arr) {
	var count = 0;
	for(var i in arr) count++;
	return count;
}
</script>
</head>
<body>
	<div id="header"></div> <!-- header -->
	<div id="sidebar"></div> <!-- sidebar -->
	<div id="main"></div> <!-- main -->
</body>
</html>

