<?php
include_once 'dbconnect.php';
$account = $_GET['account'];
$sessionsfile = file_get_contents('../temp/sessions.txt',FILE_IGNORE_NEW_LINES);
$arrsessions = explode(',', $sessionsfile);
$index = array_search($account,$arrsessions);
if (!$index)
	error_log("The alias wasn\'t found (lobby_admin_kick.php, line 7)\n",3,'../temp/error_log.txt');
else
{
	unset($arrsessions[$index]);
	$newsessions = array_values($arrsessions);
	$handle = fopen('../temp/sessions.txt', 'w');
	fwrite($handle, implode($newsessions, ','));
	fclose($handle);
	$conn = connect();
	$query = "UPDATE Users SET `LoggedIn` = 0 WHERE `Alias` = $alias";
	mysqli_query($conn, $query);
	$conn->close();
}