function enp_splitTest(quiz1, quiz2, siteURL) {
    // get our div to write to
    iframeID1 = quiz1.guid.substring(0, 8);
    iframeID2 = quiz2.guid.substring(0, 8);
    iframeID = iframeID1+'-'+iframeID2;
    var iframeContainer = document.getElementById("iframe-container-"+iframeID);

    var quizzes = [quiz1, quiz2]; // create an array of the objects
    var rand = Math.floor(Math.random() * 2); // returns 0 or 1

    // we can't use document.write because we get this error:
    // Failed to execute 'write' on 'Document': It isn't possible to write into a document from an asynchronously-loaded external script unless it is explicitly opened.
    iframeContainer.innerHTML = '<iframe frameBorder="0" height="'+quizzes[rand].height+'" width="'+quizzes[rand].width+'" src="'+siteURL+'/iframe-quiz/?guid='+quizzes[rand].guid+'"></iframe>';
}
