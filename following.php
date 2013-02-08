<?php
	// display error messages
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	// include library
	include 'lib/utils.php';
	
	// get current user id
	if(isset($_COOKIE['author_id'])) $author_id = $_COOKIE['author_id'];
	if(!isset($author_id)) $author_id = '';
	
	function get_follow_users() {
		global $author_id;
		global $dbhost;
		global $dbusername;
		global $dbpassword;
		global $dbname;
		
		// connect to database
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) {echo json_encode(array('error_msg' => 'Error connecting to database!')); return;}
		
		// set character set
		mysqli_set_charset($cid, 'utf8');
		
		$sql = 'select f.to_id, max(f.timestamp) as time, f.is_active, concat(a.given_name, \' \', a.last_name) as name from following f, authors a ' .
			   'where f.from_id = \'' . $author_id . '\' and f.to_id = a.author_id group by f.from_id, f.to_id having f.is_active = 1'; 
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo json_encode(array('error_msg' => 'Error executing query!')); return;}
		$following = array();
		while($row = mysqli_fetch_assoc($result)) {
			$fid = $row['to_id'];
			$time = $row['time'];
			$name = $row['name'];
			$following[$fid] = array('id' => $fid, 'name' => $name, 'time' => $time);
		}
		mysqli_free_result($result);
		
		// send result to client
		echo json_encode($following);
		
		// close connection
		mysqli_close($cid);
	}
	
	function get_commented_articles() {
		global $dbhost;
		global $dbusername;
		global $dbpassword;
		global $dbname;
		
		ob_start();
		get_follow_users();
		$followees = ob_get_contents();
		ob_end_clean();
		
		if(!isset($followees)) {
			echo json_encode(array());
			return;
		}
		$followees = json_decode($followees, true);
		if(!$followees) {
			echo json_encode(array());
			return;
		}
		
		// connect to database
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) return;
	
		// set character set
		mysqli_set_charset($cid, 'utf8');
		
		// get commented articles
		$sql = 'select spu_id, max(unix_timestamp(timestamp)) as time from comments where author_id in (\'' . implode('\', \'', array_keys($followees)) . '\') group by spu_id order by timestamp desc';
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading like table!'));}
		$latest = 1;
		$spus = array();
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$timestamp = $row['time'];
			$spus[$spu_id] = array('id' => $spu_id, 'timestamp' => Timesince($timestamp), 'rank' => $latest);
			$latest++;
		}
		mysqli_free_result($result);
		
		if(count($spus) == 0) {
			echo json_encode(array());
			return;
		}
		
		// get title of the articles
		$sql = 'select spu_id, title from spu where spu_id in (\'' . implode('\', \'', array_keys($spus)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading article table!'));}
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$title = $row['title'];
			if(!isset($spus[$spu_id])) continue;
			$spus[$spu_id]['title'] = $title;
		}
		mysqli_free_result($result);
		
		// get authors
		$sql = 'select aship.spu_id as spu_id, aship.author_id as author_id, concat(a.given_name, \' \', a.last_name) as name, aship.rank ' .
			   'from authorships aship, authors a where aship.author_id = a.author_id and aship.spu_id in (\'' .
			   implode('\', \'', array_keys($spus)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading authors table!'));}
		while($row = mysqli_fetch_assoc($result)) {
			$author_id = $row['author_id'];
			$author_name = $row['name'];
			$spu_id = $row['spu_id'];
			$rank = $row['rank'];
			$authors = array();
			if(isset($spus[$spu_id]['authors'])) $authors = $spus[$spu_id]['authors'];
			$authors[$author_id] = array('id' => $author_id, 'name' => $author_name, 'rank' => $rank);
			$spus[$spu_id]['authors'] = $authors;
		}
		mysqli_free_result($result);
		
		// close database
		mysqli_close($cid);
		
		// return result
		return json_encode($spus);
	}
	
	// get articles which has recent likes
	function get_liked_articles() {
		global $dbhost;
		global $dbusername;
		global $dbpassword;
		global $dbname;
		
		ob_start();
		get_follow_users();
		$followees = ob_get_contents();
		ob_end_clean();
		
		if(!isset($followees)) {
			echo json_encode(array());
			return;
		}
		$followees = json_decode($followees, true);
		if(!$followees) {
			echo json_encode(array());
			return;
		}
		
		// connect to database
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) return;
	
		// set character set
		mysqli_set_charset($cid, 'utf8');
		
		// get liked articles
		$sql = 'select spu_id, max(unix_timestamp(timestamp)) as time from likes where author_id in (\'' . implode('\', \'', array_keys($followees)) . '\') group by spu_id order by timestamp desc';
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading like table!'));}
		$spus = array();
		$latest = 1;
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$timestamp = $row['time'];
			$spus[$spu_id] = array('id' => $spu_id, 'timestamp' => Timesince($timestamp), 'rank' => $latest);
			$latest++;
		}
		mysqli_free_result($result);
		
		// get title of the articles
		$sql = 'select spu_id, title from spu where spu_id in (\'' . implode('\', \'', array_keys($spus)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading article table!'));}
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$title = $row['title'];
			if(!isset($spus[$spu_id])) continue;
			$spus[$spu_id]['title'] = $title;
		}
		mysqli_free_result($result);
		
		// get authors
		$sql = 'select aship.spu_id as spu_id, aship.author_id as author_id, concat(a.given_name, \' \', a.last_name) as name, aship.rank '.
			   'from authorships aship, authors a where aship.author_id = a.author_id and aship.spu_id in (\'' .
			   implode('\', \'', array_keys($spus)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading authors table!'));}
		while($row = mysqli_fetch_assoc($result)) {
			$author_id = $row['author_id'];
			$author_name = $row['name'];
			$spu_id = $row['spu_id'];
			$rank = $row['rank'];
			$authors = array();
			if(isset($spus[$spu_id]['authors'])) $authors = $spus[$spu_id]['authors'];
			$authors[$author_id] = array('id' => $author_id, 'name' => $author_name, 'rank' => $rank);
			$spus[$spu_id]['authors'] = $authors;
		}
		mysqli_free_result($result);
		
		// close connection
		mysqli_close($cid);
	
		// return result
		return json_encode($spus);
	}
?>

<html>
<head>
<title>openIvory - Following</title>
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
$(function() {
	// load header
	$('#header').load('header.php');
	
	// load sidebar
	$('#sidebar').load('sidebar.php?' + new Date().getTime());
	
	// load list of authors
	var div = $('<div></div>');
	div.append('<h3>Authors I follow</h3>');
	var following = <?php echo get_follow_users(); ?>;
	var authors = '';
	if(size(following) == 0) authors = '<h5><i>No following any authors!</i></h5>';
	else if(following['error_msg']) authors = '<h5><i>' + following['error_msg'] + '</i></h5>';
	else {
		var idx = 1;
		for(var id in following) {
			var name = following[id]['name'];
			authors += '<div style=\'margin:10px;display:inline-block\'>' + /*(idx++) +*/ '<a href=\'author_detail.php?id=' + id + '\'>' + name + '</a></div>';
		}
	}
	div.append(authors);
	$('#main').append(div);
	
	// add articles commented by the people whom are followed by the user
	var commented = <?php echo get_commented_articles(); ?>;
	
	if(size(commented) > 0) {
		var comment_div = $('<div></div>');
		comment_div.append('<h3>Commented articles by people I follow</h3>');
		format_articles(comment_div, 'comment', commented);
		$('#main').append(comment_div);
	}
	
	// add articles liked by the people whom are followed by the user
	var liked = <?php echo get_liked_articles(); ?>;
	if(size(liked) > 0) {
		var liked_div = $('<div></div>');
		liked_div.append('<h3>Liked articles by people I follow</h3>');
		format_articles(liked_div, 'like', liked);
		$('#main').append(liked_div);
	}
});

/* format article */
function format_articles(elm, type, map) {
	var arr = new Array();
	for(var id in map) arr.push(map[id]);
	arr.sort(function(a, b) {
		var rank_a = a['rank'];
		var rank_b = b['rank'];
		return rank_a - rank_b;
	});
	
	for(var idx in arr) {
		var obj = arr[idx];
		var id = obj['id'];
		var title = obj['title'];
		var timestamp = obj['timestamp'];
		var lastUpdate = obj['last_update'];
		var author_map = obj['authors'];
		var authors = new Array();
		var author_arr = new Array();
		for(var i in author_map) {author_arr.push(author_map[i]);}
		author_arr.sort(function(a, b) {return a.rank - b.rank;});
		
		for(var i in author_arr) {
			var author = author_arr[i];
			var author_id = author['id'];
			var author_name = author['name'];
			authors.push('<a href=\'author_detail.php?id=\'' + author_id + '>' + author_name + '</a>');
		}
		authors = authors.join(', ');
		
		var p = '';
		if(type == 'article') {
			p = '<div class=\'spu_box\'><p><a href=\'spu_detail.php?id=' + id + '\'>' + title + '</a><br>Authors: ' + authors + '<br>First uploaded ' + timestamp + '</p>';
			if(lastUpdate && (lastUpdate != '') && (lastUpdate != timestamp)) 
				p = '<div class=\'spu_box\'><p><a href=\'spu_detail.php?id=' + id + '\'>' + title + '</a><br>Authors: ' + authors + '<br>First uploaded ' + timestamp + '<br>Last updated on ' + lastUpdate + '</p>';
		}
		else if(type == 'like') {
			p = '<div class=\'spu_box\'><p><a href=\'spu_detail.php?id=' + id + '\'>' + title + '</a><br>Authors: ' + authors + '<br>Last like ' + timestamp + '</p>';
		}
		else if(type == 'comment') {
			p = '<div class=\'spu_box\'><p><a href=\'spu_detail.php?id=' + id + '\'>' + title + '</a><br>Authors: ' + authors + '<br>Last commented ' + timestamp + '</p>';
		}
		$(elm).append(p).append('<br>');
	}
}

/* function to count size of array / associative array */
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