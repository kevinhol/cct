$(function () {

    $('#login-form-link').click(function (e) {
        $("#login-form").delay(100).fadeIn(100);
        $("#register-form").fadeOut(100);
        $('#register-form-link').removeClass('active');
        $(this).addClass('active');
        e.preventDefault();
    });
    $('#register-form-link').click(function (e) {
        $("#register-form").delay(100).fadeIn(100);
        $("#login-form").fadeOut(100);
        $('#login-form-link').removeClass('active');
        $(this).addClass('active');
        e.preventDefault();
    });


    $('#sendSMS').change(
            function () {
                if ($(this.checked)) {
                    $('#verifyChoice').val("sms");
                    $('#phone').fadeIn(100);
                }
            }
    );
    $('#sendEmail').change(
            function () {
                if ($(this.checked)) {
                    $('#phone').fadeOut(100);
                    $('#verifyChoice').val("email");
                }
            }
    );


// images scripts

    $(".filter-button").click(function () {
        var value = $(this).attr('data-filter');

        if (value == "all")
        {
            //$('.filter').removeClass('hidden');
            $('.filter').show('1000');
        } else
        {
//            $('.filter[filter-item="'+value+'"]').removeClass('hidden');
//            $(".filter").not('.filter[filter-item="'+value+'"]').addClass('hidden');
            $(".filter").not('.' + value).hide('3000');
            $('.filter').filter('.' + value).show('3000');

        }
    });

    if ($(".filter-button").removeClass("active")) {
        $(this).removeClass("active");
    }
    $(this).addClass("active");

    $('#suggestName').keyup(function () {
        $('#modalMessage').hide();

        if ($('#suggestName').val().length > 2) {

            $('#sel1').html();
            $.ajax({
                url: '/CA2/ajax?suggestName=' + $('#suggestName').val(),
                success: function (json) {
                    //json.length > 6 some bug here (6 ???) :(
                    if (json.length > 6) {

                        $('#shareimage').fadeIn();
                    }
                    $.each($.parseJSON(json), function (idx, obj) {
                        $('#sel1').html("<option value='" + obj.id + "'>" + obj.username + "</option>")
                    });
                    $('#sel1').fadeIn();
                },
                type: 'GET'
            });
        } else {
            $('#sel1').fadeOut();
            $('#sel1').html();
            $('#shareimage').fadeOut();
        }
    });

    $('#shareimage').click(function () {
        var sharewith = $('#sel1').find(":selected").val();
        var imageid = $('#imageID').val();

        $.post("/CA2/ajax", {shareimage: "1", sharewith: sharewith, imageid: imageid})
                .done(function (data) {
                    console.log(data);
                    var obj = jQuery.parseJSON(data);
                    if (obj.imageShared == true) {
                        $('#modalMessage').addClass('alert-success');
                        $('#modalMessage').html("Image shared");
                    } else {
                        $('#modalMessage').addClass('alert-warning');
                        $('#modalMessage').html("Image share failed");
                    }
                    $('#modalMessage').show();

                    $('#suggestName').val('');
                    $('#sel1').html('');
                    $('#shareimage').fadeOut();
                });

    });
});


function set_share_image(image_id) {
    $('#imageID').val(image_id);
    $('#modalMessage').html('');
    $('#modalMessage').html();
    $('#modalMessage').removeClass('alert-warning');
    $('#modalMessage').removeClass('alert-success');
}