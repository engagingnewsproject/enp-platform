jQuery( document ).ready( function( $ ) {
    // see if there are select fields to set
    if($('#quiz-b').length) {
        // select the second option on load
        $('#quiz-b option:eq(1)').attr('selected', 'selected');
    }
});
