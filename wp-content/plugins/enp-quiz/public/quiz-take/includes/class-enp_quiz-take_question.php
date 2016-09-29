<?php

/**
 * Question level class for building what to render and set for questions
 * when taking a quiz
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Take_Question {
	public 	$qt, // Enp_quiz_Take Object
			$question,
			$question_response_correct,
			$question_explanation_title,
			$question_explanation_percentage,
			$question_next_step_text;
	/**
	* Build our states and set our variables
	*/
	public function __construct($qt) {
		$this->qt = $qt;
		// set question
		$this->set_question();

		// set if they got the question right or not
		$this->set_question_response_correct();
		// set random vars if necessary
		if($this->qt->state === 'question_explanation') {
			$this->set_question_explanation_vars();
		}
	}

	/**
	* Set the data context for the question.
	* Decide which question we need based on current quiz state.
	*
	* @param $response (array) response from server, if present
	* @param $quiz (object) Enp_quiz_Quiz()
	* @return $question (array) The question we need to display
	*/
	public function set_question() {
		// if we have a question id, get the question data for it
		if(!empty($this->qt->current_question_id)) {
			$question = new Enp_quiz_Question($this->qt->current_question_id);
		}

		$this->question = $question;
	}

	/**
	* See if someone answered the question correctly or not
	*/
	public function set_question_response_correct() {
		$title = 'incorrect';
		// cookie name
		$question_response_cookie_name = 'enp_current_question_is_correct';
		// first, check for a response
		if(isset($this->qt->response->response_correct) && !empty($this->qt->response->response_correct)) {
			if($this->qt->response->response_correct === '1') {
				$title = 'correct';
			}
		}
		// check from cookies
		elseif(isset($_COOKIE[$question_response_cookie_name])) {
			if($_COOKIE[$question_response_cookie_name] === '1') {
				$title = 'correct';
			}
		}

		$this->question_response_correct = $title;
	}

	public function set_question_explanation_vars() {
		$this->set_question_explanation_title();
		$this->set_question_explanation_percentage();
		$this->set_question_next_step_text();
	}

	public function set_question_explanation_title() {
		$this->question_explanation_title = $this->question_response_correct;
	}

	public function set_question_explanation_percentage() {
		if($this->question_response_correct === 'correct') {
			$percentage = $this->question->get_question_responses_correct_percentage();
		} else {
			$percentage = $this->question->get_question_responses_incorrect_percentage();
		}
		$this->question_explanation_percentage = $percentage;
	}

	public function set_question_next_step_text() {
		// find out if it's the last question or not
		$qustion_ids = $this->qt->quiz->get_questions();
		// get the last question ID
		$last_question_id = end($qustion_ids);
		$current_question_id = $this->question->get_question_id();

		if((int) $last_question_id === (int) $current_question_id) {
			// we're on the last question!
			$next_step_text = 'View Results';
		} else {
			// we're not on the last question yet
			$next_step_text = 'Next Question';
		}
		$this->question_next_step_text = $next_step_text;
	}

	public function get_question_explanation_title() {
		return $this->question_explanation_title;
	}

	public function get_question_explanation_percentage() {
		// build this off the response
		return $this->question_explanation_percentage;
	}

	public function get_question_next_step_text() {
		// build this off the response
		return $this->question_next_step_text;
	}

	public function get_question_classes() {
		$classes = 'enp-question__fieldset--'.$this->question->get_question_type().' ';
		if($this->qt->state === 'question') {

		} elseif($this->qt->state === 'question_explanation') {
			$classes = 'enp-question__answered';
		}

		return $classes;
	}

	public function get_init_json() {
		$question = clone $this;
		unset($question->qt);
		echo '<script type="text/javascript">';
		// print this whole object as js global vars in json
			echo 'var take_question_json = '.json_encode($question).';';
			echo 'var init_question_json = '.$question->question->get_take_question_json().';';
		echo '</script>';
		// unset the cloned object
		unset($question);
	}

	public function question_js_templates() {
		// clone the object so we don't reset its own values
		$qt_question = clone $this;
		foreach($qt_question->question as $key => $value) {
			if($key === 'question_image') {
				// question_image should be blank
				// because it messes up the templating for srcset
				// and src when it thinks it has a value
				$qt_question->question->$key = '';
			} else {
				$qt_question->question->$key = '{{'.$key.'}}';
			}
		}
		// force the state to 'question'
		$qt_question->qt->state = 'question';
		// image template
		$template = '<script type="text/template" id="question_image_template">';
		ob_start();
		include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'partials/question-image.php');
		$template .= ob_get_clean();
		$template .= '</script>';
		$template .= '<script type="text/template" id="question_template">';
		ob_start();
		include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'partials/question.php');
		$template .= ob_get_clean();
		$template .= '</script>';



		return $template;
	}
	/**
	* I can't think of a better way to do this right now, but I think this is OK
	* It loops all keys in the object and sets the values as handlebar style strings
	* and injects it into the template
	*/
	public function question_explanation_js_template() {
		// clone the object so we don't reset its own values
		$qt_question = clone $this;

		foreach($qt_question->question as $key => $value) {
			if($key === 'question_image') {
				// set it to a bogus image value that matches at least
			}
			$qt_question->question->$key = '{{'.$key.'}}';
		}

		foreach($qt_question as $key => $value) {
			// we don't want to unset our qt or question object
			if($key !== 'question') {
				$qt_question->$key = '{{'.$key.'}}';
			}
		}

		$template = '<script type="text/template" id="question_explanation_template">';
		ob_start();
		include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'partials/question-explanation.php');
		$template .= ob_get_clean();
		$template .= '</script>';

		return $template;
	}


	/**
	* I can't think of a better way to do this right now, but I think this is OK
	* It loops all keys in the object and sets the values as handlebar style strings
	* and injects it into the template
	*/
	public function mc_option_js_template() {
		$mc_option = new Enp_quiz_MC_option(0);
		foreach($mc_option as $key => $value) {
			$mc_option->$key = '{{'.$key.'}}';
		}
		$template = '<script type="text/template" id="mc_option_template">';
		ob_start();
		include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/mc-option.php');
		$template .= ob_get_clean();
		$template .= '</script>';

		return $template;
	}

	public function slider_js_template() {
		$slider = new Enp_quiz_Slider(0);
		foreach($slider as $key => $value) {
			$slider->$key = '{{'.$key.'}}';
		}

		$qt_question = clone $this;
		$qt_question->question_id = '{{question_id}}';

		$template = '<script type="text/template" id="slider_template">';
		ob_start();
		include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/slider.php');
		$template .= ob_get_clean();
		$template .= '</script>';

		$template .= '<script type="text/template" id="slider_range_helpers_template">';
		ob_start();
		include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'partials/slider--range-helpers.php');
		$template .= ob_get_clean();
		$template .= '</script>';

		return $template;
	}



}
