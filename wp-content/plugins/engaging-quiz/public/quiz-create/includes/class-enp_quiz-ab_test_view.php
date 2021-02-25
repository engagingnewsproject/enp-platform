<?php

/**
 * Loads and generates the AB_test
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/AB_test
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version,
 * and registers & enqueues quiz create scripts and styles
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/AB_test
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_AB_test_view extends Enp_quiz_Create {
    public function __construct() {
        $this->ab_test = $this->load_ab_test_object();
        // we're including this as a fallback for the other pages.
        // Other page classes will not need to do this
        add_filter( 'the_content', array($this, 'load_template' ));
        // load take quiz styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		// load take quiz scripts
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function load_template() {
        ob_start();
        //Start the class
        $user = new Enp_quiz_User(get_current_user_id());
        $enp_quiz_nonce = parent::$nonce;
        $ab_test = $this->ab_test;
        $quizzes = $user->get_published_quizzes();
        include_once( ENP_QUIZ_CREATE_TEMPLATES_PATH.'/ab-test.php' );
        $content = ob_get_contents();
        if (ob_get_length()) ob_end_clean();

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

		wp_register_script( $this->plugin_name.'-ab-test', plugin_dir_url( __FILE__ ) . '../js/ab-test.js', array( 'jquery' ), ENP_QUIZ_VERSION, true );
		wp_enqueue_script( $this->plugin_name.'-ab-test' );

	}


}
