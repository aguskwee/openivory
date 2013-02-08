<?php 
	// warning messages
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	// check whether if user has already login
	if(isset($_COOKIE['author_id'])) $author_id = $_COOKIE['author_id'];
	if(!isset($author_id)) {
		header('location: login.php');
		return;
	}

	// include library
	include 'lib/utils.php';
	
	// connect to database
	$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
	if(!$cid) {echo json_encode(array('error_msg' => 'Cannot connect to database!')); return;}
	
	// set character set
	mysqli_set_charset($cid, 'utf8');
	
	// get user profile
	$sql = 'select * from authors where author_id = \'' . $author_id . '\'';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo json_encode(array('error_msg' => 'Cannot retreving profile!')); return;}
	
	$profile = array();
	if($row = mysqli_fetch_assoc($result)) {
		$given_name = $row['given_name'];
		$last_name = $row['last_name'];
		$email = $row['email'];
		$department = $row['department'];
		$institution = $row['institution'];
		
		$profile['given_name'] = $given_name == null ? '' : trim($given_name);
		$profile['last_name'] = $last_name == null ? '' : trim($last_name);
		$profile['email'] = $email == null ? '' : trim($email);
		$profile['department'] = $department == null ? '' : trim($department);
		$profile['institution'] = $institution == null ? '' : trim($institution);
	}
	mysqli_free_result($result);
	
	// get user topics, if any
	$sql = 'select topic_id from author_topics where author_id = \'' . $author_id . '\'';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo json_encode(array('error_msg' => 'Cannot retrieving user\'s topics!')); return;}
	$author_topics = array();
	while($row = mysqli_fetch_assoc($result)) {
		$topic_id = $row['topic_id'];
		$author_topics[$topic_id] = '';
	}
	mysqli_free_result($result);
	
	// get all topics
	$sql = 'select * from topics';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo json_encode(array('error_msg' => 'Cannot retrieving all topics!')); return;}
	$topics = array();
	while($row = mysqli_fetch_assoc($result)) {
		$topic_id = $row['topic_id'];
		$desc = $row['description'];
		$parent_topic_id = $row['parent_topic_id'];
		$topics[$topic_id] = array('desc' => $desc, 'parent' => $parent_topic_id);
	}
	mysqli_free_result($result);
	
	// generate complete list of topics in the format of parent_id....parent_id.topic_id
	$complete_topics = array();
	foreach($topics as $topic_id => $obj) {
		$desc = get_description($topic_id);
		$complete_topics[$topic_id] = $desc;
	}
	
	// get description of author topics
	foreach($author_topics as $topic_id => $name) {
		if(!isset($complete_topics[$topic_id])) continue;
		$author_topics[$topic_id] = $complete_topics[$topic_id];
	}
	$profile['topics'] = $author_topics;
	
	// close connection
	mysqli_close($cid);

	// function to get complete description of topic
	function get_description($tid) {
		global $topics;
		$obj = $topics[$tid];
		if($obj['parent'] == null) return $obj['desc'];
		return get_description($obj['parent']) . ($obj['desc'] == null ? '' : '.' . $obj['desc']);
	}
?>

<html>
<head>
<title>openIvory - Profile</title>
<link rel="stylesheet" type="text/css" media="screen" href="css/index.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery-1.8.3.min.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/bootstrap-alert.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8' src='js/jquery.cookie.js'></script>
<script language='javascript' type='text/javascript' charset='utf-8'>
$(function() {
	// load header
	$('#header').load('header.php');
	
	// load sidebar
	$('#sidebar').load('sidebar.php');
	
	var profile = '<?php echo json_encode($profile); ?>';
	if($.trim(profile).length == 0) return;

	profile = $.parseJSON(profile);
	if(profile['error_msg']) {
		$('#main').html('<h3>' + profile['error_msg'] + '</h3>');
		return;
	}
	
	// generate user profile
	$('#given_name').val(profile['given_name']);
	$('#last_name').val(profile['last_name']);
	$('#email').html(profile['email']);
	$('#department').val(profile['department']);
	$('#institution').val(profile['institution']);

	// add author topics
	$('#existTopics').children().remove();
	if(profile['topics'] && size(profile['topics']) > 0) {
		for(var id in profile['topics']) {
			var desc = profile['topics'][id];
			create_topic_div(id, desc);
		}
	}
	
	// generate topics list
	var topics = '<?php echo json_encode($complete_topics); ?>';
	var topicMap = [];
	if(!topics) topics = {};
	else topics = $.parseJSON(topics);
	for(var id in topics) {
		var desc = topics[id] == null ? '' : topics[id];
		if($.trim(desc) == '') continue;
		topicMap.push({'id': id, 'desc': topics[id] == null ? '' : topics[id]});
	}
	topicMap.sort(function(a, b) {
		var tempa = a['desc'].toLowerCase();
		var tempb = b['desc'].toLowerCase();
		
		return tempa == tempb ? 0 : tempa > tempb ? 1 : -1; 
	});
	for(var i in topicMap) {
		var map = topicMap[i];
		var option = $('<option></option>');
		option.attr('val', map['id']);
		option.text(map['desc']);
		$('#topicList').append(option);
	}
	
	// hide help text
	$('#given_name_help').css('display', 'none');
	$('#last_name_help').css('display', 'none');
	
	// add action listener to add topic
	$('#addTopic').off('click').on('click', function() {
		var obj = $('#topicList option:selected');
		var topic_id = $(obj).attr('val');
		var desc = $(obj).text();
		// check existence of selected topic
		var isExist = false;
		for(var i in $('#existTopics').children()) {
			var elm = $('#existTopics').children().eq(i);
			var tempId = $(elm).attr('topic');
			if(tempId == topic_id) {
				isExist = true;
				break;
			}
		}
		if(!isExist) create_topic_div(topic_id, desc);
	});
	
	// add action listener to save changes
	$('#save_btn').off('click').on('click', function() {
		// check for name validity (given name and last name must be present)
		if($.trim($('#given_name').val()).length == 0) {
			$('#given_name_div').removeClass('error').addClass('error');
			$('#given_name_help').css('display', 'inline');
			return;
		}
		else {
			$('#given_name_div').removeClass('error');
			$('#given_name_help').css('display', 'none');
		}
		
		if($.trim($('#last_name').val()).length == 0) {
			$('#last_name_div').removeClass('error').addClass('error');
			$('#last_name_help').css('display', 'inline');
			return;
		}
		else {
			$('#last_name_div').removeClass('error');
			$('#last_name_help').css('display', 'none');
		}
		
		// construct profile
		var given_name = $.trim($('#given_name').val());
		var last_name = $.trim($('#last_name').val());
		var department = $.trim($('#department').val());
		var institution = $.trim($('#institution').val());
		var topics = [];
		if($('#existTopics').children().length > 0) {
			for(var i in $('#existTopics').children()) {
				var elm = $('#existTopics').children().eq(i);
				var topic_id = $(elm).attr('topic');
				if(!topic_id) continue;
				topics.push(topic_id);
			}
		}
		$.ajax({
			'url': 'saveprofile.php', 
			'type': 'POST',
			'data': {'id': $.cookie('author_id'), 'given_name': given_name, 'last_name': last_name, 'department': department, 'institution': institution, 'topics': topics.join(',')},
			'success': function(rv) {
				if(!rv || (rv == 0)) {
					alert('Error saving your profile!');
					return;
				}
				alert('Your profile has been changed!');
				document.location = 'home.php';
		}});
	});
	
	// action listener for deleting account
	$('#delete_btn').off('click').on('click', function() {
		// show confirmation to delete the account
		var div = '<div class=\'modal\' id=\'conf_delete\'>' +
				  '<div class=\'modal-header\'><h3>Confirmation</h3>' +
				  '</div>' +
				  '<div class=\'modal-body\'><p>Are you sure to delete this account?</p></div>' +
				  '<div class=\'modal-footer\'>' +
				  '<a href=\'javascript:void(0)\' onclick=\'delete_account()\' class=\'btn\'>Yes</button>' +
				  '<a href=\'javascript:void(0)\' onclick=\'$("#conf_delete").remove();\' class=\'btn\'>No</button>' + 
				  '</div>';
		$('#main').append(div);
	});
});

function delete_account() {
	$.ajax({
			'url': 'delete_account.php',
			'type': 'POST',
			'data': {'author_id': $.cookie('author_id')},
			'success': function(rv) {
				if(!rv || (rv == 0)) {
					alert('Error deleting this account!');
					return;
				}
				alert('Your account has been deactivated!');
				$('#conf_delete').remove();
				$('#log_btn').click();
			}
		});
}

function create_topic_div(topic_id, desc) {
	var div = $('<div></div>');
	div.css('margin-left', '0px');
	div.addClass('alert');
	div.addClass('span3');
	div.attr('topic', topic_id);
	var button = $('<button></button>');
	button.attr({'type': 'button', 'data-dismiss': 'alert'})
		  .addClass('close')
		  .html('&times;');
	div.append(button).append(desc);
	$('#existTopics').append(div);
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
		<h2>Your Profile</h2>
		<div class='form-horizontal'>
			<div id='given_name_div' class='control-group'>
				<label style='padding-top:5px;width:100px;float:left' for='given_name'>Given name</label>
				<div class='controls'>
					<input class='custom_input input-xlarge' type='text' id='given_name' placeholder='Type your given name...'>
					<span id='given_name_help' class='help-inline'>Given name cannot be empty!</span>
				</div>
			</div>
			<div id='last_name_div' class='control-group'>
				<label class='control-label' for='last_name'>Last name</label>
				<div class='controls'>
					<input class='custom_input input-xlarge' type='text' id='last_name' placeholder='Type your last name...'>
					<span id='last_name_help' class='help-inline'>Last name cannot be empty!</span>
				</div>
			</div>
			<div class='control-group'>
				<label class='control-label'>Email</label>
				<div class='controls'>
					<label class='control-label' id='email'></label>
				</div>
			</div>
			<div class='control-group'>
				<label class='control-label' for='department'>Department</label>
				<div class='controls'>
					<input class='custom_input input-xlarge' type='text' id='department' placeholder='Type your department name...'>
				</div>
			</div>
			<div class='control-group'>
				<label class='control-label' for='institution'>Institution</label>
				<div class='controls'>
					<input class='custom_input input-xlarge' type='text' id='institution' placeholder='Type your institution name...'>
				</div>
			</div>
			<div class='control-group'>
				<label class='control-label' for='userTopics'>Interests</label>
				<div class='controls span2' style='margin-left:25px !important'>
					<div id='existTopics'></div>
					<div class='input-append'>
						<select id='topicList'></select>
						<button id='addTopic' class='btn' type='button'>Add</button>
					</div>
				</div>
			</div>
			<button id='save_btn' type='button' class='btn btn-primary' data-loading-text='Saving changes...'>Save changes</button>
			<button id='delete_btn' type='button' class='pull-right btn btn-small btn-danger'>Delete this account</button>
		</div>
	</div> <!-- main -->
</body>
</html>