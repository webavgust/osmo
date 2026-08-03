
window.notify_out = function notify_out(notify)
{
    $.ajax({
        url: "/notify/toast/" + notify.id,
        type: "GET",
        dataType: "html",
        timeout: 999,
        success: function (html) {
            $("#toasts").prepend(html);
        },
    });
}




window.notify_delete = function notify_delete(obj) {
    count = $(".message-center").data('count');
    if(count > 1)
    {
        count--
        $(".message-center").data('count', count)
        $("#notifies .count").html(count);

        $.ajax({
            url: "/notify/delete/"  + obj.attr("id"),
            type: "GET",
            dataType: "json",
            success: function (result) {
                obj.remove();
            },
        });
    } else {
        notify_truncate();
    }
}


window.notify_truncate = function notify_truncate() {
    $.ajax({
        url: "/notify/clear",
        type: "GET",
        dataType: "json",
        success: function (result) {
            $("#notifies").addClass('d-none');
        },
    });
}

$(document).ready(function() {

    $(".navbar-nav li#notifies a").on("click", function() {
        if(!$(this).hasClass('show')) {
            $.ajax({
                url: "/notify/header",
                type: "GET",
                dataType: "html",
                success: function (html) {
                    $("#notifies .loader").hide();
                    $("#notifies .notices_shell").html(html).show();

                    $(".message-center .delete").on("click", function(e) {
                        notify_delete($(this).parents('.message-item'));
                        e.stopPropagation();
                    })
                },
            });
        } else {
            $("#notifies .loader").show();
            $("#notifies .notices_shell").html('').hide();
        }
    });
});
