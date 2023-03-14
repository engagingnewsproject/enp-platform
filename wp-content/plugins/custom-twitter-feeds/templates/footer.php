<?php
/**
 * Smash Balloon Custom Twitter Feeds Footer Template
 * Information about the person tweeting, replying, or quoting
 *
 * @version 2.0.4 Custom Twitter Feeds by Smash Balloon
 *
 */
use TwitterFeed\CTF_Display_Elements;

$loadmore_attr = CTF_Display_Elements::get_element_attribute( 'loadmore', $feed_options );
?>

<?php if (( filter_var($feed_options['showbutton'], FILTER_VALIDATE_BOOLEAN) == true) || ctf_doing_customizer( $feed_options )) : ?>
    <a href="javascript:void(0);" id="ctf-more" class="ctf-more" <?php echo $loadmore_attr ?>><span><?php echo esc_html( $feed_options['buttontext'] ) ?></span></a>
<?php endif; ?>

<?php if ($options['creditctf']):  ?>
	<div class="ctf-credit-link"><a href="https://smashballoon.com/custom-twitter-feeds" target="_blank" rel="noopener noreferrer"><?php echo ctf_get_fa_el( 'fa-twitter' ) ?>Custom Twitter Feeds Plugin</a></div>
<?php endif; ?>