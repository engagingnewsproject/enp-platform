/*
* What needs to happen on Load
*/

// ready the questions as accordions and add in swanky button template
$('.enp-question-content').each(function(i) {
    // set up accordions
    setUpAccordion($(this));
    // add in image upload button template if it doesn't have an image
    if($('.enp-question-image', this).length === 0) {
        $('.enp-question-image__input', this).after(questionImageUploadButtonTemplate());
    }
});

// hide descriptions
$('.enp-image-upload__label, .enp-button__question-image-upload, .enp-question-image-upload__input').hide();

// set-up our ajax response container for messages to get added to
$('#enp-quiz').append('<section class="enp-quiz-message-ajax-container"></section>');

// add our sliders into the templates
$('.enp-slider-options').each(function() {
    setUpSliderTemplate($(this));
});
