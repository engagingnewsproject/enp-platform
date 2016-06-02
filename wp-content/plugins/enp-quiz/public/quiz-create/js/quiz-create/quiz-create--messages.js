// append ajax response message
function appendMessage(message, status) {
    var messageID = Math.floor((Math.random() * 1000) + 1);
    $('.enp-quiz-message-ajax-container').append('<div class="enp-quiz-message enp-quiz-message--ajax enp-quiz-message--'+status+' enp-container enp-message-'+messageID+'"><p class="enp-message__list enp-message__list--'+status+'">'+message+'</p></div>');

    $('.enp-message-'+messageID).delay(3500).fadeOut(function(){
        $('.enp-message-'+messageID).fadeOut();
    });
}

// Loop through messages and display them
// Show success messages
function displayMessages(message) {
    // loop through success messages
    //for(var success_i = 0; success_i < message.success.length; success_i++) {
        if(typeof message.success !== 'undefined' && message.success.length > 0) {
            // append our new success message
            appendMessage('Quiz Saved.', 'success');
        }
    //}

    // Show error messages
    for(var error_i = 0; error_i < message.error.length; error_i++) {
        appendMessage(message.error[error_i], 'error');
    }
}


function destroySuccessMessages() {
    $('.enp-quiz-message--success').remove();
}
