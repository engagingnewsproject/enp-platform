/**
 * scripts.js
 *
 * General Enp Button scripts
 */

jQuery( document ).ready( function( $ ) {


    // Check if the user is not logged in, and the button is clickable
    // If this passes, we need to set-up button states based on localStorage
    var user_id  = enp_button_params.enp_btn_user_id;
    var enp_btn_clickable = enp_button_params.enp_btn_clickable;

    if(parseInt(user_id) === 0 && enp_btn_clickable != 0) {

        $('.enp-btns-wrap').each(function(){
            // set empty array

            var parent_obj = $(this);

            // pass the state of this to the next each loop
            var clicked_btn_names = enp_SetBtnStates.call(this);

            if(clicked_btn_names.length) {
                var btn_type = $(this).attr('data-btn-type');
                var user_clicked_message = enp_UserClickedMessage(clicked_btn_names, btn_type);

                if($('.enp-user-clicked-hint', this).length) {
                    $('.enp-user-clicked-hint', this).replaceWith(user_clicked_message);
                } else {
                    $('.enp-btns', this).after(user_clicked_message);
                }

            }
        });
    }


    /*
    *   Sets all button states based on localStorage
    *   use in nested each statement. passing this
    *   so we know what the enp-btn-wrap parent is
    */
    function enp_SetBtnStates(parent_obj) {
        var parent_obj = this;
        // create empty array to store names
        var clicked_btn_names = [];
        // See what buttons we're working with
        $('.enp-btn', this).each(function(){
            var btn_slug = $(this).attr( 'data-btn-slug' );
            var btn_type = $(this).attr( 'data-btn-type' );

            var values = enp_getLocalStorage(btn_type, btn_slug);

            // if we have an array and it has values, then let's see if
            // the post IDs match and set the operator correctly
            if(typeof values === 'object' && values !== null) {
                // check if the post id is in the array
                // if it isn't, it'll return -1
                var id = $(this).attr( 'data-pid' );
                var index = $.inArray(id, values);
                if(index !== -1) {
                    // it's in the array! set the data operator to -
                    $(this).attr( 'data-operator', '-' );
                    // set the click state
                    $(this).addClass('enp-btn--user-clicked');
                    $(this).removeClass('enp-btn--user-has-not-clicked');

                    var btn_name = $('.enp-btn__name', this).text();
                    // push to clicked button array so we can create the message
                    clicked_btn_names.push(btn_name);
                }

            }
        });
        // pass the btn_names back to our parent for outputting message
        return clicked_btn_names;
    }



    $('.enp-btn').click(function(e) {
        e.preventDefault();

        // if user is not logged in & it's required, then disable the button
        var enp_btn_clickable = enp_button_params.enp_btn_clickable;

        if( $(this).hasClass('enp-btn--error') || $(this).hasClass('enp-btn--disabled') || $(this).hasClass('enp-btn--click-wait')) {
            return false; // hey! You're not supposed to click me! Wait a second if you've already clicked
        } else if(enp_btn_clickable == 0) { // false
            // Button is disabled, return an error message with login links
            enp_pleaseLoginError(this)
            return false;
        } else {
            // Delay them from clicking over and over without waiting
            $(this).addClass('enp-btn--click-wait');
        }


        // if it's a post, pass the id/slug to an ajax request to update the post_meta for this post
        var id       = $(this).attr( 'data-pid' );
        var nonce    = $(this).attr( 'data-nonce' );
        var btn_slug = $(this).attr( 'data-btn-slug' );
        var btn_type = $(this).attr( 'data-btn-type' );
        var operator = $(this).attr( 'data-operator' );
        var url      = enp_button_params.ajax_url;
        var user_id  = enp_button_params.enp_btn_user_id;

        // assume that our front-end check is enough
        // and increase it by 1 for a super fast response time
        enp_changeCount(this, operator);

        // if it's a comment, pass the id/slug to an ajax request to update the comment_meta for this comment
        // Post to the server
        $.ajax({
            type: 'POST',
            url: url,
            data:  {
                    'action': 'enp_update_button_count',
                    'pid': id,
                    'slug': btn_slug,
                    'type': btn_type,
                    'operator': operator,
                    'user_id' : user_id,
                    'nonce': nonce
                },
            dataType: 'xml',
            success:function(xml) {
                // don't do anything!
                // If we update with the xml count, it could be wrong if someone
                // on a different connection has clicked it. Then, it would go up by
                // multiple numbers, instead of just one, and the person seeing that
                // happen would think that their click registered lots of times instead
                // of correctly counting just once

                // here's how to get the new count from the returned xml doc though
                // var count = $(xml).find('count').text();
                var btn_type = $(xml).find('type').text();
                var pid = $(xml).find('pid').text();
                var btn_slug = $(xml).find('slug').text();
                var btn = $('#'+btn_slug+'_'+btn_type+'_'+pid);
                var response = $(xml).find('response_data').text(); // will === 'success' or 'error'
                var btn_group = $('.enp-btns-'+btn_type+'-'+pid);
                var btn_wrap = $('#enp-btns-wrap-'+btn_type+'-'+pid);
                var new_operator = $(xml).find('new_operator').text();
                var operator;

                // we need to know which operator we sent over.
                if(new_operator === '+') {
                    operator = '-';
                } else {
                    operator = '+';
                }

                if(response === 'error') {
                    // there was an error updating the meta key on the server
                    // reset the count back one and let the user know what's up
                    var message = $(xml).find('message').text();
                    // show error message

                    // process the error
                    enp_processError(btn, btn_group, message);

                } else {
                    // switch out the operator data attribute

                    btn.attr('data-operator', new_operator);

                    // success! add a btn class so we can style if we want to
                    // if the new operator === '-', then we just added one
                    if(new_operator === '-') {
                        btn.removeClass('enp-btn--decreased');
                        btn.addClass('enp-btn--increased');
                        btn.removeClass('enp-btn--user-has-not-clicked');
                        btn.addClass('enp-btn--user-clicked');
                    } else {
                        btn.removeClass('enp-btn--increased');
                        btn.addClass('enp-btn--decreased');
                        btn.addClass('enp-btn--user-has-not-clicked');
                        btn.removeClass('enp-btn--user-clicked');
                    }


                    // NOT LOGGED IN, LOCALSTORAGE
                    var user_clicked_message;
                    var user_id  = enp_button_params.enp_btn_user_id;
                    // if user is not logged in, process localStorage data
                    if(parseInt(user_id) === 0) {
                        // set localStorage
                        enp_setlocalStorage(btn_type, btn_slug, pid, operator);
                        // generate message from localStorage
                        user_clicked_message = '';

                        var clicked_btn_names = enp_SetBtnStates.call(btn_wrap);

                        if(clicked_btn_names.length) {
                            var user_clicked_message = enp_UserClickedMessage(clicked_btn_names, btn_type);

                            if($('.enp-user-clicked-hint', this).length) {
                                $('.enp-user-clicked-hint', this).replaceWith(user_clicked_message);
                            } else {
                                $('.enp-btns', this).after(user_clicked_message);
                            }

                        }

                    } else {
                        // not logged in, so we can get our message from the ajax post
                        user_clicked_message = $(xml).find('user_clicked_message').text();
                    }


                    // update the button clicked message
                    if($('.enp-user-clicked-hint', btn_wrap).length) {
                        $('.enp-user-clicked-hint', btn_wrap).replaceWith(user_clicked_message);
                    } else {
                        btn_group.after(user_clicked_message);
                    }

                    // open a new ajax request to send data to ENP, if user allows
                    // TODO: package this into its own function
                    // enp_sendClickData(pid, btn_slug, btn_type);
                    $.ajax({
                        type: 'POST',
                        url: enp_button_params.ajax_url,
                        data:  {
                                'action': 'enp_send_button_count',
                                'pid': pid,
                                'slug': btn_slug,
                                'type': btn_type
                                },
                        dataType: 'xml',
                        success:function(xml) {
                            // var message = $(xml).find('message').text();
                            // console.log(message);
                        },
                        error:function(json) {
                            // console.log(json);
                            // var error = $.parseJSON(json.responseText);

                        }
                    });

                }

                // remove clicked class so they can try again
                btn.removeClass('enp-btn--click-wait');

            },
            error:function(json) {

                var error = $.parseJSON(json.responseText);
                // An error occurred when trying to post, alert an error message
                var message = error.message;
                var pid = error.pid;
                var btn_type = error.btn_type;
                var btn_slug = error.btn_slug;
                // create objects
                var btn = $('#'+btn_slug+'_'+btn_type+'_'+pid);
                var btn_group = $('.enp-btns-'+btn_type+'-'+pid);

                // process the error
                enp_processError(btn, btn_group, message);

                // remove clicked class so they can try again
                btn.removeClass('enp-btn--click-wait');
            }


        });


    });


    // Increase the count by 1
    function enp_changeCount(btn, operator) {
        var curr_count = enp_getBtnCount(btn);
        // if curr_count is 0, then remove the class that hides the 0
        if(curr_count === 0) {
            $('.enp-btn__count', btn).removeClass('enp-btn__count--zero');
        }

        // add one for the click
        if(operator === '+') {
            new_count = curr_count + 1;
        } else {
            new_count = curr_count - 1;
        }
        $(btn).attr('data-count', new_count);
        new_count = enp_formatNumber(new_count);

        // Safari Hack
        // safari v9.0.2 won't repaint the new value (it replace it in the HTML but doesn't repaint it on screen) unless its wrapped in HTML
        new_count = '<span>'+new_count+'</span>';
        
        // replace the text with the new number
        $('.enp-btn__count', btn).html(new_count);
    }


    // get the current count of a button
    function enp_getBtnCount(btn) {
        curr_count = $(btn).attr('data-count');
        return parseInt(curr_count);
    }

    // roll back count on error
    function enp_rollBackCount(btn) {
        var new_count = enp_getBtnCount(btn);
        var roll_back_count = new_count - 1;
        $(btn).attr('data-count', roll_back_count);
        roll_back_count = enp_formatNumber(roll_back_count);
        $('.enp-btn__count', btn).html(roll_back_count);
    }

    function enp_errorMessage(obj, message) {
        // append the error message
        obj.append('<p class="enp-btn-error-message">'+message+'</p>');
    }

    function enp_processError(btn, btn_group, message) {
        // roll back count
        enp_rollBackCount(btn);

        // create error message
        enp_errorMessage(btn_group, message);

        // add disabled and error classes to the button
        btn.addClass('enp-btn--disabled enp-btn--error');
    }

    // client side message to please login
    function enp_pleaseLoginError(btn) {
        var enp_login_url = enp_button_params.enp_login_url;
        // if we already have an error message, destroy it
        if($('.enp-btn-error-message').length) {
            $('.enp-btn-error-message').remove();
        }

        var btns_group_wrap_id = $(btn).parent().parent().parent().attr('id');
        // append the button wrap id to the login url
        enp_login_url = enp_login_url+'%2F%23'+btns_group_wrap_id;
        // get the place to append the message
        var btn_group = $(btn).parent().parent();
        var message = 'You must be <a href="'+enp_login_url+'">logged in</a> to click this button. Please <a href="'+enp_login_url+'">log in</a> and try again.';
        enp_errorMessage(btn_group, message);
    }

    /*
    *   localStorage function
    */
    function enp_setlocalStorage(type, slug, id, operator) {
        // get the values (returns as JSON array)
        var values = enp_getLocalStorage(type, slug);

         // if we have an array, check to see if we're adding or subtracting
         // typeof returns object when it's an array or object
        if(typeof values === 'object' && values !== null) {
            // check if the value is in the array
            // if it isn't, it'll return -1
            var index = $.inArray(id, values);
            if(operator === '-' && index !== -1) {
                // remove the item
                values.splice(index, 1);
            } else if(operator === '+' && index === -1) {
                // add an item
                values.push(id);
            } else {
                // hmm... this shouldn't happen
                console.log('Something is not right. The operator is '+operator+', and the index is '+index+'.');
            }
        } else {
            // There aren't any values, we need to create the array
            values = [id];
        }

        // Store the value
        localStorage.setItem('enp_button_'+type+'_'+slug, JSON.stringify(values));
    }

    function enp_getLocalStorage(type, slug) {
        var values = localStorage.getItem('enp_button_'+type+'_'+slug);
        // turn it into an array
        values = JSON.parse(values);

        return values;
    }


    /*
    *   Generate our button message based on localStorage
    */
    function enp_UserClickedMessage(clicked_btn_names, btn_type) {

        var user_clicked_btns_text = '';
        var alt_name_text = '';

        if(clicked_btn_names.length) {
            user_clicked_btns_text = '<p class="enp-btn-hint enp-user-clicked-hint">';

            var alt_names = ["Important", "Thoughtful", "Useful"];
            var alt_names_matches = $.grep(alt_names, function(element) {
                return $.inArray(element, clicked_btn_names ) !== -1;
            });

            if(alt_names_matches.length) { // Important is found
                alt_name_text = 'This '+btn_type+' is '+enp_build_name_text(alt_names_matches)+' to you.';
                // remove it from the array by just grabbing the ones that don't match
                clicked_btn_names = clicked_btn_names.filter(function(obj) { return alt_names_matches.indexOf(obj) == -1; });
            }

            // check if the array is still not empty after potentially removing the alt names
            if(clicked_btn_names.length) {
                user_clicked_btns_text = user_clicked_btns_text + 'You ';

                user_clicked_btns_text = user_clicked_btns_text + enp_build_name_text(clicked_btn_names);

                user_clicked_btns_text = user_clicked_btns_text + ' this '+btn_type+'.';
            }

            if(user_clicked_btns_text && alt_name_text) {
                // add a space before the important text;
                alt_name_text = ' ' + alt_name_text;
            }

            user_clicked_btns_text =  user_clicked_btns_text + alt_name_text + '</p>';

        }

        return user_clicked_btns_text;
    }


    // Build the string with commas and 'and' if necessary
    function enp_build_name_text(names) {
        var names_count = names.length;
        var name_text = '';
        var j;

        for (i = 0; i < names.length; i++) {
            // figure out if we need a comma, 'and', or nothing
            j = i+1;

            // we're on the last one (or first one)
            if(j === names_count) {
                if(names_count > 2) {
                    name_text = name_text + 'and '+names[i];
                } else if(names_count > 1) {
                    name_text = name_text + 'and '+names[i];
                } else { // first and last (only one))
                    name_text = name_text + names[i];
                }
            } else if(j === 1) { // we're on the first one
                    if(names_count > 2) {
                        name_text = name_text + names[i]+', '; // first one, and more to come
                    } else {
                        name_text = name_text + names[i]+' '; // first one and only two
                    }
            } else { // we're not on the first or last, so put a comma in there
                name_text = name_text + names[i]+', ';
            }
        }

        return name_text;
    }


    function enp_formatNumber(val) {
        var formatted_count;
        var formatted_count_html;

        if(1000 <= val) {
            var format_symbol;
            var format_divide;

            if(1000000 <= val) {
                format_symbol = 'm';
                format_divide = 100000;
            } else {
                format_symbol = 'k';
                format_divide = 1000;
            }

            formatted_count = val/format_divide;
            formatted_count = Math.floor(formatted_count * 10) / 10; // get our decimal places (ie 2.5)
            formatted_count = enp_commaSeparateNumber(formatted_count);
            // removes .0 from end of number, if it's a .0
            // ex- 12.0 becomes 12
            //formatted_count = formatted_count + 0;
            formatted_count_html = formatted_count+'<span class="enp-btn-count-formatter">'+format_symbol+'</span>';
        } else {
            formatted_count = val;
            formatted_count_html = val;
        }

        return formatted_count_html;
    }

    function enp_commaSeparateNumber(val){
        while (/(\d+)(\d{3})/.test(val.toString())){
          val = val.toString().replace(/(\d+)(\d{3})/, '$1'+','+'$2');
        }
        return val;
    }

});
