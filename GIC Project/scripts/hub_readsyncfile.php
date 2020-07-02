<?php
$alias = $_GET["alias"];
$file = "../temp/cookies/$alias.txt";
if (file_exists($file))
	$arrfile = file($file,FILE_IGNORE_NEW_LINES);
else
{
	$string = "0|0|time|END;0";
	echo $string;
	flush();
	exit;
}
$grouptype = $arrfile[2];
$syncname = $arrfile[3];
unset($arrfile);
ini_set('display_errors',1);
error_reporting(E_ALL);
$lastmessage = $_GET["lastmessage"];
$arrfile = file("../temp/$syncname.txt",FILE_IGNORE_NEW_LINES);
if (!$arrfile)
{
	exit;
}
$string = '';
for ($index = $lastmessage; $index < count($arrfile); $index++)
{
	$string .= $arrfile[$index];
	/*$contents = str_getcsv($arrfile[$index],'|');
	$string .= $contents[2] . PHP_EOL;	// type
	$string .= $contents[0] . PHP_EOL;	// ID
	switch ($contents[2])
	{
		case "pmessage":
			$string .= $contents[1]."|".$contents[3];	// MessageID + Data
			break;
		case "time":
			$string .= $contents[3];	// Data
			break;
		case "kick":
			$string .= $contents[3];	// Data
			break;
		default :
			$string .= $contents[1];	// MessageID
			break;
	}*/
	$string .= '&';
}
if ($string != '' && $string != '&')
{
	$pos = strrpos($string, '&');
    if($pos !== false)
    {
        $string = substr_replace($string, '', $pos, strlen('&'));
    }
	echo $string;
	flush();
}