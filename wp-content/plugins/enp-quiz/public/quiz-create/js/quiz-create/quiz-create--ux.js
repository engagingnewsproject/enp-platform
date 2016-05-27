/*
* General UX interactions to make a better user experience
*/

// set titles as the values are being typed
$(document).on('keyup', '.enp-question-title__textarea', function() {
    // get the value of the textarea we're typing in
    question_title = $(this).val();
    // find the accordion header it goes with and add in the title
    $(this).closest('.enp-question-content').prev('.enp-accordion-header').find('.enp-accordion-header__title').text(question_title);
});
