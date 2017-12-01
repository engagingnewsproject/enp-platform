<?php
/**
 * Bridge IDs between embed_site and embed_site_type
 * to show which sites are in a category and which categories belong to a site
 *
 * @since      1.0.1
 * @package    Enp_quiz
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Embed_site_bridge {
    public  $esb_site = array(), // array if getting sites by a type
            $esb_type = array(); // array if getting types/categories assigned to a site

    /**
    *   If you have the 'news' type/category and want all the sites in the
    *   'news' category, then do:
    *   $bridge_from = 'type', $id = $news_id
    *
    *   If you have the site http://jeremyjon.es and want all the categories assigned
    *   to it, then do:
    *   $bridge_from = 'site', $id = $site_id
    *
    *   @param  $bridge_from (MIXED: STRING/INT) $id
    *                        pass the ID you want to select from
    *                        so if you want to get all the types of a site,
    *                        pass the site ID. If you want all sites assigned
    *                        to a type (like 'news'), then pass the ID
    *   @param $id The ID you're wanting to get the results for
    *   @return embed_site_type object, false if not found
    **/
    public function __construct($bridge_from, $id) {
        return $this->get_esb_by($bridge_from, $id);
    }

    /**
    *   Build embed_site object by slug or id
    *
    *   @param  $bridge_from (MIXED: STRING/INT) $id
    *                        pass the ID you want to select from
    *                        so if you want to get all the types of a site,
    *                        pass the site ID. If you want all sites assigned
    *                        to a type (like 'news'), then pass the ID
    *   @param $id The ID you're wanting to get the results for
    *   @return embed_site_type object, false if not found
    **/
    public function get_esb_by($bridge_from, $id) {

        $esb = false;
        // if it's one of our allowed types, get it!
        if($bridge_from === 'site') {
            $esb = $this->select_embed_site_bridge_by_site($id);
        } else if($bridge_from === 'type') {
            $esb = $this->select_embed_site_bridge_by_type($id);
        }

        if($esb !== false) {
            $esb = $this->set_esb_object_values_by($bridge_from, $esb);
        }
        return $esb;
    }

    /**
    *   For using PDO to select all a site's rows
    *
    *   @param  $site_id = site that you want to find categories for
    *   @return rows from database table if found, false if not found
    **/
    protected function select_embed_site_bridge_by_site($site_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":site_id" => $site_id
        );

        // there *should* only be one since embed_syte_type is a unique column
        $sql = "SELECT * from ".$pdo->embed_site_br_site_type_table." WHERE
                embed_site_id = :site_id";
        $stmt = $pdo->query($sql, $params);
        $embed_bridge_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // return the found rows
        return $embed_bridge_rows;
    }

    /**
    *   For using PDO to select all a site's rows
    *
    *   @param  $site_id = site that you want to find categories for
    *   @return rows from database table if found, false if not found
    **/
    protected function select_embed_site_bridge_by_type($type_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":type_id" => $type_id
        );

        // there *should* only be one since embed_syte_type is a unique column
        $sql = "SELECT * from ".$pdo->embed_site_br_site_type_table." WHERE
                embed_site_type_id = :type_id";
        $stmt = $pdo->query($sql, $params);
        $embed_bridge_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // return the found rows
        return $embed_bridge_rows;
    }

    /**
    * Sets all object variables
    */
    protected function set_esb_object_values_by($bridge_from, $esb_rows) {
        if(empty($esb_rows)) {
            return false;
        }

        if($bridge_from === 'site') {
            $site_id = $esb_rows[0]['embed_site_id'];
            $type_id = $this->extract_values_from_rows('embed_site_type_id', $esb_rows);
        } else if($bridge_from === 'type') {
            $type_id = $esb_rows[0]['embed_site_type_id'];
            $site_id = $this->extract_values_from_rows('embed_site_id', $esb_rows);
        }
        $this->set_esb_site($site_id);
        $this->set_esb_type($type_id);
    }

    /**
    * Get all the values of a key and and put them into an array together
    *
    * @param $key STRING
    * @param $rows ARRAY
    * @return ARRAY of all values by key
    */
    protected function extract_values_from_rows($key, $rows) {
        $vals = array();
        foreach($rows as $row) {
            if(array_key_exists($key, $row)) {
                $vals[] = $row[$key];
            }
        }
        return $vals;
    }


    protected function set_esb_site($esb_site) {
        $this->esb_site = $esb_site;
        return $this->esb_site;
    }

    protected function set_esb_type($esb_type) {
        $this->esb_type = $esb_type;
        return $this->esb_type;
    }

    public function get_esb_site() {
        return $this->esb_site;
    }

    public function get_esb_type() {
        return $this->esb_type;
    }

    // helper functions
    public function get_sites() {
        return $this->get_esb_site();
    }

    public function get_types() {
        return $this->get_esb_type();
    }

}
