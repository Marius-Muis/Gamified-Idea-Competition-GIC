<?php
include_once 'dbconnect.php';
ini_set('display_errors',1);
error_reporting(E_ALL);
function retrieveHighscores(&$conn)
{
	$arrParticipants = array();
	$arrPoints = array();
	$index = 0;
	$query = "SELECT Alias,GamePoints FROM Users WHERE (AccountType='Participant') AND (GamePoints>0) ORDER BY GamePoints DESC LIMIT 10";
	if(!$result = mysqli_query($conn,$query,MYSQLI_STORE_RESULT))
	{
		$error = $conn->error;
		error_log("Could not retrieve high score info (login.php, line 14): $error\n",3,"../temp/error_log.txt");
	}
	else
	{
		while ($row = $result->fetch_row())
		{
			$arrParticipants[$index] = $row[0];
			$arrPoints[$index] = $row[1];
			$index++;
		}
		$result->free();
	}
	$stop = false;
	while (!$stop)
	{
		if ($index > 9)
			$stop = true;
		else
		{
			$arrParticipants[$index] = "REmptY";
			$arrPoints[$index] = 0;
			$index++;
		}
	}
	$participantsstring = "part1=$arrParticipants[0]&part2=$arrParticipants[1]&part3=$arrParticipants[2]&part4=$arrParticipants[3]&part5=$arrParticipants[4]&part6=$arrParticipants[5]&part7=$arrParticipants[6]&part8=$arrParticipants[7]&part9=$arrParticipants[8]&part10=$arrParticipants[9]";
	$pointsstring = "point1=$arrPoints[0]&point2=$arrPoints[1]&point3=$arrPoints[2]&point4=$arrPoints[3]&point5=$arrPoints[4]&point6=$arrPoints[5]&point7=$arrPoints[6]&point8=$arrPoints[7]&point9=$arrPoints[8]&point10=$arrPoints[9]";
	return $participantsstring."&".$pointsstring;
}
if(isset($_POST['btnLoginSubmit']))
{
	$alias = filter_input(INPUT_POST, "edtAlias", FILTER_SANITIZE_STRING);
    $pass = filter_input(INPUT_POST, "edtMainPassword", FILTER_SANITIZE_STRING);
	$file = '../temp/sessions.txt';
	$data = file_get_contents($file);
	if ($alias != "mod1")
	{
		if ($data == NULL || $data == "")
		{
			header('Location: http://www.ideosign.co.za/default.html?message=The+main+admin+hasn\'t+logged+in+yet,+wait+for+the+admin+to+log+in+before+attempting+to+log+in+again');
			exit;
		}
	}
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = connect();
	$alias = $connection->real_escape_string($alias);
    $pass = $connection->real_escape_string($pass);
    $searchquery = 'SELECT * FROM Users WHERE Alias="'.$alias.'" AND Password="'.$pass.'"';
    $result = $connection->query($searchquery);
	$count = mysqli_num_rows($result);
    if ($count == 0)
    {
        $connection->close();
		header('Location: http://www.ideosign.co.za/default.html?message=Credentials+incorrect,+create+an+account+if+you+don\'t+have+any');
		exit;
	}
    else
    {
        $row = $result->fetch_assoc();
        if ($row['LoggedIn'] == 1)
		{
			$arrcookie = file("../temp/cookies/$alias.txt",FILE_IGNORE_NEW_LINES);
			if(isset($arrcookie[5]) && $row['AccountType'] == 'Participant' || isset($arrcookie[3]) && $row['AccountType'] == 'Moderator')
			{
				$arrfile = explode(',', $data);
				$index = array_search($alias,$arrfile);
				if ($index === FALSE)
				{
					header('Location: http://www.ideosign.co.za/default.html?message=You+were+kicked+from+the+chat+and+cannot+rejoin');
					exit;
				}
			}
			switch ($row['AccountType'])
			{
				case 'Participant':
					$highscorestring = retrieveHighscores($connection);
					$connection->close();
					header("Location: http://ideosign.co.za/lobby_participant.html?alias=$alias&$highscorestring");
					break;
				case 'Moderator':
					$connection->close();
					header("Location: http://ideosign.co.za/lobby_secondary_moderator.html?alias=$alias");
					break;
				case 'Admin':
					$connection->close();
					if (count(file("../temp/cookies/$alias.txt",FILE_IGNORE_NEW_LINES)) > 2)
					{
						header("Location: http://www.ideosign.co.za/scripts/lobby_admin.php");
						exit;
					}
					else
					{
						header("Location: http://ideosign.co.za/lobby_main_moderator.html?alias=$alias");
						exit;
					}
					break;
			}
		}
        else
        {
            $updatequery = 'UPDATE Users SET LoggedIn=1 WHERE Alias="'.$alias.'"';
            if (!$connection->query($updatequery))
            {
				$error = $connection->error;
                $connection->close();
				error_log("Failed to set logged in status (login.php, line 97): $error\n.",3,'../temp/error_log.txt');
				header('Location: http://www.ideosign.co.za/default.html?message=Could+not+log+in');
				exit;
			}
			$string = $alias."\n".$row['AccountType'];
            switch ($row['AccountType'])
            {
				case "Admin":
					$handle = fopen("../temp/groups.txt", "w");
					fclose($handle);
					$handle = fopen($file, 'w');
                    fwrite($handle, $alias);
                    fclose($handle);
					if (file_exists("../temp/cookies/$alias.txt") === FALSE)
					{
						$handle = fopen("../temp/cookies/$alias.txt", 'w');
						fwrite($handle, $string);
						fclose($handle);
					}
					$connection->close();
                    header("Location: http://ideosign.co.za/lobby_main_moderator.html?alias=$alias");
                    break;
                case "Moderator":
					if (file_exists("../temp/cookies/$alias.txt") === FALSE)
					{
						$handle = fopen("../temp/cookies/$alias.txt", 'w');
						fwrite($handle, $string);
						fclose($handle);
					}
					$connection->close();
                    header("Location: http://ideosign.co.za/lobby_secondary_moderator.html?alias=$alias");
                    break;
                case "Participant":
					$string .= "\n".$row['Name']."\n".$row['Surname'];
					$handle = fopen("../temp/cookies/$alias.txt", 'w');
					fwrite($handle, $string);
					fclose($handle);
					$highscorestring = retrieveHighscores($connection);
					$connection->close();
					header("Location: http://ideosign.co.za/lobby_participant.html?alias=$alias&$highscorestring");
                    break;
            }
        }
    }
}