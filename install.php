 <?php
include "config.inc.php";

/* Attempt MySQL server connection. Assuming you are running MySQL
server with default setting (user 'root' with no password) */
$link     = mysqli_connect($DBServer, $DBUser, $DBPassword);

// Check connection
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Attempt create database query execution
$sql = "CREATE DATABASE tasmobackup";
if (mysqli_query($link, $sql)) {
    echo "Database created successfully";
}
// Close connection
mysqli_close($link);


$link = mysqli_connect($DBServer, $DBUser, $DBPassword, $DBName);

// Check connection
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Attempt create database query execution
$sql  = "CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `ip` text NOT NULL,
  `version` text NOT NULL,
  `lastbackup` datetime DEFAULT NULL,
  `noofbackups` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
$sql2 = "TRUNCATE TABLE `devices`;";

$sql25 = "ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`);";

$sql3 - "ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;";
$sql4 = "COMMIT;";

mysqli_query($link, $sql);
mysqli_query($link, $sql2);
mysqli_query($link, $sql25);
mysqli_query($link, $sql3);
mysqli_query($link, $sql4);


// Close connection
mysqli_close($link);
?> 
