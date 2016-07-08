/**
* Handle postMessage to set height of iframe
* 1. When the DOM is loaded, send a request to the quiz iframes to send the iframe height
* 2. Receive the message. If the request isn't what it expected, try again.
* 3. Repeat x10(?) Until get a correct response.
*/
window.addEventListener('message', receiveEnpIframeMessage, false);

// What to do when we receive a postMessage
function receiveEnpIframeMessage(event) {
    var iframe,
        iframe_id;

    if(!/dev/.test(event.origin) && !/engagingnewsproject/.test(event.origin)) {
        return false;
    }

    // make sure we got a string as our message
    if(typeof event.data !== 'string') {
        return false;
    }

    // parse the JSON data
    data = JSON.parse(event.data);
    // set the style on the height and store to localStorage
    if(/([0-9])px/.test(data.height)) {
        // get the quiz or ab_test based on ID
        // check if it's an ab test or not
        if(data.ab_test_id === "0") {
            iframe_id = 'enp-quiz-iframe-'+data.quiz_id;
        } else {
            iframe_id = 'enp-ab-test-iframe-'+data.ab_test_id;
        }
        iframe = document.getElementById(iframe_id);
        // set the height on the style
        iframe.style.height= data.height;
    }

    // send a response sayin, yea, we got it!
    // event.source.postMessage("success!", event.origin);
}

// what to do on load of an iframe
function onLoadEnpIframe() {
    // write our styles that apply to ALL quizzes
    addEnpIframeStyles();
    // call each quiz and get its height
    getEnpQuizHeights();
}

function getEnpQuizHeights() {
    // check to see if we have valid height from our PostMessage
    var quizzes = document.getElementsByClassName('enp-quiz-iframe');

    // for each quiz, send a message to that iframe so we can get its height
    for (i = 0; i < quizzes.length; ++i) {
        // get the stored iframeheight
        quiz = quizzes[i];
        // send a postMessage to get the correct height
        request = '{"status":"request","action":"sendBodyHeight"}';
        quiz.contentWindow.postMessage(request, quiz.src);
    }
}

function addEnpIframeStyles() {
    // set our styles
    var css = '.enp-quiz-iframe { -webkit-transition: all .4s ease-in-out;transition: all .4s ease-in-out; }',
    head = document.head || document.getElementsByTagName('head')[0],
    style = document.createElement('style');

    style.type = 'text/css';
    if (style.styleSheet){
      style.styleSheet.cssText = css;
    } else {
      style.appendChild(document.createTextNode(css));
    }

    head.appendChild(style);
}

// On Load with fallbacks
var alreadyrunflag=0; //flag to indicate whether target function has already been run

if (document.addEventListener) {
     document.addEventListener("DOMContentLoaded", function(){
        alreadyrunflag=1;
        onLoadEnpIframe();
    }, false);
}
// not so great check for IE
else if (document.all && !window.opera) {
    document.open();
    document.write('<script type="text/javascript" id="contentloadtag" defer="defer" src="javascript:void(0)"><\/script>');
    document.close();
    var contentloadtag=document.getElementById("contentloadtag");
    contentloadtag.onreadystatechange = function(){
        if (this.readyState=="complete"){
            alreadyrunflag=1;
            onLoadEnpIframe();
        }
    };
}

window.onload = function(){
    setTimeout(setTimeoutLoadCheck, 0);
};

function setTimeoutLoadCheck() {
    if (!alreadyrunflag) {
        onLoadEnpIframe();
    }
}
