<?php

// Report all errors except E_NOTICE
error_reporting(E_ALL ^ E_NOTICE);

class relayTable {
	
	// TODO - Need to replace all SQL queries w/ prepared statements!!!
	
	private $con;
	private $master;
	
	public  $tableName;
	public  $selected_tubeID;
	
	/* $replyJSON array holds all callback fields for the clients
	   The content of this array is as follows:
	    -----------------------------------------------------------------
	   |dbCon 	      | boolean for database connection status           |
	    -----------------------------------------------------------------
	   |createTable   | boolean for master table creation status         |
	    -----------------------------------------------------------------
   	   |insertMaster  | boolean for master addition to table status      |
   	    -----------------------------------------------------------------
   	   |insertUser    | boolean for user addition to table status        |
   	    -----------------------------------------------------------------
   	   |updateUser    | boolean for existing user update status          |
   	    -----------------------------------------------------------------
   	   |masterPresent | boolean for master exsistence                    |
   	    -----------------------------------------------------------------
   	   |usersPresent  | boolean for user(s) exsistence on updateFeed     |
   	    ----------------------------------------------------------------- 
   	   |updateFeed    | boolean for updateFeed status                    |
   	    -----------------------------------------------------------------
   	   |selectedUser  | JSON for selectedUser row in table               |
   	    ----------------------------------------------------------------- 
   	   |sessionKill   | boolean for session drop status                  |
   	    -----------------------------------------------------------------   
	*/
	public  $replyJSON;
	
	function __construct() {
		$this->con = mysqli_connect ( hostIP, hostUser, hostPw, hostDb );
		$this->replyJSON = array();
		if (mysqli_connect_errno ()) {
			//echo "Failed to connect to Database in class ", get_class ( $this ), "\n";
			$this->replyJSON['dbCon'] = false;
//			die ('db Connection Error');	// Maybe just allow script to proceed so client can process the error..
		} else
			$this->replyJSON['dbCon'] = true;
	}
	
	function table_exist($table){
		$sql = "SHOW TABLES LIKE '".$table."'";
		$res = mysqli_query($this->con, $sql);
		return ($res->num_rows > 0);
	}
	
	function fetchRecord($userid, $tableName){
		$sql = "SELECT * FROM `" . $tableName ."` WHERE userid = '$userid' ";
		$res = mysqli_query($this->con, $sql);
	
		if (!$res) {
			$this->replyJSON['masterPresent'] = false;
			return;
		} else
			$this->replyJSON['masterPresent'] = true;
		return mysqli_fetch_assoc($res);
	}

	
	function createTable($master, $tableName) {
		$this->master = $master;
		$this->tableName = $tableName;

		// First Check if table exists
		if ($this->table_exist($tableName)) {
			$this->replyJSON['sessionAlreadyExists'] = true;
			return;
		}
		
		$sql = "CREATE TABLE `" . $this->tableName . "`
		(
		PID INT NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(PID),
		userid VARCHAR(50),
		user VARCHAR(50),
		ip VARCHAR(50),
		selected_tubeID VARCHAR(50),
		selected_userID VARCHAR(50),
		nxtSelected_userID VARCHAR(50)
		)";
		
		if (mysqli_query ( $this->con, $sql )) {
			//echo "'$this->tableName' created successfully";
			$this->replyJSON['createTable'] = true;
		} else {
			//echo "Error creating '$this->tableName': " . mysqli_error ( $this->con );
			$this->replyJSON['createTable'] = false;
			return;
		}
		
		// Insert master to the table as PID = 1, and initialize w/ das golden Scheiße
		$sql = "INSERT INTO `" . $this->tableName . "` (userid,user,selected_userID,nxtSelected_userID,selected_tubeID) VALUES
				(
				'{$this->master['userid']}',
				'{$this->master['user']}',
				'{$this->master['userid']}',
				'{$this->master['userid']}',
				'5NV6Rdv1a3I'
				) ";
		
		if (mysqli_query ( $this->con, $sql )) {
			//echo "'$this->tableName' INSERT command success";
			$this->replyJSON['insertMaster'] = true;
		} else {
			//echo "Error inserting master to '$this->tableName': " . mysqli_error ( $this->con );
			$this->replyJSON['insertMaster'] = false;
			return;
		}
	}

	
	function updateUser($user, $tableName) {
		// TODO - need to decide if something w/ ip checking should be enforced
		// Maybe this is separate check to ensure requesting user ip doesn't already exist in table
		// For now, we just check user id..
		
		// First Check if table exists
		if (!$this->table_exist($tableName)) {
			$this->replyJSON['sessionNotFound'] = true;
			return;
		}
		
		$userCheck = "SELECT COUNT(*) as count FROM  `" . $tableName . "` WHERE 
					  userid='{$user['userid']}'";
					 // OR ip='{$user['ip']}'";
		
		$userCheck = mysqli_query ( $this->con, $userCheck );
		$userCheckResult = mysqli_fetch_assoc($userCheck);
		$userCheckResult = $userCheckResult ['count'];
		
		if ($userCheckResult > 0) {
			// Check for user joining session that he/she/it is already master of
			// Ideally, this is enforced on client side and prevented
			// Unfortunately, I have also written the client side code so..
			if ($user['selected_tubeID'] == null){
				$this->replyJSON['masterJoinError'] = true;
				return;
			}
			
			$sql = "UPDATE `" . $tableName . "` SET selected_tubeID = '{$user['selected_tubeID']}' 
			        WHERE userid = '{$user['userid']}'";
			
			if (mysqli_query ( $this->con, $sql )) {
				//echo "'$tableName' UPDATE command success";
				$this->replyJSON['updateUser'] = true;
			} else {
				//echo "Error updating user info to '$tableName': " . mysqli_error ( $this->con );
				$this->replyJSON['updateUser'] = false;
				return;
			}
		} else {
			$sql = "INSERT INTO `" . $tableName . "` (userid,user) VALUES
			(
			'{$user['userid']}',
			'{$user['user']}'
			) ";
			
			if (mysqli_query ( $this->con, $sql )) {
				//echo "'$tableName' INSERT command success";
				$this->replyJSON['insertUser'] = true;
			} else {
				//echo "Error inserting user to '$tableName': " . mysqli_error ( $this->con );
				$this->replyJSON['insertUser'] = false;
				return;
			}
		}
	}
	
	function updateFeed($master, $tableName) {
		// TODO - still need to add some null checking to prevent blank vid being selected
		
		// First check if session still exists
		if (!$this->table_exist($tableName)) {
			$this->replyJSON['sessionNotFound'] = true;
			return;
		}
		
		// Read out currently selected user from Master entry
		$sql = "SELECT * FROM `" . $tableName ."` WHERE PID = 1";
		$res = mysqli_query($this->con, $sql);
		
		if (!$res) {
			$this->replyJSON['masterPresent'] = false;
			return;
		} else
			$this->replyJSON['masterPresent'] = true;
		
		$res = mysqli_fetch_assoc($res);
		
		// Only allow master of the session to make this call
		if ($res['userid'] != $master['userid']){
			$this->replyJSON['unauthorizedRequest'] = true;
			return;
		}
		
		$selected_userID = $res['selected_userID'];
		$nxtSelected_userID = $res['nxtSelected_userID'];
		
		// First, update selected user pipeline held in the all records
		$sql = "UPDATE `" . $tableName . "` SET selected_userID = '$nxtSelected_userID'"; 
//			    WHERE userid = '{$master['userid']}'";
		$res = mysqli_query($this->con, $sql);
		if (!$res){
			$this->replyJSON['updateFeed'] = false;		
			return;
		}
		// Now, grab this record and echo to master client
		$sql = "SELECT * FROM `" . $tableName . "` WHERE userid = '$selected_userID'";
		$res = mysqli_query($this->con, $sql);
		if (!$res) {
			$this->replyJSON['updateFeed'] = false;	
			return;
		}
		$row1 = mysqli_fetch_assoc($res);
		$this->replyJSON['selectedUser'] = $row1;
		
		// Next, randomly select next user and disallow current user to be selected again
		// TODO - also don't allow user to be selected w/ no tube ID (e.g. check against NULL)
		$sql = "SELECT * FROM `" . $tableName . "` WHERE userid != '$nxtSelected_userID'
				ORDER BY RAND() LIMIT 1";
		$res = mysqli_query ( $this->con, $sql );
		
		if (! mysqli_num_rows ( $res ) > 0) {
			$this->replyJSON['usersPresent'] = false;
			$row1 = array('userid' => $master['userid']);
//			$row1 = json_encode($row1); 
//			echo $row1;
		} else {
			$this->replyJSON['usersPresent'] = true;
			$row1 = mysqli_fetch_assoc ( $res );
		}			
		
		// Next, update all records in table with newly selected userid
		// Update master record pipe w/ randomly selected nxtSelected_userID
		$sql = "UPDATE `" . $tableName . "` SET nxtSelected_userID = '{$row1['userid']}'"; 
//			    WHERE userid = '{$master['userid']}'";
		
		if (mysqli_query ( $this->con, $sql )) {
			$this->replyJSON['updateFeed'] = true;
		} else {
			$this->replyJSON['updateFeed'] = false;
			return;
		}
		
		// Finally, echo updated master record
		$this->replyJSON['selectedUser'] = $this->fetchRecord($nxtSelected_userID, $tableName);	// This is actually the selected user now,
																								// stole from variable used before updating
																								// the table.. pretty shitty i know
	}
	
	function fetchCurrentFeed($tableName){
		// TODO - Maybe want to add user validation query to this function..
		
		// First check if session still exists
		if (!$this->table_exist($tableName)) {
			$this->replyJSON['sessionNotFound'] = true;
			return;
		}
		// Read currently selected tube ID		
		$sql = "SELECT * FROM `" . $tableName ."` WHERE PID = 1";
		$res = mysqli_query($this->con, $sql);
		
		if (!$res) {
			//echo "Error finding master for fetchCurrentFeed: " . mysqli_error($this->con). "<br>";
			$this->replyJSON['masterPresent'] = false;
			return;
		} else
			$this->replyJSON['masterPresent'] = true;
		
		$res = mysqli_fetch_assoc($res);
		$selected_userID = $res['selected_userID'];
		
		// Fetch selected user's songID
		$sql = "SELECT * FROM `" . $tableName . "` WHERE userid = '$selected_userID'";
		$res = mysqli_query ( $this->con, $sql );		
		
		if (!$res) {
			//echo "Error finding selected user! <br>";
			// TODO - add some retry mechanism for this failure to get new userid
		}
		
		$res = mysqli_fetch_assoc($res);
		$this->selected_tubeID = $res['selected_tubeID'];
		$this->replyJSON['selectedUser'] = $res;
		//echo "<br>" . json_encode($this->replyJSON['selectedUser']) . "<br>";
	}
	
	function userLogOut($user, $tableName) {
		// Delete user from the table
		// TODO - Don't allow master to logout, master must give a killSession request
		$sql = "DELETE FROM `" . $tableName . "` WHERE userid = '{$user['userid']}'";
		$res = mysqli_query($this->con, $sql);
		if (!$res) {
			//echo "'{$user['userid']}'" . " doesn't exist in table " . "'$tableName'";
			$this->replyJSON['updateUser'] = false;
		} else
			$this->replyJSON['updateUser'] = true;
	}
	
	function killSession($master, $tableName) {
		// First pull master record for verification of table drop
		$sql = "SELECT * FROM `" . $tableName ."` WHERE PID = 1";
		$res = mysqli_query($this->con, $sql);
		
		if (!$res) {
			//echo "Error finding master for killSession command: " . mysqli_error($this->con). "<br>";
			$this->replyJSON['masterPresent'] = false;
			return;
		} else
			$this->replyJSON['masterPresent'] = true;
		
		$res = mysqli_fetch_assoc($res);
		//echo "SQL master fetch = " .$res['userid'] . "<br>";
		//echo "Master id = " .$master['userid'] . "<br>";
		
		if ($res['userid'] == $master['userid']) {
			//echo "Howdy! <br>";
			// Drop the session table
			$sql = "DROP TABLE IF EXISTS `" . $tableName . "`";
			$res = mysqli_query($this->con, $sql);			
			if (!$res) {
				//echo "Couldn't not drop `" . $tableName . "`";
				$this->replyJSON['sessionKill'] = false;
				return;
			} else
				$this->replyJSON['sessionKill'] = true;
		} else {
			echo "Only master can delete a session";
			// TODO - anything else..
			$this->replyJSON['sessionKill'] = false;
		}
	}
		
	function __destruct() {
		//echo "Destroying  " . $this->tableName . " object " . "<br>";
		$this->replyJSON = json_encode($this->replyJSON);
		//echo "Value of replyJSON object = " . $this->replyJSON;
		mysqli_close ( $this->con );
	}
}
?>