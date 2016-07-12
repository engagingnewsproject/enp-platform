/**
* Generate the Quiz End Template off of returned JSON Data and the Underscore Template
* @param quizEndJSON (JSON) data for the quiz end
* @param callback (function) to run if you want to
* @return HTML of the quiz end template with all data inserted
*/
function generateQuizEnd(quizEndJSON, callback) {
    quizEndData = {
                    score_percentage: quizEndJSON.score_percentage,
                    score_circle_dashoffset: quizEndJSON.score_circle_dashoffset,
                    quiz_end_title: quizEndJSON.quiz_end_title,
                    quiz_end_content: quizEndJSON.quiz_end_content,
    };
    qEndTemplate = quizEndTemplate(quizEndData);
    if(typeof(callback) == "function") {
        callback(explanation);
    }
    return qEndTemplate;
}

// function for our timeout to animate the svg percentage correct
function animateScore() {
    $('#enp-results__score__circle__path').attr('class', 'enp-results__score__circle__setOffset');
}

// replace share URLs if embedded with the parent URL
// so we pass traffic back to them
function setShareURL(parentURL) {
    // get the existing url of the iframe
    var iframeURL = window.location.href;

    // check if we're at quiz end or not
    if($('.enp-results__share__link').length) {
        // on a reload at quiz_end, so inject the links
        setShareURLLinks(iframeURL, parentURL);
    } else {
        // not at quiz_end, inject it into the quiz_end template
        setShareURLTemplate(iframeURL, parentURL);
    }



}

function setShareURLLinks(iframeURL, parentURL) {
    $('.enp-results__share__link').each(function() {
        var href = $(this).attr('href');
        var newHref = _.replaceURLs(href, iframeURL, parentURL);
        // set the url again
        $(this).attr('href', newHref);
    });
}

function setShareURLLinkTwitter(iframeURL, parentURL) {
    // if the loaded state was quiz end
    var twitterLink = $('.enp-results__share__item--twitter');
    // we're at the quiz end
    var twitterURL = $('.enp-results__share__item--twitter').attr('href');
    var newTwitterURL = twitterURL.replace(iframeURL, parentURL);
    // set the new href
    $('.enp-results__share__item--twitter').attr('href', newTwitterURL);
}

function setShareURLTemplate(iframeURL, parentURL) {
    var qeTemplate = $('#quiz_end_template');
    // regex string replace for our iframeURL
    var qeTemplateContent = qeTemplate.text();
    // replace all the urls, encodedURLs, and rawUrlEncoded
    var newQuizEndTemplateContent = _.replaceURLs(qeTemplateContent, iframeURL, parentURL);

    // set the content
    qeTemplate.text(newQuizEndTemplateContent);
    // override the existing template variable with the new Underscore template
    // WARNING! This is a global variable
    quizEndTemplate = _.template(qeTemplate.html());
}
