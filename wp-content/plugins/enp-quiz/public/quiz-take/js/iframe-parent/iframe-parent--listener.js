
var enpIframes = [];

function handleEnpIframeMessage(event) {
    var parentURL,
        newIframe,
        data,
        iframeID,
        thisIframe,
        exists;

    parentURL = window.location.href;

    // quit the postmessage loop if it's NOT from a trusted site (engagingnewsproject.org or our dev sites)
    // If you want to see what it matches/doesn't match, go here: http://regexr.com/3g4rc
    if(!/https?:\/\/(?:local.quiz|(?:(?:local|dev|test)\.)?engagingnewsproject\.org|(?:engagingnews|enpdev)\.(?:staging\.)?wpengine\.com)\b/.test(event.origin)) {
        return false;
    }

    // make sure we got a string as our message
    if(typeof event.data !== 'string') {
        return false;
    }

    // parse the JSON data
    data = JSON.parse(event.data);

    // get the quiz or ab_test iframe based on ID
    // check if it's an ab test or not
    if(data.ab_test_id === "0") {
        iframeID = 'enp-quiz-iframe-'+data.quiz_id;
        abTestID = false;
    } else {
        iframeID = 'enp-ab-test-iframe-'+data.ab_test_id;
        abTestID = data.ab_test_id;
    }

    iframe = document.getElementById(iframeID);
    // if we need to use it...
    newIframe = {
        iframe: iframe,
        parentURL: parentURL,
        abTestID: abTestID,
        quizID: data.quiz_id,
    };

    // if one doesn't exist, create it
    if(enpIframes.length === 0) {

        enpIframes.push(new EnpIframeQuiz(newIframe));
        thisIframe = enpIframes[0];
        console.log(data);
        console.log(thisIframe);
    } else {
        // check if it exists
        exists = false;
        for(var enp_i = 0; enp_i < enpIframes.length; enp_i++ ) {
            if(enpIframes[enp_i].iframeID === iframe.id) {
                thisIframe = enpIframes[enp_i];
                exists = true;
            }
        }
        if(exists === false) {
            // create it!

            enpIframes.push(new EnpIframeQuiz(newIframe));
            thisIframe = enpIframes[enpIframes.length - 1];
            console.log(data);
            console.log(thisIframe);
        }
    }
    thisIframe.receiveIframeMessage(event.origin, data);
}

function enpGetFBSiteNameMeta() {
    var siteName = document.querySelector('meta[property="og:site_name"]');
    if(siteName) {
        return siteName.content;
    } else {
        return false;
    }

}

/**
* Add event listener for when our iframe sends us postmessage data to process
*/
window.addEventListener('message', handleEnpIframeMessage, false);
