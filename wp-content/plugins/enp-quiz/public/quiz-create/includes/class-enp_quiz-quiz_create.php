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
    public $quiz,
           $quiz_action_url,
           $new_quiz_flag;
    public function __construct() {
        // load the quiz
        $this->quiz = $this->load_quiz();
        $quiz_id = $this->quiz->get_quiz_id();
        $this->quiz_action_url = $this->set_quiz_action_url($quiz_id);
        $this->new_quiz_flag = $this->set_new_quiz_flag($quiz_id);

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
        // load js templates
        add_action('wp_footer', array($this, 'quiz_create_js_templates'));
    }

    public function load_content($content) {
        ob_start();
        //Start the class
        $Quiz_create = $this;
        $quiz = $this->quiz;
        $quiz_id = $quiz->get_quiz_id();
        $quiz_status =  $quiz->get_quiz_status();
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
        $plugin_name = $this->plugin_name;

        wp_register_script( $plugin_name.'-accordion', plugin_dir_url( __FILE__ ) . '../js/utilities/accordion.js', array( 'underscore' ), ENP_QUIZ_VERSION, true );
		wp_enqueue_script( $plugin_name.'-accordion' );


        /*wp_register_script( $plugin_name.'-sticky-header', plugin_dir_url( __FILE__ ) . '../js/utilities/sticky-header.js', array( 'jquery', 'underscore' ), ENP_QUIZ_VERSION, true );
		wp_enqueue_script( $plugin_name.'-sticky-header' );*/


        // quiz create script.
        // Mama Mia that's alotta dependencies!
        wp_register_script(
            $plugin_name.'-quiz-create',
            plugin_dir_url( __FILE__ ) . '../js/dist/quiz-create.js',
            array(
                'jquery',
                'underscore',
                'jquery-ui-slider',
                'jquery-touch-punch',
                //$plugin_name.'-sticky-header',
                $plugin_name.'-accordion'
            ),
            ENP_QUIZ_VERSION, true
        );
        wp_enqueue_script( $plugin_name.'-quiz-create' );

        wp_localize_script( $plugin_name.'-quiz-create','quizCreate', array(
    		'ajax_url' => admin_url( 'admin-ajax.php' ),
            'quiz_create_url' => ENP_QUIZ_CREATE_URL,
            'quiz_image_url' => ENP_QUIZ_IMAGE_URL,
    	));



	}

    public function set_quiz_action_url($quiz_id) {
        if(is_numeric($quiz_id) || is_int($quiz_id)) {
            $quiz_action_url = ENP_QUIZ_CREATE_URL.$quiz_id.'/';
        } else {
            $quiz_action_url = ENP_QUIZ_CREATE_URL.'new/';
        }
        return $quiz_action_url;
    }

    public function set_new_quiz_flag($quiz_id) {
        if(empty($quiz_id)) {
            $new_quiz_flag= '1';
        } else {
            $new_quiz_flag= '0';
        }

        return $new_quiz_flag;
    }

    public function get_quiz_action_url() {
        return htmlentities($this->quiz_action_url);
    }

    public function get_new_quiz_flag() {
        return $this->new_quiz_flag;
    }
    /**
    * Template HTML loaders
    */

    public function hidden_fields() {

       $quiz_id_input = '<input id="enp-quiz-id" type="hidden" name="enp_quiz[quiz_id]" value="'.$this->quiz->get_quiz_id().'" />';
       $quiz_new_flag_input = '<input id="enp-quiz-new" type="hidden" name="enp_quiz[new_quiz]" value="'.$this->get_new_quiz_flag().'" />';

       return $quiz_id_input."\n".$quiz_new_flag_input;


    }
    public function get_mc_option_add_button($question_id) {
        $mc_option_add_button = '';
        if($this->is_before_publish() === true) {
            $mc_option_add_button = '<li class="enp-mc-option enp-mc-option--add">
                <button class="enp-btn--add enp-quiz-submit enp-mc-option__add" name="enp-quiz-submit" value="add-mc-option__question-'.$question_id.'"><svg class="enp-icon enp-icon--add enp-mc-option__add__icon" role="presentation" aria-hidden="true"><use xlink:href="#icon-add" /></svg> Add Another Option</button>
            </li>';
        }

        return $mc_option_add_button;
    }

    public function get_mc_option_delete_button($mc_option_id) {
        $mc_option_delete_button = '';

        if($this->is_before_publish() === true) {
            $mc_option_delete_button = '<button class="enp-mc-option__button enp-quiz-submit enp-mc-option__button--delete" name="enp-quiz-submit" value="mc-option--delete- '.$mc_option_id.'">
                <svg class="enp-icon enp-icon--delete enp-mc-option__icon enp-mc-option__icon--delete"><use xlink:href="#icon-delete"><title>Delete Multiple Choice Option</title></use></svg>
            </button>';
        }

        return $mc_option_delete_button;
    }

    public function get_mc_option_correct_button($question_id, $mc_option_id) {
        $mc_option_correct_button = '';


        $mc_option_correct_button = '<button class="enp-mc-option__button enp-quiz-submit enp-mc-option__button--correct"  name="enp-quiz-submit" value="mc-option--correct__question-'.$question_id.'__mc-option-'.$mc_option_id.'"'.($this->quiz->get_quiz_status() === 'published' ? ' disabled' : '').'>
            <svg class="enp-icon enp-icon--check enp-mc-option__icon enp-mc-option__icon--correct"><use xlink:href="#icon-check"><title>Mark Multiple Choice Option as Correct</title></use></svg>
        </button>';

        return $mc_option_correct_button;
    }

    public function get_question_delete_button($question_id) {
        $delete_button = '';
        if($this->is_before_publish() === true) {
            $delete_button = '<button class="enp-question__button enp-quiz-submit enp-question__button--delete" name="enp-quiz-submit" value="question--delete-'.$question_id.'">
                <svg class="enp-icon enp-icon--delete enp-question__icon--question-delete"><use xlink:href="#icon-delete"><title>Delete Question</title></use></svg>
            </button>';
        }
        return $delete_button;
    }

    public function get_question_image_template($question, $question_id, $question_i, $question_image) {
        ob_start();
        if(!empty($question_image)) {
            include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-question-image.php');
        } elseif($question_id !== '{{question_id}}') {
            include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-question-image-upload.php');
        }
        $image_template = ob_get_contents();
        // don't use ob_get_length first as this is a nested ob (output buffer)
        // inside question template and messes up the JS template output
        ob_end_clean();

        return $image_template;
    }

    public function get_quiz_create_question_image($question, $question_id) {
        $question_image = '';
        if ($question_id !== '{{question_id}}') {
            $question_image = '<img
                class="enp-question-image"
                src="'.$question->get_question_image_src().'"
                srcset="'.$question->get_question_image_srcset().'"
                alt="'.$question->get_question_image_alt().'"
            />';
        }
        return $question_image;
    }

    public function get_question_type_input($question, $question_id, $question_i) {
        $question_type_input = '';

        if($this->is_before_publish() === true) {
            // get both inputs
            $question_type_input = $this->get_question_type_mc_input($question, $question_id, $question_i)."\n".$this->get_question_type_slider_input($question, $question_id, $question_i);
        }
        // check if published
        else if($this->quiz->get_quiz_status() === 'published') {
            // is the question an mc option?
            if($question->get_question_type() === 'mc') {
                // just output the mc option input
                $question_type_input = $this->get_question_type_mc_input($question, $question_id, $question_i);
            } else if($question->get_question_type() === 'slider') {
                $question_type_input = $this->get_question_type_slider_input($question, $question_id, $question_i);
            }
        }

       return $question_type_input;
    }

    public function get_question_type_mc_input($question, $question_id, $question_i) {
        $mc_input = '<input type="radio" id="enp-question-type__mc--'.$question_id.'" class="enp-radio enp-question-type__input enp-question-type__input--mc" name="enp_question['.$question_i.'][question_type]" value="mc" '.checked( $question->get_question_type(), 'mc', false ).'/>
        <label class="enp-label enp-question-type__label enp-question-type__label--mc" for="enp-question-type__mc--'.$question_id.'"><span class="enp-screen-reader-text">Question Type: </span>Multiple Choice</label>';

        return $mc_input;
    }

    public function get_question_type_slider_input($question, $question_id, $question_i) {
        $slider_input = '<input type="radio" id="enp-question-type__slider--'.$question_id.'" class="enp-radio enp-question-type__input enp-question-type__input--slider" name="enp_question['.$question_i.'][question_type]" value="slider" '.checked( $question->get_question_type(), 'slider', false ).'/>
        <label class="enp-label enp-question-type__label enp-question-type__label--slider" for="enp-question-type__slider--'.$question_id.'"><span class="enp-screen-reader-text">Question Type: </span>Slider</label>';

        return $slider_input;
    }

    public function get_slider_range_low_input($slider, $question_i) {
        return '<label class="enp-label enp-slider-range-low__label" for="enp-slider-range-low__'.$slider->get_slider_id().'">Slider Start</label>
        <input id="enp-slider-range-low__'.$slider->get_slider_id().'" class="enp-input enp-slider-range-low__input" type="number" min="-9999999999999999.9999" max="9999999999999999.9999" name="enp_question['.$question_i.'][slider][slider_range_low]" value="'.$slider->get_slider_range_low().'" step="any"'.($this->quiz->get_quiz_status()==='published'? ' readonly' : '').'/>';
    }

    public function get_slider_range_high_input($slider, $question_i) {
        return '<label class="enp-label enp-slider-range-high__label" for="enp-slider-range-high__'.$slider->get_slider_id().'">Slider End</label>
        <input id="enp-slider-range-high__'.$slider->get_slider_id().'" class="enp-input enp-slider-range-high__input" type="number" min="-9999999999999999.9999" max="9999999999999999.9999" name="enp_question['.$question_i.'][slider][slider_range_high]" value="'.$slider->get_slider_range_high().'" step="any"'.($this->quiz->get_quiz_status()==='published'? ' readonly' : '').'/>';
    }

    public function get_slider_correct_low_input($slider, $question_i) {
        return '<label class="enp-label enp-slider-correct-low__label" for="enp-slider-correct-low__'.$slider->get_slider_id().'">Slider Answer Low</label>
        <input id="enp-slider-correct-low__'.$slider->get_slider_id().'" class="enp-input enp-slider-correct-low__input" type="number" min="-9999999999999999.9999" max="9999999999999999.9999" name="enp_question['.$question_i.'][slider][slider_correct_low]" value="'.$slider->get_slider_correct_low().'" step="any"'.($this->quiz->get_quiz_status()==='published'? ' readonly' : '').'/>';
    }

    public function get_slider_correct_high_input($slider, $question_i) {
        return '<label class="enp-label enp-slider-correct-high__label" for="enp-slider-correct-high__'.$slider->get_slider_id().'">Slider Answer High</label>
        <input id="enp-slider-correct-high__'.$slider->get_slider_id().'" class="enp-input enp-slider-correct-high__input" type="number" min="-9999999999999999.9999" max="9999999999999999.9999" name="enp_question['.$question_i.'][slider][slider_correct_high]" value="'.$slider->get_slider_correct_high().'" step="any"'.($this->quiz->get_quiz_status()==='published' ? ' readonly' : '').'/>';
    }

    public function get_slider_increment_input($slider, $question_i) {
        return '<label class="enp-label enp-slider-increment__label" for="enp-slider-increment__'.$slider->get_slider_id().'">Slider Increment</label>
        <input id="enp-slider-increment__'.$slider->get_slider_id().'" class="enp-input enp-slider-increment__input" type="number" min="-9999999999999999.9999" max="9999999999999999.9999" name="enp_question['.$question_i.'][slider][slider_increment]" value="'.$slider->get_slider_increment().'" step="any"'.($this->quiz->get_quiz_status()==='published'? ' readonly' : '').'/>';
    }

    public function get_add_question_button() {
        $add_question_btn = '';
        if($this->quiz->get_quiz_status() !== 'published') {
            $add_question_btn = '<button type="submit" class="enp-btn--add enp-quiz-submit enp-quiz-form__add-question" name="enp-quiz-submit" value="add-question"><svg class="enp-icon enp-icon--add enp-add-question__icon" role="presentation" aria-hidden="true">
              <use xlink:href="#icon-add" />
            </svg> Add Question</button>';
        }
        return $add_question_btn;
    }

    public function get_next_step_button() {
        return '<button type="submit" id="enp-btn--next-step" class="enp-btn--submit enp-quiz-submit enp-btn--next-step enp-quiz-form__submit" name="enp-quiz-submit" value="quiz-preview">'.($this->quiz->quiz_status !== 'published' ? 'Preview' : 'Settings').' <svg class="enp-icon enp-icon--chevron-right enp-btn--next-step__icon enp-quiz-form__submit__icon">
          <use xlink:href="#icon-chevron-right" />
        </svg></button>';
    }

    public function is_before_publish() {
        if($this->quiz->get_quiz_status() === 'draft' || $this->get_new_quiz_flag() === '1') {
            $is_before_publish = true;
        } else {
            $is_before_publish = false;
        }

        return $is_before_publish;
    }


    public function quiz_create_js_templates() {
        $Quiz_create = $this;
        $question_id = '{{question_id}}';
        $question_i = '{{question_position}}';
        $question = new Enp_quiz_Question($question_id);

        $js_templates = $this->question_js_template($Quiz_create, $question_id, $question_i);
        $js_templates .= $this->question_image_js_template($Quiz_create, $question, $question_id, $question_i);
        $js_templates .= $this->question_image_upload_js_template($question_id);
        $js_templates .= $this->question_image_upload_button_js_template($Quiz_create, $question_id, $question_i);
        $js_templates .= $this->mc_option_js_template($Quiz_create, $question_id, $question_i);
        $js_templates .= $this->slider_js_templates($Quiz_create, $question_id, $question_i);

        echo $js_templates;
    }

    public function question_js_template($Quiz_create, $question_id, $question_i) {

        // set-up our template
        $js_template = '<script type="text/template" id="question_template">';
            ob_start();
            include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-question.php');
            $js_template .= ob_get_contents();
            if (ob_get_length()) ob_end_clean();
        // end our template
        $js_template .= '</script>';

        return $js_template;
    }


    public function question_image_upload_button_js_template($Quiz_create, $question_id, $question_i) {
        $js_template = '<script type="text/template" id="question_image_upload_button_template">
            <button type="button" class="enp-btn--add enp-question-image-upload"><svg class="enp-icon enp-icon--photo enp-question-image-upload__icon--photo" role="presentation" aria-hidden="true">
                <use xlink:href="#icon-photo" />
            </svg>
            <svg class="enp-icon enp-icon--add enp-question-image-upload__icon--add" role="presentation" aria-hidden="true">
                <use xlink:href="#icon-add" />
            </svg> Add Image</button>
        </script>';

        return $js_template;
    }

    public function question_image_js_template($Quiz_create, $question, $question_id, $question_i) {
        $js_template = '<script type="text/template" id="question_image_template">';
            ob_start();
            include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-question-image.php');
            $js_template .= ob_get_contents();
            if (ob_get_length()) ob_end_clean();
        $js_template .= '</script>';
        return $js_template;
    }

    public function question_image_upload_js_template($question_id) {
        $js_template = '<script type="text/template" id="question_image_upload_template">';
            ob_start();
            include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-question-image-upload.php');
            $js_template .= ob_get_contents();
            if (ob_get_length()) ob_end_clean();
        $js_template .= '</script>';
        return $js_template;
    }

    public function mc_option_js_template($Quiz_create, $question_id, $question_i) {
        $mc_option_id = '{{mc_option_id}}';
        $mc_option_i = '{{mc_option_position}}';
        // set-up our template
        $js_template = '<script type="text/template" id="mc_option_template">';
            ob_start();
            include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-mc-option.php');
            $js_template .= ob_get_contents();
            if (ob_get_length()) ob_end_clean();
        // end our template
        $js_template .= '</script>';
        return $js_template;
    }

    public function slider_js_templates($Quiz_create, $question_id, $question_i) {
        $slider = new Enp_quiz_Slider(0);
        // foreach key, set it as a js template var
        foreach($slider as $key => $value) {
            // we don't want to unset our question object
            $slider->$key = '{{'.$key.'}}';
        }
        // set-up our template
        $slider_js_templates = '<script type="text/template" id="slider_template">';
            ob_start();
            include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-slider.php');
            $slider_js_templates .= ob_get_contents();
            if (ob_get_length()) ob_end_clean();
        // end our template
        $slider_js_templates .= '</script>';

        // set-up our template
        $slider_js_templates .= '<script type="text/template" id="slider_take_template">';
            ob_start();
            include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/slider.php');
            $slider_js_templates .= ob_get_contents();
            if (ob_get_length()) ob_end_clean();
        // end our template
        $slider_js_templates .= '</script>';

        // set-up our template
        $slider_js_templates .= '<script type="text/template" id="slider_take_range_helpers_template">';
            ob_start();
            include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/slider--range-helpers.php');
            $slider_js_templates .= ob_get_contents();
            if (ob_get_length()) ob_end_clean();
        // end our template
        $slider_js_templates .= '</script>';

        return $slider_js_templates;
    }

}
