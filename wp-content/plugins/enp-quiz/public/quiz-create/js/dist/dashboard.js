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

// create the button element to show/hide the dah item nav

$('.enp-dash-item__nav').each(function() {
    $(this).addClass('enp-dash-item__nav--collapsible')
            .attr('aria-hidden', true)
            .before('<button class="enp-dash-item__menu-action" type="button" aria-expanded="false" aria-controls="'+$(this).attr('id')+'"><svg class="enp-dash-item__menu-action__icon enp-dash-item__menu-action__icon--bottom"><use xlink:href="#icon-chevron-down" /></svg><svg class="enp-dash-item__menu-action__icon enp-dash-item__menu-action__icon--top"><use xlink:href="#icon-chevron-down" /></svg></button>');
});

// show/hide the dash item nav
$(document).on('click', '.enp-dash-item__menu-action', function() {
   var dashItem = $(this).closest('.enp-dash-item');

    if(dashItem.hasClass('enp-dash-item--menu-active')) {
        removeActiveMenuStates(dashItem);
    } else {

        // remove states from any active menu item, if there is one
       var previouslyActiveMenu = $('.enp-dash-item--menu-active');
       if(0 < previouslyActiveMenu.length ) {
           removeActiveMenuStates(previouslyActiveMenu);
       }
       // add in new active states
       addActiveMenuStates(dashItem);
       // move focus to first item in menu
       $('.enp-dash-item__nav__item:eq(0) a', dashItem).focus();
    }
});

function addActiveMenuStates(dashItem) {
    // add the new active states in
    dashItem.addClass('enp-dash-item--menu-active');
    // button to activate the menu
    $('.enp-dash-item__menu-action', dashItem).attr('aria-expanded', true);
    // menu
    $('.enp-dash-item__nav', dashItem).attr('aria-hidden', false);
}

function removeActiveMenuStates(dashItem) {
    // dash item card
    dashItem.removeClass('enp-dash-item--menu-active');
    // button to activate the menu
    $('.enp-dash-item__menu-action', dashItem).attr('aria-expanded', false);
    // menu
    $('.enp-dash-item__nav', dashItem).attr('aria-hidden', true);
}


// delete a quiz click
$('.enp-dash-item__delete').click(function(e) {
    e.preventDefault();
    // get the dash item
    var dashItem = $(this).closest('.enp-dash-item');

    // determine if we're deleting a quiz or an AB test
    // off of the button value
    var userAction = $(this).val();

    // TODO This should be an "undo", not a confirm
    if(userAction === 'delete-quiz') {
        confirmDeleteText = 'Are you sure you want to delete this quiz? This will also delete any AB Tests you have set-up with this Quiz.';
    }
    else if(userAction === 'delete-ab-test') {
        confirmDeleteText = 'Are you sure you want to delete this AB Test?';
    } else {
        // not sure what we're going to do here...
        alert('Something went wrong. Please send us an email telling us how you reached this error message');
    }

    // show the confirm message
    var confirmDelete = confirm(confirmDeleteText);

    if(confirmDelete === false) {
        return false;
    }  else {
        // they want to delete it, so let them
        // TODO This should be an "undo", not a confirm
    }

    // check if we have the click wait class already
    // add a click wait, if necessary
    if($(this).hasClass('enp-quiz-submit--wait')) {
        // be patient!
        return false;
    } else {
        setWait();
    }

    // add a little spinner to show we're working on deleting it
    deleteQuizWait(dashItem);

    var fd = deleteFormData(dashItem, userAction);

    $.ajax( {
        type: 'POST',
         url  : quizDashboard.ajax_url,
         data : fd,
         processData: false,  // tell jQuery not to process the data
         contentType: false,   // tell jQuery not to set contentType
    } )
    // success
    .done( quizDeleteSuccess )
    .fail( function( jqXHR, textStatus, errorThrown ) {
        console.log( 'AJAX failed', jqXHR.getAllResponseHeaders(), textStatus, errorThrown );
    } )
    .then( function( errorThrown, textStatus, jqXHR ) {

    } )
    .always(function() {
        // remove wait class elements
        unsetWait();
    });

});


function quizDeleteSuccess( response, textStatus, jqXHR ) {
    //console.log(jqXHR.responseJSON);
    if(jqXHR.responseJSON === undefined) {
        // error :(
        unsetWait();
        appendMessage('Something went wrong. Please reload the page and try again.', 'error');
        return false;
    }

    response = $.parseJSON(jqXHR.responseJSON);
    console.log(response);
    displayMessages(response.message);

    var userActionAction = response.user_action.action;
    var userActionElement = response.user_action.element;
    // see if we've created a new quiz
    if(response.status === 'success' && response.action === 'update') {
        // it worked! verify that we were deleting something
        if(userActionAction === 'delete') {
            // see if it's a quiz
            if(userActionElement === 'quiz') {
                dashItem = $('#enp-dash-item--'+response.quiz_id);
                // check if an AB Test has been deleted along with the quiz delete
                var isABTestDeleted = hasABTestDeleted(response.user_action);
                if(isABTestDeleted === true) {
                    // delete all the AB Tests
                    deleteABTestsWithQuiz(response.user_action.secondary_action.ab_test_deleted);
                }
            }
            // see if it's an AB test
            else if(userActionElement === 'ab_test') {
                dashItem = $('#enp-dash-item--'+response.ab_test_id+'a'+response.quiz_id_a+'b'+response.quiz_id_b);
            }

            removeDashItem(dashItem);

        }

    }
}

function deleteFormData(dashItem, userAction) {
    var fd;
    if(userAction === 'delete-quiz') {
        fd = deleteQuizFormData(dashItem);
    }
    else if(userAction === 'delete-ab-test') {
        fd = deleteABTestFormData(dashItem);
    }
    return fd;
}

function deleteQuizFormData(dashItem) {
    // get the quizID we want to delete
    var quizID = $('.enp-dash-item__quiz-id', dashItem).val();

    // get the form we're submitting
    var quizForm = document.getElementById("enp-delete-quiz-"+quizID);
    // create formData object
    var fd = new FormData(quizForm);
    // set our submit button value
    fd.append('enp-quiz-submit', 'delete-quiz');
    // append our action for wordpress AJAX call (which function it will run in class-enp_quiz-create.php)
    fd.append('action', 'save_quiz');

    return fd;
}

function deleteABTestFormData(dashItem) {
    // get the AB Test ID we want to delete
    var abTestID = $('.enp-dash-item__ab-test-id', dashItem).val();
    var quizIDA = $('.enp-dash-item__quiz-id-a', dashItem).val();
    var quizIDB = $('.enp-dash-item__quiz-id-b', dashItem).val();

    // get the form we're submitting
    var abTestForm = document.getElementById("enp-delete-ab-test-"+abTestID+"a"+quizIDA+"b"+quizIDB);
    // create formData object
    var fd = new FormData(abTestForm);
    // set our submit button value
    fd.append('enp-ab-test-submit', 'delete-ab-test');
    // append our action for wordpress AJAX call (which function it will run in class-enp_quiz-create.php)
    fd.append('action', 'save_ab_test');

    return fd;
}


function deleteQuizWait(dashItem) {
    dashItem.addClass('enp-dash-item--delete-wait');
    dashItem.append(waitSpinner('enp-dash-item__spinner'));
}

function removeDashItem(dashItem) {
    // remove the dashboard item
    dashItem.addClass('enp-dash-item--remove');

    // wait 300ms then actually remove it
    setTimeout(
        function() {
            dashItem.remove();
        },
        300
    );
}

/**
* check to see if any AB Tests also got deleted when deleting
* the quiz (because we don't want any lingering AB Tests that
* have a deleted quiz on them)
* @return (BOOLEAN) true if AB Test was also deleted, false if none
*/
function hasABTestDeleted(userActionJSON) {
    var ABTestDeleted = false;
    for(var prop in userActionJSON) {
        if(prop === 'secondary_action') {
            // loop this and see if we have an ab_test_deleted
            for(var secondary_prop in userActionJSON.secondary_action) {
                if(secondary_prop === 'ab_test_deleted') {
                    ABTestDeleted = true;
                    return ABTestDeleted;
                }
            }
        }
    }
    return ABTestDeleted;
}

/**
* If a quiz that was deleted also has an AB Test associated with
* it, then we need to delete those AB Tests too.
* This function removes all those AB Tests that were deleted from the view
* @param abTestsDeleted (JSON) from server response on which AB Tests were deleted
*/
function deleteABTestsWithQuiz(abTestsDeleted) {
    // we have AB Tests to remove. loop through them
    for (var i = 0; i < abTestsDeleted.length; i++) {
        // check to make sure it was deleted successfully
        if(abTestsDeleted[i].user_action.action === 'delete' && abTestsDeleted[i].status === 'success') {
            // get all the info we'll need to find the right AB Test to remove from the page
            var abTestID = abTestsDeleted[i].ab_test_id;
            var quizIDA = abTestsDeleted[i].quiz_id_a;
            var quizIDB = abTestsDeleted[i].quiz_id_b;
            // get the ab test dash item
            var abTestDashItem = $("#enp-dash-item--"+abTestID+"a"+quizIDA+"b"+quizIDB);
            // remove it
            removeDashItem(abTestDashItem);
        }
    }
}


// add a close icon to the cookie message
if($('.enp-quiz-message--welcome').length) {
    $('.enp-quiz-message--welcome').append('<button class="enp-quiz-message__close" type="button"><svg class="enp-quiz-message__close__icon enp-icon"><use xlink:href="#icon-close" /></svg></button>');
}
// remove the message on click
$(document).on('click', '.enp-quiz-message__close', function() {
    $(this).closest('.enp-quiz-message--welcome').remove();
});


// Add a loading animation
function waitSpinner(waitClass) {
    return '<div class="spinner '+waitClass+'"><div class="bounce bounce1"></div><div class="bounce bounce2"></div><div class="bounce bounce3"></div></div>';
}

// add wait classes to prevent duplicate submissions
function setWait() {
    // add click wait class
    $('.enp-quiz-submit').addClass('enp-quiz-submit--wait');
}

// removes wait classes that prevent duplicate sumissions
function unsetWait() {
    $('.enp-quiz-submit').removeClass('enp-quiz-submit--wait');
}

// set-up our ajax response container for messages to get added to
$('#enp-quiz').append('<section class="enp-quiz-message-ajax-container" aria-live="assertive"></section>');

// append ajax response message
function appendMessage(message, status) {
    var messageID = Math.floor((Math.random() * 1000) + 1);
    $('.enp-quiz-message-ajax-container').append('<div class="enp-quiz-message enp-quiz-message--ajax enp-quiz-message--'+status+' enp-container enp-message-'+messageID+'"><p class="enp-message__list enp-message__list--'+status+'">'+message+'</p></div>');

    $('.enp-message-'+messageID).delay(3500).fadeOut(function(){
        $('.enp-message-'+messageID).fadeOut();
    });
}

// Loop through messages and display them
// Show success messages
function displayMessages(message) {
    // loop through success messages
    //for(var success_i = 0; success_i < message.success.length; success_i++) {
        if(typeof message.success !== 'undefined' && message.success.length > 0) {
            // append our new success message
            appendMessage(message.success[0], 'success');
        }

    //}

    // Show error messages
    for(var error_i = 0; error_i < message.error.length; error_i++) {
        appendMessage(message.error[error_i], 'error');
    }
}


function destroySuccessMessages() {
    $('.enp-quiz-message--success').remove();
}

function removeErrorMessages() {
    if($('.enp-quiz-message--error').length) {
        $('.enp-quiz-message--error').remove();
        $('.enp-accordion-header').removeClass('question-has-error');
    }

}
});