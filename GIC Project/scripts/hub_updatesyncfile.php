<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
$alias = $_GET["alias"];
$file = "../temp/cookies/$alias.txt";
if (file_exists($file))
	$arrfile = file($file,FILE_IGNORE_NEW_LINES);
else
{
	$string = "0|0|Time|END;0";
	echo $string;
	flush();
	exit;
}
$grouptype = $arrfile[2];
$syncname = $arrfile[3];
unset($arrfile);
include_once 'dbconnect.php';
$filename = "../temp/$syncname.txt";
$lastmessage = $_GET["messageid"];
$string = '';
$conn = connect();
if ($lastmessage == -1)
{
	$arrsyncfile = file($filename, FILE_IGNORE_NEW_LINES);
	$lastrecordedid = count($arrsyncfile);
	unset($arrsynfile);
	$query = "SELECT * FROM $syncname";
}
else
	$query = "SELECT * FROM $syncname WHERE ID > $lastmessage";
$string = '';
$outstring = '';
if ($result = mysqli_query($conn,$query,MYSQLI_STORE_RESULT))
{
    while ($row = $result->fetch_row())
    {
		switch ($row[2])
		{
			case "pmessage":
				if($grouptype == "pseudonyms_feedback" || $grouptype == "pseudonyms_none")
				{
					$aliasmessage = "'" . substr($row[3], 0, strpos($row[3], ':')) . "'";
					$aliasquery = "SELECT Name,Surname FROM Users WHERE (Alias=$aliasmessage)";
					$pseudonym = mysqli_query($conn, $aliasquery, MYSQLI_STORE_RESULT);
					$pseudonymresult = $pseudonym->fetch_row();
					$sending = $pseudonymresult[0]."_".$pseudonymresult[1];
					$rowthree = $sending.substr($row[3], strpos($row[3], ':'));
					$string .= $row[0] . "|" . $row[1] . "|" . $row[2] . "|" . $rowthree . "\n";
				}
				else
				{
					$string .= $row[0] . "|" . $row[1] . "|" . $row[2] . "|" . $row[3] . "\n";
				}
				break;
			case "kick":
				if($grouptype == "pseudonyms_feedback" || $grouptype == "pseudonyms_none")
				{
					$name = "'" . substr($row[3], 0, strpos($row[3], '_')) . "'";
					$surname = "'" . substr($row[3], strpos($row[3], '_') + 1) . "'";
					$aliasquery = "SELECT Alias FROM Users WHERE `Name` = $name AND `Surname` = $surname";
					$aliasresult = mysqli_query($conn, $aliasquery, MYSQLI_STORE_RESULT);
					$aliasrow = $aliasresult->fetch_row();
				}
				else
					$aliasrow = $row[3];
				$sessionsfile = file_get_contents('../temp/sessions.txt');
				$arrsessions = explode(',',$sessionsfile);
				$index = array_search($aliasrow[0],$arrsessions);
				unset($arrsessions[$index]);
				$newsessions = array_values($arrsessions);
				$handle = fopen('../temp/sessions.txt', 'w');
				fwrite($handle, implode($newsessions, ','));
				fclose($handle);
				$string .= $row[0] . "|" . $row[1] . "|" . $row[2] . "|" . $row[3] . "\n";
				break;
			default:
				$string .= $row[0] . "|" . $row[1] . "|" . $row[2] . "|" . $row[3] . "\n";
				break;
		}
    }
	$result->free();
	if ($lastmessage != -1)
	{
		$handle = fopen($filename, "a");
		fwrite($handle, $string);
		fclose($handle);
	}
	else
	{
		$arrsyncfile = explode("\n", $string);
		$insertstring = implode("\n", array_slice($arrsyncfile, $lastrecordedid));
		$handle = fopen($filename, "a");
		fwrite($handle, $insertstring);
		fclose($handle);
	}
}
if ($string != '')
{
	$outstring = str_replace(array("\r\n","\n","\r"), "&", $string);
	$pos = strrpos($outstring, '&');
    if($pos !== false)
    {
        $outstring = substr_replace($outstring, '', $pos, strlen('&'));
    }
	echo $outstring;
    flush();
}
$conn->close();