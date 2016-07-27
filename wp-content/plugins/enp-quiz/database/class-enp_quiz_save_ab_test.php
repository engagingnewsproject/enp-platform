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
              $response = array('message'=>array('error'=>array(),'success'=>array()));

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
            $this->insert_ab_test($this->ab_test);
        }

        return $this->response;
    }

    /**
    * @param $post (array('ab_test_id', 'ab_test_updated_by')) submitted values from ab test delete form
    * @return response (array)
    */
    public function delete($post) {
        // set the ID
        $this->ab_test['ab_test_id'] = $post['ab_test_id'];
        // get the AB Test
        $ab_test = new Enp_quiz_AB_test($post['ab_test_id']);

        // check if the user who submitted the form is valid
        if((int) $post['ab_test_updated_by'] !== (int) $ab_test->get_ab_test_owner()) {
            // owner isn't valid
            $this->add_error('You are not the owner of this AB Test.');
        }

        // we need to include these for the response
        $this->ab_test['quiz_id_a'] = $ab_test->get_quiz_id_a();
        $this->ab_test['quiz_id_b'] = $ab_test->get_quiz_id_b();

        $this->ab_test['ab_test_updated_by'] = $post['ab_test_updated_by'];
        // set the updated at time
        $date_time = date("Y-m-d H:i:s");
        $this->ab_test['ab_test_updated_at'] = $date_time;
        $this->ab_test['ab_test_is_deleted'] = '1';

        $if_errors = $this->check_for_errors();
        if($if_errors === false) {
            // actually delete it
            $this->delete_ab_test($this->ab_test);
        }

        // return the response
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

    /**
    * Check if the IDs of all possible owners match on the quizzes
    * and person creating/who created the AB Test
    * @param $ab_test_owner_id (int/string) ID of the person creating/who created the ab_test
    * @param $quiz_id_a (int/string) ID of Quiz A
    * @param $quiz_id_b (int/string) ID of Quiz B
    * @return $valid (BOOLEAN) true if OK, false if fails
    */
    protected function validate_ab_test_owner($ab_test_owner_id, $quiz_id_a, $quiz_id_b) {
        $valid = false;

        $quiz_a = new Enp_quiz_Quiz($quiz_id_a);
        $quiz_b = new Enp_quiz_Quiz($quiz_id_b);

        $quiz_a_owner = $quiz_a->get_quiz_owner();
        $quiz_b_owner = $quiz_b->get_quiz_owner();

        if((int) $quiz_a_owner === (int) $quiz_b_owner && (int) $quiz_a_owner === (int) $ab_test_owner_id) {
            $valid = true;
        } else {
            $this->add_error('You are not the owner of one or more of the quizzes.');
        }

        return $valid;
    }


    /**
    * We need to make sure two different quizzes were selected to AB Test
    *
    * @param $quiz_id_a (int/string) ID of Quiz A
    * @param $quiz_id_b (int/string) ID of Quiz B
    * @return $valid (BOOLEAN) true if OK, false if fails
    */
    public function validate_different_quiz_ids($quiz_id_a, $quiz_id_b) {
        $valid = false;

        if((int) $quiz_id_a === (int) $quiz_id_b) {
            $this->add_error('Please Select two different Quizzes from the quiz dropdowns.');
        } else {
            $valid = true;
        }
        return $valid;
    }

    /**
    * Validate the submitted content from the AB Test create form
    * @param $ab_test = form submission from create AB Test page
    * @return $valid (BOOLEAN) true if OK, false if fails
    */
    protected function validate_submitted_ab_test_quizzes($ab_test) {
        $valid = false;

        $quiz_id_a = $ab_test['quiz_id_a'];
        $quiz_id_b = $ab_test['quiz_id_b'];

        // validate that both quizzes exist and are both owned by this user
        $owner = $this->validate_ab_test_owner($ab_test['ab_test_updated_by'], $quiz_id_a, $quiz_id_b);

        // validate that the quizzes are different quizzes
        $different_quizzes = $this->validate_different_quiz_ids($quiz_id_a, $quiz_id_b);

        // see if everything passed validation
        if($owner === true && $different_quizzes === true) {
            $valid = true;
        }

        return $valid;
    }


    /**
    * Insert a new AB Test into the database
    * @param $ab_test (array) full info on data to insert a new AB Test
    * @return response (array)
    */
    private function insert_ab_test($ab_test) {
        $valid = $this->validate_submitted_ab_test_quizzes($ab_test);
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

        $this->response['action'] = 'insert';
        // success!
        if($stmt !== false) {
            // set the ab test id
            $this->ab_test['ab_test_id'] = $pdo->lastInsertId();
            // build our response stuff
            $this->response['ab_test_id'] = $this->ab_test['ab_test_id'];
            $this->response['status'] = 'success';
            $this->add_success('AB Test created.');
        } else {
            $this->response['status'] = 'error';
            $this->response['ab_test_id'] = 0;
            $this->add_error('Quiz could not be added to the database. Try again and if it continues to not work, send us an email with details of how you got to this error.');
        }
        return $this->response;
    }

    /**
    * Delete an AB Test
    * @param $ab_test (array) full info on data to insert a new AB Test
    * @return response (array)
    */
    private function delete_ab_test($ab_test) {

        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':ab_test_id'       => $ab_test['ab_test_id'],
                        ':quiz_id_a'       => $ab_test['quiz_id_a'],
                        ':quiz_id_b'       => $ab_test['quiz_id_b'],
                        ':ab_test_updated_by'  => $ab_test['ab_test_updated_by'],
                        ':ab_test_updated_at'  => $ab_test['ab_test_updated_at'],
                        ':ab_test_is_deleted' => $ab_test['ab_test_is_deleted']
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->ab_test_table."
                   SET ab_test_updated_by = :ab_test_updated_by,
                       ab_test_updated_at = :ab_test_updated_at,
                       ab_test_is_deleted = :ab_test_is_deleted
                 WHERE ab_test_id = :ab_test_id
                   AND quiz_id_a = :quiz_id_a
                   AND quiz_id_b = :quiz_id_b";
        // insert the quiz into the database
        $stmt = $pdo->query($sql, $params);

        // start the response
        $this->response['action'] = 'update';
        $this->response['user_action']['action'] = 'delete';
        $this->response['user_action']['element'] = 'ab_test';
        $this->response['ab_test_id'] = $this->ab_test['ab_test_id'];
        $this->response['quiz_id_a'] = $this->ab_test['quiz_id_a'];
        $this->response['quiz_id_b'] = $this->ab_test['quiz_id_b'];
        // success!
        if($stmt !== false) {
            // build more response stuff
            $this->response['status'] = 'success';
            $this->add_success('AB Test Deleted.');
        } else {
            $this->response['status'] = 'error';
            $this->add_error('AB Test could not be deleted. Try again and if it continues to not work, send us an email with details of how you got to this error.');
        }
        return $this->response;
    }

    /**
    * Add an error message to the response
    */
    protected function add_error($message) {
        $this->response['message']['error'][] = $message;
    }

    /**
    * Add a success message to the response
    */
    protected function add_success($message) {
        $this->response['message']['success'][] = $message;
    }

    /**
    * Check for Errors in the response
    */
    protected function check_for_errors() {
        if(empty($this->response['message']['error'])) {
            return false;
        } else {
            return true;
        }
    }
}
