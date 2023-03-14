<?php
/**
 * Smash Balloon Custom Twitter Feeds Generic Header Template
 * Information about the hashtag or search
 *
 * @version 2.0.4 Custom Twitter Feeds by Smash Balloon
 *
 */
use TwitterFeed\CTF_Parse;
use TwitterFeed\CTF_Display_Elements;

$header_attr = CTF_Display_Elements::get_element_attribute( 'header-text', $feed_options );
?>
<div class="ctf-header ctf-header-type-text" <?php echo $header_attr ?>>
	<?php echo wp_kses_post( $feed_options['customheadertext'] ); ?>
</div>