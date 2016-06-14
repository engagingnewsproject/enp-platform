/**
* Submit question when you click question input label
*
* Process: On click of a selection,
* 1. Show the right one
* 2. Slide out incorrect ones (other than the one clicked)
* 3. Show the explanation
* 4. Trigger click on the question submit button
*/
$(document).on('click', '.enp-option__label', function(e){
    // make sure the question hasn't already been answered
    if(!$('.enp-question__container--unanswered').length) {
        return false;
    }
    // get the input related to the label
    var thisMCInput = $(this).prev('.enp-option__input');
    // See if the DOM has updated to select the corresponding input yet or not.
    // if it hasn't select it, then submit the form
    if ( !thisMCInput.prop( "checked" ) ) {
        thisMCInput.prop("checked", true);
    }

    // just trigger a click on the submit button
    $('.enp-question__submit').trigger('click');
});


// 5. save the quiz on click
// AJAX save
$(document).on('click', '.enp-question__submit', function(e){
    e.preventDefault();
    // get the JSON data for this question
    var questionJSON = $(this).closest('.enp-question__fieldset').data('questionJSON');

    // if mc option
    if(questionJSON.question_type === 'mc') {
        correct_string = processMCSubmit();
    } else if(questionJSON.question_type === 'slider') {
        questionFieldset = $(this).parent();
        correct_string = processSliderSubmit(questionFieldset);
    }

    // we have a legit correct answer!
    if(correct_string === 'correct' || correct_string === 'incorrect') {
        // set-up the current question container class state, remove unanswered class
        $('.enp-question__container').addClass('enp-question__container--explanation').removeClass('enp-question__container--unanswered');
    } else {
        // no correct answer... that means the response was invalid
        console.log('invalid response.');
        // don't try to save anything
        return false;
    }
    // add answered class
    $(this).closest('.enp-question__fieldset').addClass('enp-question__answered');
    // show the explanation by generating the question explanation template
    var qExplanationTemplate = generateQuestionExplanation(questionJSON, correct_string);

    // add the Question Explanation Template into the DOM
    $('.enp-question__submit').before(qExplanationTemplate);
    // focus it
    $('.enp-next-step').focus();
    // submit the question
    data = prepareQuestionFormData($(this));
    url = $('.enp-question__form').attr('action');

    // AJAX Submit form
    $.ajax( {
        type: 'POST',
        url  : url,
        data : data,
        dataType : 'json',
    } )
    // success
    .done( questionSaveSuccess )
    .fail( function( jqXHR, textStatus, errorThrown ) {
        console.log( 'AJAX failed', jqXHR.getAllResponseHeaders(), textStatus, errorThrown );
    } )
    .then( function( errorThrown, textStatus, jqXHR ) {
        console.log( 'AJAX after finished' );
    } )
    .always(function() {

    });
});

function questionSaveSuccess( response, textStatus, jqXHR ) {
    // real quick, hide the submit button so it can't get submitted again
    $('.enp-question__submit').remove();
    // get the response
    var responseJSON = $.parseJSON(jqXHR.responseText);
    console.log(responseJSON);
    // see if there are any errors
    if(responseJSON.error.length) {
        _.handle_error_message(responseJSON.error[0]);
    }

    // see if there's a next question
    else if(responseJSON.next_state === 'question') {
        // we have a next question, so generate it
        generateQuestion(responseJSON.next_question);

    } else {
        // we're at the quiz end, in the future, we might get some data
        // ready so we can populate quiz end instantly. Let's just do it based on a response from the server instead for now so we don't have to set localStorage and have duplicate copy for all the quiz end states

    }

    // send the height of the new view
    sendBodyHeight();

}

/**
* Binds JSON data to the main question element in the DOM so we always have
* access to it. Accessible via
* $('#question_'+questionJSON.question_id).data('questionJSON');
*/
function bindQuestionData(questionJSON) {
    $('#question_'+questionJSON.question_id).data('questionJSON', questionJSON);
}

/**
* Shortcut function for getting JSON data from the question wrapper element.
* Not super necessary, but if we ever want to filter/change the data before
* sending the data back, this would be handy.
* @param questionID (int/string) question Id of the
*        question in the DOM you want data for
* @return JSON data for the question
*/
function getQuestionData(questionID) {
    return $('#question_'+questionID).data('questionJSON', questionJSON);
}

/**
* Generates a new Question off of Question JSON data and the Question Template(s)
* and inserts it into the page as an "on-deck" question
*/
function generateQuestion(questionJSON) {

    var questionData = {
                        'question_id': questionJSON.question_id,
                        'question_type': questionJSON.question_type,
                        'question_title': questionJSON.question_title,
    };

    new_questionTemplate = questionTemplate(questionData);
    $('.enp-question__fieldset').before(new_questionTemplate);
    // find it and add the classes we need
    $('#question_'+questionJSON.question_id)
        .addClass('enp-question--on-deck')
        .attr('aria-hidden', true);
    // add the data to the new question
    bindQuestionData(questionJSON);

    // add in the image template, if necessary
    if(questionJSON.question_image !== '') {
        buildImageTemplate(questionJSON);
    }

    // Build templates and bind data for the question
    if(questionJSON.question_type === 'mc') {
        // build mc option templates and bind data
        buildMCOptions(questionJSON);
    } else if(questionJSON.question_type === 'slider') {
        // build slider template and bind data
        buildSlider(questionJSON);
    }
}

/**
* Increase the current question number on the progress bar
* and the width of the progress bar
* @param questionOrder = the question_order of the next question
*/
function increaseQuestionProgress(questionOrder) {
    questionNumber = parseInt(questionOrder) + 1;
    // increase the question number and css if we have another one
    totalQuestions = _.get_total_questions();
    progressBarWidth = (questionNumber/totalQuestions) * 100;
    if(progressBarWidth === 100) {
        progressBarWidth = 95;
    }
    progressBarWidth = progressBarWidth + '%';


    // BEM Taken WAAAAAAY too far...
    $('.enp-quiz__progress__bar__question-count__current-number').text(questionNumber);

    // update ARIA attributes
    $('.enp-quiz__progress__bar').attr('aria-valuetext', $('.enp-quiz__progress__bar__question-count').text());
    $('.enp-quiz__progress__bar').attr('aria-valuenow', $('.enp-quiz__progress__bar__question-count__current-number').text());

    $('.enp-quiz__progress__bar').css('width', progressBarWidth);
}


/**
* Add/Remove classes to bring in the next question
*/
function showNextQuestion(obj) {
    obj.addClass('enp-question--show')
       .removeClass('enp-question--on-deck')
       .attr('aria-hidden', false);
    // get the data from it
    questionShowJSON = obj.data('questionJSON');
    questionOrder = questionShowJSON.question_order;
    // increase the number and the width of the progress bar
    increaseQuestionProgress(questionOrder);
    // focus the question
    $('.enp-question__question', obj).focus();
}


/**
* Prepare the form data for submitting via AJAX
*
*/
function prepareQuestionFormData(clickedButton) {
    // add button value and name to the data since jQuery doesn't submit button value
    userAction = clickedButton.attr("name") + "=" + clickedButton.val();
    // add in a little data to let the server know the data is coming from an ajax call
    doing_ajax = 'doing_ajax=doing_ajax';
    data = $('.enp-question__form').serialize() + "&" + userAction + "&" + doing_ajax;

    // see if our question response is in there.
    // if it's the slider, we have to add in the value of the response for some reason, so we'll just add it in here for all question types.
    // Basically, if there's a jQuery slider attached to the input, the input doesn't get added when serializing the form for some reason.
    questionPattern = new RegExp("&enp-question-response=");
    if(questionPattern.test(data) !== true) {
        // the question response field isn't in there, so let's add it
        data += '&enp-question-response='+$('.enp-question__form input[name="enp-question-response"]').val();
    }

    return data;
}

function buildImageTemplate(questionJSON) {
    // get the template and add it in
    var questionImageData = {
                        'question_image_src': questionJSON.question_image_src,
                        'question_image_srcset': questionJSON.question_image_srcset,
                        'question_image_alt': questionJSON.question_image_alt,
    };
    // populate the template
    new_questionImageTemplate = questionImageTemplate(questionImageData);
    // insert it into the page
    $('#question_'+questionJSON.question_id+' .enp-question__question').after(new_questionImageTemplate);
}
