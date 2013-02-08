<?php
	function get_details() {
		// warning messages
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		
		// include library
		include 'lib/utils.php';
		
		// get user input 
		if(isset($_REQUEST['id'])) $author_id = $_REQUEST['id'];
		if(!isset($author_id)) {echo '<h3>Error retrieving author details!</h3>'; return;}
		
		// connect database
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) {echo '<h3>Error connecting to database!</h3>'; return;}
		
		// set character set
		mysqli_set_charset($cid, 'utf8');
		
		$author_detail = array('id' => $author_id);
		
		// get current author id
		$follows = -1;
		if(isset($_COOKIE['author_id'])) $current_author_id = $_COOKIE['author_id'];
		if(!isset($current_author_id)) $follows = 0; // user has not signed in
		else {
			// get following users
			$sql = 'select to_id, max(timestamp) as time, is_active from following where from_id = \'' . $current_author_id . '\' group by to_id';
			$result = mysqli_query($cid, $sql);
			if(!$result) {echo '<h3>Error retrieving following table!</h3>'; return;}
			$following = array();
			while($row = mysqli_fetch_assoc($result)) {
				$fid = $row['to_id'];
				$time = $row['time'];
				$is_active = $row['is_active'];
				if($is_active == 0) continue;
				$following[$fid] = $fid;
			}
			mysqli_free_result($result);
			
			// check whether this author can be followed
			if($current_author_id == $author_id) $follows = 2; // user displays his / her details
			else {
				if(isset($following[$author_id])) $follows = 3; // user has already follows this author
				else $follows = 1; // user has not followed this author
			}
		}
		
		
		$author_detail['follow'] = $follows;
		
		// get author name
		$sql = 'select concat(given_name, \' \', last_name) as name from authors where author_id = \'' . $author_id . '\'';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retrieving author table!</h3>'; return;}
		while($row = mysqli_fetch_assoc($result)) {
			$name = $row['name'];
			$author_detail['name'] = $name;
		}
		mysqli_free_result($result);
		
		// get author interests (to be implemented)
		
		// get spu written by the author
		$sql = 'select spu_id from authorships where author_id = \'' . $author_id . '\'';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retriving authorship table!</h3>'; return;}
		$spu_ids = array();
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$spu_ids[$spu_id] = $spu_id;
		}
		mysqli_free_result($result);
		
		// get title spu
		$spus = array();
		$sql = 'select spu_id, title from spu where spu_id in (\'' . implode('\', \'', array_keys($spu_ids)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retriving spu table!</h3>'; return;}
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$title = $row['title'];
			$spus[$spu_id] = array('id' => $spu_id, 'title' => $title);
		}
		mysqli_free_result($result);
		
		// get remark for the first version
		$article_order = array();
		$sql = 'select spu_id, remark, unix_timestamp(timestamp) as time from spu_versions where version = 1 and spu_id in (\'' . implode('\', \'', array_keys($spu_ids)) . '\') order by timestamp desc';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retriving remark table!</h3>'; return;}
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$remark = $row['remark'];
			$timestamp = $row['time'];
			$spus[$spu_id]['remark'] = $remark;
			$spus[$spu_id]['timestamp'] = Timesince($timestamp);
			array_push($article_order, $spu_id);
		}
		mysqli_free_result($result);
		
		// get last updated timestamp
		$sql = 'select spu_id, max(unix_timestamp(timestamp)) as time from spu_versions where spu_id in (\'' . implode('\', \'', array_keys($spu_ids)) . '\') group by spu_id';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retrieving version table!</h3>'; return;}
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$time = $row['time'];
			$spus[$spu_id]['last_updated'] = Timesince($time);
		}
		mysqli_free_result($result);
		
		// get authors for every spu
		$sql = 'select auship.author_id as aid, auship.spu_id as sid, concat(au.given_name, \' \', au.last_name) as name, auship.rank from authorships auship, authors au where au.author_id = auship.author_id and ' .
			   'auship.spu_id in (\'' . implode('\', \'', array_keys($spu_ids)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retriving author - spu table!</h3>'; return;}
		while($row = mysqli_fetch_assoc($result)) {
			$aid = $row['aid'];
			$sid = $row['sid'];
			$name = $row['name'];
			$rank = $row['rank'];
			if(!isset($spus[$sid]['authors'])) $authors = array();
			else $authors = $spus[$sid]['authors'];
			$authors[$aid] = array('id' => $aid, 'name' => $name, 'rank' => $rank);
			$spus[$sid]['authors'] = $authors;
		}
		mysqli_free_result($result);
		
		// re-sort spu
		$sorted_spus = array();
		foreach($article_order as $idx => $id) {
			array_push($sorted_spus, $spus[$id]);
		}
		
		// put spu in author details
		$author_detail['spu'] = $sorted_spus;
		
		// close connection
		mysqli_close($cid);
		
		// send to client
		echo json_encode($author_detail);
	}
?>

<html>
<head>
<title>openIvory - Author Name</title>
<link rel="stylesheet" type="text/css" media="screen" href="css/arXiv.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
$(function() {
	// add header
	$('#header').load('header.php');
	
	// add sidebar
	$('#sidebar').load('sidebar.php?' + (new Date()).getTime());
	
	var details = <?php get_details(); ?>;
	if(!details) {$('html body').append('<h3>Error reading author details!</h3>'); return;}
	
	// set author name
	var name = details['name'];
	$('title').html('OpenIvory - ' + name);
	$('#author_name').html(name);
	
	// set follow button
	var follow = details['follow'];
	if((follow == 1) || (follow == 3)) {
		var btn = $('#follow_btn');
		if(follow == 1) {
			btn.removeClass('btn-danger');
			btn.addClass('btn-primary');
			btn.text('Follow');
			btn.css({'display': 'inline-block'});
			btn.off('click').on('click', function() {follow_user(details['id']);});
		}
		else {
			btn.removeClass('btn-primary');
			btn.addClass('btn-danger');
			btn.text('Unfollow');
			btn.css({'display': 'inline-block'});
			btn.off('click').on('click', function() {follow_user(details['id']);});
		}
	}
	else $('#follow_btn').css('display', 'none');	
	
	// set topic of interests (to be implemented)
	$('#interests').html('');
	
	// set institution (to be implemented)
	$('#institution').html('');
	
	// set number of publications
	var spus = details['spu'];
	var numPub = size(spus);
	if(numPub > 0) $('#numPub').html('Number of publication(s): <b>' + numPub + '</b>');
	else $('#numPub').html('No publications so far!');
	
	// list of publications
	$('#publication').html('');
	if(numPub > 0) {
		$('#publication').html('List of publication(s):');
		for(var i in spus) {
			format_result($('#publication'), spus[i]);
		}
	}
	
	var sidebar_height = $('#sidebar').height();
	var content_height = $('#main').height();
	if(sidebar_height < content_height) $('#sidebar').css('height', content_height + 'px');
});	

function follow_user(fid) {
	// check whether it is a follow / unfollow button
	var btn = $('#follow_btn');
	btn.attr('disabled', 'disabled');
	if(btn.hasClass('btn-danger')) { // unfollow button
		$.get('follow_user.php?fid=' + fid + '&follow=0', function(is_success) {
			if(is_success == 1) {
				$('#follow_btn').text('Follow');
				$('#follow_btn').removeClass('btn-danger').addClass('btn-primary');
			}
			$('#follow_btn').removeAttr('disabled');
		});
	}
	else if(btn.hasClass('btn-primary')) { // follow button
		$.get('follow_user.php?fid=' + fid + '&follow=1', function(is_success) {
			if(is_success == 1) {
				$('#follow_btn').text('Unfollow');
				$('#follow_btn').removeClass('btn-primary').addClass('btn-danger');
			}
			$('#follow_btn').removeAttr('disabled');
		});
	}
}

function format_result(obj, data) {
	var spu_id = data['id'];
	var title = data['title'];
	var remark = data['remark'];
	var timestamp = data['timestamp'];
	var lastUpdate = data['last_updated'];
	var authorMap = data['authors'];
	var authorHTML = new Array();
	
	// sort authors by rank
	var authorArr = new Array();
	for(var i in authorMap) authorArr.push(authorMap[i]);
	authorArr.sort(function(a, b) {return a.rank - b.rank;});
	
	for(var i in authorArr) {
		var author = authorArr[i];
		var id = author['id'];
		var name = author['name'];
		authorHTML.push('<a href=\'author_detail.php?id=' + id + '\'>' + name + '</a>');
	}
	if(authorHTML.length > 0) authorHTML = authorHTML.join(', ');
	
	var div = $('<div style=\'margin-top:10px\'></div>');
	div.addClass('spu_box');
	var no = obj.find('p').length + 1;
	var p = '<p>' + no + '. <a href=\'spu_detail.php?id=' + spu_id + '\'>' + title + '</a></br>'
	if(authorHTML != '') p += ('Authors: ' + authorHTML + '</br>');
	if(timestamp != '') p += ('First uploaded ' + timestamp + '</br>');
	if((lastUpdate != '') && (lastUpdate != timestamp)) p += ('Last updated ' + lastUpdate + '</br>');
	if(remark != '') p += ('Remark: ' + remark);
	div.append(p);
	obj.append(div);
	obj.append('<br>');
}

function size(arr) {
	var count = 0;
	for(var i in arr) count++;
	return count;
}
</script>
</head>
<body>
	<div id="header"></div>
	<div id="sidebar"></div>
	<div id="main">
		<h2 id='author_name' style='display:inline-block;margin-bottom:10px'><span class="descriptor">Name:</span></h2>
		<button id='follow_btn' style='display:none;margin: -15px 0 0 20px' class='btn btn-primary'>Follow</button>
		<div style='margin-left: 10px'>
			<div id='interests'>Topic of interests: </div>
			<div id='institution'>Institution:</div>
			<div id='numPub' style='margin-bottom:10px'>Number of publication:</div>
			<div id='publication'>List of publications:</div>
		</div>
	</div>
</body>
</html>
