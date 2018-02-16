<?php

namespace Cme\Database;


/**
 * Add a question to the database
 */
class SaveEl extends DB {
    protected $tree_id,
              $el_type_id,
              $content,
              $description,
              $user_id;

    function __construct() {

    }

    /**
     *
     * @param $action (STRING) 'update' || 'insert'
     * @param $data (ARRAY) needs all the info to be saved
     * @return ARRAY with saved data or Errors
     */
    public function save($action, $data) {
        $this->validate($action, $data);
    }

    /**
     *
     * @param $action (STRING) 'update' || 'insert'
     * @param $data (ARRAY) needs all the info to be saved
     * @return true or false
     */
    protected function validate($action, $data) {

    }

    protected function insert($data) {

    }

    protected function update($data) {

    }

}
