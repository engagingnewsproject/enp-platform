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
class Enp_quiz_Take_Quiz_end {
	public $quiz, // Enp_quiz_Quiz Object
		   $correctly_answered,
		   $score,
		   $score_percentage,
		   $score_circle_dashoffset,
		   $quiz_end_title,
		   $share_content,
		   $quiz_end_content;

	/**
	* This is a big constructor. We require our files, check for $_POST submission,
	* set states, and all other details we're sure to need for our templating
	*
	*/
	public function __construct($quiz, $correctly_answered = 0) {
		$this->quiz = $quiz;
		$this->correctly_answered = $correctly_answered;
		// set the score
		$this->set_score();
		// set score percentage
		$this->set_score_percentage();
		// set the title based on the score
		$this->set_quiz_end_title();
		// set the content based on the score
		$this->set_quiz_end_content();
		// set score circle dashoffset for SVG animation
		$this->set_score_circle_dashoffset();
	}


	public function set_score() {
		$total_questions = $this->quiz->get_total_question_count();
		// calculate the score
		$this->score = $this->correctly_answered / $total_questions;
	}

	public function set_score_percentage() {
		$this->score_percentage = round($this->score * 100);
	}

	/**
	* Give them a title based on how well they did
	* @param score
	*/
	public function set_quiz_end_title() {
		$score = (int) $this->score_percentage;
		if($score < 50) {
			$title = "Ouch!";
		} elseif($score < 70) {
			$title = "Not Bad!";
		} elseif ($score < 85) {
			$title = "Nice Job!";
		}
		elseif ($score < 90) {
			$title = "Fantastic!";
		}
		elseif ($score === 100) {
			$title = "Perfect!";
		}
		$this->quiz_end_title = $title;
	}

	public function get_quiz_end_title() {
		return $this->quiz_end_title;
	}

	/**
	* Give them a title based on how well they did
	* @param score
	*/
	public function set_quiz_end_content() {
		// Not so good. Default.
		$score = (int) $this->score_percentage;
		$content = "We bet you could do better. Why don't you try taking the quiz again?";
		if($score < 70) {
			$content = "We bet you could do better. Why don't you try taking the quiz again?";
		}
		elseif ($score < 85) {
			$content = "You did pretty well! Take the quiz again and see if you can get a perfect score this time.";
		}
		elseif ($score === 100) {
			$content = "Can't do any better than that! Go ahead, share this quiz and brag about it.";
		}
		$this->quiz_end_content = $content;
	}

	public function get_quiz_end_content() {
		return $this->quiz_end_content;
	}

	public function get_score() {
		return $this->score;
	}

	public function get_score_percentage() {
		return $this->score_percentage;
	}

	public function set_score_circle_dashoffset() {
		$dashoffset = 0;
		if(!empty($this->score)) {
			// calculate the score dashoffset
            $r = 90;
            $c = M_PI*($r*2);
            $dashoffset = ((100-$this->get_score()*100)/100)*$c;
		}
		$this->score_circle_dashoffset = $dashoffset;
	}

	public function get_score_circle_dashoffset() {
		return $this->score_circle_dashoffset;
	}

	public function get_init_json() {
		$quiz_end = clone $this;
		// we already have the quiz
		unset($quiz_end->quiz);
		echo '<script type="text/javascript">';
		// print this whole object as js global vars in json
			echo 'var quiz_end_json = '.json_encode($quiz_end).';';
		echo '</script>';
		// remove the cloned object
		unset($quiz_end);
	}

	/**
	* I can't think of a better way to do this right now, but I think this is OK
	* It loops all keys in the object and sets the values as handlebar style strings
	* and injects it into the template
	*/
	public function quiz_end_template() {
		// clone the object so we don't reset its own values
		$qt_end = clone $this;

		// quiz end object variables
		foreach($qt_end as $key => $value) {
			// we don't want to unset our quiz object or twitter_share_text
			if($key !== 'quiz' && $key !== 'twitter_share_text') {
				$qt_end->$key = '{{'.$key.'}}';
			}
		}

		$template = '<script type="text/template" id="quiz_end_template">';
		ob_start();
		include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'partials/quiz-end.php');
		$template .= ob_get_clean();
		$template .= '</script>';

		return $template;
	}

	/**
	* Replace a {{mustache}} var with it's actual value. Only works with
	* {{score_percentage}} right now, but we could upgrade it to use regex or an array later (or the Mustache PHP implementation)
	*
	* @param $str (string) with {{mustache}} variable in it
	* @return $str (string) with {{score_percentage}} replaced by the actual get_score_percentage()
	*/
	public function replace_mustache_variable($str) {
		// regex to match {{string}} and extract string
		// /\{\{([^}]+)\}\}/g
		$str = str_replace('{{score_percentage}}', $this->get_score_percentage(), $str);
		return $str;
	}

	/**
	* Get the share content from the array and encode/replace {{mustache}}
	* template values (if encoded)
	* @param $key (str) key in $this->share_content array
	*		('facebook_title_end', 'twitter_text_start', etc)
	* @param $encoding = 'url', 'rawurl', 'htmlspecialchars'
	* @param $replace_mustache (boolean) true = search_repace {{vars}}, false = nope
	* @return (string) $this->share_content($key), if found.
	*/
	public function get_share_content($key = false, $encoding = 'url', $replace_mustache = true) {
		// check if it's there
		if($key === false ) {
			// we're gonna need some more from you here...
			return false;
		}
		// get the content from the quiz get_encoded function
		$content = $this->quiz->get_encoded($key, $encoding, $replace_mustache);

		// replace mustache var if necessary
		if($replace_mustache === true) {
			$content = $this->replace_mustache_variable($content);
		}

		return $content;
	}

}
