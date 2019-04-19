/**
* postMessage communication with parent of the iframe
*/
// add an event listener for receiving postMessages
_.add_event('message', window, receiveMessage);
/**
* Build the json message to send to the parent
* @param action (string) required
* @param options (object) extras
*/
function buildPostMessageAction(theAction, options) {
    var message,
        messageJSON;

    message = {
                quiz_id: _.get_quiz_id(),
                ab_test_id: _.get_ab_test_id(),
                action: theAction
            };

    if((typeof options === "object") && (options !== null)) {
        // append the objects
        message = Object.assign(message, options);
    }
    messageJSON = JSON.stringify(message);
    return messageJSON;
}

/**
* Build and send the json message to send to the parent
* @param action (string) required
* @param options (object) extras
*/
function sendPostMessageAction(theAction, options) {
    json = buildPostMessageAction(theAction, options);
    // allow all domains to access this info (*)
    parent.postMessage(json, "*");
    // if you want to see what was sent
    return json;
}

/**
* Sends a postMessage to the parent container of the iframe
*/
function sendBodyHeight() {
    // calculate the height
    bodyHeight = calculateBodyHeight();
    // and send the message to the parent of the iframe
    sendPostMessageAction("setHeight", {height: bodyHeight});
}

/**
* Sends a postMessage to the parent container of the iframe
*/
function sendScrollToMessage() {
    // send the message to the parent of the iframe
    sendPostMessageAction('scrollToQuiz');
}


/**
* Function for caluting the container height of the iframe
* @return (int)
*/
function calculateBodyHeight() {
    var height = document.getElementById('enp-quiz-container').offsetHeight + document.getElementById('enp-quiz-footer').offsetHeight;

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

/**
* Send a request to the parent frame to request the URL
*/
function requestParentURL() {
    // send the message to the parent of the iframe
    sendPostMessageAction("sendURL");
}

/**
* Send a request to the parent frame to save the embed site
*/
function sendSaveSite() {
    // send the message to the parent of the iframe
    sendPostMessageAction("saveSite");
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
        } else if(data.action === 'setShareURL') {
            setShareURL(data.parentURL);
            setCalloutURL(data.parentURL);
        } else if(data.action === 'sendSaveSite') {
            sendSaveSite();
        }
    }
}


function setCalloutURL(parentURL) {
    var href,
        link;

    link = $('.enp-callout__link');
    href = link.attr('href');
    // test to see if it's alreay been appended or not
    if(/iframe_parent_url/.test(href) === false) {
        href += '&iframe_parent_url='+parentURL;
        // set the href
        link.attr('href', href);
    }

}
