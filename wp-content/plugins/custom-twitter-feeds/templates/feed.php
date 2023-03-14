<?php
/**
 * Smash Balloon Custom Twitter Feeds Container Template
 * Information about the person tweeting, replying, or quoting
 *
 * @version 2.0.4 Custom Twitter Feeds by Smash Balloon
 *
 */
use TwitterFeed\CTF_Display_Elements;
?>

<!-- Custom Twitter Feeds by Smash Balloon -->
<div id="ctf" <?php echo $ctf_feed_classes ?>  data-ctfshortcode="<?php echo $this->getShortCodeJSON() ?>"  <?php echo $ctf_main_atts ?> data-ctfneeded="<?php echo esc_attr( $ctf_data_needed ) ?>">
	<?php
	$showheader = ($feed_options['showheader'] === 'on' || $feed_options['showheader'] === 'true' || $feed_options['showheader'] === true);

	if ($showheader || ctf_doing_customizer( $feed_options )) :
        	CTF_Display_Elements::display_header( $feed_options );
    	endif;
    ?>
    <div class="ctf-tweets">
   		<?php $this->tweet_loop( $tweet_set, $feed_options, $is_pagination ); ?>
    </div>
    <?php
    include ctf_get_feed_template_part( 'footer', $feed_options );

    /**
     * Things to add before the closing "div" tag for the main feed element. Several
     * features rely on this hook such as local images and some error messages
     *
     * @param object CTFFeedPro
     * @param string $feed_id
     *
     * @since 1.8/1.13
     */
    do_action( 'ctf_before_feed_end', $this, $feed_id ); ?>

</div>
