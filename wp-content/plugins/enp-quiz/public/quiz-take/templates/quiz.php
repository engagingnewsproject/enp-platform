<?php
// STARTUP
// display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-type: text/html; charset=utf-8');


// set enp-quiz-config file path (eehhhh... could be better to not use relative path stuff)
require_once '../../../../../enp-quiz-config.php';
require_once ENP_QUIZ_PLUGIN_DIR . 'public/quiz-take/class-enp_quiz-take.php';

// create the new object if it hasn't already been created
if(isset($qt) && is_object($qt)) {
    // do nothing, we already got it!
} else {
    // we need the $qt instance, so let's load it up
    // load up quiz_take class (requires all the files)
    $qt = new Enp_quiz_Take();
}
// get the quiz ID we need
if(isset($quiz_id)) {
    // do nothing, already set
} else {
    // set our quiz id
    $quiz_id = $qt->get_init_quiz_id();
}

//check to make sure one was found
if($quiz_id === false) {
    echo 'No quiz requested';
    exit;
}

// load the quiz
$qt->load_quiz($quiz_id);
// get the state
$state = $qt->get_state();
// check the state
if($state !== 'quiz_end') {
    $qt_question = new Enp_quiz_Take_Question($qt);
}
// create the quiz end object (so we have a template for it for the JS)
$qt_end = new Enp_quiz_Take_Quiz_end($qt->quiz, $qt->get_correctly_answered());


?>

<html lang="en-US">
<head>
    <?php
    // forces IE to load in Standards mode instead of Quirks mode (which messes things up) ?>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">

    <title><?php echo $qt->quiz->get_quiz_title();?></title>
    <?php
    // load meta
    $qt->meta_tags();
    // load styles
    $qt->styles();
    // IE8 conditional
    ?>

    <!--[if lt IE 9]>
	   <link rel="stylesheet" type="text/css" href="<?php echo ENP_QUIZ_PLUGIN_URL;?>public/quiz-take/css/ie8.css" />
    <![endif]-->

    <!--[if IE]>
	    <link rel="stylesheet" type="text/css" href="<?php echo ENP_QUIZ_PLUGIN_URL;?>public/quiz-take/css/ie9.css" />
    <![endif]-->

    <?php
    // echo styles
    echo $qt->load_quiz_styles();?>
</head>
<body id="enp-quiz">
<?php //add in our SVG
    echo $qt->load_svg();
?>
<div id="enp-quiz-container" class="enp-quiz__container">
    <header class="enp-quiz__header" role="banner">
        <h3 class="enp-quiz__title <?php echo 'enp-quiz__title--'. $qt->quiz->get_quiz_title_display();?>"><?php echo $qt->quiz->get_quiz_title();?></h3>
        <div class="enp-quiz__progress">
            <div class="enp-quiz__progress__bar"
                role="progressbar"
                aria-valuetext="Question <?php echo  $qt->get_current_question_number();?> of <?php echo $qt->quiz->get_total_question_count();?>"
                aria-valuemin="1"
                aria-valuenow="<?php echo  $qt->get_current_question_number();?>"
                aria-valuemax="<?php echo $qt->quiz->get_total_question_count();?>">

                <div class="enp-quiz__progress__bar__question-count"><span class="enp-quiz__progress__bar__question-count__current-number"><?php echo  $qt->get_current_question_number();?></span>/<span class="enp-quiz__progress__bar__question-count__total-questions"><?php echo $qt->quiz->get_total_question_count();?></span></div>
            </div>
        </div>
    </header>

    <?php
    // check for errors
    echo $qt->get_error_messages();?>

    <main class="enp-question__container <?php echo $qt->get_question_container_class();?>"
        role="main"
        aria-live="polite"
        aria-relevant="additions text" >
        <form id="quiz" class="enp-question__form" method="post" action="<?php echo $qt->get_quiz_form_action();?>">
            <?php echo $qt->get_session_id_input();?>
            <?php $qt->nonce->outputKey();?>
            <input type="hidden" name="enp-quiz-id" value="<? echo $qt->quiz->get_quiz_id();?>"/>
            <input type="hidden" name="enp-user-id" value="<? echo $qt->get_user_id();?>"/>
            <input type="hidden" name="enp-response-quiz-id" value="<? echo $qt->get_response_quiz_id();?>"/>
            <input id="correctly-answered" type="hidden" name="enp-quiz-correctly-answered" value="<? echo $qt->get_correctly_answered();?>"/>
            <?php
            if($state === 'question' || $state === 'question_explanation') {
                include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/question.php');
            } elseif($state === 'quiz_end') {
                include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/quiz-end.php');
            }?>

        </form>



    </main>
</div>
<?php $current_url = new Enp_quiz_Current_URL();?>
<footer id="enp-quiz-footer" class="enp-callout"><a class="enp-callout__link" href="<?php echo $current_url->get_root();?>/quiz-creator/?iframe_referral=true&amp;quiz_id=<?php echo $qt_end->quiz->get_quiz_id();?>" target="_blank">Powered by the Engaging News Project<span class="enp-screen-reader-text"> Link opens in a new window</span></a></footer>



<?php
echo $qt->get_init_json();
echo $qt_end->get_init_json();
if(isset($qt_question) && is_object($qt_question)) {
    echo $qt_question->get_init_json();
    echo $qt_question->question_js_templates();
    echo $qt_question->question_explanation_js_template();
    echo $qt_question->mc_option_js_template();
    echo $qt_question->slider_js_template();
}
echo $qt_end->quiz_end_template();
echo $qt->error_message_js_template();

// load scripts
$qt->scripts();
// if we're on prod, include GA Tracking code
if($_SERVER['HTTP_HOST'] === 'engagingnewsproject.org') { ?>
    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

      ga('create', 'UA-52471115-1', 'auto');
      ga('send', 'pageview');

    </script>
<?php } ?>

</body>
</html>
