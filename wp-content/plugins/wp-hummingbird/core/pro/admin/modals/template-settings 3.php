<?php
/**
 * Notifications template: settings.
 *
 * @since 3.1.1
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<# if ( 'performance' === data.module && 'reports' === data.type ) { #>
<p class="sui-description">
	<?php esc_html_e( 'Choose your email preferences for the performance test reports.', 'wphb' ); ?>
</p>

<strong><?php esc_html_e( 'Device', 'wphb' ); ?></strong>

<p class="sui-description">
	<?php esc_html_e( 'Choose which device type(s) should be tested as part of the scheduled performance test report.', 'wphb' ); ?>
</p>

<div class="sui-side-tabs">
	<div class="sui-tabs-menu">
		<label for="report_type-desktop" class="sui-tab-item <# if ( 'desktop' === data.performance.device ) {#>active<# } #>">
			<input type="radio" name="report-type" value="desktop" id="report_type-desktop">
			<?php esc_html_e( 'Desktop', 'wphb' ); ?>
		</label>

		<label for="report_type-mobile" class="sui-tab-item <# if ( 'mobile' === data.performance.device ) {#>active<# } #>">
			<input type="radio" name="report-type" value="mobile" id="report_type-mobile">
			<?php esc_html_e( 'Mobile', 'wphb' ); ?>
		</label>

		<label for="report_type-both" class="sui-tab-item <# if ( 'both' === data.performance.device ) {#>active<# } #>">
			<input type="radio" name="report-type" value="both" id="report_type-both" checked="checked">
			<?php esc_html_e( 'Both', 'wphb' ); ?>
		</label>
	</div>
</div>

<strong><?php esc_html_e( 'Results', 'wphb' ); ?></strong>

<p class="sui-description">
	<?php esc_html_e( 'Select which results should be included in the scheduled performance test report.', 'wphb' ); ?>
</p>

<label for="metrics" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
	<input type="checkbox" name="metrics" id="metrics" <# if ( data.performance.metrics ) {#>checked="checked"<# } #> />
	<span aria-hidden="true"></span>
	<span><?php esc_html_e( 'Score Metrics', 'wphb' ); ?></span>
</label>
<label for="audits" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
	<input type="checkbox" name="audits" id="audits" <# if ( data.performance.audits ) {#>checked="checked"<# } #> />
	<span aria-hidden="true"></span>
	<span><?php esc_html_e( 'Audits', 'wphb' ); ?></span>
</label>
<label for="field-data" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
	<input type="checkbox" name="field-data" id="field-data" <# if ( data.performance.fieldData ) {#>checked="checked"<# } #> />
	<span aria-hidden="true"></span>
	<span><?php esc_html_e( 'Historic Field Data', 'wphb' ); ?></span>
</label>
<# } #>

<# if ( 'uptime' === data.module && 'reports' === data.type ) { #>
<p class="sui-description">
	<?php esc_html_e( 'Configure uptime report settings.', 'wphb' ); ?>
</p>

<strong><?php esc_html_e( 'Response time report settings', 'wphb' ); ?></strong>

<p class="sui-description">
	<?php esc_html_e( 'Show the average server response time during the corresponding period in the scheduled report.', 'wphb' ); ?>
</p>

<label for="show_ping" class="sui-toggle">
	<input type="checkbox" name="show_ping" id="show_ping" aria-labelledby="show_ping-label" <# if ( data.uptime.showPing ) {#>checked="checked"<# } #> />
	<span class="sui-toggle-slider" aria-hidden="true"></span>
	<span id="show_ping-label" class="sui-toggle-label">
		<?php esc_html_e( 'Show average response time', 'wphb' ); ?>
	</span>
</label>
<# } #>


<# if ( 'database' === data.module && 'reports' === data.type ) { #>
<p class="sui-description">
	<?php esc_html_e( 'Configure general settings for database cleanup.', 'wphb' ); ?>
</p>

<strong><?php esc_html_e( 'Included Tables', 'wphb' ); ?></strong>

<p class="sui-description">
	<?php esc_html_e( 'Select which tables should be included in the database cleanup report.', 'wphb' ); ?>
</p>

<div id="included-tables" class="included-tables">
	<div id="included-tables" class="included-tables">
		<label for="revisions" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
			<input type="checkbox" name="revisions" id="revisions" <# if ( data.database.revisions ) {#>checked="checked"<# } #> />
			<span aria-hidden="true"></span>
			<span><?php esc_html_e( 'Post Revisions', 'wphb' ); ?></span>
		</label>
		<label for="drafts" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
			<input type="checkbox" name="drafts" id="drafts" <# if ( data.database.drafts ) {#>checked="checked"<# } #> />
			<span aria-hidden="true"></span>
			<span><?php esc_html_e( 'Draft Posts', 'wphb' ); ?></span>
		</label>
		<label for="trash" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
			<input type="checkbox" name="trash" id="trash" <# if ( data.database.trash ) {#>checked="checked"<# } #> />
			<span aria-hidden="true"></span>
			<span><?php esc_html_e( 'Trashed Posts', 'wphb' ); ?></span>
		</label>
		<label for="spam" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
			<input type="checkbox" name="spam" id="spam" <# if ( data.database.spam ) {#>checked="checked"<# } #> />
			<span aria-hidden="true"></span>
			<span><?php esc_html_e( 'Spam Comments', 'wphb' ); ?></span>
		</label>
		<label for="trashComment" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
			<input type="checkbox" name="trashComment" id="trashComment" <# if ( data.database.trashComment ) {#>checked="checked"<# } #> />
			<span aria-hidden="true"></span>
			<span><?php esc_html_e( 'Trashed Comments', 'wphb' ); ?></span>
		</label>
		<label for="expiredTransients" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
			<input type="checkbox" name="expiredTransients" id="expiredTransients" <# if ( data.database.expiredTransients ) {#>checked="checked"<# } #> />
			<span aria-hidden="true"></span>
			<span><?php esc_html_e( 'Expired Transients', 'wphb' ); ?></span>
		</label>
		<label for="transients" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
			<input type="checkbox" name="transients" id="transients" <# if ( data.database.transients ) {#>checked="checked"<# } #> />
			<span aria-hidden="true"></span>
			<span><?php esc_html_e( 'All Transients', 'wphb' ); ?></span>
		</label>
	</div>
</div>
<# } #>