
var cmeTreeIframes = [];

function handleCmeIframeMessage(event) {
    var parentURL,
        newIframe,
        data,
        iframeID,
        iframe,
        thisIframe,
        exists;

    parentURL = window.location.href;

    // quit the postmessage loop if it's NOT from a trusted site (engagingnewsproject.org or our dev sites)
    // If you want to see what it matches/doesn't match, go here: http://regexr.com/3g4rc
    if(!/https?:\/\/(?:dev|localhost:3000|tree\.mediaengagement\.org|enptree(\.staging)?\.wpengine\.com)\b/.test(event.origin)) {
        console.error('Domain not allowed.', event.origin)
        return false;
    }

    // make sure we got a string as our message
    if(typeof event.data !== 'string') {
        console.error('Data is not a string.')
        return false;
    }

    // parse the JSON data
    data = JSON.parse(event.data);

    iframeID = 'cme-tree__'+data.tree_id;

    iframe = document.getElementById(iframeID);
    // if we need to use it...
    newIframe = {
        iframe: iframe,
        parentURL: parentURL,
        treeID: data.tree_id
    };

    // if one doesn't exist, create it
    if(cmeTreeIframes.length === 0) {

        cmeTreeIframes.push(new CmeIframeTree(newIframe));
        thisIframe = cmeTreeIframes[0];
    } else {
        // check if it exists
        exists = false;
        for(var cme_i = 0; cme_i < cmeTreeIframes.length; cme_i++ ) {
            if(cmeTreeIframes[cme_i].iframeID === iframe.id) {
                thisIframe = cmeTreeIframes[cme_i];
                exists = true;
            }
        }
        if(exists === false) {
            // create it!
            cmeTreeIframes.push(new CmeIframeTree(newIframe));
            thisIframe = cmeTreeIframes[cmeTreeIframes.length - 1];
        }
    }
    thisIframe.receiveIframeMessage(event.origin, data);
}

/**
* Add event listener for when our iframe sends us postmessage data to process
*/
window.addEventListener('message', handleCmeIframeMessage, false);

/**
* Try to get any quizzes that might not have been loaded.
* When a quiz is loaded, it sends a request to the parent, but the parent might not be loaded yet. So, when our parent is loaded, let's also try to create our iframes
*/
document.onreadystatechange = function () {
    let trees,
        request;

    if (document.readyState === "complete") {
        // request load from quizzes
        trees = document.getElementsByClassName('cme-tree__iframe');
        // for each quiz, send a message to that iframe so we can get its height
        for (var i = 0; i < trees.length; ++i) {
            // get the stored iframeheight
            // send a postMessage to get the correct height (and kick off the proces to grab all the iframes)
            request = '{"status":"request","action":"init"}';
            console.log('sending init', request)
            trees[i].contentWindow.postMessage(request, trees[i].src);
        }
    }
};
