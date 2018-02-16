function CmeIframeTree(data) {

    this.iframe = data.iframe
    this.iframeID = data.iframe.id
    this.treeID = data.treeID

    // load it!
    this.onLoadIframe()
}

// getters and setters

CmeIframeTree.prototype = {
    constructor: CmeIframeTree,

    getSiteName: function() {
        let siteName = this.getFBSiteNameMeta()
        // see if there's a Facebook OG:SiteName attribute, if not, return the current URL
        if(!siteName) {
            siteName = document.title
        } else {
            siteName = ''
        }
        return siteName
    },

    getFBSiteNameMeta: function() {
        let siteName = document.querySelector('meta[property="og:site_name"]');
        if(siteName) {
            return siteName.content;
        } else {
            return false;
        }
    },

    // What to do when we receive a postMessage
    receiveIframeMessage: function(origin, data) {
        var response;
        response = {};
        console.log('parent recieved message', data)

        // find out what we need to do with it
        if(data.action === 'treeHeight') {
            response.setTreeHeight = this.setTreeHeight(data.treeHeight);
        }
        // anytime there's a state update, scroll to the quiz, but not on load
        else if(data.action === 'update' && data.data.updatedBy !== 'forceCurrentState') {
             response.scrollToQuizResponse = this.scrollToQuiz();
        }
        else if(data.action === 'getParentSiteName') {
            this.sendSiteName();
        } else if(data.action === 'getParentPath') {
            this.sendPath();
        } else if(data.action === 'getParentHost') {
            this.sendHost();
        }

        // send a response sayin, yea, we got it!
        // event.source.postMessage("success!", event.origin);
        return response;
    },

    // what to do on load of an iframe
    onLoadIframe: function() {
        // write our styles that apply to ALL quizzes
        this.addIframeStyles();
        // call the quiz and get its height
        this.getQuizHeight();
        // call the tree and send the host the page its embedded on
        this.sendHost();
        // call the tree and send the host the page its embedded on
        this.sendPath();
    },

    getQuizHeight: function() {
        var request;
        // send a postMessage to get the correct height
        request = '{"status":"request","action":"sendBodyHeight"}';
        this.iframe.contentWindow.postMessage(request, this.iframe.src);
    },

    /**
    * Send location.host of the current page (the parent page)
    */
    sendSiteName: function() {
        var request;

        // send a postMessage to get the correct height
        request = '{"action":"setParentSiteName","siteName":"'+this.getSiteName()+'"}';
        this.iframe.contentWindow.postMessage(request, this.iframe.src);
    },

    /**
    * Send location.path of the current page (the parent page)
    */
    sendPath: function() {
        var request;

        // send a postMessage to get the correct height
        request = '{"action":"setParentPath","path":"'+window.self.location.pathname+'"}';
        this.iframe.contentWindow.postMessage(request, this.iframe.src);
    },

    /**
    * Send location.host of the current page (the parent page)
    */
    sendHost: function() {
        var request;

        // send a postMessage to get the correct height
        request = '{"action":"setParentHost","host":"'+window.self.location.host+'"}';
        this.iframe.contentWindow.postMessage(request, this.iframe.src);
    },


    /**
    * Sets the height of the iframe on the page
    */
    setTreeHeight: function(height) {
        console.log(height)
        // set the height on the style
        this.iframe.style.height = height+'px';
        return height;
    },

    /**
    * Snaps the quiz to the top of the viewport, if needed
    */
    scrollToQuiz: function() {
        var response = false;
        // this will get the current quiz distance from the top of the viewport
        var distanceFromTopOfViewport = this.iframe.getBoundingClientRect().top;
        // see if we're within -20px and 100px of the question (negative numbers means we've scrolled PAST (down) the quiz)
        if( -20 < distanceFromTopOfViewport && distanceFromTopOfViewport < 100) {
            // Question likely within viewport. Do not scroll.
            response = 'noScroll';
        } else {
            // let's scroll them to the top of the next question (some browsers like iPhone Safari jump them way down the page)
            scrollBy(0, (distanceFromTopOfViewport - 10));
            response = 'scrolledTop';
        }
        return response;
    },

    addIframeStyles: function() {
        // set our styles
        var css = '.cme-tree__iframe { -webkit-transition: all .3s ease-in-out;transition: all .3s ease-in-out; }',
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
}
