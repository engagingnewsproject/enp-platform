

jQuery(window)
    .load(function () {
    if (jQuery('#saved_box')
        .length) {
        setTimeout('bordeaux.removeSavedMessage()', 3000)
    }
});


/// image upload

var file_frame;

jQuery('.df-upload-button').live('click', function( event ){
    clicked = jQuery(this);
    event.preventDefault();
 
    if ( file_frame ) {
        file_frame.open();
        return;
    }
 
    file_frame = wp.media.frames.file_frame = wp.media({
        title: jQuery( this ).data( 'uploader_title' ),
        button: {
            text: jQuery( this ).data( 'uploader_button_text' ),
        },
        multiple: false  
    });
 
    file_frame.on( 'select', function() {
 
        attachment = file_frame.state().get('selection').first().toJSON();
 
        // I'm getting the ID rather than the URL:
        //jQuery(".df-upload-field").val(attachment.id);
        // but you could get the URL instead by doing something like this:
        clicked.parent().find(".df-upload-field").val(attachment.url);
 
        // and you can change "thumbnail" to get other image sizes
 
    });
 
    file_frame.open();
 
});