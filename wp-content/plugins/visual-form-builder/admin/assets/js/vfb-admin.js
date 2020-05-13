jQuery(document).ready(function($) {
	// Initialize our tooltip timeout var
	var tooltip_timeout = null;

	// !Display/Hide the tooltip
	$( document ).on( 'mouseenter mouseleave', '.vfb-tooltip', function( e ) {
		// If mouse over tooltips
		if( e.type == 'mouseenter' ) {
			// Clear the timeout of our tooltip, if it exists
			if ( tooltip_timeout ) {
				clearTimeout( tooltip_timeout );
				tooltip_timeout = null;
			}

			var tip_title = $( this ).attr( 'title' ),
				tip = $( this ).attr( 'rel' ),
				width = $( this ).width();

			// Create our tooltip popup
			$( this ).append( '<div class="vfb-tooltip-popup"><h3>' + tip_title + '</h3><p>' + tip + '</p></div>' );

			// Save the title before we remove it
			$.data( this, 'title', tip_title );

			// Remove the title so the browser tooltip doesn't display
			this.title = '';

			// Move over the div so it's not on top of the link
			$( this ).find( '.vfb-tooltip-popup' ).css({left:width + 22});

			// Set a timer for hover intent
			tooltip_timeout = setTimeout( function(){
				$( '.vfb-tooltip-popup' ).fadeIn( 300 );
			}, 500 );
		}
		else {
			// Add the title back
			this.title = $.data( this, 'title' );

			// Close the tooltip
			$( '.vfb-tooltip-popup' ).fadeOut( 500 );

			// Remove the appended tooltip div
			$( this ).children().remove();
		}
	});

	// !Dynamically add options for Select, Radio, and Checkbox
	$( document ).on( 'click', 'a.vfb-add-option', function( e ) {
		e.preventDefault();

		var clones = $( this ).parent().siblings( '.vfb-cloned-options' ),
			children = clones.children(),
			num = children.length, newNum = num + 1,
			last_child = children[ num - 1 ],
			id = $( last_child ).attr( 'id' ),
			label = $( last_child ).children( 'label' ).attr( 'for' );

		// Strip out the last number (i.e. count) from the for to make a new ID
		var new_id = label.replace( new RegExp( /(\d+)$/g ), '' ),
			div_id = id.replace( new RegExp( /(\d+)$/g ), '' );

		// Clone this div and change the ID
		var newElem = $( '#' + id ).clone().attr( 'id', div_id + newNum);

		// Change the IDs of the for and input to match
		newElem.children( 'label' ).attr( 'for', new_id + newNum );
		newElem.find( 'input[type="text"]' ).attr( 'id', new_id + newNum );
		newElem.find( 'input[type="radio"]' ).attr( 'value', newNum );

		// Insert our cloned option after the last one
		$( '#' + div_id + num ).after( newElem );
	});

	// !Dynamically delete options for Select, Radio, and Checkbox
	$( document ).on( 'click', 'a.deleteOption', function( e ) {
		e.preventDefault();

		// Get how many options we already have
		var num = $( this ).parent().parent().find( '.clonedOption').length;

		// If there's only one option left, don't let someone delete it
		if ( num - 1 == 0 ) {
			alert( 'You must have at least one option.' );
		}
		else {
			$( this ).closest( 'div' ).remove();
		}
	});

	// !Sort options
	$( '.vfb-cloned-options' ).sortable({
		items: 'div.option'
	});

	// !Add values for the E-mail(s) To field
	$( document ).on( 'click', 'a.addEmail', function( e ) {
		e.preventDefault();

		// Get how many options we already have
		var num = $( this ).closest( '#email-details' ).find( '.clonedOption').length;
		// Add one to how many options
		var newNum = num + 1;

		// Get this div's ID
		var id = $( this ).closest( 'div' ).attr( 'id' );

		// Get this div's for attribute, which matches the input's ID
		var label_for = $( this ).closest( 'div' ).find( 'label' ).attr( 'for' );

		// Strip out the last number (i.e. count) from the for to make a new ID
		var new_id = label_for.replace( new RegExp( /(\d+)$/g ), '' );
		var div_id = id.replace( new RegExp( /(\d+)$/g ), '' );

		// Clone this div and change the ID
		var newElem = $( '#' + id ).clone().attr( 'id', div_id + newNum);

		// Change the IDs of the for and input to match
		newElem.find( 'label' ).attr( 'for', new_id + newNum );
		newElem.find( 'input' ).attr( 'id', new_id + newNum );

		// Insert our cloned option after the last one
		$( '#' + div_id + num ).after( newElem );
	});

	// !Delete values for the E-mail(s) To field
	$( document ).on( 'click', 'a.deleteEmail', function( e ) {
		e.preventDefault();

		// Get how many options we already have
		var num = $( this ).closest( '#email-details' ).find( '.clonedOption').length

		// If there's only one option left, don't let someone delete it
		if ( num - 1 == 0 ) {
			alert( 'You must have at least one option.' );
		}
		else {
			$( this ).closest( 'div' ).remove();
		}
	});

	// !Uncheck Radio button for Options
	$( '.option input[type="radio"]' ).mousedown( function() {
		// Save previous value before .click
		$( this ).attr( 'previousValue', $( this ).prop( 'checked' ) );
	}).click( function() {
		var previousValue = $( this ).attr( 'previousValue' );

		// Change checked value if previous value is true
		if ( previousValue == 'true' )
			$( this ).prop( 'checked', false );
	});

	// !Delete menu or entry
	$( '.menu-delete' ).click( function( ) {

		var message = ( $( this ).hasClass( 'entry-delete' ) ) ? 'entry' : 'form';

		var confirm_delete = confirm( "You are about to permanently delete this " + message + " and all of its data.\n'Cancel' to stop, 'OK' to delete." );

		if ( confirm_delete )
			return true;

		return false;
	});

	// !Field item details box toggle
	$( document ).on( 'click', 'a.item-edit', function( e ){
		e.preventDefault();

		$( e.target ).closest( 'li' ).children( '.menu-item-settings' ).slideToggle( 'fast' );

		$( this ).toggleClass( 'opened' );
		var item = $( e.target ).closest( 'dl' );

		if ( item.hasClass( 'vfb-menu-item-inactive' ) ) {
			item.removeClass( 'vfb-menu-item-inactive' )
				.addClass( 'vfb-menu-item-active' );
		}
		else {
			item.removeClass( 'vfb-menu-item-active' )
				.addClass( 'vfb-menu-item-inactive' );
		}
	});

    // !Fieldset first check
    function is_fieldset_first( item ) {
	    if ( 'FIELDSET' !== item )
	    	$( '#vfb-fieldset-first-warning' ).show();
	    else
	    	$( '#vfb-fieldset-first-warning' ).hide();
    }

	// !Nest and Sort fields
	$( '#vfb-menu-to-edit' ).nestedSortable({
		listType: 'ul',
		maxLevels: 3,
		handle: '.vfb-menu-item-handle',
		placeholder: 'vfb-sortable-placeholder',
		forcePlaceholderSize: true,
		forceHelperSize: true,
		tolerance: 'pointer',
		toleranceElement: '> dl',
		items: 'li:not(.ui-state-disabled)',
		create: function( event, ui ){
			// Make sure the page doesn't jump when at the bottom
			$( this ).css( 'min-height', $( this ).height() );
		},
		start: function( event, ui ){
			// Adjust placeholder size for how many items we're dragging
			ui.placeholder.height( ui.item.height() );
		},
		stop: function( event, ui ){
			// Get the first item after sorting
			var sorted_first_item = $( '#vfb-menu-to-edit .item-type:first' ).text();

			opts = {
				url: ajaxurl,
				type: 'POST',
				async: true,
				cache: false,
				data: {
					action: 'visual_form_builder_sort_field',
					order: $( this ).nestedSortable( 'toArray' )
				},
                success: function( response ) {
                    $( '#loading-animation' ).hide(); // Hide the loading animation

                    is_fieldset_first( sorted_first_item );

                    return;
                }
			};

			$.ajax(opts);
		}
	});

	// !Get the clicked value for creating a new field item
	$( '#form-items .vfb-draggable-form-items' ).click( function( e ) {
		e.preventDefault();
		$( this ).data( 'submit_value', $( this ).text() );
	});

	// !Create fields
	$( '#form-items .vfb-draggable-form-items' ).click( function( e ) {
		e.preventDefault();

		var d = $( this ).closest( 'form' ).serializeArray(),
			field_type = $( this ).data( 'submit_value' ),
			previous = $( '#vfb-menu-to-edit li.ui-state-disabled:first' ).attr( 'id' ).match( new RegExp( /(\d+)$/g ) )[0];

		$( 'img.waiting' ).show();

		$.post( ajaxurl,
			{
				action: 'visual_form_builder_create_field',
				data: d,
				field_type: field_type,
				previous: previous,
				page: pagenow,
				nonce: $( '#_wpnonce' ).val()
			}
		).done( function( response ) {
			$( 'img.waiting' ).hide();

			// Insert the new field last and before the Submit button
			$( response ).hide().insertBefore( '#vfb-menu-to-edit li.ui-state-disabled:first' ).fadeIn();
		});
	});

	// !Delete fields
	$( document ).on( 'click', 'a.item-delete', function( e ) {

		e.preventDefault();

		var data = childs = new Array(),
			parent = 0,
			href = $( this ).attr( 'href' ), url = href.split( '&' ),
			confirm_delete = confirm( "You are about to permanently delete this field.\n'Cancel' to stop, 'OK' to delete." );

		if ( !confirm_delete )
			return false;

		for ( var i = 0; i < url.length; i++ ) {
			// break each pair at the first "=" to obtain the argname and value
			var pos = url[i].indexOf( '=' );
			var argname = url[i].substring( 0, pos );
			var value = url[i].substring( pos + 1 );

			data[ argname ] = value;
		}

		// Find the deleted item's children
		var children = $(this).closest( '.form-item' ).find( 'ul' ).children();

		// Save the children's HTML
		var child_html = children.parent().html();

		// Loop through each child and get the ID
		children.each( function( i ) {
			childs[ i ] = $( this ).attr( 'id' ).match( new RegExp( /(\d+)$/g ) )[0];
		});

		// The closest parent (<li>) to the child items
		var t = $( this ).closest( 'li.form-item' ).parents( 'li.form-item' );

		if ( t.length )
			parent = t.attr( 'id' ).match( new RegExp( /(\d+)$/g ) )[0];

		$.post( ajaxurl,
			{
				action: 'visual_form_builder_delete_field',
				form: data['form'],
				field: data['field'],
				child_ids: childs,
				parent_id: parent,
				page: pagenow,
				nonce: data['_wpnonce']
			}
		).done( function( response ) {
			$( '#form_item_' + data['field'] ).addClass( 'deleting' ).animate({
				opacity : 0,
				height: 0
			}, 350, function() {
				$( this ).before( child_html ).remove();
			});
		});
	});

	// !Form Settings
	$( '#form-settings-button' ).click( function(e){
		e.preventDefault();

		$( this ).toggleClass( 'current' );

		$( '#form-settings' ).slideToggle( 'fast' );

		var form_id = $( 'input[name="form_id"]' ).val(),
			state = ( $( this ).hasClass( 'current' ) ) ? 'opened' : 'closed';

		$.post( ajaxurl,
			{
				action: 'visual_form_builder_form_settings',
				form: form_id,
				status: state,
				page: pagenow
			}
		).done( function( response ) {
			if ( state == 'closed' ) {
				$( '.settings-links' ).removeClass( 'on' );
				$( '.settings-links:first' ).addClass( 'on' );
				$( '.form-details' ).slideUp( 'normal' );
				$( '.form-details:first' ).show( 'normal' );
			}
		});
	});

	// !Form Settings - internal links
	$( '.settings-links' ).click( function(e){
		e.preventDefault();

		//Remove the 'on' class from all buttons
		$( '.settings-links' ).removeClass( 'on' );

		//Always close open slides
		$( '.form-details' ).slideUp( 'fast' );

		//If the next slide wasn't open, open it
		if( $( this ).next( 'div' ).is( ':hidden' ) == true ) {

			$( this ).addClass( 'on' );

			$( this ).next().slideDown( 'normal' );
		}

		var form_id = $( 'input[name="form_id"]' ).val(),
			accordion = this.hash.replace( /#/g, '' );

		$.post( ajaxurl,
			{
				action: 'visual_form_builder_form_settings',
				form: form_id,
				accordion: accordion,
				page: pagenow
			}
		);
	});

	// !Ask to Save before navigating away from page
	var vfb_forms_changed = false;
	$( '#vfb-form-builder-management input, #vfb-form-builder-management select, #vfb-form-builder-management textarea' ).change( function(){
		vfb_register_change();
	});

	function vfb_register_change() {
		vfb_forms_changed = true;
	}

	window.onbeforeunload = function(){
		if ( vfb_forms_changed )
			return 'The changes you made will be lost if you navigate away from this page.';
	};

	$( document ).on( 'submit', '#visual-form-builder-update', function() {
		window.onbeforeunload = null;
	});

	// !Sticky sidebar
	if ( $( '.columns-2 #side-sortables' ).length > 0 ) {
	    var sidebar = $( '#vfb_form_items_meta_box' ),
	    	sidebar_width = sidebar.width(),
	    	offset = sidebar.offset(),
	    	next_box = sidebar.nextAll(),
	    	hidden = false;

	    $( window ).on( 'scroll', function() {
	        if ( $( window ).scrollTop() > offset.top ) {

	            sidebar.stop().css({
		           'top' : 55,
		           'position' : 'fixed',
		           'z-index' : '1',
		           'width' : sidebar_width
	            });

	            // change opacity of other meta boxes if visible
	            if ( next_box.is( ':visible' ) ) {
	            	hidden = true;
		            next_box.stop().css({
		            	'opacity' : 0.1
		            });
	            }

	        } else {
	            sidebar.stop().css({
	            	'top' : 0,
	                'position': 'relative'
	            });

	            // only change opacity if meta box was changed
	            if ( hidden ) {
		            next_box.stop().css({
		            	'opacity' : 1
		            });
	            }
	        };
	    });
	}

	// !Display the selected confirmation type on load
	var confirmation = $( '.form-success-type:checked' ).val();
	$( '#form-success-message-' + confirmation ).show();

	// !Confirmation Message tabs
	$( '.form-success-type' ).change(function(){
		var type = $( this ).val();

		switch ( type ) {
			case 'text' :
				$( '#form-success-message-text' ).show();
				$( '#form-success-message-page, #form-success-message-redirect' ).hide();
			break;

			case 'page' :
				$( '#form-success-message-page' ).show();
				$( '#form-success-message-text, #form-success-message-redirect' ).hide();
			break;

			case 'redirect' :
				$( '#form-success-message-redirect' ).show();
				$( '#form-success-message-text, #form-success-message-page' ).hide();
			break;
		}
	});

	// !Field Types tabs
	$( '.vfb-field-types' ).click(function( e ){
		e.preventDefault();

		$( '#vfb-field-tabs li' ).removeClass( 'tabs' ); //Remove any "active" class
		$( this ).parent().addClass( 'tabs' ); //Add "active" class to selected tab

		$( '.tabs-panel-active' ).removeClass( 'tabs-panel-active' ).addClass( 'tabs-panel-inactive' );

		var activeTab = this.hash; //Find the href attribute value to identify the active tab + content
		$( activeTab ).removeClass( 'tabs-panel-inactive' ).addClass( 'tabs-panel-active' );
	});

	// !Validate the sender details section
	$( '#visual-form-builder-update' ).validate({
		rules: {
			'form_email_to[]': {
				email: true
			},
			form_email_from: {
				email: true
			},
			form_success_message_redirect: {
				url: true
			},
			form_notification_email_name: {
				required: function( element ){
					return $( '#form-notification-setting' ).is( ':checked' );
				}
			},
			form_notification_email_from: {
				required: function( element ){
					return $( '#form-notification-setting' ).is( ':checked' );
				},
				email: true
			},
			form_notification_email: {
				required: function( element ){
					return $( '#form-notification-setting' ).is( ':checked' );
				}
			}
		},
		errorPlacement: function( error, element ) {
			error.insertAfter( element.parent() );
		}
	});

	$( '#visual-form-builder-new-form' ).validate();

	// !Sender Name field readonly if the override is active
	$( '#form_email_from_name_override' ).change( function(){
		if ( $( '#form_email_from_name_override' ).val() == '' )
			$( '#form-email-sender-name' ).prop( 'readonly', false );
		else
			$( '#form-email-sender-name' ).prop( 'readonly', 'readonly' );
	});

	// !Sender Email field readonly if the override is active
	$( '#form_email_from_override' ).change( function(){
		if ( $( '#form_email_from_override' ).val() == '' )
			$( '#form-email-sender' ).prop( 'readonly', false );
		else
			$( '#form-email-sender' ).prop( 'readonly', 'readonly' );
	});


	// !Show/Hide display of Notification fields
	$( '#notification-email' ).toggle( $( '#form-notification-setting' ).prop( 'checked' ) );

	// !Enable/Disable Notification fields
	$( '#form-notification-setting' ).change( function(){
		var checked = $(this).is(':checked');

		if ( checked ) {
			$( '#notification-email' ).show();
			$( '#form-notification-email-name, #form-notification-email-from, #form-notification-email, #form-notification-subject, #form-notification-message, #form-notification-entry' ).prop( 'disabled', false );
		}
		else{
			$( '#notification-email' ).hide();
			$( '#form-notification-email-name, #form-notification-email-from, #form-notification-email, #form-notification-subject, #form-notification-message, #form-notification-entry' ).prop( 'disabled', 'disabled' );
		}
	});

	// !Entries Select All
	$( '#vfb-export-select-all' ).click( function( e ) {
		e.preventDefault();

		$( '#vfb-export-entries-fields input[type="checkbox"]' ).prop( 'checked', true );
	});

	$( '#vfb-export-unselect-all' ).click( function( e ) {
		e.preventDefault();

		$( '#vfb-export-entries-fields input[type="checkbox"]' ).prop( 'checked', false );
	});

	// !Entries fields
	$( '#vfb-export-entries-forms' ).change( function(){
		var id = $( this ).val(),
			count = vfb_entries_count( id );

		$( '#vfb-export-entries-fields' ).html( 'Loading...' );

		$.get( ajaxurl,
			{
				action: 'visual_form_builder_export_load_options',
				id: id,
				count: count,
				page: pagenow
			}
		).done( function( response ) {
			$( '#vfb-export-entries-fields' ).html( response );
		}).fail( function( response ) {
			$( '#vfb-export-entries-fields' ).html( 'Error loading entry fields.' );
		});
	});

	$( '#vfb-export-entries-rows' ).change( function(){
		var id = $( '#vfb-export-entries-forms' ).val();

		var page = $( this ).val();

		$( '#vfb-export-entries-fields' ).html( 'Loading...' );

		$.get( ajaxurl,
			{
				action: 'visual_form_builder_export_load_options',
				id: id,
				offset: page,
				page: pagenow
			}
		).done( function( response ) {
			$( '#vfb-export-entries-fields' ).html( response );
		}).fail( function( response ) {
			$( '#vfb-export-entries-fields' ).html( 'Error loading entry fields.' );
		});
	});

	function vfb_entries_count( id ) {
		 var count = '';

		 $.ajax( ajaxurl, {
			 async: false,
			 data:
			 {
				action: 'visual_form_builder_export_entries_count',
				id: id,
				page: pagenow
			 }
		}).done( function( response ) {
			if ( response > 1000 ) {

				$( '#vfb-export-entries-rows' ).empty();

				var num_pages = Math.ceil( parseInt( response ) / 1000 );

				for ( var i = 1; i <= num_pages; i++ ) {
					$( '#vfb-export-entries-rows' ).append( $( '<option></option>' ).attr( 'value', i ).text( i ) );
				}

				$( '#vfb-export-entries-pages' ).show();
			}
			else {
				$( '#vfb-export-entries-pages' ).hide();
			}

			count = response;
		}).fail( function( response ) {
		});

		return count;
	}
});
