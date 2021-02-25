<?php
/**
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

        // check to see if we need to trigger a rescrape from Facebook to update the OG Tags
        $this->facebook_api_rescrape();

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
        $quiz_owner = $this->set_quiz_value__object_first('quiz_owner', $quiz_updated_by);
        $quiz_created_by = $this->set_quiz_value__object_first('quiz_created_by', $quiz_updated_by);
        $quiz_created_at = $this->set_quiz_value('quiz_created_at', $quiz_updated_at);
        $quiz_is_deleted = $this->set_quiz_is_deleted();
        // Options
        $quiz_title_display = $this->set_quiz_value('quiz_title_display', 'show');
        $quiz_mc_options_order = $this->set_quiz_value('quiz_mc_options_order', 'random');
        $quiz_width = $this->set_quiz_css_measurement_value('quiz_width', '100%');
        $quiz_bg_color = $this->set_quiz_hex_value('quiz_bg_color', '#ffffff');
        $quiz_text_color = $this->set_quiz_hex_value('quiz_text_color', '#444444');
        $quiz_border_color = $this->set_quiz_hex_value('quiz_border_color', '#dddddd');
        $quiz_button_color = $this->set_quiz_hex_value('quiz_button_color', '#5887C0');
        $quiz_correct_color = $this->set_quiz_hex_value('quiz_correct_color', '#3bb275');
        $quiz_incorrect_color = $this->set_quiz_hex_value('quiz_incorrect_color', '#f14021');

        // custom css
        $quiz_custom_css = $this->set_quiz_css_value('quiz_custom_css', '', false);
        $quiz_custom_css_minified = $this->optimize_css($quiz_custom_css);

        // facebook
        $facebook_title = $this->set_quiz_value('facebook_title', $quiz_title);
        $facebook_description = $this->set_quiz_value('facebook_description', 'How well can you do?');
        $facebook_quote_end = $this->set_quiz_value('facebook_quote_end', 'I got {{score_percentage}}% right.');
        // email
        $email_subject = $facebook_title;
        $email_body_start = $facebook_description;
        $email_body_end = $facebook_quote_end.'

'.$facebook_description;
        // twitter
        $include_url = true;
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
            'quiz_is_deleted' => $quiz_is_deleted,
            // quiz options
            'quiz_title_display' => $quiz_title_display,
            'quiz_mc_options_order' => $quiz_mc_options_order,
            'quiz_width'    => $quiz_width,
            'quiz_bg_color' => $quiz_bg_color,
            'quiz_text_color' => $quiz_text_color,
            'quiz_border_color' => $quiz_border_color,
            'quiz_button_color' => $quiz_button_color,
            'quiz_correct_color' => $quiz_correct_color,
            'quiz_incorrect_color' => $quiz_incorrect_color,
            // quiz options - css
            'quiz_custom_css' => $quiz_custom_css,
            'quiz_custom_css_minified' => $quiz_custom_css_minified,

            // quiz options - share text
            'facebook_title' => $facebook_title,
            'facebook_description' => $facebook_description,
            'facebook_quote_end' => $facebook_quote_end,
            // email is set off of facebook share content
            'email_subject' => $email_subject,
            'email_body_start' => $email_body_start,
            'email_body_end' => $email_body_end,
            // tweet share text
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
        } 
        elseif(!array_key_exists('question', self::$quiz)) {
            return 'no_questions';
        } 
        // see if we're moving a question
        elseif(self::$user_action_action === 'move' && self::$user_action_element === 'question') {
            // make sure we're not moving it to a location that's not possible with this array. If it's greater than array count, just set it as the last one
            $to = self::$user_action_details['move_question_to'];

            if(count(self::$quiz['question']) < $to) {
                $to = count($array);
            } 
            elseif($to <= -1) {
                $to = 0;
            }

            // get index of the one you want to move
            $index = 0;
            foreach(self::$quiz['question'] as $question) {
                if($question['question_id'] == self::$user_action_details['question_id']) {
                    // this is it!
                    break;
                }
                $index++;
            }

            // check to make sure we're trying to move it to a new position, otherwise this is pointless
            if($to === $index) {
                return;
            }
            
            // find the position of the element we want to move
            $from = array_splice(self::$quiz['question'], $index, 1);
            // actually move it
            array_splice(self::$quiz['question'], $to, 0, $from);
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
            $allow_update = $this->allow_user_to_update_quiz();
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
     * @return   (BOOLEAN) true if current user owns quiz, false if not
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
    * See if we should allow the current user to update the quiz
    * @return   (BOOLEAN) true if we should allow the user
    *                     to update the quiz, false if not
    */
    protected function allow_user_to_update_quiz() {
        $allow_save = false;
        // see if user is admin or not
        if(current_user_can('manage_options') === true) {
            $allow_save = true;
        } else {
            // see if this user owns the quiz
            $allow_save = $this->quiz_owned_by_current_user();
        }

        return $allow_save;
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
                        ':quiz_updated_at'  => self::$quiz['quiz_updated_at'],
                        ':quiz_is_deleted'  => self::$quiz['quiz_is_deleted'],
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
                                            quiz_updated_at,
                                            quiz_is_deleted
                                        )
                                        VALUES(
                                            :quiz_title,
                                            :quiz_status,
                                            :quiz_finish_message,
                                            :quiz_owner,
                                            :quiz_created_by,
                                            :quiz_created_at,
                                            :quiz_updated_by,
                                            :quiz_updated_at,
                                            :quiz_is_deleted
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
                         quiz_updated_at = :quiz_updated_at,
                         quiz_is_deleted = :quiz_is_deleted

                   WHERE quiz_id = :quiz_id
                     AND quiz_owner = :quiz_owner
                ";

        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            self::$response_obj->set_status('success');
            self::$response_obj->set_action('update');

            // if we're deleting the quiz, don't say "quiz saved"
            if(self::$user_action_action === 'delete' && self::$user_action_element === 'quiz') {
                self::$response_obj->add_success('Quiz deleted.');
                // kick off the process of deleting all AB tests related to this quiz
                $ab_test_delete_response = $this->delete_quiz_ab_tests(self::$quiz_obj->get_quiz_id(), self::$quiz['quiz_owner']);
                // merge deleting ab tests with the global response
                self::$response_obj->set_ab_test_delete_response($ab_test_delete_response);

            } else {
                self::$response_obj->add_success('Quiz saved.');
            }

            // build a full response object
            self::$response_obj->set_quiz_response(self::$quiz);
        } else {
            self::$response_obj->add_error('Quiz could not be updated. Try again and if it continues to not work, send us an email with details of how you got to this error.');
        }

    }

    protected function save_quiz_options() {
        // this effectively whitelists it for us
        $quiz_options = array('quiz_title_display',
                              'quiz_mc_options_order',
                              'quiz_width',
                              'quiz_bg_color',
                              'quiz_text_color',
                              'quiz_border_color',
                              'quiz_button_color',
                              'quiz_correct_color',
                              'quiz_incorrect_color',
                              'quiz_custom_css',
                              'quiz_custom_css_minified',
                              'facebook_title',
                              'facebook_description',
                              'facebook_quote_end',
                              'email_subject',
                              'email_body_start',
                              'email_body_end',
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
                        ':quiz_updated_at'  => self::$quiz['quiz_updated_at'],
                        ':quiz_is_deleted'  => self::$quiz['quiz_is_deleted']
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
    * Should be merged with set_quiz_value with an extra paramter to allow
    * it to be an empty value. For example, with the set_quiz_value, you can't
    * delete a field. With this method, you can delete fields.
    *
    * @param $key = key that should be set in the quiz array.
    * @param $default = int or string of default value if nothing is found
    * @return value from either self::$quiz[$key] or self::$quiz_obj->get_quiz_$key()
    */
    protected function set_quiz_value__allow_empty($key, $default) {
        $param_value = $default;
        // see if the value is already in our submitted quiz
        if(array_key_exists($key, self::$quiz)) {
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
    * Sometimes you need to set the value from the object first instead
    * of the submitted value, such as for quiz_owner, when it might be
    * updated by someone other than the quiz_owner (an admin)
    * @param $key = key that should be set in the quiz array.
    * @param $default = int or string of default value if nothing is found
    * @return value from either self::$quiz[$key] or self::$quiz_obj->get_quiz_$key()
    */
    public function set_quiz_value__object_first($key, $default) {
        // see if the value is already in our quiz object
        $obj_value = $this->get_quiz_object_value($key);
        if($obj_value !== null) {
            $param_value = $obj_value;
        } elseif(array_key_exists($key, self::$quiz) && self::$quiz[$key] !== "") {
            $param_value = self::$quiz[$key];
        } else {
            $param_value = $default;
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
    * Sets a quiz value for CSS formatted entries by running it through CSS Tidy
    * @param $key = key that should be set in the quiz array.
    * @param $default = int or string of default value if nothing is found
    * @param $minify (BOOLEAN) if you want it minified or more readable
    * @return CSS Tidy formatted string
    */
    public function set_quiz_css_value($key, $default = '', $minify = true) {
        // set the value
        $css = $this->set_quiz_value__allow_empty($key, $default);
        // run it through css tidy
        $css = $this->optimize_css($css, $minify);
        // return it
        return $css;
    }

    /**
    * Run a string through CSS tidy
    *
    * @param $css = submitted css
    * @return optimized css from css tidy
    */
    public function optimize_css($css, $minify = true) {
        // it there isn't a value, return it
        if(empty($css)) {
            return '';
        }
        // get the csstidy class
        require_once ENP_QUIZ_PLUGIN_DIR . 'includes/css-tidy/class.csstidy.php';
        // open the CSS tidy class
        $csstidy = new csstidy();
        $csstidy->optimise = $css;

        $csstidy->set_cfg( 'case_properties',            false );
        // this also removes -moz, etc prefixes.
        $csstidy->set_cfg( 'discard_invalid_properties', true );

        if($minify === true) {
            // minifies the css
            $csstidy->set_cfg( 'template', 'highest');
        } else {
            // don't change the case of the class/id names
            $csstidy->set_cfg( 'optimise_shorthands', 0 );
            $csstidy->set_cfg( 'remove_last_;', false );
            $csstidy->set_cfg( 'template', ENP_QUIZ_PLUGIN_DIR . 'includes/css-tidy/formatted-css.tpl' );
        }

        $csstidy->parse( $css );

        $css = $csstidy->print->plain();

        return $css;
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

    /**
    * In order to show the updated Facebook Share Title & Description,
    * we have to tell Facebook to rescrape the URL to grab the updated content
    */
    public function facebook_api_rescrape() {
        $rescrape = false;
        $new_title = self::$quiz['facebook_title'];
        $old_title = self::$quiz_obj->get_facebook_title();
        $new_description = self::$quiz['facebook_description'];
        $old_description = self::$quiz_obj->get_facebook_description();

        // see if the submitted values match the current values or not
        if($new_title !== $old_title || $new_description !== $old_description){
            $rescrape = true;
        }

        if($rescrape === true) {
            // curl post to have facebook rescrape
            // set the ID off the response_obj just in case it's a new quiz
            $this->facebook_curl_post(ENP_QUIZ_URL . self::$response_obj->quiz_id);
        }

    }

    /**
    * Sends a curl post request to FB to trigger a rescrape of the Quiz
    * $quiz_url (string) The Quiz url you want to update.
    */
    public function facebook_curl_post($quiz_url) {
        $graph_url= "https://graph.facebook.com";

        $postData = "id=" . $quiz_url . "&scrape=true";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $graph_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $output = curl_exec($ch);
        curl_close($ch);
    }


    /**
    * Decide if a quiz should be deleted or not
    * @param self::$quiz
    */
    protected function set_quiz_is_deleted() {
        //get the current values (from submission or object)
        $is_deleted = $this->set_quiz_value('quiz_is_deleted', '0');
        // see if the user action is to delete a quiz
        if(self::$user_action_action === 'delete' && self::$user_action_element === 'quiz') {
            // if they want to delete, see if we match the quiz ID
            if(self::$user_action_details['quiz_id'] === (int) self::$quiz_obj->get_quiz_id()) {
                // we've got a match! this is the one they want to delete
                $is_deleted = 1;
            }
        }
        // return if this one should be deleted or not
        return $is_deleted;
    }

    /**
    * Deletes all AB Tests that this quiz is a part of
    * @param $quiz_id (string/int) of the id of the quiz
    * @return $ab_test_response (array) from deleting all the ab tests (which ids were deleted, if it was successful, etc)
    */
    protected function delete_quiz_ab_tests($quiz_id, $quiz_owner) {
        // get all the ab test ids that this quiz is a part of
        $ab_test_ids = $this->get_all_quiz_ab_tests($quiz_id);
        // if there are any, loop through them and delete em!
        $ab_test_response = array();
        if(!empty($ab_test_ids)) {
            // open the ab test save class
            $save_ab_test = new Enp_quiz_Save_ab_test();
            foreach($ab_test_ids as $ab_test_id) {
                $ab_test_params = array(
                                    'ab_test_id' => $ab_test_id,
                                    'ab_test_updated_by' => $quiz_owner
                            );
                $ab_test_response[] = $save_ab_test->delete($ab_test_params);
            }
        }
        return $ab_test_response;
    }

    /**
    * Select all AB Tests that a quiz is a part of
    * @param $quiz_id
    * @return $ab_test_ids (array) of all AB Test IDs
    */
    protected function get_all_quiz_ab_tests($quiz_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":quiz_id" => $quiz_id
        );
        $sql = "SELECT ab_test_id from ".$pdo->ab_test_table."
                  WHERE (quiz_id_a = :quiz_id OR quiz_id_b = :quiz_id)
                AND ab_test_is_deleted = 0";
        $stmt = $pdo->query($sql, $params);
        $ab_test_row = $stmt->fetchAll(PDO::FETCH_COLUMN);
        // return the found quiz row
        return $ab_test_row;
    }
}
?>
