<?php
/**
 * Email notice class.
 *
 * @since      2.0
 * @author     Incsub (Joel James)
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright  Copyright (c) 2022, Incsub
 * @package    WPMUDEV\Notices
 */

namespace WPMUDEV\Notices\Notices;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use WPMUDEV\Notices\Handler;

if ( ! class_exists( __NAMESPACE__ . '\\Email' ) ) {
	/**
	 * Class Email
	 *
	 * @since   2.0
	 * @package WPMUDEV\Notices
	 */
	class Email extends Notice {

		/**
		 * Current notice type.
		 *
		 * @since 2.0
		 * @var string $type
		 */
		protected $type = 'email';

		/**
		 * User id /API Key for Mailchimp subscriber list
		 *
		 * @since 1.2
		 * @var string $mc_user_id
		 */
		private $mc_user_id = '53a1e972a043d1264ed082a5b';

		/**
		 * Render a notice type content.
		 *
		 * @since 2.0
		 *
		 * @param string $plugin Plugin ID.
		 *
		 * @return void
		 */
		public function render( $plugin ) {
			$this->enqueue_assets( $plugin );

			$admin_email = get_site_option( 'admin_email' );
			$mc_list_id  = $this->get_option( 'mc_list_id' );

			$action = "https://edublogs.us1.list-manage.com/subscribe/post-json?u={$this->mc_user_id}&id={$mc_list_id}&c=?";

			/* translators: %s - plugin name */
			$title = __( "We're happy that you've chosen to install %s!", 'wdev_frash' );
			$title = apply_filters( 'wdev_email_title_' . $plugin, $title );

			/* translators: %s - plugin name */
			$message = __( 'Are you interested in how to make the most of this plugin? How would you like a quick 5 day email crash course with actionable advice on building your membership site? Only the info you want, no subscription!', 'wdev_frash' );
			$message = apply_filters( 'wdev_email_message_' . $plugin, $message );

			// Plugin title.
			$plugin_title = $this->get_option( 'title', __( 'Plugin', 'wdev_frash' ) );

			?>
			<div class="notice notice-info frash-notice frash-notice-<?php echo esc_attr( $this->type ); ?> hidden">
				<?php $this->render_hidden_fields( $plugin ); ?>

				<div class="frash-notice-logo <?php echo esc_attr( $plugin ); ?>"><span></span></div>
				<div class="frash-notice-message">
					<p class="notice-title"><?php printf( esc_html( $title ), esc_html( $plugin_title ) ); ?></p>
					<p><?php printf( esc_html( $message ), esc_html( $plugin_title ) ); ?></p>
					<div class="frash-notice-cta">
						<?php
						/**
						 * Fires before subscribe form renders.
						 *
						 * @since 1.3
						 *
						 * @param int $mc_list_id Mailchimp list ID.
						 */
						do_action( 'frash_before_subscribe_form_render', $mc_list_id );
						?>
						<form action="<?php echo esc_attr( $action ); ?>" method="get" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank">
							<label for="mce-EMAIL" class="hidden"><?php esc_html_e( 'Email', 'wdev_frash' ); ?></label>
							<input type="email" name="EMAIL" class="email" id="mce-EMAIL" value="<?php echo esc_attr( $admin_email ); ?>" required="required"/>
							<button class="frash-notice-act button-primary" data-msg="<?php esc_attr_e( 'Thanks :)', 'wdev_frash' ); ?>" type="submit">
								<?php echo esc_html( $this->get_option( 'cta_email', __( 'Get Fast!', 'wdev_frash' ) ) ); ?>
							</button>
							<span class="frash-notice-cta-divider">|</span>
							<a href="#" class="frash-notice-dismiss" data-msg="<?php esc_attr_e( 'Saving', 'wdev_frash' ); ?>">
								<?php esc_html_e( 'No thanks', 'wdev_frash' ); ?>
							</a>
							<?php
							/**
							 * Fires after subscribe form fields are rendered.
							 * Use this hook to add additional fields for on the sub form.
							 *
							 * Make sure that the additional field has is also present on the
							 * actual MC subscribe form.
							 *
							 * @since 1.3
							 *
							 * @param int $mc_list_id Mailchimp list ID.
							 */
							do_action( 'frash_subscribe_form_fields', $mc_list_id );
							?>
						</form>
						<?php
						/**
						 * Fires after subscribe form is rendered
						 *
						 * @since 1.3
						 *
						 * @param int $mc_list_id Mailchimp list ID.
						 */
						do_action( 'frash_before_subscribe_form_render', $mc_list_id );
						?>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render a notice type content.
		 *
		 * @since 2.0
		 *
		 * @param string $plugin Plugin ID.
		 *
		 * @return void
		 */
		protected function render_hidden_fields( $plugin ) {
			$wp_url = $this->get_option( 'wp_slug' );
			if ( false === strpos( $wp_url, '://' ) ) {
				$wp_url = 'https://wordpress.org/plugins/' . trim( $wp_url, '/' );
			}

			?>
			<input type="hidden" name="type" value="<?php echo esc_attr( $this->type ); ?>"/>
			<input type="hidden" name="plugin_id" value="<?php echo esc_attr( $plugin ); ?>"/>
			<input type="hidden" name="url_wp" value="<?php echo esc_attr( $wp_url ); ?>"/>
			<?php wp_nonce_field( 'wpmudev_notices_action', 'notice_nonce' ); ?>
			<?php
		}

		/**
		 * Enqueue assets for a notice if required.
		 *
		 * @since 2.0
		 *
		 * @param string $plugin Plugin ID.
		 *
		 * @return void
		 */
		protected function enqueue_assets( $plugin ) {
			wp_enqueue_style(
				'wpmudev-notices-dashboard',
				$this->assets_url( 'css/dashboard-notices.min.css' ),
				array(),
				Handler::instance()->version
			);

			wp_enqueue_script(
				'wpmudev-notices-dashboard',
				$this->assets_url( 'js/dashboard-notices.min.js' ),
				array(),
				Handler::instance()->version,
				true
			);
		}

		/**
		 * Check if current notice is allowed for the plugin.
		 *
		 * @since 2.0
		 *
		 * @param string $plugin Plugin ID.
		 *
		 * @return bool
		 */
		public function can_show( $plugin ) {
			// Mailchimp list id is required.
			$list_id = $this->get_option( 'mc_list_id', 'ss' );

			// Show only on dashboard.
			return 'dashboard' === $this->screen_id() && ! empty( $list_id );
		}

		/**
		 * Parse options for the notice.
		 *
		 * @since 2.0
		 *
		 * @param array $options Plugin options.
		 *
		 * @return array
		 */
		protected function parse_options( array $options ) {
			return wp_parse_args(
				$options,
				array(
					'title'      => __( 'Plugin', 'wdev_frash' ),
					'wp_slug'    => '',
					'cta_email'  => __( 'Get Fast!', 'wdev_frash' ),
					'mc_list_id' => '',
				)
			);
		}
	}
}
