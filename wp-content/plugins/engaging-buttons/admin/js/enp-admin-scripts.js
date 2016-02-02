/**
 * scripts.js
 *
 * Admin Enp Button scripts
 */

jQuery( document ).ready( function( $ ) {
    // Advanced CSS selections
    $('.advanced-css').hide();

    function showHideAdvancedCSSOptions(color) {
        if(color === undefined) {
            color = $('#enp_button_color').val();
        }
        // if the value isn't empty, let's show the CSS options
        var isOK = hexValidCheck(color);
        if(isOK === true) {
            $('#advanced-css-row').fadeIn();
        } else {
            $('#advanced-css-row').hide();
        }
    }
    showHideAdvancedCSSOptions();

    $('.advanced-css-control').click(function(e) {
        e.preventDefault();
        $(this).remove();
        $('.advanced-css').fadeIn();
    });

    $(".enp-css").focus(function() {
        $(this).select();
    });

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
        var selected_btn_slug = $('.btn-slug-input:checked', table_obj).val();
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
        var new_total_btns = total_btns+1;
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
        } else if(btn_slug === 'thoughtful') {
            displayName = 'Thoughtful';
        } else if(btn_slug === 'useful') {
            displayName = 'Useful';
        } else {
            displayName = 'Clicked';
        }

        $('.most-clicked-name', table_obj).text(displayName);
    }


    /*
    *
    *   Display button styles
    *
    */
    $( document ).on('change', ".btn-style-input", function() {
        // pop last class and remove it
        var new_class = $(this).val();
        enp_removeViewClass();
        $('.enp-btn-view').addClass('enp-btn-view-'+new_class);

    });

    function enp_removeViewClass(){
        $('.enp-btn-view').parent().filter(function (new_class) {
            var classes = $('.enp-btn-view').attr('class').split(' ');
            for (var i=0; i<classes.length; i++)
            {
                if (classes[i].slice(0,13) === 'enp-btn-view-')
                {
                    $('.enp-btn-view').removeClass(classes[i]);
                }
            }
        });
    }

    $( document ).on('change', ".btn-icon-input", function() {
        // if has class, remove it
        $('.enp-btns-wrap').toggleClass('enp-icon-state');
    });


    $('.enp-btn').click(function(e){
        if($(this).hasClass('enp-btn--click-wait')) {
            return false;
        }

        e.preventDefault();

        $(this).addClass('enp-btn--click-wait');

        $(this).toggleClass('enp-btn--user-clicked');
        $(this).toggleClass('enp-btn--user-has-not-clicked');

        var count = $('.enp-btn__count', this).text();

        if($(this).hasClass('enp-btn--user-has-not-clicked')) {
            // increase the count by one
            new_count = parseInt(count) - 1;
            // use the plus icon
            $(this).find("use").attr("xlink:href", "#enp-btn--user-has-not-clicked");
        } else {
            // decrease the count by one
            new_count = parseInt(count) + 1;
            // use the check icon
            $(this).find("use").attr("xlink:href", "#enp-btn--user-clicked");
        }
        // replace the count
        $('.enp-btn__count', this).text(new_count);

        // wait a little bit, then remove the class
        setTimeout(function() {
            console.log('waiting...');
            $('.enp-btn').removeClass('enp-btn--click-wait');
        }, 500);

    });

    // Colors

    // add the colorpicker
    $('.btn-color-input').wpColorPicker({
        change: function (event, ui) {
            var chosen_color = ui.color.toString();
            setBtnColors(chosen_color);
            showHideAdvancedCSSOptions(chosen_color);
        },
        clear: function () {
            setBtnColors();
            showHideAdvancedCSSOptions();
        }
    });

    $(document).on('keyup', '.btn-color-input', function(){
        setBtnColors();
    });

    $(document).on('change', '.btn-style-input', function(){
        setBtnColors();
    });

    function setBtnColors(color) {
        if(color === undefined) {
            color = $('.btn-color-input').val();
        }

        // check value of hex for valid color
        var isOK = hexValidCheck(color);
        var ghost_desktop_rules;
        var ghost_mobile_rules;

        if(isOK === true) {

            var btnStyle = $('.btn-style-input').val();
            // darker color
            var darkColor = ColorLuminance(color, -0.25);
            // write it to the input
            $('.btn-color-clicked-input').val(darkColor);
            // lighter color
            var lightColor = ColorLuminance(color, 0.15);
            // write it to the input
            $('.btn-color-active-input').val(lightColor);

            var rules;

            if(btnStyle === 'detached-count') {
                rules = [
                                ["#wpbody-content .enp-btn__name",
                                    ["background", color],
                                ],
                                ["#wpbody-content .enp-btn__count",
                                    ["color", color],
                                ],
                                ["#wpbody-content .enp-btn:hover .enp-btn__name, #wpbody-content .enp-btn--user-clicked .enp-btn__name, #wpbody-content .enp-btn--click-wait .enp-btn__name, #wpbody-content .enp-btn--click-wait:active .enp-btn__name, #wpbody-content .enp-btn--click-wait:hover .enp-btn__name, #wpbody-content .enp-btn--require-logged-in .enp-btn__name, #wpbody-content .enp-btn--require-logged-in:hover .enp-btn__name, #wpbody-content .enp-btn--require-logged-in:active .enp-btn__name",
                                    ["background", darkColor],
                                ],
                                ["#wpbody-content .enp-btn:hover .enp-btn__count, #wpbody-content .enp-btn--user-clicked .enp-btn__count, #wpbody-content .enp-btn--click-wait .enp-btn__count, #wpbody-content .enp-btn--click-wait:active .enp-btn__count, #wpbody-content .enp-btn--click-wait:hover .enp-btn__count, #wpbody-content .enp-btn--require-logged-in .enp-btn__count, #wpbody-content .enp-btn--require-logged-in:hover .enp-btn__count, #wpbody-content .enp-btn--require-logged-in:active .enp-btn__count",
                                    ["color", darkColor],
                                ],
                                ["#wpbody-content .enp-btn:active .enp-btn__name",
                                    ["background", lightColor],
                                ],
                                ["#wpbody-content .enp-btn:active .enp-btn__count",
                                    ["color", lightColor],
                                ]
                            ];
            } else if(btnStyle === 'plain-text-w-count-bg') {
                rules = [
                                ["#wpbody-content .enp-btn__name",
                                    ["color", color],
                                ],
                                ["#wpbody-content .enp-btn__count",
                                    ["background", color],
                                ],
                                ["#wpbody-content .enp-icon",
                                    ["fill", color],
                                ],
                                ["#wpbody-content .enp-btn:hover .enp-btn__name, #wpbody-content .enp-btn--user-clicked .enp-btn__name, #wpbody-content .enp-btn--click-wait .enp-btn__name, #wpbody-content .enp-btn--click-wait:active .enp-btn__name, #wpbody-content .enp-btn--click-wait:hover .enp-btn__name, #wpbody-content .enp-btn--require-logged-in .enp-btn__name, #wpbody-content .enp-btn--require-logged-in:hover .enp-btn__name, #wpbody-content .enp-btn--require-logged-in:active .enp-btn__name",
                                    ["color", darkColor],
                                ],
                                ["#wpbody-content .enp-btn:hover .enp-btn__count, #wpbody-content .enp-btn--user-clicked .enp-btn__count, #wpbody-content .enp-btn--click-wait .enp-btn__count, #wpbody-content .enp-btn--click-wait:active .enp-btn__count, #wpbody-content .enp-btn--click-wait:hover .enp-btn__count, #wpbody-content .enp-btn--require-logged-in .enp-btn__count, #wpbody-content .enp-btn--require-logged-in:hover .enp-btn__count, #wpbody-content .enp-btn--require-logged-in:active .enp-btn__count",
                                    ["background", darkColor],
                                ],
                                ["#wpbody-content .enp-btn:hover .enp-icon, #wpbody-content .enp-btn--user-clicked .enp-icon, #wpbody-content .enp-btn--click-wait .enp-icon, #wpbody-content .enp-btn--click-wait:active .enp-icon, #wpbody-content .enp-btn--click-wait:hover .enp-icon, #wpbody-content .enp-btn--require-logged-in .enp-icon, #wpbody-content .enp-btn--require-logged-in:hover .enp-icon, #wpbody-content .enp-btn--require-logged-in:active .enp-icon",
                                    ["fill", darkColor],
                                ],
                                ["#wpbody-content .enp-btn:active .enp-btn__name",
                                    ["color", lightColor],
                                ],
                                ["#wpbody-content .enp-btn:active .enp-btn__count",
                                    ["background", lightColor],
                                ],
                                ["#wpbody-content .enp-btn:active .enp-icon",
                                    ["fill", lightColor],
                                ]
                            ];
            } else if(btnStyle === 'ghost') {
                rules = [
                            ["#wpbody-content .enp-btn, #wpbody-content .enp-btn--require-logged-in, #wpbody-content .enp-btn--require-logged-in:active",
                                ["color", color],
                                ["border", "2px solid "+color],
                                ["background", "transparent"],
                            ],
                            ["#wpbody-content .enp-btn:hover, #wpbody-content .enp-btn:focus, #wpbody-content .enp-btn--user-clicked:focus",
                                ["border", "2px solid "+color],
                            ],
                            ["#wpbody-content .enp-btn:active, #wpbody-content .enp-btn--click-wait, #wpbody-content .enp-btn--click-wait:active, #wpbody-content .enp-btn--click-wait:hover, #wpbody-content .enp-btn--user-clicked, #wpbody-content .enp-btn--increased",
                                ["color", "#ffffff"],
                            ],
                            ["#wpbody-content .enp-btn:active",
                                ["background", lightColor],
                                ["border", "2px solid "+lightColor],
                            ],
                            ["#wpbody-content .enp-btn--user-clicked, #wpbody-content .enp-btn--increased, #wpbody-content .enp-btn--click-wait, #wpbody-content .enp-btn--click-wait:active, #wpbody-content .enp-btn--click-wait:hover",
                                ["background", color],
                                ["border", "2px solid "+color],
                                ["color", "#ffffff"],
                            ],
                            ["#wpbody-content .enp-btn:active .enp-icon, #wpbody-content .enp-btn--user-clicked .enp-icon, #wpbody-content .enp-btn--user-clicked.enp-btn--click-wait .enp-icon, #wpbody-content .enp-btn--click-wait .enp-icon, #wpbody-content .enp-btn--click-wait:active .enp-icon, #wpbody-content .enp-btn--click-wait:hover .enp-icon",
                                ["fill", "#ffffff"],
                            ],
                            ["#wpbody-content .enp-icon, #wpbody-content .enp-btn--require-logged-in .enp-icon, #wpbody-content .enp-btn--require-logged-in:hover .enp-icon, #wpbody-content .enp-btn--require-logged-in:active .enp-icon",
                                ["fill", color],
                            ],
                        ];
            } else {
                rules = [
                                ["#wpbody-content .enp-btn",
                                    ["background", color],
                                ],
                                ["#wpbody-content .enp-btn:hover, #wpbody-content .enp-btn--user-clicked, #wpbody-content .enp-btn--click-wait, #wpbody-content .enp-btn--click-wait:active, #wpbody-content .enp-btn--click-wait:hover, #wpbody-content .enp-btn--require-logged-in, #wpbody-content .enp-btn--require-logged-in:hover, #wpbody-content .enp-btn--require-logged-in:active",
                                    ["background", darkColor],
                                ],
                                ["#wpbody-content .enp-btn:active",
                                    ["background", lightColor],
                                ]
                            ];
                if (btnStyle === 'count-block-inverse') {
                    // append one more rule
                    rules.push(
                                ["#wpbody-content .enp-btn__count",
                                    ["color", color],
                                ],
                                ["#wpbody-content .enp-btn--user-clicked .enp-btn__count",
                                    ["color", darkColor],
                                ]
                            );
                }
            }
            // delete the existing dynamic sheet
            deleteSheet();
            // create a new one
            var sheet = createSheet();
            // add the styles to the newly created sheet
            addStylesheetRules(sheet, rules);

            // clear the textarea
            $('#enp-css').val('');

            var css_rules = textStylesheetRules(rules);

            // add the rules to a textarea
            $('#enp-css').val(css_rules);

        } else if(color === '') {
            // delete the existing dynamic sheet
            deleteSheet();
            // clear the textarea
            $('#enp-css').val('');
        }
    }

    setBtnColors();

    function deleteSheet() {
        if($('style[title="btnColor"]').length) {
            $('style[title="btnColor"]').remove();
        }

    }

    function createSheet() {
    	// Create the <style> tag
    	var style = document.createElement("style");

    	style.setAttribute("title", "btnColor");

    	// WebKit hack :(
    	style.appendChild(document.createTextNode(""));

    	// Add the <style> element to the page
    	document.head.appendChild(style);

    	return style.sheet;
    }

    /*
    addStylesheetRules([
      ['h2', // Also accepts a second argument as an array of arrays instead
        ['color', 'red'],
        ['background-color', 'green', true] // 'true' for !important rules
      ],
      ['.myClass',
        ['background-color', 'yellow']
      ]
    ]);
    */
    function addStylesheetRules (styleSheet, rules) {

      for (var i = 0, rl = rules.length; i < rl; i++) {
        var j = 1, rule = rules[i], selector = rules[i][0], propStr = '';
        // If the second argument of a rule is an array of arrays, correct our variables.
        if (Object.prototype.toString.call(rule[1][0]) === '[object Array]') {
          rule = rule[1];
          j = 0;
        }

        for (var pl = rule.length; j < pl; j++) {
          var prop = rule[j];
          propStr += prop[0] + ':' + prop[1] + (prop[2] ? ' !important' : '') + ';\n';
        }

        // Insert CSS Rule
        styleSheet.insertRule(selector + '{' + propStr + '}', styleSheet.cssRules.length);
      }
  }

  function textStylesheetRules(rules) {
    var sheet_rules = '';

    for (var i = 0, rl = rules.length; i < rl; i++) {
      var j = 1, rule = rules[i], selector = rules[i][0], propStr = '\n';
      // replaces all instances of #wpbody-content to body .enp-btns-wrap
      selector = selector.replace(/#wpbody-content/g, "body .enp-btns-wrap");

      // If the second argument of a rule is an array of arrays, correct our variables.
      if (Object.prototype.toString.call(rule[1][0]) === '[object Array]') {
        rule = rule[1];
        j = 0;
      }

      for (var pl = rule.length; j < pl; j++) {
        var prop = rule[j];
        propStr += '\t'+prop[0] + ': ' + prop[1] + (prop[2] ? ' !important' : '') + ';\n';
      }

      //
      // Insert CSS Rule
      sheet_rules += selector + ' {' + propStr + '}\n\n';
    }
    return sheet_rules;
}


  function ColorLuminance(hex, lum) {
    	// validate hex string
    	hex = String(hex).replace(/[^0-9a-f]/gi, '');
    	if (hex.length < 6) {
    		hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    	}
    	lum = lum || 0;

    	// convert to decimal and change luminosity
    	var rgb = "#", c, i;
    	for (i = 0; i < 3; i++) {
    		c = parseInt(hex.substr(i*2,2), 16);
    		c = Math.round(Math.min(Math.max(0, c + (c * lum)), 255)).toString(16);
    		rgb += ("00"+c).substr(c.length);
    	}

    	return rgb;
    }

    function hexValidCheck(hex) {
        return /(^#[0-9A-F]{6}$)|(^#[0-9A-F]{3}$)/i.test(hex);
    }

});
