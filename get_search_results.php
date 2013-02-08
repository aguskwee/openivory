<?php
	// display warning messages
	//error_reporting(E_ALL);
	//ini_set('display_errors', true);
	
	// get user input
	if(isset($_REQUEST['s'])) $str = $_REQUEST['s'];
	if(!isset($str)) $str = '';
	
	// include library
	include 'lib/utils.php';

	// connect to mysqli
	$cid = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbname);
	if(!$cid) {echo json_encode(array('error_msg' => 'Cannot connect to database!')); return;}

	// set character set
	mysqli_set_charset($cid, 'utf8');

	// get author id
	$sql = 'select author_id from authors where concat(given_name, \' \', last_name) like \'%' . $str . '%\'';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo json_encode(array('error_msg' => 'Error reading authors table!')); return;}
	$author_ids = array();
	while($row = mysqli_fetch_assoc($result)) {
		$author_id = $row['author_id'];
		array_push($author_ids, $author_id);
	}
	mysqli_free_result($result);

	// get spu id from author id
	$sql = 'select spu_id from authorships where author_id in (\'' . implode('\', \'', $author_ids) . '\')';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo json_encode(array('error_msg' => 'Error reading authorships table')); return;}
	$spu_ids = array();
	while($row = mysqli_fetch_assoc($result)) {
		$spu_id = $row['spu_id'];
		$spu_ids[$spu_id] = 1;
	}
	mysqli_free_result($result);

	// get spu id from spu table
	$sql = 'select spu_id from spu where title like \'%' . $str . '%\'';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo json_encode(array('error_msg' => 'Error reading spu table!')); return;}
	while($row = mysqli_fetch_assoc($result)) {
		$spu_id = $row['spu_id'];
		$spu_ids[$spu_id] = 1;
	}
	mysqli_free_result($result);


	// get title of spus
	$sql = 'select spu_id, title from spu where spu_id in (\'' . implode('\', \'', array_keys($spu_ids)) . '\')';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo json_encode(array('error_msg' => 'Error reading spu table (title)!')); return;}
	$spus = array();
	while($row = mysqli_fetch_assoc($result)) {
		$spu_id = $row['spu_id'];
		$title = $row['title'];
		if(!isset($spus[$spu_id])) $spus[$spu_id] = array();
		$spus[$spu_id]['title'] = $title;
	}
	mysqli_free_result($result);

	// get timestamp and remark
	$sql = 'select spu_id, unix_timestamp(timestamp) as time, remark from spu_versions where version = 1 and spu_id in (\'' . implode('\', \'', array_keys($spu_ids)) . '\')';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo json_encode(array('error_msg' => 'Error reading version table!')); return;}
	while($row = mysqli_fetch_assoc($result)) {
		$spu_id = $row['spu_id'];
		$timestamp = $row['time'];
		$remark = $row['remark'];
		if(!isset($spus[$spu_id])) continue;
		$spus[$spu_id]['timestamp'] = Timesince($timestamp);
		$spus[$spu_id]['remark'] = $remark;
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

	// read authorship table
	$sql = 'select author_id, spu_id, rank from authorships where spu_id in (\'' . implode('\', \'', array_keys($spu_ids)) . '\')';
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo json_encode(array('error_msg' => 'Error reading authorships table!')); return;}
	$author_ids = array();
	while($row = mysqli_fetch_assoc($result)) {
		$author_id = $row['author_id'];
		$spu_id = $row['spu_id'];
		$rank = $row['rank'];
		$authors = array();
		if(isset($spus[$spu_id]['authors'])) $authors = $spus[$spu_id]['authors'];
		$authors[$author_id] = array('id' => $author_id, 'rank' => $rank);
		$spus[$spu_id]['authors'] = $authors;
		$author_ids[$author_id] = 1;
	}
	mysqli_free_result($result);

	// get author name
	$sql = 'select author_id, concat(given_name, \' \', last_name) as name from authors where author_id in (\'' . implode('\', \'', array_keys($author_ids)) . '\')'; 
	$result = mysqli_query($cid, $sql);
	if(!$result) {echo json_encode(array('error_msg' => 'Error reading authors table!')); return;}
	$author_map = array();
	while($row = mysqli_fetch_assoc($result)) {
		$author_id = $row['author_id'];
		$author_name = $row['name'];
		$author_map[$author_id] = $author_name;
	}
	mysqli_free_result($result);
	
	// put author name in spus object
	foreach($spus as $spu_id => $obj) {
		$authors = $obj['authors'];
		foreach($authors as $author_id => $map) {
			$author_name = $author_map[$author_id];
			if(!isset($author_name)) continue;
			$authors[$author_id]['name'] = $author_name;
		}
		$spus[$spu_id]['authors'] = $authors;
	}
	
	// close connections
	mysqli_close($cid);
	
	// send to users
	echo json_encode($spus);
?>