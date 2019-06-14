// UTILITY
/**
* get a string or decimal integer and return a formatted decimal number
* @param places (int) how many decimal places you want to leave in Defaults to 0.
*/
_.reformat_number = function(number, multiplier, places) {
    if(multiplier === "") {
        multiplier = 1;
    }
    if(places === "") {
        places = 0;
    }
    number = number * multiplier;
    number = number.toFixed(places);
    return number;
};

/**
* Determine if we're on the last question or not
*/
_.is_last_question = function(questionJSON) {
    questionNumber = parseInt(questionJSON.question_order) + 1;
    totalQuestions = _.get_total_questions();
    if(questionNumber === totalQuestions) {
        return true;
    } else {
        return false;
    }
};

_.get_total_questions = function() {
    quizJSON = $('#quiz').data('quizJSON');
    return quizJSON.questions.length;
};

_.is_json_string = function(str) {
    try {
        json = JSON.parse(str);
    } catch (e) {
        return false;
    }
    return json;
};

_.get_quiz_id = function() {
    json = $('#quiz').data('quizJSON');
    return json.quiz_id;
};

_.get_ab_test_id = function() {
    return ab_test_id_json.ab_test_id;
};

_.handle_error_message = function(error) {
    errorMessage = errorMessageTemplate({'error': error});
    // remove the question container
    $('.enp-question__fieldset').remove();
    // add an error class to the container
    $('.enp-question__container').addClass('enp-question__container--error');
    // insert it into the page
    $('.enp-question__container').prepend(errorMessage);
    // focus the error message
    $('.enp-quiz-message--error a, .enp-quiz-message--error button').focus();
};

/**
* use to add event listeners with IE fallback
* from http://stackoverflow.com/questions/6927637/addeventlistener-in-internet-explorer
*/
_.add_event = function(evnt, elem, func) {
   if (elem.addEventListener)  // W3C DOM
      elem.addEventListener(evnt,func,false);
   else if (elem.attachEvent) { // IE DOM
      elem.attachEvent("on"+evnt, func);
   }
   else { // No much to do
      elem[evnt] = func;
   }
};

// mimic PHP's rawurlencode from
// http://locutus.io/php/url/rawurlencode/
_.rawurlencode = function(str) {
    str = (str + '');
    // Tilde should be allowed unescaped in future versions of PHP (as reflected below),
    // but if you want to reflect current
    // PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
    return encodeURIComponent(str)
    .replace(/!/g, '%21')
    .replace(/'/g, '%27')
    .replace(/\(/g, '%28')
    .replace(/\)/g, '%29')
    .replace(/\*/g, '%2A');
};


_.replaceURLs = function(str, oldURL, newURL) {
    return str.replace(oldURL, newURL)
              .replace(encodeURIComponent(oldURL), encodeURIComponent(newURL))
              .replace(_.rawurlencode(oldURL), _.rawurlencode(newURL));
};
