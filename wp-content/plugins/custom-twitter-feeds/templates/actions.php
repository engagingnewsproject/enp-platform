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
use TwitterFeed\CTF_GDPR_Integrations;

$post_id  					= CTF_Parse::get_post_id( $post );
$author_screen_name 		= CTF_Parse::get_author_screen_name( $post );
$retweet_count 				= CTF_Parse::get_retweet_count( $post ) > 0 ? CTF_Parse::get_retweet_count( $post ) : '';
$favorite_count 			= CTF_Parse::get_favorite_count( $post ) > 0 ? CTF_Parse::get_favorite_count( $post ) : '';
$actions_attr 				= CTF_Display_Elements::get_element_attribute( 'actions', $feed_options );
$viewtwitterlink_attr 		= CTF_Display_Elements::get_element_attribute( 'viewtwitterlink', $feed_options );
$viewtwitterlink_text_attr 	= CTF_Display_Elements::get_element_attribute( 'viewtwitterlink_text', $feed_options );
$display_action_links 		= CTF_Display_Elements::display_action_links( $feed_options );
$tweet_action_end_url = $post_id . '&related=' . $author_screen_name;


// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>

<?php if ( $display_action_links ) : ?>
	<div class="ctf-tweet-actions" <?php echo $actions_attr; ?>>
		<?php if ( ctf_show( 'actions', $feed_options ) ) : ?>

		<!--style="' . $feed_options['iconsize'] . $feed_options['iconcolor'] . '"-->
		<a href="<?php echo esc_url( 'https://twitter.com/intent/tweet?in_reply_to=' . $tweet_action_end_url ) ?>" class="ctf-reply" target="_blank" rel="noopener noreferrer">
			<?php echo ctf_get_fa_el( 'fa-reply' ) ?>
			<span class="ctf-screenreader"><?php _e( 'Reply on Twitter', 'custom-twitter-feeds' ); ?> <?php echo esc_attr( $post_id )?></span>
		</a>

		<!--style="' . $feed_options['iconsize'] . $feed_options['iconcolor'] . '"-->
		<a href="<?php echo esc_url( 'https://twitter.com/intent/retweet?tweet_id=' . $tweet_action_end_url ) ?>" class="ctf-retweet" target="_blank" rel="noopener noreferrer"><?php echo ctf_get_fa_el( 'fa-retweet' ) ?>
			<span class="ctf-screenreader"><?php _e( 'Retweet on Twitter', 'custom-twitter-feeds' ); ?> <?php echo esc_attr( $post_id ) ?></span>
			<span class="ctf-action-count ctf-retweet-count"><?php echo $retweet_count; ?></span>
		</a>

		<!--style="' . $feed_options['iconsize'] . $feed_options['iconcolor'] . '"-->
		<a href="<?php echo esc_url( 'https://twitter.com/intent/like?tweet_id=' . $tweet_action_end_url ) ?>" class="ctf-like" target="_blank" rel="nofollow noopener noreferrer">
			<?php echo ctf_get_fa_el( 'fa-heart' ) ?>
			<span class="ctf-screenreader"><?php _e( 'Like on Twitter', 'custom-twitter-feeds' ); ?> <?php echo esc_attr( $post_id ) ?></span>
			<span class="ctf-action-count ctf-favorite-count"><?php echo $favorite_count; ?></span>
		</a>
		<?php endif; ?>

		<?php if (( isset($feed_options['viewtwitterlink']) && $feed_options['viewtwitterlink'] == true) || ctf_doing_customizer( $feed_options) ) : ?>
			<a href="<?php echo esc_url( 'https://twitter.com/' . $author_screen_name . '/status/' .$post_id ) ?>" class="ctf-twitterlink" target="_blank" rel="nofollow noopener noreferrer" <?php echo $viewtwitterlink_attr ?>>
				<span <?php echo $viewtwitterlink_text_attr ?>><?php echo esc_html( $feed_options['twitterlinktext'] ) ?></span>
				<span class="ctf-screenreader"><?php echo esc_attr( $post_id ) ?></span>
			</a>
		<?php endif; ?>
	</div>
<?php endif; ?>
