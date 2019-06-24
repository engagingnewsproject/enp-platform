<?php
/**
 * Create an embed site object
 * Shows what sites have embedded quizzes on them
 *
 * @since      1.0.1
 * @package    Enp_quiz
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Embed_domain {
    public  $embed_domain_id,
            $embed_domain_name,
            $embed_domain_url,
            $embed_domain_site_ids = array(),
            $embed_domain_site_types = array(),
            $embed_domain_type_ids = array(),
            $embed_domain_quiz_ids = array(),
            $embed_domain_created_at,
            $embed_domain_updated_at;

    /**
    * @param $query STRING must be a root domain like 'domain.com'
    */
    public function __construct($query) {
        return $this->get_embed_domain($query);
    }

    /**
    *   Build embed_domain object by url
    *
    *   @param  $url = url that you want to select from embed_domain_url
    *   @return embed_domain object, false if not found
    **/
    public function get_embed_domain($query) {
        $embed_domain = false;

            $embed_domain = $this->select_embed_site_ids_by_domain($query);

        if($embed_domain !== false) {
            $embed_domain = $this->set_embed_domain_object_values($embed_domain);
        }

        return $embed_domain;
    }

    /**
    *   For using PDO to select one site row
    *
    *   @param  $url = url that you want to select
    *   @return row from database table if found, false if not found
    **/
    public function select_embed_site_ids_by_domain($domain) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":domain" => $domain
        );

        $sql = "SELECT embed_site_id, SUBSTRING_INDEX((SUBSTRING_INDEX((SUBSTRING_INDEX(embed_site_url, '://', -1)), '/', 1)), '.', -2) as domain from ".$pdo->embed_site_table." WHERE embed_site_is_dev=0 HAVING
                domain = :domain";
        $stmt = $pdo->query($sql, $params);
        $embed_domain_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // return the found domain row ids
        return $embed_domain_rows;
    }


    /**
    * Sets all object variables
    */
    protected function set_embed_domain_object_values($embed_domain_rows) {

        // create site objects for each id
        $sites = array_map( function ($row) { return new Enp_quiz_Embed_site($row['embed_site_id']); }, $embed_domain_rows);
        
        if(empty($sites)) {
            return;
        }

        // set some stuff off the first entry
        $this->set_embed_domain_id($embed_domain_rows[0]['domain']);
        $this->set_embed_domain_name($sites[0]->get_embed_site_name());
        $this->set_embed_domain_url($embed_domain_rows[0]['domain']);
        $this->set_embed_domain_created_at($sites[0]->get_embed_site_created_at());
        // set this off the last item in the array
        $this->set_embed_domain_updated_at($sites[count($sites)-1]->get_embed_site_updated_at());

        // set-up our arrays for storing things we will be setting soon
        $site_ids = [];
        $quiz_ids = [];

        foreach($sites as $site) {
            $site_ids[] = $site->get_embed_site_id();
            $site_quiz_ids = $site->get_embed_site_quiz_ids();
           
            foreach($site_quiz_ids as $quiz_id) {
                $quiz_ids[] = $quiz_id;
            }
        }


        $this->embed_domain_site_ids = $site_ids;
        $this->embed_domain_quiz_ids = $quiz_ids;

    }

    protected function set_embed_domain_id($embed_domain_id) {
        $this->embed_domain_id = $embed_domain_id;
        return $this->embed_domain_id;
    }

    protected function set_embed_domain_name($embed_domain_name) {
        $this->embed_domain_name = $embed_domain_name;
        return $this->embed_domain_name;
    }

    protected function set_embed_domain_url($embed_domain_url) {
        $this->embed_domain_url = $embed_domain_url;
        return $this->embed_domain_url;
    }

    protected function set_embed_domain_created_at($embed_domain_created_at) {
        $this->embed_domain_created_at = $embed_domain_created_at;
        return $this->embed_domain_created_at;
    }

    protected function set_embed_domain_updated_at($embed_domain_updated_at) {
        $this->embed_domain_updated_at = $embed_domain_updated_at;
        return $this->embed_domain_updated_at;
    }

    protected function set_embed_domain_type_ids() {
        $embed_domain_id = $this->get_embed_domain_id();
        $types = new Enp_quiz_Embed_domain_bridge('site', $embed_domain_id);
        $this->embed_domain_type_ids = $types->get_esb_type();
        return $this->embed_domain_type_ids;
    }

    protected function set_embed_domain_quiz_ids() {
        $embed_domain_id = $this->get_embed_domain_id();
        $embed_quizzes = $this->select_embed_quizzes_by_site_id($embed_domain_id);
        $eqs = array();
        if(is_array($embed_quizzes)) {
            foreach($embed_quizzes as $eq) {
                $eqs[] = $eq['embed_quiz_id'];
            }
        }
        $this->embed_domain_quiz_ids = $eqs;
        return $this->embed_domain_quiz_ids;
    }

    public function get_embed_domain_id() {
        return $this->embed_domain_id;
    }

    public function get_embed_domain_name() {
        return $this->embed_domain_name;
    }

    public function get_embed_domain_site_ids() {
        return $this->embed_domain_site_ids;
    }

    public function get_embed_domain_url() {
        return $this->embed_domain_url;
    }

    public function get_embed_domain_created_at() {
        return $this->embed_domain_created_at;
    }

    public function get_embed_domain_updated_at() {
        return $this->embed_domain_updated_at;
    }

    /**
    * Get all of a site's categories/types by id
    * @param $embed_domain_id (Optional)
    * @return $types (objects) from Enp_quiz_Embed_domain_type class
    */
    public function get_embed_domain_types() {
        return $this->embed_domain_types;
    }


}
