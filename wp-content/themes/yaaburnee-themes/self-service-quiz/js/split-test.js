function enp_splitTest(quiz1, quiz2) {
    var quizzes = [quiz1, quiz2]; // create an array of the objects
    var rand = Math.floor(Math.random() * 2); // returns 0 or 1
    document.write('<iframe frameBorder="0" height="'+quizzes[rand].height+'" width="'+quizzes[rand].width+'" src="http://dev/enp/iframe-quiz/?guid='+quizzes[rand].guid+'"></iframe>');
}
