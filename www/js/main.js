$(function () {
    $.nette.init();
    $('.review select').barrating({
        theme: 'fontawesome-stars',
        onSelect: function (value, text, event) {
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

    Nette.validators.AppFormsRules_validateDateRange = function (elem, arg, value) {

        var inputDate = moment(value, 'DD.MM.YYYY');

        if (arg[0]) {//minDate
            var minDate = moment(arg[0], 'DD.MM.YYYY');
        }
        else
            var minDate = moment();

        if (arg[1]) {//maxDate
            var maxDate = moment(arg[1], 'DD.MM.YYYY');
        }
        else
            var maxDate = moment();

        if (arg[2] == "param1") {//pokud je rovno, přičti jeden rok
            var minDate = moment(minDate).add(1, 'years'); //minDate + 1 rok
        }

        if (inputDate.isBefore(minDate) || inputDate.isAfter(maxDate)) {
            return false;
        }
        return true;
    };

    if (typeof filter !== 'undefined') {
        if (filter) {
            $('#room-filter-table').show();
        }
    }

    $("#room-filter-fieldset").on("click", function () {
        $('#room-filter-table').toggle();
    });

    $("#filter-zrus").click(function () {
        $(location).attr('href', 'zrusFiltr!');
    });

});