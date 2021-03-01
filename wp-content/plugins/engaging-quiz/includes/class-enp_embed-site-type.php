<?php
/**
 * Create an embed site object
 * Shows what sites have embedded quizzes on them
 *
 * @since      1.0.1
 * @package    Enp_quiz
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Embed_site_type {
    public  $embed_site_type_id,
            $embed_site_type_slug,
            $embed_site_type_name;

    public function __construct($selector) {
        return $this->get_embed_site_type($selector);
    }

    /**
    *   Build embed_site object by slug or id
    *
    *   @param  $selector (MIXED: STRING/INT) $slug or $id
    *   @return embed_site_type object, false if not found
    **/
    public function get_embed_site_type($selector) {
        // if it's an integer or only has number characters,
        // then it's an ID
        $selector_check = new Enp_quiz_Slugify();
        $is_id = $selector_check->is_id($selector);

        if($is_id === true) {
            $embed_site_type = $this->select_embed_site_type_by_id($selector);
        } else {
            $embed_site_type = $this->select_embed_site_type_by_slug($selector);
        }

        if($embed_site_type !== false) {
            $embed_site_type = $this->set_embed_site_type_object_values($embed_site_type);
        }
        return $embed_site_type;
    }

    /**
    *   For using PDO to select one embed site type row
    *
    *   @param  $slug = type name that you want to select
    *   @return row from database table if found, false if not found
    **/
    protected function select_embed_site_type_by_slug($embed_site_type_slug) {
        // make sure it's a legit slug
        $slugify = new Enp_quiz_Slugify();
        $is_slug = $slugify->is_slug($embed_site_type_slug);
        if($is_slug !== true) {
            // uh oh. bad slug.
            return false;
        }

        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":embed_site_type_slug" => $embed_site_type_slug
        );

        // there *should* only be one since embed_syte_type is a unique column
        $sql = "SELECT * from ".$pdo->embed_site_type_table." WHERE
                embed_site_type_slug = :embed_site_type_slug";
        $stmt = $pdo->query($sql, $params);
        $embed_site_row = $stmt->fetch();
        // return the found row
        return $embed_site_row;
    }

    /**
    *   For using PDO to select one embed site type row
    *
    *   @param  $id = id that you want to select
    *   @return row from database table if found, false if not found
    **/
    protected function select_embed_site_type_by_id($embed_site_type_id) {

        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":embed_site_type_id" => $embed_site_type_id
        );

        // there *should* only be one since embed_syte_type is a unique column
        $sql = "SELECT * from ".$pdo->embed_site_type_table." WHERE
                embed_site_type_id = :embed_site_type_id
                LIMIT 1";
        $stmt = $pdo->query($sql, $params);
        $embed_site_row = $stmt->fetch();
        // return the found quiz row
        return $embed_site_row;
    }

    /**
    * Sets all object variables
    */
    protected function set_embed_site_type_object_values($embed_site_type) {
         $this->set_embed_site_type_id($embed_site_type['embed_site_type_id']);
         $this->set_embed_site_type_slug($embed_site_type['embed_site_type_slug']);
         $this->set_embed_site_type_name($embed_site_type['embed_site_type_name']);
    }

    protected function set_embed_site_type_id($embed_site_type_id) {
        $this->embed_site_type_id = $embed_site_type_id;
        return $this->embed_site_type_id;
    }

    protected function set_embed_site_type_slug($embed_site_type_slug) {
        $this->embed_site_type_slug = $embed_site_type_slug;
        return $this->embed_site_type_slug;
    }

    protected function set_embed_site_type_name($embed_site_type_name) {
        $this->embed_site_type_name = $embed_site_type_name;
        return $this->embed_site_type_name;
    }

    public function get_embed_site_type_id() {
        return $this->embed_site_type_id;
    }

    public function get_embed_site_type_slug() {
        return $this->embed_site_type_slug;
    }

    public function get_embed_site_type_name() {
        return $this->embed_site_type_name;
    }

    /**
    * Get all sites attached to a site type
    */
    public function get_embed_sites($embed_site_type_id = false) {
        // if none was passed, use the current one
        if($embed_site_type_id === false) {
            $embed_site_type_id = $this->get_embed_site_type_id();
        }
        // get the types attached to this site
        $type = new Enp_quiz_Embed_site_bridge('type', $embed_site_type_id);
        $sites = array();
        foreach($type->get_sites() as $site_id) {
            // build a new type object
            $site = new Enp_quiz_Embed_site($site_id);
            if($site->get_embed_site_id() !== null) {
                $sites[] = $site;
            }
        }
        return $sites;
    }


}
