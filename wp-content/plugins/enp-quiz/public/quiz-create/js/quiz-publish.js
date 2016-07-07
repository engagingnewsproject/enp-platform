jQuery( document ).ready( function( $ ) {
    // select all text on focus
    $('.enp-embed-code').focus(function(){
        $(this).select();
    });

});
