/**
* postMessage communication with parent of the iframe
*/
// add an event listener for receiving postMessages
_.add_event('message', window, receiveMessage);

/**
* Sends a postMessage to the parent container of the iframe
*/
function sendBodyHeight() {
    // calculate the height
    height = calculateBodyHeight();
    console.log('sending body height of '+height);
    // allow all domains to access this info (*)
    // and send the message to the parent of the iframe
    json = '{"quiz_id":"'+_.get_quiz_id()+'","ab_test_id":"'+_.get_ab_test_id()+'","height":"'+height+'"}';
    parent.postMessage(json, "*");
}
/**
* Function for caluting the container height of the iframe
* @return (int)
*/
function calculateBodyHeight() {
    var height = document.getElementById('enp-quiz-container').offsetHeight;

    // calculate the height of the slide-hide mc elements, if there
    if($('.enp-option__input--slide-hide').length) {
        var removedMC = 0;
        $('.enp-option__input--slide-hide').each(function(){
            var label = $(this).next('.enp-option__label');
            removedMC = removedMC + label.outerHeight(true);
        });
        // subtract the height of the removedMC options from the total height
        height = height - removedMC;
    }

    // return the height
    return height + "px";
}

function receiveMessage(event) {
    // check to make sure we received a string
    if(typeof event.data !== 'string') {
        return false;
    }
    // check if valid JSON
    data = _.is_json_string(event.data);

    // see what they want to do
    if(data.status === 'request') {
        // they want us to send something... what do they want to send?
        // if they want the bodyHeight, then send the bodyHeight!
        if(data.action === 'sendBodyHeight') {
            sendBodyHeight();
        }
    }

}
