document.addEventListener("click", function (e) {
    if (e.target && e.target.class == "other_message_listitems")
    {
        fmessageid = e.target.id;
        $.ajax({
            url: "http://www.ideosign.co.za/scripts/hub_messagesend.php",
            type: "POST",
            data: {
                type: "tag",
                messageid: fmessageid
            },
            success: function (response) {
                alert(response);
            },
            error: function (response) {
                alert(response);
            }
        });
    }
});