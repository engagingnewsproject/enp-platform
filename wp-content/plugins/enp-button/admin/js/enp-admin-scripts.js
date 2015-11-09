/**
 * scripts.js
 *
 * Admin Enp Button scripts
 */

jQuery( document ).ready( function( $ ) {

    //add/remove btn html
    var addBtnHtml = '<div class="enp-add-btn-wrap"><a class="enp-add-btn"><svg class="icon-add"><use xlink:href="#icon-add"></use></svg> Add Another Button</a></div>';
    var removeBtnHtml = '<a class="enp-remove-btn">Remove Button <svg class="icon-remove"><use xlink:href="#icon-remove"></use></svg></a>';

    var max_btns = enp_maxBtns();
    var total_btns = enp_totalBtns();

    // last button, so put the "Add Button button in there"
    if(total_btns < max_btns) {
        $(addBtnHtml).insertAfter('.enp-btn-table-wrap:last');
    }


    $('.enp-btn-form').each(function(i, table_obj) {
        var total_btns = enp_totalBtns();

        var add_enp_btns = '<tr class="btn-controls-row"><th></th><td class="btn-controls" data-button="'+i+'">'+ removeBtnHtml+'</td></tr>';
        // append to the table
        $(this).append(add_enp_btns);

        if(total_btns === 1) {
            // hide that beautiful remove button we just created
            $('.enp-remove-btn').hide();
        }
        // set the right display name
        var selected_btn_slug = $('.btn-slug-input:checked', table_obj).val()
        if(selected_btn_slug !== false){
            enp_setDisplayPopularName(selected_btn_slug ,table_obj);
        }

    });

    // show/hide the correct options
    enp_btnSlugVisibility();

    // Add button
    $(document).on('click', '.enp-add-btn', function(e){
        e.preventDefault();

        // check to see if there's already the max amount of buttons possible
        var max_btns = enp_maxBtns();
        var total_btns = enp_totalBtns();

        if(max_btns <= total_btns) {
            // Too many buttons. Cancel
            console.log('There are no more buttons to create. Max number of buttons reached.');
            return false;
        }

        var new_button = $('.enp-btn-table-wrap:eq(0)').clone();

        // clear the inputs
        $('input', new_button).prop( "checked", false );

        // get our indexes ready for replacing
        var prev_index = 0; // because we cloned the first copy
        var new_index = enp_totalBtns(); // because if there's one, our array item is 0
        var new_button_form = $('.enp-btn-form', new_button);
        // Reset Most clicked text
        enp_setDisplayPopularName(false, new_button);
        // pass the new value to the reindex function
        enp_reIndexForm(prev_index, new_index, new_button_form);

        // add the new, empty form onto the page
        $('.enp-btn-table-wrap:last').after(new_button);

        // reveal all remove buttons, if they're not already
        $('.enp-remove-btn').show();

        // check to see if we should allow the creation of another button
        var new_total_btns = total_btns+1
        if(max_btns <= new_total_btns) {
            $('.enp-add-btn').hide();
        }

        enp_btnSlugVisibility();

    });


    // Remove button
    $(document).on('click', '.enp-remove-btn', function(e){
        e.preventDefault();

        var total_btns = enp_totalBtns();

        if(total_btns === 1) {
            console.log('you cannot remove all buttons');
            return false;
        }

        var btn_number = $(this).parent().data('button');

        // unset the values before removing. Necessary for our show/hide slugs to work correctly
        $('.enp-btn-form[data-button="'+btn_number+'"] input').prop( "checked", false );

        // Remove the form from the clicked button
        $('.enp-btn-form[data-button="'+btn_number+'"]').parent().fadeOut('300', function() { $(this).remove(); });

        // reindex numbers after this one if it's not the last one
        if(btn_number !== (total_btns - 1)) {
            $('.enp-btn-form').each(function() {
                form_number = $(this).attr('data-button');

                // if the clicked button is less than the current form loop index,
                // then we need to reduce all the following forms by one
                if(btn_number < form_number) {
                    var new_index;
                    new_index = form_number - 1;

                    // reindex all the attributes
                    enp_reIndexForm(form_number, new_index, this);
                }
            });
        }

        // check how many buttons are left. If just one, then hide the remove button
        var new_total_btns = total_btns - 1;
        if(new_total_btns === 1) {
            $('.enp-remove-btn').hide();
        }

        // check to see if we should show the .enp-add-btn again
        var max_btns = enp_maxBtns();
        if(new_total_btns < max_btns) {
            $('.enp-add-btn').fadeIn();
        }

        enp_btnSlugVisibility();

    });


    // on change of slug inputs
    $( document ).on('change', ".btn-slug-input", function() {
        // show/hide the correct options
        enp_btnSlugVisibility();
        var parentForm = $(this).closest('.enp-btn-form');
        enp_setDisplayPopularName($(this).val(), parentForm);
    });


        // count how many buttons we have
    function enp_totalBtns() {
        var total_btns = $('.enp-btn-form').length;
        return total_btns;
    }

    // count how many button slugs are available
    function enp_maxBtns() {
        var max_btns = $('.btn-select-slug:eq(0) input').length;
        return max_btns;
    }


    // Reindex option
    function enp_reIndexForm(prev_index, new_index, form) {
        var prev_index_str = prev_index.toString();
        var new_index_str = new_index.toString();

        // reduce the index by one on the table data attribute
        $(form).attr('data-button', new_index);
        // reduce the index by one on the btn control data attribute
        $('.btn-controls', form).attr('data-button', new_index);

        $('input', form).each(function() {
            var input_name = $(this).attr('name');
            // replace the old value with the new value
            var new_input_name = input_name.replace('['+prev_index_str+']', '['+new_index_str+']');
            // set the new name as the new value
            $(this).attr('name', new_input_name);
        });
    }


    // an array of the current selected button slugs
    function enp_getSelectedBtnSlugs() {
        var selected_btns = [];

        $('.btn-select-slug').each(function() {
            selected_btns.push($('.btn-slug-input:checked', this).val());
        });

        return selected_btns;
    }


    // If a btn slug option is selected, remove it from the rest of the buttons
    // so users can't select the same button twice
    function enp_btnSlugVisibility() {
        // get current selections
        selected_btns = enp_getSelectedBtnSlugs();

        // show everything to "reset" it
        $('.btn-slug-input').show();
        $('.btn-slug-input').parent().show();

        // hide the selected options from all the other options
        for (i = 0; i < selected_btns.length; i++) {
            // hide the not checked ones
            $('.btn-slug-input-'+selected_btns[i]+':not(:checked)').hide();
            $('.btn-slug-input-'+selected_btns[i]+':not(:checked)').parent().hide();

            /* show the checked ones
            $('.btn-slug-input-'+selected_btns[i]+':checked').show();
            $('.btn-slug-input-'+selected_btns[i]+':checked').parent().show();*/

        }
    }


    // Replace the wording on popular button display to make it a little easier to understand
    function enp_setDisplayPopularName(btn_slug, table_obj) {

        if(btn_slug === 'respect') {
            displayName = 'Respected';
        } else if(btn_slug === 'important') {
            displayName = 'Important';
        } else if(btn_slug === 'recommend') {
            displayName = 'Recommended';
        } else {
            displayName = 'Clicked';
        }

        $('.most-clicked-name', table_obj).text(displayName);
    }

});
