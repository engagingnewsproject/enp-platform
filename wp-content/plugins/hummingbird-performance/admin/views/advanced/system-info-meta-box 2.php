<?php
/**
 * Advanced tools: system information meta box.
 *
 * @package Hummingbird
 *
 * @var array $system_info Array of system information ( PHP, MySQL, WordPress & Server) settings and values.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-margin-bottom">
	<p>
		<?php esc_html_e( 'Use this info if you are having issues with Hummingbird and your server setup. It will give you the most up to date information about your stack.', 'wphb' ); ?>
	</p>
</div>

<div class="sui-form-field sui-input-md">
	<select id="wphb-system-info-dropdown" class="sui-select sui-form-field wphb-system-info-dropdown" name="system-info" aria-label="<?php esc_attr_e( 'Select system', 'wphb' ); ?>">
		<option value="php"><?php esc_html_e( 'PHP', 'wphb' ); ?></option>
		<option value="db"><?php esc_html_e( 'MySQL', 'wphb' ); ?></option>
		<option value="wp"><?php esc_html_e( 'WordPress', 'wphb' ); ?></option>
		<option value="server"><?php esc_html_e( 'Server', 'wphb' ); ?></option>
	</select>
</div>

<?php foreach ( $system_info as $system_name => $system_info_arr ) : ?>
	<table id="wphb-system-info-<?php echo esc_attr( $system_name ); ?>" class="sui-table wphb-sys-info-table sui-hidden">
		<tbody>
		<?php foreach ( $system_info_arr as $name => $value ) : ?>
			<tr>
				<td><?php echo esc_html( $name ); ?></td>
				<td><?php echo wp_kses_post( $value ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php endforeach; ?>
