<?php

    require_once('class.csstidy.php');
    // get the posted CSS
    $original_css = file_get_contents('unsanitized.css');
    // set our new css to be optimized
    $css = $original_css;
    // open the CSS tidy class
    $csstidy = new csstidy();
    $csstidy->optimise = $css;
    // don't change the case of the class/id names
	$csstidy->set_cfg( 'case_properties',            false );
    $css = preg_replace( '/\\\\([0-9a-fA-F]{4})/', '\\\\\\\\$1', $prev = $css );
	// prevent content: '\3434' from turning into '\\3434'
	$css = str_replace( array( '\'\\\\', '"\\\\' ), array( '\'\\', '"\\' ), $css );

	$css = strip_tags( $css );

    $csstidy->parse( $css );
    $css = $csstidy->print->plain();

    // save optimized css
    // $css;

    // save original css for output back in the css editor
    // $original_css;
?>
