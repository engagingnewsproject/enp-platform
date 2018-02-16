<?php

namespace Cme\Database;
use Cme\Utility as Utility;

/**
 * Add a question to the database
 */
class SaveEmbed extends DB {
    public $DB;

    function __construct() {
        $this->DB = new \Cme\Database\DB();
    }

    /**
     *
     * @param $data (ARRAY) needs all the info to be saved
     * @return ARRAY with saved data or Errors
     */
    public function save($embed) {
        // decide how to save it...
        return $this->insert($embed);
    }

    /* Validates data structure
     * @param $data (ARRAY) needs all the info to be saved
     *
     *    'embed'=> [
     *       'tree_id'=> (STRING/INT), // from tree table
     *       'site_id'=> (STRING/INT), // from tree_site table
     *       'path'   => (STRING), // ex. '/path/to/embed/'
     *       'iframe' => (BOOLEAN), // is it in an iframe or not?
     *     ]
     *
     * @return true or false
     */
    protected function validate($embed) {
        $is_valid = false;

        // check if we have all the data we need
        if(!isset($embed['tree_id'])) {
            $this->errors[] = 'No Tree ID sent.';
        }

        if(!isset($embed['site_id'])) {
            $this->errors[] = 'No Site ID sent.';
        }

        if(!isset($embed['path'])) {
            $this->errors[] = 'No Embed Path sent.';
        }

        if(empty($embed['path'])) {
            $this->errors[] = 'Embed Path is empty.';
        }

        if(!isset($embed['is_iframe'])) {
            $this->errors[] = 'Is it an iframe? Set is_iframe to true or false.';
        }

        // if we have any errors, return false. Passes first round of being the correct data structure
        if(!empty($this->errors)) {
            return $is_valid;
        }

        // check that it's a valid pathname by adding 'http://example.com' to test it
        if(filter_var('http://example.com'.$embed['path'], FILTER_VALIDATE_URL) === false) {
            $this->errors[] = 'Invalid path.';
        }

        // check that the site exists
        $site = $this->DB->get_site($embed['site_id']);
        if($site === false) {
            $this->errors[] = 'Site doesn\'t exist.';
        }

        // check that it doesn't already exist
        if($this->DB->get_site($embed['path']) !== false) {
            $this->errors[] = 'Site already exists.';
        }

        // check that it's a valid Tree
        if($this->DB->validate_tree_id($embed['tree_id']) === false) {
            $this->errors[] = 'Invalid tree_id.';
        }

        // check that is_iframe is boolean
        if(is_bool( $embed['is_iframe'] ) === false) {
            $this->errors[] = 'is_iframe must be boolean.';
        }

        // if we have don't have any errors, it's valid!
        if(empty($this->errors)) {
            $is_valid = true;
        }

        return $is_valid;

    }

    protected function sanitize($embed) {
        if(isset($embed['path'])) {
            $embed['path'] = filter_var($embed['path'], FILTER_SANITIZE_URL);
        }

        return $embed;
    }

    /**
    * Inserts a new interaction
    * @param $data (ARRAY) See validate for structure
    * @return (ARRAY)
    */
    protected function insert($embed) {
        // sanitize data
        $embed = $this->sanitize($embed);
        // make sure it's valid
        $is_valid = $this->validate($embed);

        // if validation doesn't pass, return the errors
        if($is_valid !== true) {
            return $this->errors;
        }
        // check if it exists already
        $embed_check = $this->DB->get_embed($embed['path'],
                                        ['site_id' => $embed['site_id'],
                                         'tree_id' => $embed['tree_id']
                                        ]);
        if($embed_check !== false) {
            $response = [
                            'embed_id'   => $embed_check['embed_id'],
                            'status'     => 'success',
                            'action'     => 'embedExists'
                        ];

            return $response;
        }


        // Get our Parameters ready
        $params = [
                    ':tree_id'              => $embed['tree_id'],
                    ':site_id'              => $embed['site_id'],
                    ':embed_path'           => $embed['path'],
                    ':embed_is_iframe'      => $embed['is_iframe']
                  ];
        // write our SQL statement
        $sql = 'INSERT INTO '.$this->DB->tables['tree_embed'].' (
                                            site_id,
                                            tree_id,
                                            embed_path,
                                            embed_is_iframe
                                        )
                                        VALUES(
                                            :site_id,
                                            :tree_id,
                                            :embed_path,
                                            :embed_is_iframe
                                        )';
        // insert the mc_option into the database
        $stmt = $this->DB->query($sql, $params);

        // success!
        if($stmt !== false) {
            $embed_id = $this->DB->lastInsertId();

            $response = [
                            'embed_id'          => $embed_id,
                            'status'           => 'success',
                            'action'           => 'insertEmbed'
                        ];
        } else {
            // handle errors
            $this->errors[] = 'Insert embed failed.';
            $this->errors['status'] = 'error';
        }


        if(!empty($this->errors)) {
            return $this->errors;
        }

        return $response;
    }

}
