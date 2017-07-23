/**
 * Created by Zbyněk Mlčák on 26.06.2017.
 */
var geocoder;
var map;
function initialize() {
    geocoder = new google.maps.Geocoder();
    var latlng = new google.maps.LatLng(50.054519, 14.492397);
    var mapOptions = {
        zoom: 17,
        center: latlng,
        styles: [{"featureType":"all","elementType":"all","stylers":[{"invert_lightness":true},{"saturation":10},{"lightness":30},{"gamma":0.5},{"hue":"#435158"}]}]
    };
    map = new google.maps.Map(document.getElementById('map'), mapOptions);
    codeAddress();
}

function codeAddress() {
    //var address;
    geocoder.geocode( { 'address': address}, function(results, status) {
        if (status == 'OK') {
            map.setCenter(results[0].geometry.location);
            var marker = new google.maps.Marker({
                map: map,
                position: results[0].geometry.location
            });
        } else {
            alert('Geocode was not successful for the following reason: ' + status);
        }
    });
}