<?/**
 * Kick off the save process. Connect to database, validation functions.
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/database
 *
 * Extended by all the other Save classes
 *
 *
 * @since      0.0.1
 * @package    Enp_quiz
 * @subpackage Enp_quiz/database
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Save {

    public function __construct() {

    }

    /**
     * Process a string to get it ready for saving. Checks if isset
     * and sanitizes it.
     *
     * @return   sanitized string or default
     * @since    0.0.1
     */
    public function process_string($posted_string, $default) {
        $string = $default;
        if(isset($_POST[$posted_string])) {
            $posted_string = sanitize_text_field($_POST[$posted_string]);
            if(!empty($posted_string)) {
                $string = $posted_string;
            }
        }
        return $string;
    }

    /**
     * Process an integer to get it ready for saving. Checks if isset
     * and casts it as an integer.
     *
     * @return   sanitized integer or default
     * @since    0.0.1
     */
    public function process_int($posted_int, $default) {
        $int = $default;
        if(isset($_POST[$posted_int])) {
            $posted_int = (int) $_POST[$posted_int];
            // if the $posted_int is greater than 0,
            // then it's a potentially valid quiz_id
            if( 0 < $posted_int ) {
                $int = $posted_int;
            }
        }
        return $int;
    }

    /**
    * Validation function for hex keys
    * @param $string potential hex
    * @return true if hex, false if not
    */
    public function validate_hex($string) {
        $valid_hex = false;
        // validate hex string
        $matches = null;
        preg_match('/#([a-fA-F0-9]{3}){1,2}\\b/', $string, $matches);

        if(!empty($matches)) {
            $valid_hex = true;
        }
        return $valid_hex;
    }

    /**
    * Validation function for CSS measurements
    * @param $string potential CSS measurement
    * @return true if valid, false if not
    */
    public function validate_css_measurement($string) {
        $valid_CSS = false;
        // validate hex string
        $matches = null;
        preg_match("#^(auto|0)$|^[+-]?[0-9]+.?([0-9]+)?(px|rem|em|ex|%|in|cm|mm|pt|pc|vw|vh)$#", $string, $matches);

        if(!empty($matches)) {
            $valid_CSS = true;
        }
        return $valid_CSS;
    }

    /**
    * Utility function to compare two numbers and return the lower one
    * @param $a (int)
    * @param $b (int)
    * @return (int) Lowest of the numbers, or, if equal, $a
    */
    public function set_low_value($a, $b) {
        $a = $this->set_int_type($a);
        $b = $this->set_int_type($b);
        // set $a as the default for low
        $low = $a;
        // see if b is actually lower
        if($b < $a) {
            // if b is lower, set $b as low
            $low = $b;
        }
        // return $low
        return $low;
    }

    /**
    * Utility function to compare two numbers and return the higher one
    * @param $a (int)
    * @param $b (int)
    * @return (int) Highest of the numbers, or, if equal, $a
    */
    public function set_high_value($a, $b) {
        $a = $this->set_int_type($a);
        $b = $this->set_int_type($b);
        // set $a as the default for high
        $high = $a;
        // see if b is actually higher
        if($a < $b) {
            // if b is higher, set $b as high
            $high = $b;
        }
        // return $high
        return $high;
    }

    public function set_int_type($a) {
        // check if it's already an int or float (double)
        $type = gettype($a);
        if($type !== "integer" && $type !== "double") {
            // cast to int
            $a = (int) $a;
        }
        return $a;
    }
}
