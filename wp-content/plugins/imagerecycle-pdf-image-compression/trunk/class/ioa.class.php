<?php
defined('ABSPATH') || die('No direct script access allowed!');

class ioaphp
{
    /**
     * Authentication values array
     * @var array
     */
    protected $auth = array();

    /**
     * Image Optimize API Url
     * @var string api url
     */
    protected $apiUrl = 'https://api.imagerecycle.com/v1/';

    /**
     * Last Error message
     * @var string
     */
    protected $lastError = null;

    /**
     * Last Error code
     * @var string
     */
    protected $lastErrCode = null;

    /**
     * Full CURL response
     * @var string
     */
    protected $fullAPIResponse = null;

    /**
     *
     * @param string $key
     * @param string $secret
     */
    public function __construct($key, $secret)
    {
        $this->auth = array('key' => $key, 'secret' => $secret);

        // Enable curl debugging if set in the settings
        $settings = get_option( '_wpio_settings' );
        if(is_array($settings) && !empty($settings['wpio_debug_curl'])){
            add_action('http_api_curl', function (&$curl, $options, $url)  {
                $debug_file = dirname(__FILE__).'/../debug.php';

                if (!file_exists($debug_file)) {
                    file_put_contents($debug_file, "<?php die(); ?>\r\n");
                }
                $file = fopen($debug_file, 'a+');
                curl_setopt($curl, CURLOPT_VERBOSE, true);
                curl_setopt($curl, CURLOPT_STDERR, $file);
            }, 10, 3);
        }
    }

    /**
     * Change the API URL
     * @param string $url
     */
    public function setAPIUrl($url)
    {
        $this->apiUrl = $url;
    }

    /**
     * Upload a file by file url
     * @param string $file_url posted file
     * @param array $params
     */
    public function uploadFileByUrl($file_url, $params = array())
    {
        // Define auth and optimization parameters
        $parameters = array(
            'auth' => json_encode($this->auth),
            'params' => json_encode($params)
        );

        // Override the curl request to add our file to the POST request has HTTP API doesn't have this functionality
        add_action('http_api_curl', function (&$curl, $options, $url) use ($file_url, $parameters) {
            if ($url !== $this->apiUrl.'images/' && $options['method'] !== 'POST') {
                // In case this is not a IR call or not an upload one
                return;
            }

            $parameters['url'] = $file_url;

            // Finally add our auth and optimization parameters to the request
            curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        },10, 3);

        // Define some request default settings
        $args =  array(
            'blocking'    => true,
            'timeout'     => 300,
            'headers' => array(
                'Connection'  => 'keep-alive',
                'Expect'  => ''
            ),
            'body' => ''
        );


        return $this->callAPI($this->apiUrl . 'images/', 'POST', $args);
    }

    /**
     * Upload a file sent through an html post form
     * @param $_FILES $file posted file
     */
    public function uploadFile($file, $params = array())
    {
        // Define auth and optimization parameters
        $parameters = array(
            'auth' => json_encode($this->auth),
            'params' => json_encode($params)
        );

        // Override the curl request to add our file to the POST request has HTTP API doesn't have this functionality
        add_action('http_api_curl', function (&$curl, $options, $url) use ($file, $parameters) {
            if ($url !== $this->apiUrl.'images/' && $options['method'] !== 'POST') {
                // In case this is not a IR call or not an upload one
                return;
            }

            // Add the file to Curl depending on what allow the Curl version
            if (class_exists('CURLFile')) {
                $parameters['file'] = new CURLFile($file);
            } else if (function_exists('curl_version')) {
                $parameters['file'] = '@'.$file;
            }

            // Finally add our auth and optimization parameters to the request
            curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        },10, 3);

//        add_action( 'requests-curl.after_request', function ($headers, $info) {
//            error_log('headers:'.json_encode($headers) );
//            error_log('$info:'.json_encode($info) );
//
//        }, 10, 2 );

        // Define a boundary in case we don't use Curl for the requests
        $boundary = "=================".substr(md5(rand(0,32000)), 0, 10);
        $body = '';

        // Override the socket requests to add our own parameters as HTTP API doesn't support sending files
        add_action('requests-fsockopen.after_headers', function (&$out) use ($file, $parameters, $boundary, &$body) {
            // Set headers to send a file in the request
            $out .= "Content-Type: multipart/form-data; boundary=";
            $out .= "$boundary\r\n";

            // Add our auth and optimization parameters to the POST parameters
            foreach($parameters as $key => $val) {
                $body .= "--$boundary\r\n";
                $body .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n".$val."\r\n";
            }

            // Append the file content as a POST parameter
            $fileContents = file_get_contents($file);
            $filename = pathinfo($file, PATHINFO_BASENAME);
            $body .= "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"$filename\"\r\n";
            $body .= "Content-Transfer-Encoding: binary\r\n\r\n";
            $body .= $fileContents."\r\n";
            $body .= "--$boundary--\r\n";

            // Finally add the body length
            $out .= "Content-Length: ".strlen($body)."\r\n";

        }, 10, 1);

        add_action('requests-fsockopen.before_send', function (&$out) use ($file, &$body) {
            // Add our POST parameters to the request body
            $out .= $body;
        }, 10, 1);

        // Define some request default settings
        $args =  array(
            'blocking'    => true,
            'timeout'     => 300,
            'headers' => array(
                'Connection'  => 'keep-alive',
                'Expect'  => ''
            ),
            'body' => ''
        );


        return $this->callAPI($this->apiUrl . 'images/', 'POST', $args);
    }

    /**
     * Call the API with WP HTTP api
     *
     * @param string $url
     * @param string $type HTTP method
     * @param array $datas
     * @return boolean
     */
    protected function callAPI($url, $type = 'HEAD', $datas = array())
    {
        switch ($type) {
            case  'GET':
                $url = $url . '?' . http_build_query($datas);
                $response = wp_remote_get($url);
                break;
            case 'POST':
                $response = wp_remote_post($url, $datas);
                break;
            default:
                $response = wp_remote_head($url, $datas);
                break;
        }

        if (is_wp_error($response)) {
            $this->lastError = $response->get_error_message();
            $this->lastErrCode = $response->get_error_code();
            return false;
        }

        $result = json_decode(wp_remote_retrieve_body($response));
        $this->fullAPIResponse = $response;

        if (isset($result->errCode)) {
            $this->lastError = $result->errMessage;
            $this->lastErrCode = $result->errCode;
            return false;
        }

        return $result;
    }

    /**
     * Get one image
     * @param int $id
     * @return String|boolean
     */
    public function getImage($id)
    {
        $params = array(
            'auth' => json_encode($this->auth),
            'params' => ''
        );


        return $this->callAPI($this->apiUrl . 'images/' . (int)$id, 'GET', $params);
    }

    /**
     * Get account information
     *
     * @return String|boolean
     */
    public function getAccountInfos()
    {
        $params = array(
            'auth' => json_encode($this->auth),
            'params' => ''
        );

        return $this->callAPI($this->apiUrl . 'accounts/mine', 'GET', $params);
    }

    /**
     * Get last error message
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    public function getLastErrCode()
    {
        return $this->lastErrCode;
    }

    /**
     * Gets full CURL Response from IR server
     * @return string1
     */
    public function getFullAPIResponse()
    {
        return $this->fullAPIResponse;
    }
}

?>