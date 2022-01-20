<?php
/**
 * Main template file for database cleanup reports.
 *
 * @since 3.2.0
 *
 * @package Hummingbird
 * @var array  $params     Parameters array: REPORT_TYPE, USER_NAME, SITE_URL, SITE_NAME, FIELDS, SITE_MANAGE_URL.
 * @var Object $last_test  Last test object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<table class="main-content" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
	<tbody>
	<tr style="padding: 0; text-align: left; vertical-align: top;">
		<td class="main-content-text" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 30px; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
			<p style="color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 25px;font-weight: 600;line-height: 34px;margin: 0 0 30px;padding: 0;text-align: left;">
				<?php esc_html_e( 'Database cleanup report for', 'wphb' ); ?>
				<a class="brand" href="<?php echo esc_attr( $params['SITE_MANAGE_URL'] ); ?>" target="_blank" style="color: #17A8E3;font-family: Roboto, Arial, sans-serif;font-weight: inherit;line-height: 30px;margin: 0;padding: 0;text-align: left;text-decoration: none">
					<?php echo esc_html( $params['SITE_URL'] ); ?>
				</a>
			</p>

			<?php /* translators: %s: Username. */ ?>
			<p style="color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 18px;font-weight: normal;line-height: 24px;margin: 0 0 10px;padding: 0;text-align: left"><?php printf( esc_html__( 'Hi %s,', 'wphb' ), esc_attr( $params['USER_NAME'] ) ); ?></p>
			<p style="color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 18px;font-weight: normal;line-height: 28px;margin: 0 0 30px;padding: 0;text-align: left;letter-spacing: -0.3px;">
				<?php esc_html_e( 'Here’s a quick summary of the latest database cleanup.', 'wphb' ); ?>
			</p>

			<table class="reports-list" align="center" style="border-collapse: collapse;border-spacing: 0;margin: 0 0 30px;padding: 0;text-align: left;vertical-align: top;width: 100%">
				<thead>
				<tr style="background-color: #F2F2F2">
					<td style="padding-left: 20px;border-radius: 4px 0 0 0;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;font-weight: 500;line-height: 27px; letter-spacing: -0.23px; text-align: left">
						<?php esc_html_e( 'Data Type', 'wphb' ); ?>
					</td>
					<td style="padding-right: 20px;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;font-weight: 500;line-height: 27px; letter-spacing: -0.23px; text-align: right; width:100px">
						<?php esc_html_e( 'Entries Deleted', 'wphb' ); ?>
					</td>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $params['FIELDS'] as $field => $info ) : ?>
					<?php
					if ( ! isset( $last_test[ $field ] ) ) {
						continue;
					}
					?>
					<tr class="report-list-item" style="border: 1px solid #F2F2F2;padding: 0;text-align: left;vertical-align: top">
						<td class="report-list-item-info" style="border-collapse: collapse !important;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;font-weight: 500;letter-spacing: -0.23px;line-height: 22px;margin: 0;padding: 18px 0;text-align: left;vertical-align: top">
							<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle; letter-spacing: -0.25px;padding-left: 20px;">
								<?php echo esc_html( $info['title'] ); ?>
							</span>
						</td>
						<td class="report-list-item-info" style="border-collapse: collapse !important;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 13px;font-weight: 500;letter-spacing: -0.25px;line-height: 21px;margin: 0;min-width: 65px;padding: 18px 0;text-align: right;vertical-align: top">
							<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px;padding-right: 20px;"><?php echo (int) $last_test[ $field ]; ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<p style="color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 15px;font-weight: normal;line-height: 20px;margin: 0 0 20px;padding: 0;text-align: left;clear: both"><?php esc_html_e( 'Stay humming.', 'wphb' ); ?></p>
			<strong><?php esc_html_e( 'Hummingbird', 'wphb' ); ?></strong>
			<p style="color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 15px;font-weight: normal;line-height: 15px;margin: 7px 0 13px;padding: 0;text-align: left"><?php esc_html_e( 'Performance Hero', 'wphb' ); ?></p>
			<p style="color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 15px;font-weight: normal;line-height: 15px;margin: 0;padding: 0;text-align: left"><?php esc_html_e( 'WPMU DEV', 'wphb' ); ?></p>
		</td>
	</tr>
	</tbody>
</table>