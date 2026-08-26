<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Admin_Menus_Addons extends NF_Abstracts_Submenu
{
    public $parent_slug = 'ninja-forms';

    public $menu_slug = 'ninja-forms#add-ons';

    public $position = 7;

    public function __construct()
    {
        $disable_marketing = false;
        if ( ! apply_filters( 'ninja_forms_disable_marketing', $disable_marketing ) ) {
            parent::__construct();
        }

        add_action( 'admin_init', array( $this, 'nf_upgrade_redirect' ) );
    }

    /**
     * If we have required updates, unregister the menu item
     */
    public function nf_upgrade_redirect() {
        global $pagenow;
            
        if( "1" == get_option( 'ninja_forms_needs_updates' ) ) {
            remove_submenu_page( $this->parent_slug, $this->menu_slug );
        }
    }

    public function get_page_title()
    {
        $title = '<span style="color:#84cc1e">' . esc_html__( 'Add-Ons', 'ninja-forms' ) . '</span>'; 

        return $title;
    }

    public function get_capability()
    {
        return apply_filters( 'ninja_forms_admin_extend_capabilities', $this->capability );
    }

    public function display()
    {
        // Fetch our marketing feed.
        $saved = get_option( 'ninja_forms_addons_feed', false );
        $feed_unavailable = false;
        // If we got back nothing...
        if ( ! $saved ) {
            // Default to the in-app file.
            $items = file_get_contents( Ninja_Forms::$dir . '/lib/Legacy/addons-feed.json' );
            $items = json_decode( $items, true );
        } // Otherwise... (We did get something from the db.)
        else {
            // Use the data we fetched.
            $items = json_decode( $saved, true );

            // The cached feed can end up corrupted (e.g. a non-JSON response was cached
            // before this validation existed). Fall back to the bundled list instead of
            // fatal-ing — the weekly feed cron will overwrite the option once it
            // successfully fetches valid data again.
            if ( ! is_array( $items ) ) {
                $feed_unavailable = true;
                $items = file_get_contents( Ninja_Forms::$dir . '/lib/Legacy/addons-feed.json' );
                $items = json_decode( $items, true );
            }
        }

        // Last-resort guard: never let a bad bundled file take down the page either.
        if ( ! is_array( $items ) ) {
            $items = array();
        }

        if ( $feed_unavailable ) {
            add_action( 'admin_notices', array( $this, 'render_feed_unavailable_notice' ) );
        }
        //shuffle( $items );

        $notices = array();

        //Check if an affiliate ID is set
        $u_id = get_option( 'nf_aff', false );
        if ( !$u_id ) $u_id = apply_filters( 'ninja_forms_affiliate_id', false );

        foreach ($items as &$item) {
            $plugin_data = array();
            if( !empty( $item['plugin'] ) && file_exists( WP_PLUGIN_DIR.'/'.$item['plugin'] ) ){
                $plugin_data = get_plugin_data( WP_PLUGIN_DIR.'/'.$item['plugin'], false, true );
            }
            
            if ( ! file_exists( Ninja_Forms::$dir . '/' . $item[ 'image' ] ) ) {
                $item[ 'image' ] = 'assets/img/add-ons/placeholder.png';
            }

            $version = isset ( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';

            //Rewrite link for affiliates
            if ( $u_id && $item[ 'link' ]) {
                $last_slash = strripos( $item[ 'link' ], '/' );
                $item[ 'link' ] = substr( $item[ 'link' ], 0, $last_slash );
                $item[ 'link' ] =  urlencode( $item[ 'link' ] );
                $item[ 'link' ] = 'http://www.shareasale.com/r.cfm?u=' . $u_id . '&b=812237&m=63061&afftrack=&urllink=' . $item[ 'link' ];
            }

            if ( ! empty ( $version ) && $version < $item['version'] ) {

                $notices[] = array(
                    'title' => $item[ 'title' ],
                    'old_version' => $version,
                    'new_version' => $item[ 'version' ]
                );
            }

            $item["status"] = self::getItemStatus($item);
        }

        $groups = [
            'advanced' => [
                'title' => __( 'Advanced Form Features', 'ninja-forms' ),
                'items' => self::filterItemsByCategroy( $items, 'advanced-form-features' ),
            ],
            'submissions' => [
                'title' => __( 'Submissions Extended', 'ninja-forms' ),
                'items' => self::filterItemsByCategroy( $items, 'submissions-extended' ),
            ],
            'payments' => [
                'title' => __( 'Accept Payments', 'ninja-forms' ),
                'items' => self::filterItemsByCategroy( $items, 'accept-payments' ),
            ],
            'automation' => [
                'title' => __( 'Automation', 'ninja-forms' ),
                'items' => self::filterItemsByCategroy( $items, 'automation' ),
            ],
            'marketing' => [
                'title' => __( 'Email Marketing', 'ninja-forms' ),
                'items' => self::filterItemsByCategroy( $items, 'email-marketing' ),
            ],
            'crm' => [
                'title' => __( 'CRMs', 'ninja-forms' ),
                'items' => self::filterItemsByCategroy( $items, 'crm-integrations' ),
            ],
            'notifications' => [
                'title' => __( 'Notifications & Workflow', 'ninja-forms' ),
                'items' => self::filterItemsByCategroy( $items, 'notification-workflow' ),
            ],
        ];

        return [
            "notices"   =>  $notices,
            "groups"    =>  $groups,
            "items"     =>  $items
        ];
    }

    public static function filterItemsByCategroy( $items, $category ) {
        if ( ! is_array( $items ) ) {
            return array();
        }
        return array_filter( $items, function( $item ) use ($category) {
            return array_filter( $item['categories'], function( $itemCategory ) use ($category){
                return $category === $itemCategory['slug'];
            });
        });
    }

    /**
     * Admin notice shown when the cached add-ons feed is corrupted/unreadable
     * and we've fallen back to the bundled add-ons list.
     */
    public function render_feed_unavailable_notice() {
        // The dashboard page's own stylesheet hides any direct child of #wpbody-content
        // that isn't the React app's ".wrap" root, so this notice needs the "wrap" class
        // to stay visible on this specific page.
        ?>
        <div class="wrap">
            <div class="notice notice-warning">
                <p><?php esc_html_e( 'The Ninja Forms add-ons feed is temporarily unavailable. Showing the default add-ons list below.', 'ninja-forms' ); ?></p>
            </div>
        </div>
        <?php
    }

    public static function getItemStatus( $item ) {
        $status = "unknown";
        
        if( ! empty( $item['plugin'] ) && file_exists( WP_PLUGIN_DIR.'/'.$item['plugin'] ) ) {

            if( is_plugin_active( $item['plugin'] ) ) {
                $status =  'active';
            } elseif( is_plugin_inactive( $item['plugin'] ) ) {
                $status = "installed";
            }

        } 
        return $status;
    }

} // End Class NF_Admin_Addons
