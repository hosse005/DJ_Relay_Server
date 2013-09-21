<html>
<head>
<meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<title>View Users</title>
<link rel="stylesheet" type="text/css" href="default.css">
</head>
<body>
	<div style="text-align: center;">
		<img style="width: 400px; height: 245px;" alt="Raspberrypi"
			src="Das_DJ.png">
	</div>
	<br>


<?php

function __autoload($relayTable) {
	include $relayTable . '.php';
}

const hostIP = '127.0.0.1';
const hostUser = 'root';
const hostPw = 'N0tS0m1n1!';
const hostDb = 'djRelay_db';

// Connect to MySQL
$con=mysqli_connect(hostIP,hostUser,hostPw,hostDb);
// Check connection
if (mysqli_connect_errno())
{
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// *********** TEST COMMANDS for relayTable functionality.. ***************
$testArray = array('userid' => '6969696969', 'user' => 'evan', 'ip' => '192.1.1.1', 'selected_tubeID' => '5NV6Rdv1a3I');
$testUser = array('userid' => '5252039', 'user' => 'gizmo', 'ip' => '10.10.1.1', 'selected_tubeID' => 'VsdLn46UXnA');
$nsync = array('userid' => '1234', 'user' => 'gizmo', 'ip' => '10.10.1.1', 'selected_tubeID' => 'VsdLn46UXnA');
$test = new relayTable();
//$test->createTable($testArray, "dummy1");
//$test->updateUser($testUser, "dummy1");
$test->fetchCurrentFeed("dummy");
$test->updateFeed($testArray, "dummy");
$test->userLogOut($nsync, "dummy");
//$test->killSession($testArray, "dummy1");
// ************************************************************************

if (isset ( $_REQUEST ['ip'] )) { // This should really be a user name check, not IP address
	$ipAddr = $_REQUEST ['ip'];
	$tubeID = $_REQUEST ['selected_tubeID'];
	$user = $_REQUEST ['user'];
	
	$ipCheck = mysqli_query ($con, "SELECT COUNT(*) as count FROM users WHERE ip='$ipAddr'" );
	$ipCheckResult = mysqli_fetch_assoc ($ipCheck );
	$ipCheckResult = $ipCheckResult ['count'];
	echo "ipCheck = $ipCheckResult<br>";
	if ($ipCheckResult > 0) {
		mysqli_query ($con, "UPDATE users SET selected_tubeID='$tubeID' WHERE ip='$ipAddr'" );
		echo "If statement hit<br>"; // Debug
	} else {
		echo "Else statement hit<br>"; // Debug
		mysqli_query ($con, "INSERT INTO users (user,ip,selected_tubeID) VALUES ('$user','$ipAddr','$tubeID')" );
	}
} else {
	echo "No IP submitted to server yet!<br>";
}

$result = mysqli_query ($con,"SELECT * from users" );
// Table starting tag and header cells
echo " <table style='width: 75%; text-align: left; margin-left: auto; margin-right: auto;' border='0' cellpadding='2' cellspacing='2'><tr><th>User ID</th><th>User</th><th>Local IP Address</th><th>Selected Tube ID</th></tr>";
while ( $row = mysqli_fetch_array ( $result ) ) {
	// Display the results in different cells
	echo "<tr><td>" . $row ['userid'] . "</td><td>" . $row ['user'] . "</td><td>" . $row ['ip'] . "</td><td>" . $row ['selected_tubeID'] . "</td></tr>";
}
// Table closing tag
echo "</table>";
echo "<br>";

$selected_tubeID = 'selected_tubeID.txt';
if (file_exists($selected_tubeID)) {
	// Read out currently selected song
	$tubeFile = fopen ( $selected_tubeID, "r" );
	while ( ! feof ( $tubeFile ) ) {
		$jsonData = fgets ( $tubeFile );
	}
	fclose ( $tubeFile );
	
	$phpArray = json_decode ( utf8_encode ( $jsonData ) );
	$crntUsr = $phpArray->user;
	echo "Current Selected User: " . $phpArray->user . "<br><br>";
} else  
	$crntUsr = null;


echo "Next Selected User Info:<br>";
$sql = "SELECT * FROM users WHERE user != '$crntUsr' ORDER BY RAND() LIMIT 1";
$res = mysqli_query ($con, $sql );

if (! mysqli_num_rows ( $res ) > 0) {
	die ( 'No users' );
	echo "No users<br>";
}
$row1 = mysqli_fetch_assoc ($res );
$var1 = $row1 ['userid'];
$var2 = $row1 ['user'];
$var3 = $row1 ['ip'];
$var4 = $row1 ['selected_tubeID'];

echo "User ID: " . $var1 . "<br>";
echo "User: " . $var2 . "<br>";
echo "IP Address: " . $var3 . "<br>";
echo "Selected Tube ID: " . $var4 . "<br>";

print (json_encode ( $row1 )) ;

$file = fopen ( $selected_tubeID, "w" );
fwrite ( $file, json_encode ( $row1 ) );
fclose ( $file );

?>

<br>
	<a href="index.html">Back</a>

</body>
</html>


