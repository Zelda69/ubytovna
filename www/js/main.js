$(function () {
    $.nette.init();
    $('.review select').barrating({
        theme: 'fontawesome-stars',
        onSelect: function(value, text, event) {
            if (typeof(event) !== 'undefined') {
                // rating was selected by a user
                console.log(event);
                console.log(event.target.parentElement.previousSibling);
                $(event.target.parentElement.previousSibling).barrating({readonly: true});

                /*
                 $.ajax({
                 url: "/get_votes.php",
                 type: "GET",
                 data: {rate: vote},
                 });
                 */
            } else {
                // rating was selected programmatically
                // by calling `set` method
            }
        }
    });
    $('.review-done select').barrating({
        theme: 'fontawesome-stars',
        readonly: true
    });

    Nette.validators.AppFormsRules_validateDateRange = function(elem, arg, value) {

        var inputDate = moment(value, 'DD.MM.YYYY');

        if(arg[0]){//minDate
            var minDate = moment(arg[0], 'DD.MM.YYYY');
        }
        else
            var minDate = moment();

        if(arg[1]){//maxDate
            var maxDate = moment(arg[1], 'DD.MM.YYYY');
        }
        else
            var maxDate = moment();

        if(arg[2] == "param1"){//pokud je rovno, přičti jeden rok
            var minDate = moment(minDate).add(1, 'years'); //minDate + 1 rok
        }

        if (inputDate.isBefore(minDate) || inputDate.isAfter(maxDate)) {
            return false;
        }
        return true;
    };

    if(filter) {
        $('#room-filter-table').show();
    }

    $("#room-filter-fieldset").on("click", function(){
        $('#room-filter-table').toggle();
    });

    $("#filter-zrus").click(function () {
        $(location).attr('href','zrusFiltr!');
    });

});

/*

if(window.location.href == 'https://www.stargate-game.cz/vesmir.php?page=13') {
    document.getElementsByTagName('fieldset')[1].children[0].innerHTML += '<input type="button" onclick="op()" value="OP" id="op-but"><span id="prog"></span>';
    var prog = 0;
}

function op() {
    prog = 0;
    document.getElementById('op-but').type = 'hidden';
    document.getElementById('prog').innerHTML = 'Progress: 0 %';

    for(var sid = 1; sid <= 200; sid++) {
        var img = new Image();
        img.name = sid;
        img.onload = function() { load(this); }
        img.src = '/vesmir/mapa/sektor.php?sektor=' + sid + '&amp;filtr=0&amp;id_pl=';
    }
}

function load(img) {
    var sid = img.name;
    var can = document.createElement('canvas');
    can.hidden = true;
    document.getElementById('obsah').appendChild(can);
    var ctx = can.getContext("2d");

    can.width = img.naturalWidth;
    can.height = img.naturalHeight;
    ctx.drawImage(img, 0, 0, img.naturalWidth, img.naturalHeight, 0, 0, can.width, can.height);

    var imgdata = ctx.getImageData(0,0, can.width, can.height);
    var count = 0;

    for(var i = 0; i < imgdata.width * imgdata.height * 4; i+=4) {
        if((imgdata.data[i] != 0 || imgdata.data[i+1] != 0 || imgdata.data[i+2] != 0) && (imgdata.data[i] != 255 || imgdata.data[i+1] != 255 || imgdata.data[i+2] != 255)) {
            if(0 == imgdata.data[(i-4*1)] && 0 == imgdata.data[(i-4*1)+1] && 0 == imgdata.data[(i-4*1)+2] &&
                imgdata.data[i] == imgdata.data[(i+4*1)] && imgdata.data[i+1] == imgdata.data[(i+4*1)+1] && imgdata.data[i+2] == imgdata.data[(i+4*1)+2] &&
                imgdata.data[i] == imgdata.data[(i+4*2)] && imgdata.data[i+1] == imgdata.data[(i+4*2)+1] && imgdata.data[i+2] == imgdata.data[(i+4*2)+2] &&
                imgdata.data[i] == imgdata.data[(i+4*3)] && imgdata.data[i+1] == imgdata.data[(i+4*3)+1] && imgdata.data[i+2] == imgdata.data[(i+4*3)+2] &&
                imgdata.data[i] == imgdata.data[(i+4*4)] && imgdata.data[i+1] == imgdata.data[(i+4*4)+1] && imgdata.data[i+2] == imgdata.data[(i+4*4)+2] &&
                0 == imgdata.data[(i+4*5)] && 0 == imgdata.data[(i+4*5)+1] && 0 == imgdata.data[(i+4*5)+2]) {
                count++;
            }
        }
    }

    if(count >= 1) {
        alert('OP: ' + sid);
    }

    prog++;

    if(prog == 200) {
        document.getElementById('op-but').type = 'button';
    }

    document.getElementById('prog').innerHTML = 'Progress: ' + (prog / 2) + ' %';
}*/
