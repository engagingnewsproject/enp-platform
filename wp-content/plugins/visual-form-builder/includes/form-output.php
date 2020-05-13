<?php
global $wpdb;

// Get global settings
$vfb_settings 	= get_option( 'vfb-settings' );

// Settings - Place Address labels above fields
$settings_address_labels	= isset( $vfb_settings['address-labels'] ) ? false : true;

// Extract shortcode attributes, set defaults
extract( shortcode_atts( array(
	'id' => ''
	), $atts )
);

// Add JavaScript files to the front-end, only once
if ( !$this->add_scripts )
	$this->scripts();

// Get form id.  Allows use of [vfb id=1] or [vfb 1]
$form_id = ( isset( $id ) && !empty( $id ) ) ? (int) $id : key( $atts );

// If form is submitted, show success message, otherwise the form
if ( isset( $_POST['vfb-submit'] ) && isset( $_POST['form_id'] ) && $_POST['form_id'] == $form_id ) {
	$output = $this->confirmation();
	return;
}

$order = sanitize_sql_orderby( 'form_id DESC' );
$form  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->form_table_name WHERE form_id = %d ORDER BY $order", $form_id ) );

// Return if no form found
if ( !$form )
	return;

// Get fields
$order_fields   = sanitize_sql_orderby( 'field_sequence ASC' );
$fields         = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->field_table_name WHERE form_id = %d ORDER BY $order_fields", $form_id ) );

// Setup default variables
$count = 1;
$open_fieldset = $open_section = false;
$submit = 'Submit';
$verification = '';

$label_alignment = ( $form->form_label_alignment !== '' ) ? esc_attr( " $form->form_label_alignment" ) : '';

// Start form container
$output .= sprintf( '<div id="vfb-form-%d" class="visual-form-builder-container">', $form_id );

$output .= sprintf(
	'<form id="%1$s-%2$d" class="visual-form-builder %3$s %4$s" method="post" enctype="multipart/form-data">
	<input type="hidden" name="form_id" value="%5$d" />',
	esc_attr( $form->form_key ),
	$form_id,
	"vfb-form-$form_id",
	$label_alignment,
	absint( $form->form_id )
);
foreach ( $fields as $field ) :
	$field_id		= absint( $field->field_id );
	$field_type 	= esc_html( $field->field_type );
	$field_name		= esc_html( stripslashes( $field->field_name ) );
	$required_span 	= ( !empty( $field->field_required ) && $field->field_required === 'yes' ) ? ' <span class="vfb-required-asterisk">*</span>' : '';
	$required 		= ( !empty( $field->field_required ) && $field->field_required === 'yes' ) ? esc_attr( ' required' ) : '';
	$validation 	= ( !empty( $field->field_validation ) ) ? esc_attr( " $field->field_validation" ) : '';
	$css 			= ( !empty( $field->field_css ) ) ? esc_attr( " $field->field_css" ) : '';
	$id_attr 		= "vfb-{$field_id}";
	$size			= ( !empty( $field->field_size ) ) ? esc_attr( " vfb-$field->field_size" ) : '';
	$layout 		= ( !empty( $field->field_layout ) ) ? esc_attr( " vfb-$field->field_layout" ) : '';
	$default 		= ( !empty( $field->field_default ) ) ? wp_specialchars_decode( esc_html( stripslashes( $field->field_default ) ), ENT_QUOTES ) : '';
	$description	= ( !empty( $field->field_description ) ) ? wp_specialchars_decode( esc_html( stripslashes( $field->field_description ) ), ENT_QUOTES ) : '';

	// Close each section
	if ( $open_section == true ) :
		// If this field's parent does NOT equal our section ID
		if ( $sec_id && $sec_id !== absint( $field->field_parent ) ) :
			$output .= '</div><div class="vfb-clear"></div>';
			$open_section = false;
		endif;
	endif;

	// Force an initial fieldset and display an error message to strongly encourage user to add one
		if ( $count === 1 && $field_type !== 'fieldset' ) :
			$output .= sprintf( '<fieldset class="vfb-fieldset"><div class="vfb-legend" style="background-color:#FFEBE8;border:1px solid #CC0000;"><h3>%1$s</h3><p style="color:black;">%2$s</p></div><ul class="section section-%3$d">',
			__( 'Oops! Missing Fieldset', 'visual-form-builder' ),
			__( 'If you are seeing this message, it means you need to <strong>add a Fieldset to the beginning of your form</strong>. Your form may not function or display properly without one.', 'visual-form-builder' ),
			$count
			);

			$count++;
		endif;

	if ( $field_type == 'fieldset' ) :
		// Close each fieldset
		if ( $open_fieldset == true )
			$output .= '</ul>&nbsp;</fieldset>';

		// Only display Legend if field name is not blank
		$legend = !empty( $field_name ) ? sprintf( '<div class="vfb-legend"><h3>%s</h3></div>', $field_name ) : '&nbsp;';

		$output .= sprintf(
			'<fieldset class="vfb-fieldset vfb-fieldset-%1$d %2$s %3$s" id="item-%4$s">%5$s<ul class="vfb-section vfb-section-%1$d">',
			$count,
			esc_attr( $field->field_key ),
			$css,
			$id_attr,
			$legend
		);

		$open_fieldset = true;
		$count++;

	elseif ( $field_type == 'section' ) :

		$output .= sprintf(
			'<div id="item-%1$s" class="vfb-section-div %2$s"><h4>%3$s</h4>',
			$id_attr,
			$css,
			$field_name
		);

		// Save section ID for future comparison
		$sec_id = $field_id;
		$open_section = true;

	elseif ( !in_array( $field_type, array( 'verification', 'secret', 'submit' ) ) ) :

		$columns_choice = ( !empty( $field->field_size ) && in_array( $field_type, array( 'radio', 'checkbox' ) ) ) ? esc_attr( " vfb-$field->field_size" ) : '';

		if ( $field_type !== 'hidden' ) :

			// Don't add for attribute for certain form items
			$for = !in_array( $field_type, array( 'checkbox', 'radio', 'time', 'address', 'instructions' ) ) ? ' for="%4$s"' : '';

			$output .= sprintf(
				'<li class="vfb-item vfb-item-%1$s %2$s %3$s" id="item-%4$s"><label' . $for . ' class="vfb-desc">%5$s %6$s</label>',
				$field_type,
				$columns_choice,
				$layout,
				$id_attr,
				$field_name,
				$required_span
			);
		endif;

	elseif ( in_array( $field_type, array( 'verification', 'secret' ) ) ) :

		if ( $field_type == 'verification' ) :
			$verification .= sprintf(
				'<fieldset class="vfb-fieldset vfb-fieldset-%1$d %2$s %3$s" id="item-%4$s" style="display:block"><div class="vfb-legend"><h3>%5$s</h3></div><ul class="vfb-section vfb-section-%1$d">',
				$count,
				esc_attr( $field->field_key ),
				$css,
				$id_attr,
				$field_name
			);
		endif;

		if ( $field_type == 'secret' ) :
			// Default logged in values
			$logged_in_display = $logged_in_value = '';

			// If the user is logged in, fill the field in for them
			if ( is_user_logged_in() ) :
				// Hide the secret field if logged in
				$logged_in_display = ' style="display:none;"';
				$logged_in_value = 14;

				// Get logged in user details
				$user = wp_get_current_user();
				$user_identity = ! empty( $user->ID ) ? $user->display_name : '';

				// Display a message for logged in users
				$logged_in_as = sprintf( __( 'Logged in as <a href="%1$s">%2$s</a>. Verification not required.', 'visual-form-builder-pro' ), admin_url( 'profile.php' ), $user_identity );

				$verification .= sprintf(
					'<li class="vfb-item" id="%1$s">%2$s</li>',
					$id_attr,
					$logged_in_as
				);
			endif;

			$verification .= sprintf(
				'<li class="vfb-item vfb-item-%1$s" %2$s style="display:block"><label for="%3$s" class="vfb-desc">%4$s%5$s</label>',
				$field_type,
				$logged_in_display,
				$id_attr,
				$field_name,
				$required_span
			);

			// Set variable for testing if required is Yes/No
			$verification .= ( empty( $required ) ) ? '<input type="hidden" name="_vfb-required-secret" value="0" />' : '';

			// Set hidden secret to matching input
			$verification .= sprintf( '<input type="hidden" name="_vfb-secret" value="vfb-%d" />', $field_id );

			$validation = '{digits:true,maxlength:2,minlength:2}';

			$verification_item = sprintf(
				'<input type="text" name="vfb-%1$d" id="%2$s" value="%3$s" class="vfb-text %4$s %5$s %6$s %7$s" style="display:block" />',
				$field_id,
				$id_attr,
				$logged_in_value,
				$size,
				$required,
				$validation,
				$css
			);

			$verification .= ( !empty( $description ) ) ? sprintf( '<span class="vfb-span">%1$s<label>%2$s</label></span>', $verification_item, $description ) : $verification_item;

		endif;
	endif;

	switch ( $field_type ) {
		case 'text' :
		case 'email' :
		case 'url' :
		case 'currency' :
		case 'number' :
		case 'phone' :

			// HTML5 types
			if ( in_array( $field_type, array( 'email', 'url' ) ) )
				$type = esc_attr( $field_type );
			elseif ( 'phone' == $field_type )
				$type = 'tel';
			else
				$type = 'text';

			$form_item = sprintf(
				'<input type="%8$s" name="vfb-%1$d" id="%2$s" value="%3$s" class="vfb-text %4$s %5$s %6$s %7$s" />',
				$field_id,
				$id_attr,
				$default,
				$size,
				$required,
				$validation,
				$css,
				$type
			);

			$output .= ( !empty( $description ) ) ? sprintf( '<span class="vfb-span">%1$s<label>%2$s</label></span>', $form_item, $description ) : $form_item;

			break;

		case 'textarea' :

			$form_item = sprintf(
				'<textarea name="vfb-%1$d" id="%2$s" class="vfb-textarea %4$s %5$s %6$s">%3$s</textarea>',
				$field_id,
				$id_attr,
				$default,
				$size,
				$required,
				$css
			);

			$output .= '<div>';

			$output .= ( !empty( $description ) ) ? sprintf( '<span class="vfb-span"><label>%2$s</label></span>%1$s', $form_item, $description ) : $form_item;

			$output .= '</div>';

			break;

		case 'select' :

			$field_options = maybe_unserialize( $field->field_options );

			$options = '';

			// Loop through each option and output
			foreach ( $field_options as $option => $value ) {
				$options .= sprintf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr(trim( stripslashes( $value ) ) ), selected( $default, ++$option, 0 ) );
			}

			$form_item = sprintf(
				'<select name="vfb-%1$d" id="%2$s" class="vfb-select %3$s %4$s %5$s">%6$s</select>',
				$field_id,
				$id_attr,
				$size,
				$required,
				$css,
				$options
			);

			$output .= ( !empty( $description ) ) ? sprintf( '<span class="vfb-span"><label>%2$s</label></span>%1$s', $form_item, $description ) : $form_item;

			break;

		case 'radio' :

			$field_options = maybe_unserialize( $field->field_options );

			$options = '';

			// Loop through each option and output
			foreach ( $field_options as $option => $value ) {
				$option++;

				$options .= sprintf(
					'<span class="vfb-span"><input type="radio" name="vfb-%1$d" id="%2$s-%3$d" value="%6$s" class="vfb-radio %4$s %5$s"%8$s /><label for="%2$s-%3$d" class="vfb-choice">%7$s</label></span>',
					$field_id,
					$id_attr,
					$option,
					$required,
					$css,
					esc_attr( trim( stripslashes( $value ) ) ),
					wp_specialchars_decode( stripslashes( $value ) ),
					checked( $default, $option, 0 )
				);
			}

			$form_item = $options;

			$output .= '<div>';

			$output .= ( !empty( $description ) ) ? sprintf( '<span><label>%2$s</label></span>%1$s', $form_item, $description ) : $form_item;

			$output .= '<div style="clear:both"></div></div>';

			break;

		case 'checkbox' :

			$field_options = maybe_unserialize( $field->field_options );

			$options = '';

			// Loop through each option and output
			foreach ( $field_options as $option => $value ) {
				$options .= sprintf(
					'<span class="vfb-span"><input type="checkbox" name="vfb-%1$d[]" id="%2$s-%3$d" value="%6$s" class="vfb-checkbox %4$s %5$s"%8$s /><label for="%2$s-%3$d" class="vfb-choice">%7$s</label></span>',
					$field_id,
					$id_attr,
					$option,
					$required,
					$css,
					esc_attr( trim( stripslashes( $value ) ) ),
					wp_specialchars_decode( stripslashes( $value ) ),
					checked( $default, ++$option, 0 )
				);
			}

			$form_item = $options;

			$output .= '<div>';

			$output .= ( !empty( $description ) ) ? sprintf( '<span><label>%2$s</label></span>%1$s', $form_item, $description ) : $form_item;

			$output .= '<div style="clear:both"></div></div>';

			break;

		case 'address' :

			$address = '';

			$address_parts = array(
			    'address'    => array(
			    	'label'    => __( 'Street Address', 'visual-form-builder' ),
			    	'layout'   => 'full'
			    ),
			    'address-2'  => array(
			    	'label'    => __( 'Apt, Suite, Bldg. (optional)', 'visual-form-builder' ),
			    	'layout'   => 'full'
			    ),
			    'city'       => array(
			    	'label'    => __( 'City', 'visual-form-builder' ),
			    	'layout'   => 'left'
			    ),
			    'state'      => array(
			    	'label'    => __( 'State / Province / Region', 'visual-form-builder' ),
			    	'layout'   => 'right'
			    ),
			    'zip'        => array(
			    	'label'    => __( 'Postal / Zip Code', 'visual-form-builder' ),
			    	'layout'   => 'left'
			    ),
			    'country'    => array(
			    	'label'    => __( 'Country', 'visual-form-builder' ),
			    	'layout'   => 'right'
			    )
			);

			$address_parts = apply_filters( 'vfb_address_labels', $address_parts, $form_id );

			$label_placement = apply_filters( 'vfb_address_labels_placement', $settings_address_labels, $form_id );

			$placement_bottom = ( $label_placement ) ? '<label for="%2$s-%4$s">%5$s</label>' : '';
			$placement_top    = ( !$label_placement ) ? '<label for="%2$s-%4$s">%5$s</label>' : '';

			foreach ( $address_parts as $parts => $part ) :

				// Make sure the second address line is not required
				$addr_required = ( 'address-2' !== $parts ) ? $required : '';

				if ( 'country' == $parts ) :

					$options = '';

					foreach ( $this->countries as $country ) {
						$options .= sprintf( '<option value="%1$s"%2$s>%1$s</option>', $country, selected( $default, $country, 0 ) );
					}

					$address .= sprintf(
						'<span class="vfb-%3$s">' . $placement_top . '<select name="vfb-%1$d[%4$s]" class="vfb-select %7$s %8$s" id="%2$s-%4$s">%6$s</select>' . $placement_bottom . '</span>',
						$field_id,
						$id_attr,
						esc_attr( $part['layout'] ),
						esc_attr( $parts ),
						esc_html( $part['label'] ),
						$options,
						$addr_required,
						$css
					);

				else :

					$address .= sprintf(
						'<span class="vfb-%3$s">' . $placement_top . '<input type="text" name="vfb-%1$d[%4$s]" id="%2$s-%4$s" maxlength="150" class="vfb-text vfb-medium %7$s %8$s" />' . $placement_bottom . '</span>',
						$field_id,
						$id_attr,
						esc_attr( $part['layout'] ),
						esc_attr( $parts ),
						esc_html( $part['label'] ),
						$size,
						$addr_required,
						$css
					);

				endif;

			endforeach;

			$output .= '<div>';

			$output .= !empty( $description ) ? "<span class='vfb-span'><label>$description</label></span>$address" : $address;

			$output .= '</div>';

			break;

		case 'date' :
			// Load jQuery UI datepicker library
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'vfb-datepicker-i18n' );

			$options = maybe_unserialize( $field->field_options );
			$dateFormat = ( $options ) ? $options['dateFormat'] : '';

			$form_item = sprintf(
				'<input type="text" name="vfb-%1$d" id="%2$s" value="%3$s" class="vfb-text vfb-date-picker %4$s %5$s %6$s" data-dp-dateFormat="%7$s" />',
				$field_id,
				$id_attr,
				$default,
				$size,
				$required,
				$css,
				$dateFormat
			);

			$output .= ( !empty( $description ) ) ? sprintf( '<span class="vfb-span">%1$s<label>%2$s</label></span>', $form_item, $description ) : $form_item;

			break;

		case 'time' :

			$hour = $minute = $ampm = '';

			// Get the time format (12 or 24)
			$time_format = str_replace( 'time-', '', $validation );

			$time_format 	= apply_filters( 'vfb_time_format', $time_format, $form_id );
			$total_mins 	= apply_filters( 'vfb_time_min_total', 55, $form_id );
			$min_interval 	= apply_filters( 'vfb_time_min_interval', 5, $form_id );

			// Set whether we start with 0 or 1 and how many total hours
			$hour_start = ( $time_format == '12' ) ? 1 : 0;
			$hour_total = ( $time_format == '12' ) ? 12 : 23;

			// Hour
			for ( $i = $hour_start; $i <= $hour_total; $i++ ) {
				$hour .= sprintf( '<option value="%1$02d">%1$02d</option>', $i );
			}

			// Minute
			for ( $i = 0; $i <= $total_mins; $i += $min_interval ) {
				$minute .= sprintf( '<option value="%1$02d">%1$02d</option>', $i );
			}

			// AM/PM
			if ( $time_format == '12' ) {
				$ampm = sprintf(
					'<span class="vfb-time"><select name="vfb-%1$d[ampm]" id="%2$s-ampm" class="vfb-select %5$s %6$s"><option value="AM">AM</option><option value="PM">PM</option></select><label for="%2$s-ampm">AM/PM</label></span>',
					$field_id,
					$id_attr,
					$hour,
					$minute,
					$required,
					$css
				 );
			}

			$form_item = sprintf(
				'<span class="vfb-time"><select name="vfb-%1$d[hour]" id="%2$s-hour" class="vfb-select %5$s %6$s">%3$s</select><label for="%2$s-hour">HH</label></span>' .
				'<span class="vfb-time"><select name="vfb-%1$d[min]" id="%2$s-min" class="vfb-select %5$s %6$s">%4$s</select><label for="%2$s-min">MM</label></span>' .
				'%7$s',
				$field_id,
				$id_attr,
				$hour,
				$minute,
				$required,
				$css,
				$ampm
			);

			$output .= ( !empty( $description ) ) ? sprintf( '<span class="vfb-span"><label>%2$s</label></span>%1$s', $form_item, $description ) : $form_item;

			$output .= '<div class="clear"></div>';

			break;

		case 'html' :
			//Load CKEditor library
			wp_enqueue_script( 'vfb-ckeditor' );

			$form_item = sprintf(
				'<textarea name="vfb-%1$d" id="%2$s" class="vfb-textarea ckeditor %4$s %5$s %6$s">%3$s</textarea>',
				$field_id,
				$id_attr,
				$default,
				$size,
				$required,
				$css
			);

			$output .= '<div>';

			$output .= ( !empty( $description ) ) ? sprintf( '<span class="vfb-span"><label>%2$s</label></span>%1$s', $form_item, $description ) : $form_item;

			$output .= '</div>';

			break;

		case 'file-upload' :

			$options = maybe_unserialize( $field->field_options );
			$accept = ( !empty( $options[0] ) ) ? " {accept:'$options[0]'}" : '';


			$form_item = sprintf(
				'<input type="file" name="vfb-%1$d" id="%2$s" value="%3$s" class="vfb-text %4$s %5$s %6$s %7$s %8$s" />',
				$field_id,
				$id_attr,
				$default,
				$size,
				$required,
				$validation,
				$css,
				$accept
			);

			$output .= ( !empty( $description ) ) ? sprintf( '<span class="vfb-span">%1$s<label>%2$s</label></span>', $form_item, $description ) : $form_item;

			break;

		case 'instructions' :

			$output .= wp_specialchars_decode( esc_html( stripslashes( $description ) ), ENT_QUOTES );

			break;

		case 'submit' :
			$submit = sprintf(
				'<li class="vfb-item vfb-item-submit" id="item-%2$s">
				<input type="submit" name="vfb-submit" id="%2$s" value="%3$s" class="vfb-submit %4$s" />
				</li>',
				$field_id,
				$id_attr,
				wp_specialchars_decode( esc_html( $field_name ), ENT_QUOTES ),
				$css
			);

			break;

		default:
			echo '';
	}

	// Closing </li>
	$output .= ( !in_array( $field_type , array( 'verification', 'secret', 'submit', 'fieldset', 'section' ) ) ) ? '</li>' : '';
endforeach;


// Close user-added fields
$output .= '</ul>&nbsp;</fieldset>';

// Make sure the verification displays even if they have not updated their form
if ( empty( $verification ) ) :

	$verification = sprintf(
		'<fieldset class="vfb-fieldset vfb-verification" style="display:block">
		<div class="vfb-legend"><h3>%1$s</h3></div>
		<ul class="vfb-section vfb-section-%2$d">
		<li class="vfb-item vfb-item-text" style="display:block">
		<label for="vfb-secret" class="vfb-desc">%3$s<span>*</span></label>
		<div><input type="text" name="vfb-secret" id="vfb-secret" class="vfb-text vfb-medium" style="display:block" /></div>
		</li>',
		__( 'Verification' , 'visual-form-builder'),
		$count,
		__( 'Please enter any two digits with <strong>no</strong> spaces (Example: 12)' , 'visual-form-builder')
	);

endif;

// Output our security test
$output .= sprintf(
	$verification .
	'<li style="display:none;"><label>%1$s:</label><div><input name="vfb-spam" /></div></li>
	%2$s</ul>
	</fieldset>',
	__( 'This box is for spam protection - <strong>please leave it blank</strong>' , 'visual-form-builder'),
	$submit
);

$output .= wp_referer_field( false );

// Close the form out
$output .= '</form>';

// Close form container
$output .= '</div> <!-- .visual-form-builder-container -->';

// Force tags to balance
force_balance_tags( $output );
