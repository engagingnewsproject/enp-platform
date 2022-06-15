<?php
/**
 * Smash Balloon Custom Twitter Feeds Error Template
 *
 * @version 2.0 Custom Twitter Feeds by Smash Balloon
 *
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>
<div id="ctf" class="ctf" data-ctfshortcode="<?php echo $this->getShortCodeJSON() ?>">
	<div class="ctf-error">
		<div class="ctf-error-user"></div>
		<?php if ( current_user_can( 'manage_options' ) ): ?>
			<div class="ctf-error-admin">
				<?php if ( ! empty( $this->api_obj->api_error_no ) ): ?>
					<p>Unable to load Tweets</p>
					<a class="twitter-share-button" href="https://twitter.com/share" data-size="large" data-url="<?php echo get_the_permalink() ?>" data-text="Check out this website"></a>
					<?php if ( !empty( $this->feed_options['screenname'] ) ) : ?>
						<a class="twitter-follow-button" href="https://twitter.com/share" data-size="large" data-url="https://twitter.com/<?php echo $this->feed_options['screenname'] ?>" data-dnt="true">Follow</a>
					<?php endif; ?>
					<p><b>This message is only visible to admins:</b><br />
					An error has occurred with your feed.<br />
					<?php if ( $this->missing_credentials ): ?>
						There is a problem with your access token, access token secret, consumer token, or consumer secret<br />
					<?php endif; ?>
					<?php if ( isset( $this->errors['error_message'] ) ): ?>
						<?php echo $this->errors['error_message']; ?> <br />
					<?php endif; ?>
					The error response from the Twitter API is the following:<br />
					<code>Error number: <?php echo $this->api_obj->api_error_no  ?> <br />
					Message: <?php echo $this->api_obj->api_error_message ?> </code>
					<a href="https://smashballoon.com/custom-twitter-feeds/docs/errors/?utm_campaign=twitter-free&utm_source=frontend&utm_medium=errormessage" target="_blank" rel="noopener noreferrer">Click here to troubleshoot</a></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>