<?php
/**
 * ImageRecycle pdf & image compression
 *
 * @package ImageRecycle pdf & image compression
 */
defined('ABSPATH') || die('No direct script access allowed!');

class wpImageRecycle {
    
    private $allowed_ext = array('jpg','jpeg','png','gif','pdf');
    private $allowedPath = array('wp-content/uploads','wp-content/themes');


    protected $totalImages = 0;
    protected $limit = 30;
    protected  $totalNotOptimizedImages = 0;


    public function __construct() {
        include_once 'ioa.class.php';

        //Get settings
        $this->settings = array(
            "wpio_api_include"=>"wp-content/uploads,wp-content/themes",
            "wpio_api_resize_auto"=>"0",
            "wpio_api_maxsize"=>"1600",
            "wpio_api_minfilesize"=>"0",
            "wpio_api_maxfilesize"=>"5120",
            "wpio_api_typepdf"=>"lossy",
            "wpio_api_typepng"=>"lossy",
            "wpio_api_typejpg"=>"lossy",
            "wpio_api_typegif"=>"lossy",
            "wpio_api_send_email"=>"1",
            "wpio_debug_curl" => "0",
            "wpio_api_optimization_status" => "1",
            "clean_metadata"=>"1",
            "preserve_metadata_datetime"=>"0",
            "preserve_metadata_location"=>"0",
            "preserve_metadata_copyright"=>"0",
            "preserve_metadata_orientation"=>"0",
            "preserve_metadata_color_profile"=>"0"

        );
        $settings = get_option( '_wpio_settings' );
        if(is_array($settings)){
            $this->settings = array_merge($this->settings, $settings);
        }

        //Add a widget to the dashboard.
        add_action( 'wp_dashboard_setup', array($this,'wpio_add_dashboard_widgets') );

        //Add column in media manager
        add_filter('manage_media_columns', array(&$this,'addMediaColumn'));

        //process files during upload
        add_filter('wp_generate_attachment_metadata', array(&$this,'generateMetadata'));
        add_filter('wp_handle_upload', array(&$this,'addFileToQueue'));  //for pdf file

        //Add content in column media manager
        add_action('manage_media_custom_column', array(&$this,'fillMediaColumn'), 10, 2 );

        add_action('admin_menu',array(&$this,'wpio_add_menu_page'));

        add_action('wp_ajax_wpio_optimize', array(&$this,'doActionOptimize'));
        add_action('wp_ajax_wpio_optimize_all', array(&$this,'doActionOptimizeAll'));
        add_action('wp_ajax_wpio_stop_optimize_all', array(&$this,'stopOptimizeAll'));
        add_action('wp_ajax_wpio_optimize_all_on', array(&$this,'optimizeAllOn'));
        add_action('wp_ajax_wpio_revert', array(&$this,'doActionRevert'));
        add_action('wp_ajax_wpio_enable_optimization', array(&$this,'enableOptimization'));
        add_action('wp_ajax_wpio_disable_optimization', array(&$this,'disableOptimization'));
        add_action('wp_ajax_wpio_queue_count', array($this, 'countItemsInQueue') );
        add_action('wp_ajax_wpio_unqueued', array($this, 'doActionUnqueued') );
        add_action('wp_ajax_wpio_dismiss_optimizeAll_notice', array($this, 'dismissOptimizeAll') );
        add_action('wp_ajax_wpio_dismiss_optimization_disabled_notice', array($this, 'dismissOptimizationDisabled') );
        add_action('wp_ajax_wpio_scan_images', array(&$this,'scanImages'));
        add_action('wp_ajax_wpio_reinitialize', array(&$this,'reinitialize'));
        add_action('wp_ajax_wpio_count_images', array(&$this,'countTotalImages'));

        $fail_counter = (int)get_option('wpio_optimize_fail_counter');
        $api_optimization_status = isset( $this->settings['wpio_api_optimization_status'] ) ? (int)$this->settings['wpio_api_optimization_status'] : 1;
        $wpio_optimization_disabled  = get_option( 'wpio_optimization_disabled_notice_dismissed', 0);
        if(!$api_optimization_status && ($fail_counter>=3) && empty($wpio_optimization_disabled) ) {
            add_action('admin_print_footer_scripts', array(&$this,'wpio_notice_dismissed_js'));
            add_action( 'admin_notices', array($this, 'wpio_optimization_disabled_notice') );
        }

        add_action('admin_enqueue_scripts', array(&$this,'addScriptUploadPage'));
        add_action('admin_init', array(&$this,'wpio_admin_init'));
        add_action('wp_ajax_wpio_createAccount', array(&$this,'saveNewAccountData'));
        add_action('admin_footer', array(&$this,'wpio_ajax_script'));      
        
        add_action( 'admin_enqueue_scripts', array(&$this,'wpio_heartbeat_enqueue'));
        add_filter( 'heartbeat_received',  array(&$this,'wpio_heartbeat_received'), 10, 2 );
        if(!defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON) {        
            add_filter( 'cron_schedules',   array(&$this,'wpio_add_short_schedule') ); 
            add_action( 'wpio_auto_optimize_hourly', array(&$this,'wpio_auto_optimize') );
        }
    }   
   
    function wpio_add_dashboard_widgets() {
        if( function_exists( 'wp_add_dashboard_widget' ) ) {
            wp_add_dashboard_widget(
                    'wpio_stats_widget',
                    __('ImageRecycle Statistics','wpio'),
                    array($this, 'wpio_stats_dashboard_widget')
            );
        }
    }
        
    /**
     * the function to output the contents of our Dashboard Widget.
     */
    function wpio_stats_dashboard_widget() {
        global $wpdb;

        $rows = array();
        $i = 0;
        $averageCompression = 0;
        $savedSpace = 0;
        $savedBandwidth= 0;
        $totalOptimizedFiles = 0;

        $q = "Select COUNT(id) as totalOptimizedFiles, SUM(`size_after`) as totalOptimized, SUM(`size_before`) as totalOriginal " .
             " From ". $wpdb->prefix. "wpio_images";
        $result = $wpdb->get_row($q,OBJECT );

        if($result) {
            $averageCompression = $result->totalOptimized > 0 ? round(( 1 -  ( $result->totalOptimized / $result->totalOriginal ) ) * 100, 2) : 0;
            $savedSpace = $result->totalOriginal - $result->totalOptimized ;
            $savedBandwidth = $savedSpace * 10000;
            $totalOptimizedFiles = $result->totalOptimizedFiles;
        }

        $rows[$i] = new stdClass;
        $rows[$i]->title = __('Average compression of your files','wpio');
        $rows[$i]->data = $averageCompression .'%';
        $i++;

        $rows[$i] = new stdClass;
        $rows[$i]->title = __('Saved disk space','wpio');
        $rows[$i]->data = WPIO_Helper::formatBytes($savedSpace,2);
        $i++;

        $rows[$i] = new stdClass;
        $rows[$i]->title = __('Bandwidth saved','wpio') ;
        $rows[$i]->data = WPIO_Helper::formatBytes($savedBandwidth,2);
        $i++;

        $rows[$i] = new stdClass;
        $rows[$i]->title = __('Total number of processed files','wpio');
        $rows[$i]->data = $totalOptimizedFiles;
        ?>
        <ul class="ir_stats">
        <?php
        for($j=0; $j<= $i; $j++) { ?>
            <li>
                <label><?php echo $rows[$j]->title;?></label>
                <span><?php echo $rows[$j]->data;?></span>
            </li>
        <?php
        } ?>
        </ul>

        <style>
        ul.ir_stats li label {
            display: inline-block;
            margin-right: 5px;
            min-width: 230px;
            color: #777;
        }
        </style>
        <?php
    }

    public static function update_db_check() {

        if ( get_option( 'wpio_db_version' ) != WPIO_IMAGERECYCLE_VERSION ) {
            if( get_option( 'wpio_db_version' ) === false) {
                //fix md5 issue in plugin version 2.1.1
                  global $wpdb;
                  $query = 'SELECT a.id, a.file FROM '.$wpdb->prefix. 'wpio_images a'  ;
                  $images = $wpdb->get_results($query);
                  if(!empty($images) ) {
                    $results = array();
                    foreach ($images as $image) {

                        $file = realpath(ABSPATH. $image->file);
                        $image->md5 = md5_file($file);
                        $results[] = $image;
                    }
                    wpImageRecycle::updateMd5Images($results);
                  }
            }
            wpImageRecycle::install();
        }
    }

    public static function install(){
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE `".$wpdb->prefix."wpio_images` (
           `id` int(11) NOT NULL AUTO_INCREMENT,
           `file` varchar(250) NOT NULL,
           `md5` varchar(32) NOT NULL,
           `api_id` int(11) NOT NULL,
           `size_before` int(11) NOT NULL,
           `size_after` int(11) NOT NULL,
           `date` datetime NOT NULL,
                   `expiration_date` datetime NOT NULL,
           PRIMARY KEY (`id`),
           UNIQUE KEY `file` (`file`)
        );";
        dbDelta( $sql );

        $sql = "CREATE TABLE `".$wpdb->prefix."wpio_listimages` (
           `id` int(11) NOT NULL AUTO_INCREMENT,
           `filename` varchar(250) NOT NULL,
           `filesize` int(11) NOT NULL,
           `filetype` varchar(5) NOT NULL,
           `modified` datetime NOT NULL,
            `md5` varchar(32) NOT NULL,
           PRIMARY KEY (`id`),
           UNIQUE KEY `filename` (`filename`)
        );";
        dbDelta( $sql );

         $sql = "CREATE TABLE `".$wpdb->prefix."wpio_queue` (
           `id` int(11) NOT NULL AUTO_INCREMENT,
           `file` varchar(255) NOT NULL,
           PRIMARY KEY (`id`)
        );";
        dbDelta( $sql );

        update_option( "wpio_db_version", WPIO_IMAGERECYCLE_VERSION );

        if(!defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON) {
            //Use wp_next_scheduled to check if the event is already scheduled
           $timestamp = wp_next_scheduled( 'wpio_auto_optimize_hourly' );
           if( $timestamp == false ){
             //Schedule the event for right now, then to repeat hourly using the hook 'wpio_auto_optimize_hourly'
              if(defined('WP_DEBUG') && WP_DEBUG) {
                  wp_schedule_event( time(), 'twomin', 'wpio_auto_optimize_hourly' );
              }else {
                  wp_schedule_event( time(), 'hourly', 'wpio_auto_optimize_hourly' );
              }
           }
        }
         // add option index
        add_option('wpio_indexation_auto',false,'','yes');

        // Redirect to setting page
        add_option('wpio_do_activation_redirect', true);

    }
    
    public function uninstall(){
     wp_clear_scheduled_hook( 'wpio_auto_optimize_hourly' );
    }
        
    function wpio_add_short_schedule($schedules ) {
        //for debuging
        if(defined('WP_DEBUG') && WP_DEBUG) {
         $schedules['twomin'] = array(
            'interval' => 2 * 60, //7 days * 24 hours * 60 minutes * 60 seconds
            'display' => __( 'Two Minutes', 'wpio' )
         );
        }
        return $schedules;
    }
    
    // Load the heartbeat JS
    function wpio_heartbeat_enqueue( $hook_suffix ) {
        // Make sure the JS part of the Heartbeat API is loaded.
        wp_enqueue_script( 'heartbeat' );
        add_action('admin_print_footer_scripts', array(&$this,'wpio_heartbeat_footer_js'));
    }
    // Inject our JS into the admin footer
    function wpio_heartbeat_footer_js() {
        global $pagenow;
    ?>
        <script>
        (function($){
            // Hook into the heartbeat-send
            $(document).on('heartbeat-send', function(e, data) {
                data['wpio_heartbeat'] = 'queue_process';
            });

            // Listen for the custom event "heartbeat-tick" on $(document).
            $(document).on( 'heartbeat-tick', function(e, data) {
                  // Only proceed if our EDD data is present
                if ( ! data['wpio_result'] )
                    return;
            });
        }(jQuery));
        </script>
     <?php
    }

    // Modify the data that goes back with the heartbeat-tick
    function wpio_heartbeat_received( $response, $data ) {

        // Make sure we only run our query if the edd_heartbeat key is present
        if( isset($data['wpio_heartbeat']) && $data['wpio_heartbeat'] == 'queue_process' ) {
            wp_remote_head(WPIO_IMAGERECYCLE_URL.'/queue_process.php');
            // Send back the number of timestamp
            $response['wpio-result'] = time();
        }
        return $response;
    }


    function wpio_auto_optimize() {
        $now =  time();
        $ao_lastRun = (int)get_option( 'wpio_ao_lastRun', 0 );
        if($now - $ao_lastRun < 60 ) {
            //$ao_running = true;
            $this->write_log('auto_optimize is already running');
            return true;
        }
        global $wpdb;
        $wpio_queue = new WPIO_queue(false);
        $count = $wpio_queue->dbCount(); $this->write_log('queue count:'.$count);
        while(!empty($count) ) {
            //get option without cache
            $row = $wpdb->get_row("SELECT option_value FROM $wpdb->options WHERE option_name = 'wpio_ao_status' LIMIT 1" );
            if ( is_object( $row ) ) {
                $runStatus = $row->option_value;
            }else {
                $runStatus = 0;
            }

            $this->write_log($runStatus);
            if(empty($runStatus)) {
                break ;
            }
                update_option( 'wpio_ao_lastRun', time() ); //need to update running status to avoid run multi queue processing
                $this->write_log('auto_optimize is continue running');
                $nextImg = $wpio_queue->getLastFile();
                $wpio_queue->saveChange($nextImg, false);
                if($nextImg) {
                    $this->write_log('start optimize file: ' .$nextImg);
                    $returned = $this->optimize(ABSPATH.$nextImg);
                    $this->write_log($returned);
                }
            $count = $wpio_queue->dbCount(); $this->write_log('queue count:'.$count);
            //$wpio_queue->loadImages();
        }

        update_option( 'wpio_ao_lastRun', null);
        $this->write_log('auto_optimize is complete');

        //send email to admin
        $opt_is_ao = (int)get_option( 'wpio_is_OptimizeAll', 0 );
        $isOptimizeAll = ($opt_is_ao && $count> 1)? true: false;
        $send_email = true;
        $settings = get_option( '_wpio_settings' );
        if(is_array($settings) && isset($settings['wpio_api_send_email']) ){
           $send_email = (int)$settings['wpio_api_send_email'];
        }
        if($isOptimizeAll && $send_email) {
            $to = get_bloginfo('admin_email');
            $subject =  __( 'Optimize process complete', 'wpio' ) ;
            $message =  __( '<div class="main-presentation" style="margin: 0px auto; max-width: 1200px; background-color:#f0f1f4;font-family: helvetica,arial,sans-serif;">
       <div class="main-textcontent" style="margin: 0px auto; min-height: 300px; border-left: 1px dotted #d2d3d5; border-right: 1px dotted #d2d3d5; width: 840px; background-color:#fff;border-top: 5px solid #544766;" cellspacing="0" cellpadding="0" align="center">
           <a href="https://www.imagerecycle.com/" target="_blank"> <img src="https://www.imagerecycle.com/images/Notification-mail/logo-image-recycle.png" alt="logo image recycle" width="500" height="84" class="CToWUd" style="display: block; outline: medium none; text-decoration: none; margin-left: auto; margin-right: auto; margin-top:15px;"> </a>
           <p style="background-color: #ffffff; color: #445566; font-family: helvetica,arial,sans-serif; font-size: 24px; line-height: 24px; padding-right: 10px; padding-left: 10px;" align="center"><strong>Congratulation, all WordPress media optimized.<br></strong></p>
           <p style="background-color: #ffffff; color: #445566; font-family: helvetica,arial,sans-serif; font-size: 14px; line-height: 22px; padding-left: 20px; padding-right: 20px; text-align: center;">
               You recently launch a global image/PDF optimization on your website.<br>This is just an automatic friendly message to let you know that the process has been completed with success.<br>Enjoy!</p>
           <p></p>
           <p><a style="width: 250px; float: right; background: #554766; font-size: 12px; line-height: 18px; text-align: center;  margin-right:4px;color: #fff;font-size: 14px;text-decoration: none; text-transform: uppercase; padding: 8px 20px; font-weight:bold;" href="https://www.imagerecycle.com" target="_blank">Visit ImageRecycle</a></p>', 'wpio' ) ;

            $headers = array('Content-Type: text/html; charset=UTF-8');
            if(!wp_mail( $to, $subject, $message, $headers) ) {
                  $this->write_log('mail can not sent');
            }else {
                $this->write_log('mail sent successfuly');
            }
        }

        update_option("wpio_is_OptimizeAll",0,false );
    }
    
    public function countItemsInQueue() {
        $wpio_queue = new WPIO_queue();
        $this->ajaxReponse(true,array('remainFiles'=> $wpio_queue->count()) );
    }
    
    public function doActionUnqueued() {
        update_option("wpio_ao_status",0,false );
        $file = stripslashes($_REQUEST['file']);
        $wpio_queue = new WPIO_queue();
        $wpio_queue->unqueue($file);
        update_option("wpio_ao_status",1,false );
        $this->ajaxReponse(true);
    }
    
    function dismissOptimizeAll() {
        global $current_user;
        $user_id = $current_user->ID;

        add_user_meta($user_id, 'wpio-optimizeall-notice-dismissed', 'true', true);
        $this->ajaxReponse(true);
    }
    
    function dismissOptimizationDisabled() {
        update_option( 'wpio_optimization_disabled_notice_dismissed', 1);
        $this->ajaxReponse(true);
    }
    
    function wpio_optimization_disabled_notice() {
    ?>
        <div class="notice error wpio_optimization_disabled_notice is-dismissible" >
            <p><?php _e( 'ImageRecycle automatic optimization has been disabled due to too many consecutive errors, please check your account <a href="https://www.imagerecycle.com/my-account/api-and-quota">https://www.imagerecycle.com/my-account/api-and-quota</a>', 'wpio' ); ?></p>
        </div>

    <?php
    }

     // Inject our JS into the admin footer
    function wpio_notice_dismissed_js() {
    ?>
        <script>
        (function($){
            $(document).on( 'click', '.wpio_optimization_disabled_notice .notice-dismiss', function() {
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'wpio_dismiss_optimization_disabled_notice'
                    }
                })
            });

        }(jQuery));
        </script>
     <?php
    }

    //Add menu link in the media section
    public function wpio_add_menu_page(){
        add_media_page( 'ImageRecycle', 'ImageRecycle', 'activate_plugins', 'wp-image-recycle-page', array(&$this,'showWPImageRecycleMainPage'));
        add_options_page('ImageRecycle', 'ImageRecycle', 'manage_options', 'option-image-recycle', array( $this, 'showWPImageRecycleSetting' ));
        add_submenu_page( null, 'Folder tree', 'Folder tree', 'manage_options', 'wpir-foldertree', array( $this, 'folderTree' ) );

    }
    
    public function wpio_admin_init(){

        load_plugin_textdomain( 'wpio', false,  plugin_basename( WPIO_IMAGERECYCLE) .DIRECTORY_SEPARATOR.'languages');
        register_setting('Image Recycle','_wpio_settings');
        $wpioSetting = new wpioSetting();
        add_settings_section('wp-image-recycle-page','',array( $wpioSetting, 'showSettings' ),'option-image-recycle');

        add_settings_field('wpio_api_optimization_status','<label class="wpio-tooltip" alt="'.__('Optimization process may be stopped after 3 unsuccessful connection to the server (the main reason is that there’s not optimization quota anymore on your account or sub account)', 'wpio').'">'
                            . __('Activate media optimization', 'wpio').' : </label>', array( $wpioSetting, 'showOptimizationStatus' ), 'option-image-recycle', 'wp-image-recycle-page');

        add_settings_field('wpio_api_key','<label class="wpio-tooltip" alt="'.__('The API key is like an ID, you can get one on ImageRecycle website. It will determine your optimization quota available.', 'wpio').'">'
                            . __('API Key : ', 'wpio').'</label>', array( $wpioSetting, 'showAPIKey' ), 'option-image-recycle', 'wp-image-recycle-page');

        add_settings_field('wpio_api_secret', '<label class="wpio-tooltip" alt="'.__('The API secret is like a password, you can get one on ImageRecycle website. It will determine your optimization quota available.', 'wpio').'">'
                            . __('API Secret : ', 'wpio'), array( $wpioSetting, 'showAPISecret' ), 'option-image-recycle', 'wp-image-recycle-page');

        add_settings_field('wpio_api_include', '<label class="wpio-tooltip" alt="'.__('Determine the content folders you want to optimize, by default it includes all your WordPress media and theme folders.', 'wpio').'">'
                .__('Include folders : ', 'wpio'), array( $wpioSetting, 'showIncludeFolder' ), 'option-image-recycle', 'wp-image-recycle-page');

        add_settings_field('wpio_api_resize_auto', '<label class="wpio-tooltip" alt="'.__('This parameter will activate the image resizing. So all original images will be resized (use with caution)', 'wpio').'">'
                .__('Image resize : ', 'wpio'), array( $wpioSetting, 'showImageResize' ), 'option-image-recycle', 'wp-image-recycle-page');

        add_settings_field('wpio_api_maxsize', '<label class="wpio-tooltip" alt="'.__('The width in pixels you will resize your images, if the parameter above is activated.', 'wpio').'">'
                . __('Image resize, max size (px) : ', 'wpio'), array( $wpioSetting, 'showmaxsize' ), 'option-image-recycle', 'wp-image-recycle-page');

        add_settings_field('wpio_api_minfilesize', '<label class="wpio-tooltip" alt="'.__('The minimum file size to start optimization ie. file size must be beyond this limit to be optimized', 'wpio').'">'
                .__('Min file size to optimize (Kb) : ', 'wpio'), array( $wpioSetting, 'showminfilesize' ), 'option-image-recycle', 'wp-image-recycle-page');

        add_settings_field('wpio_api_maxfilesize',  '<label class="wpio-tooltip" alt="'.__('The maximum file size to start optimization ie. file size must be over this limit to be optimized', 'wpio').'">'
                .__('Max file size to optimize (Kb) : ', 'wpio'), array( $wpioSetting, 'showmaxfilesize' ), 'option-image-recycle', 'wp-image-recycle-page');

        add_settings_field('wpio_api_typepdf', '<label class="wpio-tooltip" alt="'.__('The optimization quality to apply on PDF files', 'wpio').'">'
                . __('Compression type - PDF : ', 'wpio'), array( $wpioSetting, 'showtypepdf' ), 'option-image-recycle', 'wp-image-recycle-page');

        add_settings_field('wpio_api_typepng', '<label class="wpio-tooltip" alt="'.__('The optimization quality to apply on PNG files', 'wpio').'">'
                . __('Compression type - PNG : ', 'wpio'), array( $wpioSetting, 'showtypepng' ), 'option-image-recycle', 'wp-image-recycle-page');

        add_settings_field('wpio_api_typejpg', '<label class="wpio-tooltip" alt="'.__('The optimization quality to apply on JPG files', 'wpio').'">'
                . __('Compression type - JPG : ', 'wpio'), array( $wpioSetting, 'showtypejpg' ), 'option-image-recycle', 'wp-image-recycle-page');

        add_settings_field('wpio_api_typegif',  '<label class="wpio-tooltip" alt="'.__('The optimization quality to apply on GIF files', 'wpio').'">'
                . __('Compression type - GIF : ', 'wpio'), array( $wpioSetting, 'showtypegif' ), 'option-image-recycle', 'wp-image-recycle-page');
        add_settings_field('clean_metadata', '<label class="wpio-tooltip" alt="'.__('Remove/Preserve specific metadata, exifs, from images', 'wpio').'">'
                .__('Clean metadata : ', 'wpio'), array( $wpioSetting, 'showCleanMetadata' ), 'option-image-recycle', 'wp-image-recycle-page');
         add_settings_field('preserve_meta', '<label class="wpio-tooltip" alt="'.__('', 'wpio').'">'
                .__('', 'wpio'), array( $wpioSetting, 'showPreserveMetadata' ), 'option-image-recycle', 'wp-image-recycle-page');
        add_settings_field('wpio_api_send_email', '<label class="wpio-tooltip" alt="'.__('The system will send an email to admins when the background optimization is finished', 'wpio').'">'
                .__('Send email : ', 'wpio'), array( $wpioSetting, 'showSendEmail' ), 'option-image-recycle', 'wp-image-recycle-page');
        add_settings_field('wpio_debug_curl', '<label class="wpio-tooltip" alt="'.__('A debug file will be created in the plugin folder with curl debug informations', 'wpio').'">'
                .__('Debug curl requests : ', 'wpio'), array( $wpioSetting, 'showDebugCurl' ), 'option-image-recycle', 'wp-image-recycle-page');

        // Redirect to setting after activate
        if (get_option('wpio_do_activation_redirect', false)) {
            delete_option('wpio_do_activation_redirect');
            exit( wp_redirect(admin_url("options-general.php?page=option-image-recycle")) );
        }
    }
    
    public function folderTree() {
       /* Do nothing */
    }
           
    public function showWPImageRecycleSetting() {
         $wpioSetting = new wpioSetting();
         $wpioSetting->display();
    }
      
    public function showWPImageRecycleMainPage(){
    //Proceed actions if needed
    wp_enqueue_script('wp-image-optimizer',plugins_url('js/script.js',dirname(__FILE__)),array(),WPIO_IMAGERECYCLE_VERSION );
    wp_enqueue_style('wp-image-optimizer',plugins_url('css/style.css',dirname(__FILE__)),array(),WPIO_IMAGERECYCLE_VERSION);
        //reset list fail files in session
        if(isset($_SESSION['wpir_failFiles']) ) {
            $_SESSION['wpir_failFiles']= array();
        }
        if(isset($_SESSION['wpir_processed']) ) {
            $_SESSION['wpir_processed'] = 0;
        }

        $filters = array();
        $filters['optimized'] = isset($_GET['optimized']) ? $_GET['optimized'] : "";
        $filters['filetype']  = isset($_GET['filetype']) ? $_GET['filetype'] : "";
        $filters['s']         = isset($_GET['s']) ? stripslashes($_GET['s']) : "";
        $filters['m']         = isset($_GET['m']) ? (int) $_GET['m'] : 0;
        $images = $this->getLocalImages($filters);
        $images = $this->prepareLocalImages($images);

        if( empty($this->settings['wpio_api_key']) || empty($this->settings['wpio_api_secret']) ) {
            include_once WPIO_IMAGERECYCLE .'class/pages/wpio-dashboard.php';
        }else{
        echo '<h1>ImageRecycle - images and pdf compression</h1>';
        if(isset($_GET['iomess']) && $_GET['iomess']==='accountCreated'){
        echo '<div class="updated notice notice-success is-dismissible below-h2"><p>Your account has been created and your API key and secret automatically filled.  You\'re ready to optimize your images.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dissmiss.</span></button></div>';
        }
            /*filter & search */
$selectedAtr =   ' selected="selected"';

$allowed_ext = array();
        for($i=0;$i<count($this->allowed_ext); $i++) {
            $compression_type = isset($this->settings['wpio_api_type'.$this->allowed_ext[$i]])? $this->settings['wpio_api_type'.$this->allowed_ext[$i]] : "none" ;
            if($compression_type!="none") {
                $allowed_ext[] = $this->allowed_ext[$i];
            }
        }
        if(in_array('jpg', $allowed_ext ) ) { $allowed_ext[] = 'jpeg' ; }

$table = new WPIOTable();
            ?>
<div class="wrap">
<form id="wpio-filter" method="get"  autocomplete="off">
    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ;?>" />
    <div class="wpio-filter">
        <div class="filter-items">
            <label for="optimize-filter" class="screen-reader-text"><?php _e('Filter by optimized','wpio'); ?></label>
            <select class="optimize-filters" name="optimized" id="optimize-filter">
        <option value="" ><?php _e('All','wpio'); ?></option>
                <option value="no" <?php if($filters['optimized']=='no') echo $selectedAtr;?> ><?php _e('Not optimized','wpio'); ?></option>
                <option value="yes" <?php if($filters['optimized']=='yes') echo $selectedAtr;?>><?php _e('Optimized','wpio'); ?></option>
            </select>

            <label for="filetype-filter" class="screen-reader-text"><?php _e('Filter by type','wpio'); ?></label>
            <select class="filetype-filters" name="filetype" id="filetype-filter">
        <option value="" ><?php _e('All files','wpio'); ?></option>
                <?php if(in_array("png", $allowed_ext) ): ?>
                <option value="png" <?php if($filters['filetype']=='png') echo $selectedAtr;?> ><?php _e('PNG files','wpio'); ?></option>
                <?php endif ?>
                <?php if(in_array("jpg", $allowed_ext) ): ?>
                <option value="jpg" <?php if($filters['filetype']=='jpg') echo $selectedAtr;?> ><?php _e('JPG files','wpio'); ?></option>
                <?php endif ?>
                <?php if(in_array("gif", $allowed_ext) ): ?>
                <option value="gif" <?php if($filters['filetype']=='gif') echo $selectedAtr;?> ><?php _e('GIF files','wpio'); ?></option>
                <?php endif ?>
                <?php if(in_array("pdf", $allowed_ext) ): ?>
                <option value="pdf" <?php if($filters['filetype']=='pdf') echo $selectedAtr;?> ><?php _e('PDF files','wpio'); ?></option>
                <?php endif ?>
            </select>
            <?php $table->months_dropdown_listimages( 'attachment' ); ?>
            <div class="actions">
                <input name="filter_action" id="post-query-submit" class="button" value="<?php _e('Filter','wpio'); ?>" type="submit">
            </div>
            <div class="actions">
                <input id="wpio_scan_images" style="margin-left: -7px;position: relative; float: left;" class="button action wpio_scan_images" type="button" value="<?php _e('Index images','wpio')  ?>">
            </div>
        </div>
        <div class="search-form">
        <label for="media-search-input" class="screen-reader-text"><?php _e('Search','wpio'); ?></label>
        <input placeholder="<?php _e('Search','wpio'); ?>" id="media-search-input" class="search" name="s" value="<?php echo $filters['s'];?>" type="search">
        </div>
    </div>
</form>
<?php
    $api_optimization_status = isset( $this->settings['wpio_api_optimization_status'] ) ? (int)$this->settings['wpio_api_optimization_status'] : 1;
    if(!$api_optimization_status) {
        echo '<div class="notice notice-warning" style="margin-left:0; padding: 5px 10px">'. __('Optimization is disabled, you can enable it in the plugin config.','wpio').'</div>' ;
    }
?>

<?php
        $table = new WPIOTable();
        $table->setColumns(array( 'cb' => '<input type="checkbox" />', 'thumbnail'=> __('Image','wpio') ,'filename'=>__('Filename','wpio'),'size'=>__('Size (Kb)','wpio'),'status'=>__('Compression','wpio'),'actions'=>__('Actions','wpio')));
        $table->setItems($images, $this->totalImages);
        $table->display();
        if($this->totalNotOptimizedImages==0) $this->totalNotOptimizedImages = 1; //avoid divide zero
        $progressVal = floor(0 / $this->totalNotOptimizedImages);
        if($progressVal>100) $progressVal =100;
        $pressMsg = sprintf("Processing ... %s / %s images", 0, $this->totalNotOptimizedImages);
        ?>
        <div id="progress_init" style="display: none">
            <progress value="<?php echo $progressVal;?>" max="100"></progress><span><?php echo $pressMsg;?></span>
            <p class="timeRemain"></p>
        </div>
        <?php
    }
echo '</div>';  //close div for wrap

        $api_optimization_status = isset( $this->settings['wpio_api_optimization_status'] ) ? (int)$this->settings['wpio_api_optimization_status'] : 1;
        if(!$api_optimization_status) { ?>
            <script>
                wpio_disable_optimization = true;
            </script>
        <?php
        }
        global $current_user ;
        $user_id = $current_user->ID;
        /* Check that the user hasn't already clicked to ignore the message */
        if ( get_user_meta($user_id, 'wpio-optimizeall-notice-dismissed') ) { ?>

          <script type="text/javascript">
              wpio_dismiss_optimizeAll = 1;
          </script>
        <?php
        }
    }
    /**
     * This function adds the jQuery script to the plugin's page footer
     */
    function wpio_ajax_script() {

            $screen = get_current_screen();
            if ( 'media_page_wp-image-recycle-page' != $screen->id )
                    return false;
    ?>
    <script type="text/javascript">

    jQuery(document).ready(function($){
        // Show time!
        wpiolist.init();
    })
    </script>
    <?php
    }
    
    public function getImagesforWPIOTable() {
        $filters = array();
        $filters['optimized'] = isset($_GET['optimized']) ? $_GET['optimized'] : "";
        $filters['filetype']  = isset($_GET['filetype']) ? $_GET['filetype'] : "";
        $filters['s']         = isset($_GET['s']) ? stripslashes($_GET['s']): "";
        $filters['m']         = isset($_GET['m']) ? (int) $_GET['m'] : 0;
        $images = $this->getLocalImages($filters);
        $images = $this->prepareLocalImages($images);

        return $images;
    }

    public function countTotalImages() {
        require_once 'filesystem.php';
        $data = array();

        $allowed_ext = array();
        for($i=0;$i<count($this->allowed_ext); $i++) {
            $compression_type = isset($this->settings['wpio_api_type'.$this->allowed_ext[$i]])? $this->settings['wpio_api_type'.$this->allowed_ext[$i]] : "none" ;
            if($compression_type!="none") {
                $allowed_ext[] = $this->allowed_ext[$i];
            }
        }
        if(in_array('jpg', $allowed_ext ) ) { $allowed_ext[] = 'jpeg' ; }
        $this->allowed_ext = array_values($this->allowed_ext);
        $min_size = (int)$this->settings['wpio_api_minfilesize'] *1024;
        $max_size = (int)$this->settings['wpio_api_maxfilesize'] *1024;
        if($max_size==0) $max_size = 5 * 1024 * 1024;

        $include_folders = isset( $this->settings['wpio_api_include'] ) ? $this->settings['wpio_api_include'] : 'wp-content/uploads,wp-content/themes';
        $allowedPath = explode(',',$include_folders);

        $total = 0;
        foreach ($allowedPath as $cur_dir) {
            $scan_dir = str_replace('/', DIRECTORY_SEPARATOR, ABSPATH.$cur_dir) ;
            $files = \ImageRecycle\Standalone\Filesystem::listFiles($scan_dir,$allowed_ext,IR_ITERATOR_ONLYFILE);
            $total += count($files);
        }

        $data['total'] = $total;

        //truncate table wp_wpio_listimages before re-index
        global $wpdb;
        $query = "TRUNCATE TABLE " . $wpdb->prefix . "wpio_listimages";
        if($wpdb->query($query) === false) {
            $wpdb->print_error();
            die();
        }

        $this->ajaxReponse(true, $data);
    }

    public function scanImages() {
        require_once 'filesystem.php';
        $data = array();
        $default_limit = 500;
        $allowed_ext = array();
        for($i=0;$i<count($this->allowed_ext); $i++) {
            $compression_type = isset($this->settings['wpio_api_type'.$this->allowed_ext[$i]])? $this->settings['wpio_api_type'.$this->allowed_ext[$i]] : "none" ;
            if($compression_type!="none") {
                $allowed_ext[] = $this->allowed_ext[$i];
            }
        }
        if(in_array('jpg', $allowed_ext ) ) { $allowed_ext[] = 'jpeg' ; }
        $this->allowed_ext = array_values($this->allowed_ext);
        $min_size = (int)$this->settings['wpio_api_minfilesize'] *1024;
        $max_size = (int)$this->settings['wpio_api_maxfilesize'] *1024;
        if($max_size==0) $max_size = 5 * 1024 * 1024;

        $include_folders = isset( $this->settings['wpio_api_include'] ) ? $this->settings['wpio_api_include'] : 'wp-content/uploads,wp-content/themes';
        $allowedPath = explode(',',$include_folders);
        $results = array();
        $last_folder = "";
        $fileIndex = 0;
        $datas =  $_POST['datas'] ;
        if(!empty($datas) && is_array($datas)) {
             $last_folder = $datas['folder'];
             $fileIndex =  intval($datas['fileIndex']);
        }

        $continue = false;
        if(empty($last_folder)) { $continue = true;  $fi= 0; }
        foreach ($allowedPath as $cur_dir) {
            $fi = 0;
            $limit = $default_limit + $fileIndex;
            $scan_dir = str_replace('/', DIRECTORY_SEPARATOR, ABSPATH.$cur_dir) ;
            if($cur_dir== $last_folder || $scan_dir== $last_folder) {
                $continue = true;
            }

            if($continue) {

                $files = \ImageRecycle\Standalone\Filesystem::listFiles($scan_dir,$allowed_ext,IR_ITERATOR_ONLYFILE);
                foreach ($files as $filename) {
                    $filename = $scan_dir.$filename;
                    if(filesize($filename) < $min_size || filesize($filename) > $max_size) {
                        continue;
                    }

                    if($fi >= $fileIndex) {
                        $fileData =  array();
                        $fileData['filename'] = DIRECTORY_SEPARATOR.substr($filename, strlen(ABSPATH));
                        $fileData['size'] = filesize($filename);
                        $fileData['filetype'] = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
                        $fileData['modified'] = gmdate("Y-m-d H:i:s", filemtime($filename) ) ;
                        $fileData['md5']  = md5_file($filename);

                        $results[] = $fileData;
                        $fileIndex = 0;

                        if($fi>=$limit) {

                            $this->addImagesToDb($results);
                            $data['continue'] = $continue;
                            $data['folder'] = $cur_dir;
                            $data['fileIndex'] = $fi;
                            $data['processedImages'] = count($results);
                            $this->ajaxReponse(true, $data);
                            break;
                        }
                    }

                    $fi++;
                }

            }

        }  //end of loop
        $continue = false;
        // update indexation auto to true
        update_option('wpio_indexation_auto',true,'');
        $this->addImagesToDb($results);
        //$this->cleanDuplicateImages();

        $data['continue'] = $continue;
        $data['folder'] = $cur_dir;
        $data['fileIndex'] = $fi;
        $data['processedImages'] = count($results);
        $this->ajaxReponse(true, $data);
    }

    static function updateMd5Images($results) {

        if(empty($results)) return;
        global $wpdb;
        $query = "INSERT IGNORE INTO " . $wpdb->prefix . "wpio_images (id, file, md5) VALUES ";

        $place_holders=  array();
        $values  = array();
        for($i=0;$i< count($results);$i++) {
            $place_holders[] = "('%d', '%s', '%s')";
            array_push($values, $results[$i]->id,$results[$i]->file,$results[$i]->md5);
        }
        $query .= implode(', ', $place_holders);
        $query .= " ON DUPLICATE KEY UPDATE `file` = VALUES(`file`), `md5` = VALUES(`md5`) ";

        if($wpdb->query($wpdb->prepare($query, $values)) === false) {
            $wpdb->print_error();
            die();
        }

    }

    function addImagesToDb($results) {
        if(empty($results)) return;
        global $wpdb;
        $query = "INSERT IGNORE INTO " . $wpdb->prefix . "wpio_listimages (filename, filesize, filetype, modified, md5) VALUES ";

        $place_holders=  array();
        $values  = array();
        for($i=0;$i< count($results);$i++) {
            $place_holders[] = "('%s', '%d', '%s', '%s','%s')";
            array_push($values, $results[$i]['filename'],$results[$i]['size'],$results[$i]['filetype'], $results[$i]['modified'],$results[$i]['md5']);
        }
        $query .= implode(', ', $place_holders);
        $query .= " ON DUPLICATE KEY UPDATE `modified` = VALUES(modified), `md5` = VALUES(`md5`), `filesize` = VALUES(`filesize`) ";

        if($wpdb->query($wpdb->prepare($query, $values)) === false) {
            $wpdb->print_error();
            die();
        }

    }
    
    function cleanDuplicateImages() {
        global $wpdb;
        $query = "DELETE n1 From " . $wpdb->prefix . "wpio_listimages n1, ".$wpdb->prefix . "wpio_listimages n2 WHERE n1.filename = n2.filename AND n1.id < n2.id  " ;
        if($wpdb->query($query) === false) {
            $wpdb->print_error();
            die();
        }

        return true;
    }

    function reinitialize() {
        $data = array();
        global $wpdb;

        $query = 'TRUNCATE TABLE '.$wpdb->prefix. 'wpio_queue';
        if($wpdb->query($query) === false) {
            $wpdb->print_error();
            die();
        }

        $query = "TRUNCATE TABLE " . $wpdb->prefix . "wpio_listimages";
        if($wpdb->query($query) === false) {
            $wpdb->print_error();
            die();
        }

        $query = "TRUNCATE TABLE " . $wpdb->prefix . "wpio_images";
        if($wpdb->query($query) === false) {
            $wpdb->print_error();
            die();
        }

        $this->ajaxReponse(true, $data);
    }

    protected function getLocalImages($filters = array()){
        global $wpdb;
        $count_query = 'SELECT COUNT(a.id) FROM '.$wpdb->prefix. 'wpio_listimages a LEFT JOIN '.$wpdb->prefix. 'wpio_images b ON a.filename=b.file';
        if(isset($filters['onlyfile']) && $filters['onlyfile'] ) {
             $query = 'SELECT a.filename
              FROM '.$wpdb->prefix. 'wpio_listimages a LEFT JOIN '.$wpdb->prefix. 'wpio_images b ON a.filename=b.file'  ;
        }else {
             $query = 'SELECT a.id, a.filename, a.filesize, a.filetype, b.md5,
                  (b.id>0) as optimized, b.expiration_date as expiration_date, b.size_before,((b.size_before - b.size_after)/(b.size_before) * 100) AS percentage
              FROM '.$wpdb->prefix. 'wpio_listimages a LEFT JOIN '.$wpdb->prefix. 'wpio_images b ON a.filename=b.file'  ;
        }

        $where = array();
        $values = array();
        if(isset($filters['filetype']) && $filters['filetype']) {
            $where[] = " a.filetype= %s";
            $values[] = $filters['filetype'];
        }
        $s = "";
        if(isset($filters['s']) && $filters['s']) {
            $s = strtolower($filters['s'] );
            $where[] = " a.filename LIKE %s";
            $values[] =  '%'.$s .'%' ;
        }

        if(isset($filters['optimized']) && $filters['optimized']) {
            $optimizedOnly = ($filters['optimized'] == 'yes')? true: false;
            if($optimizedOnly) {
                $where[] = " (b.id > 0 AND a.md5 = b.md5) " ;
            }else {
                $where[] = " (b.id is NULL OR a.md5 != b.md5) " ;
            }

        }else {
            $filter_optimized = false;
        }

        if(isset($filters['m']) && $filters['m']>0 ) {
            $filter_date = true;
            $year =  (int)substr($filters['m'],0,4);
            $month = (int)substr($filters['m'],4);
            $firstday = strtotime($year."-". $month. "-1");
            $lastday  = strtotime(date("Y-m-t", $firstday));
            $where[] = " a.modified >= '". gmdate("Y-m-d H:i:s", $firstday) ."'";
            $where[] = " a.modified <= '". gmdate("Y-m-d H:i:s", $lastday) ."'" ;

        }else {
            $filter_date = false;
        }

        if(count($where)) {
            $query .= " WHERE ". implode(" AND ", $where);
            $count_query .= " WHERE ". implode(" AND ", $where);
            // add prepare
            if(count($values)>0) {
                $count_query = $wpdb->prepare($count_query,$values);
            }

        }else {
            $count_query = 'SELECT COUNT(a.id) FROM '.$wpdb->prefix. 'wpio_listimages a';
        }
        if(isset($filters['onlyfile']) && $filters['onlyfile'] ) {
            //do nothing
        }else {
            $this->totalImages = $wpdb->get_var($count_query);

            $count_query2 = 'SELECT COUNT(a.id) FROM '.$wpdb->prefix. 'wpio_listimages a LEFT JOIN '.$wpdb->prefix. 'wpio_images b ON a.filename=b.file';
            $count_query2 .= " WHERE  b.id is NULL OR a.md5 != b.md5 " ;
            $this->totalNotOptimizedImages = $wpdb->get_var($count_query2);
        }
        // If no sort, default to title
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'filename';
        // If no order, default to asc
        $orderdir = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
        $sortorder = array('asc', 'ASC',  'desc','DESC');
        if(!in_array($orderdir,$sortorder )) {
            $orderdir = 'asc';
        }

        // Determine sort order
        switch ($orderby) {
            case 'size':
                $query .= " Order by a.filesize ". $orderdir;
                break;
            case 'optimized':
                $query .= " Order by percentage ". $orderdir.", a.filename";

                break;
            default:
                $query .= " Order by a.filename ". $orderdir;

                break;
        }


        if(isset($_GET['paged'])){
            $paged = (int)$_GET['paged'];
        }else{
            $paged = 1;
        }
        if(!isset($filters['no_limit']) ) {

            $limit = (isset($filters['limit']) )? (int)$filters['limit'] :  $this->limit;
            $limitStart = ($paged-1)* $limit;
            $query .=" Limit ".$limitStart. ", ". $limit;
        }
        // add prepare
        if(count($values)>0) {
            $query = $wpdb->prepare($query,$values);
        }
        if(isset($filters['onlyfile']) && $filters['onlyfile'] ) {
            $images = $wpdb->get_col($query);
        }else {
            $images = $wpdb->get_results($query, ARRAY_A);
        }


        return $images;
    }

    public function getTotalImages() {
        return $this->totalImages;
    }

    function usort_reorder( $a, $b ) {
        // If no sort, default to title
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'filename';
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';

        // Determine sort order
        switch ($orderby) {
            case 'size':
                $result = $a[$orderby] - $b[$orderby];
                break;
             case 'optimized':
                 if($a[$orderby] > $b[$orderby]) {
                     $result = 1;
                 }elseif($a[$orderby] == $b[$orderby]) {
                     if( $order === 'asc') {
                        $result = strcmp( $a['filename'], $b['filename'] );
                     }else {
                        $result = strcmp( $b['filename'], $a['filename'] );
                     }
                 }else {
                     $result = -1;
                 }
                break;
            default:
                $result = strcmp( $a[$orderby], $b[$orderby] );
                break;
        }

        // Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
    }
    
    protected function prepareLocalImages($images){
        $wpio_queue = new WPIO_queue();
        $preparedImages = array();
        $now = time();
        foreach ($images as $image){
            $data = array();
            //check file exists
            if (file_exists(ABSPATH.$image['filename'])){
                $data['filename'] = $image['filename'];
                $data['filetype'] = $image['filetype'];
                $data['size'] = number_format(filesize(ABSPATH.$image['filename'])/1000, 2, '.', '') ;
                $file = realpath(ABSPATH.$image['filename']);
                if(md5_file($file) != $image['md5']) {
                    $image['optimized'] = false;
                }
                if( !empty($image['optimized']) ){

                    $progressVal = round(($image['size_before'] -filesize(ABSPATH.$image['filename']))/$image['size_before']*100,2);
                    $data['status'] = '<div class="wpio-progress-wrap" style="float: left">
                        <div class="wpio-progress-bar" style="width: '. $progressVal.'%;min-width:0.01%"></div></div><span class="optimizationStatus">'.$progressVal.'%</span> <span class="spinner"></span>';
                    $expirationTime = strtotime($image['expiration_date']);

                    if($expirationTime < $now) {
                        $data['expired'] = true;
                        $data['actions'] =  '';
                    }else {
                        $data['actions'] = '<a class="button ioa-proceed" data-action="wpio_revert" data-file="'.$image['filename'].'">Revert to original</a>';
                    }

                }else{
                    if($wpio_queue->isFilePresent($image['filename']) ) {
                        $data['actions'] = '<a class="button button-primary ioa-queued" data-action="wpio_unqueued" data-file="'.$image['filename'].'">Queued</a>';
                    }else {
                        $data['status'] = '<span class="spinner"></span><span class="optimizationStatus"></span>';
                        $data['actions'] = '<a class="button button-primary ioa-proceed" data-action="wpio_optimize" data-file="'.$image['filename'].'">Optimize</a>';
                    }
                }
                $preparedImages[] = $data;
            }else{
                  $this->deleteFileExists($image['filename']);
            }

        }
        return $preparedImages;
    }
    //delete file name not exists
    public function deleteFileExists($filename){
        global $wpdb;
        try{
            $query1 = $wpdb->prepare('DELETE FROM '.$wpdb->prefix.'wpio_images WHERE file=%s',$filename);
            $wpdb->query($query1);
            $query2 = $wpdb->prepare('DELETE FROM '.$wpdb->prefix.'wpio_listimages WHERE filename=%s',$filename);
            $wpdb->query($query2);
            $query3 = $wpdb->prepare('DELETE FROM '.$wpdb->prefix.'wpio_queue WHERE file=%s',$filename);
            $wpdb->query($query3);
        }catch (Exception $e){
            $this->write_log($e);
        }
    }

    public function addMediaColumn( $columns ) {
    $columns['wp-image-recycle'] = __('ImageRecycle','wpio');
    return $columns;
    }
    
    public function fillMediaColumn( $column_name, $id ) {
    $allowed_ext = array('jpg','jpeg','png','gif','pdf');
    switch ( $column_name ) {
        case 'wp-image-recycle' :
        global $wpdb;
        $relativePath = '/wp-content/uploads/' ;
        $meta = wp_get_attachment_metadata( $id );
        if(isset($meta['file'])) {
            $relativePath .= $meta['file'] ;
        } else {
            $file_path = get_post_meta( $id, '_wp_attached_file', true );
            if ($file_path) {
                 $relativePath .= $file_path ;
            }
        }
        $query = $wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'wpio_images WHERE file=%s',$relativePath);
        $row = $wpdb->get_row($query, OBJECT);

        $allowed_ext = array();
        for($i=0;$i<count($this->allowed_ext); $i++) {
            $compression_type = isset($this->settings['wpio_api_type'.$this->allowed_ext[$i]])? $this->settings['wpio_api_type'.$this->allowed_ext[$i]] : "none" ;
            if($compression_type!="none") {
                $allowed_ext[] = $this->allowed_ext[$i];
            }
        }
        if(in_array('jpg', $allowed_ext ) ) { $allowed_ext[] = 'jpeg' ; }

        $data = '<span class="optimizationStatus"></span><br/><a class="button button-primary ioa-proceed" data-action="wpio_optimize" data-file="'. $relativePath .'"><span class="spinner"></span>'. __('Optimize','wpio').'</a>';
        $url_file = wp_get_attachment_url($id);
        $ext = pathinfo($url_file, PATHINFO_EXTENSION);
        if(!$row){
            if(in_array($ext , $allowed_ext)){
                echo $data;
            }
        }else{
            if(!in_array($ext,$allowed_ext)){
            }else{
                echo '<span class="optimizationStatus">Optimized at '.round(($row->size_before-$row->size_after)/$row->size_before*100,2).'%</span><br/>';
                echo  '<a class="button ioa-proceed" data-action="wpio_revert" data-file="'.$row->file.'"><span class="spinner"></span>'. __('Revert to original','wpio').'</a>';
            }
        }
        break;
    }
    }
    
    function addScriptUploadPage($page) {

        if ( $page === 'settings_page_option-image-recycle' ) {
            wp_enqueue_script('wpio-qtip',plugins_url('js/jquery.qtip.min.js',dirname(__FILE__)) , array('jquery'), WPIO_IMAGERECYCLE_VERSION, true );
            wp_enqueue_style('wpio-qtip',plugins_url('/css/jquery.qtip.css',dirname(__FILE__)) );
            wp_enqueue_style('wpio-setting',plugins_url('/css/setting.css',dirname(__FILE__)) );
        }

        if ( $page === 'settings_page_option-image-recycle' || $page === 'upload.php') {
        wp_enqueue_script('wp-image-optimizer',plugins_url('js/script.js',dirname(__FILE__)), array('jquery'), WPIO_IMAGERECYCLE_VERSION, true );
    }

    }
    
    protected function optimize($file)
    {
    //Optimization action
    global $wpdb;
    $response = new stdClass();
        $response->status = false;
        $response->errCode = 0;
        $response->msg =  __('Not yet optimized','wpio') ;

        $api_optimization_status = isset( $this->settings['wpio_api_optimization_status'] ) ? (int)$this->settings['wpio_api_optimization_status'] : 1;
        if(!$api_optimization_status) {
             $response->msg =  __('Optimization is disabled, you can enable it in the plugin config','wpio') ;
            return $response;
        }

    $file = realpath($file);
    $relativePath = DIRECTORY_SEPARATOR.substr($file,strlen(ABSPATH));
    if ($file === false || strpos($file, str_replace("/", DIRECTORY_SEPARATOR, ABSPATH)) !== 0) {
                $response->msg =  __('File not found','wpio') ;
        return $response;
    }
    if (!in_array(strtolower(pathinfo($file,PATHINFO_EXTENSION)), $this->allowed_ext)) {
        $response->msg =  __('This file type is not allowed','wpio') ;
        return $response;
    }
    if (!file_exists($file) || filesize($file) === 0) {
        $response->msg =  __('File not found','wpio') ;
        return $response;
    }

    $query = $wpdb->prepare('SELECT id FROM '.$wpdb->prefix.'wpio_images WHERE file=%s',$relativePath);
    if ($wpdb->query($query) === false) {
        return $response;
    }

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext == 'jpeg') {
        $ext = 'jpg';
    }
    $compressionType = $this->settings['wpio_api_type'.$ext];
    if (empty($compressionType)) {
        $compressionType = 'lossy';
    }
    if ($compressionType=="none" || !in_array($ext, $this->allowed_ext)) {
        $response->msg =  __('This file type is not allowed','wpio') ;
        return $response;
    }

    $fparams = array("compression_type"=> $compressionType);
    if($this->settings['clean_metadata'] == "0" ) {
        $fparams['preserve_metas'] = array();
        $fparams['preserve_metas'][] = 'orientation';
        $fparams['preserve_metas'][] = 'icc';
        $fparams['preserve_metas'][] = 'copyright';
        $fparams['preserve_metas'][] = 'datetime';
        $fparams['preserve_metas'][] = 'location';

    } else if ($this->settings['clean_metadata'] == "preserve" ) {
        $fparams['preserve_metas'] = array();
        if ($this->settings['preserve_metadata_datetime'] == "1" ) {
             $fparams['preserve_metas'][] = 'datetime';
        }
        if ($this->settings['preserve_metadata_location'] == "1" ) {
             $fparams['preserve_metas'][] = 'location';
        }
        if ($this->settings['preserve_metadata_copyright'] == "1" ) {
             $fparams['preserve_metas'][] = 'copyright';
        }
         if ($this->settings['preserve_metadata_orientation'] == "1" ) {
             $fparams['preserve_metas'][] = 'orientation';
        }
         if ($this->settings['preserve_metadata_color_profile'] == "1" ) {
             $fparams['preserve_metas'][] = 'icc';
        }
    }

    $resize_auto = $this->settings['wpio_api_resize_auto'];
    $resize_maxsize = (int)$this->settings['wpio_api_maxsize'];
    $resize_maxheight = (int)$this->settings['wpio_api_maxheight'];
    if ($resize_auto && $resize_maxsize) {   //Only apply on new images
        $installed_time = (int)$this->settings['wpio_api_installed_time'];
        if (empty($installed_time)) {
            $installed_time = time();
            $this->settings['wpio_api_installed_time'] = $installed_time;
            update_option( '_wpio_settings', $this->settings );
        }

        $size = @getimagesize($file);
        $fileCreated = filectime($file);
        $fparams['resize'] = array();

        //contain a state of the checkbox. If it's '1' then date doesn't matter.
        $does_date_matter = $this->settings['wpio_api_resize_date_notmatter'];
        if ($size && ($size[0] > $resize_maxsize)) {
            if (($fileCreated > $installed_time) || $does_date_matter) {
                $fparams['resize'] +=  array("width"=> $resize_maxsize);
            }
        }
        if (($size && ($size[1] > $resize_maxheight)) || $does_date_matter) {
            if ($fileCreated > $installed_time) {
                $fparams['resize'] +=  array("height"=> $resize_maxheight);
            }
        }
    }
     if (empty($this->settings['wpio_api_key']) || empty($this->settings['wpio_api_secret'])) {
        return false;
     }
    $ioa = new ioaphp($this->settings['wpio_api_key'], $this->settings['wpio_api_secret']);
    $file_url = get_site_url() . '/' . str_replace( ABSPATH, '', $file );
    $return = $ioa->uploadFileByUrl($file_url, $fparams);
    if ($return === false) { //try again by upload local file
        $return = $ioa->uploadFile($file, $fparams);
    }

    if ($return === false || $return === null || is_string($return) ) {
        $response->msg = $ioa->getLastError();
            $response->errCode = $ioa->getLastErrCode();
            $fail_counter = (int)get_option('wpio_optimize_fail_counter');
            $fail_counter++;

            update_option( 'wpio_optimize_fail_counter', $fail_counter, false);
            if($fail_counter >=3) {
                $this->disableOptimization(false);
            }

            $this->db_log($response->msg.' '.$file, $response->errCode);
            $this->write_log($ioa->getFullAPIResponse());
            return $response;
    } else {
            //reset fail counter
            update_option( 'wpio_optimize_fail_counter', 0,false);
    }


        clearstatcache();
    $sizebefore = filesize($file);

    $optimizedFileContent = $this->wpio_file_get_contents($return->optimized_url);
    if($optimizedFileContent===false){
        $response->msg =  __('Optimized url not found','wpio') ;
        return $response;
    }
    if(file_put_contents($file, $optimizedFileContent)===false){
        $response->msg =  __('Download optimized image fail','wpio') ;
        return $response;
    }
    $md5 = md5_file($file);

        clearstatcache();
    $size_after = filesize($file);
    $query = $wpdb->prepare('INSERT INTO '.$wpdb->prefix.'wpio_images (`file`,`md5`,api_id,size_before,size_after,`date`,expiration_date) 
                    VALUES (%s,%s,%d,%d,%d,%s,%s) ON DUPLICATE KEY UPDATE `md5` = VALUES(`md5`), size_after = VALUES(size_after), `date` = VALUES(`date`), expiration_date = VALUES(expiration_date), api_id = VALUES(api_id) ',
                        $relativePath,$md5,$return->id,$sizebefore,$size_after,date('Y-m-d H:i:s'),$return->expiration_date);
    if($wpdb->query($query)===false){
        $response->msg =  __('Save optimized image to db fail','wpio') ;
        return $response;
    }else{
        // update md5 after Optimize in wpio_listimage
         $fileData =  array();
         $fileData['filename'] = $relativePath;
         $fileData['size'] = $size_after;
         $fileData['filetype'] = strtolower(pathinfo($file,PATHINFO_EXTENSION));
         $fileData['modified'] = gmdate("Y-m-d H:i:s", filemtime($file)) ;
         $fileData['md5']  = $md5;
         $results[] = $fileData;

         $this->addImagesToDb($results);
    }

        $response->status = true;
        $response->msg = sprintf(  __('Optimized at %s%%','wpio') , round(($sizebefore-$size_after)/$sizebefore*100,2));

    return $response;

    }
    
    public function doActionOptimize()
    {
        $file = ABSPATH.$_REQUEST['file'];
        $returned = $this->optimize($file);
        $this->ajaxReponse($returned->status, $returned);
    }

    public function doActionOptimizeAll()
    {
        $filters = array('optimized' => 'no');
        $filters['onlyfile'] = 1;
        $filters['no_limit'] = 1;
        update_option("wpio_ao_status", 1, false );
        $images = 0;
        try {
        $images = $this->getLocalImages($filters);
            if (count($images)) {
                $wpio_queue = new WPIO_queue(false);
                $wpio_queue->setImages($images);

                $wpio_queue->save();
                wp_remote_head(WPIO_IMAGERECYCLE_URL.'/queue_process.php');
            }
        }catch (Exception $e){
             error_log($e);
        }
        $this->ajaxReponse(true,array('totalImagesProcessing'=> count($images)));
    }
    
    public function optimizeAllOn() {
        update_option("wpio_is_OptimizeAll",1,false );
        $this->ajaxReponse(true);
    }
    
    public function stopOptimizeAll() {
        $wpio_queue = new WPIO_queue();
        $wpio_queue->clear();
        update_option("wpio_ao_status",0,false );
        update_option("wpio_is_OptimizeAll",0,false );
        $this->ajaxReponse(true);
    }
    
    public function doActionRevert(){
    global $wpdb;

    $file = realpath(ABSPATH.$_REQUEST['file']);
        $relativePath = DIRECTORY_SEPARATOR.substr($file,strlen(ABSPATH));

    $query = $wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'wpio_images WHERE file=%s',$relativePath);
    if($wpdb->query($query)===false){
        $this->ajaxReponse(false);
    }
    $row = $wpdb->get_row($query,OBJECT);
    if(!$row){
        $this->ajaxReponse(false);
    }

    $ioa = new ioaphp($this->settings['wpio_api_key'], $this->settings['wpio_api_secret']);
    $return = $ioa->getImage($row->api_id);

    if(!isset($return->id)){
        if($return === false) {
            $this->db_log($ioa->getLastError(), $ioa->getLastErrCode());
        }
        $this->ajaxReponse(false);
    }
    $fileContent = $this->wpio_file_get_contents($return->origin_url);
    if($fileContent===false){
        $this->ajaxReponse(false);
    }

    if(file_put_contents(ABSPATH.$row->file, $fileContent)===false){
        $this->ajaxReponse(false);
    }

    $query = $wpdb->prepare('DELETE FROM '.$wpdb->prefix.'wpio_images WHERE file=%s',$relativePath);
    $result = $wpdb->query($query);
    if($result===false){
        $this->ajaxReponse(false);
    }
    $response = new stdClass();
    $response->filename = $row->file;
    $this->ajaxReponse(true,$response);
    }
       
    public function wpio_file_get_contents($file_url) {
        $file_url = str_replace(' ', '%20', $file_url);
        $result = wp_remote_get($file_url);
        if (is_wp_error($result)) {
            return false;
        }

        $data = wp_remote_retrieve_body($result) ;

        if (is_wp_error($data)) {
            return false;
        }

        return $data;
    }
    
    public function enableOptimization(){
        $settings = get_option('_wpio_settings');
        $settings['wpio_api_optimization_status'] = 1;
        $result = update_option('_wpio_settings', $settings);
        //reset fail counter
        update_option( 'wpio_optimize_fail_counter', 0, false);
        $this->ajaxReponse(true);
    }
    
    public function disableOptimization($return=true){
        $settings = get_option('_wpio_settings');
        $settings['wpio_api_optimization_status'] = 0;
        $result = update_option('_wpio_settings', $settings);
        if ($return) {
            $this->ajaxReponse(true);
        }
    }
    
    public function generateMetadata($meta){
        //if not images file then return;
        if(!isset($meta['file']) ) {
            return $meta;
        }

        $path = pathinfo('/wp-content/uploads/'.$meta['file'], PATHINFO_DIRNAME).'/';
        $wpio_queue = new WPIO_queue();
        $wpio_queue->enqueue('/wp-content/uploads/'.$meta['file']);
        $wpio_queue->saveChange('/wp-content/uploads/'.$meta['file'], true);
        if(is_array($meta['sizes']) && count($meta['sizes']) ) {
            $results =  array();
            foreach($meta['sizes'] as $thumb){

                $fileData =  array();
                $filename = ABSPATH. $path.$thumb['file'] ;
                $fileData['filename'] = $path.$thumb['file'] ;
                $fileData['filetype'] = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
                $fileData['size'] = filesize($filename);
                $fileData['modified'] = gmdate("Y-m-d H:i:s", filemtime($filename) ) ;
                $fileData['md5']  = md5_file(ABSPATH.$path.$thumb['file']);
                $results[] = $fileData;

                $wpio_queue->enqueue($path.$thumb['file']);
                $wpio_queue->saveChange($path.$thumb['file'], true);
            }
            $this->addImagesToDb($results);
        }

        update_option("wpio_ao_status",1,false );
        wp_remote_head(WPIO_IMAGERECYCLE_URL.'/queue_process.php');
        return $meta;
    }
    
    function addFileToQueue($fileinfo) {
        //Add file to the index table
        $allowed_minetype = array("image/jpeg","image/jpeg","image/gif","image/png","application/pdf");
        if(isset($fileinfo['type']) && in_array( $fileinfo['type'],$allowed_minetype ) ){

            $fileData =  array();
            $filename = $fileinfo['file'] ;
            $fileData['filename'] = DIRECTORY_SEPARATOR.substr($filename, strlen(ABSPATH));
            $fileData['filetype'] = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
            $fileData['size'] = filesize($filename);
            $fileData['modified'] = gmdate("Y-m-d H:i:s", filemtime($filename) ) ;
            $fileData['md5'] = md5_file($filename);
            $results =  array();
            $results[] = $fileData;
            $this->addImagesToDb($results);
        }

        if(isset($fileinfo['type']) && $fileinfo['type']=="application/pdf") {
            $wpio_queue = new WPIO_queue();
            $wpio_queue->enqueue($fileinfo['file']);
            $wpio_queue->saveChange($fileinfo['file'],true);
        }

        return $fileinfo;
    }
    
    protected function ajaxReponse($status,$datas=null){
        $response = array('status'=>$status,'datas'=>$datas);
        echo json_encode($response);
        die();
    }
        
    public function saveNewAccountData()
    {
        $key = $_REQUEST['key'];
        $secret = $_REQUEST['secret'];
        $settings = get_option('_wpio_settings');
        $settings['wpio_api_key'] = $key;
        $settings['wpio_api_secret'] = $secret;
        $result = update_option('_wpio_settings', $settings);
        echo json_encode($result);
        die();
    }
    
    //write log only in debug mode
    function write_log ( $log )  {
       if(defined('WP_DEBUG') && WP_DEBUG) {
        if ( is_array( $log ) || is_object( $log ) ) {
           error_log( print_r( $log, true ) );
        } else {
           error_log( $log );
        }
       }
    }

    //Log the error in database
    function db_log($errMsg, $errCode) {
        $err_logs = get_option('wpio_err_logs', array());

        array_unshift($err_logs, array(
                'msg' => $errMsg,
                'code' => $errCode,
                'time' => time()
        ));

        // Keep only 10 first items
        array_splice($err_logs, 0, 10);

        update_option('wpio_err_logs', $err_logs);
    }
}


class IgnorantRecursiveDirectoryIterator extends RecursiveDirectoryIterator
{
    function getChildren()
    {
        try {
            return new IgnorantRecursiveDirectoryIterator($this->getPathname());
        } catch (UnexpectedValueException $e) {
            return new RecursiveArrayIterator(array());
        }
    }
}

if (!class_exists('WPIOTable')) {

    include_once(WPIO_IMAGERECYCLE . 'class/wpio-table.php');
}

if (!class_exists('WPIO_queue')) {
    include_once(WPIO_IMAGERECYCLE . 'class/wpio-queue.php');
}
if (!class_exists('wpioSetting')) {
    include_once(WPIO_IMAGERECYCLE . 'class/wpio-setting.php');
}
