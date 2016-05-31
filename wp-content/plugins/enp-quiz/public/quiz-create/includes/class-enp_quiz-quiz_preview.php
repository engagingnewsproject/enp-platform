<?php

/**
 * Loads and generates the Quiz_preview
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/Quiz_preview
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version,
 * and registers & enqueues quiz create scripts and styles
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/Quiz_preview
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Quiz_preview extends Enp_quiz_Create {
    public $quiz; // object

    public function __construct() {
        // set the quiz object
        $this->quiz = $this->load_quiz();
        // check if it's valid
        // if it's not, they'll get redirected to the quiz create page
        $this->validate_quiz_redirect($this->quiz);
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
        $enp_quiz_nonce = parent::$nonce;
        $enp_current_page = 'preview';
        // set the button name
        if($quiz->get_quiz_status() === 'published') {
            $enp_next_button_name = 'Embed';
        } else {
            $enp_next_button_name = 'Publish';
        }

        include_once( ENP_QUIZ_CREATE_TEMPLATES_PATH.'/quiz-preview.php' );
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

        wp_register_script( $this->plugin_name.'-sticky-header', plugin_dir_url( __FILE__ ) . '../js/utilities/sticky-header.js', array( 'jquery', 'underscore' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name.'-sticky-header' );

        $this->enqueue_color_picker();

		wp_register_script( $this->plugin_name.'-quiz-preview', plugin_dir_url( __FILE__ ) . '../js/quiz-preview.js', array( 'jquery', 'wp-color-picker' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name.'-quiz-preview' );

	}

    /*
    * Color picker is only enqueud via admin functions usually. Takes a
    * surprising amount of work to get it working on the front-end
    * http://wordpress.stackexchange.com/questions/82718/how-do-i-implement-the-wordpress-iris-picker-into-my-plugin-on-the-front-end
    */
    public function enqueue_color_picker() {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script(
           'iris',
           admin_url( 'js/iris.min.js' ),
           array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ),
           false,
           1
        );
        wp_enqueue_script(
           'wp-color-picker',
           admin_url( 'js/color-picker.min.js' ),
           array( 'iris' ),
           false,
           1
        );
        $colorpicker_l10n = array(
           'clear' => __( 'Clear' ),
           'defaultString' => __( 'Default' ),
           'pick' => __( 'Select Color' ),
           'current' => __( 'Current Color' ),
        );
        wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );
    }


}
