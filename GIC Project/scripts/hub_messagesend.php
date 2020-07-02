<?php
header("Access-Control-Allow-Origin: http://ideosign.co.za");
$alias = $_GET["alias"];
$arrfile = file("../temp/cookies/$alias.txt",FILE_IGNORE_NEW_LINES);
$table = $arrfile[3];
include_once 'dbconnect.php';
$bcheck = false;
$conn = connect();
$ajaxcalltype = $_GET['type'];
if ($ajaxcalltype == 'time' && $alias == 'mod1')
{
	$arrcurrentmods = array();
	if (!strpos($arrfile[5], ','))
	{
		array_push($arrcurrentmods, $arrfile[5]);
	}
	else
	{
		$arrcurrentmods = explode(',', $arrfile[5]);
	}
}
switch ($ajaxcalltype)
{
    case "pmessage":
		$messageidquery = "SELECT `MessageID` FROM $table ORDER BY `MessageID` DESC LIMIT 1";
		$result = mysqli_query($conn, $messageidquery, MYSQLI_STORE_RESULT);
		if ($result === FALSE)
		{
			$error = $conn->error;
			$conn->close();
			error_log("MySQLi Error at line 94: $error\n",3,"../temp/error_log.txt");
			exit();
		}
		$row = mysqli_fetch_row($result);
		$messageid = $row[0] + 1;
        $type = $conn->real_escape_string("pmessage");
        $data = $conn->real_escape_string($alias.":".$_GET['message']);
        $bcheck = true;
        break;
    case "like":
        $data = $conn->real_escape_string(null);
        $type = $conn->real_escape_string("like");
        $messageid = $conn->real_escape_string($_GET['messageid']);
        $bcheck = true;
        break;
    case "tag":
        $data = $conn->real_escape_string(null);
        $type = $conn->real_escape_string("tag");
        $messageid = $conn->real_escape_string($_GET['messageid']);
        $bcheck = true;
        break;
    case "time":
        $messageid = $conn->real_escape_string("0");
        $type = $conn->real_escape_string("time");
        try
        {
            $date = new DateTime("now", new DateTimeZone('Africa/Johannesburg'));
        }
        catch (Exception $e)
        {
            $conn->close();
            error_log("Could not retrieve the server time (hub_messagesend.php, line 53).\n",3,"../temp/error_log.txt");
			exit();
        }
        $line = $_GET['offsetchange'].";".$date->format('Y-m-d H:i:s');  // offsetchange = int minutes; second param (timeThen) in mysqli datetime format
        $data = $conn->real_escape_string($line);
        $bcheck = true;
        break;
	case "kick":
		$messageid = 0;
		$type = $conn->real_escape_string('kick');
		$data = $conn->real_escape_string($_GET['kickalias']);
		$bcheck = true;
		break;
	case "untag":
		$data = '';
		$messageid = $conn->real_escape_string($_GET['messageid']);
		$type = $conn->real_escape_string('untag');
		$bcheck = true;
		break;
}
if (!$bcheck)
{
    $conn->close();
    error_log("The GET param did not trigger (hub_messagesend.php, line 64).\n",3,"../temp/error_log.txt");
	exit();
}
else
{
	$type = "'".$type."'";
	$data = "'".$data."'";
	$insertquery = '';
	if ($type == "'time'")
	{
		foreach ($arrcurrentmods as $table)
			$insertquery .= "INSERT INTO $table (MessageID, Type, Data) VALUES ($messageid,$type,$data);";
		$insertquery = rtrim($insertquery, ';');
		if (!mysqli_multi_query($conn, $insertquery))
		{
			$error = $conn->error;
			$conn->close();
			error_log("MySQLi Error at line 83: $error\n",3,"../temp/error_log.txt");
			exit();
		}
	}
	else
	{
		$insertquery = "INSERT INTO $table (`MessageID`,`Type`,`Data`) VALUES ($messageid,$type,$data)";
		if (!$conn->query($insertquery))
		{
			$error = $conn->error;
			$conn->close();
			error_log("MySQLi Error at line 94: $error\n",3,"../temp/error_log.txt");
			exit();
		}
	}
    $conn->close();
}