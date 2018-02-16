<?php
/**
 *
 */
namespace Cme\Template;
use Cme\Utility as Utility;

class Template
{
    public $template,
           $template_name;

    function __construct()
    {
    }

    protected function set_template() {
        $template = false;
        if($this->template_name !== false) {
            $template_file = file_get_contents(TREE_PATH."/templates/$this->template_name.hbs");

            if(is_string($template_file)) {
                $template = $template_file;
            }
        }

        $this->template = $template;
    }

    protected function set_template_name($template_name) {
        if(!Utility\is_slug($template_name)) {
            return false;
        }

        $this->template_name = $template_name;
    }

    public function get_template_name() {
        return $this->template_name;
    }

    public function get_template() {
        return $this->template;
    }
}
