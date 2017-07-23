/**
 * Created by Zbyněk Mlčák on 29.06.2017.
 */
$(function () {
    tinymce.init({
        selector: 'textarea[name=content]',
        language: 'cs',
        /*language_url: 'plugins/langs/cs.js',*/
        paste_data_images: true,
        width: '100%',
        height: 300,
        plugins: [
            /*        "advlist autolink lists link image charmap print preview anchor imagetools",
             "searchreplace visualblocks code fullscreen",
             "insertdatetime media table contextmenu paste",*/
            "advlist autolink lists link image charmap print preview hr anchor pagebreak",
            "searchreplace wordcount visualblocks visualchars code fullscreen",
            "insertdatetime media nonbreaking save table contextmenu directionality",
            "emoticons template paste textcolor colorpicker textpattern"
        ],
        image_advtab: true,
        file_picker_callback: function (callback, value, meta) {
            if (meta.filetype == 'image') {
                $('#upload').trigger('click');
                $('#upload').on('change', function () {
                    var file = this.files[0];
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        callback(e.target.result, {
                            alt: ''
                        });
                    };
                    reader.readAsDataURL(file);
                });
            }
        },
        toolbar: "insertfile undo redo | styleselect | forecolor bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
        entities: "160,nbsp",
        entity_encoding: "named",
        entity_encoding: "raw",
        /*    images_upload_url: 'postAcceptor.php',
         images_upload_base_path: '/some/basepath'*/

        /*    plugins: 'myplugin',
         external_plugins: {
         'myplugin': '/js/myplugin/plugin.min.js'
         }*/
    });

    $("#newRoom header").click(function () {
        $("#newRoom form").toggle();
    })

    $("#existRooms header").click(function () {
        $("#existRooms table").toggle();
    })
});


