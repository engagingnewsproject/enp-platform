<?php

/**
 * Loads and generates the Quiz_publish
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/Quiz_publish
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version,
 * and registers & enqueues quiz create scripts and styles
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/Quiz_publish
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Quiz_publish extends Enp_quiz_Create {
    public $quiz; // object

    public function __construct() {
        // set the quiz object
        $this->quiz = $this->load_quiz();
        // check if it's valid
        // if it's not, they'll get redirected to the quiz create page
        $this->validate_quiz_redirect($this->quiz, 'publish');
        // we're including this as a fallback for the other pages.
        // Other page classes will not need to do this
        add_filter( 'the_content', array($this, 'load_content' ));
        // load take quiz styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		// load take quiz scripts
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function load_content($content) {
        ob_start();
        $quiz = $this->quiz;
        $enp_current_page = 'publish';
        include_once( ENP_QUIZ_CREATE_TEMPLATES_PATH.'/quiz-publish.php' );
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public function enqueue_styles() {

	}

	/**
	 * Register and enqueue the JavaScript for quiz create.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_scripts() {

		wp_register_script( $this->plugin_name.'-quiz-publish', plugin_dir_url( __FILE__ ) . '../js/quiz-publish.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name.'-quiz-publish' );

	}


}
