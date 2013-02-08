<html>
<head>
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />

<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language="javascript" type="text/javascript" charset="utf-8">
$(function() {	
	// add header
	$('#header').load('header.php');
	
	// add sidebar
	$('#sidebar').load('sidebar.php?' + (new Date()).getTime());
	
	// get search query
	var search_txt = document.location.href;
	search_txt = search_txt.substr(search_txt.indexOf('?') + 1);
	search_txt = search_txt.split('&');
	for(var idx in search_txt) {
		var str = search_txt[idx].split('=');
		if(str[0] == 'q') {
			search_txt = str[1];
			break;
		}
	}

	if($.trim(search_txt) == '') return;
	
	// decode query string
	search_txt = decodeURIComponent(search_txt);
	$('#search_txt').val(search_txt);
	
	// set loading bar
	$('#main').html('<h2>Loading...</h2>');
	
	get_results(search_txt);
});

function get_results(str) {
	// show loading page
	$('#search_results').children().remove()
	$('#search_results').append('<p>Loading...</p>');
	$('#search_btn').attr('disabled', 'disabled');
	
	$.ajax({
		url: 'get_search_results.php',
		type: 'POST',
		data: {s: str},
		success: function(data) {
			if(!data) data = {};
			else data = $.parseJSON(data);
			display_results(data, str);
		},
		complete: function() {
			$('#search_btn').removeAttr('disabled');
		}
	});
}

function display_results(data, query) {
	var obj = $('#main');
	obj.children().remove();
	
	// check whether it is error
	if(data['error_msg']) {
		obj.append('<p>An error occured while getting results!</p><p>' + data['error_msg'] + '<\p>');
		return;
	}
	
	var numberOfResults = size(data);
	if(numberOfResults == 0) {
		obj.append('<h4>No results are found! Please modify your search criteria!</h4>');
		return;
	}
	obj.append('<h4>' + numberOfResults + ' results:</h4>');
	
	for(var i in data) {
		var result = data[i];
		format_result(obj, i, result, query);
	}
	
	var sidebar_height = $('#sidebar').height();
	var content_height = $('#main').height();
	if(sidebar_height < content_height) $('#sidebar').css('height', content_height + 'px');
}

function format_result(obj, spu_id, data, query) {
	var title = data['title'];
	var remark = data['remark'];
	var timestamp = data['timestamp'];
	var lastUpdate = data['last_updated'];
	var authorMap = data['authors'];
	var authorHTML = new Array();
	var authorArr = new Array();
	for(var i in authorMap) authorArr.push(authorMap[i]);
	authorArr.sort(function(a, b) {return a.rank - b.rank;});
	for(var i in authorArr) {
		var author = authorArr[i];
		var id = author['id'];
		var name = author['name'];
		authorHTML.push('<a href=\'author_detail.php?id=' + id + '\'>' + highlight_keyword(name, query) + '</a>');
	}
	if(authorHTML.length > 0) authorHTML = authorHTML.join(', ');
	
	var div = $('<div></div>');
	div.addClass('spu_box');
	var no = obj.find('p').length + 1;
	var p = '<p>' + no + '. <a href=\'spu_detail.php?id=' + spu_id + '\'>' + highlight_keyword(title, query) + '</a><br>'
	if(authorHTML != '') p += ('Authors: ' + authorHTML + '<br>');
	if(timestamp != '') p += ('First uploaded ' + timestamp + '<br>');
	if((lastUpdate != '') && (lastUpdate != timestamp)) p += ('Last updated ' + lastUpdate + '<br>');
	if(remark != '') p += ('Remark: ' + remark);
	div.append(p);
	obj.append(div);
	obj.append('<br>');
}

function highlight_keyword(str, q) {
	str = str.replace(new RegExp('(' + q + ')', 'gi'), '<span style=\'background-color:#ffffae\'>$1</span>');
	return str;
}

function size(arr) {
	var count = 0;
	for(var i in arr) count++;
	return count;
}
</script>


</head>
<body>
<div id='header'></div>
<div id='sidebar'></div>
<div id="main"></div>
</body>
</html>