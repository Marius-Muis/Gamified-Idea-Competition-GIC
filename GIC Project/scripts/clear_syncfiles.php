<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
include_once 'dbconnect.php';
$pass = filter_input(INPUT_GET, "edtClearSyncfilesPassword", FILTER_SANITIZE_STRING);
$conn = connect();
$query = 'SELECT * FROM Users WHERE AccountType="Admin" AND Password="'.$pass.'" LIMIT 1';
$result = $conn->query($query);
$numrows = $result->num_rows;
if ($numrows != 1)
{
	header("Location: http://www.ideosign.co.za?message=Password+incorrect");
    exit;
}
$result->free();
$handle = fopen("../temp/sessions.txt", 'w');
fclose($handle);
$handle = fopen("../temp/groups.txt", 'w');
fwrite($handle, "redirect");
fclose($handle);
$handle = fopen("../temp/minscore.txt", 'w');
fclose($handle);
$arrmods = array();
for($i = 0;$i < 12;$i++)
{
    $arrmods[$i] = "mod".($i+1);
}
$files = glob('../temp/cookies/*');
foreach($files as $file)
{
  if(is_file($file))
    unlink($file);
}
$query = "UPDATE `Users` SET `LoggedIn` = 0 WHERE `LoggedIn` = 1";
if(!mysqli_query($conn, $query))
{
	$error = $conn->error;
	$conn->close();
	error_log("Didn\'t reset logged in statuses (clear_syncfiles.php, line 49): $error\n",3,'../temp/error_log.txt');
	die('Could not reset logged in statuses.');
}
$query = "";
foreach ($arrmods as $mod)
{
	$handle = fopen("../temp/$mod.txt", 'w');
	fclose($handle);
	$query .= "TRUNCATE TABLE `$mod`;";
}
if (!mysqli_multi_query($conn,rtrim($query,';')))
{
	$error = $conn->error;
	$conn->close();
	error_log("Didn't clear mod tables (clear_syncfiles.php, line 63): $error\n",3,'../temp/error_log.txt');
	die("Could not clear mod tables.");
}
$conn->close();
header ('Location: http://www.ideosign.co.za/default.html?reset=true');