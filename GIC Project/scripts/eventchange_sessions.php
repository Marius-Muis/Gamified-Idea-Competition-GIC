<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include_once 'dbconnect.php';
$connection = connect();
if (isset($_GET['kickalias']))
{
	$alias = $_GET['kickalias'];
	$query = "UPDATE Users SET `LoggedIn` = 0 WHERE `Alias` = '".$alias."'";
	if (!mysqli_query($connection, $query))
	{
		$error = $connection->error;
		$connection->close();
		error_log("Couldn\'t reset logged in status in db (eventchange_sessions.php, line 15): $error\n",3,'../temp/error_log.txt');
	}
	$handle = fopen("../temp/cookies/$alias.txt", 'w');
	fclose($handle);
}
$countparts = 0;
$countmods = 0;
$file = "../temp/sessions.txt";
$line = "";
$query = "SELECT `Alias`, `AccountType` FROM Users WHERE `LoggedIn` = 1";
if ($connection->multi_query($query))
{
	if ($result = $connection->store_result())
	{
		while ($row = $result->fetch_row())
		{
			$line .= $row[0].",";
			if ($row[1] == "Moderator" || $row[1] == "Admin")
			{
				$countmods += 1;
			}
			else
			{
				$countparts += 1;
			}
		}
		$result->free();
	}
	else
	{
		$error = $connection->error;
		$connection->close();
		error_log("Couldn\'t retrieve logged in statuses from db (eventchange_sessions.php, line 32): $error\n",3,'../temp/error_log.txt');
	}
}
else
{
	$error = $connection->error;
	$conection->close();
	error_log("Couldn\'t retrieve logged in statuses from db (eventchange_sessions.php, line 32): $error\n",3,'../temp/error_log.txt');
}
$connection->close();
$handler = fopen($file, 'w');
$line = rtrim($line,',');
fwrite($handler, $line) or error_log('Couldn\'t write to sessions.txt (eventchange_sessions.php, line 49)\n',3,'../temp/error_log.txt');
fclose($handler);
$counters = $countmods.",".$countparts.",".$line;
echo $counters;
flush();