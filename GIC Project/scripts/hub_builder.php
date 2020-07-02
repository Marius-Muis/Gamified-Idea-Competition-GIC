<?php
session_start([
	'read_and_close' => true,
]);
$alias = $_SESSION['alias'];
$accounttype = $_SESSION['accounttype'];
$grouptype = $_SESSION['grouptype'];
session_write_close();
?>
<html>
    <head>
        <title>Communication Hub</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="/styling/styling.css" />
		<script src="http://code.jquery.com/jquery-latest.min.js">    //  src="http://code.jquery.com/jquery-latest.min.js"
		<?php
		switch ($accounttype)
            {
                case "Admin":
                    
					echo file_get_contents('tag.js');
					echo "\n";
					echo file_get_contents('time.js');
                    break;
                
                case "Moderator":
                    echo file_get_contents('tag.js');
                    break;
                
                
                case "Participant":
                    echo file_get_contents('message.js');
                    if ($grouptype == "anonymous_feedback" || $grouptype == "pseudonyms_feedback")
					{
						echo "\n";
                        echo file_get_contents('like.js');
					}
                    break;
            }
		?>
		</script>
    </head>
    <body>
        <form action="/scripts/hub_messagesend.php" name="hub_form">
            <fieldset>
                <header class="header_footer">
                    <label id="timecounter_label">Time left:</label>
                    <input id="input_timer" type="text" name="timecounter" value="">
                    <?php
                    if ($accounttype == "Admin")
                    {
                        echo '<label id="timechange_label">Change Time Limit:</label>';
                        echo '<input id="input_timechange" type="text" name="timechange" value="">';
                        echo '<input type="button" value="Change..." name="btnChange" onClick="changeTime()"/>';
                    }
                    ?>
                    <input id="alias" type="text" name="aliasholder" disabled="" value="<?php echo $alias;?>">
                </header>
                <section id="sec_messages">
                    <ul id="messages_list">
                    </ul>
                </section>
                <?php
                    if ($accounttype == "Participant"){
                        echo '<footer class="header_footer">';
                        echo '  <input id="messagebox" type="text" name="edtMessage" value="type here..." required="" />';
                        echo '  <input type="button" value="SEND" name="btnSend" onClick="sendMessage()"/>';
                        echo '</footer>';
                    }
                ?>
            </fieldset>
        </form>
    </body>
	<script >    //  src="http://code.jquery.com/jquery-latest.min.js"
            var timeLimit = 0;
            var timeThenPlusTimeLimit = 0;
            var currentTime = 0;
            var flagStart = false;
			
			function redirect(){
                sync.close();
                clearInterval(x);
                <?php
				if ($accounttype == 'Admin')
                {
                    echo 'window.location.href("cleanup.php");';
                }
				else
				{
                    echo 'window.loaction.href("default.html");';
                }
				?>
            }
            
            <?php
            if (preg_match("/mod[1-9]/", $alias) || preg_match("/mod[1-9][1-9]/", $alias))
            {
				session_commit();
                echo "var sync = new EventSource('hub_updatesyncfile.php');";
            }
            else
            {
				session_commit();
                echo "var sync = new EventSource('hub_readsyncfile.php');";
            }
            ?>
			//sync.onopen = function (e){
				//window.alert("Started. " + sync.readyState);
			//};

			window.onload = function(){
			sync.addEventListener("message",function(event){
                var line = event.data;
                var indexOne = line.indexOf('|');
                var messageID = line.substring(0, indexOne);
                var indexTwo = line.indexOf(':');
                var alias = line.substring(indexOne + 1, indexTwo);
                var message = line.substring(indexTwo + 1, line.length);
                
                var p = document.forms['hub_form'].elements["messages_list"];
                var newElement = document.createElement('li');
                newElement.setAttribute('id', messageID);
                if (alias == document.getElementById('alias').value){
                    newElement.setAttribute('class', "my_message_listitems");
                }else{
                    newElement.setAttribute('class', "other_message_listitems");
                }
                newElement.innerHTML = message;
                p.appendChild(newElement);
            },false);
            
            sync.addEventListener("tag",function(event){
                var messageID = event.data;
                const li = document.forms['hub_form'].elements[messageID];
                var message = li.innerHTML;
                li.innerHTML = message + "&nbsp&nbsp 0 Likes";
                li.style.backgroundColor = "#76d176";
                if (li.class == "my_message_listitems"){
                    li.class = "tagged_my_message_listitems";
                }else{
                    li.class = "tagged_other_message_listitems";
                }
            },false);
            
            sync.addEventListener("like",function(event){
                var messageID = event.data;
                var message = document.forms['hub_form'].elements[messageID].innerHTML;
                var index = message.Indexof("Likes");
                var currentLikes = parseInt(message.charAt(index - 2), 10);
                var newLikes = currentLikes + 1;
                var newmessage = message.substring(0, index - 2) + newLikes + " Likes";
                document.forms['hub_form'].elements[messageID].innerHTML = newmessage;
            },false);
            
            sync.addEventListener("time",function(event){
                var arrDate = new Array();
                if (event.id == 1){
                    flagStart = true;
                }
                var line = event.data;
                var indexSemiColon = line.indexOf(';');
                var offsetChange = line.substring(0, indexSemiColon);    // measured in minutes/ string containing "END"
                var timeThen = line.substring(indexSemiColon + 1, line.length);
                if (offsetChange != "END"){
                    var timeThenDate = new Date(timeThen);
                    timeLimit += offsetChange * 60 * 1000;
                    currentTime = timeThenDate.getTime();
                    if (event.id == 1){
                        timeThenPlusTimeLimit = currentTime + timeLimit;
                    }else{
                        timeThenPlusTimeLimit += offsetChange * 60 * 1000;
                    }
					document.forms['hub_form'].elements['input_timechange'].value = timeLimit;
                }
                else{
                    redirect();
                }
            },false);
			}
            

			sync.addEventListener("error",function(event){
				window.alert(event.data);
			},false);
            
            var x = setInterval(function(){
                currentTime += 1000;
                var distance = timeThenPlusTimeLimit - currentTime;
                var instanceTime = new Date(distance);
                var minutes = instancetime.getMinutes();
                var seconds = instancetime.getSeconds();
                document.forms['hub_form'].elements["input_timer"].value = minutes + ":" + seconds;
                if (distance < 0 && flagStart == true)
                    redirect();
            },1000);
        </script>
</html>