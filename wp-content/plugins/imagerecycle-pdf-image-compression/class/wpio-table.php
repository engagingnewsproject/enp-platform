<?php
/**
 * ImageRecycle pdf & image compression
 *
 * @package ImageRecycle pdf & image compression
 */
defined('ABSPATH') || die('No direct script access allowed!');

include_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class WPIOTable extends WP_List_Table
{
    protected $columns;
    protected $totalItems;

    /**
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     */
    function __construct()
    {

        global $status, $page;

        //Set parent defaults
        parent::__construct(
            array(
                //singular name of the listed records
                'singular' => 'image',
                //plural name of the listed records
                'plural' => 'images',
                //does this table support ajax?
                'ajax' => true,
                'screen' => "media_page_wp-image-recycle-page"
            )
        );
    }

    public function setColumns($columns)
    {
        $this->columns = (array)$columns;
    }

    public function setItems($items, $totalItems)
    {
        $this->items = $items;
        $this->totalItems = $totalItems;
    }

    protected function column_default($item, $columnName)
    {
        if (isset($item[$columnName])) {
            return $item[$columnName];
        }
    }

    public function display()
    {
        // add input hidden indexation auto
        $imgR_index_auto = (int)get_option('wpio_indexation_auto', 0);
        $this->prepare_items();
        wp_nonce_field('ajax-wpio_nonce', '_ajax_wpio_nonce');
        echo '<input type="hidden" id="imgRce_index_auto" name="imgRce_index_auto" value="' . $imgR_index_auto . '" />';
        echo '<input type="hidden" id="order" name="order" value="' . $this->_pagination_args['order'] . '" />';
        echo '<input type="hidden" id="orderby" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';
        parent::display();
    }

    public function prepare_items()
    {
        $hidden = array();
        $sortable = array(
            'filename' => array('filename', false),
            'size' => array('size', false),
            'status' => array('optimized', false)
        );

        $current_page = $this->get_pagenum();
        $total_items = $this->totalItems;
        $per_page = 30;
        $this->set_pagination_args(array(
            'total_items' => $this->totalItems,                  //WE have to calculate the total number of items
            'per_page' => $per_page,                     //WE have to determine how many items to show on a page

            //WE have to calculate the total number of pages
            'total_pages' => ceil($total_items / $per_page),
            // Set ordering values if needed (useful for AJAX)
            'orderby' => !empty($_REQUEST['orderby']) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'filename',
            'order' => !empty($_REQUEST['order']) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'asc'
        ));
        $this->_column_headers = array($this->get_columns(), $hidden, $sortable);
    }

    /**
     * Handle an incoming ajax request (called from admin-ajax.php)
     *
     * @since 3.1.0
     * @access public
     */
    function ajax_response()
    {

        check_ajax_referer('ajax-wpio_nonce', '_ajax_wpio_nonce');
        $wpir = new wpImageRecycle();
        $images = $wpir->getImagesforWPIOTable();


        $this->setColumns(array('cb' => '<input type="checkbox" />', 'thumbnail' => __('Image', 'wpio'), 'filename' => __('Filename', 'wpio'), 'size' => __('Size (Kb)', 'wpio'), 'status' => __('Compression', 'wpio'), 'actions' => __('Actions', 'wpio')));
        $this->setItems($images, $wpir->getTotalImages());

        $this->prepare_items();

        extract($this->_args);
        extract($this->_pagination_args, EXTR_SKIP);

        ob_start();
        if (!empty($_REQUEST['no_placeholder']))
            $this->display_rows();
        else
            $this->display_rows_or_placeholder();
        $rows = ob_get_clean();

        ob_start();
        $this->print_column_headers();
        $headers = ob_get_clean();

        ob_start();
        $this->pagination('top');
        $pagination_top = ob_get_clean();

        ob_start();
        $this->pagination('bottom');
        $pagination_bottom = ob_get_clean();

        $response = array('rows' => $rows);
        $response['pagination']['top'] = $pagination_top;
        $response['pagination']['bottom'] = $pagination_bottom;
        $response['column_headers'] = $headers;
        $total_items = $wpir->getTotalImages();
        if (isset($total_items))
            $response['total_items_i18n'] = sprintf(_n('1 item', '%s items', $total_items), number_format_i18n($total_items));

        if (isset($total_pages)) {
            $response['total_pages'] = $total_pages;
            $response['total_pages_i18n'] = number_format_i18n($total_pages);
        }

        die(json_encode($response));
    }

    public function get_columns()
    {
        return $this->columns;
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="image[]" value="%s" />', $item['filename']
        );
    }

    public function column_thumbnail($item)
    {

        if ($item['filetype'] == 'pdf') {
            $fileurl = WPIO_IMAGERECYCLE_URL . '/images/pdf-icon.png';
        } else {
            $fileurl = get_site_url() . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $item['filename']);
        }
        //get file name and add to alt attribute values
        $namefile = basename($fileurl);
        return sprintf(
            '<img class="image-small" style="display:none" src="%s" alt="%s"/>', $fileurl, $namefile
        );
    }

    function get_bulk_actions()
    {
        $actions = array(
            'optimize_selected' => 'Optimize selected',
            'revert_selected' => 'Revert selected'
        );
        return $actions;
    }

    function extra_tablenav($which)
    {
        $optimizeAllText = __('OptimizeAll', 'wpio');
        if ($optimizeAllText == "OptimizeAll") {
            $optimizeAllText = "Optimize all";
        }

        $wpio_queue = new WPIO_queue();
        $now = time();
        $ao_lastRun = (int)get_option('wpio_ao_lastRun', 0);
        $ao_status = (int)get_option('wpio_ao_status', 0);
        $ao_running = ($wpio_queue->count() && ($ao_status || ($now - $ao_lastRun) < 60)) ? true : false;

        ?>
        <div class="alignleft actions bulkactions">
            <span style="display: inline-block; margin:5px 18px 0 10px;float: left;"><?php _e('OR', 'wpio'); ?></span>
            <?php if (!$ao_running) { ?>
                <input id="dooptimizeall" class="button button-primary action" type="button"
                       value="<?= $optimizeAllText ?>">
            <?php } else { ?>
                <input id="stopoptimizeall" class="button action" type="button"
                       value="<?php _e('Stop optimization', 'wpio') ?>">
                <span class="spinner" id="wpio_processspinner"></span><span id="wpio_processstatus"></span>
            <?php } ?>


        </div>
        <?php
    }

    //filter by date in listimages table
    function months_dropdown_listimages($post_type)
    {
        global $wpdb, $wp_locale;
        if (apply_filters('disable_months_dropdown', false, $post_type)) {
            return;
        }
        $months = $wpdb->get_results(" SELECT DISTINCT YEAR( modified ) AS year, MONTH( modified ) AS month FROM " . $wpdb->prefix . "wpio_listimages ORDER BY modified DESC ");
        $months = apply_filters('months_dropdown_results', $months, $post_type);
        $month_count = count($months);
        if (!$month_count || (1 == $month_count && 0 == $months[0]->month))
            return;
        $m = isset($_GET['m']) ? (int)$_GET['m'] : 0;
        ?>
        <label for="filter-by-date" class="screen-reader-text"><?php _e('Filter by date', 'wpio'); ?></label>
        <select name="m" id="filter-by-date">
            <option<?php selected($m, 0); ?> value="0"><?php _e('Last modification date', 'wpio'); ?></option>
            <?php
            foreach ($months as $arc_row) {
                if (0 == $arc_row->year)
                    continue;

                $month = zeroise($arc_row->month, 2);
                $year = $arc_row->year;

                printf("<option %s value='%s'>%s</option>\n",
                    selected($m, $year . $month, false),
                    esc_attr($arc_row->year . $month),
                    /* translators: 1: month name, 2: 4-digit year */

                    sprintf( __( '%1$s %2$d','wpio'), $wp_locale->get_month( $month ), $year )
                );
            }
            ?>
        </select>
        <?php
    }

}