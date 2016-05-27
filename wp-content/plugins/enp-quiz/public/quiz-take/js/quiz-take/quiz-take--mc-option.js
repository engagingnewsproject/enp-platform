/**
* Find all the mc options in a container and tell us which is the correct one
* @param container (obj) wrapper for the inputs to search
* @param callback (function) Something to do with the correct one found
* @return input object that is correct
*/
function locateCorrectMCOption(container, callback) {
    var correct;
    $('.enp-option__input', container).each(function(e, obj) {
        if($(this).data('correct') === '1') {
            correct =  $(this);
            if(typeof(callback) == "function") {
                callback($(this));
            }
            return false;
        }
    });
    return correct;
}


/**
* Find all the mc options in a container and tell us which are incorrect
* @param container (obj) wrapper for the inputs to search
* @param callback (function) Something to do with the incorrect one found
* @return array of MC Input objects that are incorrect
*/
function locateIncorrectMCOptions(container, callback) {
    var incorrect;
    $('.enp-option__input', container).each(function(e, obj) {
        if($(this).data('correct') === '0') {
            incorrect =  $(this);
            if(typeof(callback) == "function") {
                callback($(this));
            }
        }
    });
    return incorrect;
}

/**
* Remove the MC Option from view by adding a class
* @param obj (jQuery object)
*/
function removeMCOption(obj) {
    if(!obj.hasClass('enp-option__input--incorrect-clicked')) {
        obj.addClass('enp-option__input--slide-hide');
    }
}

/**
* Highlight the correct MC Option by adding a class
* @param obj (jQuery object)
*/
function showCorrectMCOption(obj) {
    obj.addClass('enp-option__input--correct');
}

/**
* Attach data to the MC Options that lets us know if the
* mc option is correct or incorrect
* @param questionJSON (JSON String) Question level JSON
*/
function bindMCOptionData(questionJSON) {
    for(var prop in questionJSON.mc_option) {
        mc_option_id = questionJSON.mc_option[prop].mc_option_id;
        mc_option_correct = questionJSON.mc_option[prop].mc_option_correct;
        $('#enp-option__'+mc_option_id).data('correct', mc_option_correct);
    }
}

function buildMCOptions(questionJSON) {
    // generate mc option templates
    questionJSON.mc_option = _.shuffle(questionJSON.mc_option);
    for(var prop in questionJSON.mc_option) {
        mc_option_id = questionJSON.mc_option[prop].mc_option_id;
        mc_option_content = questionJSON.mc_option[prop].mc_option_content;
        mcOptionData = {
                        'mc_option_id': mc_option_id,
                        'mc_option_content': mc_option_content
        };

        // generate the template
        new_mcOption = mcOptionTemplate(mcOptionData);
        // insert it into the page
        $('#question_'+questionJSON.question_id+' .enp-question__submit').before(new_mcOption);
    }

    // append the data to the mc options
    bindMCOptionData(questionJSON);
}

function processMCSubmit() {
    // find the selected mc option input
    var selectedMCInput = $('.enp-option__input:checked');
    // see if the input is correct or incorrect
    var correct = selectedMCInput.data('correct');

    // check if it's correct or not
    if(correct === '1') {
        correct_string = 'correct';
        // it's right! add the correct class to the input
        selectedMCInput.addClass('enp-option__input--correct-clicked');
        // add the class thta highlights the correct option
        showCorrectMCOption(selectedMCInput);
    } else {
        // it's wrong :( :( :(
        correct_string = 'incorrect';
        // add incorrect clicked class so it remains in view, but is highlighted as the one they clicked
        selectedMCInput.addClass('enp-option__input--incorrect-clicked');
        // highlight the correct option
        correctInput = locateCorrectMCOption($('.enp-question__fieldset'), showCorrectMCOption);
    }
    // remove all the ones that are incorrect that DON'T Have incorrect-clicked on them
    locateIncorrectMCOptions($('.enp-question__fieldset'), removeMCOption);

    return correct_string;
}
