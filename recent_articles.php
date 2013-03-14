<?php
?>

<html>
<head>
<title>openIvory - Recently Uploaded Articles</title>
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery.cookie.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
var curPage = 1;

$(function() {
	// check if the user has already logged in.
	// if not, go to login page
	if(!$.cookie('author_id')) {document.location.href = 'login.php'; return;}
	
	// add header
	$('#header').load('header.php');
	
	// add side bar
	$('#sidebar').load('sidebar.php?' + (new Date()).getTime());
	
	// get recent articles
	get_articles();
});	

function get_articles(type) {
	if(type == 'older') curPage++;
	else if(type == 'later') curPage--;
	if(curPage == 0) curPage = 1;

	$('#content').html('<br><h4>Retrieving recent articles...</h4>')
	$('body').scrollTop(0);
	
	// get recently uploaded SPU
	$.get('get_recentk_articles.php?num=10&page=' + curPage, function(data) {
		if(data == null) {
			$('#main').html('<br><h4>Error reading recent articles!</h4>');
			return;
		}
		
		var recent_spus = $.parseJSON(data);
		
		var spus_div = '';
		if(recent_spus) {
			if(size(recent_spus) > 0) {
				spus_div = format_articles('article', recent_spus);
			}
		}
		
		var content = '';
		if(spus_div.length > 0) { 
			content = spus_div;
			
			// add pagination
			content += '<a href=\'javascript:void(0)\' onclick=\'get_articles();\'></a>';
		}
		else content = '<br><h4>Error reading recent articles!</h4>';
		
		// add pagination
		if(content.indexOf('Error') != -1) {}
		else if(curPage == 1) {
			content += '<a style=\'float:right\' href=\'javascript:void(0);\' onclick=\'get_articles("older");\'>Older >></a>';
		}
		else {
			content += ('<a style=\'float:left\' href=\'javascript:void(0);\' onclick=\'get_articles("later");\'><< Later</a>' +
						'<a style=\'float:right\' href=\'javascript:void(0);\' onclick=\'get_articles("older");\'>Older >></a>');
		}
		
		$('#content').html(content);
		
		var sidebar_height = $('#sidebar').height();
		var content_height = $('#main').height();
		if(sidebar_height < content_height) $('#sidebar').css('height', content_height + 'px');
	});
}

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
	<div id="main">
		<h4>Recently uploaded articles</h4>
		<div id='content'></div>
	</div> <!-- main -->
</body>
</html>
