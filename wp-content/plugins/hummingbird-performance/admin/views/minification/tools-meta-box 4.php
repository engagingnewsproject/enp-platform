<?php
/**
 * Tools meta box.
 *
 * @since 1.8
 * @package Hummingbird
 *
 * @var string $css  Above the fold CSS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<strong><?php esc_html_e( 'CSS above the fold', 'wphb' ); ?></strong>
		<span class="sui-description">
			<?php
			esc_html_e(
				'Drastically reduce your page load time by moving all of your stylesheets
			to the footer to force them to load after your content.',
				'wphb'
			);
			?>
			<br><br>
			<?php
			esc_html_e(
				'This will result in the content loading quickly, with the styling
			followed shortly after.',
				'wphb'
			);
			?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<ol class="sui-description">
			<li>
				<?php esc_html_e( 'Add critical layout and styling CSS here. We will insert into <style> tags in your <head> section of each page.', 'wphb' ); ?>
			</li>
			<li>
				<?php esc_html_e( 'Next, switch to the manual mode in asset optimization and move all of your CSS files to the footer area.', 'wphb' ); ?>
			</li>
		</ol>

		<span class="sui-description">
			<?php esc_html_e( 'CSS to insert into your <head> area', 'wphb' ); ?>
		</span>
		<textarea class="sui-form-control" name="critical_css" placeholder="<?php esc_attr_e( 'Add CSS here', 'wphb' ); ?>"><?php echo esc_html( $css ); ?></textarea>
	</div>
</div>
