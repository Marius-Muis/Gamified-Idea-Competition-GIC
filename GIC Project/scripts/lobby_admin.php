<?php
include_once 'dbconnect.php';
ini_set('display_errors',1);
error_reporting(E_ALL);
$alias = "mod1";
$arrcookie = file("../temp/cookies/$alias.txt",FILE_IGNORE_NEW_LINES);
$accounttype = $arrcookie[1];
if (count($arrcookie) > 2)
{
	$minscore = file("../temp/minscore.txt",FILE_IGNORE_NEW_LINES);
	header("Location: http://www.ideosign.co.za/scripts/eventchange_groups.php?alias=$alias");
	exit;
}
unset($arrcookie);
function groupline(&$line, $id, $arrparts, $index, $grouptype, &$linequeries)
{
    $numparts = count($arrparts);
    for ($counter = $index; $counter < $index + 5; $counter++)
    {
        if ($counter < $numparts)
        {
            $line .= $arrparts[$counter]."\n";
            $ParticipantInSessionID = "'".$id."_".$arrparts[$counter]."'";
			$alias = "'".$arrparts[$counter]."'";
            $linequeries .= "INSERT INTO $grouptype VALUES ($ParticipantInSessionID,$id,$alias);";
        }
    }
}
if(isset($_REQUEST['btnStart']))
{
    $connection = connect();
    $question = $connection->real_escape_string(trim($_REQUEST['edtQuestion']));
    $timelimit = $connection->real_escape_string($_REQUEST['edtTimeLimit']);
    $minscore = $connection->real_escape_string($_REQUEST['edtMinScore']);
    try
    {
        $date = new DateTime("now", new DateTimeZone('Africa/Johannesburg'));
    }
    catch (Exception $e)
    {
        $connection->close();
        die ("Could not retrieve server time info. ".$e->getMessage());
    }
    $datetime = $connection->real_escape_string($date->format('Y-m-d H:i:s'));
    $insertquery = "INSERT INTO Sessions (`DateTime`,`Question`,`TimeLimit`,`FinalTimeLimit`,`MinScore`) VALUES ('$datetime','$question','$timelimit','$timelimit','$minscore')";
    if (!mysqli_query($connection,$insertquery))
    {
        $connection->close();
        die ("Could not query the database and insert the values.");
    }
    $id = mysqli_insert_id($connection);
    $sessionid = $id;
    $data = file_get_contents('../temp/sessions.txt');
    $arrsessions = str_getcsv($data,",");
	$string = implode($arrsessions,',');
	$arrmods = array("mod1","mod2","mod3","mod4","mod5","mod6","mod7","mod8","mod9","mod10","mod11","mod12",);
    $arrcurrentmods = array();
    $arrcurrentparts = array();
    for ($i = 0; $i < count($arrsessions); $i++)
    {
        if (preg_match("/mod[0-9]/",$arrsessions[$i]) || preg_match("/mod[0-9][0-9]/",$arrsessions[$i]))
        {
			array_push($arrcurrentmods, $arrsessions[$i]);
        }
        else
        {
            array_push($arrcurrentparts, $arrsessions[$i]);
        }
    }
    shuffle($arrcurrentparts);
    shuffle($arrcurrentmods);
    $currentmods = $arrcurrentmods;
    $arrgrouptypes = array("anonymous_none","anonymous_feedback","pseudonyms_none","pseudonyms_feedback");
    $numgroups = ceil((count($arrcurrentparts) / 5));
    $numexperiments = ceil(($numgroups) / 4);
	$groups = array();
	bstop = false;
	bgroupsspecified = false;
	counter = 1;
	while (!bstop)
	{
		if (isset($_REQUEST[counter]))
		{
			if (!bgroupsspecified)
				bgroupsspecified = true;
			array_push($groups, $_REQUEST[counter])
		}
		else
			bstop = true;
	}
    $line = "";
    $linequeries = "";
	$arrpartsmodone = array('empty', 'empty', 'empty', 'empty', 'empty');
	$numparts = count($arrcurrentparts);
    for ($i = 0; $i < $numexperiments; $i++)
    {
        $y = 0;
        shuffle($arrgrouptypes);
        for ($x = $i * 4; $x < (($i + 1) * 4); $x++)
        {
            if ($x < $numgroups)
            {
				if (!bgroupsspecified)
					$groups[$x] = $arrgrouptypes[$y];
				if ($arrcurrentmods[$x] == "mod1")
				{
					$grouptype = $groups[$x];
					$countermodone = 0;
					for ($q = $x*5; $q < $x*5+5; $q++)
					{
						if ($q < $numparts)
						{
							$arrpartsmodone[$countermodone] = $arrcurrentparts[$q];
							$countermodone++;
						}
					}
				}
                $line .= $groups[$x]."\n".$arrcurrentmods[$x]."\n";
                groupline($line, $id, $arrcurrentparts, $x*5, $groups[$x], $linequeries);
                $y++;
            }
        }
    }
	foreach($arrcurrentmods as $mod)
	{
		$data = "'" . $timelimit . ";" . $datetime . "'";
		$linequeries .= "INSERT INTO $mod (`MessageID`,`Type`,`Data`) VALUES (0,'time',$data);";
	}
	$linequeries = rtrim($linequeries,';');
	if(!mysqli_multi_query($connection,$linequeries))
	{
		$error = $connection->error;
		$connection->close();
		error_log("Could not insert the queries into the db (lobby_admin.php, line 102): $error\n",3,'../temp/error_log.txt');
		die("Could not insert into mod tables.");
	}
	$handle = fopen("../temp/minscore.txt",'w');
	$string = $minscore . "\n" . $question;
	fwrite($handle, $string) or die("Could not write/create minscore.txt");
	fclose($handle);
	$syncfile = "mod1";
	$handle = fopen("../temp/cookies/$alias.txt","w");
	$string = $alias."\n".$accounttype."\n".$grouptype."\n".$syncfile."\n".$sessionid."\n".implode($currentmods,',')."\n".implode($groups,',');
	fwrite($handle,$string);
	fclose($handle);
	$connection->close();
	if ($grouptype == "pseudonyms_feedback" || $grouptype == "pseudonyms_none")
	{
		$index = 0;
		for ($q = 0; $q < 5; $q++)
		{
			if ($arrpartsmodone[$q] != 'empty')
			{
				$index++;
				$aliasmessage = "'" . $arrpartsmodone[$q] . "'";
				$aliasquery = "SELECT `Name`,`Surname` FROM Users WHERE `Alias` = $aliasmessage;";
			}
			else
				break;
		}
		if ($index != 0)
		{
			$conn = connect();
			$result = mysqli_multi_query($conn, rtrim($aliasquery, ';'));
			if (!$result || $result == 0)
			{
				$error = $conn->error;
				$conn->close();
				error_log("DB multi_query failed (lobby_admin.php, line 153): $error\n",3,'../temp/error_log.txt');
				die("DB multi_query failed: $error");
			}
			for ($r = 0; $r < $result; $r++)
			{
				if ($r == 0)
					$resultrow = mysqli_store_result($conn);
				else
					$resultrow = mysqli_next_result($conn);
				$row = $resultrow->fetch_row();
				$arrpartsmodone[$r] = $row[0] . "_" . $row[1];
			}
			$conn->close();
		}
	}
	$lineparts = 'part1='.$arrpartsmodone[0].'&part2='.$arrpartsmodone[1].'&part3='.$arrpartsmodone[2].'&part4='.$arrpartsmodone[3].'&part5='.$arrpartsmodone[4];
	$line = rtrim($line,"\n");
    $handle = fopen("../temp/groups.txt", "w");
    fwrite($handle, $line) or die("Could not write to temp file.");
    fclose($handle);
	header("Location: http://www.ideosign.co.za/hub_admin.html?alias=$alias&question=$question&grouptype=$grouptype&messageid=0&$lineparts");
}