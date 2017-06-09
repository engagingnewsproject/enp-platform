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

    /**
    * Checks to make sure the twitter value being saved is under the allowed
    * character limit
    *
    * @param $tweet (string) string to validate
    * @param $include_url (BOOLEAN) URLs count as 21 characters. Set true if
    *                               you will be using a URL with the tweet
    * @param $mustache (BOOLEAN) checks for {{score_percentage}} and replaces
    *                            it with '100' if found
    * @return BOOLEAN true if valid, false if not
    */
    public function validate_tweet($tweet, $include_url = false, $mustache = false) {
        $valid = false;
        if($mustache === true) {
            // see if a {{score_percentage}} is in there and replace it with 100 (max length)
            $tweet = str_replace('{{score_percentage}}', '100', $tweet);
        }

        // count the characters
        $chars = strlen($tweet);

        // set the length we'll check against
        if($include_url === true) {
            // 140 - 23 = 117
            // allowed tweet length if URL is included
            $allowed_chars = 117;
        } else {
            $allowed_chars = 140;
        }

        // actually check if it's valid
        if($chars <= $allowed_chars) {
            $valid = true;
        }

        return $valid;
    }

    /**
    * Sets a new error to our error response array
    *
    * @since 1.1.0
    * @param string = message you want to add
    * @return response object array
    */
    public function add_error($error) {
        $this->response['error'][] = $error;
    }

    /**
    * Adds multiple errors from an array
    *
    * @since 1.1.0
    * @param $errors ARRAY of error message you want to add
    * @return response object array
    */
    public function add_errors($errors) {
        // join the errors together without overwriting any of them
        $this->response['error'] = $this->response['error'] + $errors;
    }

    /**
    * Sets a new success to our success response array
    *
    * @since 1.1.0
    * @param string = message you want to add
    * @return response object array
    */
    public function add_success($success) {
        $this->response['success'][] = $success;
    }

    /**
    * Checks to see if there are any errors in our response
    *
    * @since 1.1.0
    * @return BOOLEAN
    */
    public function has_errors($response) {
        $has_errors = true;
        // if the array is empty, then there are no errors
        if(empty($response['error'])) {
            $has_errors = false;
        }
        return $has_errors;
    }

    /**
    * Checks if there are any errors set in the error array
    *
    * @return BOOLEAN
    */
    public function is_valid($response) {
        // if the response has errors, then it's invalid
        // if the response does not have errors, then it's valid
        return !$this->has_errors($response);
    }

    /**
    * Is the url a valid url?
    *
    * @since 1.1.0
    * @param $url STRING
    * @return BOOLEAN
    */
    public function is_valid_url($url) {
        $valid = false;

        if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
            $valid = true;
        }

        return $valid;
    }

    /**
    * Checks to see if it's a slug or not
    * Allowed characters are A-Z, a-z, 0-9, and dashes (-)
    *
    * @param $string (STRING)
    * @return  BOOLEAN
    */
    public function is_slug($string) {
        $is_slug = false;
        // check for disallowed characters and strings that starts or ends in a dash (-)
        // if matches === 1, then it's a slug
        preg_match('/[^a-z0-9-]+|^-|-$/', $string, $matches);

        // check to make sure it's not null/empty
        // if there's a match, it's not a slug
        // also make sure $string !== boolean
        if(is_bool($string) === false && is_int($string) !== true && !empty($string) && empty($matches)) {
            $is_slug = true;
        }

        return $is_slug;
    }

    /**
    * Checks if a string is probably an ID (contains only numbers)
    * This could likely live in a better locale, but don't have a good place for it
    * and it makes sense that you'd be doing this alongside slugs
    *
    * @param $string (MIXED String/Integer)
    * @return BOOLEAN
    */
    public function is_id($string) {
        $is_id = false;

        // make sure it's a valid string
        if(is_bool($string) === false && !empty($string)) {
            $string = (string) $string;
            // Regex check where the only allowed characters are 0-9
            // if a match is found, then it's not an ID
            $matches = null;
            preg_match('/[^0-9]/', $string, $matches);
            // if preg_match returns false (0) & it's not null/empty then it's an ID
            if(empty($matches)) {
                $is_id = true;
            }
        }


        return $is_id;
    }

    /**
    * Checks to see if a quiz exists or not
    *
    * @param $quiz_id (String/Integer)
    * @return BOOLEAN
    */
    public function does_quiz_exist($quiz_id) {
        $quiz = new Enp_quiz_Quiz($quiz_id);
        $quiz_id = $quiz->get_quiz_id();

        return $this->is_id($quiz_id);
    }

    /**
    * Checks to see if a embed site exists or not
    *
    * @param $site_id (String/Integer)
    * @return BOOLEAN
    */
    public function does_embed_site_exist($site_id) {

        $site = new Enp_quiz_Embed_site($site_id);
        $site_id = $site->get_embed_site_id();
        return $this->is_id($site_id);
    }

    /**
    * Checks to see if a embed quiz exists or not
    *
    * @param $embed_quiz_query (URL || Integer)
    * @return BOOLEAN
    */
    public function does_embed_quiz_exist($embed_quiz_query) {
        $embed_quiz = new Enp_quiz_Embed_quiz($embed_quiz_query);
        $id = $embed_quiz->get_embed_quiz_id();

        return $this->is_id($id);
    }

    /**
    * Not working right now...
    */
    public function is_date($date) {
        $is_date = false;
        $parsed_date = date_parse($date);
        // check if valid gegorian calendar date
        // ie- 2017-04-31 should be invalid
        $is_gregorian = checkdate ( $parsed_date['month'], $parsed_date['day'], $parsed_date['year'] );

        if($is_gregorian === true) {
            // passed the gregorian check!

            // make sure it matches our format
            preg_match('/2[0-9]{3}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $date, $matches);

            if(!empty($matches)) {
                // passed the regex test!
                // check if it's in the right format
                $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date);

                $errors = DateTime::getLastErrors();
                if (empty($errors['warning_count'])) {
                    $is_date = true;
                }
            }

        }


        return $is_date;

    }
}
