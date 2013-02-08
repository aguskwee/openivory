<?php 
	function get_details() {
		// warning messages
		//error_reporting(E_ALL);
		//ini_set('display_errors', true);
		
		// include library
		include('lib/utils.php');
		
		// specify last topk history to store
		$lastk = 5;
		
		// get user input
		if(isset($_REQUEST['id'])) $spu_id = $_REQUEST['id'];
		if(!isset($spu_id)) {echo '<h3>Error retrieving spu details!</h3>'; return;}
		
		// connect to mysql
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) {echo '<h3>Error connecting to database!</h3>'; return;}
		
		// set character set
		mysqli_set_charset($cid, 'utf8');
		
		// get spu title
		$sql = 'select title from spu where spu_id = \'' . $spu_id . '\'';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retriving spu table!</h3>'; return;}
		$spu_detail = array('id' => $spu_id);
		while($row = mysqli_fetch_assoc($result)) {
			$title = $row['title'];
			$spu_detail['title'] = $title;
		}
		mysqli_free_result($result);
		
		// get versions
		$sql = 'select abstract, unix_timestamp(timestamp) as time, version, remark, arXiv_id from spu_versions where spu_id = \'' . $spu_id . '\'';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retrieving version table!</h3>'; return;}
		$versions = array();
		while($row = mysqli_fetch_assoc($result)) {
			$abstract = $row['abstract'];
			$timestamp = $row['timestamp'];
			$version = $row['version'];
			$remark = $row['remark'];
			$arxiv_id = $row['arXiv_id'];
			$arxiv_id = preg_replace('/v[0-9]+$/', '', $arxiv_id);
			$versions[$version] = array('abstract' => $abstract, 'timestamp' => Timesince($timestamp), 'remark' => $remark);
			
			// add arxiv id if any
			if($arxiv_id != '') {
				if(isset($spu_detail['linkbacks'])) $linkbacks = $spu_detail['linkback'];
				else $linkbacks = array();
				$linkbacks['arXiv'] = $arxiv_id;
				$spu_detail['linkbacks'] = $linkbacks;
			}
		}
		$spu_detail['versions'] = $versions;
		mysqli_free_result($result);
		
		// get topics
		$sql = 'select topic_id  from spu_topics where spu_id = \'' . $spu_id . '\'';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retrieving topic table!</h3>'; return;}
		$topics = array();
		while($row = mysqli_fetch_assoc($result)) {
			$topic = $row['topic_id'];
			$topics[$topic] = $topic;
		}
		$spu_detail['topics'] = $topics;
		mysqli_free_result($result);
		
		// get topic description
		$sql = 'select topic_id, description from topics where topic_id in (\'' . implode('\', \'', array_keys($topics)) . '\')';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retrieving topic\'s description table!</h3>'; return;}
		while($row = mysqli_fetch_assoc($result)) {
			$topic_id = $row['topic_id'];
			$topic_desc = $row['description'];
			if(!isset($topics[$topic_id])) continue;
			if($topic_desc == null) continue;
			$topics[$topic_id] = $topic_desc;
		}
		$spu_detail['topics'] = $topics;
		mysqli_free_result($result);
		
		// get authors
		$sql = 'select au.author_id as id, concat(au.given_name, \' \', au.last_name) as name, autship.rank from authorships autship, authors au where autship.author_id = au.author_id and autship.spu_id = \'' . $spu_id . '\'';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retrieving author table!</h3>'; return;}
		$authors = array();
		while($row = mysqli_fetch_assoc($result)) {
			$id = $row['id'];
			$name = $row['name'];
			$rank = $row['rank'];
			$authors[$id] = array('id' => $id, 'name' => $name, 'rank' => $rank);
		}
		$spu_detail['authors'] = $authors;
		mysqli_free_result($result);
		
		// get comments
		$sql = 'select c.comment_id, concat(a.given_name, \' \', a.last_name) as name, c.content, unix_timestamp(c.timestamp) as time from comments c, authors a where c.spu_id = \'' . $spu_id . '\' and a.author_id = c.author_id '
			   . 'order by c.timestamp';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retrieving comment table!</h3>'; return;}
		$comments = array();
		while($row = mysqli_fetch_assoc($result)) {
			$comment_id = $row['comment_id'];
			$content = $row['content'];
			$timestamp = $row['time'];
			$aname = $row['name'];
			$comments[$comment_id] = array('content' => $content, 'timestamp' => Timesince($timestamp), 'author' => $aname);
		}
		mysqli_free_result($result);
		$spu_detail['comments'] = $comments;
		
		// get other authors who like this spu
		$sql = 'select l.author_id, concat(a.given_name, \' \', a.last_name) as name from likes l, authors a where l.author_id = a.author_id and l.spu_id = \'' . $spu_id . '\'';
		$result = mysqli_query($cid, $sql);
		if(!$result) {echo '<h3>Error retrieving like table!</h3>'; return;}
		$likes = array();
		while($row = mysqli_fetch_assoc($result)) {
			$author_id = $row['author_id'];
			$author_name = $row['name'];
			array_push($likes, array('id' => $author_id, 'name' => $author_name));
		}
		if(count($likes) > 0) $spu_detail['likes'] = $likes;
		mysqli_free_result($result);
		
		// close connection
		mysqli_close($cid);
		
		// add this spu id to cookie
		if(isset($_COOKIE['author_id'])) {
			$history = '';
			if(isset($_COOKIE['lastk_articles'])) $history = $_COOKIE['lastk_articles'];
			if(strlen(trim($history)) == 0) $history = $spu_id;
			else {
				$history = explode(',', $history);
				// get the last item in the array
				// if it is the same as the current one, ignore
				// otherwise add to array
				if($history[count($history) - 1] != $spu_id) {
					if(count($history) >= $lastk) array_shift($history); 
					array_push($history, $spu_id);
				}
				$history = implode(',', $history);
			}
			setcookie('lastk_articles', $history, 0);
		}
		
		// send result to client
		echo json_encode($spu_detail);
	}
?>
<link rel="stylesheet" type="text/css" media="screen" href="css/arXiv.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />

<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/bootstrap.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/date_format.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery.cookie.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
$(function() {	
	var storagek = 5;
	
	// add header
	$('#header').load('header.php');
	
	// add sidebar
	$('#sidebar').load('sidebar.php?' + (new Date()).getTime());

	var details = <?php get_details(); ?>;
	if(!details) {$('html body').append('<h3>Error reading spu details!</h3>'); return;}
	
	// set page title
	var title = 'OpenIvory - ' + details['title'];
	$('title').html(title);
	
	// set title
	$('#title').html(details['title']);
	
	// set related topics
	var topics = details['topics'];
	var topicsArr = new Array();
	for(var i in topics) topicsArr.push(topics[i]);	
	$('#topics').html('Related topics: <i>' + topicsArr.join(', ') + '</i>');
	
	// set authors
	var authors = details['authors'];
	var authorsHTML = new Array();
	var authorArr = new Array();
	for(var i in authors) authorArr.push(authors[i]);
	authorArr.sort(function(a, b) {return a.rank - b.rank;});
	
	for(var i in authorArr) {
		var author = authorArr[i];
		var id = author['id'];
		var name = author['name'];
		authorsHTML.push('<a href=\'author_detail.php?id=' + id + '\'>' + name + '</a>');
	}
	authorsHTML = authorsHTML.join(', ');
	$('#authors').html(authorsHTML);
	
	// set abstract from the latest versions 
	var versions = details['versions'];
	var idx = '';
	var abs = '';
	for(var i in versions) {
		if(idx == '') idx = i;
		if(idx < i) idx = i;
	}
	abs = versions[idx]['abstract'];
	$('#abstract').html(abs);
	
	// set comments if any
	if(details['comments']) {
		for(var i in details['comments']) {
			var comment = details['comments'][i];
			var user = comment['author'];
			var timestamp = comment['timestamp'];
			var content = comment['content'];
			var div = $('<div></div>');
			div.addClass('comment_div');
			div.append('<p><span style=\'font-weight:bold\'>' + user + '</span> - <span style=\'font-size:90%\'>' + timestamp + '<span></p><p style=\'font-size:120%\'>' + $.trim(content) + '</p>');
			$('#new_comment_div').before(div);
		}
	}
	
	// set likes if any
	if(details['likes']) {
		var likes = new Array();
		for(var i in details['likes']) {
			var author_name = details['likes'][i]['name'];
			var author_id = details['likes'][i]['id'];
			likes.push('<span><a href=\'author_detail.php?id=' + author_id + '\'>' + author_name + '</a></span>');
		}
		$('#likes').append('Liked by ' + likes.join(', '));
	}
	else {
		if($.cookie('author_id')) 
			$('#likes').append('<p><a id=\'first_like\' href=\'javascript:void(0)\' onclick=\'add_new_like("' + $.cookie('author_id') + '", "' + details['id'] + 
							   '")\'>Be the first to like this article!</a><img id=\'like_loader\' src=\'img/loader.gif\' style=\'display:none;margin-left:5px;height:15px;vertical-align:top\'></img></p>');
	}
	
	// add link back if any
	if(details['linkbacks']) {
		var ul = $('<ul></ul>');
		if($('#originallinks').find('ul').length > 0) $('#originallinks').find('ul').remove();
		for(var name in details['linkbacks']) {
			ul.append('<li><a href=\'' + details['linkbacks'][name] + '\' target=\'_blank\'>' + name + '</a></li>');
		}
		$('#originallinks').append(ul);
		$('#originallinks').css('display', 'block');
	}
	else $('.linkbacks').first().css('display', 'none');
	
	// add listener to new_comment obj
	$('#new_comment_txt').off('focus').on('focus', function() {
		$(this).removeAttr('placeholder');
		$(this).attr('rows', '3');
		$('#new_comment_btn').css('display', 'block');
	});
	$('#new_comment_txt').off('blur').on('blur', function() {
		if($.trim($(this).val()) == '') {
			$(this).attr('placeholder', 'Compose new comment...');
			$(this).attr('rows', '1');
			$('#new_comment_btn').css('display', 'none');
		}
	});
	
	$('#new_comment_btn').off('click').on('click', function() {
		$(this).text('Posting...').attr('disabled', 'disabled');
		add_new_comment($('#new_comment_txt').val(), details['id']);
	});
	
	// change log button
	if($.cookie('author_id') == null) {
		$('#new_comment_txt').css('display', 'none');
		if(details['comments'] && (size(details['comments']) == 0)) $('#comments').css('display', 'none');
	}
	else {
		$('#comments').css('display', 'block');
		$('#new_comment_txt').css('display', 'block');
	}
});	

function add_new_like(author_id, spu_id) {
	$('#like_loader').css('display', 'inline');
	$('#first_like').removeAttr('href').removeAttr('onclick');
	$.get('add_new_like.php?author_id=' + author_id + '&spu_id=' + spu_id, function(likes) {
		if(!likes) {
			alert('Error processing your request!');
			$('#like_loader').css('display', 'none');
			return;
		}
		
		$('#like_loader').css('display', 'none');
		
		likes = $.parseJSON(likes);
		var updated_likes = new Array();
		var p = $('<p>Liked by </p>');
		for(var i in likes) {
			var id = likes[i]['id'];
			var name = likes[i]['name'];
			
			updated_likes.push('<span><a href=\'author_detail.php?id=' + id + '\'>' + name + '</a></span>');
		}
		if(size(updated_likes) > 0) p.append(updated_likes.join(', '));
		$('#likes').text('').append(p);
	});
}

function add_new_comment(content, spu_id) {
	var date = new Date();
	var sql_date = Math.floor(date.getTime() / 1000);
	var user = 'current user';
	content = $.trim(content).replace(/\n/gi, '<br>');
	// get current user from cookie
	user = decodeURIComponent($.cookie('author'));

	// add comment to database and generate new comments that have not shown (take the last shown comment)
	$.ajax({
		'url': 'add_new_comment.php',
		'type': 'POST',
		'data': {'author_id': $.cookie('author_id'), 'spu_id': spu_id, 'content': content, 'timestamp': sql_date},
		'success': function(data) {
			if(data == 0) {
				alert('Error posting your comment!');
				return;
			}
			var div = $('<div></div>');
			div.addClass('comment_div');
			div.append('<p><span style=\'font-weight:bold\'>' + user + '</span> - <span style=\'font-size:90%\'>' + date.format('yyyy-mm-dd HH:MM:ss') + '<span></p><p style=\'font-size:120%\'>' + $.trim(content) + '</p>');
			$('#new_comment_div').before(div);
			
			// reset new content div
			$('#new_comment_txt').val('').blur();
		},
		'complete': function(data) {
			$('#new_comment_btn').text('Post').removeAttr('disabled');
		}
	});
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
	<div id="main" style='margin-left:0px !important; padding-right:0px'>
		<div id='abs'>
			<div class="extra-services">
				<div class="full-text">
					<h2>Cites This Article:</h2>
					<!--<ul>
					<li><a href="#">spu.id 1</a></li>
					<li><a href="#">spu.id 2</a></li>
					</ul>
					<a href="#">Eventually: Download a complete list.</a>-->
					<p>No citation yet!</p>
				</div><!--end full-text-->
				<div class="full-text">
					<h2>Cited By This Article:</h2>
					<!--<ul>
					<li><a href="#">spu.id 1</a></li>
					<li><a href="#">spu.id 2</a></li>
					</ul>
					<a href="#">Eventually: Download a complete list.</a>-->
					<p>No citation yet!</p>
				</div><!--end full-text-->
				<div id="originallinks" class="linkbacks">
					<h3>Link back to:</h3>
					<ul></ul>
				</div>
				<div class="bookmarks" style='display:none'>
					<h3 style='line-height:1'>Bookmark</h3>
					<!--<a href="/ct?url=http%3A%2F%2Fwww.citeulike.org%2Fposturl%3Furl%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799%26title%3DCompeting%2520Process%2520Hazard%2520Function%2520Models%2520for%2520Player%2520Ratings%2520in%2520Ice%250A%2520%2520Hockey%26authors%3D&amp;v=77fde041" title="Bookmark on CiteULike"><img src="http://static.arxiv.org/icons/social/citeulike.png" alt="CiteULike logo" /></a>
					<a href="/ct?url=http%3A%2F%2Fwww.connotea.org%2Faddpopup%3Furi%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799&amp;v=a0c42590" title="Bookmark on Connotea"><img src="http://static.arxiv.org/icons/social/connotea.png" alt="Connotea logo" /></a>
					<a href="/ct?url=http%3A%2F%2Fwww.bibsonomy.org%2FBibtexHandler%3FrequTask%3Dupload%26url%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799%26description%3DCompeting%2520Process%2520Hazard%2520Function%2520Models%2520for%2520Player%2520Ratings%2520in%2520Ice%250A%2520%2520Hockey&amp;v=8f2daf66" title="Bookmark on BibSonomy"><img src="http://static.arxiv.org/icons/social/bibsonomy.png" alt="BibSonomy logo" /></a>
					<a href="/ct?url=http%3A%2F%2Fwww.mendeley.com%2Fimport%2F%3Furl%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799&amp;v=2a0c30df" title="Bookmark on Mendeley"><img src="http://static.arxiv.org/icons/social/mendeley.png" alt="Mendeley logo" /></a>-->
					<a href="/ct?url=http%3A%2F%2Fexport.arxiv.org%2Ffb%2Farxivpost%2F%3Furl%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799&amp;v=2f1962db" title="Bookmark on Facebook"><img src="http://static.arxiv.org/icons/social/facebook.png" alt="Facebook logo" /></a>
					<a href="/ct?url=http%3A%2F%2Fexport.arxiv.org%2Ffb%2Farxivpost%2F%3Furl%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799&amp;v=2f1962db" title="Bookmark on Twitter"><img src="img/twitter.jpg" alt="Twitter logo" /></a>
					<a href="/ct?url=http%3A%2F%2Fexport.arxiv.org%2Ffb%2Farxivpost%2F%3Furl%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799&amp;v=2f1962db" title="Bookmark on Google+"><img src="img/gplus.jpg" alt="Google+ logo" /></a>
					<!--<a href="/ct?url=http%3A%2F%2Fexport.arxiv.org%2Ffb%2Flinkedin_post%3Furl%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799&amp;v=f4051792" title="Bookmark on LinkedIn"><img src="http://static.arxiv.org/icons/social/linkedin.png" alt="LinkedIn logo" /></a>
					<a href="/ct?url=http%3A%2F%2Fdel.icio.us%2Fpost%3Furl%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799%26description%3DCompeting%2520Process%2520Hazard%2520Function%2520Models%2520for%2520Player%2520Ratings%2520in%2520Ice%250A%2520%2520Hockey&amp;v=f8e51204" title="Bookmark on del.icio.us"><img src="http://static.arxiv.org/icons/social/delicious.png" alt="del.icio.us logo" /></a>
					<a href="/ct?url=http%3A%2F%2Fdigg.com%2Fsubmit%3Furl%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799%26title%3DCompeting%2520Process%2520Hazard%2520Function%2520Models%2520for%2520Player%2520Ratings%2520in%2520Ice%250A%2520%2520Hockey&amp;v=0c1e83b5" title="Bookmark on Digg"><img src="http://static.arxiv.org/icons/social/digg.png" alt="Digg logo" /></a>
					<a href="/ct?url=http%3A%2F%2Freddit.com%2Fsubmit%3Furl%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799%26title%3DCompeting%2520Process%2520Hazard%2520Function%2520Models%2520for%2520Player%2520Ratings%2520in%2520Ice%250A%2520%2520Hockey&amp;v=3d397020" title="Bookmark on Reddit"><img src="http://static.arxiv.org/icons/social/reddit.png" alt="Reddit logo" /></a>
					<a href="/ct?url=http%3A%2F%2Fsciencewise.info%2Fbookmarks%2Fadd%3Furl%3Dhttp%3A%2F%2Farxiv.org%2Fabs%2F1208.0799&amp;v=cf757490" title="Bookmark on ScienceWISE"><img src="http://static.arxiv.org/icons/social/sciencewise.png" alt="ScienceWISE logo" /></a>-->
				</div>
			</div><!--end extra-services-->

			<div class="leftcolumn">
				<div class="subheader">
					<h1 id='topics' style='line-height:1.5'></h1>
				</div>
				<h1 class="title" id='title'><span class="descriptor">Title:</span>
				spu.title goes here</h1>
				<div class="authors" id='authors'><span class="descriptor">Authors:</span></div>
				<p id='abstract' style='margin:20px'>
					<span class="descriptor">Abstract:</span> 
				</p>
				<!--CONTEXT-->

				<div style='margin:20px'>
					<table style='font-size:100%; width:100%'>
						<tr><td id='likes'></td></tr>
						<tr><td>
							<div id='comments'>
								<div style='margin-top:10px;margin-bottom:10px;padding-bottom:5px;border-bottom:2px solid #dfdfdf;font-size:110%;font-weight:bold'>User comments</div>
								<div id='new_comment_div' style='margin-top:10px;margin-bottom:40px'>
									<textarea id='new_comment_txt' type='text' style='width:100%;resize:none' rows='1' placeholder='Compose new comment...'></textarea>
									<button id='new_comment_btn' type='button' data-loading-text='Posting...' class='btn' style='display:none'>Post</button>
								</div>
							</div>
						</td></tr>
					</table>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
