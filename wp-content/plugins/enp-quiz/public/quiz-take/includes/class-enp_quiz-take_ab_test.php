<?php

/**
* A class for deciding how to move forward when an ab test gets loaded.
* Mostly, picking which quiz_id should get loaded.
*/
class Enp_quiz_Take_AB_test {
    public $ab_test_id,
           $ab_test_quiz_id; // which quiz_id was chosen?

    public function __construct($ab_test_id = false) {
        if((int) $ab_test_id <= 0) {
            return 'invalid AB Test ID';
        }

        $this->set_ab_test_id($ab_test_id);
        $this->set_ab_test_quiz_id($this->ab_test_id);

    }
    /**
    * Set the ab_test_id
    */
    public function set_ab_test_id($ab_test_id) {
        $this->ab_test_id = $ab_test_id;
    }

    /**
    * Find out which quiz_id should get loaded
    * @return quiz_id
    */
    public function set_ab_test_quiz_id($ab_test_id) {
        // first check for a posted value from the quiz form
        if(isset($_POST['enp-quiz-id'])) {
            $quiz_id = $_POST['enp-quiz-id'];
        }
        // try getting it from a cookie if it's a reload
        elseif(isset($_COOKIE['enp_ab_quiz_id'])) {
            // get the quiz_id
            $quiz_id = $_COOKIE['enp_ab_quiz_id'];
        }
        // no values, so start it over
        else {
            // randomize which Quiz to send them to
            $quiz_id = $this->random_ab_test_quiz_selector($ab_test_id);
            // set a cookie that they've taken this AB Test
            $twentythirtyeight = 2147483647;
            $cookie_path = parse_url(ENP_TAKE_AB_TEST_URL, PHP_URL_PATH).$ab_test_id;
            setcookie('enp_ab_quiz_id', $quiz_id, $twentythirtyeight, $cookie_path);
        }
        // set the value
        $this->ab_test_quiz_id = $quiz_id;
    }

    public function get_ab_test_id() {
        return $this->ab_test_id;
    }

    public function get_ab_test_quiz_id() {
        return $this->ab_test_quiz_id;
    }

    /**
    * Randomly select a quiz_id from the ab_test
    * @return quiz_id
    */
    public function random_ab_test_quiz_selector($ab_test_id) {
        // get the ab_test_id class loaded
        $ab_test = new Enp_quiz_AB_test($ab_test_id);
        // get the quiz ids
        $id_a = $ab_test->get_quiz_id_a();
        $id_b = $ab_test->get_quiz_id_b();
        // put the quiz ids in an array
        $quizzes = array($id_a, $id_b);
        // select a random one
        $rand = array_rand($quizzes);
        $quiz_id = $quizzes[$rand];
        // return the selected quiz_id
        return $quiz_id;
    }
}
?>
