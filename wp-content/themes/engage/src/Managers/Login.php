<?php
/*
 * Modifications to the login process
 */
namespace Engage\Managers;

class Login {

    public function __construct() {

    }

    public function run() {
        add_action( 'login_enqueue_scripts', [$this, 'loginLogo']);
        add_filter('login_headerurl', [$this, 'loginLogoURL']);
        add_action( 'login_enqueue_scripts', [$this, 'enqueueScript']);

        // redirects
        add_action('login_redirect', [$this, 'redirect_to_quiz_dashboard'], 10, 1);
        add_action('registration_redirect', [$this, 'redirect_to_quiz_dashboard'], 10, 1);
        add_action('template_redirect', [$this, 'redirect_to_quiz_dashboard_from_marketing']);

        // replace #enplogin# with our current state
        add_filter( 'wp_setup_nav_menu_item', [$this, 'enp_setup_nav_menu_item' ]);
    }


    // redirect to quiz creator dashboard on login
    public function redirect_to_quiz_dashboard($redirect_to) {

        if(ENP_QUIZ_DASHBOARD_URL) {
            $redirect_to = ENP_QUIZ_DASHBOARD_URL.'user';
        }
        return $redirect_to;

    }

    // redirect to quiz dashboard if logged in and trying to get to the quiz creator
    public function redirect_to_quiz_dashboard_from_marketing() {
        if(is_user_logged_in() === true && is_page('quiz-creator') && ENP_QUIZ_DASHBOARD_URL) {
            $redirect_to = ENP_QUIZ_DASHBOARD_URL.'user';
            wp_redirect($redirect_to);
            exit;
        }
    }


    /** 
     * The main code, this replace the #keyword# by the correct links with 
    * nonce ect
    */
    public function enp_setup_nav_menu_item( $item ) {
        global $pagenow;

        if ( $pagenow != 'nav-menus.php' && ! defined( 'DOING_AJAX' ) && isset( $item->url ) && strstr( $item->url, '#enp' ) != '' ) {
            $item_url = substr( $item->url, 0, strpos( $item->url, '#', 1 ) ) . '#';

            switch ( $item_url ) {

                case '#enplogin#' :     $item->url = is_user_logged_in() ? wp_logout_url() : wp_login_url();
                                        $item->title = is_user_logged_in() ? 'Log out' : 'Login';
                break;
                case '#enpquizcreator#' :   $item->url = is_user_logged_in() ? ENP_QUIZ_DASHBOARD_URL.'user' : site_url('quiz-creator');
                                            $item->title = 'Quiz Creator';

                break;

            }
            $item->url = esc_url( $item->url );
        }
        return $item;
    }

    /*
     * Customize Login Logo
     */
    public function loginLogo() { 
        ?>
        <style type="text/css">
            .login h1 a {
                background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/assets/img/cme-logo.png) !important;
                background-size: 315px !important;
                background-position: center center !important;
                width: 315px!important;
            }
        </style>
        <?php 
    }
    
    public function loginLogoURL() {
        return home_url();
    }

    /**
     * Add Google Analytics to Login page
     */
    function enqueueScript() {
      ?>

      <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

        ga('create', 'UA-52471115-4', 'auto');
        ga('send', 'pageview');
      </script>

      <?php
    }
    
}