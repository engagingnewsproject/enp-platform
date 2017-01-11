/**
* Generate the Question Explanation off of JSON Data and the Underscore Template
* @param questionJSON
* @param correct (string) 'incorrect' or 'correct'
* @param callback (function) to run if you want to
* @return HTML of the explanation template with all data inserted
*/
function generateQuestionExplanation(questionJSON, correct, callback) {
    // check to make sure the Question Explanation hasn't already been generated
    if(0 < $('#enp-explanation_'+questionJSON.question_id).length) {
        return false;
    }


    if(_.is_last_question(questionJSON) === true) {
        question_next_step_text = 'View Results';
    } else {
        question_next_step_text = 'Next Question';
    }
    var question_response_percentage = questionJSON['question_responses_'+correct+'_percentage'];
    question_response_percentage = _.reformat_number(question_response_percentage, 100);
    explanationTemplate = questionExplanationTemplate({
                            question_id: questionJSON.question_id,
                            question_explanation: questionJSON.question_explanation,
                            question_explanation_title: correct,
                            question_explanation_percentage: question_response_percentage,
                            question_next_step_text: question_next_step_text
                        });
    if(typeof(callback) == "function") {
        callback(explanationTemplate);
    }
    return explanationTemplate;
}

/**
* Click on Next Question / Quiz End button
* 1. Prep the form values
* 2. Show the next question or quiz end template
* 3. Submit the form (so we can register a new page view/change the state of the quiz, etc)
*/
$(document).on('click', '.enp-next-step', function(e){
    e.preventDefault();
    url = $('.enp-question__form').attr('action');
    // prepare the data to submit
    data = prepareQuestionFormData($(this));

    $('.enp-question__answered').addClass('enp-question--remove');
    $('.enp-question__container').removeClass('enp-question__container--explanation').addClass('enp-question__container--unanswered');

    // bring in the next question/quiz end, it it's there
    if($('.enp-question--on-deck').length) {
        nextQuestion = $('.enp-question--on-deck');
        // add the classes for the next question
        showNextQuestion(nextQuestion);
    }


    // submit the form
    $.ajax( {
        type: 'POST',
        url  : url,
        data : data,
        dataType : 'json',
    } )
    // success
    .done( questionExplanationSubmitSuccess )
    .fail( function( jqXHR, textStatus, errorThrown ) {
        console.log( 'AJAX failed', jqXHR.getAllResponseHeaders(), textStatus, errorThrown );
    } )
    .then( function( errorThrown, textStatus, jqXHR ) {

    } )
    .always(function() {

    });
});

/**
* On successful AJAX submit, either set-up the Next, Next question,
* or set-up the Quiz End state.
*
*/
function questionExplanationSubmitSuccess( response, textStatus, jqXHR ) {
    var responseJSON = $.parseJSON(jqXHR.responseText);

    // see if there are any errors
    if(responseJSON.error.length) {
        _.handle_error_message(responseJSON.error[0]);
    }

    if(responseJSON.state === 'quiz_end') {

        // see if there's a next question
        qEndTemplate = generateQuizEnd(responseJSON.quiz_end);

        $('.enp-question__form').append(qEndTemplate);
        $('.enp-results').addClass('enp-question--on-deck').addClass('enp-question--show').removeClass('enp-question--on-deck');
        // make progress bar the full width
        $('.enp-quiz__progress__bar').css('width', '100%');

        // Append the text "Correct" to the number correct/incorrect
        $('.enp-quiz__progress__bar__question-count__total-questions').append(' Correct');
        // Change the first number to the amount they got correct
        $('.enp-quiz__progress__bar__question-count__current-number').text(responseJSON.quiz_end.correctly_answered);
        // change the ARIA progress bar description
        $('.enp-quiz__progress__bar').attr('aria-valuetext', $('.enp-quiz__progress__bar__question-count').text());

        // add the resetOffset to take it to 0%
        $('#enp-results__score__circle__path').attr('class', 'enp-results__score__circle__resetOffset');
        // add the animateScore after a slight delay so the animation comes in
        animateScoreID = window.setTimeout(animateScore, 250);

    } else if(responseJSON.state === 'question') {
        // check if we already have a question to show
        if(!$('.enp-question--show').length) {
            // if we don't, then we're in a state where the quiz
            // was reloaded when on the question explanation state, so we don't
            // have an on deck question. we need to generate it and insert it now
            generateQuestion(responseJSON.next_question);
            // get the question we just inserted
            nextQuestion = $('.enp-question--on-deck');
            // add the classes for the next question
            showNextQuestion(nextQuestion);
        }

    }

    // remove the question that was answered
    $('.enp-question__answered').remove();

    // send the height of the new view
    sendBodyHeight();
    // scroll to top of next question
    sendScrollToMessage();
}
