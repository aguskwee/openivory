<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery.cookie.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
$(function() {
	if($.cookie('author_id') == null) $('#log_btn').text('Login').off('click').on('click', function() {document.location = 'login.php';});
	else {
		$('#log_btn').text('Logout').off('click').on('click', function() {user_logout();});
		$('#user_txt').html('<a style=\'color:black\' href=\'setting.php\'>' + $.cookie('author') + '</a>');
	}
		
	$('#search_txt').off('keyup').on('keyup', function(e) {
		if(e.keyCode == 13) { // Enter key
			document.location.href = 'search.php?q=' + $(this).val();
		}
	});
	
	// check if it has query string
	var search_txt = document.location.href;
	var query = '';
	search_txt = search_txt.substr(search_txt.indexOf('?') + 1);
	search_txt = search_txt.split('&');
	for(var idx in search_txt) {
		var str = search_txt[idx].split('=');
		if(str[0] == 'q') {
			query = str[1];
			break;
		}
	}
	if($.trim(query) != '') {
		query = decodeURIComponent(query);
		$('#search_txt').val(query);
	}
});
	
function user_logout() {
	$.cookie('author', null);
	$.cookie('author_id', null);
	$.cookie('email', null);
	$.cookie('lastk_articles', null);
	document.location = 'index.php';
}
</script>
<span><b><a href='home.php'  style='color:black'>openIvory</a></b></span>
<div style='float:right;margin-right:5px'><a id='log_btn' href='javascript:void(0);' style='color:black;margin-right: 10px'>Login</a><span id='user_txt'></span></div>
<input id='search_txt' type='text' placeholder='Search' style='height:auto !important;margin: -5px 10px auto auto;float:right'></input>
