<?php
/**
 * Smash Balloon Custom Twitter Feeds Link Box Template
 * Information about the person tweeting, replying, or quoting
 *
 * @version 2.0 Custom Twitter Feeds by Smash Balloon
 *
 */
use TwitterFeed\CTF_Parse;
use TwitterFeed\CTF_Display_Elements;

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$quoted_text 				= CTF_Display_Elements::post_text( $quoted, $feed_options );

$quoted_media_text 		= CTF_Display_Elements::get_quoted_media_text( $post, $feed_options );
$quoted_name 			= CTF_Parse::get_quoted_name( $quoted );
$quoted_verfied 		= CTF_Parse::get_quoted_verified( $quoted );
$quoted_screen_name 	= CTF_Parse::get_quoted_screen_name( $quoted );

?>
<!--style="' . $feed_options['quotedauthorsize'] . $feed_options['quotedauthorweight'] . $feed_options['textcolor'] . '"-->
<a href="https://twitter.com/<?php echo $quoted_screen_name ?>/status/<?php echo $quoted['id_str'] ?>" class="ctf-quoted-tweet" target="_blank" rel="noopener noreferrer">
	<span class="ctf-quoted-author-name"><?php echo $quoted_name  ?></span>
	<?php if ( $quoted_verfied == 1 ) : ?>
		<span class="ctf-quoted-verified"><?php echo ctf_get_fa_el( 'fa-check-circle' ) ?></span>
	<?php endif; ?>
	<span class="ctf-quoted-author-screenname">@<?php echo $quoted_screen_name ?></span>
	<!--style="' . $feed_options['tweettextsize'] . $feed_options['tweettextweight'] . $feed_options['textcolor'] . '"-->
	<p class="ctf-quoted-tweet-text"><?php echo nl2br( $quoted_text ) ?><?php echo $quoted_media_text ?></p>
</a>
