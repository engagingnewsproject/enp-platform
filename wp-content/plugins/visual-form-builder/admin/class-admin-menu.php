<?php
/**
 * Add all admin menus
 *
 * Defines and adds all admin menus
 *
 */
class Visual_Form_Builder_Admin_Menu {

	/**
	 * Initial setup
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// Add main menu
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Adds the main menu
	 *
	 * @since  3.0
	 * @access public
	 * @return void
	 */
	public function add_menu() {
		global $pagenow, $typenow;

		$page = add_menu_page(
			'Visual Form Builder',
			__( 'Visual Form Builder', 'visual-form-builder' ),
			'manage_options',
			'visual-form-builder',
			array( $this, 'admin' ),
			'dashicons-feedback'
		);

		$all_forms = add_submenu_page(
			'visual-form-builder',
			__( 'Visual Form Builder', 'visual-form-builder' ),
			__( 'All Forms', 'visual-form-builder' ),
			'manage_options',
			'visual-form-builder',
			array( $this, 'admin' )
		);

		$add_new = add_submenu_page(
			'visual-form-builder',
			__( 'Add New', 'visual-form-builder' ),
			__( 'Add New', 'visual-form-builder' ),
			'manage_options',
			'vfb-add-new',
			array( $this, 'add_new_form' )
		);

		$entries = add_submenu_page(
			'visual-form-builder',
			__( 'Entries', 'visual-form-builder' ),
			__( 'Entries', 'visual-form-builder' ),
			'manage_options',
			'vfb-entries',
			array( $this, 'entries' )
		);

		$export = add_submenu_page(
			'visual-form-builder',
			__( 'Export', 'visual-form-builder' ),
			__( 'Export', 'visual-form-builder' ),
			'manage_options',
			'vfb-export',
			array( $this, 'export' )
		);

		$settings = add_submenu_page(
			'visual-form-builder',
			__( 'Settings', 'visual-form-builder' ),
			__( 'Settings', 'visual-form-builder' ),
			'manage_options',
			'vfb-settings',
			array( $this, 'settings' )
		);

		$scripts = new Visual_Form_Builder_Admin_Scripts_Loader();
		add_action( 'load-' . $page, array( $scripts, 'add_css' ) );
		add_action( 'load-' . $page, array( $scripts, 'add_js' ) );

		add_action( 'load-' . $add_new, array( $scripts, 'add_css' ) );
		add_action( 'load-' . $add_new, array( $scripts, 'add_js' ) );

		add_action( 'load-' . $entries, array( $scripts, 'add_css' ) );
		add_action( 'load-' . $entries, array( $scripts, 'add_js' ) );

		add_action( 'load-' . $settings, array( $scripts, 'add_css' ) );
		add_action( 'load-' . $settings, array( $scripts, 'add_js' ) );

		add_action( 'load-' . $export, array( $scripts, 'add_css' ) );
		add_action( 'load-' . $export, array( $scripts, 'add_js' ) );

		// Enable Screen Options tabs here (saving is hooked in main plugin instance() )
		$screen_options = new Visual_Form_Builder_Admin_Screen_Options();
		add_action( 'load-' . $page, array( $screen_options, 'add_option' ) );
		add_action( 'load-' . $entries, array( $screen_options, 'add_option_entries' ) );

		// Add Meta Boxes
		$meta_boxes = new Visual_Form_Builder_Meta_Boxes();
		add_action( 'load-' . $page, array( $meta_boxes, 'add_meta_boxes' ) );

		// Add Help dropdown
		add_action( 'load-' . $page, array( $this, 'help' ) );
	}

	/**
	 * Load either the All Forms list or Edit Form view
	 *
	 * @since  3.0
	 * @access public
	 * @return void
	 */
	public function admin() {
		if ( isset( $_GET['form'] ) && 'edit' == $_GET['action'] )
			$this->edit_form();
		else
			$this->forms_list();
	}

	/**
	 * View for All Forms list
	 *
	 * @since  3.0
	 * @access public
	 * @return void
	 */
	public function forms_list() {
		$forms = new Visual_Form_Builder_Forms_List();
	?>
	<div class="wrap">
		<h1>
		<?php
			_e( 'Visual Form Builder', 'visual-form-builder' );

			// Add New link
			echo sprintf(
				' <a href="%1$s" class="page-title-action">%2$s</a>',
				esc_url( admin_url( 'admin.php?page=vfb-add-new' ) ),
				esc_html( __( 'Add New', 'visual-form-builder' ) )
			);

			// If searched, output the query
			if ( isset( $_POST['s'] ) && !empty( $_POST['s'] ) )
				echo '<span class="subtitle">' . sprintf( __( 'Search results for "%s"' , 'visual-form-builder'), esc_html( $_POST['s'] ) );

			$form_nav_selected_id = isset( $_GET['form'] ) ? $_GET['form'] : '0';
		?>
		</h1>

		<div id="vfb-form-list">
			<div id="vfb-sidebar">
				<div id="vfb-upgrade-column">
					<div class="vfb-pro-upgrade">
				    	<h2><a href="http://vfbpro.com">VFB Pro</a></h2>
				        <p class="vfb-pro-call-to-action">
				        	<a class="vfb-btn vfb-btn-inverse" href="http://vfbpro.com/pages/pricing" target="_blank"><?php _e( 'View Pricing' , 'visual-form-builder'); ?></a>
				        	<a class="vfb-btn vfb-btn-primary" href="http://vfbpro.com/pages/pricing" target="_blank"><?php _e( 'Buy Now' , 'visual-form-builder'); ?></a>
				        </p>
				        <p class="vfb-pro-call-to-action">
				        	<a class="button" href="http://demo.vfbpro.com" target="_blank"><?php _e( 'Try the Free Live Demo &rarr;' , 'visual-form-builder'); ?></a>
				        </p>
				        <h3><?php _e( 'New Features' , 'visual-form-builder'); ?></h3>
				        <ul>
				        	<li><a href="http://vfbpro.com/collections/add-ons"><?php _e( 'Now with Add-Ons' , 'visual-form-builder'); ?></a></li>
				            <li><?php _e( 'Akismet Support' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'reCAPTCHA v2' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Conditional Logic' , 'visual-form-builder'); ?></li>
				            <li><?php _e( '15 new Form Fields' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Complete Entries Management' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Import/Export' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Quality HTML Email Template' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Plain Text Email Option' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Email Designer' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Data &amp; Form Migration' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Scheduling' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Limit Form Entries' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Form Paging' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Custom Capabilities' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Automatic Updates' , 'visual-form-builder'); ?></li>
				        </ul>

				        <p><a href="http://vfbpro.com/pages/features"><?php _e( 'View all features' , 'visual-form-builder'); ?></a></p>
				    </div> <!-- .vfb-pro-upgrade -->

			   		<h3><?php _e( 'Promote Visual Form Builder' , 'visual-form-builder'); ?></h3>
			        <ul id="promote-vfb">
			        	<li id="twitter"><?php _e( 'Follow VFB Pro on Twitter' , 'visual-form-builder'); ?>: <a href="http://twitter.com/#!/vfbpro">@vfbpro</a></li>
			            <li id="star"><a href="http://wordpress.org/extend/plugins/visual-form-builder/"><?php _e( 'Rate Visual Form Builder on WordPress.org' , 'visual-form-builder'); ?></a></li>
			            <li id="paypal">
			                <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=G87A9UN9CLPH4&lc=US&item_name=Visual%20Form%20Builder&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted"><img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" width="74" height="21"></a>
			            </li>
			        </ul>
			    </div> <!-- #vfb-upgrade-column -->
			</div> <!-- #vfb-sidebar -->
			<div id="vfb-main" class="vfb-order-type-list">
				<form id="forms-filter" method="post" action="">
				<?php
					$forms->views();
					$forms->prepare_items();

					$forms->search_box( 'search', 'search_id' );
					$forms->display();
				?>
				</form>
			</div> <!-- #vfb-main -->
		</div> <!-- #vfb-form-list -->
	</div> <!-- .wrap -->
	<?php
	}

	/**
	 * Display the Add New form
	 *
	 * Uses the Visual_Form_Builder_Forms_New class
	 *
	 * @since  3.0
	 * @access public
	 * @return void
	 */
	public function add_new_form() {
		$add_new = new Visual_Form_Builder_Forms_New();
		$add_new->display();
	}

	/**
	 * View for Edit Form
	 *
	 * @since  3.0
	 * @access public
	 * @return void
	 */
	public function edit_form() {
		$edit = new Visual_Form_Builder_Forms_Edit();
		$edit->display();
	}

	/**
	 * View for Entries
	 * @return [type] [description]
	 */
	public function entries() {
		$entries_list   = new Visual_Form_Builder_Entries_List();
		$entries_detail = new Visual_Form_Builder_Entries_Detail();
	?>
	<div class="wrap">
		<h1>
			<?php
				_e( 'Entries', 'visual-form-builder' );

				// If searched, output the query
				if ( isset( $_POST['s'] ) && !empty( $_POST['s'] ) )
					echo '<span class="subtitle">' . sprintf( __( 'Search results for "%s"' , 'visual-form-builder' ), esc_html( $_POST['s'] ) );
			?>
		</h1>
		<?php
		if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'view', 'edit', 'update_entry' ) ) ) :
			$entries_detail->entries_detail();
		else :
			$entries_list->views();
			$entries_list->prepare_items();
		?>
    	<form id="entries-filter" method="post" action="">
		<?php
        	$entries_list->search_box( 'search', 'search_id' );
        	$entries_list->display();
		?>
        </form>
	<?php endif; ?>
	</div>
	<?php
	}

	/**
	 * View for the Export page
	 *
	 * @since  3.0
	 * @access public
	 * @return void
	 */
	public function export() {
		$export = new Visual_Form_Builder_Export();
		$export->display();
	}

	/**
	 * View for the Settings page
	 *
	 * @since  3.0
	 * @access public
	 * @return void
	 */
	public function settings() {
		$settings = new Visual_Form_Builder_Page_Settings();
		$settings->display();
	}

	/**
	 * Add Help dropdown
	 * @return [type] [description]
	 */
	public function help() {
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-getting-started',
			'title' => 'Getting Started',
			'content' => '<ul>
						<li>Click on the + tab, give your form a name and click Create Form.</li>
						<li>Select form fields from the box on the left and click a field to add it to your form.</li>
						<li>Edit the information for each form field by clicking on the down arrow.</li>
						<li>Drag and drop the elements to put them in order.</li>
						<li>Click Save Form to save your changes.</li>
					</ul>'
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-item-config',
			'title' => 'Form Item Configuration',
			'content' => "<ul>
						<li><em>Name</em> will change the display name of your form input.</li>
						<li><em>Description</em> will be displayed below the associated input.</li>
						<li><em>Validation</em> allows you to select from several of jQuery's Form Validation methods for text inputs. For more about the types of validation, read the <em>Validation</em> section below.</li>
						<li><em>Required</em> is either Yes or No. Selecting 'Yes' will make the associated input a required field and the form will not submit until the user fills this field out correctly.</li>
						<li><em>Options</em> will only be active for Radio and Checkboxes.  This field contols how many options are available for the associated input.</li>
						<li><em>Size</em> controls the width of Text, Textarea, Select, and Date Picker input fields.  The default is set to Medium but if you need a longer text input, select Large.</li>
						<li><em>CSS Classes</em> allow you to add custom CSS to a field.  This option allows you to fine tune the look of the form.</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-validation',
			'title' => 'Validation',
			'content' => "<p>Visual Form Builder uses the <a href='http://docs.jquery.com/Plugins/Validation/Validator'>jQuery Form Validation plugin</a> to perform clientside form validation.</p>
					<ul>

						<li><em>Email</em>: makes the element require a valid email.</li>
						<li><em>URL</em>: makes the element require a valid url.</li>
						<li><em>Date</em>: makes the element require a date. <a href='http://docs.jquery.com/Plugins/Validation/Methods/date'>Refer to documentation for various accepted formats</a>.
						<li><em>Number</em>: makes the element require a decimal number.</li>
						<li><em>Digits</em>: makes the element require digits only.</li>
						<li><em>Phone</em>: makes the element require a US or International phone number. Most formats are accepted.</li>
						<li><em>Time</em>: choose either 12- or 24-hour time format (NOTE: only available with the Time field).</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-confirmation',
			'title' => 'Confirmation',
			'content' => "<p>Each form allows you to customize the confirmation by selecing either a Text Message, a WordPress Page, or to Redirect to a URL.</p>
					<ul>
						<li><em>Text</em> allows you to enter a custom formatted message that will be displayed on the page after your form is submitted. HTML is allowed here.</li>
						<li><em>Page</em> displays a dropdown of all WordPress Pages you have created. Select one to redirect the user to that page after your form is submitted.</li>
						<li><em>Redirect</em> will only accept URLs and can be used to send the user to a different site completely, if you choose.</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-notification',
			'title' => 'Notification',
			'content' => "<p>Send a customized notification email to the user when the form has been successfully submitted.</p>
					<ul>
						<li><em>Sender Name</em>: the name that will be displayed on the email.</li>
						<li><em>Sender Email</em>: the email that will be used as the Reply To email.</li>
						<li><em>Send To</em>: the email where the notification will be sent. This must be a required text field with email validation.</li>
						<li><em>Subject</em>: the subject of the email.</li>
						<li><em>Message</em>: additional text that can be displayed in the body of the email. HTML tags are allowed.</li>
						<li><em>Include a Copy of the User's Entry</em>: appends a copy of the user's submitted entry to the notification email.</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-tips',
			'title' => 'Tips',
			'content' => "<ul>
						<li>Fieldsets, a way to group form fields, are an essential piece of this plugin's HTML. As such, at least one fieldset is required and must be first in the order. Subsequent fieldsets may be placed wherever you would like to start your next grouping of fields.</li>
						<li>Security verification is automatically included on very form. It's a simple logic question and should keep out most, if not all, spam bots.</li>
						<li>There is a hidden spam field, known as a honey pot, that should also help deter potential abusers of your form.</li>
						<li>Nesting is allowed underneath fieldsets and sections.  Sections can be nested underneath fieldsets.  Nesting is not required, however, it does make reorganizing easier.</li>
					</ul>"
		) );
	}
}
