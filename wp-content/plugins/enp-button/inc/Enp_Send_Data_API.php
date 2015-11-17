<?
/*
*   Enp_Send_Data_API class
*   Processes all click metadata and sends it to ENP if the user chooses to allow data collection
*   since v0.0.9
*/
// To do everything, just create a new instance
// new Enp_Send_Data();
class Enp_Send_Data {
    protected $site_url;

    public function __construct() {
        // sets all data as empty string
        // $this->set_defaults();

        // sets the data
        $this->site_url = site_url();

        // if we want to move back to loops by default
        // $this->send_all_engaging_data();

    }

    /*
    *
    *   Sets a default array of all data to send when batch processing from mysql
    *
    */
    protected function set_batch_data_defaults($row, $slug) {
        $defaults = array(
                        'site_url' => $this->site_url,
                        'meta_id'  => $row->meta_id,
                        'button'   => $slug,
                        'clicks'   => $row->meta_value,
                        'post_id'    => null,
                        'comment_id' => null,
                        'post_type'  => null,
                        'button_url' => null,
                    );

        return $defaults;
    }


    public function send_click_data($data) {
        global $wpdb;
        try {
            if(empty($data['type'])) {
                throw new Exception('Enp_Send_Data: No $data[\'type\'] set in send_click_data.');
            }

            $row = $wpdb->get_row( 'SELECT * FROM '.$wpdb->{$data['type'].'meta'}.'
                                WHERE '.$data['type'].'_id = "'.$data['button_id'].'"
                                AND meta_key = "enp_button_'.$data['slug'].'"
                                LIMIT 1');
            if($row === null) {
                 throw new Exception('Enp_Send_Data: Row not found in send_click_data.');
            }

            // get comment or post data
            if($data['type'] === 'comment') {
                $comment_id = $data['button_id'];
                $post_id = $wpdb->get_var('SELECT comment_post_ID FROM '.$wpdb->comments.' WHERE comment_ID = "'.$comment_id.'" LIMIT 1');

                if($post_id === null) {
                    throw new Exception('Enp_Send_Data: $post_id not found by get_var in send_click_data.');
                }

                $post_type = 'comment';
            } else {
                $post_id = $data['button_id'];
                $comment_id = '0';
                $post_type = get_post_type($post_id);
            }

            $send_data = array(
                            'site_url' => $this->site_url,
                            'meta_id'  => $row->meta_id,
                            'button'   => $data['slug'],
                            'clicks'   => $row->meta_value,
                            'post_id'    => $post_id,
                            'comment_id' => $comment_id,
                            'post_type'  => $post_type,
                            'button_url' => $data['button_url']
                        );

            // send data to web service
            $send = $this->send_data($send_data);

        } catch(Exception $e) {
            $this->send_error($e->getMessage(), $data);
        }
    }

    /*
    *
    *   Main processing function. Gets all active button slugs and
    *
    */

    public function send_all_engaging_data() {
        $slugs = get_option('enp_button_slugs'); // active slugs
        if($slugs != false) {
            foreach($slugs as $slug) {
                $this->build_and_send_post_data($slug);

                $this->build_and_send_comment_data($slug);
            }
        } else {
            return false;
        }
    }


    /*
    *
    *   Builds and sends all post data
    *
    */
    protected function build_and_send_post_data($slug) {
        global $wpdb;
        $meta_rows = $wpdb->get_results( 'SELECT * FROM '.$wpdb->postmeta.' WHERE meta_key = "enp_button_'.$slug.'"');

        foreach($meta_rows as $row) {
            // set the post ID
            $post_id = $row->post_id;
            $post_type = get_post_type($post_id);

            // our unique data for this row
            $post_data = array(
                    'post_id'    => $post_id,
                    'comment_id' => 0,
                    'post_type'  => $post_type,
                    'button_url' => $this->set_post_button_url($post_type, $post_id),
                    );

            // send data
            $this->process_and_send_data($row, $post_data, $slug);

        }
    }

    /*
    *
    *   Builds and sends all comment data
    *
    */
    protected function build_and_send_comment_data($slug) {
        global $wpdb;
        $meta_rows = $wpdb->get_results( 'SELECT * FROM '.$wpdb->commentmeta.' WHERE meta_key = "enp_button_'.$slug.'"');

        foreach($meta_rows as $row) {
            // set the post ID
            $comment_id = $row->comment_id;
            // get post ID from comment table
            $post_id = $wpdb->get_var('SELECT comment_post_ID FROM '.$wpdb->comments.' WHERE comment_ID = "'.$comment_id.'" LIMIT 1');

            // our unique data for this row
            $comment_data = array(
                    'post_id'    => $post_id,
                    'comment_id' => $comment_id,
                    'post_type'  => 'comment',
                    'button_url' => $this->set_comment_button_url($comment_id, $post_id),
                    );

            // send data
            $this->process_and_send_data($row, $comment_data, $slug);

        }
    }

    protected function set_post_button_url($post_type, $post_id) {
        if($post_type === 'post') {
            $url_slug = 'p';
        } elseif($post_type === 'page') {
            $url_slug = 'page_id';
        } else {
            $url_slug = 'post_type='.$post_type.'&p';
        }
        return $this->site_url .'/?'.$url_slug.'='.$post_id;
    }

    protected function set_comment_button_url($comment_id, $post_id) {
        $post_type = get_post_type($post_id);
        // build the url off of our post_button_url function
        $post_url = $this->set_post_button_url($post_type, $post_id);
        // append the comment link
        return $post_url.'#comment-'.$comment_id;
    }

    protected function process_and_send_data($row, $data, $slug) {
        // get our default array
        $data_defaults = $this->set_batch_data_defaults($row, $slug);

        // merge our default and our row data
        $merged_data = array_merge($data_defaults, $data);

        // send data to web service
        $this->send_data($merged_data);
    }

    /*
    *
    *   Error handling
    *
    */
    protected function send_error($error_message, $data = array('no data found')) {
        $error_data = array(
                            'error'    => $error_message,
                            'timestamp'=> date("Y-m-d H:i:s")
                        );
        if(is_array($data)) {
            $error_data = array_merge($error_data, $data);
        }
        // TODO: I would love if there was just one curl connection since this is duplicate code
        //       But it's too easy to get stuck in a never ending loop of error/post if we reuse the send_data function
        // send the data
        $data_json = json_encode($error_data);
        $ch = curl_init();
        // local
        // curl_setopt($ch, CURLOPT_URL, 'http://dev/enp-api/api.php');
        // live
        curl_setopt($ch, CURLOPT_URL, 'http://fda668417f344263bdb9e66a5904eaf5.engagingnewsproject.org/api.php');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_json))
        );

        curl_exec($ch);

        curl_close($ch);
    }

    public function send_data($data = false) {
        try {
            if($data === false) {
                throw new Exception('Enp_Send_Data: No $data in send_data.');
            }
            // encode to json
            $data_json = json_encode($data);
            // if our data is not valid, quit
            if($data_json === false) {
                throw new Exception('Enp_Send_Data: Couldn\'t encode $data to json in send_data.');
            }

            // open connection
            $ch = curl_init();
            // local
            //curl_setopt($ch, CURLOPT_URL, 'http://dev/enp-api/api.php');
            // live
            curl_setopt($ch, CURLOPT_URL, 'http://fda668417f344263bdb9e66a5904eaf5.engagingnewsproject.org/api.php');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_json))
            );

            $json_result = curl_exec($ch);

            // decode results
            $result = json_decode($json_result);

            $curl_error = false;
            // check for errors
            if(curl_errno($ch)) {
                $curl_error = curl_error($ch);
            }
            // close our connection
            curl_close($ch);

            // throw error. We can't throw this error before we close the connection,
            // otherwise we don't have access to the error or we problematically
            // leave the connection open
            if($curl_error !== false) {
                throw new Exception($curl_error);
            }

            return $result;

        } catch(Exception $e) {
            $this->send_error($e->getMessage(), $data);
        }

    }

}

?>
