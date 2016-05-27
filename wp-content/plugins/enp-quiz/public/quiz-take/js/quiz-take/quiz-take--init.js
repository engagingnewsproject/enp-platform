// on load, bind the initial question_json to the question id
// check if the init_question_json variable exists
if(typeof init_question_json !== 'undefined') {
    bindQuestionData(init_question_json);
    // on load, bind the initial question_json to the mc options, if it's an mc option question
    if(init_question_json.question_type === 'mc') {
        bindMCOptionData(init_question_json);
    } else if (init_question_json.question_type === 'slider') {
        // on load, bind and create slider if it's a slider
        bindSliderData(init_question_json);
    }



}

// on load, bind the quiz data to the quiz DOM
bindQuizData(quiz_json);

/**
* Binds JSON data to the quiz form element in the DOM so we always have
* access to it. Accessible via
* $('#quiz').data('quizJSON');
*/
function bindQuizData(quizJSON) {
    $('#quiz').data('quizJSON', quizJSON);
}

// send the Body Height, even if they're not ready for it.
// The parent page will request the body height once its loaded.
// This should cover either scenario.
sendBodyHeight();
// after images are loaded, send the height again,
// regardless if it's been sent or not so we know for sure that
// the height is correct
$('.enp-question-image').load(function() {
    // image loaded
    // console.log('image loaded');
    sendBodyHeight();
});
