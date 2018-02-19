<?php
/**
* Manages the rendering and sending of the correct template
* @since 0.0.1
* @author jones.jeremydavid@gmail.com
*/
namespace Cme\Template;
use Cme\Utility as Utility;

class Compile extends Template {
    public $template,
           $template_name;

    public function __construct($template_name) {
        $this->set_template_name($template_name);
        $this->set_template();

        $template_name = $this->get_template_name();
        $template = $this->get_template();

        if($template && $template_name) {
            return $this->compile($template, TREE_PATH."/views/$template_name.php");
        } else {
            return false;
        }
    }


    protected function compile($template, $destination) {
        if(empty($template)) {
            return false;
        }
        $options = array(
            'flags' => \LightnCandy\LightnCandy::FLAG_HANDLEBARS,
            'helpers' => array(
                'environment'  => '\Cme\Template\Helpers::environment',
                'group_start'  => '\Cme\Template\Helpers::group_start',
                'group_end'  => '\Cme\Template\Helpers::group_end',
                'el_number'  => '\Cme\Template\Helpers::el_number',
                'destination'  => '\Cme\Template\Helpers::destination',
            )
        );



        $phpStr = \LightnCandy\LightnCandy::compile($template, $options);
        // Save the compiled PHP code into a php file
        file_put_contents($destination, '<?php ' . $phpStr . '?>');
        // get the file and return it
        // Get the render function from the php file
        return $destination;
    }


}
