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
        } elseif($order_by === 'quiz_start_rate') {
            $this->order_by = 'quiz_starts / quiz_views';
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

        // if it's one that should only search published quizzes, set the
        // status to published
        $published_only = array('quiz_views',
                                'quiz_starts',
                                'quiz_finishes',
                                'quiz_score_average',
                                'quiz_completion_rate',
                                'quiz_start_rate'
                                );

        if(in_array($order_by, $published_only) ) {
            $this->set_status('published');
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

    /*
    * Decide which quiz search function we're going to use
    * @return $quiz_ids (array)
    */
    public function select_quizzes() {
        // set our offset
        $this->set_offset();

        if($this->_type === 'admin' && $this->include === 'all_users' && $this->search !== '') {
            // let admin all_user searches include search by user email
            $quiz_ids = $this->search_include_quizzes_all_users_join();

        } else {
            $quiz_ids = $this->select_quizzes_one_user();
        }
        return $quiz_ids;
    }

    /**
    * The main function that people will call in order to actual
    * find results
    * @return ARRAY of quiz_ids
    */
    public function select_quizzes_one_user() {

        // make a pdo connection
        $pdo = new enp_quiz_Db();

        $AND_sql = array($this->get_status_sql(),
                         $this->get_search_sql($pdo),
                         $this->get_include_sql()
                     );
        $AND_sql = $this->and_it($AND_sql);
        if($AND_sql !== ''){
            $AND_sql = ' AND ('.$AND_sql.')';
        }

        $sql = "SELECT quiz_id from $pdo->quiz_table
                WHERE quiz_is_deleted = $this->deleted
                $AND_sql
                ORDER BY $this->order_by $this->order
                LIMIT $this->limit
                OFFSET $this->offset";
        $stmt = $pdo->query($sql);
        $quiz_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $total_sql = "SELECT COUNT(*) from $pdo->quiz_table
                WHERE quiz_is_deleted = $this->deleted
                $AND_sql";
        $total_stmt = $pdo->query($total_sql);
        $this->total = $total_stmt->fetchColumn();

        return $quiz_ids;
    }


    /**
    * Try getting the search results by doing a join on the user
    * table with PDO. If it fails, it'll fall back to a pretty good
    * replacement search
    * @return $quiz_ids (array) of quiz_ids
    */
    public function search_include_quizzes_all_users_join() {
        try {
            // access global wpdb

            global $wpdb;
            $user_table = $wpdb->users;

            // query to get any possible quiz match
            $pdo = new enp_quiz_Db();

            $user_sql = '';

            $quiz_sql = array($this->get_status_sql('quiz'),
                             $this->get_include_sql(),
                         );
            $quiz_sql = $this->and_it($quiz_sql);

            $search_term = $this->process_search_string($pdo);
            $search_sql = array(
                                "user.user_email LIKE $search_term", $this->get_search_sql($pdo, 'quiz')
                            );
            $search_sql = $this->or_it($search_sql);
            if($search_sql !== '' && $quiz_sql !== '') {
                $user_sql = "AND ($quiz_sql AND ($search_sql))";
            } elseif ($search_sql !== '') {
                $user_sql = "AND ($search_sql)";
            }

            $sql = "SELECT quiz_id from $pdo->quiz_table quiz
                    INNER JOIN $user_table user
                    ON quiz.quiz_created_by = user.ID
                    WHERE quiz.quiz_is_deleted = $this->deleted
                    $user_sql
                    ORDER BY quiz.$this->order_by $this->order
                    LIMIT $this->limit
                    OFFSET $this->offset";
            $stmt = $pdo->query($sql);
            $quiz_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // get the total found
            $total_sql = "SELECT COUNT(*) from $pdo->quiz_table quiz
                    INNER JOIN $user_table user
                    ON quiz.quiz_created_by = user.ID
                    WHERE quiz.quiz_is_deleted = $this->deleted
                    $user_sql";
            $total_stmt = $pdo->query($total_sql);
            $this->total = $total_stmt->fetchColumn();

            return $quiz_ids;
        } catch (Exception $e) {
            // var_dump('failed');
            $quiz_ids = $this->search_include_quizzes_all_users();
            return $quiz_ids;
        }
    }

    /**
    * Include searching user emails when an admin is using the search field
    * This function is to make sure
    * we can search by user using our PDO and $wp_global tables when the
    * Quiz Database and WP Database are on different connections.
    * @return $quiz_ids (array) of quiz_ids
    */
    public function search_include_quizzes_all_users() {

        // access global wpdb
        global $wpdb;

        // query to get any possible quiz match
        $pdo = new enp_quiz_Db();

        $AND_sql = array($this->get_status_sql(),
                         $this->get_search_sql($pdo),
                         $this->get_include_sql()
                     );
        $AND_sql = $this->and_it($AND_sql);
        $initial_AND_sql = ($AND_sql !== '' ? ' AND ' : '').$AND_sql;
        // get all possible quiz IDs for this query (no limit or offset)
        $sql = "SELECT quiz_id from $pdo->quiz_table
                WHERE quiz_is_deleted = $this->deleted
                $initial_AND_sql
                ORDER BY $this->order_by $this->order";
        $stmt = $pdo->query($sql);

        $quiz_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Do a search of all users by email address and see if it matches anyone
        $users = $wpdb->get_col(
            $wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_email LIKE %s", '%'.$this->search.'%')
        );

        $users_sql = $this->get_users_sql($users);

        $quiz_ids_sql = $this->get_quiz_ids_sql($quiz_ids);

        if($users_sql !== '') {
            $quiz_sql = array($this->get_status_sql(),
                             $this->get_include_sql(),
                             $users_sql
                         );
            $quiz_sql = $this->and_it($quiz_sql);
            if($quiz_sql !== '') {
                $quiz_sql = "($quiz_sql)";
            }
        } else {
            $quiz_sql = '';
        }


        $search_sql = array(
                            $quiz_sql,
                            $quiz_ids_sql
                        );
        $search_sql = $this->or_it($search_sql);
        if($search_sql !== '') {
            $user_sql = "AND ($search_sql)";
        } else {
            $user_sql = "";
        }

        $sql = "SELECT quiz_id from $pdo->quiz_table
                WHERE quiz_is_deleted = $this->deleted
                $user_sql
                ORDER BY $this->order_by $this->order
                LIMIT $this->limit
                OFFSET $this->offset";
        $stmt = $pdo->query($sql);
        $quiz_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $total_sql = "SELECT COUNT(*) from $pdo->quiz_table
                WHERE quiz_is_deleted = $this->deleted
                $user_sql";
        $total_stmt = $pdo->query($total_sql);
        $this->total = $total_stmt->fetchColumn();

        return $quiz_ids;
    }



    /**
    * Include all quizzes already found in the sql query
    */
    protected function get_quiz_ids_sql($quiz_ids) {
        $quiz_ids_sql = '';
        if(!empty($quiz_ids)) {
            $quiz_ids_sql =  " quiz_id IN (" . implode(',', array_map('intval', $quiz_ids)) . ")";
        }
        return $quiz_ids_sql;
    }


    /**
    * Build the status sql query
    * @param $join_string
    */
    protected function get_status_sql($join_string = '') {
        $join_string = $this->process_join_string($join_string);

        $status_sql = '';
        if($this->status !=='') {
            $status_sql =  "${join_string}quiz_status = '$this->status'";
        }
        return $status_sql;
    }

    /**
    * If it's a query on a join table, add a . to the table reference
    */
    protected function process_join_string($join_string = '') {
        if($join_string !== '') {
            $join_string .= '.'; // add a . after it
        }
        return $join_string;
    }

    /**
    * Build the search sql query
    * @return MYSQL Search query
    */
    protected function get_search_sql($pdo, $join_string = '') {
        $join_string = $this->process_join_string($join_string);
        $search_sql = '';

        if($this->search !== '') {
            // wildcard and quote it
            $quoted_str = $this->process_search_string($pdo);
            // build the sql
            $search_sql = "${join_string}quiz_title LIKE $quoted_str";
        }

        return $search_sql;
    }

    /**
    * Make search strings a wildcard and quote it
    * @param $pdo = PDO database connection
    * @return wildcard, quoted search query term
    */
    protected function process_search_string($pdo) {
        // make it a wildcard string
        $search_str = '%'.$this->search.'%';
        // quote it for security
        $quoted_str = $pdo->quote($search_str);

        return $quoted_str;
    }

    /**
    * Build the include sql query
    * If admin && all users, allow search all. Otherwise limit to current user
    * @return string
    */
    protected function get_include_sql() {

        if($this->_type === 'admin' && $this->include === 'all_users') {
            $include_sql = '';
        } else {
            $include_sql = 'quiz_created_by = '.get_current_user_id();
        }

        return $include_sql;
    }

    /**
    * For fallback User search.
    * @param $users (array) user ids
    * @return quiz_created_by string search for all matching user ids
    */
    protected function get_users_sql($users) {
        $user_sql = '';
        if(!empty($users)) {
            $user_sql = "quiz_created_by IN (" . implode(',', array_map('intval', $users)) . ")";
        }
        return $user_sql;
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

    /**
    * Take an array of statements and put AND between them
    * @param $array (array) of strings
    * @return (string)
    */
    public function and_it($array) {
        // remove empty items from array
        $array = array_filter($array);
        $stmt = implode(' AND ', $array);

        return $stmt;
    }

    /**
    * Take an array of statments and put OR between them
    * @param $array (array) of strings
    * @return (string)
    */
    public function or_it($array) {
        // remove empty items from array
        $array = array_filter($array);
        $stmt = implode(' OR ', $array);

        return $stmt;
    }
}
