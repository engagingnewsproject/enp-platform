<?php
// STARTUP
// display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if(isset($_GET['ab_test_id'])) {
    // set enp-quiz-config file path (eehhhh... could be better to not use relative path stuff)
    require_once '../../../../../enp-quiz-config.php';
    require_once ENP_QUIZ_PLUGIN_DIR . 'public/quiz-take/class-enp_quiz-take.php';
    $qt = new Enp_quiz_Take();
    // get the ab_test_id from the URL
    $ab_test_id = $_GET['ab_test_id'];
    // check if there's a cookie set or not
    $ab_quiz_id_cookie_name = 'enp_ab_quiz_id';
    if(isset($_COOKIE[$ab_quiz_id_cookie_name])) {
        // get the quiz_id
        $quiz_id = $_COOKIE[$ab_quiz_id_cookie_name];
    } else {
        // randomize which Quiz to send them to
        $ab_test = new Enp_quiz_AB_test($ab_test_id);
        $id_a = $ab_test->get_quiz_id_a();
        $id_b = $ab_test->get_quiz_id_b();
        $quizzes = array($id_a, $id_b);
        // select a random one
        $rand = array_rand($quizzes);
        $quiz_id = $quizzes[$rand];
        // set a cookie that they've taken this AB Test
        $twentythirtyeight = 2147483647;
        $cookie_path = parse_url(ENP_TAKE_AB_TEST_URL, PHP_URL_PATH).$ab_test_id;
        setcookie($ab_quiz_id_cookie_name, $quiz_id, $twentythirtyeight, $cookie_path);
    }

    $qt->set_ab_test_id($ab_test_id);
    // open a new quiz via quiz.php
    include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/quiz.php');
} else {
    echo 'No quiz requested';
    exit;
}
