<?php
/**
 * Response messages for saving quizzes
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/includes
 *
 * Called by Enp_quiz_Quiz_save
 *
 * This class defines all code for processing responses and success/error messages
 * The quiz that get passed here will already have been sanitized
 *
 * @since      0.0.1
 * @package    Enp_quiz
 * @subpackage Enp_quiz/database
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Save_quiz_Response extends Enp_quiz_Save {
    public $quiz_id,
           $quiz_title,
           $quiz_status,
           $status,
           $action,
           $message = array('error'=>array(),'success'=>array()),
           $question = array(),
           $quiz_option = array(),
           $user_action = array();

    public function __construct() {

    }

    /**
    * Sets a quiz_id on our response array
    * @param string = quiz_id you want to set
    * @return response object array
    */
    public function set_quiz_id($quiz_id) {
        $this->quiz_id = $quiz_id;
    }

    /**
    * Sets an action on our response array
    * @param string = action you want to set
    * @return response object array
    */
    public function set_action($action) {
        $this->action = $action;
    }

    /**
    * Sets a status on our response array
    * @param string = status you want to set
    * @return response object array
    */
    public function set_status($status) {
        $this->status = $status;
    }

    /**
    * Gets response from quiz save class and assigns values to our response object
    */
    public function set_quiz_response($quiz) {
        $this->set_quiz_id($quiz['quiz_id']);
        $this->quiz_title = $quiz['quiz_title'];
        $this->quiz_status = $quiz['quiz_status'];
        $this->quiz_finish_message = $quiz['quiz_finish_message'];
        $this->quiz_updated_at = $quiz['quiz_updated_at'];
    }

    /**
    * Gets response from save quiz option class and and assigns them
    * to our response object
    *
    * @param $quiz_option_response = array() of values like 'action', 'status', and 'quiz_option_id'
    * @param $quiz = the quiz array that was being saved
    */
    public function set_quiz_option_response($quiz_option_response, $quiz_option) {
        // sets our quiz_option response to our quiz_option response
        $this->quiz_option[$quiz_option] = $quiz_option_response;
    }

    /**
    * Loops through all passed responses from the save class and and assigns them
    * to our response object
    *
    * @param $question_response = array() of values like 'action', 'status', and 'mc_option_id'
    * @param $question = the question array that was being saved
    */
    public function set_question_response($question_response, $question) {
        $question_number = $question['question_order'];

        // sets the key/value for each item passed in the response
        foreach($question_response as $key => $value) {
            // set the question array with our response values
            $this->question[$question_number][$key] = $value;
        }
    }

    /**
    * Loops through all passed responses from the save class and and assigns them
    * to our response object
    *
    * @param $mc_option_response = array() of values like 'action', 'status', and 'mc_option_id'
    * @param $question = the question array that was being saved
    * @param $mc_option = the mc_option array that was being saved
    */
    public function set_mc_option_response($mc_option_response, $question, $mc_option) {
        $question_number = $question['question_order'];
        $mc_option_number = $mc_option['mc_option_order'];
        // sets the key/value for each item passed in the response
        foreach($mc_option_response as $key => $value) {
            // set the question/mc_option array with our response values
            $this->question[$question_number]['mc_option'][$mc_option_number][$key] = $value;
        }
    }

    /**
    * Loops through all passed responses from the save class and and assigns them
    * to our response object
    *
    * @param $slider_response = array() of values like 'action', 'status', and 'mc_option_id'
    * @param $question = the question array that was being saved
    */
    public function set_slider_response($slider_response, $question) {
        $question_number = $question['question_order'];

        // sets the key/value for each item passed in the response
        foreach($slider_response as $key => $value) {
            // set the question array with our response values
            $this->question[$question_number]['slider'][$key] = $value;
        }
    }

    /**
    * Sets a new error to our error response array
    * @param string = message you want to add
    * @return response object array
    */
    public function add_error($error) {
        $this->message['error'][] = $error;
    }

    /**
    * Sets a new success to our success response array
    * @param string = message you want to add
    * @return response object array
    */
    public function add_success($success) {
        $this->message['success'][] = $success;
    }


    /**
    * Build a user_action response array so enp_quiz-create class knows
    * what to do next, like go to preview page, add another question, etc
    */
    public function set_user_action_response($quiz) {
        $action = null;
        $element = null;
        $details = array();

        // set the user action to 'save' if there isn't one
        if(array_key_exists('user_action', $quiz)) {
            $user_action = $quiz['user_action'];
        } else {
            $user_action = 'save';
        }

        // if they want to preview, then see if they're allowed to go on
        if($user_action === 'quiz-preview') {
            $action = 'next';
            $element = 'preview';
        }
        // if they want to publish, then see if they're allowed to go on
        elseif($user_action === 'quiz-publish') {
            $action = 'next';
            $element = 'publish';
        }
        // if they want to add a question
        elseif($user_action === 'add-question') {
            // what else do we want to do?
            $action = 'add';
            $element = 'question';
        }
        // check to see if user wants to add-mc-option
        elseif(strpos($user_action, 'add-mc-option__question-') !== false) {
            $action = 'add';
            $element = 'mc_option';
            // extract the question number by removing 'add-mc-option__question-' from the string
            // we can't use question_id because the question_id might not
            // have been created yet
            $question_id = str_replace('add-mc-option__question-', '', $user_action);
            $details = array('question_id' => (int) $question_id);
        }
        // check to see if user wants to add-mc-option
        elseif(strpos($user_action, 'mc-option--correct__question-') !== false) {
            // get our matches
            preg_match_all('/\d+/', $user_action, $matches);
            // will return array of arrays splitting all number groups
            // first match is our question_id
            $question_id = $matches[0][0];
            // second match is our option_id
            $mc_option_id = $matches[0][1];
            $action = 'set_correct';
            $element = 'mc_option';

            $details = array(
                            'question_id' => (int) $question_id,
                            'mc_option_id' => (int) $mc_option_id,
                        );
        }
        // DELETE question
        elseif(strpos($user_action, 'question--delete-') !== false) {
            $action = 'delete';
            $element = 'question';
            // extract the question number by removing 'add-mc-option__question-' from the string
            // we can't use question_id because the question_id might not
            // have been created yet
            $question_id = str_replace('question--delete-', '', $user_action);
            $details = array('question_id' => (int) $question_id);
        }
        // UPLOAD question_image
        elseif(strpos($user_action, 'question-image--upload-') !== false) {

            $action = 'upload';
            $element = 'question_image';
            // extract the question number by removing 'add-mc-option__question-' from the string
            // we can't use question_id because the question_id might not
            // have been created yet
            $question_id = str_replace('question-image--upload-', '', $user_action);
            $details = array('question_id' => (int) $question_id);
        }
        // DELETE question_image
        elseif(strpos($user_action, 'question-image--delete-') !== false) {

            $action = 'delete';
            $element = 'question_image';
            // extract the question number by removing 'add-mc-option__question-' from the string
            // we can't use question_id because the question_id might not
            // have been created yet
            $question_id = str_replace('question-image--delete-', '', $user_action);
            $details = array('question_id' => (int) $question_id);
        }
        // DELETE mc_option
        elseif(strpos($user_action, 'mc-option--delete-') !== false) {
            $action = 'delete';
            $element = 'mc_option';
            // extract the question number by removing 'add-mc-option__question-' from the string
            // we can't use question_id because the question_id might not
            // have been created yet
            $mc_option_id = str_replace('mc-option--delete-', '', $user_action);
            $details = array('mc_option_id' => (int) $mc_option_id);
        }
        // DELETE entire Quiz! Gasp!
        elseif(strpos($user_action, 'delete-quiz') !== false) {
            $action = 'delete';
            $element = 'quiz';
            $details = array('quiz_id' => (int) $quiz['quiz_id']);
        }

        $this->user_action = array(
                                    'action' => $action,
                                    'element' => $element,
                                    'details' => $details,
                                );
    }


    /**
    * Runs all checks to validate and build error messages on quiz form
    * All the functions it runs add to the response object if there are errors
    *
    * @usage $response = new Enp_quiz_Save_quiz_Response();
    *        $validate = $response->validate_quiz_and_questions($quiz);
    *        var_dump($validate); // returns 'invalid' or 'valid'
    *        var_dump($response->get_error_messages()); // all error messages
    *
    * @param $quiz (quiz array or Enp_quiz_Quiz() object)
    * @return (kinda) builds all error messages, accessible from $this->message['error']
    * @return (string) valid or invalid
    */
    public function validate_quiz_and_questions($quiz) {
        // if we got passed a quiz object, let's turn it into an array
        if(is_object($quiz)) {
            $quiz = $this->quiz_object_to_array($quiz);
        }

        // check to make sure there's a quiz ID
        if($quiz['quiz_id'] === null) {
            // there's no quiz... invalid
            $this->add_error('Quiz does not exist.');
            return 'invalid';
        }

        // validate the quiz
        $this->validate_quiz($quiz);
        // check to see if they need to add questions
        if($this->validate_has_question($quiz) === 'has_question') {
            // we have a question title and explanation in the first question,
            // so let's check more in depth. This checks for all errors
            // in all questions
            $this->validate_questions($quiz);
        }

        // return a valid or invalid string.
        // the function builds all error messages too, so those will be
        // available to whatever is calling validate_quiz_and_questions($quiz)
        if(empty($this->message['error'])) {
            return 'valid';
        } else {
            return 'invalid';
        }
    }

    public function quiz_object_to_array($quiz_obj) {
        $quiz_array = (array) $quiz_obj;
        $new_questions_array = array();
        if(!empty($quiz_array)) {
            $question_ids = $quiz_obj->get_questions();
            if(!empty($question_ids)){
                foreach($question_ids as $question_id) {
                    // generate the object
                    $question_obj = new Enp_quiz_Question($question_id);
                    // arrayify the question
                    $question_array = (array) $question_obj;
                    // check if mc or slider and add that object as an array
                    if($question_obj->get_question_type() === 'mc') {
                        $mc_option_ids = $question_obj->get_mc_options();
                        if(!empty($mc_option_ids)) {
                            foreach($question_obj->get_mc_options() as $mc_option_id) {
                                $mc_option_object = new Enp_quiz_MC_option($mc_option_id);
                                $mc_option_array = (array) $mc_option_object;
                                $question_array['mc_option'][] = $mc_option_array;
                            }
                        }
                    } elseif($question_obj->get_question_type() === 'slider') {
                        // get the slider ID
                        $slider_id = $question_obj->get_slider();
                        // get the slider object
                        $slider_object = new Enp_quiz_Slider($slider_id);
                        // cast it to an array
                        $slider_array = (array) $slider_object;
                        // add it to the question
                        $question_array['slider'] = $slider_array;
                    }

                    // add it to our questions array
                    $quiz_array['question'][] = $question_array;

                }
            }

        }
        return $quiz_array;
    }


    /**
    * Checks to see if the first question is empty. If it is, add an error
    * @return 'has_questions' if question found, false if there are questions
    *
    */
    public function validate_has_question($quiz) {
        if(empty($quiz['question'][0]['question_title']) && empty($quiz['question'][0]['question_explanation'])) {
            $this->add_error('Question 1 does not have question text or an explanation.');
            return false;
        }
        return 'has_question';
    }

    /**
    * Loop through questions and check for errors
    */
    public function validate_questions($quiz) {
        $i = 1;
        // this is weird to set it as OK initially, but...
        $return_message = 'no_errors';
        // loop through all questions and check for titles, answer explanations, etc
        foreach($quiz['question'] as $question) {
            // checks if the title is empty or not
            $validate_title = $this->validate_question_title($question);
            if($validate_title === 'no_title') {
                $return_message = 'has_errors';
            }
            // checks if the answer explanation is empty or not
            $validate_explanation = $this->validate_question_explanation($question);
            if($validate_explanation === 'no_question_explanation') {
                $return_message = 'has_errors';
            }

            // check to see if the question is a slider or mc choice
            if($question['question_type'] === 'mc') {
                //TODO
                // add mc_options if mc question type
                $this->validate_question_mc_options($question);
            } elseif($question['question_type'] === 'slider') {
                // validate slider
                $this->validate_question_slider($question);
            } else {
                // should never happen...
                $this->add_error('Question '.($question['question_order']+1).' does not have a question type (multiple choice, slider, etc).');
            }
            $i++;
        }
        return $return_message;
    }


    /**
    * Checks questions for titles
    * @return true if no question, false if there are questions
    *
    */
    public function validate_question_title($question) {
        $return_message = 'has_title';
        if(empty($question['question_title'])) {
            $this->add_error('Question '.($question['question_order']+1).' is missing an actual question.');
            $return_message = 'no_title';
        }

        return $return_message;
    }

    /**
    * Checks questions for answer explanation
    * @return string 'has_question_explanation' if found, 'no_question_explanation' if not found
    *
    */
    public function validate_question_explanation($question) {
        $return_message = 'has_question_explanation';
        if(empty($question['question_explanation'])) {
            $this->add_error('Question '.($question['question_order']+1).' is missing an answer explanation.');
            $return_message = 'no_question_explanation';
        }

        return $return_message;
    }


    /**
    * Checks questions for mc_options (if it should have them)
    * @return string 'has_mc_options' if found, 'no_mc_options' if not found
    *
    */
    public function validate_question_mc_options($question) {

        $return_message = 'no_mc_options';
        if(!array_key_exists('mc_option', $question)) {
            return $return_message;
        } else {
            $mc_options = $question['mc_option'];
        }

        if(empty($mc_options)) {
            $this->add_error('Question '.($question['question_order']+1).' is missing multiple choice options.');
            $return_message = 'no_mc_options';
            return $return_message;
        }

        if(count($mc_options) === 1) {
            $this->add_error('Question '.($question['question_order']+1).' does not have enough multiple choice options.');
        } else {
            $mc_option_has_correct = false;
            foreach($mc_options as $option) {
                // check for values
                if($option['mc_option_content'] === '') {
                   $this->add_error('Question '.($question['question_order']+1).' has an empty Multiple Choice Option field.');
                }
                // check to see if ANY one has been chosen
                if((int)$option['mc_option_correct'] === 1) {
                    $mc_option_has_correct = true;
                    // we have a correct option! yay! Everything is good.
                    $return_message = 'has_mc_options';
                }
            }
            if($mc_option_has_correct !== true ) {
                $this->add_error('Question '.($question['question_order']+1).' needs a correct Multiple Choce Option answer to be selected.');
            }
        }

        return $return_message;
    }

    public function validate_question_slider($question) {
        $return_message = 'valid';
        if(!array_key_exists('slider', $question)) {
            $return_message = 'key_does_not_exist';
            return $return_message;
        } else {
            $slider = $question['slider'];
        }

        if(empty($slider)) {
            $this->add_error('Question '.($question['question_order']+1).' Slider has no values.');
            $return_message = 'no_slider_values';
            return $return_message;
        }

        // check all the necessary fields for values
        $required_fields = array(
                            'slider_id',
                            'slider_range_low',
                            'slider_range_high',
                            'slider_correct_low',
                            'slider_correct_high',
                            'slider_increment'
                    );

        $empty_fields = false;

        foreach($required_fields as $required) {
            // check if it's empty or not and that it doesn't match a 0 because
            // 0 is a valid entry
            if(empty($slider[$required]) && $slider[$required] !== (float) 0) {
                $empty_fields = true;
                $this->add_error('Question '.($question['question_order']+1).' Slider field '.$required.' is empty.');
            }
        }

        // if we have empty fields, just finish the validation check now
        if($empty_fields === true) {
            $return_message = 'missing_required_slider_fields';
            return $return_message;
        }
        if($slider['slider_range_high'] <= $slider['slider_range_low'] ) {
            $return_message = 'invalid';
            $this->add_error('Slider range for Question '.($question['question_order']+1).' needs to be changed.');
        }

        if($slider['slider_correct_high'] < $slider['slider_correct_low']) {
            $return_message = 'invalid';
            $this->add_error('Question '.($question['question_order']+1).' Slider Correct Answer High value is greater than the Correct Low value.');
        }

        if($slider['slider_range_high'] < $slider['slider_correct_high']  ) {
            $return_message = 'invalid';
            $this->add_error('Question '.($question['question_order']+1).' Slider Correct Answer is higher than the slider range. Increase the Slider End value or decrease the Slider Correct value.');
        }

        if($slider['slider_correct_low'] < $slider['slider_range_low']  ) {
            $return_message = 'invalid';
            $this->add_error('Question '.($question['question_order']+1).' Slider Correct answer is lower than the slider range. Decrease the Slider Start value or increase the Slider Correct answer.');
        }

        // get all increments into an array
        if($slider['slider_increment'] === (float) 0) {
            $return_message = 'invalid';
            $this->add_error('Question '.($question['question_order']+1).' Slider increment cannot be 0.');
        }
        // check that we're less than 1001 intervals
        elseif( 1000 < ($slider['slider_range_high'] - $slider['slider_range_low']) / $slider['slider_increment'] ) {
            $return_message = 'invalid';
            $this->add_error('Question '.($question['question_order']+1).' has '.($slider['slider_range_high'] - $slider['slider_range_low']) / $slider['slider_increment'].' intervals. It cannot have more than 1000 intervals. Decrease the Slider Low/High Range values or increase the Slider Increment value.');
        } else {
            // check to make sure the increment allows user to select the correct answer
            // loop through the numbers to validate the increment
            // stop at 1000, that's too many.
            $start = $slider['slider_range_low'];
            $end = $slider['slider_range_high'];
            $current_number = $start;

            while($current_number <= $end) {
                // check if we're in the correct range
                if($slider['slider_correct_low'] <= $current_number && $current_number <= $slider['slider_correct_high'] ) {
                    // we've got a correct answer!
                    break;
                }
                // we're above the correct answer high, then break and return the error message. no reason to keep checking
                elseif($slider['slider_correct_high'] < $current_number ) {
                    $this->add_error('Question '.($question['question_order']+1).' Slider correct answer is impossible to select with a Slider Increment value of '.$slider['slider_increment'].'. Change the correct answer value or increment value.');
                    break;
                } else {
                    // increase the interval and keep going
                    $current_number = $current_number + $slider['slider_increment'];
                }
            }

        }

        return $return_message;
    }

    /**
    * Get the user_action['action']
    * @param response_obj
    * @return string set in the user_action['action']
    */
    public function get_user_action_action() {
        return $this->user_action['action'];
    }

    /**
    * Get the user_action['element']
    * @param response_obj
    * @return string set in the user_action['element']
    */
    public function get_user_action_element() {
        return $this->user_action['element'];
    }

    /**
    * Get the user_action['details']  array
    * @param response_obj
    * @return array set in the user_action['details']
    */
    public function get_user_action_details() {
        return $this->user_action['details'];
    }

    /**
    * Get the error messages array
    * @param response_obj
    * @return array set in the $messages['error']
    */
    public function get_error_messages() {
        return $this->message['error'];
    }

    /**
    * Get the success messages array
    * @param response_obj
    * @return array set in the $messages['success']
    */
    public function get_success_messages() {
        return $this->message['success'];
    }

    /**
    * Validate a quiz
    * @param $quiz (array) a 100% complete quiz array
    * @return (mixed) true if valid, false if not
    */
    public function validate_quiz($quiz) {
        $quiz_options = $quiz['quiz_options'];
        // check key_exists and not empty
        $quiz_keys = array('quiz_title');
        $quiz_option_keys = array(
                              'quiz_title_display',
                              'quiz_text_color',
                              'quiz_bg_color',
                              'quiz_width'
                          );
        $valid = $this->validate_is_set($quiz, $quiz_keys);
        $valid_options = $this->validate_is_set($quiz_options, $quiz_option_keys);
        // we have values! let's keep on going with our check
        if($valid !== false) {
            // validate quiz title
            $this->validate_quiz_title($quiz['quiz_title']);
            // validate quiz title display
            $this->validate_quiz_title_display($quiz_options['quiz_title_display']);
            // validate hex values
            $hex_keys = array(
                                'quiz_text_color',
                                'quiz_bg_color'
                               );
            $this->validate_hex_values($quiz_options, $hex_keys);
            // validate css_measurement values
            $css_measurement_keys = array('quiz_width');
            $this->validate_css_measurement_values($quiz_options, $css_measurement_keys);
        }
    }

    public function validate_quiz_title($value) {
        $valid = false;
        if(empty($value)) {
            $this->add_error("Quiz Title can't be empty. Please enter a Title for your Quiz.");
        } else {
            $valid = true;
        }
        return $valid;
    }

    public function validate_quiz_title_display($value) {
        $valid = false;
        if($value !== 'show' && $value !== 'hide') {
            $this->add_error('Quiz Title Display must equal "show" or "hide".');
        } else {
            $valid = true;
        }
        return $valid;
    }

    public function validate_is_set($array = array(), $keys = array()) {
        // set as true and prove it's not valid
        $valid = true;
        if(empty($array) || empty($keys)) {
            return false;
        }
        // check for all values
        foreach($keys as $key) {
            // validate the the key exists in the array
            $validate = $this->validate_exists($array, $key);

            // if it doesn't exist, set valid to false
            if($validate === false) {
                $valid = false;
            } else {
                // it exists, so see if it's empty
                $validate = $this->validate_not_empty($array, $key);
                // if it's empty, set $valid to false
                if($validate === false) {
                    $valid = false;
                }
            }
        }

        return $valid;
    }

    public function validate_exists($array, $key) {
        $exists = false;
        if(array_key_exists($key, $array)) {
            $exists = true;
        } else {
            // if key doesn't exist, add error
            // if empty, add error
            $this->add_error($key.' does not exist.');
        }
        return $exists;
    }

    public function validate_not_empty($array, $key) {
        $not_empty = false;
        if(!empty($array[$key])) {
            $not_empty = true;
        } else {
            // if empty, add error
            $this->add_error($key.' is empty.');
        }
        return $not_empty;
    }

    public function validate_hex_values($array, $keys) {
        $valid = true;
        if(!empty($keys)) {
            foreach($keys as $key) {
                $validate = $this->validate_hex($array[$key]);
                if($validate === false) {
                    // generate a good error message
                    $this->add_error('Hex Color value for '.$key.' was not valid. Hex Color Value must be a valid Hex value like #ffffff');
                    $valid = false;
                }
            }
        }
        return $valid;
    }

    public function validate_css_measurement_values($array, $keys) {
        $valid = true;
        if(!empty($keys)) {
            foreach($keys as $key) {
                $validate = $this->validate_css_measurement($array[$key]);
                if($validate === false) {
                    // generate a good error message
                    // give a good error message
                    $this->add_error('CSS Measurement value for '.$key.' is not valid. Measurements can be any valid CSS Measurement such as 100%, 600px, 30rem, 80vw');
                    $valid = false;
                }
            }
        }
        return $valid;
    }
}
