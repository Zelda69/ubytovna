/**
 * Created by Zbyněk Mlčák on 17.06.2017.
 */
window.onscroll = function (e) {
    if(window.scrollY>=50) {
        document.getElementById('scrollUp').style.display = 'block';
    } else {
        document.getElementById('scrollUp').style.display = 'none';
    }
}

function scrollUp() {
    if(window.scrollY!=0) {
        setTimeout(function() {
            window.scrollTo(0,window.scrollY-50);
            scrollUp();
        }, 40);
    }
}
