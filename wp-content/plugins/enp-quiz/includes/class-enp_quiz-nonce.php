<?
//http://code.tutsplus.com/tutorials/secure-your-forms-with-form-keys--net-4753
//You can of course choose any name for your class or integrate it in something like a functions or base class
class Enp_quiz_Nonce {
    //Here we store the generated form key
    private $nonce;

    //Here we store the old form key (more info at step 4)
    private $old_nonce;

    // session name, if necessary, so we can differentiate multiple
    // forms from the same user
    private $session_name;

    //The constructor stores the form key (if one exists) in our class variable
    function __construct($session_name = '')
    {
        if($session_name === '') {
            // if it's empty, set one for the nonce
            $this->session_name = 'enp_quiz_nonce';
        } else {
            $this->session_name = $session_name;
        }

        //We need the previous key so we store it
        if(isset($_SESSION[$this->session_name]))
        {
            $this->old_nonce = $_SESSION[$this->session_name];
        }
    }

    //Function to generate the form key
    private function generateKey()
    {
        //Get the IP-address of the user
        $ip = $_SERVER['REMOTE_ADDR'];

        //We use mt_rand() instead of rand() because it is better for generating random numbers.
        //We use 'true' to get a longer string.
        //See http://www.php.net/mt_rand for a precise description of the function and more examples.
        $uniqid = uniqid(mt_rand(), true);

        //Return the hash
        return md5($ip . $uniqid);
    }


    //Function to output the form key
    public function outputKey()
    {
        //Generate the key and store it inside the class
        $this->nonce = $this->generateKey();
        //Store the form key in the session
        $_SESSION[$this->session_name] = $this->nonce;

        //Output the form key
        echo "<input type='hidden' name='".$this->session_name."' id='".$this->session_name."' value='".$this->nonce."' />";
    }


    //Function that validated the form key POST data
    public function validate($nonce = false) {
        if($nonce === false) {
            // no nonce, return false
            return false;
        }

        //We use the old nonce and not the new generated version
        if($nonce == $this->old_nonce)
        {
            //The key is valid, return true.
            return true;
        } else {
            // check if sessions are enabled. old_nonce will be null if cookies are disabled
            if($this->old_nonce == null) {
                return null;
            } else {
                //The key is invalid, return false.
                return false;
            }
        }
    }
}

?>
