var block_default = {
    message: '<i class="fas fa-spin fa-sync text-white"></i>',
    baseZ: 100000,
    overlayCSS: {
        backgroundColor: "#000",
        opacity: 0.5,
        cursor: "wait",
    },
    css: {
        border: 0,
        padding: 0,
        backgroundColor: "transparent",
    },
};

$(document).ready(function() {
    $("div.hidden").on("click", function() {
        $(this).removeClass("hidden");
    });
});


function settingsSet(data)
{
    $.ajax({
        url: '/api/settings/set?_token=' + $("meta[name='_token']").attr('content'),
        type: "POST",
        data: data,
        dataType: "json",
    });
}

$(function () {
    "use strict";
    var sidebar_mode = $("meta[name='sidebar_mode']").attr("content");
    if(sidebar_mode === 'full' && $(document).width() < 1500)
    {
        sidebar_mode = 'mini-sidebar';
        settingsSet({'sidebar_mode': sidebar_mode});
    } else if(sidebar_mode === 'mini-sidebar' && $(document).width() >= 1500) {
        sidebar_mode = 'full';
        settingsSet({'sidebar_mode': sidebar_mode});
    }


    $("#main-wrapper").AdminSettings({
        Theme: false, // this can be true or false ( true means dark and false means light ),
        Layout: "vertical",
        LogoBg: "skin1", // You can change the Value to be skin1/skin2/skin3/skin4/skin5/skin6
        NavbarBg: "skin1", // You can change the Value to be skin1/skin2/skin3/skin4/skin5/skin6
        SidebarType: sidebar_mode, // You can change it full / mini-sidebar / iconbar / overlay
        SidebarColor: "skin6", // You can change the Value to be skin1/skin2/skin3/skin4/skin5/skin6
        SidebarPosition: true, // it can be true / false ( true means Fixed and false means absolute )
        HeaderPosition: true, // it can be true / false ( true means Fixed and false means absolute )
        BoxedLayout: false, // it can be true / false ( true means Boxed and false means Fluid )
    });
});



function sidebar(arParams)
{
    var block_elem = $("body");
    $(block_elem).block({
        message: '<i class="fas fa-spin fa-sync text-white"></i>',
        overlayCSS: {
            backgroundColor: "#000",
            opacity: 0.5,
            cursor: "wait",
        },
        css: {
            border: 0,
            padding: 0,
            backgroundColor: "transparent",
        },
    });
    $.ajax({
        url: arParams.href,
        type: arParams.method ?? "GET",
        data: arParams.data ?? [],
        dataType: "html",
        success: function (result) {
            $("#offcanvas").html(result);
            $("#offcanvas > div").offcanvas('show');

            $(block_elem).unblock();
        },
        error: function () {
            $(block_elem).unblock();
        }
    });
}
function sidebar_close()
{
    $("#offcanvas > div").offcanvas('hide');
}

function box(arParams)
{
    box_close();
    var block_elem = $("body");
    $(block_elem).block({
        message: '<i class="fas fa-spin fa-sync text-white"></i>',
        overlayCSS: {
            backgroundColor: "#000",
            opacity: 0.5,
            cursor: "wait",
        },
        css: {
            border: 0,
            padding: 0,
            backgroundColor: "transparent",
        },
    });
    $.ajax({
        url: arParams.href,
        type: arParams.method ?? "GET",
        data: arParams.data ?? [],
        dataType: "html",
        success: function (result) {
            $("#box").html(result);
            $("#box > div").modal('show');

            $(block_elem).unblock();
        },
        error: function () {
            $(block_elem).unblock();
        }
    });
}

function box_close()
{
    $("#box > div").modal('hide');
}


function progress(obj, route)
{
    if(obj.hasClass('processing') || obj.hasClass('locked')) return false;
    obj.css("width", $(obj).outerWidth() + 'px');
    obj.css("height", $(obj).outerHeight() + 'px');
    obj.find('.progress-bar').css("width", '0%');
    obj.addClass("processing");

    $.ajax({
        type: 'POST',
        url: route,
        success: function(data) {
            progress_observer(data.uuid, obj);
        },
        error: function() {
            toastr.error("Не получилось выполнить действие", "Это провал!", {
                progressBar: true,
                "timeOut": 3000,
            });
            progress_reset(obj);
        }
    });
}

function csrf_token() {
    return $('meta[name="_token"]').attr('content');
}

function progress_observer(uuid, obj)
{
    var uuid = uuid;
    var obj = obj;
    window.interval = setInterval(() => {
        $.ajax({
            type: 'POST',
            url: "/api/ajax_progress/observe/?_token=" + csrf_token(),
            data: {
                uuid: uuid
            },
            success: function(response) {
                if(response.percent > 0)
                    obj.find('.progress-bar').css("width", response.percent + '%');
                if(response.percent > 50) {
                    obj.find('.spinner-border').addClass('white');
                }
                if(response.status == 'finished')
                {
                    clearInterval(window.interval);
                    obj.attr("name", obj.find('span.name').html());
                    obj.find('span.name').html(response.message);
                    if(obj.removeClass('processing').addClass("locked"));


                    toastr.success(response.message, "Это успех!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });


                    setTimeout(() => {
                        obj.find('span.name').html(obj.attr("name"));
                        obj.removeAttr("name");
                        progress_reset(obj);
                    }, 3000);
                }
            },
            error: function() {
                clearInterval(window.interval);
                progress_reset(obj);
            }
        });
    }, 1000);
}
function progress_reset(obj)
{
    obj.css("width", "");
    obj.css("height", "");
    obj.removeClass("processing");
    obj.removeClass("locked");
    obj.find('.spinner-border').removeClass('white');
}


function cost_normalize(sum) {
    // Разделяем число на целую и дробную части
    let [integerPart, fractionalPart = ''] = String(sum).split('.');

    // Округляем дробную часть до двух знаков
    if (fractionalPart) {
        fractionalPart = Math.round(parseFloat(`0.${fractionalPart}`) * 100) / 100;
        fractionalPart = fractionalPart.toFixed(2).split('.')[1]; // Берем только дробную часть
    }

    // Разбиваем целую часть на группы по три цифры
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

    // Объединяем результат
    return integerPart + (fractionalPart ? `.${fractionalPart}` : '');
}


function ajax(uid) {
    params = window[uid];

    if(params.confirm && !confirm(params.confirm)) return;
    if(params.pre) {
        params.data = window[params.pre](params.data);
    }
    console.log(params.data);

    $.ajax({
        url: params.url,
        type: params.method,
        data: params.data,
        dataType: params.dataType,
        success: function (response) {

            if (params.callback) {
                window[params.callback](response);
            }

            if (params.reload) {
                location.reload();
            }

            if (params.message) {
                toastr.success(params.message, "Это успех!", {
                    progressBar: true,
                    "timeOut": 3000
                });
            }
        },
        error: function error() {
            toastr.error("Не получилось выполнить действие", "Это провал!", {
                progressBar: true,
                "timeOut": 3000
            });
        }
    });
}

var toastr_bottom_center = {
    "closeButton": false,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toastr-bottom-center",
    "preventDuplicates": true,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "2000",
    "extendedTimeOut": "2000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};

function body_block() {
    $("body").block({
        message: '<i class="fas fa-spin fa-sync text-white"></i>',
        overlayCSS: {
            backgroundColor: "#000",
            opacity: 0.5,
            cursor: "wait",
        },
        css: {
            border: 0,
            padding: 0,
            backgroundColor: "transparent",
        },
    });
}

function body_unblock() {
    $("body").unblock();
}

function stripTags(html) {
    // Создаем временный элемент
    var tempDiv = document.createElement("div");
    // Устанавливаем HTML-содержимое элемента
    tempDiv.innerHTML = html;
    // Возвращаем текстовое содержимое без HTML-тегов
    return tempDiv.textContent || tempDiv.innerText || "";
}
