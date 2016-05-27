<?/**
 * Save process for ab_tests
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/includes
 *
 * Called by Enp_quiz_AB_test
 *
 * This class defines all code for processing and saving ab_tests
 *
 * @since      0.0.1
 * @package    Enp_quiz
 * @subpackage Enp_quiz/database
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Save_ab_test extends Enp_quiz_Save {
    protected $ab_test,
              $response = array('messages'=>array('error'=>array(),'success'=>array()));

    public function __construct() {

    }

    /**
    * Validate and save the posted values
    * @param $post = $_POST from server;
    * @return response (array)
    */
    public function save($post) {
        $this->set_submitted_ab_test($post);
        $if_errors = $this->check_for_errors();
        if($if_errors === false) {
            // initially looks OK
            // try saving the ab_test
            $this->save_ab_test($this->ab_test);
        }

        return $this->response;
    }

    /**
    * Get Posted values, validate them, and build our AB Test var to save.
    * Also builds response if errors.
    * @param $post = $_POST values
    * @return true or false
    */
    protected function set_submitted_ab_test($post) {

        if(isset($post['enp-ab-test-title']) && !empty($post['enp-ab-test-title'])) {
            $this->ab_test['ab_test_title'] = $post['enp-ab-test-title'];
        } else {
            $this->add_error('Please enter a Title for your AB Test.');
        }

        if(isset($post['enp-ab-test-quiz-a']) && !empty($post['enp-ab-test-quiz-a'])) {
            $this->ab_test['quiz_id_a'] = $post['enp-ab-test-quiz-a'];
        }

        if(isset($post['enp-ab-test-quiz-b']) && !empty($post['enp-ab-test-quiz-b'])) {
            $this->ab_test['quiz_id_b'] = $post['enp-ab-test-quiz-b'];
        }

        if(isset($post['ab_test_updated_by']) && !empty($post['ab_test_updated_by'])) {
            $this->ab_test['ab_test_updated_by'] = $post['ab_test_updated_by'];
            $this->ab_test['ab_test_created_by'] = $this->ab_test['ab_test_updated_by'];
            $this->ab_test['ab_test_owner'] = $this->ab_test['ab_test_updated_by'];
        } else {
            $this->add_error('There is no user set to create this AB Test.');
        }

        // set the date_time
		$date_time = date("Y-m-d H:i:s");
        $this->ab_test['ab_test_updated_at'] = $date_time;
        $this->ab_test['ab_test_created_at'] = $date_time;

    }

    protected function validate_ab_test_quizzes($ab_test) {
        $valid = false;
        // validate that both quizzes exist and are both owned by this user

        $quiz_a = new Enp_quiz_Quiz($ab_test['quiz_id_a']);
        $quiz_b = new Enp_quiz_Quiz($ab_test['quiz_id_b']);

        $quiz_a_owner = $quiz_a->get_quiz_owner();
        $quiz_b_owner = $quiz_b->get_quiz_owner();
        $owner = false;

        $quiz_a_id = $quiz_a->get_quiz_id();
        $quiz_b_id = $quiz_b->get_quiz_id();
        $different_quizzes = false;

        if((int) $quiz_a_owner === (int) $quiz_b_owner && (int) $quiz_a_owner === (int) $ab_test['ab_test_updated_by']) {
            $owner = true;
        } else {
            $this->add_error('You are not the owner of one or more of the quizzes.');
        }

        if((int) $quiz_a_id === (int) $quiz_b_id) {

            $this->add_error('Please Select two different Quizzes from the quiz dropdowns.');
        } else {
            $different_quizzes = true;
        }

        if($owner === true && $different_quizzes === true) {
            $valid = true;
        }

        return $valid;
    }

    private function save_ab_test($ab_test) {
        $valid = $this->validate_ab_test_quizzes($ab_test);
        if($valid !== true) {
            return false;
        }

        // try saving
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':ab_test_title'       => $ab_test['ab_test_title'],
                        ':quiz_id_a'           => $ab_test['quiz_id_a'],
                        ':quiz_id_b'           => $ab_test['quiz_id_b'],
                        ':ab_test_owner'       => $ab_test['ab_test_owner'],
                        ':ab_test_created_by'  => $ab_test['ab_test_created_by'],
                        ':ab_test_created_at'  => $ab_test['ab_test_created_at'],
                        ':ab_test_updated_by'  => $ab_test['ab_test_updated_by'],
                        ':ab_test_updated_at'  => $ab_test['ab_test_updated_at']
                    );
        // write our SQL statement
        $sql = "INSERT INTO ".$pdo->ab_test_table." (
                                            ab_test_title,
                                            quiz_id_a,
                                            quiz_id_b,
                                            ab_test_owner,
                                            ab_test_created_by,
                                            ab_test_created_at,
                                            ab_test_updated_by,
                                            ab_test_updated_at
                                        )
                                        VALUES(
                                            :ab_test_title,
                                            :quiz_id_a,
                                            :quiz_id_b,
                                            :ab_test_owner,
                                            :ab_test_created_by,
                                            :ab_test_created_at,
                                            :ab_test_updated_by,
                                            :ab_test_updated_at
                                        )";
        // insert the quiz into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            // set the ab test id
            $this->ab_test['ab_test_id'] = $pdo->lastInsertId();
            // build our response stuff
            $this->response['ab_test_id'] = $this->ab_test['ab_test_id'];
            $this->response['status'] = 'success';
            $this->response['action'] = 'insert';
            $this->add_success('AB Test created.');
        } else {
            $this->add_error('Quiz could not be added to the database. Try again and if it continues to not work, send us an email with details of how you got to this error.');
        }
        return $this->response;
    }

    /**
    * Add an error message to the response
    */
    protected function add_error($message) {
        $this->response['messages']['error'][] = $message;
    }

    /**
    * Add a success message to the response
    */
    protected function add_success($message) {
        $this->response['messages']['success'][] = $message;
    }

    /**
    * Check for Errors in the response
    */
    protected function check_for_errors() {
        if(empty($this->response['messages']['error'])) {
            return false;
        } else {
            return true;
        }
    }
}
