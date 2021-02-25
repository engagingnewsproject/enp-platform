function temp_addQuestionImage(question_id) {
    $('#enp-question--'+questionID+' .enp-question-image-upload').hide();
    $('#enp-question--'+questionID+' .enp-question-image-upload').after(waitSpinner('enp-image-upload-wait'));
}

function unset_tempAddQuestionImage(question_id) {
    $('#enp-question--'+questionID+' .enp-question-image-upload').show();
    $('#enp-question--'+questionID+' .enp-image-upload-wait').remove();

    appendMessage('Image could not be uploaded. Please reload the page and try again.', 'error');
}

function addQuestionImage(question) {
    questionID = question.question_id;
    $('#enp-question--'+questionID+' .enp-question-image-upload').remove();
    $('#enp-question--'+questionID+' .enp-question-image-upload__input').remove();
    $('#enp-question--'+questionID+' .enp-image-upload-wait').remove();


    // add the value for this question in the input field
    $('#enp-question--'+questionID+' .enp-question-image__input').val(question.question_image);

    // load the new image template
    templateParams ={question_id: questionID, question_position: getQuestionIndex(questionID)};
    $('#enp-question--'+questionID+' .enp-question-image__input').after(questionImageTemplate(templateParams));

    imageFile = question.question_image;
    // get the 580 wide one
    imageFile = imageFile.replace(/-original/g, '580w');

    // build the imageURL
    imageURL = quizCreate.quiz_image_url + $('#enp-quiz-id').val() + '/' + questionID + '/' + imageFile;

    // insert the image
    $('#enp-question--'+questionID+' .enp-question-image__container').prepend('<img class="enp-question-image enp-question-image" src="'+imageURL+'" alt="'+question.question_image_alt+'"/>');

}

function temp_removeQuestionImage(questionID) {
    $('#enp-question--'+questionID+' .enp-question-image__container').addClass('enp-question__image--remove');
    // set a temporary data attribute so we can get the value back if it doesn't save
    imageInput = $('#enp-question-image-'+questionID);
    imageFilename = imageInput.val();
    imageInput.data('image_filename', imageFilename);
    // unset the val in the image input
    imageInput.val('');


}

function temp_unsetRemoveQuestionImage(questionID) {
    $('#enp-question--'+questionID+' .enp-question-image__container').removeClass('enp-question__image--remove');

    // set the val in the image input back
    oldImageFilename = $('#enp-question-image-'+questionID).data('image_filename');
    $('#enp-question-image-'+questionID).val(oldImageFilename);
    // send an error message
    appendMessage('Image could not be deleted. Please reload the page and try again.', 'error');
}

function removeQuestionImage(question) {
    questionID = question.question_id;

    question = $('#enp-question--'+questionID);

    $('.enp-question-image__container', question).remove();
    // clear the input
    $('.enp-question-image__input', question).val('');

    // bring the labels back
    // load the new image template
    templateParams ={question_id: questionID, question_position: getQuestionIndex(questionID)};
    $('.enp-question-image__input',question).after(questionImageUploadTemplate(templateParams));
    // hide the upload button
    $('.enp-image-upload__label, .enp-button__question-image-upload, .enp-question-image-upload__input', question).hide();
    // bring the swanky upload image visual button back
    $('.enp-question-image__input',question).after(questionImageUploadButtonTemplate());
    // focus the button in case they want to upload a new one
    $('.enp-question-image-upload',question).focus();
}

$(document).on('click', '.enp-question-image-upload', function() {
    imageUploadInput = $(this).siblings('.enp-question-image-upload__input');
    imageUploadInput.trigger('click'); // bring up file selector
});

$(document).on('change', '.enp-question-image-upload__input',  function() {
    imageSubmit = $(this).siblings('.enp-button__question-image-upload');
    imageSubmit.trigger('click');
    // move focus to image description input
    imageSubmit.siblings('.enp-question-image-alt__input').focus();
});
