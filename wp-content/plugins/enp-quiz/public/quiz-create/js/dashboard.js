jQuery( document ).ready( function( $ ) {

    // create the list view toggle elements
    $('.enp-quiz-list__view').prepend('<svg class="enp-view-toggle enp-view-toggle__grid enp-icon"><use xlink:href="#icon-grid"><title>Grid View</title></use></svg><svg class="enp-view-toggle enp-view-toggle__list enp-icon"><use xlink:href="#icon-list"><title>List View</title></use></svg>');

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


    $('.enp-dash-item__nav').addClass('enp-dash-item__nav--collapsible').before('<button class="enp-dash-item__menu-action" type="button"><svg class="enp-dash-item__menu-action__icon enp-dash-item__menu-action__icon--bottom"><use xlink:href="#icon-chevron-down" /></svg><svg class="enp-dash-item__menu-action__icon enp-dash-item__menu-action__icon--top"><use xlink:href="#icon-chevron-down" /></svg></button>');

    $(document).on('click', '.enp-dash-item__menu-action', function() {
       var dashItem = $(this).closest('.enp-dash-item');

        if(dashItem.hasClass('enp-dash-item--menu-active')) {
            dashItem.removeClass('enp-dash-item--menu-active');
            $('.enp-dash-container, #enp-quiz').removeClass('enp-dash-list--focus-one');

        } else {

            $('.enp-dash-item').removeClass('enp-dash-item--menu-active');
            dashItem.addClass('enp-dash-item--menu-active');
            $('.enp-dash-container, #enp-quiz').addClass('enp-dash-list--focus-one');
            // move focus to first item in menu
            $('.enp-dash-item__nav__item:eq(0) a', dashItem).focus();
        }
    });


    // add a close icon to the cookie message
    if($('.enp-quiz-message--welcome').length) {
        $('.enp-quiz-message--welcome').append('<button class="enp-quiz-message__close" type="button"><svg class="enp-quiz-message__close__icon enp-icon"><use xlink:href="#icon-close" /></svg></button>');

    }
    // remove the message on click
    $(document).on('click', '.enp-quiz-message__close', function() {
        $(this).closest('.enp-quiz-message--welcome').remove();
    });

});
