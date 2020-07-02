function changeTime() {
    const item = document.forms['hub_form'].elements["input_timechange"];
    if (item.value == "" || item.value == null || /([0-9]|[0-9][0-9])/.test(item.value) == false)
    {
        alert("Enter a valid timelimit change (in minutes, between 0 and 99");
        item.value = "";
    }
    else
    {
        $.ajax({
            url: "http://www.ideosign.co.za/scripts/hub_messagesend.php",
            type: "POST",
            data: {
                type: "time",
                offsetchange: item.value
            },
            success: function () {
                alert("The timelimit change has successfully been registered.");
                item.value = "";
            },
            error: function (response) {
                alert("The timelimit change wasn\'t registered. Error: " + response);
            }
        });
    }
};