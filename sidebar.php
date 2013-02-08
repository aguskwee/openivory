<?php		
	include 'lib/utils.php';

	function get_author_info() {
		global $dbhost;
		global $dbusername;
		global $dbpassword;
		global $dbname;
		
		$info = array();
		if(!isset($_COOKIE['author_id'])) return json_encode($info);
		
		// set author id
		$author_id = $_COOKIE['author_id'];
		
		// connect to database
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) {return array('error_msg' => 'Error connecting database!');}
		
		// set character set
		mysqli_set_charset($cid, 'utf8');
		
		// get author info
		$sql = 'select * from authors where author_id = \'' . $author_id . '\'';
		$result = mysqli_query($cid, $sql);
		if(!$result) {mysqli_close($cid); return json_encode(array('error_msg' => 'Error executing query!'));}
		if($row = mysqli_fetch_assoc($result)) {
			$name = trim($row['given_name'] . ' ' . $row['last_name']);
			$email = trim($row['email']);
			$info['name'] = $name;
			$info['email'] = $email;
		}
		mysqli_free_result($result);
		
		// get topic of interest
		$sql = 'select t.topic_id as tid, t.parent_topic_id as ptid from author_topics a, topics t where a.author_id = ' . $author_id . ' and a.topic_id = t.topic_id';
		$result = mysqli_query($cid, $sql);
		if(!$result) {mysqli_close($cid); return json_encode(array('error_msg' => 'Error executing query 2!'));}
		$topics = array();
		while($row = mysqli_fetch_assoc($result)) {
			$topic = trim($row['ptid'] . $row['tid']);
			array_push($topics, $topic);
		}
		mysqli_free_result($result);
		$info['topics'] = $topics;
		
		// close database
		mysqli_close($cid);
		
		// send to client
		return json_encode($info);
	}
	
	function get_viewed_spu_details() {		
		global $dbhost;
		global $dbusername;
		global $dbpassword;
		global $dbname;
		
		$recentk = 3;
		
		$spu_details = array();
		if(!isset($_COOKIE['lastk_articles'])) return json_encode($spu_details);
		
		// get location
		$location = $_SERVER['REQUEST_URI'];
		$current_spu_id = '';
		if(strpos($location, 'spu_detail.php') !== false) {
			// get spu id
			$location = explode('?', $location);
			$location = $location[1];
			$location = explode('&', $location);
			foreach($location as $idx => $obj) {
				$prop = explode('=', $obj);
				$prop = $prop[0];
				$val = explode('=', $obj);
				$val = $val[1];
				if($prop == 'id') {
					$current_spu_id = $val;
					break;
				}
			}
		}
		
		// get spu ids
		$spu_ids = $_COOKIE['lastk_articles'];
		$spu_ids = explode(',', $spu_ids);
	
		// get last id
		if($current_spu_id == '') {
			// get last $recentk history
			$temp_spu_ids = array();
			if(count($spu_ids) > $recentk) {
				for($i = count($spu_ids) - 1; $i >= count($spu_ids) - $recentk; $i--) {
					array_push($temp_spu_ids, $spu_ids[$i]);
				}
				$spu_ids = $temp_spu_ids;
			}		
		
		}
		else {
			$temp_spu_ids = array();
			if(count($spu_ids) > $recentk) {
				for($i = count($spu_ids) - 2; ($i >= count($spu_ids) - $recentk - 1) && ($i >=0); $i--) {
					array_push($temp_spu_ids, $spu_ids[$i]);
				}
				$spu_ids = $temp_spu_ids;
			}		
		}
		
		// connect to database
		$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
		if(!$cid) {return array('error_msg' => 'Error connecting database!');}
		
		// set character set
		mysqli_set_charset($cid, 'utf8');
		
		// get title, and first upload time
		$sql = 'select s.spu_id, s.title, year(v.timestamp) as yr from spu s, spu_versions v where s.spu_id = v.spu_id and v.version = \'1\' and s.spu_id in (\'' .
			   implode('\', \'', $spu_ids) . '\');';
		$result = mysqli_query($cid, $sql);
		if(!$result) {mysqli_close($cid); return json_encode(array('error_msg' => 'Error executing query!'));}
		while($row = mysqli_fetch_assoc($result)) {
			$spu_id = $row['spu_id'];
			$title = $row['title'];
			$year = $row['yr'];
			$spu_details[$spu_id] = array('id' => $spu_id, 'title' => $title, 'year' => $year);
		}
		mysqli_free_result($result);
		
		// get authors
		$sql = 'select concat(a.given_name, \' \', a.last_name) as name, aship.spu_id as spu_id, aship.author_id as author_id, aship.rank from authors a, authorships aship where ' .
			   'a.author_id = aship.author_id and aship.spu_id in (\'' . implode('\', \'', $spu_ids) . '\');';
		$result = mysqli_query($cid, $sql);
		if(!$result) {mysqli_close($cid); return json_encode(array('error_msg' => 'Error executing query!'));}
		while($row = mysqli_fetch_assoc($result)) {
			$name = $row['name'];
			$spu_id = $row['spu_id'];
			$author_id = $row['author_id'];
			$rank = $row['rank'];
			if(!isset($spu_details[$spu_id]['authors'])) $authors = array();
			else $authors = $spu_details[$spu_id]['authors'];
			$authors[$author_id] = array('id' => $author_id, 'name' => $name, 'rank' => $rank);
			$spu_details[$spu_id]['authors'] = $authors;
		}
		mysqli_free_result($result);
		
		// close database
		mysqli_close($cid);
	
		// resort spu
		$sorted_spus = array();
		foreach($spu_ids as $i => $spu_id) {
			if(!isset($spu_details[$spu_id])) continue;
			array_push($sorted_spus, $spu_details[$spu_id]);
		}
		
		// send to client
		return json_encode($sorted_spus);
	}
	
?>

<script language='javascript' type='text/javascript' charset='utf-8'>
$(function() {
	// check whether the user has already logged in
	// show nothing if the user has not logged in
	// otherwise, show dashboard
	if($.cookie('author_id')) {
		$('#sidebar').html('<h4>Loading dashboard...</h4>');
		
		var sidebar_content = '';
		
		// add dashboard text
		sidebar_content += '<h4>Dashboard</h4>';
		
		// add user info
		var user_info = <?php echo get_author_info(); ?>;
		var user_div = '<h4 style=\'margin-bottom:0px\'>' + user_info['name'] + '</h4><p style=\'font-size:0.9em\'>' + user_info['email'] + '</p>';
		var topics = new Array();
		if(user_info['topics'] && (size(user_info['topics']) > 0)) {
		/*	topics += 'Topics of Interest<br>';
			for(var i = 0; i < size(user_info['topics']); i++) {
				var topic = user_info['topics'][i];
				topics.push(topic);
			}
			user_div += '<br>' + topics.join(', ');
		*/}
		sidebar_content += '<div style=\'margin:15px\'>' + user_div;
		
		// add followed author link
		sidebar_content += '<h5><a href=\'following.php\' style=\'line-height:3\'>Authors I follow</a></h5>';
		
		// get recently viewed spu
		if($.cookie('lastk_articles')) {
			var spu_details = <?php echo get_viewed_spu_details(); ?>;
			var spu_div = new Array();
			for(var i in spu_details) {
				var spu = spu_details[i];
				var id = spu['id']
				var title = spu['title'];
				var year = spu['year'];
				var authors = spu['authors'];
				
				// create author block
				var author_block = new Array();
				var authorArr = new Array();
				for(var i in authors) authorArr.push(authors[i]);
				authorArr.sort(function(a, b) {return a.rank - b.rank;});
				
				for(var i in authorArr) {
					var author = authorArr[i];
					var id = author['id'];
					var name = author['name'];
					author_block.push('<a href=\'author_detail.php?id=' + id + '\'>' + name + '</a>');
				}
				if(author_block.length > 0) {
					if(author_block.length == 1) author_block = author_block.join(', ');
					else if(author_block.length == 2) author_block = author_block[0] + ' and ' + author_block[1];
					else author_block = author_block[0] + ' et al';
				}
				
				var div = '<div class=\'spu_box_sidebar\'>' + author_block + ' (' + year + ') <a href=\'spu_detail.php?id=' + id + '\'>' + title + '</a></div>'; 
				spu_div.push(div);
			}
			
			sidebar_content += ('<h5>Recently Viewed</h5>' + spu_div.join('<br>') + '</div>');
		}
		
		$('#sidebar').html(sidebar_content);
	}

});

function size(arr) {
	var count = 0; 
	for(var i in arr) count++;
	return count;
}
</script>