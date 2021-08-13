<?php
/**
 * Hummingbird PRO upgrade page.
 *
 * @since 2.0.1
 * @package Hummingbird
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-upgrade-page">
	<div class="sui-upgrade-page-header">
		<div class="sui-upgrade-page__container">
			<div class="sui-upgrade-page-header__content">
				<h1><?php esc_html_e( 'Upgrade to Hummingbird Pro', 'wphb' ); ?></h1>
				<p><?php esc_html_e( 'Get Hummingbird Pro for our full WordPress speed optimization suite, including uptime monitoring, enhanced hosted file minification, and white label reports for clients.', 'wphb' ); ?></p>
				<p><?php esc_html_e( 'Plus – you’ll get WPMU DEV membership, which includes our award winning Smush Pro plugin for image optimization, 24/7 live WordPress support, and unlimited usage of all our premium plugins.', 'wphb' ); ?></p>
				<a href="<?php echo esc_url( Utils::get_link( 'plugin', 'hummingbird_propage_topbutton' ) ); ?>" class="sui-button sui-button-lg sui-button-purple" target="_blank">
					<?php esc_html_e( 'Try Hummingbird Pro for free', 'wphb' ); ?>
				</a>
				<div class="sui-reviews">
					<span class="sui-reviews__stars"></span>
					<div class="sui-reviews__rating"><span class="sui-reviews-rating">-</span> / <?php esc_html_e( '5.0 rating from', 'wphb' ); ?> <span class="sui-reviews-customer-count">-</span> <?php esc_html_e( 'customers', 'wphb' ); ?></div>
					<a class="sui-reviews__link" href="https://www.reviews.io/company-reviews/store/wpmudev-org" target="_blank">
						Reviews.io<span class="sui-icon-arrow-right" aria-hidden="true"></span>
					</a>
				</div>
			</div>

			<div class="sui-upgrade-page-header__image"></div>
		</div>
	</div>
	<div class="sui-upgrade-page-features">
		<div class="sui-upgrade-page-features__header">
			<h2><?php esc_html_e( 'Pro Features', 'wphb' ); ?></h2>
			<p><?php esc_html_e( 'Upgrading to Pro will get you the following benefits.', 'wphb' ); ?></p>
		</div>
	</div>
	<div class="sui-upgrade-page__container">
		<div class="sui-upgrade-page-features__items">
			<div class="sui-upgrade-page-features__item">
				<span class="sui-icon-graph-bar" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'White label automated reporting', 'wphb' ); ?></h3>
				<p><?php esc_html_e( 'You’ll get automated email reporting of your site’s performance (or if you have a multisite network, we’ve still got you covered!). You can even white label this for your clients, and have the reports sent straight to them. You’re informed and look great, and we do the work for you.', 'wphb' ); ?></p>
			</div>
			<div class="sui-upgrade-page-features__item">
				<span class="sui-icon-uptime" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'Uptime monitoring', 'wphb' ); ?></h3>
				<p><?php esc_html_e( 'Make sure you know when your site is available with Hummingbird’s Pro uptime monitoring. We’ll notify you by email if there’s a problem with any of your WordPress sites loading, and we’ll even check your site’s average response time, and give you a rolling average, so you always know how well your site is performing.', 'wphb' ); ?></p>
			</div>
			<div class="sui-upgrade-page-features__item">
				<span class="sui-icon-arrows-in" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'Enhanced file minification with CDN', 'wphb' ); ?></h3>
				<p><?php esc_html_e( 'You’ll get enhanced file minification, with 2x the compression. Plus, for maximum speed, you can load your files from our global WPMU DEV CDN – instead of your server. Make your files smaller, and then load them faster with Hummingbird Pro.', 'wphb' ); ?></p>
			</div>
			<div class="sui-upgrade-page-features__item">
				<span class="sui-icon-smush" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'Smush Pro for the best image optimization', 'wphb' ); ?></h3>
				<p><?php esc_html_e( 'Hummingbird Pro + Smush Pro gives you the fastest possible WordPress site: Hummingbird’s performance optimization + Smush’s award-winning image optimization. It’s a powerful combination which your visitors, customers, and search engines will love.', 'wphb' ); ?></p>
			</div>
			<div class="sui-upgrade-page-features__item">
				<span class="sui-icon-gdpr" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'Premium WordPress plugins', 'wphb' ); ?></h3>
				<p><?php esc_html_e( 'You’ll get our full suite of premium WordPress plugins, making sure from Security to Backups to Marketing and SEO you’ve got all the WordPress solutions you can possible need. You get unlimited usage on unlimited sites, and can join the millions using our plugins.', 'wphb' ); ?></p>
			</div>
			<div class="sui-upgrade-page-features__item">
				<span class="sui-icon-hub" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'Manage unlimited WordPress sites', 'wphb' ); ?></h3>
				<p><?php esc_html_e( 'You can manage unlimited WordPress sites with automated updates, backups, security, and performance! – checks, all in one place. All of this can be white labeled for your clients, and you even get our 24/7 live WordPress support.', 'wphb' ); ?></p>
			</div>
			<div class="sui-upgrade-page-features__item">
				<span class="sui-icon-help-support" aria-hidden="true"></span>
				<h3><?php esc_html_e( '24/7 live WordPress support', 'wphb' ); ?></h3>
				<p><?php esc_html_e( 'We can’t stress this enough: our outstanding WordPress support is available with live chat 24/7, and we’ll help you with absolutely any WordPress issue – not just our products. It’s an expert WordPress team on call for you, whenever you need them.', 'wphb' ); ?></p>
			</div>
			<div class="sui-upgrade-page-features__item">
				<span class="sui-icon-wpmudev-logo" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'The WPMU DEV Guarantee', 'wphb' ); ?></h3>
				<p><?php esc_html_e( "You'll be delighted with Hummingbird Pro 😁 You've got a free trial to test the WPMU DEV Membership, and if you continue but change your mind, you can cancel any time.", 'wphb' ); ?></p>
			</div>
		</div>
	</div>
	<div class="sui-upgrade-page-cta">
		<div class="sui-upgrade-page-cta__inner">
			<h2><?php esc_html_e( 'Join 752,819 Happy Members', 'wphb' ); ?></h2>
			<p><?php esc_html_e( "97% of customers are happy with WPMU DEV's service, and it’s a great time to join them: as a Hummingbird user you’ll get a free trial, so you can see what all the fuss is about.", 'wphb' ); ?></p>
			<a href="<?php echo esc_url( Utils::get_link( 'plugin', 'hummingbird_propage_bottombutton' ) ); ?>" class="sui-button sui-button-lg sui-button-purple" target="_blank">
				<?php esc_html_e( 'Get Hummingbird Pro, and get a better WordPress', 'wphb' ); ?>
			</a>
			<button type="button" class="sui-button sui-button-lg sui-button-purple sui-hidden-desktop">
				<?php esc_html_e( 'Get Hummingbird Pro', 'wphb' ); ?>
			</button>
			<a href="<?php echo esc_url( Utils::get_link( 'plugin', 'hummingbird_propage_bottombutton' ) ); ?>" target="_blank">
				<?php esc_html_e( 'Try Pro for free', 'wphb' ); ?>
			</a>
		</div>
	</div>
</div>
