<?php
	// warning messages
	//error_reporting(E_ALL);
	//ini_set('display_errors', true);
	
	// include library
	include 'lib/utils.php';
	
	$recentk = 3;
	
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
		
		// get recent 3 articles
		$sql = 'select spu_id, unix_timestamp(timestamp) as time from spu_versions where version = 1 order by time desc limit ' . $recentk;
		$result = mysqli_query($cid, $sql);
		if(!$result) {return json_encode(array('error_msg' => 'Error reading versions table!'));}
		$spus = array();
		$latest = 1;
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$timestamp = $row['time'];
			$spus[$spu_id] = array('id' => $spu_id, 'timestamp' => Timesince($timestamp), 'rank' => $latest);
			$latest++;
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
		
		// return result
		return json_encode($spus);
	}
	
	// get articles which has recent likes
	function get_recentk_likes() {
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
		
		// get recent likes
		$sql = 'select spu_id, max(unix_timestamp(timestamp)) as time from likes group by spu_id order by time desc limit ' . $recentk;
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
	
	// get articles which has recent likes
	function get_recentk_comments() {
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
		
		// get recent comments
		$sql = 'select spu_id, max(unix_timestamp(timestamp)) as time from comments group by spu_id order by time desc limit ' . $recentk;
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
?>
<link rel="stylesheet" type="text/css" href="css/front.css">
<script language="javascript" type="text/javascript" charset="utf-8">
$(function() {
	// generate recent articles
	var articles = <?php echo get_recentk_articles(); ?>;
	if(!articles) $('#article_tab').css('display', 'none');
	else format_articles($('#recent_articles'), 'article', articles);
	
	// generate recent likes
	var likes = <?php echo get_recentk_likes(); ?>;
	if(!likes) $('#likes_tab').css('display', 'none');
	else format_articles($('#recent_likes'), 'like', likes);
	
	// generate recent comments
	var comments = <?php echo get_recentk_comments(); ?>;
	if(!comments) $('#comments_tab').css('display', 'none');
	else format_articles($('#recent_comments'), 'comment', comments);
	
	var sidebar_height = $('#sidebar').height();
	var content_height = $('#main').height();
	if(sidebar_height < content_height) $('#sidebar').css('height', content_height + 'px');
});

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

</script>

<div id='article_tab'><h4>Recent uploaded articles</h4><div id='recent_articles'></div></div>
<div id='likes_tab'><h4>Recent liked articles</h4><div id='recent_likes'></div></div>
<div id='comment_tab'><h4>Recent commented articles</h4><div id='recent_comments'></div></div>