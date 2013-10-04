<?php
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
	
	function createTable($master, $tableName) {
		$this->master = $master;
		$this->tableName = $tableName;
		$sql = "CREATE TABLE `" . $this->tableName . "`
		(
		PID INT NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(PID),
		userid VARCHAR(50),
		user VARCHAR(50),
		ip VARCHAR(50),
		selected_tubeID VARCHAR(50),
		selected_userID VARCHAR(50)
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
		$sql = "INSERT INTO `" . $this->tableName . "` (userid,user,selected_tubeID,selected_userID) VALUES
				(
				'{$this->master['userid']}',
				'{$this->master['user']}',
				'5NV6Rdv1a3I',
				'{$this->master['userid']}'
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
		$userCheck = "SELECT COUNT(*) as count FROM  `" . $tableName . "` WHERE 
					  userid='{$user['userid']}'";
					 // OR ip='{$user['ip']}'";
		
		$userCheck = mysqli_query ( $this->con, $userCheck );
		$userCheckResult = mysqli_fetch_assoc($userCheck);
		$userCheckResult = $userCheckResult ['count'];
		
		if ($userCheckResult > 0) {
			//echo "userCheckResult > 0" . "/n";
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
		// Read out currently selected user from Master entry
		$sql = "SELECT * FROM `" . $tableName ."` WHERE PID = 1";
		$res = mysqli_query($this->con, $sql);
		
		if (!$res) {
			//echo "Error finding master for updateFeed: " . mysqli_error($this->con). "<br>";
			$this->replyJSON['masterPresent'] = false;
			return;
		} else
			$this->replyJSON['masterPresent'] = true;
		
		$res = mysqli_fetch_assoc($res);
		$selected_userID = $res['selected_userID'];
		
		// Randomly select next user, disallow current user to be selected again
		// TODO - also don't allow user to be selected w/ no tube ID (e.g. check against NULL)
		// TODO - only let master user execute this command!
		$sql = "SELECT * FROM `" . $tableName . "` WHERE userid != '$selected_userID'
				ORDER BY RAND() LIMIT 1";
		$res = mysqli_query ( $this->con, $sql );
		
		if (! mysqli_num_rows ( $res ) > 0) {
			//echo "No users joined this table yet <br>";
			$this->replyJSON['usersPresent'] = false;
			return;
		} else
			$this->replyJSON['usersPresent'] = true;
		
		// Update master table with newly selected userid
		$row1 = mysqli_fetch_assoc ( $res );
		$sql = "UPDATE `" . $tableName . "` SET selected_userID = '{$row1['userid']}' 
			    WHERE userid = '{$master['userid']}'";
		
		if (mysqli_query ( $this->con, $sql )) {
			//echo "<br>" . '$tableName'. " UPDATE feed command success with '{$row1['userid']}'";
			$this->replyJSON['updateFeed'] = true;
			$this->replyJSON['selectedUser'] = $row1;
			//echo "<br>". json_encode($this->replyJSON['selectedUser']) . "<br>";
		} else {
			//echo "Error updating selected user to '$tableName': " . mysqli_error ( $this->con );
			$this->replyJSON['updateFeed'] = false;
			return;
		}
	}
	
	function fetchCurrentFeed($tableName){
		// TODO - Maybe want to add user validation query to this function..
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