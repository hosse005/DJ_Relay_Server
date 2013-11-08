<?php

function __autoload($relayTable) {
	include $relayTable . '.php';
}

const hostIP = '127.0.0.1';
const hostUser = 'djadmin';
const hostPw = 'gizmo';
const hostDb = 'djRelay_db';

$relayTable = new relayTable();

// Master Create Session Request
if (isset ($_REQUEST['createSession']) && isset ($_REQUEST['sessionID'])
    	&& isset ($_REQUEST['userID']) && isset ($_REQUEST['user'])) {		// Probably only need userid, but keep for now
	if ($_REQUEST['createSession'] = true) {
		$tableName = $_REQUEST['sessionID'];
	
		$master = array();
		$master['userid'] = $_REQUEST['userID'];
		$master['user'] = $_REQUEST['user'];
//		$master['ip'] = $_REQUEST['ip'];

		$relayTable->createTable($master, $tableName);
	}
}

// User Join Session Request
if (isset ($_REQUEST['joinSession']) && isset ($_REQUEST['sessionID'])
    	&& isset ($_REQUEST['userID']) && isset ($_REQUEST['user'])) {
	if ($_REQUEST['joinSession'] = true) {
		$tableName = $_REQUEST['sessionID'];
	
		$user = array();
		$user['userid'] = $_REQUEST['userID'];
		$user['user'] = $_REQUEST['user'];
//		$user['ip'] = $_REQUEST['ip'];
	
		$relayTable->updateUser($user, $tableName);
	}	
}

// User Song Update Request
if (isset ($_REQUEST['updateSong']) && isset ($_REQUEST['sessionID']) && isset ($_REQUEST['userID'])
		&& isset ($_REQUEST['user']) && isset ($_REQUEST['selected_tubeID'])) {
	if ($_REQUEST['updateSong'] = true) {
		$tableName = $_REQUEST['sessionID'];
	
		$user = array();
		$user['userid'] = $_REQUEST['userID'];
		$user['user'] = $_REQUEST['user'];
//		$user['ip'] = $_REQUEST['ip'];
		$user['selected_tubeID'] = $_REQUEST['selected_tubeID'];
	
		$relayTable->updateUser($user, $tableName);
	}
}

// Master Feed Update Request
if (isset ($_REQUEST['updateFeed']) && isset ($_REQUEST['sessionID']) && isset ($_REQUEST['userID'])
	&& isset ($_REQUEST['user'])) {
	if ($_REQUEST['updateFeed'] = true) {
		$tableName = $_REQUEST['sessionID'];

		$master = array();
		$master['userid'] = $_REQUEST['userID'];
		$master['user'] = $_REQUEST['user'];
//		$master['ip'] = $_REQUEST['ip'];

		$relayTable->updateFeed($master, $tableName);
	}
}

// Fetch Current Feed Request
if (isset ($_REQUEST['fetchFeed']) && isset ($_REQUEST['sessionID']) && isset ($_REQUEST['userID'])
	&& isset ($_REQUEST['user'])) {
	if ($_REQUEST['fetchFeed'] = true) {
		$tableName = $_REQUEST['sessionID'];

		$user = array();
		$user['userid'] = $_REQUEST['userID'];
		$user['user'] = $_REQUEST['user'];
//		$user['ip'] = $_REQUEST['ip'];

		//$relayTable->fetchCurrentFeed($user, $tableName);		// TODO - when user check is implemented
		$relayTable->fetchCurrentFeed($tableName);
	}
}

// User Log Out Request
if (isset ($_REQUEST['userLogOut']) && isset ($_REQUEST['sessionID']) && isset ($_REQUEST['userID'])
	&& isset ($_REQUEST['user'])) {
	if ($_REQUEST['userLogOut'] = true) {
		$tableName = $_REQUEST['sessionID'];

		$user = array();
		$user['userid'] = $_REQUEST['userID'];
		$user['user'] = $_REQUEST['user'];
//		$user['ip'] = $_REQUEST['ip'];

		$relayTable->userLogOut($user, $tableName);
	}
}

// Master Kill Session Request
if (isset ($_REQUEST['killSession']) && isset ($_REQUEST['sessionID']) && isset ($_REQUEST['userID'])
	&& isset ($_REQUEST['user'])) {
	if ($_REQUEST['killSession'] = true) {
		$tableName = $_REQUEST['sessionID'];

		$master = array();
		$master['userid'] = $_REQUEST['userID'];
		$master['user'] = $_REQUEST['user'];
//		$master['ip'] = $_REQUEST['ip'];

		$relayTable->killSession($master, $tableName);
	}
}

echo json_encode($relayTable->replyJSON);


?>