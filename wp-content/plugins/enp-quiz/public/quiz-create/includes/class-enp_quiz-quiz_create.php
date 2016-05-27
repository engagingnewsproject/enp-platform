<?php

/**
 * Loads and generates the Quiz_create
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/Quiz_create
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version,
 * and registers & enqueues quiz create scripts and styles
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/Quiz_create
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Quiz_create extends Enp_quiz_Create {
    public $quiz;
    public function __construct() {
        // load the quiz
        $this->quiz = $this->load_quiz();

        // if the quiz is published, go to the preview page instead
        // because you can't edit published quizzes and display error message
        // TODO: Offer to duplicate the quiz in error message?
        $this->quiz_published_redirect($this->quiz);

        //add_action('init', array($this, 'register_my_session', 1));
        // Other page classes will not need to do this
        add_filter( 'the_content', array($this, 'load_content' ));
        // load take quiz styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		// load take quiz scripts
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function load_content($content) {
        ob_start();
        //Start the class
        $quiz = $this->quiz;
        $enp_quiz_nonce = parent::$nonce;
        $user_action = $this->load_user_action();
        $enp_current_page = 'create';
        include_once( ENP_QUIZ_CREATE_TEMPLATES_PATH.'/quiz-create.php' );
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

        wp_register_script( $this->plugin_name.'-accordion', plugin_dir_url( __FILE__ ) . '../js/utilities/accordion.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name.'-accordion' );


        wp_register_script( $this->plugin_name.'-sticky-header', plugin_dir_url( __FILE__ ) . '../js/utilities/sticky-header.js', array( 'jquery', 'underscore' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name.'-sticky-header' );

        wp_register_script( $this->plugin_name.'-quiz-create', plugin_dir_url( __FILE__ ) . '../js/dist/quiz-create.js', array( 'jquery', 'underscore', $this->plugin_name.'-accordion' ), $this->version, true );


        wp_localize_script( $this->plugin_name.'-quiz-create','quizCreate', array(
    		'ajax_url' => admin_url( 'admin-ajax.php' ),
            'quiz_create_url' => ENP_QUIZ_CREATE_URL,
            'quiz_image_url' => ENP_QUIZ_IMAGE_URL,
    	));

        wp_enqueue_script( $this->plugin_name.'-quiz-create' );

        // jQuery slider
        wp_register_script( $this->plugin_name.'-jquery-ui', plugin_dir_url( __FILE__ ) .'../../quiz-take/js/dist/jquery-ui.min.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name.'-jquery-ui' );



	}

}
