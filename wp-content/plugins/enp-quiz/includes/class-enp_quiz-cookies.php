<?php
/**
* A little utility class for managing cookies
*/
class Enp_quiz_Cookies {

    public function __construct() {

    }

    /**
    * Set a cookie
    * @param $name (string) the name of the cookie you want to set
    * @param $value (string) the value you want stored in the cookie
    * @param $path (string) the path you want it set to on the domain.
    * defaults to '/'
    * @param $time (int) amount time you want the cookie set for. Defaults to year 2038 ()
    */
    public function set_cookie($name, $value, $path = '/', $time = 2147483647) {
        setcookie($name, $value, $time, $path);
    }

    /**
    * Unset a cookie
    * @param $name (string) the name of the cookie you want to set
    * @param $path (string) the path you want it set to on the domain. defaults to '/'
    */
    public function unset_cookie($name, $path = '/') {
        // unset it immediately
        unset($_COOKIE[$name]);
        // set the cookie so it'll expire
        setcookie($name, '', time() - 3600, $path);
    }


}
?>
