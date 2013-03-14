<?php
	// show warning messages
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	// include library
	include 'lib/utils.php';
	
	// get user input
	if(isset($_REQUEST['num'])) $recentk = $_REQUEST['num'];
	if(isset($_REQUEST['page'])) $page = $_REQUEST['page'];
	if(!isset($recentk)) $recentk = 5;
	if(!isset($page)) $page = 1;
	$page--;
	
	// connect to database
	$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
	if(!$cid) return;
	
	// set character set
	mysqli_set_charset($cid, 'utf8');
		
	$article_order = array();
		
	// get recentk articles
	$sql = 'select spu_id, unix_timestamp(timestamp) as time from spu_versions where version = 1 order by time desc limit ' . $recentk . ' offset ' . ($recentk * $page);
	$result = mysqli_query($cid, $sql);
	if(!$result) {return json_encode(array('error_msg' => 'Error reading versions table!'));}
	$spus = array();
	$latest = 1;
	while($row = mysqli_fetch_assoc($result)) {
		$spu_id = $row['spu_id'];
		$timestamp = $row['time'];
		$spus[$spu_id] = array('id' => $spu_id, 'timestamp' => Timesince($timestamp), 'rank' => $latest);
		$latest++;
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
	echo json_encode($sorted_spus);
?>