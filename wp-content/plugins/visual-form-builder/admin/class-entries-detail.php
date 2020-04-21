<?php
/**
 * Class that builds our Entries detail page
 *
 * @since 1.4
 */
class Visual_Form_Builder_Entries_Detail {
	/**
	 * [__construct description]
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'entries_detail' ) );
	}

	public function entries_detail() {
		global $wpdb;

		$entry_id = absint( $_GET['entry'] );

		$entries = $wpdb->get_results( $wpdb->prepare( "SELECT forms.form_title, entries.* FROM " . VFB_WP_FORMS_TABLE_NAME . " AS forms INNER JOIN " . VFB_WP_ENTRIES_TABLE_NAME . " AS entries ON entries.form_id = forms.form_id WHERE entries.entries_id  = %d", $entry_id ) );

		echo '<p>' . sprintf( '<a href="?page=%s" class="view-entry">&laquo; Back to Entries</a>', $_GET['page'] ) . '</p>';

		// Get the date/time format that is saved in the options table
		$date_format = get_option('date_format');
		$time_format = get_option('time_format');

		// Loop trough the entries and setup the data to be displayed for each row
		foreach ( $entries as $entry ) {
			$data = unserialize( $entry->data );
?>
			<form id="entry-edit" method="post" action="">
			<h3><span><?php echo stripslashes( $entry->form_title ); ?> : <?php echo __( 'Entry' , 'visual-form-builder'); ?> # <?php echo $entry->entries_id; ?></span></h3>
            <div id="vfb-poststuff" class="metabox-holder has-right-sidebar">
				<div id="side-info-column" class="inner-sidebar">
					<div id="side-sortables">
						<div id="submitdiv" class="postbox">
							<h3><span><?php echo __( 'Details' , 'visual-form-builder'); ?></span></h3>
							<div class="inside">
							<div id="submitbox" class="submitbox">
								<div id="minor-publishing">
									<div id="misc-publishing-actions">
										<div class="misc-pub-section">
											<span><strong><?php echo  __( 'Form Title' , 'visual-form-builder'); ?>: </strong><?php echo stripslashes( $entry->form_title ); ?></span>
										</div>
										<div class="misc-pub-section">
											<span><strong><?php echo  __( 'Date Submitted' , 'visual-form-builder'); ?>: </strong><?php echo date( "$date_format $time_format", strtotime( $entry->date_submitted ) ); ?></span>
										</div>
										<div class="misc-pub-section">
											<span><strong><?php echo __( 'IP Address' , 'visual-form-builder'); ?>: </strong><?php echo $entry->ip_address; ?></span>
										</div>
										<div class="misc-pub-section">
											<span><strong><?php echo __( 'Email Subject' , 'visual-form-builder'); ?>: </strong><?php echo stripslashes( $entry->subject ); ?></span>
										</div>
										<div class="misc-pub-section">
											<span><strong><?php echo __( 'Sender Name' , 'visual-form-builder'); ?>: </strong><?php echo stripslashes( $entry->sender_name ); ?></span>
										</div>
										<div class="misc-pub-section">
											<span><strong><?php echo __( 'Sender Email' , 'visual-form-builder'); ?>: </strong><a href="mailto:<?php echo stripslashes( $entry->sender_email ); ?>"><?php echo stripslashes( $entry->sender_email ); ?></a></span>
										</div>
										<div class="misc-pub-section">
											<span><strong><?php echo __( 'Emailed To' , 'visual-form-builder'); ?>: </strong><?php echo preg_replace('/\b([A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b/i', '<a href="mailto:$1">$1</a>', implode( ',', unserialize( stripslashes( $entry->emails_to ) ) ) ); ?></span>
										</div>
										<div class="clear"></div>
									</div> <!--#misc-publishing-actions -->
								</div> <!-- #minor-publishing -->

								<div id="major-publishing-actions">
									<div id="delete-action">
										<?php echo sprintf( '<a class="submitdelete deletion entry-delete" href="?page=%2$s&action=%3$s&entry=%4$d">%1$s</a>', __( 'Move to Trash', 'visual-form-builder' ), $_GET['page'], 'trash', $entry_id ); ?>
									</div>
									<div id="publishing-action">
										<?php submit_button( __( 'Print', 'visual-form-builder' ), 'secondary', 'submit', false, array( 'onclick' => 'window.print();return false;' ) ); ?>
									</div>
									<div class="clear"></div>
								</div> <!-- #major-publishing-actions -->
							</div> <!-- #submitbox -->
							</div> <!-- .inside -->
						</div> <!-- #submitdiv -->
					</div> <!-- #side-sortables -->
				</div> <!-- #side-info-column -->
            <!--</div>  #poststuff -->
			<div id="vfb-entries-body-content">
        <?php
			$count = 0;
			$open_fieldset = $open_section = false;

			foreach ( $data as $k => $v ) :
				if ( !is_array( $v ) ) :
					if ( $count == 0 ) {
						echo '<div class="postbox">
							<h3><span>' . $entry->form_title . ' : ' . __( 'Entry' , 'visual-form-builder') .' #' . $entry->entries_id . '</span></h3>
							<div class="inside">';
					}

					echo '<h4>' . ucwords( $k ) . '</h4>';
					echo $v;
					$count++;
				else :
					// Cast each array as an object
					$obj = (object) $v;

					if ( $obj->type == 'fieldset' ) :
						// Close each fieldset
						if ( $open_fieldset == true )
							echo '</table>';

						echo '<h3>' . stripslashes( $obj->name ) . '</h3><table class="form-table">';

						$open_fieldset = true;
					endif;


					switch ( $obj->type ) :
						case 'fieldset' :
						case 'section' :
						case 'submit' :
						case 'page-break' :
						case 'verification' :
						case 'secret' :
							break;

						case 'file-upload' :
							?>
							<tr valign="top">
								<th scope="row"><label for="field[<?php echo $obj->id; ?>]"><?php echo stripslashes( $obj->name ); ?></label></th>
								<td style="background:#eee;border:1px solid #ddd"><a href="<?php esc_attr_e( $obj->value ); ?>" target="_blank"><?php echo esc_html( $obj->value ); ?></a></td>
							</tr>
	                    	<?php
							break;

						case 'textarea' :
						case 'html' :
							?>
							<tr valign="top">
								<th scope="row"><label for="field[<?php echo $obj->id; ?>]"><?php echo stripslashes( $obj->name ); ?></label></th>
								<td style="background:#eee;border:1px solid #ddd"><?php echo wpautop( esc_html( $obj->value ) ); ?></td>
							</tr>
	                    	<?php
							break;

						default :
							?>
							<tr valign="top">
								<th scope="row"><label for="field[<?php echo $obj->id; ?>]"><?php echo stripslashes( $obj->name ); ?></label></th>
								<td style="background:#eee;border:1px solid #ddd"><?php echo esc_html( $obj->value ); ?></td>
							</tr>
                        	<?php
							break;

					endswitch;
				endif;
			endforeach;

			if ( $count > 0 )
				echo '</div></div>';

		}
		echo '</table></div>';
		echo '<br class="clear"></div>';


		echo '</form>';
	}
}
