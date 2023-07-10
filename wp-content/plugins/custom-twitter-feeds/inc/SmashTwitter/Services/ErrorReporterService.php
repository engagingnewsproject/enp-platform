<?php
/**
 * Class ErrorReporterService
 *
 * Tracks errors and creates messages.
 *
 * @since 2.2.2
 */
namespace TwitterFeed\SmashTwitter\Services;

class ErrorReporterService {

	public function init_hooks() {
		add_action( 'ctf_before_feed_start', array( $this, 'maybe_add_error_html' ), 10, 1 );
		add_action( 'ctf_before_feed_start', array( $this, 'maybe_show_visitor_notice' ), 10, 1 );

	}

	public function maybe_add_error_html( $ctf_feed ) {
		if ( ! ctf_current_user_can( 'manage_twitter_feed_options' ) ) {
			return;
		}

		if ( empty( $ctf_feed->error_report ) ) {
			return;
		}

		$maybe_critical = $ctf_feed->error_report->get_critical_error();
		if ( empty( $maybe_critical ) ) {
			return;
		}
        ?>
		<div class="ctf-error ctf_smash_error">
			<div class="ctf-error-user">
				<div class="ctf-error-admin">
					<strong>This message is only visible to admins:</strong>
					<p>
						<?php echo wp_kses_post( $maybe_critical['message'] ) ?>
					</p>
					<p>
						<?php echo wp_kses_post( $maybe_critical['directions'] ) ?>
					</p>
				</div>
			</div>
		</div>
<?php

	}

	function maybe_show_visitor_notice( $ctf_feed ) {
		if ( ! empty( $ctf_feed->tweet_set ) ) {
            if ( is_array( $ctf_feed->tweet_set ) && is_array( $ctf_feed->tweet_set[0] ) ) {
	            return;
            }
		}
		if ( ctf_current_user_can( 'manage_twitter_feed_options' ) ) {
			return;
		}
		?>
        <div id="ctf" class="ctf">
            <div class="ctf-error">
                <div class="ctf-error-user">
					<?php esc_html_e( 'Twitter feed is not available at the moment.', 'custom-twitter-feeds' ) ?>
                </div>
            </div>
        </div>
		<?php
	}
}