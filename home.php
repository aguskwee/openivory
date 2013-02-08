<?php
	// warning messages
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	// include library
	include 'lib/utils.php';
	
	// get logged in user, if any
	if(isset($_COOKIE['author_id'])) $current_author_id = $_COOKIE['author_id']; 
	if(!isset($current_author_id)) $current_author_id = '';
	
	$recentk = 5;
	
	// get the recently added articles
	function get_recentk_articles() {
		global $dbhost;
		global $dbusername;
		global $dbpassword;
		global $dbname;
		global $recentk;

		// connect to database
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) return;
	
		// set character set
		mysqli_set_charset($cid, 'utf8');
		
		$article_order = array();
		
		// get recent 3 articles
		$sql = 'select spu_id, unix_timestamp(timestamp) as time from spu_versions where version = 1 order by timestamp desc limit ' . $recentk;
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading versions table!'));}
		$spus = array();
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$timestamp = $row['time'];
			$spus[$spu_id] = array('id' => $spu_id, 'timestamp' => Timesince($timestamp));
			array_push($article_order, $spu_id);
		}
		mysqli_free_result($result);
		
		// get last updated timestamp
		$sql = 'select spu_id, max(unix_timestamp(timestamp)) as time from spu_versions where spu_id in (\'' . implode('\', \'', array_keys($spus)) . '\') group by spu_id';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retrieving version table!</h3>'; return;}
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$time = $row['time'];
			$spus[$spu_id]['last_updated'] = Timesince($time);
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
		
		// close connection
		mysqli_close($cid);
		
		// re sort articles
		$sorted_spus = array();
		foreach($article_order as $idx => $id) {
			array_push($sorted_spus, $spus[$id]);
		}
		
		// return result
		return json_encode($sorted_spus);
	}
	
	// get articles which has recent likes
	function get_recentk_likes() {
		global $dbhost;
		global $dbusername;
		global $dbpassword;
		global $dbname;
		global $recentk;
		global $current_author_id;

		if($current_author_id == '') return array();
		
		// connect to database
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) return;
	
		// set character set
		mysqli_set_charset($cid, 'utf8');
		
		$article_order = array();
		
		// get recent likes
		$sql = 'select spu_id, max(unix_timestamp(timestamp)) as time from likes where author_id = \'' . $current_author_id . '\' group by spu_id order by timestamp desc limit ' . $recentk;
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading like table!'));}
		$spus = array();
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$timestamp = $row['time'];
			$spus[$spu_id] = array('id' => $spu_id, 'timestamp' => Timesince($timestamp));
			array_push($article_order, $spu_id);
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
		$sql = 'select aship.spu_id as spu_id, aship.author_id as author_id, concat(a.given_name, \' \', a.last_name) as name, aship.rank ' .
			   'from authorships aship, authors a where aship.author_id = a.author_id and aship.spu_id in (\'' .
			   implode('\', \'', array_keys($spus)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading authors table!'));}
		while($row = mysqli_fetch_assoc($result)) {
			$author_id = $row['author_id'];
			$author_name = $row['name'];
			$rank = $row['rank'];
			$spu_id = $row['spu_id'];
			$authors = array();
			if(isset($spus[$spu_id]['authors'])) $authors = $spus[$spu_id]['authors'];
			$authors[$author_id] = array('id' => $author_id, 'name' => $author_name, 'rank' => $rank);
			$spus[$spu_id]['authors'] = $authors;
		}
		mysqli_free_result($result);
		
		// close connection
		mysqli_close($cid);
		
		// re sort articles
		$sorted_spus = array();
		foreach($article_order as $idx => $id) {
			array_push($sorted_spus, $spus[$id]);
		}
		
		// return result
		return json_encode($spus);
	}
	
	// get articles which has recent likes
	function get_recentk_comments() {
		global $dbhost;
		global $dbusername;
		global $dbpassword;
		global $dbname;
		global $recentk;
		global $current_author_id;

		if($current_author_id == '') return array();
		
		$article_order = array();
		
		// connect to database
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) return;
	
		// set character set
		mysqli_set_charset($cid, 'utf8');
		
		// get recent comments
		$sql = 'select spu_id, max(unix_timestamp(timestamp)) as time from comments where author_id = \'' . $current_author_id . '\' group by spu_id order by timestamp desc limit ' . $recentk;
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading like table!'));}
		$spus = array();
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$timestamp = $row['time'];
			$spus[$spu_id] = array('id' => $spu_id, 'timestamp' => Timesince($timestamp));
			array_push($article_order, $spu_id);
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
		$sql = 'select aship.spu_id as spu_id, aship.author_id as author_id, concat(a.given_name, \' \', a.last_name) as name, aship.rank ' .
			   'from authorships aship, authors a where aship.author_id = a.author_id and aship.spu_id in (\'' .
			   implode('\', \'', array_keys($spus)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading authors table!'));}
		while($row = mysqli_fetch_assoc($result)) {
			$author_id = $row['author_id'];
			$author_name = $row['name'];
			$rank = $row['rank'];
			$spu_id = $row['spu_id'];
			$authors = array();
			if(isset($spus[$spu_id]['authors'])) $authors = $spus[$spu_id]['authors'];
			$authors[$author_id] = array('id' => $author_id, 'name' => $author_name, 'rank' => $rank);
			$spus[$spu_id]['authors'] = $authors;
		}
		mysqli_free_result($result);
		
		// close database
		mysqli_close($cid);
		
		// re sort articles
		$sorted_spus = array();
		foreach($article_order as $idx => $id) {
			array_push($sorted_spus, $spus[$id]);
		}
		
		// return result
		return json_encode($spus);
	}
?>

<html>
<head>
<title>openIvory - Home</title>
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery.cookie.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
$(function() {
	// check if the user has already logged in.
	// if not, go to login page
	if(!$.cookie('author_id')) {document.location.href = 'login.php'; return;}
	
	// add header
	$('#header').load('header.php');
	
	// add side bar
	$('#sidebar').load('sidebar.php?' + (new Date()).getTime());
	
	// add list of recently uploaded SPU
	var recent_spus = <?php echo get_recentk_articles(); ?>;
	var spus_div = '';
	if(recent_spus) {
		if(size(recent_spus) > 0) {
			spus_div = format_articles('article', recent_spus);
		}
	}
	
	// add list of recently commented article by current user
	var recent_commented = <?php echo get_recentk_comments(); ?>;
	var comments_div = '';
	if(recent_commented) {
		if(size(recent_commented) > 0) {
			comments_div = format_articles('comment', recent_commented);
		}
	}
	
	// add list of recently liked article by current user
	var recent_likes = <?php echo get_recentk_likes(); ?>;
	var likes_div = '';
	if(recent_likes) {
		if(size(recent_likes) > 0) {
			likes_div = format_articles('like', recent_likes);
		}
	}

	var content = '';
	if(spus_div.length > 0) {
		content += '<h4>Recently uploaded articles</h4>' + spus_div;
	}
	if(likes_div.length > 0) {
		content += '<h4>Recently liked articles</h4>' + likes_div;
	}
	if(comments_div.length > 0) {
		content += '<h4>Recently commented articles</h4>' + comments_div;
	}

	$('#main').html(content);
	
	var sidebar_height = $('#sidebar').height();
	var content_height = $('#main').height();
	if(sidebar_height < content_height) $('#sidebar').css('height', content_height + 'px');
});	

function format_articles(type, arr) {
	var content = '';
	for(var i in arr) {
		var obj = arr[i];
		var id = obj['id'];
		var title = obj['title'];
		var timestamp = obj['timestamp'];
		var lastUpdate = obj['last_update'];
		var author_map = obj['authors'];
		var authors = new Array();
		var author_arr = new Array();
		for(var i in author_map) author_arr.push(author_map[i]);
		author_arr.sort(function(a, b) {return a.rank - b.rank});
		
		for(var i in author_arr) {
			var author = author_arr[i];
			var author_id = author['id'];
			var author_name = author['name'];
			authors.push('<a href=\'author_detail.php?id=' + author_id + '\'>' + author_name + '</a>');
		}
		authors = authors.join(', ');
		
		var p = ''
		if(type == 'article') {
			p = '<div class=\'spu_box\'><p><a href=\'spu_detail.php?id=' + id + '\'>' + title + '</a><br>Authors: ' + authors + '<br>First uploaded ' + timestamp + '</p></div>';
			if(lastUpdate && (lastUpdate != '') && (lastUpdate != timestamp)) 
				p = '<div class=\'spu_box\'><p><a href=\'spu_detail.php?id=' + id + '\'>' + title + '</a><br>Authors: ' + authors + '<br>First uploaded ' + timestamp + '<br>Last updated on ' + lastUpdate + '</p></div>';
		}
		else if(type == 'like') {
			p = '<div class=\'spu_box\'><p><a href=\'spu_detail.php?id=' + id + '\'>' + title + '</a><br>Authors: ' + authors + '<br>Last liked ' + timestamp + '</p></div>';
		}
		else if(type == 'comment') {
			p = '<div class=\'spu_box\'><p><a href=\'spu_detail.php?id=' + id + '\'>' + title + '</a><br>Authors: ' + authors + '<br>Last commented ' + timestamp + '</p></div>';
		}
		content += p + '<br>';
	}

	return content;
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
