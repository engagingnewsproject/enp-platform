// ajax submission
$(document).on('click', '.enp-quiz-submit', function(e) {

    if(!$(this).hasClass('enp-btn--next-step')) {
        e.preventDefault();
        // if new quiz flag is 1, then check for a title before continue
        if($('#enp-quiz-new').val() === '1') {
            // check for a title
            if($('.enp-quiz-title__textarea').val() === '') {
                $('.enp-quiz-title__textarea').focus();
                appendMessage('Please enter a title for your quiz.', 'error');
                return false;
            }
        }

        // add an "Are you sure about that?"
        if($(this).hasClass('enp-question__button--delete')) {
            // TODO This should be an "undo", not a confirm
            var confirmDelete = confirm('Are you sure you want to delete this question?');
            if(confirmDelete === false) {
                return false;
            }  else {
                // they want to delete it, so let them
                // TODO This should be an "undo", not a confirm
            }
        }

        // add a click wait, if necessary or r
        if($(this).hasClass('enp-quiz-submit--wait')) {
            console.log('waiting...');
            return false;
        } else {
            setWait();
        }

        // ajax send
        var userAction = $(this).val();
        // save the quiz
        saveQuiz(userAction);
    }
});

function saveQuiz(userAction) {
    var response,
        userActionAction,
        userActionElement;

    // get form
    var quizForm = document.getElementById("enp-quiz-create-form");
    // create formData object
    var fd = new FormData(quizForm);
    // set our submit button value
    fd.append('enp-quiz-submit', userAction);
    // append our action for wordpress AJAX call
    fd.append('action', 'save_quiz');

    // this sets up the immediate actions so it feels faster to the user
    // Optimistic Ajax
    setTemp(userAction);

    $.ajax( {
        type: 'POST',
         url  : quizCreate.ajax_url,
         data : fd,
         processData: false,  // tell jQuery not to process the data
         contentType: false,   // tell jQuery not to set contentType
    } )
    // success
    .done( quizSaveSuccess )
    .fail( function( jqXHR, textStatus, errorThrown ) {
        console.log( 'AJAX failed', jqXHR.getAllResponseHeaders(), textStatus, errorThrown );
    } )
    .then( function( errorThrown, textStatus, jqXHR ) {
        console.log( 'AJAX after finished' );

    } )
    .always(function() {
        // remove wait class elements
        unsetWait();
    });
}

function quizSaveSuccess( response, textStatus, jqXHR ) {
    console.log(jqXHR.responseJSON);
    if(jqXHR.responseJSON === undefined) {
        // error :(
        unsetWait();
        appendMessage('Something went wrong. Please reload the page and try again.', 'error');
        return false;
    }

    response = $.parseJSON(jqXHR.responseJSON);

    userActionAction = response.user_action.action;
    userActionElement = response.user_action.element;
    // see if we've created a new quiz
    if(response.status === 'success' && response.action === 'insert') {
        // set-up quiz
        setNewQuiz(response);
    }
    // check user action
    if(userActionAction == 'add' && userActionElement == 'question') {
        var newQuestionResponse = getNewQuestion(response.question);

        if(newQuestionResponse !== false && newQuestionResponse.question_id !== undefined && parseInt(newQuestionResponse.question_id) > 0) {
            // we have a new question!
            new_questionID = newQuestionResponse.question_id;
            new_mcOption = getNewMCOption(new_questionID, response.question);
            new_sliderID = newQuestionResponse.slider.slider_id;
            addQuestion(new_questionID, new_mcOption.mc_option_id, new_sliderID);
        } else {
            unset_tempAddQuestion();
        }
    }
    // remove Question
    else if(userActionAction == 'delete' && userActionElement == 'question') {
        // check to see if the action was completed
        questionID = response.user_action.details.question_id;
        questionResponse = checkQuestionSaveStatus(questionID, response.question);
        if(questionResponse !== false && questionResponse.action === 'update' && questionResponse.status === 'success') {
            removeQuestion(questionID);
        } else {
            temp_unsetRemoveQuestion(questionID);
        }

    } else if(userActionAction == 'delete' && userActionElement == 'mc_option') {
        // check to see if the action was completed
        var mcOptionID = response.user_action.details.mc_option_id;
        mcOptionResponse = checkMCOptionSaveStatus(mcOptionID, response.question);
        if(mcOptionResponse !== false && mcOptionResponse.action === 'update' && mcOptionResponse.status === 'success') {
            removeMCOption(mcOptionID);
        } else {
            temp_unsetRemoveMCOption(mcOptionID);
        }

    }
    // add mc_option
    else if(userActionAction == 'add' && userActionElement == 'mc_option') {
        // get the new inserted mc_option_id
        questionID = response.user_action.details.question_id;
        var newMCOptionResponse = getNewMCOption(questionID, response.question);
        if(newMCOptionResponse !== false && newMCOptionResponse.mc_option_id !== undefined && parseInt(newMCOptionResponse.mc_option_id) > 0) {
            // looks good! add the mc option
            addMCOption(newMCOptionResponse.mc_option_id, questionID);
        } else {
            // uh oh, something didn't go right. Remove it.
            unset_tempAddMCOption(questionID);
        }
    }
    // set correct mc_option
    else if(userActionAction == 'set_correct' && userActionElement == 'mc_option') {
        // set the correct one
        setCorrectMCOption(response.user_action.details.mc_option_id, response.user_action.details.question_id);
    }
    // add question image
    else if(userActionAction == 'upload' && userActionElement == 'question_image') {
        // check to see if the action was completed
        questionID = response.user_action.details.question_id;
        questionResponse = checkQuestionSaveStatus(questionID, response.question);
        if(questionResponse !== false && questionResponse.action === 'update' && questionResponse.status === 'success') {
            addQuestionImage(questionResponse);
        } else {
            temp_unsetAddQuestionImage(questionID);
        }
    }
    // remove image
    else if(userActionAction == 'delete' && userActionElement == 'question_image') {
        // check to see if the action was completed
        questionID = response.user_action.details.question_id;
        questionResponse = checkQuestionSaveStatus(questionID, response.question);
        if(questionResponse !== false && questionResponse.action === 'update' && questionResponse.status === 'success') {
            removeQuestionImage(questionResponse);
        } else {
            temp_unsetRemoveQuestionImage(questionID);
        }
    }
    // show ajax messages
    displayMessages(response.message);
}

function setNewQuiz(response) {
    $('#enp-quiz-id').val(response.quiz_id);

    // change the URL to our new one
    var html = $('body').innerHTML;
    var pageTitle = $('.enp-quiz-title__textarea').val();
    pageTitle = 'Quiz: '+pageTitle;
    var urlPath = quizCreate.quiz_create_url + response.quiz_id;
    window.history.pushState({"html":html,"pageTitle":pageTitle},"", urlPath);
}

function setTemp(userAction) {
    var pattern;
    // deleting a question
    console.log(userAction);
    if(userAction.indexOf('add-question') > -1) {
        // match the number for the ID
        temp_addQuestion();
    }
    else if(userAction.indexOf('add-mc-option__question') > -1) {
        pattern = /add-mc-option__question-/g;
        questionID = userAction.replace(pattern, '');
        temp_addMCOption(questionID);
    }
    else if(userAction.indexOf('question--delete') > -1) {
        // match the number for the ID
        pattern = /question--delete-/g;
        questionID = userAction.replace(pattern, '');
        temp_removeQuestion(questionID);
    }
    // deleting a mc option
    else if(userAction.indexOf('mc-option--delete') > -1) {
        // match the number for the ID
        pattern = /mc-option--delete-/g;
        mcOptionID = userAction.replace(pattern, '');
        temp_removeMCOption(mcOptionID);
    }
    // delete an image
    else if(userAction.indexOf('question-image--upload') > -1) {
        // match the number for the ID
        pattern = /question-image--upload-/g;
        questionID = userAction.replace(pattern, '');
        console.log(questionID);
        temp_addQuestionImage(questionID);
    }
    // delete an image
    else if(userAction.indexOf('question-image--delete') > -1) {
        // match the number for the ID
        pattern = /question-image--delete-/g;
        questionID = userAction.replace(pattern, '');
        console.log(questionID);
        temp_removeQuestionImage(questionID);
    }

}

// add wait classes to prevent duplicate submissions
// and add message/animation to show stuff is happening
function setWait() {
    // TODO: animation to show stuff is happening and they should wait a sec
    $('.enp-quiz-message-ajax-container').append('<div class="enp-quiz-message--saving">'+waitSpinner('enp-quiz-message--saving__spinner')+'<div class="enp-quiz-message--saving__text">Saving</div></div>');
    // add click wait class
    $('.enp-quiz-submit').addClass('enp-quiz-submit--wait');
}
// removes wait classes that prevent duplicate sumissions
function unsetWait() {
    $('.enp-quiz-submit').removeClass('enp-quiz-submit--wait');
    $('.enp-quiz-message--saving').remove();
}
