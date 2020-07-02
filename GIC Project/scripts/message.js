function sendMessage() {
    var fmessage = document.forms['hub_form'].elements["edtMessage"].value;
    document.forms['hub_form'].elements["edtMessage"].value = "";
    $.ajax({
        url: "http://www.ideosign.co.za/scripts/hub_messagesend.php",
        type: "POST",
        data: {
            type: "message",
            message: fmessage
        },
        success: function (response) {
            alert(response);
        },
        error: function (response) {
            alert(response);
        }
    });
};