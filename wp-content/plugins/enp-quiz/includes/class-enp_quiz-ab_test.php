<?
/**
* Create a user object and gives an overview of all the
* things that user is owner of
* @param $user_id = the id of the user you want to get
* @return user object
*/
class Enp_quiz_AB_test {
    public  $ab_test_id,
            $ab_test_title,
            $quiz_id_a,
            $quiz_id_b,
            $ab_test_owner,
            $ab_test_created_by,
            $ab_test_created_at,
            $ab_test_updated_by,
            $ab_test_updated_at,
            $ab_test_is_deleted;


    public function __construct($ab_test_id) {
        // returns false if no ab_test_id found
        $this->get_ab_test_by_id($ab_test_id);
    }

    /**
    *   Build quiz object by id
    *
    *   @param  $ab_test_id = quiz_id that you want to select
    *   @return quiz object, false if not found
    **/
    public function get_ab_test_by_id($ab_test_id) {
        $ab_test = $this->select_ab_test_by_id($ab_test_id);
        if($ab_test !== false) {
            $this->set_ab_test_object_values($ab_test);
        }
        return $ab_test;
    }

    /**
    *   For using PDO to select one ab_test row
    *
    *   @param  $ab_test_id = ab_test_id that you want to select
    *   @return row from database table if found, false if not found
    **/
    public function select_ab_test_by_id($ab_test_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":ab_test_id" => $ab_test_id
        );
        $sql = "SELECT * from ".$pdo->ab_test_table." WHERE
                ab_test_id = :ab_test_id
                AND ab_test_is_deleted = 0";
        $stmt = $pdo->query($sql, $params);
        $ab_test_row = $stmt->fetch();
        // return the found quiz row
        return $ab_test_row;
    }

    /**
    * Hook up all the values for the object
    * @param $quiz = row from the quiz_table
    */
    protected function set_ab_test_object_values($ab_test) {
        $this->ab_test_id = $this->set_ab_test_id($ab_test);
        $this->ab_test_title = $this->set_ab_test_title($ab_test);
        $this->quiz_id_a = $this->set_quiz_id_a($ab_test);
        $this->quiz_id_b = $this->set_quiz_id_b($ab_test);
        $this->ab_test_owner = $this->set_ab_test_owner($ab_test);
        $this->ab_test_created_by = $this->set_ab_test_created_by($ab_test);
        $this->ab_test_created_at = $this->set_ab_test_created_at($ab_test);
        $this->ab_test_updated_by = $this->set_ab_test_updated_by($ab_test);
        $this->ab_test_updated_at = $this->set_ab_test_updated_at($ab_test);
        $this->ab_test_is_deleted = $this->set_ab_test_is_deleted($ab_test);
    }

    /**
    * Set the ab_test_id for our Quiz Object
    * @param $ab_test = ab_test row from ab_test database table
    * @return ab_test_id field from the database
    */
    protected function set_ab_test_id($ab_test) {
        return $ab_test['ab_test_id'];
    }

    /**
    * Set the ab_test_title for our Quiz Object
    * @param $ab_test = ab_test row from ab_test database table
    * @return ab_test_title field from the database
    */
    protected function set_ab_test_title($ab_test) {
        $ab_test_title = stripslashes($ab_test['ab_test_title']);
        return $ab_test_title;
    }

    /**
    * Set the quiz_id_a for our Quiz Object
    * @param $ab_test = ab_test row from ab_test database table
    * @return quiz_id_a field from the database
    */
    protected function set_quiz_id_a($ab_test) {
        return $ab_test['quiz_id_a'];
    }

    /**
    * Set the quiz_id_b for our Quiz Object
    * @param $ab_test = ab_test row from ab_test database table
    * @return quiz_id_b field from the database
    */
    protected function set_quiz_id_b($ab_test) {
        return $ab_test['quiz_id_b'];
    }

    /**
    * Set the created_by for our Quiz Object
    * @param $ab_test = ab_test row from ab_test database table
    * @return created_by field from the database
    */
    protected function set_ab_test_owner($ab_test) {
        return $ab_test['ab_test_owner'];
    }

    /**
    * Set the created_by for our Quiz Object
    * @param $ab_test = ab_test row from ab_test database table
    * @return created_by field from the database
    */
    protected function set_ab_test_created_by($ab_test) {
        return $ab_test['ab_test_created_by'];
    }

    /**
    * Set the created_at for our Quiz Object
    * @param $ab_test = ab_test row from ab_test database table
    * @return created_at field from the database
    */
    protected function set_ab_test_created_at($ab_test) {
        return $ab_test['ab_test_created_at'];
    }

    /**
    * Set the updated_by for our Quiz Object
    * @param $ab_test = ab_test row from ab_test database table
    * @return updated_by field from the database
    */
    protected function set_ab_test_updated_by($ab_test) {
        return $ab_test['ab_test_updated_by'];
    }

    /**
    * Set the updated_at for our Quiz Object
    * @param $ab_test = ab_test row from ab_test database table
    * @return updated_at field from the database
    */
    protected function set_ab_test_updated_at($ab_test) {
        return $ab_test['ab_test_updated_at'];
    }

    /**
    * Set the is_deleted for our Quiz Object
    * @param $ab_test = ab_test row from ab_test database table
    * @return is_deleted field from the database
    */
    protected function set_ab_test_is_deleted($ab_test) {
        return $ab_test['ab_test_is_deleted'];
    }

    /**
    * Get the ab_test_id for our AB Test Object
    * @return ab_test_id from the object
    */
    public function get_ab_test_id() {
        return $this->ab_test_id;
    }

    /**
    * Get the ab_test_title for our AB Test Object
    * @return ab_test_title from the object
    */
    public function get_ab_test_title() {
        return $this->ab_test_title;
    }

    /**
    * Get the quiz_id_a for our AB Test Object
    * @return quiz_id_a from the object
    */
    public function get_quiz_id_a() {
        return $this->quiz_id_a;
    }

    /**
    * Get the quiz_id_b for our AB Test Object
    * @return quiz_id_b from the object
    */
    public function get_quiz_id_b() {
        return $this->quiz_id_b;
    }

    /**
    * Get the owner for our AB Test Object
    * @return ab_test_owner from the object
    */
    public function get_ab_test_owner() {
        return $this->ab_test_owner;
    }

    /**
    * Get the created_by for our AB Test Object
    * @return ab_test_created_by from the object
    */
    public function get_ab_test_created_by() {
        return $this->ab_test_created_by;
    }

    /**
    * Get the created_at for our AB Test Object
    * @return ab_test_created_at from the object
    */
    public function get_ab_test_created_at() {
        return $this->ab_test_created_at;
    }

    /**
    * Get the ab_test_updated_by for our AB Test Object
    * @return ab_test_updated_by from the object
    */
    public function get_ab_test_updated_by() {
        return $this->ab_test_updated_by;
    }

    /**
    * Get the updated_at for our AB Test Object
    * @return ab_test_updated_at from the object
    */
    public function get_ab_test_updated_at() {
        return $this->ab_test_updated_at;
    }

    /**
    * Get the is_deleted for our AB Test Object
    * @return ab_test_is_deleted from the object
    */
    public function get_ab_test_is_deleted() {
        return $this->ab_test_is_deleted;
    }
}
