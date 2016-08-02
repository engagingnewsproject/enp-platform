<?php
/**
* A little utility class for searching quizzes
*/
class Enp_quiz_Search_quizzes {
    private    $_type; // admin or user

    protected  $search = '', // string
               $include = 'user', // 'all_users'
               $order_by = 'quiz_created_at',
               $status = '',
               $order = 'DESC',
               $page = '1',
               $offset = '0',
               $limit = '10',
               $deleted = '0',
               $total;

    public function __construct() {

        $this->_type = $this->set_type();

    }

    /**
    * Set if we're in admin or user mode for searches.
    * Admins can search ALL quizzes. Users can only search their own.
    */
    private function set_type() {
        if(current_user_can('manage_options')) {
            $_type = 'admin';
        } else {
            $_type = 'user';
        }

        return $_type;
    }

    /**
    * Which column you want to order results by
    * @param $order (string) Options: 'quiz_created_at', 'quiz_updated_at', 'quiz_views', 'quiz_starts', 'quiz_finishes', 'quiz_score_average'
    */
    public function set_order_by($order_by) {
        $order_by_whitelist = array('quiz_created_at',
                                    'quiz_updated_at',
                                    'quiz_views',
                                    'quiz_starts',
                                    'quiz_finishes',
                                    'quiz_score_average',
                                    'published',
                                    'draft');
        // set a default
        $this->order_by = 'quiz_created_at';

        // special ones first
        if($order_by === 'quiz_completion_rate') {
            $this->order_by = 'quiz_finishes / quiz_views';
        }

        // see if what they submitted is in our whitelist
        else if(in_array($order_by, $order_by_whitelist)) {
            // if it's in the whitelist, let it be set
            $this->order_by = $order_by;
        }

        // check to see if want to see published or draft quizzes
        if($this->order_by === 'published' || $this->order_by === 'draft') {
            $this->set_status($this->order_by);
            // order it by created
            $this->set_order_by('quiz_created_at');
        }

    }

    /**
    * Sets if we want ascending or descending order for results
    * @param $str (string) 'ASC' or 'DESC'
    */
    public function set_order($order) {
        $this->order = 'ASC';
        if($order === 'DESC') {
            $this->order = 'DESC';
        }
    }

    /**
    * Sets the status
    * @param $str (string) ('published',or 'draft')
    */
    public function set_status($status) {
        $this->status = '';
        if($status === 'published' || $status === 'draft') {
            $this->status = $status;
        }
    }

    /**
    * Limit the setting of an include to 'user' or 'all_users' IF admin
    */
    public function set_include($str) {
        $this->include = 'user';
        if($str === 'all_users' && $this->_type === 'admin') {
            $this->include = 'all_users';
        }
    }

    /**
    * Set a search to be the string.
    * @param $str (string) string you want to set as the search
    * @note Make sure to always quote this before doing a select query
    */
    public function set_search($str) {
        // set it as the search
        $this->search = $str;
    }

    /**
    * Set the page number you want to view
    */
    public function set_page($str) {
        // set the string to an integer
        $this->page = (int) $str;
    }

    /**
    * Set the limit for the number of results you want to return
    */
    public function set_limit($str) {
        // set the string to an integer
        $this->limit = (int) $str;
    }

    /**
    * Set the offset for the PDO query off of page * limit
    */
    protected function set_offset() {
        $this->offset = $this->page * $this->limit - $this->limit;
    }

    /**
    * Set if you want to see deleted or active results
    * 0 = not deleted, 1 = deleted
    */
    public function set_deleted($str) {
        $this->deleted = '0';
        // if the string is '1', then set it.
        if($str === '1') {
            $this->deleted = 1;
        }
    }


    public function set_variables_from_url_query() {
        // check for variables
        if(isset($_GET['include']) && $_GET['include']==='all_users') {
            // get from ALL users
            $this->set_include('all_users');
        }

        $accepted_params = array('search',
                                 'order',
                                 'order_by',
                                 'status',
                                 'page',
                                 'limit',
                                 'deleted');

        // check each $_GET key, and if it's in our accepted_params list
        // check the $val and set it if it's not empty
        foreach($_GET as $key => $val) {
            if(in_array($key, $accepted_params)) {
                // check to make sure it's really set (it should be)
                // and that it's not empty
                if($val !== '') {
                    // pass the value to our setter
                    $set_value = "set_$key";
                    $this->$set_value($val);
                }
            }
        }

    }

    /**
    * The main function that people will call in order to actual
    * find results
    * @return ARRAY of quiz_ids
    */
    public function select_quizzes() {

        // make a pdo connection
        $pdo = new enp_quiz_Db();

        $status_sql = $this->get_status_sql();
        $search_sql = $this->get_search_sql($pdo);
        $include_sql = $this->get_include_sql();
        // set our offset
        $this->set_offset();

        $sql = "SELECT quiz_id from $pdo->quiz_table
                WHERE quiz_is_deleted = $this->deleted
                $status_sql
                $search_sql
                $include_sql
                ORDER BY $this->order_by $this->order
                LIMIT $this->limit
                OFFSET $this->offset";
        $stmt = $pdo->query($sql);
        $quiz_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $total_sql = "SELECT COUNT(*) from $pdo->quiz_table
                WHERE quiz_is_deleted = $this->deleted
                $status_sql
                $search_sql
                $include_sql";
        $total_stmt = $pdo->query($total_sql);
        $this->total = $total_stmt->fetchColumn();

        if($this->_type === 'admin' && $this->include === 'all_users') {
            $quiz_ids_by_user = $this->include_quizzes_by_user($quiz_ids);
            if(!empty($quiz_ids_by_user)) {
                $quiz_ids = $quiz_ids_by_user;
            }
        }



        return $quiz_ids;
    }

    public function include_quizzes_by_user($quiz_ids) {

        // access global wpdb
        global $wpdb;

        // Do a search of all users by email address and see if it matches anyone
        $users = $wpdb->get_col(
            $wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_email LIKE %s", '%'.$this->search.'%')
        );

        if(!empty($users)) {
            // do another query to get those user/s quizzes
            $pdo = new enp_quiz_Db();

            $status_sql = $this->get_status_sql();
            $include_sql = $this->get_include_sql();
            $quiz_ids_sql = $this->get_quiz_ids_sql($quiz_ids);

            $sql = "SELECT quiz_id from $pdo->quiz_table
                    WHERE quiz_is_deleted = $this->deleted
                    AND quiz_created_by IN (" . implode(',', array_map('intval', $users)) . ")
                    $status_sql
                    $include_sql
                    $quiz_ids_sql
                    ORDER BY $this->order_by $this->order
                    LIMIT $this->limit
                    OFFSET $this->offset";
            $stmt = $pdo->query($sql);
            $quiz_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);


            $total_sql = "SELECT COUNT(*) from $pdo->quiz_table
                    WHERE quiz_is_deleted = $this->deleted
                    AND quiz_created_by IN (" . implode(',', array_map('intval', $users)) . ")
                    $status_sql
                    $include_sql
                    $quiz_ids_sql";
            $total_stmt = $pdo->query($total_sql);
            $this->total = $total_stmt->fetchColumn();
        }

        return $quiz_ids;
    }

    /**
    * Include all quizzes already found in the sql query
    */
    protected function get_quiz_ids_sql($quiz_ids) {
        $quiz_ids_sql = '';
        if(!empty($quiz_ids)) {
            $quiz_ids_sql =  " OR quiz_id IN (" . implode(',', array_map('intval', $quiz_ids)) . ")";
        }
        return $quiz_ids_sql;
    }


    /**
    * Build the status sql query
    */
    protected function get_status_sql() {
        $status_sql = '';
        if($this->status !=='') {
            $status_sql =  " AND quiz_status = '$this->status'";
        }
        return $status_sql;
    }

    /**
    * Build the search sql query
    */
    protected function get_search_sql($pdo) {
        $search_sql = '';

        if($this->search !== '') {
            // make it a wildcard string
            $search_str = '%'.$this->search.'%';
            // quote it for security
            $quoted_str = $pdo->quote($search_str);
            // build the sql
            $search_sql = " AND quiz_title LIKE $quoted_str";
        }

        return $search_sql;
    }

    /**
    * Build the include sql query
    */
    protected function get_include_sql() {
        if($this->_type === 'admin' && $this->include === 'all_users') {
            $include_sql = '';
        } else {
            $include_sql = ' AND quiz_created_by = '.get_current_user_id();
        }

        return $include_sql;
    }


    public function get_total() {
        return $this->total;
    }

    public function get_page() {
        return $this->page;
    }

    public function get_limit() {
        return $this->limit;
    }
}
