<?php
/**
 * Handles the auditing of navigation menu events.
 *
 * @package WP_Defender\Component\Audit
 */

namespace WP_Defender\Component\Audit;

use Calotes\Helper\Array_Cache;
use WP_Defender\Model\Audit_Log;

/**
 * Handles the auditing of navigation menu events such as menu creation.
 *
 * @author  Shoaib Ali
 * @since   2.6.1
 */
class Menu_Audit extends Audit_Event {

	public const ACTION_CREATED = 'created';
	/**
	 * Type of audit.
	 *
	 * @var string
	 */
	protected $type = 'Navigation Menu';

	/**
	 * Menu_Audit constructor.
	 * Hooks into the admin_init action to cache the old menu state.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'cache_old_menu' ) );
	}

	/**
	 * Cache old menu before updating.
	 */
	public function cache_old_menu() {
		$script_name = filter_var( defender_get_data_from_request( 'SCRIPT_NAME', 's' ), FILTER_SANITIZE_URL );
		if ( filter_input( INPUT_POST, 'action' ) ) {
			$action_type = filter_input( INPUT_POST, 'action' );
		} else {
			$action_type = filter_input( INPUT_GET, 'action' );
		}
		if ( filter_input( INPUT_POST, 'menu' ) ) {
			$term_id = filter_input( INPUT_POST, 'menu' );
		} else {
			$term_id = filter_input( INPUT_GET, 'menu' );
		}

		if (
			'nav-menus.php' === basename( $script_name )
			&& ! empty( $action_type )
			&& ! empty( $term_id )
		) {
			Array_Cache::append(
				'menu_updated',
				$this->build_menu( $term_id ),
				'audit'
			);
		}
	}

	/**
	 * Constructs an array representing the menu structure based on the provided term ID.
	 *
	 * @param  mixed $term_id  The ID of the menu.
	 *
	 * @return array
	 */
	public function build_menu( $term_id ): array {
		$menu     = array();
		$menu_obj = wp_get_nav_menu_object( $term_id );

		if ( ! empty( $menu_obj ) ) {
			$menu['term_id'] = $menu_obj->term_id;
			$menu['name']    = $menu_obj->name;
			$menu['items']   = array();

			$items = wp_get_nav_menu_items( $menu_obj->term_id );
			if ( ! empty( $items ) ) {
				foreach ( $items as $item ) {
					array_push(
						$menu['items'],
						array(
							'item_id'          => $item->ID,
							'title'            => $item->title,
							'url'              => $item->url,
							'object'           => $item->object,
							'menu_order'       => $item->menu_order,
							'menu_item_parent' => $item->menu_item_parent,
						)
					);
				}
			}
		}

		return $menu;
	}

	/**
	 * Returns an array of hooks associated with various menu actions for auditing purposes.
	 *
	 * @return array
	 */
	public function get_hooks(): array {
		return array(
			'wp_create_nav_menu'      => array(
				'args'        => array( 'term_id', 'menu_data' ),
				'callback'    => array( self::class, 'menu_created_callback' ),
				'event_type'  => Audit_Log::EVENT_TYPE_MENU,
				'action_type' => self::ACTION_CREATED,
			),
			'wp_update_nav_menu'      => array(
				'args'        => array( 'menu_id', 'menu_data' ),
				'callback'    => array( self::class, 'menu_updated_callback' ),
				'event_type'  => Audit_Log::EVENT_TYPE_MENU,
				'action_type' => self::ACTION_UPDATED,
			),
			'wp_delete_nav_menu'      => array(
				'args'        => array( 'term_id' ),
				'callback'    => array( self::class, 'menu_deleted_callback' ),
				'event_type'  => Audit_Log::EVENT_TYPE_MENU,
				'action_type' => self::ACTION_DELETED,
			),
			'wp_update_nav_menu_item' => array(
				'args'        => array( 'menu_id', 'menu_item_db_id', 'args' ),
				'callback'    => array( self::class, 'menu_item_updated_callback' ),
				'event_type'  => Audit_Log::EVENT_TYPE_MENU,
				'action_type' => self::ACTION_UPDATED,
			),
			'delete_post'             => array(
				'args'        => array( 'post_ID' ),
				'callback'    => array( self::class, 'menu_item_deleted_callback' ),
				'event_type'  => Audit_Log::EVENT_TYPE_MENU,
				'action_type' => self::ACTION_DELETED,
			),
		);
	}

	/**
	 * Callback for when a menu item is updated. It checks if the update is significant for logging.
	 *
	 * @return array|bool
	 */
	public function menu_item_updated_callback() {
		if ( $this->doing_autosave() ) {
			return false;
		}

		// Filter $_POST.
		$post_array = filter_input_array( INPUT_POST );

		if ( ! isset( $post_array['menu-name'] ) ) {
			return false;
		}

		$func_args       = func_get_args();
		$menu_id         = $func_args[1]['menu_id'];
		$menu_item_db_id = $func_args[1]['menu_item_db_id'];
		$args            = $func_args[1]['args'];
		$blog_name       = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		$old_menu        = $this->get_cached_menu( $menu_id );

		if ( ! empty( $old_menu ) ) {
			$old_items = array_column( $old_menu['items'], 'item_id' );
			$new_items = array_keys( $post_array['menu-item-title'] );

			if ( ! empty( $old_menu['items'] ) ) {
				foreach ( $old_menu['items'] as $old_item ) {
					if ( $menu_item_db_id === $old_item['item_id'] ) {
						if (
							$old_item['menu_order'] !== $args['menu-item-position']
							|| $old_item['menu_item_parent'] !== $args['menu-item-parent-id']
							|| ( ! empty( $args['menu-item-title'] ) && $old_item['title'] !== $args['menu-item-title'] )
						) {
							return array(
								sprintf(
								/* translators: 1: Blog name, 2: User's display name, 3: Menu item, 4: Menu name */
									esc_html__( '%1$s %2$s updated item "%3$s" from menu "%4$s"', 'wpdef' ),
									$blog_name,
									$this->get_user_display( get_current_user_id() ),
									$post_array['menu-item-title'][ $menu_item_db_id ],
									$post_array['menu-name']
								),
								$this->type,
							);
						}

						break;
					}
				}
			}

			$added_items = array_diff( $new_items, $old_items );
			if ( in_array( $menu_item_db_id, $added_items, true ) ) {
				return array(
					sprintf(
					/* translators: 1: Blog name, 2: User's display name, 3: Menu item, 4: Menu name */
						esc_html__( '%1$s %2$s added item "%3$s" to menu "%4$s"', 'wpdef' ),
						$blog_name,
						$this->get_user_display( get_current_user_id() ),
						$post_array['menu-item-title'][ $menu_item_db_id ],
						$post_array['menu-name']
					),
					$this->type,
				);
			}
		}

		return false;
	}

	/**
	 * Callback for when a menu item is deleted. It logs the deletion if the item existed in the cached menu.
	 *
	 * @return array|bool
	 */
	public function menu_item_deleted_callback() {
		$post_array = filter_input_array( INPUT_POST );

		if ( ! isset( $post_array['menu-name'] ) ) {
			return false;
		}

		$func_args       = func_get_args();
		$menu_item_db_id = $func_args[1]['post_ID'];
		$menu_id         = (int) $post_array['menu'];
		$blog_name       = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		$old_menu        = $this->get_cached_menu( $menu_id );

		if ( ! empty( $old_menu ) && is_array( $old_menu ) ) {
			$old_items = array_column( $old_menu['items'], 'item_id' );
			if ( in_array( $menu_item_db_id, $old_items, true ) ) {
				$key = array_search( $menu_item_db_id, $old_items, true );

				return array(
					sprintf(
					/* translators: 1: Blog name, 2: User's display name, 3: Menu item, 4: Menu name */
						esc_html__( '%1$s %2$s removed item "%3$s" from menu "%4$s"', 'wpdef' ),
						$blog_name,
						$this->get_user_display( get_current_user_id() ),
						$old_menu['items'][ $key ]['title'],
						$post_array['menu-name']
					),
					$this->type,
				);
			}
		}

		return false;
	}

	/**
	 * Callback for when a new menu is created. It logs the creation event.
	 *
	 * @return array
	 */
	public function menu_created_callback(): array {
		$func_args = func_get_args();
		$menu_data = $func_args[1]['menu_data'];
		$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';

		return array(
			sprintf(
			/* translators: 1: Blog name, 2: User's display name, 3: Menu name */
				esc_html__( '%1$s %2$s created a new menu "%3$s"', 'wpdef' ),
				$blog_name,
				$this->get_user_display( get_current_user_id() ),
				$menu_data['menu-name']
			),
			$this->type,
		);
	}

	/**
	 * Callback for when a menu is updated. It logs the update if there are changes to the menu structure.
	 *
	 * @return array|bool
	 */
	public function menu_updated_callback() {
		$func_args  = func_get_args();
		$menu_id    = $func_args[1]['menu_id'];
		$menu_data  = $func_args[1]['menu_data'];
		$post_array = filter_input_array( INPUT_POST );
		$new_items  = isset( $post_array['menu-item-title'] ) ? array_keys( $post_array['menu-item-title'] ) : array();
		$old_menu   = $this->get_cached_menu( $menu_id );
		$old_items  = array_column( $old_menu['items'], 'item_id' );

		sort( $new_items );
		sort( $old_items );
		if ( $new_items === $old_items ) {
			// Check if item title, position or parent is changed.
			$is_any_item_changed = false;
			foreach ( $old_menu['items'] as $old_items ) {
				if (
					$old_items['title'] !== $post_array['menu-item-title'][ $old_items['item_id'] ]
					|| $old_items['menu_order'] !== (int) $post_array['menu-item-position'][ $old_items['item_id'] ]
					|| $old_items['menu_item_parent'] !== $post_array['menu-item-parent-id'][ $old_items['item_id'] ]
				) {
					$is_any_item_changed = true;
					break;
				}
			}

			if ( false === $is_any_item_changed ) {
				$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';

				return array(
					sprintf(
					/* translators: 1: Blog name, 2: User's display name, 3: Menu name */
						esc_html__( '%1$s %2$s updated menu "%3$s"', 'wpdef' ),
						$blog_name,
						$this->get_user_display( get_current_user_id() ),
						$menu_data['menu-name']
					),
					$this->type,
				);
			}
		}

		return false;
	}

	/**
	 * Callback for when a menu is deleted. It logs the deletion event.
	 *
	 * @return array
	 */
	public function menu_deleted_callback(): array {
		$func_args = func_get_args();
		$menu_id   = $func_args[1]['term_id'];
		$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		$old_menu  = $this->get_cached_menu( $menu_id );

		return array(
			sprintf(
			/* translators: 1: Blog name, 2: User's display name, 3: Menu name */
				esc_html__( '%1$s %2$s deleted menu "%3$s"', 'wpdef' ),
				$blog_name,
				$this->get_user_display( get_current_user_id() ),
				$old_menu['name']
			),
			$this->type,
		);
	}

	/**
	 * Retrieves a cached menu from the runtime cache based on the term ID.
	 *
	 * @param  int $term_id  Menu term ID.
	 *
	 * @return bool|array
	 */
	public function get_cached_menu( $term_id ) {
		$cached = Array_Cache::get( 'menu_updated', 'audit', array() );
		foreach ( $cached as $menu_arr ) {
			if ( $term_id === $menu_arr['term_id'] ) {
				return $menu_arr;
			}
		}

		return false;
	}

	/**
	 * Checks if the current request is doing an auto save.
	 *
	 * @return bool
	 */
	public function doing_autosave(): bool {
		return defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE;
	}
}