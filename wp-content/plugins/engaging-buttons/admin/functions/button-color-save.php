<?php

/*
*   This is all just fallback code for if someone submits the form without javascript
*   It will create all the CSS to save to the database
*
*/
function enp_create_button_css($button_style = false) {
    $button_color = get_option('enp_button_color');
    $clicked_color = get_option('enp_button_color_clicked');
    $active_color = get_option('enp_button_color_active');
    $button_style = get_option('enp_button_style');

    if(empty($button_color) || $button_color === false) {
        // they don't want any custom CSS. Git on outta here.
        return false;
    }

    if(empty($clicked_color) || $clicked_color === false) {
        enp_hex_check_and_return_color($button_color, -0.14);
    }

    if(empty($active_color) || $active_color === false) {
        enp_hex_check_and_return_color($button_color, 0.15);
    }

    if($button_style === false || empty($button_style)) {
        $button_style = 'ghost';
    }

    if($button_style === 'ghost') {
        $css = '
                .enp-btns-wrap .enp-btn, .enp-btns-wrap .enp-btn--require-logged-in, .enp-btns-wrap .enp-btn--require-logged-in:active {
                	color: '.$button_color.';
                	border: 2px solid '.$button_color.';
                	background: transparent;
                }

                .enp-btns-wrap .enp-btn:hover, .enp-btns-wrap .enp-btn:focus, .enp-btns-wrap .enp-btn--user-clicked:focus {
                	border: 2px solid '.$button_color.';
                }

                .enp-btns-wrap .enp-btn:active, .enp-btns-wrap .enp-btn--click-wait, .enp-btns-wrap .enp-btn--click-wait:active, .enp-btns-wrap .enp-btn--click-wait:hover, .enp-btns-wrap .enp-btn--user-clicked, .enp-btns-wrap .enp-btn--increased {
                	color: #ffffff;
                }

                .enp-btns-wrap .enp-btn:active {
                	background: #ff11dd;
                	border: 2px solid #ff11dd;
                }

                .enp-btns-wrap .enp-btn--user-clicked, .enp-btns-wrap .enp-btn--increased, .enp-btns-wrap .enp-btn--click-wait, .enp-btns-wrap .enp-btn--click-wait:active, .enp-btns-wrap .enp-btn--click-wait:hover {
                	background: '.$button_color.';
                	border: 2px solid '.$button_color.';
                	color: #ffffff;
                }

                .enp-btns-wrap .enp-btn:active .enp-icon, .enp-btns-wrap .enp-btn--user-clicked .enp-icon, .enp-btns-wrap .enp-btn--user-clicked.enp-btn--click-wait .enp-icon, .enp-btns-wrap .enp-btn--click-wait .enp-icon, .enp-btns-wrap .enp-btn--click-wait:active .enp-icon, .enp-btns-wrap .enp-btn--click-wait:hover .enp-icon {
                	fill: #ffffff;
                }

                .enp-btns-wrap .enp-icon, .enp-btns-wrap .enp-btn--require-logged-in .enp-icon, .enp-btns-wrap .enp-btn--require-logged-in:hover .enp-icon, .enp-btns-wrap .enp-btn--require-logged-in:active .enp-icon {
                	fill: '.$button_color.';
                }
                ';

    } elseif ($button_style === 'detached-count') {
        $css = '.enp-btns-wrap .enp-btn__name {
                	background: '.$button_color.';
                }

                .enp-btns-wrap .enp-btn__count {
                	color: '.$button_color.';
                }

                .enp-btns-wrap .enp-btn:hover .enp-btn__name, .enp-btns-wrap .enp-btn--user-clicked .enp-btn__name, .enp-btns-wrap .enp-btn--click-wait .enp-btn__name, .enp-btns-wrap .enp-btn--click-wait:active .enp-btn__name, .enp-btns-wrap .enp-btn--click-wait:hover .enp-btn__name, .enp-btns-wrap .enp-btn--require-logged-in .enp-btn__name, .enp-btns-wrap .enp-btn--require-logged-in:hover .enp-btn__name, .enp-btns-wrap .enp-btn--require-logged-in:active .enp-btn__name {
                	background: '.$clicked_color.';
                }

                .enp-btns-wrap .enp-btn:hover .enp-btn__count, .enp-btns-wrap .enp-btn--user-clicked .enp-btn__count, .enp-btns-wrap .enp-btn--click-wait .enp-btn__count, .enp-btns-wrap .enp-btn--click-wait:active .enp-btn__count, .enp-btns-wrap .enp-btn--click-wait:hover .enp-btn__count, .enp-btns-wrap .enp-btn--require-logged-in .enp-btn__count, .enp-btns-wrap .enp-btn--require-logged-in:hover .enp-btn__count, .enp-btns-wrap .enp-btn--require-logged-in:active .enp-btn__count {
                	color: '.$clicked_color.';
                }

                .enp-btns-wrap .enp-btn:active .enp-btn__name {
                	background: '.$active_color.';
                }

                .enp-btns-wrap .enp-btn:active .enp-btn__count {
                	color: '.$active_color.';
                }
                ';

    } elseif ($button_style === 'plain-text-w-count-bg') {
        $css = '.enp-btns-wrap .enp-btn__name {
                	color: '.$button_color.';
                }

                .enp-btns-wrap .enp-btn__count {
                	background: '.$button_color.';
                }

                .enp-btns-wrap .enp-icon {
                	fill: '.$button_color.';
                }

                .enp-btns-wrap .enp-btn:hover .enp-btn__name, .enp-btns-wrap .enp-btn--user-clicked .enp-btn__name, .enp-btns-wrap .enp-btn--click-wait .enp-btn__name, .enp-btns-wrap .enp-btn--click-wait:active .enp-btn__name, .enp-btns-wrap .enp-btn--click-wait:hover .enp-btn__name, .enp-btns-wrap .enp-btn--require-logged-in .enp-btn__name, .enp-btns-wrap .enp-btn--require-logged-in:hover .enp-btn__name, .enp-btns-wrap .enp-btn--require-logged-in:active .enp-btn__name {
                	color: '.$clicked_color.';
                }

                .enp-btns-wrap .enp-btn:hover .enp-btn__count, .enp-btns-wrap .enp-btn--user-clicked .enp-btn__count, .enp-btns-wrap .enp-btn--click-wait .enp-btn__count, .enp-btns-wrap .enp-btn--click-wait:active .enp-btn__count, .enp-btns-wrap .enp-btn--click-wait:hover .enp-btn__count, .enp-btns-wrap .enp-btn--require-logged-in .enp-btn__count, .enp-btns-wrap .enp-btn--require-logged-in:hover .enp-btn__count, .enp-btns-wrap .enp-btn--require-logged-in:active .enp-btn__count {
                	background: '.$clicked_color.';
                }

                .enp-btns-wrap .enp-btn:hover .enp-icon, .enp-btns-wrap .enp-btn--user-clicked .enp-icon, .enp-btns-wrap .enp-btn--click-wait .enp-icon, .enp-btns-wrap .enp-btn--click-wait:active .enp-icon, .enp-btns-wrap .enp-btn--click-wait:hover .enp-icon, .enp-btns-wrap .enp-btn--require-logged-in .enp-icon, .enp-btns-wrap .enp-btn--require-logged-in:hover .enp-icon, .enp-btns-wrap .enp-btn--require-logged-in:active .enp-icon {
                	fill: '.$clicked_color.';
                }

                .enp-btns-wrap .enp-btn:active .enp-btn__name {
                	color: '.$active_color.';
                }

                .enp-btns-wrap .enp-btn:active .enp-btn__count {
                	background: '.$active_color.';
                }

                .enp-btns-wrap .enp-btn:active .enp-icon {
                	fill: '.$active_color.';
                }
                ';

    } else {
        // plain, block count, curve count
        $css = '.enp-btns-wrap .enp-btn {
                	background: '.$button_color.';
                }

                .enp-btns-wrap .enp-btn:hover, .enp-btns-wrap .enp-btn--user-clicked, .enp-btns-wrap .enp-btn--click-wait, .enp-btns-wrap .enp-btn--click-wait:active, .enp-btns-wrap .enp-btn--click-wait:hover, .enp-btns-wrap .enp-btn--require-logged-in, .enp-btns-wrap .enp-btn--require-logged-in:hover, .enp-btns-wrap .enp-btn--require-logged-in:active {
                	background: '.$clicked_color.';
                }

                .enp-btns-wrap .enp-btn:active {
                	background: '.$active_color.';
                }
                ';

        if($button_style === 'count-block-inverse') {
            // we have to add a few more in there
            $css .= '.enp-btns-wrap .enp-btn__count {
                    	color: '.$button_color.';
                    }

                    .enp-btns-wrap .enp-btn--user-clicked .enp-btn__count {
                    	color: '.$clicked_color.';
                    }
                    ';
        }
    }

    return $css;
}

/**
 * Lightens/darkens a given colour (hex format), returning the altered colour in hex format.7
 * @param str $hex Colour as hexadecimal (with or without hash);
 * @percent float $percent Decimal ( 0.2 = lighten by 20%(), -0.4 = darken by 40%() )
 * @return str Lightened/Darkend colour as hexadecimal (with hash);
 */
 // NOTE: This doesn't seem to work very consistently...
function enp_color_luminance( $hex, $percent ) {
	// validate hex string
	$hex = preg_replace( '/[^0-9a-f]/i', '', $hex );
	$new_hex = '#';

	if ( strlen( $hex ) < 6 ) {
		$hex = $hex[0] + $hex[0] + $hex[1] + $hex[1] + $hex[2] + $hex[2];
	}

	// convert to decimal and change luminosity
	for ($i = 0; $i < 3; $i++) {
		$dec = hexdec( substr( $hex, $i*2, 2 ) );
		$dec = min( max( 0, $dec + ($dec * $percent) ), 255 );
		$new_hex .= str_pad( dechex( $dec ) , 2, 0, STR_PAD_LEFT );
	}

	return $new_hex;
}

// validate that it's a valid HEX
function enp_validate_color($hex) {
    $valid_hex_check = false;
    // validate hex string
    $matches = null;
    $color = preg_match('/#([a-fA-F0-9]{3}){1,2}\\b/', $hex, $matches);

    if(!empty($matches)) {
        $valid_hex_check = true;
    }
    return $valid_hex_check;
}

/*
*   The $percent is used as a fallback if the field is empty
*    (it shouldn't be unless javascript is off)
*/
function enp_hex_check_and_return_color($value, $percent) {
    $hex = false;
    // validate the hex value
    if(enp_validate_color($value) === true) {
        $hex = $value;
    } else {
        // the value is invalid, so let's try to create a valid one
        // check to see if there's a main color or not
        $base_button_color = get_option('enp_button_color');
        // check to validate that color
        if(!empty($base_button_color) && enp_validate_color($base_button_color) === true) {
            // change the hex to darken/lighten
            $hex = enp_color_luminance( $base_button_color, $percent);
        }
    }
    return $hex;
}


?>
