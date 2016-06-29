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

function updateOGTags(quizEndJSON) {
    ogTitle = facebookTitleEndTemplate({score_percentage: quizEndJSON.score_percentage});
    ogDescription = facebookDescriptionEndTemplate({score_percentage: quizEndJSON.score_percentage});

    $("meta[property='og:title']").attr('content', ogTitle);
    $("meta[property='og:description']").attr('content', ogDescription);
}

// function for our timeout to animate the svg percentage correct
function animateScore() {
    $('#enp-results__score__circle__path').attr('class', 'enp-results__score__circle__setOffset');
}
