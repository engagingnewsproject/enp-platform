<?php
/**
 * Smash Balloon Custom Twitter Feeds Header Generic Template
 * Information about the person tweeting, replying, or quoting
 *
 * @version 2.0.4 Custom Twitter Feeds by Smash Balloon
 *
 */
use TwitterFeed\CTF_Parse;
use TwitterFeed\CTF_Display_Elements;

$header_text = CTF_Parse::get_generic_header_text( $feed_options );
$header_url = CTF_Parse::get_generic_header_url( $feed_options );
$header_attr = CTF_Display_Elements::get_element_attribute( 'header', $feed_options );
?>

<div class="ctf-header ctf-header-type-generic" <?php echo $header_attr ?>>
	<a href="<?php echo esc_url( 'https://twitter.com/' . $header_url ) ?>" target="_blank" rel="noopener noreferrer" class="ctf-header-link">
		<div class="ctf-header-text">
			<p class="ctf-header-no-bio"><?php echo wp_kses_post( $header_text ); ?></p>
		</div>
		<div class="ctf-header-img">
			<div class="ctf-header-generic-icon">
				<?php echo ctf_get_fa_el( 'fa-twitter' ) ?>
			</div>
		</div>
	</a>
</div>