<?php

/**
 * Loads and generates the Quiz_results
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/Quiz_results
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version,
 * and registers & enqueues quiz create scripts and styles
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/Quiz_results
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Quiz_results extends Enp_quiz_Create {
    public $quiz;
    public function __construct() {
        // load the quiz
        $this->quiz = $this->load_quiz();
        $this->quiz->quiz_score_chart_data = $this->quiz->get_quiz_score_chart_data();
        // we're including this as a fallback for the other pages.
        // Other page classes will not need to do this
        add_filter( 'the_content', array($this, 'load_template' ));
        // load take quiz styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		// load take quiz scripts
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        // add in json data for scores
        add_action('wp_footer', array($this, 'quiz_results_json'));
        // add an empty slider data object
        add_action('wp_head', array($this, 'setup_slider_results_json'));
    }

    public function load_template() {
        ob_start();
        //Start the class
        $quiz = $this->quiz;
        include_once( ENP_QUIZ_CREATE_TEMPLATES_PATH.'quiz-results.php' );
        $content = ob_get_contents();
        if (ob_get_length()) ob_end_clean();

        return $content;
    }

    public function enqueue_styles() {
        wp_register_style( $this->plugin_name.'-chartist', plugin_dir_url( __FILE__ ) . '../css/chartist.min.css', array(), $this->version );
 	  	wp_enqueue_style( $this->plugin_name.'-chartist' );
	}

    public function quiz_results_json() {

        $quiz_results = $this->quiz->quiz_score_chart_data;

        echo '<script type="text/javascript">';
		    // print this whole object as js global vars in json
			echo 'var quiz_results_json = '.json_encode($quiz_results).';';
		echo '</script>';

    }

    public function setup_slider_results_json() {
        echo '<script type="text/javascript">';
			echo 'slider_results_json = {};';
		echo '</script>';
    }

    public function slider_results_json($slider) {

        $slider_responses_chart_data = $slider->get_slider_responses_chart_data();

        echo '<script type="text/javascript">';
		    // print this whole object as js global vars in json
			echo 'slider_results_json["'.$slider->get_slider_id().'"] = '.json_encode($slider_responses_chart_data).';';
		echo '</script>';

    }

	/**
	 * Register and enqueue the JavaScript for quiz create.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_scripts() {
        // charts
        wp_register_script( $this->plugin_name.'-charts', plugin_dir_url( __FILE__ ) . '../js/utilities/chartist.min.js', $this->version, true );
		wp_enqueue_script( $this->plugin_name.'-charts' );
        // accordion
        wp_register_script( $this->plugin_name.'-accordion', plugin_dir_url( __FILE__ ) . '../js/utilities/accordion.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name.'-accordion' );

        // general scripts
		wp_register_script( $this->plugin_name.'-quiz-results', plugin_dir_url( __FILE__ ) . '../js/dist/quiz-results.js', array( 'jquery', 'underscore', $this->plugin_name.'-accordion', $this->plugin_name.'-charts' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name.'-quiz-results' );

	}

    public function option_correct_icon($correct) {
        if($correct === '1') {
            $svg = '<svg class="enp-icon enp-icon--close enp-results-question__option__icon enp-results-question__option__icon--correct">
                <use xlink:href="#icon-check" />
            </svg>';
        } else {
            $svg = '<svg class="enp-icon enp-icon--close enp-results-question__option__icon enp-results-question__option__icon--incorrect">
                <use xlink:href="#icon-close" />
            </svg>';
        }

        return $svg;
    }


}
