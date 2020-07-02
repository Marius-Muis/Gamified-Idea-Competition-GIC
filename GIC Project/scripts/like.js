document.addEventListener("click", function (e) {
    if (e.target && e.target.class == "tagged_other_message_listitems")
    {
        fmessageid = e.target.id;
        $.ajax({
            url: "http://www.ideosign.co.zalogin.php",
            type: "POST",
            data: {
                type: "like",
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