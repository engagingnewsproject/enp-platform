<?php
/**
 * Advanced tools: general meta box.
 *
 * @package Hummingbird
 *
 * @var bool   $query_stings          URL Query Strings enabled or disabled.
 * @var bool   $query_strings_global  Is URL Query Strings a global option.
 * @var bool   $cart_fragments        WooCommerce cart fragments.
 * @var bool   $emoji                 Remove Emojis file enabled or disabled.
 * @var bool   $emoji_global          Is Emoji clearing a global option.
 * @var string $prefetch              Prefetch DNS URLs.
 * @var string $preconnect            Preconnect URLs.
 * @var bool   $woo_active            Is WooCommerce activated.
 * @var string $woo_link              Link to WooCommerce Settings - Products page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-margin-bottom">
	<p>
		<?php esc_html_e( 'Here are a few additional tweaks you can make to further reduce your page load times.', 'wphb' ); ?>
	</p>
</div>

<?php do_action( 'wphb_advanced_tools_notice' ); ?>

<?php if ( ! apply_filters( 'wphb_query_strings_disabled', false ) ) : ?>
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php esc_html_e( 'URL Query Strings', 'wphb' ); ?></span>
			<span class="sui-description">
				<?php esc_html_e( 'Some proxy caching servers and even some CDNs cannot cache static assets with query strings, resulting in a large missed opportunity for increased speeds.', 'wphb' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<label for="query_strings" class="sui-toggle">
					<input type="checkbox" name="query_strings" id="query_strings" aria-labelledby="query-strings-label" <?php checked( $query_stings ); ?> <?php disabled( apply_filters( 'wphb_query_strings_disabled', false ) ); ?>>
					<span class="sui-toggle-slider" aria-hidden="true"></span>
					<span id="query-strings-label" class="sui-toggle-label"><?php esc_html_e( 'Remove query strings from my assets', 'wphb' ); ?></span>
				</label>
				<?php if ( is_multisite() && is_network_admin() ) : ?>
					<div class="sui-border-frame sui-toggle-content">
						<label for="query_strings_global" class="sui-checkbox sui-checkbox-sm">
							<input type="checkbox" name="query_strings_global" id="query_strings_global" aria-labelledby="query-strings-global-label" <?php checked( $query_strings_global ); ?>>
							<span aria-hidden="true"></span>
							<span id="query-strings-global-label"><?php esc_html_e( 'Apply this setting globally', 'wphb' ); ?></span>
						</label>
						<span class="sui-description">
							<?php esc_html_e( 'By default, subsites are able to overwrite this setting. Enabling this option will force the network settings on all subsites.', 'wphb' ); ?>
						</span>
					</div>
				<?php elseif ( is_multisite() && apply_filters( 'wphb_query_strings_disabled', false ) ) : ?>
					<div class="sui-toggle-content" style="margin-top: 10px">
						<?php
						$notice_text = esc_html__( 'This option is overwritten by global network settings.', 'wphb' );
						$this->admin_notices->show_inline( $notice_text, 'grey' );
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'WooCommerce Cart Fragments', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'WooCommerce uses ajax calls to update cart totals without refreshing the page. These ajax calls run on every page and can drastically increase page load times. We recommend disabling cart fragments on all non-WooCommerce pages.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<label for="cart_fragments" class="sui-toggle">
				<input type="checkbox" name="cart_fragments" id="cart_fragments" aria-labelledby="cart_fragments-label" <?php checked( (bool) $cart_fragments ); ?> <?php disabled( apply_filters( 'wphb_cart_fragments_disabled', ! $woo_active ) ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="cart_fragments-label" class="sui-toggle-label"><?php esc_html_e( 'Disable cart fragments', 'wphb' ); ?></span>
			</label>

			<div
				tabindex="0"
				id="cart_fragments_desc"
				class="sui-toggle-content sui-border-frame"
				aria-labelledby="cart-fragment-description"
				style="display: <?php echo $cart_fragments && $woo_active ? 'block' : 'none'; ?>;"
			>
				<span class="sui-description" id="cart-fragment-description">
					<?php esc_html_e( 'Choose whether to disable this feature on only non-WooCommerce pages, or all pages.', 'wphb' ); ?>
				</span>
				<div class="sui-form-field sui-no-margin-bottom">
					<span class="sui-label"><?php esc_html_e( 'Disable cart fragments on', 'wphb' ); ?></span>
					<div class="sui-side-tabs">
						<div class="sui-tabs-menu">
							<label for="cart_fragments-true" class="sui-tab-item <?php echo 'all' === $cart_fragments ? '' : 'active'; ?>">
								<input type="radio" name="cart_fragments_value" value="1" id="cart_fragments-true" checked="checked">
								<?php esc_html_e( 'Non-WooCommerce Pages', 'wphb' ); ?></label>

							<label for="cart_fragments-all" class="sui-tab-item <?php echo 'all' === $cart_fragments ? 'active' : ''; ?>">
								<input type="radio" name="cart_fragments_value" value="all" id="cart_fragments-all">
								<?php esc_html_e( 'All Pages', 'wphb' ); ?></label>
						</div>
					</div>
				</div>
				<?php
				$this->admin_notices->show_inline(
					sprintf( /* translators: %1$s - <a> link, %2$s - </a> closing tag */
						esc_html__( 'After disabling cart fragments, be sure to enable the %1$sRedirect to the cart page after successful addition%2$s option in your Woocommerce Settings to redirect your customers to the main cart page instead of waiting for an item to be added to the cart.', 'wphb' ),
						'<a href="' . esc_url( $woo_link ) . '" target="_blank">',
						'</a>'
					),
					'grey'
				);
				?>
			</div>

			<?php if ( ! $woo_active ) : ?>
				<div class="sui-toggle-content" style="margin-top: 10px">
					<?php
					$this->admin_notices->show_inline(
						esc_html__( 'This option requires WooCommerce to be installed and activated.', 'wphb' ),
						'grey'
					);
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php if ( ! apply_filters( 'wphb_emojis_disabled', false ) ) : ?>
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php esc_html_e( 'Emojis', 'wphb' ); ?></span>
			<span class="sui-description">
				<?php esc_html_e( 'WordPress adds Javascript and CSS files to convert common symbols like “:)” to visual emojis. If you don’t need emojis this will remove two unnecessary assets.', 'wphb' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<label for="emojis" class="sui-toggle">
					<input type="checkbox" name="emojis" id="emojis" aria-labelledby="emojis-label" <?php checked( $emoji ); ?> <?php disabled( apply_filters( 'wphb_emojis_disabled', false ) ); ?>>
					<span class="sui-toggle-slider" aria-hidden="true"></span>
					<span id="emojis-label" class="sui-toggle-label"><?php esc_html_e( 'Remove the default Emoji JS & CSS files', 'wphb' ); ?></span>
				</label>
				<?php if ( is_multisite() && is_network_admin() ) : ?>
					<div class="sui-border-frame sui-toggle-content">
						<label for="emojis_global" class="sui-checkbox sui-checkbox-sm">
							<input type="checkbox" name="emojis_global" id="emojis_global" aria-labelledby="emojis-global-label" <?php checked( $emoji_global ); ?>>
							<span aria-hidden="true"></span>
							<span id="emojis-global-label"><?php esc_html_e( 'Apply this setting globally', 'wphb' ); ?></span>
						</label>
						<span class="sui-description">
							<?php esc_html_e( 'By default, subsites are able to overwrite this setting. Enabling this option will force the network settings on all subsites.', 'wphb' ); ?>
						</span>
					</div>
				<?php elseif ( is_multisite() && apply_filters( 'wphb_emojis_disabled', false ) ) : ?>
					<div class="sui-toggle-content" style="margin-top: 10px">
						<?php
						$notice_text = esc_html__( 'This option is overwritten by global network settings.', 'wphb' );
						$this->admin_notices->show_inline( $notice_text, 'grey' );
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Prefetch DNS Requests', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Speeds up web pages by pre-resolving DNS. In essence it tells a browser it should resolve the DNS of a specific domain prior to it being explicitly called – very useful if you use third party services.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<textarea
				placeholder="//fonts.googleapis.com
//fonts.gstatic.com
//ajax.googleapis.com
//apis.google.com
//google-analytics.com
//www.google-analytics.com
//ssl.google-analytics.com
//youtube.com
//s.gravatar.com"
				id="url_strings"
				name="url_strings"
				class="sui-form-control"
				aria-label="<?php esc_html_e( 'Prefetch DNS Requests', 'wphb' ); ?>"
				aria-describedby="url_strings-id"
			><?php echo esc_html( $prefetch ); ?></textarea>
			<span id="url_strings-id" class="sui-description">
				<?php esc_html_e( 'Add one host entry per line replacing the http:// or https:// with // e.g. //fonts.googleapis.com. We’ve added a few common DNS requests to get you started.', 'wphb' ); ?>
				<a href="#" id="wphb-adv-paste-value"><?php esc_html_e( 'Paste in recommended defaults.', 'wphb' ); ?></a>
			</span>
		</div>
	</div>
</div>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Preconnect', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'If you load resources from other domains, using Preconnect can provide a faster page loading experience for your users. It tells the browser to set up early connections before an HTTP request is actually sent to your server., and includes DNS lookup, TCP handshake, TLS negotiation, etc.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<textarea
					placeholder="//cdn.google.com
//fonts.gstatic.com
//cdn.aliyuncs.com
//apis.google.com"
					id="preconnect_strings"
					name="preconnect_strings"
					class="sui-form-control"
					aria-label="<?php esc_html_e( 'Preconnect', 'wphb' ); ?>"
					aria-describedby="preconnect_strings-id"
			><?php echo esc_html( $preconnect ); ?></textarea>
			<span id="preconnect_strings-id" class="sui-description">
				<?php
				printf( /* translators: %1$s - opening <a>, %2$s - closing </a> */
					esc_html__( 'Add hosts one per line, with no http or https. We’ve added a few common domains as an example. Note that Preconnect requests are made without the crossorigin attribute by default. If you’d like to add the crossorigin attribute, please see our %1$sdocumentation%2$s first.', 'wphb' ),
					'<a href="' . esc_url( \Hummingbird\Core\Utils::get_documentation_url( 'wphb-advanced' ) ) . '" target="_blank">',
					'</a>'
				);
				?>
			</span>
		</div>
	</div>
</div>
