<?php
// STARTUP
// display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// set enp-quiz-config file path (eehhhh... could be better to not use relative path stuff)
require_once '../../../../../enp-quiz-config.php';
require_once ENP_QUIZ_PLUGIN_DIR . 'public/quiz-take/class-enp_quiz-take.php';
require_once ENP_QUIZ_PLUGIN_DIR . 'public/quiz-take/includes/class-enp_quiz-take_ab_test.php';



if(isset($_GET['ab_test_id'])) {

    $qt = new Enp_quiz_Take();
    // get the ab_test_id from the URL
    $ab_test_id = $_GET['ab_test_id'];
    $ab_test = new Enp_quiz_Take_AB_test($ab_test_id);

    // get quiz_id selected by the $ab_test object
    $quiz_id = $ab_test->get_ab_test_quiz_id();
    // set the
    $qt->set_ab_test_id($ab_test_id);
    // open a new quiz via quiz.php
    include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/quiz.php');
} else {
    echo 'No quiz requested';
    exit;
}
