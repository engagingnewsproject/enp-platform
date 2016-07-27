<?php
/**
* A Utility class for managing cookies
* on the Quiz Take side of things
*/
class Enp_quiz_Cookies_Quiz_take extends Enp_quiz_Cookies {
    public $path;

    public function __construct($path) {
        $this->path = $path;
    }

    /**
    * Set the user_id. Used globally on root path for every quiz a user takes.
    */
    public function set_cookie__user_id($user_id) {
        $this->set_cookie('enp_quiz_user_id', $user_id);
    }

    /**
    * Set if a question was answered correctly or not
    * 0 = incorrect, 1 = correct
    */
    public function set_cookie__question_correct($question_id, $correct) {
        $this->set_cookie('enp_question_'.$question_id.'_is_correct', $correct, $this->path);
    }

    /**
	* Sets enp_current_question_id cookie
	* @param $question_id = id of the question you want
	*					    to set the current question cookie to
	*/
	public function set_cookie__current_question($question_id) {
        $this->set_cookie('enp_current_question_id', $question_id, $this->path);
	}

	/**
	* Sets correctly_answered cookie
	* @param $correctly_answered = number of correctly answered questions
	*/
	public function set_cookie__correctly_answered($correctly_answered) {
        $this->set_cookie('enp_correctly_answered', $correctly_answered, $this->path);
	}

	/**
	* Sets enp_quiz_state cookie
	* @param $state = state of the quiz
	*				  (question, question_explanation, quiz_end)
	*/
	public function set_cookie__state($state) {
        $this->set_cookie('enp_quiz_state', $state, $this->path);
	}

    /**
	* Sets enp_quiz_response_id cookie
	* @param $response_id = response_id of the quiz
	*/
	public function set_cookie__response_id($response_id) {
        $this->set_cookie('enp_response_id', $response_id, $this->path);
	}

    /**
	* We need cookies for quiz state and how they're doing score wise
	* On each page load we'll save cookies as a snapshot of the current state
    * @param $quiz_take (object) quiz take object
	*/
	public function set_quiz_cookies($quiz_take) {
        // set state
        $state = $quiz_take->state;
        $response = $quiz_take->response;

		// check for errors first
		if(!empty($quiz_take->error)) {
			return false;
		}

		// quiz state
		if(!empty($state)) {
			$this->set_cookie__state($state);
		} else {
			return false;
		}

		// question number
		if($state === 'question') {
			$this->set_cookie__current_question( $quiz_take->current_question_id);
		}

		// correctly answered
		if($state === 'question_explanation' &&  isset($response->correctly_answered) ) {
			// set the total questions gotten right
			$this->set_cookie__correctly_answered( $response->correctly_answered);

			// set a cookie for this individual question's response (correct/incorrect)
			$this->set_cookie__question_correct($quiz_take->current_question_id, $response->response_correct);
		}

	}

    /**
    * Set a quiz back to it's clean, initial state as if it
    * was just getting started. Used on Quiz Restart button clicks
    * because state cookies sometimes don't get reset correctly
    * @param $quiz (object)
    */
    public function reset_quiz_cookies($quiz) {
        $this->unset_quiz_cookies($quiz);
		// all quiz reset work
	    $question_ids = $quiz->get_questions();
	    // set the first question off of the question_ids from the quiz
        // since it's the beginning state
		// set current question cookie
		$this->set_cookie__current_question($question_ids[0]);
	    // set our state to 'question,' since it's the starting
        // state
		$this->set_cookie__state('question');
    }

    /**
    * Unset all cookies from a quiz
    * @param $quiz (object)
    */
	public function unset_quiz_cookies($quiz) {
		$quiz_id = $quiz->get_quiz_id();
		$question_ids = $quiz->get_questions();
		// loop through all questions and unset their cookie
		foreach($question_ids as $question_id) {
			$this->unset_cookie__question_correct($question_id);
		}
		// unset the current question id
		$this->unset_cookie__current_question_id();
		// unset the total correct
		$this->unset_cookie__correctly_answered();
		// unset the state
		$this->unset_cookie__quiz_state();
		// unset response id
		$this->unset_cookie__response_id();

	}

    // unset response id cookie
    public function unset_cookie__response_id() {

		$this->unset_cookie('enp_response_id', $this->path);
    }

    // unset quiz state cookie
    public function unset_cookie__quiz_state() {
		$this->unset_cookie('enp_quiz_state', $this->path);
    }

    // unset correctly_answered cookie
    public function unset_cookie__correctly_answered() {
		$this->unset_cookie('enp_correctly_answered', $this->path);
    }

    // unset current_question_id cookie
    public function unset_cookie__current_question_id() {
		$this->unset_cookie('enp_current_question_id', $this->path);
    }

    // unset question_correct cookie
    public function unset_cookie__question_correct($question_id) {
		$this->unset_cookie('enp_question_'.$question_id.'_is_correct', $this->path);
    }
}
