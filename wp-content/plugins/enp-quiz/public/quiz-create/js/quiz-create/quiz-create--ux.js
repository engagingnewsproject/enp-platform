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


// a click on Preview or Publish nav just clicks the preview button instead
$(document).on('click', '.enp-quiz-breadcrumbs__link--preview, .enp-quiz-breadcrumbs__link--publish', function(e) {
    e.preventDefault();
    $('.enp-btn--next-step').trigger('click');
});


function hideSaveButton() {
    $('.enp-quiz-form__save, .enp-btn--next-step').hide();
}

function showSaveButton() {
    $('.enp-quiz-form__save').show().addClass('enp-quiz-form__save--reveal');
    $('.enp-btn--next-step').show().addClass('enp-btn--next-step--reveal');
    $('.enp-quiz-breadcrumbs__link--preview').removeClass('enp-quiz-breadcrumbs__link--disabled');
}
