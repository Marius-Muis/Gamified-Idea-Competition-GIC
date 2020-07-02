<?php
include_once 'dbconnect.php';
header("Header-Type: Access-Control-Allow-Origin: *");
$alias = $_GET['alias'];
$file = "../temp/cookies/$alias.txt";
if (file_exists($file))
	$arrcookie = file($file,FILE_IGNORE_NEW_LINES);
else
{
	echo "http://www.ideosign.co.za?message=Session+canceled+by+admin";
	exit;
}
if (!isset($arrcookie[1]))
{
	echo "http://www.ideosign.co.za?message=Session+canceled+by+admin";
	exit;
}
$accounttype = $arrcookie[1];
$data = file_get_contents("../temp/groups.txt");
if (!empty($data))
{
	if ($data == "redirect")
	{
		echo 'redirect';
	}
	else
	{
		unset($data);
		$arrinfo = file("../temp/groups.txt", FILE_IGNORE_NEW_LINES);
		foreach($arrinfo as $pos => $instance)
		{
			if (strtolower($instance) == strtolower($alias))
			{
				$index = (floor($pos/7)) * 7;
				$grouptype = $arrinfo[$index];
				$syncfile = $arrinfo[$index + 1];
				if ($accounttype == 'Moderator' || $accounttype == "Admin")
				{
					$lineparts = '';
					if ($grouptype == "pseudonyms_feedback" || $grouptype == "pseudonyms_none")
					{
						$arrparts = array('empty', 'empty', 'empty', 'empty', 'empty');
						$index = 0;
						for ($q = $pos + 1; $q < $pos + 6; $q++)
						{
							if (isset($arrinfo[$q]))
							{
								$index++;
								$aliasmessage = "'" . $arrinfo[$q] . "'";
								$aliasquery = "SELECT `Name`, `Surname` FROM Users WHERE `Alias` = $aliasmessage;";
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
								$arrparts[$r] = $row[0] . "_" . $row[1];
							}
							$conn->close();
						}
					}
					else
					{
						$counterarr = 0;
						for ($q = $pos + 1; $q < $pos + 6; $q++)
						{
							if (isset($arrinfo[$q]))
								$arrparts[$counterarr] = $arrinfo[$q];
							else
								$arrparts[$counterarr] = "empty";
							$counterarr++;
						}
					}
					$lineparts = 'part1='.$arrparts[0].'&part2='.$arrparts[1].'&part3='.$arrparts[2].'&part4='.$arrparts[3].'&part5='.$arrparts[4];
				}
				break;
			}
		}
		if (!isset($grouptype) || !isset($syncfile))
		{
			if ($accounttype == 'Participant')
				echo "http://www.ideosign.co.za?message=Session+has+started;+you+were+not+dealt+into+a+group";
			else
				echo "http://www.ideosign.co.za?message=There+are+already+enough+moderators+for+all+participants";
		}
		else
		{
			$minscore = file("../temp/minscore.txt",FILE_IGNORE_NEW_LINES);
			switch ($accounttype)
			{
				case 'Moderator':
					if (count(file("../temp/$alias.txt")) > 0)
						$messageidsend = -1;
					else
						$messageidsend = 0;
					if (count($arrcookie) < 3)
					{
						$handle = fopen("../temp/cookies/$alias.txt","w");
						$string = $alias."\n".$accounttype."\n".$grouptype."\n".$syncfile;
						fwrite($handle,$string);
						fclose($handle);
						echo "http://www.ideosign.co.za/hub_moderator.html?alias=$alias&question=$minscore[1]&grouptype=$grouptype&messageid=$messageidsend&$lineparts";
					}
					else
						echo "http://www.ideosign.co.za/hub_moderator.html?alias=$alias&question=$minscore[1]&grouptype=$arrcookie[2]&messageid=$messageidsend&$lineparts";
					break;
		
				case 'Participant':
					if (count($arrcookie) < 5)
					{
						$handle = fopen("../temp/cookies/$alias.txt","w");
						$string = $alias."\n".$accounttype."\n".$grouptype."\n".$syncfile."\n".$arrcookie[2]."\n".$arrcookie[3];
						fwrite($handle,$string);
						fclose($handle);
						$names = $arrcookie[2]."_".$arrcookie[3];
						echo "http://www.ideosign.co.za/hub_participant.html?alias=$alias&grouptype=$grouptype&minscore=$minscore[0]&question=$minscore[1]&names=$names";
					}
					else
					{
						$names = $arrcookie[4]."_".$arrcookie[5];
						echo "http://www.ideosign.co.za/hub_participant.html?alias=$alias&grouptype=$arrcookie[2]&minscore=$minscore[0]&question=$minscore[1]&names=$names";
					}
					break;
				case 'Admin':
					header("Location: http://www.ideosign.co.za/hub_admin.html?alias=$alias&question=$minscore[1]&grouptype=$grouptype&messageid=-1&$lineparts");
					break;
			}
		}
	}
	flush();
}