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
        // If we got back nothing...
        if ( ! $saved ) {
            // Default to the in-app file.
            $items = file_get_contents( Ninja_Forms::$dir . '/lib/Legacy/addons-feed.json' );
            $items = json_decode( $items, true );
        } // Otherwise... (We did get something from the db.)
        else {
            // Use the data we fetched.
            $items = json_decode( $saved, true );
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
        return array_filter( $items, function( $item ) use ($category) {
            return array_filter( $item['categories'], function( $itemCategory ) use ($category){
                return $category === $itemCategory['slug'];
            });
        });
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
