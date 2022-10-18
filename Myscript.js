console.log('файл js подключен');
async function time_btn(){
    //ВРЕМЯ
    let gettime = await fetch("./gettime.php",{
        method:'GET'
    })
        .then((data) =>{
            return data})
        .then((resp) =>
        {return resp.text()})
        .then(resBody => {
            console.log(resBody)
            return resBody;
        });
    $('#timetext').attr('value', gettime);
    $('#timetext').attr('style', 'visibility : visible; border:none; text-align: center');

}

setInterval( async ()=>{
    time_btn()
}, 1000);

document.addEventListener("DOMContentLoaded", time_btn());


function chatsize(){
    var new_height;
    if(window.innerHeight >= 1080){
        new_height = window.innerHeight * 0.8;
    }else if(window.innerHeight >= 720 && window.innerHeight < 1080){
        new_height = window.innerHeight * 0.7;
    }else{
        new_height = window.innerHeight * 0.4;
    }

    const $box = document.querySelector('#chat_result');
    $box.style.height =  new_height + "px";
}

function filesize(){
    var new_height;
    if(window.innerHeight >= 1080){
        new_height = window.innerHeight * 0.8;
    }else if(window.innerHeight >= 720 && window.innerHeight < 1080){
        new_height = window.innerHeight * 0.7;
    }else{
        new_height = window.innerHeight * 0.4;
    }

    const $box = document.querySelector('#file_result');
    $box.style.height =  new_height + "px";
}


function sizeheader(){
    var new_height = window.innerHeight * 0.1;
    const $box = document.querySelector('#size_header');
    $box.style.height =  new_height + "px";
}

function addmsg(type, msg){
    /* Simple helper to add a div.
    type is the name of a CSS class (old/new/error).
    msg is the contents of the div */
    $("#chat_result").append(
        "<div class='msg "+ type +"'>"+ msg +"</div>"
    );
}

function waitForMsg(){
    /* This requests the url "chatcheck.php"
    When it complete (or errors)*/
    $.ajax({
        type: "GET",
        url: "chatcheck.php",

        async: true, /* If set to non-async, browser shows page as "Loading.."*/
        cache: false,
        timeout:50000, /* Timeout in ms */

        success: function(data){ /* called when request to barge.php completes */
            addmsg("new", data); /* Add response to a .msg div (with the "new" class)*/
            setTimeout(
                waitForMsg, /* Request next message */
                1000 /* ..after 1 seconds */
            );
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){
            addmsg("error", textStatus + " (" + errorThrown + ")");
            setTimeout(
                waitForMsg, /* Try again after.. */4000); /* milliseconds (4seconds) */
        }
    });
};

$(document).ready(function(){
    waitForMsg(); /* Start the inital request */
});
