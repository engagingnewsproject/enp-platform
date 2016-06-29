<?/**
 * Save process for quizzes
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/includes
 *
 * Called by Enp_quiz_Quiz_create and Enp_quiz_Quiz_preview
 *
 * This class defines all code for processing and saving quizzes
 *
 * @since      0.0.1
 * @package    Enp_quiz
 * @subpackage Enp_quiz/database
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Save_quiz extends Enp_quiz_Save {
    protected static $quiz,
                     $quiz_obj,
                     $response_obj,
                     $user_action_action,
                     $user_action_element,
                     $user_action_details;

    public function __construct() {

    }

    public function save($quiz) {
        // first off, sanitize the whole submitted thang
        self::$quiz = $this->sanitize_array($quiz);
        // flatten it out to make it easier to work with
        self::$quiz = $this->flatten_quiz_array(self::$quiz);

        // Open a new response object
        self::$response_obj = new Enp_quiz_Save_quiz_Response();
        // setup the user_action response
        self::$response_obj->set_user_action_response(self::$quiz);
        // these are referenced a lot, so lets set a quick link up for them
        self::$user_action_action = self::$response_obj->get_user_action_action();
        self::$user_action_element = self::$response_obj->get_user_action_element();
        self::$user_action_details = self::$response_obj->get_user_action_details();

        // create our object
        self::$quiz_obj = new Enp_quiz_Quiz(self::$quiz['quiz_id']);
        // fill the quiz with all the values
        $this->prepare_submitted_quiz();
        // process questions
        $this->prepare_submitted_questions();

        // Check if we're allowed to save. If any glaring errors, return the errors here
        // TODO: Check to make sure we can save. If there are errors, just return to page!

        // Alrighty!
        // actually save the quiz
        $this->save_quiz();

        // if we are trying to move to preview, check
        // for error messages (like, not enough mc_options, no correct option set, no questions, etc...)
        if(self::$user_action_action === 'next') {
            // builds error messages if trying to preview quiz
            $quiz_obj_validate = new Enp_quiz_Quiz(self::$quiz['quiz_id']);
            $validate = self::$response_obj->validate_quiz_and_questions($quiz_obj_validate);

            // see if they're trying to publish the quiz
            if(self::$user_action_element === 'publish') {
                if($validate === 'valid') {
                    // is the quiz already published?
                    if(self::$quiz_obj->get_quiz_status() !== 'published') {
                        // OK, it's good! Publish it!
                        $this->pdo_publish_quiz();
                        // now let's reset the data and responses on that quiz as well
                        // delete all the responses & reset the stats for the quiz and questions
            			$quiz_data = new Enp_quiz_Save_quiz_take_Quiz_data(self::$quiz_obj);
            			$quiz_data->delete_quiz_responses(self::$quiz_obj);
                    } else {
                        // don't worry about it, probably just clicked on the "embed" on the quiz preview/settings page
                    }

                }
            }
        }

        // return the response to the user
        return self::$response_obj;
    }

    /**
    * The quiz they submit isn't in the exact format we want it in
    * so we need to reindex it to a nice format and set their values
    *
    * @param $quiz = array() in this format:
    *        $quiz = array(
    *            'quiz' => $_POST['enp_quiz'], // array of quiz values (quiz_id, quiz_title, etc)
    *            'questions' => $_POST['enp_question'], // array of questions
    *            'quiz_updated_by' => $user_id,
    *            'quiz_updated_at' => $date_time,
    *        );
    * @return nicely formatted and value validated quiz array ready for saving
    */
    protected function prepare_submitted_quiz() {

        $quiz_id = $this->set_quiz_value('quiz_id', 0);
        $quiz_title = $this->set_quiz_value('quiz_title', '');
        $quiz_status = $this->set_quiz_value('quiz_status', 'draft');
        $quiz_finish_message = $this->set_quiz_value('quiz_finish_message', 'Thanks for taking our quiz!');
        $quiz_updated_by = $this->set_quiz_value('quiz_updated_by', 0);
        $quiz_updated_at = $this->set_quiz_value('quiz_updated_at', date("Y-m-d H:i:s"));
        $quiz_owner = $this->set_quiz_value('quiz_owner', $quiz_updated_by);
        $quiz_created_by = $this->set_quiz_value('quiz_created_by', $quiz_updated_by);
        $quiz_created_at = $this->set_quiz_value('quiz_created_at', $quiz_updated_at);
        // Options
        $quiz_title_display = $this->set_quiz_value('quiz_title_display', 'show');
        $quiz_width = $this->set_quiz_css_measurement_value('quiz_width', '100%');
        $quiz_bg_color = $this->set_quiz_hex_value('quiz_bg_color', '#ffffff');
        $quiz_text_color = $this->set_quiz_hex_value('quiz_text_color', '#444444');
        // facebook
        $facebook_title_start = $this->set_quiz_value('facebook_title_start', $quiz_title);
        $facebook_description_start = $this->set_quiz_value('facebook_description_start', 'How well can you do?');
        $facebook_title_end = $this->set_quiz_value('facebook_title_end', $quiz_title.' - I got {{score_percentage}}% right.');
        $facebook_description_end = $this->set_quiz_value('facebook_description_end', 'How well can you do?');
        // email
        $email_subject_start = $facebook_title_start;
        $email_body_start = $facebook_description_start;
        $email_subject_end = $facebook_title_end;
        $email_body_end = $facebook_description_end;
        // twitter
        $include_url = true;
        $do_not_replace_mustache = false;
        $tweet_start = $this->set_tweet_value('tweet_start', 'How well can you do on our quiz?', $include_url, $do_not_replace_mustache);
        $replace_mustache = true;
        $tweet_end = $this->set_tweet_value('tweet_end', 'I got {{score_percentage}}% right on this quiz. How many can you get right?', $include_url, $replace_mustache);

        $default_quiz = array(
            'quiz_id' => $quiz_id,
            'quiz_title' => $quiz_title,
            'quiz_status' => $quiz_status,
            'quiz_finish_message' => $quiz_finish_message,
            'quiz_owner'      => $quiz_owner,
            'quiz_created_by' => $quiz_created_by,
            'quiz_created_at' => $quiz_created_at,
            'quiz_updated_by' => $quiz_updated_by,
            'quiz_updated_at' => $quiz_updated_at,
            // quiz options
            'quiz_title_display' => $quiz_title_display,
            'quiz_width'    => $quiz_width,
            'quiz_bg_color' => $quiz_bg_color,
            'quiz_text_color' => $quiz_text_color,
            // quiz options - share text
            'facebook_title_start' => $facebook_title_start,
            'facebook_description_start' => $facebook_description_start,
            'facebook_title_end' => $facebook_title_end,
            'facebook_description_end' => $facebook_description_end,
            // email is set off of facebook share content
            'email_subject_start' => $email_subject_start,
            'email_body_start' => $email_body_start,
            'email_subject_end' => $email_subject_end,
            'email_body_end' => $email_body_end,
            // tweet share text
            'tweet_start'=> $tweet_start,
            'tweet_end'=> $tweet_end,
        );
        // We don't want to lose anything that was in the sent quiz (like questions, etc)
        // so we'll merge them to make sure we don't lose anything
        self::$quiz = array_merge(self::$quiz, $default_quiz);
    }
    /**
    * Reformat and set values for all submitted questions
    *
    * @param $questions = array() in this format:
    *        $questions = array(
    *                        array(
    *                            $question = array(
    *                           'question_id' => $question['question_id'],
    *                           'question_title' =>$question['question_title'],
    *                           'question_type' =>$question['question_type'],
    *                           'slider' || 'mc_option' => array();
    *                           'question_explanation' =>  $question['question_explanation'],
    *                           'question_order' => $question['question_order'],
    *        );
    *                        ),
    *                    );
    * @return nicely formatted and value validated questions array ready for saving
    */
    protected function prepare_submitted_questions() {

        $allow_question_save = $this->preprocess_questions();
        if($allow_question_save === 'no_questions') {
            // nothing to save/insert here...
            return false;
        }
        // start our counter for our question_order
        $i = 0;
        // open a new default_questions array
        $prepared_questions = array();
        // loop through all submitted questions
        foreach(self::$quiz['question'] as $question) {
            // see if we're supposed to delete this question
            $question['question_is_deleted'] = $this->preprocess_deleted_question($question);

            // Check if we're deleting this question
            if($question['question_is_deleted'] === 0) {
                // we're clear!
                // add in our new question_order value
                $question['question_order'] = $i;
                $i++;
            } else {
                // if we're deleting the question, we need to set the question_order
                // differently and NOT increase the question_order counter
                // add in our new question_order value
                $question['question_order'] = -1;
            }

            // create the object
            $question_obj = new Enp_quiz_Save_question();
            // prepare the values
            $question = $question_obj->prepare_submitted_question($question);
            // set the nicely formatted returned $question
            $prepared_questions[] = $question;


        }

        // We don't want to lose anything that was in the sent quiz
        // so we'll merge them to make sure we don't lose anything
        self::$quiz['question'] = $prepared_questions;
    }

    protected function preprocess_questions() {
        // If user action is ADD QUESTION
        // create a dummy question to insert in the DB
        if(self::$user_action_action === 'add' && self::$user_action_element === 'question') {
            // create a blank question in the db
            if(!array_key_exists('question', self::$quiz)) {
                // this is the first question for the quiz, we need to create an empty array for it.
                // the values will get automatically generated later by our prepare_question functions
                self::$quiz['question'] = array();
            }
            // add a new empty question array to the end of the array
            // so our foreach loop will run one extra time
            self::$quiz['question'][] = array();
        } elseif(!array_key_exists('question', self::$quiz)) {
            return 'no_questions';
        }
    }

    /**
    * we need to check if a question is trying to be deleted, so we know
    * to NOT increase the order counter and to flag the is_deleted value
    * @param $question (array) question submitted from form
    * @return $is_deleted (int) 1 = delete, 0 = not deleted
    */
    protected function preprocess_deleted_question($question) {
        $is_deleted = 0;
        // see if we're supposed to delete a question
        if(self::$user_action_action === 'delete' && self::$user_action_element === 'question') {
            // flag it as being deleted
            // if they want to delete, see if we match the question_id
            $question_id_to_delete = self::$user_action_details['question_id'];
            if($question_id_to_delete === (int) $question['question_id']) {
                // we've got a match! this is the one they want to delete
                $is_deleted = 1;
            }
        }

        return $is_deleted;
    }

    /**
    * Reformats quiz array into something easier to work with
    * array('quiz' => array('quiz_id'=>1, 'quiz_title'=>'Untitled',...));
    * becomes
    * array('quiz_id'=>0, quiz_title' => 'Untitled'...)
    */
    protected function flatten_quiz_array($submitted_quiz) {
        $flattened_quiz = array();
        if(array_key_exists('quiz', $submitted_quiz) && is_array($submitted_quiz['quiz'])) {
            // Flatten the submitted arrays a bit to make it easier to understand
            $flattened_quiz = $submitted_quiz['quiz'];
            // unset (delete) the quiz
            unset($submitted_quiz['quiz']);
        }

        if(array_key_exists('question', $submitted_quiz) && is_array($submitted_quiz['question'])) {
            // get the question
            $flattened_quiz['question'] = $submitted_quiz['question'];
            // unset (delete) the quiz
            unset($submitted_quiz['question']);
        }

        // merge the remainder
        $flattened_quiz = array_merge($submitted_quiz, $flattened_quiz);

        return $flattened_quiz;
    }

    /*
    * Sanitize all keys and values of an array. Loops through ALL arrays (even nested)
    */
    protected function sanitize_array($array) {
        $sanitized_array = array();
        // check to make sure it's an array
        if (!is_array($array) || !count($array)) {
    		return $sanitized_array;
    	}
        // loop through each key/value
    	foreach ($array as $key => $value) {
            // sanitize the key
            $key = sanitize_key($key);

            // if it's not an array, sanitize the value
    		if (!is_array($value) && !is_object($value)) {
    			$sanitized_array[$key] = sanitize_text_field($value);
    		}

            // if it is an array, loop through that array with the same function
    		if (is_array($value)) {
    			$sanitized_array[$key] = $this->sanitize_array($value);
    		}
    	}
        // return our new, clean, sanitized array
    	return $sanitized_array;
    }

    /**
    * Core function that hooks everything together for saving
    * Inserts or Updates the quiz in the database
    *
    * @return response array of quiz_id, action, status, and errors (if any)
    */
    private function save_quiz() {

        // check for the quiz_title real quick
        if(self::$quiz['quiz_title'] === '') {
            // You had ONE job...
            self::$response_obj->add_error('Please enter a quiz title.');
            return false;
        }

        //  If the quiz_obj doesn't exist the quiz object will set the quiz_id as null
        if(self::$quiz_obj->get_quiz_id() === null) {
            // Congratulations, quiz! You're ready for insert!
            $this->insert_quiz();
            // set the quiz_id on our array self::quiz array now that we have one
            self::$quiz['quiz_id'] = self::$response_obj->quiz_id;
        } else {
            // check to make sure that the quiz owner matches
            $allow_update = $this->quiz_owned_by_current_user();
            // update a quiz entry
            if($allow_update === true) {
                // the current user matches the quiz owner
                $this->update_quiz();
            } else {
                // Hmm... the user is trying to update a quiz that isn't theirs
                self::$response_obj->add_error('Quiz Update not Allowed');
                return false;
            }
        }

        // save all of our questions and other stuff
        // check to make sure a quiz_id is there
        if(self::$quiz['quiz_id'] !== 0) {
            // if a quiz_id is set, lets try saving the quiz options
            $this->save_quiz_options();

            // check to see if we HAVE questions to save
            if(!empty(self::$quiz['question'])){
                $this->save_questions();
            }

        } else {
            // hopefully won't ever happen... this would mean that the quiz_insert failed
            // so we don't have a quiz to assign these questions to
            self::$response_obj->add_error('Questions could not be saved to your quiz.');
            return false;
        }
    }

    /**
     * Check to see if the owner of the submitted quiz matches
     * the one they want to update
     *
     * @param    $quiz_owner_id = Selected quiz row from database
     * @param    $user_id = User trying to update the quiz
     * @return   returns quiz row if exists, false if not
     * @since    0.0.1
     */
    protected function quiz_owned_by_current_user() {
        // cast to integers to make sure we're talkin the same talk here
        $quiz_owner_id = (int) self::$quiz_obj->get_quiz_owner();
        $current_user_id = (int) self::$quiz['quiz_updated_by'];
        // set it to false to start. Guilty til proven innocent.
        $quiz_owned_by_current_user = false;
        // check to make sure we have values for each
        if($quiz_owner_id !== false && $current_user_id !== false ) {
            // check to see if the owner and user match
            if($quiz_owner_id === $current_user_id) {
                // if they match, then it's legit
                $quiz_owned_by_current_user = true;
            }
        }

        return $quiz_owned_by_current_user;
    }
    /**
    * Connects to DB and inserts the quiz.
    * @return builds and returns a response message
    */
    protected function insert_quiz() {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':quiz_title'       => self::$quiz['quiz_title'],
                        ':quiz_status'      => self::$quiz['quiz_status'],
                        ':quiz_finish_message' => self::$quiz['quiz_finish_message'],
                        ':quiz_owner'       => self::$quiz['quiz_owner'],
                        ':quiz_created_by'  => self::$quiz['quiz_created_by'],
                        ':quiz_created_at'  => self::$quiz['quiz_created_at'],
                        ':quiz_updated_by'  => self::$quiz['quiz_updated_by'],
                        ':quiz_updated_at'  => self::$quiz['quiz_updated_at']
                    );
        // write our SQL statement
        $sql = "INSERT INTO ".$pdo->quiz_table." (
                                            quiz_title,
                                            quiz_status,
                                            quiz_finish_message,
                                            quiz_owner,
                                            quiz_created_by,
                                            quiz_created_at,
                                            quiz_updated_by,
                                            quiz_updated_at
                                        )
                                        VALUES(
                                            :quiz_title,
                                            :quiz_status,
                                            :quiz_finish_message,
                                            :quiz_owner,
                                            :quiz_created_by,
                                            :quiz_created_at,
                                            :quiz_updated_by,
                                            :quiz_updated_at
                                        )";
        // insert the quiz into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            self::$quiz['quiz_id'] = $pdo->lastInsertId();
            self::$response_obj->set_status('success');
            self::$response_obj->set_action('insert');
            self::$response_obj->add_success('Quiz created.');
            // build a full response object
            self::$response_obj->set_quiz_response(self::$quiz);

        } else {
            self::$response_obj->add_error('Quiz could not be added to the database. Try again and if it continues to not work, send us an email with details of how you got to this error.');
        }
    }

    /**
    * Connects to DB and updates the quiz.
    * @return builds and returns a response message
    */
    protected function update_quiz() {
        // connect to PDO
        $pdo = new enp_quiz_Db();

        $params = $this->set_update_quiz_params();

        $sql = "UPDATE ".$pdo->quiz_table."
                     SET quiz_title = :quiz_title,
                         quiz_status = :quiz_status,
                         quiz_finish_message = :quiz_finish_message,
                         quiz_updated_by = :quiz_updated_by,
                         quiz_updated_at = :quiz_updated_at

                   WHERE quiz_id = :quiz_id
                     AND quiz_owner = :quiz_owner
                ";

        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            self::$response_obj->set_status('success');
            self::$response_obj->set_action('update');
            self::$response_obj->add_success('Quiz saved.');
            // build a full response object
            self::$response_obj->set_quiz_response(self::$quiz);
        } else {
            self::$response_obj->add_error('Quiz could not be updated. Try again and if it continues to not work, send us an email with details of how you got to this error.');
        }

    }

    protected function save_quiz_options() {
        // this effectively whitelists it for us
        $quiz_options = array('quiz_title_display',
                              'quiz_width',
                              'quiz_bg_color',
                              'quiz_text_color',
                              'facebook_title_start',
                              'facebook_description_start',
                              'facebook_title_end',
                              'facebook_description_end',
                              'email_subject_start',
                              'email_body_start',
                              'email_subject_end',
                              'email_body_end',
                              'tweet_start',
                              'tweet_end'
                            );
        foreach($quiz_options as $quiz_option) {
            if(array_key_exists($quiz_option, self::$quiz)) {
                $save_quiz_option = new Enp_quiz_Save_quiz_option();
                $save_quiz_option->save_quiz_option($quiz_option);
            }
        }

    }

    /**
    * Connects to DB and updates the quiz.
    * @return builds and returns a response message
    */
    protected function pdo_publish_quiz() {
        // connect to PDO
        $pdo = new enp_quiz_Db();

        $params = array(':quiz_id'          => self::$quiz_obj->get_quiz_id(),
                        ':quiz_status'      => 'published',
                        ':quiz_owner'       => self::$quiz['quiz_owner'],
                        ':quiz_updated_by'  => self::$quiz['quiz_updated_by'],
                        ':quiz_updated_at'  => self::$quiz['quiz_updated_at']
                    );

        $sql = "UPDATE ".$pdo->quiz_table."
                     SET quiz_status = :quiz_status,
                         quiz_updated_by = :quiz_updated_by,
                         quiz_updated_at = :quiz_updated_at
                   WHERE quiz_id = :quiz_id
                     AND quiz_owner = :quiz_owner";

        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            self::$response_obj->set_quiz_id(self::$quiz['quiz_id']);
            self::$response_obj->set_status('success');
            self::$response_obj->set_action('update');
            self::$response_obj->add_success('Quiz published!');
        } else {
            self::$response_obj->add_error('Quiz could not be published. Try again and if it continues to not work, send us an email with details of how you got to this error.');
        }

    }

    /**
    * Can be called from anywhere to publish a quiz
    *
    */
    public function publish_quiz($quiz) {
        if(is_object($quiz)) {
            $quiz = (array) $quiz;
        }
        // set our action
        $quiz['user_action'] = 'quiz-publish';
        $this->save($quiz);
    }

    /**
     * Populate self::$quiz array with values
     * from self::$quiz_obj if they're not present in self::$quiz
     * We need all the values to brute update, but don't want to have to pass the
     * values in every form.
     *
     * @param    $slider = array(); of slider data
     * @return   ID of saved slider or false if error
     * @since    0.0.1
     */
    protected function set_update_quiz_params() {
        $params = array(':quiz_id'          => self::$quiz_obj->get_quiz_id(),
                        ':quiz_title'       => self::$quiz['quiz_title'],
                        ':quiz_status'      => self::$quiz['quiz_status'],
                        ':quiz_finish_message' => self::$quiz['quiz_finish_message'],
                        ':quiz_owner'       => self::$quiz['quiz_owner'],
                        ':quiz_updated_by'  => self::$quiz['quiz_updated_by'],
                        ':quiz_updated_at'  => self::$quiz['quiz_updated_at']
                    );
        return $params;
    }


    /**
    * Loop through a $quiz['question'] array to save to the db
    *
    * @param    $question = array(); of all $quiz['question'] data
    * @since    0.0.1
    */
    protected function save_questions() {
        // loop through the questions and save each one
        foreach(self::$quiz['question'] as $question) {
            // save it! Yay!
            // get access to the question object
            $question_obj = new Enp_quiz_Save_question();
            // save the question
            $question_obj->save_question($question);
        }
    }

    /**
    * Check to see if a value was passed in self::$quiz array
    * If it was, set it as the value. If it wasn't, set the value
    * from self::$quiz_obj
    *
    * @param $key = key that should be set in the quiz array.
    * @param $default = int or string of default value if nothing is found
    * @return value from either self::$quiz[$key] or self::$quiz_obj->get_quiz_$key()
    */
    protected function set_quiz_value($key, $default) {
        $param_value = $default;
        // see if the value is already in our submitted quiz
        if(array_key_exists($key, self::$quiz) && self::$quiz[$key] !== "") {
            $param_value = self::$quiz[$key];
        } else {
            $obj_value = $this->get_quiz_object_value($key);
            if($obj_value !== null) {
                $param_value = $obj_value;
            }
        }

        return $param_value;
    }

    /**
    * Dynamically set our value from the quiz object
    * @param $key = the value you want to get 'quiz_title', 'quiz_id', etc.
    * @return $obj_value (mixed) value if found, null if not found
    */
    protected function get_quiz_object_value($key) {
        $obj_value = null;
        // check to see if there's even an object
        if(self::$quiz_obj->get_quiz_id() !== null) {
            // if it's not in our submited quiz, try to get it from the object
            // dynamically create the quiz getter function
            $get_obj_value = 'get_'.$key;
            // get the quiz object value
            $obj_value = self::$quiz_obj->$get_obj_value();
        }
        return $obj_value;
    }

    /**
    * Get and validate quiz value from object or set as default
    * useful if user submission doesn't validate.
    *
    * @param $key = the value you want to get/set 'quiz_bg_color', 'quiz_width', etc.
    * @param $default = the value you want to fall back to
    * @param $validation = string of validation function 'css_measurement', 'hex', etc
    * @return $value (mixed) quiz object value if found and valid, $default value if not found in object/invalid object value
    */
    protected function validate_quiz_value_from_object($key, $default, $validation) {
        // try to get the value from the object
        $obj_value = $this->get_quiz_object_value($key);
        // build our validation function string
        $validate_function = 'validate_'.$validation;
        // validate our object value
        $valid_obj_value = $this->$validate_function($obj_value);
        // if it's not valid, set it to the default
        if($valid_obj_value === false) {
            // ok, we've exhausted all or options. set it to the default
            $value = $default;
        } else {
            // the object is valid, that's good!
            $value = $obj_value;
        }
        return $value;
    }
    /**
    * Check to see if a value was passed in self::$quiz array
    * If it was, set it as the value (after validating). If it wasn't, set the value
    * from self::$quiz_obj or default
    *
    * @param $key = key that should be set in the quiz array.
    * @param $default = int or string of default value if nothing is found
    * @return value from either self::$quiz[$key] or self::$quiz_obj->get_quiz_$key()
    */
    public function set_quiz_hex_value($key, $default) {
        // set it with what they submitted
        $hex = $this->set_quiz_value($key, $default);
        // validate the hex
        $valid_hex = $this->validate_hex($hex);
        // check it

        if($valid_hex === false) {
            // generate a good error message
            self::$response_obj->add_error('Hex Color value for '.$key.' was not valid. Hex Color Value must be a valid Hex value like #ffffff');
            // if it's not a valid hex, try to get the old value from the object
            // and fallback to default if necessary
            $hex = $this->validate_quiz_value_from_object($key, $default, 'hex');
        }

        return $hex;
    }

    /**
    * Check to see if a value was passed in self::$quiz array
    * If it was, set it as the value (after validating). If it wasn't, set the value
    * from self::$quiz_obj or default
    *
    * @param $key = key that should be set in the quiz array.
    * @param $default = int or string of default value if nothing is found
    * @return value from either self::$quiz[$key] or self::$quiz_obj->get_quiz_$key()
    */
    public function set_quiz_css_measurement_value($key, $default) {
        // set it with what they submitted
        $css_measurement = $this->set_quiz_value($key, $default);
        // validate the hex
        $valid_css_measurement = $this->validate_css_measurement($css_measurement);
        // check it
        if($valid_css_measurement === false) {
            // add a good error message
            self::$response_obj->add_error('CSS Measurement value for '.$key.' is not valid. Measurements can be any valid CSS Measurement such as 100%, 600px, 30rem, 80vw');
            // if it's not a valid css_measurement, try to get the old value from the object
            // and fallback to default if necessary
            $css_measurement = $this->validate_quiz_value_from_object($key, $default, 'css_measurement');
        }

        return $css_measurement;
    }

    /**
    * Check to see if a value was passed in self::$quiz array
    * If it was, set it as the value (after validating). If it wasn't, set the value
    * from self::$quiz_obj or default
    *
    * @param $key = key that should be set in the quiz array.
    * @param $default = int or string of default value if nothing is found
    * @param $include_url (BOOLEAN) URLs count as 21 characters. Set true if
    *                               you will be using a URL with the tweet
    * @param $mustache (BOOLEAN) checks for {{score_percentage}} and replaces
    *                            it with '100' if found
    * @return value from either self::$quiz[$key] or self::$quiz_obj->get_quiz_$key()
    */
    public function set_tweet_value($key, $default, $include_url, $mustache) {
        // set it with what they submitted
        $tweet = $this->set_quiz_value($key, $default);
        // validate the tweet
        $valid_tweet = $this->validate_tweet($tweet, $include_url, $mustache);
        // check it

        if($valid_tweet === false) {
            // generate a good error message
            self::$response_obj->add_error('The tweet for '.$key.' has too many characters. It can\'t have more than 117 characters because sharing the URL for the quiz uses up 23 of the 140 available characters.');
            // if it's not a valid tweet, try to get the old value from the object
            // and fallback to default if necessary
            $tweet = $this->validate_quiz_value_from_object($key, $default, 'tweet');
        }

        return $tweet;
    }


}
?>
