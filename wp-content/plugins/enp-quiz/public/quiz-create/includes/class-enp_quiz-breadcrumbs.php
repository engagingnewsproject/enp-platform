<?php

/**
 * Loads and generates the AB_results
 *
 * @link       http://engagingnewsproject.org
 * @since      0.2.0
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/Breadcrumbs
 */

/**
 * Loads states and HTML for the breadcrumbs nav in Quiz Create
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/Breadcrumbs
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Breadcrumbs {
    public $quiz_id,
           $quiz_status,
           $current_page,
           $create_name,
           $create_url,
           $create_class,
           $preview_name,
           $preview_url,
           $preview_class,
           $publish_name,
           $publish_url,
           $publish_class;

    public function __construct($current_page, $quiz_id, $quiz_status) {
        // check to make sure it's an accepted value
        if(in_array($current_page, array('create', 'preview', 'publish'), true )) {
            $this->current_page = $current_page;
        } else {
            return 'invalid page';
        }
        $this->quiz_id = $quiz_id;
        $this->quiz_status = $quiz_status;

        $this->set_names();
        $this->set_urls();
        $this->set_classes();
    }

    /**
    * Sets the text names of the links based on if published or not
    */
    protected function set_names() {
        if($this->quiz_status === 'published') {
            $this->preview_name = 'Settings';
            $this->publish_name = 'Embed';
            $this->create_name = 'Edit';
        } else {
            $this->create_name = 'Create';
            $this->preview_name = 'Preview';
            $this->publish_name = 'Publish';
        }
    }

    /**
    * Sets the href values vased on current page and quiz status
    */
    protected function set_urls() {
        $this->create_url = ENP_QUIZ_CREATE_URL;
        $this->create_url .= (!empty($this->quiz_id) ?  $this->quiz_id.'/' : 'new');

        $this->preview_url = (!empty($this->quiz_id) ? ENP_QUIZ_PREVIEW_URL.$this->quiz_id.'/' : '#');

        $this->publish_url = (!empty($this->quiz_id) ? ENP_QUIZ_PUBLISH_URL.$this->quiz_id.'/' : '#');
    }

    /**
    * Sets active and disaled class based on current page and quiz status
    */
    protected function set_classes() {
        $this->create_class = '';
        $this->preview_class = '';
        $this->publish_class = '';

        // create classes
        if($this->current_page === 'create') {
            $this->create_class = ' enp-quiz-breadcrumbs__link--active';

            // if the quiz isn't published, disable publish link
            if($this->quiz_status !== 'published') {
                $this->publish_class = ' enp-quiz-breadcrumbs__link--disabled';
            }
        }

        // preview classes
        if($this->current_page === 'preview') {
            $this->preview_class = ' enp-quiz-breadcrumbs__link--active';
        }
        // if it's a new quiz, disable the preview link
        if(empty($this->quiz_id)) {
            $this->preview_class .= ' enp-quiz-breadcrumbs__link--disabled';
        }

        // publish class
        if($this->current_page === 'publish') {
            $this->publish_class = ' enp-quiz-breadcrumbs__link--active';
        }
    }

    /**
    * Getters!
    */

    public function get_create_url() {
        return $this->create_url;
    }

    public function get_create_name() {
        return $this->create_name;
    }

    public function get_create_class() {
        return $this->create_class;
    }

    public function get_preview_url() {
        return $this->preview_url;
    }

    public function get_preview_name() {
        return $this->preview_name;
    }

    public function get_preview_class() {
        return $this->preview_class;
    }

    public function get_publish_url() {
        return $this->publish_url;
    }

    public function get_publish_name() {
        return $this->publish_name;
    }

    public function get_publish_class() {
        return $this->publish_class;
    }

    /**
    * Compiles HTML for the create link
    * @return HTML
    */
    public function get_create_link() {
        return '<a href="'.$this->get_create_url().'" class="enp-quiz-breadcrumbs__link'.$this->get_create_class().'">'.$this->get_create_name().'</a>';
    }

    /**
    * Compiles HTML for the preview link
    * @return HTML
    */
    public function get_preview_link() {
        return '<a class="enp-quiz-breadcrumbs__link enp-quiz-breadcrumbs__link--preview'.$this->get_preview_class().'" href="'.$this->get_preview_url().'">'.$this->get_preview_name().'</a>';
    }

    /**
    * Compiles HTML for the publish link
    * @return HTML
    */
    public function get_publish_link() {
        return '<a class="enp-quiz-breadcrumbs__link enp-quiz-breadcrumbs__link--publish'.$this->get_publish_class().'" href="'.$this->get_publish_url().'">'.$this->get_publish_name().'</a>';
    }

}
?>
