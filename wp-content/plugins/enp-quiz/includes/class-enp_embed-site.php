<?/**
 * Create an embed site object
 * Shows what sites have embedded quizzes on them
 *
 * @since      1.0.1
 * @package    Enp_quiz
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Embed_site {
    public  $embed_site_id,
            $embed_site_name,
            $embed_site_url,
            $embed_site_type_ids = array(),
            $embed_site_quiz_ids = array(),
            $embed_site_created_at,
            $embed_site_updated_at,
            $embed_site_is_dev;

    /**
    * @param $query STRING can be ID or URL
    */
    public function __construct($query) {
        return $this->get_embed_site($query);
    }

    /**
    *   Build embed_site object by url
    *
    *   @param  $url = url that you want to select from embed_site_url
    *   @return embed_site object, false if not found
    **/
    public function get_embed_site($query) {
        $embed_site = false;

        // see if it's an ID or URL
        if(filter_var($query, FILTER_VALIDATE_URL) !== false) {
            $embed_site = $this->select_embed_site_by_url($query);
        } else {
            $embed_site = $this->select_embed_site_by_id($query);
        }

        if($embed_site !== false) {
            $embed_site = $this->set_embed_site_object_values($embed_site);
        }

        return $embed_site;
    }

    /**
    *   For using PDO to select one site row
    *
    *   @param  $url = url that you want to select
    *   @return row from database table if found, false if not found
    **/
    public function select_embed_site_by_url($embed_site_url) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":embed_site_url" => $embed_site_url
        );

        $sql = "SELECT * from ".$pdo->embed_site_table." WHERE
                embed_site_url = :embed_site_url";
        $stmt = $pdo->query($sql, $params);
        $embed_site_row = $stmt->fetch();
        // return the found site row
        return $embed_site_row;
    }

    /**
    *   For using PDO to select one site row
    *
    *   @param  $url = url that you want to select
    *   @return row from database table if found, false if not found
    **/
    public function select_embed_site_by_id($embed_site_id) {
        // make sure id isn't a boolean
        if(is_bool($embed_site_id)) {
            return false;
        }
        
        // make sure the query is valid
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":embed_site_id" => $embed_site_id
        );

        $sql = "SELECT * from ".$pdo->embed_site_table." WHERE
                embed_site_id = :embed_site_id";
        $stmt = $pdo->query($sql, $params);
        $embed_site_row = $stmt->fetch();
        // return the found site row
        return $embed_site_row;
    }

    /**
    *   For using PDO to select all a site's rows
    *
    *   @param  $site_id = embed_quiz_site that you want to get quizzes on that site
    *   @return rows from database table if found, false if not found
    **/
    public function select_embed_quizzes_by_site_id($site_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":embed_site_id" => $site_id
        );

        $sql = "SELECT embed_quiz_id from ".$pdo->embed_quiz_table." WHERE
                embed_site_id = :embed_site_id";
        $stmt = $pdo->query($sql, $params);
        $embed_quiz_row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // return the found site row
        return $embed_quiz_row;
    }

    /**
    * Sets all object variables
    */
    protected function set_embed_site_object_values($embed_site) {
         $this->set_embed_site_id($embed_site['embed_site_id']);
         $this->set_embed_site_name($embed_site['embed_site_name']);
         $this->set_embed_site_url($embed_site['embed_site_url']);
         $this->set_embed_site_created_at($embed_site['embed_site_created_at']);
         $this->set_embed_site_updated_at($embed_site['embed_site_updated_at']);
         $this->set_embed_site_is_dev($embed_site['embed_site_is_dev']);
         $this->set_embed_site_type_ids();
         $this->set_embed_site_quiz_ids();
    }

    protected function set_embed_site_id($embed_site_id) {
        $this->embed_site_id = $embed_site_id;
        return $this->embed_site_id;
    }

    protected function set_embed_site_name($embed_site_name) {
        $this->embed_site_name = $embed_site_name;
        return $this->embed_site_name;
    }

    protected function set_embed_site_url($embed_site_url) {
        $this->embed_site_url = $embed_site_url;
        return $this->embed_site_url;
    }

    protected function set_embed_site_created_at($embed_site_created_at) {
        $this->embed_site_created_at = $embed_site_created_at;
        return $this->embed_site_created_at;
    }

    protected function set_embed_site_updated_at($embed_site_updated_at) {
        $this->embed_site_updated_at = $embed_site_updated_at;
        return $this->embed_site_updated_at;
    }

    protected function set_embed_site_is_dev($embed_site_is_dev) {
        $this->embed_site_is_dev = $embed_site_is_dev;
        return $this->embed_site_is_dev;
    }

    protected function set_embed_site_type_ids() {
        $embed_site_id = $this->get_embed_site_id();
        $types = new Enp_quiz_Embed_site_bridge('site', $embed_site_id);
        $this->embed_site_type_ids = $types->get_esb_type();
        return $this->embed_site_type_ids;
    }

    protected function set_embed_site_quiz_ids() {
        $embed_site_id = $this->get_embed_site_id();
        $embed_quizzes = $this->select_embed_quizzes_by_site_id($embed_site_id);
        $eqs = array();
        if(is_array($embed_quizzes)) {
            foreach($embed_quizzes as $eq) {
                $eqs[] = $eq['embed_quiz_id'];
            }
        }
        $this->embed_site_quiz_ids = $eqs;
        return $this->embed_site_quiz_ids;
    }

    public function get_embed_site_id() {
        return $this->embed_site_id;
    }

    public function get_embed_site_name() {
        return $this->embed_site_name;
    }

    public function get_embed_site_url() {
        return $this->embed_site_url;
    }

    public function get_embed_site_created_at() {
        return $this->embed_site_created_at;
    }

    public function get_embed_site_updated_at() {
        return $this->embed_site_updated_at;
    }

    public function get_embed_site_is_dev() {
        return $this->embed_site_is_dev;
    }

    /**
    * Get all of a site's categories/types by id
    * @param $embed_site_id (Optional)
    * @return $types (objects) from Enp_quiz_Embed_site_type class
    */
    public function get_embed_site_types($embed_site_id = false) {
        // if none was passed, use the current one
        if($embed_site_id === false) {
            $embed_site_id = $this->get_embed_site_id();
        }
        // get the types attached to this site
        $types = new Enp_quiz_Embed_site_bridge('site', $embed_site_id);

        $site_types = array();
        foreach($types->get_types() as $type_id) {
            // build a new type object
            $site_types[] = new Enp_quiz_Embed_site_type($type_id);
        }
        return $site_types;
    }


}
