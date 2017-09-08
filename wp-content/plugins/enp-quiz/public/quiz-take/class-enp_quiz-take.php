<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/quiz-take
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version,
 * and registers & enqueues quiz take scripts and styles
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Take {
	public $quiz,
		   $ab_test_id = false,
		   $user_id,
		   $response_quiz_id,
		   $state = '',
		   $correctly_answered,
		   $current_question_id,
		   $next_question_id,
		   $current_question_number,
		   $nonce,
		   $cookie_manager,
		   $quiz_url, // set as ab test or quiz url
		   $response = array(),
		   $error = array();

	/**
	* Constructor to set a Version number and load all necessary files
	*/
	public function __construct() {
		// Define Version
		if(!defined('ENP_QUIZ_VERSION')) {
			// also defined in enp_quiz.php for the Quiz Create side of things
			define('ENP_QUIZ_VERSION', '0.2.0');
		}
		// require files
		$this->load_files();

	}

	/**
	* Set the ab_test_id for our class
	*/
	public function set_ab_test_id($ab_test_id) {
		$this->ab_test_id = $ab_test_id;
	}

	/**
	* Get the right quiz, process it (including saves, if necessary),
	* and set up all states and variables for the quiz to be
	* properly loade
	*/
	public function load_quiz($quiz_id = false) {
		// set nonce
		$this->set_nonce($quiz_id);
		$this->cookie_manager = $this->set_cookie_manager($quiz_id);

		$this->quiz_url = $this->set_quiz_url($quiz_id);
		// get our quiz
		$this->quiz = new Enp_quiz_Quiz($quiz_id);

		// set user_id
		$this->set_user_id();

		// check if we have a posted var
		if(isset($_POST['enp-question-submit'])) {
			// sets $this->response;
            $this->save_quiz_take();
        }

		// make sure a quiz got loaded
		$this->validate_quiz();

		if(isset($_POST['enp-quiz-restart'])) {
			// sets $this->response;
            $this->quiz_restart();
        }

		// check for any errors
		$this->set_error_messages();
		// set a response id
		$this->set_response_quiz_id($quiz_id);
		// set our state
		$this->set_state();

		// set random vars we'll need
		$this->set_current_question_id();
		$this->set_current_question_number();
		// set how many they've gotten right so far
		$this->set_correctly_answered();
		// set cookies we'll need on reload or correct/incorrect amounts
		$this->cookie_manager->set_quiz_cookies($this);

		// if it's the first question, we need to save the
		// initial quiz view and question view
		$this->save_initial_view_data();

		// if we're doing AJAX, echo the response back to the server
		// this is echo-ed after everything else is done so we don't get "header already sent" errors from PHP
		if(isset($_POST['doing_ajax'])) {
			header('Content-type: application/json');
			echo json_encode($this->response);
			// don't produce anymore HTML or render anything else
			// otherwise the server keeps going and sends us all
			// the HTML of the page too, but we just want the JSON data
			die();
		}
	}

	/**
	 * Stylesheets for quiz take
	 *
	 * @since    0.0.1
	 */
	public function styles() {
		$styles = array(ENP_QUIZ_PLUGIN_URL.'public/quiz-take/css/enp_quiz-take.min.css');
		foreach($styles as $href) {
			echo '<link rel="stylesheet" type="text/css" href="'.$href.'?v'.ENP_QUIZ_VERSION.'" media="all" />';
		}
	}

	/**
	 * JavaScript for quiz take
	 *
	 * @since    0.0.1
	 */
	public function scripts() {
		// use minified version on engagingnewsproject.org
		if($_SERVER['HTTP_HOST'] === 'engagingnewsproject.org' || $_SERVER['HTTP_HOST'] === 'mediaengagement.org') {
			$ext = '.min';
		} else {
			$ext = '';
		}

		$scripts = array(
						"https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js",
						// if developing offline
						// ENP_QUIZ_PLUGIN_URL.'public/quiz-take/js/dist/jquery.min.js',
						ENP_QUIZ_PLUGIN_URL.'public/quiz-take/js/dist/utilities'.$ext.'.js',
						ENP_QUIZ_PLUGIN_URL.'public/quiz-take/js/dist/quiz-take'.$ext.'.js?v'.ENP_QUIZ_VERSION,
					);
		foreach($scripts as $src) {
			echo '<script src="'.$src.'"></script>';
		}
	}

	/**
	* Load the data of our class into the document in JSON
	* format so our JS can hook into it and process it accordingly
	*/
	public function get_init_json() {
		$json = clone $this;
		if($json->ab_test_id === false) {
			$json->ab_test_id = 0;
		}

		// output the quiz level json and ab test id
		echo '<script type="text/javascript">';
			echo 'var ab_test_id_json = {"ab_test_id":"'.$json->ab_test_id.'"};';
			// print this whole object as js global vars in json
			echo 'var quiz_json = '.json_encode($json->quiz).';';
		echo '</script>';
		// remove quiz from the object so we don't print it again
		unset($json->quiz);
		echo '<script type="text/javascript">';
		// print this whole object as js global vars in json
			echo 'var qt_json = '.json_encode($json).';';
		echo '</script>';
		// unset the cloned object
		unset($json);

	}

	/**
	* Require all the files we'll need. This is loaded outside of WP, so we need
	* to require everything we need on our own.
	*/
	public function load_files() {
		// require the necessary files
        require_once ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-quiz.php';
        require_once ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-question.php';
        require_once ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-mc_option.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-slider.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-ab_test.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-nonce.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-cookies.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-current-url.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'public/quiz-take/includes/class-enp_quiz-cookies_quiz_take.php';

		// Quiz Take Classes
		require_once ENP_QUIZ_PLUGIN_DIR . 'public/quiz-take/includes/class-enp_quiz-take_quiz_end.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'public/quiz-take/includes/class-enp_quiz-take_question.php';
        // Database
        require_once ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_db.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_take.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_take_question_view.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_take_response_quiz.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_take_response_ab_test.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_take_response_question.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_take_response_mc.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_take_response_slider.php';
		require_once ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_take_quiz_data.php';

	}

	/**
	* Output all the meta tags our <head> needs
	*/
	public function meta_tags() {
		echo '<meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <meta property="og:url" content="http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] .'/'. $_SERVER['REQUEST_URI'] .'" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="'.$this->quiz->get_encoded('facebook_title', 'htmlspecialchars').'" />
    <meta property="og:description" content="'.$this->quiz->get_encoded('facebook_description', 'htmlspecialchars').'" />';
	}

	/**
	* Generate and set the nonce for our form
	*/
	public function set_nonce($quiz_id) {
		// allow use of URLs for sessions is cookies off
		ini_set('session.use_cookies', 1);
		ini_set('session.use_only_cookies', 0);
		ini_set('session.use_trans_sid', 1);
		//Start the session
	   	session_start();
	   	if($this->ab_test_id !== false) {
		   // we're on an ab test
			$nonce_name = 'enp_quiz_take_ab_test_'.$this->ab_test_id.'_nonce';
	   	} else {
			$nonce_name = 'enp_quiz_take_'.$quiz_id.'_nonce';
	   	}

	   	//Start the class
	   	$this->nonce = new Enp_quiz_Nonce($nonce_name);
	}

	/**
	* Outputs the PHPSESSID into our form so we can pass it along when
	* cookies are turned off. This automatically gets added to forms when
	* people are using a PHP version that was compiled with
	* --enable-trans-sid, but we can't assume that everyone's will be
	* as BlueHost doesn't have it enabled. (https://www.sitepoint.com/community/t/php-sessions-and-phpsessid/1041/3)
	* If you do have it enabled, you'll get the PHPSESSID input in the HTML
	* twice, but it's not an issue.
	*/
	public function get_session_id_input() {
		return '<input type="hidden" name="PHPSESSID" value="'.session_id().'" />';
	}

	/**
	* Quick check to see if we have a valid quiz before moving on
	*/
	public function validate_quiz() {
		$quiz_id = $this->quiz->get_quiz_id();
		if(empty($quiz_id)) {
            echo 'Quiz not found';
            exit;
        } else {
			return true;
		}
	}

	/**
	* Add all of our SVG to the DOM
	*/
	public function load_svg() {
		$svg = file_get_contents(ENP_QUIZ_PLUGIN_URL.'public/quiz-take/svg/symbol-defs.svg');
	    return $svg;
	}

	/**
	* Quiz Option styles that we need to override our own CSS
	*/
	public function load_quiz_styles() {
		// figure out the width of our progress bar
		$progress_bar_width = $this->get_progress_bar_width();
		$text_color = $this->quiz->get_quiz_text_color();
		$bg_color = $this->quiz->get_quiz_bg_color();
		$border_color = $this->quiz->get_quiz_border_color();
		$button_color = $this->quiz->get_quiz_button_color();
		$correct_color = $this->quiz->get_quiz_correct_color();
		$incorrect_color = $this->quiz->get_quiz_incorrect_color();

		// get the CSS we need
		$bg_color_css = (strtolower($bg_color) !== '#ffffff' ? $this->get_bg_color_css() : '');
		$text_color_css = (strtolower($text_color) !== '#444444' ? $this->get_text_color_css() : '');
		$border_color_css = (strtolower($border_color) !== '#dddddd' ? $this->get_border_color_css() : '');
		$button_color_css = (strtolower($button_color) !== '#5887c0' ? $this->get_button_color_css() : '');
		$correct_color_css = (strtolower($correct_color) !== '#3bb275' ? $this->get_correct_color_css() : '');
		$incorrect_color_css = (strtolower($incorrect_color) !== '#f14021' ? $this->get_incorrect_color_css() : '');
		$custom_css = $this->quiz->get_quiz_custom_css_minified();

		return "<style tyle='text/css'>
$custom_css
$bg_color_css $text_color_css $border_color_css $button_color_css $correct_color_css $incorrect_color_css
#enp-quiz .enp-quiz__progress__bar {
	width: $progress_bar_width%;
}
</style>";

	}

	/**
	* Calculate how wide the progress bar should be
	* @return int
	*/
	public function get_progress_bar_width() {
		$progress_bar_width = $this->get_current_progress_bar_question_number()/$this->quiz->get_total_question_count();
		// reduce the number a little if we're at the very end so it still looks like there's more to go
		if($this->state !== 'quiz_end' && $progress_bar_width === 1) {
			$progress_bar_width = .9;
		}
		$progress_bar_width = number_format( $progress_bar_width * 100, 2 );

		return $progress_bar_width;
	}

	/**
	* Output HTML for the Progress Bar
	* @return string (HTML)
	*/
	public function get_progress_bar() {
		$progressbar =  '<div class="enp-quiz__progress__bar"
			role="progressbar"
			aria-valuetext="'.$this->get_progress_bar_text_value().'"
			aria-valuemin="1"
			aria-valuenow="'. $this->get_current_progress_bar_question_number().'"
			aria-valuemax="'.$this->quiz->get_total_question_count().'">

			<div class="enp-quiz__progress__bar__question-count">'.$this->get_progress_bar_display_text().'</div>
		</div>';
		return $progressbar;
	}

	/**
	* get_current_question_number() returns 0 if you're at the end, but
	* we need it to be the total questions if it's at the end.
	*/
	public function get_current_progress_bar_question_number() {
		if($this->state === 'quiz_end') {
			$progress_bar_question_number =  $this->quiz->get_total_question_count();
		} else {
			$progress_bar_question_number = $this->get_current_question_number();
		}

		return $progress_bar_question_number;
	}

	public function get_progress_bar_text_value() {
		if($this->state === 'quiz_end') {
			$progress_bar_text_value = 'Quiz Complete. '.  $this->get_correctly_answered().'/'. $this->quiz->get_total_question_count().' Correct.';
		} else {
			$progress_bar_text_value = 'Question '.  $this->get_current_progress_bar_question_number().' of '. $this->quiz->get_total_question_count();
		}
		return $progress_bar_text_value;
	}

	/**
	* Text to visually for the progress bar.
	* @return string (HTML)
	*/
	function get_progress_bar_display_text() {
		$progress_bar_display_text = '<span class="enp-quiz__progress__bar__question-count__current-number">'. ($this->state === 'quiz_end' ? $this->get_correctly_answered() :  $this->get_current_progress_bar_question_number()).'</span>/<span class="enp-quiz__progress__bar__question-count__total-questions">'.$this->quiz->get_total_question_count() . ($this->state === 'quiz_end' ? ' Correct':'').'</span>';

		return $progress_bar_display_text;
	}

	/**
	* All CSS for Background Color.
	* @return string
	*/
	public function get_bg_color_css() {
		$bg_color = $this->quiz->get_quiz_bg_color();
		return "
#enp-quiz .enp-quiz__container,
#enp-quiz .enp-option__label,
#enp-quiz .enp-explanation,
#enp-quiz .enp-question__container--explanation .enp-option__input--incorrect-clicked+.enp-option__label {
    background-color: $bg_color;
}";
	}

	/**
	* All CSS for text Color.
	* @return string
	*/
	public function get_text_color_css() {
		$text_color = $this->quiz->get_quiz_text_color();
		return "
#enp-quiz .enp-quiz__container,
#enp-quiz .enp-explanation,
#enp-quiz .enp-quiz__title,
#enp-quiz .enp-question__question,
#enp-quiz .enp-option__label,
#enp-quiz .enp-question__helper,
#enp-quiz .enp-results__score__title,
#enp-quiz .enp-results__encouragement,
#enp-quiz .enp-results__description,
#enp-quiz .enp-results__share-title,
#enp-quiz .enp-explanation__title,
#enp-quiz .enp-explanation__explanation,
#enp-quiz .enp-explanation__percentage,
#enp-quiz .enp-slider-input__prefix,
#enp-quiz .enp-slider-input__suffix,
#enp-quiz .enp-slider-input__input,
#enp-quiz .enp-slider-input__range-helper__number {
    color: $text_color;
}";
	}

	/**
	* All CSS for Border Color.
	* @return string
	*/
	public function get_border_color_css() {
		$border_color = $this->quiz->get_quiz_border_color();
		return "
#enp-quiz .enp-quiz__container,
#enp-quiz .enp-quiz__header {
	border-color: $border_color;
}";
	}

	/**
	* All CSS for Button Color.
	* @return string
	*/
	public function get_button_color_css() {
		$button_color = $this->quiz->get_quiz_button_color();
		return "
#enp-quiz .enp-btn,
#enp-quiz .enp-btn:hover,
#enp-quiz .enp-btn:focus,
#enp-quiz .enp-btn--add,
#enp-quiz .enp-question__submit,
#enp-quiz .enp-next-step__icon,
#enp-quiz .enp-question__submit__icon,
#enp-quiz .enp-results__share__item__icon {
	background-color: $button_color;
}

#enp-quiz .enp-btn:hover,
#enp-quiz .enp-btn:focus {
	box-shadow: inset 0 2px 0 rgba(0,0,0,.2);
}

#enp-quiz .enp-next-step,
#enp-quiz .enp-next-step:hover,
#enp-quiz .enp-next-step:focus {
	background: transparent;
	color: $button_color;
	border-color: $button_color;
	box-shadow: none;
}
#enp-quiz .enp-next-step .enp-next-step__icon {
	background: transparent;
	fill: $button_color;
}
#enp-quiz .enp-next-step:hover .enp-next-step__icon,
#enp-quiz .enp-next-step:focus .enp-next-step__icon {
	background: $button_color;
	fill: #ffffff;
}
#enp-quiz .enp-next-step .enp-next-step__icon,
#enp-quiz .enp-question__submit:focus .enp-question__submit__icon,
#enp-quiz .enp-question__submit:hover .enp-question__submit__icon {
	fill: $button_color;
}";
	}

	/**
	* All CSS for "Correct" Color.
	* @return string
	*/
	public function get_correct_color_css() {
		$correct_color = $this->quiz->get_quiz_correct_color();
		return "
#enp-quiz .enp-quiz__progress__bar,
#enp-quiz .enp-option__input:checked + .enp-option__label:before,
#enp-quiz .enp-question__container--unanswered .enp-option__label:hover:before,
#enp-quiz .enp-question__container--explanation .enp-option__input--correct-clicked + .enp-option__label:before,
#enp-quiz .enp-slider .ui-slider-range-show-correct,
#enp-quiz .enp-slider .ui-slider-range-min {
	background-color: $correct_color;
}

#enp-quiz .enp-question__container--explanation .enp-option__input--correct + .enp-option__label,
#enp-quiz .enp-explanation--correct {
	box-shadow: inset 4px 0 0 $correct_color;
}

#enp-quiz .enp-quiz__progress__bar,
#enp-quiz .enp-explanation--correct .enp-explanation__title,
#enp-quiz .ui-slider-range-show-correct__tooltip__text,
#enp-quiz .enp-slider-input__input--correct {
	color: $correct_color;
}

#enp-quiz .enp-option__label:before,
#enp-quiz .ui-slider-range-show-correct__tooltip,
#enp-quiz .enp-slider .ui-slider-handle--correct {
	border: 2px solid $correct_color;
}

#enp-quiz #enp-results__score__circle__path {
  stroke: $correct_color;
}

#enp-quiz .enp-question__answered .ui-slider-range-min {
    background-color: ".$this->quiz->get_quiz_bg_color().";
}";
	}

	/**
	* All CSS for "Incorrect" Color.
	* @return string
	*/
	public function get_incorrect_color_css() {
		$incorrect_color = $this->quiz->get_quiz_incorrect_color();
		return "
#enp-quiz .enp-explanation--incorrect .enp-explanation__title,
#enp-quiz .enp-slider-input__input--incorrect {
	color: $incorrect_color;
}

#enp-quiz .enp-question__container--explanation .enp-option__input--incorrect-clicked + .enp-option__label:before {
	background-color: $incorrect_color;
}

#enp-quiz .enp-question__container--explanation .enp-option__input--incorrect-clicked + .enp-option__label:before,
#enp-quiz .enp-slider .ui-slider-handle--incorrect {
	border: 2px solid $incorrect_color;
}

#enp-quiz .enp-question__container--explanation .enp-option__input--incorrect-clicked + .enp-option__label,
#enp-quiz .enp-explanation--incorrect {
	box-shadow: inset 4px 0 0 $incorrect_color;
}";

	}

	/**
	* Check if our Nonce is valid or invald
	* @param $quiz_id (string)
	* @return (BOOLEAN) true if valid, false if not
	*/
	public function validate_nonce($quiz_id) {
		// validate nonce
		if($this->ab_test_id !== false) {
			// it's an ab test nonce
			$nonce_name = 'enp_quiz_take_ab_test_'.$this->ab_test_id.'_nonce';
		} else {
			// it's a quiz nonce
			$nonce_name = 'enp_quiz_take_'.$quiz_id.'_nonce';
		}

		if(isset($_POST[$nonce_name])) {
			$posted_nonce = $_POST[$nonce_name];
		}

	    //Is it a POST request?
 	    if($_SERVER['REQUEST_METHOD'] === 'POST') {
		   $validate_nonce = $this->nonce->validate($posted_nonce);
 		   //Validate the form key
 		   if(!isset($posted_nonce) || $validate_nonce !== true) {
 			   // Form key is invalid,
			   // return them to the page (they're probably refreshing the page)
			   //first, check if it's null or not
			   if($validate_nonce === null) {
				   // cookies are likely disabled
				   $this->error[] = 'It looks like Cookies are disabled. Please enable Cookies in order to take the quiz. If you only have third-party Cookies disabled, <a href="'.$this->quiz_url.'" target="_blank">go here to take the quiz.</a>';
			   } else {
				   $this->error[] = 'It looks like this quiz got started in a different browser window. <a href="'.$this->quiz_url.'">Click here to get it working again.</a>';
			   }
			   return false;
 		   }
 	    }

		return true;
	}

	/**
	* Output our error message into the DOM if there is one
	*/
	public function get_error_messages() {
		if(isset($this->response->error) && !empty($this->response->error)) {
	        $errors = $this->response->error;
	        echo '<section class="enp-quiz-message enp-quiz-message--error" role="alertdialog"  aria-labelledby="enp-quiz-message__title" aria-describedby="enp-message__list">
	        <h3 class="enp-quiz-message__title enp-quiz-message__title--error">Error</h3>
	        <ul class="enp-message__list">';
	        foreach($errors as $error) {
	            echo '<li class="enp-message__list__item">'.$error.'</li>';
	        }
	        echo '</ul></section>';
		}
	}

	/**
	* JS Error message template for displaying errors from AJAX response
	*/
	public function error_message_js_template() {
		return '<script type="text/template" id="error_message_template">
			<section class="enp-quiz-message enp-quiz-message--error" role="alertdialog" aria-labelledby="enp-quiz-message__title" aria-describedby="enp-message__list">
				<h3 id="enp-quiz-message__title" class="enp-quiz-message__title enp-quiz-message__title--error">Error</h3>
				<ul id="enp-message__list" class="enp-message__list">
					<li class="enp-message__list__item">{{error}}</li>
				</ul>
			</section>
		</script>';
	}

	/**
	* Process and save quiz take form submissions and set the response variable
	*/
	public function save_quiz_take() {
		$response = false;
		$save_data = array();

		// get the posted id
		if(isset($_POST['enp-quiz-id'])) {
			$save_data['quiz_id'] = $_POST['enp-quiz-id'];
		}

		$validate_nonce = $this->validate_nonce($save_data['quiz_id']);

		if($validate_nonce === false) {
			// set the response as our error
			$this->response = array('error'=>$this->error);
			// cast it to an object so we can access it like
			// $this->response->error as if it actually came back
			// from the save_quiz_take class that way
			$this->response = (object) $this->response;
			return false;
		}

		// get the posted data
		// get user action
		if(isset($_POST['enp-question-submit'])) {
			$save_data['user_action'] = $_POST['enp-question-submit'];
		}

		// get the user_id
		$save_data['user_id'] = $this->user_id;
		$save_data['response_quiz_id'] = $this->get_submitted_response_quiz_id();

		if($save_data['response_quiz_id'] === false) {
			return false;
		}

		// get the correctly_answered value
		if(isset($_POST['enp-quiz-correctly-answered'])) {
			$save_data['correctly_answered'] = $_POST['enp-quiz-correctly-answered'];
		} else {
			// set the response as our error
			$this->response = array('error'=>$this->error);
			$this->error[] = 'No Quiz Correctly Answered Total found.';
			return false;
		}

		$save_data['response_quiz_updated_at'] = date("Y-m-d H:i:s");


		if($save_data['user_action'] === 'enp-question-submit') {
			// build the data array
			$save_data = $this->build_response_data($save_data);
		} elseif($save_data['user_action'] === 'enp-next-question') {
			// build the data array
			$save_data = $this->build_moving_on_data($save_data);
		}

		// save the response
		$save_quiz_take = new Enp_quiz_Save_quiz_take();
		$response = $save_quiz_take->save_quiz_take($save_data);

		// parse the JSON response
		$this->response = json_decode($response);
	}

	/**
	* Get the appropriate response quiz id if there is one
	* @return response quiz id
	*/
	public function get_submitted_response_quiz_id() {
		// get the response_quiz_id
		if(isset($_POST['enp-response-quiz-id'])) {
			$response_quiz_id = $_POST['enp-response-quiz-id'];
		} else {
			// set the response as our error
			$this->response = array('error'=>$this->error);
			$this->error[] = 'No Response Quiz ID found.';
			$response_quiz_id = false;
		}

		return $response_quiz_id;

	}


	public function build_response_data($response_array) {

		// set defaults
		$response_data = array(
						'quiz_id'	=> '',
						'question_id' => '',
						'question_type' => '',
						'question_response' => '',
						'user_action' => '',
						);
		// merge the passed values with our defaults
		$response_data = array_merge($response_data, $response_array);

		if(isset($_POST['enp-question-id'])) {
			$response_data['question_id'] = $_POST['enp-question-id'];
		}

		if(isset($_POST['enp-question-type'])) {
			$response_data['question_type'] = $_POST['enp-question-type'];
		}

		if(isset($_POST['enp-question-response'])) {
			$response_data['question_response'] = $_POST['enp-question-response'];
		}

		return $response_data;
	}


	public function build_moving_on_data($moving_on_array) {

		// set defaults
		$moving_on_data = array(
						'quiz_id'	=> '',
						'question_id' => '',
						'moving_on_created_at' => date("Y-m-d H:i:s"),
						'user_action' => '',
						);
		// merge the passed values with our defaults
		$moving_on_data = array_merge($moving_on_data, $moving_on_array);

		if(isset($_POST['enp-question-id'])) {
			$moving_on_data['question_id'] = $_POST['enp-question-id'];
		}

		return $moving_on_data;
	}

	/**
	* Process a quiz restart submission
	*/
	public function quiz_restart() {
		$quiz_id = $this->quiz->get_quiz_id();
		// validate the nonce
		$validate_nonce = $this->validate_nonce($quiz_id);
		if($validate_nonce === false) {
			// if anyone says they have to click the restart button twice sometimes, it's because they have two quizzes open and they tried clicking restart on a quiz that had an invalid nonce
			header('Location: '.$this->quiz_url);
			exit;
		}

		// check to make sure we're actually at the end of the quiz
		if(isset($_COOKIE['enp_quiz_state']) && $_COOKIE['enp_quiz_state'] !== 'quiz_end') {
			// if they're not,
			// redirect them to the page they're supposed to be on
			header('Location: '.$this->quiz_url);
			exit;
		}

		// update our quiz restarted field in the response_quiz table
		$this->response_quiz_restarted();

		// delete cookies and set new cookie states as if
		// it just started (state = question, question_id = first)
		$this->cookie_manager->reset_quiz_cookies($this->quiz);

		// redirect them so if they reload the page, it doesn't think there's another quiz_restart being posted
		// figure out if we should redirect to an ab test or quiz

		// if cookies are set, we can safely redirect them to the quiz_url
		// and let the cookies set the new state. Without a redirect
		// the cookie state gets stuck on quiz_end
		// so, if we have a cookie set, let's redirect them
		if(isset($_COOKIE['enp_quiz_state'])) {
			header('Location: '.$this->quiz_url);
			exit;
		}
		// if we don't have any cookies set, cookies aren't available
		// so don't redirect so we can keep using the same user_id and quiz_id (for ab_tests)
		else {
			return;
		}


	}

	public function set_current_question_id() {
		$question = array();
		$question_id = '';

		if(isset($this->response) && !empty($this->response) && empty($this->response->error)) {
			// see what we should do
			if($this->state === 'question_explanation') {
				// show the question explanation template
				// we'll still need this question so we can get the explanation
				$question_id = $this->response->question_id;
			}
			// if a state is set (meaning, we have a response) & the state is 'question', that means we're moving on, so get the next_question response
			elseif($this->state === 'question') {
				$question_id = $this->response->next_question->question_id;
			}
		}
		// check for cookies to see if we're on a page reload or something
		elseif(isset($_COOKIE['enp_current_question_id'])) {
			$question_id = $_COOKIE['enp_current_question_id'];
		}
		// probably new pageload. just get the first question of the quiz
		else {
			$question_ids = $this->quiz->get_questions();
			// set the first question off of the question_ids from the quiz
			$question_id = $question_ids[0];
		}

		$this->current_question_id = $question_id;
	}

	public function set_current_question_number() {
		// if we're at the end, the current question number is the total of the questions
		$current_question_number = 0;
		// check state
		if($this->state === 'quiz_end') {
			$this->current_question_number = $this->quiz->get_total_question_count();
		} else {
			// find it off of the quiz array
			$question_ids = $this->quiz->get_questions();
			// set counter at 1 because we want the first question to be 1 not 0
			$i = 1;
			// loop question ids
			foreach($question_ids as $question_id) {
				// if current question id matches the array question id, set the counter as the current question number
				if((int)$question_id === (int)$this->current_question_id) {
					$current_question_number = $i;
					// we got it! break out
					break;
				} else {
					// didn't find it yet, increase the counter
					$i++;
				}
			}

		}

		$this->current_question_number = $current_question_number;
	}

	/**
	* Track how many questions they've gotten right over the course of the quiz
	*/
	public function set_correctly_answered() {

		// set state off response, if it's there
		if(isset($this->response->correctly_answered)) {
			$correctly_answered = $this->response->correctly_answered;
		}
		// try to set the state from the cookie
		elseif(isset($_COOKIE['enp_correctly_answered'])) {
			$correctly_answered = $_COOKIE['enp_correctly_answered'];
		}
		// probably a new quiz
		else {
			$correctly_answered = 0;
		}

		$this->correctly_answered = $correctly_answered;
	}

	public function get_correctly_answered() {
		return $this->correctly_answered;
	}

	public function set_error_messages() {
		if(isset($this->response->error) && !empty($this->response->error)) {
			$this->error = $this->response->error;
		}
	}

	public function set_state() {
		// set state off response, if it's there
		if(isset($this->response->state) && !empty($this->response->state)) {
			$this->state = $this->response->state;
		}
		// try to set the state from the cookie
		elseif(isset($_COOKIE['enp_quiz_state']) && !empty($_COOKIE['enp_quiz_state'])) {
			$this->state = $_COOKIE['enp_quiz_state'];
		}
		// probably a new quiz
		else {
			$this->state = 'question';
		}
	}

	// getters
	public function get_state() {
		return $this->state;
	}

	public function get_current_question_number() {
		return $this->current_question_number;
	}

	public function get_question_container_class() {
		$class = '';
		if($this->state === 'question') {
			$class = 'enp-question__container--unanswered';
		} elseif($this->state === 'question_explanation') {
			$class = 'enp-question__container--explanation';
		} elseif($this->state === 'quiz_end') {
			$class = 'enp-quiz-end';
		}
		return $class;
	}

	public function set_user_id() {
		// check off the response object
		if(isset($this->response->user_id) && !empty($this->response->user_id)) {
			$uuid = $this->response->user_id;
		}
		// check off post data (for cookie-less quiz restarts)
		elseif(isset($_POST['enp-user-id'])) {
			$uuid = $_POST['enp-user-id'];
		}
		// check on user_id cookie
		elseif(isset($_COOKIE['enp_quiz_user_id'])) {
			// set from cookie
			$uuid = $_COOKIE['enp_quiz_user_id'];
		}
		// no user_id found
		else {
			// no user_id, so build a new one
			$uuid = uniqid('enp_', true);
			// set the eternal cookie
			$this->cookie_manager->set_cookie__user_id($uuid);
		}

		$this->user_id = $uuid;
	}

	public function get_user_id() {
		return $this->user_id;
	}

	public function is_ab_test() {
		$is_ab_test = false;
		if($this->ab_test_id === false) {
			$is_ab_test = false;
		} elseif($this->ab_test_id !== false) {
			$is_ab_test = true;
		}

		return $is_ab_test;
	}

	public function set_quiz_url($quiz_id) {
		$is_ab_test = $this->is_ab_test();

		if($is_ab_test === false) {
			$quiz_url = ENP_QUIZ_URL.$quiz_id;
		} elseif($is_ab_test === true) {
			$quiz_url = ENP_TAKE_AB_TEST_URL.$this->ab_test_id;
		} else {
			// no quiz or ab test
			$quiz_url = false;
		}
		return $quiz_url;
	}

	public function set_cookie_manager($quiz_id) {
		$is_ab_test = $this->is_ab_test();
		if($is_ab_test === false && $quiz_id !== false) {
			$cookie_path = parse_url(ENP_QUIZ_URL, PHP_URL_PATH).$quiz_id;
		} elseif($is_ab_test === true) {
			$cookie_path = parse_url(ENP_TAKE_AB_TEST_URL, PHP_URL_PATH).$this->ab_test_id;
		} else {
			// no quiz or ab test
			$cookie_path = false;
		}

		return new Enp_quiz_Cookies_Quiz_take($cookie_path);
	}

	/**
	* if it's the first question, we need to save the initial quiz view
	* and question view. All other quiz and question view data is handled
	* by the response class
	* @param quiz object
	* @param question_id (to see if we're on the first question)
	*/
	protected function save_initial_view_data() {
		if($this->state === 'question') {
			// we're on a question. It might be the first one, let's find out!
			$question_ids = $this->quiz->get_questions();
			if((int) $this->current_question_id === (int) $question_ids[0]) {
				// we're on the first question of the first quiz in the question state, so we can update the quiz views and first question view
				// save quiz view
				$quiz_data = new Enp_quiz_Save_quiz_take_Quiz_data($this->quiz);
				$quiz_data->update_quiz_views();
				// save question view
				$save_question_view = new Enp_quiz_Save_quiz_take_Question_view($this->current_question_id);

			}
		}
	}


	protected function set_response_quiz_id($quiz_id) {

		// check off the response object
		if(isset($this->response->response_quiz_id) && !empty($this->response->response_quiz_id)) {
			$this->response_quiz_id = $this->response->response_quiz_id;
		}
		// try setting from the cookie
		elseif(isset($_COOKIE['enp_response_id']) && !empty($_COOKIE['enp_response_id'])) {
			$this->response_quiz_id = $_COOKIE['enp_response_id'];
		}
		// nothing found. create a new one.
		else {
			$this->create_response_quiz_id($quiz_id);
		}

	}

	public function get_response_quiz_id() {
		return $this->response_quiz_id;
	}

	protected function create_response_quiz_id($quiz_id) {
		// create and set the cookie
		$response_quiz = new Enp_quiz_Save_quiz_take_Response_quiz();
		$start_quiz_data = array(
								'quiz_id' => $quiz_id,
								'user_id' => $this->user_id,
								'response_quiz_updated_at' => date("Y-m-d H:i:s")
							);
		$response_quiz = $response_quiz->insert_response_quiz($start_quiz_data);
		$this->response_quiz_id = $response_quiz['response_quiz_id'];
		// set our response_quiz_id cookie

		$this->cookie_manager->set_cookie__response_id($this->response_quiz_id);

		if($this->ab_test_id !== false) {
			// we're on an AB Test, so link the response to it.
			// we only need to do this when we initially create the response,
			// nor do we care what the response AB Test ID even is until we generate
			// overall AB TEST results.
			$results_ab_test = new Enp_quiz_Save_quiz_take_Response_ab_test($this->ab_test_id, $this->response_quiz_id);
		}

		// create an initial question response too
		$this->create_response_question($start_quiz_data);

	}

	protected function create_response_question($data) {
		$data['question_id'] = $this->quiz->questions[0];
		$data['response_quiz_id'] = $this->response_quiz_id;
		// create a new question response entry
		$question_response = new Enp_quiz_Save_quiz_take_Response_question();
		$question_response->insert_response_question($data);
	}

	protected function response_quiz_restarted() {
		$restart_data = array(
								'response_quiz_id' => $this->get_submitted_response_quiz_id(),
								'user_id' => $this->user_id,
								'quiz_id' => $this->quiz->get_quiz_id(),
								'response_quiz_updated_at' => date("Y-m-d H:i:s")
							);
		if($restart_data['user_id'] === false || $restart_data['response_quiz_id'] === false) {
			// redirect them to the page they're supposed to be on
			return false;
		}

		$response_quiz = new Enp_quiz_Save_quiz_take_Response_quiz();
		$response_quiz = $response_quiz->update_response_quiz_restarted($restart_data);
	}

	public function get_quiz_form_action() {
		$form_action = '';
		// check if we're on an AB Test or not
		if($this->ab_test_id !== false) {
	        $form_action = htmlentities(ENP_TAKE_AB_TEST_URL).$this->ab_test_id;
	    } else {
	        $form_action = htmlentities(ENP_QUIZ_URL).$this->quiz->get_quiz_id();
	    }
		return $form_action;
	}

	public function get_init_quiz_id() {
		$quiz_id = $_GET['quiz_id'];
		return $quiz_id;
	}

}
