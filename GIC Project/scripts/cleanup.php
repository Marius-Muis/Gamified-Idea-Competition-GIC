<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
include_once 'dbconnect.php';
$arrfile = file("../temp/cookies/mod1.txt",FILE_IGNORE_NEW_LINES);
$sessionid = $arrfile[4];
$arrcurrentmods = array();
$arrgroups = array();
if (!strpos($arrfile[5], ','))
{
	array_push($arrcurrentmods, $arrfile[5]);
}
else
{
	$arrcurrentmods = explode(',', $arrfile[5]);
}
if (!strpos($arrfile[6], ','))
{
	array_push($arrgroups, $arrfile[6]);
}
else
{
	$arrgroups = explode(',', $arrfile[6]);
}
unset($arrfile);
file_put_contents("../temp/sessions.txt", "");
$conn = connect();
$files = glob('../temp/cookies/*');
foreach($files as $file)
{
  if(is_file($file))
    unlink($file);
}
$query = "UPDATE `Users` SET `LoggedIn` = 0 WHERE `LoggedIn` = 1";
if (!$conn->query($query))
{
	$error = $conn->error;
    $conn->close();
	error_log("Failed to reset logged in statuses (cleanup.php, line 39): $error\n",3,"../temp/error_log.txt");
    header("Location: http://www.ideosign.co.za?message=There+was+an+error+during+the+capturing+phase;+please+contact+me");
}
file_put_contents("../temp/groups.txt", "");
file_put_contents("../temp/minscore.txt","");
foreach ($arrcurrentmods as $pos => $mod)
{
    $tablename = $arrgroups[$pos];
    $tablenameideas = "ideas_".$tablename;
    $flagtimeset = false;
	$timechange = 0;
    $arrmessages = array();
    $arrpoints = array();
    $query = "SELECT * FROM `$mod`";
        if ($result = mysqli_query($conn, $query, MYSQLI_STORE_RESULT))
        {
            while ($row = $result->fetch_row())
            {
                $type = $row[2];
                switch ($type)
                {
                    case "pmessage":
                        $arrmessages[$row[1]] = $row[3];
                        $arrpoints[$row[1]] = 0;
                        break;
                    case "tag":
						$arrpoints[$row[1]] = 2;
                        break;
                    case "like":
						$arrpoints[$row[1]] += 1;
                        break;
                    case "time":
						if ($mod == 'mod1')
						{
							if (!$flagtimeset)
							{
								$flagtimeset = true;
								$timelimit = intval(substr($row[3], 0, strpos($row[3], ';')));
								$timesetat = new DateTime(substr($row[3], strpos($row[3], ';') + 1));
								$finaltimelimit = $timelimit;
							}
							else
							{
								$rowoffsetchange = substr($row[3], 0, strpos($row[3], ';'));
								if (strtolower($rowoffsetchange) == 'end')
								{
									$timeend = new DateTime(substr($row[3], strpos($row[3], ';') + 1));
									$interval = date_diff($timesetat, $timeend);
									$finaltimelimit = $interval->format("%I");
								}
								else
								{
									$timechange += intval($rowoffsetchange);
									$finaltimelimit = $timelimit + $timechange;
									if ($finaltimelimit < 0)
									{
										$timeend = new DateTime(substr($row[3], strpos($row[3], ';') + 1));
										$interval = date_diff($timesetat, $timeend);
										$finaltimelimit = $interval->format('%I');
									}
								}
							}
						}
                        break;
                }
            }
            mysqli_free_result($result);
            $query = "";
            foreach ($arrmessages as $index => $entry)
            {
                if ($arrpoints[$index] != 0)
                {
                    $aliasmessage = substr($arrmessages[$index], 0, strpos($arrmessages[$index], ':'));
                    $participantinsessionid = $conn->real_escape_string($sessionid."_".$aliasmessage);
					$participantinsessionid = "'".$participantinsessionid."'";
                    $searchquery = "SELECT * FROM $tablename WHERE `ParticipantInSessionID` = $participantinsessionid";
                    if (!$result = mysqli_query($conn, $searchquery, MYSQLI_STORE_RESULT))
                    {
						$error = $conn->error;
                        $conn->close();
						error_log("There was a failure trying to find the ParticipantInSessionID field (cleanup.php, line 119): $error\n",3,"../temp/error_log.txt");
                        header("Location: http://www.ideosign.co.za?message=There+was+an+error+during+the+capturing+phase;+please+contact+me");
                    }
                    if (mysqli_num_rows($result) == 0)
                    {
                        $query .= 'INSERT INTO `'.$tablename.'` VALUES ("'.$participantinsessionid.'",'.$sessionid.',"'.$aliasmessage.'");';
                    }
					$result->free();
                    $message = $conn->real_escape_string(substr($arrmessages[$index], strpos($arrmessages[$index], ':') + 1));
					if ($tablename == 'pseudonyms_feedback' || $tablename == 'anonymous_feedback')
					{
						$points = $conn->real_escape_string($arrpoints[$index]);
						$query .= 'INSERT INTO `'.$tablenameideas.'` (`Idea`,`ParticipantInSessionID`,`Points`) VALUES ("'.$message.'","'.$participantinsessionid.'",'.$points.');';
						$query .= 'UPDATE `Users` SET `GamePoints` = GamePoints + '.$points.' WHERE `Alias` = "'.$aliasmessage.'";';
					}
					else
						$query .= 'INSERT INTO `'.$tablenameideas.'` (`Idea`,`ParticipantInSessionID`) VALUES ("'.$message.'","'.$participantinsessionid.'");';
                }
            }
			if ($mod == 'mod1')
				$query .= "UPDATE Sessions SET `FinalTimeLimit` = $finaltimelimit WHERE `SessionID` = $sessionid;";
			$query = rtrim($query, ";");
			if ($query != "" || $query == null)
			{
				if (!mysqli_multi_query($conn, $query))
				{
					$error = $conn->error;
					$conn->close();
					error_log("There was an error during the cleanup operation (cleanup.php, line 147): $error\n",3,"../temp/error_log.txt");
					header("Location: http://www.ideosign.co.za?message=There+was+an+error+during+the+capturing+phase;+please+contact+me");
				}
				else
					do{} while(mysqli_more_results($conn) && mysqli_next_result($conn));
			}
        }
		else
		{
			$error = $conn->error;
			$conn->close();
			error_log("Could not query the temp table ($mod) (cleanup.php, line 158): $error",3,"../temp/error_log.txt");
			header("Location: http://www.ideosign.co.za?message=There+was+an+error+during+the+capturing+phase;+please+contact+me");
		}
	$handle = fopen("../temp/$mod.txt", 'w');
	fclose($handle);
    $query = "TRUNCATE TABLE $mod";
    if (!mysqli_query($conn, $query))
	{
		$error = $conn->error;
		$conn->close();
		error_log("Could not clear the $mod table (cleanup.php, line 168): $error\n",3,"../temp/error_log.txt");
		header("Location: http://www.ideosign.co.za?message=There+was+an+error+during+the+capturing+phase;+please+contact+me");
	}
}
$conn->close();
header("Location: http://www.ideosign.co.za/default.html?message=Session+successfully+ended;+data+captured!");