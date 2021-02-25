<?php
/**
* A Utility class for getting the current url and parsing it
* on the Quiz Take side of things
*/
class Enp_quiz_Current_URL {
    protected $root,
              $uri;

    public function __construct() {
        $this->set_root();
        $this->set_uri();
    }

    /**
    * Set URL taking into account Https and Port
    * useful bc we don't have to differentiate between AB TEST or QUIZ url
    * @link http://css-tricks.com/snippets/php/get-current-page-url/
    * @version Refactored by @AlexParraSilva
    */
    protected function set_root() {
        $url  = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';
        $url .= '://' . $_SERVER['SERVER_NAME'];
        $url .= in_array( $_SERVER['SERVER_PORT'], array('80', '443') ) ? '' : ':' . $_SERVER['SERVER_PORT'];

        $this->root = $url;
    }

    protected function set_uri() {
        $this->uri = $_SERVER['REQUEST_URI'];
    }

   public function get_full_url() {
       $url = $this->get_root() . $this->get_uri();
       return $url;
   }

   public function get_root() {
       return $this->root;
   }

   public function get_uri() {
       return $this->uri;
   }
}
?>
