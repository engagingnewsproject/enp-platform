<?php
/**
* Manages the rendering and sending of the correct template
* @since 0.0.1
* @author jones.jeremydavid@gmail.com
*/
namespace Cme\Template;
use Cme\Utility as Utility;

class Render extends Template {
    public $data,
           $template_name,
           $template_path;

    public function __construct($template_name, $data_slug) {
        // set the name
        $this->set_template_name($template_name);
        // get the name
        $template_name = $this->get_template_name();
        // set the path
        $this->set_template_path($template_name);
        // set the data
        $this->set_data($data_slug);
    }

    /**
    * Passes data to the compiled template render function
    */
    public function render() {
        $renderer = include $this->get_template_path();
        $data = $this->get_data();
        return $renderer($this->get_data());
    }

    protected function set_data($tree_slug) {
        $data = false;

        $body = file_get_contents(TREE_URL."/data/$tree_slug.min.json");
        // decode it if it's not empty
        if(!empty($body)) {
            $data = json_decode($body, true);
        }

        // check to make sure it's a valid array
        if(!is_array($data)) {
            $data = false;
        }

        $this->data = $data;
    }

    protected function set_template_path() {
        $path = false;
        $template_name = $this->get_template_name();
        if(Utility\is_slug($template_name)) {
            $path = "views/$template_name.php";
        }
        $this->template_path = $path;
    }

    public function get_data() {
        return $this->data;
    }

    public function get_template_path() {
        return $this->template_path;
    }

}
