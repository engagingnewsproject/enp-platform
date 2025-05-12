<?php
/**
 * Base controller.
 *
 * @package Calotes\Base
 */

namespace Calotes\Base;

use ReflectionClass;
use WP_Defender\Component\Hub_Connector;

/**
 * This class use for:
 *  1. Register admin page.
 *  2. Register sub-page.
 *  3. Help to queue scripts & output script data for frontend.
 *  4. Render frontend view.
 */
class Controller extends Component {

	/**
	 * The slug for the page.
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * The layout for the page.
	 *
	 * @var string|null
	 */
	protected $layout = null;

	/**
	 * Registers a page or subpage in the admin menu.
	 *
	 * @param  string      $title  The title of the page.
	 * @param  string      $slug  The slug for the page.
	 * @param  callable    $callback  The callback function to handle the page content.
	 * @param  string|null $parent_slug  Optional. The slug of the parent page. Default is null.
	 * @param  string|null $icon  Optional. The icon for the menu item. Default is null.
	 * @param  string      $menu_title  Optional. The title for the menu item. Default is an empty string.
	 */
	public function register_page( $title, $slug, $callback, $parent_slug = null, $icon = null, $menu_title = '' ) {
		$hook       = is_multisite() ? 'network_admin_menu' : 'admin_menu';
		$menu_title = '' !== $menu_title ? $menu_title : $title;
		$function   = function () use ( $title, $slug, $callback, $parent_slug, $icon, $menu_title ) {
			$cap = is_multisite() ? 'manage_network_options' : 'manage_options';
			if ( null === $parent_slug ) {
				$page_hook = add_menu_page( $title, $menu_title, $cap, $slug, $callback, $icon );
			} else {
				$page_hook = add_submenu_page( $parent_slug, $title, $menu_title, $cap, $slug, $callback );
			}
			add_action( 'load-' . $page_hook, array( $this, 'trigger_load_action' ) );
		};

		add_action( $hook, $function );
	}

	/**
	 * Redirects to the Onboard page only on single sites or network admin.
	 */
	public function trigger_load_action() {
		$redirect_slug = 'wp-defender';
		if (
			$redirect_slug !== $this->slug
			&& true !== (bool) get_site_option( 'wp_defender_shown_activator' )
		) {
			// Redirect to the Onboard page only on single sites or network admin.
			wp_safe_redirect( network_admin_url( 'admin.php?page=' . $redirect_slug ) );
			exit;
		}
	}

	/**
	 * Render a view file.
	 *
	 * @param  mixed $view_file  The view file to render.
	 * @param  array $params  Optional. The parameters to pass to the view file. Default is an empty array.
	 * @param  bool  $output  Optional. Whether to output the rendered content or return it. Default is true.
	 *
	 * @return void|bool|string If $output is false, the rendered content. Otherwise, true on success, false on failure.
	 */
	public function render( $view_file, $params = array(), $output = true ) {
		$stop_further = $this->check_has_server_error();

		if ( $stop_further ) {
			return false;
		}
		if ( Hub_Connector::should_render() ) {
			wp_dequeue_script( 'def-iplockout' );
			// Custimize the text.
			add_filter(
				'wpmudev_hub_connector_localize_text_vars',
				array( 'WP_Defender\Component\Hub_Connector', 'customize_text_vars' ),
				10,
				2
			);
			do_action( 'wpmudev_hub_connector_ui', Hub_Connector::PLUGIN_IDENTIFIER );
			return;
		}

		$base_path = $this->get_base_path();
		$view      = new View( $base_path . 'view' );
		// Assign controller to this.
		if ( ! isset( $params['controller'] ) ) {
			$params['controller'] = $this;
		}
		if ( is_array( $view_file ) ) {
			$content = '';
			foreach ( $view_file as $vf ) {
				$content .= $view->render( $vf, $params );
			}
		} else {
			$content = $view->render( $view_file, $params );
		}

		if ( ! empty( $this->layout ) ) {
			$template = new View( $base_path . 'view' . DIRECTORY_SEPARATOR . 'layouts' );
			$content  = $template->render(
				$this->layout,
				array_merge(
					$params,
					array(
						'controller' => $this,
						'contents'   => $content,
					)
				)
			);
		}
		if ( false === $output ) {
			return $content;
		}

		echo wp_kses_post( $content );
	}

	/**
	 * This will guess the called class path, and return the base.
	 *
	 * @return bool|string
	 */
	private function get_base_path() {
		$reflector = new ReflectionClass( get_called_class() );
		$base_path = dirname( $reflector->getFileName(), 2 );

		if ( is_dir( $base_path . DIRECTORY_SEPARATOR . 'controller' )
			&& is_dir( $base_path . DIRECTORY_SEPARATOR . 'view' )
		) {
			return $base_path . DIRECTORY_SEPARATOR;
		}

		return false;
	}

	/**
	 * Render a partial view file.
	 *
	 * @param  mixed $view_file  The view file to render.
	 * @param  array $params  Optional. The parameters to pass to the view file. Default is an empty array.
	 * @param  bool  $output  Optional. Whether to output the rendered content or return it. Default is true.
	 *
	 * @return string The rendered content.
	 */
	public function render_partial( $view_file, $params = array(), $output = true ) {
		$base_path = $this->get_base_path();

		if ( ! isset( $params['controller'] ) ) {
			$params['controller'] = $this;
		}

		$view = new View( $base_path . 'view' );
		if ( is_array( $view_file ) ) {
			$content = '';
			foreach ( $view_file as $vf ) {
				$content .= $view->render( $vf, $params );
			}
		} else {
			$content = $view->render( $view_file, $params );
		}
		if ( true === $output ) {
			/**
			 * Ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			 * Why?
			 * Because $content has scripts that will be broken if we escape it.
			 */
			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return $content;
	}

	/**
	 * Check for has server not found error.
	 *
	 * @return bool
	 */
	private function check_has_server_error(): bool {
		global $defender_server_not_supported;

		if ( is_wp_error( $defender_server_not_supported ) ) {
			$html  = '<div class="sui-wrap"><div class="sui-notice sui-notice-info">';
			$html .= '<div class="sui-notice-content">';
			$html .= '<div class="sui-notice-message">';
			$html .= '<p>' . $defender_server_not_supported->get_error_message() . '</p>';
			$html .= '</div></div></div></div>';

			echo wp_kses_post( $html );

			return true;
		}

		return false;
	}
}