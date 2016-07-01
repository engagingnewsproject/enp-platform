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
$('#enp-quiz').append('<section class="enp-quiz-message-ajax-container" aria-live="assertive"></section>');

// add our sliders into the templates
$('.enp-slider-options').each(function() {
    setUpSliderTemplate($(this));
});

// check if there are any questions. If there aren't, then don't show the save/preview buttons
var url = window.location.href;
var patt = new RegExp("quiz-create/new");
if(patt.test(url) === true) {
    hideSaveButton();
}

// check if there are any error messages
if($('.enp-message__item--error').length !== 0) {
    var re = /Question \d+/;
    // check each to see if we need to higlight a question
    $('.enp-message__item--error').each(function() {
        errorMessage = $(this).text();
        found = errorMessage.match(re);
        // if we found anything, process it
        if(found !== null) {
            // extract the number
            questionNumber = found[0].replace(/Question /, '');
            questionNumber = questionNumber - 1;
            console.log(questionNumber);
            questionHeader = $('.enp-question-content:eq('+questionNumber+')').prev('.enp-accordion-header');
            console.log(questionHeader.text());
            if(!questionHeader.hasClass('question-has-error')) {
                questionHeader.addClass('question-has-error');
            }
        }

    });

}
