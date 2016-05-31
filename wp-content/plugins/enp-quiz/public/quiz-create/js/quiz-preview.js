jQuery( document ).ready( function( $ ) {
    $('.enp-quiz-styles__input--color').wpColorPicker({
        clear: function(element) {
            setDefaultInputColor(element);
        },
        palettes: false
    });

    // change the name of the clear buttons to "Default"
    $('.wp-picker-clear').val('Default');
    // prevent click on circle from adding # to url and causing
    // page to jump to top
    $('.iris-square-value').click(function(e) {
        e.preventDefault();
    });

    function hexValidCheck(hex) {
        return /(^#[0-9A-F]{6}$)|(^#[0-9A-F]{3}$)/i.test(hex);
    }

    function setInputColor(obj, hex) {
        // validate hex
        var validateHex = hexValidCheck(hex);
        console.log(validateHex);
        if(validateHex === false) {
            setDefaultInputColor($(this));
        } else {
            $(this).val(hex);
        }

    }

    /*
    * If we want to set a default if they clear things.
    * The back-end code will set a default if they submit
    * an empty or invalid hex anyways
    */
    function setDefaultInputColor(element) {
        id = $(element.target).prev().attr('id');
        // figure out which one we're on
        if(id === 'enp-quiz-bg-color') {
            $('#'+id).wpColorPicker('color', '#ffffff');
        } else if(id === 'enp-quiz-text-color') {
            $('#'+id).wpColorPicker('color', '#444444');
        }
    }

});
