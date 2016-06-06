<?php
/**
 * Save responses from people taking quizzes
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/includes
 *
 *
 * @since      0.0.1
 * @package    Enp_quiz
 * @subpackage Enp_quiz/database
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */

class Enp_quiz_Save_quiz_take {
    public static $return = array('error'=>array()),
                  $quiz,
                  $save_response_quiz_obj,
                  $save_response_question_obj,
                  $next_question = array(),
                  $quiz_end = array(),
                  $last_question_flag = false;

    public function __construct() {

    }

    public function save_quiz_take($data) {

        $valid = $this->validate_save_quiz_take($data);

        if($valid === false) {
            self::$return = json_encode(self::$return);
            return self::$return;
        }

        // merge data into our response
        self::$return = array_merge($data, self::$return);

        self::$quiz = new Enp_quiz_Quiz($data['quiz_id']);

        // create our save quiz response class
        self::$save_response_quiz_obj = new Enp_quiz_Save_quiz_take_Response_quiz();
        // create our save response class
        self::$save_response_question_obj = new Enp_quiz_Save_quiz_take_Response_question();

        // Check that they submitted a question on the form
        if($data['user_action'] === 'enp-question-submit') {
            // validate that they have a correct response (ie their slider response is within range or their MC Option ID is valid for that question)
            $valid_response = self::$save_response_question_obj->validate_response_data(self::$return);
            // if it's not true, get outta here
            if($valid_response !== true) {
                self::$return['error'][] = 'Invalid response.';
                self::$return = json_encode(self::$return);
                return self::$return;
            }

            $save_response_quiz_response = self::$save_response_quiz_obj->update_response_quiz_started(self::$return);
            // check to make sure whatever we saved returned a response
            if(!empty($save_response_quiz_response)) {
                self::$return = array_merge(self::$return, $save_response_quiz_response);
            }

            $save_response_question_response = self::$save_response_question_obj->update_response_question(self::$return);
            // check to make sure whatever we saved returned a response
            if(!empty($save_response_question_response)) {
                self::$return = array_merge(self::$return, $save_response_question_response);
            }
        }

        // get current question & build next question
        if(array_key_exists('question_id', $data) && !empty($data['question_id'])) {
            $current_question_id = $data['question_id'];
            $this->set_next_question($current_question_id);
        }

        // build what state we'll be in on the response (what should load for the user NOW?)
        $this->build_state($data);
        // build what to do next (for JS to pre-load) (AFTER this state)
        $this->build_next_state(self::$return);

        // if either state is quiz_end, generate the quiz_end data
        if(self::$return['state'] === 'quiz_end' || self::$return['next_state'] === 'quiz_end') {
            $this->set_quiz_end();
        }

        // update quiz/question data
        $this->update_quiz_data($data);
        $this->update_question_data();

        // convert to JSON and return it
        self::$return = json_encode(self::$return);
        return self::$return;
    }

    protected function validate_save_quiz_take($data) {
        $valid = false;
        // check to see if we have data
        if(empty($data)) {
            self::$return['error'][] = 'No Data to save.';
        }
        // check if we have a user action set
        if(empty($data['user_action'])) {
            self::$return['error'][] = 'No user action set.';
        }
        // check to make sure we have a quiz id
        if(empty($data['quiz_id'])) {
            self::$return['error'][] = 'No Quiz ID set.';
        }
        if(empty($return['error'])) {
            $valid = true;
        }
        return $valid;
    }

    /**
    * Our API should give all the info of what to do
    * when it returns. So let's build that into our JSON response.
    *
    * @param $response (array) our current response to be sent to the browser
    */
    private function build_state($data) {

        if($data['user_action'] === 'enp-question-submit') {
            // if  they submitted a question, the next state will always be showing
            // the question explanation
            $state = 'question_explanation';
        } elseif($data['user_action'] === 'enp-next-question') {
            // Might be next question, might be the end of the quiz
            if(self::$last_question_flag === true && (int) $data['question_id'] === (int) self::$next_question->question_id) {
                // see if we're on the last question or not
                // we're at the quiz end if the next question array is empty
                $state = 'quiz_end';
            } else {
                // we have a question to display! Set it to the question state
                $state = 'question';
            }
        } elseif($data['user_action'] === 'enp-quiz-restart') {
            $state = 'question';
        } else {
            // TODO: Error
            $state = false;
        }

        self::$return['state'] = $state;
    }

    /**
    * If the JS wants to, it can use this response to preload content
    *
    * @param $response (array) our current response to be sent to the browser
    * @return $response (array) appended with our next actions
    */
    protected function build_next_state() {
        if(self::$return['state'] === 'question') {
            self::$return['next_state'] = 'question_explanation';
        }
        elseif(self::$return['state'] === 'quiz_end') {
            self::$return['next_state'] = 'question';
        }
        elseif(self::$return['state'] === 'question_explanation' && self::$last_question_flag === true) {
            // no question next, so we're at the end
            // build the final page with their data
            self::$return['next_state'] = 'quiz_end';
        }
        else {
            // we have another question!
            // get the JSON
            self::$return['next_state'] = 'question';
        }

    }

    /**
    * Finding and setting the next question up in the series
    * Used to preload and see where we're at in the question series
    *
    */
    protected function set_next_question($current_question_id) {
        // get the questions for this quiz
        $question_ids = self::$quiz->get_questions();
        $question_count = count($question_ids);
        // see where we're at in the question cycle
        if(!empty($question_ids)) {
            $i = 0;
            foreach($question_ids as $question_id) {
                if($question_id === (int) $current_question_id) {
                    // this is the current one, so we need the NEXT one, if there is one
                    $i++;
                    if(isset($question_ids[$i]) && is_int($question_ids[$i])) {

                        // this is the next one!
                        // generate the question object JSON
                        self::$next_question = new Enp_quiz_Question($question_ids[$i]);
                        self::$return['next_question'] = self::$next_question->get_take_question_array();
                        // no need to loop anymore
                        break;
                    } elseif($question_count === $i) {
                        self::$last_question_flag = true;
                        $i = $i - 1;
                        self::$next_question = new Enp_quiz_Question($question_ids[$i]);
                        self::$return['next_question'] = self::$next_question->get_take_question_array();
                        // no need to loop anymore
                        break;
                    }
                }
                $i++;
            }
        }

    }

    /**
    * Set the quiz_end data when the state or next_state is quiz_end
    */
    protected function set_quiz_end() {
        $quiz_end = new Enp_quiz_Take_Quiz_end(self::$quiz);
        self::$return['quiz_end'] = (array) $quiz_end;
    }

    protected function update_quiz_data($data) {
        // if it doesn't match one of the states we need, go ahead and exit
        if(self::$return['state'] !== 'question_explanation' && self::$return['state'] !== 'quiz_end') {
            return false;
        }

		$quiz_data = new Enp_quiz_Save_quiz_take_Quiz_data(self::$quiz);
        $question_ids = self::$quiz->get_questions();
        // if the new returned state is question_explanation and the submitted question_id was the first question of the quiz, then someone Started the quiz.
        if(self::$return['state'] === 'question_explanation' && (int) $data['question_id'] === (int) $question_ids[0]) {
			$quiz_data->update_quiz_starts();
		} elseif (self::$return['state'] === 'quiz_end') {
            // update the response quiz table with the new score and state
            self::$save_response_quiz_obj->update_response_quiz_completed(self::$return);
            // if the new returnd state is quiz_end, then they finished the quiz
            // get their score
		    $quiz_data->update_quiz_finishes(self::$return['quiz_end']['score']);

        }

    }

    protected function update_question_data() {
        // if we're on a question, update the question view for the next question
        // and create the next response
		if(self::$return['state'] === 'question') {
			$save_question_view = new Enp_quiz_Save_quiz_take_Question_view(self::$next_question->question_id);


            // create the next question response
            $data = self::$return;
            $data['question_id'] = self::$next_question->question_id;
    		// create a new question response entry
    		self::$save_response_question_obj->insert_response_question($data);
		}
    }

}
?>
