jQuery( document ).ready( function( $ ) {

    // create the list view toggle elements
    $('.enp-quiz-list__view').prepend('<svg class="enp-view-toggle enp-view-toggle__grid enp-icon"><use xlink:href="#icon-grid" /></svg><svg class="enp-view-toggle enp-view-toggle__list enp-icon"><use xlink:href="#icon-list" /></svg>');

    // add active class initially to grid view
    $('.enp-view-toggle__grid').addClass('enp-view-toggle__active');

    // on click, add active class/remove it from the other one
    $(document).on('click', '.enp-view-toggle', function() {
        // check if it has the active class or not
        if(!$(this).hasClass('enp-view-toggle__active')) {
            // remove active class from siblings
            $(this).siblings('.enp-view-toggle').removeClass('enp-view-toggle__active');
            // add active class to itself
            $(this).addClass('enp-view-toggle__active');
            // find the corresponding list
            var quizList = $(this).parent().parent().next('.enp-dash-list');
            // check if it has the list view class, if it does, remove it, else, add it
            if( quizList.hasClass('enp-dash-list--list-view') ) {
                quizList.removeClass('enp-dash-list--list-view');
            } else {
                quizList.addClass('enp-dash-list--list-view');
            }
        }
    });
});
