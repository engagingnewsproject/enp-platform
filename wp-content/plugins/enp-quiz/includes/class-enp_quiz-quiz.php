<?
/**
* Create a quiz object
* @param $quiz_id = the id of the quiz you want to get
* @return quiz object
*/
class Enp_quiz_Quiz {
    public  $quiz_id,
            $quiz_title,
            $quiz_status,
            $quiz_finish_message,
            $quiz_owner,
            $quiz_created_by,
            $quiz_created_at,
            $quiz_updated_by,
            $quiz_updated_at,
            $questions,
            $quiz_options,
            $quiz_views,
            $quiz_starts,
            $quiz_finishes,
            $quiz_score_average,
            $quiz_time_spent,
            $quiz_time_spent_average;

    protected static $quiz;

    public function __construct($quiz_id) {
        // returns false if no quiz found
        $this->get_quiz_by_id($quiz_id);
    }

    /**
    *   Build quiz object by id
    *
    *   @param  $quiz_id = quiz_id that you want to select
    *   @return quiz object, false if not found
    **/
    public function get_quiz_by_id($quiz_id) {
        self::$quiz = $this->select_quiz_by_id($quiz_id);
        if(self::$quiz !== false) {
            self::$quiz = $this->set_quiz_object_values();
        }
        return self::$quiz;
    }

    /**
    *   For using PDO to select one quiz row
    *
    *   @param  $quiz_id = quiz_id that you want to select
    *   @return row from database table if found, false if not found
    **/
    public function select_quiz_by_id($quiz_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":quiz_id" => $quiz_id
        );
        $sql = "SELECT * from ".$pdo->quiz_table." WHERE
                quiz_id = :quiz_id
                AND quiz_is_deleted = 0";
        $stmt = $pdo->query($sql, $params);
        $quiz_row = $stmt->fetch();
        // return the found quiz row
        return $quiz_row;
    }

    /**
    * Hook up all the values for the object
    * @param $quiz = row from the quiz_table
    */
    protected function set_quiz_object_values() {
        $this->quiz_id = $this->set_quiz_id();
        $this->quiz_title = $this->set_quiz_title();
        $this->quiz_status = $this->set_quiz_status();
        $this->quiz_finish_message = $this->set_quiz_finish_message();
        $this->quiz_owner = $this->set_quiz_owner();
        $this->quiz_created_by = $this->set_quiz_created_by();
        $this->quiz_created_at = $this->set_quiz_created_at();
        $this->quiz_updated_by = $this->set_quiz_updated_by();
        $this->quiz_updated_at = $this->set_quiz_updated_at();
        $this->questions = $this->set_questions();
        $this->quiz_views = $this->set_quiz_views();
        $this->quiz_starts = $this->set_quiz_starts();
        $this->quiz_finishes = $this->set_quiz_finishes();
        $this->quiz_score_average = $this->set_quiz_score_average();
        $this->quiz_time_spent = $this->set_quiz_time_spent();
        $this->quiz_time_spent_average = $this->set_quiz_time_spent_average();

        // set options
        $this->set_quiz_options();
    }

    /**
    * Queries the quiz options table and sets more quiz object values
    * @param $quiz_id
    */
    protected function set_quiz_options() {
        $option_rows = $this->select_quiz_options();
        foreach($option_rows as $row => $option) {
            // allow any option value to be set. We can whitelist it later if we'd like/if it's a security issue
            // $whitelist = array('');
            // check if in_array($whitelist);
            // ...
            $option_value = $option['quiz_option_value'];
            $option_name = $option['quiz_option_name'];

            $this->quiz_options[$option_name] = stripslashes($option_value);

        }
    }

    /**
    *   For using PDO to select one quiz row
    *
    *   @param  $quiz_id = quiz_id that you want to select
    *   @return row from database table if found, false if not found
    **/
    protected function select_quiz_options() {
        $quiz_id = $this->quiz_id;

        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":quiz_id" => $quiz_id
        );
        $sql = "SELECT * from ".$pdo->quiz_option_table." WHERE
                quiz_id = :quiz_id";
        $stmt = $pdo->query($sql, $params);
        $option_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $option_rows;
    }

    /**
    * Set the quiz_id for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return quiz_id field from the database
    */
    protected function set_quiz_id() {
        $quiz_id = self::$quiz['quiz_id'];
        return $quiz_id;
    }

    /**
    * Set the quiz_title for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return quiz_title field from the database
    */
    protected function set_quiz_title() {
        $quiz_title = stripslashes(self::$quiz['quiz_title']);
        return $quiz_title;
    }

    /**
    * Set the quiz_status for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return 'published' or 'draft'
    */
    protected function set_quiz_status() {
        $quiz_status = self::$quiz['quiz_status'];
        if($quiz_status !== 'published') {
            $quiz_status = 'draft';
        }
        return $quiz_status;
    }

    /**
    * Set the quiz_finish_message for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return quiz_finish_message field from the database
    */
    protected function set_quiz_finish_message() {
        $quiz_finish_message = stripslashes(self::$quiz['quiz_finish_message']);
        return $quiz_finish_message;
    }

    /**
    * Set the quiz_owner for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return quiz_owner field from the database
    */
    protected function set_quiz_owner() {
        $quiz_owner = self::$quiz['quiz_owner'];
        return $quiz_owner;
    }

    /**
    * Set the created_by for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return created_by field from the database
    */
    protected function set_quiz_created_by() {
        $created_by = self::$quiz['quiz_created_by'];
        return $created_by;
    }

    /**
    * Set the created_at for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return created_at field from the database
    */
    protected function set_quiz_created_at() {
        $created_at = self::$quiz['quiz_created_at'];
        return $created_at;
    }

    /**
    * Set the updated_by for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return updated_by field from the database
    */
    protected function set_quiz_updated_by() {
        $updated_by = self::$quiz['quiz_updated_by'];
        return $updated_by;
    }

    /**
    * Set the updated_at for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return updated_at field from the database
    */
    protected function set_quiz_updated_at() {
        $updated_at = self::$quiz['quiz_updated_at'];
        return $updated_at;
    }

    /**
    * Set the questions for our Quiz Object
    * @param $quiz_id
    * @return questions array of ids array(3,4,5) from the database
    */
    protected function set_questions() {
        $quiz_id = self::$quiz['quiz_id'];

        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":quiz_id" => $quiz_id
        );
        $sql = "SELECT question_id from ".$pdo->question_table." WHERE
                quiz_id = :quiz_id
                AND question_is_deleted = 0
                ORDER BY question_order ASC";
        $stmt = $pdo->query($sql, $params);
        $question_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $questions = array();
        foreach($question_rows as $row => $question) {
            $questions[] = (int) $question['question_id'];
        }
        return $questions;
    }

    /**
    * Set the views for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return views field from the database
    */
    protected function set_quiz_views() {
        $views = self::$quiz['quiz_views'];
        return $views;
    }

    /**
    * Set the starts for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return starts field from the database
    */
    protected function set_quiz_starts() {
        $starts = self::$quiz['quiz_starts'];
        return $starts;
    }

    /**
    * Set the finishes for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return finishes field from the database
    */
    protected function set_quiz_finishes() {
        $finishes = self::$quiz['quiz_finishes'];
        return $finishes;
    }

    /**
    * Set the score_average for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return score_average field from the database
    */
    protected function set_quiz_score_average() {
        $score_average = self::$quiz['quiz_score_average'];
        return $score_average;
    }

    /**
    * Set the time_spent for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return time_spent field from the database
    */
    protected function set_quiz_time_spent() {
        $time_spent = self::$quiz['quiz_time_spent'];
        return $time_spent;
    }

    /**
    * Set the time_spent_average for our Quiz Object
    * @param $quiz = quiz row from quiz database table
    * @return time_spent_average field from the database
    */
    protected function set_quiz_time_spent_average() {
        $time_spent_average = self::$quiz['quiz_time_spent_average'];
        return $time_spent_average;
    }

    /**
    * Get the quiz_id for our Quiz Object
    * @param $quiz = quiz object
    * @return quiz_id from the object
    */
    public function get_quiz_id() {
        $quiz_id = $this->quiz_id;
        return $quiz_id;
    }

    /**
    * Get the quiz_title for our Quiz Object
    * @param $quiz = quiz object
    * @return quiz_title from the object
    */
    public function get_quiz_title() {
        $quiz_title = $this->quiz_title;
        return $quiz_title;
    }

    /**
    * Get the quiz_status for our Quiz Object
    * @param $quiz = quiz object
    * @return 'published' or 'draft'
    */
    public function get_quiz_status() {
        $quiz_status = $this->quiz_status;
        return $quiz_status;
    }

    /**
    * Get the quiz_finish_message for our Quiz Object
    * @param $quiz = quiz object
    * @return quiz_finish_message from the quiz object
    */
    public function get_quiz_finish_message() {
        $quiz_finish_message = $this->quiz_finish_message;
        return $quiz_finish_message;
    }

    /**
    * Get the quiz_owner for our Quiz Object
    * @param $quiz = quiz object
    * @return user_id
    */
    public function get_quiz_owner() {
        $quiz_owner = $this->quiz_owner;
        return $quiz_owner;
    }

    /**
    * Get the quiz_created_by for our Quiz Object
    * @param $quiz = quiz object
    * @return user_id
    */
    public function get_quiz_created_by() {
        $quiz_created_by = $this->quiz_created_by;
        return $quiz_created_by;
    }

    /**
    * Get the quiz_created_at for our Quiz Object
    * @param $quiz = quiz object
    * @return Date formatted Y-m-d H:i:s
    */
    public function get_quiz_created_at() {
        $quiz_created_at = $this->quiz_created_at;
        return $quiz_created_at;
    }

    /**
    * Get the quiz_updated_by for our Quiz Object
    * @param $quiz = quiz object
    * @return user_id
    */
    public function get_quiz_updated_by() {
        $quiz_updated_by = $this->quiz_updated_by;
        return $quiz_updated_by;
    }

    /**
    * Get the quiz_updated_at for our Quiz Object
    * @param $quiz = quiz object
    * @return Date formatted Y-m-d H:i:s
    */
    public function get_quiz_updated_at() {
        $quiz_updated_at = $this->quiz_updated_at;
        return $quiz_updated_at;
    }

    /**
    * Get a quiz option from our Quiz Object
    * @param $key (string) key from the $this->quiz_option array
    * @return (Mixed) $value of the item in the array if found, null if not found
    */
    public function get_quiz_option($key) {
        $value = null;
        if(array_key_exists($key, $this->quiz_options)) {
            $value = $this->quiz_options[$key];
        }
        return $value;
    }

    /**
    * Get the quiz_title_display for our Quiz Object
    * @param $quiz = quiz object
    * @return (string) 'show' or 'hide'
    */
    public function get_quiz_title_display() {
        return $this->get_quiz_option('quiz_title_display');
    }


    /**
    * Get the quiz_width for our Quiz Object
    * @param $quiz = quiz object
    * @return (string) %, px, em, or rem value (100%, 800px, 20rem, etc)
    */
    public function get_quiz_width() {
        return $this->get_quiz_option('quiz_width');
    }

    /**
    * Get the quiz_bg_color for our Quiz Object
    * @param $quiz = quiz object
    * @return #hex code
    */
    public function get_quiz_bg_color() {
        return $this->get_quiz_option('quiz_bg_color');
    }

    /**
    * Get the quiz_text_color for our Quiz Object
    * @param $quiz = quiz object
    * @return #hex code
    */
    public function get_quiz_text_color() {
        return $this->get_quiz_option('quiz_text_color');
    }

    /**
    * Get the facebook_title_start for our Quiz Object
    * @param $quiz = quiz object
    * @return string
    */
    public function get_facebook_title_start() {
        return $this->get_quiz_option('facebook_title_start');
    }

    /**
    * Get the facebook_description_start for our Quiz Object
    * @param $quiz = quiz object
    * @return string
    */
    public function get_facebook_description_start() {
        return $this->get_quiz_option('facebook_description_start');
    }

    /**
    * Get the facebook_title_end for our Quiz Object
    * @param $quiz = quiz object
    * @return string
    */
    public function get_facebook_title_end() {
        return $this->get_quiz_option('facebook_title_end');
    }

    /**
    * Get the facebook_description_end for our Quiz Object
    * @param $quiz = quiz object
    * @return string
    */
    public function get_facebook_description_end() {
        return $this->get_quiz_option('facebook_description_end');
    }


    /**
    * Get the email_subject_start for our Quiz Object
    * @param $quiz = quiz object
    * @return string
    */
    public function get_email_subject_start() {
        return $this->get_quiz_option('email_subject_start');
    }

    /**
    * Get the facebook_description_start for our Quiz Object
    * @param $quiz = quiz object
    * @return string
    */
    public function get_email_body_start() {
        return $this->get_quiz_option('email_body_start');
    }

    /**
    * Get the email_subject_end for our Quiz Object
    * @param $quiz = quiz object
    * @return string
    */
    public function get_email_subject_end() {
        return $this->get_quiz_option('email_subject_end');
    }

    /**
    * Get the email_body_end for our Quiz Object
    * @param $quiz = quiz object
    * @return string
    */
    public function get_email_body_end() {
        return $this->get_quiz_option('email_body_end');
    }

    /**
    * Get the tweet_start for our Quiz Object
    * @param $quiz = quiz object
    * @return string
    */
    public function get_tweet_start() {
        $tweet = $this->get_quiz_option('tweet_start');
        return $tweet;
    }

    /**
    * Get the tweet_end for our Quiz Object
    * @param $quiz = quiz object
    * @return string
    */
    public function get_tweet_end() {
        $tweet = $this->get_quiz_option('tweet_end');
        // find/replace mustache values?
        // $mustache = true;
        // $tweet = $this->encode_content($tweet, 'url', $mustache);
        return $tweet;
    }

    /**
    * Utility function for encoding content
    * @param $key (string) of Object var ('tweet_end', 'email_body_start', etc)
    * @param $encoding (string) 'rawurl','url','htmlspecialchars'
    * @param $mustache BOOLEAN keep mustache variables decoded
    * @return encoded string
    */
    public function get_encoded($key, $encoding = 'url', $mustache = false) {
        $getter = 'get_'.$key;
        $value = $this->$getter();
        $value = $this->encode_content($value, $encoding, $mustache);
        return $value;
    }
    /**
    * Get the questions for our Quiz Object
    * @param $quiz = quiz object
    * @return array of question_id's as integers
    */
    public function get_questions() {
        $questions = $this->questions;
        return $questions;
    }


    /**
    * Get the quiz views for our Quiz Object
    * @param $quiz = quiz object
    * @return Date formatted Y-m-d H:i:s
    */
    public function get_quiz_views() {
        $quiz_views = $this->quiz_views;
        return $quiz_views;
    }

    /**
    * Get the quiz starts for our Quiz Object
    * @param $quiz = quiz object
    * @return Date formatted Y-m-d H:i:s
    */
    public function get_quiz_starts() {
        $quiz_starts = $this->quiz_starts;
        return $quiz_starts;
    }

    /**
    * Get the quiz finishes for our Quiz Object
    * @param $quiz = quiz object
    * @return Date formatted Y-m-d H:i:s
    */
    public function get_quiz_finishes() {
        $quiz_finishes = $this->quiz_finishes;
        return $quiz_finishes;
    }

    /**
    * Get the quiz score_average for our Quiz Object
    * @param $quiz = quiz object
    * @return Date formatted Y-m-d H:i:s
    */
    public function get_quiz_score_average() {
        $quiz_score_average = $this->quiz_score_average;
        return $quiz_score_average;
    }

    /**
    * Get the quiz time_spent for our Quiz Object
    * @param $quiz = quiz object
    * @return Date formatted Y-m-d H:i:s
    */
    public function get_quiz_time_spent() {
        $quiz_time_spent = $this->quiz_time_spent;
        return $quiz_time_spent;
    }

    /**
    * Get the quiz time_spent_average for our Quiz Object
    * @param $quiz = quiz object
    * @return Date formatted Y-m-d H:i:s
    */
    public function get_quiz_time_spent_average() {
        $quiz_time_spent_average = $this->quiz_time_spent_average;
        return $quiz_time_spent_average;
    }

    /**
    * Get the individual score data on each take of this quiz
    * @param $quiz = quiz object
    * @return array of all the scores
    */
    public function get_quiz_scores() {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":quiz_id" => $this->get_quiz_id()
        );
        $sql = "SELECT quiz_score from ".$pdo->response_quiz_table."
                 WHERE quiz_completed = 1
                   AND quiz_id = :quiz_id
                   AND response_quiz_is_deleted = 0
              ORDER BY quiz_score ASC";
        $stmt = $pdo->query($sql, $params);
        $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $quiz_scores = array();
        foreach($scores as $score) {
            $quiz_scores[] = (int) round($score['quiz_score'] * 100);
        }

        // return the found quiz row
        return $quiz_scores;
    }

    /**
    * Outputs an array that returns the count of each possible score
    * @return array($score => $how_many_people_got_this_score, 100'=>'12', '50'=>12, '0'=>4);
    */
    public function get_quiz_scores_group_count() {
        $all_quiz_scores = $this->get_quiz_scores();
        $all_quiz_scores = array_count_values($all_quiz_scores);
        // merge the array with a default of possible scores array
        // so we have all values, even if they don't have a set score yet
        $default_scores = array();
        $total_questions = count($this->questions);
        // return 0 if there are no questions
        if($total_questions === 0) {
            return $all_quiz_scores;
        }

        $i = 0;
        while($i <= $total_questions) {
            $key = (int) round($i/$total_questions * 100);
            $default_scores[$key] = 0;
            $i++;
        }


        // merge the arrays while preserving keys
        $all_quiz_scores = $all_quiz_scores + $default_scores;
        // sort by key, low to high
        ksort($all_quiz_scores);

        return $all_quiz_scores;
    }

    /**
    * Useful for line charts and tables of quiz score data
    * @return array('quiz_scores'=>array(scores grouped by integer), 'quiz_scores_labels'=>array(score labels))
    */
    public function get_quiz_score_chart_data() {
        $all_quiz_scores = $this->get_quiz_scores_group_count();
        $quiz_scores_labels = array();
        $quiz_scores = array();
        foreach($all_quiz_scores as $key => $val) {
            $quiz_scores_labels[] = $key.'%';
            $quiz_scores[] = $val;
        }

        $quiz_results = array(
            'quiz_scores' => $quiz_scores,
            'quiz_scores_labels' => $quiz_scores_labels,
        );

        return $quiz_results;
    }



    /**
    * Create an entire quiz json object with all question and mc option data
    */
    public function get_quiz_json() {
        $quiz = $this->get_take_quiz_array();
        return json_encode($quiz);
    }

    public function get_take_quiz_array() {
        $quiz = (array) $this;
        unset($quiz['quiz_owner']);
        unset($quiz['quiz_created_by']);
        unset($quiz['quiz_updated_by']);

        return $quiz;
    }

    /**
    * If you ever need the entirely built quiz at once with all questions
    * and all MC Option/Slider data
    * @return array of quiz and questions
    */
    public function get_quiz_with_full_questions_array() {
        $quiz = $this->get_take_quiz_array();
        $question_ids = $this->get_questions();
        // create a blank question array
        // remove what we don't need
        unset($quiz['questions']);
        $quiz['question'] = array();
        // loop questions
        if(!empty($question_ids)) {
            foreach($question_ids as $question_id) {
                // get question object
                $question = new Enp_quiz_Question($question_id);
                $question_array = $question->get_take_question_array();
                // add this question to the array we'll send via json
                $quiz['question'][] = $question_array;
            }
        }
        return $quiz;
    }


    /**
    * Get the value we should be saving on a quiz
    * get posted if present, if not, get object. This is so we give them their
    * current entry if we don't *actually* save yet.
    * @param $string = what you want to get ('quiz_title', 'quiz_status', whatever)
    * @return $value
    */
    public function get_value($string) {
        $value = '';
        if(isset($_POST['enp_quiz'])) {
            $posted_value = $_POST['enp_quiz'];
            if(!empty($posted_value[$string])) {
                $value = stripslashes($posted_value[$string]);
            }

        }
        // if the value didn't get set, try with our object
        if($value === '') {
            $get_obj_value = 'get_'.$string;
            $obj_value = $this->$get_obj_value();
            if($obj_value !== null) {
                $value = $obj_value;
            }
        }
        // send them back whatever the value should be
        return $value;
    }

    /**
    * Encode and replace {{mustache}} template vars for share text
    *
    * @param $content (string) the content you want encoded
    * @param $encoding (mixed - string or boolean).
    *		 false = no encoding. rawurl = rawurlencode(). url = urlencode(). htmlspecialchars = htmlspecialchars();
    * @param $mustache (BOOLEAN) Should we search the string to replace {{mustache}} strings?
    * @return STRING encoded and {{mustache}} replaced $content
    */
    public function encode_content($content = '', $encoding = 'url', $mustache = false) {
        if($encoding === 'url') {
            $content = urlencode($content);
        } elseif($encoding === 'rawurl') {
            $content = rawurlencode($content);
        } elseif($encoding === 'htmlspecialchars') {
            $content = htmlspecialchars($content);
        }

        if($mustache === true) {
            // re-create mustache template variables that just got encoded
            $content = $this->prepare_encoded_mustache_string($content);
        }

        return $content;
    }


    /**
    * If a string is URL encoded and you need to make it turn back into
    * {{var}}. Right now it only replaces score_percentage, but we could upgrade * it to use regex or an array later (or the Mustache PHP implementation)
    *
    * @param $str (urlcoded string)
    * @return $str with %7B%7Bscore_percentage%7D%7D turned into {{score_percentage}}
    */
    public function prepare_encoded_mustache_string($str) {
        $str = str_replace('%7B%7Bscore_percentage%7D%7D', '{{score_percentage}}', $str);
        return $str;
    }



}
