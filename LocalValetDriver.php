<?php
/**
* Defines customizations for this site
* https://laravel.com/docs/5.5/valet#local-drivers
*/
class LocalValetDriver extends LaravelValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        // only route /quiz-embed/ and /ab-embed/ paths here
        if(strpos($uri, 'quiz-embed/') || strpos($uri, 'ab-embed/')) {
            return true;
        }

        return false;

    }

    /**
     * Get the fully resolved path to the application's front controller.
     * Actually... we're using this as our literal front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        // set our document root as the $sitePath
        $_SERVER["DOCUMENT_ROOT"] = $sitePath;
        // load the config
        require_once 'wp-content/enp-quiz-config.php';

        // see if it's a quiz embed or ab test
        if(strpos($uri, 'quiz-embed/')) {
            // get the number
            $quiz_id = str_replace('/quiz-embed/', '', $uri);
            $template = 'quiz';
        } else if (strpos($uri, 'ab-embed/')) {
            $ab_test_id = str_replace('/ab-embed/', '', $uri);
            $template = 'ab-test';
        }

        // render the file
        include $sitePath.'/wp-content/plugins/enp-quiz/public/quiz-take/templates/'.$template.'.php';

        // This is our literal frontController, so just return a blank index.php file and be done
        return $sitePath.'/wp-content/index.php';
    }
}
