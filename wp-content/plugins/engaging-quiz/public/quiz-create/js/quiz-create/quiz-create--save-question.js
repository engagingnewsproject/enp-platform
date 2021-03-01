function temp_addQuestion() {

    templateParams = {question_id: 'newQuestionTemplateID',
            question_position: 'newQuestionPosition'};

    // add the template in
    $('.enp-quiz-create__questions').append(questionTemplate(templateParams));

    newQuestion = $('#enp-question--newQuestionTemplateID');
    questionImageUpload = questionImageUploadTemplate(templateParams);
    // add the question upload area
    $('.enp-question-image__input', newQuestion).after(questionImageUpload);
    // hide the new image buttons
    $('.enp-image-upload__label, .enp-button__question-image-upload, .enp-question-image-upload__input', newQuestion).hide();

    // add the swanky image upload button
    // bring the swanky upload image visual button back
    $('.enp-question-image__input',newQuestion).after(questionImageUploadButtonTemplate());

    // add a temp MC option
    temp_addMCOption('newQuestionTemplateID');

    // add a temp Slider
    temp_addSlider('newQuestionTemplateID');

    // set-up accordion
    // set-up question_content var
    setUpAccordion($('#enp-question--newQuestionTemplateID'));

    // focus the accordion button
    $('#enp-question--newQuestionTemplateID__accordion-header').focus();

}

// undo our temp action
function unset_tempAddQuestion() {
    // we didn't get a valid response from the server, so remove the question
    $('#enp-question--newQuestionTemplateID__accordion-header').remove();
    $('#enp-question--newQuestionTemplateID').remove();
    // give them an error message
    appendMessage('Question could not be added. Please reload the page and try again.', 'error');
}

// clone question, clear values, delete mc_options except one, add questionID, add MC option ID
function addQuestion(questionID, mcOptionID, sliderID) {

    // find replace on container
    findReplaceDomAttributes(document.getElementById('enp-question--newQuestionTemplateID__accordion-container'), /newQuestionTemplateID/, questionID);
    // find/replace all array index attributes
    findReplaceDomAttributes(document.getElementById('enp-question--'+questionID), /newQuestionPosition/, getQuestionIndex(questionID));

    // change the default MCOptionIDs
    addMCOption(mcOptionID, questionID);

    // change the default sliderIds
    addSlider(sliderID, questionID);
}


function temp_removeQuestion(questionID) {
    var accordionContainer,
        question;
    // move the keyboard focus to the element BEFORE? the accordion
    // find the container
    accordionContainer = getQuestionContainer(questionID)
    // remove the accordion container
    accordionContainer.addClass('enp-question--remove');
    // find the question
    question = $('#enp-question--'+questionID);
    // move the keyboard focus to the element AFTER? the accordion
    accordionContainer.next().find('enp-question-content').focus();
    // remove the question
    // question.addClass('enp-question--remove');
}

function temp_unsetRemoveQuestion(questionID) {
    var accordionContainer,
        question;
    // move the keyboard focus to the element BEFORE? the accordion
    // find the button
    accordionContainer = getQuestionContainer(questionID)
    // remove the accordion container remove class
    accordionContainer.removeClass('enp-question--remove');
    // find the question
    // $('#enp-question--'+questionID).removeClass('enp-question--remove');

    appendMessage('Question could not be deleted. Please reload the page and try again.', 'error');
}

function removeQuestion(questionID) {
    // remove accordion
    getQuestionContainer(questionID).remove();
    // remove question
    // $('#enp-question--'+questionID).remove();
    // update the indexes
    updateQuestionIndexes()
}
