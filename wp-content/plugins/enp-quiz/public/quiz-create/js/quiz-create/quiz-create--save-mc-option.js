function temp_removeMCOption(mcOptionID) {
    var mcOption;
    mcOption = $('#enp-mc-option--'+mcOptionID);
    // add keyboard focus to the next button element (either correct button or add option button)
    mcOption.next('.enp-mc-option').find('button:first').focus();
    // remove the mcOption
    mcOption.addClass('enp-mc-option--remove');
}

function removeMCOption(mcOptionID) {
    var $question
    // grab the question object
    $question = getQuestionByMCOptionID(mcOptionID)
    // actually remove the mc option
    $('#enp-mc-option--'+mcOptionID).remove();
    // reindex MC options for this question
    updateMCIndexes($question)
}

function temp_unsetRemoveMCOption(mcOptionID) {
    var mcOption;
    mcOption = $('#enp-mc-option--'+mcOptionID);
    // add keyboard focus to the next button element (either correct button or add option button)
    mcOption.removeClass('enp-mc-option--remove');
    // move focus back to mc option button delete
    $('.enp-mc-option__button--delete', mcOption).focus();
    // give them an error message
    appendMessage('Multiple Choice Option could not be deleted. Please reload the page and try again.', 'error');
}


// set MC Option as correct and unset all other mc options for that question
// this also UNSETs correct MCOption if it is already correct
function setCorrectMCOption(mcOptionID, questionID) {
    // check if it already has the enp-mc-option--correct class
    if($('#enp-mc-option--'+mcOptionID).hasClass('enp-mc-option--correct')) {
        // It's not NOT correct
        $('#enp-mc-option--'+mcOptionID).removeClass('enp-mc-option--correct');
        // nothing is right (correct)!
        return false;
    }
    // remove the correct class from the old one
    $('#enp-question--'+questionID+' .enp-mc-option').removeClass('enp-mc-option--correct');
    // add the correct class to the newly clicked one
    $('#enp-mc-option--'+mcOptionID).addClass('enp-mc-option--correct');
}

function temp_addMCOption(questionID) {
    // clone the template
    temp_mc_option = mcOptionTemplate({question_id: questionID, question_position: 'newQuestionPosition', mc_option_id: 'newMCOptionID', mc_option_position: 'newMCOptionPosition'});

    // insert it
    $('#enp-question--'+questionID+' .enp-mc-option--add').before(temp_mc_option);

    // focus it
    $('#enp-question--'+questionID+' .enp-mc-option--inputs:last .enp-mc-option__input').focus();
}

function unset_tempAddMCOption(questionID) {
    $('#enp-question--'+questionID+' #enp-mc-option--newMCOptionID').remove();
    appendMessage('Multiple Choice Option could not be added. Please reload the page and try again.', 'error');
}

// add MC option ID, question ID, question index, and mc option index
function addMCOption(new_mcOptionID, questionID) {

    new_mcOption_el = document.querySelector('#enp-question--'+questionID+' #enp-mc-option--newMCOptionID');

    // find/replace all index attributes (just in the name, but it'll search all attributes)
    findReplaceDomAttributes(new_mcOption_el, /newQuestionPosition/, getQuestionIndex(questionID));
    new_mc_option_index = $('#enp-question--'+questionID+' .enp-mc-option--inputs').length - 1;
    findReplaceDomAttributes(new_mcOption_el, /newMCOptionPosition/, new_mc_option_index);
    findReplaceDomAttributes(new_mcOption_el, /newMCOptionID/, new_mcOptionID);
}
