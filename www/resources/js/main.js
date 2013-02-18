$( function () {
    try {
        if (!Modernizr.geolocation) {
            throw new Error('Geolocation is not supported on this browser.');
        }

        navigator.geolocation.getCurrentPosition(function (position) {
            var latitude = position.coords.latitude;
            var longitude = position.coords.longitude;

            $.ajax({
                dataType: 'json',
                type: 'get',
                url: '__main.php/api/v1/getBreathability/'+latitude+'/'+longitude,
                success: function (data) {
                    $('div.answer').html(data.html);
                },
                error: function (xhr, textStatus, errorThrown) {
                    console.log('AJAX call failed', xhr, textStatus, errorThrown);
                }
            });

        });

    } catch (e) {
        console.log(e.message);
    }
});
