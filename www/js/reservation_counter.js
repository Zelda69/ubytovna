/**
 * Created by Zbyněk Mlčák on 12.07.2017.
 */
$(document).ready(function () {
    // Set the date we're counting down to
    var countDownDate = new Date(reservation_expired).getTime();

// Update the count down every 1 second
    var x = setInterval(function () {

        // Get todays date and time
        var now = new Date().getTime();

        // Find the distance between now an the count down date
        var distance = countDownDate - now;

        // Time calculations for days, hours, minutes and seconds
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Display the result in the element with id="demo"
        if (document.getElementById("reservation_expired") != null) {
            document.getElementById("reservation_expired").innerHTML = " " + minutes + " minut a " + seconds + " sekund";
            //days + "d " + hours + "h " +

            // If the count down is finished, write some text
            if (distance < 0) {
                clearInterval(x);
                document.getElementById("reservation_expired").innerHTML = " 0 minut";
            }
        }
    }, 1000);
});