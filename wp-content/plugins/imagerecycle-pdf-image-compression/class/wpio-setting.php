<?php
/**
 * ImageRecycle pdf & image compression
 *
 * @package ImageRecycle pdf & image compression
 */
defined('ABSPATH') || die('No direct script access allowed!');

class wpioSetting
{

    public function __construct()
    {
        //Get settings
        $this->settings = array(
            "wpio_api_include" => "wp-content/uploads,wp-content/themes",
            "wpio_api_resize_auto" => "0",
            "wpio_api_maxsize" => "1600",
            "wpio_api_maxheight" => "1600",
            "wpio_api_minfilesize" => "0",
            "wpio_api_maxfilesize" => "5120",
            "wpio_api_typepdf" => "lossy",
            "wpio_api_typepng" => "lossy",
            "wpio_api_typejpg" => "lossy",
            "wpio_api_typegif" => "lossy",
            "wpio_api_send_email" => "1",
            "wpio_debug_curl" => "0",
            "wpio_api_optimization_status" => "1",
            "clean_metadata" => "1",
            "preserve_metadata_datetime" => "0",
            "preserve_metadata_location" => "0",
            "preserve_metadata_copyright" => "0",
            "preserve_metadata_orientation" => "0",
            "preserve_metadata_color_profile" => "0"
        );
        $settings = get_option('_wpio_settings');
        if (is_array($settings)) {
            $this->settings = array_merge($this->settings, $settings);
        }

        add_action('wp_ajax_wpio_getFolders', array($this, 'getFolders'));
        add_action('wp_ajax_wpio_setFolders', array($this, 'setFolders'));
        add_action('load-dashboard_page_wpir-foldertree', array(&$this, 'wpir_foldertree_thickbox'));
        wp_enqueue_script('accordion');
    }

    public function display()
    {
        ?>

        <div class="wrap wpio-wrap">
            <h2 id="wpio-hidden" style="display: none"></h2>
            <?php
            if (empty($this->settings['wpio_api_key']) || empty($this->settings['wpio_api_secret'])) {
                include_once WPIO_IMAGERECYCLE . 'class/pages/wpio-dashboard.php';
            } ?>

            <?php
            if (!empty($this->settings['wpio_api_key']) && !empty($this->settings['wpio_api_secret'])) {
                $ioa = new ioaphp($this->settings['wpio_api_key'], $this->settings['wpio_api_secret']);
                $return = $ioa->getAccountInfos();
                $percentQuota = 0;
                $consumption_text = "";
                if ($return && (floatval($return->quota_allowed) > 0)) {
                    $consumption_text = __('Consummated quota from', 'wpio') . " " . date('d F Y', $return->quota_start) . " " .
                        __('to', 'wpio') . " " . date('d F Y', $return->quota_end) . ": " .
                        "<b>" . WPIO_Helper::formatBytes(floatval($return->quota_current)) . " / " .
                        WPIO_Helper::formatBytes(floatval($return->quota_allowed)) . "</b>";
                    $percentQuota = number_format(min(($return->quota_current / $return->quota_allowed), 1) * 100, 2);
                } else if ($return) {
                    $consumption_text = __('Consummated quota from', 'wpio') . " " . date('d F Y', $return->quota_start) . " " .
                        __('to', 'wpio') . " " . date('d F Y', $return->quota_end) . ": " .
                        "<b>" . WPIO_Helper::formatBytes(floatval($return->quota_current)) . "</b>";
                } else {

                    $this->db_log($ioa->getLastError(), $ioa->getLastErrCode());
                }

                ?>

                <h2 id="wpio_consumption"><?php _e('Optimization quota usage :', 'wpio') ?> </h2>
                <?php if ($percentQuota > 0) { ?>
                    <?php echo $consumption_text; ?>
                    <div style="float: left;width:100%;clear:both">

                        <div class="wpio-progress-wrap" style="float: left">
                            <div class="wpio-progress-bar"
                                 style="width: <?php echo $percentQuota; ?>%;min-width:0.5%"></div>
                            <span><?php echo $percentQuota; ?>%</span>
                        </div>

                        <div style="float: left;margin:10px 10px 10px 20px">
                            <a href="https://www.imagerecycle.com/prices" target="_blank"
                               class="button button-primary"><?php _e('Get optimization quota', 'wpio') ?></a>
                        </div>
                    </div>
                <?php } else { //unlimited quota ?>
                    <span style="line-height: 25px;"><?php echo $consumption_text; ?></span>
                    <a href="https://www.imagerecycle.com/prices" target="_blank"
                       class="button button-primary"><?php _e('Get optimization quota', 'wpio') ?></a>
                <?php }

            } ?>

            <h2 id="wpio_settings"><?php _e('Image Recycle Settings', 'wpio') ?></h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('Image Recycle');
                do_settings_sections('option-image-recycle');
                submit_button();
                ?>
            </form>
            <!-- Display 10 latest errors -->
            <?php $wpio_logs = get_option('wpio_err_logs', array());
            $date_format = get_option('date_format');
            $time_format = get_option('time_format');
            $display_format = $date_format . " " . $time_format;
            ?>
            <div class="accordion-container wpio-section-logs">
                <div class="accordion-section control-section control-section-default">
                    <h3 class="accordion-section-title" tabindex="0"><?php _e('ImageRecycle log', 'wpio') ?> <span>(<?php _e('10 latest errors encountered', 'wpio'); ?>)</span></h3>
                    <table class="accordion-section-content">
                        <head class="customize-control log_heading">
                            <tr>
                                <td class="errCode"><?php _e('Error code', 'wpio'); ?></td>
                                <td class="errTime"><?php _e('Logged time', 'wpio'); ?></td>
                                <td class="errMsg"><?php _e('Error Message', 'wpio'); ?></td>
                            </tr>
                        </head>
                        <tbody>
                            <?php
                            if (!empty($wpio_logs)):
                                foreach ($wpio_logs as $wpio_log):
                                    ?>
                                    <tr class="customize-control">
                                        <td class="errCode"><?php echo $wpio_log['code']; ?></td>
                                        <td class="errTime"><?php echo date($display_format, $wpio_log['time']); ?></td>
                                        <td class="errMsg"><?php echo $wpio_log['msg']; ?></td>
                                    </tr>
                                <?php
                                endforeach;
                            endif;
                            ?>
                        </tbody>
                    </table>

                </div>
            </div>

            <div style="float: left;width:100%; border-top:1px solid #333; margin:20px 0 0 0;padding-top:10px">
                <div style="float: left;">
                    <p class="description" style="color: #f00">
                        <a id="wpio_reinitialize"
                           class="button button-secondary"><?php _e('Reinitialize', 'wpio') ?></a>
                        <?php _e('Use with caution: it will re-index all your images as if they’ve never compressed, then re-launch a global optimization.', 'wpio') ?>
                    </p>
                </div>

            </div>

        </div>
        <?php
    }

    public function showSettings()
    {
        echo 'WP Image Optimizer';
    }

    public function showAPIKey()
    {
        $api_key = isset($this->settings['wpio_api_key']) ? $this->settings['wpio_api_key'] : '';
        echo '<input id="wpio_api_key" name="_wpio_settings[wpio_api_key]" type="text" value="' . esc_attr($api_key) . '" size="50"/>';

        $installed_time = isset($this->settings['wpio_api_installed_time']) ? $this->settings['wpio_api_installed_time'] : time();
        echo '<input type="hidden" name="_wpio_settings[wpio_api_installed_time]" value="' . $installed_time . '" />';

    }

    public function showAPISecret()
    {
        $api_secret = isset($this->settings['wpio_api_secret']) ? $this->settings['wpio_api_secret'] : '';
        echo '<input id="wpio_api_secret" name="_wpio_settings[wpio_api_secret]" type="text" value="' . esc_attr($api_secret) . '" size="50"/>';
    }

    public function showIncludeFolder()
    {
        $api_include = isset($this->settings['wpio_api_include']) ? $this->settings['wpio_api_include'] : 'wp-content' . DIRECTORY_SEPARATOR . 'uploads,wp-content' . DIRECTORY_SEPARATOR . 'themes';
        echo '<input id="wpio_api_inxclude" readonly type="text" value="' . esc_attr($api_include) . '" size="50"/>';
        echo '<input id="wpio_api_inxclude_id" name="_wpio_settings[wpio_api_include]" type="hidden" value="' . esc_attr($api_include) . '" size="50"/>';
        echo '<a href="index.php?page=wpir-foldertree&TB_iframe=true&width=600&height=550"  class="thickbox"><span class="dashicons dashicons-portfolio" style="line-height:1.5;text-decoration:none"></span></a>';
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
    }

    
    public function showImageResize(){
        $api_imageresize = isset( $this->settings['wpio_api_resize_auto'] ) ? $this->settings['wpio_api_resize_auto'] : 0;
        $checked = isset( $this->settings['wpio_api_resize_date_notmatter'] ) ? 'checked' : '';
        if($api_imageresize == 1){
            echo '<label><input id="wpio_api_resize_auto_yes" name="_wpio_settings[wpio_api_resize_auto]" type="radio" value="1" checked>'. __('Yes','wpio') .'</label>'.
                    '<label style="margin-left:15px"><input id="wpio_api_resize_auto_no" name="_wpio_settings[wpio_api_resize_auto]" type="radio" value="0">'. __('No','wpio') .'</label>'.
                    '<br><label><input type="checkbox" id="wpio_api_resize_date_notmatter_val" name="_wpio_settings[wpio_api_resize_date_notmatter]" value="1" '.$checked. '/>'. __('Resize also images present before plugin installation', 'wpio') .'</label>';
        }else{
            echo '<label><input id="wpio_api_resize_auto_yes" name="_wpio_settings[wpio_api_resize_auto]" type="radio" value="1">'. __('Yes','wpio') .'</label>'.
                    '<label style="margin-left:15px"><input id="wpio_api_resize_auto_no" name="_wpio_settings[wpio_api_resize_auto]" type="radio" value="0" checked>'. __('No','wpio') .'</label>'.
                    '<br><label style="margin-left:15px"><input type="checkbox" id="wpio_api_resize_status_val" name="_wpio_settings[wpio_api_resize_date_notmatter]" value="1" '.$checked. ' disabled/>'. __('Resize also images present before plugin installation', 'wpio') .'</label>';
        }
    }

    public function showmaxsize()
    {
        $api_maxsize = isset($this->settings['wpio_api_maxsize']) ? $this->settings['wpio_api_maxsize'] : '1600';
        $api_maxheight = isset($this->settings['wpio_api_maxheight']) ? $this->settings['wpio_api_maxheight'] : '1600';
        $maxsize_input = __('Width', 'wpio') . ': <input id="wpio_api_maxsize" name="_wpio_settings[wpio_api_maxsize]" type="text" value="' . esc_attr($api_maxsize) . '" size="10"/>px';
        $maxsize_input .= '<br>' . __('Height', 'wpio') . ':<input id="wpio_api_maxheight" name="_wpio_settings[wpio_api_maxheight]" type="text" value="' . esc_attr($api_maxheight) . '" size="10"/>px';
        $maxsize_input .= '<p class="description">' . __('Use with caution ! Resize all images regarding the max specified size ie. if 1600px the max width image size will be 1600px', 'wpio') . '</p>';
        echo $maxsize_input;
    }

    public function showminfilesize()
    {
        $api_minfilesize = isset($this->settings['wpio_api_minfilesize']) ? $this->settings['wpio_api_minfilesize'] : '0';
        echo '<input id="wpio_api_minfilesize" name="_wpio_settings[wpio_api_minfilesize]" type="text" value="' . esc_attr($api_minfilesize) . '" size="10"/>';
    }

    public function showmaxfilesize()
    {
        $api_maxfilesize = isset($this->settings['wpio_api_maxfilesize']) ? $this->settings['wpio_api_maxfilesize'] : '5120';
        echo '<input id="wpio_api_maxfilesize" name="_wpio_settings[wpio_api_maxfilesize]" type="text" value="' . esc_attr($api_maxfilesize) . '" size="10"/>';
    }

    public function wpio_viewselect($viewId, $viewName, $value)
    {
        $option_array = array('lossy' => __('Best saving', 'wpio'), 'lossless' => __('Original quality', 'wpio'), 'none' => __('No compression', 'wpio'));
        $select = "<select id='$viewId' name='$viewName'>";
        foreach ($option_array as $key => $option) {
            if ($key == $value) {
                $select .= "<option value='$key' selected>$option</option>";
            } else {
                $select .= "<option value='$key'>$option</option>";
            }
        }
        $select .= '</select>';
        return $select;
    }

    public function showtypepdf()
    {
        $api_typepdf = isset($this->settings['wpio_api_typepdf']) ? $this->settings['wpio_api_typepdf'] : 'lossy';
        $typepdf = $this->wpio_viewselect('wpio_api_typepdf', '_wpio_settings[wpio_api_typepdf]', $api_typepdf);
        echo $typepdf;
    }

    public function showtypepng()
    {
        $api_typepng = isset($this->settings['wpio_api_typepng']) ? $this->settings['wpio_api_typepng'] : 'lossy';
        $typepng = $this->wpio_viewselect('wpio_api_typepng', '_wpio_settings[wpio_api_typepng]', $api_typepng);
        echo $typepng;
    }

    public function showtypejpg()
    {
        $api_typejpg = isset($this->settings['wpio_api_typejpg']) ? $this->settings['wpio_api_typejpg'] : 'lossy';
        $typejpg = $this->wpio_viewselect('wpio_api_typejpg', '_wpio_settings[wpio_api_typejpg]', $api_typejpg);
        echo $typejpg;
    }

    public function showtypegif()
    {
        $api_typegif = isset($this->settings['wpio_api_typegif']) ? $this->settings['wpio_api_typegif'] : 'lossy';
        $typegif = $this->wpio_viewselect('wpio_api_typegif', '_wpio_settings[wpio_api_typegif]', $api_typegif);
        echo $typegif;
    }

    public function showCleanMetadata()
    {
        $clean_metadata = isset($this->settings['clean_metadata']) ? $this->settings['clean_metadata'] : '1';

        $option_array = array('1' => __('All', 'wpio'), '0' => __('None', 'wpio'), 'preserve' => __('Preserve Selected', 'wpio'));
        $select = "<select id='clean_metadata' name='_wpio_settings[clean_metadata]'>";
        foreach ($option_array as $key => $option) {
            if ($key == $clean_metadata) {
                $select .= "<option value='$key' selected>$option</option>";
            } else {
                $select .= "<option value='$key'>$option</option>";
            }
        }
        $select .= '</select>';
        echo $select;

    }

    public function showPreserveMetadata()
    {
        $hide = ($this->settings['clean_metadata'] != "preserve") ? "style='display:none'" : "";
        $option_array = array('datetime' => __('Date and Time', 'wpio'), 'location' => __('Location', 'wpio'), 'copyright' => __('Copyright', 'wpio'), 'orientation' => __('Orientation', 'wpio'), 'color_profile' => __('Color profile', 'wpio'));
        $html = "<div id='preserve_metadata' " . $hide . "><ul>";
        foreach ($option_array as $key => $label) {
            if ($this->settings['preserve_metadata_' . $key] == "1") {
                $html .= "<li>" . $label . "<br/><input type='checkbox' name='_wpio_settings[preserve_metadata_" . $key . "]' value='1' checked /></li>";
            } else {
                $html .= "<li>" . $label . "<br/><input type='checkbox' name='_wpio_settings[preserve_metadata_" . $key . "]' value='1' /></li>";
            }
        }
        $html .= '</ul></div>';
        echo $html;

    }

    public function showSendEmail()
    {
        $api_send_email = isset($this->settings['wpio_api_send_email']) ? $this->settings['wpio_api_send_email'] : 0;
        if ($api_send_email == 1) {
            echo '<label><input id="wpio_api_send_email_yes" name="_wpio_settings[wpio_api_send_email]" type="radio" value="1" checked>' . __('Yes', 'wpio') . '</label>' .
                '<label style="margin-left:15px"><input id="wpio_api_send_email_no" name="_wpio_settings[wpio_api_send_email]" type="radio" value="0">' . __('No', 'wpio') . '</label>';
        } else {
            echo '<label><input id="wpio_api_send_email_yes" name="_wpio_settings[wpio_api_send_email]" type="radio" value="1">' . __('Yes', 'wpio') . '</label>' .
                '<label style="margin-left:15px"><input id="wpio_api_send_email_no" name="_wpio_settings[wpio_api_send_email]" type="radio" value="0" checked>' . __('No', 'wpio') . '</label>';
        }
    }

    public function showDebugCurl()
    {
        $wpio_debug_curl = isset($this->settings['wpio_debug_curl']) ? $this->settings['wpio_debug_curl'] : 0;
        if ($wpio_debug_curl == 1) {
            echo '<label><input id="wpio_debug_curl" name="_wpio_settings[wpio_debug_curl]" type="radio" value="1" checked>' . __('Yes', 'wpio') . '</label>' .
                '<label style="margin-left:15px"><input id="wpio_debug_curl" name="_wpio_settings[wpio_debug_curl]" type="radio" value="0">' . __('No', 'wpio') . '</label>';
        } else {
            echo '<label><input id="wpio_debug_curl" name="_wpio_settings[wpio_debug_curl]" type="radio" value="1">' . __('Yes', 'wpio') . '</label>' .
                '<label style="margin-left:15px"><input id="wpio_debug_curl" name="_wpio_settings[wpio_debug_curl]" type="radio" value="0" checked>' . __('No', 'wpio') . '</label>';
        }
    }

    public function showOptimizationStatus()
    {
        $api_optimization_status = isset($this->settings['wpio_api_optimization_status']) ? (int)$this->settings['wpio_api_optimization_status'] : 1;
        $checked = ($api_optimization_status) ? 'checked="checked"' : '';
        echo '<input type="hidden" id="" name="_wpio_settings[wpio_api_optimization_status]" value="0" />';
        echo '<input type="checkbox" id="wpio_api_optimization_status_val" name="_wpio_settings[wpio_api_optimization_status]" value="1" ' . $checked . ' />';
    }

    public function getFolders()
    {

        $include_folders = isset($this->settings['wpio_api_include']) ? $this->settings['wpio_api_include'] : 'wp-content/uploads,wp-content/themes';
        $selected_folders = explode(',', $include_folders);
        $path = ABSPATH . DIRECTORY_SEPARATOR;
        $dir = $_REQUEST['dir'];

        $return = $dirs = array();
        if (file_exists($path . $dir)) {
            $files = scandir($path . $dir);

            natcasesort($files);
            if (count($files) > 2) { // The 2 counts for . and ..
                // All dirs
                $baseDir = ltrim(rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $dir), '/'), '/');
                if ($baseDir != '') $baseDir .= '/';
                foreach ($files as $file) {
                    if (file_exists($path . $dir . DIRECTORY_SEPARATOR . $file) && $file != '.' && $file != '..' && is_dir($path . $dir . DIRECTORY_SEPARATOR . $file)) {

                        if (in_array($baseDir . $file, $selected_folders)) {
                            $dirs[] = array('type' => 'dir', 'dir' => $dir, 'file' => $file, 'checked' => true);
                        } else {
                            $hasSubFolderSelected = false;
                            foreach ($selected_folders as $selected_folder) {
                                if (strpos($selected_folder, $baseDir . $file) === 0) {
                                    $hasSubFolderSelected = true;
                                }
                            }
                            if ($hasSubFolderSelected) {
                                $dirs[] = array('type' => 'dir', 'dir' => $dir, 'file' => $file, 'pchecked' => true);
                            } else {
                                $dirs[] = array('type' => 'dir', 'dir' => $dir, 'file' => $file);
                            }

                        }
                    }
                }
                $return = $dirs;
            }
        }
        echo json_encode($return);
        die();
    }

    public function setFolders()
    {

        $folders = $_REQUEST['folders'];
        $settings = get_option('_wpio_settings');
        $settings['wpio_api_include'] = $folders;
        $result = update_option('_wpio_settings', $settings);

        echo json_encode($result);
        die();
    }

    public function wpir_foldertree_thickbox()
    {
        if (!defined('IFRAME_REQUEST')) {
            define('IFRAME_REQUEST', true);
        }
        iframe_header();
        global $wp_scripts, $wp_styles;
        if (WP_DEBUG) {
            wp_enqueue_script('wp-image-optimizer-jaofoldertree', plugins_url('/js/jaofoldertree.js', dirname(__FILE__)), array(), WPIO_IMAGERECYCLE_VERSION . '-' . rand(1, 1000));
            wp_enqueue_style('wp-image-optimizer-jaofoldertree-css', plugins_url('/css/jaofoldertree.css', dirname(__FILE__)), array(), WPIO_IMAGERECYCLE_VERSION . '-' . rand(1, 1000));
        } else {
            wp_enqueue_script('wp-image-optimizer-jaofoldertree', plugins_url('/js/jaofoldertree.js', dirname(__FILE__)), array(), WPIO_IMAGERECYCLE_VERSION);
            wp_enqueue_style('wp-image-optimizer-jaofoldertree-css', plugins_url('/css/jaofoldertree.css', dirname(__FILE__)), array(), WPIO_IMAGERECYCLE_VERSION);
        }

        $include_folders = isset($this->settings['wpio_api_include']) ? $this->settings['wpio_api_include'] : 'wp-content/uploads,wp-content/themes';
        $selected_folders = explode(',', $include_folders);
        ?>
        <div style="padding-top: 10px;">
            <div class="pull-left" style="float: left">
                <div id="wpio_foldertree"></div>
            </div>
            <div class="pull-right" style="float: right;margin-right: 10px;">
                <button class="button button-primary" type="button"
                        onclick="jSelectFolders()"><?php echo __('OK', 'wpio') ?></button>
                <button class="button" type="button"
                        onclick="window.parent.tb_remove();"><?php echo __('Cancel', 'wpio') ?></button>
            </div>
        </div>
        <script>
            var curFolders = <?php echo json_encode($selected_folders);?>;

            jQuery(document).ready(function ($) {
                var sdir = '/';
                jSelectFolders = function () {

                    var fchecked = [];
                    curFolders.sort();
                    for (i = 0; i < curFolders.length; i++) {
                        curDir = curFolders[i];
                        valid = true;
                        for (j = 0; j < i; j++) {
                            if (curDir.indexOf(curFolders[j]) == 0) {
                                valid = false;
                            }
                        }
                        if (valid) {
                            fchecked.push(curDir);
                        }
                    }

                    data = {};
                    data.folders = fchecked.join(',');
                    data.action = 'wpio_setFolders';
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: data
                    }).done(function (result) {
                        window.parent.tb_remove();
                    });

                    window.parent.document.getElementById('wpio_api_inxclude').value = fchecked.join(',');
                    window.parent.document.getElementById('wpio_api_inxclude_id').value = fchecked.join(',');
                }
                $('#wpio_foldertree').wpio_jaofoldertree({
                    script: ajaxurl,
                    usecheckboxes: true,
                    showroot: '/',
                    oncheck: function (elem, checked, type, file) {
                        var dir = file;
                        if (file.substring(file.length - 1) == sdir) {
                            file = file.substring(0, file.length - 1);
                        }
                        if (file.substring(0, 1) == sdir) {
                            file = file.substring(1, file.length);
                        }
                        if (checked) {
                            if (file != "" && curFolders.indexOf(file) == -1) {
                                curFolders.push(file);
                            }
                        } else {

                            if (file != "" && !$(elem).next().hasClass('pchecked')) {
                                temp = [];
                                for (i = 0; i < curFolders.length; i++) {
                                    curDir = curFolders[i];
                                    if (curDir.indexOf(file) !== 0) {
                                        temp.push(curDir);
                                    }
                                }
                                curFolders = temp;
                            } else {
                                var index = curFolders.indexOf(file);
                                if (index > -1) {
                                    curFolders.splice(index, 1);
                                }
                            }
                        }

                    }
                });

                jQuery('#wpio_foldertree').bind('afteropen', function () {
                    jQuery(jQuery('#wpio_foldertree').wpio_jaofoldertree('getchecked')).each(function () {
                        curDir = this.file;
                        if (curDir.substring(curDir.length - 1) == sdir) {
                            curDir = curDir.substring(0, curDir.length - 1);
                        }
                        if (curDir.substring(0, 1) == sdir) {
                            curDir = curDir.substring(1, curDir.length);
                        }
                        if (curFolders.indexOf(curDir) == -1) {
                            curFolders.push(curDir);
                        }
                    })
                    spanCheckInit();

                })

                spanCheckInit = function () {
                    $("span.check").unbind('click');
                    $("span.check").bind('click', function () {
                        $(this).removeClass('pchecked');
                        $(this).toggleClass('checked');
                        if ($(this).hasClass('checked')) {
                            $(this).prev().prop('checked', true).trigger('change');
                            ;
                        } else {
                            $(this).prev().prop('checked', false).trigger('change');
                            ;
                        }
                        setParentState(this);
                        setChildrenState(this);
                    });
                }

                setParentState = function (obj) {
                    var liObj = $(obj).parent().parent(); //ul.jaofoldertree
                    var noCheck = 0, noUncheck = 0, totalEl = 0;
                    liObj.find('li span.check').each(function () {

                        if ($(this).hasClass('checked')) {
                            noCheck++;
                        } else {
                            noUncheck++;
                        }
                        totalEl++;
                    })

                    if (totalEl == noCheck) {
                        liObj.parent().children('span.check').removeClass('pchecked').addClass('checked');
                        liObj.parent().children('input[type="checkbox"]').prop('checked', true).trigger('change');
                    } else if (totalEl == noUncheck) {
                        liObj.parent().children('span.check').removeClass('pchecked').removeClass('checked');
                        liObj.parent().children('input[type="checkbox"]').prop('checked', false).trigger('change');
                    } else {
                        liObj.parent().children('span.check').removeClass('checked').addClass('pchecked');
                        liObj.parent().children('input[type="checkbox"]').prop('checked', false).trigger('change');
                    }

                    if (liObj.parent().children('span.check').length > 0) {
                        setParentState(liObj.parent().children('span.check'));
                    }
                }

                setChildrenState = function (obj) {
                    if ($(obj).hasClass('checked')) {
                        $(obj).parent().find('li span.check').removeClass('pchecked').addClass("checked");
                        $(obj).parent().find('li input[type="checkbox"]').prop('checked', true).trigger('change');
                    } else {
                        $(obj).parent().find('li span.check').removeClass("checked");
                        $(obj).parent().find('li input[type="checkbox"]').prop('checked', false).trigger('change');
                    }

                }
            })
        </script>
        <?php
        iframe_footer();
        exit; //Die to prevent the page continueing loading and adding the admin menu's etc. 
    }

    //Log the error in database
    function db_log($errMsg, $errCode)
    {

        $err_logs = get_option('wpio_err_logs');
        if (empty($err_logs)) {
            $err_logs = array();
        }

        $err = array();
        $err['msg'] = $errMsg;
        $err['code'] = $errCode;
        $err['time'] = time();
        array_push($err_logs, $err);

        if (count($err_logs) > 10) {
            array_shift($err_logs);
        }
        update_option('wpio_err_logs', $err_logs);
    }
}

if (!class_exists('WPIO_Helper')) {
    include_once(WPIO_IMAGERECYCLE . 'class/wpio-helper.php');
}