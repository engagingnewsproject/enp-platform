<?php
/**
 * Helper for plugin table and popups
 *
 * @var int                             $pid                   Project ID.
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls                  URLs class.
 * @var string                          $membership_type       Membership type.
 * @var array                           $membership_data       Membership data.
 * @var bool                            $is_wpmudev_host       Is WPMUDEV Host.
 * @var bool                            $is_standalone_hosting Is standalone hosting plan.
 *
 * @package WPMUDEV DASHBOARD 4.9.0
 */

$url_upgrade = $urls->remote_site . 'hub/account/';

$hub_client_pid = 3779636;
$reactivate_url = $urls->remote_site . '/hub/account/';

$url_upgrade_to_agency = $urls->remote_site . '/hub/account/';

$free               = false;
$is_unit_membership = false;
$is_unit_allowed    = false;
$dash_pid           = 119;
$has_hosted_access  = $is_wpmudev_host && ! $is_standalone_hosting && 'free' === $membership_type;

if ( 'free' === $membership_type ) {
	$free = true;
} elseif ( 'unit' === $membership_type ) {
	$is_unit_membership = true;
}
// Subscribed unit plugin(s) and Dashboard are allowed with unit membership type.
$is_unit_allowed = intval( $pid ) === $dash_pid;
if ( ! $is_unit_allowed && $is_unit_membership ) {
	foreach ( $membership_data['membership_projects'] as $p ) {
		$is_unit_allowed = intval( $pid ) === intval( $p );
		if ( $is_unit_allowed ) {
			break;
		}
	}
}

// Skip if project-ID is invalid.
$pid = intval( $pid );
if ( ! $pid ) {
	return;
}

$res = false;
// For backward compatibility while updating.
if ( method_exists( WPMUDEV_Dashboard::$site, 'get_project_info' ) ) {
	$res = WPMUDEV_Dashboard::$site->get_project_info( $pid );
}

// Skip invalid projects.
if ( false === $res || empty( $res->pid ) || empty( $res->name ) ) {
	return;
}

// Skip hidden projects.
if ( $res->is_hidden ) {
	return;
}

$hashes = array(
	'project-activate'   => wp_create_nonce( 'project-activate' ),
	'project-deactivate' => wp_create_nonce( 'project-deactivate' ),
	'project-install'    => wp_create_nonce( 'project-install' ),
	'project-delete'     => wp_create_nonce( 'project-delete' ),
	'project-update'     => wp_create_nonce( 'project-update' ),
	'project-upgrade'    => wp_create_nonce( 'project-upgrade' ),
	'project-download'   => wp_create_nonce( 'project-download' ),
);

$main_action             = array();
$actions                 = array();
$is_single_action        = false;
$actions_icon            = 'sui-icon-plus';
$main_action_class       = 'sui-button-blue';
$main_action_class_modal = 'sui-button-blue';
$show_num_install        = false;
$allow_description       = false;
$num_install             = 0;
$rounded_num_install     = 0;
$modal_install_button    = array();
$incompatible_reason     = '';

if ( ! $res->is_installed ) {
	$is_single_action    = true;
	$show_num_install    = false;
	$allow_description   = true;
	$num_install         = (int) $res->downloads;
	$rounded_num_install = $num_install;
	if ( $num_install > 999 ) {
		$rounded_num_install = ceil( ( $num_install / 1000 ) ) . 'k';
	}
	if ( $num_install > 999999 ) {
		$rounded_num_install = ceil( ( $num_install / 1000000 ) ) . 'm';
	}

	/*
	 * Plugin is not installed yet.
	 * Possible Actions: Install, Download, Incompatible, Upgrade Membership.
	 */
	$actions_icon = 'sui-icon-plus';

	if ( ! $res->is_licensed ) {
		if ( false === $free ) {
			$main_action = array(
				'name' => __( 'Upgrade Membership', 'wpmudev' ),
				'url'  => $reactivate_url,
				'icon' => 'sui-wpmudev-logo',
				'type' => 'none',
			);
		}
	} elseif ( $res->is_compatible && $res->url->install ) {
		$actions              = array(
			'install' => array(
				'name' => __( 'Install', 'wpmudev' ),
				'url'  => $res->url->install,
				'type' => 'modal-ajax',
				'icon' => 'sui-icon-download',
				'data' => array(
					'action'  => 'project-install',
					'hash'    => $hashes['project-install'],
					'project' => $pid,
				),
			),
		);
		$modal_install_button = array(
			'name'  => __( 'Install', 'wpmudev' ),
			'class' => 'sui-button-blue',
			'url'   => $res->url->install,
			'type'  => 'modal-ajax',
			'icon'  => 'sui-icon-plus',
			'data'  => array(
				'action'  => 'project-install',
				'hash'    => $hashes['project-install'],
				'project' => $pid,
			),
		);
	} elseif ( $res->is_compatible ) {
		$actions = array(
			'download' => array(
				'name' => '',
				'url'  => $res->url->download,
				'type' => 'ajax',
				'icon' => 'sui-icon-download',
				'data' => array(
					'action'  => 'project-download',
					'hash'    => $hashes['project-download'],
					'project' => $pid,
				),
			),

		);
	} else {
		$incompatible_reason = $res->incompatible_reason;
		if ( empty( $incompatible_reason ) ) {
			$incompatible_reason = __( 'Incompatible', 'wpmudev' );
		}
	}
} else {
	/*
	 * Plugin is installed.
	 * Possible Actions: Update, Activate, Deactivate, Install Upfront, Configure, Delete.
	 */
	$is_single_action = false;
	$actions_icon     = 'sui-icon-widget-settings-config';

	// update always prioritized.
	if ( $res->has_update ) {
		if ( ( $is_unit_membership && false === $is_unit_allowed ) || ( $free && ! $res->is_licensed ) ) {
			$main_action_class_modal = 'sui-button-purple';
			$main_action = array(
				'name' => __( 'Upgrade Membership', 'wpmudev' ),
				'url'  => $reactivate_url,
				'type' => 'href',
				'icon' => 'sui-icon-download',
				'data' => array(
					'action'  => 'upgrade-membership',
					'hash'    => '',
					'project' => $pid,
				),
			);
		} else {
			$main_action = array(
				'name' => __( 'Update', 'wpmudev' ),
				'url'  => '',
				'type' => 'modal-ajax',
				'icon' => 'sui-icon-download',
				'data' => array(
					'action'  => 'project-update',
					'hash'    => $hashes['project-update'],
					'project' => $pid,
				),
			);
		}

		if ( $is_unit_membership && false === $is_unit_allowed ) {
			$actions['update'] = array(
				'name' => __( 'Upgrade Membership', 'wpmudev' ),
				'url'  => $reactivate_url,
				'type' => 'href',
				'icon' => 'sui-icon-download',
				'data' => array(
					'action'  => 'upgrade-membership',
					'hash'    => '',
					'project' => $pid,
				),
			);
		} else {
			$actions['update'] = array(
				'name' => __( 'Update', 'wpmudev' ),
				'url'  => '#update=' . $pid,
				'type' => 'modal-ajax',
				'icon' => 'sui-icon-download',
				'data' => array(
					'action'  => 'project-update',
					'hash'    => $hashes['project-update'],
					'project' => $pid,
				),
			);
		}

		$actions['changelog'] = array(
			'name' => __( 'View Changelog', 'wpmudev' ),
			'url'  => '#update=' . $pid,
			'type' => 'modal-ajax',
			'icon' => 'sui-icon-list-bullet',
			'data' => array(
				'action'  => 'project-update',
				'hash'    => $hashes['project-update'],
				'project' => $pid,
			),
		);

		// Activate, configure, delete.
		if ( ! $res->is_active ) {
			$actions['activate'] = array(
				'name' => ( $res->is_network_admin ? __( 'Network Activate', 'wpmudev' ) : __( 'Activate', 'wpmudev' ) ),
				'url'  => '#activate=' . $pid,
				'type' => 'ajax',
				'icon' => 'sui-icon-power-on-off',
				'data' => array(
					'action'  => 'project-activate',
					'hash'    => $hashes['project-activate'],
					'project' => $pid,
				),
			);
		}

		if ( isset( $res->url->config ) && ! empty( $res->url->config ) ) {
			$actions['configure'] = array(
				'name' => __( 'Configure', 'wpmudev' ),
				'url'  => $res->url->config,
				'type' => 'href',
				'icon' => 'sui-icon-wrench-tool',
				'data' => array(
					'action'  => 'project-configure',
					'hash'    => '',
					'project' => $pid,
				),
			);
		}

		if ( $res->is_active ) {
			// Don't allow deactivate of Dashboard on hosted sites.
			if ( $dash_pid !== $pid || false === isset( $_SERVER['WPMUDEV_HOSTED'] ) ) {
				$actions['deactivate'] = array(
					'name' => ( $res->is_network_admin ? __( 'Network Deactivate', 'wpmudev' ) : __( 'Deactivate', 'wpmudev' ) ),
					'url'  => '#deactivate=' . $pid,
					'type' => 'ajax',
					'icon' => 'sui-icon-power-on-off',
					'data' => array(
						'action'  => 'project-deactivate',
						'hash'    => $hashes['project-deactivate'],
						'project' => $pid,
					),
				);
			}
		} else {
			$actions['delete'] = array(
				'name'  => __( 'Delete', 'wpmudev' ),
				'url'   => '#',
				'type'  => 'ajax',
				'icon'  => 'sui-icon-trash',
				'class' => 'dashui-red-link',
				'data'  => array(
					'action'  => 'project-delete',
					'hash'    => $hashes['project-delete'],
					'project' => $pid,
				),
			);
		}
	} elseif ( $res->special ) {
		switch ( $res->special ) {
			case 'dropin':
				$main_action = array(
					'name' => __( 'Dropin', 'wpmudev' ),
					'url'  => '#',
					'type' => 'none',
					'icon' => '',
					'data' => array(
						'action'  => 'project-dropin',
						'hash'    => '',
						'project' => $pid,
					),
				);
				break;
			case 'muplugin':
				$main_action = array(
					'name' => __( 'MU Plugin', 'wpmudev' ),
					'url'  => '#',
					'type' => 'none',
					'icon' => '',
					'data' => array(
						'action'  => 'project-muplugin',
						'hash'    => '',
						'project' => $pid,
					),
				);
				break;
			default:
				break;
		}
	} elseif ( $res->is_active ) {
		if ( isset( $res->url->config ) && ! empty( $res->url->config ) ) {
			$main_action = array(
				'name' => __( 'Configure', 'wpmudev' ),
				'url'  => $res->url->config,
				'type' => 'href',
				'icon' => 'sui-icon-wrench-tool',
				'data' => array(
					'action'  => 'project-configure',
					'hash'    => '',
					'project' => $pid,
				),
			);

			$actions['configure'] = array(
				'name' => __( 'Configure', 'wpmudev' ),
				'url'  => $res->url->config,
				'type' => 'href',
				'icon' => 'sui-icon-wrench-tool',
				'data' => array(
					'action'  => 'project-configure',
					'hash'    => '',
					'project' => $pid,
				),
			);
		}

		// Don't allow deactivate of Dashboard on hosted sites.
		if ( $dash_pid !== $pid || false === isset( $_SERVER['WPMUDEV_HOSTED'] ) ) {
			$actions['deactivate'] = array(
				'name' => ( $res->is_network_admin ? __( 'Network Deactivate', 'wpmudev' ) : __( 'Deactivate', 'wpmudev' ) ),
				'url'  => '#deactivate=' . $pid,
				'type' => 'ajax',
				'icon' => 'sui-icon-power-on-off',
				'data' => array(
					'action'  => 'project-deactivate',
					'hash'    => $hashes['project-deactivate'],
					'project' => $pid,
				),
			);
		}
	} else {
		// Activate.
		$main_action = array(
			'name' => ( $res->is_network_admin ? __( 'Network Activate', 'wpmudev' ) : __( 'Activate', 'wpmudev' ) ),
			'url'  => '#activate=' . $pid,
			'type' => 'ajax',
			'icon' => 'sui-icon-power-on-off',
			'data' => array(
				'action'  => 'project-activate',
				'hash'    => $hashes['project-activate'],
				'project' => $pid,
			),
		);

		$actions['activate'] = array(
			'name' => ( $res->is_network_admin ? __( 'Network Activate', 'wpmudev' ) : __( 'Activate', 'wpmudev' ) ),
			'url'  => '#activate=' . $pid,
			'type' => 'ajax',
			'icon' => 'sui-icon-power-on-off',
			'data' => array(
				'action'  => 'project-activate',
				'hash'    => $hashes['project-activate'],
				'project' => $pid,
			),
		);

		$actions['delete'] = array(
			'name'  => __( 'Delete', 'wpmudev' ),
			'url'   => '#',
			'type'  => 'href',
			'icon'  => 'sui-icon-trash',
			'class' => 'sui-button-delete',
			'data'  => array(
				'action'  => 'project-delete',
				'hash'    => $hashes['project-delete'],
				'project' => $pid,
			),
		);
	}

	$main_action_class = 'sui-button-icon';
}

// Show special error and message if Upfront not installed.
if ( $res->is_installed && $res->need_upfront ) {
	if ( ! WPMUDEV_Dashboard::$site->is_upfront_installed() ) {
		// This upfront theme needs Upfront parent to work!
		echo 'Upfront needed';
	}
}

// Pic Gallery.
$gallery_items = array();
if ( ! empty( $res->url->video ) ) {
	$gallery_items[] = array(
		'thumb' => $res->url->thumbnail,
		'full'  => $res->url->video,
		'desc'  => '',
		'type'  => 'video',
	);
}
if ( is_array( $res->screenshots ) ) {
	foreach ( $res->screenshots as $item ) {
		$gallery_items[] = array(
			'thumb' => $item['url'],
			'full'  => $item['url'],
			'desc'  => $item['desc'],
			'type'  => 'image',
		);
	}
}

if ( empty( $gallery_items ) ) {
	$gallery_items[] = array(
		'thumb' => $res->url->thumbnail,
		'full'  => $res->url->thumbnail,
		'desc'  => '',
		'type'  => 'image',
	);
}

$slider_class = '';
if ( 1 === count( $gallery_items ) ) {
	$slider_class = 'no-nav';
}

$has_features = false;
$features     = array(
	0 => array(),
	1 => array(),
);
// Chunk feature into 2.
if ( is_array( $res->features ) && ! empty( $res->features ) ) {
	$has_features = true;
	$chunk_size   = ceil( count( $res->features ) / 2 );
	$features     = array_chunk( $res->features, $chunk_size );
}

// Don't show secondary actions if there is only one and it's the same one as main action.
$secondary_action_same_as_primary = false;
if ( 1 === count( $actions ) && $res->is_installed ) {
	$actions_action_index = array_keys( $actions )[0];
	if ( isset( $actions[ $actions_action_index ]['data'] ) && isset( $main_action['data'] ) ) {
		$secondary_action_same_as_primary = $actions[ $actions_action_index ]['data']['action'] === $main_action['data']['action'];
	}
}

$attr = array(
	'project'             => $pid,
	'licensed'            => intval( $res->is_licensed ),
	'installed'           => intval( $res->is_installed ),
	'has-update'          => intval( $res->has_update ),
	'is-compatible'       => intval( $res->is_compatible ),
	'incompatible-reason' => $incompatible_reason,
	'active'              => intval( $res->is_active ),
	'order'               => intval( $res->default_order ),
	'popularity'          => $res->popularity,
	'downloads'           => $res->downloads,
	'released'            => $res->release_stamp,
	'updated'             => $res->update_stamp,
	'type'                => $res->type,
	'name'                => esc_html( $res->name ),
	'info'                => esc_html( $res->info ),
);

foreach ( $res->tags as $tid => $plugin_tag ) {
	$attr[ 'plugin-tag-' . $tid ] = 1;
}
?>
<div class="sui-hidden"><?php require WPMUDEV_Dashboard::$site->plugin_path . 'template/sui/element-project-info/plugin-row.php'; ?></div>

<div class="sui-modal sui-modal-lg">
	<div
		role="dialog"
		id="plugin-modal-<?php echo esc_attr( $pid ); ?>"
		class="sui-modal-content js-plugin-modal sui-content-fade-in"
		aria-modal="true"
		aria-labelledby="dialogTitle<?php echo esc_attr( $pid ); ?>2"
		aria-describedby="dialogDescription<?php echo esc_attr( $pid ); ?>2"
		data-project="<?php echo esc_attr( $pid ); ?>"
		data-hash="<?php echo esc_attr( wp_create_nonce( 'show-popup' ) ); ?>">
		<div class="sui-box">
			<div class="sui-box-header">
				<h3 class="sui-box-title" id="dialogTitle<?php echo esc_attr( $pid ); ?>2"><?php echo esc_html( $res->name ); ?></h3>
				<div class="sui-actions-right">

					<?php if ( ! empty( $incompatible_reason ) ) : ?>
						<span class="sui-tag sui-tag-sm sui-tag-red sui-tag-ghost"><?php echo esc_html( $incompatible_reason ); ?></span>
					<?php endif; ?>


					<?php if ( ! empty( $modal_install_button ) ) : ?>
						<a
							class="sui-button <?php echo esc_attr( $modal_install_button['class'] ); ?>"
							href="<?php echo esc_url( $modal_install_button['url'] ); ?>"
							data-type="<?php echo esc_attr( $modal_install_button['type'] ); ?>"
							<?php if ( isset( $modal_install_button['data'] ) && is_array( $modal_install_button['data'] ) ) : ?>
								<?php foreach ( $modal_install_button['data'] as $key_attr => $data_attr ) : ?>
									data-<?php echo esc_attr( $key_attr ); ?>="<?php echo esc_attr( $data_attr ); ?>"
								<?php endforeach; ?>
							<?php endif; ?>
						>
					<span class="sui-loading-text">
						<?php if ( $modal_install_button['icon'] ) : ?>
							<i class="<?php echo esc_attr( $modal_install_button['icon'] ); ?>"></i>
						<?php endif; ?>
						<?php echo esc_html( $modal_install_button['name'] ); ?>
					</span>
							<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
						</a>
					<?php endif; ?>


					<?php if ( ! empty( $main_action ) ) : ?>
						<a
							class="sui-button <?php echo esc_attr( $main_action_class_modal ); ?>"
							href="<?php echo esc_url( $main_action['url'] ); ?>"
							data-type="<?php echo esc_attr( $main_action['type'] ); ?>"
							<?php if ( isset( $main_action['data'] ) && is_array( $main_action['data'] ) ) : ?>
								<?php foreach ( $main_action['data'] as $key_attr => $data_attr ) : ?>
									data-<?php echo esc_attr( $key_attr ); ?>="<?php echo esc_attr( $data_attr ); ?>"
								<?php endforeach; ?>
							<?php endif; ?>
						>
					<span class="sui-loading-text">
						<?php echo esc_html( $main_action['name'] ); ?>
					</span>
							<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
						</a>
					<?php endif; ?>

				</div>
				<button class="sui-button-icon plugin-modal-close" data-modal-close="" style="margin-left: 10px">
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog.', 'wpmudev' ); ?></span>
				</button>
			</div>
			<?php // load async later. ?>
			<div class="sui-box-body js-dialog-body js-is-loading">
				<div class="sui-block-content-center js-dialog-loader">
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
				</div>
			</div>

			<div class="sui-box-footer">
				<a class="sui-button sui-button-ghost plugin-modal-close" data-modal-close"><?php esc_html_e( 'Close', 'wpmudev' ); ?></a>
				<div class="sui-actions-right">

					<?php if ( ! empty( $modal_install_button ) ) : ?>
						<a
							class="sui-button <?php echo esc_attr( $modal_install_button['class'] ); ?>"
							href="<?php echo esc_url( $modal_install_button['url'] ); ?>"
							data-type="<?php echo esc_attr( $modal_install_button['type'] ); ?>"
							<?php if ( isset( $modal_install_button['data'] ) && is_array( $modal_install_button['data'] ) ) : ?>
								<?php foreach ( $modal_install_button['data'] as $key_attr => $data_attr ) : ?>
									data-<?php echo esc_attr( $key_attr ); ?>="<?php echo esc_attr( $data_attr ); ?>"
								<?php endforeach; ?>
							<?php endif; ?>
						>
					<span class="sui-loading-text">
						<?php if ( $modal_install_button['icon'] ) : ?>
							<i class="<?php echo esc_attr( $modal_install_button['icon'] ); ?>"></i>
						<?php endif; ?>
						<?php echo esc_html( $modal_install_button['name'] ); ?>
					</span>
							<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
						</a>
					<?php endif; ?>


					<?php if ( ! empty( $main_action ) ) : ?>
						<a
							class="sui-button <?php echo esc_attr( $main_action_class_modal ); ?>"
							href="<?php echo esc_url( $main_action['url'] ); ?>"
							data-type="<?php echo esc_attr( $main_action['type'] ); ?>"
							<?php if ( isset( $main_action['data'] ) && is_array( $main_action['data'] ) ) : ?>
								<?php foreach ( $main_action['data'] as $key_attr => $data_attr ) : ?>
									data-<?php echo esc_attr( $key_attr ); ?>="<?php echo esc_attr( $data_attr ); ?>"
								<?php endforeach; ?>
							<?php endif; ?>
						>
					<span class="sui-loading-text">

						<?php echo esc_html( $main_action['name'] ); ?>
					</span>
							<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) : ?>
<div class="sui-modal sui-modal-sm">

	<div
		role="dialog"
		id="plugin-modal-after-install-<?php echo esc_attr( $pid ); ?>"
		class="sui-modal-content sui-content-fade-in"
		aria-modal="true"
		aria-labelledby="dialogTitleafter<?php echo esc_attr( $pid ); ?>2"
		aria-describedby="dialogDescriptionafter<?php echo esc_attr( $pid ); ?>2"
		data-project="<?php echo esc_attr( $pid ); ?>"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">

				<button class="sui-button-icon plugin-modal-close sui-button-float--right" data-modal-close="">
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog.', 'wpmudev' ); ?></span>
				</button>
				<h3
					class="sui-box-title sui-lg"
					id="dialogTitleafter<?php echo esc_attr( $pid ); ?>"
				>
					<?php echo esc_html( sprintf( __( '%s installed!', 'wpmudev' ), $res->name ) ); ?>
				</h3>
				<p id="dialogDescriptionafter<?php echo esc_attr( $pid ); ?>" class="sui-description">
					<?php esc_html_e( 'Would you like to activate it now?', 'wpmudev' ); ?>
				</p>
			</div>

			<div class="sui-box-footer sui-flatten sui-content-center">
				<a class="sui-button plugin-modal-close" href="#"><?php esc_html_e( 'CONTINUE', 'wpmudev' ); ?></a>
				<a
					class="sui-button sui-button-blue"
					data-action="project-activate"
					href="#"
					data-hash="<?php echo esc_attr( $hashes['project-activate'] ); ?>"
					data-project="<?php echo esc_attr( $pid ); ?>"
				>
					<span class="sui-loading-text">
						<?php esc_html_e( 'ACTIVATE', 'wpmudev' ); ?>
					</span>
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
				</a>
			</div>

			<div class="sui-block-content-center"></div>

		</div>

	</div>

</div>
<?php endif; ?>