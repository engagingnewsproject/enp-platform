<?php
/**
 * Abstract class for the admin pages.
 *
 * @package Hummingbird\Admin
 */

namespace Hummingbird\Admin;

use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;
use Hummingbird\WP_Hummingbird;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Page
 */
abstract class Page {

	/**
	 * Page slug.g
	 *
	 * @var string
	 */
	protected $slug = '';

	/**
	 * Meta boxes array.
	 *
	 * @var array
	 */
	protected $meta_boxes = array();

	/**
	 * Submenu tabs.
	 *
	 * @var array
	 */
	protected $tabs = array();

	/**
	 * Page ID.
	 *
	 * @var false|null|string
	 */
	public $page_id = null;

	/**
	 * Admin notices.
	 *
	 * @var Notices
	 */
	protected $admin_notices;

	/**
	 * Membership status.
	 *
	 * @var bool
	 *
	 * @used-by views/meta-box.php
	 */
	private $is_pro;

	/**
	 * Admin_Page constructor.
	 *
	 * @param string $slug        Module slug.
	 * @param string $page_title  Page title.
	 * @param string $menu_title  Menu title.
	 * @param bool   $parent      Parent or not.
	 * @param bool   $render      Render the page.
	 */
	public function __construct( $slug, $page_title, $menu_title, $parent = false, $render = true ) {
		$this->slug = $slug;

		$this->admin_notices = Notices::get_instance();

		$this->is_pro = Utils::is_member();

		if ( ! $parent ) {
			$this->page_id = add_menu_page(
				$page_title,
				$menu_title,
				Utils::get_admin_capability(),
				$slug,
				$render ? array( $this, 'render' ) : null,
				$this->get_menu_icon()
			);
		} else {
			$this->page_id = add_submenu_page(
				$parent,
				$page_title,
				$menu_title,
				Utils::get_admin_capability(),
				$slug,
				$render ? array( $this, 'render' ) : null
			);
		}

		if ( $render ) {
			add_action( 'load-' . $this->page_id, array( $this, 'register_meta_boxes' ) );
			add_action( 'load-' . $this->page_id, array( $this, 'on_load' ) );
			add_action( 'load-' . $this->page_id, array( $this, 'trigger_load_action' ) );
			add_filter( 'load-' . $this->page_id, array( $this, 'add_screen_hooks' ) );
		}
	}

	/**
	 * Return the admin menu slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Trigger an action before this screen is loaded
	 */
	public function trigger_load_action() {
		do_action( 'wphb_load_admin_page_' . $this->get_slug() );

		// Check that the library is available.
		if ( Utils::is_member() || ! file_exists( WPHB_DIR_PATH . 'core/externals/plugin-notice/notice.php' ) ) {
			return;
		}

		/* @noinspection PhpIncludeInspection */
		include_once WPHB_DIR_PATH . 'core/externals/plugin-notice/notice.php';
		do_action( 'wpmudev-recommended-plugins-register-notice', WPHB_BASENAME, __( 'Hummingbird', 'wphb' ), Admin::$admin_pages );
	}

	/**
	 * Load an admin view.
	 *
	 * @param string $name  View name = file name.
	 * @param array  $args  Arguments.
	 * @param bool   $echo  Echo or return.
	 *
	 * @return string
	 */
	public function view( $name, $args = array(), $echo = true ) {
		$file    = WPHB_DIR_PATH . "admin/views/{$name}.php";
		$content = '';

		if ( is_file( $file ) ) {
			ob_start();

			if ( isset( $args['id'] ) ) {
				$args['orig_id'] = $args['id'];
				$args['id']      = str_replace( '/', '-', $args['id'] );
			}
			extract( $args );

			/* @noinspection PhpIncludeInspection */
			include $file;

			$content = ob_get_clean();
		}

		if ( ! $echo ) {
			return $content;
		}

		echo $content;
	}

	/**
	 * Get modal file by type.
	 *
	 * @param string $type Accepts: bulk-update, check-files, check-performance, dismiss-report, membership,
	 *                     minification-advanced, minification-basic, quick-setup, database-cleanup.
	 */
	public function modal( $type ) {
		if ( empty( $type ) ) {
			return;
		}

		$type = strtolower( $type );
		$file = WPHB_DIR_PATH . "admin/modals/{$type}-modal.php";

		if ( file_exists( $file ) ) {
			/* @noinspection PhpIncludeInspection */
			include_once $file;
		}
	}

	/**
	 * Check if view exists.
	 *
	 * @param string $name  View name = file name.
	 *
	 * @return bool
	 */
	protected function view_exists( $name ) {
		$file = WPHB_DIR_PATH . "admin/views/{$name}.php";
		return is_file( $file );
	}

	/**
	 * Common hooks for all screens
	 */
	public function add_screen_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );
		add_action( 'network_admin_notices', array( $this, 'notices' ) );

		// Add the admin body classes.
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
	}

	/**
	 * Return the classes to be added to the body in the admin.
	 *
	 * @param String $classes Classes to be added.
	 * @return String
	 */
	public function admin_body_class( $classes ) {
		$classes .= ' ' . WPHB_SUI_VERSION . ' wpmud ';

		return $classes;
	}

	/**
	 * Notices.
	 */
	public function notices() {}

	/**
	 * Function triggered when the page is loaded before render any content
	 */
	public function on_load() {}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook  Hook from where the call is made.
	 */
	public function enqueue_scripts( $hook ) {
		// Styles.
		wp_enqueue_style( 'wphb-admin', WPHB_DIR_URL . 'admin/assets/css/wphb-app.min.css', array(), WPHB_VERSION );

		// Scripts.
		wp_enqueue_script(
			'wphb-wpmudev-sui',
			WPHB_DIR_URL . 'admin/assets/js/wphb-sui.min.js',
			array( 'jquery', 'clipboard' ),
			WPHB_VERSION,
			true
		);
		Utils::enqueue_admin_scripts( WPHB_VERSION );

		// Google visualization library for Uptime and Historic Field Data Page.
		// @see https://core.trac.wordpress.org/ticket/18857 for explanation on why.
		if ( sanitize_title( __( 'Hummingbird Pro', 'wphb' ) ) . '_page_wphb-uptime' === $hook || 'main' === $this->get_current_tab() ) {
			wp_enqueue_script( 'wphb-google-chart', 'https://www.gstatic.com/charts/loader.js', array(), WPHB_VERSION, true );
		}

		// Enqueue Color picker.
		if ( 'lazy' === $this->get_current_tab() ) {
			wp_enqueue_script( 'wp-color-picker-alpha', WPHB_DIR_URL . 'admin/assets/dist/wp-color-picker-alpha.min.js', array( 'wp-color-picker' ), WPHB_VERSION, true );
			wp_enqueue_style( 'wp-color-picker' );
		}
	}

	/**
	 * Trigger before on_load, allows to register meta boxes for the page
	 */
	public function register_meta_boxes() {}

	/**
	 * Add meta box.
	 *
	 * @param string   $id               Meta box ID.
	 * @param string   $title            Meta box title.
	 * @param callable $callback         Callback for meta box content.
	 * @param callable $callback_header  Callback for meta box header.
	 * @param callable $callback_footer  Callback for meta box footer.
	 * @param string   $context          Meta box context.
	 * @param array    $args             Arguments.
	 */
	public function add_meta_box( $id, $title, $callback = null, $callback_header = null, $callback_footer = null, $context = 'main', $args = array() ) {
		$default_args = array(
			'box_class'         => 'sui-box',
			'box_header_class'  => 'sui-box-header',
			'box_content_class' => 'sui-box-body',
			'box_footer_class'  => 'sui-box-footer',
		);

		$args = wp_parse_args( $args, $default_args );

		if ( ! isset( $this->meta_boxes[ $this->slug ] ) ) {
			$this->meta_boxes[ $this->slug ] = array();
		}

		if ( ! isset( $this->meta_boxes[ $this->slug ][ $context ] ) ) {
			$this->meta_boxes[ $this->slug ][ $context ] = array();
		}

		$meta_box = array(
			'id'              => $id,
			'title'           => $title,
			'callback'        => $callback,
			'callback_header' => $callback_header,
			'callback_footer' => $callback_footer,
			'args'            => $args,
		);

		/**
		 * Allow to filter a WP Hummingbird Metabox
		 *
		 * @param array $meta_box Meta box attributes
		 * @param string $slug Admin page slug
		 * @param string $page_id Admin page ID
		 */
		$meta_box = apply_filters( 'wphb_add_meta_box', $meta_box, $this->slug, $this->page_id );
		$meta_box = apply_filters( 'wphb_add_meta_box_' . $meta_box['id'], $meta_box, $this->slug, $this->page_id );

		if ( $meta_box ) {
			$this->meta_boxes[ $this->slug ][ $context ][ $id ] = $meta_box;
		}
	}

	/**
	 * Render meta box.
	 *
	 * @param string $context  Meta box context. Default: main.
	 */
	protected function do_meta_boxes( $context = 'main' ) {
		if ( empty( $this->meta_boxes[ $this->slug ][ $context ] ) ) {
			return;
		}

		do_action_ref_array( 'wphb_admin_do_meta_boxes_' . $this->slug, array( &$this ) );

		foreach ( $this->meta_boxes[ $this->slug ][ $context ] as $id => $box ) {
			$args = array(
				'title'           => $box['title'],
				'id'              => $id,
				'callback'        => $box['callback'],
				'callback_header' => $box['callback_header'],
				'callback_footer' => $box['callback_footer'],
				'args'            => $box['args'],
			);
			$this->view( 'meta-box', $args );
		}
	}

	/**
	 * Check if there is any meta box for a given context.
	 *
	 * @param string $context  Meta box context.
	 *
	 * @return bool
	 */
	protected function has_meta_boxes( $context ) {
		return ! empty( $this->meta_boxes[ $this->slug ][ $context ] );
	}

	/**
	 * Renders the template header that is repeated on every page.
	 * From WPMU DEV Dashboard
	 */
	protected function render_header() {
		?>
		<div class="sui-header">
			<h1 class="sui-header-title"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div class="sui-actions-right">
				<?php do_action( 'wphb_sui_header_sui_actions_right' ); ?>
				<?php if ( ! apply_filters( 'wpmudev_branding_hide_doc_link', false ) ) : ?>
					<a href="<?php echo esc_url( Utils::get_documentation_url( $this->slug, $this->get_current_tab() ) ); ?>" target="_blank" class="sui-button sui-button-ghost">
						<span class="sui-icon-academy" aria-hidden="true"></span>
						<?php esc_html_e( 'View Documentation', 'wphb' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="sui-floating-notices">
			<div role="alert" id="wphb-ajax-update-notice" class="sui-notice" aria-live="assertive"></div>
			<?php do_action( 'wphb_sui_floating_notices' ); ?>
		</div>
		<?php
	}

	/**
	 * Render the page
	 */
	public function render() {
		$settings = Settings::get_settings( 'settings' );
		?>
		<div class="sui-wrap<?php echo $settings['accessible_colors'] ? ' sui-color-accessible ' : ' '; ?>wrap-wp-hummingbird wrap-wp-hummingbird-page <?php echo 'wrap-' . esc_attr( $this->slug ); ?>">
			<?php
			if ( filter_input( INPUT_GET, 'updated', FILTER_UNSAFE_RAW ) ) {
				$this->admin_notices->show_floating( apply_filters( 'wphb_update_notice_text', __( 'Your changes have been saved.', 'wphb' ) ) );
			}
			$this->render_header();
			$this->render_inner_content();
			$this->render_footer();
			$this->render_modals();
			?>
		</div><!-- end container -->
		<?php
	}

	/**
	 * Render inner content.
	 */
	protected function render_inner_content() {
		$this->view( $this->slug . '-page' );
	}

	/**
	 * Render footer.
	 */
	protected function render_footer() {
		$hide_footer = false;
		$footer_text = sprintf(
			/* translators: %s - icon */
			esc_html__( 'Made with %s by WPMU DEV', 'wphb' ),
			'<span aria-hidden="true" class="sui-icon-heart"></span>'
		);

		if ( Utils::is_member() ) {
			$hide_footer = apply_filters( 'wpmudev_branding_change_footer', $hide_footer );
			$footer_text = apply_filters( 'wpmudev_branding_footer_text', $footer_text );
		}
		?>
		<div class="sui-footer">
			<?php
			// @codingStandardsIgnoreStart
			echo $footer_text;
			// @codingStandardsIgnoreEnd
			?>
		</div>

		<?php if ( Utils::is_member() ) : ?>

			<?php if ( ! $hide_footer ) : ?>
				<ul class="sui-footer-nav">
					<li><a href="https://wpmudev.com/hub2/" target="_blank"><?php esc_html_e( 'The Hub', 'wphb' ); ?></a></li>
					<li><a href="https://wpmudev.com/projects/category/plugins/" target="_blank"><?php esc_html_e( 'Plugins', 'wphb' ); ?></a></li>
					<li><a href="https://wpmudev.com/roadmap/" target="_blank"><?php esc_html_e( 'Roadmap', 'wphb' ); ?></a></li>
					<li><a href="https://wpmudev.com/hub2/support/" target="_blank"><?php esc_html_e( 'Support', 'wphb' ); ?></a></li>
					<li><a href="https://wpmudev.com/docs/" target="_blank"><?php esc_html_e( 'Docs', 'wphb' ); ?></a></li>
					<li><a href="https://wpmudev.com/hub2/community/" target="_blank"><?php esc_html_e( 'Community', 'wphb' ); ?></a></li>
					<li><a href="https://wpmudev.com/terms-of-service/" target="_blank"><?php esc_html_e( 'Terms of Service', 'wphb' ); ?></a></li>
					<li><a href="https://incsub.com/privacy-policy/" target="_blank"><?php esc_html_e( 'Privacy Policy', 'wphb' ); ?></a></li>
				</ul>
			<?php endif; ?>

		<?php else : ?>

			<ul class="sui-footer-nav">
				<li><a href="https://profiles.wordpress.org/wpmudev#content-plugins" target="_blank"><?php esc_html_e( 'Free Plugins', 'wphb' ); ?></a></li>
				<li><a href="https://wpmudev.com/features/" target="_blank"><?php esc_html_e( 'Membership', 'wphb' ); ?></a></li>
				<li><a href="https://wpmudev.com/roadmap/" target="_blank"><?php esc_html_e( 'Roadmap', 'wphb' ); ?></a></li>
				<li><a href="https://wordpress.org/support/plugin/hummingbird-performance" target="_blank"><?php esc_html_e( 'Support', 'wphb' ); ?></a></li>
				<li><a href="https://wpmudev.com/docs/" target="_blank"><?php esc_html_e( 'Docs', 'wphb' ); ?></a></li>
				<li><a href="https://wpmudev.com/hub-welcome/" target="_blank"><?php esc_html_e( 'The Hub', 'wphb' ); ?></a></li>
				<li><a href="https://wpmudev.com/terms-of-service/" target="_blank"><?php esc_html_e( 'Terms of Service', 'wphb' ); ?></a></li>
				<li><a href="https://incsub.com/privacy-policy/" target="_blank"><?php esc_html_e( 'Privacy Policy', 'wphb' ); ?></a></li>
			</ul>

		<?php endif; ?>

		<?php if ( ! $hide_footer ) : ?>
			<ul class="sui-footer-social">
				<li><a href="https://www.facebook.com/wpmudev" target="_blank">
						<span class="sui-icon-social-facebook" aria-hidden="true"></span>
						<span class="sui-screen-reader-text">Facebook</span>
					</a></li>
				<li><a href="https://twitter.com/wpmudev" target="_blank">
						<span class="sui-icon-social-twitter" aria-hidden="true"></span>
						<span class="sui-screen-reader-text">Twitter</span>
					</a></li>
				<li><a href="https://www.instagram.com/wpmu_dev/" target="_blank">
						<span class="sui-icon-instagram" aria-hidden="true"></span>
						<span class="sui-screen-reader-text">Instagram</span>
					</a></li>
			</ul>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render modals.
	 *
	 * @since 2.6.0
	 */
	protected function render_modals() {
		$show = false;

		if ( is_multisite() && ( is_network_admin() || is_super_admin() ) ) {
			$this->modal( 'clear-cache-network-wide' );
		}

		if ( WP_Hummingbird::get_instance()->admin->show_quick_setup ) {
			$show  = true;
			$modal = 'tracking-modal';
			$this->modal( 'tracking' );
			$this->modal( 'check-performance-onboard' );
		} elseif ( WP_Hummingbird::get_instance()->admin->show_upgrade_summary ) {
			$show  = true;
			$modal = 'upgrade-summary-modal';
			$this->modal( 'upgrade-summary' );
		}

		if ( ! $show ) {
			return;
		}
		?>
		<script>
			window.addEventListener("load", function(){
				<?php if ( WP_Hummingbird::get_instance()->admin->show_quick_setup ) : ?>
					window.WPHB_Admin.getModule( 'dashboard' );
				<?php endif; ?>
				window.SUI.openModal( '<?php echo esc_attr( $modal ); ?>', 'wpbody-content', undefined, false );
			});
		</script>
		<?php
	}

	/**
	 * Return this menu page URL
	 *
	 * @return string
	 */
	public function get_page_url() {
		if ( is_multisite() && is_network_admin() ) {
			global $_parent_pages;

			if ( isset( $_parent_pages[ $this->slug ] ) ) {
				$parent_slug = $_parent_pages[ $this->slug ];
				if ( $parent_slug && ! isset( $_parent_pages[ $parent_slug ] ) ) {
					$url = network_admin_url( add_query_arg( 'page', $this->slug, $parent_slug ) );
				} else {
					$url = network_admin_url( 'admin.php?page=' . $this->slug );
				}
			} else {
				$url = '';
			}

			return esc_url( $url );
		}

		return menu_page_url( $this->slug, false );
	}

	/**
	 * Get the current screen tab
	 *
	 * @return string
	 */
	public function get_current_tab() {
		$tabs = $this->get_tabs();
		$view = filter_input( INPUT_GET, 'view', FILTER_UNSAFE_RAW );
		$view = sanitize_text_field( $view );

		if ( $view && array_key_exists( $view, $tabs ) ) {
			return $view;
		}

		if ( empty( $tabs ) ) {
			return false;
		}

		reset( $tabs );
		return key( $tabs );
	}

	/**
	 * Get the list of tabs for this screen
	 *
	 * @return array
	 */
	protected function get_tabs() {
		return apply_filters( 'wphb_admin_page_tabs_' . $this->slug, $this->tabs );
	}

	/**
	 * Display tabs navigation
	 */
	public function show_tabs() {
		$this->view(
			'tabs',
			array(
				'tabs' => $this->get_tabs(),
			)
		);
	}

	/**
	 * Show flat (horizontal) layout tabs.
	 *
	 * @since 3.0.0
	 */
	public function show_tabs_flat() {
		$this->view(
			'tabs-flat',
			array(
				'tabs' => $this->get_tabs(),
			)
		);
	}

	/**
	 * Get a tab URL
	 *
	 * @param string $tab  Tab ID.
	 *
	 * @return string
	 */
	public function get_tab_url( $tab ) {
		$tabs = $this->get_tabs();
		if ( ! isset( $tabs[ $tab ] ) ) {
			return '';
		}

		if ( is_multisite() && is_network_admin() ) {
			return network_admin_url( 'admin.php?page=' . $this->slug . '&view=' . $tab );
		} else {
			return admin_url( 'admin.php?page=' . $this->slug . '&view=' . $tab );
		}
	}

	/**
	 * Return the name of a tab
	 *
	 * @param string $tab  Tab ID.
	 *
	 * @return mixed|string
	 */
	public function get_tab_name( $tab ) {
		$tabs = $this->get_tabs();
		if ( ! isset( $tabs[ $tab ] ) ) {
			return '';
		}

		return $tabs[ $tab ];
	}

	/**
	 * Hummingbird icon svg image.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function get_menu_icon() {
		ob_start();
		?>
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M12.7326 3.2L13.9481 4.05L13.2596 10.2295L10.9901 13.2555L10.7606 13.5615L10.6841 13.944L10.2422 16.137L8.27018 15.287L10.2761 12.7795L10.5481 12.4395L10.6246 12.006L11.4348 7.23704L11.4408 7.23749L11.4451 7.17626L12.1206 3.2H12.7326ZM10.7096 1.5L10.0555 5.42467L0 4.67049L9.10861 11.1063L9.00966 11.7L5.60972 15.95L11.5596 18.5L12.4096 14.25L14.9596 10.85L15.6459 4.67319L20 4.05093V3.20062H15.8095L15.8095 3.2L13.3191 1.5H13.2596H10.7096ZM6.0009 6.82949L9.41994 9.23827L9.77423 7.1125L6.0009 6.82949Z" fill="#F0F6FC" />
		</svg>
		<?php
		$svg = ob_get_clean();

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

}