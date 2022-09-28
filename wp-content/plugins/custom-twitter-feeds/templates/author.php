<?php
/**
 * Smash Balloon Custom Twitter Feeds Author Template
 * Information about the person tweeting, replying, or quoting
 *
 * @version 2.0 Custom Twitter Feeds by Smash Balloon
 *
 */
use TwitterFeed\CTF_Parse;
use TwitterFeed\CTF_Display_Elements;
use TwitterFeed\CTF_GDPR_Integrations;

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$author_display_name 	= CTF_Parse::get_display_author_name( $post );
$author_screen_name 	= CTF_Parse::get_author_screen_name( $post );
$avatar_src 			= CTF_Parse::get_avatar_url( $post, $feed_options );
$post_id 				= CTF_Parse::get_tweet_id( $post );
$utc_offset 			= CTF_Parse::get_utc_offset( $post );
$created_at 			= CTF_Parse::get_original_timestamp( $post );
$verified 				= CTF_Parse::get_verified( $post );

$author_attr 		= CTF_Display_Elements::get_element_attribute( 'author', $feed_options );
$avatar_attr 		= CTF_Display_Elements::get_element_attribute( 'avatar', $feed_options );
$author_text_attr 	= CTF_Display_Elements::get_element_attribute( 'author_text', $feed_options );
$date_attr 			= CTF_Display_Elements::get_element_attribute( 'date', $feed_options );
$logo_attr 			= CTF_Display_Elements::get_element_attribute( 'logo', $feed_options );
$retweeter_attr 	= CTF_Display_Elements::get_element_attribute( 'retweeter', $feed_options );

$created_at 		= CTF_Parse::get_original_timestamp( $post );
$date_text_attr 	= CTF_Display_Elements::get_post_date_attr($created_at, $feed_options );

if ( isset( $retweeter ) && ctf_show( 'retweeter', $feed_options ) ) :
	$retweeter_name = $retweeter['name'];
	$retweeter_screen_name = $retweeter['screen_name'];
	?>
	<div class="ctf-context" <?php echo $retweeter_attr ?>>
	    <a href="<?php echo esc_url( 'https://twitter.com/intent/user?screen_name=' . $retweeter_screen_name ) ?>" target="_blank" rel="nofollow noopener noreferrer" class="ctf-retweet-icon"><?php echo ctf_get_fa_el( 'fa-retweet' ) ?><span class="ctf-screenreader"><?php esc_html_e( 'Retweet on Twitter', 'custom-twitter-feeds' ); ?></span></a>
	    <a href="<?php echo esc_url( 'https://twitter.com/' . strtolower( $retweeter_screen_name ) ) ?>" target="_blank" rel="nofollow noopener noreferrer" class="ctf-retweet-text"><?php echo esc_html( $retweeter_name . ' ' . __( $feed_options['retweetedtext'], 'custom-twitter-feeds' ) )?></a>
	</div>
<?php endif; ?>

<?php if ( ctf_show( 'avatar', $feed_options ) || ctf_show( 'author', $feed_options ) || ctf_show( 'logo', $feed_options ) || ctf_show( 'date', $feed_options ) ) : ?>
	<div class="ctf-author-box">
		<div class="ctf-author-box-link">
	        <?php if ( ctf_show( 'author', $feed_options )  ) : ?>
				<?php if( ctf_show( 'avatar', $feed_options ) ): ?>
					<!--style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] . $feed_options['textcolor'] . '"-->
					<a href="https://twitter.com/<?php echo $author_screen_name ?>" class="ctf-author-avatar" target="_blank" rel="noopener noreferrer" <?php echo $avatar_attr  ?>>
						<?php if ( CTF_GDPR_Integrations::doing_gdpr( $feed_options ) ) : ?>
							<span data-avatar="<?php echo esc_url( CTF_Parse::get_avatar( $post ) ) ?>" data-alt="<?php echo $author_screen_name ?>">Avatar</span>
						<?php else: ?>
							<img src="<?php echo esc_url( $avatar_src ) ?>" alt="<?php echo $author_screen_name ?>" width="48" height="48">
						<?php endif; ?>
					</a>
				<?php endif;?>

				<?php if( ctf_show( 'author_text', $feed_options ) ): ?>
					<!--style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] . $feed_options['textcolor'] . '"-->
					<a href="https://twitter.com/<?php echo $author_screen_name ?>" target="_blank" rel="noopener noreferrer" class="ctf-author-name" <?php echo $author_text_attr; ?>><?php echo $author_display_name ?></a>
					<?php if( $verified == 1 ): ?>
						<span class="ctf-verified" <?php echo $author_text_attr; ?> ><?php echo ctf_get_fa_el( 'fa-check-circle' ) ?></span>
					<?php endif;?>
					<!--style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] . $feed_options['textcolor'] . '"-->
					<a href="https://twitter.com/<?php echo $author_screen_name ?>" class="ctf-author-screenname" target="_blank" rel="noopener noreferrer" <?php echo $author_text_attr; ?>>@<?php echo $author_screen_name  ?></a>
					<span class="ctf-screename-sep">&middot;</span>
				<?php endif;?>
	        <?php endif; ?>

			<?php if( ctf_show( 'date', $feed_options ) ): ?>
				<div class="ctf-tweet-meta" <?php echo $date_attr ?>>
					<!--style="' . $feed_options['datetextsize'] . $feed_options['datetextweight'] . $feed_options['textcolor'] . '"-->
					<a href="https://twitter.com/<?php echo $author_screen_name ?>/status/<?php echo $post_id  ?>" class="ctf-tweet-date" target="_blank" rel="noopener noreferrer" <?php echo $date_text_attr; ?>><?php echo ctf_get_formatted_date( $created_at, $feed_options, $utc_offset ) ?></a>
				</div>
			<?php endif; ?>
		</div>
	    <?php if ( ctf_show( 'logo', $feed_options ) ) : ?>
			<!--style="' . $feed_options['logosize'] . $feed_options['logocolor'] . '"-->
			<div class="ctf-corner-logo" <?php echo $logo_attr ?>>
				<?php echo ctf_get_fa_el( 'fa-twitter' ); ?>
			</div>
		<?php endif; ?>

	</div>
<?php endif; ?>
