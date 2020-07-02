<?php
session_start();
$arrinfo = file("../temp/groups.txt", FILE_IGNORE_NEW_LINES);
foreach($arrinfo as $pos => $instance)
{
	if ($instance == $_SESSION['alias'])
	{
		$index = (floor($pos/7)) * 7;
		$_SESSION['grouptype'] = $arrinfo[$index];
		$_SESSION['syncfile'] = $arrinfo[$index + 1];
		break;
	}
}
if (!isset($_SESSION['grouptype']) || !isset($_SESSION['syncfile']))
{
    die("Your alias was not found in the file. You have therefore not been dealt into a group.");
}
else
{
	switch ($_SESSION['accounttype'])
	{
	case 'Admin':
		header("Location: http://www.ideosign.co.za/hub_admin.html");
		break;
	
	case 'Participant':
		header("Location: http://www.ideosign.co.za/hub_participant.html");
		break;
	}

	case 'Moderator':
		header("Location: http://www.ideosign.co.za/hub_moderator.html");
		break;

}