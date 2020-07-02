<?php
$servername = "localhost";
$username = "generic";
$password = "K9Uj2v8US4X9MwQ";
$dbname = "Gamified_Idea_Competition";
function connect()
{
	global $servername;
	global $username;
	global $password;
	global $dbname;

    $connection = new mysqli($servername,$username,$password,$dbname);	//new mysqli("localhost","generic","K9Uj2v8US4X9MwQ","Gamified_Idea_Competition");
    if ($connection->connect_error)
    {
        die("Connection failed: ".$connection->connect_error);
    }
    else
    {
        return $connection;
    }
}


