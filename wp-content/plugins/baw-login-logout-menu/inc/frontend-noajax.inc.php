<?php
if( !defined( 'ABSPATH' ) )
	die( 'Cheatin\' uh?' );

/* Used to return the correct title for the double login/logout menu item */
function bawllm_loginout_title( $title ) {
	$titles = explode( '|', $title );
	if ( ! is_user_logged_in() ) {
		return esc_html( isset( $titles[0] ) ? $titles[0] : $title );
	} else {
		return esc_html( isset( $titles[1] ) ? $titles[1] : $title );
	}
}

/* The main code, this replace the #keyword# by the correct links with nonce ect */
add_filter( 'wp_setup_nav_menu_item', 'bawllm_setup_nav_menu_item' );
function bawllm_setup_nav_menu_item( $item ) {
	global $pagenow;
	if ( $pagenow != 'nav-menus.php' && ! defined( 'DOING_AJAX' ) && isset( $item->url ) && strstr( $item->url, '#baw' ) != '' ) {
		$item_url = substr( $item->url, 0, strpos( $item->url, '#', 1 ) ) . '#';
		$item_redirect = str_replace( $item_url, '', $item->url );
		if( $item_redirect == '%actualpage%') {
			$item_redirect = $_SERVER['REQUEST_URI'];
		}
		switch ( $item_url ) {
			case '#bawloginout#' : 	
									$item_redirect = explode( '|', $item_redirect );
									if ( count( $item_redirect ) != 2 ) {
										$item_redirect[1] = $item_redirect[0];
									}
									for ( $i = 0; $i <= 1; $i++ ) {
										if ( '%actualpage%' == $item_redirect[ $i ] ) {
											$item_redirect[ $i ] = $_SERVER['REQUEST_URI'];
										}
									}
									$item->url = is_user_logged_in() ? wp_logout_url( $item_redirect[1] ) : wp_login_url( $item_redirect[0] );
									$item->title = bawllm_loginout_title( $item->title ) ; break;
			case '#bawlogin#' : 	$item->url = wp_login_url( $item_redirect ); break;
			case '#bawlogout#' : 	$item->url = wp_logout_url( $item_redirect ); break;
			case '#bawregister#' : 	if( is_user_logged_in() ) {
										$item->title = '#bawregister#'; 
									} else {
										$item->url = site_url( 'wp-login.php?action=register', 'login' );
									}
									$item = apply_filters( 'bawregister_item', $item );
									break;
		}
		$item->url = esc_url( $item->url );
	}
	return $item;
}

add_filter( 'wp_nav_menu_objects', 'bawllm_wp_nav_menu_objects' );
function bawllm_wp_nav_menu_objects( $sorted_menu_items ) {
	foreach ( $sorted_menu_items as $k => $item ) {
		if ( $item->title==$item->url && '#bawregister#' == $item->title ) {
			unset( $sorted_menu_items[ $k ] );
		}
	}
	return $sorted_menu_items;
}

/* [login] shortcode */
add_shortcode( 'login', 'bawllm_shortcode_login' );
function bawllm_shortcode_login( $atts, $content = null ) {
	$atts = shortcode_atts(array(
		"edit_tag" => "",
		"redirect" => esc_url( $_SERVER['REQUEST_URI'] )
	), $atts, 'login' );

	$edit_tag = esc_html( strip_tags( $atts['edit_tag'] ) );
	$href = wp_login_url( $atts['redirect'] );
	$content = $content != '' ? $content : __( 'Log In' );

	return '<a href="' . esc_url( $href ) . '"' . $atts['edit_tag'] . '>' . $content . '</a>';
}

/* [loginout] shortcode */
add_shortcode( 'loginout', 'bawllm_shortcode_loginout' );
function bawllm_shortcode_loginout( $atts, $content = null ) {
	$atts = shortcode_atts(array(
		"edit_tag" => "",
		"redirect" => esc_url( $_SERVER['REQUEST_URI'] )
	), $atts, 'loginout' );

	$edit_tag = strip_tags( $atts['edit_tag'] );
	$href = is_user_logged_in() ? wp_logout_url( $atts['redirect'] ) : wp_login_url( $atts['redirect'] );
	if ( $content && strstr( $content, '|' ) != '' ) { // the "|" char is used to split titles
		$content = explode( '|', $content );
		$content = is_user_logged_in() ? $content[1] : $content[0];
	} else {
		$content = is_user_logged_in() ? __( 'Logout' ) : __( 'Log In' );
	}

	return '<a href="' . esc_url( $href ) . '"' . $atts['edit_tag'] . '>' . $content . '</a>';
}

/* [logout] shortcode */
add_shortcode( 'logout', 'bawllm_shortcode_logout' );
function bawllm_shortcode_logout( $atts, $content = null ) {
	$atts = shortcode_atts(array(
		"edit_tag" => "",
		"redirect" => esc_url( $_SERVER['REQUEST_URI'] )
	), $atts, 'logout' );

	$href = wp_logout_url( $atts['redirect'] );
	$edit_tag = esc_html( strip_tags( $atts['edit_tag'] ) );
	$content = $content != '' ? $content : __( 'Logout' );

	return '<a href="' . esc_url( $href ) . '"' . $atts['edit_tag'] . '>' . $content . '</a>';
}

/* [register] shortcode */
add_shortcode( 'register', 'bawllm_shortcode_register' );
function bawllm_shortcode_register( $atts, $content = null ) {
	if( is_user_logged_in() ) {
		return '';
	}
	$href = site_url( 'wp-login.php?action=register', 'login' );
	$content = $content != '' ? $content : __( 'Register' );
	$link = '<a href="' . $href. '">' . $content . '</a>';
	return $link;
}