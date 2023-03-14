<?php
/**
 * Smash Balloon Custom Twitter Feeds Item Template
 * Information about the person tweeting, replying, or quoting
 *
 * @version 2.0.4 Custom Twitter Feeds by Smash Balloon
 *
 */
use TwitterFeed\CTF_Parse;
use TwitterFeed\CTF_Display_Elements;

$post 				= CTF_Parse::get_post( $tweet_set[$i] );
$tweet_classes 		= CTF_Display_Elements::get_item_classes( $tweet_set, $feed_options, $i);
$num_media 			= false;
$retweet_data_att 	= CTF_Display_Elements::get_retweet_attr( $post, $this->check_for_duplicates );
$author_screen_name = CTF_Parse::get_author_screen_name( $post );
$post_id  			= CTF_Parse::get_post_id( $post );
$post_media_text 	= CTF_Display_Elements::get_post_media_text( $tweet_set[$i],$feed_options );
$post_media_count 	= CTF_Display_Elements::get_post_media_text( $tweet_set[$i],$feed_options, 'media_count' );
$multi_class		= ( $post_media_count > 1 ) ? ' ctf-multi-media-icon' : '';
$post_text 			= CTF_Display_Elements::post_text( $post, $feed_options );

$quoted 			= CTF_Parse::get_quoted_tc( $post );
$quoted_media 		= CTF_Parse::get_quoted_media( $quoted, $num_media );
$post_text_attr 	= CTF_Display_Elements::get_post_text_attr( $post_text, $feed_options, $post_id );
$text_and_link_attr = CTF_Display_Elements::get_element_attribute( 'text_and_link', $feed_options );
$text_no_link_attr 	= CTF_Display_Elements::get_element_attribute( 'text_no_link', $feed_options );
?>

<div <?php echo $tweet_classes ?> id="<?php echo esc_attr( $post_id ); ?>" <?php echo $retweet_data_att ?>>

	<?php include ctf_get_feed_template_part( 'author', $feed_options ); ?>
	<div class="ctf-tweet-content">
		<?php
			if (ctf_show( 'text', $feed_options )) :
				CTF_Display_Elements::get_post_text( $feed_options, $post_text, $post_id, $author_screen_name, $post_media_text );
			endif;
		?>
	</div>

	<?php
		if (ctf_show( 'linkbox', $feed_options ) && isset( $quoted )) {
			include ctf_get_feed_template_part( 'linkbox', $feed_options );
		}
	?>
	<?php include ctf_get_feed_template_part( 'actions', $feed_options ); ?>
</div>