<?php
if( !defined( 'ABSPATH' ) )
	die( 'Cheatin\' uh?' );

/* Add a metabox in admin menu page */
add_action( 'admin_head-nav-menus.php', 'bawllm_add_nav_menu_metabox' );
function bawllm_add_nav_menu_metabox() {
	add_meta_box( 'bawllm', __( 'Login/Logout links' ) . ' v' . BAWLLM_VERSION, 'bawllm_nav_menu_metabox', 'nav-menus', 'side', 'default' );
}

/* The metabox code : Awesome code stolen from screenfeed.fr (GregLone) Thank you mate. */
function bawllm_nav_menu_metabox( $object ) {
	global $nav_menu_selected_id;

	$elems = array(	'#bawlogin#' => __( 'Log In' ), 
					'#bawlogout#' => __( 'Log Out' ), 
					'#bawloginout#' => __( 'Log In' ) . '|' . __( 'Log Out' ), 
					'#bawregister#' => __( 'Register' ) 
				);
	class bawlogItems {
		public $db_id = 0;
		public $object = 'bawlog';
		public $object_id;
		public $menu_item_parent = 0;
		public $type = 'custom';
		public $title;
		public $url;
		public $target = '';
		public $attr_title = '';
		public $classes = array();
		public $xfn = '';
	}

	$elems_obj = array();
	foreach ( $elems as $value => $title ) {
		$elems_obj[ $title ] 				= new bawlogItems();
		$elems_obj[ $title ]->object_id		= esc_attr( $value );
		$elems_obj[ $title ]->title			= esc_attr( $title );
		$elems_obj[ $title ]->url			= esc_attr( $value );
	}

	$walker = new Walker_Nav_Menu_Checklist( array() );
	?>
	<div id="login-links" class="loginlinksdiv">

		<div id="tabs-panel-login-links-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">
			<ul id="login-linkschecklist" class="list:login-links categorychecklist form-no-clear">
				<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $elems_obj ), 0, (object) array( 'walker' => $walker ) ); ?>
			</ul>
		</div>

		<p class="button-controls">
			<span class="list-controls hide-if-no-js">
				<a href="javascript:void(0);" class="help" onclick="jQuery( '#help-login-links' ).toggle();"><?php _e( 'Help' ); ?></a>
				<span class="hide-if-js" id="help-login-links"><br /><a name="help-login-links"></a>
					<?php
					if( get_locale() == 'fr_FR' ) { // Light L10N
						echo '&#9725; Vous pouvez ajouter une page de redirection apr&egrave;s le login/logout du membre en ajoutant simplement le lien relatif apr&egrave;s le mot cl&eacute; dans le lien, exemple <code>#bawloginout#index.php</code>.';
						echo '<br />&#9725; Vous pouvez aussi ajouter <code>%actualpage%</code> pour que la redirection soit faite sur la page en cours, exemple : <code>#bawloginout#%actualpage%</code>.';
					}else{
						echo '&#9725; You can add a redirection page after the user\'s login/logout simply adding a relative link after the link\'s keyword, example <code>#bawloginout#index.php</code>.';
						echo '<br />&#9725; You can also add <code>%actualpage%</code> to redirect the user on the actual visited page, example : <code>#bawloginout#%actualpage%</code>.';
					}
					
					?>
				</span>
			</span>

			<span class="add-to-menu">
				<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu' ); ?>" name="add-login-links-menu-item" id="submit-login-links" />
				<span class="spinner"></span>
			</span>
		</p>

	</div>
	<?php
}

/* Modify the "type_label" */
add_filter( 'wp_setup_nav_menu_item', 'bawllm_nav_menu_type_label' );
function bawllm_nav_menu_type_label( $menu_item ) {
	$elems = array( '#bawlogin#', '#bawlogout#', '#bawloginout#', '#bawregister#' );
	if ( isset( $menu_item->object, $menu_item->url ) && 'custom'== $menu_item->object && in_array( $menu_item->url, $elems ) ) {
		$menu_item->type_label = ( get_locale() == 'fr_FR' ? 'Connexion' : 'Connection' );
	}

	return $menu_item;
}