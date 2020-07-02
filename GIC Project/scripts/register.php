<?php
include_once 'dbconnect.php';
if(isset($_POST['edtCreateSubmit']))
{
	$connection = connect();
	$query = "SELECT `LoggedIn` FROM `Users` WHERE `AccountType` = 'Admin'";
	$result = $connection->query($query);
	$row = $result->fetch_row();
	if ($row[0] == 0)
	{
		header('Location: http://www.ideosign.co.za/default.html?message=The+admin+must+be+logged+in+before+you+can+create+an+account');
		exit;
	}
	unset($result);
	unset($row);
	$name = $connection->real_escape_string(preg_replace( "#[^\w]#", "", $_POST['edtName']));
	$surname = $connection->real_escape_string(preg_replace( "#[^\w]#", "", $_POST['edtSurname']));
	$email = $connection->real_escape_string($_POST['edtEmail']);
	$alias = $connection->real_escape_string($_POST['edtAlias']);
	$password = $connection->real_escape_string($_POST['edtCreatePassword']);
	$existquery ='SELECT * FROM Users WHERE Name="'.$name.'" AND Surname="'.$surname.'" AND Email="'.$email.'"';
	$result = $connection->query($existquery);
	$count = $result->num_rows;
	if($count > 0 || $alias == 'REmptY')
		{
			$connection->close();
			header('Location: http://www.ideosign.co.za/register.html?message=This+account+already+exist+or+it+is+too+similiar+to+another+one');
			exit;
		}
	else
		{
			$aliasquery = 'SELECT * FROM Users WHERE Alias="'.$alias.'"';
			$result = $connection->query($aliasquery);
			$count = $result->num_rows;
			if($count > 0)
			{
				$connection->close();
				header('Location: http://www.ideosign.co.za/register.html?message=The+alias+you+have+entered+already+exists;+please+choose+another+one');
				exit;
			}
			else
			{
				$insertquery = 'INSERT INTO Users VALUES ("'.$alias.'","Participant","'.$name.'","'.$surname.'","'.$email.'","'.$password.'",0,0)';
				if (!$connection->query($insertquery))
				{
					$error = $connection->error;
					$connection->close();
					error_log("Failed to create an account (register.php, line 48): $error\n",3,'../temp/error_log.txt');
					header('Location: http://www.ideosign.co.za/default.html?message=Failed+to+create+an+account');
					exit;
				}
				$connection->close();
				header("Location: http://www.ideosign.co.za/default.html?registered=true");
			}
		}
}