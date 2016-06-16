jQuery( document ).ready( function( $ ) {

    // a click on Publish nav just clicks the Publish button instead
    $(document).on('click', '.enp-quiz-breadcrumbs__link--publish', function(e) {
        e.preventDefault();
        $('.enp-btn--next-step').trigger('click');
    });


    $('.enp-quiz-styles__input--color').each(function() {
        // set-up the new HTML
        $(this).wrap('<div class="enp-quiz-styles__iris-wrapper"></div>');
        // add a block to show the color
        $(this).before('<div class="enp-quiz-styles__color-demo" style="background: '+$(this).val()+'"></div>');


        // init the actual iris selector
        $(this).iris({
            width: 180,
            palettes: false,
            change: function(event, ui) {
                // change the preview background color
                $(this).prev(".enp-quiz-styles__color-demo").css( 'background', ui.color.toString());
            }
        });

        $(this).next('.iris-picker').append('<button type="button" class="enp-quiz-styles__set-default">Default</button><button type="button" class="enp-iris__close"><svg class="enp-icon enp-iris-close__icon"><use xlink:href="#icon-close" /></svg></button>');
    });

    $(document).on('click', '.enp-iris__close', function() {
        $(this).closest('.enp-quiz-styles__iris-wrapper').find('.enp-quiz-styles__input--color').iris('hide');

    });

    $('.enp-quiz-styles__iris-wrapper').on('click', '.enp-quiz-styles__color-demo', function() {
        // give focus to the input instead
        $(this).next('.enp-quiz-styles__input--color').trigger('focus');
    });

    $('.enp-quiz-styles__input--color').focus(function() {
        // hide any open ones
        $('.enp-quiz-styles__input--color').iris('hide');
        // show this one
        $(this).iris('show');
    });

    $('.enp-quiz-styles__iris-wrapper').on('click', '.enp-quiz-styles__set-default', function() {
        colorInput = $(this).closest('.enp-quiz-styles__iris-wrapper').find('.enp-quiz-styles__input--color');
        defaultVal = colorInput.attr('data-default');
        colorInput.iris('color', defaultVal);
    });

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
