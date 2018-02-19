<?php

namespace Cme\Database;
use Cme\Utility as Utility;

/**
 * Add a question to the database
 */
class SaveSite extends DB {
    public $DB;

    function __construct() {
        $this->DB = new \Cme\Database\DB();
    }

    /**
     *
     * @param $data (ARRAY) needs all the info to be saved
     * @return ARRAY with saved data or Errors
     */
    public function save($site) {

        // decide how to save it...
        return $this->insert($site);
    }

    /* Validates data structure
     * @param $data (ARRAY) needs all the info to be saved
     *
     *    'site'=> [
     *       'name'=> (STRING),
     *       'host' => (STRING)
     *     ]
     *
     * @return true or false
     */
    protected function validate($site) {
        $is_valid = false;

        // check if we have all the data we need
        if(!isset($site['name'])) {
            $this->errors[] = 'No Site Name sent.';
        }

        if(!isset($site['host'])) {
            $this->errors[] = 'No Site Host sent.';
        }

        // if we have any errors, return false. Passes first round of being the correct data structure
        if(!empty($this->errors)) {
            return $is_valid;
        }

        // check that it's a valid URL by adding 'http://' to test it
        if(filter_var('http://'.$site['host'], FILTER_VALIDATE_URL) === false) {
            $this->errors[] = 'Invalid host.';
        }

        // if we have don't have any errors, it's valid!
        if(empty($this->errors)) {
            $is_valid = true;
        }

        return $is_valid;

    }

    protected function sanitize($site) {
        if(isset($site['host'])) {
            $site['host'] = filter_var($site['host'], FILTER_SANITIZE_URL);
        }

        return $site;
    }

    /**
    * Inserts a new interaction
    * @param $data (ARRAY) See validate for structure
    * @return (ARRAY)
    */
    protected function insert($site) {
        // sanitize data
        $site = $this->sanitize($site);
        // make sure it's valid
        $is_valid = $this->validate($site);

        // if validation doesn't pass, return the errors
        if($is_valid !== true) {
            return $this->errors;
        }

        // check if it exists already
        $site_check = $this->DB->get_site($site['host']);
        if($site_check !== false) {
            $response = [
                            'site_id'   => $site_check['site_id'],
                            'status'    => 'success',
                            'action'    => 'siteExists'
                        ];

            return $response;
        }

        // Get our Parameters ready
        $params = [
                    ':site_host'              => $site['host'],
                    ':site_name'              => $site['name']
                  ];
        // write our SQL statement
        $sql = 'INSERT INTO '.$this->DB->tables['tree_site'].' (
                                            site_host,
                                            site_name
                                        )
                                        VALUES(
                                            :site_host,
                                            :site_name
                                        )';
        // insert the mc_option into the database
        $stmt = $this->DB->query($sql, $params);

        // success!
        if($stmt !== false) {
            $site_id = $this->DB->lastInsertId();

            $response = [
                            'site_id'          => $site_id,
                            'status'           => 'success',
                            'action'           => 'insertSite'
                        ];

        } else {
            // handle errors
            $this->errors[] = 'Insert site failed.';
            $this->errors['status'] = 'error';
        }


        if(!empty($this->errors)) {
            return $this->errors;
        }

        return $response;
    }

}
