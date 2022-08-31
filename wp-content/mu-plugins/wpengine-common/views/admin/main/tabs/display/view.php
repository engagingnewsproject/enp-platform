<?php
/**
 * Admin UI - Display Tab
 * Adds the WP Engine Admin "Display" tab.
 *
 * @package wpengine/common-mu-plugin
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Check user capabilities.
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$enable_admin_bar_name = 'wpe-adminbar-enable';
$admin_bar_roles_name  = 'wpe-adminbar-roles';
$nonce_action          = PWP_NAME . '-config';
$form_name             = 'displayoptions';
$form_action           = add_query_arg(
	array(
		'page' => 'wpengine-common',
		'tab'  => 'display',
	),
	admin_url()
);

?>
<form
	class="wpe-common-plugin-container"
	method="post"
	action="<?php echo esc_url( $form_action ); ?>"
	name="<?php echo esc_attr( $form_name ); ?>"
>
	<?php wp_nonce_field( $nonce_action ); ?>
	<h2><?php esc_html_e( 'Display Options', 'wpe-common' ); ?></h2>

	<input
		id="<?php echo esc_attr( $enable_admin_bar_name ); ?>"
		name="<?php echo esc_attr( $enable_admin_bar_name ); ?>"
		type="checkbox"
		<?php checked( $this->is_wpengine_admin_bar_enabled() ); ?>
	>
	<label for="<?php echo esc_attr( $enable_admin_bar_name ); ?>" class="wpe-checkbox-label">
		<?php
		printf(
			/* translators: %1$s: an opening HTML tag, %2$s: a closing HTML tag */
			esc_html__( 'Display the %1$s"WP Engine Quick Actions"%2$s menu in the WordPress Admin Bar.', 'wpe-common' ),
			'<strong>',
			'</strong>'
		);
		?>
	</label>
	<h3><?php esc_html_e( 'Access Roles', 'wpe-common' ); ?></h3>
	<p>
		<?php
		printf(
			/* translators: %1$s: an opening HTML tag, %2$s: a closing HTML tag */
			esc_html__( 'Administrators will always see the %1$s"WP Engine Quick Actions"%2$s menu. For other users, please select which role should have access to it below', 'wpe-common' ),
			'<strong>',
			'</strong>'
		);
		?>
	</p>
	<div class="wpe-access-roles">
		<?php
		$roles_with_access = get_option( $admin_bar_roles_name, array() );
		foreach ( wpengine\admin_options\get_access_roles() as $user_role => $role_name ) :
			$input_id = "role-{$user_role}";

			?>
			<input
				id="<?php echo esc_attr( $input_id ); ?>"
				name="<?php echo esc_attr( "{$admin_bar_roles_name}[]" ); ?>"
				value="<?php echo esc_attr( $user_role ); ?>"
				type="checkbox"
				<?php
				checked(
					is_array( $roles_with_access ) &&
					in_array( $user_role, $roles_with_access, true )
				)
				?>
			/>
			<label for="<?php echo esc_attr( $input_id ); ?>" class="wpe-checkbox-label">
				<?php echo esc_html( $role_name ); ?>
			</label>
			<br/>
		<?php endforeach; ?>
	</div>
	<div class="wpe-admin-button-controls">
		<input
			type="submit"
			name="<?php echo esc_attr( $form_name ); ?>"
			value="<?php esc_attr_e( 'Save', 'wpe-common' ); ?>"
			class="wpe-admin-button-primary"
		/>
	</div>
</form>
