function temp_addSlider(questionID) {
    // clone the template
    temp_slider = sliderTemplate({
            question_id: questionID,
            question_position: 'newQuestionPosition',
            slider_id: 'newSliderID',
            slider_range_low: '0',
            slider_range_high: '10',
            slider_correct_low: '5',
            slider_correct_high: '5',
            slider_increment: '1',
            slider_prefix: '',
            slider_suffix: ''
        });

    // insert it
    $(temp_slider).appendTo('#enp-question--'+questionID+' .enp-question');

}

// add MC option ID, question ID, question index, and mc option index
function addSlider(new_sliderID, questionID) {

    new_slider_el = document.querySelector('#enp-question--'+questionID+' .enp-slider-options');

    // find/replace all index attributes (just in the name, but it'll search all attributes)
    findReplaceDomAttributes(new_slider_el, /newQuestionPosition/, getQuestionIndex(questionID));

    findReplaceDomAttributes(new_slider_el, /newSliderID/, new_sliderID);

    // set-up the UX (accordions, range buttons, etc) now that we have an ID
    setUpSliderTemplate('#enp-question--'+questionID+' .enp-slider-options');
}

function createSliderTemplate(container) {
    var sliderData;
    // scrape the input values and create the template
    sliderRangeLow = parseFloat($('.enp-slider-range-low__input', container).val());
    sliderRangeHigh = parseFloat($('.enp-slider-range-high__input', container).val());
    sliderIncrement = parseFloat($('.enp-slider-increment__input', container).val());
    sliderStart = getSliderStart(sliderRangeLow, sliderRangeHigh, sliderIncrement);

    sliderData = {
        'slider_id': $('.enp-slider-id', container).val(),
        'slider_range_low': sliderRangeLow,
        'slider_range_high': sliderRangeHigh,
        'slider_start': sliderStart,
        'slider_increment': sliderIncrement,
        'slider_prefix': $('.enp-slider-prefix__input', container).val(),
        'slider_suffix': $('.enp-slider-suffix__input', container).val(),
        'slider_input_size': $('.enp-slider-range-high__input', container).val().length
    };

    // create slider template
    slider = sliderTakeTemplate(sliderData);
    sliderExample = $('<div class="enp-slider-preview"></div>').html(slider);
    // insert it
    $(sliderExample).prependTo(container);

    $('.enp-slider__label', container).text('Slider Preview');
    // create the jQuery slider
    createSlider($('.enp-slider-input__input', container), sliderData);
}

// on change slider values
$(document).on('blur', '.enp-slider-range-low__input', function() {
    sliderID = $(this).data('sliderID');
    slider = getSlider(sliderID);
    sliderRangeLow = parseFloat($(this).val());
    slider.slider('option', 'min',  sliderRangeLow);
    sliderInput = $("#enp-slider-input__"+sliderID);
    sliderInput.attr('min', sliderRangeLow);

    // set midpoint
    setSliderStart(slider, sliderInput);
});

// on change slider values
$(document).on('input', '.enp-slider-range-low__input', function() {
    var low;
    // get input value
    low = $(this).val();
    sliderPreview = getSliderPreviewElement($(this), '.enp-slider_input__range-helper__number--low');
    sliderPreview.text(low);

});

// on change slider values
$(document).on('input', '.enp-slider-range-high__input', function() {
    var high;
    // get input value
    high = $(this).val();
    sliderPreview = getSliderPreviewElement($(this), '.enp-slider_input__range-helper__number--high');
    sliderPreview.text(high);
});

// update high range and max value
$(document).on('blur', '.enp-slider-range-high__input', function() {
    sliderID = $(this).data('sliderID');
    slider = getSlider(sliderID);
    sliderRangeHigh = parseFloat($(this).val());
    slider.slider('option', 'max',  sliderRangeHigh);
    sliderInput = $("#enp-slider-input__"+sliderID);
    sliderInput.attr('max', sliderRangeHigh);
    // set midpoint
    setSliderStart(slider, sliderInput);
});

/**
* If the high correct value range isn't being used, we need to keep
* the low and high correct values in sync.
*/
$(document).on('input', '.enp-slider-correct-low__input', function() {

    // Check if the high input is being used.
    // if it's NOT being used, we need to keep the high value in sync
    if($(this).data('correctRangeInUse') === false) {
        // get the high correct input from the data on the low input
        highCorrectInput = $(this).data('highCorrectInput');
        // get the low correct value
        lowCorrectVal = $(this).val();
        // make the high correct input match the low correct input
        highCorrectInput.val(lowCorrectVal);
        console.log(highCorrectInput.val());
    }
});

// update high range and max value
$(document).on('blur', '.enp-slider-increment__input', function() {
    sliderID = $(this).data('sliderID');
    slider = getSlider(sliderID);
    sliderIncrement = parseFloat($(this).val());
    slider.slider('option', 'step',  sliderIncrement);
    sliderInput = $("#enp-slider-input__"+sliderID);
    sliderInput.attr('step', sliderIncrement);

    // set midpoint
    setSliderStart(slider, sliderInput);
});

// update the slider prefix on value change
$(document).on('input', '.enp-slider-prefix__input', function() {
    var prefix;
    // get input value
    prefix = $(this).val();
    sliderPreview = getSliderPreviewElement($(this), '.enp-slider-input__prefix');
    sliderPreview.text(prefix);
});

// update the slider suffix on value change
$(document).on('input', '.enp-slider-suffix__input', function() {
    var suffix;
    // get input value
    suffix = $(this).val();
    sliderPreview = getSliderPreviewElement($(this), '.enp-slider-input__suffix');
    sliderPreview.text(suffix);
});

// set the slider to the middle point
function setSliderStart(slider, sliderInput) {
    low = slider.slider('option', 'min');
    high = slider.slider('option', 'max');
    interval = slider.slider('option', 'step');
    sliderValue = getSliderStart(low, high, interval);
    // set it
    slider.slider("value", sliderValue);
    sliderInput.val(sliderValue);
}

// calculate where the slider should start
function getSliderStart(low, high, interval) {
    low = parseFloat(low);
    high = parseFloat(high);
    interval = parseFloat(interval);

    totalIntervals = (high - low)/interval;
    middleInterval = ((totalIntervals/2)*interval) + low;
    remainder = middleInterval % interval;
    middleInterval = middleInterval - remainder;

    return middleInterval;
}

function getSliderPreviewElement(obj, element) {
    return obj.closest('.enp-slider-options').find(element);
}

$(document).on('click', '.enp-slider-correct-answer-range', function() {
    sliderID = $(this).data('sliderID');
    if($(this).hasClass('enp-slider-correct-answer-range--add-range')) {
        addSliderRange(sliderID);
    } else {
        removeSliderRange(sliderID);
    }
});


function removeSliderRange(sliderID) {
    var container,
        lowCorrectInput;
    container = getSliderOptionsContainer(sliderID);
    // get low input
    lowCorrectInput = $('.enp-slider-correct-low__input', container);

    // hide the answer range high input and "to" thang
    $('.enp-slider-correct__helper', container).addClass('enp-slider-correct__helper--hidden').text('');
    // hide the input correct high container
    $('.enp-slider-correct-high__input-container', container).addClass('enp-slider-correct-high__input-container--hidden');
    // change the low correct label to Slider Answer (remove Low)
    $('.enp-slider-correct-low__label', container).text('Slider Answer');
    // Set the button content and classes
    $('.enp-slider-correct-answer-range', container).removeClass('enp-slider-correct-answer-range--remove-range').addClass('enp-slider-correct-answer-range--add-range').html('<span class="enp-screen-reader-text">Remove Answer Range</span><svg class="enp-icon enp-slider-correct-answer-range__icon"><use xlink:href="#icon-add" /></svg> Answer Range');

    // Make Correct High value equal the Low value
    lowCorrectVal = lowCorrectInput.val();
    $('.enp-slider-correct-high__input', container).val(lowCorrectVal);
    // set data attribute on the low input so we know if the high input needs to get updated or not
    lowCorrectInput.data('correctRangeInUse', false);
}

function addSliderRange(sliderID) {
    var container,
        lowCorrectInput,
        highCorrectInput;

    container = getSliderOptionsContainer(sliderID);
    // get low input
    lowCorrectInput = $('.enp-slider-correct-low__input', container);
    // get high input
    highCorrectInput = $('.enp-slider-correct-high__input', container);

    $('.enp-slider-correct-low__label', container).text('Slider Answer Low');
    $('.enp-slider-correct__helper', container).removeClass('enp-slider-correct__helper--hidden').text('to');
    $('.enp-slider-correct-high__input-container', container).removeClass('enp-slider-correct-high__input-container--hidden');
    $('.enp-slider-correct-answer-range', container).removeClass('enp-slider-correct-answer-range--add-range').addClass('enp-slider-correct-answer-range--remove-range').html('<svg class="enp-icon enp-slider-correct-answer-range__icon"><use xlink:href="#icon-close" /></svg>');
    // Add one interval to the high value if it equals the low value
    highCorrectVal = parseFloat( highCorrectInput.val() );
    lowCorrectVal = parseFloat( lowCorrectInput.val() );
    increment = parseFloat( $('.enp-slider-increment__input', container).val() );
    if(highCorrectVal <= lowCorrectVal) {
        highCorrectInput.val(lowCorrectVal + increment);
    }
    // focus the slider range high input
    highCorrectInput.focus();
    // set data attribute on the low input so we know if the high input needs to get updated or not
    lowCorrectInput.data('correctRangeInUse', true);
}

function getSliderOptionsContainer(sliderID) {
    var sliderOptions;
    $('.enp-slider-options').each(function() {
        // check it's sliderID
        if($(this).data('sliderID') === sliderID) {
            // if it equals, then set the slider var and break out of the each loop
            sliderOptions = $(this);
            if(typeof(callback) == "function") {
                callback($(this));
            }
            return false;
        }
    });
    return sliderOptions;
}


function setUpSliderTemplate(sliderOptionsContainer) {

    sliderID = $('.enp-slider-id', sliderOptionsContainer).val();
    // add data to slider options container
    $(sliderOptionsContainer).data('sliderID', sliderID);
    createSliderTemplate(sliderOptionsContainer);
    // add in the correct answer range selector
    $('.enp-slider-correct-high__container', sliderOptionsContainer).append('<button class="enp-slider-correct-answer-range" type="button"></button>');
    // add the sliderID to all the inputs
    $('input, button', sliderOptionsContainer).each(function() {
        $(this).data('sliderID', sliderID);
    });
    // get low correct input
    lowCorrectInput = $('.enp-slider-correct-low__input', sliderOptionsContainer);
    // get high correct input
    highCorrectInput = $('.enp-slider-correct-high__input', sliderOptionsContainer);
    // See if we should hide the slider answer high and add in the option to add in a high value
    // link the high and low inputs together so we can easily find them as needed
    lowCorrectInput.data('highCorrectInput', highCorrectInput);
    highCorrectInput.data('lowCorrectInput', lowCorrectInput);
    correctLow = parseFloat( lowCorrectInput.val() );
    correctHigh = parseFloat( highCorrectInput.val() );

    if( correctLow === correctHigh ) {
        removeSliderRange(sliderID);
    } else {
        addSliderRange(sliderID);
    }


    // set-up accordion for advanced options
    // create the title and content accordion object so our headings can get created
    accordion = {title: 'Advanced Slider Options', content: $('.enp-slider-advanced-options__content', sliderOptionsContainer), baseID: sliderID};
    //returns an accordion object with the header object and content object
    accordion = enp_accordion__create_headers(accordion);
    // set-up all the accordion classes and start classes (so they're closed by default)
    enp_accordion__setup(accordion);
}
