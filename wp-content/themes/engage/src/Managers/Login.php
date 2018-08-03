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